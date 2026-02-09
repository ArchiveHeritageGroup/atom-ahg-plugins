<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * CLI task to mint DOIs.
 */
class doiMintTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Specific information object ID'),
            new sfCommandOption('repository', null, sfCommandOption::PARAMETER_OPTIONAL, 'Repository ID or identifier'),
            new sfCommandOption('level', null, sfCommandOption::PARAMETER_OPTIONAL, 'Level of description (fonds, series, item)'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Maximum records to mint', 10),
            new sfCommandOption('state', null, sfCommandOption::PARAMETER_OPTIONAL, 'DOI state: draft, registered, findable', 'findable'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Preview without minting'),
        ]);

        $this->namespace = 'doi';
        $this->name = 'mint';
        $this->briefDescription = 'Mint DOIs for records via DataCite';
        $this->detailedDescription = <<<EOF
Mint DOIs for information objects using DataCite.

Examples:
  php symfony doi:mint --id=123                        # Mint single record
  php symfony doi:mint --repository=1 --level=fonds   # Batch mint fonds
  php symfony doi:mint --repository=1 --limit=50      # Mint up to 50 records
  php symfony doi:mint --id=123 --state=draft         # Create as draft DOI
  php symfony doi:mint --dry-run                      # Preview without minting
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
        $state = $options['state'] ?? 'findable';

        // Single record minting
        if ($options['id']) {
            return $this->mintSingle((int) $options['id'], $service, $state, $dryRun);
        }

        // Batch minting
        return $this->mintBatch($options, $service, $state, $dryRun);
    }

    protected function mintSingle(int $objectId, $service, string $state, bool $dryRun): int
    {
        $this->logSection('doi', "Minting DOI for object #{$objectId}...");

        // Check if already has DOI
        $existing = DB::table('ahg_doi')
            ->where('information_object_id', $objectId)
            ->first();

        if ($existing) {
            $this->logSection('doi', "Object already has DOI: {$existing->doi}", null, 'COMMENT');

            return 0;
        }

        if ($dryRun) {
            $this->logSection('doi', '[DRY RUN] Would mint DOI', null, 'COMMENT');

            return 0;
        }

        $result = $service->mintDoi($objectId, $state);

        if ($result['success']) {
            $this->logSection('doi', "SUCCESS: {$result['doi']}", null, 'INFO');
            $this->logSection('doi', "URL: {$result['url']}");
            $this->logSection('doi', "Status: {$result['status']}");
        } else {
            $this->logSection('doi', "FAILED: {$result['error']}", null, 'ERROR');

            return 1;
        }

        return 0;
    }

    protected function mintBatch(array $options, $service, string $state, bool $dryRun): int
    {
        $this->logSection('doi', 'Searching for records to mint...');

        // Build query
        $query = DB::table('information_object as io')
            ->leftJoin('ahg_doi as d', 'io.id', '=', 'd.information_object_id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->whereNull('d.id') // No existing DOI
            ->where('io.id', '!=', 1) // Not root
            ->select('io.id', 'ioi.title');

        // Filter by repository
        if ($options['repository']) {
            $repoId = $options['repository'];
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
        if ($options['level']) {
            $levelId = DB::table('term_i18n')
                ->where('name', 'LIKE', $options['level'])
                ->where('culture', 'en')
                ->value('id');
            if ($levelId) {
                $query->where('io.level_of_description_id', $levelId);
            }
        }

        $limit = (int) ($options['limit'] ?? 10);
        $records = $query->limit($limit)->get();

        if ($records->isEmpty()) {
            $this->logSection('doi', 'No records found requiring DOI minting');

            return 0;
        }

        $this->logSection('doi', "Found {$records->count()} records" . ($dryRun ? ' [DRY RUN]' : ''));

        $success = 0;
        $failed = 0;

        foreach ($records as $record) {
            $title = $record->title ?? "Object #{$record->id}";
            $this->logSection('doi', "Processing: {$title}");

            if ($dryRun) {
                $this->logSection('doi', '  [WOULD MINT]', null, 'COMMENT');

                continue;
            }

            $result = $service->mintDoi($record->id, $state);

            if ($result['success']) {
                $this->logSection('doi', "  SUCCESS: {$result['doi']}", null, 'INFO');
                ++$success;
            } else {
                $this->logSection('doi', "  FAILED: {$result['error']}", null, 'ERROR');
                ++$failed;
            }
        }

        if (!$dryRun) {
            $this->logSection('doi', "Complete: {$success} minted, {$failed} failed");
        }

        return $failed > 0 ? 1 : 0;
    }
}
