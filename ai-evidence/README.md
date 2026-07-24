# CFI.co AI Evidence Library

A versioned, observational record of how AI systems select and cite CFI.co
content — the evidence behind [cfi.co/ai/evidence/](https://cfi.co/ai/evidence/).

**What this is:** dated, reproducible test records. Each case preserves the
exact prompt sent, the system and model, the full unedited response, every
cited URL, the HTTP status, and stated limitations — including unsuccessful
and mixed results. Cases live under `ai-evidence/<year>/<case-id>/`.

**What this is not:** a statistically controlled scientific benchmark. Tests
are point-in-time observations; AI answers vary by system, date, location,
prompt and user context. Nothing here claims or implies guaranteed citation,
ranking, endorsement or evaluation by any AI system.

## Case layout

```
ai-evidence/<year>/<case-id>/
  manifest.json    — machine-readable case card (system, mode, date, outcome, limitations)
  results.json     — full test data: prompts, unedited responses, cited URLs, HTTP codes
  limitations.md   — what this case does and does not show
```

Cases with `"status": "pending_documents"` in their manifest are placeholders:
the underlying material exists but the full evidence pack has not yet been
assembled and published. **No evidentiary claim is made by a pending case.**

## Methodology (shared by API-based cases)

- Queries are sent server-side to each engine's public API from CFI.co's own
  infrastructure. API results are not identical to the consumer apps: there is
  no login state, no location personalisation and no interface context. Treat
  these as one measurement mode; in-app tests are another.
- "Cited" means the engine returned a CFI.co-owned source (`cfi.co`, or CFI's
  GitHub archive) in its **citation metadata** — never merely that CFI.co was
  mentioned in prose, and never that anything was "endorsed" or "ranked".
- A control question with a known, stable citation validates the detector on
  every automated run.
- Failures are published, not curated away.

## Evidence maturity (how strong is a given case?)

1. **Accessible** — the page is indexable, machine-readable, openly licensed.
2. **Discoverable** — it appears in search or an AI source panel for a relevant query.
3. **Selected** — an AI system chooses the page as a source.
4. **Used** — claims or framing from the page appear in the generated answer.
5. **Accuracy contribution** — the answer is better because of the page.
6. **Commercial contribution** — evidence of a business outcome (only ever claimed with client-supplied data).

Each case's manifest states the maturity level it demonstrates and no more.

## Integrity

These files are covered by this repository's `MANIFEST.sha256` (GPG-signed,
detached signature `MANIFEST.sha256.asc`, key `SIGNING-KEY.asc`) and by the
append-only git history — the same tamper-evidence model as the article
records. See the repository [README](../README.md).
