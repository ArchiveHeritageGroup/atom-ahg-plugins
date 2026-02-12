<?php

namespace AtomFramework\Console\Commands\Doi;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Deactivate DOIs (create tombstones).
 */
class DeactivateCommand extends BaseCommand
{
    protected string $name = 'doi:deactivate';
    protected string $description = 'Deactivate DOIs';
    protected string $detailedDescription = <<<'EOF'
    Deactivate DOIs when records are deleted or need to be hidden.
    Deactivated DOIs maintain their landing page for citation integrity
    but are hidden from DataCite discovery.

    Examples:
      php bin/atom doi:deactivate --id=123                      Deactivate by DOI record ID
      php bin/atom doi:deactivate --object-id=456              Deactivate by object ID
      php bin/atom doi:deactivate --id=123 --reason="Deleted"  With reason
      php bin/atom doi:deactivate --id=123 --reactivate        Reactivate a deactivated DOI
      php bin/atom doi:deactivate --list-deleted               List all deactivated DOIs
      php bin/atom doi:deactivate --id=123 --dry-run           Preview without changes
    EOF;

    protected function configure(): void
    {
        $this->addOption('id', null, 'DOI record ID to deactivate');
        $this->addOption('object-id', null, 'Information object ID to deactivate DOI for');
        $this->addOption('reason', null, 'Reason for deactivation');
        $this->addOption('reactivate', null, 'Reactivate instead of deactivate');
        $this->addOption('list-deleted', null, 'List all deactivated DOIs');
        $this->addOption('dry-run', null, 'Preview without making changes');
    }

    protected function handle(): int
    {
        $serviceFile = $this->getAtomRoot() . '/plugins/ahgDoiPlugin/lib/Services/DoiService.php';
        if (!file_exists($serviceFile)) {
            $this->error("DoiService not found at: {$serviceFile}");

            return 1;
        }

        require_once $serviceFile;

        $service = new \ahgDoiPlugin\Services\DoiService();
        $dryRun = $this->hasOption('dry-run');

        // List deactivated DOIs
        if ($this->hasOption('list-deleted')) {
            return $this->listDeleted();
        }

        // Get DOI record ID
        $doiId = null;
        if ($this->hasOption('id')) {
            $doiId = (int) $this->option('id');
        } elseif ($this->hasOption('object-id')) {
            $objectId = (int) $this->option('object-id');
            $doi = DB::table('ahg_doi')
                ->where('information_object_id', $objectId)
                ->first();
            if (!$doi) {
                $this->error("No DOI found for object #{$objectId}");

                return 1;
            }
            $doiId = $doi->id;
        }

        if (!$doiId) {
            $this->error('Please specify --id or --object-id');

            return 1;
        }

        // Get DOI record
        $doi = DB::table('ahg_doi')->where('id', $doiId)->first();
        if (!$doi) {
            $this->error("DOI record #{$doiId} not found");

            return 1;
        }

        $this->info("DOI: {$doi->doi}");
        $this->line("  Current status: {$doi->status}");

        // Reactivate
        if ($this->hasOption('reactivate')) {
            return $this->reactivateDoi($doi, $service, $dryRun);
        }

        // Deactivate
        return $this->deactivateDoi($doi, $service, $this->option('reason') ?? '', $dryRun);
    }

    protected function deactivateDoi(object $doi, $service, string $reason, bool $dryRun): int
    {
        if ($doi->status === 'deleted') {
            $this->comment('DOI is already deactivated');

            return 0;
        }

        $this->info('Deactivating DOI...');
        if ($reason) {
            $this->line("  Reason: {$reason}");
        }

        if ($dryRun) {
            $this->comment('[DRY RUN] Would deactivate DOI');

            return 0;
        }

        $result = $service->deactivateDoi($doi->id, $reason);

        if ($result['success']) {
            $this->success('DOI deactivated');
            $this->line("  Previous status: {$result['previous_status']}");
            $this->line('  The DOI now resolves to a tombstone page');
        } else {
            $this->error("FAILED: {$result['error']}");

            return 1;
        }

        return 0;
    }

    protected function reactivateDoi(object $doi, $service, bool $dryRun): int
    {
        if ($doi->status !== 'deleted') {
            $this->comment("DOI is not deactivated (status: {$doi->status})");

            return 0;
        }

        $this->info('Reactivating DOI...');

        if ($dryRun) {
            $this->comment('[DRY RUN] Would reactivate DOI');

            return 0;
        }

        $result = $service->reactivateDoi($doi->id);

        if ($result['success']) {
            $this->success('DOI reactivated');
            $this->line('  The DOI is now findable again');
        } else {
            $this->error("FAILED: {$result['error']}");

            return 1;
        }

        return 0;
    }

    protected function listDeleted(): int
    {
        $deleted = DB::table('ahg_doi as d')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('d.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->where('d.status', 'deleted')
            ->select(['d.id', 'd.doi', 'd.deactivated_at', 'd.deactivation_reason', 'ioi.title'])
            ->orderByDesc('d.deactivated_at')
            ->get();

        if ($deleted->isEmpty()) {
            $this->info('No deactivated DOIs found');

            return 0;
        }

        $this->info("Found {$deleted->count()} deactivated DOIs:");
        $this->newline();

        foreach ($deleted as $doi) {
            $this->bold("  ID: {$doi->id}");
            $this->line("    DOI: {$doi->doi}");
            $this->line('    Title: ' . ($doi->title ?? 'Untitled'));
            $this->line('    Deactivated: ' . ($doi->deactivated_at ?? 'Unknown'));
            if ($doi->deactivation_reason) {
                $this->line("    Reason: {$doi->deactivation_reason}");
            }
            $this->newline();
        }

        return 0;
    }
}
