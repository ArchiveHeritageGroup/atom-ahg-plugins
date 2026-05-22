<?php

/**
 * InferenceSignerTest - standalone test for the AI inference signing crypto.
 *
 * Issue #140. Pure unit test, no AtoM / Symfony bootstrap required:
 *
 *     php ahgProvenancePlugin/testing/InferenceSignerTest.php
 *
 * Exit code 0 = all assertions passed, 1 = a failure.
 *
 * Copyright (C) 2026 Johan Pieterse, Plain Sailing Information Systems
 * The Archive and Heritage Group (Pty) Ltd. Licensed GPL-3.0-or-later.
 */

require_once dirname(__FILE__) . '/../lib/Service/InferenceRecord.php';
require_once dirname(__FILE__) . '/../lib/Service/InferenceSigner.php';
require_once dirname(__FILE__) . '/../lib/Service/InferenceService.php';

use AhgProvenancePlugin\Service\InferenceSigner;
use AhgProvenancePlugin\Service\InferenceService;

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

$tmpDir = sys_get_temp_dir() . '/ahg-sign-test-' . bin2hex(random_bytes(6));

// ── 1. Signing is opt-in: null before keygen ────────────────────────────────
$signer = new InferenceSigner($tmpDir);
check('signing opt-in: isEnabled() false before keygen', $signer->isEnabled() === false);
check('signing opt-in: keyId() null before keygen', $signer->keyId() === null);
check('signing opt-in: sign() returns null before keygen', $signer->sign(['id' => 1]) === null);

// ── 2. Generate → sign → verify round trip ──────────────────────────────────
$keyId = $signer->generateKeypair();
check('keygen: signer_key_id has ed25519: prefix', strpos($keyId, 'ed25519:') === 0);
check('keygen: isEnabled() true after keygen', $signer->isEnabled() === true);
check('keygen: keyId() matches returned id', $signer->keyId() === $keyId);

$manifest  = ['id' => 42, 'uuid' => 'abc-123', 'input_hash' => 'aa', 'output_hash' => 'bb', 'confidence' => 0.91, 'service_name' => 'NER'];
$signature = $signer->sign($manifest);
check('sign: returns a string signature', is_string($signature) && $signature !== '');
check('verify: round trip with correct key', $signer->verify($signature, $manifest, $signer->publicKey()) === true);

// ── 3. Verify fails on a tampered manifest ──────────────────────────────────
$tampered = $manifest;
$tampered['output_hash'] = 'CC';
check('verify: fails on tampered manifest', $signer->verify($signature, $tampered, $signer->publicKey()) === false);

// ── 4. Verify fails against the wrong public key ────────────────────────────
$wrongPublicKey = sodium_crypto_sign_publickey(sodium_crypto_sign_keypair());
check('verify: fails against wrong public key', $signer->verify($signature, $manifest, $wrongPublicKey) === false);

// ── 5. canonicalize() is key-order independent ──────────────────────────────
$a = $signer->canonicalize(['b' => 2, 'a' => 1, 'c' => ['z' => 9, 'y' => 8]]);
$b = $signer->canonicalize(['c' => ['y' => 8, 'z' => 9], 'a' => 1, 'b' => 2]);
check('canonicalize: key-order independent', $a === $b);

// ── 6. keygen refuses to clobber without --force ────────────────────────────
$clobberRefused = false;
try {
    $signer->generateKeypair();
} catch (\RuntimeException $e) {
    $clobberRefused = strpos($e->getMessage(), 'already exists') !== false;
}
check('keygen: refuses to overwrite without force', $clobberRefused);

$regenerated = $signer->generateKeypair(true);
check('keygen: --force mints a fresh keypair', strpos($regenerated, 'ed25519:') === 0 && $regenerated !== $keyId);

// ── 7. InferenceService::composeModelManifest() ─────────────────────────────
$mm = InferenceService::composeModelManifest('trocr-base-handwritten', '1.0', 'HTR', null);
check('composeModelManifest: carries live model identity', $mm['model_name'] === 'trocr-base-handwritten' && $mm['service_name'] === 'HTR');
check('composeModelManifest: synthesises model_id', $mm['model_id'] === 'trocr-base-handwritten@1.0');

$mmOverlay = InferenceService::composeModelManifest('qwen3:8b', 'unknown', 'LLM', ['model_id' => 'curated-id', 'publisher' => 'AHG']);
check('composeModelManifest: keeps operator-curated model_id', $mmOverlay['model_id'] === 'curated-id' && $mmOverlay['publisher'] === 'AHG');

// ── 8. InferenceService::normalizeConfidence() ──────────────────────────────
check('normalizeConfidence: null passes through', InferenceService::normalizeConfidence(null) === null);
check('normalizeConfidence: decimal string from DB casts to float', InferenceService::normalizeConfidence('0.95000') === 0.95);
check('normalizeConfidence: rounds to column precision (5dp)', InferenceService::normalizeConfidence(0.123456789) === 0.12346);

// ── 9. record() / verify round trip through the single manifest builder ─────
// record() signs a manifest built from a native-typed row; the verify task
// rebuilds it from a row the DB hands back (bigint/decimal columns as strings).
// manifestFromRow() must coerce both to the identical canonical manifest.
$svc   = new InferenceService();
$mmRow = InferenceService::composeModelManifest('trocr-base-handwritten', '1.2', 'HTR', null);

$signTimeRow = (object) [
    'id' => 7, 'uuid' => 'uuid-rt-1', 'occurred_at' => '2026-05-22 12:00:00',
    'service_name' => 'HTR', 'model_name' => 'trocr-base-handwritten', 'model_version' => '1.2',
    'input_hash' => str_repeat('a', 64), 'output_hash' => str_repeat('b', 64),
    'confidence' => 0.87654, 'model_manifest' => json_encode($mmRow),
    'target_entity_type' => 'information_object', 'target_entity_id' => 4321, 'target_field' => 'transcript',
];
$rtSig = $signer->sign($svc->manifestFromRow($signTimeRow));

$dbRow = (object) [
    'id' => '7', 'uuid' => 'uuid-rt-1', 'occurred_at' => '2026-05-22 12:00:00',
    'service_name' => 'HTR', 'model_name' => 'trocr-base-handwritten', 'model_version' => '1.2',
    'input_hash' => str_repeat('a', 64), 'output_hash' => str_repeat('b', 64),
    'confidence' => '0.87654', 'model_manifest' => json_encode($mmRow),
    'target_entity_type' => 'information_object', 'target_entity_id' => '4321', 'target_field' => 'transcript',
];
check('round trip: DB-typed row reproduces the signed manifest',
    $signer->verify($rtSig, $svc->manifestFromRow($dbRow), $signer->publicKey()) === true);

$tamperedRow = clone $dbRow;
$tamperedRow->output_hash = str_repeat('c', 64);
check('round trip: a changed output_hash fails verification',
    $signer->verify($rtSig, $svc->manifestFromRow($tamperedRow), $signer->publicKey()) === false);

// ── cleanup ─────────────────────────────────────────────────────────────────
foreach (glob($tmpDir . '/*') ?: [] as $f) {
    @unlink($f);
}
@rmdir($tmpDir);

echo "\n{$passed} passed, {$failed} failed.\n";
exit($failed > 0 ? 1 : 0);
