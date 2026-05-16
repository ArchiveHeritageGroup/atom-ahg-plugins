<?php

/**
 * ResearchStudioService - grounded AI artefact generator.
 *
 * Spec: docs/atom-heratio-research-enhancements-spec.md §1.1 - §1.3
 *
 * Eight output types: briefing, study_guide, faq, timeline, diagram (Mermaid),
 * video_script, spreadsheet, audio.
 *
 * The system prompt is invariant: archival-research assistant, must cite [N]
 * only from supplied sources, no invention. The user prompt embeds the source
 * block (built from research_collection_item joined to information_object_i18n)
 * plus the output-type-specific instruction.
 *
 * Spreadsheet generation is two-step: LLM is asked for strict JSON, we then
 * write the .xlsx via PhpSpreadsheet locally.
 *
 * Audio generation: LLM writes a two-voice script; if app_ahg_tts_endpoint is
 * configured, the script is POSTed and the returned mp3 URL is persisted.
 * Otherwise the artefact lands in status='error' but the transcript is kept
 * so operators can hand it to TTS manually.
 */

use Illuminate\Database\Capsule\Manager as DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ResearchStudioService
{
    public const OUTPUT_TYPES = [
        'briefing'     => ['label' => 'Briefing document',  'format' => 'markdown'],
        'study_guide'  => ['label' => 'Study guide',        'format' => 'markdown'],
        'faq'          => ['label' => 'FAQ',                'format' => 'markdown'],
        'timeline'     => ['label' => 'Timeline',           'format' => 'markdown'],
        'diagram'      => ['label' => 'Diagram (Mermaid)',  'format' => 'mermaid'],
        'video_script' => ['label' => 'Video script',       'format' => 'markdown'],
        'spreadsheet'  => ['label' => 'Spreadsheet (.xlsx)', 'format' => 'json'],
        'audio'        => ['label' => 'Audio (TTS)',        'format' => 'markdown'],
    ];

    /**
     * Generate an artefact synchronously. Returns the new artefact id.
     */
    public function generate(
        int $projectId,
        array $sourceObjectIds,
        string $outputType,
        array $options = [],
        ?int $createdBy = null
    ): int {
        if (!isset(self::OUTPUT_TYPES[$outputType])) {
            throw new \InvalidArgumentException("Unknown output type: {$outputType}");
        }
        if (count($sourceObjectIds) === 0) {
            throw new \InvalidArgumentException('At least one source is required');
        }

        // Insert the artefact row first so we have an id to write files against.
        $artefactId = DB::table('research_studio_artefact')->insertGetId([
            'project_id'        => $projectId,
            'created_by'        => $createdBy,
            'output_type'       => $outputType,
            'body_format'       => self::OUTPUT_TYPES[$outputType]['format'],
            'source_object_ids' => json_encode(array_values($sourceObjectIds)),
            'status'            => 'generating',
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        try {
            $startedAt = microtime(true);

            $sources = $this->loadSources($sourceObjectIds);
            $sourceBlock = $this->formatSourceBlock($sources);

            [$systemPrompt, $userPrompt] = $this->promptFor($outputType, $sourceBlock, $options);

            $llm = $this->loadLlmService();
            $result = $llm->complete(
                $systemPrompt,
                $userPrompt,
                $options['config_id'] ?? null,
                [
                    'temperature' => $options['temperature'] ?? 0.3,
                    'max_tokens'  => $options['max_tokens']  ?? 4000,
                ]
            );

            if (empty($result['success'])) {
                throw new \RuntimeException('LLM call failed: ' . ($result['error'] ?? 'unknown error'));
            }

            $body = $result['text'] ?? '';
            $citations = $this->extractCitations($body, $sources);
            $update = [
                'body'               => $body,
                'citations'          => json_encode($citations),
                'model'              => $result['model']       ?? null,
                'tokens_used'        => $result['tokens_used'] ?? 0,
                'generation_time_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'status'             => 'ready',
                'updated_at'         => date('Y-m-d H:i:s'),
            ];

            $title = $this->extractTitle($body) ?? (self::OUTPUT_TYPES[$outputType]['label'] . ' - ' . date('j M Y'));
            $update['title'] = mb_substr($title, 0, 500);

            // Type-specific post-processing
            if ($outputType === 'spreadsheet') {
                $update = array_merge($update, $this->postProcessSpreadsheet($artefactId, $projectId, $body));
            }
            if ($outputType === 'audio') {
                $update = array_merge($update, $this->postProcessAudio($artefactId, $projectId, $body));
            }

            DB::table('research_studio_artefact')->where('id', $artefactId)->update($update);

            // Activity log (§2.2 instrumentation)
            $this->logActivity($createdBy, $projectId, $outputType, $artefactId, $sources);
        } catch (\Throwable $e) {
            DB::table('research_studio_artefact')->where('id', $artefactId)->update([
                'status'     => 'error',
                'error_text' => $e->getMessage() . "\n" . $e->getTraceAsString(),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            throw $e;
        }

        return $artefactId;
    }

    /**
     * Source pool for a project's Studio form: collection items belonging to
     * any collection under the project, joined to title/identifier/slug.
     */
    public function sourcePool(int $projectId): array
    {
        $culture = $this->culture();

        return DB::table('research_collection_item as rci')
            ->join('research_collection as rc', 'rci.collection_id', '=', 'rc.id')
            ->leftJoin('information_object as io', 'rci.object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) use ($culture) {
                $join->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->where('rc.project_id', $projectId)
            ->select(
                'rci.object_id',
                'rc.name as collection_name',
                'ioi.title',
                'io.identifier',
                'slug.slug'
            )
            ->orderBy('rc.name')
            ->orderBy('ioi.title')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    public function listForProject(int $projectId, int $limit = 50): array
    {
        return DB::table('research_studio_artefact')
            ->where('project_id', $projectId)
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    public function get(int $artefactId): ?object
    {
        return DB::table('research_studio_artefact')->where('id', $artefactId)->first();
    }

    public function delete(int $artefactId): bool
    {
        $row = $this->get($artefactId);
        if (!$row) {
            return false;
        }
        if (!empty($row->xlsx_path) && is_file($row->xlsx_path)) {
            @unlink($row->xlsx_path);
        }
        return (bool) DB::table('research_studio_artefact')->where('id', $artefactId)->delete();
    }

    // =========================================================================
    // PROMPT TEMPLATES
    // =========================================================================

    protected function promptFor(string $outputType, string $sourceBlock, array $options): array
    {
        $systemPrompt = <<<SYS
You are an expert archival-research assistant working with the records held by The Archive and Heritage Group. Your output must be GROUNDED in the supplied source records. Cite each factual claim using [N] markers, where N matches the source number in the SOURCES block. NEVER invent facts, people, dates, or quotes. If the sources do not support a claim, say so explicitly. Output should be in clear, neutral, archival prose unless the requested output type specifies otherwise.
SYS;

        $userIntro = "SOURCES:\n{$sourceBlock}\n\n";

        $typeInstructions = [
            'briefing' => <<<TXT
Write a briefing document for a researcher. 400-700 words. Markdown formatting (## headings, bullet lists). Sections: ## Overview, ## Key facts, ## People & organisations, ## Themes, ## Open questions. Cite every factual claim with [N] markers.
TXT,
            'study_guide' => <<<TXT
Write a study guide for a graduate-level student. 600-1000 words. Markdown formatting. Sections: ## Background context, ## Key terms, ## Reading guide (numbered, one per source), ## Discussion questions (5), ## Further research. Cite every reference with [N].
TXT,
            'faq' => <<<TXT
Write a FAQ document. 6-10 Q&A pairs. Markdown formatting: each question as a heading "### Q: ...", answer as a paragraph below citing [N] markers. Questions should be the kind a researcher actually asks when first seeing this collection.
TXT,
            'timeline' => <<<TXT
Write a chronological timeline based on the sources. Markdown formatting. Each entry as: "**YYYY-MM-DD** - Event description [N]." If only a year is known, use **YYYY**. Order chronologically. End with a one-paragraph synthesis citing the timeline's gaps and density.
TXT,
            'diagram' => <<<TXT
Produce a Mermaid diagram showing the relationships between the entities in the sources. Use Mermaid graph syntax. Wrap the diagram in ```mermaid ... ``` fences. Below the diagram, add a "## Legend" section explaining each node's relationship to a source, with [N] citations.
TXT,
            'video_script' => <<<TXT
Write a 2-3 minute video script. Two voices: HOST (the curator) and EXPERT (the historian). Format as alternating speaker lines: "HOST: ..." / "EXPERT: ...". Keep sentences short and spoken-friendly. Cite source numbers in brackets after factual claims.
TXT,
            'spreadsheet' => <<<TXT
Return a STRICT JSON object describing a tabular extraction of the sources. The JSON must have this exact shape and nothing else (no markdown, no commentary, no fences):
{
  "header": "Short title for the spreadsheet",
  "intro":  "1-2 sentence description",
  "columns": ["Column 1 name", "Column 2 name", ...],
  "rows": [
    ["row1col1", "row1col2", ...],
    ...
  ]
}
Decide the columns based on what the sources contain (Title, Date, Place, Person, Type, Reference code, etc.). Add a final column called "Source" containing the [N] reference for that row.
TXT,
            'audio' => <<<TXT
Write a two-voice podcast-style audio script for a 2-3 minute spoken piece. Two speakers: "HOST" and "CURATOR". Format as alternating speaker lines: "HOST: ..." / "CURATOR: ...". Keep sentences short and spoken-friendly. Cite source numbers in brackets after factual claims. Add a short "[INTRO MUSIC]" / "[OUTRO MUSIC]" stage direction at the top and bottom.
TXT,
        ];

        $userPrompt = $userIntro . ($typeInstructions[$outputType] ?? $typeInstructions['briefing']);
        return [$systemPrompt, $userPrompt];
    }

    // =========================================================================
    // SOURCE LOADING
    // =========================================================================

    protected function loadSources(array $objectIds): array
    {
        $culture = $this->culture();
        $rows = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($join) use ($culture) {
                $join->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->whereIn('io.id', $objectIds)
            ->select(
                'io.id',
                'io.identifier',
                'ioi.title',
                'ioi.scope_and_content',
                'ioi.extent_and_medium',
                'slug.slug'
            )
            ->get();

        $byId = [];
        foreach ($rows as $r) {
            $byId[(int) $r->id] = $r;
        }

        // Preserve the order the user supplied
        $ordered = [];
        $n = 1;
        foreach ($objectIds as $id) {
            $id = (int) $id;
            if (!isset($byId[$id])) {
                continue;
            }
            $row = (array) $byId[$id];
            $row['n'] = $n++;
            $ordered[] = $row;
        }
        return $ordered;
    }

    protected function formatSourceBlock(array $sources): string
    {
        $lines = [];
        foreach ($sources as $s) {
            $title  = $s['title']      ?: 'Untitled record';
            $ref    = $s['identifier'] ?: ('ID ' . $s['id']);
            $body   = $s['scope_and_content'] ?: $s['extent_and_medium'] ?: '';
            $body   = $this->trim(strip_tags((string) $body), 800);
            $lines[] = "[{$s['n']}] {$title} ({$ref})\n    " . $body;
        }
        return implode("\n\n", $lines);
    }

    /**
     * Build the citations[] array used by the popover UI: one entry per [N]
     * marker that actually appears in the body, with a 220-char snippet.
     */
    protected function extractCitations(string $body, array $sources): array
    {
        $byN = [];
        foreach ($sources as $s) {
            $byN[$s['n']] = $s;
        }

        // Find every [N] marker in the body
        preg_match_all('/\[(\d{1,3})\]/', $body, $matches);
        $seen = array_unique(array_map('intval', $matches[1] ?? []));
        sort($seen);

        $citations = [];
        foreach ($seen as $n) {
            if (!isset($byN[$n])) {
                continue;
            }
            $s = $byN[$n];
            $snippet = $this->trim(strip_tags((string) ($s['scope_and_content'] ?: $s['extent_and_medium'] ?: '')), 220);
            $citations[] = [
                'n'         => $n,
                'object_id' => (int) $s['id'],
                'title'     => (string) ($s['title'] ?: 'Untitled record'),
                'reference' => (string) ($s['identifier'] ?: ''),
                'snippet'   => $snippet,
                'url'       => $s['slug'] ? ('/index.php/' . $s['slug']) : null,
            ];
        }
        return $citations;
    }

    protected function extractTitle(string $body): ?string
    {
        // First markdown H1/H2 line
        if (preg_match('/^#+\s*(.+)$/m', $body, $m)) {
            return trim($m[1]);
        }
        // Otherwise first non-empty line
        foreach (preg_split('/\R/', $body) as $line) {
            $line = trim($line);
            if ($line !== '') {
                return mb_substr($line, 0, 200);
            }
        }
        return null;
    }

    // =========================================================================
    // POST-PROCESSING - SPREADSHEET
    // =========================================================================

    protected function postProcessSpreadsheet(int $artefactId, int $projectId, string $body): array
    {
        $json = $this->extractJson($body);
        if (!is_array($json) || !isset($json['columns'], $json['rows'])) {
            return [
                'status'     => 'error',
                'error_text' => 'LLM did not return a valid spreadsheet JSON structure',
            ];
        }

        if (!class_exists(Spreadsheet::class)) {
            return [
                'status'     => 'error',
                'error_text' => 'PhpSpreadsheet is not installed',
            ];
        }

        $dir = sfConfig::get('sf_upload_dir') . '/research-studio/' . $projectId;
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $path = $dir . '/artefact-' . $artefactId . '.xlsx';

        $sheet = new Spreadsheet();
        $ws = $sheet->getActiveSheet();
        $ws->setTitle(mb_substr((string) ($json['header'] ?? 'Studio'), 0, 31));

        $rowIdx = 1;
        if (!empty($json['intro'])) {
            $ws->setCellValue('A' . $rowIdx, (string) $json['intro']);
            $ws->getStyle('A' . $rowIdx)->getFont()->setBold(true);
            $rowIdx += 2;
        }

        // Header row
        $col = 1;
        foreach ((array) $json['columns'] as $h) {
            $ws->setCellValueByColumnAndRow($col++, $rowIdx, (string) $h);
        }
        $ws->getStyle($this->cellRange(1, $rowIdx, count($json['columns']), $rowIdx))->getFont()->setBold(true);
        $rowIdx++;

        // Data rows
        foreach ((array) $json['rows'] as $row) {
            $col = 1;
            foreach ((array) $row as $val) {
                $ws->setCellValueByColumnAndRow($col++, $rowIdx, (string) $val);
            }
            $rowIdx++;
        }

        // Auto-size columns
        for ($i = 1; $i <= count($json['columns']); $i++) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $ws->getColumnDimension($letter)->setAutoSize(true);
        }

        (new Xlsx($sheet))->save($path);

        return ['xlsx_path' => $path];
    }

    protected function extractJson(string $body): ?array
    {
        // Strip ```json fences if present
        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/sU', $body, $m)) {
            $body = $m[1];
        }
        $body = trim($body);
        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : null;
    }

    protected function cellRange(int $col1, int $row1, int $col2, int $row2): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col1) . $row1
             . ':' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col2) . $row2;
    }

    // =========================================================================
    // POST-PROCESSING - AUDIO
    // =========================================================================

    protected function postProcessAudio(int $artefactId, int $projectId, string $body): array
    {
        $update = ['audio_transcript' => $body];

        $endpoint = sfConfig::get('app_ahg_tts_endpoint');
        $key      = sfConfig::get('app_ahg_tts_key');

        if (!$endpoint) {
            // No TTS configured - persist transcript, surface a clear error
            $update['status']     = 'error';
            $update['error_text'] = 'TTS endpoint is not configured (app_ahg_tts_endpoint). Transcript persisted for manual hand-off.';
            return $update;
        }

        $payload = json_encode(['script' => $body, 'artefact_id' => $artefactId]);
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => array_filter([
                'Content-Type: application/json',
                $key ? ('Authorization: Bearer ' . $key) : null,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            $update['status']     = 'error';
            $update['error_text'] = "TTS endpoint returned HTTP {$httpCode}";
            return $update;
        }

        $data = json_decode((string) $response, true);
        if (!is_array($data) || empty($data['audio_url'])) {
            $update['status']     = 'error';
            $update['error_text'] = 'TTS endpoint did not return an audio_url';
            return $update;
        }

        $update['audio_url']              = (string) $data['audio_url'];
        $update['audio_duration_seconds'] = isset($data['duration_seconds']) ? (int) $data['duration_seconds'] : null;
        return $update;
    }

    // =========================================================================
    // PLUMBING
    // =========================================================================

    protected function loadLlmService(): object
    {
        $path = sfConfig::get('sf_plugins_dir') . '/ahgAIPlugin/lib/Services/LlmService.php';
        if (!class_exists('LlmService') && is_file($path)) {
            require_once $path;
        }
        if (!class_exists('LlmService')) {
            throw new \RuntimeException('LlmService unavailable (ahgAIPlugin not installed)');
        }
        return new \LlmService();
    }

    protected function culture(): string
    {
        if (class_exists('\\AtomExtensions\\Helpers\\CultureHelper')) {
            return \AtomExtensions\Helpers\CultureHelper::getCulture();
        }
        return class_exists('\\sfContext') ? \sfContext::getInstance()->getUser()->getCulture() : 'en';
    }

    protected function trim(string $s, int $max): string
    {
        $s = trim(preg_replace('/\s+/', ' ', $s));
        return mb_strlen($s) <= $max ? $s : mb_substr($s, 0, $max - 1) . '…';
    }

    protected function logActivity(?int $researcherId, int $projectId, string $outputType, int $artefactId, array $sources): void
    {
        if (!$researcherId) {
            return;
        }
        try {
            DB::table('research_activity_log')->insert([
                'researcher_id' => $researcherId,
                'project_id'    => $projectId,
                'activity_type' => 'ai_studio',
                'entity_type'   => 'studio_artefact',
                'entity_id'     => $artefactId,
                'entity_title'  => $outputType,
                'details'       => json_encode([
                    'output_type'   => $outputType,
                    'source_count'  => count($sources),
                ]),
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // non-fatal
        }
    }
}
