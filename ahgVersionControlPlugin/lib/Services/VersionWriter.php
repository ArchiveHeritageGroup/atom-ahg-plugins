<?php

namespace AhgVersionControl\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * VersionWriter — concurrency-safe persistence of a version snapshot.
 *
 * Allocates the next version_number per entity inside a row-locked transaction
 * (SELECT MAX(...) FOR UPDATE), inserts the new row, returns the version_number.
 *
 * Two concurrent callers serialise on the lock and produce sequential numbers
 * (e.g. 5 then 6). The UNIQUE (entity_id, version_number) constraint is the
 * belt-and-braces backstop if the lock ever fails.
 *
 * @phase C
 */
class VersionWriter
{
    private const TABLE_MAP = [
        'information_object' => ['table' => 'information_object_version', 'fk' => 'information_object_id', 'parent' => 'information_object'],
        'actor'              => ['table' => 'actor_version',              'fk' => 'actor_id',              'parent' => 'actor'],
    ];

    /** Retry budget for deadlocks (SQLSTATE 40001) and rare unique-key races. */
    private const MAX_RETRIES = 3;

    /**
     * Persist a snapshot as the next version for the given entity.
     *
     * @param array<string,mixed> $snapshot
     * @return int the allocated version_number
     */
    public function write(
        string $entityType,
        int $entityId,
        array $snapshot,
        ?string $changeSummary = null,
        ?int $userId = null,
        bool $isRestore = false,
        ?int $restoredFromVersion = null,
    ): int {
        if (!isset(self::TABLE_MAP[$entityType])) {
            throw new \RuntimeException("VersionWriter: unsupported entity_type '{$entityType}'");
        }
        $table = self::TABLE_MAP[$entityType]['table'];
        $fk = self::TABLE_MAP[$entityType]['fk'];
        $parent = self::TABLE_MAP[$entityType]['parent'];

        $connection = DB::connection();

        $attempt = 0;
        while (true) {
            ++$attempt;
            try {
                return $connection->transaction(function () use (
                    $connection, $entityType, $parent, $table, $fk, $entityId, $snapshot,
                    $changeSummary, $userId, $isRestore, $restoredFromVersion
                ) {
                    // Lock the parent entity row to serialise concurrent version writes
                    // for THIS entity. Avoids the gap-lock deadlock that an empty version
                    // table provokes when multiple transactions all SELECT MAX FOR UPDATE.
                    $connection->select(
                        "SELECT id FROM {$parent} WHERE id = ? FOR UPDATE",
                        [$entityId],
                    );

                    $maxRows = $connection->select(
                        "SELECT MAX(version_number) AS mx FROM {$table} WHERE {$fk} = ?",
                        [$entityId],
                    );
                    $previousVersion = isset($maxRows[0]) ? $maxRows[0]->mx : null;
                    $nextVersion = ((int) ($previousVersion ?? 0)) + 1;

                    $changedFields = null;
                    if ($previousVersion !== null) {
                        $prevSnapshotJson = $connection->table($table)
                            ->where($fk, $entityId)
                            ->where('version_number', $previousVersion)
                            ->value('snapshot');
                        if (is_string($prevSnapshotJson)) {
                            $prev = json_decode($prevSnapshotJson, true);
                            if (is_array($prev)) {
                                $changedFields = $this->computeChangedFields($prev, $snapshot);
                            }
                        }
                    }

                    $versionRowId = $connection->table($table)->insertGetId([
                        $fk                     => $entityId,
                        'version_number'        => $nextVersion,
                        'snapshot'              => json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        'change_summary'        => $changeSummary,
                        'changed_fields'        => $changedFields !== null ? json_encode($changedFields) : null,
                        'created_by'            => $userId,
                        'created_at'            => date('Y-m-d H:i:s'),
                        'is_restore'            => $isRestore ? 1 : 0,
                        'restored_from_version' => $restoredFromVersion,
                    ]);

                    // Phase I — audit dual-write. Audited inside the same
                    // transaction so audit + version commit atomically; if
                    // ahg_audit_log isn't present (plugin not installed), the
                    // try/catch keeps the version write succeeding.
                    $this->writeAuditEntry(
                        $entityType, $entityId, $nextVersion, (int) $versionRowId,
                        $table, $isRestore, $restoredFromVersion, $changeSummary, $userId,
                    );

                    return $nextVersion;
                });
            } catch (\Throwable $e) {
                // Retry on deadlock (40001) and duplicate-key (1062 / 23000)
                $sqlState = method_exists($e, 'getSqlState') ? $e->getSqlState() : null;
                $errCode = method_exists($e, 'getCode') ? (string) $e->getCode() : '';
                $isDeadlock = $sqlState === '40001' || str_contains($e->getMessage(), 'Deadlock');
                $isDupKey = $sqlState === '23000' || str_contains($e->getMessage(), 'Duplicate entry');
                if (($isDeadlock || $isDupKey) && $attempt < self::MAX_RETRIES) {
                    usleep(50_000 * $attempt); // 50/100/150 ms backoff
                    continue;
                }
                throw $e;
            }
        }
    }

