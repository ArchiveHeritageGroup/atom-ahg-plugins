<?php

/**
 * php symfony version:prune [--entity=information_object,actor] [--retain-count=N] [--retain-days=N] [--dry-run]
 *
 * Phase M — apply retention rules to version history. By default reads
 * retain_count and retain_days from ahg_settings (group=version_control).
 * Command-line overrides take precedence.
 *
 * Keep rules (a version is KEPT if any are true):
 *   - version_number = 1 (the baseline is always kept)
 *   - retain_count > 0 AND version_number > (max_version - retain_count)
 *   - retain_days  > 0 AND created_at > (now - retain_days)
 *
 * Otherwise: prune. Both rules zero = nothing pruned (default).
 *
 * @phase M
 */
class versionPruneTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('entity', null, sfCommandOption::PARAMETER_OPTIONAL, 'CSV: information_object,actor', 'information_object,actor'),
            new sfCommandOption('retain-count', null, sfCommandOption::PARAMETER_OPTIONAL, 'Override retain_count setting'),
            new sfCommandOption('retain-days', null, sfCommandOption::PARAMETER_OPTIONAL, 'Override retain_days setting'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Report what would be pruned, delete nothing'),
        ]);
        $this->namespace = 'version';
        $this->name = 'prune';
        $this->briefDescription = 'Apply retention rules to version history (preserves v1 + most-recent N).';
    }

    protected function execute($arguments = [], $options = [])
    {
        $entities = array_filter(
            array_map('trim', explode(',', (string) ($options['entity'] ?? ''))),
            fn ($e) => in_array($e, ['information_object', 'actor'], true),
        );
        if (empty($entities)) {
            throw new sfCommandException('--entity must be information_object, actor, or both');
        }

        $retainCount = $options['retain-count'] !== null
            ? (int) $options['retain-count']
            : (int) $this->readSetting('version_control.retain_count', '0');
        $retainDays = $options['retain-days'] !== null
            ? (int) $options['retain-days']
            : (int) $this->readSetting('version_control.retain_days', '0');
        $dryRun = !empty($options['dry-run']);

        $this->logSection('prune', sprintf(
            'retain_count=%d  retain_days=%d  dry_run=%s',
            $retainCount, $retainDays, $dryRun ? 'yes' : 'no',
        ));

        if ($retainCount <= 0 && $retainDays <= 0) {
            $this->logSection('prune', 'Both retention rules are 0 — nothing to do.');
            return;
        }

        foreach ($entities as $entityType) {
            $cfg = $entityType === 'actor'
                ? ['table' => 'actor_version', 'fk' => 'actor_id']
                : ['table' => 'information_object_version', 'fk' => 'information_object_id'];

            $this->logSection('prune', "{$entityType} — scanning…");
            $deleted = $this->prune($cfg['table'], $cfg['fk'], $retainCount, $retainDays, $dryRun);
            $this->logSection('prune', "{$entityType} — " . ($dryRun ? 'would prune' : 'pruned') . " {$deleted} row(s)");
        }
    }

    private function readSetting(string $key, string $default): string
    {
        try {
            $v = \Illuminate\Database\Capsule\Manager::table('ahg_settings')
                ->where('setting_key', $key)
                ->value('setting_value');
            return is_string($v) ? $v : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    private function prune(string $table, string $fk, int $retainCount, int $retainDays, bool $dryRun): int
    {
        $db = \Illuminate\Database\Capsule\Manager::connection();

        // Build the IN-list of IDs to delete using two SELECTs unioned in PHP.
        // Doing this in pure SQL with subqueries would be cleaner but bumps
        // into MySQL's "can't update target table" rule when the DELETE refers
        // to itself. Two SELECTs + a WHERE id IN (...) DELETE is portable.

        $cutoff = $retainDays > 0
            ? date('Y-m-d H:i:s', time() - 86400 * $retainDays)
            : null;

        // Find candidate version_table.id values that:
        //   - are NOT version_number=1
        //   - AND (retain_count=0 OR version_number <= max_version_for_entity - retain_count)
        //   - AND (retain_days=0 OR created_at < cutoff)
        // grouped by entity.

        // First get the max version_number per entity.
        $maxVersions = $db->table($table)
            ->select($fk, $db->raw('MAX(version_number) AS mx'))
            ->groupBy($fk)
            ->get()
            ->all();

        $toDelete = [];
        foreach ($maxVersions as $row) {
            $entityId = (int) $row->{$fk};
            $maxVersion = (int) $row->mx;

            $cutoffCount = $retainCount > 0 ? ($maxVersion - $retainCount) : null;

            $q = $db->table($table)
                ->where($fk, $entityId)
                ->where('version_number', '!=', 1);

            if ($cutoffCount !== null && $cutoff !== null) {
                // Keep if EITHER rule says keep. Delete only when BOTH delete.
                $q->where('version_number', '<=', $cutoffCount)
                  ->where('created_at', '<', $cutoff);
            } elseif ($cutoffCount !== null) {
                $q->where('version_number', '<=', $cutoffCount);
            } elseif ($cutoff !== null) {
                $q->where('created_at', '<', $cutoff);
            }

            $ids = $q->pluck('id')->all();
            foreach ($ids as $id) {
                $toDelete[] = (int) $id;
            }
        }

        if (empty($toDelete)) {
            return 0;
        }
        if ($dryRun) {
            return count($toDelete);
        }

        // Delete in chunks to keep transactions bounded.
        $deleted = 0;
        foreach (array_chunk($toDelete, 1000) as $chunk) {
            $deleted += $db->table($table)->whereIn('id', $chunk)->delete();
        }
        return $deleted;
    }
}
