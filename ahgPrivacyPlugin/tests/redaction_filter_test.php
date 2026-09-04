<?php

/**
 * Pure-logic checks for RedactionContentFilter's substitution and verification.
 *
 * No database, no Symfony: substitute(), normalise() and detectLeaks() are pure
 * string handling, which is why they can be tested at all. Run:
 *
 *   php tests/redaction_filter_test.php
 *
 * The verification these cover is a privacy guard: when it cannot prove a redacted
 * value is absent from the page, the record must be withheld rather than served.
 */

require_once __DIR__ . '/../lib/Service/RedactionContentFilter.php';

use ahgPrivacyPlugin\Service\RedactionContentFilter;

$passed = 0;
$failed = 0;

function check(string $label, $got, $want): void
{
    global $passed, $failed;
    if ($got === $want) {
        ++$passed;
        printf("  PASS  %s\n", $label);

        return;
    }
    ++$failed;
    printf("  FAIL  %s\n        expected: %s\n        actual:   %s\n", $label, var_export($want, true), var_export($got, true));
}

$rc = new ReflectionClass(RedactionContentFilter::class);
$m = function (string $name) use ($rc) {
    $method = $rc->getMethod($name);
    $method->setAccessible(true);

    return $method;
};
$substitute = $m('substitute');
$detectLeaks = $m('detectLeaks');
$normalise = $m('normalise');

$secret = 'Donor was Mrs A. Khumalo of 14 Rose Street, Durban';
$red = '[REDACTED - personal data removed]';

echo "\nSubstitution covers the forms a theme actually renders\n";

check('plain value substituted', strpos($substitute->invoke(null, "<p>{$secret}</p>", $secret, $red), $secret), false);
check(
    'ENT_QUOTES rendering substituted',
    strpos($substitute->invoke(null, '<p>' . htmlspecialchars($secret, ENT_QUOTES) . '</p>', $secret, $red), htmlspecialchars($secret, ENT_QUOTES)),
    false
);
$multi = "Line one about Khumalo\nLine two with more detail";
check('nl2br rendering substituted', strpos($substitute->invoke(null, '<p>' . nl2br($multi) . '</p>', $multi, $red), 'Line one about Khumalo'), false);

echo "\nVerification catches what substitution missed\n";

check(
    'truncated render detected as leak',
    $detectLeaks->invoke(null, '<p>' . substr($secret, 0, 30) . '...</p>', ['scope_and_content' => $secret], ['scope_and_content' => $red]),
    ['scope_and_content']
);
check(
    'whitespace-variant render detected as leak',
    $detectLeaks->invoke(null, '<p>Donor was   Mrs A. Khumalo of 14 Rose Street,   Durban</p>', ['scope_and_content' => $secret], ['scope_and_content' => $red]),
    ['scope_and_content']
);
check(
    'markup-split value detected as leak',
    $detectLeaks->invoke(null, '<p>Donor was <em>Mrs A. Khumalo</em> of 14 Rose Street, Durban</p>', ['scope_and_content' => $secret], ['scope_and_content' => $red]),
    ['scope_and_content']
);
check(
    'a properly redacted page reports no leak',
    $detectLeaks->invoke(null, '<p>' . $red . '</p>', ['scope_and_content' => $secret], ['scope_and_content' => $red]),
    []
);
check(
    'a short value is not verified, so it cannot withhold every page',
    $detectLeaks->invoke(null, '<p>Created in 1994, catalogued 1994.</p>', ['event_dates' => '1994'], ['event_dates' => $red]),
    []
);
check(
    'a passthrough field is not a leak',
    $detectLeaks->invoke(null, "<p>{$secret}</p>", ['title' => $secret], ['title' => $secret]),
    []
);

echo "\nUnverifiable is not the same as clean\n";

check('normalise decodes entities and collapses space', $normalise->invoke(null, '<p>a &amp;  b<br>c</p>'), 'a & b c');

// Invalid UTF-8 makes the /u collapse fail. That must surface as "cannot verify",
// not as an empty haystack in which nothing is ever found.
$badUtf8 = "<p>Donor was Mrs A. Khumalo of 14 Rose Street, Durban \xC3\x28 trailing</p>";
check('normalise returns null when the page cannot be read', $normalise->invoke(null, $badUtf8), null);
check(
    'detectLeaks returns null rather than "no leaks"',
    $detectLeaks->invoke(null, $badUtf8, ['scope_and_content' => $secret], ['scope_and_content' => $red]),
    null
);

printf("\n%d passed, %d failed\n", $passed, $failed);
exit($failed === 0 ? 0 : 1);
