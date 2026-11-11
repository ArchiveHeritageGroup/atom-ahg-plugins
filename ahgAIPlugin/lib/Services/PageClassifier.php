<?php

/**
 * PageClassifier - document type and privacy screening per page (or per window
 * of N words) of a record's PDF, rolled up to the record, so one label for the
 * file does not hide what a mixed file contains (catalogue#320, the PSIS twin of
 * heratio#1584).
 *
 * Each page result is an ahg_ai_inference row (signed by ahgProvenancePlugin,
 * with model and input digest) whose target_field is "page:{from}-{to}:privacy"
 * or "page:{from}-{to}:doctype". A run starts with a "pages:run" row; the
 * segments of a record are the rows after its latest run row. A reviewer's
 * correction is an ahg_ai_override event on the page row; the AI result is kept.
 *
 * Privacy is the deterministic PII scan (ahgPrivacyPlugin) of the page text. The
 * roll-up is the maximum, not the mean: one flagged page flags the record, and
 * the flagged pages are saved as a PII scan for the privacy review queue. The
 * document type comes from the LLM through the AHG AI gateway, chosen from the
 * Dropdown Manager list page_document_type, and only by day (GPUs are off
 * 18:00-06:00 SAST); at night the privacy screen still runs.
 *
 * Page text is never stored in the provenance rows (it holds the PII being
 * screened): the excerpt names the pages and the input is a sha256 digest.
 *
 * ponytail: page text comes from pdftotext; a scanned page with no text layer
 * is reported as "no text to read", not OCR'd. Add OCR here if scanned files
 * need screening.
 */

namespace AhgAIPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

class PageClassifier
{
    public const TAXONOMY = 'page_document_type';

    /** A weak or unvalidated finding (a date with no "born" near it) may not flag a page. */
    public const MIN_CONFIDENCE = 0.5;

    /** Gateway model for the document type (setting ai_page_model). */
    public const DEFAULT_MODEL = 'qwen3:8b';

    /** Used only when the list has no rows yet (database/seed_page_document_type.sql). */
    private const FALLBACK_TYPES = [
        'letter' => 'Letter', 'form' => 'Form', 'invoice' => 'Invoice', 'purchase_order' => 'Purchase order',
        'delivery_note' => 'Delivery note', 'medical_record' => 'Medical record', 'identity_document' => 'Identity document',
        'report' => 'Report', 'minutes' => 'Minutes', 'newspaper_cutting' => 'Newspaper cutting',
        'photograph' => 'Photograph', 'other' => 'Other',
    ];

    private static function plugins(): string
    {
        return dirname(__DIR__, 3);
    }

    /** @return array<string,string> code => label */
    public static function types(): array
    {
        try {
            $rows = DB::table('ahg_dropdown')->where('taxonomy', self::TAXONOMY)->where('is_active', 1)
                ->orderBy('sort_order')->pluck('label', 'code')->all();
        } catch (\Throwable $e) {
            $rows = [];
        }

        return $rows ?: self::FALLBACK_TYPES;
    }

    public static function setting(string $key, string $default = ''): string
    {
        $v = DB::table('ahg_settings')->where('setting_key', $key)->value('setting_value');

        return null === $v ? $default : (string) $v;
    }

    /**
     * Should an ingested record be read page by page? ai_page_classify_on_ingest
     * (default off), limited to the collections in ai_page_classify_collections
     * (comma-separated record ids; empty = every collection).
     */
    public static function wantsOnIngest(int $ioId): bool
    {
        if ('1' !== self::setting('ai_page_classify_on_ingest', '0')) {
            return false;
        }
        $roots = array_filter(array_map('intval', explode(',', self::setting('ai_page_classify_collections'))));
        if (!$roots) {
            return true;
        }
        $node = DB::table('information_object')->where('id', $ioId)->first(['lft', 'rgt', 'parent_id']);
        if (!$node) {
            return false;
        }
        if ($node->lft) {
            $ancestors = DB::table('information_object')->where('lft', '<=', $node->lft)->where('rgt', '>=', $node->rgt)->pluck('id')->all();
        } else {
            // Not placed in the nested set yet: walk the parents.
            $ancestors = [$ioId];
            for ($p = (int) $node->parent_id, $i = 0; $p > 1 && $i < 50; $i++) {
                $ancestors[] = $p;
                $p = (int) DB::table('information_object')->where('id', $p)->value('parent_id');
            }
        }

        return (bool) array_intersect($roots, array_map('intval', $ancestors));
    }

