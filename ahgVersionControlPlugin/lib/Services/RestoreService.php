<?php

namespace AhgVersionControl\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * RestoreService — apply a stored version snapshot back to the live entity.
 *
 * v0.2 scope (Phase L, 2026-05-16):
 *   ✓ base row (UPDATE in place — preserves entity id and all child FKs)
 *   ✓ i18n rows (DELETE + INSERT per culture)
 *   ✓ custom_field_value rows from ahgCustomFieldsPlugin (DELETE + INSERT)
 *   ✓ access_points (object_term_relation) — DELETE + INSERT
 *   ✓ events (event + event_i18n) — DELETE + INSERT (snapshot now captures event_i18n)
 *   ✓ relations (relation table, subject/object side as appropriate) — DELETE + INSERT
 *   ✓ physical_objects — DELETE + INSERT relation rows linking the IO to existing
 *     physical_object rows (the physical_object base rows themselves are not
 *     restored; they must still exist)
 *
 * After applying the snapshot, the service explicitly writes a new version
 * via VersionWriter with is_restore=1 and restored_from_version=N. The
 * SaveListener (AtoM) and Eloquent observer (Heratio) are suppressed during
 * the in-place writes by VersionContext::skip(), so the restore produces
 * exactly one new version row.
 *
 * @phase L (2026-05-16) — extends Phase H restore from base+i18n+custom_fields
 *                        to full coverage including access_points/events/relations.
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
        $accessPoints = is_array($snapshot['access_points'] ?? null) ? $snapshot['access_points'] : [];
        $events = is_array($snapshot['events'] ?? null) ? $snapshot['events'] : [];
        $relations = is_array($snapshot['relations'] ?? null) ? $snapshot['relations'] : [];
        $physicalObjects = is_array($snapshot['physical_objects'] ?? null) ? $snapshot['physical_objects'] : [];

        $connection = DB::connection();

        // Suppress automatic version capture during the in-place writes so we
        // can issue exactly one is_restore=1 version after the transaction.
        VersionContext::skip();
        try {
            $connection->transaction(function () use ($cfg, $entityId, $base, $i18n, $customFields, $accessPoints, $events, $relations, $physicalObjects, $entityType) {
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

                // ------------------------------------------------------------
                // Phase L (v0.2): related-entity restore
                // ------------------------------------------------------------

                // 4. Access points (object_term_relation rows for this object).
                //    Applies to information_object only — actors do not own access points.
                if ($entityType === 'information_object') {
                    DB::table('object_term_relation')->where('object_id', $entityId)->delete();
                    foreach ($accessPoints as $ap) {
                        if (!is_array($ap) || empty($ap['term_id'])) {
                            continue;
                        }
                        DB::table('object_term_relation')->insert([
                            'object_id'  => $entityId,
                            'term_id'    => (int) $ap['term_id'],
                            'start_date' => $ap['start_date'] ?? null,
                            'end_date'   => $ap['end_date']   ?? null,
                        ]);
                    }
                }

                // 5. Events (event + event_i18n).
                //    For IO restore: event.object_id = entityId.
                //    For actor restore: event.actor_id = entityId.
                //    DELETE cascades to event_i18n via FK on the base AtoM schema.
                $eventFk = $entityType === 'actor' ? 'actor_id' : 'object_id';
                $eventIds = DB::table('event')->where($eventFk, $entityId)->pluck('id')->all();
                if (!empty($eventIds)) {
                    DB::table('event_i18n')->whereIn('id', $eventIds)->delete();
                    DB::table('event')->whereIn('id', $eventIds)->delete();
                }
                foreach ($events as $ev) {
                    if (!is_array($ev)) {
                        continue;
                    }
                    $newId = DB::table('event')->insertGetId([
                        $eventFk            => $entityId,
                        'type_id'           => $ev['type_id']         ?? null,
                        'actor_id'          => $entityType === 'information_object' ? ($ev['actor_id']  ?? null) : null,
                        'object_id'         => $entityType === 'actor'              ? ($ev['object_id'] ?? null) : $entityId,
                        'start_date'        => $ev['start_date']      ?? null,
                        'end_date'          => $ev['end_date']        ?? null,
                        'start_time'        => $ev['start_time']      ?? null,
                        'end_time'          => $ev['end_time']        ?? null,
                        'source_culture'    => $ev['source_culture']  ?? 'en',
                    ]);
                    $eventI18n = is_array($ev['i18n'] ?? null) ? $ev['i18n'] : [];
                    foreach ($eventI18n as $row) {
                        if (!is_array($row) || empty($row['culture'])) {
                            continue;
                        }
                        DB::table('event_i18n')->insert([
                            'id'          => $newId,
                            'culture'     => $row['culture'],
                            'name'        => $row['name']        ?? null,
                            'description' => $row['description'] ?? null,
                            'date'        => $row['date']        ?? null,
                        ]);
                    }
                }

                // 6. Relations.
                //    IO: relation.object_id = entityId (actor links, IO-to-IO, etc.)
                //    Actor: relation rows where subject_id OR object_id = entityId.
                if ($entityType === 'information_object') {
                    DB::table('relation')->where('object_id', $entityId)->delete();
                    foreach ($relations as $rel) {
                        if (!is_array($rel) || empty($rel['subject_id']) || empty($rel['type_id'])) {
                            continue;
                        }
                        DB::table('relation')->insert([
                            'object_id'      => $entityId,
                            'subject_id'     => (int) $rel['subject_id'],
                            'type_id'        => (int) $rel['type_id'],
                            'start_date'     => $rel['start_date']      ?? null,
                            'end_date'       => $rel['end_date']        ?? null,
                            'source_culture' => $rel['source_culture']  ?? 'en',
                        ]);
                    }
                } else {
                    // Actor: delete relations on either side, then re-insert.
                    DB::table('relation')
                        ->where(function ($q) use ($entityId) {
                            $q->where('subject_id', $entityId)->orWhere('object_id', $entityId);
                        })
                        ->delete();
                    foreach ($relations as $rel) {
                        if (!is_array($rel) || empty($rel['type_id'])) {
                            continue;
                        }
                        DB::table('relation')->insert([
                            'subject_id'     => $rel['subject_id'] ?? null,
                            'object_id'      => $rel['object_id']  ?? null,
                            'type_id'        => (int) $rel['type_id'],
                            'start_date'     => $rel['start_date']      ?? null,
                            'end_date'       => $rel['end_date']        ?? null,
                            'source_culture' => $rel['source_culture']  ?? 'en',
                        ]);
                    }
                }

                // 7. Physical objects (re-link existing physical_object rows to this IO).
                //    NOTE: we do NOT restore the physical_object base rows themselves —
                //    if a physical_object was deleted, that link is lost. This is the
                //    documented v0.2 boundary.
                if ($entityType === 'information_object') {
                    // Determine which relation rows currently link a physical_object to this IO.
                    $physRelIds = DB::table('relation as r')
                        ->join('physical_object as po', 'po.id', '=', 'r.subject_id')
                        ->where('r.object_id', $entityId)
                        ->pluck('r.id')
                        ->all();
                    if (!empty($physRelIds)) {
                        DB::table('relation')->whereIn('id', $physRelIds)->delete();
                    }
                    foreach ($physicalObjects as $po) {
                        if (!is_array($po) || empty($po['physical_object_id'])) {
                            continue;
                        }
                        // Verify the physical_object still exists; if not, skip silently.
                        $exists = DB::table('physical_object')->where('id', (int) $po['physical_object_id'])->exists();
                        if (!$exists) {
                            continue;
                        }
                        DB::table('relation')->insert([
                            'subject_id'     => (int) $po['physical_object_id'],
                            'object_id'      => $entityId,
                            // type_id of the linking relation isn't captured by SnapshotBuilder;
                            // use the AtoM convention (term_id of "Physical object placement"
                            // taxonomy). Fall back to whatever non-null value was in the snapshot.
                            'type_id'        => $po['type_id'] ?? QubitTerm::PHYSICAL_OBJECT_PLACEMENT_ID ?? 200,
                            'source_culture' => $po['source_culture'] ?? 'en',
                        ]);
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