    /**
     * Audit dual-write — write a row to ahg_audit_log mirroring the version event.
     *
     * Action vocabulary:
     *   version_created  — a normal save produced a new version
     *   version_restored — RestoreService produced this version (is_restore=1)
     *
     * entity_type uses the version table name ('information_object_version' or
     * 'actor_version') so the audit feed can filter on version events without
     * mixing them into the normal save audit feed. entity_id stays the parent
     * entity id so clicking the row resolves to the underlying record.
     */
    private function writeAuditEntry(
        string $entityType,
        int $entityId,
        int $versionNumber,
        int $versionRowId,
        string $versionTable,
        bool $isRestore,
        ?int $restoredFromVersion,
        ?string $changeSummary,
        ?int $userId,
    ): void {
        try {
            $username = null;
            $userEmail = null;
            if ($userId !== null) {
                $userRow = DB::table('user')->where('id', $userId)->first();
                if ($userRow) {
                    $username = $userRow->username ?? null;
                    $userEmail = $userRow->email ?? null;
                }
            }

            // Resolve a simple entity title for the friendly label in the audit feed.
            $entityTitle = null;
            try {
                $i18nTable = $entityType === 'actor' ? 'actor_i18n' : 'information_object_i18n';
                $titleColumn = $entityType === 'actor' ? 'authorized_form_of_name' : 'title';
                $entityTitle = DB::table($i18nTable)
                    ->where('id', $entityId)
                    ->orderBy('culture')
                    ->value($titleColumn);
            } catch (\Throwable $e) {
                // Best-effort only.
            }

            $metadata = [
                'version_number'        => $versionNumber,
                'version_row_id'        => $versionRowId,
                'version_table'         => $versionTable,
                'is_restore'            => $isRestore,
                'restored_from_version' => $restoredFromVersion,
                'change_summary'        => $changeSummary,
                'parent_entity_type'    => $entityType,
                'parent_entity_id'      => $entityId,
            ];

            DB::table('ahg_audit_log')->insert([
                'uuid'           => $this->generateUuid(),
                'user_id'        => $userId,
                'username'       => $username,
                'user_email'     => $userEmail,
                'action'         => $isRestore ? 'version_restored' : 'version_created',
                'entity_type'    => $versionTable,
                'entity_id'      => $entityId,
                'entity_title'   => $entityTitle,
                'module'         => 'version_control',
                'action_name'    => $isRestore ? 'restore' : 'create',
                'request_method' => 'INTERNAL',
                'metadata'       => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'status'         => 'success',
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // ahg_audit_log table not present or schema mismatch — never block
            // the version write on the audit dual-write.
            error_log('ahgVersionControlPlugin: audit dual-write failed: ' . $e->getMessage());
        }
    }

    /**
     * UUID v4 (random) — used for ahg_audit_log.uuid. The audit-trail plugin
     * already requires a per-row UUID; this matches its generation pattern.
     */
    private function generateUuid(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }

    /**
     * Lightweight changed-fields computation: returns a flat sorted list of
     * dotted paths whose value differs between $old and $new.
     *
     * IMPORTANT: MySQL JSON columns reorder keys on storage (length-then-lex),
     * so byte-wise json_encode comparison is unreliable. Uses canonicalJson()
     * which recursively ksorts associative arrays before serialising.
     *
     * Paths:
     *   base.<column>
     *   i18n.<culture>          (when an entire culture was added/removed)
     *   i18n.<culture>.<column> (when a culture-scoped field changed)
     *   access_points | events | relations | physical_objects | custom_fields
     *     (flagged as a whole when any element differs)
     *
     * @return array<int,string>
     */
    private function computeChangedFields(array $old, array $new): array
    {
        $changed = [];

        $oldBase = is_array($old['base'] ?? null) ? $old['base'] : [];
        $newBase = is_array($new['base'] ?? null) ? $new['base'] : [];
        foreach (array_unique(array_merge(array_keys($oldBase), array_keys($newBase))) as $key) {
            if ($this->canonicalJson($oldBase[$key] ?? null) !== $this->canonicalJson($newBase[$key] ?? null)) {
                $changed[] = "base.{$key}";
            }
        }

        $oldI18n = $this->indexByCulture(is_array($old['i18n'] ?? null) ? $old['i18n'] : []);
        $newI18n = $this->indexByCulture(is_array($new['i18n'] ?? null) ? $new['i18n'] : []);
        $cultures = array_unique(array_merge(array_keys($oldI18n), array_keys($newI18n)));
        sort($cultures);
        foreach ($cultures as $culture) {
            $oldRow = $oldI18n[$culture] ?? null;
            $newRow = $newI18n[$culture] ?? null;
            if ($oldRow === null && $newRow !== null) {
                $changed[] = "i18n.{$culture}";
                continue;
            }
            if ($oldRow !== null && $newRow === null) {
                $changed[] = "i18n.{$culture}";
                continue;
            }
            $oldArr = (array) $oldRow;
            $newArr = (array) $newRow;
            foreach (array_unique(array_merge(array_keys($oldArr), array_keys($newArr))) as $key) {
                if ($key === 'culture' || $key === 'id') {
                    continue;
                }
                if ($this->canonicalJson($oldArr[$key] ?? null) !== $this->canonicalJson($newArr[$key] ?? null)) {
                    $changed[] = "i18n.{$culture}.{$key}";
                }
            }
        }

        foreach (['access_points', 'events', 'relations', 'physical_objects', 'custom_fields'] as $section) {
            if ($this->canonicalJson($old[$section] ?? []) !== $this->canonicalJson($new[$section] ?? [])) {
                $changed[] = $section;
            }
        }

        sort($changed);
        return $changed;
    }

    /**
     * Canonical JSON serialisation: recursively sorts associative-array keys
     * so the output is byte-stable regardless of insertion order. Sequential
     * arrays (list-shaped) keep their element order.
     */
    private function canonicalJson(mixed $value): string
    {
        if (is_array($value)) {
            if (array_is_list($value)) {
                return '[' . implode(',', array_map(fn ($v) => $this->canonicalJson($v), $value)) . ']';
            }
            ksort($value);
            $parts = [];
            foreach ($value as $k => $v) {
                $parts[] = json_encode((string) $k, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                    . ':' . $this->canonicalJson($v);
            }
            return '{' . implode(',', $parts) . '}';
        }
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<string,array<string,mixed>>
     */
    private function indexByCulture(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $culture = $row['culture'] ?? null;
            if (is_string($culture) && $culture !== '') {
                $out[$culture] = $row;
            }
        }
        return $out;
    }
}
