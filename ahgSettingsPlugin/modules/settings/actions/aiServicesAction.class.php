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
            'translation_enabled' => '1',
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
            'spellcheck_fields' => '["title","scopeAndContent"]',
            'mt_endpoint' => 'http://127.0.0.1:5100/translate',
            'mt_timeout' => '30',
            'translation_source_lang' => 'en',
            'translation_target_lang' => 'af',
            'translation_fields' => '["title","scope_and_content"]',
            'translation_mode' => 'review',
            'translation_overwrite' => '0',
            'translation_sector' => 'archives',
            'translation_save_culture' => '1',
            'translation_field_mappings' => '{}'
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

        // Translation languages (OPUS-MT supported) with culture codes for AtoM
        $this->translationLanguages = [
            'en' => ['name' => 'English', 'culture' => 'en'],
            'af' => ['name' => 'Afrikaans', 'culture' => 'af'],
            'zu' => ['name' => 'Zulu', 'culture' => 'zu'],
            'xh' => ['name' => 'Xhosa', 'culture' => 'xh'],
            'st' => ['name' => 'Sotho', 'culture' => 'st'],
            'tn' => ['name' => 'Tswana', 'culture' => 'tn'],
            'nso' => ['name' => 'Northern Sotho (Sepedi)', 'culture' => 'nso'],
            'ss' => ['name' => 'Swati', 'culture' => 'ss'],
            've' => ['name' => 'Venda', 'culture' => 've'],
            'ts' => ['name' => 'Tsonga', 'culture' => 'ts'],
            'nr' => ['name' => 'Ndebele', 'culture' => 'nr'],
            'sw' => ['name' => 'Swahili', 'culture' => 'sw'],
            'yo' => ['name' => 'Yoruba', 'culture' => 'yo'],
            'ig' => ['name' => 'Igbo', 'culture' => 'ig'],
            'ha' => ['name' => 'Hausa', 'culture' => 'ha'],
            'am' => ['name' => 'Amharic', 'culture' => 'am'],
            'nl' => ['name' => 'Dutch', 'culture' => 'nl'],
            'fr' => ['name' => 'French', 'culture' => 'fr'],
            'de' => ['name' => 'German', 'culture' => 'de'],
            'es' => ['name' => 'Spanish', 'culture' => 'es'],
            'pt' => ['name' => 'Portuguese', 'culture' => 'pt'],
            'it' => ['name' => 'Italian', 'culture' => 'it'],
            'ar' => ['name' => 'Arabic', 'culture' => 'ar'],
            'ru' => ['name' => 'Russian', 'culture' => 'ru'],
            'zh' => ['name' => 'Chinese', 'culture' => 'zh']
        ];

        // Translatable fields by GLAM/DAM sector
        $this->translationFieldsBySector = [
            'archives' => [
                'title' => 'Title',
                'scope_and_content' => 'Scope and Content',
                'archival_history' => 'Archival History',
                'acquisition' => 'Immediate Source of Acquisition',
                'arrangement' => 'System of Arrangement',
                'access_conditions' => 'Conditions Governing Access',
                'reproduction_conditions' => 'Conditions Governing Reproduction',
                'finding_aids' => 'Finding Aids',
                'related_units_of_description' => 'Related Units of Description',
                'appraisal' => 'Appraisal, Destruction and Scheduling',
                'accruals' => 'Accruals',
                'physical_characteristics' => 'Physical Characteristics',
                'location_of_originals' => 'Location of Originals',
                'location_of_copies' => 'Location of Copies'
            ],
            'library' => [
                'title' => 'Title',
                'alternate_title' => 'Alternate Title',
                'edition' => 'Edition',
                'extent_and_medium' => 'Extent and Medium',
                'scope_and_content' => 'Scope and Content (Abstract)',
                'access_conditions' => 'Access Conditions',
                'reproduction_conditions' => 'Reproduction Conditions',
                'physical_characteristics' => 'Physical Description',
                'sources' => 'Sources'
            ],
            'museum' => [
                'title' => 'Object Name/Title',
                'alternate_title' => 'Other Names',
                'scope_and_content' => 'Description',
                'archival_history' => 'Provenance',
                'acquisition' => 'Acquisition Method',
                'physical_characteristics' => 'Physical Description',
                'access_conditions' => 'Display Conditions',
                'location_of_originals' => 'Current Location',
                'related_units_of_description' => 'Related Objects'
            ],
            'gallery' => [
                'title' => 'Artwork Title',
                'alternate_title' => 'Alternative Titles',
                'scope_and_content' => 'Description/Statement',
                'archival_history' => 'Provenance',
                'acquisition' => 'Acquisition',
                'physical_characteristics' => 'Medium and Dimensions',
                'access_conditions' => 'Exhibition Conditions',
                'reproduction_conditions' => 'Copyright/Reproduction',
                'location_of_originals' => 'Current Location'
            ],
            'dam' => [
                'title' => 'Asset Title',
                'alternate_title' => 'Alt Text',
                'scope_and_content' => 'Description',
                'access_conditions' => 'Usage Rights',
                'reproduction_conditions' => 'License Terms',
                'sources' => 'Source/Credits',
                'finding_aids' => 'Keywords/Tags'
            ]
        ];

        // All available translatable fields (union of all sectors)
        $this->allTranslatableFields = [
            'title' => 'Title',
            'alternate_title' => 'Alternate Title',
            'edition' => 'Edition',
            'extent_and_medium' => 'Extent and Medium',
            'archival_history' => 'Archival History',
            'acquisition' => 'Immediate Source of Acquisition',
            'scope_and_content' => 'Scope and Content',
            'appraisal' => 'Appraisal',
            'accruals' => 'Accruals',
            'arrangement' => 'Arrangement',
            'access_conditions' => 'Access Conditions',
            'reproduction_conditions' => 'Reproduction Conditions',
            'physical_characteristics' => 'Physical Characteristics',
            'finding_aids' => 'Finding Aids',
            'location_of_originals' => 'Location of Originals',
            'location_of_copies' => 'Location of Copies',
            'related_units_of_description' => 'Related Units of Description',
            'rules' => 'Rules/Conventions',
            'sources' => 'Sources',
            'revision_history' => 'Revision History'
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
            'translation_enabled',
            'processing_mode',
            'summary_field',
            'api_url',
            'api_key',
            'api_timeout',
            'auto_extract_on_upload',
            'summarizer_max_length',
            'summarizer_min_length',
            'spellcheck_language',
            'mt_endpoint',
            'mt_timeout',
            'translation_source_lang',
            'translation_target_lang',
            'translation_mode',
            'translation_overwrite',
            'translation_sector',
            'translation_save_culture'
        ];

        try {
            foreach ($fieldsToSave as $field) {
                $value = $request->getParameter($field, '');
                
                // Handle checkboxes
                if (in_array($field, ['ner_enabled', 'summarizer_enabled', 'spellcheck_enabled', 'translation_enabled', 'auto_extract_on_upload', 'translation_overwrite', 'translation_save_culture'])) {
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

            // Handle translation fields (checkboxes to JSON array)
            // Use all translatable fields from the database schema
            $allFields = [
                'title', 'alternate_title', 'edition', 'extent_and_medium',
                'archival_history', 'acquisition', 'scope_and_content',
                'appraisal', 'accruals', 'arrangement', 'access_conditions',
                'reproduction_conditions', 'physical_characteristics', 'finding_aids',
                'location_of_originals', 'location_of_copies', 'related_units_of_description',
                'rules', 'sources', 'revision_history'
            ];
            $translateFields = [];
            foreach ($allFields as $field) {
                if ($request->getParameter('translate_field_' . $field)) {
                    $translateFields[] = $field;
                }
            }
            $translateFieldsJson = json_encode($translateFields);
            DB::table('ahg_ner_settings')
                ->updateOrInsert(
                    ['setting_key' => 'translation_fields'],
                    ['setting_value' => $translateFieldsJson, 'updated_at' => date('Y-m-d H:i:s')]
                );
            $this->settings['translation_fields'] = $translateFieldsJson;

            // Handle field mappings (source -> target)
            $fieldMappings = [];
            foreach ($allFields as $field) {
                $targetField = $request->getParameter('translate_target_' . $field, $field);
                // Only store if different from source (to keep data compact)
                if ($targetField !== $field) {
                    $fieldMappings[$field] = $targetField;
                }
            }
            $fieldMappingsJson = json_encode($fieldMappings);
            DB::table('ahg_ner_settings')
                ->updateOrInsert(
                    ['setting_key' => 'translation_field_mappings'],
                    ['setting_value' => $fieldMappingsJson, 'updated_at' => date('Y-m-d H:i:s')]
                );
            $this->settings['translation_field_mappings'] = $fieldMappingsJson;

            // Also update ahg_translation_settings table for the plugin
            try {
                $mtEndpoint = $request->getParameter('mt_endpoint', 'http://127.0.0.1:5100/translate');
                $mtTimeout = $request->getParameter('mt_timeout', '30');
                $targetLang = $request->getParameter('translation_target_lang', 'af');

                DB::table('ahg_translation_settings')
                    ->updateOrInsert(['setting_key' => 'mt.endpoint'], ['setting_value' => $mtEndpoint]);
                DB::table('ahg_translation_settings')
                    ->updateOrInsert(['setting_key' => 'mt.timeout_seconds'], ['setting_value' => $mtTimeout]);
                DB::table('ahg_translation_settings')
                    ->updateOrInsert(['setting_key' => 'mt.target_culture'], ['setting_value' => $targetLang]);
            } catch (Exception $e) {
                // Table might not exist yet
            }

            $this->getUser()->setFlash('notice', 'AI Services settings saved successfully.');
        } catch (Exception $e) {
            $this->getUser()->setFlash('error', 'Error saving settings: ' . $e->getMessage());
        }
    }
}
