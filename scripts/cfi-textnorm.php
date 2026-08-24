<?php
/**
 * cfi-textnorm — the published-side half of the contribution provenance rule.
 *
 * A contributed piece is checked by comparing what the author confirmed against what CFI.co
 * published. Those two things live in different formats — a .docx and a page of HTML — so
 * neither can be compared byte for byte. Both are reduced to the SAME normalised text and
 * hashed:
 *
 *     cfi-doctext-1   .docx -> normalised text     (Python, on mail.cfi.co)
 *     cfi-webtext-1   HTML  -> normalised text     (this file)
 *
 * The two front ends differ; the tail — how a paragraph is normalised — is identical by
 * construction and must stay that way.
 *
 * !! THE HAZARD THIS FILE EXISTS TO SURVIVE
 *
 * ContributorLedger::canonical() already carries a scar from the same shape of problem: two
 * implementations of one rule in two languages, where a disagreement produces a record that
 * fails to verify despite having been written perfectly well. A contributor would be told
 * their piece does not match what they approved, because of an entity-decoding difference
 * between PHP and Python.
 *
 * So this rule is not "documented and hoped over". conformance/cfi-textnorm-1.json pins every
 * decision with an expected hash, and BOTH implementations must reproduce all of it:
 *
 *     php8.2 scripts/cfi-textnorm-conformance.php
 *     cfi-contribute-receipt --conformance          (on mail.cfi.co)
 *
 * Anything not pinned by a fixture is not part of the rule.
 *
 * !! VERSIONING. Changing any behaviour here is a NEW VERSION, never an edit. A hash published
 * against cfi-webtext-1 must stay reproducible forever, so a change means cfi-webtext-2 and
 * both must then exist.
 *
 * Deliberately NOT normalised: quotes, dashes, capitalisation, spelling. Those are editorial
 * changes and MUST move the hash — that is the entire point.
 *
 * This file only defines functions. Including it does nothing.
 */

if (!defined('CFI_TEXTNORM_VERSION')) {
    define('CFI_TEXTNORM_VERSION', 'cfi-webtext-1');
}

/** Paragraph separator sentinel. U+001E is a control character: it cannot occur in article text
 *  and cannot survive normalisation, so it can never be confused with content. */
if (!defined('CFI_TEXTNORM_SEP')) {
    define('CFI_TEXTNORM_SEP', "\x1E");
}

if (!function_exists('cfi_textnorm_paragraph')) {
/**
 * Normalise ONE paragraph. Identical in effect to normalise_paragraph() in
 * /usr/local/bin/cfi-contribute-receipt on mail.cfi.co.
 *
 * NFC first, so that a precomposed and a decomposed accent hash the same — an organisation
 * called "Crédit Agricole" must not depend on which keyboard typed it.
 *
 * Then the invisible characters go. A soft hyphen or a zero-width space is not readable text,
 * and leaving them in would let two visually identical documents hash differently — which,
 * on a record whose purpose is to prove sameness, would be a defect that looks like tampering.
 *
 * Then every kind of space becomes one ordinary space. \s covers ASCII whitespace; \p{Zs}
 * covers the Unicode space separators including the non-breaking space Word inserts freely.
 *
 * NOTE, pinned by fixture: U+2028/U+2029 (Zl/Zp) are NOT treated as whitespace, by either
 * implementation. Word does not emit them. The behaviour is pinned rather than corrected,
 * because correcting it now would be a version change for no practical gain.
 */
function cfi_textnorm_paragraph($s)
{
    $s = (string) $s;
    if (class_exists('Normalizer')) {
        $n = Normalizer::normalize($s, Normalizer::FORM_C);
        if ($n !== false && $n !== null) {
            $s = $n;
        }
    }
    $s = str_replace(
        array("\xC2\xAD", "\xE2\x80\x8B", "\xE2\x80\x8C", "\xE2\x80\x8D", "\xEF\xBB\xBF"),
        '',
        $s
    );
    $s = preg_replace('/[\s\p{Zs}]+/u', ' ', $s);
    return trim($s);
}
}

if (!function_exists('cfi_textnorm_join')) {
/**
 * The shared tail: normalise each paragraph, drop the empties, join with LF, trim.
 * Dropping empties is what makes a stray blank paragraph in Word — or a <p>&nbsp;</p> a
 * theme leaves behind — invisible to the hash.
 */
function cfi_textnorm_join(array $paragraphs)
{
    $out = array();
    foreach ($paragraphs as $p) {
        $p = cfi_textnorm_paragraph($p);
        if ($p !== '') {
            $out[] = $p;
        }
    }
    return trim(implode("\n", $out));
}
}

if (!function_exists('cfi_webtext_1')) {
/**
 * cfi-webtext-1: published HTML -> normalised text.
 *
 * Operates on the record's `content_html`, which is the post content verbatim — so a third
 * party can fetch the public record and reproduce the hash without access to WordPress.
 *
 * Order matters and is pinned by fixtures:
 *   - comments and script/style go first, with their contents, because they are not readable
 *     text and may contain anything at all;
 *   - <br> becomes a SPACE, not a paragraph break, matching w:br on the .docx side;
 *   - block boundaries become the sentinel BEFORE tags are stripped, or "</p><p>" would weld
 *     the last word of one paragraph to the first word of the next;
 *   - entities are decoded AFTER stripping, so that a literal "&lt;p&gt;" written in an
 *     article is read as text and never as markup.
 */
function cfi_webtext_1($html)
{
    $s = (string) $html;
    $s = str_replace(array("\r\n", "\r"), "\n", $s);

    $s = preg_replace('/<!--.*?-->/s', '', $s);
    $s = preg_replace('#<(script|style|noscript)\b[^>]*>.*?</\1>#is', ' ', $s);

    $s = preg_replace('#<br\b[^>]*>#i', ' ', $s);

    $block = 'p|div|h[1-6]|li|ul|ol|dl|dd|dt|blockquote|pre|table|thead|tbody|tfoot'
           . '|tr|td|th|figure|figcaption|section|article|aside|header|footer|main'
           . '|nav|address|form|fieldset|hr';
    $s = preg_replace('#<(?:' . $block . ')\b[^>]*>#i', CFI_TEXTNORM_SEP, $s);
    $s = preg_replace('#</(?:' . $block . ')\s*>#i', CFI_TEXTNORM_SEP, $s);

    $s = strip_tags($s);
    $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    return cfi_textnorm_join(explode(CFI_TEXTNORM_SEP, $s));
}
}

if (!function_exists('cfi_webtext_1_sha256')) {
function cfi_webtext_1_sha256($html)
{
    return hash('sha256', cfi_webtext_1($html));
}
}
