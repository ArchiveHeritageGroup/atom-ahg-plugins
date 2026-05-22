<?php

/**
 * aiProvenanceVerifyTask - verify Ed25519 signatures on recorded AI inferences.
 *
 * Issue #140, acceptance criterion: "a recorded signature verifies against the
 * public key". Also the audit primitive for the AI ops runbook (heratio#142):
 * an operator can re-derive the canonical manifest of any ahg_ai_inference row
 * and confirm its detached signature.
 *
 * Copyright (C) 2026 Johan Pieterse, Plain Sailing Information Systems
 * The Archive and Heritage Group (Pty) Ltd. Licensed GPL-3.0-or-later.
 */

require_once dirname(__FILE__) . '/../Service/InferenceRecord.php';
require_once dirname(__FILE__) . '/../Service/InferenceSigner.php';
require_once dirname(__FILE__) . '/../Service/InferenceService.php';

use AhgProvenancePlugin\Service\InferenceService;
use AhgProvenancePlugin\Service\InferenceSigner;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Re-verify the Ed25519 signature on recorded AI inferences.
 *
 *     php symfony ai-provenance:verify                 verify the 100 newest signed rows
 *     php symfony ai-provenance:verify --id=42         verify a single row
 *     php symfony ai-provenance:verify --limit=500     widen the batch
 *
 * Exit code is non-zero when any row fails verification.
 */
class aiProvenanceVerifyTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Verify a single ahg_ai_inference row by id'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Max rows to verify', 100),
        ]);

        $this->namespace = 'ai-provenance';
        $this->name = 'verify';
        $this->briefDescription = 'Verify Ed25519 signatures on recorded AI inferences';
        $this->detailedDescription = <<<EOD
The [ai-provenance:verify|INFO] task re-derives the canonical manifest of each
signed ahg_ai_inference row and verifies its detached Ed25519 signature against
the operator public key (issue #140 / heratio#136).

  [php symfony ai-provenance:verify|INFO]
  [php symfony ai-provenance:verify --id=42|INFO]
  [php symfony ai-provenance:verify --limit=500|INFO]

Rows signed by a retired key (signer_key_id other than the current key) are
skipped with a note. The task exits non-zero if any row fails verification.
EOD;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        \AhgCore\Core\AhgDb::init();

        $signer    = new InferenceSigner();
        $publicKey = $signer->publicKey();
        if ($publicKey === null) {
            $this->logSection('ai-provenance', 'No signing keypair found - run ai-provenance:keygen first.', null, 'ERROR');

            return 1;
        }
        $currentKeyId = $signer->keyId();
        $this->logSection('ai-provenance', 'Verifying against signer key ' . ($currentKeyId ?: '(unknown)'));

        $service = new InferenceService();

        $query = Capsule::table('ahg_ai_inference')
            ->whereNotNull('signature')
            ->orderBy('id');
        if (!empty($options['id'])) {
            $query->where('id', (int) $options['id']);
        } else {
            $query->limit((int) ($options['limit'] ?: 100));
        }
        $rows = $query->get();

        if (count($rows) === 0) {
            $this->logSection('ai-provenance', 'No signed inference rows to verify.');

            return 0;
        }

        $pass = 0;
        $fail = 0;
        $skip = 0;

        foreach ($rows as $row) {
            // A row signed by a retired key cannot be checked with the public
            // half we currently hold - report and move on.
            if (!empty($row->signer_key_id) && $currentKeyId !== null && $row->signer_key_id !== $currentKeyId) {
                $this->logSection('ai-provenance', sprintf('id %-6d SKIP  signed by retired key %s', $row->id, $row->signer_key_id));
                $skip++;
                continue;
            }

            $ok = false;
            try {
                $ok = $signer->verify((string) $row->signature, $service->manifestFromRow($row), $publicKey);
            } catch (\Throwable $e) {
                $this->logSection('ai-provenance', sprintf('id %-6d ERROR %s', $row->id, $e->getMessage()), null, 'ERROR');
            }

            if ($ok) {
                $pass++;
                $this->logSection('ai-provenance', sprintf('id %-6d PASS  %s  %s', $row->id, $row->service_name, $row->uuid));
            } else {
                $fail++;
                $this->logSection('ai-provenance', sprintf('id %-6d FAIL  %s  %s', $row->id, $row->service_name, $row->uuid), null, 'ERROR');
            }
        }

        $this->logSection('ai-provenance', sprintf('Done: %d pass, %d fail, %d skipped.', $pass, $fail, $skip));

        return $fail > 0 ? 1 : 0;
    }
}
