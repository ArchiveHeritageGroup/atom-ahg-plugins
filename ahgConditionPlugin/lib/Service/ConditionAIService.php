<?php

namespace AhgCondition\Service;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * AI Condition Assessment — Analyze condition photos via LLaVA vision model.
 *
 * Sends condition photos to Ollama (LLaVA) with a conservation-specific prompt,
 * parses structured output into condition ratings, damage types, and recommendations.
 *
 * Issue #27: AI Condition Assessment
 */
class ConditionAIService
{
    private string $ollamaUrl;
    private string $model;
    private int $timeout;

    /** Spectrum 5.1 damage type vocabulary */
    private const DAMAGE_TYPES = [
        'tear', 'stain', 'foxing', 'fading', 'water_damage',
        'mold', 'pest_damage', 'abrasion', 'brittleness', 'loss',
        'crack', 'corrosion', 'discolouration', 'deformation', 'dust',
    ];

    /** Valid overall ratings */
    private const RATINGS = ['excellent', 'good', 'fair', 'poor', 'critical'];

    /** Severity levels */
    private const SEVERITIES = ['minor', 'moderate', 'severe', 'critical'];

    public function __construct()
    {
        // Load from voice/AI settings or defaults
        $this->ollamaUrl = $this->getSetting('voice_local_llm_url', 'http://localhost:11434');
        $this->model = $this->getSetting('voice_local_llm_model', 'llava:7b');
        $this->timeout = (int) $this->getSetting('voice_local_llm_timeout', 60);
    }

