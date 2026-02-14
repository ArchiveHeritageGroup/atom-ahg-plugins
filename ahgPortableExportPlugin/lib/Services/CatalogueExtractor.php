<?php

namespace AhgPortableExportPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Extracts catalogue data from the AtoM database for portable export.
 *
 * Queries the information_object hierarchy (MPPT-ordered), access points,
 * events, creators, and repositories — producing catalogue.json and
 * taxonomies.json for the client-side viewer.
 */
class CatalogueExtractor
{
    /** @var string */
    protected $culture;

    /** @var callable|null Progress callback: fn(int $current, int $total) */
    protected $progressCallback;

    public function __construct(string $culture = 'en', ?callable $progressCallback = null)
    {
        $this->culture = $culture;
        $this->progressCallback = $progressCallback;
    }

    /**
     * Extract catalogue data for the given scope.
     *
     * @return array{descriptions: array, hierarchy: array, taxonomies: array, repositories: array}
     */
    public function extract(string $scopeType, ?string $scopeSlug = null, ?int $repositoryId = null): array
    {
        $descriptions = $this->extractDescriptions($scopeType, $scopeSlug, $repositoryId);
        $hierarchy = $this->buildHierarchy($descriptions);
        $taxonomies = $this->extractTaxonomies($descriptions);
        $repositories = $this->extractRepositories($descriptions);

        return [
            'descriptions' => $descriptions,
            'hierarchy' => $hierarchy,
            'taxonomies' => $taxonomies,
            'repositories' => $repositories,
        ];
    }

    /**
     * Extract all information objects matching the scope.
     */
    protected function extractDescriptions(string $scopeType, ?string $scopeSlug, ?int $repositoryId): array
    {
        $query = DB::table('information_object as io')
            ->join('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $this->culture);
            })
            ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
            ->where('io.id', '!=', 1) // Exclude root node
            ->select(
                'io.id',
                'io.identifier',
                'io.parent_id',
                'io.lft',
                'io.rgt',
                'io.level_of_description_id',
                'io.repository_id',
                'io.source_culture',
                'ioi.title',
                'ioi.scope_and_content',
                'ioi.extent_and_medium',
                'ioi.archival_history',
                'ioi.acquisition',
                'ioi.arrangement',
                'ioi.access_conditions',
                'ioi.reproduction_conditions',
                'ioi.physical_characteristics',
                'ioi.finding_aids',
                'ioi.location_of_originals',
                'ioi.location_of_copies',
                'ioi.related_units_of_description',
                'ioi.rules',
                's.slug'
            )
            ->orderBy('io.lft');

        // Apply scope filters
        switch ($scopeType) {
            case 'fonds':
                if ($scopeSlug) {
                    $root = $this->resolveSlug($scopeSlug);
                    if ($root) {
                        $query->where('io.lft', '>=', $root->lft)
                            ->where('io.rgt', '<=', $root->rgt);
                    }
                }
                break;

            case 'repository':
                if ($repositoryId) {
                    $query->where('io.repository_id', '=', $repositoryId);
                }
                break;

            case 'custom':
                // Custom scope uses fonds slug with repository filter
                if ($scopeSlug) {
                    $root = $this->resolveSlug($scopeSlug);
                    if ($root) {
                        $query->where('io.lft', '>=', $root->lft)
                            ->where('io.rgt', '<=', $root->rgt);
                    }
                }
                if ($repositoryId) {
                    $query->where('io.repository_id', '=', $repositoryId);
                }
                break;

            case 'all':
            default:
                // No additional filters
                break;
        }

        $rows = $query->get();
        $total = count($rows);
        $descriptions = [];

        foreach ($rows as $i => $row) {
            $desc = $this->buildDescription($row);
            $descriptions[$row->id] = $desc;

            if ($this->progressCallback && $i % 50 === 0) {
                ($this->progressCallback)($i, $total);
            }
        }

        // Enrich with access points, events, digital objects, creators
        $ids = array_keys($descriptions);
        if (!empty($ids)) {
            $this->enrichWithAccessPoints($descriptions, $ids);
            $this->enrichWithEvents($descriptions, $ids);
            $this->enrichWithDigitalObjects($descriptions, $ids);
            $this->enrichWithCreators($descriptions, $ids);
        }

