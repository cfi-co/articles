<?php
/**
 * CFI.co Awards — transparency export.
 *
 * Run via:  php8.2 /usr/local/bin/wp eval-file scripts/export.php --allow-root \
 *               --path=/var/customers/webs/marten/cfi.co/awards
 *
 * Emits, for every PUBLISHED award announcement (post_type=post, status=publish):
 *   announcements/<year>/<ID>-<slug>.json   exact machine record + content_sha256
 *   announcements/<year>/<ID>-<slug>.md     human-readable view (verbatim HTML body)
 *
 * Design rules that protect the "we never modify announcements" guarantee:
 *  - Body is the RAW stored post_content, byte-for-byte (no the_content filters,
 *    no HTML->MD conversion). $wpdb is used, not get_post(), to avoid filters.
 *  - Volatile internal postmeta (_edit_lock, quadrum_post_views_count, Yoast
 *    caches, ...) is deliberately NOT exported — it changes constantly and would
 *    manufacture fake "modification" commits.
 *  - Curation/display-only categories (FRONT, FEATURED*, approval, x-*, ...)
 *    rotate by design for the homepage and are excluded; only substantive
 *    sector/region/award categories are recorded, so re-exports stay stable.
 *  - The internal WP username is NOT exposed; author is a fixed editorial label.
 */

/**
 * cfi-webtext-1: the published-side half of the contribution provenance rule. Required here
 * rather than inlined so that scripts/cfi-textnorm-conformance.php tests the SAME code the
 * export runs - two copies of a hashing rule is the defect this whole mechanism exists to
 * avoid. __DIR__ is used because sync.sh invokes this file through `wp eval-file`, which
 * includes it, so the working directory is the repo root rather than scripts/.
 */
require_once __DIR__ . '/cfi-textnorm.php';

if (!defined('ABSPATH')) { fwrite(STDERR, "Must run via wp eval-file\n"); exit(1); }

global $wpdb;

$REPO   = dirname(__DIR__);
$OUTDIR = $REPO . '/articles';
$PLAN   = $REPO . '/scripts/.commit-plan';   // consumed by commit.sh
$US     = "\x1f";                            // field separator (unit separator)

$EDITORIAL_AUTHOR = 'CFI.co Editorial';

// Byline rule (2026-08-14). Until now `author` was this constant on EVERY record — all
// 2,796 of them — so the field carried no information at all, and a named contributor's
// work was recorded as house editorial. The rule: a house/automation login means unsigned
// house copy and keeps $EDITORIAL_AUTHOR; any other WordPress user is a real person and is
// named. These three logins are the house accounts (the same list cfi-author-schema.php
// works from); 2,698 of 2,796 posts sit on them.
$HOUSE_LOGINS = array('marten', 'CFI', 'crm');

// RETROSPECTIVE as of 2026-08-15, on Anthony's ruling: "Correct the data. Do not rewrite
// the prose. One append-only pass: author -> the byline; correction_status set;
// content_sha256 unchanged if HTML is unchanged; new record_sha256 per record."
// An empty value applies the rule to the whole corpus; a date would apply it only from
// that date. content_html is never touched, so content_sha256 does not move.
//
// !! WHAT THIS DOES NOT REACH. It can only name an author WordPress already knows about,
// i.e. posts whose post_author is a real account (~98). The ~274 house-attributed
// contributed pieces on the known-open register are posted under `marten`/`CFI` with the
// real byline living only in the BODY MARKUP (`<strong>Author:</strong>` caption, or the
// "About the Author(s)" bio block). Those cannot be derived from post_author and are NOT
// fixed here — see auto-memory contributor-gate-open-aug13-2026. Do not assume a clean
// run means the 274 are done.
const BYLINE_RULE_FROM = '';   // '' = apply to all records (Anthony's ruling, 15 Aug 2026)

// Machine-readable licence identifier stamped into every record (schema v2.2,
// 2026-07-08). Canonical text: LICENCE.md / https://cfi.co/licence/oaal-1.0
$LICENCE_ID = 'CFI-OAAL-1.0';

