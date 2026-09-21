<?php

/**
 * php symfony subtree:export - export a run of descriptions and their digital
 * objects to external storage. AtoM 2.8.1.
 */
class subtreeExportTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('slug', null, sfCommandOption::PARAMETER_REQUIRED, 'Slug of the record to start at'),
            new sfCommandOption('bound', null, sfCommandOption::PARAMETER_OPTIONAL, 'Slug of the ancestor the walk may not leave (default: the parent)'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'How many records, from the start onwards', 1000),
            new sfCommandOption('batch-size', null, sfCommandOption::PARAMETER_OPTIONAL, 'Records per batch; 0 is a single pass', 0),
            new sfCommandOption('output', null, sfCommandOption::PARAMETER_REQUIRED, 'Destination directory on external storage'),
            new sfCommandOption('no-masters', null, sfCommandOption::PARAMETER_NONE, 'Skip master files'),
            new sfCommandOption('no-references', null, sfCommandOption::PARAMETER_NONE, 'Skip reference images'),
            new sfCommandOption('thumbnails', null, sfCommandOption::PARAMETER_NONE, 'Include thumbnails'),
            new sfCommandOption('no-metadata', null, sfCommandOption::PARAMETER_NONE, 'Skip the per-batch metadata JSON'),
            new sfCommandOption('estimate-only', null, sfCommandOption::PARAMETER_NONE, 'Report what would be exported and stop'),
            new sfCommandOption('resume', null, sfCommandOption::PARAMETER_OPTIONAL, 'Continue an existing job by id'),
            new sfCommandOption('force', null, sfCommandOption::PARAMETER_NONE, 'Proceed even if the destination looks too small'),
        ]);

        $this->namespace = 'subtree';
        $this->name = 'export';
        $this->briefDescription = 'Export a run of descriptions and their digital objects to external storage';
        $this->detailedDescription = <<<'EOF'
Exports a record and the next N descriptions in tree order, with their digital
objects, to a directory on external storage.

This is a forward walk, NOT a descendant walk: the record it was written for is a
leaf, so "this record and its descendants" would export exactly one row. The walk
is bounded by an ancestor (the parent unless --bound says otherwise) so it cannot
run past the end of its collection and start exporting the next one.

  ./symfony subtree:export --slug=smt-les-hab1-1 --limit=1000 --output=/mnt/ext/rari
  ./symfony subtree:export --slug=smt-les-hab1-1 --limit=1000 --batch-size=100 --output=/mnt/ext/rari
  ./symfony subtree:export --resume=7
  ./symfony subtree:export --slug=smt-les-hab1-1 --limit=1000 --output=/mnt/ext/rari --estimate-only

