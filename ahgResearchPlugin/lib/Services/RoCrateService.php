<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * RoCrateService - RO-Crate Research Data Packaging
 *
 * Generates RO-Crate (Research Object Crate) compliant manifests
 * and Schema.org Dataset JSON-LD for research projects. RO-Crate is
 * a community standard for packaging research data with rich metadata.
 *
 * @see https://www.researchobject.org/ro-crate/
 * @package ahgResearchPlugin
 * @version 2.1.0
 */
class RoCrateService
{
    /**
     * Base upload path for RO-Crate packages.
     */
    private string $basePath;

    /**
     * Constructor.
     *
     * @param string $basePath Upload base path (defaults to AtoM uploads dir)
     */
    public function __construct(string $basePath = '/usr/share/nginx/archive/uploads')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    // =========================================================================
    // PACKAGE GENERATION
    // =========================================================================

    /**
     * Generate an RO-Crate package for a project.
     *
     * Builds a compliant ro-crate-metadata.json containing:
     * - Root dataset descriptor
     * - Project metadata entity
     * - Collection items as data entities
     * - Assertions as contextual entities
     * - Snapshots with integrity hashes
     * - Annotations as contextual entities
     *
     * @param int $projectId The research project ID
     * @return string File path to the generated ro-crate-metadata.json
     */
    public function packageProject(int $projectId): string
    {
        $project = DB::table('research_project as p')
            ->leftJoin('research_researcher as r', 'p.owner_id', '=', 'r.id')
            ->where('p.id', $projectId)
            ->select(
                'p.*',
                'r.first_name as owner_first_name',
                'r.last_name as owner_last_name',
                'r.email as owner_email',
                'r.orcid_id as owner_orcid'
            )
            ->first();

        if (!$project) {
            throw new \RuntimeException('Project not found: ' . $projectId);
        }

        // Build the @graph array
        $graph = [];

        // 1. Root dataset (ro-crate-metadata.json descriptor)
        $graph[] = [
            '@id' => 'ro-crate-metadata.json',
            '@type' => 'CreativeWork',
            'about' => ['@id' => './'],
            'conformsTo' => ['@id' => 'https://w3id.org/ro/crate/1.1'],
        ];

        // 2. Root dataset entity
        $rootDataset = [
            '@id' => './',
            '@type' => 'Dataset',
            'name' => $project->title,
            'description' => $project->description ?? '',
            'dateCreated' => $project->created_at,
            'dateModified' => $project->updated_at,
            'license' => 'https://creativecommons.org/licenses/by/4.0/',
        ];

        // Add creator
        $creatorId = '#researcher-' . $project->owner_id;
        $rootDataset['author'] = ['@id' => $creatorId];

        $graph[] = [
            '@id' => $creatorId,
            '@type' => 'Person',
            'name' => trim(($project->owner_first_name ?? '') . ' ' . ($project->owner_last_name ?? '')),
            'email' => $project->owner_email ?? null,
            'identifier' => $project->owner_orcid ? 'https://orcid.org/' . $project->owner_orcid : null,
        ];

        // 3. Collections and their items
        $collections = DB::table('research_collection')
            ->where('project_id', $projectId)
            ->orderBy('id')
            ->get()
            ->toArray();

        $hasPart = [];

        foreach ($collections as $collection) {
            $collectionId = '#collection-' . $collection->id;
            $collectionEntity = [
                '@id' => $collectionId,
                '@type' => 'Dataset',
                'name' => $collection->name,
                'description' => $collection->description ?? '',
                'dateCreated' => $collection->created_at,
            ];

            // Get items for this collection
            $items = DB::table('research_collection_item as ci')
                ->leftJoin('information_object_i18n as i18n', function ($join) {
                    $join->on('ci.object_id', '=', 'i18n.id')
                        ->where('i18n.culture', '=', 'en');
                })
                ->leftJoin('slug', 'ci.object_id', '=', 'slug.object_id')
                ->where('ci.collection_id', $collection->id)
                ->select(
                    'ci.*',
                    'i18n.title as object_title',
                    'slug.slug as object_slug'
                )
                ->orderBy('ci.sort_order')
                ->get()
                ->toArray();

            $itemRefs = [];
            foreach ($items as $item) {
                $itemId = '#item-' . $item->object_id;
                $itemRefs[] = ['@id' => $itemId];

                $graph[] = [
                    '@id' => $itemId,
                    '@type' => 'DigitalDocument',
                    'name' => $item->object_title ?? ('Object #' . $item->object_id),
                    'identifier' => $item->object_slug ?? null,
                    'additionalType' => $item->object_type ?? 'information_object',
                ];
            }

            $collectionEntity['hasPart'] = $itemRefs;
            $graph[] = $collectionEntity;
            $hasPart[] = ['@id' => $collectionId];
        }

        // 4. Assertions as contextual entities
        $assertions = DB::table('research_assertion as a')
            ->leftJoin('research_researcher as r', 'a.researcher_id', '=', 'r.id')
            ->where('a.project_id', $projectId)
            ->where('a.status', '!=', 'retracted')
            ->select(
                'a.*',
                'r.first_name as researcher_first_name',
                'r.last_name as researcher_last_name'
            )
            ->orderBy('a.id')
            ->get()
            ->toArray();

        foreach ($assertions as $assertion) {
            $assertionId = '#assertion-' . $assertion->id;
            $graph[] = [
                '@id' => $assertionId,
                '@type' => 'Claim',
                'name' => ($assertion->subject_label ?? '') . ' ' . $assertion->predicate . ' ' . ($assertion->object_label ?? $assertion->object_value ?? ''),
                'about' => ['@id' => '#item-' . $assertion->subject_id],
                'creator' => trim(($assertion->researcher_first_name ?? '') . ' ' . ($assertion->researcher_last_name ?? '')),
                'additionalType' => $assertion->assertion_type,
                'confidence' => $assertion->confidence !== null ? (float) $assertion->confidence : null,
                'status' => $assertion->status,
                'dateCreated' => $assertion->created_at,
            ];
            $hasPart[] = ['@id' => $assertionId];
        }

        // 5. Snapshots with integrity hashes
        $snapshots = DB::table('research_snapshot')
            ->where('project_id', $projectId)
            ->orderBy('id')
            ->get()
            ->toArray();

        foreach ($snapshots as $snapshot) {
            $snapshotId = '#snapshot-' . $snapshot->id;
            $graph[] = [
                '@id' => $snapshotId,
                '@type' => 'Dataset',
                'name' => $snapshot->title,
                'description' => $snapshot->description ?? '',
                'dateCreated' => $snapshot->created_at,
                'additionalType' => 'ResearchSnapshot',
                'sha256' => $snapshot->hash_sha256 ?? null,
                'numberOfItems' => (int) $snapshot->item_count,
            ];
            $hasPart[] = ['@id' => $snapshotId];
        }

        // 6. Annotations as contextual entities
        $annotations = DB::table('research_annotation')
            ->where('project_id', $projectId)
            ->orderBy('id')
            ->get()
            ->toArray();

        foreach ($annotations as $annotation) {
            $annotationId = '#annotation-' . $annotation->id;
            $graph[] = [
                '@id' => $annotationId,
                '@type' => 'Comment',
                'name' => $annotation->title ?? ('Annotation #' . $annotation->id),
                'text' => $annotation->content ?? '',
                'about' => $annotation->object_id ? ['@id' => '#item-' . $annotation->object_id] : null,
                'additionalType' => $annotation->annotation_type ?? 'note',
                'dateCreated' => $annotation->created_at,
            ];
            $hasPart[] = ['@id' => $annotationId];
        }

        $rootDataset['hasPart'] = $hasPart;
        // Insert root dataset as second element (after the descriptor)
        array_splice($graph, 1, 0, [$rootDataset]);

        // Build the complete RO-Crate metadata document
        $roCrate = [
            '@context' => 'https://w3id.org/ro/crate/1.1/context',
            '@graph' => $graph,
        ];

        // Write to disk
        $outputDir = $this->basePath . '/research/ro-crate/' . $projectId;
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $filePath = $outputDir . '/ro-crate-metadata.json';
        file_put_contents($filePath, json_encode($roCrate, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $filePath;
    }

    /**
     * Generate an RO-Crate package scoped to a single collection.
     *
     * @param int $collectionId The research collection ID
     * @return string File path to the generated ro-crate-metadata.json
     */
    public function packageCollection(int $collectionId): string
    {
        $collection = DB::table('research_collection as c')
            ->leftJoin('research_researcher as r', 'c.researcher_id', '=', 'r.id')
            ->where('c.id', $collectionId)
            ->select(
                'c.*',
                'r.first_name as researcher_first_name',
                'r.last_name as researcher_last_name',
                'r.email as researcher_email',
                'r.orcid_id as researcher_orcid'
            )
            ->first();

        if (!$collection) {
            throw new \RuntimeException('Collection not found: ' . $collectionId);
        }

        $graph = [];

        // 1. RO-Crate descriptor
        $graph[] = [
            '@id' => 'ro-crate-metadata.json',
            '@type' => 'CreativeWork',
            'about' => ['@id' => './'],
            'conformsTo' => ['@id' => 'https://w3id.org/ro/crate/1.1'],
        ];

        // 2. Creator entity
        $creatorId = '#researcher-' . $collection->researcher_id;
        $graph[] = [
            '@id' => $creatorId,
            '@type' => 'Person',
            'name' => trim(($collection->researcher_first_name ?? '') . ' ' . ($collection->researcher_last_name ?? '')),
            'email' => $collection->researcher_email ?? null,
            'identifier' => $collection->researcher_orcid ? 'https://orcid.org/' . $collection->researcher_orcid : null,
        ];

        // 3. Collection items
        $items = DB::table('research_collection_item as ci')
            ->leftJoin('information_object_i18n as i18n', function ($join) {
                $join->on('ci.object_id', '=', 'i18n.id')
                    ->where('i18n.culture', '=', 'en');
            })
            ->leftJoin('slug', 'ci.object_id', '=', 'slug.object_id')
            ->where('ci.collection_id', $collectionId)
            ->select(
                'ci.*',
                'i18n.title as object_title',
                'slug.slug as object_slug'
            )
            ->orderBy('ci.sort_order')
            ->get()
            ->toArray();

        $hasPart = [];

        foreach ($items as $item) {
            $itemId = '#item-' . $item->object_id;
            $hasPart[] = ['@id' => $itemId];

            $graph[] = [
                '@id' => $itemId,
                '@type' => 'DigitalDocument',
                'name' => $item->object_title ?? ('Object #' . $item->object_id),
                'identifier' => $item->object_slug ?? null,
                'additionalType' => $item->object_type ?? 'information_object',
            ];
        }

        // 4. Root dataset
        $rootDataset = [
            '@id' => './',
            '@type' => 'Dataset',
            'name' => $collection->name,
            'description' => $collection->description ?? '',
            'dateCreated' => $collection->created_at,
            'dateModified' => $collection->updated_at ?? $collection->created_at,
            'author' => ['@id' => $creatorId],
            'hasPart' => $hasPart,
        ];

        // Insert root dataset after descriptor
        array_splice($graph, 1, 0, [$rootDataset]);

        $roCrate = [
            '@context' => 'https://w3id.org/ro/crate/1.1/context',
            '@graph' => $graph,
        ];

        // Write to disk
        $projectId = $collection->project_id ?? 0;
        $outputDir = $this->basePath . '/research/ro-crate/collection-' . $collectionId;
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $filePath = $outputDir . '/ro-crate-metadata.json';
        file_put_contents($filePath, json_encode($roCrate, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $filePath;
    }

    // =========================================================================
    // MANIFEST GENERATION
    // =========================================================================

    /**
     * Build a comprehensive manifest array for a project.
     *
     * Includes all collections, items, snapshots with hashes,
     * assertions, and a dataset-level SHA-256 integrity hash.
     *
     * @param int $projectId The research project ID
     * @return array Manifest with datasets, hashes, query_ids, transformations
     */
    public function generateManifest(int $projectId): array
    {
        $project = DB::table('research_project')
            ->where('id', $projectId)
            ->first();

        if (!$project) {
            throw new \RuntimeException('Project not found: ' . $projectId);
        }

        // Get all collections with item counts
        $collections = DB::table('research_collection as c')
            ->where('c.project_id', $projectId)
            ->select('c.*')
            ->orderBy('c.id')
            ->get()
            ->toArray();

        $datasets = [];

        foreach ($collections as $collection) {
            $items = DB::table('research_collection_item as ci')
                ->leftJoin('information_object_i18n as i18n', function ($join) {
                    $join->on('ci.object_id', '=', 'i18n.id')
                        ->where('i18n.culture', '=', 'en');
                })
                ->where('ci.collection_id', $collection->id)
                ->select('ci.object_id', 'ci.object_type', 'i18n.title as object_title')
                ->orderBy('ci.sort_order')
                ->get()
                ->toArray();

            $datasets[] = [
                'collection_id' => (int) $collection->id,
                'name' => $collection->name,
                'item_count' => count($items),
                'items' => array_map(function ($item) {
                    return [
                        'object_id' => (int) $item->object_id,
                        'object_type' => $item->object_type ?? 'information_object',
                        'title' => $item->object_title ?? null,
                    ];
                }, $items),
            ];
        }

        // Get all snapshots with hashes
        $snapshots = DB::table('research_snapshot')
            ->where('project_id', $projectId)
            ->orderBy('created_at')
            ->select('id', 'title', 'hash_sha256', 'item_count', 'status', 'created_at')
            ->get()
            ->toArray();

        $hashes = [];
        foreach ($snapshots as $snapshot) {
            $hashes[] = [
                'snapshot_id' => (int) $snapshot->id,
                'title' => $snapshot->title,
                'hash_sha256' => $snapshot->hash_sha256,
                'item_count' => (int) $snapshot->item_count,
                'status' => $snapshot->status,
                'created_at' => $snapshot->created_at,
            ];
        }

        // Get all saved searches (queries) linked to project
        $savedSearches = DB::table('research_saved_search')
            ->where('project_id', $projectId)
            ->orderBy('id')
            ->select('id', 'name', 'search_query', 'search_filters', 'search_type', 'total_results_at_save')
            ->get()
            ->toArray();

        $queryIds = [];
        foreach ($savedSearches as $search) {
            $queryIds[] = [
                'search_id' => (int) $search->id,
                'name' => $search->name,
                'query' => $search->search_query,
                'filters' => $search->search_filters ? json_decode($search->search_filters, true) : null,
                'type' => $search->search_type,
                'results_at_save' => $search->total_results_at_save !== null ? (int) $search->total_results_at_save : null,
            ];
        }

        // Get all assertions for provenance/transformation tracking
        $assertions = DB::table('research_assertion')
            ->where('project_id', $projectId)
            ->where('status', '!=', 'retracted')
            ->orderBy('id')
            ->get()
            ->toArray();

        $transformations = [];
        foreach ($assertions as $assertion) {
            $transformations[] = [
                'assertion_id' => (int) $assertion->id,
                'type' => $assertion->assertion_type,
                'subject' => $assertion->subject_label ?? ($assertion->subject_type . ':' . $assertion->subject_id),
                'predicate' => $assertion->predicate,
                'object' => $assertion->object_label ?? $assertion->object_value ?? ($assertion->object_type . ':' . $assertion->object_id),
                'confidence' => $assertion->confidence !== null ? (float) $assertion->confidence : null,
                'status' => $assertion->status,
            ];
        }

        // Compute dataset-level SHA-256 from all manifest content
        $manifestPayload = json_encode([
            'project_id' => $projectId,
            'datasets' => $datasets,
            'hashes' => $hashes,
            'query_ids' => $queryIds,
            'transformations' => $transformations,
        ], JSON_UNESCAPED_SLASHES);

        $datasetHash = hash('sha256', $manifestPayload);

        return [
            'project_id' => (int) $projectId,
            'project_title' => $project->title,
            'generated_at' => date('Y-m-d H:i:s'),
            'dataset_hash' => $datasetHash,
            'datasets' => $datasets,
            'hashes' => $hashes,
            'query_ids' => $queryIds,
            'transformations' => $transformations,
        ];
    }

    // =========================================================================
    // SCHEMA.ORG DATASET EXPORT
    // =========================================================================

    /**
     * Generate a Schema.org Dataset JSON-LD representation.
     *
     * Produces a standards-compliant JSON-LD document suitable for
     * dataset discovery services (Google Dataset Search, DataCite, etc.).
     *
     * @param int $projectId The research project ID
     * @return array Schema.org Dataset JSON-LD structure
     */
    public function generateSchemaOrgDataset(int $projectId): array
    {
        $project = DB::table('research_project as p')
            ->leftJoin('research_researcher as r', 'p.owner_id', '=', 'r.id')
            ->where('p.id', $projectId)
            ->select(
                'p.*',
                'r.first_name as owner_first_name',
                'r.last_name as owner_last_name',
                'r.email as owner_email',
                'r.orcid_id as owner_orcid',
                'r.institution as owner_institution'
            )
            ->first();

        if (!$project) {
            throw new \RuntimeException('Project not found: ' . $projectId);
        }

        // Build creator
        $creator = [
            '@type' => 'Person',
            'name' => trim(($project->owner_first_name ?? '') . ' ' . ($project->owner_last_name ?? '')),
        ];

        if (!empty($project->owner_email)) {
            $creator['email'] = $project->owner_email;
        }

        if (!empty($project->owner_orcid)) {
            $creator['identifier'] = [
                '@type' => 'PropertyValue',
                'propertyID' => 'ORCID',
                'value' => 'https://orcid.org/' . $project->owner_orcid,
            ];
        }

        if (!empty($project->owner_institution)) {
            $creator['affiliation'] = [
                '@type' => 'Organization',
                'name' => $project->owner_institution,
            ];
        }

        // Build distribution (access URLs)
        $distribution = [];

        $collections = DB::table('research_collection')
            ->where('project_id', $projectId)
            ->get()
            ->toArray();

        foreach ($collections as $collection) {
            $distribution[] = [
                '@type' => 'DataDownload',
                'name' => $collection->name,
                'contentUrl' => '/research/collection/' . $collection->id,
                'encodingFormat' => 'application/json',
            ];
        }

        // Count assertion types and entities for variableMeasured
        $assertionTypeCounts = DB::table('research_assertion')
            ->where('project_id', $projectId)
            ->where('status', '!=', 'retracted')
            ->selectRaw('assertion_type, COUNT(*) as count')
            ->groupBy('assertion_type')
            ->pluck('count', 'assertion_type')
            ->toArray();

        $entityTypes = DB::table('research_assertion')
            ->where('project_id', $projectId)
            ->where('status', '!=', 'retracted')
            ->selectRaw('DISTINCT subject_type')
            ->pluck('subject_type')
            ->toArray();

        $totalCollectionItems = 0;
        foreach ($collections as $collection) {
            $totalCollectionItems += DB::table('research_collection_item')
                ->where('collection_id', $collection->id)
                ->count();
        }

        $variableMeasured = [];

        foreach ($assertionTypeCounts as $type => $count) {
            $variableMeasured[] = [
                '@type' => 'PropertyValue',
                'name' => 'assertion_type_' . $type,
                'value' => $count,
            ];
        }

        $variableMeasured[] = [
            '@type' => 'PropertyValue',
            'name' => 'entity_types',
            'value' => implode(', ', $entityTypes),
        ];

        $variableMeasured[] = [
            '@type' => 'PropertyValue',
            'name' => 'total_collection_items',
            'value' => $totalCollectionItems,
        ];

        $variableMeasured[] = [
            '@type' => 'PropertyValue',
            'name' => 'total_collections',
            'value' => count($collections),
        ];

        // Build Schema.org Dataset
        $dataset = [
            '@context' => 'https://schema.org',
            '@type' => 'Dataset',
            'name' => $project->title,
            'description' => $project->description ?? '',
            'creator' => $creator,
            'dateCreated' => $project->created_at,
            'dateModified' => $project->updated_at,
        ];

        if (!empty($project->institution)) {
            $dataset['publisher'] = [
                '@type' => 'Organization',
                'name' => $project->institution,
            ];
        }

        if (!empty($distribution)) {
            $dataset['distribution'] = $distribution;
        }

        if (!empty($variableMeasured)) {
            $dataset['variableMeasured'] = $variableMeasured;
        }

        // Add temporal coverage from project dates
        if ($project->start_date || $project->expected_end_date) {
            $temporal = $project->start_date ?? '';
            if ($project->expected_end_date) {
                $temporal .= '/' . $project->expected_end_date;
            }
            $dataset['temporalCoverage'] = $temporal;
        }

        return $dataset;
    }
}
