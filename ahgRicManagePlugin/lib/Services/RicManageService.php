<?php

namespace AhgRicManage\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * RicManageService.
 *
 * Reads and writes the record-centric RiC metadata for an information object
 * (its RiC-O entity type + a small, extensible set of RiC properties), lists
 * the record's typed RiC relations (base `relation` + the `ric_relation_meta`
 * sidecar), and serialises a record to RiC-O JSON-LD - all from MySQL, no
 * triplestore required.
 *
 * An AtoM information_object IS a RiC Record: this service adds the RiC framing
 * without a separate entity store, so more RiC entity types can be layered on
 * later without reworking what is captured here.
 */
class RicManageService
{
    /** RiC-O entity types a record may be typed as (record-centric set for v1). */
    public const ENTITY_TYPES = [
        'Record' => 'Record',
        'RecordSet' => 'Record Set',
        'RecordPart' => 'Record Part',
        'RecordResource' => 'Record Resource',
        'Instantiation' => 'Instantiation',
    ];

    /** Record-centric RiC property keys -> human label. Extend here as needed. */
    public const PROPERTY_FIELDS = [
        'ric_identifier' => 'RiC identifier',
        'scope' => 'Scope',
        'authenticity_note' => 'Authenticity note',
        'integrity_note' => 'Integrity note',
        'provenance_note' => 'Provenance note',
    ];

    /**
     * Get a record's RiC metadata, with defaults when none is stored yet.
     *
     * @return array{entity_type:string, properties:array<string,string>}
     */
    public function getRecordMeta(int $objectId): array
    {
        $row = DB::table('ric_record_meta')->where('object_id', $objectId)->first();

        $properties = [];
        if ($row && !empty($row->ric_data)) {
            $decoded = json_decode((string) $row->ric_data, true);
            if (is_array($decoded)) {
                $properties = $decoded;
            }
        }

        // Normalise to the known key set so the panel always renders every field.
        $normalised = [];
        foreach (array_keys(self::PROPERTY_FIELDS) as $key) {
            $normalised[$key] = isset($properties[$key]) ? (string) $properties[$key] : '';
        }

        return [
            'entity_type' => $row && isset(self::ENTITY_TYPES[$row->entity_type]) ? $row->entity_type : 'Record',
            'properties' => $normalised,
        ];
    }

    /**
     * Upsert a record's RiC entity type + properties.
     *
     * @param array<string,string> $properties
     */
    public function saveRecordMeta(int $objectId, string $entityType, array $properties): void
    {
        if (!isset(self::ENTITY_TYPES[$entityType])) {
            $entityType = 'Record';
        }

        // Keep only known property keys; trim values.
        $clean = [];
        foreach (array_keys(self::PROPERTY_FIELDS) as $key) {
            if (isset($properties[$key]) && '' !== trim((string) $properties[$key])) {
                $clean[$key] = trim((string) $properties[$key]);
            }
        }

        DB::table('ric_record_meta')->updateOrInsert(
            ['object_id' => $objectId],
            [
                'entity_type' => $entityType,
                'ric_data' => empty($clean) ? null : json_encode($clean, JSON_UNESCAPED_UNICODE),
                'updated_at' => DB::raw('NOW()'),
            ]
        );
    }

    /**
     * List the record's typed RiC relations (both directions).
     *
     * @return array<int,array{predicate:string, direction:string, target_id:int, target_title:string, target_slug:?string}>
     */
    public function getTypedRelations(int $objectId, string $culture = 'en'): array
    {
        $rows = DB::table('relation as r')
            ->join('ric_relation_meta as m', 'm.relation_id', '=', 'r.id')
            ->where(function ($q) use ($objectId) {
                $q->where('r.subject_id', $objectId)->orWhere('r.object_id', $objectId);
            })
            ->select('r.id', 'r.subject_id', 'r.object_id', 'm.rico_predicate', 'm.inverse_predicate', 'm.dropdown_code')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $outgoing = ((int) $row->subject_id === $objectId);
            $targetId = $outgoing ? (int) $row->object_id : (int) $row->subject_id;
            $predicate = $outgoing
                ? (string) $row->rico_predicate
                : (string) ($row->inverse_predicate ?: $row->rico_predicate);

            $out[] = [
                'predicate' => $predicate,
                'direction' => $outgoing ? 'outgoing' : 'incoming',
                'target_id' => $targetId,
                'target_title' => $this->ioTitle($targetId, $culture),
                'target_slug' => $this->ioSlug($targetId),
            ];
        }

