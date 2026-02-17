<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * CLI task to deactivate DOIs (create tombstones).
 */
class doiDeactivateTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('id', null, sfCommandOption::PARAMETER_OPTIONAL, 'DOI record ID to deactivate'),
            new sfCommandOption('object-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Information object ID to deactivate DOI for'),
            new sfCommandOption('reason', null, sfCommandOption::PARAMETER_OPTIONAL, 'Reason for deactivation'),
            new sfCommandOption('reactivate', null, sfCommandOption::PARAMETER_NONE, 'Reactivate instead of deactivate'),
            new sfCommandOption('list-deleted', null, sfCommandOption::PARAMETER_NONE, 'List all deactivated DOIs'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Preview without making changes'),
        ]);

        $this->namespace = 'doi';
        $this->name = 'deactivate';
        $this->briefDescription = 'Deactivate DOIs (create tombstones)';
        $this->detailedDescription = <<<EOF
Deactivate DOIs when records are deleted or need to be hidden.
Deactivated DOIs maintain their landing page for citation integrity
but are hidden from DataCite discovery.

Examples:
  php symfony doi:deactivate --id=123                      # Deactivate by DOI record ID
  php symfony doi:deactivate --object-id=456              # Deactivate by object ID
  php symfony doi:deactivate --id=123 --reason="Deleted"  # With reason
  php symfony doi:deactivate --id=123 --reactivate        # Reactivate a deactivated DOI
  php symfony doi:deactivate --list-deleted               # List all deactivated DOIs
  php symfony doi:deactivate --id=123 --dry-run           # Preview without changes
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgDoiPlugin/lib/Services/DoiService.php';

        $service = new \ahgDoiPlugin\Services\DoiService();
        $dryRun = !empty($options['dry-run']);

        // List deactivated DOIs
        if (!empty($options['list-deleted'])) {
            return $this->listDeleted();
        }

        // Get DOI record ID
        $doiId = null;
        if (!empty($options['id'])) {
            $doiId = (int) $options['id'];
        } elseif (!empty($options['object-id'])) {
            $objectId = (int) $options['object-id'];
            $doi = DB::table('ahg_doi')
                ->where('information_object_id', $objectId)
                ->first();
            if (!$doi) {
                $this->logSection('doi', "No DOI found for object #{$objectId}", null, 'ERROR');

                return 1;
            }
            $doiId = $doi->id;
        }

        if (!$doiId) {
            $this->logSection('doi', 'Please specify --id or --object-id', null, 'ERROR');

            return 1;
        }

        // Get DOI record
        $doi = DB::table('ahg_doi')->where('id', $doiId)->first();
        if (!$doi) {
            $this->logSection('doi', "DOI record #{$doiId} not found", null, 'ERROR');

            return 1;
        }

        $this->logSection('doi', "DOI: {$doi->doi}");
        $this->logSection('doi', "Current status: {$doi->status}");

        // Reactivate
        if (!empty($options['reactivate'])) {
            return $this->reactivateDoi($doi, $service, $dryRun);
        }

        // Deactivate
        return $this->deactivateDoi($doi, $service, $options['reason'] ?? '', $dryRun);
    }

    protected function deactivateDoi(object $doi, $service, string $reason, bool $dryRun): int
    {
        if ($doi->status === 'deleted') {
            $this->logSection('doi', 'DOI is already deactivated', null, 'COMMENT');

            return 0;
        }

        $this->logSection('doi', 'Deactivating DOI...');
        if ($reason) {
            $this->logSection('doi', "Reason: {$reason}");
        }

        if ($dryRun) {
            $this->logSection('doi', '[DRY RUN] Would deactivate DOI', null, 'COMMENT');

            return 0;
        }

        $result = $service->deactivateDoi($doi->id, $reason);

        if ($result['success']) {
            $this->logSection('doi', 'SUCCESS: DOI deactivated', null, 'INFO');
            $this->logSection('doi', "Previous status: {$result['previous_status']}");
            $this->logSection('doi', 'The DOI now resolves to a tombstone page');
        } else {
            $this->logSection('doi', "FAILED: {$result['error']}", null, 'ERROR');

            return 1;
        }

        return 0;
    }

    protected function reactivateDoi(object $doi, $service, bool $dryRun): int
    {
        if ($doi->status !== 'deleted') {
            $this->logSection('doi', "DOI is not deactivated (status: {$doi->status})", null, 'COMMENT');

            return 0;
        }

        $this->logSection('doi', 'Reactivating DOI...');

        if ($dryRun) {
            $this->logSection('doi', '[DRY RUN] Would reactivate DOI', null, 'COMMENT');

            return 0;
        }

        $result = $service->reactivateDoi($doi->id);

        if ($result['success']) {
            $this->logSection('doi', 'SUCCESS: DOI reactivated', null, 'INFO');
            $this->logSection('doi', 'The DOI is now findable again');
        } else {
            $this->logSection('doi', "FAILED: {$result['error']}", null, 'ERROR');

            return 1;
        }

        return 0;
    }

    protected function listDeleted(): int
    {
        $deleted = DB::table('ahg_doi as d')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('d.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('d.status', 'deleted')
            ->select(['d.id', 'd.doi', 'd.deactivated_at', 'd.deactivation_reason', 'ioi.title'])
            ->orderByDesc('d.deactivated_at')
            ->get();

        if ($deleted->isEmpty()) {
            $this->logSection('doi', 'No deactivated DOIs found');

            return 0;
        }

        $this->logSection('doi', "Found {$deleted->count()} deactivated DOIs:");
        $this->logSection('doi', '');

        foreach ($deleted as $doi) {
            $this->logSection('doi', "ID: {$doi->id}");
            $this->logSection('doi', "  DOI: {$doi->doi}");
            $this->logSection('doi', '  Title: ' . ($doi->title ?? 'Untitled'));
            $this->logSection('doi', '  Deactivated: ' . ($doi->deactivated_at ?? 'Unknown'));
            if ($doi->deactivation_reason) {
                $this->logSection('doi', "  Reason: {$doi->deactivation_reason}");
            }
            $this->logSection('doi', '');
        }

        return 0;
    }
}
