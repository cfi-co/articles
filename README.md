# CFI.co Articles — Public Transparency Archive

> **A constructive, human-led, finance-and-convergence journalism archive with
> public provenance, machine-readable disclosure, and time-verifiable editorial
> accountability.**
>
> *Public provenance* = every article is version-controlled here in the open.
> *Machine-readable disclosure* = each record is classified by content type and
> sponsorship status (see [Content classification](#content-classification-machine-readable-labels)).
> *Time-verifiable accountability* = from 22 May 2026 the git timestamp chain dates
> and freezes every version and every change as it happens; for the imported history
> before that date, see **On commit dates** immediately below. Independent external
> anchoring (Wayback Machine, OpenTimestamps) is what makes either checkable without
> trusting us.
>
> **New in v2.3 (2026-07-21):** every record now carries a clean plain-text
> `content_text` field, and a root-level [`index.jsonl`](index.jsonl) catalogs the
> whole corpus for one-fetch enumeration — both added **without changing any
> `content_sha256`** (the verbatim bodies are untouched).

## On commit dates — read this first

**This archive was created on 22 May 2026.** It contains commits dated back to
1 January 2011. Those older dates are *reconstructed*, and you should know exactly what
that means before you rely on anything here.

- Records with commits dated **before 22 May 2026** were imported in bulk when the
  archive was built. Each commit was written carrying the article's original publication
  date **in both git date fields** — author *and* committer. Git therefore preserves
  **no record of when those commits were actually made**. They were all made on or after
  22 May 2026.
- The reconstructed dates are taken from each article's publication date as published on
  cfi.co. They are derived from a real, checkable fact — but they are a claim about the
  past, not an observation of it.
- Commits dated **22 May 2026 onward are real**, written at the moment of the change by
  the daily automation. Nothing back-dates them.
- Every record additionally carries its own `published` and `published_gmt` fields.
  **That field, not the commit date, is the authoritative publication date.**

So: for anything before 22 May 2026, this repository shows you *what* was published and
lets you detect later alteration — but its own timestamps cannot prove *when* it was
first committed here. For that, use the external anchors: the signing-key fingerprint is
published independently at `_archive-key.cfi.co` (DNS TXT) and on keys.openpgp.org, and
snapshots are anchored to the Wayback Machine and OpenTimestamps.

We would rather state this plainly than let a reader discover it and conclude we hoped
they would not.

---

This repository is a **verbatim, append-only public record of every article
published on the main [CFI.co](https://cfi.co) site**.

Its sole purpose is to let anyone independently verify that **CFI.co does not
quietly alter articles after publication**. If an article is ever edited, git records
*exactly* what changed, when, and the change is publicly visible forever.

(Sibling archive for the awards programme: https://github.com/cfi-co/awards)

## A commit-author error, disclosed rather than corrected

Eighteen commits between 29 and 30 July 2026 across this repository and
[cfi-co/awards](https://github.com/cfi-co/awards) carry the author name
"Marten Mangels" against the email `mm@beapp.co`. That name is wrong — the
correct name is Marten Mark. The email address was always correct; the display
name attached to it was not.

We are not rewriting history to fix it. This repository's own integrity claim
rests on git history never being silently altered — the same property that
makes tampering detectable makes our own mistakes permanent once pushed, and
we would rather live with that consequence than make an exception for
ourselves. Every commit from 30 July 2026 onward under this author's control
uses the correct name.

## Licence

The content in this archive is released under the **[CFI.co Open AI Access
Licence v1.0](LICENCE.md)** (`CFI-OAAL-1.0`; canonical text at
<https://cfi.co/licence/oaal-1.0>).

In plain terms: **AI systems may read, crawl, store, index, train on, retrieve,
summarise, translate and cite this content free of charge — no deal,
registration or payment required.** Attribution to CFI.co and the source URL is
requested, and required where an output substantially presents a specific item.
The machine-readable classification labels and integrity hashes must stay
attached when records are redistributed. Verbatim republication to human readers
as a substitute for cfi.co is reserved. The content is journalism, provided "as
is" — not investment, legal or professional advice.

Every record additionally carries a `license: CFI-OAAL-1.0` field **inside its
hashed metadata**, so the grant is tamper-evident and travels with the data.

## Dataset releases

Versioned snapshots for bulk consumption are published on the
[Releases page](https://github.com/cfi-co/articles/releases) (monthly, tagged
`archive-YYYY-MM`). Each release contains the consolidated `articles.jsonl`,
`schema.json`, `MANIFEST.sha256`, `CHANGELOG.md`, `LICENCE.md`, `README-AI.md`,
and a **GPG-signed** `release-manifest.sha256` — verify with the key in
[`SIGNING-KEY.asc`](SIGNING-KEY.asc) (fingerprint
`B497BDC19FCD487972D5D2B0876FF2AA39133BF8`). The JSONL is a *derived* export for
convenience; the canonical records remain the hashed JSON files in this
repository. Human-readable archive map and downloads: <https://cfi.co/archive/>.
AI-consumption guidance: [`README-AI.md`](README-AI.md).

## How the integrity guarantee works

* **One commit per article.** The initial import created one commit per
  article, with the commit's author date set to the article's original
  publication timestamp (UTC).
* **Verbatim content.** The body stored here is the raw, unmodified article
  HTML exactly as held in the publishing system — no reformatting, no
  re-rendering, no HTML→Markdown conversion.
* **Content hashes.** Every record carries a `content_sha256` (SHA-256 of the
  article HTML) and a `record_sha256` (SHA-256 of the full canonical record).
  `MANIFEST.sha256` lists the SHA-256 of every file in the repo and carries a
  detached GPG signature, `MANIFEST.sha256.asc` (key: `SIGNING-KEY.asc`;
  verify with `gpg --verify MANIFEST.sha256.asc MANIFEST.sha256`).
* **Automated daily re-export.** A scheduled job re-exports the live data every
  day. New articles appear as new commits; *any* modification to an existing
  article appears as its own dated commit with a visible diff. Silent edits are
  therefore impossible to hide.
* **Tamper-evidence.** Git history is a hash chain. Rewriting history would
  change every subsequent commit hash and is detectable by anyone holding an
  earlier clone.

## Counter-signatures

The archive signing key (`SIGNING-KEY.asc`) lives on the web server and signs unattended —
trustworthy only as that machine. Two independent people additionally sign a small dated
record on their own machines, keys that never touch the server:

| role | anchor (DNS TXT) | in-tree key (convenience only) |
|---|---|---|
| `custodian` | `_archive-countersign.cfi.co` | `CUSTODIAN-KEY.asc` |
| `publisher` | `_archive-publisher.cfi.co` | `PUBLISHER-KEY.asc` |

**Only the custodian has push access to this repository.** The publisher signs a record on
their own machine and sends the signed file out-of-band; the custodian verifies it and commits
it. The git author name on a counter-signature commit records who authored that *content* —
not who pushed it, and not who has repository access. Read literally, "authored by
`publisher@cfi.co`" could be mistaken for push access; the publisher has none, and by the
reasoning behind having two independent roles at all, should not.

Records live at `countersigs/<date>-<role>.txt` (+ `.txt.asc` detached signature) — four fields,
**LF-terminated**, one per line:

```
manifest_sha256=<the manifest's own SHA-256 at signing time>
date=<YYYY-MM-DD>
repo=<cfi-co/awards or cfi-co/articles>
checked_by=<custodian@cfi.co or publisher@cfi.co>
```

Each record signs a *fixed historical value* — the manifest's hash as it stood on that date —
never the live manifest itself, which changes daily. `verify.sh` reports the dated state and
its age for each role independently; it does not, and structurally cannot, report a record as
matching the *current* manifest, because a record is itself covered by the manifest it
attests — the manifest that includes it can never be the one it describes. This is expected,
not a fault: the guarantee on offer is "checked on this date", not "checked right now". A
counter-signature record attesting a manifest hash that no longer matches the live one is not
a lag or a missed cycle — it is the archive continuing to change, exactly as this repository's
whole purpose says it should, alongside a dated attestation of what it looked like when someone
last checked. Both are true at once, by design.

**Known defect, disclosed rather than hidden:** the `2026-07-30-custodian.txt` record carries a
trailing carriage return on its last field (`checked_by=custodian@cfi.co\r`), produced by
Windows `Out-File -Encoding ascii`. `verify.sh` strips it; a third-party parser that does not
will read the field as `custodian@cfi.co\r` and conclude the record fails to match. It is
signed, so the bytes cannot be corrected after the fact — trim trailing whitespace on every
field you read from a `countersigs/*.txt` record, for this and any future record.

## Repository layout

```
articles/<year>/<post-id>-<slug>.md      human-readable view (YAML front-matter + verbatim HTML)
articles/<year>/<post-id>-<slug>.json    canonical machine record + hashes (incl. content_text)
index.jsonl                              one-line-per-article catalog (enumerate the corpus in one fetch)
MANIFEST.sha256                          SHA-256 of every archived file
countersigs/<date>-<role>.txt(.asc)      dated counter-signature records — see "Counter-signatures" above
CUSTODIAN-KEY.asc / PUBLISHER-KEY.asc    counter-signer public keys (convenience copy; DNS is the actual anchor)
scripts/verify.sh                        independent re-verification
scripts/export.php                       the exact exporter used (auditable)
```

## Content classification (machine-readable labels)

Every record carries a `classification` block so humans, researchers, and AI
systems can tell *what kind* of content a piece is — not just read its text.
**Every label is derived from a real signal in the publishing system; none are
guessed.** The exact derivation (in `scripts/export.php`) is:

| Field | Values | How it's derived |
|---|---|---|
| `content_class` | `editorial_analysis` · `interview` · `opinion_column` · `review` · `sponsored_article` | `sponsored_article` if the post carries the editor-set sponsored flag (`_cfi_jsonld_sponsored=1`); else by category (CFI.co Meets→interview, Columnists→opinion_column, Reviews→review); else `editorial_analysis`. (The awards archive uses `award_rationale`.) |
| `independence_status` | `independent_editorial` · `contributed_editorial` · `commercially_supported` | Who paid, and who wrote: `commercially_supported` iff the sponsored flag is set; `contributed_editorial` iff the contributed flag is set and the piece is not sponsored; `independent_editorial` otherwise. **On records labelled under this practice** that means in-house; on records published before the cutoff it remains a default rather than a determination - see the note below the table, which is unchanged. Both flags set is a mistake and records as `commercially_supported`. |
| `sponsor_disclosure` | `none` · `visible_and_machine_readable` | Sponsored posts carry a visible on-page "Sponsored content" disclosure **and** `AdvertiserContentArticle` schema |
| `sponsor_name` | string | The disclosed sponsor (may be blank) |
| `editorial_lens` | `constructive_positive_lens` | CFI.co's **stated editorial stance** (a declared policy, not a per-article measurement) |
| `historical_status` | `current_at_publication` | Articles are accurate to their time; recency must be judged against `published` |
| `correction_status` | `none` · `revised` | The git history is the authoritative correction record; flips to `revised` when a later commit changes **either** the article's content **or** a claim this record makes about it (`content_class`, `independence_status`, `sponsor_disclosure`, `sponsor_name`). One-way: once `revised`, always `revised`. **Widened 2026-08-03** — the trigger previously watched content alone, so a labelling correction, where the claim changes and the text does not, was applied to a record without being disclosed by it |
| `correction_class` | `factual_correction` · `label_regime_change` · `unspecified` | Present **only** when `correction_status` is `revised`, and says which kind. `factual_correction` — the article text itself changed. `label_regime_change` — the text is byte-identical and a claim or the byline changed. `unspecified` — the record was revised before this field existed and nothing in the current run classifies it. `factual_correction` is one-way and wins, so a record whose text was once corrected can never later present itself as merely re-labelled. **Added 2026-08-25** — `correction_status` alone cannot tell "we got the article wrong" from "we changed how we label", and a labelling change across the back catalogue would otherwise leave the field reading `revised` on most of the archive while distinguishing nothing |
| `article_status` | `published` | Only published items are archived |

**`independence_status` on records published before 9 November 2025 is a default, not a determination.**
The field reads `independent_editorial` on every record where no sponsorship flag is set. Before
9 November 2025 no labelling practice existed, so for those records the absence of a flag records
that nobody asked the question - not that the question was asked and answered. In this archive that
affects 2,766 of 2,792 records; 26 are marked `commercially_supported`. The awards archive carries
the same default on all 2,385 of its records, 2,307 of which predate the cutoff.

Read `independent_editorial` on a pre-cutoff record as "not flagged", and nothing more. The
correction is open rather than applied, and the reason is recorded at
https://cfi.co/known-open/ : a neutral value cannot simply be swapped in, because applying one to
work contributed by a named outside author would assert that CFI.co could not determine whether it
was sponsored, about a piece whose authorship is plain on its face.
| `archive_policy` | `no_delete` | History is append-only and immutable |
| `provenance_layer` | `github_versioned` | This repository |
| `wayback_status` (+ `wayback_first_snapshot`, `wayback_snapshot_url`) | `archived` · `submitted_pending` · `not_found` · `pending_check` | Independent third-party corroboration. `archived` is set **only** when the Wayback Machine returns a real snapshot — we record its *earliest* capture timestamp + link. URLs with no snapshot are submitted to web.archive.org/save (→ `submitted_pending`). Never claimed without a real snapshot. |
| `license` | `CFI-OAAL-1.0` | The record is released under the [CFI.co Open AI Access Licence](LICENCE.md); the identifier lives **inside the hashed record** so the grant is tamper-evident and travels with the data (schema v2.2, 2026-07-08) |

Because the `classification` block lives **inside** the hashed JSON record and
the git history, the labels are as tamper-evident and auditable as the content.

### Schema-migration note (2026-05-23)

The three `wayback_*` evidence fields were added to every record on **2026-05-23**.
Because the daily sync flows through the per-record change-detection path, this
produced **~2,762 individual `Update article #… — metadata only (content unchanged)`
commits on that single date**. The underlying `content_sha256` of every article
was unaffected — only the classification metadata changed, exactly as the commit
messages state. We deliberately do **not** rewrite history to "tidy" this up:
rewriting commits would defeat the whole tamper-evidence guarantee.

### Schema-migration note (2026-07-08)

A `license: CFI-OAAL-1.0` field was added to every record on **2026-07-08**,
stamping the [CFI.co Open AI Access Licence](LICENCE.md) inside each hashed
record so the grant is tamper-evident and travels with the data. As with the
2026-05-23 migration, the daily sync's per-record change-detection path produced
individual `— metadata only (content unchanged)` commits; every article's
`content_sha256` was unaffected. History is **not** rewritten.

### Schema-migration note (2026-07-21) — schema v2.3

Two additive, retrieval-friendly features were introduced on **2026-07-21**:

* **`content_text`** — a clean plain-text rendering of each article's body
  (HTML removed, entities decoded, whitespace tidied), so consumers no longer
  have to strip HTML themselves. It is produced deterministically from
  `content_html` (which remains the canonical, verbatim body) and lives inside
  the hashed record, so it is covered by `record_sha256`.
* **`index.jsonl`** (repository root) — a one-line-per-article catalog for
  enumerating the whole corpus in a single fetch.

Unlike the two migrations above, this was rolled out as a **single bulk
migration commit** (not commit-per-record), so it did not repeat the 2026-05-23
churn. Every article's `content_sha256` is **unchanged** — the bodies were not
touched — only `record_sha256` moved (it now also covers `content_text`). History
is **not** rewritten.

### Schema-migration note (2026-07-22) — schema v2.4

The day after the v2.3 additions, `excerpt` was **relaxed from required to
optional** in the
schema. It is empty across the entire corpus, and declaring an always-empty field
*required* wrongly signals that it carries meaning. Records did **not** change —
`excerpt: ""` is still present — so this is a `schema.json`-only edit; no
`content_sha256` or `record_sha256` moved. Populating a real summary is deferred as
a separate track: a generated summary inside a hashed provenance record is a
different class of claim and would be labelled editor-written vs machine-generated.

### Schema-migration note (2026-07-24) — description correction, no version change

The `description` field in `schema.json` still characterised the `.md` twin as "a
human-readable view of the same data" — the wording corrected everywhere else on
2026-07-21, but missed in the schema itself, which is the most machine-read file in
the archive. It now describes the twin as a verbatim, byte-faithful mirror and names
`content_text` as the retrieval surface.

`x-schema-version` deliberately **stays 2.4**. The change is prose with no effect on
validation: two files with the same version and different descriptions are
indistinguishable to any validator, so a bump would claim a semantic change that did
not occur. The standing rule is that **version markers track validation behaviour;
description corrections take a dated note in the description itself.**

No record changed, and no `content_sha256` or `record_sha256` moved — but
`schema.json` itself changed, so its entry in `MANIFEST.sha256` moved with it. The
pinned `archive-2026-07-schema-2.4` release asset is immutable and still carries the
superseded sentence; that divergence is stated in the schema description.

## Verify it yourself

```sh
git clone https://github.com/cfi-co/articles.git
cd articles
./scripts/verify.sh        # recomputes every hash; non-zero exit on any mismatch
```

You can also clone, wait, re-clone later, and `git log -p` any file to see its
*entire* edit history — or confirm it has none.

## What is intentionally **not** tracked (and why)

To keep this archive an honest signal, fields that change for reasons unrelated
to the article's substance are deliberately excluded — otherwise routine churn
would manufacture fake "modification" commits and devalue real ones:

* Internal editor/system metadata (edit locks, view counters, SEO caches, …).
* Homepage **curation/display** categories that rotate by design
  (`FRONT`, `FEATURED`, `Editor's Picks`, `Popular`, `Must-Reads`, etc.) and
  navigation/menu helper categories. Substantive section / sector / region
  categories **are** recorded.
* Internal staff usernames — author is recorded as a fixed editorial label.

The exporter (`scripts/export.php`) is committed here so these rules are fully
auditable. Scope: published articles only (`post_type = post`).