    /**
     * Analyze a condition photo and return structured assessment.
     *
     * @param string $imagePath Absolute path to the photo file
     * @param string $materialType Material type hint (paper, textile, metal, etc.)
     * @return array {success, overall_rating, damage_types[], severity, description, recommendations, raw_response}
     */
    public function analyzePhoto(string $imagePath, string $materialType = 'unknown'): array
    {
        if (!file_exists($imagePath)) {
            return ['success' => false, 'error' => 'Image file not found'];
        }

        // Convert to JPEG if needed, resize for LLM
        [$processedPath, $mimeType] = $this->prepareImage($imagePath);

        $imageData = file_get_contents($processedPath);
        if (!$imageData) {
            return ['success' => false, 'error' => 'Could not read image file'];
        }

        $base64 = base64_encode($imageData);
        $prompt = $this->buildPrompt($materialType);

        // Call Ollama
        $url = rtrim($this->ollamaUrl, '/') . '/api/generate';
        $payload = json_encode([
            'model'  => $this->model,
            'prompt' => $prompt,
            'images' => [$base64],
            'stream' => false,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            return ['success' => false, 'error' => 'LLM unavailable: ' . ($error ?: "HTTP {$httpCode}")];
        }

        $data = json_decode($response, true);
        $rawText = trim($data['response'] ?? '');

        if (empty($rawText)) {
            return ['success' => false, 'error' => 'LLM returned empty response'];
        }

        // Parse structured output
        return $this->parseResponse($rawText);
    }

    /**
     * Analyze a condition photo and save results to the database.
     *
     * @param int    $objectId        Information object ID
     * @param int    $photoId         spectrum_condition_photo ID
     * @param string $materialType    Material type hint
     * @param int    $userId          User performing the assessment
     * @return array Result with condition_check_id if successful
     */
    public function analyzeAndSave(int $objectId, int $photoId, string $materialType, int $userId): array
    {
        // Get photo file path
        $photo = DB::table('spectrum_condition_photo')->where('id', $photoId)->first();
        if (!$photo) {
            return ['success' => false, 'error' => 'Photo not found'];
        }

        $webDir = \sfConfig::get('sf_web_dir', \sfConfig::get('sf_root_dir'));
        $imagePath = $webDir . '/' . ltrim($photo->file_path, '/');

        if (!file_exists($imagePath)) {
            // Try with filename
            $uploadPath = $this->getSetting('photo_upload_path', $webDir . '/uploads/condition_photos');
            $imagePath = $uploadPath . '/' . $photo->filename;
        }

        $result = $this->analyzePhoto($imagePath, $materialType);
        if (!$result['success']) {
            return $result;
        }

        // Create or update condition check
        $checkId = DB::table('spectrum_condition_check')->insertGetId([
            'object_id'              => $objectId,
            'check_date'             => date('Y-m-d'),
            'overall_condition'      => $result['overall_rating'],
            'condition_rating'       => $result['overall_rating'],
            'condition_description'  => $result['description'],
            'recommended_treatment'  => $result['recommendations'],
            'treatment_priority'     => $this->mapSeverityToPriority($result['severity']),
            'material_type'          => $materialType !== 'unknown' ? $materialType : null,
            'photo_count'            => 1,
            'workflow_state'         => 'completed',
            'condition_note'         => 'AI-generated assessment via ' . $this->model,
            'created_by'             => $userId,
            'created_at'             => date('Y-m-d H:i:s'),
            'updated_at'             => date('Y-m-d H:i:s'),
        ]);

        // Link photo to this check
        DB::table('spectrum_condition_photo')
            ->where('id', $photoId)
            ->update(['condition_check_id' => $checkId]);

        // Insert damage records
        if (!empty($result['damage_types'])) {
            foreach ($result['damage_types'] as $damage) {
                try {
                    DB::table('condition_damage')->insert([
                        'condition_report_id' => $checkId,
                        'damage_type'         => $damage['type'],
                        'severity'            => $damage['severity'] ?? $result['severity'],
                        'location'            => $damage['location'] ?? 'overall',
                        'treatment_required'  => ($result['severity'] !== 'minor') ? 1 : 0,
                        'treatment_notes'     => $damage['notes'] ?? null,
                        'created_at'          => date('Y-m-d H:i:s'),
                    ]);
                } catch (\Exception $e) {
                    // Continue — table may have slightly different schema
                }
            }
        }

        $result['condition_check_id'] = $checkId;
        return $result;
    }

    /**
     * Build conservation-specific prompt for LLaVA.
     */
    private function buildPrompt(string $materialType): string
    {
        $materialHint = $materialType !== 'unknown'
            ? "The object is made of {$materialType}. "
            : '';

        return <<<PROMPT
You are a professional conservator assessing the physical condition of a cultural heritage object from a photograph. {$materialHint}Analyze this image and provide a structured condition assessment.

Respond in EXACTLY this format (one item per line):

RATING: [one of: excellent, good, fair, poor, critical]
SEVERITY: [one of: minor, moderate, severe, critical]
DAMAGE: [comma-separated list from: tear, stain, foxing, fading, water_damage, mold, pest_damage, abrasion, brittleness, loss, crack, corrosion, discolouration, deformation, dust, none]
DESCRIPTION: [2-3 sentences describing the visible condition]
RECOMMENDATIONS: [1-2 sentences on conservation treatment needed]

Be specific about what you observe. If the object appears in good condition with no visible damage, say so. Do not invent damage that is not visible.
PROMPT;
    }

    /**
     * Parse LLM response into structured data.
     */
    private function parseResponse(string $text): array
    {
        $result = [
            'success'          => true,
            'overall_rating'   => 'fair',
            'severity'         => 'moderate',
            'damage_types'     => [],
            'description'      => '',
            'recommendations'  => '',
            'raw_response'     => $text,
        ];

        // Parse each line
        foreach (explode("\n", $text) as $line) {
            $line = trim($line);

            if (preg_match('/^RATING:\s*(.+)/i', $line, $m)) {
                $rating = strtolower(trim($m[1]));
                if (in_array($rating, self::RATINGS)) {
                    $result['overall_rating'] = $rating;
                }
            } elseif (preg_match('/^SEVERITY:\s*(.+)/i', $line, $m)) {
                $severity = strtolower(trim($m[1]));
                if (in_array($severity, self::SEVERITIES)) {
                    $result['severity'] = $severity;
                }
            } elseif (preg_match('/^DAMAGE:\s*(.+)/i', $line, $m)) {
                $damages = array_map('trim', explode(',', strtolower($m[1])));
                foreach ($damages as $d) {
                    $d = str_replace(' ', '_', $d);
                    if ($d === 'none') continue;
                    // Fuzzy match against known types
                    $matched = $this->matchDamageType($d);
                    if ($matched) {
                        $result['damage_types'][] = [
                            'type'     => $matched,
                            'severity' => $result['severity'],
                            'location' => 'overall',
                            'notes'    => null,
                        ];
                    }
                }
            } elseif (preg_match('/^DESCRIPTION:\s*(.+)/i', $line, $m)) {
                $result['description'] = trim($m[1]);
            } elseif (preg_match('/^RECOMMENDATIONS?:\s*(.+)/i', $line, $m)) {
                $result['recommendations'] = trim($m[1]);
            }
        }

        // If structured parsing failed, use full text as description
        if (empty($result['description'])) {
            $result['description'] = $text;
        }

        return $result;
    }

    /**
     * Fuzzy match a damage type string to known Spectrum vocabulary.
     */
    private function matchDamageType(string $input): ?string
    {
        // Exact match
        if (in_array($input, self::DAMAGE_TYPES)) {
            return $input;
        }

        // Common synonyms
        $synonyms = [
            'water' => 'water_damage', 'moisture' => 'water_damage', 'wet' => 'water_damage',
            'mould' => 'mold', 'fungus' => 'mold', 'fungal' => 'mold',
            'insect' => 'pest_damage', 'pest' => 'pest_damage', 'bug' => 'pest_damage', 'rodent' => 'pest_damage',
            'fade' => 'fading', 'faded' => 'fading', 'bleach' => 'fading',
            'torn' => 'tear', 'rip' => 'tear', 'split' => 'tear',
            'rust' => 'corrosion', 'oxidation' => 'corrosion', 'tarnish' => 'corrosion',
            'brittle' => 'brittleness', 'fragile' => 'brittleness',
            'scratch' => 'abrasion', 'scuff' => 'abrasion', 'wear' => 'abrasion',
            'missing' => 'loss', 'lacuna' => 'loss', 'hole' => 'loss',
            'discolour' => 'discolouration', 'discolor' => 'discolouration', 'yellowing' => 'discolouration',
            'warp' => 'deformation', 'bent' => 'deformation', 'buckle' => 'deformation',
            'dirty' => 'dust', 'grime' => 'dust', 'soot' => 'dust',
            'cracked' => 'crack', 'cracking' => 'crack',
            'stained' => 'stain', 'spot' => 'stain', 'mark' => 'stain',
            'fox' => 'foxing',
        ];

        if (isset($synonyms[$input])) {
            return $synonyms[$input];
        }

        // Partial match
        foreach (self::DAMAGE_TYPES as $type) {
            if (str_contains($input, $type) || str_contains($type, $input)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Map severity to treatment priority.
     */
    private function mapSeverityToPriority(string $severity): string
    {
        return match ($severity) {
            'critical' => 'urgent',
            'severe'   => 'high',
            'moderate' => 'normal',
            default    => 'low',
        };
    }

    /**
     * Prepare image for LLM — resize and convert if needed.
     */
    private function prepareImage(string $filePath): array
    {
        $mimeType = mime_content_type($filePath) ?: 'image/jpeg';
        $supported = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (in_array($mimeType, $supported)) {
            $size = @getimagesize($filePath);
            if ($size && ($size[0] <= 2048 && $size[1] <= 2048)) {
                return [$filePath, $mimeType];
            }
        }

        // Convert/resize
        $tmpPath = sys_get_temp_dir() . '/condition_ai_' . md5($filePath) . '.jpg';
        if (file_exists($tmpPath) && (time() - filemtime($tmpPath)) < 3600) {
            return [$tmpPath, 'image/jpeg'];
        }

        $cmd = sprintf(
            'convert %s[0] -resize 1024x1024\\> -quality 85 %s 2>&1',
            escapeshellarg($filePath),
            escapeshellarg($tmpPath)
        );
        exec($cmd, $output, $exitCode);

        if ($exitCode === 0 && file_exists($tmpPath)) {
            return [$tmpPath, 'image/jpeg'];
        }

        return [$filePath, $mimeType];
    }

    /**
     * Read a setting from ahg_settings.
     */
    private function getSetting(string $key, string $default = ''): string
    {
        try {
            $row = DB::table('ahg_settings')
                ->where('setting_key', $key)
                ->first();
            return $row->setting_value ?? $default;
        } catch (\Exception $e) {
            return $default;
        }
    }
}