Masters and references are included by default; thumbnails are not. Run with
--estimate-only first: it reports the record count and byte total without writing
anything.
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        new sfDatabaseManager($this->configuration);
        $atomRoot = sfConfig::get('sf_root_dir');

        if (!empty($options['resume'])) {
            return $this->drainJob((int) $options['resume'], $atomRoot);
        }

        foreach (['slug', 'output'] as $required) {
            if (empty($options[$required])) {
                $this->logSection('subtree', "--{$required} is required.");

                return 1;
            }
        }

        $start = SubtreeExportService::resolveStart($options['slug'], $options['bound'] ?: null);

        if (null === $start) {
            // One message for three causes would send someone hunting the wrong one.
            $this->logSection('subtree', sprintf(
                'Could not resolve --slug=%s%s. Either the slug does not exist, it is not an information object, or the bound does not contain it.',
                $options['slug'],
                $options['bound'] ? ' within --bound='.$options['bound'] : ''
            ));

            return 1;
        }

        $flags = [
            'masters' => !$options['no-masters'],
            'references' => !$options['no-references'],
            'thumbnails' => (bool) $options['thumbnails'],
        ];
        $usages = SubtreeExportService::usagesFor($flags);

        if (empty($usages)) {
            $this->logSection('subtree', 'Nothing to export: masters, references and thumbnails are all excluded.');

            return 1;
        }

        $max = max(1, (int) $options['limit']);
        $est = SubtreeExportService::estimate($start, $max, $usages);

        $this->logSection('subtree', sprintf(
            'start %s (lft %d), bounded by %s (%d-%d)',
            $options['slug'], $start['lft'], $start['bound_slug'], $start['bound_lft'], $start['bound_rgt']
        ));
        $this->logSection('subtree', sprintf(
            '%d records, %d files, %s',
            $est['items'], $est['files'], SubtreeExportService::humanBytes($est['bytes'])
        ));

        if ($est['items'] < $max) {
            // Silently exporting fewer than asked is how you discover at the far end
            // that the walk hit the bound.
            $this->logSection('subtree', sprintf(
                'NOTE: only %d records available before the bound ends - asked for %d.',
                $est['items'], $max
            ));
        }

        $room = SubtreeExportRunner::destinationHasRoom($options['output'], $est['bytes']);

        if ($room['unknown']) {
            $this->logSection('subtree', 'Could not read free space on '.$room['probe'].' - proceeding without the check.');
        } elseif (!$room['ok']) {
            $this->logSection('subtree', sprintf(
                'Destination %s has %s free; %s wanted (estimate plus headroom).%s',
                $room['probe'],
                SubtreeExportService::humanBytes($room['free']),
                SubtreeExportService::humanBytes($room['required']),
                $options['force'] ? ' Proceeding: --force.' : ' Use --force to override.'
            ));

            if (!$options['force']) {
                return 1;
            }
        }

        if ($options['estimate-only']) {
            $this->logSection('subtree', 'Estimate only - nothing written.');

            return 0;
        }

        $id = SubtreeExportRunner::createJob([
            'slug' => $options['slug'],
            'start' => $start,
            'max_items' => $max,
            'batch_size' => max(0, (int) $options['batch-size']),
            'masters' => $flags['masters'],
            'references' => $flags['references'],
            'thumbnails' => $flags['thumbnails'],
            'metadata' => !$options['no-metadata'],
            'output' => rtrim($options['output'], '/'),
            'bytes' => $est['bytes'],
            'items' => $est['items'],
        ]);

        $this->logSection('subtree', 'job '.$id.' created');

        return $this->drainJob($id, $atomRoot);
    }

    /** Drain until finished, or until one batch is done when batching. */
    private function drainJob(int $id, string $atomRoot): int
    {
        $job = SubtreeExportRunner::job($id);

        if (false === $job) {
            $this->logSection('subtree', 'No such job: '.$id);

            return 1;
        }

        $log = function ($m) { $this->logSection('subtree', $m); };

        do {
            $r = SubtreeExportRunner::drain($job, $atomRoot, $log);

            $this->logSection('subtree', sprintf(
                'batch: %d records, %d files, %s%s%s',
                $r['items'], $r['files'], SubtreeExportService::humanBytes($r['bytes']),
                $r['missing'] ? sprintf(', %d MISSING on disk', $r['missing']) : '',
                // Surfaced, not buried: a file whose size disagrees with the
                // catalogue is the signal that a copy was short or a record stale.
                !empty($r['mismatched']) ? sprintf(', %d SIZE MISMATCH', $r['mismatched']) : ''
            ));

            $job = SubtreeExportRunner::job($id);
        } while (!$r['done'] && 0 === (int) $job->batch_size && $r['items'] > 0);

        $this->logSection('subtree', sprintf(
            'job %d %s - %d/%d records, %d files, %s written, %d missing',
            $id, $job->status, $job->items_done, $job->max_items,
            $job->files_done, SubtreeExportService::humanBytes((int) $job->bytes_written),
            $job->files_missing
        ));

        if ((int) $job->files_missing > 0) {
            $this->logSection('subtree', 'Missing files are recorded in subtree_export_file with status=missing.');
        }

        return 'failed' === $job->status ? 1 : 0;
    }
}