        return $out;
    }

    /**
     * Serialise a record to RiC-O JSON-LD from MySQL (record + meta + relations).
     *
     * @return array<string,mixed>
     */
    public function exportRicO(int $objectId, string $culture = 'en'): array
    {
        $meta = $this->getRecordMeta($objectId);
        $baseUrl = rtrim((string) \sfConfig::get('app_siteBaseUrl', ''), '/');
        $slug = $this->ioSlug($objectId);
        $id = $slug ? "{$baseUrl}/{$slug}" : "{$baseUrl}/informationobject/{$objectId}";

        $doc = [
            '@context' => 'https://www.ica.org/standards/RiC/ontology',
            '@id' => $id,
            '@type' => 'rico:' . $meta['entity_type'],
            'rico:name' => $this->ioTitle($objectId, $culture),
        ];

        $propMap = [
            'ric_identifier' => 'rico:hasOrHadIdentifier',
            'scope' => 'rico:scope',
            'authenticity_note' => 'rico:authenticityNote',
            'integrity_note' => 'rico:integrityNote',
            'provenance_note' => 'rico:history',
        ];
        foreach ($propMap as $key => $predicate) {
            if (!empty($meta['properties'][$key])) {
                $doc[$predicate] = $meta['properties'][$key];
            }
        }

        // Subjects -> rico:hasOrHadSubject, repository -> rico:hasOrHadHolder,
        // places -> rico:hasOrHadSpatialCoverage. Derived from the record's
        // existing access points / repository so the RiC-O output is complete.
        $subjects = $this->getAccessPointNames($objectId, self::TAXONOMY_SUBJECT, $culture);
        if (!empty($subjects)) {
            $doc['rico:hasOrHadSubject'] = $subjects;
        }
        $places = $this->getAccessPointNames($objectId, self::TAXONOMY_PLACE, $culture);
        if (!empty($places)) {
            $doc['rico:hasOrHadSpatialCoverage'] = $places;
        }
        $holder = $this->getRepositoryName($objectId, $culture);
        if (null !== $holder) {
            $doc['rico:hasOrHadHolder'] = $holder;
        }

        $relations = [];
        foreach ($this->getTypedRelations($objectId, $culture) as $rel) {
            $targetSlug = $rel['target_slug'];
            $relations[] = [
                'predicate' => $rel['predicate'],
                'target' => $targetSlug ? "{$baseUrl}/{$targetSlug}" : "{$baseUrl}/informationobject/{$rel['target_id']}",
                'targetName' => $rel['target_title'],
            ];
        }
        if (!empty($relations)) {
            $doc['rico:relations'] = $relations;
        }

        return $doc;
    }

    /** Subject/place access-point term names for a record (taxonomy 35 = subjects, 42 = places). */
    public const TAXONOMY_SUBJECT = 35;
    public const TAXONOMY_PLACE = 42;

    /**
     * Access-point term names for a record in a given taxonomy.
     *
     * @return array<int,string>
     */
    public function getAccessPointNames(int $objectId, int $taxonomyId, string $culture = 'en'): array
    {
        return DB::table('object_term_relation as otr')
            ->join('term as t', 't.id', '=', 'otr.term_id')
            ->leftJoin('term_i18n as ti', function ($j) use ($culture) {
                $j->on('ti.id', '=', 't.id')->where('ti.culture', '=', $culture);
            })
            ->where('otr.object_id', $objectId)
            ->where('t.taxonomy_id', $taxonomyId)
            ->pluck('ti.name')
            ->filter()
            ->values()
            ->all();
    }

    /** The record's holding repository name (rico:hasOrHadHolder), or null. */
    public function getRepositoryName(int $objectId, string $culture = 'en'): ?string
    {
        $repoId = DB::table('information_object')->where('id', $objectId)->value('repository_id');
        if (!$repoId) {
            return null;
        }

        $name = DB::table('actor_i18n')->where('id', $repoId)->where('culture', $culture)->value('authorized_form_of_name');
        if (empty($name)) {
            $name = DB::table('actor_i18n')->where('id', $repoId)->value('authorized_form_of_name');
        }

        return $name ? (string) $name : null;
    }

    /** Resolve an information object's title (culture fallback to any, then slug). */
    public function ioTitle(int $objectId, string $culture = 'en'): string
    {
        $title = DB::table('information_object_i18n')
            ->where('id', $objectId)
            ->where('culture', $culture)
            ->value('title');

        if (empty($title)) {
            $title = DB::table('information_object_i18n')->where('id', $objectId)->value('title');
        }

        if (empty($title)) {
            $slug = $this->ioSlug($objectId);

            return $slug ?: ('#' . $objectId);
        }

        return (string) $title;
    }

    /** Resolve an object's slug, or null. */
    public function ioSlug(int $objectId): ?string
    {
        $slug = DB::table('slug')->where('object_id', $objectId)->value('slug');

        return $slug ? (string) $slug : null;
    }
}
