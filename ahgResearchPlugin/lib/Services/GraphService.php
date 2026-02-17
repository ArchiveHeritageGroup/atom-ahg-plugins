<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * GraphService - Research Relationship Graph Builder
 *
 * Builds graph data structures from research assertions and AtoM
 * entity relationships. Supports D3.js-ready node/edge output and
 * export to GEXF (Gephi) and GraphML formats.
 *
 * Tables: research_assertion, relation, actor_i18n
 *
 * @package ahgResearchPlugin
 * @version 2.0.0
 */
class GraphService
{
    // =========================================================================
    // GRAPH BUILDING
    // =========================================================================

    /**
     * Build a D3.js-ready relationship graph from research assertions.
     *
     * Nodes are unique subjects and objects from assertions.
     * Edges are the assertions themselves (predicate as label).
     *
     * @param int $projectId The research project ID
     * @param array $filters Filters: assertion_type, status
     * @return array Graph with 'nodes' and 'edges' arrays
     */
    public function buildRelationshipGraph(int $projectId, array $filters = []): array
    {
        $query = DB::table('research_assertion')
            ->where('project_id', $projectId);

        if (!empty($filters['assertion_type'])) {
            $query->where('assertion_type', $filters['assertion_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $assertions = $query->orderBy('created_at', 'asc')->get();

        $nodes = [];
        $edges = [];

        foreach ($assertions as $assertion) {
            // Build subject node key
            $subjectKey = $assertion->subject_type . ':' . $assertion->subject_id;
            if (!isset($nodes[$subjectKey])) {
                $nodes[$subjectKey] = (object) [
                    'id' => $subjectKey,
                    'type' => $assertion->subject_type,
                    'label' => $assertion->subject_label ?? $subjectKey,
                    'group' => $assertion->subject_type,
                ];
            }

            // Build object node key (only if the assertion references another entity)
            if (!empty($assertion->object_type) && !empty($assertion->object_id)) {
                $objectKey = $assertion->object_type . ':' . $assertion->object_id;
                if (!isset($nodes[$objectKey])) {
                    $nodes[$objectKey] = (object) [
                        'id' => $objectKey,
                        'type' => $assertion->object_type,
                        'label' => $assertion->object_label ?? $objectKey,
                        'group' => $assertion->object_type,
                    ];
                }

                $edges[] = (object) [
                    'source' => $subjectKey,
                    'target' => $objectKey,
                    'label' => $assertion->predicate,
                    'type' => $assertion->assertion_type,
                    'status' => $assertion->status,
                    'confidence' => $assertion->confidence ? (float) $assertion->confidence : null,
                ];
            }
        }

        return [
            'nodes' => array_values($nodes),
            'edges' => $edges,
        ];
    }

    /**
     * Build a graph from AtoM actor relationships (relation table).
     *
     * Queries the relation table joined with actor_i18n to build
     * a graph of entity relationships stored in base AtoM.
     *
     * @param string $entityType The entity type (e.g. 'actor')
     * @param int $entityId The entity ID
     * @return array Graph with 'nodes' and 'edges' arrays
     */
    public function buildEntityGraph(string $entityType, int $entityId): array
    {
        $nodes = [];
        $edges = [];

        if ($entityType === 'actor') {
            // Get the source actor's name
            $sourceActor = DB::table('actor_i18n')
                ->where('id', $entityId)
                ->where('culture', 'en')
                ->first();

            if (!$sourceActor) {
                return ['nodes' => [], 'edges' => []];
            }

            $sourceKey = 'actor:' . $entityId;
            $nodes[$sourceKey] = (object) [
                'id' => $sourceKey,
                'type' => 'actor',
                'label' => $sourceActor->authorized_form_of_name ?? 'Actor #' . $entityId,
                'group' => 'actor',
            ];

            // Query relations where this actor is the subject
            $subjectRelations = DB::table('relation as rel')
                ->leftJoin('actor_i18n as ai', function ($join) {
                    $join->on('rel.object_id', '=', 'ai.id')
                        ->where('ai.culture', '=', 'en');
                })
                ->leftJoin('term_i18n as ti', function ($join) {
                    $join->on('rel.type_id', '=', 'ti.id')
                        ->where('ti.culture', '=', 'en');
                })
                ->where('rel.subject_id', $entityId)
                ->select(
                    'rel.id as relation_id',
                    'rel.object_id',
                    'rel.type_id',
                    'ai.authorized_form_of_name as object_name',
                    'ti.name as relation_type_name'
                )
                ->get();

            foreach ($subjectRelations as $rel) {
                $objectKey = 'actor:' . $rel->object_id;
                if (!isset($nodes[$objectKey])) {
                    $nodes[$objectKey] = (object) [
                        'id' => $objectKey,
                        'type' => 'actor',
                        'label' => $rel->object_name ?? 'Actor #' . $rel->object_id,
                        'group' => 'actor',
                    ];
                }

                $edges[] = (object) [
                    'source' => $sourceKey,
                    'target' => $objectKey,
                    'label' => $rel->relation_type_name ?? 'related to',
                    'type' => 'relational',
                    'status' => 'verified',
                    'confidence' => null,
                ];
            }

            // Query relations where this actor is the object
            $objectRelations = DB::table('relation as rel')
                ->leftJoin('actor_i18n as ai', function ($join) {
                    $join->on('rel.subject_id', '=', 'ai.id')
                        ->where('ai.culture', '=', 'en');
                })
                ->leftJoin('term_i18n as ti', function ($join) {
                    $join->on('rel.type_id', '=', 'ti.id')
                        ->where('ti.culture', '=', 'en');
                })
                ->where('rel.object_id', $entityId)
                ->select(
                    'rel.id as relation_id',
                    'rel.subject_id',
                    'rel.type_id',
                    'ai.authorized_form_of_name as subject_name',
                    'ti.name as relation_type_name'
                )
                ->get();

            foreach ($objectRelations as $rel) {
                $subjectNodeKey = 'actor:' . $rel->subject_id;
                if (!isset($nodes[$subjectNodeKey])) {
                    $nodes[$subjectNodeKey] = (object) [
                        'id' => $subjectNodeKey,
                        'type' => 'actor',
                        'label' => $rel->subject_name ?? 'Actor #' . $rel->subject_id,
                        'group' => 'actor',
                    ];
                }

                $edges[] = (object) [
                    'source' => $subjectNodeKey,
                    'target' => $sourceKey,
                    'label' => $rel->relation_type_name ?? 'related to',
                    'type' => 'relational',
                    'status' => 'verified',
                    'confidence' => null,
                ];
            }
        }

        return [
            'nodes' => array_values($nodes),
            'edges' => $edges,
        ];
    }

    // =========================================================================
    // EXPORT FORMATS
    // =========================================================================

    /**
     * Export project graph as GEXF XML string (for Gephi).
     *
     * @param int $projectId The project ID
     * @return string The GEXF XML document
     */
    public function exportGEXF(int $projectId): string
    {
        $graph = $this->buildRelationshipGraph($projectId);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<gexf xmlns="http://gexf.net/1.3" version="1.3">' . "\n";
        $xml .= '  <meta lastmodifieddate="' . date('Y-m-d') . '">' . "\n";
        $xml .= '    <creator>AtoM Heratio - ahgResearchPlugin</creator>' . "\n";
        $xml .= '    <description>Research assertion graph for project ' . $projectId . '</description>' . "\n";
        $xml .= '  </meta>' . "\n";
        $xml .= '  <graph defaultedgetype="directed">' . "\n";

        // Node attributes
        $xml .= '    <attributes class="node">' . "\n";
        $xml .= '      <attribute id="0" title="type" type="string"/>' . "\n";
        $xml .= '      <attribute id="1" title="group" type="string"/>' . "\n";
        $xml .= '    </attributes>' . "\n";

        // Edge attributes
        $xml .= '    <attributes class="edge">' . "\n";
        $xml .= '      <attribute id="0" title="assertion_type" type="string"/>' . "\n";
        $xml .= '      <attribute id="1" title="status" type="string"/>' . "\n";
        $xml .= '      <attribute id="2" title="confidence" type="float"/>' . "\n";
        $xml .= '    </attributes>' . "\n";

        // Nodes
        $xml .= '    <nodes>' . "\n";
        foreach ($graph['nodes'] as $node) {
            $xml .= '      <node id="' . $this->xmlEscape($node->id) . '" label="' . $this->xmlEscape($node->label) . '">' . "\n";
            $xml .= '        <attvalues>' . "\n";
            $xml .= '          <attvalue for="0" value="' . $this->xmlEscape($node->type) . '"/>' . "\n";
            $xml .= '          <attvalue for="1" value="' . $this->xmlEscape($node->group) . '"/>' . "\n";
            $xml .= '        </attvalues>' . "\n";
            $xml .= '      </node>' . "\n";
        }
        $xml .= '    </nodes>' . "\n";

        // Edges
        $xml .= '    <edges>' . "\n";
        foreach ($graph['edges'] as $index => $edge) {
            $xml .= '      <edge id="' . $index . '" source="' . $this->xmlEscape($edge->source) . '" target="' . $this->xmlEscape($edge->target) . '" label="' . $this->xmlEscape($edge->label) . '">' . "\n";
            $xml .= '        <attvalues>' . "\n";
            $xml .= '          <attvalue for="0" value="' . $this->xmlEscape($edge->type) . '"/>' . "\n";
            $xml .= '          <attvalue for="1" value="' . $this->xmlEscape($edge->status) . '"/>' . "\n";
            if ($edge->confidence !== null) {
                $xml .= '          <attvalue for="2" value="' . $edge->confidence . '"/>' . "\n";
            }
            $xml .= '        </attvalues>' . "\n";
            $xml .= '      </edge>' . "\n";
        }
        $xml .= '    </edges>' . "\n";

        $xml .= '  </graph>' . "\n";
        $xml .= '</gexf>' . "\n";

        return $xml;
    }

    /**
     * Export project graph as GraphML XML string.
     *
     * @param int $projectId The project ID
     * @return string The GraphML XML document
     */
    public function exportGraphML(int $projectId): string
    {
        $graph = $this->buildRelationshipGraph($projectId);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<graphml xmlns="http://graphml.graphdrawing.org/xmlns"' . "\n";
        $xml .= '  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
        $xml .= '  xsi:schemaLocation="http://graphml.graphdrawing.org/xmlns http://graphml.graphdrawing.org/xmlns/1.0/graphml.xsd">' . "\n";

        // Key definitions for node attributes
        $xml .= '  <key id="d0" for="node" attr.name="type" attr.type="string"/>' . "\n";
        $xml .= '  <key id="d1" for="node" attr.name="label" attr.type="string"/>' . "\n";
        $xml .= '  <key id="d2" for="node" attr.name="group" attr.type="string"/>' . "\n";

        // Key definitions for edge attributes
        $xml .= '  <key id="d3" for="edge" attr.name="label" attr.type="string"/>' . "\n";
        $xml .= '  <key id="d4" for="edge" attr.name="assertion_type" attr.type="string"/>' . "\n";
        $xml .= '  <key id="d5" for="edge" attr.name="status" attr.type="string"/>' . "\n";
        $xml .= '  <key id="d6" for="edge" attr.name="confidence" attr.type="double"/>' . "\n";

        $xml .= '  <graph id="G" edgedefault="directed">' . "\n";

        // Nodes
        foreach ($graph['nodes'] as $node) {
            $xml .= '    <node id="' . $this->xmlEscape($node->id) . '">' . "\n";
            $xml .= '      <data key="d0">' . $this->xmlEscape($node->type) . '</data>' . "\n";
            $xml .= '      <data key="d1">' . $this->xmlEscape($node->label) . '</data>' . "\n";
            $xml .= '      <data key="d2">' . $this->xmlEscape($node->group) . '</data>' . "\n";
            $xml .= '    </node>' . "\n";
        }

        // Edges
        foreach ($graph['edges'] as $index => $edge) {
            $xml .= '    <edge id="e' . $index . '" source="' . $this->xmlEscape($edge->source) . '" target="' . $this->xmlEscape($edge->target) . '">' . "\n";
            $xml .= '      <data key="d3">' . $this->xmlEscape($edge->label) . '</data>' . "\n";
            $xml .= '      <data key="d4">' . $this->xmlEscape($edge->type) . '</data>' . "\n";
            $xml .= '      <data key="d5">' . $this->xmlEscape($edge->status) . '</data>' . "\n";
            if ($edge->confidence !== null) {
                $xml .= '      <data key="d6">' . $edge->confidence . '</data>' . "\n";
            }
            $xml .= '    </edge>' . "\n";
        }

        $xml .= '  </graph>' . "\n";
        $xml .= '</graphml>' . "\n";

        return $xml;
    }

    // =========================================================================
    // ENTITY RELATIONSHIPS
    // =========================================================================

    /**
     * Get all relationships for an entity from both assertions and AtoM relations.
     *
     * Combines research assertions (where entity is subject or object)
     * with AtoM relation table entries (actor relationships).
     *
     * @param string $type The entity type (e.g. 'actor', 'information_object')
     * @param int $id The entity ID
     * @return array List of relationship objects
     */
    public function getEntityRelationships(string $type, int $id): array
    {
        $relationships = [];

        // Get assertions where this entity is the subject
        $subjectAssertions = DB::table('research_assertion as a')
            ->leftJoin('research_researcher as r', 'a.researcher_id', '=', 'r.id')
            ->where('a.subject_type', $type)
            ->where('a.subject_id', $id)
            ->select(
                'a.*',
                'r.first_name as researcher_first_name',
                'r.last_name as researcher_last_name'
            )
            ->get();

        foreach ($subjectAssertions as $assertion) {
            $relationships[] = (object) [
                'source' => 'assertion',
                'assertion_id' => $assertion->id,
                'direction' => 'outgoing',
                'related_type' => $assertion->object_type,
                'related_id' => $assertion->object_id,
                'related_label' => $assertion->object_label,
                'predicate' => $assertion->predicate,
                'assertion_type' => $assertion->assertion_type,
                'status' => $assertion->status,
                'confidence' => $assertion->confidence ? (float) $assertion->confidence : null,
                'researcher_name' => trim(($assertion->researcher_first_name ?? '') . ' ' . ($assertion->researcher_last_name ?? '')),
            ];
        }

        // Get assertions where this entity is the object
        $objectAssertions = DB::table('research_assertion as a')
            ->leftJoin('research_researcher as r', 'a.researcher_id', '=', 'r.id')
            ->where('a.object_type', $type)
            ->where('a.object_id', $id)
            ->select(
                'a.*',
                'r.first_name as researcher_first_name',
                'r.last_name as researcher_last_name'
            )
            ->get();

        foreach ($objectAssertions as $assertion) {
            $relationships[] = (object) [
                'source' => 'assertion',
                'assertion_id' => $assertion->id,
                'direction' => 'incoming',
                'related_type' => $assertion->subject_type,
                'related_id' => $assertion->subject_id,
                'related_label' => $assertion->subject_label,
                'predicate' => $assertion->predicate,
                'assertion_type' => $assertion->assertion_type,
                'status' => $assertion->status,
                'confidence' => $assertion->confidence ? (float) $assertion->confidence : null,
                'researcher_name' => trim(($assertion->researcher_first_name ?? '') . ' ' . ($assertion->researcher_last_name ?? '')),
            ];
        }

        // Get AtoM relation table entries (for actors)
        if ($type === 'actor') {
            // Relations where entity is the subject
            $atomSubject = DB::table('relation as rel')
                ->leftJoin('actor_i18n as ai', function ($join) {
                    $join->on('rel.object_id', '=', 'ai.id')
                        ->where('ai.culture', '=', 'en');
                })
                ->leftJoin('term_i18n as ti', function ($join) {
                    $join->on('rel.type_id', '=', 'ti.id')
                        ->where('ti.culture', '=', 'en');
                })
                ->where('rel.subject_id', $id)
                ->select(
                    'rel.id as relation_id',
                    'rel.object_id',
                    'rel.type_id',
                    'ai.authorized_form_of_name as related_name',
                    'ti.name as relation_type_name'
                )
                ->get();

            foreach ($atomSubject as $rel) {
                $relationships[] = (object) [
                    'source' => 'atom_relation',
                    'relation_id' => $rel->relation_id,
                    'direction' => 'outgoing',
                    'related_type' => 'actor',
                    'related_id' => $rel->object_id,
                    'related_label' => $rel->related_name ?? 'Actor #' . $rel->object_id,
                    'predicate' => $rel->relation_type_name ?? 'related to',
                    'assertion_type' => 'relational',
                    'status' => 'verified',
                    'confidence' => null,
                    'researcher_name' => null,
                ];
            }

            // Relations where entity is the object
            $atomObject = DB::table('relation as rel')
                ->leftJoin('actor_i18n as ai', function ($join) {
                    $join->on('rel.subject_id', '=', 'ai.id')
                        ->where('ai.culture', '=', 'en');
                })
                ->leftJoin('term_i18n as ti', function ($join) {
                    $join->on('rel.type_id', '=', 'ti.id')
                        ->where('ti.culture', '=', 'en');
                })
                ->where('rel.object_id', $id)
                ->select(
                    'rel.id as relation_id',
                    'rel.subject_id',
                    'rel.type_id',
                    'ai.authorized_form_of_name as related_name',
                    'ti.name as relation_type_name'
                )
                ->get();

            foreach ($atomObject as $rel) {
                $relationships[] = (object) [
                    'source' => 'atom_relation',
                    'relation_id' => $rel->relation_id,
                    'direction' => 'incoming',
                    'related_type' => 'actor',
                    'related_id' => $rel->subject_id,
                    'related_label' => $rel->related_name ?? 'Actor #' . $rel->subject_id,
                    'predicate' => $rel->relation_type_name ?? 'related to',
                    'assertion_type' => 'relational',
                    'status' => 'verified',
                    'confidence' => null,
                    'researcher_name' => null,
                ];
            }
        }

        return $relationships;
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Escape a string for safe XML attribute/content inclusion.
     *
     * @param string|null $value The value to escape
     * @return string The XML-safe string
     */
    private function xmlEscape(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
