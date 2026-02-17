<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * WebAnnotationService - W3C Web Annotation Data Model Implementation
 *
 * Implements the W3C Web Annotation Data Model (https://www.w3.org/TR/annotation-model/)
 * for scholarly annotations on archival objects. Supports IIIF annotation list
 * import/export and promotion of annotations to research assertions.
 *
 * Tables: research_annotation_v2, research_annotation_target,
 *         research_assertion, research_assertion_evidence
 *
 * @package ahgResearchPlugin
 * @version 2.0.0
 */
class WebAnnotationService
{
    // =========================================================================
    // ANNOTATION CRUD
    // =========================================================================

    /**
     * Create a W3C annotation with body and targets.
     *
     * @param int $researcherId The researcher creating the annotation
     * @param array $data Keys: project_id, motivation (commenting|describing|classifying|linking|questioning|tagging|highlighting),
     *                     body (array with type, value, format, language), targets (array of target data),
     *                     visibility (private|shared|public)
     * @return int Annotation ID
     */
    public function createAnnotation(int $researcherId, array $data): int
    {
        // Build creator JSON from researcher info
        $researcher = DB::table('research_researcher')
            ->where('id', $researcherId)
            ->first();

        $creatorJson = null;
        if ($researcher) {
            $creator = [
                'type' => 'Person',
                'name' => trim(($researcher->first_name ?? '') . ' ' . ($researcher->last_name ?? '')),
            ];
            if (!empty($researcher->email)) {
                $creator['email'] = $researcher->email;
            }
            if (!empty($researcher->orcid_id)) {
                $creator['id'] = 'https://orcid.org/' . $researcher->orcid_id;
            }
            if (!empty($researcher->institution)) {
                $creator['institution'] = $researcher->institution;
            }
            $creatorJson = json_encode($creator);
        }

        // Build generated timestamp
        $now = date('Y-m-d\TH:i:s\Z');
        $generatedJson = json_encode([
            'type' => 'Software',
            'name' => 'AtoM Heratio Research Portal',
            'generated_at' => $now,
        ]);

        // Build body JSON
        $bodyJson = null;
        if (isset($data['body'])) {
            $body = [
                'type' => $data['body']['type'] ?? 'TextualBody',
                'value' => $data['body']['value'] ?? '',
                'format' => $data['body']['format'] ?? 'text/plain',
            ];
            if (!empty($data['body']['language'])) {
                $body['language'] = $data['body']['language'];
            }
            if (!empty($data['body']['purpose'])) {
                $body['purpose'] = $data['body']['purpose'];
            }
            $bodyJson = json_encode($body);
        }

        $annotationId = DB::table('research_annotation_v2')->insertGetId([
            'researcher_id' => $researcherId,
            'project_id' => $data['project_id'] ?? null,
            'motivation' => $data['motivation'] ?? 'commenting',
            'body_json' => $bodyJson,
            'creator_json' => $creatorJson,
            'generated_json' => $generatedJson,
            'status' => 'active',
            'visibility' => $data['visibility'] ?? 'private',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Insert targets
        if (!empty($data['targets']) && is_array($data['targets'])) {
            foreach ($data['targets'] as $target) {
                $this->addTarget($annotationId, $target);
            }
        }

        return $annotationId;
    }

    /**
     * Get an annotation by ID with its targets.
     *
     * @param int $id The annotation ID
     * @return object|null The annotation with targets, or null
     */
    public function getAnnotation(int $id): ?object
    {
        $annotation = DB::table('research_annotation_v2 as a')
            ->leftJoin('research_researcher as r', 'a.researcher_id', '=', 'r.id')
            ->where('a.id', $id)
            ->where('a.status', '!=', 'deleted')
            ->select(
                'a.*',
                'r.first_name as researcher_first_name',
                'r.last_name as researcher_last_name',
                'r.email as researcher_email',
                'r.orcid_id as researcher_orcid'
            )
            ->first();

        if ($annotation) {
            $annotation->targets = DB::table('research_annotation_target')
                ->where('annotation_id', $id)
                ->orderBy('id')
                ->get()
                ->toArray();
        }

        return $annotation;
    }

    /**
     * Get annotations for a specific object/entity.
     *
     * @param int $objectId The object ID to get annotations for
     * @param string|null $entityType The entity type filter (e.g. 'information_object', 'actor')
     * @param array $filters Keys: motivation, visibility, researcher_id
     * @return array List of annotations with targets
     */
    public function getObjectAnnotations(int $objectId, ?string $entityType = null, array $filters = []): array
    {
        $query = DB::table('research_annotation_v2 as a')
            ->join('research_annotation_target as t', 'a.id', '=', 't.annotation_id')
            ->leftJoin('research_researcher as r', 'a.researcher_id', '=', 'r.id')
            ->where('t.source_id', $objectId)
            ->where('a.status', '!=', 'deleted');

        if ($entityType !== null) {
            $query->where('t.source_type', $entityType);
        }

        if (!empty($filters['motivation'])) {
            $query->where('a.motivation', $filters['motivation']);
        }

        if (!empty($filters['visibility'])) {
            $query->where('a.visibility', $filters['visibility']);
        }

        if (!empty($filters['researcher_id'])) {
            $query->where('a.researcher_id', $filters['researcher_id']);
        }

        $annotations = $query->select(
            'a.*',
            'r.first_name as researcher_first_name',
            'r.last_name as researcher_last_name'
        )
            ->distinct()
            ->orderBy('a.created_at', 'desc')
            ->get()
            ->toArray();

        // Attach targets to each annotation
        foreach ($annotations as &$annotation) {
            $annotation->targets = DB::table('research_annotation_target')
                ->where('annotation_id', $annotation->id)
                ->orderBy('id')
                ->get()
                ->toArray();
        }

        return $annotations;
    }

    /**
     * Get all annotations for a project.
     *
     * @param int $projectId The project ID
     * @param array $filters Keys: motivation, visibility, researcher_id
     * @return array List of annotations with targets
     */
    public function getProjectAnnotations(int $projectId, array $filters = []): array
    {
        $query = DB::table('research_annotation_v2 as a')
            ->leftJoin('research_researcher as r', 'a.researcher_id', '=', 'r.id')
            ->where('a.project_id', $projectId)
            ->where('a.status', '!=', 'deleted');

        if (!empty($filters['motivation'])) {
            $query->where('a.motivation', $filters['motivation']);
        }

        if (!empty($filters['visibility'])) {
            $query->where('a.visibility', $filters['visibility']);
        }

        if (!empty($filters['researcher_id'])) {
            $query->where('a.researcher_id', $filters['researcher_id']);
        }

        $annotations = $query->select(
            'a.*',
            'r.first_name as researcher_first_name',
            'r.last_name as researcher_last_name'
        )
            ->orderBy('a.created_at', 'desc')
            ->get()
            ->toArray();

        // Attach targets to each annotation
        foreach ($annotations as &$annotation) {
            $annotation->targets = DB::table('research_annotation_target')
                ->where('annotation_id', $annotation->id)
                ->orderBy('id')
                ->get()
                ->toArray();
        }

        return $annotations;
    }

    /**
     * Update an annotation (body, motivation, visibility).
     *
     * @param int $id The annotation ID
     * @param array $data Fields to update (body, motivation, visibility)
     * @return bool Success status
     */
    public function updateAnnotation(int $id, array $data): bool
    {
        $updateData = [];

        if (isset($data['motivation'])) {
            $updateData['motivation'] = $data['motivation'];
        }

        if (isset($data['visibility'])) {
            $updateData['visibility'] = $data['visibility'];
        }

        if (isset($data['body'])) {
            $body = [
                'type' => $data['body']['type'] ?? 'TextualBody',
                'value' => $data['body']['value'] ?? '',
                'format' => $data['body']['format'] ?? 'text/plain',
            ];
            if (!empty($data['body']['language'])) {
                $body['language'] = $data['body']['language'];
            }
            if (!empty($data['body']['purpose'])) {
                $body['purpose'] = $data['body']['purpose'];
            }
            $updateData['body_json'] = json_encode($body);
        }

        if (empty($updateData)) {
            return true;
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        return DB::table('research_annotation_v2')
            ->where('id', $id)
            ->where('status', '!=', 'deleted')
            ->update($updateData) >= 0;
    }

    /**
     * Soft-delete an annotation (set status to 'deleted').
     *
     * @param int $id The annotation ID
     * @return bool Success status
     */
    public function deleteAnnotation(int $id): bool
    {
        return DB::table('research_annotation_v2')
            ->where('id', $id)
            ->update([
                'status' => 'deleted',
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    // =========================================================================
    // TARGET MANAGEMENT
    // =========================================================================

    /**
     * Add a target to an existing annotation.
     *
     * @param int $annotationId The annotation ID
     * @param array $targetData Keys: source_type, source_id, selector_type
     *              (TextQuoteSelector|FragmentSelector|SvgSelector|PointSelector|RangeSelector|TimeSelector),
     *              selector_json, source_url
     * @return int The target ID
     */
    public function addTarget(int $annotationId, array $targetData): int
    {
        $selectorJson = null;
        if (isset($targetData['selector_json'])) {
            $selectorJson = is_array($targetData['selector_json'])
                ? json_encode($targetData['selector_json'])
                : $targetData['selector_json'];
        }

        return DB::table('research_annotation_target')->insertGetId([
            'annotation_id' => $annotationId,
            'source_type' => $targetData['source_type'] ?? 'information_object',
            'source_id' => $targetData['source_id'] ?? null,
            'selector_type' => $targetData['selector_type'] ?? null,
            'selector_json' => $selectorJson,
            'source_url' => $targetData['source_url'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Remove a target from an annotation.
     *
     * @param int $targetId The target ID
     * @return bool Success status
     */
    public function removeTarget(int $targetId): bool
    {
        return DB::table('research_annotation_target')
            ->where('id', $targetId)
            ->delete() > 0;
    }

    // =========================================================================
    // IIIF / W3C WEB ANNOTATION EXPORT & IMPORT
    // =========================================================================

    /**
     * Export annotations for an object as a W3C/IIIF annotation list (JSON-LD format).
     *
     * Returns a W3C Web Annotation compatible structure:
     * {
     *   "@context": "http://www.w3.org/ns/anno.jsonld",
     *   "type": "AnnotationPage",
     *   "items": [...]
     * }
     *
     * @param int $objectId The object ID to export annotations for
     * @return array W3C AnnotationPage structure
     */
    public function exportAsIIIFAnnotationList(int $objectId): array
    {
        // Get the slug for building URIs
        $slug = DB::table('slug')
            ->where('object_id', $objectId)
            ->value('slug');

        $baseUri = \sfConfig::get('app_siteBaseUrl', 'https://psis.theahg.co.za');

        // Get all active, non-private annotations targeting this object
        $annotations = DB::table('research_annotation_v2 as a')
            ->join('research_annotation_target as t', 'a.id', '=', 't.annotation_id')
            ->leftJoin('research_researcher as r', 'a.researcher_id', '=', 'r.id')
            ->where('t.source_id', $objectId)
            ->where('a.status', 'active')
            ->whereIn('a.visibility', ['shared', 'public'])
            ->select('a.*', 'r.first_name', 'r.last_name', 'r.orcid_id')
            ->distinct()
            ->orderBy('a.created_at')
            ->get()
            ->toArray();

        $items = [];
        foreach ($annotations as $annotation) {
            $targets = DB::table('research_annotation_target')
                ->where('annotation_id', $annotation->id)
                ->orderBy('id')
                ->get()
                ->toArray();

            $item = [
                '@context' => 'http://www.w3.org/ns/anno.jsonld',
                'id' => $baseUri . '/api/research/annotations/' . $annotation->id,
                'type' => 'Annotation',
                'motivation' => $annotation->motivation,
                'created' => date('c', strtotime($annotation->created_at)),
                'modified' => date('c', strtotime($annotation->updated_at)),
            ];

            // Creator
            $creator = [];
            if ($annotation->creator_json) {
                $creatorData = json_decode($annotation->creator_json, true);
                if ($creatorData) {
                    $creator = $creatorData;
                }
            }
            if (empty($creator) && ($annotation->first_name || $annotation->last_name)) {
                $creator = [
                    'type' => 'Person',
                    'name' => trim($annotation->first_name . ' ' . $annotation->last_name),
                ];
                if (!empty($annotation->orcid_id)) {
                    $creator['id'] = 'https://orcid.org/' . $annotation->orcid_id;
                }
            }
            if (!empty($creator)) {
                $item['creator'] = $creator;
            }

            // Generator
            if ($annotation->generated_json) {
                $generatedData = json_decode($annotation->generated_json, true);
                if ($generatedData) {
                    $item['generator'] = [
                        'type' => $generatedData['type'] ?? 'Software',
                        'name' => $generatedData['name'] ?? 'AtoM Heratio',
                    ];
                }
            }

            // Body
            if ($annotation->body_json) {
                $bodyData = json_decode($annotation->body_json, true);
                if ($bodyData) {
                    $item['body'] = [
                        'type' => $bodyData['type'] ?? 'TextualBody',
                        'value' => $bodyData['value'] ?? '',
                        'format' => $bodyData['format'] ?? 'text/plain',
                    ];
                    if (!empty($bodyData['language'])) {
                        $item['body']['language'] = $bodyData['language'];
                    }
                    if (!empty($bodyData['purpose'])) {
                        $item['body']['purpose'] = $bodyData['purpose'];
                    }
                }
            }

            // Targets
            $targetList = [];
            foreach ($targets as $target) {
                $t = [];

                // Source URL or build from source_type + source_id
                if (!empty($target->source_url)) {
                    $t['source'] = $target->source_url;
                } elseif ($slug && $target->source_type === 'information_object') {
                    $t['source'] = $baseUri . '/' . $slug;
                } elseif ($target->source_id) {
                    $targetSlug = DB::table('slug')
                        ->where('object_id', $target->source_id)
                        ->value('slug');
                    $t['source'] = $baseUri . '/' . ($targetSlug ?: $target->source_id);
                }

                // Selector
                if ($target->selector_type && $target->selector_json) {
                    $selectorData = json_decode($target->selector_json, true);
                    if ($selectorData) {
                        $selector = array_merge(
                            ['type' => $target->selector_type],
                            $selectorData
                        );
                        $t['selector'] = $selector;
                    }
                } elseif ($target->selector_type) {
                    $t['selector'] = ['type' => $target->selector_type];
                }

                if (!empty($t)) {
                    $targetList[] = $t;
                }
            }

            // W3C spec: single target as object, multiple as array
            if (count($targetList) === 1) {
                $item['target'] = $targetList[0];
            } elseif (count($targetList) > 1) {
                $item['target'] = $targetList;
            }

            $items[] = $item;
        }

        return [
            '@context' => 'http://www.w3.org/ns/anno.jsonld',
            'id' => $baseUri . '/api/research/annotations/object/' . $objectId,
            'type' => 'AnnotationPage',
            'partOf' => [
                'id' => $baseUri . '/api/research/annotations/object/' . $objectId . '/collection',
                'type' => 'AnnotationCollection',
                'total' => count($items),
            ],
            'items' => $items,
        ];
    }

    /**
     * Import IIIF annotations from a W3C annotation list.
     *
     * Parses items from a W3C Web Annotation list and creates corresponding
     * annotation_v2 and annotation_target records.
     *
     * @param int $researcherId The researcher importing the annotations
     * @param int $objectId The object to attach annotations to
     * @param array $iiifData W3C AnnotationPage or AnnotationCollection data
     * @return int Number of annotations imported
     */
    public function importIIIFAnnotations(int $researcherId, int $objectId, array $iiifData): int
    {
        $items = [];

        // Handle AnnotationPage or AnnotationCollection
        if (isset($iiifData['items'])) {
            $items = $iiifData['items'];
        } elseif (isset($iiifData['resources'])) {
            // IIIF Presentation API 2.x compatibility
            $items = $iiifData['resources'];
        }

        if (empty($items)) {
            return 0;
        }

        $imported = 0;

        foreach ($items as $item) {
            // Skip if not an Annotation type
            $type = $item['type'] ?? $item['@type'] ?? '';
            if ($type !== 'Annotation' && $type !== 'oa:Annotation') {
                continue;
            }

            // Parse motivation
            $motivation = $this->parseMotivation($item['motivation'] ?? 'commenting');

            // Parse body
            $body = null;
            if (isset($item['body'])) {
                $bodyData = $item['body'];
                if (is_string($bodyData)) {
                    $body = [
                        'type' => 'TextualBody',
                        'value' => $bodyData,
                        'format' => 'text/plain',
                    ];
                } elseif (is_array($bodyData)) {
                    $body = [
                        'type' => $bodyData['type'] ?? 'TextualBody',
                        'value' => $bodyData['value'] ?? ($bodyData['chars'] ?? ''),
                        'format' => $bodyData['format'] ?? 'text/plain',
                    ];
                    if (!empty($bodyData['language'])) {
                        $body['language'] = $bodyData['language'];
                    }
                    if (!empty($bodyData['purpose'])) {
                        $body['purpose'] = $bodyData['purpose'];
                    }
                }
            }

            // Parse targets
            $targets = [];
            $targetData = $item['target'] ?? $item['on'] ?? null;

            if ($targetData !== null) {
                // Normalize to array of targets
                $targetList = isset($targetData['source']) || isset($targetData['id'])
                    ? [$targetData]
                    : (is_array($targetData) && !isset($targetData['type']) ? $targetData : [$targetData]);

                foreach ($targetList as $tgt) {
                    $target = [
                        'source_type' => 'information_object',
                        'source_id' => $objectId,
                    ];

                    if (is_string($tgt)) {
                        $target['source_url'] = $tgt;
                    } else {
                        $target['source_url'] = $tgt['source'] ?? $tgt['id'] ?? null;

                        // Parse selector
                        if (isset($tgt['selector'])) {
                            $selector = $tgt['selector'];
                            $selectorType = $selector['type'] ?? null;

                            if ($selectorType && in_array($selectorType, [
                                'TextQuoteSelector', 'FragmentSelector', 'SvgSelector',
                                'PointSelector', 'RangeSelector', 'TimeSelector',
                            ])) {
                                $target['selector_type'] = $selectorType;
                            }

                            // Store full selector data minus the type key
                            $selectorCopy = $selector;
                            unset($selectorCopy['type']);
                            if (!empty($selectorCopy)) {
                                $target['selector_json'] = $selectorCopy;
                            }
                        }
                    }

                    $targets[] = $target;
                }
            } else {
                // No target specified, default to the object itself
                $targets[] = [
                    'source_type' => 'information_object',
                    'source_id' => $objectId,
                ];
            }

            // Create the annotation
            $this->createAnnotation($researcherId, [
                'motivation' => $motivation,
                'body' => $body,
                'targets' => $targets,
                'visibility' => 'private',
            ]);

            $imported++;
        }

        return $imported;
    }

    // =========================================================================
    // ASSERTION PROMOTION
    // =========================================================================

    /**
     * Promote an annotation to an assertion.
     *
     * Bridge method that creates an assertion from annotation content and
     * links the annotation as evidence for the assertion.
     *
     * @param int $annotationId The annotation ID to promote
     * @param array $assertionData Keys: subject_type, subject_id, subject_label,
     *              predicate, object_value, object_type, object_id, object_label,
     *              assertion_type (biographical|chronological|spatial|relational|attributive),
     *              confidence (0-100)
     * @return int Assertion ID
     */
    public function promoteToAssertion(int $annotationId, array $assertionData): int
    {
        $annotation = $this->getAnnotation($annotationId);

        if (!$annotation) {
            throw new \RuntimeException('Annotation not found: ' . $annotationId);
        }

        // Create the assertion record
        $assertionId = DB::table('research_assertion')->insertGetId([
            'researcher_id' => $annotation->researcher_id,
            'project_id' => $annotation->project_id,
            'subject_type' => $assertionData['subject_type'],
            'subject_id' => $assertionData['subject_id'],
            'subject_label' => $assertionData['subject_label'] ?? null,
            'predicate' => $assertionData['predicate'],
            'object_value' => $assertionData['object_value'] ?? null,
            'object_type' => $assertionData['object_type'] ?? null,
            'object_id' => $assertionData['object_id'] ?? null,
            'object_label' => $assertionData['object_label'] ?? null,
            'assertion_type' => $assertionData['assertion_type'] ?? 'attributive',
            'status' => 'proposed',
            'confidence' => $assertionData['confidence'] ?? null,
            'version' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Link the annotation as supporting evidence
        $note = null;
        if ($annotation->body_json) {
            $bodyData = json_decode($annotation->body_json, true);
            $note = $bodyData['value'] ?? null;
        }

        DB::table('research_assertion_evidence')->insert([
            'assertion_id' => $assertionId,
            'source_type' => 'annotation',
            'source_id' => $annotationId,
            'selector_json' => null,
            'relationship' => 'supports',
            'note' => $note,
            'added_by' => $annotation->researcher_id,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $assertionId;
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Parse a W3C motivation string to the supported ENUM value.
     *
     * Handles namespaced motivations (oa:commenting) and maps unsupported
     * values to the closest match.
     *
     * @param string $motivation The motivation string from W3C data
     * @return string Normalized motivation value
     */
    private function parseMotivation(string $motivation): string
    {
        // Strip namespace prefix (oa:, sc:, etc.)
        if (str_contains($motivation, ':')) {
            $motivation = substr($motivation, strpos($motivation, ':') + 1);
        }

        $allowed = [
            'commenting', 'describing', 'classifying', 'linking',
            'questioning', 'tagging', 'highlighting',
        ];

        $motivation = strtolower($motivation);

        if (in_array($motivation, $allowed)) {
            return $motivation;
        }

        // Map common IIIF/W3C motivations to closest supported value
        $mappings = [
            'bookmarking' => 'tagging',
            'identifying' => 'classifying',
            'editing' => 'commenting',
            'moderating' => 'commenting',
            'replying' => 'commenting',
            'assessing' => 'describing',
            'painting' => 'describing',
            'supplementing' => 'describing',
        ];

        return $mappings[$motivation] ?? 'commenting';
    }
}
