<?php

namespace AtomFramework\Console\Commands\ResourceSync;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Record a ResourceSync / OAI tombstone for an information object that has
 * been removed or permanently unpublished.
 *
 * Ported from the Heratio ahg-oai `oai:mark-deleted` command. ResourceSync's
 * ChangeList and the OAI-PMH ListRecords surface read the SAME
 * `oai_deleted_record` table, so a tombstone recorded here is reported by both
 * federation endpoints.
 *
 *   php bin/atom resourcesync:mark-deleted 1234
 *   php bin/atom resourcesync:mark-deleted 1234 --reason="Withdrawn at donor request"
 *   php bin/atom resourcesync:mark-deleted --all-unpublished
 *   php bin/atom resourcesync:mark-deleted --list
 */
class MarkDeletedCommand extends BaseCommand
{
    protected string $name = 'resourcesync:mark-deleted';
    protected string $description = 'Record a ResourceSync/OAI tombstone so harvesters clean up a deleted record';
    protected string $detailedDescription = <<<'EOF'
    Records a tombstone in oai_deleted_record. The ResourceSync ChangeList
    (and the OAI-PMH endpoint, if installed) then advertise change="deleted"
    for the record so downstream aggregators remove their copy.

    Examples:
      php bin/atom resourcesync:mark-deleted 1234
      php bin/atom resourcesync:mark-deleted 1234 --reason="Withdrawn"
      php bin/atom resourcesync:mark-deleted --all-unpublished
      php bin/atom resourcesync:mark-deleted --list

    Options:
      --reason=TEXT       Optional reason text recorded with the tombstone
      --all-unpublished   Tombstone every IO with an oai_local_identifier that
                          is not currently published
      --list              Show current tombstones and exit (no writes)
    EOF;

    /** AtoM publication-status taxonomy IDs. */
    private const STATUS_TYPE_PUBLICATION = 158;
    private const STATUS_PUBLISHED = 160;

    protected function configure(): void
    {
        $this->addArgument('oai_local_identifier', 'The oai_local_identifier of the record to tombstone', false);
        $this->addOption('reason', null, 'Optional reason text recorded with the tombstone');
        $this->addOption('all-unpublished', null, 'Tombstone every unpublished IO that has an oai_local_identifier');
        $this->addOption('list', null, 'Show current tombstones and exit (no writes)');
    }

    protected function handle(): int
    {
        if (! $this->hasTable('oai_deleted_record')) {
            $this->error('oai_deleted_record table is missing. Load the ahgResourceSyncPlugin install.sql first.');

            return 1;
        }

        if ($this->option('list') !== null) {
            return $this->listTombstones();
        }

        if ($this->option('all-unpublished') !== null) {
            return $this->markAllUnpublished();
        }

        $id = $this->argument('oai_local_identifier');
        if ($id === null || $id === '') {
            $this->error('Provide an oai_local_identifier, or use --all-unpublished / --list.');

            return 2;
        }

        return $this->markSingle((int) $id, (string) ($this->option('reason') ?? ''));
    }

    private function listTombstones(): int
    {
        $rows = DB::table('oai_deleted_record')
            ->orderBy('deleted_at', 'desc')
            ->limit(50)
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No tombstones recorded.');

            return 0;
        }

        foreach ($rows as $r) {
            $this->line(sprintf(
                '  oai_local_id=%-10s deleted_at=%-20s %s',
                $r->oai_local_identifier,
                $r->deleted_at,
                $r->reason ?? ''
            ));
        }
        $this->success(sprintf('%d tombstone(s).', count($rows)));

        return 0;
    }

    private function markSingle(int $oaiLocalId, string $reason = ''): int
    {
        $now = gmdate('Y-m-d H:i:s');

        $existing = DB::table('oai_deleted_record')
            ->where('oai_local_identifier', $oaiLocalId)
            ->exists();

        if ($existing) {
            $this->warning("Tombstone for oai_local_identifier={$oaiLocalId} already exists; updating timestamp + reason.");
            DB::table('oai_deleted_record')
                ->where('oai_local_identifier', $oaiLocalId)
                ->update(['deleted_at' => $now, 'reason' => $reason !== '' ? $reason : null]);
        } else {
            DB::table('oai_deleted_record')->insert([
                'oai_local_identifier' => $oaiLocalId,
                'deleted_at' => $now,
                'reason' => $reason !== '' ? $reason : null,
            ]);
        }

        $this->success("Tombstone recorded for oai_local_identifier={$oaiLocalId}.");

        return 0;
    }

    /**
     * Tombstone every IO that has an oai_local_identifier set but is not
     * currently published. Idempotent — re-runs only add new tombstones.
     */
    private function markAllUnpublished(): int
    {
        if (! $this->hasColumn('information_object', 'oai_local_identifier')) {
            $this->error('information_object has no oai_local_identifier column; nothing to tombstone.');

            return 1;
        }

        $candidates = DB::table('information_object as io')
            ->leftJoin('status as st', function ($j) {
                $j->on('st.object_id', '=', 'io.id')
                    ->where('st.type_id', '=', self::STATUS_TYPE_PUBLICATION);
            })
            ->leftJoin('oai_deleted_record as dr', 'dr.oai_local_identifier', '=', 'io.oai_local_identifier')
            ->whereNotNull('io.oai_local_identifier')
            ->where(function ($q) {
                $q->whereNull('st.status_id')
                    ->orWhere('st.status_id', '!=', self::STATUS_PUBLISHED);
            })
            ->whereNull('dr.oai_local_identifier')
            ->select('io.oai_local_identifier')
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('No unpublished records need tombstoning.');

            return 0;
        }

        $now = gmdate('Y-m-d H:i:s');
        $count = 0;
        foreach ($candidates as $c) {
            DB::table('oai_deleted_record')->insert([
                'oai_local_identifier' => (int) $c->oai_local_identifier,
                'deleted_at' => $now,
                'reason' => 'Auto-tombstoned: not published',
            ]);
            $count++;
        }

        $this->success(sprintf('Recorded %d tombstone(s) for unpublished records.', $count));

        return 0;
    }

    private function hasTable(string $table): bool
    {
        try {
            return DB::schema()->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            return DB::schema()->hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
