<?php

namespace AhgAPIPlugin\Repository;

use Illuminate\Database\Capsule\Manager as DB;

class ApiRepository
{
    protected $culture;

    public function __construct($culture = 'en')
    {
        $this->culture = $culture;
    }

    /**
     * Get sector code for a display_standard_id from database mapping
     */
    public function getSectorCode(?int $displayStandardId): ?string
    {
        if (!$displayStandardId) {
            return 'archive';
        }

        $mapping = DB::table('display_standard_sector')
            ->where('term_id', $displayStandardId)
            ->value('sector');

        return $mapping ?: 'archive';
    }

    /**
     * Get sector code by information object ID
     */
    public function getSectorCodeByObjectId(int $informationObjectId): ?string
    {
        $displayStandardId = DB::table('information_object')
            ->where('id', $informationObjectId)
            ->value('display_standard_id');

        return $this->getSectorCode($displayStandardId);
    }

    /**
     * Get display standard info (id, name, code, sector) from database
     */
    public function getDisplayStandardInfo(?int $displayStandardId): ?array
    {
        if (!$displayStandardId) {
            return null;
        }

        $term = DB::table('term as t')
            ->join('term_i18n as ti', function ($join) {
                $join->on('t.id', '=', 'ti.id')
                     ->where('ti.culture', '=', $this->culture);
            })
            ->leftJoin('display_standard_sector as dss', 't.id', '=', 'dss.term_id')
            ->where('t.id', $displayStandardId)
            ->select(['t.id', 't.code', 'ti.name', 'dss.sector'])
            ->first();

        if (!$term) {
            return null;
        }

        return [
            'id' => $term->id,
            'code' => $term->code,
            'name' => $term->name,
            'sector' => $term->sector ?: 'archive'
        ];
    }

    public function getDescriptions(array $params = []): array
    {
        $limit = min($params['limit'] ?? 10, 100);
        $skip = $params['skip'] ?? 0;

        $query = DB::table('information_object as io')
            ->join('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', $this->culture);
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->leftJoin('term_i18n as lod', function ($join) {
                $join->on('io.level_of_description_id', '=', 'lod.id')
                     ->where('lod.culture', '=', $this->culture);
            })
            ->leftJoin('display_standard_sector as dss', 'io.display_standard_id', '=', 'dss.term_id')
            ->where('io.id', '!=', 1)
            ->select([
                'io.id',
                'slug.slug',
                'ioi.title',
                'io.level_of_description_id',
                'lod.name as level_of_description',
                'io.display_standard_id',
                'dss.sector',
                'io.repository_id',
                'io.parent_id',
                'io.lft',
                'io.rgt'
            ]);

        if (!empty($params['repository'])) {
            $repoId = $this->getObjectIdBySlug($params['repository']);
            if ($repoId) {
                $query->where('io.repository_id', $repoId);
            }
        }

        if (!empty($params['level'])) {
            $levelId = $this->getLevelOfDescriptionId($params['level']);
            if ($levelId) {
                $query->where('io.level_of_description_id', $levelId);
            }
        }

        if (!empty($params['sector'])) {
            $query->where('dss.sector', $params['sector']);
        }

        $total = $query->count();
        $results = $query->orderBy('io.id', 'desc')->skip($skip)->take($limit)->get();

        return [
            'total' => $total,
            'limit' => $limit,
            'skip' => $skip,
            'results' => $results->map(function ($row) {
                return [
                    'id' => $row->id,
                    'slug' => $row->slug,
                    'title' => $row->title,
                    'level_of_description' => $row->level_of_description,
                    'sector' => $row->sector ?: 'archive',
                    'repository_id' => $row->repository_id,
                    'parent_id' => $row->parent_id != 1 ? $row->parent_id : null
                ];
            })->toArray()
        ];
    }

