<?php

namespace AhgPortableExportPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Extracts all entity types from AtoM into structured JSON for archive export.
 *
 * Produces re-importable JSON files covering descriptions, authorities,
 * taxonomies, rights, accessions, physical objects, events, notes,
 * relations, digital object metadata, and repositories.
 */
class ArchiveExtractor
{
    protected string $culture;
    protected ?callable $progressCallback;

    public function __construct(string $culture = 'en', ?callable $progressCallback = null)
    {
        $this->culture = $culture;
        $this->progressCallback = $progressCallback;
    }

    /**
     * Orchestrate full archive extraction.
     *
     * @param int    $exportId  portable_export.id
     * @param array  $options   Keys: scope_type, scope_slug, scope_repository_id, scope_items, entity_types
     * @return array  Map of entity type => output file path
     */
    public function extract(int $exportId, array $options, string $outputDir): array
    {
        @mkdir($outputDir . '/data', 0755, true);

        $scopeType = $options['scope_type'] ?? 'all';
        $scopeSlug = $options['scope_slug'] ?? null;
        $repositoryId = $options['scope_repository_id'] ?? null;
        $scopeItems = $options['scope_items'] ?? null;
        $entityTypes = $options['entity_types'] ?? [
            'descriptions', 'authorities', 'taxonomies', 'rights',
            'accessions', 'physical_objects', 'events', 'notes',
            'relations', 'digital_objects', 'repositories',
        ];

        $files = [];
        $totalTypes = count($entityTypes);
        $processed = 0;

        // Resolve scope for scoped entity types
        $scopeIds = $this->resolveScopeIds($scopeType, $scopeSlug, $repositoryId, $scopeItems);

        foreach ($entityTypes as $type) {
            $data = [];

            switch ($type) {
                case 'descriptions':
                    $data = $this->extractDescriptions($scopeType, $scopeIds, $repositoryId);
                    break;
                case 'authorities':
                    $data = $this->extractAuthorities($scopeIds);
                    break;
                case 'taxonomies':
                    $data = $this->extractTaxonomies();
                    break;
                case 'rights':
                    $data = $this->extractRights($scopeIds);
                    break;
                case 'accessions':
                    $data = $this->extractAccessions($scopeIds, $repositoryId);
                    break;
                case 'physical_objects':
                    $data = $this->extractPhysicalObjects($scopeIds);
                    break;
                case 'events':
                    $data = $this->extractEvents($scopeIds);
                    break;
                case 'notes':
                    $data = $this->extractNotes($scopeIds);
                    break;
                case 'relations':
                    $data = $this->extractRelations($scopeIds);
                    break;
                case 'digital_objects':
                    $data = $this->extractDigitalObjects($scopeIds);
                    break;
                case 'repositories':
                    $data = $this->extractRepositories($repositoryId);
                    break;
            }

            $filePath = $outputDir . '/data/' . $type . '.json';
            file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $files[$type] = ['path' => 'data/' . $type . '.json', 'count' => count($data)];

            $processed++;
            if ($this->progressCallback) {
                ($this->progressCallback)($processed, $totalTypes);
            }
        }

        return $files;
    }

    /**
     * Resolve scope to a set of information_object IDs (or null for all).
     */
    protected function resolveScopeIds(string $scopeType, ?string $scopeSlug, ?int $repositoryId, ?array $scopeItems): ?array
    {
        if ($scopeType === 'all' && !$repositoryId) {
            return null; // No filtering
        }

        $query = DB::table('information_object as io')
            ->where('io.id', '!=', 1);

        switch ($scopeType) {
            case 'fonds':
                if ($scopeSlug) {
                    $slugRow = DB::table('slug')->where('slug', $scopeSlug)->first();
                    if ($slugRow) {
                        $root = DB::table('information_object')->where('id', $slugRow->object_id)->first();
                        if ($root) {
                            $query->where('io.lft', '>=', $root->lft)
                                ->where('io.rgt', '<=', $root->rgt);
                        }
                    }
                }
                break;

            case 'repository':
                if ($repositoryId) {
                    $query->where('io.repository_id', '=', $repositoryId);
                }
                break;

            case 'custom':
                if (!empty($scopeItems)) {
                    $ranges = DB::table('information_object')
                        ->whereIn('id', $scopeItems)
                        ->select('lft', 'rgt')
                        ->get();

                    if ($ranges->isNotEmpty()) {
                        $query->where(function ($q) use ($ranges) {
                            foreach ($ranges as $range) {
                                $q->orWhere(function ($q2) use ($range) {
                                    $q2->where('io.lft', '>=', $range->lft)
                                        ->where('io.rgt', '<=', $range->rgt);
                                });
                            }
                        });
                    }
                }
                break;

            default:
                if ($repositoryId) {
                    $query->where('io.repository_id', '=', $repositoryId);
                }
                break;
        }

        return $query->pluck('io.id')->toArray();
    }

