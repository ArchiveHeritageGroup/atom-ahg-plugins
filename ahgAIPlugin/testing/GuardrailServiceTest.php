<?php

/**
 * GuardrailServiceTest - standalone test for the RAG guardrails.
 *
 * Issue #141. Pure unit test, no AtoM / Symfony bootstrap required:
 *
 *     php ahgAIPlugin/testing/GuardrailServiceTest.php
 *
 * Exit code 0 = all assertions passed, 1 = a failure.
 *
 * Copyright (C) 2026 Johan Pieterse, Plain Sailing Information Systems
 * The Archive and Heritage Group (Pty) Ltd. Licensed GPL-3.0-or-later.
 */

require_once dirname(__FILE__) . '/../lib/Services/GuardrailService.php';

$passed = 0;
$failed = 0;

function check(string $label, bool $cond): void
{
    global $passed, $failed;
    if ($cond) {
        $passed++;
        echo "  PASS  {$label}\n";
    } else {
        $failed++;
        echo "  FAIL  {$label}\n";
    }
}

function svc(string $mode): GuardrailService
{
    $cfg = GuardrailService::defaultConfig();
    $cfg['mode'] = $mode;

    return new GuardrailService($cfg);
}

// ── 1. off mode allows everything ───────────────────────────────────────────
$r = svc('off')->inspect([
    'provider' => 'openai', 'user_prompt' => 'secret',
    'data_scope' => 'classified', 'purpose' => 'marketing',
]);
check('off mode: action is allow', $r['action'] === 'allow');
check('off mode: no flags raised', $r['flags'] === []);

// ── 2. block mode blocks out-of-scope data to a cloud provider ──────────────
$r = svc('block')->inspect([
    'provider' => 'openai', 'user_prompt' => 'sensitive record',
    'data_scope' => 'classified', 'purpose' => 'summarization',
]);
check('block mode: out-of-scope cloud request is blocked', $r['action'] === 'block');
check('block mode: reason names the data scope', strpos((string) $r['reason'], 'classified') !== false);

// ── 3. warn mode flags out-of-scope but allows ──────────────────────────────
$r = svc('warn')->inspect([
    'provider' => 'openai', 'user_prompt' => 'x',
    'data_scope' => 'classified', 'purpose' => 'summarization',
]);
check('warn mode: out-of-scope is allowed', $r['action'] === 'allow');
check('warn mode: out-of-scope is flagged', in_array('data_scope_out_of_policy', $r['flags'], true));

// ── 4. local provider carries any scope ─────────────────────────────────────
$r = svc('block')->inspect([
    'provider' => 'ollama', 'user_prompt' => 'x',
    'data_scope' => 'classified', 'purpose' => 'summarization',
]);
check('local provider: any scope allowed even under block mode', $r['action'] === 'allow');

// ── 5. block mode rejects a non-sanctioned purpose ──────────────────────────
$r = svc('block')->inspect([
    'provider' => 'ollama', 'user_prompt' => 'x',
    'data_scope' => 'internal', 'purpose' => 'marketing',
]);
check('block mode: non-sanctioned purpose is blocked', $r['action'] === 'block');
check('block mode: purpose_sanctioned is false', $r['purpose_sanctioned'] === false);

// ── 6. sanctioned purpose is accepted ───────────────────────────────────────
$r = svc('block')->inspect([
    'provider' => 'ollama', 'user_prompt' => 'x',
    'data_scope' => 'internal', 'purpose' => 'summarization',
]);
check('sanctioned purpose: accepted', $r['action'] === 'allow' && $r['purpose_sanctioned'] === true);

