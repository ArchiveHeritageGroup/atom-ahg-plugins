<?php

class AhgTranslationService
{
    /** @var AhgTranslationRepository */
    private $repo;

    public function __construct()
    {
        $this->repo = new AhgTranslationRepository();
    }

    public function getSetting(string $key, $default = null)
    {
        return $this->repo->getSetting($key, $default);
    }

    public function setSetting(string $key, string $value): void
    {
        $this->repo->setSetting($key, $value);
    }

    public function translateText(string $text, string $sourceCulture, string $targetCulture = 'en', ?int $maxLength = null): array
    {
        // Use NLLB-200 API (ahg-ai service)
        $endpoint = $this->getSetting('mt.endpoint', 'http://192.168.0.112:5004/ai/v1/translate');
        $apiKey = $this->getSetting('mt.api_key', 'ahg_ai_demo_internal_2026');
        $timeout = (int)$this->getSetting('mt.timeout_seconds', '60');

        // NLLB-200 API format with max_length for field
        $payloadData = array(
            'text'   => $text,
            'source' => $sourceCulture,
            'target' => $targetCulture,
        );
        if ($maxLength !== null) {
            $payloadData['max_length'] = $maxLength;
        }
        $payload = json_encode($payloadData);

        $t0 = microtime(true);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'X-API-Key: ' . $apiKey,
            ),
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => $timeout,
        ));

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $errstr = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $elapsedMs = (int)round((microtime(true) - $t0) * 1000);

        if ($errno !== 0) {
            return array(
                'ok' => false,
                'translation' => null,
                'http_status' => $status ?: null,
                'error' => 'cURL error ' . $errno . ': ' . $errstr,
                'elapsed_ms' => $elapsedMs,
                'endpoint' => $endpoint,
            );
        }

        $data = json_decode((string)$raw, true);
        if (!is_array($data)) {
            return array(
                'ok' => false,
                'translation' => null,
                'http_status' => $status,
                'error' => 'Invalid JSON from MT endpoint',
                'elapsed_ms' => $elapsedMs,
                'endpoint' => $endpoint,
            );
        }

        // NLLB-200 returns 'translated' field
        $translation = $data['translated'] ?? $data['translatedText'] ?? $data['translation'] ?? null;

        if ($status < 200 || $status >= 300 || !is_string($translation)) {
            return array(
                'ok' => false,
                'translation' => null,
                'http_status' => $status,
                'error' => $data['detail'] ?? $data['error'] ?? 'MT endpoint returned non-2xx or missing translation',
                'elapsed_ms' => $elapsedMs,
                'endpoint' => $endpoint,
            );
        }

        return array(
            'ok' => true,
            'translation' => $translation,
            'http_status' => $status,
            'error' => null,
            'elapsed_ms' => $elapsedMs,
            'endpoint' => $endpoint,
            'model' => $data['model'] ?? 'nllb-200',
        );
    }

    public function createDraft(int $objectId, string $fieldName, string $sourceCulture, string $targetCulture, string $sourceText, string $translatedText, ?int $userId = null): array
    {
        $sourceHash = hash('sha256', $sourceText);

        return $this->repo->createDraft(array(
            'object_id' => $objectId,
            'entity_type' => 'information_object',
            'field_name' => $fieldName,
            'source_culture' => $sourceCulture,
            'target_culture' => $targetCulture,
            'source_hash' => $sourceHash,
            'source_text' => $sourceText,
            'translated_text' => $translatedText,
            'created_by_user_id' => $userId,
        ));
    }

    public function applyDraft(int $draftId, bool $overwrite = false): array
    {
        $draft = $this->repo->getDraft($draftId);
        if (!$draft) return array('ok' => false, 'error' => 'Draft not found');
        if ($draft['status'] !== 'draft') return array('ok' => false, 'error' => 'Draft not in draft state');

        $allowed = AhgTranslationRepository::allowedFields();
        if (!isset($allowed[$draft['field_name']])) return array('ok' => false, 'error' => 'Field not allowed');

        $column = $allowed[$draft['field_name']];
        $objectId = (int)$draft['object_id'];
        $culture = (string)$draft['target_culture'];
        $text = (string)$draft['translated_text'];

        $this->repo->ensureInformationObjectI18nRow($objectId, $culture);

        $current = $this->repo->getInformationObjectField($objectId, $culture, $column);
        if (!$overwrite && $current !== null && trim($current) !== '') {
            return array('ok' => false, 'error' => 'Target field not empty; use overwrite=1 to replace');
        }

        $this->repo->updateInformationObjectField($objectId, $culture, $column, $text);
        $this->repo->markDraftApplied($draftId);

        return array('ok' => true);
    }

    /**
     * Apply a draft with an explicit target culture (for saveCulture option)
     */
    public function applyDraftWithCulture(int $draftId, bool $overwrite = false, ?string $targetCulture = null): array
    {
        $draft = $this->repo->getDraft($draftId);
        if (!$draft) return array('ok' => false, 'error' => 'Draft not found');
        if ($draft['status'] !== 'draft') return array('ok' => false, 'error' => 'Draft not in draft state');

        $allowed = AhgTranslationRepository::allowedFields();
        if (!isset($allowed[$draft['field_name']])) return array('ok' => false, 'error' => 'Field not allowed');

        $column = $allowed[$draft['field_name']];
        $objectId = (int)$draft['object_id'];
        // Use provided targetCulture or fall back to draft's target_culture
        $culture = $targetCulture !== null ? $targetCulture : (string)$draft['target_culture'];
        $text = (string)$draft['translated_text'];

        $this->repo->ensureInformationObjectI18nRow($objectId, $culture);

        $current = $this->repo->getInformationObjectField($objectId, $culture, $column);
        if (!$overwrite && $current !== null && trim($current) !== '') {
            return array('ok' => false, 'error' => 'Target field not empty; use overwrite=1 to replace');
        }

        $this->repo->updateInformationObjectField($objectId, $culture, $column, $text);
        $this->repo->markDraftApplied($draftId);

        return array('ok' => true, 'culture' => $culture);
    }

    public function updateDraftText(int $draftId, string $newText): bool
    {
        return $this->repo->updateDraftText($draftId, $newText);
    }

    public function logAttempt(?int $objectId, ?string $field, ?string $src, ?string $tgt, array $result): void
    {
        $this->repo->logAttempt($objectId, $field, $src, $tgt, $result);
    }

    public function repo(): AhgTranslationRepository
    {
        return $this->repo;
    }
}