// Date systematic independence labelling began. Drives index.jsonl's
// independence_basis. Evidence for the date, re-checkable at any time: the earliest
// commercially_supported record is published 2025-11-09, and no record published
// before it carries a sponsorship label of any kind. Do NOT move this date without
// re-running that check — it is the difference between "we assessed this" and "we
// inherited this", on 2,695 records.
const INDEPENDENCE_LABELLING_FROM = '2025-11-09';

// Default content_class when no more-specific signal matches. Articles repo =
// editorial_analysis; the awards repo overrides this to 'award_rationale'.
$DEFAULT_CONTENT_CLASS = 'editorial_analysis';

// Category slug -> content_class (documented heuristic; sponsored flag wins).
$CONTENT_CLASS_BY_SLUG = array(
    'cfi-co-meets' => 'interview',
    'columnists'   => 'opinion_column',
    'reviews'      => 'review',
);

// Display/curation categories that rotate by design — excluded for stability.
$EXCLUDE_CAT_SLUGS = array(
    // homepage curation buckets (rotate by design)
    'front', 'featured', 'editors-picks', 'popular', 'must-reads',
    'editors-10', 'the-editors-list', 'hidden-gems',
    // workflow / junk / uncategorised
    'approval', 'uncategorized', '4673',
    // navigation / menu helpers + awards-site cross-links
    'menu', 'lifestyle-menu', 'projects-menu', 'middle-east-menu',
    'awards-africa', 'awards-africa-featured', 'awards-asia-pacific',
    'awards-awards', 'awards-europe', 'awards-latin-america',
    'awards-north-america',
);

@mkdir($OUTDIR, 0755, true);

/* 1. All published announcements, oldest first (chronological history). */
$posts = $wpdb->get_results(
    // post_author is needed for the byline rule. It was NOT in this list until
    // 2026-08-15: the rule read $p->post_author, got an undefined property, and
    // silently left every author as the house label. PHP warned; nothing else did.
    "SELECT ID, post_author, post_title, post_name, post_content, post_excerpt,
            post_date, post_date_gmt, post_modified_gmt
       FROM {$wpdb->posts}
      WHERE post_type='post' AND post_status='publish'
      ORDER BY post_date_gmt ASC, ID ASC"
);

/* 2. Bulk category map (one query) -> [post_id => sorted [slug => name]]. */
$catrows = $wpdb->get_results(
    "SELECT tr.object_id pid, t.name name, t.slug slug
       FROM {$wpdb->term_relationships} tr
       JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
       JOIN {$wpdb->terms} t          ON t.term_id = tt.term_id
      WHERE tt.taxonomy='category'"
);
$catmap = array();      // filtered -> recorded categories
$catslugs = array();    // ALL slugs per post -> content_class detection
foreach ($catrows as $r) {
    $catslugs[$r->pid][$r->slug] = true;
    if (in_array($r->slug, $EXCLUDE_CAT_SLUGS, true)) continue;
    $catmap[$r->pid][$r->name] = true;
}

/* 2b. Sponsored flag (authoritative paid-content signal, set by the
       cfi-sponsored-flag.php editor metabox; consumed for the visible
       disclosure + AdvertiserContentArticle schema). */
$sponmap = array();
foreach ($wpdb->get_results(
    "SELECT post_id pid, meta_key mk, meta_value mv
       FROM {$wpdb->postmeta}
      WHERE meta_key IN ('_cfi_jsonld_sponsored','_cfi_jsonld_sponsor_name')"
) as $m) {
    if ($m->mk === '_cfi_jsonld_sponsored')   $sponmap[$m->pid]['flag'] = $m->mv;
    if ($m->mk === '_cfi_jsonld_sponsor_name') $sponmap[$m->pid]['name'] = $m->mv;
}

/* 2b-i. Contributed-opinion flag, set by the cfi-contributor-flag.php editor tickbox.
        Bulk-loaded for the same reason as the sponsorship map: a get_post_meta() per post
        inside the export loop is ~2,800 queries. */
