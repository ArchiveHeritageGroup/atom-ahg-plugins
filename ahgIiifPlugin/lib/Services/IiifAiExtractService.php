<?php

namespace AhgIiif\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * IIIF AI Extract (#220).
 *
 * Operates over a digital object's IIIF manifest at the canvas/region level:
 * lists canvases, crops a region via the IIIF Image API, sends the region image
 * to the AHG AI gateway vision model, runs an extraction task, and stores the
 * result for curator review.
 *
 * AI INFERENCE ROUTES THROUGH THE GATEWAY ONLY.
 * The VLM call goes through \AtomFramework\Services\AI\AiGatewayClient
 * (https://ai.theahg.co.za/ai/v1). The region-image fetch is a plain LOCAL read
 * from Cantaloupe (127.0.0.1:8182) — deliberately NOT the gateway client, whose
 * SSRF guard would (correctly) reject a private IP.
 *
 * Storage: table `iiif_ai_extract` (status draft/approved/rejected).
 *
 * @author The Archive and Heritage Group
 */
class IiifAiExtractService
{
    /** Vision-capable model routed by the gateway (confirmed live 2026-07-04). */
    public const DEFAULT_VISION_MODEL = 'llava:7b';

    /** Supported extraction tasks → VLM prompt. */
    public const TASK_PROMPTS = [
        'caption' => 'Provide a single concise caption (one sentence) for this archival image. Output only the caption.',
        'describe' => 'Describe this archival image in detail for a catalogue: subjects, setting, notable objects, and any visible text. Be factual; do not invent details you cannot see.',
        'transcribe' => 'Transcribe all text visible in this image exactly as written, preserving line breaks. Output only the transcription, nothing else.',
        'entities' => 'List the named entities depicted or written in this image. Return ONLY a JSON array of objects like [{"type":"person|organization|place|date","text":"..."}]. If none, return [].',
        'tags' => 'Suggest 5 to 10 short descriptive subject keywords (tags) for this archival image. Return only a comma-separated list.',
    ];

    private string $cantaloupeBaseUrl;
    private string $visionModel;

    public function __construct(?string $cantaloupeBaseUrl = null, ?string $visionModel = null)
    {
        $this->cantaloupeBaseUrl = rtrim(
            $cantaloupeBaseUrl ?? \sfConfig::get('app_iiif_cantaloupe_internal_url', 'http://127.0.0.1:8182'),
            '/'
        );
        $this->visionModel = $visionModel
            ?? (string) \sfConfig::get('app_iiif_ai_vision_model', self::DEFAULT_VISION_MODEL);
    }

    /**
     * List the image canvases for an information object, with the Cantaloupe id
     * and pixel dimensions needed to request a region crop.
     *
     * MCP-tool surface: iiif_manifest_canvases.
     *
     * @return array<int,array{index:int,digital_object_id:int,label:string,cantaloupe_id:string,width:int,height:int,image_base:string}>
     */
    public function listCanvases(int $objectId): array
    {
        $rows = DB::table('digital_object as do')
            ->where('do.object_id', $objectId)
            ->whereNull('do.parent_id')
            ->where('do.mime_type', 'LIKE', 'image/%')
            ->orderBy('do.id')
            ->select('do.id', 'do.name', 'do.path', 'do.mime_type')
            ->get();

        $canvases = [];
        $index = 0;
        foreach ($rows as $do) {
            $cantaloupeId = $this->cantaloupeId((string) $do->path, (string) $do->name);
            $dims = $this->imageDimensions($cantaloupeId);
            $canvases[] = [
                'index' => $index,
                'digital_object_id' => (int) $do->id,
                'label' => (string) $do->name,
                'cantaloupe_id' => $cantaloupeId,
                'width' => $dims['width'],
                'height' => $dims['height'],
                'image_base' => "{$this->cantaloupeBaseUrl}/iiif/2/{$cantaloupeId}",
            ];
            $index++;
        }

        return $canvases;
    }

    /**
     * Crop a region of a canvas, run a VLM extraction task through the gateway,
     * and store the result as a draft.
     *
     * MCP-tool surface: iiif_region_extract.
     *
     * @param string $region 'full' or IIIF region 'x,y,w,h'
     * @param string $task   one of array_keys(self::TASK_PROMPTS)
     * @return array{success:bool,error:?string,extract_id:?int,task:string,region:string,model:?string,text:?string,json:mixed}
     */
    public function extractRegion(int $objectId, int $canvasIndex, string $region, string $task, ?int $userId = null): array
    {
        $task = strtolower(trim($task));
        if (!isset(self::TASK_PROMPTS[$task])) {
            return $this->fail("Unknown task '{$task}'", $task, $region);
        }
        $region = $this->normaliseRegion($region);

        $canvases = $this->listCanvases($objectId);
        if (!isset($canvases[$canvasIndex])) {
            return $this->fail("Canvas index {$canvasIndex} not found for object {$objectId}", $task, $region);
        }
        $canvas = $canvases[$canvasIndex];

        // Bound the delivered size so the base64 payload to the VLM stays small,
        // but NEVER upscale (Cantaloupe 403s an "!w,h" request larger than the
        // source). Downscale by width only when the source region exceeds the cap.
        $maxWidth = 1024;
        if ($region === 'full') {
            $sourceWidth = (int) $canvas['width'];
        } else {
            $parts = explode(',', $region);
            $sourceWidth = (int) ($parts[2] ?? 0);
        }
        $size = ($sourceWidth > $maxWidth) ? "{$maxWidth}," : 'max';
        $imageUrl = "{$this->cantaloupeBaseUrl}/iiif/2/{$canvas['cantaloupe_id']}/{$region}/{$size}/0/default.jpg";

        $bytes = $this->fetchImageBytes($imageUrl);
        if ($bytes === null) {
            return $this->fail("Failed to fetch region image from Cantaloupe", $task, $region);
        }

        $client = \AtomFramework\Services\AI\AiGatewayClient::fromSettings();
        if (!$client->isConfigured()) {
            return $this->fail('AI gateway API key not configured', $task, $region);
        }

        $result = $client->visionGenerate(
            self::TASK_PROMPTS[$task],
            [base64_encode($bytes)],
            $this->visionModel,
            ['temperature' => 0.1, 'num_predict' => 512]
        );

        if (empty($result['success'])) {
            return $this->fail($result['error'] ?? 'Vision model returned no text', $task, $region);
        }

        $text = trim((string) $result['text']);
        $json = ($task === 'entities') ? $this->tryDecodeJson($text) : null;

        $now = date('Y-m-d H:i:s');
        $extractId = (int) DB::table('iiif_ai_extract')->insertGetId([
            'object_id' => $objectId,
            'digital_object_id' => $canvas['digital_object_id'],
            'canvas_index' => $canvasIndex,
            'region' => $region,
            'task' => $task,
            'model' => (string) ($result['model'] ?? $this->visionModel),
            'prompt' => self::TASK_PROMPTS[$task],
            'output_text' => $text,
            'output_json' => $json !== null ? json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
            'confidence' => null,
            'status' => 'draft',
            'created_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'success' => true,
            'error' => null,
            'extract_id' => $extractId,
            'task' => $task,
            'region' => $region,
            'model' => (string) ($result['model'] ?? $this->visionModel),
            'text' => $text,
            'json' => $json,
        ];
    }

    /**
     * All stored extractions for an object (newest first).
     *
     * @return array<int,array<string,mixed>>
     */
    public function listExtractions(int $objectId): array
    {
        return DB::table('iiif_ai_extract')
            ->where('object_id', $objectId)
            ->orderByDesc('id')
            ->get()
            ->map(function ($r) {
                $row = (array) $r;
                if (!empty($row['output_json'])) {
                    $row['output_json'] = json_decode($row['output_json'], true);
                }
                return $row;
            })
            ->toArray();
    }

    // ---------------------------------------------------------------------
    // Internals
    // ---------------------------------------------------------------------

    /** Cantaloupe identifier convention, mirrored from IiifManifestV3Service. */
    private function cantaloupeId(string $path, string $name): string
    {
        $imagePath = ltrim($path, '/');

        return str_replace('/', '_SL_', $imagePath) . $name;
    }

    /** Read width/height from Cantaloupe's info.json (0/0 if unavailable). */
    private function imageDimensions(string $cantaloupeId): array
    {
        $infoUrl = "{$this->cantaloupeBaseUrl}/iiif/2/{$cantaloupeId}/info.json";
        $json = $this->fetchImageBytes($infoUrl);
        if ($json !== null) {
            $info = json_decode($json, true);
            if (is_array($info) && isset($info['width'], $info['height'])) {
                return ['width' => (int) $info['width'], 'height' => (int) $info['height']];
            }
        }

        return ['width' => 0, 'height' => 0];
    }

    /**
     * Plain LOCAL fetch from Cantaloupe. Not the gateway client — this reads a
     * private-IP upstream by design, so we must NOT route it through the
     * SSRF-guarded HttpClientService.
     */
    private function fetchImageBytes(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $code !== 200 || $body === '') {
            return null;
        }

        return (string) $body;
    }

    /** Validate a region token: 'full' or four comma-separated non-negative ints. */
    private function normaliseRegion(string $region): string
    {
        $region = trim($region);
        if ($region === '' || strtolower($region) === 'full') {
            return 'full';
        }
        if (preg_match('/^\d+,\d+,\d+,\d+$/', $region)) {
            return $region;
        }

        return 'full';
    }

    /** Best-effort JSON decode for the entities task (strips code fences). */
    private function tryDecodeJson(string $text)
    {
        $clean = trim($text);
        $clean = preg_replace('/^```(?:json)?|```$/m', '', $clean);
        $decoded = json_decode(trim((string) $clean), true);

        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
    }

    private function fail(string $error, string $task, string $region): array
    {
        return [
            'success' => false,
            'error' => $error,
            'extract_id' => null,
            'task' => $task,
            'region' => $region,
            'model' => null,
            'text' => null,
            'json' => null,
        ];
    }
}
