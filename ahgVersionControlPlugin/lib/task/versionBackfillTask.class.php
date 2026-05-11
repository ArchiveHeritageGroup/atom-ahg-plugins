<?php

/**
 * php symfony version:backfill [--entity=information_object,actor] [--batch=500] [--dry-run] [--user-id=N]
 *
 * Phase L — backfill v1 baselines for entities that have no version history.
 * Idempotent: entities that already have any version row are skipped.
 *
 * Run after plugin install on existing corpora so the version timeline doesn't
 * start mid-history. Progress is reported per batch.
 *
 * @phase L
 */
class versionBackfillTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('entity', null, sfCommandOption::PARAMETER_OPTIONAL, 'CSV: information_object,actor', 'information_object,actor'),
            new sfCommandOption('batch', null, sfCommandOption::PARAMETER_OPTIONAL, 'Batch size (default 500)', 500),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Print what would be backfilled, write nothing'),
            new sfCommandOption('user-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'created_by for the v1 rows; default NULL'),
        ]);
        $this->namespace = 'version';
        $this->name = 'backfill';
        $this->briefDescription = 'Create v1 baseline versions for entities that have no version history';
    }

    protected function execute($arguments = [], $options = [])
    {
        $entities = array_map('trim', explode(',', (string) ($options['entity'] ?? '')));
        $entities = array_filter($entities, fn ($e) => in_array($e, ['information_object', 'actor'], true));
        if (empty($entities)) {
            throw new sfCommandException('--entity must be information_object, actor, or both');
        }
        $batch = max(50, (int) ($options['batch'] ?? 500));
        $dryRun = !empty($options['dry-run']);
        $userId = !empty($options['user-id']) ? (int) $options['user-id'] : null;

        $libDir = realpath(__DIR__ . '/../Services');
        require_once $libDir . '/SnapshotBuilder.php';
        require_once $libDir . '/VersionWriter.php';
        require_once $libDir . '/VersionContext.php';

        $builder = new \AhgVersionControl\Services\SnapshotBuilder();
        $writer  = new \AhgVersionControl\Services\VersionWriter();

        foreach ($entities as $entityType) {
            $cfg = $entityType === 'actor'
                ? ['base' => 'actor', 'ver' => 'actor_version', 'fk' => 'actor_id']
                : ['base' => 'information_object', 'ver' => 'information_object_version', 'fk' => 'information_object_id'];

            $startedAt = microtime(true);
            $this->logSection('backfill', "{$entityType} — scanning…");

            // Find entities with NO version row at all (idempotent guard).
            $todo = \Illuminate\Database\Capsule\Manager::table($cfg['base'])
                ->leftJoin($cfg['ver'], $cfg['ver'] . '.' . $cfg['fk'], '=', $cfg['base'] . '.id')
                ->whereNull($cfg['ver'] . '.' . $cfg['fk'])
                ->pluck($cfg['base'] . '.id')
                ->all();
            $total = count($todo);
            $this->logSection('backfill', "{$entityType} — {$total} entity/entities to backfill");

            if ($total === 0) {
                continue;
            }
            if ($dryRun) {
                $this->logSection('backfill', '[dry-run] — no rows would be written');
                continue;
            }

            $processed = 0;
            $errors = 0;
            $chunks = array_chunk($todo, $batch);
            foreach ($chunks as $chunkIdx => $chunk) {
                foreach ($chunk as $entityId) {
                    try {
                        $snapshot = $entityType === 'actor'
                            ? $builder->buildForActor((int) $entityId)
                            : $builder->buildForInformationObject((int) $entityId);
                        $writer->write(
                            entityType: $entityType,
                            entityId: (int) $entityId,
                            snapshot: $snapshot,
                            changeSummary: 'Initial backfill (v1 baseline)',
                            userId: $userId,
                        );
                        $processed++;
                    } catch (\Throwable $e) {
                        $errors++;
                        error_log("versionBackfillTask: {$entityType} {$entityId} failed: " . $e->getMessage());
                    }
                }
                $elapsed = microtime(true) - $startedAt;
                $rate = $elapsed > 0 ? round($processed / $elapsed, 1) : 0;
                $this->logSection(
                    'backfill',
                    sprintf('%s — batch %d/%d done · processed=%d · errors=%d · rate=%.1f/s',
                        $entityType, $chunkIdx + 1, count($chunks), $processed, $errors, $rate),
                );
            }

            $elapsed = microtime(true) - $startedAt;
            $rate = $elapsed > 0 ? round($processed / $elapsed, 1) : 0;
            $this->logSection(
                'backfill',
                sprintf('%s — DONE · processed=%d · errors=%d · total=%.1fs · rate=%.1f/s',
                    $entityType, $processed, $errors, $elapsed, $rate),
            );
        }
    }
}
