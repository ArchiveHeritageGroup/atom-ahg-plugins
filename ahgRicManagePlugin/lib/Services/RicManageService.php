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

    /**
     * Record-centric RiC property keys -> human label.
     *
     * Only the RiC-O concepts that ISAD(G) has NO home for live here. Identifier
     * (rico:hasOrHadIdentifier), scope (rico:scope) and provenance/history
     * (rico:history) used to be captured here too, but they duplicated the ISAD
     * Identifier, Scope and content, and Archival history fields on the same
     * form. Those are now derived from the archival fields on export (see
     * exportRicO) so nothing is captured twice.
     */
    public const PROPERTY_FIELDS = [
        'authenticity_note' => 'Authenticity note',
        'integrity_note' => 'Integrity note',
    ];

    /** RiC property key -> RiC-O predicate. Single source of truth for the export
     *  and the form/panel labels. */
    public const PROPERTY_PREDICATES = [
        'authenticity_note' => 'rico:authenticityNote',
        'integrity_note' => 'rico:integrityNote',
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

        $ricData = empty($clean) ? null : json_encode($clean, JSON_UNESCAPED_UNICODE);

        // Write on a DEDICATED PDO connection, isolated from the shared
        // Illuminate/Propel connection. Writing ric_record_meta on the shared
        // connection mid-request silently voided the information-object update
        // that runs inside handlePost's DB::transaction (title/access points were
        // lost). A separate connection commits independently and cannot interfere.
        $cfg = DB::connection()->getConfig();
        $dsn = 'mysql:host=' . ($cfg['host'] ?? '127.0.0.1')
            . (!empty($cfg['port']) ? ';port=' . $cfg['port'] : '')
            . ';dbname=' . $cfg['database']
            . ';charset=' . ($cfg['charset'] ?? 'utf8mb4');
        $pdo = new \PDO($dsn, $cfg['username'] ?? 'root', (string) ($cfg['password'] ?? ''), [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);

        $check = $pdo->prepare('SELECT id FROM ric_record_meta WHERE object_id = ?');
        $check->execute([$objectId]);
        if ($check->fetchColumn()) {
            $pdo->prepare('UPDATE ric_record_meta SET entity_type = ?, ric_data = ?, updated_at = NOW() WHERE object_id = ?')
                ->execute([$entityType, $ricData, $objectId]);
        } else {
            $pdo->prepare('INSERT INTO ric_record_meta (object_id, entity_type, ric_data, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())')
                ->execute([$objectId, $entityType, $ricData]);
        }
        $pdo = null;
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
            ->select('r.id', 'r.subject_id', 'r.object_id', 'm.rico_predicate', 'm.inverse_predicate', 'm.dropdown_code', 'm.certainty', 'm.evidence')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $outgoing = ((int) $row->subject_id === $objectId);
            $targetId = $outgoing ? (int) $row->object_id : (int) $row->subject_id;
            $predicate = $outgoing
                ? (string) $row->rico_predicate
                : (string) ($row->inverse_predicate ?: $row->rico_predicate);

            $out[] = [
                'relation_id' => (int) $row->id,
                'predicate' => $predicate,
                'code' => (string) $row->dropdown_code,
                'direction' => $outgoing ? 'outgoing' : 'incoming',
                'certainty' => $row->certainty,
                'evidence' => $row->evidence,
                'target_id' => $targetId,
                'target_title' => $this->ioTitle($targetId, $culture),
                'target_slug' => $this->ioSlug($targetId),
            ];
        }

        return $out;
    }

    /** relation.type_id used for RiC IO-to-IO relations (the "Converse term" term). */
    public const RIC_RELATION_TYPE_ID = 177;

    /**
     * The RiC-O relation types (ahg_dropdown taxonomy ric_relation_type), with the
     * predicate/inverse/domain/range decoded from each row's metadata JSON. Drives
     * both the capture dropdown and saveRelation().
     *
     * @return array<int,array<string,?string>>
     */
    public function getRelationTypes(): array
    {
        $rows = DB::table('ahg_dropdown')
            ->where('taxonomy', 'ric_relation_type')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get(['code', 'label', 'metadata']);

        $out = [];
        foreach ($rows as $r) {
            $meta = json_decode((string) ($r->metadata ?: '{}'), true) ?: [];
            $out[] = [
                'code' => (string) $r->code,
                'label' => (string) $r->label,
                'predicate' => (string) ($meta['predicate'] ?? ''),
                'inverse' => (string) ($meta['inverse'] ?? ''),
                'domain' => $meta['domain'] ?? null,
                'range' => $meta['range'] ?? null,
            ];
        }

        return $out;
    }

    /**
     * Create a typed RiC relation: a base `relation` row (subject -> target) plus
     * the ric_relation_meta sidecar carrying the RiC-O predicate. The relation row
     * is written through RelationService::save(), which creates the base `object`
     * row first (relation.id is a non-auto_increment QubitObject id under STRICT
     * mode). Runs in its own AJAX request, so it never collides with a form save's
     * transaction.
     *
     * @return array{success:bool,error?:string,relation_id?:int}
     */
    public function saveRelation(int $subjectId, int $targetId, string $code, ?string $certainty, ?string $evidence, string $culture = 'en'): array
    {
        if ($subjectId <= 0 || $targetId <= 0) {
            return ['success' => false, 'error' => 'Missing subject or target record'];
        }
        if ($subjectId === $targetId) {
            return ['success' => false, 'error' => 'A record cannot relate to itself'];
        }

        $type = null;
        foreach ($this->getRelationTypes() as $t) {
            if ($t['code'] === $code) {
                $type = $t;
                break;
            }
        }
        if (null === $type) {
            return ['success' => false, 'error' => 'Unknown RiC relation type'];
        }

        try {
            $relationId = (int) \AhgCore\Services\RelationService::save([
                'subject_id' => $subjectId,
                'object_id' => $targetId,
                'type_id' => self::RIC_RELATION_TYPE_ID,
            ], $culture);

            DB::table('ric_relation_meta')->insert([
                'relation_id' => $relationId,
                'rico_predicate' => $type['predicate'],
                'inverse_predicate' => $type['inverse'] ?: null,
                'domain_class' => $type['domain'],
                'range_class' => $type['range'],
                'dropdown_code' => $code,
                'certainty' => $certainty ?: null,
                'evidence' => $evidence ?: null,
            ]);
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Save failed: ' . $e->getMessage()];
        }

        return ['success' => true, 'relation_id' => $relationId];
    }

    /** Delete a typed RiC relation (its sidecar meta + the base relation row). */
    public function deleteRelation(int $relationId): void
    {
        if ($relationId <= 0) {
            return;
        }
        DB::table('ric_relation_meta')->where('relation_id', $relationId)->delete();
        \AhgCore\Services\RelationService::delete($relationId);
    }

    /**
     * Title search for information objects to use as a relation target. MySQL
     * (title LIKE) rather than Elasticsearch so it is self-contained and returns
     * a known shape. Excludes the current record and the root.
     *
     * @return array<int,array{id:int,title:string,slug:string}>
     */
    public function searchTargets(int $excludeId, string $q, string $culture = 'en', int $limit = 12): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return [];
        }

        $rows = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i', function ($j) use ($culture) {
                $j->on('i.id', '=', 'io.id')->where('i.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'io.id')
            ->where('io.id', '>', 1)
            ->where('io.id', '!=', $excludeId)
            ->where('i.title', 'like', '%' . $q . '%')
            ->orderBy('i.title')
            ->limit($limit)
            ->get(['io.id', 'i.title', 's.slug']);

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id' => (int) $r->id,
                'title' => (string) ($r->title ?: ('#' . $r->id)),
                'slug' => (string) $r->slug,
            ];
        }

        return $out;
    }

    /**
     * Serialise a record to RiC-O JSON-LD from MySQL (record + meta + relations).
     *
     * Valid JSON-LD with an inline context, so any RDF reader gets triples. The
     * record and every entity it points to are nodes in one @graph: the holder
     * as a rico:CorporateBody, name access points as agents, places, subjects
     * and documentary form types, and typed relations as real RiC-O predicates.
     *
     * @param callable|null $canSee given a related description id, false leaves the relation out
     *
     * @return array<string,mixed>
     */
    public function exportRicO(int $objectId, string $culture = 'en', ?callable $canSee = null): array
    {
        $meta = $this->getRecordMeta($objectId);
        $baseUrl = rtrim((string) \sfConfig::get('app_siteBaseUrl', ''), '/');
        $uri = function (int $id, string $kind = 'informationobject') use ($baseUrl): string {
            $slug = $this->ioSlug($id);

            return $slug ? "{$baseUrl}/{$slug}" : "{$baseUrl}/{$kind}/{$id}";
        };

        $record = [
            '@id' => $uri($objectId),
            '@type' => 'rico:'.$meta['entity_type'],
            'rico:title' => $this->ioTitle($objectId, $culture),
        ];
        $nodes = [];
        $add = function (array $node) use (&$nodes): array {
            $nodes[$node['@id']] = $node;

            return ['@id' => $node['@id']];
        };

        foreach (self::PROPERTY_PREDICATES as $key => $predicate) {
            if (!empty($meta['properties'][$key])) {
                $record[$predicate] = $meta['properties'][$key];
            }
        }

        // Identifier, scope and history come from the record's own ISAD(G) fields.
        $io = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i', function ($j) use ($culture) {
                $j->on('i.id', '=', 'io.id')->where('i.culture', '=', $culture);
            })
            ->where('io.id', $objectId)
            ->select('io.identifier', 'io.repository_id', 'i.scope_and_content', 'i.archival_history')
            ->first();
        if ($io) {
            if (!empty($io->identifier)) {
                $record['rico:identifier'] = $io->identifier;
            }
            if (!empty($io->scope_and_content)) {
                $record['rico:scopeAndContent'] = $io->scope_and_content;
            }
            if (!empty($io->archival_history)) {
                $record['rico:history'] = $io->archival_history;
            }
            $holder = $this->getRepositoryName($objectId, $culture);
            if (!empty($io->repository_id) && null !== $holder) {
                $record['rico:hasOrHadHolder'] = $add([
                    '@id' => $uri((int) $io->repository_id, 'repository'),
                    '@type' => 'rico:CorporateBody',
                    'rico:name' => $holder,
                ]);
            }
        }

        // Access points become linked entities, not text.
        $terms = [
            self::TAXONOMY_SUBJECT => ['rico:hasOrHadSubject', 'skos:Concept', 'skos:prefLabel'],
            self::TAXONOMY_PLACE => ['rico:hasOrHadSpatialCoverage', 'rico:Place', 'rico:name'],
            self::TAXONOMY_GENRE => ['rico:hasDocumentaryFormType', 'rico:DocumentaryFormType', 'rico:name'],
        ];
        foreach ($terms as $taxonomyId => [$predicate, $type, $label]) {
            foreach ($this->getAccessPointTerms($objectId, $taxonomyId, $culture) as $t) {
                $record[$predicate][] = $add(['@id' => $uri((int) $t->id, 'term'), '@type' => $type, $label => (string) $t->name]);
            }
        }
        $agentTypes = [131 => 'rico:CorporateBody', 132 => 'rico:Person', 133 => 'rico:Family'];
        foreach ($this->getNameAccessPointAgents($objectId, $culture) as $a) {
            $record['rico:isAssociatedWith'][] = $add([
                '@id' => $uri((int) $a->id, 'actor'),
                '@type' => $agentTypes[(int) $a->entity_type_id] ?? 'rico:Agent',
                'rico:name' => (string) $a->name,
            ]);
        }

        // Typed relations, as the RiC-O predicates they are. A related
        // description the caller may not see is left out entirely.
        $typed = DB::schema()->hasTable('ric_relation_meta') ? $this->getTypedRelations($objectId, $culture) : [];
        foreach ($typed as $rel) {
            $targetId = (int) $rel['target_id'];
            if ($canSee && !$canSee($targetId)) {
                continue;
            }
            $predicate = (string) $rel['predicate'];
            if ('' === $predicate || 0 !== strpos($predicate, 'rico:')) {
                continue;
            }
            $targetType = DB::table('ric_record_meta')->where('object_id', $targetId)->value('entity_type');
            $record[$predicate][] = $add([
                '@id' => $uri($targetId),
                '@type' => 'rico:'.($targetType ?: 'RecordResource'),
                'rdfs:label' => (string) $rel['target_title'],
            ]);
        }

        return [
            '@context' => [
                'rico' => 'https://www.ica.org/standards/RiC/ontology#',
                'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
                'skos' => 'http://www.w3.org/2004/02/skos/core#',
            ],
            '@graph' => array_values(array_merge([$record['@id'] => $record], $nodes)),
        ];
    }

    /** Access-point terms (id and name) for a record in a taxonomy. @return array<int,object> */
    public function getAccessPointTerms(int $objectId, int $taxonomyId, string $culture = 'en'): array
    {
        return DB::table('object_term_relation as otr')
            ->join('term as t', 't.id', '=', 'otr.term_id')
            ->leftJoin('term_i18n as ti', function ($j) use ($culture) {
                $j->on('ti.id', '=', 't.id')->where('ti.culture', '=', $culture);
            })
            ->where('otr.object_id', $objectId)
            ->where('t.taxonomy_id', $taxonomyId)
            ->whereNotNull('ti.name')
            ->get(['t.id', 'ti.name'])
            ->all();
    }

    /** Name access points (id, name, entity type). @return array<int,object> */
    public function getNameAccessPointAgents(int $objectId, string $culture = 'en'): array
    {
        return DB::table('relation as r')
            ->join('actor as a', 'a.id', '=', 'r.object_id')
            ->leftJoin('actor_i18n as ai', function ($j) use ($culture) {
                $j->on('ai.id', '=', 'r.object_id')->where('ai.culture', '=', $culture);
            })
            ->where('r.subject_id', $objectId)
            ->where('r.type_id', self::RELATION_NAME_ACCESS_POINT)
            ->whereNotNull('ai.authorized_form_of_name')
            ->get(['a.id', 'a.entity_type_id', 'ai.authorized_form_of_name as name'])
            ->all();
    }

    /** Access-point taxonomies + the name-access-point relation type. */
    public const TAXONOMY_SUBJECT = 35;
    public const TAXONOMY_PLACE = 42;
    public const TAXONOMY_GENRE = 78;
    public const RELATION_NAME_ACCESS_POINT = 161;

    /** Name access points (actors linked via relation type 161). @return array<int,string> */
    public function getNameAccessPointNames(int $objectId, string $culture = 'en'): array
    {
        return DB::table('relation as r')
            ->leftJoin('actor_i18n as ai', function ($j) use ($culture) {
                $j->on('ai.id', '=', 'r.object_id')->where('ai.culture', '=', $culture);
            })
            ->where('r.subject_id', $objectId)
            ->where('r.type_id', self::RELATION_NAME_ACCESS_POINT)
            ->pluck('ai.authorized_form_of_name')
            ->filter()
            ->values()
            ->all();
    }

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
