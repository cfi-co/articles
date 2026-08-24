<?php
/**
 * Runs conformance/cfi-textnorm-1.json against the PHP implementation.
 *
 * The Python implementation on mail.cfi.co runs the same file:
 *     cfi-contribute-receipt --conformance
 *
 * Both must pass, or a contributed piece can be recorded as not matching what its author
 * approved when nothing is actually wrong. Run this after ANY edit to cfi-textnorm.php.
 *
 *     php8.2 scripts/cfi-textnorm-conformance.php
 *
 * Exit status is 0 only if every case of an implemented kind passes AND the fixture file is
 * internally consistent. A runner that cannot fail is worse than no runner, so it also
 * verifies each expect_sha256 against its own expect_text.
 */

require_once __DIR__ . '/cfi-textnorm.php';

$fixtures = __DIR__ . '/../conformance/cfi-textnorm-1.json';
if (!is_file($fixtures)) {
    fwrite(STDERR, "conformance fixtures not found: $fixtures\n");
    exit(2);
}
$doc = json_decode(file_get_contents($fixtures), true);
if (!is_array($doc) || empty($doc['cases'])) {
    fwrite(STDERR, "conformance fixtures are not readable JSON with cases\n");
    exit(2);
}

/** Kinds this implementation is responsible for. 'docx' belongs to the Python side. */
$implemented = array('paragraph', 'html');

$pass = $fail = $skip = 0;
$failures = array();

foreach ($doc['cases'] as $c) {
    $id   = isset($c['id']) ? $c['id'] : '(unnamed)';
    $kind = isset($c['kind']) ? $c['kind'] : '';

    // Fixture self-consistency: the stated hash must be the hash of the stated text.
    if (hash('sha256', $c['expect_text']) !== $c['expect_sha256']) {
        $fail++;
        $failures[] = array($id, 'fixture is self-inconsistent: expect_sha256 is not sha256(expect_text)');
        continue;
    }

    if (!in_array($kind, $implemented, true)) {
        $skip++;
        continue;
    }

    if ($kind === 'paragraph') {
        $got = cfi_textnorm_join(array($c['input']));
    } else {
        $got = cfi_webtext_1($c['input']);
    }

    if ($got === $c['expect_text']) {
        $pass++;
    } else {
        $fail++;
        $failures[] = array($id, sprintf("expected %s\n         got      %s",
            json_encode($c['expect_text'], JSON_UNESCAPED_UNICODE),
            json_encode($got, JSON_UNESCAPED_UNICODE)));
    }
}

foreach ($failures as $f) {
    printf("  FAIL %-28s %s\n", $f[0], $f[1]);
}
printf("\n%s (php) - %d passed, %d failed, %d not this implementation's kind\n",
    $fail ? 'FAILED' : 'PASSED', $pass, $fail, $skip);
exit($fail ? 1 : 0);