    public function getDescriptionBySlug(string $slug): ?array
    {
        $row = DB::table('information_object as io')
            ->join('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', $this->culture);
            })
            ->join('slug', 'io.id', '=', 'slug.object_id')
            ->leftJoin('term_i18n as lod', function ($join) {
                $join->on('io.level_of_description_id', '=', 'lod.id')
                     ->where('lod.culture', '=', $this->culture);
            })
            ->leftJoin('term as dst', 'io.display_standard_id', '=', 'dst.id')
            ->leftJoin('term_i18n as dsti', function ($join) {
                $join->on('dst.id', '=', 'dsti.id')
                     ->where('dsti.culture', '=', $this->culture);
            })
            ->leftJoin('display_standard_sector as dss', 'io.display_standard_id', '=', 'dss.term_id')
            ->where('slug.slug', $slug)
            ->select([
                'io.id',
                'io.identifier',
                'io.level_of_description_id',
                'io.display_standard_id',
                'dst.code as display_standard_code',
                'dsti.name as display_standard_name',
                'dss.sector',
                'io.repository_id',
                'io.parent_id',
                'slug.slug',
                'ioi.title',
                'ioi.scope_and_content',
                'ioi.extent_and_medium',
                'ioi.archival_history',
                'ioi.acquisition',
                'ioi.arrangement',
                'ioi.access_conditions',
                'ioi.reproduction_conditions',
                'lod.name as level_of_description'
            ])
            ->first();

        if (!$row) {
            return null;
        }

        return [
            'id' => $row->id,
            'slug' => $row->slug,
            'identifier' => $row->identifier,
            'title' => $row->title,
            'level_of_description' => $row->level_of_description,
            'display_standard' => $row->display_standard_id ? [
                'id' => $row->display_standard_id,
                'code' => $row->display_standard_code,
                'name' => $row->display_standard_name
            ] : null,
            'sector' => $row->sector ?: 'archive',
            'scope_and_content' => $row->scope_and_content,
            'extent_and_medium' => $row->extent_and_medium,
            'archival_history' => $row->archival_history,
            'acquisition' => $row->acquisition,
            'arrangement' => $row->arrangement,
            'access_conditions' => $row->access_conditions,
            'reproduction_conditions' => $row->reproduction_conditions,
            'repository_id' => $row->repository_id,
            'parent_id' => $row->parent_id != 1 ? $row->parent_id : null
        ];
    }

    
    /**
     * Get full description with all related data
     */
    public function getFullDescription(string $slug): ?array
    {
        $base = $this->getDescriptionBySlug($slug);
        if (!$base) {
            return null;
        }

        $id = $base["id"];

        // Get dates/events
        $base["dates"] = $this->getDescriptionDates($id);

        // Get digital objects
        $base["digital_objects"] = $this->getDigitalObjects($id);

        // Get access points (subjects, places, names)
        $base["subjects"] = $this->getAccessPoints($id, 35);
        $base["places"] = $this->getAccessPoints($id, 42);
        $base["names"] = $this->getRelatedActors($id);

        // Get notes
        $base["notes"] = $this->getNotes($id);

        // Get properties (custom fields)
        $base["properties"] = $this->getProperties($id);

        // Get hierarchy info
        $base["hierarchy"] = $this->getHierarchy($id);

        // Get children count
        $base["children_count"] = $this->getChildrenCount($id);

        return $base;
    }

    protected function getDescriptionDates(int $objectId): array
    {
        $events = DB::table("event as e")
            ->leftJoin("event_i18n as ei", function ($join) {
                $join->on("e.id", "=", "ei.id")
                     ->where("ei.culture", "=", $this->culture);
            })
            ->leftJoin("term_i18n as ti", function ($join) {
                $join->on("e.type_id", "=", "ti.id")
                     ->where("ti.culture", "=", $this->culture);
            })
            ->where("e.object_id", $objectId)
            ->select(["e.id", "e.start_date", "e.end_date", "e.type_id", "ti.name as event_type", "ei.date as date_display", "ei.description"])
            ->get();

        return $events->map(function ($row) {
            return [
                "event_type" => $row->event_type,
                "date_display" => $row->date_display,
                "start_date" => $row->start_date,
                "end_date" => $row->end_date,
                "description" => $row->description
            ];
        })->toArray();
    }

    protected function getDigitalObjects(int $objectId): array
    {
        $objects = DB::table("digital_object as do")
            ->where("do.object_id", $objectId)
            ->select(["do.id", "do.name", "do.path", "do.mime_type", "do.byte_size", "do.checksum", "do.usage_id"])
            ->get();

        return $objects->map(function ($row) {
            $thumbnailPath = null;
            if ($row->path) {
                $pathInfo = pathinfo($row->path);
                $thumbnailPath = "/uploads/" . $pathInfo["dirname"] . "/" . $pathInfo["filename"] . "_142." . ($pathInfo["extension"] ?? "jpg");
            }
            return [
                "id" => $row->id,
                "name" => $row->name,
                "mime_type" => $row->mime_type,
                "byte_size" => $row->byte_size,
                "checksum" => $row->checksum,
                "thumbnail_url" => $thumbnailPath,
                "master_url" => $row->path ? "/uploads/" . $row->path : null
            ];
        })->toArray();
    }

    protected function getAccessPoints(int $objectId, int $taxonomyId): array
    {
        $terms = DB::table("object_term_relation as otr")
            ->join("term as t", "otr.term_id", "=", "t.id")
            ->join("term_i18n as ti", function ($join) {
                $join->on("t.id", "=", "ti.id")
                     ->where("ti.culture", "=", $this->culture);
            })
            ->where("otr.object_id", $objectId)
            ->where("t.taxonomy_id", $taxonomyId)
            ->select(["t.id", "ti.name"])
            ->get();

        return $terms->map(function ($row) {
            return ["id" => $row->id, "name" => $row->name];
        })->toArray();
    }

    protected function getRelatedActors(int $objectId): array
    {
        $actors = DB::table("event as e")
            ->join("actor as a", "e.actor_id", "=", "a.id")
            ->join("actor_i18n as ai", function ($join) {
                $join->on("a.id", "=", "ai.id")
                     ->where("ai.culture", "=", $this->culture);
            })
            ->leftJoin("slug", "a.id", "=", "slug.object_id")
            ->leftJoin("term_i18n as ti", function ($join) {
                $join->on("e.type_id", "=", "ti.id")
                     ->where("ti.culture", "=", $this->culture);
            })
            ->where("e.object_id", $objectId)
            ->whereNotNull("e.actor_id")
            ->select(["a.id", "slug.slug", "ai.authorized_form_of_name", "ti.name as relation_type"])
            ->get();

        return $actors->map(function ($row) {
            return [
                "id" => $row->id,
                "slug" => $row->slug,
                "name" => $row->authorized_form_of_name,
                "relation_type" => $row->relation_type
            ];
        })->toArray();
    }

    protected function getNotes(int $objectId): array
    {
        $notes = DB::table("note as n")
            ->join("note_i18n as ni", function ($join) {
                $join->on("n.id", "=", "ni.id")
                     ->where("ni.culture", "=", $this->culture);
            })
            ->leftJoin("term_i18n as ti", function ($join) {
                $join->on("n.type_id", "=", "ti.id")
                     ->where("ti.culture", "=", $this->culture);
            })
            ->where("n.object_id", $objectId)
            ->select(["n.id", "ti.name as note_type", "ni.content"])
            ->get();

        return $notes->map(function ($row) {
            return ["type" => $row->note_type, "content" => $row->content];
        })->toArray();
    }

    protected function getProperties(int $objectId): array
    {
        $props = DB::table("property as p")
            ->leftJoin("property_i18n as pi", function ($join) {
                $join->on("p.id", "=", "pi.id")
                     ->where("pi.culture", "=", $this->culture);
            })
            ->where("p.object_id", $objectId)
            ->select(["p.name", "pi.value"])
            ->get();

        $result = [];
        foreach ($props as $prop) {
            if ($prop->name && $prop->value) {
                $result[$prop->name] = $prop->value;
            }
        }
        return $result;
    }

    protected function getHierarchy(int $objectId): array
    {
        $current = DB::table("information_object")
            ->where("id", $objectId)
            ->select(["parent_id", "lft", "rgt"])
            ->first();

        if (!$current || $current->parent_id == 1) {
            return [];
        }

        $ancestorRows = DB::table("information_object as io")
            ->join("information_object_i18n as ioi", function ($join) {
                $join->on("io.id", "=", "ioi.id")
                     ->where("ioi.culture", "=", $this->culture);
            })
            ->leftJoin("slug", "io.id", "=", "slug.object_id")
            ->where("io.lft", "<", $current->lft)
            ->where("io.rgt", ">", $current->rgt)
            ->where("io.id", "!=", 1)
            ->select(["io.id", "slug.slug", "ioi.title", "io.lft"])
            ->orderBy("io.lft", "asc")
            ->get();

        return $ancestorRows->map(function ($row) {
            return ["id" => $row->id, "slug" => $row->slug, "title" => $row->title];
        })->toArray();
    }

    protected function getChildrenCount(int $objectId): int
    {
        return DB::table("information_object")
            ->where("parent_id", $objectId)
            ->count();
    }

