<?php

/**
 * php symfony narssa:transfer-package
 *
 * Build a NARSSA-compliant transfer package (METS + EAD2002 + digital files +
 * manifest.csv) from approved 'transfer_narssa' disposal actions OR from an
 * explicit list of information_object IDs.
 *
 * Usage:
 *   php symfony narssa:transfer-package                         # all approved transfer_narssa disposals
 *   php symfony narssa:transfer-package --io-ids=886,553        # explicit list
 *   php symfony narssa:transfer-package --user-id=1 --title="Q1 2027 transfer"
 *
 * Output: package at uploads/narssa/<reference>.tar.gz; row in narssa_transfer.
 */
class narssaTransferPackageTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('io-ids', null, sfCommandOption::PARAMETER_OPTIONAL, 'CSV of information_object IDs (skips disposal-driven mode)'),
            new sfCommandOption('user-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'user.id to record as initiator', null),
            new sfCommandOption('title', null, sfCommandOption::PARAMETER_OPTIONAL, 'Title for the transfer record', null),
            new sfCommandOption('description', null, sfCommandOption::PARAMETER_OPTIONAL, 'Description for the transfer record', null),
        ]);

        $this->namespace = 'narssa';
        $this->name = 'transfer-package';
        $this->briefDescription = 'Build a NARSSA-compliant transfer package (METS + EAD2002 + files)';
    }

    public function execute($arguments = [], $options = [])
    {
        $cfg = ProjectConfiguration::getApplicationConfiguration('qubit', 'cli', false);
        sfContext::createInstance($cfg);

        require_once sfConfig::get('sf_plugins_dir') . '/ahgNARSSAPlugin/lib/Services/TransferPackageService.php';
        $svc = new \AhgNARSSA\Services\TransferPackageService();

        $userId = $options['user-id'] !== null ? (int) $options['user-id'] : null;

        if (!empty($options['io-ids'])) {
            $ids = array_filter(array_map('intval', explode(',', (string) $options['io-ids'])));
            $result = $svc->build($ids, $userId, $options['title'] ?? null, $options['description'] ?? null);
        } else {
            $result = $svc->buildFromApprovedDisposals($userId);
            if (empty($result['transfer_id'])) {
                $this->log('No approved transfer_narssa disposals to package.');
                return 0;
            }
        }

        $this->logSection('narssa', sprintf(
            'Packaged transfer #%d (%s): %d items, %s bytes, package=%s',
            $result['transfer_id'],
            $result['reference'],
            $result['item_count'],
            number_format($result['total_bytes']),
            $result['package_path'],
        ));
        $this->log('SHA-256: ' . $result['package_sha256']);
        return 0;
    }
}