    // ─── Descriptions ───────────────────────────────────────────────

    protected function extractDescriptions(string $scopeType, ?array $scopeIds, ?int $repositoryId): array
    {
        $query = DB::table('information_object as io')
            ->join('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $this->culture);
            })
            ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
            ->leftJoin('object as o', 'io.id', '=', 'o.id')
            ->where('io.id', '!=', 1);

        if ($scopeIds !== null) {
            $this->applyIdFilter($query, 'io.id', $scopeIds);
        }

        $rows = $query->select(
            'io.id', 'io.identifier', 'io.parent_id', 'io.lft', 'io.rgt',
            'io.level_of_description_id', 'io.repository_id', 'io.source_culture',
            'io.description_status_id', 'io.level_of_detail_id',
            'ioi.title', 'ioi.scope_and_content', 'ioi.extent_and_medium',
            'ioi.archival_history', 'ioi.acquisition', 'ioi.arrangement',
            'ioi.access_conditions', 'ioi.reproduction_conditions',
            'ioi.physical_characteristics', 'ioi.finding_aids',
            'ioi.location_of_originals', 'ioi.location_of_copies',
            'ioi.related_units_of_description', 'ioi.rules',
            'ioi.revision_history', 'ioi.sources',
            's.slug', 'o.created_at', 'o.updated_at'
        )->orderBy('io.lft')->get();

        $result = [];
        foreach ($rows as $row) {
            $result[] = (array) $row;
        }

        // Also get all i18n cultures for each description
        if (!empty($result)) {
            $ids = array_column($result, 'id');
            $allI18n = $this->getI18nRecords('information_object_i18n', $ids);
            foreach ($result as &$desc) {
                $desc['i18n'] = $allI18n[$desc['id']] ?? [];
            }

            // Get properties
            $properties = $this->getProperties($ids);
            foreach ($result as &$desc) {
                $desc['properties'] = $properties[$desc['id']] ?? [];
            }
        }