        return array_values($descriptions);
    }

    /**
     * Build a description array from a database row.
     */
    protected function buildDescription(object $row): array
    {
        $levelName = $this->getTermName($row->level_of_description_id);

        return [
            'id' => (int) $row->id,
            'identifier' => $row->identifier,
            'slug' => $row->slug,
            'parent_id' => (int) $row->parent_id,
            'lft' => (int) $row->lft,
            'rgt' => (int) $row->rgt,
            'level_of_description_id' => $row->level_of_description_id ? (int) $row->level_of_description_id : null,
            'level_of_description' => $levelName,
            'repository_id' => $row->repository_id ? (int) $row->repository_id : null,
            'title' => $row->title ?? '',
            'scope_and_content' => $row->scope_and_content,
            'extent_and_medium' => $row->extent_and_medium,
            'archival_history' => $row->archival_history,
            'acquisition' => $row->acquisition,
            'arrangement' => $row->arrangement,
            'access_conditions' => $row->access_conditions,
            'reproduction_conditions' => $row->reproduction_conditions,
            'physical_characteristics' => $row->physical_characteristics,
            'finding_aids' => $row->finding_aids,
            'location_of_originals' => $row->location_of_originals,
            'location_of_copies' => $row->location_of_copies,
            'related_units_of_description' => $row->related_units_of_description,
            'rules' => $row->rules,
            'dates' => [],
            'creators' => [],
            'subjects' => [],
            'places' => [],
            'genres' => [],
            'name_access_points' => [],
            'digital_objects' => [],
        ];
    }

    /**
     * Enrich descriptions with access points (subjects, places, genres).
     */
    protected function enrichWithAccessPoints(array &$descriptions, array $ids): void
    {
        // Subject access points (taxonomy_id = 35)
        $this->loadAccessPoints($descriptions, $ids, 35, 'subjects');

        // Place access points (taxonomy_id = 42)
        $this->loadAccessPoints($descriptions, $ids, 42, 'places');

        // Genre access points (taxonomy_id = 78)
        $this->loadAccessPoints($descriptions, $ids, 78, 'genres');
    }

    /**
     * Load access points of a specific taxonomy for the given IDs.
     */
    protected function loadAccessPoints(array &$descriptions, array $ids, int $taxonomyId, string $field): void
    {
        $chunks = array_chunk($ids, 500);

        foreach ($chunks as $chunk) {
            $rows = DB::table('object_term_relation as otr')
                ->join('term as t', 'otr.term_id', '=', 't.id')
                ->join('term_i18n as ti', function ($join) {
                    $join->on('t.id', '=', 'ti.id')
                        ->where('ti.culture', '=', $this->culture);
                })
                ->where('t.taxonomy_id', '=', $taxonomyId)
                ->whereIn('otr.object_id', $chunk)
                ->select('otr.object_id', 'ti.name')
                ->get();

            foreach ($rows as $row) {
                $oid = (int) $row->object_id;
                if (isset($descriptions[$oid]) && $row->name) {
                    $descriptions[$oid][$field][] = $row->name;
                }
            }
        }
    }

    /**
     * Enrich descriptions with event dates and actors.
     */
    protected function enrichWithEvents(array &$descriptions, array $ids): void
    {
        $chunks = array_chunk($ids, 500);

        foreach ($chunks as $chunk) {
            $rows = DB::table('event as e')
                ->leftJoin('event_i18n as ei', function ($join) {
                    $join->on('e.id', '=', 'ei.id')
                        ->where('ei.culture', '=', $this->culture);
                })
                ->leftJoin('actor_i18n as ai', function ($join) {
                    $join->on('e.actor_id', '=', 'ai.id')
                        ->where('ai.culture', '=', $this->culture);
                })
                ->leftJoin('term_i18n as eti', function ($join) {
                    $join->on('e.type_id', '=', 'eti.id')
                        ->where('eti.culture', '=', $this->culture);
                })
                ->whereIn('e.object_id', $chunk)
                ->select(
                    'e.object_id',
                    'e.type_id',
                    'eti.name as event_type',
                    'ei.date',
                    'e.start_date',
                    'e.end_date',
                    'ai.authorized_form_of_name as actor_name'
                )
                ->get();

            foreach ($rows as $row) {
                $oid = (int) $row->object_id;
                if (!isset($descriptions[$oid])) {
                    continue;
                }

                $descriptions[$oid]['dates'][] = [
                    'type' => $row->event_type,
                    'date' => $row->date,
                    'start_date' => $row->start_date,
                    'end_date' => $row->end_date,
                    'actor' => $row->actor_name,
                ];
            }
        }
    }

    /**
     * Enrich descriptions with creator name access points (relation type 161).
     */
    protected function enrichWithCreators(array &$descriptions, array $ids): void
    {
        $chunks = array_chunk($ids, 500);

        foreach ($chunks as $chunk) {
            $rows = DB::table('relation as r')
                ->join('actor_i18n as ai', function ($join) {
                    $join->on('r.object_id', '=', 'ai.id')
                        ->where('ai.culture', '=', $this->culture);
                })
                ->where('r.type_id', '=', 161) // Name access point relation
                ->whereIn('r.subject_id', $chunk)
                ->select('r.subject_id as object_id', 'ai.authorized_form_of_name as name')
                ->get();

            foreach ($rows as $row) {
                $oid = (int) $row->object_id;
                if (isset($descriptions[$oid]) && $row->name) {
                    $descriptions[$oid]['name_access_points'][] = $row->name;
                }
            }
        }

        // Also extract creators from events (type_id = 111 = creation)
        foreach ($descriptions as &$desc) {
            foreach ($desc['dates'] as $date) {
                if ($date['actor'] && !in_array($date['actor'], $desc['creators'])) {
                    $desc['creators'][] = $date['actor'];
                }
            }
        }
    }

    /**
     * Enrich descriptions with digital object references.
     */
    protected function enrichWithDigitalObjects(array &$descriptions, array $ids): void
    {
        $chunks = array_chunk($ids, 500);

        foreach ($chunks as $chunk) {
            $rows = DB::table('digital_object as do2')
                ->whereIn('do2.object_id', $chunk)
                ->select(
                    'do2.id',
                    'do2.object_id',
                    'do2.name',
                    'do2.path',
                    'do2.mime_type',
                    'do2.byte_size',
                    'do2.usage_id'
                )
                ->get();

            foreach ($rows as $row) {
                $oid = (int) $row->object_id;
                if (!isset($descriptions[$oid])) {
                    continue;
                }

                $descriptions[$oid]['digital_objects'][] = [
                    'id' => (int) $row->id,
                    'name' => $row->name,
                    'path' => $row->path,
                    'mime_type' => $row->mime_type,
                    'byte_size' => $row->byte_size ? (int) $row->byte_size : null,
                    'usage_id' => $row->usage_id ? (int) $row->usage_id : null,
                ];
            }
        }
    }

    /**
     * Build a nested hierarchy from flat MPPT-ordered descriptions.
     */
    protected function buildHierarchy(array $descriptions): array
    {
        $tree = [];
        $lookup = [];

        foreach ($descriptions as $desc) {
            $node = [
                'id' => $desc['id'],
                'title' => $desc['title'],
                'slug' => $desc['slug'],
                'identifier' => $desc['identifier'],
                'level' => $desc['level_of_description'],
                'has_digital_objects' => !empty($desc['digital_objects']),
                'children' => [],
            ];
            $lookup[$desc['id']] = $node;
        }

        foreach ($descriptions as $desc) {
            $id = $desc['id'];
            $parentId = $desc['parent_id'];

            if (isset($lookup[$parentId])) {
                $lookup[$parentId]['children'][] = &$lookup[$id];
            } else {
                $tree[] = &$lookup[$id];
            }
        }

        return $tree;
    }

    /**
     * Extract unique taxonomy terms used across descriptions.
     */
    protected function extractTaxonomies(array $descriptions): array
    {
        $subjects = [];
        $places = [];
        $genres = [];

        foreach ($descriptions as $desc) {
            foreach ($desc['subjects'] as $s) {
                $subjects[$s] = ($subjects[$s] ?? 0) + 1;
            }
            foreach ($desc['places'] as $p) {
                $places[$p] = ($places[$p] ?? 0) + 1;
            }
            foreach ($desc['genres'] as $g) {
                $genres[$g] = ($genres[$g] ?? 0) + 1;
            }
        }

        arsort($subjects);
        arsort($places);
        arsort($genres);

        return [
            'subjects' => $subjects,
            'places' => $places,
            'genres' => $genres,
        ];
    }

    /**
     * Extract repository names used in descriptions.
     */
    protected function extractRepositories(array $descriptions): array
    {
        $repoIds = [];
        foreach ($descriptions as $desc) {
            if ($desc['repository_id']) {
                $repoIds[$desc['repository_id']] = true;
            }
        }

        if (empty($repoIds)) {
            return [];
        }

        $repos = DB::table('actor_i18n')
            ->whereIn('id', array_keys($repoIds))
            ->where('culture', '=', $this->culture)
            ->select('id', 'authorized_form_of_name')
            ->get();

        $result = [];
        foreach ($repos as $r) {
            $result[(int) $r->id] = $r->authorized_form_of_name;
        }

        return $result;
    }

    /**
     * Resolve a slug to an information_object row.
     */
    protected function resolveSlug(string $slug): ?object
    {
        $slugRow = DB::table('slug')->where('slug', $slug)->first();
        if (!$slugRow) {
            return null;
        }

        return DB::table('information_object')
            ->where('id', $slugRow->object_id)
            ->first();
    }

    /**
     * Get a term name by ID, with caching.
     */
    protected function getTermName(?int $termId): ?string
    {
        static $cache = [];

        if (!$termId) {
            return null;
        }

        if (!isset($cache[$termId])) {
            $term = DB::table('term_i18n')
                ->where('id', $termId)
                ->where('culture', $this->culture)
                ->first();
            $cache[$termId] = $term ? $term->name : null;
        }

        return $cache[$termId];
    }
}