    /** May the GPU-backed step run now? 06:00-18:00 SAST only. */
    public static function gpuHours(?\DateTimeInterface $at = null): bool
    {
        $d = new \DateTime(($at ?? new \DateTime())->format('c'));
        $d->setTimezone(new \DateTimeZone('Africa/Johannesburg'));
        $h = (int) $d->format('G');

        return $h >= 6 && $h < 18;
    }

    /** The record's master PDF on disk, or null. */
    public static function masterPdf(int $ioId): ?object
    {
        $do = DB::table('digital_object')->where('object_id', $ioId)->whereNull('parent_id')
            ->where('mime_type', 'application/pdf')->orderBy('id')->first(['id', 'path', 'name']);
        if (!$do) {
            return null;
        }
        $abs = dirname(self::plugins()) . rtrim((string) $do->path, '/') . '/' . $do->name;

        return is_file($abs) ? (object) ['abs' => $abs, 'url' => rtrim((string) $do->path, '/') . '/' . $do->name] : null;
    }

    /** @return list<array{page:int,text:string}>|null null when there is no PDF */
    public static function pageTexts(int $ioId): ?array
    {
        $pdf = self::masterPdf($ioId);
        if (!$pdf) {
            return null;
        }
        $info = (string) shell_exec('pdfinfo ' . escapeshellarg($pdf->abs) . ' 2>/dev/null');
        $n = preg_match('/^Pages:\s+(\d+)/m', $info, $m) ? (int) $m[1] : 0;
        $out = [];
        for ($i = 1; $i <= $n; $i++) {
            $text = (string) shell_exec(sprintf('pdftotext -f %d -l %d -layout %s - 2>/dev/null', $i, $i, escapeshellarg($pdf->abs)));
            $out[] = ['page' => $i, 'text' => $text];
        }

        return $out;
    }

    /**
     * Classify and screen every page (or window) of a record's PDF.
     *
     * @return array{units:int, flagged:list<string>, doctype:bool, extraction_id:?int, error?:string}
     */
    public function run(int $ioId, ?int $userId = null, ?bool $withDoctype = null): array
    {
        $pages = self::pageTexts($ioId);
        if (null === $pages) {
            return ['units' => 0, 'flagged' => [], 'doctype' => false, 'extraction_id' => null, 'error' => 'This record has no PDF master file.'];
        }
        $window = max(0, (int) self::setting('ai_page_window_words', '0'));
        $units = self::units($pages, $window);
        $withDoctype ??= self::gpuHours();
        $types = self::types();
        $pii = $this->pii();

        $this->record($ioId, 'pages:run', 'PAGES', 'psis-page-classifier', '1', json_encode(['window' => $window, 'units' => count($units), 'doctype' => $withDoctype]), 'pages 1-' . max(1, count($pages)), null, $userId);

        $flagged = [];
        $flaggedText = [];
        foreach ($units as $u) {
            $range = $u['from'] . '-' . $u['to'];
            if ('' === trim($u['text'])) {
                // No text: not "clear" - nobody has read it.
                $this->record($ioId, "page:{$range}:privacy", 'PII', 'pii-regex', 'unknown', json_encode(['label' => 'unread', 'types' => []]), "pages {$range}", null, $userId, '');
                continue;
            }
            $findings = self::strong($pii->detectPii($u['text'])['entities'] ?? []);
            $kinds = array_values(array_unique(array_column($findings, 'type')));
            $this->record($ioId, "page:{$range}:privacy", 'PII', 'pii-regex', 'unknown',
                json_encode(['label' => $findings ? 'flagged' : 'clear', 'types' => $kinds]), "pages {$range}",
                $findings ? (float) max(array_column($findings, 'confidence')) : 1.0, $userId, $u['text']);
            if ($findings) {
                $flagged[] = $range;
                $flaggedText[] = $u['text'];
            }

            if ($withDoctype) {
                $model = null;
                $code = $this->doctype($u['text'], $types, $model);
                if (null !== $code) {
                    $this->record($ioId, "page:{$range}:doctype", 'LLM', $model ?: 'unknown', 'unknown', json_encode(['label' => $code]), "pages {$range}", null, $userId, $u['text']);
                }
            }
        }

        // The record-level flag: a PII scan of the flagged pages only, into the
        // privacy review queue (masked values; high-risk findings flagged).
        $extractionId = null;
        if ($flaggedText) {
            $res = $pii->detectPii(implode("\n\n", $flaggedText));
            $res['entities'] = self::strong($res['entities'] ?? []);
            $summary = \ahgPrivacyPlugin\Service\PiiDetectionService::emptySummary();
            foreach ($res['entities'] as $e) {
                $summary = \ahgPrivacyPlugin\Service\PiiDetectionService::countEntity($summary, $e['risk_level']);
            }
            $res['summary'] = $summary;
            $res['categories'] = array_count_values(array_column($res['entities'], 'category'));
            $extractionId = $pii->saveScanResults($ioId, $res, $userId);
        }

        return ['units' => count($units), 'flagged' => $flagged, 'doctype' => $withDoctype, 'extraction_id' => $extractionId];
    }

