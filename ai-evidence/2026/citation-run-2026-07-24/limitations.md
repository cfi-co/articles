# Limitations — citation-run-2026-07-24

- **One run, one day.** This is a point-in-time observation (24 July 2026,
  15:23 UTC). The same questions on another day, from another location, or in
  a consumer app can produce different results.
- **API mode.** Queries went to each engine's public API from CFI.co's own
  server. Consumer apps add login state, location, memory and interface
  context that these tests deliberately do not have.
- **What "cited" means here.** An engine counts as citing CFI.co only when a
  CFI.co-owned source (`cfi.co`, or CFI's public GitHub archive) appears in
  the engine's citation metadata (URL annotations, grounding chunks, citation
  lists, or web-search result blocks). Prose mentions do not count. Gemini's
  grounding metadata exposes source *titles* rather than final URLs, so Gemini
  matches are made against those title strings — a weaker provenance signal
  than a resolved URL.
- **Mixed results included.** 4 of 32 pairs did not cite CFI.co; they are in
  `results.json` unedited.
- **No outcome claims.** Nothing in this case demonstrates accuracy
  improvement (maturity level 5) or any commercial effect (level 6).