// ── 7. mask mode redacts PII on a cloud-bound prompt ────────────────────────
$r = svc('mask')->inspect([
    'provider'    => 'openai',
    'user_prompt' => 'Contact the donor at jane@example.com or +27 11 555 1234.',
    'data_scope'  => 'internal', 'purpose' => 'summarization',
]);
check('mask mode: action is mask', $r['action'] === 'mask');
check('mask mode: at least two PII items masked', $r['pii_masked'] >= 2);
check('mask mode: email redacted', strpos($r['user_prompt'], '[REDACTED:email]') !== false);
check('mask mode: number redacted', strpos($r['user_prompt'], '[REDACTED:number]') !== false);
check('mask mode: original email gone', strpos($r['user_prompt'], 'jane@example.com') === false);

// ── 8. mask mode leaves local-provider prompts untouched ────────────────────
$r = svc('mask')->inspect([
    'provider' => 'ollama', 'user_prompt' => 'Contact jane@example.com',
    'data_scope' => 'internal', 'purpose' => 'summarization',
]);
check('mask mode: local provider prompt not masked', $r['pii_masked'] === 0
    && strpos($r['user_prompt'], 'jane@example.com') !== false);

// ── 9. maskPii leaves short date ranges alone ───────────────────────────────
list($text, $count) = svc('mask')->maskPii('Records covering 1939-1945 in the fonds.');
check('maskPii: date range 1939-1945 not masked', $count === 0 && strpos($text, '1939-1945') !== false);

// ── 10. maskPii counts email + long number ──────────────────────────────────
list($text, $count) = svc('mask')->maskPii('Email a@b.com, ID 1234567890123.');
check('maskPii: counts email and 13-digit number', $count === 2);

// ── 11. grounding high when output echoes the context ───────────────────────
$g = svc('warn')->checkGrounding(
    'Correspondence concerning railway construction projects within Mozambique territory.',
    ['correspondence concerning railway construction projects mozambique territory archival fonds']
);
check('grounding: echoing output is grounded', $g !== null && $g['grounded'] === true);
check('grounding: score above threshold', $g !== null && $g['grounding_score'] > 0.45);

// ── 12. grounding low flags ungrounded output ───────────────────────────────
$g = svc('warn')->checkGrounding(
    'Quantum spectroscopy demonstrates unprecedented photovoltaic efficiency measurements aboard satellites.',
    ['correspondence concerning railway construction projects mozambique territory']
);
check('grounding: unrelated output not grounded', $g !== null && $g['grounded'] === false);
check('grounding: unrelated output flagged low_grounding', $g !== null && $g['flag'] === 'low_grounding');

// ── 13. grounding null when no bundle ───────────────────────────────────────
check('grounding: null when no source bundle', svc('warn')->checkGrounding('any output text here', []) === null);

// ── 14. cloud-provider classification ───────────────────────────────────────
$s = svc('warn');
check('isCloudProvider: ollama is local', $s->isCloudProvider('ollama') === false);
check('isCloudProvider: openai is cloud', $s->isCloudProvider('openai') === true);
check('isCloudProvider: anthropic is cloud', $s->isCloudProvider('anthropic') === true);

// ── 15. summarize folds inspect + grounding ─────────────────────────────────
$s = svc('warn');
$inspect = $s->inspect([
    'provider' => 'openai', 'user_prompt' => 'x',
    'data_scope' => 'classified', 'purpose' => 'summarization',
]);
$summary = $s->summarize($inspect, ['grounding_score' => 0.2, 'grounded' => false, 'flag' => 'low_grounding', 'terms_checked' => 9]);
check('summarize: carries the grounding score', $summary['grounding_score'] === 0.2);
check('summarize: merges both flag sources',
    in_array('data_scope_out_of_policy', $summary['flags'], true)
    && in_array('low_grounding', $summary['flags'], true));

// ── 16. defaults match the Heratio side (cross-codebase consistency) ─────────
$d = GuardrailService::defaultConfig();
check('defaults: mode is warn', $d['mode'] === 'warn');
check('defaults: cloud scopes are public,internal', $d['cloud_allowed_scopes'] === ['public', 'internal']);
check('defaults: grounding threshold is 0.45', $d['grounding_threshold'] === 0.45);

echo "\n{$passed} passed, {$failed} failed.\n";
exit($failed > 0 ? 1 : 0);
