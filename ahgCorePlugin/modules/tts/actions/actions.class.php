<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * TTS Actions - Text-to-Speech API endpoints
 */
class ttsActions extends AhgController
{
    /**
     * Get TTS settings for a specific sector
     * GET /tts/settings?sector=archive
     */
    public function executeSettings($request)
    {
        $this->getResponse()->setContentType('application/json');

        $sector = $request->getParameter('sector', 'archive');

        // Get general settings
        $settings = [];
        try {
            $rows = DB::table('ahg_tts_settings')->where('sector', 'all')->get();
            foreach ($rows as $row) {
                $settings[$row->setting_key] = $row->setting_value;
            }
        } catch (Exception $e) {
            $settings = [];
        }

        // Get sector-specific fields
        $fieldsRow = DB::table('ahg_tts_settings')
            ->where('setting_key', 'fields_to_read')
            ->where('sector', $sector)
            ->first();

        $fieldsToRead = $fieldsRow ? json_decode($fieldsRow->setting_value, true) : [];

        return $this->renderText(json_encode([
            'enabled' => ($settings['enabled'] ?? '1') === '1',
            'rate' => floatval($settings['default_rate'] ?? 1.0),
            'readLabels' => ($settings['read_labels'] ?? '1') === '1',
            'keyboardShortcuts' => ($settings['keyboard_shortcuts'] ?? '1') === '1',
            'fieldsToRead' => $fieldsToRead,
            'sector' => $sector,
        ]));
    }

    /**
     * Extract text from PDF for TTS
     * GET /tts/pdfText?id=<digital_object_id>
     */
    public function executePdfText($request)
    {
        $this->getResponse()->setContentType('application/json');

        $objectId = $request->getParameter('id');
        if (!$objectId) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Missing object ID'
            ]));
        }

        // Get the digital object
        $digitalObject = QubitDigitalObject::getById($objectId);
        if (!$digitalObject) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Digital object not found'
            ]));
        }

        // Check if it's a PDF
        $mimeType = $digitalObject->mimeType;
        if ($mimeType !== 'application/pdf') {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Not a PDF file'
            ]));
        }

        // Get the file path
        $originalFilePath = $digitalObject->getAbsolutePath();
        if (!$originalFilePath || !file_exists($originalFilePath)) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'PDF file not found on disk'
            ]));
        }

        // Check if we should use the redacted PDF instead
        $filePath = $originalFilePath;
        $isRedacted = false;

        // Get the information object ID for redaction check
        $infoObjectId = $digitalObject->objectId;

        // Load PdfRedactionService if available
        $redactionServicePath = $this->config('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PdfRedactionService.php';
        if (file_exists($redactionServicePath)) {
            require_once $redactionServicePath;
        }

        if (class_exists('ahgPrivacyPlugin\Service\PdfRedactionService')) {
            $redactionService = new \ahgPrivacyPlugin\Service\PdfRedactionService();

            // Check if this object has redactions
            if ($redactionService->hasRedactions($infoObjectId)) {
                // Get the redacted PDF
                $result = $redactionService->getRedactedPdf($infoObjectId, $originalFilePath);

                if ($result['success'] && !empty($result['path']) && file_exists($result['path'])) {
                    $filePath = $result['path'];
                    $isRedacted = true;
                }
            }
        }

        // Extract text using pdftotext
        $maxPages = (int) $request->getParameter('max_pages', 50);
        $text = $this->extractPdfText($filePath, $maxPages);

        if ($text === false) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Failed to extract text from PDF'
            ]));
        }

        // Clean up the text
        $text = $this->cleanText($text);

        // Check if text is meaningful (not just whitespace/gibberish)
        if (strlen($text) < 50) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'This PDF has no readable text. It may be a scanned image that has not been OCR processed.',
                'chars' => strlen($text)
            ]));
        }

        return $this->renderText(json_encode([
            'success' => true,
            'text' => $text,
            'pages' => $this->countPages($filePath),
            'chars' => strlen($text),
            'redacted' => $isRedacted
        ]));
    }

    /**
     * Extract text from PDF using pdftotext
     */
    protected function extractPdfText($filePath, $maxPages = 50)
    {
        // Check if pdftotext exists
        $pdftotext = trim(shell_exec('which pdftotext'));
        if (empty($pdftotext)) {
            return false;
        }

        // Build command with page limit
        $escapedPath = escapeshellarg($filePath);
        $cmd = sprintf('%s -l %d -enc UTF-8 -nopgbrk %s -',
            $pdftotext,
            $maxPages,
            $escapedPath
        );

        $output = shell_exec($cmd . ' 2>/dev/null');

        return $output !== null ? $output : false;
    }

    /**
     * Count pages in PDF
     */
    protected function countPages($filePath)
    {
        $pdfinfo = trim(shell_exec('which pdfinfo'));
        if (empty($pdfinfo)) {
            return null;
        }

        $escapedPath = escapeshellarg($filePath);
        $output = shell_exec(sprintf('%s %s 2>/dev/null | grep "Pages:"', $pdfinfo, $escapedPath));

        if (preg_match('/Pages:\s*(\d+)/', $output, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Clean extracted text for TTS
     */
    protected function cleanText($text)
    {
        // Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        // Remove control characters except newlines
        $text = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $text);

        // Remove redacted content
        $text = $this->removeRedactions($text);

        // Trim
        $text = trim($text);

        return $text;
    }

    /**
     * Replace redacted/masked content with spoken word "redacted" for accessibility
     * This allows blind users to know content was redacted
     */
    protected function removeRedactions($text)
    {
        // AHG Privacy Plugin redaction patterns - replace with "redacted" for accessibility
        $patterns = [
            '/\[NAME REDACTED\]/i',
            '/\[ID REDACTED\]/i',
            '/\[PASSPORT REDACTED\]/i',
            '/\[EMAIL REDACTED\]/i',
            '/\[PHONE REDACTED\]/i',
            '/\[ACCOUNT REDACTED\]/i',
            '/\[TAX NUMBER REDACTED\]/i',
            '/\[CARD REDACTED\]/i',
            '/\[ORG REDACTED\]/i',
            '/\[LOCATION REDACTED\]/i',
            '/\[DATE REDACTED\]/i',
            '/\[ADDRESS REDACTED\]/i',
            '/\[\w+\s+REDACTED\]/i',         // Any [WORD REDACTED] pattern
            '/\[REDACTED\]/i',
            '/\[REMOVED\]/i',
            '/\[PII REMOVED\]/i',
            '/\[PII REDACTED\]/i',
            '/█+/',                          // Block characters
            '/▓+/',                          // Block characters
            '/░+/',                          // Block characters
            '/\*{3,}/',                      // Multiple asterisks (****)
            '/X{3,}/',                       // Multiple X's (XXXX)
            '/\[\.{3,}\]/',                  // [...]
            '/<redacted>.*?<\/redacted>/is', // XML-style redaction tags
        ];

        foreach ($patterns as $pattern) {
            $text = preg_replace($pattern, ' redacted ', $text);
        }

        // Clean up multiple "redacted" words in a row and multiple spaces
        $text = preg_replace('/(\s*redacted\s*)+/', ' redacted ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}
