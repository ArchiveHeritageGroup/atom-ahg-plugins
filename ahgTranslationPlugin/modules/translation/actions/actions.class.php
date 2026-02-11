<?php

use AtomFramework\Http\Controllers\AhgController;
class translationActions extends AhgController
{
    /** @var AhgTranslationService */
    private $svc;

    public function boot(): void
    {
        $this->svc = new AhgTranslationService();
    }

    public function executeHealth($request)
    {
        $endpoint = $this->svc->getSetting('mt.endpoint', 'http://127.0.0.1:5100/translate');

        // Lightweight check; do not assume MT supports en->en. We just verify endpoint is reachable.
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
            CURLOPT_POSTFIELDS => json_encode(array('source' => 'af', 'target' => 'en', 'text' => 'toets')),
            CURLOPT_TIMEOUT => 5,
        ));
        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $ok = ($errno === 0) && ($status >= 200 && $status < 500); // 4xx still means reachable

        return $this->renderText(json_encode(array(
            'ok' => $ok,
            'endpoint' => $endpoint,
            'http_status' => $status,
            'curl_error' => $errno ? ($errno . ': ' . $err) : null,
        )));
    }

    public function executeSettings($request)
    {
        // Require authenticated user (tighten to admin later if you want)
        if (!$this->getUser()->isAuthenticated()) $this->forward404();

        if ($request->isMethod(sfRequest::POST)) {
            $endpoint = trim((string)$request->getParameter('endpoint'));
            $timeout = trim((string)$request->getParameter('timeout'));
            if ($endpoint !== '') $this->svc->setSetting('mt.endpoint', $endpoint);
            if ($timeout !== '' && ctype_digit($timeout)) $this->svc->setSetting('mt.timeout_seconds', $timeout);

            $this->getUser()->setFlash('notice', 'Settings updated');
            $this->redirect('@ahg_translation_settings');
        }

        $this->endpoint = $this->svc->getSetting('mt.endpoint', 'http://127.0.0.1:5100/translate');
        $this->timeout = $this->svc->getSetting('mt.timeout_seconds', '30');
    }

    /**
     * POST /translation/translate/:id
     * Params:
     *  - field: source field key (one of allowed field keys)
     *  - targetField: target field key (defaults to same as field)
     *  - source: source culture code (default: current user culture)
     *  - target: target culture code (default: from settings)
     *  - apply: 0/1 (default 0) apply immediately
     *  - overwrite: 0/1 (default 0)
     *  - saveCulture: 0/1 (default 1) save with AtoM culture code
     */
    public function executeTranslate($request)
    {
        $this->forward404Unless($request->isMethod(sfRequest::POST));

        $id = (int)$request->getParameter('id');
        $fieldKey = (string)$request->getParameter('field');
        $targetFieldKey = (string)$request->getParameter('targetField', $fieldKey); // Default to same field
        $source = (string)$request->getParameter('source', $this->culture());
        $target = (string)$request->getParameter('target', $this->svc->getSetting('mt.target_culture', 'en'));
        $apply = ((int)$request->getParameter('apply', 0) === 1);
        $overwrite = ((int)$request->getParameter('overwrite', 0) === 1);
        $saveCulture = ((int)$request->getParameter('saveCulture', 1) === 1);

        $allowed = AhgTranslationRepository::allowedFields();
        if (!isset($allowed[$fieldKey])) {
            return $this->renderText(json_encode(array('ok' => false, 'error' => 'Unsupported source field: ' . $fieldKey)));
        }
        if (!isset($allowed[$targetFieldKey])) {
            return $this->renderText(json_encode(array('ok' => false, 'error' => 'Unsupported target field: ' . $targetFieldKey)));
        }

        $sourceColumn = $allowed[$fieldKey];
        $targetColumn = $allowed[$targetFieldKey];
        $sourceText = $this->svc->repo()->getInformationObjectField($id, $source, $sourceColumn);

        if ($sourceText === null || trim($sourceText) === '') {
            return $this->renderText(json_encode(array('ok' => false, 'error' => 'No source text for this field/language')));
        }

        // Get max length for target field (field_length - 1)
        $maxLength = AhgTranslationRepository::fieldMaxLength($targetColumn);

        $result = $this->svc->translateText($sourceText, $source, $target, $maxLength);
        $this->svc->logAttempt($id, $fieldKey, $source, $target, $result);

        if (empty($result['ok'])) {
            return $this->renderText(json_encode(array(
                'ok' => false,
                'error' => $result['error'] ?? 'Translation failed',
                'http_status' => $result['http_status'] ?? null,
            )));
        }

        $translated = $result['translation'];

        $userId = null;
        try { $userId = (int)$this->getUser()->getAttribute('userid'); } catch (Exception $e) {}

        // Create draft with target field info
        $draft = $this->svc->createDraft($id, $targetFieldKey, $source, $target, $sourceText, $translated, $userId);
        if (empty($draft['ok'])) {
            return $this->renderText(json_encode(array('ok' => false, 'error' => $draft['error'] ?? 'Failed to create draft')));
        }

        $resp = array(
            'ok' => true,
            'draft_id' => $draft['draft_id'],
            'deduped' => $draft['deduped'] ?? false,
            'translation' => $translated,
            'source_text' => $sourceText,
            'source_field' => $fieldKey,
            'target_field' => $targetFieldKey,
        );

        if ($apply) {
            // Determine target culture - if saveCulture is false, save to source culture
            $targetCulture = $saveCulture ? $target : $source;
            $applied = $this->svc->applyDraftWithCulture((int)$draft['draft_id'], $overwrite, $targetCulture);
            $resp['apply_ok'] = !empty($applied['ok']);
            $resp['saved_culture'] = $targetCulture;
            if (empty($applied['ok'])) $resp['apply_error'] = $applied['error'] ?? 'Apply failed';
        }

        return $this->renderText(json_encode($resp));
    }

    public function executeApply($request)
    {
        $this->forward404Unless($request->isMethod(sfRequest::POST));

        $draftId = (int)$request->getParameter('draftId');
        $overwrite = ((int)$request->getParameter('overwrite', 0) === 1);
        $saveCulture = ((int)$request->getParameter('saveCulture', 1) === 1);
        $targetCulture = (string)$request->getParameter('targetCulture', '');
        $editedText = $request->getParameter('editedText');

        // If edited text is provided, update the draft first
        if ($editedText !== null && $editedText !== '') {
            $this->svc->updateDraftText($draftId, $editedText);
        }

        // Apply with culture option
        if ($saveCulture && $targetCulture !== '') {
            $result = $this->svc->applyDraftWithCulture($draftId, $overwrite, $targetCulture);
        } else {
            $result = $this->svc->applyDraft($draftId, $overwrite);
        }

        return $this->renderText(json_encode($result));
    }
}
