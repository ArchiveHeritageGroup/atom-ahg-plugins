<?php

namespace AtomFramework\Console\Commands\Dedupe;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Merge duplicate records.
 */
class MergeCommand extends BaseCommand
{
    protected string $name = 'dedupe:merge';
    protected string $description = 'Merge duplicate records';
    protected string $detailedDescription = <<<'EOF'
    Merge a detected duplicate pair, keeping one record as primary.

    The merge operation:
      - Transfers digital objects from secondary to primary
      - Redirects slugs from secondary to primary
      - Moves child records to primary
      - Archives secondary record data
      - Logs the merge for audit

    Examples:
      php bin/atom dedupe:merge 123                    Merge detection #123, keep record A
      php bin/atom dedupe:merge 123 --primary=b        Keep record B as primary
      php bin/atom dedupe:merge 123 --dry-run          Preview without making changes
    EOF;

    protected function configure(): void
    {
        $this->addArgument('detection-id', 'Detection record ID to merge', true);
        $this->addOption('primary', null, 'Primary record ID (a or b)', 'a');
        $this->addOption('dry-run', null, 'Preview merge without making changes');
        $this->addOption('force', 'f', 'Skip confirmation prompts');
    }

    protected function handle(): int
    {
        $serviceFile = $this->getAtomRoot() . '/plugins/ahgDedupePlugin/lib/Services/DedupeService.php';
        if (!file_exists($serviceFile)) {
            $this->error("DedupeService not found at: {$serviceFile}");

            return 1;
        }

        require_once $serviceFile;

        $detectionId = (int) $this->argument('detection-id');
        $primaryChoice = strtolower($this->option('primary', 'a'));
        $dryRun = $this->hasOption('dry-run');
        $force = $this->hasOption('force');

        // Load detection record
        $detection = DB::table('ahg_duplicate_detection')
            ->where('id', $detectionId)
            ->first();

        if (!$detection) {
            $this->error("Detection #{$detectionId} not found");

            return 1;
        }

        if ('merged' === $detection->status) {
            $this->error('This duplicate pair has already been merged');

            return 1;
        }

        // Determine primary and secondary records
        $primaryId = 'b' === $primaryChoice ? $detection->record_b_id : $detection->record_a_id;
        $secondaryId = 'b' === $primaryChoice ? $detection->record_a_id : $detection->record_b_id;

        // Load record details
        $primaryRecord = $this->loadRecordDetails($primaryId);
        $secondaryRecord = $this->loadRecordDetails($secondaryId);

        if (!$primaryRecord || !$secondaryRecord) {
            $this->error('One or both records no longer exist');

            return 1;
        }

        // Display merge preview
        $this->bold('  === Merge Preview ===');
        $this->newline();
        $this->line('  PRIMARY RECORD (will be kept):');
        $this->displayRecord($primaryRecord);
        $this->newline();
        $this->line('  SECONDARY RECORD (will be merged):');
        $this->displayRecord($secondaryRecord);
        $this->newline();

        // Show what will be transferred
        $this->info('  Merge operations:');
        $this->line("    - Transfer {$secondaryRecord->digital_object_count} digital objects");
        $this->line("    - Transfer {$secondaryRecord->child_count} child records");
        $this->line("    - Redirect slug: {$secondaryRecord->slug} -> {$primaryRecord->slug}");
        $this->newline();

        if ($dryRun) {
            $this->info('DRY RUN - No changes made');

            return 0;
        }

        // Confirm unless forced
        if (!$force) {
            $this->warning('WARNING: This operation cannot be undone!');
            if (!$this->confirm('  Proceed with merge?', false)) {
                $this->line('  Merge cancelled.');

                return 0;
            }
        }

        // Perform merge
        $service = new \ahgDedupePlugin\Services\DedupeService();

        try {
            $result = $service->mergeRecords($detectionId, $primaryId, 1);

            $this->newline();
            $this->bold('  === Merge Complete ===');
            $this->line("  Primary record: {$primaryId}");
            $this->line("  Merge log ID: {$result['merge_log_id']}");
            $this->line("  Digital objects moved: {$result['digital_objects_moved']}");
            $this->line("  Children moved: {$result['children_moved']}");
            $this->line("  Slugs redirected: {$result['slugs_redirected']}");

            return 0;
        } catch (\Exception $e) {
            $this->error('Merge failed: ' . $e->getMessage());

            return 1;
        }
    }

    private function loadRecordDetails(int $recordId): ?object
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

    private function displayRecord(object $record): void
    {
        $this->line("    ID: {$record->id}");
        $this->line('    Title: ' . ($record->title ?? 'Untitled'));
        $this->line('    Identifier: ' . ($record->identifier ?? 'N/A'));
        $this->line("    Slug: {$record->slug}");
        $this->line("    Digital Objects: {$record->digital_object_count}");
        $this->line("    Child Records: {$record->child_count}");
    }
}
