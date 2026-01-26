<?php

/**
 * IIIF Annotation Service
 *
 * Manages annotations using W3C Web Annotation Data Model
 * Supports Annotorious and Mirador annotation creation/storage
 *
 * @package ahgIiifPlugin
 * @author Johan Pieterse - The Archive and Heritage Group
 */
class IiifAnnotationService
{
    private $baseUrl;

    public const MOTIVATION_COMMENTING = 'commenting';
    public const MOTIVATION_TAGGING = 'tagging';
    public const MOTIVATION_DESCRIBING = 'describing';
    public const MOTIVATION_LINKING = 'linking';
    public const MOTIVATION_TRANSCRIBING = 'transcribing';
    public const MOTIVATION_IDENTIFYING = 'identifying';

    public function __construct($baseUrl = null)
    {
        if ($baseUrl === null) {
            $host = $_SERVER['HTTP_HOST'] ?? 'psis.theahg.co.za';
            $this->baseUrl = "https://{$host}";
        } else {
            $this->baseUrl = $baseUrl;
        }
    }

    // ========================================================================
    // Annotation CRUD Operations
    // ========================================================================

    /**
     * Get all annotations for an object
     */
    public function getAnnotationsForObject($objectId)
    {
        $sql = 'SELECT a.*, b.body_type, b.body_value, b.body_format, b.body_language
                FROM iiif_annotation a
                LEFT JOIN iiif_annotation_body b ON a.id = b.annotation_id
                WHERE a.object_id = ?
                ORDER BY a.created_at';

        return QubitPdo::fetchAll($sql, [$objectId], ['fetchMode' => PDO::FETCH_OBJ]);
    }

    /**
     * Get annotations for a specific canvas
     */
    public function getAnnotationsForCanvas($canvasId)
    {
        $sql = 'SELECT a.*, b.body_type, b.body_value, b.body_format, b.body_language
                FROM iiif_annotation a
                LEFT JOIN iiif_annotation_body b ON a.id = b.annotation_id
                WHERE a.target_canvas = ?
                ORDER BY a.created_at';

        return QubitPdo::fetchAll($sql, [$canvasId], ['fetchMode' => PDO::FETCH_OBJ]);
    }

    /**
     * Get a single annotation by ID
     */
    public function getAnnotation($annotationId)
    {
        $sql = 'SELECT * FROM iiif_annotation WHERE id = ?';
        $annotation = QubitPdo::fetchOne($sql, [$annotationId], ['fetchMode' => PDO::FETCH_OBJ]);

        if ($annotation) {
            $bodySql = 'SELECT * FROM iiif_annotation_body WHERE annotation_id = ?';
            $annotation->bodies = QubitPdo::fetchAll($bodySql, [$annotationId], ['fetchMode' => PDO::FETCH_OBJ]);
        }

        return $annotation;
    }

