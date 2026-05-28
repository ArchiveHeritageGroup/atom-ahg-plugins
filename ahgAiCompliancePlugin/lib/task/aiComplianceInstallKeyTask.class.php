<?php
/**
 * PSIS / AtoM-AHG - generate or rotate the Ed25519 signing key for the inference chain.
 *
 * Equivalent to Heratio's `php artisan ai-compliance:install-key`.
 *
 *   php symfony ai-compliance:install-key
 *   php symfony ai-compliance:install-key --rotate
 *   php symfony ai-compliance:install-key --rotate --force
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */
class aiComplianceInstallKeyTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('rotate', null, sfCommandOption::PARAMETER_NONE, 'Generate a fresh keypair even if one already exists'),
            new sfCommandOption('force', null, sfCommandOption::PARAMETER_NONE, 'Overwrite without confirmation'),
        ]);

        $this->namespace        = 'ai-compliance';
        $this->name             = 'install-key';
        $this->briefDescription = 'Generate the Ed25519 signing keypair for the AI inference log';
        $this->detailedDescription = <<<EOF
The [ai-compliance:install-key|INFO] task creates the Ed25519 keypair that signs
every receipt in the ai_inference_log chain. The secret key is written to
data/ai-keys/inference-signing.sk (mode 0600); the public key (and the kid)
is also registered in ai_inference_key so the verifier and the
/.well-known/ai-inference-pubkey endpoint can find it.

  [php symfony ai-compliance:install-key|INFO]
  [php symfony ai-compliance:install-key --rotate|INFO]
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        // Ensure Symfony app context + Capsule DB are booted.
        sfContext::createInstance($this->configuration);
        $this->bootFramework();

        $secretPath = SignerFactory::secretPath();
        $publicPath = SignerFactory::publicPath();

        if (is_readable($secretPath) && !$options['rotate']) {
            $this->logSection('ai-compliance', 'Keypair already exists at ' . $secretPath);
            $this->logSection('ai-compliance', 'Use --rotate to generate a fresh one (old key is preserved in ai_inference_key for verifying old receipts).');
            return 0;
        }

        if (is_readable($secretPath) && !$options['force']) {
            $this->logSection('ai-compliance', 'Refusing to overwrite an existing key without --force. Use --rotate --force to proceed.', null, 'ERROR');
            return 1;
        }

        $generated = SignerFactory::generateAndSave($secretPath, $publicPath);
        $keyPair   = $generated['keyPair'];

        $resolver = new KeyResolver();
        $resolver->register($keyPair->kid(), $keyPair->publicKey(), true);

        $this->logSection('ai-compliance', 'Signing keypair installed.');
        $this->logBlock([
            '',
            '  secret: ' . $secretPath . ' (0600)',
            '  public: ' . $publicPath . ' (0644)',
            '  kid:    ' . $keyPair->kid(),
            '  alg:    ed25519',
            '',
            'Public key endpoint: /.well-known/ai-inference-pubkey',
            '',
        ], 'INFO');

        return 0;
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