$contribmap = array();
foreach ($wpdb->get_col(
    "SELECT post_id FROM {$wpdb->postmeta}
      WHERE meta_key = '_cfi_contributed' AND meta_value = '1'"
) as $pid) {
    $contribmap[(int) $pid] = true;
}

/* 2b-ii. Author lookup for the byline rule. user_login decides whether a post is house
          copy; display_name is what gets published. Both are read once here rather than
          per-post: get_userdata() inside the export loop would be ~2,800 queries. */
$userlogin = array();
$userdisp  = array();
foreach ($wpdb->get_results("SELECT ID, user_login, display_name FROM {$wpdb->users}") as $u) {
    $userlogin[$u->ID] = $u->user_login;
    $userdisp[$u->ID]  = $u->display_name;
}

/* 2b-iii. Publisher-approved bylines (scripts/approved-bylines.json), by post ID.
          These pieces sit under a HOUSE account with the real byline only in the body
          markup, so post_author cannot reach them. Nothing is extracted at export time:
          a record is renamed only if the publisher put its ID in that file. Single-source
          extractions were deliberately NOT approved — a wrong name invents a person,
          where the house label merely fails to name one. (Ruling, 15 Aug 2026.) */
$approved_bylines = array();
if (is_file("$REPO/scripts/approved-bylines.json")) {
    $ab = json_decode(file_get_contents("$REPO/scripts/approved-bylines.json"), true);
    if (is_array($ab) && isset($ab['bylines']) && is_array($ab['bylines'])) {
        foreach ($ab['bylines'] as $pid => $nm) {
            $nm = trim((string) $nm);
            if ($nm !== '') $approved_bylines[(int) $pid] = $nm;
        }
    }
}

/* 2c. Wayback evidence cache (built by scripts/wayback.php; gitignored).
       url => [status, earliest snapshot ts, snapshot url]. */