    /**
     * Create a new annotation
     */
    public function createAnnotation(array $data)
    {
        $conn = Propel::getConnection();

        $sql = 'INSERT INTO iiif_annotation (object_id, canvas_id, target_canvas, target_selector, motivation, created_by, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())';

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $data['object_id'],
            $data['canvas_id'] ?? null,
            $data['target_canvas'],
            json_encode($data['target_selector'] ?? null),
            $data['motivation'] ?? self::MOTIVATION_COMMENTING,
            $data['created_by'] ?? null,
        ]);

        $annotationId = $conn->lastInsertId();

        // Add annotation body
        if (!empty($data['body'])) {
            $this->addAnnotationBody($annotationId, $data['body']);
        }

        return $annotationId;
    }

    /**
     * Add a body to an annotation
     */
    public function addAnnotationBody($annotationId, array $body)
    {
        $conn = Propel::getConnection();

        $sql = 'INSERT INTO iiif_annotation_body (annotation_id, body_type, body_value, body_format, body_language, body_purpose)
                VALUES (?, ?, ?, ?, ?, ?)';

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $annotationId,
            $body['type'] ?? 'TextualBody',
            $body['value'] ?? '',
            $body['format'] ?? 'text/plain',
            $body['language'] ?? 'en',
            $body['purpose'] ?? null,
        ]);

        return $conn->lastInsertId();
    }

    /**
     * Update an annotation
     */
    public function updateAnnotation($annotationId, array $data)
    {
        $conn = Propel::getConnection();

        $updates = ['updated_at = NOW()'];
        $params = [];

        if (isset($data['target_selector'])) {
            $updates[] = 'target_selector = ?';
            $params[] = json_encode($data['target_selector']);
        }

        if (isset($data['motivation'])) {
            $updates[] = 'motivation = ?';
            $params[] = $data['motivation'];
        }

        $params[] = $annotationId;

        $sql = 'UPDATE iiif_annotation SET ' . implode(', ', $updates) . ' WHERE id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        // Update body if provided
        if (!empty($data['body'])) {
            $deleteSql = 'DELETE FROM iiif_annotation_body WHERE annotation_id = ?';
            $deleteStmt = $conn->prepare($deleteSql);
            $deleteStmt->execute([$annotationId]);

            $this->addAnnotationBody($annotationId, $data['body']);
        }

        return true;
    }

    /**
     * Delete an annotation
     */
    public function deleteAnnotation($annotationId)
    {
        $conn = Propel::getConnection();

        // Delete bodies first (foreign key constraint)
        $deleteBodiesSql = 'DELETE FROM iiif_annotation_body WHERE annotation_id = ?';
        $stmt = $conn->prepare($deleteBodiesSql);
        $stmt->execute([$annotationId]);

        // Delete annotation
        $deleteAnnotationSql = 'DELETE FROM iiif_annotation WHERE id = ?';
        $stmt = $conn->prepare($deleteAnnotationSql);
        $stmt->execute([$annotationId]);

        return true;
    }

    // ========================================================================
    // Format Conversion
    // ========================================================================

    /**
     * Format annotations as IIIF Annotation Page
     */
    public function formatAsAnnotationPage($annotations, $objectId)
    {
        $items = [];

        foreach ($annotations as $annotation) {
            $items[] = $this->formatAnnotationAsIiif($annotation);
        }

        return [
            '@context' => 'http://www.w3.org/ns/anno.jsonld',
            'id' => $this->baseUrl . '/iiif/annotations/object/' . $objectId,
            'type' => 'AnnotationPage',
            'items' => $items,
        ];
    }

    /**
     * Format a database annotation as IIIF/W3C annotation
     */
    private function formatAnnotationAsIiif($annotation)
    {
        $iiifAnnotation = [
            '@context' => 'http://www.w3.org/ns/anno.jsonld',
            'id' => '#' . $annotation->id,
            'type' => 'Annotation',
            'motivation' => $annotation->motivation,
        ];

        // Body
        if (!empty($annotation->body_value)) {
            $iiifAnnotation['body'] = [[
                'type' => $annotation->body_type ?? 'TextualBody',
                'value' => $annotation->body_value,
                'purpose' => $annotation->motivation,
            ]];
        }

        // Target with selector
        $selector = json_decode($annotation->target_selector, true);

        if ($selector) {
            $iiifAnnotation['target'] = [
                'source' => $annotation->target_canvas,
                'selector' => $selector,
            ];
        } else {
            $iiifAnnotation['target'] = [
                'source' => $annotation->target_canvas,
            ];
        }

        return $iiifAnnotation;
    }

    /**
     * Parse Annotorious annotation format to database format
     */
    public function parseAnnotoriousAnnotation(array $annoData, $objectId)
    {
        $data = [
            'object_id' => $objectId,
            'target_canvas' => is_array($annoData['target']) ? ($annoData['target']['source'] ?? '') : $annoData['target'],
            'motivation' => $annoData['motivation'] ?? self::MOTIVATION_COMMENTING,
        ];

        // Parse selector
        if (isset($annoData['target']['selector'])) {
            $selector = $annoData['target']['selector'];

            if (is_array($selector)) {
                $data['target_selector'] = $selector;
            } elseif (is_string($selector)) {
                // SVG selector
                if (strpos($selector, '<svg') !== false) {
                    $data['target_selector'] = [
                        'type' => 'SvgSelector',
                        'value' => $selector,
                    ];
                }
                // Fragment selector (xywh)
                elseif (preg_match('/xywh=/', $selector)) {
                    $data['target_selector'] = [
                        'type' => 'FragmentSelector',
                        'conformsTo' => 'http://www.w3.org/TR/media-frags/',
                        'value' => $selector,
                    ];
                }
            }
        }

        // Parse body
        if (isset($annoData['body'])) {
            $body = $annoData['body'];

            if (is_array($body) && isset($body[0])) {
                $body = $body[0];
            }

            $data['body'] = [
                'type' => $body['type'] ?? 'TextualBody',
                'value' => $body['value'] ?? '',
                'format' => $body['format'] ?? 'text/plain',
                'language' => $body['language'] ?? 'en',
                'purpose' => $body['purpose'] ?? null,
            ];
        }

        return $data;
    }
}
