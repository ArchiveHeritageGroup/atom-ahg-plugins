<?php

namespace AhgVersionControl\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * SnapshotBuilder — pure-function service that produces a canonical JSON
 * snapshot of an entity's full state for the version history.
 *
 * Deterministic ordering of all collections so two snapshots of the same
 * underlying data are byte-equivalent (essential for stable diff output).
 *
 * NO DB writes. Read-only. Called by VersionWriter (Phase C) which handles persistence.
 *
 * @phase B
 */
class SnapshotBuilder
{
    public const SCHEMA_VERSION = 1;

    /**
     * Build a snapshot of an information_object including all i18n rows,
     * access points, events, custom field values and physical-object relations.
     *
     * @return array<string,mixed>
     */
    public function buildForInformationObject(int $id): array
    {
        $base = (array) DB::table('information_object')->where('id', $id)->first();
        if (empty($base)) {
            throw new \RuntimeException("information_object {$id} not found");
        }

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'entity_type'    => 'information_object',
            'entity_id'      => $id,
            'captured_at'    => gmdate('Y-m-d\TH:i:s\Z'),
            'base'           => $this->normaliseRow($base),
            'i18n'           => $this->fetchI18n('information_object_i18n', 'id', $id),
            'access_points'  => $this->fetchAccessPoints($id),
            'events'         => $this->fetchEventsForObject($id),
            'relations'      => $this->fetchRelationsForObject($id),
            'physical_objects' => $this->fetchPhysicalObjects($id),
            'custom_fields'  => $this->fetchCustomFields($id),
        ];
    }

    /**
     * Build a snapshot of an actor including all i18n rows and related events.
     *
     * @return array<string,mixed>
     */
    public function buildForActor(int $id): array
    {
        $base = (array) DB::table('actor')->where('id', $id)->first();
        if (empty($base)) {
            throw new \RuntimeException("actor {$id} not found");
        }

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'entity_type'    => 'actor',
            'entity_id'      => $id,
            'captured_at'    => gmdate('Y-m-d\TH:i:s\Z'),
            'base'           => $this->normaliseRow($base),
            'i18n'           => $this->fetchI18n('actor_i18n', 'id', $id),
            'events'         => $this->fetchEventsForActor($id),
            'relations'      => $this->fetchRelationsForActor($id),
            'custom_fields'  => $this->fetchCustomFields($id),
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchI18n(string $table, string $fkColumn, int $id): array
    {
        $rows = DB::table($table)->where($fkColumn, $id)->orderBy('culture')->get();
        return array_map(fn ($row) => $this->normaliseRow((array) $row), $rows->all());
    }

    /**
     * Access points = subject/place/name/genre term relations on object_term_relation.
     * AtoM stores all four types here keyed by term.taxonomy_id; we just emit
     * the term_id refs deterministically — the diff computer (Phase E) resolves
     * taxonomy labels for display.
     *
     * @return array<int,array<string,mixed>>
     */
    private function fetchAccessPoints(int $objectId): array
    {
        $rows = DB::table('object_term_relation')
            ->where('object_id', $objectId)
            ->orderBy('term_id')
            ->orderBy('id')
            ->get(['term_id', 'start_date', 'end_date']);
        return array_map(fn ($row) => $this->normaliseRow((array) $row), $rows->all());
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchEventsForObject(int $objectId): array
    {
        $rows = DB::table('event')
            ->where('object_id', $objectId)
            ->orderBy('id')
            ->get(['id', 'type_id', 'actor_id', 'start_date', 'end_date', 'start_time', 'end_time', 'source_culture']);

        $events = [];
        foreach ($rows as $r) {
            $eventId = (int) $r->id;
            $i18n = DB::table('event_i18n')
                ->where('id', $eventId)
                ->orderBy('culture')
                ->get(['culture', 'name', 'description', 'date'])
                ->all();
            $event = $this->normaliseRow((array) $r);
            // Drop the surrogate id from the snapshot — event ids are re-issued on
            // restore. Keep relative ordering via the array index.
            unset($event['id']);
            $event['i18n'] = array_map(fn ($x) => $this->normaliseRow((array) $x), $i18n);
            $events[] = $event;
        }
        return $events;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchEventsForActor(int $actorId): array
    {
        $rows = DB::table('event')
            ->where('actor_id', $actorId)
            ->orderBy('id')
            ->get(['type_id', 'object_id', 'start_date', 'end_date', 'start_time', 'end_time', 'source_culture']);
        return array_map(fn ($row) => $this->normaliseRow((array) $row), $rows->all());
    }

    /**
     * IO relations: rows in `relation` where object_id = this IO.
     * Captures actor links (creators, name access points etc.) and IO-to-IO links.
     *
     * @return array<int,array<string,mixed>>
     */
    private function fetchRelationsForObject(int $objectId): array
    {
        $rows = DB::table('relation')
            ->where('object_id', $objectId)
            ->orderBy('subject_id')
            ->orderBy('type_id')
            ->orderBy('id')
            ->get(['subject_id', 'type_id', 'start_date', 'end_date', 'source_culture']);
        return array_map(fn ($row) => $this->normaliseRow((array) $row), $rows->all());
    }

    /**
     * Actor relations: rows in `relation` where subject_id or object_id = this actor.
     * Captures parent-child, related authority, etc.
     *
     * @return array<int,array<string,mixed>>
     */
    private function fetchRelationsForActor(int $actorId): array
    {
        $rows = DB::table('relation')
            ->where(function ($q) use ($actorId) {
                $q->where('subject_id', $actorId)->orWhere('object_id', $actorId);
            })
            ->orderBy('subject_id')
            ->orderBy('object_id')
            ->orderBy('type_id')
            ->orderBy('id')
            ->get(['subject_id', 'object_id', 'type_id', 'start_date', 'end_date', 'source_culture']);
        return array_map(fn ($row) => $this->normaliseRow((array) $row), $rows->all());
    }

    /**
     * Physical object links via the relation table with a physical-object subject.
     * Snapshot records ID refs only; labels look up from physical_object_i18n at
     * render time.
     *
     * @return array<int,array<string,mixed>>
     */
    private function fetchPhysicalObjects(int $informationObjectId): array
    {
        $rows = DB::table('relation')
            ->join('physical_object', 'physical_object.id', '=', 'relation.subject_id')
            ->where('relation.object_id', $informationObjectId)
            ->orderBy('relation.subject_id')
            ->get([
                'relation.subject_id AS physical_object_id',
                'physical_object.type_id',
                'physical_object.source_culture',
            ]);
        return array_map(fn ($row) => $this->normaliseRow((array) $row), $rows->all());
    }

    /**
     * Custom fields from ahgCustomFieldsPlugin. The plugin uses `custom_field_value.object_id`
     * as the FK to either information_object.id or actor.id (EAV).
     *
     * Wrapped in try/catch so this works even when ahgCustomFieldsPlugin is not installed.
     *
     * @return array<int,array<string,mixed>>
     */
    private function fetchCustomFields(int $objectId): array
    {
        try {
            $rows = DB::table('custom_field_value')
                ->where('object_id', $objectId)
                ->orderBy('field_definition_id')
                ->orderBy('sequence')
                ->orderBy('id')
                ->get(['field_definition_id', 'value_text', 'value_number', 'value_date', 'value_boolean', 'value_dropdown', 'sequence']);
            return array_map(fn ($row) => $this->normaliseRow((array) $row), $rows->all());
        } catch (\Throwable $e) {
            // ahgCustomFieldsPlugin not installed — return empty set.
            return [];
        }
    }

    /**
     * Cast row to array with stable key ordering and JSON-friendly value types.
     * Stringifies dates/datetimes to ISO 8601 for byte-stable diffs.
     *
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function normaliseRow(array $row): array
    {
        ksort($row);
        foreach ($row as $key => $value) {
            if ($value instanceof \DateTimeInterface) {
                $row[$key] = $value->format(\DateTimeInterface::ATOM);
            } elseif (is_object($value) && method_exists($value, '__toString')) {
                $row[$key] = (string) $value;
            }
        }
        return $row;
    }
}
