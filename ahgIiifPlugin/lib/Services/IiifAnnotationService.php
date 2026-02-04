<?php

use Illuminate\Database\Capsule\Manager as DB;

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
        // Ensure Laravel DB is initialized
        \AhgCore\Core\AhgDb::init();

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
        return DB::table('iiif_annotation as a')
            ->leftJoin('iiif_annotation_body as b', 'a.id', '=', 'b.annotation_id')
            ->where('a.object_id', $objectId)
            ->orderBy('a.created_at')
            ->select([
                'a.*',
                'b.body_type',
                'b.body_value',
                'b.body_format',
                'b.body_language',
            ])
            ->get()
            ->toArray();
    }

    /**
     * Get annotations for a specific canvas
     */
    public function getAnnotationsForCanvas($canvasId)
    {
        return DB::table('iiif_annotation as a')
            ->leftJoin('iiif_annotation_body as b', 'a.id', '=', 'b.annotation_id')
            ->where('a.target_canvas', $canvasId)
            ->orderBy('a.created_at')
            ->select([
                'a.*',
                'b.body_type',
                'b.body_value',
                'b.body_format',
                'b.body_language',
            ])
            ->get()
            ->toArray();
    }

    /**
     * Get a single annotation by ID
     */
    public function getAnnotation($annotationId)
    {
        $annotation = DB::table('iiif_annotation')
            ->where('id', $annotationId)
            ->first();

        if ($annotation) {
            $annotation->bodies = DB::table('iiif_annotation_body')
                ->where('annotation_id', $annotationId)
                ->get()
                ->toArray();
        }

        return $annotation;
    }

    /**
     * Create a new annotation
     */
    public function createAnnotation(array $data)
    {
        $annotationId = DB::table('iiif_annotation')->insertGetId([
            'object_id' => $data['object_id'],
            'canvas_id' => $data['canvas_id'] ?? null,
            'target_canvas' => $data['target_canvas'],
            'target_selector' => json_encode($data['target_selector'] ?? null),
            'motivation' => $data['motivation'] ?? self::MOTIVATION_COMMENTING,
            'created_by' => $data['created_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

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
        return DB::table('iiif_annotation_body')->insertGetId([
            'annotation_id' => $annotationId,
            'body_type' => $body['type'] ?? 'TextualBody',
            'body_value' => $body['value'] ?? '',
            'body_format' => $body['format'] ?? 'text/plain',
            'body_language' => $body['language'] ?? 'en',
            'body_purpose' => $body['purpose'] ?? null,
        ]);
    }

    /**
     * Update an annotation
     */
    public function updateAnnotation($annotationId, array $data)
    {
        $updates = ['updated_at' => date('Y-m-d H:i:s')];

        if (isset($data['target_selector'])) {
            $updates['target_selector'] = json_encode($data['target_selector']);
        }

        if (isset($data['motivation'])) {
            $updates['motivation'] = $data['motivation'];
        }

        DB::table('iiif_annotation')
            ->where('id', $annotationId)
            ->update($updates);

        // Update body if provided
        if (!empty($data['body'])) {
            DB::table('iiif_annotation_body')
                ->where('annotation_id', $annotationId)
                ->delete();

            $this->addAnnotationBody($annotationId, $data['body']);
        }

        return true;
    }

    /**
     * Delete an annotation
     */
    public function deleteAnnotation($annotationId)
    {
        // Delete bodies first (foreign key constraint)
        DB::table('iiif_annotation_body')
            ->where('annotation_id', $annotationId)
            ->delete();

        // Delete annotation
        DB::table('iiif_annotation')
            ->where('id', $annotationId)
            ->delete();

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
            // Handle both array and object formats
            $annotation = is_array($annotation) ? (object) $annotation : $annotation;
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
