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

        // 'transcribe' → accurate LOCAL OCR (Tesseract, no gateway — the gateway
        // does not route an OCR endpoint and a small VLM hallucinates text).
        // All other tasks → gateway vision model.
        if ($task === 'transcribe') {
            $text = $this->ocrRegion($bytes);
            if ($text === null || $text === '') {
                return $this->fail('OCR produced no text for this region', $task, $region);
            }
            $modelUsed = 'tesseract';
            $json = null;
        } else {
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
            $modelUsed = (string) ($result['model'] ?? $this->visionModel);
            $json = ($task === 'entities') ? $this->tryDecodeJson($text) : null;
        }

        $now = date('Y-m-d H:i:s');
        $extractId = (int) DB::table('iiif_ai_extract')->insertGetId([
            'object_id' => $objectId,
            'digital_object_id' => $canvas['digital_object_id'],
            'canvas_index' => $canvasIndex,
            'region' => $region,
            'task' => $task,
            'model' => $modelUsed,
            'prompt' => ($task === 'transcribe') ? null : self::TASK_PROMPTS[$task],
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
            'model' => $modelUsed,
            'text' => $text,
            'json' => $json,
        ];
    }

    /**
     * Approve a draft extraction: write its text to an information-object i18n
     * text field and mark the row approved. Mirrors aiSummarizeTask write-back.
     *
     * @return array{success:bool,error?:string,object_id?:int,target_field?:string}
     */
    public function approve(int $extractId, string $targetField = 'scope_and_content'): array
    {
        $textFields = ['scope_and_content', 'arrangement', 'physical_characteristics', 'archival_history', 'title'];
        $isSubjects = ($targetField === 'subject_access_points');
        if (!$isSubjects && !in_array($targetField, $textFields, true)) {
            return ['success' => false, 'error' => "Invalid target field '{$targetField}'"];
        }

        $row = DB::table('iiif_ai_extract')->where('id', $extractId)->first();
        if (!$row) {
            return ['success' => false, 'error' => 'Extraction not found'];
        }

        $io = \QubitInformationObject::getById((int) $row->object_id);
        if (!$io) {
            return ['success' => false, 'error' => 'Information object not found'];
        }

        // Subject-access-point write-back: turn a tags/entities extraction into
        // Subjects-taxonomy terms and link them to the record. Uses the Propel
        // nested-set-safe path (setTermRelationByName → QubitTerm::save computes
        // lft/rgt), so browse stays correct with no rebuild step.
        if ($isSubjects) {
            $names = $this->extractTermNames($row);
            if (empty($names)) {
                return ['success' => false, 'error' => 'No usable terms (use a tags or entities extraction)'];
            }

            // Create/find each term (Propel, nested-set-safe) then link via a
            // direct object_term_relation insert — the proven ahgAIPlugin path.
            // Deliberately NOT $io->save(): that fires arOpenSearch indexing which
            // throws when ES is down and would roll the relation back (orphan term).
            $linked = [];
            foreach ($names as $name) {
                $termId = $this->findOrCreateSubjectTerm($name);
                if ($termId !== null) {
                    $this->linkTermToObject((int) $row->object_id, $termId);
                    $linked[] = $name;
                }
            }

            if (empty($linked)) {
                return ['success' => false, 'error' => 'Failed to create or link any terms'];
            }

            DB::table('iiif_ai_extract')->where('id', $extractId)->update([
                'status' => 'approved',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return [
                'success' => true,
                'object_id' => (int) $row->object_id,
                'target_field' => $targetField,
                'terms_linked' => count($linked),
                'terms' => $linked,
                'note' => 'Subject terms link in the DB immediately; run search:populate to reindex for ES search.',
            ];
        }

        $text = trim((string) ($row->output_text ?? ''));
        if ($text === '') {
            return ['success' => false, 'error' => 'Extraction has no text to apply'];
        }

        // Qubit/Propel i18n setters are magic (__call) — call directly, as the
        // proven aiSummarizeTask write-back does (method_exists is false for them).
        // The field write persists before the post-save search-index hook fires;
        // catch \Throwable (arOpenSearch throws a TypeError when ES is unreachable
        // or in a CLI context) so an indexing hiccup never loses the approval — a
        // later `search:populate` reconciles the index.
        $setter = 'set' . str_replace('_', '', ucwords($targetField, '_'));
        $indexWarning = null;
        try {
            $io->$setter($text);
            $io->save();
        } catch (\Throwable $e) {
            $indexWarning = 'field saved; search reindex failed (' . $e->getMessage() . ')';
        }

        DB::table('iiif_ai_extract')->where('id', $extractId)->update([
            'status' => 'approved',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $out = ['success' => true, 'object_id' => (int) $row->object_id, 'target_field' => $targetField];
        if ($indexWarning !== null) {
            $out['warning'] = $indexWarning;
        }

        return $out;
    }

    /**
     * MCP tool manifest (tiny-iiif style) describing the IIIF AI Extract JSON
     * endpoints as callable tools, so an external AI client / MCP wrapper can
     * drive the manifest deterministically. Pure data — no DB access.
     *
     * @return array<string,mixed>
     */
    public function mcpToolManifest(string $baseUrl = ''): array
    {
        $base = rtrim($baseUrl, '/');
        $tasks = array_keys(self::TASK_PROMPTS);

        return [
            'name' => 'ahg-iiif-ai-extract',
            'description' => 'Region-scoped AI extraction over IIIF canvases (AtoM/Heratio ahgIiifPlugin).',
            'version' => '1.0',
            'auth' => 'Session cookie or configured auth; all tools require an authenticated user.',
            'tools' => [
                [
                    'name' => 'iiif_manifest_canvases',
                    'description' => 'List the image canvases of an information object with pixel dimensions and IIIF image base URLs.',
                    'http' => ['method' => 'GET', 'path' => '/iiif/ai/canvases/object/{object_id}', 'url' => $base . '/iiif/ai/canvases/object/{object_id}'],
                    'inputSchema' => [
                        'type' => 'object',
                        'required' => ['object_id'],
                        'properties' => [
                            'object_id' => ['type' => 'integer', 'description' => 'Information object id.'],
                        ],
                    ],
                ],
                [
                    'name' => 'iiif_region_extract',
                    'description' => 'Run an AI extraction task on a canvas region (full canvas or IIIF x,y,w,h). Vision tasks use the AI gateway; transcribe uses OCR.',
                    'http' => ['method' => 'POST', 'path' => '/iiif/ai/extract', 'url' => $base . '/iiif/ai/extract'],
                    'inputSchema' => [
                        'type' => 'object',
                        'required' => ['object_id', 'task'],
                        'properties' => [
                            'object_id' => ['type' => 'integer'],
                            'canvas_index' => ['type' => 'integer', 'default' => 0],
                            'region' => ['type' => 'string', 'default' => 'full', 'description' => "'full' or 'x,y,w,h'."],
                            'task' => ['type' => 'string', 'enum' => $tasks],
                        ],
                    ],
                ],
                [
                    'name' => 'iiif_list_extractions',
                    'description' => 'List stored AI extractions for an information object (newest first).',
                    'http' => ['method' => 'GET', 'path' => '/iiif/ai/extract/object/{object_id}', 'url' => $base . '/iiif/ai/extract/object/{object_id}'],
                    'inputSchema' => [
                        'type' => 'object',
                        'required' => ['object_id'],
                        'properties' => [
                            'object_id' => ['type' => 'integer'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Derive subject-term names from a tags/entities extraction row.
     * entities → the "text" of each JSON entity; tags (or fallback) → the
     * comma/semicolon/newline-split output text. Cleaned, deduped (CI), capped.
     *
     * @param object $row iiif_ai_extract row
     * @return array<int,string>
     */
    private function extractTermNames(object $row): array
    {
        $names = [];

        if (($row->task ?? '') === 'entities' && !empty($row->output_json)) {
            $decoded = json_decode((string) $row->output_json, true);
            if (is_array($decoded)) {
                foreach ($decoded as $e) {
                    $t = is_array($e) ? ($e['text'] ?? '') : (is_string($e) ? $e : '');
                    if ($t !== '') {
                        $names[] = (string) $t;
                    }
                }
            }
        }

        if (empty($names)) {
            foreach (preg_split('/[,;\n]+/', (string) ($row->output_text ?? '')) as $p) {
                $p = trim($p);
                if ($p !== '') {
                    $names[] = $p;
                }
            }
        }

        // Clean list markers/quotes, drop empties/over-long, dedupe case-insensitively, cap at 25.
        $clean = [];
        foreach ($names as $n) {
            $n = trim($n, " \t\"'`-•*.");
            $n = trim($n);
            if ($n === '' || mb_strlen($n) > 100) {
                continue;
            }
            $key = mb_strtolower($n);
            if (!isset($clean[$key])) {
                $clean[$key] = $n;
            }
            if (count($clean) >= 25) {
                break;
            }
        }

        return array_values($clean);
    }

    /**
     * Find (case-insensitive) or create a term in the Subjects taxonomy.
     * Creation uses Propel QubitTerm::save() so lft/rgt (nested set) is computed
     * — browse stays correct with no rebuild step.
     */
    private function findOrCreateSubjectTerm(string $name): ?int
    {
        $find = function () use ($name) {
            return DB::table('term')
                ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
                ->where('term.taxonomy_id', \QubitTaxonomy::SUBJECT_ID)
                ->whereRaw('LOWER(term_i18n.name) = ?', [mb_strtolower($name)])
                ->value('term.id');
        };

        $existing = $find();
        if ($existing) {
            return (int) $existing;
        }

        // QubitTerm::save() inserts the term (with nested set) BEFORE its post-save
        // search-index hook, which throws a TypeError when ES is unreachable (CLI).
        // So swallow the throw and resolve the id by re-querying — the row persists.
        try {
            $term = new \QubitTerm();
            $term->setTaxonomyId(\QubitTaxonomy::SUBJECT_ID);
            $term->setName($name);
            $term->setRoot();
            $term->save();
        } catch (\Throwable $e) {
            // fall through to re-query
        }

        $id = $find();

        return $id ? (int) $id : null;
    }

    /**
     * Link a term to an object as an access point via a direct object_term_relation
     * insert (dedup-guarded). Mirrors ahgAIPlugin's link helper; no IO save / no ES.
     */
    private function linkTermToObject(int $objectId, int $termId): void
    {
        $exists = DB::table('object_term_relation')
            ->where('object_id', $objectId)
            ->where('term_id', $termId)
            ->exists();
        if ($exists) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $relId = DB::table('object')->insertGetId([
            'class_name' => 'QubitObjectTermRelation',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('object_term_relation')->insert([
            'id' => $relId,
            'object_id' => $objectId,
            'term_id' => $termId,
        ]);
    }

    /** Reject a draft extraction. */
    public function reject(int $extractId): bool
    {
        return DB::table('iiif_ai_extract')->where('id', $extractId)->update([
            'status' => 'rejected',
            'updated_at' => date('Y-m-d H:i:s'),
        ]) > 0;
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

    /** Local Tesseract OCR on region bytes — the accurate transcribe path (no gateway). */
    private function ocrRegion(string $bytes): ?string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'iiifocr_');
        if ($tmp === false) {
            return null;
        }
        $img = $tmp . '.jpg';
        @rename($tmp, $img);
        file_put_contents($img, $bytes);
        $out = shell_exec('tesseract ' . escapeshellarg($img) . ' stdout 2>/dev/null');
        @unlink($img);

        $text = trim((string) $out);

        return $text !== '' ? $text : null;
    }

    /**
     * Public browser IIIF URL for a region preview, sized so it never upscales.
     * Uses the public Cantaloupe path (default /iiif/2), resolved against the host.
     */
    public function previewUrl(array $canvas, string $region): string
    {
        $base = rtrim((string) \sfConfig::get('app_iiif_cantaloupe_url', '/iiif/2'), '/');
        $region = $this->normaliseRegion($region);
        if ($region === 'full') {
            $w = (int) ($canvas['width'] ?? 0);
        } else {
            $parts = explode(',', $region);
            $w = (int) ($parts[2] ?? 0);
        }
        $size = ($w > 512) ? '512,' : 'max';

        return "{$base}/{$canvas['cantaloupe_id']}/{$region}/{$size}/0/default.jpg";
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
