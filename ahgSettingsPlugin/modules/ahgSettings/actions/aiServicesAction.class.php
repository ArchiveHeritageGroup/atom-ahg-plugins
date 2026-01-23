<?php

use Illuminate\Database\Capsule\Manager as DB;
use AtomExtensions\Services\AclService;

class AhgSettingsAiServicesAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAdministrator()) {
            AclService::forwardUnauthorized();
        }

        // Get current settings from ahg_ner_settings
        $this->settings = [];
        try {
            $rows = DB::table('ahg_ner_settings')->get();
            foreach ($rows as $row) {
                $this->settings[$row->setting_key] = $row->setting_value;
            }
        } catch (Exception $e) {
            $this->settings = [];
        }

        // Set defaults if not present
        $defaults = [
            'ner_enabled' => '1',
            'summarizer_enabled' => '1',
            'spellcheck_enabled' => '0',
            'processing_mode' => 'job',
            'summary_field' => 'scopeAndContent',
            'api_url' => 'http://192.168.0.112:5004/ai/v1',
            'api_key' => 'ahg_ai_demo_internal_2026',
            'api_timeout' => '60',
            'auto_extract_on_upload' => '0',
            'ner_entity_types' => '["PERSON","ORG","GPE","DATE"]',
            'summarizer_max_length' => '500',
            'summarizer_min_length' => '100',
            'spellcheck_language' => 'en_ZA',
            'spellcheck_fields' => '["title","scopeAndContent"]'
        ];

        foreach ($defaults as $key => $value) {
            if (!isset($this->settings[$key])) {
                $this->settings[$key] = $value;
            }
        }

        // Available fields for summary target
        $this->summaryFields = [
            'scopeAndContent' => 'Scope and Content',
            'abstract' => 'Abstract',
            'archivalHistory' => 'Archival History',
            'acquisition' => 'Immediate Source of Acquisition',
            'appraisal' => 'Appraisal, Destruction and Scheduling',
            'arrangement' => 'System of Arrangement',
            'physicalCharacteristics' => 'Physical Characteristics',
            'relatedUnitsOfDescription' => 'Related Units of Description',
            'locationOfOriginals' => 'Location of Originals',
            'locationOfCopies' => 'Location of Copies',
            'findingAids' => 'Finding Aids',
            'generalNote' => 'General Note'
        ];

        // Available languages for spell check
        $this->spellcheckLanguages = [
            'en_US' => 'English (US)',
            'en_GB' => 'English (UK)',
            'en_ZA' => 'English (South Africa)',
            'af_ZA' => 'Afrikaans',
            'zu_ZA' => 'Zulu',
            'xh_ZA' => 'Xhosa',
            'de_DE' => 'German',
            'fr_FR' => 'French',
            'es_ES' => 'Spanish',
            'pt_PT' => 'Portuguese',
            'nl_NL' => 'Dutch'
        ];

        // Available fields for spell check
        $this->spellcheckFields = [
            'title' => 'Title',
            'scopeAndContent' => 'Scope and Content',
            'abstract' => 'Abstract',
            'archivalHistory' => 'Archival History',
            'acquisition' => 'Immediate Source of Acquisition'
        ];

        // Handle form submission
        if ($request->isMethod('post')) {
            $this->processForm($request);
        }
    }

    protected function processForm($request)
    {
        $fieldsToSave = [
            'ner_enabled',
            'summarizer_enabled',
            'spellcheck_enabled',
            'processing_mode',
            'summary_field',
            'api_url',
            'api_key',
            'api_timeout',
            'auto_extract_on_upload',
            'summarizer_max_length',
            'summarizer_min_length',
            'spellcheck_language'
        ];

        try {
            foreach ($fieldsToSave as $field) {
                $value = $request->getParameter($field, '');
                
                // Handle checkboxes
                if (in_array($field, ['ner_enabled', 'summarizer_enabled', 'spellcheck_enabled', 'auto_extract_on_upload'])) {
                    $value = $request->getParameter($field) ? '1' : '0';
                }

                DB::table('ahg_ner_settings')
                    ->updateOrInsert(
                        ['setting_key' => $field],
                        ['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')]
                    );
                
                $this->settings[$field] = $value;
            }

            // Handle entity types (checkboxes to JSON array)
            $entityTypes = [];
            foreach (['PERSON', 'ORG', 'GPE', 'DATE'] as $type) {
                if ($request->getParameter('entity_' . $type)) {
                    $entityTypes[] = $type;
                }
            }
            $entityTypesJson = json_encode($entityTypes);
            DB::table('ahg_ner_settings')
                ->updateOrInsert(
                    ['setting_key' => 'ner_entity_types'],
                    ['setting_value' => $entityTypesJson, 'updated_at' => date('Y-m-d H:i:s')]
                );
            $this->settings['ner_entity_types'] = $entityTypesJson;

            // Handle spellcheck fields (checkboxes to JSON array)
            $spellFields = [];
            foreach (['title', 'scopeAndContent', 'abstract', 'archivalHistory', 'acquisition'] as $field) {
                if ($request->getParameter('spellcheck_field_' . $field)) {
                    $spellFields[] = $field;
                }
            }
            $spellFieldsJson = json_encode($spellFields);
            DB::table('ahg_ner_settings')
                ->updateOrInsert(
                    ['setting_key' => 'spellcheck_fields'],
                    ['setting_value' => $spellFieldsJson, 'updated_at' => date('Y-m-d H:i:s')]
                );
            $this->settings['spellcheck_fields'] = $spellFieldsJson;

            $this->getUser()->setFlash('notice', 'AI Services settings saved successfully.');
        } catch (Exception $e) {
            $this->getUser()->setFlash('error', 'Error saving settings: ' . $e->getMessage());
        }
    }
}
