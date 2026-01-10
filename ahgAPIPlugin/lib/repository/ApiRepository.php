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

    protected function getObjectIdBySlug(string $slug): ?int
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
}
