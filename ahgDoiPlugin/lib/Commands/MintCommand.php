<?php

namespace AtomFramework\Console\Commands\Doi;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Mint DOIs for archival records via DataCite.
 */
class MintCommand extends BaseCommand
{
    protected string $name = 'doi:mint';
    protected string $description = 'Mint DOIs for archival records';
    protected string $detailedDescription = <<<'EOF'
    Mint DOIs for information objects using DataCite.

    Examples:
      php bin/atom doi:mint --id=123                        Mint single record
      php bin/atom doi:mint --repository=1 --level=fonds   Batch mint fonds
      php bin/atom doi:mint --repository=1 --limit=50      Mint up to 50 records
      php bin/atom doi:mint --id=123 --state=draft         Create as draft DOI
      php bin/atom doi:mint --dry-run                      Preview without minting
    EOF;

    protected function configure(): void
    {
        $this->addOption('id', null, 'Specific information object ID');
        $this->addOption('repository', null, 'Repository ID or identifier');
        $this->addOption('level', null, 'Level of description (fonds, series, item)');
        $this->addOption('limit', null, 'Maximum records to mint', '10');
        $this->addOption('state', null, 'DOI state: draft, registered, findable', 'findable');
        $this->addOption('dry-run', null, 'Preview without minting');
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
        $state = $this->option('state', 'findable');

        // Single record minting
        if ($this->hasOption('id')) {
            return $this->mintSingle((int) $this->option('id'), $service, $state, $dryRun);
        }

        // Batch minting
        return $this->mintBatch($service, $state, $dryRun);
    }

    protected function mintSingle(int $objectId, $service, string $state, bool $dryRun): int
    {
        $this->info("Minting DOI for object #{$objectId}...");

        // Check if already has DOI
        $existing = DB::table('ahg_doi')
            ->where('information_object_id', $objectId)
            ->first();

        if ($existing) {
            $this->comment("Object already has DOI: {$existing->doi}");

            return 0;
        }

        if ($dryRun) {
            $this->comment('[DRY RUN] Would mint DOI');

            return 0;
        }

        $result = $service->mintDoi($objectId, $state);

        if ($result['success']) {
            $this->success("DOI minted: {$result['doi']}");
            $this->line("  URL: {$result['url']}");
            $this->line("  Status: {$result['status']}");
        } else {
            $this->error("FAILED: {$result['error']}");

            return 1;
        }

        return 0;
    }

    protected function mintBatch($service, string $state, bool $dryRun): int
    {
        $this->info('Searching for records to mint...');

        // Build query
        $query = DB::table('information_object as io')
            ->leftJoin('ahg_doi as d', 'io.id', '=', 'd.information_object_id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->whereNull('d.id') // No existing DOI
            ->where('io.id', '!=', 1) // Not root
            ->select('io.id', 'ioi.title');

        // Filter by repository
        if ($this->hasOption('repository')) {
            $repoId = $this->option('repository');
            if (!is_numeric($repoId)) {
                $repoId = DB::table('repository')
                    ->where('identifier', $repoId)
                    ->value('id');
            }
            if ($repoId) {
                $query->where('io.repository_id', $repoId);
            }
        }

        // Filter by level
        if ($this->hasOption('level')) {
            $levelId = DB::table('term_i18n')
                ->where('name', 'LIKE', $this->option('level'))
                ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
                ->value('id');
            if ($levelId) {
                $query->where('io.level_of_description_id', $levelId);
            }
        }

        $limit = (int) $this->option('limit', '10');
        $records = $query->limit($limit)->get();

        if ($records->isEmpty()) {
            $this->info('No records found requiring DOI minting');

            return 0;
        }

        $this->info("Found {$records->count()} records" . ($dryRun ? ' [DRY RUN]' : ''));

        $success = 0;
        $failed = 0;

        foreach ($records as $record) {
            $title = $record->title ?? "Object #{$record->id}";
            $this->line("  Processing: {$title}");

            if ($dryRun) {
                $this->comment('    [WOULD MINT]');

                continue;
            }

            $result = $service->mintDoi($record->id, $state);

            if ($result['success']) {
                $this->success("  DOI: {$result['doi']}");
                ++$success;
            } else {
                $this->error("  FAILED: {$result['error']}");
                ++$failed;
            }
        }

        if (!$dryRun) {
            $this->info("Complete: {$success} minted, {$failed} failed");
        }

        return $failed > 0 ? 1 : 0;
    }
}
