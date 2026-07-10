# CFI.co Articles — Public Transparency Archive

> **A constructive, human-led, finance-and-convergence journalism archive with
> public provenance, machine-readable disclosure, and time-verifiable editorial
> accountability.**
>
> *Public provenance* = every article is version-controlled here in the open.
> *Machine-readable disclosure* = each record is classified by content type and
> sponsorship status (see [Content classification](#content-classification-machine-readable-labels)).
> *Time-verifiable accountability* = the git timestamp chain dates and freezes
> every version and every change (independent Wayback Machine corroboration is
> being added on top).

This repository is a **verbatim, append-only public record of every article
published on the main [CFI.co](https://cfi.co) site**.

Its sole purpose is to let anyone independently verify that **CFI.co does not
quietly alter articles after publication**. Every article is committed
individually and back-dated to its original publication date. If an article is
ever edited, git records *exactly* what changed, when, and the change is
publicly visible forever.

(Sibling archive for the awards programme: https://github.com/cfi-co/awards)

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
  `MANIFEST.sha256` lists the SHA-256 of every file in the repo.
* **Automated daily re-export.** A scheduled job re-exports the live data every
  day. New articles appear as new commits; *any* modification to an existing
  article appears as its own dated commit with a visible diff. Silent edits are
  therefore impossible to hide.
* **Tamper-evidence.** Git history is a hash chain. Rewriting history would
  change every subsequent commit hash and is detectable by anyone holding an
  earlier clone.

## Repository layout

```
articles/<year>/<post-id>-<slug>.md      human-readable view (YAML front-matter + verbatim HTML)
articles/<year>/<post-id>-<slug>.json    canonical machine record + hashes
MANIFEST.sha256                          SHA-256 of every archived file
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
| `independence_status` | `independent_editorial` · `commercially_supported` | `commercially_supported` iff sponsored flag set |
| `sponsor_disclosure` | `none` · `visible_and_machine_readable` | Sponsored posts carry a visible on-page "Sponsored content" disclosure **and** `AdvertiserContentArticle` schema |
| `sponsor_name` | string | The disclosed sponsor (may be blank) |
| `editorial_lens` | `constructive_positive_lens` | CFI.co's **stated editorial stance** (a declared policy, not a per-article measurement) |
| `historical_status` | `current_at_publication` | Articles are accurate to their time; recency must be judged against `published` |
| `correction_status` | `none` · `revised` | The git history is the authoritative correction record; flips to `revised` when a later content change is committed |
| `article_status` | `published` | Only published items are archived |
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