public function getAuthorities(array $params = []): array
    {
        $limit = min($params['limit'] ?? 10, 100);
        $skip = $params['skip'] ?? 0;

        $query = DB::table('actor as a')
            ->join('actor_i18n as ai', function ($join) {
                $join->on('a.id', '=', 'ai.id')
                     ->where('ai.culture', '=', $this->culture);
            })
            ->leftJoin('slug', 'a.id', '=', 'slug.object_id')
            ->leftJoin('term_i18n as et', function ($join) {
                $join->on('a.entity_type_id', '=', 'et.id')
                     ->where('et.culture', '=', $this->culture);
            })
            ->where('a.id', '!=', 1)
            ->select([
                'a.id',
                'slug.slug',
                'ai.authorized_form_of_name',
                'a.entity_type_id',
                'et.name as entity_type'
            ]);

        $total = $query->count();
        $results = $query->orderBy('ai.authorized_form_of_name', 'asc')
                         ->skip($skip)->take($limit)->get();

        return [
            'total' => $total,
            'limit' => $limit,
            'skip' => $skip,
            'results' => $results->map(function ($row) {
                return [
                    'id' => $row->id,
                    'slug' => $row->slug,
                    'authorized_form_of_name' => $row->authorized_form_of_name,
                    'entity_type' => $row->entity_type
                ];
            })->toArray()
        ];
    }

    public function getAuthorityBySlug(string $slug): ?array
    {
        $row = DB::table('actor as a')
            ->join('actor_i18n as ai', function ($join) {
                $join->on('a.id', '=', 'ai.id')
                     ->where('ai.culture', '=', $this->culture);
            })
            ->join('slug', 'a.id', '=', 'slug.object_id')
            ->leftJoin('term_i18n as et', function ($join) {
                $join->on('a.entity_type_id', '=', 'et.id')
                     ->where('et.culture', '=', $this->culture);
            })
            ->where('slug.slug', $slug)
            ->select(['a.id', 'slug.slug', 'ai.*', 'et.name as entity_type'])
            ->first();

        if (!$row) {
            return null;
        }

        return [
            'id' => $row->id,
            'slug' => $row->slug,
            'authorized_form_of_name' => $row->authorized_form_of_name,
            'entity_type' => $row->entity_type,
            'dates_of_existence' => $row->dates_of_existence,
            'history' => $row->history,
            'places' => $row->places,
            'functions' => $row->functions
        ];
    }

    public function getRepositories(array $params = []): array
    {
        $limit = min($params['limit'] ?? 10, 100);
        $skip = $params['skip'] ?? 0;

        $query = DB::table('repository as r')
            ->join('actor as a', 'r.id', '=', 'a.id')
            ->join('actor_i18n as ai', function ($join) {
                $join->on('a.id', '=', 'ai.id')
                     ->where('ai.culture', '=', $this->culture);
            })
            ->leftJoin('slug', 'r.id', '=', 'slug.object_id')
            ->select(['r.id', 'slug.slug', 'ai.authorized_form_of_name', 'r.identifier']);

        $total = $query->count();
        $results = $query->orderBy('ai.authorized_form_of_name', 'asc')
                         ->skip($skip)->take($limit)->get();

        return [
            'total' => $total,
            'limit' => $limit,
            'skip' => $skip,
            'results' => $results->map(function ($row) {
                return [
                    'id' => $row->id,
                    'slug' => $row->slug,
                    'name' => $row->authorized_form_of_name,
                    'identifier' => $row->identifier
                ];
            })->toArray()
        ];
    }

    public function getTaxonomies(): array
    {
        $results = DB::table('taxonomy as t')
            ->join('taxonomy_i18n as ti', function ($join) {
                $join->on('t.id', '=', 'ti.id')
                     ->where('ti.culture', '=', $this->culture);
            })
            ->where('t.id', '!=', 1)
            ->select(['t.id', 'ti.name', 't.usage'])
            ->orderBy('ti.name', 'asc')
            ->get();

        return $results->map(function ($row) {
            return ['id' => $row->id, 'name' => $row->name, 'usage' => $row->usage];
        })->toArray();
    }

    public function getTaxonomyTerms(int $taxonomyId): array
    {
        $results = DB::table('term as t')
            ->join('term_i18n as ti', function ($join) {
                $join->on('t.id', '=', 'ti.id')
                     ->where('ti.culture', '=', $this->culture);
            })
            ->where('t.taxonomy_id', $taxonomyId)
            ->where('t.id', '!=', 1)
            ->select(['t.id', 'ti.name', 't.parent_id', 't.code'])
            ->orderBy('t.lft', 'asc')
            ->get();

        return $results->map(function ($row) {
            return [
                'id' => $row->id,
                'name' => $row->name,
                'code' => $row->code,
                'parent_id' => $row->parent_id != 1 ? $row->parent_id : null
            ];
        })->toArray();
    }

    /**
     * Get all sectors from database
     */
    public function getSectors(): array
    {
        return DB::table('display_standard_sector')
            ->select('sector')
            ->distinct()
            ->orderBy('sector')
            ->pluck('sector')
            ->toArray();
    }

    public function getObjectIdBySlug(string $slug): ?int
    {
        $row = DB::table('slug')->where('slug', $slug)->first();
        return $row ? $row->object_id : null;
    }

    protected function getLevelOfDescriptionId(string $name): ?int
    {
        $row = DB::table('term_i18n as ti')
            ->join('term as t', 'ti.id', '=', 't.id')
            ->where('t.taxonomy_id', 34)
            ->whereRaw('LOWER(ti.name) = ?', [strtolower($name)])
            ->first();
        return $row ? $row->id : null;
    }

    // ========================================================================
    // CONDITION ASSESSMENT METHODS (Mobile)
    // ========================================================================

    public function getConditions(array $params = []): array
    {
        $limit = min($params['limit'] ?? 10, 100);
        $skip = $params['skip'] ?? 0;

        $query = DB::table('spectrum_condition_check as c')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('c.object_id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', $this->culture);
            })
            ->leftJoin('slug', 'c.object_id', '=', 'slug.object_id')
            ->select([
                'c.id',
                'c.object_id',
                'slug.slug as object_slug',
                'ioi.title as object_title',
                'c.condition_reference',
                'c.check_date',
                'c.check_reason',
                'c.checked_by',
                'c.overall_condition',
                'c.condition_note',
                'c.treatment_priority',
                'c.next_check_date'
            ]);

        if (!empty($params['object_id'])) {
            $query->where('c.object_id', $params['object_id']);
        }

        if (!empty($params['object_slug'])) {
            $objectId = $this->getObjectIdBySlug($params['object_slug']);
            if ($objectId) {
                $query->where('c.object_id', $objectId);
            }
        }

        if (!empty($params['overall_condition'])) {
            $query->where('c.overall_condition', $params['overall_condition']);
        }

        if (!empty($params['checked_by'])) {
            $query->where('c.checked_by', 'LIKE', '%' . $params['checked_by'] . '%');
        }

        $total = $query->count();
        $results = $query->orderBy('c.check_date', 'desc')
                         ->skip($skip)
                         ->take($limit)
                         ->get();

        return [
            'total' => $total,
            'limit' => $limit,
            'skip' => $skip,
            'results' => $results->map(function ($row) {
                return (array) $row;
            })->toArray()
        ];
    }

    public function getConditionById(int $id): ?array
    {
        $row = DB::table('spectrum_condition_check as c')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('c.object_id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', $this->culture);
            })
            ->leftJoin('slug', 'c.object_id', '=', 'slug.object_id')
            ->where('c.id', $id)
            ->select(['c.*', 'slug.slug as object_slug', 'ioi.title as object_title'])
            ->first();

        if (!$row) {
            return null;
        }

        $result = (array) $row;

        // Get photos
        $result['photos'] = $this->getConditionPhotos($id);

        return $result;
    }

    public function createCondition(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        return DB::table('spectrum_condition_check')->insertGetId([
            'object_id' => $data['object_id'],
            'condition_reference' => $data['condition_reference'] ?? $this->generateConditionReference(),
            'check_date' => $data['check_date'] ?? $now,
            'check_reason' => $data['check_reason'] ?? null,
            'checked_by' => $data['checked_by'],
            'overall_condition' => $data['overall_condition'] ?? null,
            'condition_note' => $data['condition_note'] ?? null,
            'completeness_note' => $data['completeness_note'] ?? null,
            'hazard_note' => $data['hazard_note'] ?? null,
            'technical_assessment' => $data['technical_assessment'] ?? null,
            'recommended_treatment' => $data['recommended_treatment'] ?? null,
            'treatment_priority' => $data['treatment_priority'] ?? null,
            'next_check_date' => $data['next_check_date'] ?? null,
            'environment_recommendation' => $data['environment_recommendation'] ?? null,
            'handling_recommendation' => $data['handling_recommendation'] ?? null,
            'display_recommendation' => $data['display_recommendation'] ?? null,
            'storage_recommendation' => $data['storage_recommendation'] ?? null,
            'packing_recommendation' => $data['packing_recommendation'] ?? null
        ]);
    }

    public function updateCondition(int $id, array $data): bool
    {
        $updateData = array_filter([
            'check_date' => $data['check_date'] ?? null,
            'check_reason' => $data['check_reason'] ?? null,
            'checked_by' => $data['checked_by'] ?? null,
            'overall_condition' => $data['overall_condition'] ?? null,
            'condition_note' => $data['condition_note'] ?? null,
            'completeness_note' => $data['completeness_note'] ?? null,
            'hazard_note' => $data['hazard_note'] ?? null,
            'technical_assessment' => $data['technical_assessment'] ?? null,
            'recommended_treatment' => $data['recommended_treatment'] ?? null,
            'treatment_priority' => $data['treatment_priority'] ?? null,
            'next_check_date' => $data['next_check_date'] ?? null,
            'environment_recommendation' => $data['environment_recommendation'] ?? null,
            'handling_recommendation' => $data['handling_recommendation'] ?? null,
            'display_recommendation' => $data['display_recommendation'] ?? null,
            'storage_recommendation' => $data['storage_recommendation'] ?? null,
            'packing_recommendation' => $data['packing_recommendation'] ?? null
        ], function ($v) { return $v !== null; });

        if (empty($updateData)) {
            return false;
        }

        return DB::table('spectrum_condition_check')
            ->where('id', $id)
            ->update($updateData) > 0;
    }

    public function deleteCondition(int $id): bool
    {
        // Delete photos first
        DB::table('spectrum_condition_photo')->where('condition_check_id', $id)->delete();
        return DB::table('spectrum_condition_check')->where('id', $id)->delete() > 0;
    }

    protected function generateConditionReference(): string
    {
        return 'CC-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    }

    // ========================================================================
    // CONDITION PHOTOS (Mobile Upload)
    // ========================================================================

    public function getConditionPhotos(int $conditionId): array
    {
        $photos = DB::table('spectrum_condition_photo')
            ->where('condition_check_id', $conditionId)
            ->orderBy('sort_order')
            ->get();

        return $photos->map(function ($row) {
            return [
                'id' => $row->id,
                'photo_type' => $row->photo_type,
                'caption' => $row->caption,
                'description' => $row->description,
                'location_on_object' => $row->location_on_object,
                'filename' => $row->filename,
                'file_path' => $row->file_path,
                'mime_type' => $row->mime_type,
                'file_size' => $row->file_size,
                'width' => $row->width,
                'height' => $row->height,
                'photographer' => $row->photographer,
                'photo_date' => $row->photo_date,
                'is_primary' => (bool) $row->is_primary,
                'thumbnail_url' => $row->file_path ? '/uploads/conditions/' . basename($row->file_path) : null,
                'full_url' => $row->file_path ? '/uploads/conditions/' . basename($row->file_path) : null
            ];
        })->toArray();
    }

    public function createConditionPhoto(int $conditionId, array $data): int
    {
        $sortOrder = DB::table('spectrum_condition_photo')
            ->where('condition_check_id', $conditionId)
            ->max('sort_order') ?? 0;

        return DB::table('spectrum_condition_photo')->insertGetId([
            'condition_check_id' => $conditionId,
            'photo_type' => $data['photo_type'] ?? 'detail',
            'caption' => $data['caption'] ?? null,
            'description' => $data['description'] ?? null,
            'location_on_object' => $data['location_on_object'] ?? null,
            'filename' => $data['filename'],
            'original_filename' => $data['original_filename'] ?? $data['filename'],
            'file_path' => $data['file_path'],
            'file_size' => $data['file_size'] ?? null,
            'mime_type' => $data['mime_type'] ?? null,
            'width' => $data['width'] ?? null,
            'height' => $data['height'] ?? null,
            'photographer' => $data['photographer'] ?? null,
            'photo_date' => $data['photo_date'] ?? date('Y-m-d'),
            'camera_info' => $data['camera_info'] ?? null,
            'sort_order' => $sortOrder + 1,
            'is_primary' => $data['is_primary'] ?? 0
        ]);
    }

    public function deleteConditionPhoto(int $photoId): bool
    {
        $photo = DB::table('spectrum_condition_photo')->where('id', $photoId)->first();
        if ($photo && $photo->file_path && file_exists($photo->file_path)) {
            @unlink($photo->file_path);
        }
        return DB::table('spectrum_condition_photo')->where('id', $photoId)->delete() > 0;
    }

    // ========================================================================
    // HERITAGE ASSETS (International Standards)
    // ========================================================================

    public function getAssets(array $params = []): array
    {
        $limit = min($params['limit'] ?? 10, 100);
        $skip = $params['skip'] ?? 0;

        $query = DB::table('heritage_asset as ha')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('ha.object_id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', $this->culture);
            })
            ->leftJoin('slug', 'ha.object_id', '=', 'slug.object_id')
            ->leftJoin('heritage_asset_class as hac', 'ha.asset_class_id', '=', 'hac.id')
            ->select([
                'ha.id',
                'ha.object_id',
                'slug.slug as object_slug',
                'ioi.title as object_title',
                'ha.recognition_status',
                'hac.name as asset_class',
                'ha.acquisition_date',
                'ha.acquisition_cost',
                'ha.current_carrying_amount',
                'ha.fair_value_at_acquisition',
                'ha.nominal_value',
                'ha.measurement_basis',
                'ha.acquisition_method',
                'ha.donor_name'
            ]);

        if (!empty($params['asset_class_id'])) {
            $query->where('ha.asset_class_id', $params['asset_class_id']);
        }

        if (!empty($params['object_slug'])) {
            $objectId = $this->getObjectIdBySlug($params['object_slug']);
            if ($objectId) {
                $query->where('ha.object_id', $objectId);
            }
        }

        $total = $query->count();
        $results = $query->orderBy('ha.id', 'desc')
                         ->skip($skip)
                         ->take($limit)
                         ->get();

        return [
            'total' => $total,
            'limit' => $limit,
            'skip' => $skip,
            'results' => $results->map(function ($row) {
                return (array) $row;
            })->toArray()
        ];
    }

    public function getAssetById(int $id): ?array
    {
        $row = DB::table('heritage_asset as ha')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('ha.object_id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', $this->culture);
            })
            ->leftJoin('slug', 'ha.object_id', '=', 'slug.object_id')
            ->leftJoin('heritage_asset_class as hac', 'ha.asset_class_id', '=', 'hac.id')
            ->where('ha.id', $id)
            ->select(['ha.*', 'slug.slug as object_slug', 'ioi.title as object_title', 'hac.name as asset_class'])
            ->first();

        if (!$row) {
            return null;
        }

        $result = (array) $row;

        // Get valuation history
        $result['valuations'] = $this->getAssetValuations($id);

        return $result;
    }

    public function getAssetByObjectId(int $objectId): ?array
    {
        $asset = DB::table('heritage_asset')->where('object_id', $objectId)->first();
        if (!$asset) {
            return null;
        }
        return $this->getAssetById($asset->id);
    }

    public function createAsset(array $data): int
    {
        return DB::table('heritage_asset')->insertGetId([
            'object_id' => $data['object_id'],
            'asset_number' => $data['asset_number'] ?? $this->generateAssetNumber(),
            'asset_class_id' => $data['asset_class_id'] ?? null,
            'acquisition_date' => $data['acquisition_date'] ?? null,
            'acquisition_cost' => $data['acquisition_cost'] ?? null,
            'acquisition_method' => $data['acquisition_method'] ?? null,
            'current_value' => $data['current_value'] ?? null,
            'last_valuation_date' => $data['last_valuation_date'] ?? null,
            'valuation_method' => $data['valuation_method'] ?? null,
            'currency_code' => $data['currency_code'] ?? 'ZAR',
            'is_insured' => $data['is_insured'] ?? 0,
            'insurance_value' => $data['insurance_value'] ?? null,
            'insurance_policy' => $data['insurance_policy'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function updateAsset(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        $updateData = array_filter($data, function ($v, $k) {
            return $v !== null && !in_array($k, ['id', 'object_id', 'created_at']);
        }, ARRAY_FILTER_USE_BOTH);

        return DB::table('heritage_asset')
            ->where('id', $id)
            ->update($updateData) > 0;
    }

    protected function generateAssetNumber(): string
    {
        return 'HA-' . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    // ========================================================================
    // VALUATIONS
    // ========================================================================

    public function getAssetValuations(int $assetId): array
    {
        $valuations = DB::table('heritage_valuation_history')
            ->where('asset_id', $assetId)
            ->orderBy('valuation_date', 'desc')
            ->get();

        return $valuations->map(function ($row) {
            return (array) $row;
        })->toArray();
    }

    public function createValuation(array $data): int
    {
        $id = DB::table('heritage_valuation_history')->insertGetId([
            'asset_id' => $data['asset_id'],
            'valuation_date' => $data['valuation_date'] ?? date('Y-m-d'),
            'valuation_amount' => $data['valuation_amount'],
            'valuation_method' => $data['valuation_method'] ?? null,
            'valuer_name' => $data['valuer_name'] ?? null,
            'valuer_organization' => $data['valuer_organization'] ?? null,
            'valuation_notes' => $data['valuation_notes'] ?? null,
            'currency_code' => $data['currency_code'] ?? 'ZAR',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Update asset current value
        DB::table('heritage_asset')
            ->where('id', $data['asset_id'])
            ->update([
                'current_value' => $data['valuation_amount'],
                'last_valuation_date' => $data['valuation_date'] ?? date('Y-m-d'),
                'valuation_method' => $data['valuation_method'] ?? null,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        return $id;
    }

    // ========================================================================
    // PRIVACY/COMPLIANCE (International)
    // ========================================================================

    public function getDsars(array $params = []): array
    {
        $limit = min($params['limit'] ?? 10, 100);
        $skip = $params['skip'] ?? 0;

        $query = DB::table('privacy_dsar')
            ->select([
                'id',
                'reference_number',
                'jurisdiction',
                'request_type',
                'status',
                'priority',
                'requestor_name',
                'requestor_email',
                'requestor_phone',
                'is_verified',
                'received_date',
                'due_date',
                'completed_date',
                'assigned_to'
            ]);

        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (!empty($params['request_type'])) {
            $query->where('request_type', $params['request_type']);
        }

        if (!empty($params['jurisdiction'])) {
            $query->where('jurisdiction', $params['jurisdiction']);
        }

        $total = $query->count();
        $results = $query->orderBy('received_date', 'desc')
                         ->skip($skip)
                         ->take($limit)
                         ->get();

        return [
            'total' => $total,
            'limit' => $limit,
            'skip' => $skip,
            'results' => $results->map(function ($row) {
                return (array) $row;
            })->toArray()
        ];
    }

    public function getDsarById(int $id): ?array
    {
        $row = DB::table('privacy_dsar as d')
            ->leftJoin('privacy_dsar_i18n as di', function ($join) {
                $join->on('d.id', '=', 'di.id')
                     ->where('di.culture', '=', $this->culture);
            })
            ->where('d.id', $id)
            ->select(['d.*', 'di.*'])
            ->first();

        if (!$row) {
            return null;
        }

        $result = (array) $row;

        // Get logs
        $result['logs'] = DB::table('privacy_dsar_log')
            ->where('dsar_id', $id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($r) { return (array) $r; })
            ->toArray();

        return $result;
    }

    public function createDsar(array $data): int
    {
        $id = DB::table('privacy_dsar')->insertGetId([
            'reference_number' => $data['reference_number'] ?? $this->generateDsarReference(),
            'request_type' => $data['request_type'] ?? 'access',
            'status' => 'pending',
            'requester_name' => $data['requester_name'],
            'requester_email' => $data['requester_email'] ?? null,
            'requester_phone' => $data['requester_phone'] ?? null,
            'requester_address' => $data['requester_address'] ?? null,
            'date_received' => $data['date_received'] ?? date('Y-m-d'),
            'date_due' => $data['date_due'] ?? date('Y-m-d', strtotime('+30 days')),
            'assigned_to' => $data['assigned_to'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Insert i18n
        DB::table('privacy_dsar_i18n')->insert([
            'id' => $id,
            'culture' => $this->culture,
            'subject_matter' => $data['subject_matter'] ?? null,
            'notes' => $data['notes'] ?? null
        ]);

        return $id;
    }

    public function updateDsar(int $id, array $data): bool
    {
        $mainData = array_filter([
            'status' => $data['status'] ?? null,
            'date_due' => $data['date_due'] ?? null,
            'date_completed' => $data['date_completed'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'updated_at' => date('Y-m-d H:i:s')
        ], function ($v) { return $v !== null; });

        if (!empty($mainData)) {
            DB::table('privacy_dsar')->where('id', $id)->update($mainData);
        }

        // Update i18n if provided
        $i18nData = array_filter([
            'subject_matter' => $data['subject_matter'] ?? null,
            'notes' => $data['notes'] ?? null
        ], function ($v) { return $v !== null; });

        if (!empty($i18nData)) {
            DB::table('privacy_dsar_i18n')
                ->where('id', $id)
                ->where('culture', $this->culture)
                ->update($i18nData);
        }

        // Log status change
        if (!empty($data['status'])) {
            DB::table('privacy_dsar_log')->insert([
                'dsar_id' => $id,
                'action' => 'status_change',
                'details' => json_encode(['new_status' => $data['status']]),
                'user_id' => $data['user_id'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        return true;
    }

    protected function generateDsarReference(): string
    {
        return 'DSAR-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    public function getBreaches(array $params = []): array
    {
        $limit = min($params['limit'] ?? 10, 100);
        $skip = $params['skip'] ?? 0;

        $query = DB::table('privacy_breach as b')
            ->leftJoin('privacy_breach_i18n as bi', function ($join) {
                $join->on('b.id', '=', 'bi.id')
                     ->where('bi.culture', '=', $this->culture);
            })
            ->select([
                'b.id',
                'b.reference_number',
                'b.breach_date',
                'b.discovery_date',
                'b.severity',
                'b.status',
                'b.breach_type',
                'b.affected_count',
                'bi.description',
                'bi.impact_assessment'
            ]);

        if (!empty($params['status'])) {
            $query->where('b.status', $params['status']);
        }

        if (!empty($params['severity'])) {
            $query->where('b.severity', $params['severity']);
        }

        $total = $query->count();
        $results = $query->orderBy('b.breach_date', 'desc')
                         ->skip($skip)
                         ->take($limit)
                         ->get();

        return [
            'total' => $total,
            'limit' => $limit,
            'skip' => $skip,
            'results' => $results->map(function ($row) {
                return (array) $row;
            })->toArray()
        ];
    }

    public function createBreach(array $data): int
    {
        $id = DB::table('privacy_breach')->insertGetId([
            'reference_number' => $data['reference_number'] ?? 'BR-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 4)),
            'breach_date' => $data['breach_date'] ?? date('Y-m-d'),
            'discovery_date' => $data['discovery_date'] ?? date('Y-m-d'),
            'severity' => $data['severity'] ?? 'medium',
            'status' => 'investigating',
            'breach_type' => $data['breach_type'] ?? null,
            'affected_count' => $data['affected_count'] ?? null,
            'reported_by' => $data['reported_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        DB::table('privacy_breach_i18n')->insert([
            'id' => $id,
            'culture' => $this->culture,
            'description' => $data['description'] ?? null,
            'impact_assessment' => $data['impact_assessment'] ?? null,
            'containment_actions' => $data['containment_actions'] ?? null,
            'remediation_steps' => $data['remediation_steps'] ?? null
        ]);

        return $id;
    }
}
