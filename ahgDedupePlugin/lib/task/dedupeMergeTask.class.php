<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * CLI task to merge duplicate records.
 */
class dedupeMergeTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addArguments([
            new sfCommandArgument('detection-id', sfCommandArgument::REQUIRED, 'Detection record ID to merge'),
        ]);

        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('primary', null, sfCommandOption::PARAMETER_OPTIONAL, 'Primary record ID (a or b)', 'a'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Preview merge without making changes'),
            new sfCommandOption('force', null, sfCommandOption::PARAMETER_NONE, 'Skip confirmation prompts'),
        ]);

        $this->namespace = 'dedupe';
        $this->name = 'merge';
        $this->briefDescription = 'Merge duplicate records';
        $this->detailedDescription = <<<EOF
Merge a detected duplicate pair, keeping one record as primary.

The merge operation:
  - Transfers digital objects from secondary to primary
  - Redirects slugs from secondary to primary
  - Moves child records to primary
  - Archives secondary record data
  - Logs the merge for audit

Examples:
  php symfony dedupe:merge 123                    # Merge detection #123, keep record A
  php symfony dedupe:merge 123 --primary=b        # Keep record B as primary
  php symfony dedupe:merge 123 --dry-run          # Preview without making changes
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgDedupePlugin/lib/Services/DedupeService.php';

        $detectionId = (int) $arguments['detection-id'];
        $primaryChoice = strtolower($options['primary']);
        $dryRun = $options['dry-run'];
        $force = $options['force'];

        // Load detection record
        $detection = DB::table('ahg_duplicate_detection')
            ->where('id', $detectionId)
            ->first();

        if (!$detection) {
            $this->logSection('dedupe', "Detection #{$detectionId} not found", null, 'ERROR');

            return 1;
        }

        if ('merged' === $detection->status) {
            $this->logSection('dedupe', 'This duplicate pair has already been merged', null, 'ERROR');

            return 1;
        }

        // Determine primary and secondary records
        $primaryId = 'b' === $primaryChoice ? $detection->record_b_id : $detection->record_a_id;
        $secondaryId = 'b' === $primaryChoice ? $detection->record_a_id : $detection->record_b_id;

        // Load record details
        $primaryRecord = $this->loadRecordDetails($primaryId);
        $secondaryRecord = $this->loadRecordDetails($secondaryId);

        if (!$primaryRecord || !$secondaryRecord) {
            $this->logSection('dedupe', 'One or both records no longer exist', null, 'ERROR');

            return 1;
        }

        // Display merge preview
        $this->logSection('dedupe', '=== Merge Preview ===');
        $this->log('');
        $this->log('PRIMARY RECORD (will be kept):');
        $this->displayRecord($primaryRecord);
        $this->log('');
        $this->log('SECONDARY RECORD (will be merged):');
        $this->displayRecord($secondaryRecord);
        $this->log('');

        // Show what will be transferred
        $this->logSection('dedupe', 'Merge operations:');
        $this->log("  - Transfer {$secondaryRecord->digital_object_count} digital objects");
        $this->log("  - Transfer {$secondaryRecord->child_count} child records");
        $this->log("  - Redirect slug: {$secondaryRecord->slug} -> {$primaryRecord->slug}");
        $this->log('');

        if ($dryRun) {
            $this->logSection('dedupe', 'DRY RUN - No changes made');

            return 0;
        }

        // Confirm unless forced
        if (!$force) {
            $this->logSection('dedupe', 'WARNING: This operation cannot be undone!', null, 'COMMENT');
            $this->log('Use --force to skip this confirmation');

            return 0;
        }

        // Perform merge
        $service = new \ahgDedupePlugin\Services\DedupeService();

        try {
            $result = $service->mergeRecords($detectionId, $primaryId, sfContext::getInstance()->user->getUserId() ?? 1);

            $this->logSection('dedupe', '=== Merge Complete ===');
            $this->log("Primary record: {$primaryId}");
            $this->log("Merge log ID: {$result['merge_log_id']}");
            $this->log("Digital objects moved: {$result['digital_objects_moved']}");
            $this->log("Children moved: {$result['children_moved']}");
            $this->log("Slugs redirected: {$result['slugs_redirected']}");

            return 0;
        } catch (\Exception $e) {
            $this->logSection('dedupe', 'Merge failed: ' . $e->getMessage(), null, 'ERROR');

            return 1;
        }
    }

    protected function loadRecordDetails($recordId)
    {
        return DB::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->select([
                'io.id',
                'io.identifier',
                'io.repository_id',
                'ioi.title',
                'slug.slug',
                DB::raw('(SELECT COUNT(*) FROM digital_object WHERE information_object_id = io.id) as digital_object_count'),
                DB::raw('(SELECT COUNT(*) FROM information_object WHERE parent_id = io.id) as child_count'),
            ])
            ->where('io.id', $recordId)
            ->first();
    }

    protected function displayRecord($record)
    {
        $this->log("  ID: {$record->id}");
        $this->log('  Title: ' . ($record->title ?? 'Untitled'));
        $this->log('  Identifier: ' . ($record->identifier ?? 'N/A'));
        $this->log("  Slug: {$record->slug}");
        $this->log("  Digital Objects: {$record->digital_object_count}");
        $this->log("  Child Records: {$record->child_count}");
    }
}
