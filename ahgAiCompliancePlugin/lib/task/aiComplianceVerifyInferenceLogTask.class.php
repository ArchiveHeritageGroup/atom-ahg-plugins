<?php
/**
 * PSIS / AtoM-AHG - walk the ai_inference_log chain, verify hashes + signatures.
 *
 * Equivalent to Heratio's `php artisan ai-compliance:verify-inference-log`.
 * Exit code 0 = chain intact end-to-end. Non-zero = first broken seq + reason.
 *
 *   php symfony ai-compliance:verify-inference-log
 *   php symfony ai-compliance:verify-inference-log --from=2026-08-02T00:00:00Z
 *   php symfony ai-compliance:verify-inference-log --to=2026-08-03T00:00:00Z
 *   php symfony ai-compliance:verify-inference-log --service=llm
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */

use AhgInferenceReceipts\ReceiptChain;
use AhgInferenceReceipts\Signer;
use Illuminate\Database\Capsule\Manager as DB;

class aiComplianceVerifyInferenceLogTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('from', null, sfCommandOption::PARAMETER_OPTIONAL, 'Start verification from this ISO timestamp (inclusive)'),
            new sfCommandOption('to', null, sfCommandOption::PARAMETER_OPTIONAL, 'Stop verification at this ISO timestamp (inclusive)'),
            new sfCommandOption('service', null, sfCommandOption::PARAMETER_OPTIONAL, 'Restrict report to a specific service name (still walks full chain - filter is for narration)'),
            new sfCommandOption('quiet-pass', null, sfCommandOption::PARAMETER_NONE, 'Suppress per-chunk progress on PASS'),
        ]);

        $this->namespace        = 'ai-compliance';
        $this->name             = 'verify-inference-log';
        $this->briefDescription = 'Walk the ai_inference_log chain and validate hashes + signatures';
        $this->detailedDescription = <<<EOF
The [ai-compliance:verify-inference-log|INFO] task walks the append-only
ai_inference_log chain, recomputes each entry's hash from the canonical
(JCS) form of its signing view, validates the Ed25519 signature against the
registered public key, and reports tampering at the first broken receipt.

  [php symfony ai-compliance:verify-inference-log|INFO]
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        $this->bootFramework();

        $count = (int) DB::table('ai_inference_log')->count();
        if ($count === 0) {
            $this->logSection('ai-compliance', 'ai_inference_log is empty; nothing to verify.');
            return 0;
        }

        $fromSeq = $this->resolveSeq($options['from'] ?? null, 'from');
        $toSeq   = $this->resolveSeq($options['to'] ?? null, 'to');

        if (empty($options['quiet-pass'])) {
            $msg = sprintf('Verifying chain (rows: %d', $count);
            if ($fromSeq !== null) { $msg .= ", from seq {$fromSeq}"; }
            if ($toSeq !== null)   { $msg .= ", to seq {$toSeq}"; }
            $msg .= ')...';
            $this->logSection('ai-compliance', $msg);
        }

        $resolver = new KeyResolver();
        $store    = new PropelChainStore();

        // ReceiptChain needs a Signer for the append() path; verify() does not
        // call sign() so we hand it a no-op signer wrapping a throwaway keypair.
        // Verification authenticates against the resolver, not this signer.
        $throwaway = \AhgInferenceReceipts\KeyPair::generate();
        $signer    = new Signer($throwaway);
        $chain     = new ReceiptChain($store, $signer, $resolver->asCallable());

        $started = microtime(true);
        $result  = $chain->verify($fromSeq ?? 0, $toSeq);
        $elapsed = number_format((microtime(true) - $started) * 1000, 1);

        if ($result->ok) {
            $this->logSection('ai-compliance', sprintf('PASS - %d receipts verified in %s ms', $result->checkedCount, $elapsed));
            return 0;
        }

        $this->logSection('ai-compliance', sprintf('FAIL at seq %d: %s', $result->brokenAtSeq, $result->reason), null, 'ERROR');
        $this->logBlock([
            '',
            'Investigation pointers:',
            sprintf('  - inspect: SELECT * FROM ai_inference_log WHERE seq = %d', $result->brokenAtSeq),
            '  - the failure point is the FIRST broken receipt; tampering further down is masked until this is resolved',
            '  - if the kid is unknown, check the ai_inference_key table',
            '',
        ], 'ERROR');

        return 1;
    }

    private function resolveSeq(?string $iso, string $bound): ?int
    {
        if (empty($iso)) {
            return null;
        }

        $direction = $bound === 'from' ? '>=' : '<=';

        $row = DB::table('ai_inference_log')
            ->where('ts', $direction, $iso)
            ->orderBy('seq', $bound === 'from' ? 'asc' : 'desc')
            ->first(['seq']);

        return $row === null ? null : (int) $row->seq;
    }

    private function bootFramework(): void
    {
        $databaseManager = new sfDatabaseManager($this->configuration);

        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
    }
}