    private static function strong(array $entities): array
    {
        return array_values(array_filter($entities, fn ($f) => ($f['confidence'] ?? 0) >= self::MIN_CONFIDENCE && ($f['validated'] ?? null) !== false));
    }

    /**
     * Pages as units, or windows of $window words across pages.
     *
     * @param list<array{page:int,text:string}> $pages
     *
     * @return list<array{from:int,to:int,text:string}>
     */
    public static function units(array $pages, int $window): array
    {
        if ($window <= 0) {
            return array_map(fn ($p) => ['from' => (int) $p['page'], 'to' => (int) $p['page'], 'text' => (string) $p['text']], $pages);
        }
        $out = [];
        $buf = [];
        $from = null;
        $to = null;
        foreach ($pages as $p) {
            $words = preg_split('/\s+/u', trim((string) $p['text']), -1, PREG_SPLIT_NO_EMPTY);
            if (!$words) {
                // An empty page stays its own unit, so it shows as unread.
                if ($buf) {
                    $out[] = ['from' => $from, 'to' => $to, 'text' => implode(' ', $buf)];
                    $buf = [];
                    $from = null;
                }
                $out[] = ['from' => (int) $p['page'], 'to' => (int) $p['page'], 'text' => ''];
                continue;
            }
            foreach ($words as $w) {
                $from ??= (int) $p['page'];
                $to = (int) $p['page'];
                $buf[] = $w;
                if (count($buf) >= $window) {
                    $out[] = ['from' => $from, 'to' => $to, 'text' => implode(' ', $buf)];
                    $buf = [];
                    $from = null;
                }
            }
        }
        if ($buf) {
            $out[] = ['from' => $from, 'to' => $to, 'text' => implode(' ', $buf)];
        }

        return $out;
    }

    /**
     * The segments of the latest run, each with its effective (reviewed) value.
     *
     * @return list<array{from:int,to:int,privacy:?array,doctype:?array}>
     */
    public function segments(int $ioId): array
    {
        $runId = (int) DB::table('ahg_ai_inference')->where('target_entity_type', 'information_object')
            ->where('target_entity_id', $ioId)->where('target_field', 'pages:run')->max('id');
        if (0 === $runId) {
            return [];
        }
        $rows = DB::table('ahg_ai_inference')->where('target_entity_type', 'information_object')
            ->where('target_entity_id', $ioId)->where('id', '>', $runId)->where('target_field', 'like', 'page:%')
            ->orderBy('id')->get(['id', 'target_field', 'output_excerpt', 'model_name', 'confidence']);
        $overrides = $rows->isEmpty() ? collect() : DB::table('ahg_ai_override')->whereIn('inference_id', $rows->pluck('id')->all())
            ->where('status', 'applied')->orderBy('id')->get(['inference_id', 'override_value'])->keyBy('inference_id');

        $seg = [];
        foreach ($rows as $r) {
            if (!preg_match('/^page:(\d+)-(\d+):(privacy|doctype)$/', $r->target_field, $m)) {
                continue;
            }
            $out = json_decode((string) $r->output_excerpt, true) ?: [];
            $ov = isset($overrides[$r->id]) ? $overrides[$r->id]->override_value : null;
            $k = $m[1] . '-' . $m[2];
            $seg[$k]['from'] = (int) $m[1];
            $seg[$k]['to'] = (int) $m[2];
            $seg[$k][$m[3]] = [
                'id' => (int) $r->id, 'label' => $ov ?? ($out['label'] ?? null), 'types' => $out['types'] ?? [],
                'model' => $r->model_name, 'confidence' => $r->confidence, 'original' => $out['label'] ?? null, 'overridden' => null !== $ov,
            ];
        }

        return array_values(array_map(fn ($s) => $s + ['privacy' => null, 'doctype' => null], $seg));
    }