        return $result;
    }

    // ─── Authorities ────────────────────────────────────────────────

    protected function extractAuthorities(?array $scopeIds): array
    {
        $query = DB::table('actor as a')
            ->join('actor_i18n as ai', function ($join) {
                $join->on('a.id', '=', 'ai.id')
                    ->where('ai.culture', '=', $this->culture);
            })
            ->leftJoin('object as o', 'a.id', '=', 'o.id')
            ->leftJoin('slug as s', 'a.id', '=', 's.object_id')
            ->where('a.id', '!=', \QubitActor::ROOT_ID ?? 3);

        // If scoped, only include actors related to the scoped IOs
        if ($scopeIds !== null) {
            $actorIds = $this->getRelatedActorIds($scopeIds);
            if (empty($actorIds)) {
                return [];
            }
            $this->applyIdFilter($query, 'a.id', $actorIds);
        }

        $rows = $query->select(
            'a.id', 'a.entity_type_id', 'a.description_identifier',
            'a.corporate_body_identifiers', 'a.parent_id',
            'ai.authorized_form_of_name', 'ai.dates_of_existence',
            'ai.history', 'ai.places', 'ai.legal_status', 'ai.functions',
            'ai.mandates', 'ai.internal_structures', 'ai.general_context',
            's.slug', 'o.created_at', 'o.updated_at'
        )->orderBy('ai.authorized_form_of_name')->get();

        $result = [];
        foreach ($rows as $row) {
            $result[] = (array) $row;
        }

        if (!empty($result)) {
            $ids = array_column($result, 'id');
            $allI18n = $this->getI18nRecords('actor_i18n', $ids);
            foreach ($result as &$actor) {
                $actor['i18n'] = $allI18n[$actor['id']] ?? [];
            }
        }

        return $result;
    }

    // ─── Taxonomies ─────────────────────────────────────────────────

    protected function extractTaxonomies(): array
    {
        $taxonomies = DB::table('taxonomy as t')
            ->join('taxonomy_i18n as ti', function ($join) {
                $join->on('t.id', '=', 'ti.id')
                    ->where('ti.culture', '=', $this->culture);
            })
            ->select('t.id', 't.usage', 'ti.name', 'ti.note')
            ->orderBy('ti.name')
            ->get();

        $result = [];
        foreach ($taxonomies as $tax) {
            $terms = DB::table('term as t')
                ->join('term_i18n as ti', function ($join) {
                    $join->on('t.id', '=', 'ti.id')
                        ->where('ti.culture', '=', $this->culture);
                })
                ->where('t.taxonomy_id', $tax->id)
                ->select('t.id', 't.parent_id', 't.taxonomy_id', 'ti.name')
                ->orderBy('t.lft')
                ->get();

            $termArray = [];
            foreach ($terms as $term) {
                $termArray[] = (array) $term;
            }

            $result[] = [
                'id' => (int) $tax->id,
                'name' => $tax->name,
                'usage' => $tax->usage,
                'note' => $tax->note,
                'terms' => $termArray,
            ];
        }

        return $result;
    }

    // ─── Rights ─────────────────────────────────────────────────────

    protected function extractRights(?array $scopeIds): array
    {
        $query = DB::table('rights as r')
            ->leftJoin('rights_i18n as ri', function ($join) {
                $join->on('r.id', '=', 'ri.id')
                    ->where('ri.culture', '=', $this->culture);
            })
            ->leftJoin('object as o', 'r.id', '=', 'o.id');

        if ($scopeIds !== null) {
            $this->applyIdFilter($query, 'r.object_id', $scopeIds);
        }

        $rows = $query->select(
            'r.id', 'r.object_id', 'r.basis_id', 'r.act_id',
            'r.start_date', 'r.end_date', 'r.rights_holder_id',
            'r.copyright_status_id',
            'ri.rights_note', 'ri.copyright_note', 'ri.license_identifier',
            'ri.license_terms', 'ri.license_note', 'ri.statute_jurisdiction',
            'ri.statute_citation', 'ri.statute_determination_date',
            'ri.statute_note',
            'o.created_at', 'o.updated_at'
        )->get();

        $result = [];
        foreach ($rows as $row) {
            $result[] = (array) $row;
        }

        return $result;
    }

    // ─── Accessions ─────────────────────────────────────────────────

    protected function extractAccessions(?array $scopeIds, ?int $repositoryId): array
    {
        $query = DB::table('accession as a')
            ->join('accession_i18n as ai', function ($join) {
                $join->on('a.id', '=', 'ai.id')
                    ->where('ai.culture', '=', $this->culture);
            })
            ->leftJoin('object as o', 'a.id', '=', 'o.id');

        if ($repositoryId) {
            // Accessions may not have repository_id directly; filter by linked deaccessions if needed
        }

        $rows = $query->select(
            'a.id', 'a.identifier', 'a.date', 'a.source_of_acquisition_id',
            'a.location_information',
            'ai.appraisal', 'ai.archival_history', 'ai.scope_and_content',
            'ai.physical_characteristics', 'ai.received_extent_units',
            'ai.processing_notes', 'ai.title',
            'o.created_at', 'o.updated_at'
        )->get();

        $result = [];
        foreach ($rows as $row) {
            $rec = (array) $row;

            // Get accession events
            $events = DB::table('accession_event')
                ->where('accession_id', $row->id)
                ->get();
            $rec['events'] = [];
            foreach ($events as $evt) {
                $rec['events'][] = (array) $evt;
            }

            // Get donor links
            $donors = DB::table('relation as r')
                ->join('actor_i18n as ai2', function ($join) {
                    $join->on('r.object_id', '=', 'ai2.id')
                        ->where('ai2.culture', '=', $this->culture);
                })
                ->where('r.subject_id', $row->id)
                ->where('r.type_id', \QubitTerm::DONOR_ID ?? 154)
                ->select('r.object_id as donor_id', 'ai2.authorized_form_of_name as donor_name')
                ->get();
            $rec['donors'] = [];
            foreach ($donors as $d) {
                $rec['donors'][] = (array) $d;
            }

            $result[] = $rec;
        }

        return $result;
    }

    // ─── Physical Objects ───────────────────────────────────────────

    protected function extractPhysicalObjects(?array $scopeIds): array
    {
        $query = DB::table('physical_object as po')
            ->join('physical_object_i18n as poi', function ($join) {
                $join->on('po.id', '=', 'poi.id')
                    ->where('poi.culture', '=', $this->culture);
            })
            ->leftJoin('object as o', 'po.id', '=', 'o.id');

        if ($scopeIds !== null) {
            // Only include physical objects linked to scoped IOs
            $poIds = DB::table('relation')
                ->whereIn('subject_id', $scopeIds)
                ->where('type_id', \QubitTerm::HAS_PHYSICAL_OBJECT_ID ?? 161)
                ->pluck('object_id')
                ->toArray();

            if (empty($poIds)) {
                return [];
            }
            $this->applyIdFilter($query, 'po.id', $poIds);
        }

        $rows = $query->select(
            'po.id', 'po.type_id',
            'poi.name', 'poi.description', 'poi.location',
            'o.created_at', 'o.updated_at'
        )->get();

        $result = [];
        foreach ($rows as $row) {
            $result[] = (array) $row;
        }

        return $result;
    }

    // ─── Events ─────────────────────────────────────────────────────

    protected function extractEvents(?array $scopeIds): array
    {
        $query = DB::table('event as e')
            ->leftJoin('event_i18n as ei', function ($join) {
                $join->on('e.id', '=', 'ei.id')
                    ->where('ei.culture', '=', $this->culture);
            })
            ->leftJoin('object as o', 'e.id', '=', 'o.id');

        if ($scopeIds !== null) {
            $this->applyIdFilter($query, 'e.object_id', $scopeIds);
        }

        $rows = $query->select(
            'e.id', 'e.object_id', 'e.actor_id', 'e.type_id',
            'e.start_date', 'e.end_date',
            'ei.date', 'ei.description', 'ei.name',
            'o.created_at', 'o.updated_at'
        )->get();

        $result = [];
        foreach ($rows as $row) {
            $result[] = (array) $row;
        }

        return $result;
    }

    // ─── Notes ──────────────────────────────────────────────────────

    protected function extractNotes(?array $scopeIds): array
    {
        $query = DB::table('note as n')
            ->join('note_i18n as ni', function ($join) {
                $join->on('n.id', '=', 'ni.id')
                    ->where('ni.culture', '=', $this->culture);
            })
            ->leftJoin('object as o', 'n.id', '=', 'o.id');

        if ($scopeIds !== null) {
            $this->applyIdFilter($query, 'n.object_id', $scopeIds);
        }

        $rows = $query->select(
            'n.id', 'n.object_id', 'n.type_id', 'n.scope',
            'n.user_id',
            'ni.content',
            'o.created_at', 'o.updated_at'
        )->get();

        $result = [];
        foreach ($rows as $row) {
            $result[] = (array) $row;
        }

        return $result;
    }

    // ─── Relations ──────────────────────────────────────────────────

    protected function extractRelations(?array $scopeIds): array
    {
        $query = DB::table('relation as r')
            ->leftJoin('relation_i18n as ri', function ($join) {
                $join->on('r.id', '=', 'ri.id')
                    ->where('ri.culture', '=', $this->culture);
            })
            ->leftJoin('object as o', 'r.id', '=', 'o.id');

        if ($scopeIds !== null) {
            $query->where(function ($q) use ($scopeIds) {
                $chunks = array_chunk($scopeIds, 500);
                foreach ($chunks as $chunk) {
                    $q->orWhereIn('r.subject_id', $chunk)
                      ->orWhereIn('r.object_id', $chunk);
                }
            });
        }

        $rows = $query->select(
            'r.id', 'r.subject_id', 'r.object_id', 'r.type_id',
            'r.start_date', 'r.end_date',
            'ri.description', 'ri.date',
            'o.created_at', 'o.updated_at'
        )->get();

        $result = [];
        foreach ($rows as $row) {
            $result[] = (array) $row;
        }

        return $result;
    }

    // ─── Digital Objects ────────────────────────────────────────────

    protected function extractDigitalObjects(?array $scopeIds): array
    {
        $query = DB::table('digital_object as d')
            ->leftJoin('object as o', 'd.id', '=', 'o.id');

        if ($scopeIds !== null) {
            $this->applyIdFilter($query, 'd.object_id', $scopeIds);
        }

        $rows = $query->select(
            'd.id', 'd.object_id', 'd.usage_id', 'd.name', 'd.path',
            'd.mime_type', 'd.byte_size', 'd.checksum', 'd.checksum_type',
            'd.parent_id', 'd.sequence',
            'o.created_at', 'o.updated_at'
        )->get();

        $result = [];
        foreach ($rows as $row) {
            $result[] = (array) $row;
        }

        return $result;
    }

    // ─── Repositories ───────────────────────────────────────────────

    protected function extractRepositories(?int $repositoryId): array
    {
        $query = DB::table('repository as r')
            ->join('actor_i18n as ai', function ($join) {
                $join->on('r.id', '=', 'ai.id')
                    ->where('ai.culture', '=', $this->culture);
            })
            ->leftJoin('repository_i18n as ri', function ($join) {
                $join->on('r.id', '=', 'ri.id')
                    ->where('ri.culture', '=', $this->culture);
            })
            ->leftJoin('object as o', 'r.id', '=', 'o.id')
            ->leftJoin('slug as s', 'r.id', '=', 's.object_id')
            ->where('r.id', '!=', \QubitRepository::ROOT_ID ?? 6);

        if ($repositoryId) {
            $query->where('r.id', $repositoryId);
        }

        $rows = $query->select(
            'r.id', 'r.identifier', 'r.desc_status_id', 'r.desc_detail_id',
            'ai.authorized_form_of_name', 'ai.history', 'ai.places',
            'ai.dates_of_existence',
            'ri.geocultural_context', 'ri.collecting_policies', 'ri.buildings',
            'ri.holdings', 'ri.finding_aids', 'ri.opening_times',
            'ri.access_conditions', 'ri.disabled_access',
            'ri.research_services', 'ri.reproduction_services',
            'ri.public_facilities',
            's.slug', 'o.created_at', 'o.updated_at'
        )->orderBy('ai.authorized_form_of_name')->get();

        $result = [];
        foreach ($rows as $row) {
            $rec = (array) $row;

            // Get contact information
            $contacts = DB::table('contact_information')
                ->where('actor_id', $row->id)
                ->get();
            $rec['contacts'] = [];
            foreach ($contacts as $c) {
                $rec['contacts'][] = (array) $c;
            }

            $result[] = $rec;
        }

        return $result;
    }

    // ─── Helpers ────────────────────────────────────────────────────

    /**
     * Apply an ID filter with chunking to avoid query-size limits.
     */
    protected function applyIdFilter($query, string $column, array $ids): void
    {
        if (count($ids) <= 500) {
            $query->whereIn($column, $ids);
        } else {
            $chunks = array_chunk($ids, 500);
            $query->where(function ($q) use ($column, $chunks) {
                foreach ($chunks as $chunk) {
                    $q->orWhereIn($column, $chunk);
                }
            });
        }
    }

    /**
     * Get all i18n records for a set of entity IDs.
     */
    protected function getI18nRecords(string $table, array $ids): array
    {
        $grouped = [];
        $chunks = array_chunk($ids, 500);

        foreach ($chunks as $chunk) {
            $rows = DB::table($table)->whereIn('id', $chunk)->get();
            foreach ($rows as $row) {
                $grouped[(int) $row->id][] = (array) $row;
            }
        }

        return $grouped;
    }

    /**
     * Get object properties for a set of IDs.
     */
    protected function getProperties(array $ids): array
    {
        $grouped = [];
        $chunks = array_chunk($ids, 500);

        foreach ($chunks as $chunk) {
            $rows = DB::table('property as p')
                ->leftJoin('property_i18n as pi', function ($join) {
                    $join->on('p.id', '=', 'pi.id')
                        ->where('pi.culture', '=', $this->culture);
                })
                ->whereIn('p.object_id', $chunk)
                ->select('p.id', 'p.object_id', 'p.name', 'p.scope', 'pi.value')
                ->get();

            foreach ($rows as $row) {
                $grouped[(int) $row->object_id][] = (array) $row;
            }
        }

        return $grouped;
    }

    /**
     * Get actor IDs related to a set of information object IDs.
     */
    protected function getRelatedActorIds(array $scopeIds): array
    {
        $actorIds = [];
        $chunks = array_chunk($scopeIds, 500);

        foreach ($chunks as $chunk) {
            // From events
            $eventActors = DB::table('event')
                ->whereIn('object_id', $chunk)
                ->whereNotNull('actor_id')
                ->pluck('actor_id')
                ->toArray();
            $actorIds = array_merge($actorIds, $eventActors);

            // From relations (name access points)
            $relationActors = DB::table('relation')
                ->whereIn('subject_id', $chunk)
                ->whereNotNull('object_id')
                ->pluck('object_id')
                ->toArray();
            $actorIds = array_merge($actorIds, $relationActors);
        }

        return array_unique($actorIds);
    }
}
