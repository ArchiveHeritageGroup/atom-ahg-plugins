<?php

namespace AhgVersionControl\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * RestoreService — apply a stored version snapshot back to the live entity.
 *
 * v1 scope (Phase H):
 *   ✓ base row (UPDATE in place — preserves entity id and all child FKs)
 *   ✓ i18n rows (DELETE + INSERT per culture)
 *   ✓ custom_field_value rows from ahgCustomFieldsPlugin (DELETE + INSERT)
 *   ✗ access_points / events / relations / physical_objects (left untouched —
 *     full restore of these requires careful object-table FK handling and
 *     ships in Phase L cleanup work)
 *
 * After applying the snapshot, the service explicitly writes a new version
 * via VersionWriter with is_restore=1 and restored_from_version=N. The
 * SaveListener (AtoM) and Eloquent observer (Heratio) are suppressed during
 * the in-place writes by VersionContext::skip(), so the restore produces
 * exactly one new version row.
 *
 * @phase H
 */
class RestoreService
{
    private const ENTITY_CONFIG = [
        'information_object' => [
            'base_table'   => 'information_object',
            'i18n_table'   => 'information_object_i18n',
            'fk_in_i18n'   => 'id',
        ],
        'actor' => [
            'base_table'   => 'actor',
            'i18n_table'   => 'actor_i18n',
            'fk_in_i18n'   => 'id',
        ],
    ];

    /**
     * @return int the new version_number created by the restore
     */
    public function restore(string $entityType, int $entityId, int $targetVersionNumber, ?int $userId = null): int
    {
        if (!isset(self::ENTITY_CONFIG[$entityType])) {
            throw new \RuntimeException("RestoreService: unsupported entity_type '{$entityType}'");
        }
        $cfg = self::ENTITY_CONFIG[$entityType];
        $versionTable = $entityType === 'actor' ? 'actor_version' : 'information_object_version';
        $vfk = $entityType === 'actor' ? 'actor_id' : 'information_object_id';

        // Phase J — clearance check against the CURRENT classification of the
        // entity (not the historical version being restored). Locked decision:
        // a security upgrade must not be reversible by a lower-cleared user.
        $clearance = new ClearanceCheck();
        if (!$clearance->canUserRestore($userId, $entityId)) {
            throw new InsufficientClearanceException(
                $clearance->explainDenial($userId, $entityId)
                    ?? 'Insufficient security clearance to restore this record.',
            );
        }

        // Load the target snapshot.
        $snapJson = DB::table($versionTable)
            ->where($vfk, $entityId)
            ->where('version_number', $targetVersionNumber)
            ->value('snapshot');
        if (!is_string($snapJson)) {
            throw new \RuntimeException("RestoreService: version {$targetVersionNumber} not found for {$entityType} {$entityId}");
        }
        $snapshot = json_decode($snapJson, true);
        if (!is_array($snapshot)) {
            throw new \RuntimeException("RestoreService: snapshot JSON is malformed");
        }

        $base = is_array($snapshot['base'] ?? null) ? $snapshot['base'] : [];
        $i18n = is_array($snapshot['i18n'] ?? null) ? $snapshot['i18n'] : [];
        $customFields = is_array($snapshot['custom_fields'] ?? null) ? $snapshot['custom_fields'] : [];

        $connection = DB::connection();

        // Suppress automatic version capture during the in-place writes so we
        // can issue exactly one is_restore=1 version after the transaction.
        VersionContext::skip();
        try {
            $connection->transaction(function () use ($cfg, $entityId, $base, $i18n, $customFields, $entityType) {
                // 1. UPDATE base row. Exclude the primary key and noise columns
                // (lft/rgt/oai_local_identifier — drift fields) to avoid breaking
                // the nested-set tree on information_object.
                $baseUpdate = $base;
                unset($baseUpdate['id'], $baseUpdate['lft'], $baseUpdate['rgt'], $baseUpdate['oai_local_identifier']);
                if (!empty($baseUpdate)) {
                    DB::table($cfg['base_table'])
                        ->where('id', $entityId)
                        ->update($baseUpdate);
                }

                // 2. Replace i18n rows. DELETE all then INSERT from snapshot.
                DB::table($cfg['i18n_table'])->where($cfg['fk_in_i18n'], $entityId)->delete();
                foreach ($i18n as $row) {
                    if (!is_array($row) || empty($row['culture'])) {
                        continue;
                    }
                    // Ensure the FK column is set correctly.
                    $row[$cfg['fk_in_i18n']] = $entityId;
                    DB::table($cfg['i18n_table'])->insert($row);
                }

                // 3. Replace custom_field_value rows (if ahgCustomFieldsPlugin installed).
                if ($entityType === 'information_object' || $entityType === 'actor') {
                    try {
                        DB::table('custom_field_value')->where('object_id', $entityId)->delete();
                        foreach ($customFields as $cfRow) {
                            if (!is_array($cfRow) || empty($cfRow['field_definition_id'])) {
                                continue;
                            }
                            DB::table('custom_field_value')->insert([
                                'field_definition_id' => $cfRow['field_definition_id'],
                                'object_id'           => $entityId,
                                'value_text'          => $cfRow['value_text']     ?? null,
                                'value_number'        => $cfRow['value_number']   ?? null,
                                'value_date'          => $cfRow['value_date']     ?? null,
                                'value_boolean'       => $cfRow['value_boolean']  ?? null,
                                'value_dropdown'      => $cfRow['value_dropdown'] ?? null,
                                'sequence'            => $cfRow['sequence']       ?? 0,
                            ]);
                        }
                    } catch (\Throwable $e) {
                        // ahgCustomFieldsPlugin not installed — silently skip.
                    }
                }
            });
        } finally {
            VersionContext::enable();
        }

        // Re-snapshot the now-restored entity and write a new version with the
        // is_restore marker. This produces a record of WHEN the restore happened
        // alongside the snapshot of the post-restore state.
        $builder = new SnapshotBuilder();
        $writer = new VersionWriter();
        $newSnapshot = $entityType === 'actor'
            ? $builder->buildForActor($entityId)
            : $builder->buildForInformationObject($entityId);

        return $writer->write(
            entityType: $entityType,
            entityId: $entityId,
            snapshot: $newSnapshot,
            changeSummary: sprintf('Restored from v%d', $targetVersionNumber),
            userId: $userId,
            isRestore: true,
            restoredFromVersion: $targetVersionNumber,
        );
    }
}