    /**
     * The record-level result: privacy is the maximum (any flagged page flags the
     * record; an unread page makes it incomplete), the type is a spread.
     *
     * @return array{privacy:string, flagged:list<string>, unread:list<string>, pages:int, spread:array<string,int>}
     */
    public static function rollup(array $segments): array
    {
        $pages = fn ($s) => $s['to'] - $s['from'] + 1;
        $flagged = $unread = [];
        $spread = [];
        $total = 0;
        foreach ($segments as $s) {
            $total += $pages($s);
            $p = $s['privacy']['label'] ?? null;
            $r = $s['from'] === $s['to'] ? (string) $s['from'] : $s['from'] . '-' . $s['to'];
            if ('flagged' === $p) {
                $flagged[] = $r;
            } elseif ('unread' === $p || null === $p) {
                $unread[] = $r;
            }
            if ($t = $s['doctype']['label'] ?? null) {
                $spread[$t] = ($spread[$t] ?? 0) + $pages($s);
            }
        }
        arsort($spread);

        return ['privacy' => $flagged ? 'flagged' : ($unread ? 'incomplete' : 'clear'), 'flagged' => $flagged, 'unread' => $unread, 'pages' => $total, 'spread' => $spread];
    }

    /** A reviewer accepts or corrects one page result; recorded as an override event. */
    public function review(int $inferenceId, string $value, int $userId, ?string $reason = null): void
    {
        $row = DB::table('ahg_ai_inference')->where('id', $inferenceId)->first(['target_field', 'output_excerpt']);
        if (!$row || !preg_match('/^page:\d+-\d+:(privacy|doctype)$/', $row->target_field, $m)) {
            throw new \InvalidArgumentException('Not a page result.');
        }
        $allowed = 'privacy' === $m[1] ? ['flagged', 'clear'] : array_keys(self::types());
        if (!in_array($value, $allowed, true)) {
            throw new \InvalidArgumentException('Not a valid value for this page result.');
        }
        $original = (string) ((json_decode((string) $row->output_excerpt, true) ?: [])['label'] ?? '');
        $this->provenance();
        (new \AhgProvenancePlugin\Service\OverrideService())->record($inferenceId, $original, $value, $userId, $reason);
    }

    private function doctype(string $text, array $types, ?string &$model): ?string
    {
        $model = null;
        if (!class_exists('LlmService')) {
            require_once __DIR__ . '/LlmService.php';
        }
        $list = implode("\n", array_map(fn ($c, $l) => "{$c} - {$l}", array_keys($types), $types));
        $res = (new \LlmService())->complete(
            "You classify one page of an archival file. Answer with exactly one code from this list and nothing else:\n{$list}",
            mb_substr($text, 0, 6000), null,
            // qwen3:8b got every clear page of the test file right where the
            // default mistral:7b got half; thinking off, or it spends the 20
            // tokens on a hidden reasoning pass and answers nothing.
            ['temperature' => 0, 'max_tokens' => 20, 'think' => false, 'model' => self::setting('ai_page_model', self::DEFAULT_MODEL)]
        );
        if (empty($res['success'])) {
            return null;
        }
        $model = $res['model'] ?? null;
        $answer = mb_strtolower((string) $res['text']);
        foreach (array_keys($types) as $code) {
            if (preg_match('/\b' . preg_quote((string) $code, '/') . '\b/', $answer)) {
                return (string) $code;
            }
        }

        return isset($types['other']) ? 'other' : null;
    }

    private function pii(): \ahgPrivacyPlugin\Service\PiiDetectionService
    {
        if (!class_exists('ahgPrivacyPlugin\\Service\\PiiDetectionService')) {
            require_once self::plugins() . '/ahgPrivacyPlugin/lib/Service/PiiDetectionService.php';
        }

        return new \ahgPrivacyPlugin\Service\PiiDetectionService();
    }

    private function provenance(): void
    {
        foreach (['InferenceRecord', 'InferenceSigner', 'InferenceService', 'OverrideService'] as $c) {
            if (!class_exists('AhgProvenancePlugin\\Service\\' . $c)) {
                require_once self::plugins() . '/ahgProvenancePlugin/lib/Service/' . $c . '.php';
            }
        }
    }

    private function record(int $ioId, string $field, string $service, string $model, string $version, string $output, string $excerpt, ?float $confidence, ?int $userId, ?string $input = null): void
    {
        $this->provenance();
        (new \AhgProvenancePlugin\Service\InferenceService())->record(new \AhgProvenancePlugin\Service\InferenceRecord(
            serviceName: $service,
            modelName: $model,
            modelVersion: $version,
            inputHash: hash('sha256', $input ?? $excerpt),
            outputHash: hash('sha256', $output),
            targetEntityType: 'information_object',
            targetEntityId: $ioId,
            targetField: $field,
            confidence: $confidence,
            inputExcerpt: $excerpt,
            outputExcerpt: $output,
            userId: $userId,
        ));
    }
}