$waybackmap = array();
if (is_file("$REPO/scripts/.wayback-cache.tsv")) {
    foreach (file("$REPO/scripts/.wayback-cache.tsv",
                  FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
        $p = explode("\t", $l);
        if (count($p) >= 4) {
            $waybackmap[$p[0]] = array('status' => $p[1], 'ts' => $p[2], 'snap' => $p[3]);
        }
    }
}

$plan = fopen($PLAN, 'w');
$indexBuf = '';
$n = 0; $bytes = 0;

foreach ($posts as $p) {
    $id    = (int) $p->ID;
    $year  = substr($p->post_date, 0, 4);
    $slug  = $p->post_name !== '' ? $p->post_name : 'post';
    $slug  = preg_replace('/[^a-z0-9-]+/', '-', strtolower($slug));
    $slug  = trim(preg_replace('/-+/', '-', $slug), '-');
    if (strlen($slug) > 80) $slug = substr($slug, 0, 80);

    $cats = array();
    if (isset($catmap[$id])) { $cats = array_keys($catmap[$id]); sort($cats); }

    $url     = get_permalink($id);

    // Page Links To (_links_to) can aim a post at another address. On this estate it is
    // used as a theme workaround so a menu entry lands on a category index, which means
    // get_permalink() above returns the redirect target rather than an address at which
    // this article can be read. Disclose that: otherwise a verifier fetches the url,
    // receives HTTP 200 on an index page, and concludes the record checks out. A 404
    // announces its own failure; a 200 on the wrong page does not.
    $links_to    = (string) get_post_meta($id, '_links_to', true);
    $redirected  = ($links_to !== '' && $p->post_name !== ''
                    && strpos($links_to, $p->post_name) === false);

    $content = (string) $p->post_content;          // RAW, verbatim
    $chash   = hash('sha256', $content);

    // Absolute path to this record's OWN prior file — computed here, ahead
    // of the later $base/$reljs (which stay repo-relative, for the write +
    // index further down), purely so correction_status below can read what
    // this record already said before this run overwrites it.
    $prior_path = $OUTDIR . '/' . $year . '/' . $id . '-' . $slug . '.json';

    // --- Content classification (every value is grounded in a real signal;
    //     derivation rules are documented in the README so labels are auditable). ---
    $sponsored = isset($sponmap[$id]['flag']) && $sponmap[$id]['flag'] === '1';
    $sponsor   = $sponsored ? (string) ($sponmap[$id]['name'] ?? '') : '';
    $contributed = isset($contribmap[$id]);

    // independence_status, 2026-08-25. Three values on one axis - who paid, and who wrote:
    //
    //   commercially_supported  paid for
    //   contributed_editorial   written by an outside contributor, not paid for
    //   independent_editorial   in-house
    //
    // Until today this was a BINARY: sponsored, or else independent_editorial. That default
    // was harmless only while independent_editorial meant "not flagged". The moment it means
    // "in-house", every contributed piece published from now on silently asserts CFI.co wrote
    // it - re-creating, one record at a time, exactly the defect the byline backfill exists to
    // correct. Both values now come from a deliberate editorial act; nothing falls through.
    //
    // Sponsored wins if both are somehow set. It is the stronger disclosure, and the policy
    // says contributed opinion cannot be commercially funded - so both set is a mistake, and
    // the mistake must not be resolved in the direction that hides payment.
    if ($sponsored) {
        $independence = 'commercially_supported';
        if ($contributed) {
            fwrite(STDERR, "post $id is flagged BOTH sponsored and contributed; recorded as "
                . "commercially_supported. One of the two tickboxes is wrong.\n");
        }
    } elseif ($contributed) {
        $independence = 'contributed_editorial';
    } else {
        $independence = 'independent_editorial';
    }
    if ($sponsored) {
        $content_class = 'sponsored_article';
    } else {
        $content_class = $DEFAULT_CONTENT_CLASS;
        foreach ($CONTENT_CLASS_BY_SLUG as $cslug => $cclass) {
            if (isset($catslugs[$id][$cslug])) { $content_class = $cclass; break; }
        }
    }

    // Correction status (2026-07-31 fix — was a hardcoded 'none', so the
    // documented "none -> revised when content later changed" transition
    // never actually happened; README-AI.md called it authoritative while
    // it was dead). Compared against the record's own PRIOR file, not
    // against git history — export.php has no git awareness, and this
    // needs to run identically whether or not the file happens to already
    // be committed. A missing prior file means a genuinely new record
    // ('none'). Once 'revised', stays 'revised' even if content later
    // matches again — a one-way transition, per the documented semantics,
    // so the record permanently discloses that it was corrected at least
    // once; git history remains the place to see exactly what changed.
    //
    // 2026-08-03: the content hash alone was not enough. The sponsorship
    // labels live in post META (_cfi_jsonld_sponsored,
    // _cfi_jsonld_sponsor_name), so correcting a mislabelled article changes
    // what the record CLAIMS while leaving post_content — and therefore
    // $chash — byte-identical. The record then rewrote independence_status
    // from independent_editorial to commercially_supported with
    // correction_status still reading 'none': a record asserting it had
    // never been corrected, in the same run that corrected it. A claim
    // changing while the content stays fixed is the case that most needs
    // disclosing, so the claim-bearing fields are now compared as well.
    //
    // Deliberately NOT a diff of the whole classification block. wayback_*
    // legitimately churns on ordinary re-checks (pending_check -> archived)
    // and 'license' moves on schema bumps; either would flip thousands of
    // records to 'revised' and leave the field meaning nothing. The set
    // below is exactly the fields that state something about the article
    // which a reader could rely on and we could get wrong.
    $claim_fields = array(
        'content_class'       => $content_class,
        'independence_status' => $independence,
        'sponsor_disclosure'  => $sponsored ? 'visible_and_machine_readable' : 'none',
        'sponsor_name'        => $sponsor,
    );

    // Byline: name a real person, keep the house label only for unsigned house copy.
    // Computed HERE, above correction_status, because a changed author is a changed
    // CLAIM about the article and has to be comparable against the prior record.
    $record_author = $EDITORIAL_AUTHOR;
    if (BYLINE_RULE_FROM === '' || substr($p->post_date_gmt, 0, 10) >= BYLINE_RULE_FROM) {
        $login = isset($userlogin[$p->post_author]) ? $userlogin[$p->post_author] : '';
        $disp  = isset($userdisp[$p->post_author])  ? trim($userdisp[$p->post_author]) : '';
        if ($disp !== '' && !in_array(strtolower($login), array_map('strtolower', $HOUSE_LOGINS), true)) {
            $record_author = $disp;
        }
    }

    // An approved byline WINS over anything derived. It is the publisher's explicit
    // decision about that specific record, so it must not be second-guessed by a rule.
    if (isset($approved_bylines[$id])) {
        $record_author = $approved_bylines[$id];
    }

    $correction_status = 'none';
    $correction_class  = '';
    if (is_file($prior_path)) {
        $prior = json_decode(file_get_contents($prior_path), true);
        if (is_array($prior)) {
            $prior_hash   = $prior['content_sha256'] ?? null;
            $prior_status = $prior['classification']['correction_status'] ?? 'none';
            $prior_class  = isset($prior['classification']) && is_array($prior['classification'])
                            ? $prior['classification'] : array();

            // A key MISSING from the prior record is a schema addition, not a
            // correction — only a key that was present and now says something
            // different is a changed claim. Without this guard every record
            // written before a field existed would flip to 'revised' the first
            // time this ran, which is the mass-mislabelling the narrow field
            // set above is meant to avoid.
            $claim_changed = false;
            foreach ($claim_fields as $ck => $cv) {
                if (array_key_exists($ck, $prior_class) && $prior_class[$ck] !== $cv) {
                    $claim_changed = true;
                    break;
                }
            }

            $author_changed = array_key_exists('author', $prior)
                              && $prior['author'] !== $record_author;

            if ($prior_status === 'revised'
                || ($prior_hash !== null && $prior_hash !== $chash)
                || $claim_changed
                || $author_changed) {
                $correction_status = 'revised';
            }

            // correction_class, 2026-08-25. correction_status is a two-value flag, so a
            // corrected FACT and a re-labelled CLAIM read identically once set. That was
            // tolerable while revisions were rare; it stops being tolerable the moment a
            // labelling-regime change touches the back catalogue in one pass, because the
            // field would then say "revised" on most of the archive and distinguish
            // nothing. This says WHICH kind, so a reader can tell "we got the article
            // wrong" from "we changed how we label".
            //
            //   factual_correction    the article text itself changed
            //   label_regime_change   the text is byte-identical; a claim or the byline
            //                         changed
            //   unspecified           revised before this field existed, and nothing
            //                         changed in this run to classify it from
            //
            // factual_correction STICKS, the same way correction_status is one-way: a
            // record whose text was once corrected must not later present itself as
            // having only been re-labelled. It is the stronger disclosure and it wins.
            //
            // Emitted below only where correction_status is 'revised' — like sponsor_name
            // and site_addressability — so the ~2,640 unrevised records keep their
            // existing record_sha256 and this change rehashes nothing that it does not
            // actually describe.
            if ($correction_status === 'revised') {
                $prior_cc = $prior_class['correction_class'] ?? '';
                if ($prior_hash !== null && $prior_hash !== $chash) {
                    $correction_class = 'factual_correction';
                } elseif ($claim_changed || $author_changed) {
                    $correction_class = 'label_regime_change';
                } else {
                    $correction_class = $prior_cc !== '' ? $prior_cc : 'unspecified';
                }
                if ($prior_cc === 'factual_correction') {
                    $correction_class = 'factual_correction';
                }
            }
        }
    }

    $wb = $waybackmap[$url] ?? array('status' => 'pending_check', 'ts' => '', 'snap' => '');
    // The claim fields are spread from $claim_fields rather than restated, so the
    // values compared above and the values published below cannot drift apart. If
    // they ever did, the detector would go quietly blind again.
    $classification = array(
        'content_class'          => $claim_fields['content_class'],
        'editorial_lens'         => 'constructive_positive_lens', // CFI's stated stance
        'independence_status'    => $claim_fields['independence_status'],
        'sponsor_disclosure'     => $claim_fields['sponsor_disclosure'],
        'sponsor_name'           => $claim_fields['sponsor_name'],
        'article_status'         => 'published',
        'historical_status'      => 'current_at_publication',
        'correction_status'      => $correction_status,
        'archive_policy'         => 'no_delete',
        'provenance_layer'       => 'github_versioned',
        'wayback_status'         => $wb['status'],   // archived | submitted_pending | not_found | pending_check
        'wayback_first_snapshot' => $wb['ts'],       // earliest Wayback capture (YYYYMMDDhhmmss)
        'wayback_snapshot_url'   => $wb['snap'],
        'license'                => $LICENCE_ID,   // CFI.co Open AI Access Licence (schema v2.2)
    );
    // Emitted only where it applies, like sponsor_name, so unaffected records keep
    // their existing record_sha256.
    if ($correction_class !== '') {
        $classification['correction_class'] = $correction_class;
    }
    if ($redirected) {
        $classification['site_addressability'] = 'redirects_away';
        $classification['site_note'] = 'This post redirects to a different address on '
            . 'cfi.co, so the url field is a redirect target rather than a page at which '
            . 'this text can be read. The redirect is a site navigation arrangement, not a '
            . 'withdrawal: nothing has been removed, and the verbatim text is preserved in '
            . 'this record.';
    }

    // Exact machine record. Key order is fixed; record_sha256 covers all
    // fields except itself, so the public can independently re-verify.
    $record = array(
        'id'             => $id,
        'title'          => $p->post_title,
        'slug'           => $p->post_name,
        'url'            => $url,
        'author'         => $record_author,
        'published'      => $p->post_date,          // site-local
        'published_gmt'  => $p->post_date_gmt,
        'modified_gmt'   => $p->post_modified_gmt,
        'categories'     => $cats,
        'classification' => $classification,
        'excerpt'        => $p->post_excerpt,
        'content_html'   => $content,
        'content_text'   => html_to_text($content),
        'content_sha256' => $chash,
    );

    // --- Contribution provenance -------------------------------------------------------
    // Only contributed pieces carry this block. Gating it on the contributor flag is not a
    // tidiness choice: record_sha256 covers every field, so adding a key unconditionally
    // would recompute the hash of every record in the archive and manufacture a rewrite of
    // the entire estate in one commit. Contributed pieces alone move, and only when they
    // first gain the block.
    //
    // published_text_sha256 is the join. content_sha256 is over the HTML and cannot be
    // compared with anything the author ever saw; this is over the same normalised text the
    // author's confirmation was taken against, so a third party can fetch this record, run
    // the published rule, and check the two agree. Spec:
    // crm.cfi.co/docs/superpowers/specs/2026-08-24-contribution-provenance-design.md
    if ($contributed) {
        $contribution = array(
            'normaliser'            => CFI_TEXTNORM_VERSION,
            'published_text_sha256' => cfi_webtext_1_sha256($content),
        );
        // Written by the confirmation loop. Absent until it exists, and absent rather than
        // false for any piece that never gained one - the same pattern the estate already
        // uses for pre-9-November-2025 sponsorship.
        foreach (array('submission_id', 'received_text_sha256', 'received_utc',
                       'origin_domain', 'origin_verified',
                       'confirmed_text_sha256', 'confirmed_utc', 'confirmation_method') as $k) {
            $v = (string) get_post_meta($id, '_cfi_contrib_' . $k, true);
            if ($v !== '') {
                $contribution[$k] = $v;
            }
        }
        // A confirmation hash without its method could be read as verified when it was a
        // phone call. Refuse to emit that combination rather than publish an overstatement.
        if (isset($contribution['confirmed_text_sha256'])
            && empty($contribution['confirmation_method'])) {
            unset($contribution['confirmed_text_sha256'], $contribution['confirmed_utc']);
            $contribution['confirmation_note'] =
                'A confirmation was recorded without its method and is withheld here rather '
                . 'than shown, because an unmethoded confirmation cannot be told apart from '
                . 'a verified one.';
        }
        // Emit ONLY when there is actual provenance to report. published_text_sha256 is a
        // hash of the record's own text: on its own it asserts nothing about where the piece
        // came from, and a "contribution" block containing only that would invite a reader to
        // believe provenance was tracked when it was not.
        //
        // This matters for the retrospective pass: setting _cfi_contributed on ~295 historic
        // records would otherwise give every one of them an empty-but-official-looking
        // provenance block. Those pieces predate receipt capture and have no origin evidence,
        // and saying so by absence is the honest answer.
        $has_provenance = isset($contribution['submission_id'])
            || isset($contribution['confirmed_text_sha256'])
            || isset($contribution['received_text_sha256']);
        if ($has_provenance) {
            $record['contribution'] = $contribution;
        }
    }
    $json = json_encode($record,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $record['record_sha256'] = hash('sha256', $json);
    $json = json_encode($record,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

    // Human-readable view. Front-matter is YAML; body is the verbatim HTML
    // so nothing is transformed. JSON sidecar is the canonical source.
    $fm  = "---\n";
    $fm .= 'id: ' . $id . "\n";
    $fm .= 'title: ' . yaml_str($p->post_title) . "\n";
    $fm .= 'year: ' . (int) $year . "\n";
    $fm .= 'published: ' . $p->post_date . "\n";
    $fm .= 'published_gmt: ' . $p->post_date_gmt . "\n";
    $fm .= 'author: ' . yaml_str($record_author) . "\n";   // must match the JSON record
    $fm .= 'url: ' . yaml_str($url) . "\n";
    if ($redirected) {
        $fm .= 'site_addressability: ' . $classification['site_addressability'] . "\n";
        $fm .= 'site_note: ' . yaml_str($classification['site_note']) . "\n";
    }
    $fm .= 'categories: [' . implode(', ', array_map('yaml_str', $cats)) . "]\n";
    $fm .= 'content_class: ' . $classification['content_class'] . "\n";
    $fm .= 'independence_status: ' . $classification['independence_status'] . "\n";
    $fm .= 'sponsor_disclosure: ' . $classification['sponsor_disclosure'] . "\n";
    if ($sponsored) $fm .= 'sponsor_name: ' . yaml_str($sponsor) . "\n";
    $fm .= 'editorial_lens: ' . $classification['editorial_lens'] . "\n";
    $fm .= 'historical_status: ' . $classification['historical_status'] . "\n";
    $fm .= 'correction_status: ' . $classification['correction_status'] . "\n";
    $fm .= 'archive_policy: ' . $classification['archive_policy'] . "\n";
    $fm .= 'provenance_layer: ' . $classification['provenance_layer'] . "\n";
    $fm .= 'wayback_status: ' . $classification['wayback_status'] . "\n";
    if ($classification['wayback_first_snapshot'] !== '') {
        $fm .= 'wayback_first_snapshot: ' . $classification['wayback_first_snapshot'] . "\n";
        $fm .= 'wayback_snapshot_url: ' . yaml_str($classification['wayback_snapshot_url']) . "\n";
    }
    $fm .= 'license: ' . $classification['license'] . "\n";
    $fm .= 'content_sha256: ' . $chash . "\n";
    $fm .= 'canonical: ' . $id . '-' . $slug . ".json\n";
    $fm .= "---\n\n";
    $fm .= '# ' . $p->post_title . "\n\n";
    $fm .= "> Verbatim archived copy. Canonical machine record: `" .
           $id . '-' . $slug . ".json`.\n\n";
    $md  = $fm . $content . "\n";

    $dir = $OUTDIR . '/' . $year;
    @mkdir($dir, 0755, true);
    $base   = $id . '-' . $slug;
    $relmd  = "articles/$year/$base.md";
    $reljs  = "articles/$year/$base.json";
    file_put_contents("$REPO/$relmd", $md);
    file_put_contents("$REPO/$reljs", $json);

    $msg = sprintf('Add article #%d: %s (%s)',
        $id, sanitize_oneline($p->post_title), $year);
    fwrite($plan, implode($US, array(
        $p->post_date_gmt, $id, $relmd, $reljs, $msg,
    )) . "\n");

    // Root-level catalog line: enough to enumerate + filter + verify without
    // fetching each record. Same chronological order as the export loop, so
    // newly-published articles append at the end (clean, append-only diffs).
    $indexBuf .= json_encode(array(
        'id'                  => $id,
        'record_id'           => 'cfi-article-' . $id,
        'year'                => (int) $year,
        'slug'                => $p->post_name,
        'title'               => $p->post_title,
        'url'                 => $url,
        'published_gmt'       => $p->post_date_gmt,
        'content_class'       => $classification['content_class'],
        'independence_status' => $classification['independence_status'],
        // How much that label is worth. Systematic independence labelling began on
        // 2025-11-09: the earliest commercially_supported record is dated that day and
        // NOT ONE record published before it carries a sponsorship label. So on a
        // pre-cutover record `independent_editorial` is an inherited default, not a
        // finding — 2,695 of 2,796 records as at 2026-08-14. An agent that honours
        // labels would otherwise read the whole back catalogue as assessed-independent,
        // which we already know is wrong. Catalogue-only: index.jsonl is derived and is
        // not part of record_sha256, so adding this rehashes nothing.
        'independence_basis'  => (substr($p->post_date_gmt, 0, 10) >= INDEPENDENCE_LABELLING_FROM)
                                 ? 'assessed'
                                 : 'default_pre_' . INDEPENDENCE_LABELLING_FROM,
        'sponsor_disclosure'  => $classification['sponsor_disclosure'],
        'path'                => $reljs,
        'md_path'             => $relmd,
        'content_sha256'      => $chash,
        'record_sha256'       => $record['record_sha256'],
    ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

    $n++; $bytes += strlen($md) + strlen($json);
}
fclose($plan);
file_put_contents("$REPO/index.jsonl", $indexBuf);

echo "Exported $n articles (" . round($bytes / 1048576, 1) . " MB)\n";
echo "Wrote index.jsonl ($n rows)\n";
echo "Commit plan: $PLAN\n";

/**
 * Deterministic plain-text rendering of an HTML body, for retrieval/grounding
 * consumers that would otherwise have to strip HTML themselves.
 *
 * Pure string operations only (no wpautop, no the_content filters, no theme or
 * plugin hooks) so re-exporting an unchanged content_html always yields a
 * byte-identical content_text — a re-export can never manufacture a spurious
 * "changed" commit. content_html stays the canonical body and the content_sha256
 * subject; content_text is a convenience field, but it is covered by
 * record_sha256, so it is as tamper-evident as everything else in the record.
 */
function html_to_text($html) {
    $s = (string) $html;
    $s = str_replace(array("\r\n", "\r"), "\n", $s);
    // Script/style contents are not readable text.
    $s = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $s);
    // <br> and block-level closes become line breaks.
    $s = preg_replace('#<br\s*/?>#i', "\n", $s);
    $s = preg_replace(
        '#</(p|div|h[1-6]|li|ul|ol|blockquote|tr|table|figure|figcaption|section|article|header|footer|pre)>#i',
        "\n\n", $s);
    // Strip all remaining tags and HTML comments.
    $s = strip_tags($s);
    // Entities -> real characters.
    $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // Non-breaking spaces -> normal spaces, then tidy whitespace WITHOUT
    // collapsing paragraph breaks.
    $s = str_replace("\xc2\xa0", ' ', $s);
    $s = preg_replace('/[ \t]+/', ' ', $s);
    $s = preg_replace('/ *\n */', "\n", $s);
    $s = preg_replace('/\n{3,}/', "\n\n", $s);
    return trim($s);
}

function yaml_str($s) {
    return '"' . str_replace(array('\\', '"'), array('\\\\', '\\"'), (string) $s) . '"';
}
function sanitize_oneline($s) {
    $s = preg_replace('/\s+/', ' ', trim((string) $s));
    return strlen($s) > 120 ? substr($s, 0, 117) . '...' : $s;
}
