<?php

/**
 * Pure-logic tests for PiiDetectionService.
 *
 * No database, no Symfony, no container, no fixtures - this file requires the
 * service class and nothing else. That is deliberate: the bugs these cover are
 * decisions (banding, scoring, overlap resolution, what may become a compliance
 * assertion), and a decision extracted as a pure static is testable without the
 * harness this plugin does not have.
 *
 *   php tests/pii_scoring_test.php
 *
 * Exit status is 0 when every case passes, 1 otherwise, so it can be wired into
 * CI unchanged.
 */

require_once __DIR__ . '/../lib/Service/PiiDetectionService.php';

use ahgPrivacyPlugin\Service\PiiDetectionService;

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

function section(string $name): void
{
    printf("\n%s\n", $name);
}

$svc = new PiiDetectionService();

// ── The band enumerations must agree ────────────────────────────────────
section('Risk bands are enumerated in exactly one place');

check(
    'emptySummary carries every band',
    array_keys(PiiDetectionService::emptySummary()),
    ['total', 'critical_risk', 'high_risk', 'medium_risk', 'low_risk']
);

// The regression that motivated all of this: a risk level assigned to a type
// but absent from the band list is silently discarded at count time.
$ref = new ReflectionClass(PiiDetectionService::class);
$levelsProp = $ref->getProperty('riskLevels');
$levelsProp->setAccessible(true);
$assigned = array_values(array_unique($levelsProp->getValue()));
sort($assigned);
$known = PiiDetectionService::RISK_BANDS;
sort($known);
check('every assigned risk level is a known band', array_values(array_diff($assigned, $known)), []);
check('every weighted band is a known band', array_values(array_diff(array_keys(PiiDetectionService::RISK_WEIGHTS), PiiDetectionService::RISK_BANDS)), []);

section('Counting and merging preserve the critical band');

$s = PiiDetectionService::countEntity(PiiDetectionService::emptySummary(), 'critical');
check('countEntity records a critical finding', $s['critical_risk'], 1);
check('countEntity increments the total', $s['total'], 1);
check('a critical finding scores 30, not 0', PiiDetectionService::calculateRiskScore($s), 30);
check('isHighRisk is true for critical alone', PiiDetectionService::isHighRisk($s), true);

$merged = PiiDetectionService::mergeSummary($s, PiiDetectionService::countEntity(PiiDetectionService::emptySummary(), 'critical'));
check('mergeSummary carries critical across scans', $merged['critical_risk'], 2);
check('mergeSummary sums totals', $merged['total'], 2);

$unknown = PiiDetectionService::countEntity(PiiDetectionService::emptySummary(), 'moderate');
check('an unknown band is counted, not dropped', $unknown['total'], 1);

check('score is capped at 100', PiiDetectionService::calculateRiskScore(['critical_risk' => 99]), 100);

// ── Validation gates the compliance assertion ───────────────────────────
section('Only validated findings may assert a category of personal data');

check(
    'an unvalidated high-risk finding is not reportable',
    PiiDetectionService::hasReportableFinding([['risk_level' => 'high', 'validated' => false]]),
    false
);
check(
    'a validated high-risk finding is reportable',
    PiiDetectionService::hasReportableFinding([['risk_level' => 'high', 'validated' => true]]),
    true
);
check(
    'a validated low-risk finding is not reportable',
    PiiDetectionService::hasReportableFinding([['risk_level' => 'low', 'validated' => true]]),
    false
);
check('no findings is not reportable', PiiDetectionService::hasReportableFinding([]), false);

// ── Luhn ────────────────────────────────────────────────────────────────
section('Card numbers are checksum-validated, not shape-matched');

check('a valid card passes Luhn', PiiDetectionService::passesLuhn('4111 1111 1111 1111'), true);
check('a transposed card fails Luhn', PiiDetectionService::passesLuhn('4111111111111112'), false);

// ── Jurisdiction selection never narrows to nothing ─────────────────────
section('Jurisdiction selection widens rather than silently detecting nothing');

$all = PiiDetectionService::patternsForJurisdictions([]);
check('no jurisdiction configured selects every pattern', count($all) > 0 && isset($all['SA_ID'], $all['EMAIL']), true);

$gdpr = PiiDetectionService::patternsForJurisdictions(['gdpr']);
check('universal patterns survive a foreign jurisdiction', isset($gdpr['EMAIL'], $gdpr['CREDIT_CARD']), true);
check('a jurisdiction with no specific patterns still detects', count($gdpr) > 0, true);

$popia = PiiDetectionService::patternsForJurisdictions(['popia']);
check('a configured jurisdiction adds its own patterns', isset($popia['SA_ID'], $popia['PHONE_SA']), true);

section('Unimplemented jurisdictions are reportable, not silent');

check(
    'a jurisdiction with patterns is not reported missing',
    PiiDetectionService::jurisdictionsWithoutPatterns(['popia']),
    []
);
check(
    'a jurisdiction with no patterns is reported',
    PiiDetectionService::jurisdictionsWithoutPatterns(['kenya_dpa']),
    ['kenya_dpa']
);
check(
    'mixed set reports only the uncovered ones',
    PiiDetectionService::jurisdictionsWithoutPatterns(['popia', 'kenya_dpa', 'ndpa', 'lgpd']),
    ['kenya_dpa', 'lgpd']
);
check('matching is case insensitive', PiiDetectionService::jurisdictionsWithoutPatterns(['POPIA']), []);
check('an empty set reports nothing', PiiDetectionService::jurisdictionsWithoutPatterns([]), []);

// Reading the registry must never be able to narrow detection.
check(
    'an unreadable registry falls back to every pattern',
    count(PiiDetectionService::patternsForJurisdictions(PiiDetectionService::installedJurisdictionCodes())),
    count(PiiDetectionService::patternsForJurisdictions([]))
);

// ── End to end through detectPii (still no database) ────────────────────
section('Detection: overlaps, context gates, validation');

$card = $svc->detectPii('Payment made with card 4111 1111 1111 1111 on file.');
check('a valid card is detected once', count($card['entities']), 1);
check('the card is marked validated', $card['entities'][0]['validated'] ?? null, true);
check('the card reaches the critical band', $card['summary']['critical_risk'], 1);
check('the card scores', PiiDetectionService::calculateRiskScore($card['summary']), 30);

$badCard = $svc->detectPii('Payment made with card 4111 1111 1111 1112 on file.');
check('an invalid card yields no finding', count($badCard['entities']), 0);

$ref10 = $svc->detectPii('See reference 1234567890 in the finding aid.');
check("'reference' no longer opens the financial gate", count($ref10['entities']), 0);

$acct = $svc->detectPii('Bank account 1234567890 was closed.');
check('a genuine account number is found exactly once', count($acct['entities']), 1);

$accession = $svc->detectPii('Accession 12345678901 was transferred in 1994.');
check('a bare 11-digit reference is not a national ID', count($accession['entities']), 0);

$nin = $svc->detectPii('His national identity number 12345678901 was recorded.');
check('an 11-digit run in ID context is detected', count($nin['entities']), 1);
check('the NIN is not marked validated', $nin['entities'][0]['validated'] ?? null, false);
check(
    'an unvalidated NIN cannot create an inventory entry',
    PiiDetectionService::hasReportableFinding($nin['entities']),
    false
);

$repeated = $svc->detectPii('Contact a@example.com for details, or write to a@example.com instead.');
check('a repeated value yields distinct offsets', count(array_unique(array_column($repeated['entities'], 'position'))), 2);

printf("\n%d passed, %d failed\n", $passed, $failed);
exit($failed === 0 ? 0 : 1);
