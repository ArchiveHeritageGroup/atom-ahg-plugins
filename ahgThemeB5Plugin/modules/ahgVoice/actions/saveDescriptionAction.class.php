<?php
/**
 * AHG Voice — Save AI-generated description to record.
 *
 * POST /ahgVoice/saveDescription
 * Params: information_object_id, description, save_target, save_mode
 * Returns: JSON {success, message}
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */

use Illuminate\Database\Capsule\Manager as DB;

class ahgVoiceSaveDescriptionAction extends sfAction
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        // Auth check
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderJson(['success' => false, 'error' => 'Authentication required'], 401);
        }

        if (!$request->isMethod('POST')) {
            return $this->renderJson(['success' => false, 'error' => 'POST required'], 405);
        }

        $ioId = (int) $request->getParameter('information_object_id');
        $description = trim($request->getParameter('description', ''));
        $saveTarget = $request->getParameter('save_target', 'description'); // description | alt_text | both
        $saveMode = $request->getParameter('save_mode', 'replace'); // append | replace

        if ($ioId <= 0) {
            return $this->renderJson(['success' => false, 'error' => 'Invalid information object ID']);
        }
        if (empty($description)) {
            return $this->renderJson(['success' => false, 'error' => 'No description provided']);
        }

        // Verify the information object exists
        $io = DB::table('information_object')->where('id', $ioId)->first();
        if (!$io) {
            return $this->renderJson(['success' => false, 'error' => 'Information object not found']);
        }

        $culture = sfConfig::get('sf_default_culture', 'en');
        $saved = [];

        // Save to scope_and_content (description)
        if ($saveTarget === 'description' || $saveTarget === 'both') {
            $this->saveToI18n($ioId, 'scope_and_content', $description, $saveMode, $culture);
            $saved[] = 'description';
        }

        // Save to digital object alt text
        if ($saveTarget === 'alt_text' || $saveTarget === 'both') {
            $this->saveAltText($ioId, $description, $saveMode);
            $saved[] = 'alt text';
        }

        // Audit log
        $this->logAudit($ioId, $saveTarget, $saveMode);

        return $this->renderJson([
            'success' => true,
            'message' => 'Saved to ' . implode(' and ', $saved),
        ]);
    }

    /**
     * Save text to information_object_i18n field.
     */
    protected function saveToI18n($ioId, $field, $text, $mode, $culture)
    {
        $existing = DB::table('information_object_i18n')
            ->where('id', $ioId)
            ->where('culture', $culture)
            ->first();

        if ($existing) {
            $currentVal = $existing->$field ?? '';
            $newVal = ($mode === 'append' && !empty($currentVal))
                ? $currentVal . "\n\n" . $text
                : $text;

            DB::table('information_object_i18n')
                ->where('id', $ioId)
                ->where('culture', $culture)
                ->update([$field => $newVal, 'updated_at' => date('Y-m-d H:i:s')]);
        } else {
            DB::table('information_object_i18n')->insert([
                'id'      => $ioId,
                'culture' => $culture,
                $field    => $text,
            ]);
        }
    }

    /**
     * Save alt text to digital_object record.
     */
    protected function saveAltText($ioId, $text, $mode)
    {
        // Find digital object linked to this information object
        $do = DB::table('digital_object')
            ->where('object_id', $ioId)
            ->first();

        if (!$do) {
            return; // No digital object to update
        }

        // Check if digital_object has an i18n table with alt text
        try {
            if (DB::getSchemaBuilder()->hasTable('digital_object_i18n')) {
                $existing = DB::table('digital_object_i18n')
                    ->where('id', $do->id)
                    ->where('culture', sfConfig::get('sf_default_culture', 'en'))
                    ->first();

                if ($existing) {
                    $currentAlt = $existing->alt_text ?? '';
                    $newAlt = ($mode === 'append' && !empty($currentAlt))
                        ? $currentAlt . ' ' . $text
                        : $text;

                    DB::table('digital_object_i18n')
                        ->where('id', $do->id)
                        ->where('culture', sfConfig::get('sf_default_culture', 'en'))
                        ->update(['alt_text' => $newAlt]);
                }
            }
        } catch (\Exception $e) {
            // Table may not exist or have different schema
        }
    }

    /**
     * Log the save action.
     */
    protected function logAudit($ioId, $target, $mode)
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('audit_log')) {
                return;
            }
            DB::table('audit_log')->insert([
                'user_id'     => $this->getUser()->getAttribute('user_id'),
                'action'      => 'voice_ai_save_description',
                'object_type' => 'information_object',
                'object_id'   => $ioId,
                'details'     => json_encode(['target' => $target, 'mode' => $mode]),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            // Silent
        }
    }

    /**
     * Render JSON response.
     */
    protected function renderJson($data, $statusCode = 200)
    {
        $response = $this->getResponse();
        $response->setStatusCode($statusCode);
        $response->setContent(json_encode($data));

        return sfView::NONE;
    }
}
