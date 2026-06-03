<?php

/**
 * C2PA content-credential signing (Heratio #749/#753 parity).
 *
 * Usage:
 *   php symfony c2pa:generate-key             # mint an Ed25519 key (store the secret in ahg_settings)
 *   php symfony c2pa:sign --io=900985         # build + sign + store a manifest for an IO
 *   php symfony c2pa:sign --verify=12         # verify a stored manifest by id
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 */
class c2paSignTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('io', null, sfCommandOption::PARAMETER_OPTIONAL, 'Information object id to sign'),
            new sfCommandOption('verify', null, sfCommandOption::PARAMETER_OPTIONAL, 'Verify a stored manifest by id'),
            new sfCommandOption('generate-key', null, sfCommandOption::PARAMETER_NONE, 'Mint a new Ed25519 signing key'),
            new sfCommandOption('action', null, sfCommandOption::PARAMETER_OPTIONAL, 'C2PA action to record (e.g. published)'),
            new sfCommandOption('allow-training', null, sfCommandOption::PARAMETER_NONE, 'Declare AI training/mining permitted'),
        ]);

        $this->namespace = 'c2pa';
        $this->name = 'sign';
        $this->briefDescription = 'Generate, sign, store and verify C2PA content credentials';
        $this->detailedDescription = <<<EOF
Builds + signs a C2PA 2.1 manifest (Standard Metadata Assertions, GPS-gated by
#751, plus a training-mining declaration) for an information object and stores
it in ahg_c2pa_manifest.

  php symfony c2pa:generate-key
  php symfony c2pa:sign --io=900985 --action=published
  php symfony c2pa:sign --verify=12
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        require_once sfConfig::get('sf_plugins_dir') . '/ahgMetadataExportPlugin/lib/C2pa/C2paManifestService.php';

        if (!empty($options['generate-key'])) {
            $k = \AhgMetadataExport\C2pa\C2paManifestService::generateKey();
            $this->logSection('c2pa', 'New Ed25519 key — store the SECRET in ahg_settings (key=c2pa_secret_key) or app_c2pa_secret_key_b64:');
            $this->log('  kid:        ' . $k['kid']);
            $this->log('  secret_b64: ' . $k['secret_b64']);
            $this->log('  public_b64: ' . $k['public_b64']);

            return 0;
        }

        $service = new \AhgMetadataExport\C2pa\C2paManifestService();

        if (!empty($options['verify'])) {
            $res = $service->verifyStored((int) $options['verify']);
            $this->logSection('c2pa', $res === null ? 'Manifest not found.' : ('Signature ' . ($res ? 'VALID' : 'INVALID')));

            return $res ? 0 : 1;
        }

        if (empty($options['io'])) {
            $this->logSection('c2pa', 'Specify --io=ID, --verify=ID, or --generate-key', null, 'ERROR');

            return 1;
        }

        if (!$service->isConfigured()) {
            $this->logSection('c2pa', 'No signing key configured. Run c2pa:generate-key and store the secret first.', null, 'ERROR');

            return 1;
        }

        $signed = $service->signInformationObject((int) $options['io'], [
            'action'            => $options['action'] ?? null,
            'trainingPermitted' => !empty($options['allow-training']),
        ]);
        $this->logSection('c2pa', sprintf('Signed manifest %s (kid=%s, %d assertions).',
            $signed['manifest_label'] ?? '?', $signed['claim_signature']['kid'] ?? '?', count($signed['assertions'] ?? [])));

        return 0;
    }
}
