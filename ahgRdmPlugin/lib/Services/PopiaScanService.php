<?php

/**
 * @author    Johan Pieterse <johan@theahg.co.za>
 * @copyright The Archive and Heritage Group (Pty) Ltd
 * @license   AGPL-3.0-or-later
 */

namespace AhgRdm\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * POPIA sensitivity scan — reverse port of Heratio ahg-rdm PopiaScanService
 * (heratio#1339). The value AND the liability of ahgRdmPlugin, so:
 * deterministic-first, AI-augmented, human-final.
 *
 * - Deterministic backbone (NO LLM, cannot hallucinate): SA ID (Luhn + embedded
 *   date), email, SA phone, passport. High precision + explainable.
 * - Special-category lexicon: health/religion/biometric terms -> flag for review.
 * - NER augmentation (lower trust): ahgNerService persons/places/orgs, always
 *   'AI-suggested', routed through the AI gateway (never a node). Best-effort —
 *   if disabled/over-quota/down, the deterministic detectors stand alone.
 *
 * The scan NEVER auto-decides. It records findings (review_status='pending') and
 * a dataset verdict; a human confirms/overrides each in the gate (later phase).
 */
class PopiaScanService
{
    /** Illustrative special-category lexicon (health / religion / biometric / orientation). Expandable. */
    private const SPECIAL_TERMS = [
        'hiv', 'aids', 'diabetes', 'cancer', 'depression', 'pregnan', 'disability',
        'psychiatric', 'medication', 'diagnosis', 'treatment', 'mental health',
        'religio', 'church', 'mosque', 'muslim', 'christian', 'jewish', 'hindu',
        'biometric', 'fingerprint', 'dna', 'genetic', 'sexual orientation',
        'political party', 'trade union',
    ];

    /** Max chars sent to NER per file (keeps the gateway call bounded). */
    private const NER_TEXT_CAP = 20000;

    /** Below this many non-whitespace chars, a PDF's text layer is treated as
     *  empty/near-empty and we fall back to rasterise + OCR. */
    private const PDF_TEXTLAYER_MIN_CHARS = 24;

    /** OCR fallback bounds for scanned PDFs: page cap + raster DPI. */
    private const PDF_OCR_PAGE_CAP = 10;

    private const PDF_OCR_DPI = 200;

    /**
     * Scan every file in a dataset, persist findings, set the verdict.
     *
     * @return array{verdict:string, findings:int, files:int, scanned:int}
     */
    public function scanDataset(int $datasetId): array
    {
        $files = DB::table('rdm_dataset_file')->where('dataset_id', $datasetId)->get();

        // Re-scan is idempotent: clear prior findings first.
        DB::table('rdm_scan_finding')->where('dataset_id', $datasetId)->delete();
        DB::table('rdm_dataset')->where('id', $datasetId)->update(['status' => 'scanning']);

        $findingsCount = 0;
        $scanned = 0;
        $hasSpecial = false;
        $hasPersonal = false;

        foreach ($files as $f) {
            $extract = $this->extractContent($f);
            if ($extract === null || trim($extract['text']) === '') {
                continue; // unreadable / scanned-PDF-no-OCR / unsupported -> skip
            }
            $text = $extract['text'];
            $scanned++;

            $found = array_merge(
                $this->deterministic($text),
                $this->specialCategory($text),
                $this->ner($text)
            );

            // OCR introduces noise: demote OCR-derived findings one confidence
            // notch so the human gate treats them as lower-trust. Still 'pending',
            // still the same type/category/method.
            if ($extract['via_ocr']) {
                $found = array_map(function ($fd) {
                    $fd['confidence'] = $this->demote($fd['confidence']);

                    return $fd;
                }, $found);
            }

            foreach ($found as $fd) {
                DB::table('rdm_scan_finding')->insert([
                    'dataset_id'      => $datasetId,
                    'dataset_file_id' => $f->id,
                    'file_name'       => $f->original_name,
                    'type'            => $fd['type'],
                    'category'        => $fd['category'],
                    'sample'          => $fd['sample'],
                    'confidence'      => $fd['confidence'],
                    'method'          => $fd['method'],
                    'review_status'   => 'pending',
                    'created_at'      => date('Y-m-d H:i:s'),
                ]);
                $findingsCount++;
                $hasSpecial = $hasSpecial || $fd['category'] === 'special_category';
                $hasPersonal = $hasPersonal || $fd['category'] === 'personal';
            }
        }

        $verdict = $hasSpecial ? 'SPECIAL_CATEGORY' : ($hasPersonal ? 'PERSONAL' : 'CLEAR');
        // CLEAR -> back to draft (publishable); anything with PII -> human review gate.
        $status = $verdict === 'CLEAR' ? 'draft' : 'review';

        DB::table('rdm_dataset')->where('id', $datasetId)->update([
            'verdict'    => $verdict,
            'status'     => $status,
            'scanned_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['verdict' => $verdict, 'findings' => $findingsCount, 'files' => $files->count(), 'scanned' => $scanned];
    }

    // --- text extraction ---------------------------------------------------

    /**
     * Extract a file's text for scanning, recording whether it came via OCR
     * (lower-trust). PDFs prefer the born-digital text layer and only fall back
     * to rasterise + OCR when that layer is empty/near-empty.
     *
     * @return array{text:string, via_ocr:bool}|null
     */
    private function extractContent(object $fileRow): ?array
    {
        $path = $this->masterDiskPath((int) $fileRow->io_id);
        if (!$path || !is_file($path)) {
            return null;
        }

        $mime = strtolower((string) (mime_content_type($path) ?: ''));

        // PDF: pdftotext first (born-digital); OCR fallback for scanned/image-only.
        if (strpos($mime, 'pdf') !== false) {
            $text = (string) @shell_exec('pdftotext -enc UTF-8 ' . escapeshellarg($path) . ' - 2>/dev/null');
            if (mb_strlen(trim($text)) >= self::PDF_TEXTLAYER_MIN_CHARS) {
                return ['text' => $text, 'via_ocr' => false];
            }
            $ocr = $this->ocrPdf($path, $fileRow->original_name);
            if ($ocr !== null && trim($ocr) !== '') {
                return ['text' => $ocr, 'via_ocr' => true];
            }

            return trim($text) === '' ? null : ['text' => $text, 'via_ocr' => false];
        }

        // Image -> Tesseract OCR (local; degrades to null when unavailable).
        if (strpos($mime, 'image/') === 0) {
            $text = (string) @shell_exec('tesseract ' . escapeshellarg($path) . ' stdout 2>/dev/null');

            return trim($text) === '' ? null : ['text' => $text, 'via_ocr' => true];
        }

        // Text-like (csv/txt/json/xml/plain): read bytes.
        $bytes = @file_get_contents($path);

        return is_string($bytes) ? ['text' => $bytes, 'via_ocr' => false] : null;
    }

    /** Absolute disk path of the master digital_object for an IO, or null. */
    private function masterDiskPath(int $ioId): ?string
    {
        $master = DB::table('digital_object')
            ->where('object_id', $ioId)
            ->whereNull('parent_id')
            ->first();
        if (!$master || empty($master->path)) {
            return null;
        }

        $webDir = rtrim((string) \sfConfig::get('sf_web_dir'), '/');
        $rel = '/' . ltrim((string) $master->path, '/');

        return $webDir . $rel . ($master->name ?? '');
    }

    /**
     * Rasterise a scanned PDF (up to PDF_OCR_PAGE_CAP pages) and OCR each page,
     * returning the concatenated text. Reuses host tooling only (pdftoppm from
     * poppler-utils + tesseract). Returns null when nothing is recoverable.
     */
    private function ocrPdf(string $pdfPath, string $name): ?string
    {
        if (!is_executable('/usr/bin/pdftoppm')) {
            return null;
        }

        $dir = sys_get_temp_dir() . '/rdmocr_' . bin2hex(random_bytes(6));
        if (!@mkdir($dir) && !is_dir($dir)) {
            return null;
        }

        try {
            $cmd = sprintf(
                '/usr/bin/pdftoppm -jpeg -r %d -f 1 -l %d %s %s 2>/dev/null',
                self::PDF_OCR_DPI,
                self::PDF_OCR_PAGE_CAP,
                escapeshellarg($pdfPath),
                escapeshellarg($dir . '/p')
            );
            @exec($cmd);

            $pages = glob($dir . '/p-*.jpg') ?: [];
            sort($pages, SORT_NATURAL);
            if (!$pages) {
                return null;
            }

            $parts = [];
            foreach ($pages as $img) {
                $t = (string) @shell_exec('tesseract ' . escapeshellarg($img) . ' stdout 2>/dev/null');
                if (trim($t) !== '') {
                    $parts[] = $t;
                }
            }

            return $parts ? implode("\n", $parts) : null;
        } finally {
            foreach (glob($dir . '/p-*.jpg') ?: [] as $img) {
                @unlink($img);
            }
            @rmdir($dir);
        }
    }

    /** Drop a confidence one notch (OCR noise marking). low is the floor. */
    private function demote(string $confidence): string
    {
        switch ($confidence) {
            case 'high':   return 'medium';
            case 'medium': return 'low';
            default:       return 'low';
        }
    }

    // --- detectors ---------------------------------------------------------

    /** Deterministic, explainable, no LLM. */
    private function deterministic(string $text): array
    {
        $out = [];

        // SA ID number: 13-digit runs that pass embedded-date + Luhn.
        if (preg_match_all('/\b\d{13}\b/', $text, $m)) {
            foreach (array_unique($m[0]) as $cand) {
                if ($this->isValidSaId($cand)) {
                    $out[] = $this->finding('sa_id_number', 'personal', $this->mask($cand), 'high', 'deterministic');
                }
            }
        }
        // Email
        if (preg_match_all('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $text, $m)) {
            foreach (array_unique($m[0]) as $e) {
                $out[] = $this->finding('email', 'personal', $this->maskEmail($e), 'high', 'deterministic');
            }
        }
        // SA phone (+27XXXXXXXXX or 0XXXXXXXXX)
        if (preg_match_all('/\b(?:\+27|0)\d{9}\b/', $text, $m)) {
            foreach (array_unique($m[0]) as $p) {
                $out[] = $this->finding('phone', 'personal', $this->mask($p), 'high', 'deterministic');
            }
        }
        // Passport-style: a letter + 8 digits (illustrative; medium confidence).
        if (preg_match_all('/\b[A-Z]\d{8}\b/', $text, $m)) {
            foreach (array_unique($m[0]) as $p) {
                $out[] = $this->finding('passport', 'personal', $this->mask($p), 'medium', 'deterministic');
            }
        }

        return $out;
    }

    private function specialCategory(string $text): array
    {
        $lc = mb_strtolower($text);
        $out = [];
        foreach (self::SPECIAL_TERMS as $term) {
            if (mb_strpos($lc, $term) !== false) {
                $out[] = $this->finding('special_category', 'special_category', $term, 'medium', 'lexicon');
            }
        }

        return $out;
    }

    /**
     * NER augmentation — AI-suggested, best-effort, gateway-routed via
     * ahgNerService. AtoM's NER returns spaCy labels (PERSON/ORG/GPE); map them
     * to our finding types. Any failure (disabled/over-quota/down) is swallowed
     * so the deterministic detectors stand alone.
     */
    private function ner(string $text): array
    {
        $out = [];
        try {
            if (!class_exists('\ahgNerService')) {
                require_once \sfConfig::get('sf_plugins_dir') . '/ahgAIPlugin/lib/Services/NerService.php';
            }
            $svc = new \ahgNerService();
            $r = $svc->extract(mb_substr($text, 0, self::NER_TEXT_CAP), true);
            if (empty($r['success'])) {
                return $out;
            }
            $ent = $r['entities'] ?? [];
            foreach (array_unique($ent['PERSON'] ?? []) as $v) {
                $out[] = $this->finding('person', 'personal', (string) $v, 'medium', 'ner');
            }
            foreach (array_unique($ent['GPE'] ?? []) as $v) {
                $out[] = $this->finding('location', 'personal', (string) $v, 'low', 'ner');
            }
            foreach (array_unique($ent['ORG'] ?? []) as $v) {
                $out[] = $this->finding('org', 'personal', (string) $v, 'low', 'ner');
            }
        } catch (\Throwable $e) {
            // best-effort; deterministic findings stand alone
        }

        return $out;
    }

    // --- helpers -----------------------------------------------------------

    private function finding(string $type, string $category, string $sample, string $confidence, string $method): array
    {
        return compact('type', 'category', 'sample', 'confidence', 'method');
    }

    /** Valid SA ID: 13 digits, valid embedded YYMMDD date, Luhn checksum 0. */
    private function isValidSaId(string $s): bool
    {
        if (!preg_match('/^\d{13}$/', $s)) {
            return false;
        }
        $mm = (int) substr($s, 2, 2);
        $dd = (int) substr($s, 4, 2);
        if ($mm < 1 || $mm > 12 || $dd < 1 || $dd > 31) {
            return false;
        }
        $sum = 0;
        $alt = false;
        for ($i = 12; $i >= 0; $i--) {
            $n = (int) $s[$i];
            if ($alt) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
            $alt = !$alt;
        }

        return $sum % 10 === 0;
    }

    /** Mask a structured identifier: keep first 4 + last 2, redact the middle. */
    private function mask(string $v): string
    {
        $len = strlen($v);
        if ($len <= 6) {
            return str_repeat('*', max(0, $len - 1)) . substr($v, -1);
        }

        return substr($v, 0, 4) . str_repeat('*', $len - 6) . substr($v, -2);
    }

    private function maskEmail(string $e): string
    {
        $at = strpos($e, '@');
        if ($at === false) {
            return $this->mask($e);
        }

        return substr($e, 0, 1) . '***' . substr($e, $at);
    }
}
