<?php

/**
 * aiProvenanceKeygenTask - mint the Ed25519 AI-inference signing keypair.
 *
 * Port of the Heratio `ahg:provenance-ai:keygen` artisan command to the
 * AtoM-AHG side - issue #140 (heratio#136 crypto).
 *
 * Copyright (C) 2026 Johan Pieterse, Plain Sailing Information Systems
 * The Archive and Heritage Group (Pty) Ltd. Licensed GPL-3.0-or-later.
 */

require_once dirname(__FILE__) . '/../Service/InferenceSigner.php';

use AhgProvenancePlugin\Service\InferenceSigner;

/**
 * Generate the Ed25519 keypair used to sign AI inference manifests. Run once
 * per install:
 *
 *     php symfony ai-provenance:keygen
 *
 * The private key lands in the AtoM install's data/ahg-ai-signing/ directory -
 * outside every plugin git repo and never written to the database. Until this
 * task is run, inferences are simply recorded unsigned.
 */
class aiProvenanceKeygenTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('force', null, sfCommandOption::PARAMETER_NONE, 'Replace an existing keypair'),
        ]);

        $this->namespace = 'ai-provenance';
        $this->name = 'keygen';
        $this->briefDescription = 'Generate the Ed25519 keypair that signs AI inference manifests';
        $this->detailedDescription = <<<EOD
The [ai-provenance:keygen|INFO] task mints the Ed25519 keypair used to sign
AI inference manifests (issue #140 / heratio#136).

  [php symfony ai-provenance:keygen|INFO]
  [php symfony ai-provenance:keygen --force|INFO]   replace an existing keypair

The private key is written to the AtoM install's data/ahg-ai-signing/ directory.
It is never stored in the database or git - only the detached signature and a
short signer_key_id reference are persisted on each ahg_ai_inference row. Keep
ed25519.private backed up: losing it means signatures can no longer be minted
(existing signatures still verify against the retained public key).
EOD;
    }

    public function execute($arguments = [], $options = [])
    {
        $signer = new InferenceSigner();

        try {
            $keyId = $signer->generateKeypair(!empty($options['force']));
        } catch (\Throwable $e) {
            $this->logSection('ai-provenance', $e->getMessage(), null, 'ERROR');

            return 1;
        }

        $this->logSection('ai-provenance', 'Ed25519 inference-signing keypair generated.');
        $this->logSection('ai-provenance', 'signer_key_id: ' . $keyId);
        $this->logSection('ai-provenance', 'location:      ' . $signer->keyDir());
        $this->logSection('ai-provenance', 'Keep ed25519.private safe and backed up - it is not in git or the database.');

        return 0;
    }
}
