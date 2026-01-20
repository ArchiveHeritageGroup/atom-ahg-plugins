<?php

class ahgDAMPluginEditAction extends sfIsadPluginEditAction
{
    // Define form fields for DAM
    public static $NAMES = [
        'accessConditions',
        'accruals',
        'acquisition',
        'alternateTitle',
        'appraisal',
        'archivalHistory',
        'arrangement',
        'creators',
        'descriptionDetail',
        'descriptionIdentifier',
        'descriptionStatus',
        'displayStandard',
        'displayStandardUpdateDescendants',
        'extentAndMedium',
        'findingAids',
        'identifier',
        'institutionResponsibleIdentifier',
        'language',
        'languageNotes',
        'languageOfDescription',
        'levelOfDescription',
        'locationOfCopies',
        'locationOfOriginals',
        'nameAccessPoints',
        'genreAccessPoints',
        'subjectAccessPoints',
        'placeAccessPoints',
        'parent',
        'publicationStatus',
        'relatedUnitsOfDescription',
        'repository',
        'reproductionConditions',
        'revisionHistory',
        'rules',
        'scopeAndContent',
        'scriptOfDescription',
        'sources',
        'title',
    ];

    public function execute($request)
    {
        // Call grandparent (DefaultEditAction) execute for setup
        DefaultEditAction::execute($request);

        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters());
            if ($this->form->isValid()) {
                $this->processForm();
                $this->resource->save();

                // Save IPTC and location data AFTER resource has ID
                error_log("DAM execute: about to save IPTC, resourceId=" . $this->resource->id);
                $this->saveIptcMetadataDirectly();
                $this->saveItemLocation();
                $this->updateDisplayObjectConfig();

                // Save film metadata (versions, holdings, links)
                $this->saveVersionLinks();
                $this->saveFormatHoldings();
                $this->saveExternalLinks();

                $this->resource->updateXmlExports();
                $this->redirect([$this->resource, 'module' => 'informationobject']);
            }
        }


        // Load IPTC data for template
        if ($this->resource->id) {
            $this->iptc = \Illuminate\Database\Capsule\Manager::table('dam_iptc_metadata')
                ->where('object_id', $this->resource->id)
                ->first();

            // Load film metadata: versions, holdings, links
            $this->versionLinks = \Illuminate\Database\Capsule\Manager::table('dam_version_links')
                ->where('object_id', $this->resource->id)
                ->orderBy('id')
                ->get();

            $this->formatHoldings = \Illuminate\Database\Capsule\Manager::table('dam_format_holdings')
                ->where('object_id', $this->resource->id)
                ->orderBy('id')
                ->get();

            $this->externalLinks = \Illuminate\Database\Capsule\Manager::table('dam_external_links')
                ->where('object_id', $this->resource->id)
                ->orderBy('id')
                ->get();
        } else {
            $this->iptc = (object)[];
            $this->versionLinks = collect([]);
            $this->formatHoldings = collect([]);
            $this->externalLinks = collect([]);
        }
        QubitDescription::addAssets($this->response);
    }

    protected function updateDisplayObjectConfig()
    {
        if (!$this->resource->id) return;
        
        $existing = \Illuminate\Database\Capsule\Manager::table('display_object_config')
            ->where('object_id', $this->resource->id)
            ->first();
        
        if ($existing) {
            \Illuminate\Database\Capsule\Manager::table('display_object_config')
                ->where('object_id', $this->resource->id)
                ->update(['object_type' => 'dam', 'updated_at' => date('Y-m-d H:i:s')]);
        } else {
            \Illuminate\Database\Capsule\Manager::table('display_object_config')
                ->insert([
                    'object_id' => $this->resource->id,
                    'object_type' => 'dam',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }
    }

    protected function saveItemLocation()
    {
        if (!$this->resource->id) return;
        
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/ItemPhysicalLocationRepository.php';
        $locRepo = new \AtomFramework\Repositories\ItemPhysicalLocationRepository();
        $request = $this->getRequest();
        $locationData = [
            'physical_object_id' => $request->getParameter('item_physical_object_id') ?: null,
            'barcode' => $request->getParameter('item_barcode'),
            'box_number' => $request->getParameter('item_box_number'),
            'folder_number' => $request->getParameter('item_folder_number'),
            'shelf' => $request->getParameter('item_shelf'),
            'row' => $request->getParameter('item_row'),
            'position' => $request->getParameter('item_position'),
            'item_number' => $request->getParameter('item_item_number'),
            'extent_value' => $request->getParameter('item_extent_value') ?: null,
            'extent_unit' => $request->getParameter('item_extent_unit'),
            'condition_status' => $request->getParameter('item_condition_status') ?: null,
            'condition_notes' => $request->getParameter('item_condition_notes'),
            'access_status' => $request->getParameter('item_access_status') ?: 'available',
            'notes' => $request->getParameter('item_location_notes'),
        ];
        if (array_filter($locationData)) {
            $locRepo->saveLocationData((int)$this->resource->id, $locationData);
        }
    }

    protected function processField($field)
    {
        switch ($field->getName()) {
            case 'displayStandard':
                // Force DAM display standard
                $damTemplateId = \Illuminate\Database\Capsule\Manager::table('term')
                    ->where('taxonomy_id', 70)
                    ->where('code', 'dam')
                    ->value('id');
                if ($damTemplateId) {
                    $this->resource->displayStandardId = $damTemplateId;
                }
                break;

            default:
                return parent::processField($field);
        }
    }

    protected function processForm()
    {
        // Capture old values for audit trail
        $isNew = !isset($this->resource->id) || !$this->resource->id;
        $oldValues = [];
        if (!$isNew && $this->resource->id) {
            $oldValues = $this->captureCurrentValues((int)$this->resource->id);
        }

        // Call parent to process form fields
        parent::processForm();

        // Audit trail (only for edits with existing ID)
        if ($this->resource->id) {
            $newValues = $this->captureCurrentValues((int)$this->resource->id);
            $this->logAudit($isNew ? 'create' : 'update', (int)$this->resource->id, $oldValues, $newValues);
        }
    }

    protected function saveIptcMetadataDirectly()
    {
        $request = $this->getRequest();
        error_log("DAM saveIptc: resourceId=" . $this->resource->id);
        try {
            $objectId = $this->resource->id;
            if (!$objectId) {
                error_log("DAM IPTC Save Error: No object ID");
                return;
            }

            // Get or create IPTC record
            $existing = \Illuminate\Database\Capsule\Manager::table('dam_iptc_metadata')
                ->where('object_id', $objectId)
                ->first();

            $iptcData = [
                'object_id' => $objectId,
                'creator' => $request->getParameter('iptc_creator'),
                'creator_job_title' => $request->getParameter('iptc_creator_job_title'),
                'creator_email' => $request->getParameter('iptc_creator_email'),
                'creator_phone' => $request->getParameter('iptc_creator_phone'),
                'creator_website' => $request->getParameter('iptc_creator_website'),
                'creator_city' => $request->getParameter('iptc_creator_city'),
                'creator_address' => $request->getParameter('iptc_creator_address'),
                'headline' => $request->getParameter('iptc_headline'),
                'duration_minutes' => $request->getParameter('duration_minutes') ?: null,
                'production_country' => $request->getParameter('production_country'),
                'production_country_code' => $request->getParameter('production_country_code'),
                'caption' => $request->getParameter('iptc_caption'),
                'keywords' => $request->getParameter('iptc_keywords'),
                'iptc_subject_code' => $request->getParameter('iptc_subject_code'),
                'intellectual_genre' => $request->getParameter('iptc_intellectual_genre'),
                'persons_shown' => $request->getParameter('iptc_persons_shown'),
                'date_created' => $request->getParameter('iptc_date_created') ?: null,
                'city' => $request->getParameter('iptc_city'),
                'state_province' => $request->getParameter('iptc_state_province'),
                'country' => $request->getParameter('iptc_country'),
                'country_code' => $request->getParameter('iptc_country_code'),
                'sublocation' => $request->getParameter('iptc_sublocation'),
                'credit_line' => $request->getParameter('iptc_credit_line'),
                'source' => $request->getParameter('iptc_source'),
                'copyright_notice' => $request->getParameter('iptc_copyright_notice'),
                'rights_usage_terms' => $request->getParameter('iptc_rights_usage_terms'),
                'license_type' => $request->getParameter('iptc_license_type') ?: null,
                'license_url' => $request->getParameter('iptc_license_url'),
                'license_expiry' => $request->getParameter('iptc_license_expiry') ?: null,
                'model_release_status' => $request->getParameter('iptc_model_release_status') ?: null,
                'model_release_id' => $request->getParameter('iptc_model_release_id'),
                'property_release_status' => $request->getParameter('iptc_property_release_status') ?: null,
                'property_release_id' => $request->getParameter('iptc_property_release_id'),
                'artwork_title' => $request->getParameter('iptc_artwork_title'),
                'artwork_creator' => $request->getParameter('iptc_artwork_creator'),
                'artwork_date' => $request->getParameter('iptc_artwork_date'),
                'artwork_source' => $request->getParameter('iptc_artwork_source'),
                'artwork_copyright' => $request->getParameter('iptc_artwork_copyright'),
                'title' => $request->getParameter('iptc_title'),
                'job_id' => $request->getParameter('iptc_job_id'),
                'instructions' => $request->getParameter('iptc_instructions'),
                'asset_type' => $request->getParameter('asset_type') ?: null,
                'genre' => $request->getParameter('genre'),
                'color_type' => $request->getParameter('color_type') ?: null,
                'audio_language' => $request->getParameter('audio_language'),
                'subtitle_language' => $request->getParameter('subtitle_language'),
                'production_company' => $request->getParameter('production_company'),
                'distributor' => $request->getParameter('distributor'),
                'broadcast_date' => $request->getParameter('broadcast_date') ?: null,
                'awards' => $request->getParameter('awards'),
                'series_title' => $request->getParameter('series_title'),
                'season_number' => $request->getParameter('season_number') ?: null,
                'episode_number' => $request->getParameter('episode_number') ?: null,
                'contributors_json' => $this->buildContributorsJson($request),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($existing) {
                \Illuminate\Database\Capsule\Manager::table('dam_iptc_metadata')
                    ->where('object_id', $objectId)
                    ->update($iptcData);
            } else {
                $iptcData['created_at'] = date('Y-m-d H:i:s');
                \Illuminate\Database\Capsule\Manager::table('dam_iptc_metadata')
                    ->insert($iptcData);
            }
        } catch (\Exception $e) {
            error_log("DAM IPTC Save Error: " . $e->getMessage());
        }
    }

    protected function captureCurrentValues(int $resourceId): array
    {
        $values = [];
        try {
            $io = \Illuminate\Database\Capsule\Manager::table('information_object')
                ->where('id', $resourceId)
                ->first();
            if ($io) {
                $values['identifier'] = $io->identifier;
                $values['level_of_description_id'] = $io->level_of_description_id;
            }
            $iptc = \Illuminate\Database\Capsule\Manager::table('dam_iptc_metadata')
                ->where('object_id', $resourceId)
                ->first();
            if ($iptc) {
                $values['iptc'] = (array)$iptc;
            }
        } catch (\Exception $e) {
            error_log("DAM capture error: " . $e->getMessage());
        }
        return $values;
    }

    protected function logAudit(string $action, int $resourceId, array $oldValues, array $newValues): void
    {
        try {
            $user = sfContext::getInstance()->getUser();
            $userId = $user->isAuthenticated() ? $user->getAttribute('user_id') : null;
            $username = $user->isAuthenticated() ? $user->getAttribute('username', 'Unknown') : 'Anonymous';

            \Illuminate\Database\Capsule\Manager::table('audit_log')->insert([
                'object_type' => 'QubitInformationObject',
                'object_id' => $resourceId,
                'action' => $action,
                'user_id' => $userId,
                'username' => $username,
                'old_values' => json_encode($oldValues),
                'new_values' => json_encode($newValues),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            error_log("DAM audit error: " . $e->getMessage());
        }
    }

    protected function addField($name)
    {
        switch ($name) {
            case 'levelOfDescription':
                // Filter levels by DAM sector
                $this->form->setDefault('levelOfDescription', $this->resource->levelOfDescription ? sfConfig::get('sf_script_name', '/index.php') . '/' . $this->resource->levelOfDescription->slug : null);
                $this->form->setValidator('levelOfDescription', new sfValidatorString());
                $choices = [];
                $choices[null] = null;
                // Get DAM sector levels from database
                $culture = $this->context->user->getCulture() ?? 'en';
                $levels = \Illuminate\Database\Capsule\Manager::table('level_of_description_sector as los')
                    ->join('term', 'los.term_id', '=', 'term.id')
                    ->join('term_i18n', function($j) use ($culture) {
                        $j->on('term.id', '=', 'term_i18n.id')->where('term_i18n.culture', '=', $culture);
                    })
                    ->where('los.sector', 'dam')
                    ->orderBy('los.display_order')
                    ->select('term.id', 'term_i18n.name')
                    ->get();
                // Get terms from QubitTaxonomy and filter by sector levels
                $levelIds = $levels->pluck('id')->toArray();
                foreach (QubitTaxonomy::getTaxonomyTerms(QubitTaxonomy::LEVEL_OF_DESCRIPTION_ID) as $item) {
                    if (in_array($item->id, $levelIds)) {
                        // Use slug-based URL to avoid routing prefix issues
                        $url = sfConfig::get('sf_script_name', '/index.php') . '/' . $item->slug;
                        $choices[$url] = $item;
                    }
                }
                $this->form->setWidget('levelOfDescription', new sfWidgetFormSelect(['choices' => $choices], ['class' => 'form-select']));
                break;
            default:
                return parent::addField($name);
        }
    }

    /**
     * Build contributors JSON from form arrays
     */
    protected function buildContributorsJson($request)
    {
        $roles = $request->getParameter('credit_role', []);
        $names = $request->getParameter('credit_name', []);

        $contributors = [];
        foreach ($roles as $index => $role) {
            if (!empty($role) && !empty($names[$index])) {
                $contributors[] = [
                    'role' => trim($role),
                    'name' => trim($names[$index])
                ];
            }
        }

        return !empty($contributors) ? json_encode($contributors) : null;
    }

    /**
     * Save alternative version links
     */
    protected function saveVersionLinks()
    {
        if (!$this->resource->id) return;

        $request = $this->getRequest();
        $objectId = $this->resource->id;

        try {
            $ids = $request->getParameter('version_id', []);
            $titles = $request->getParameter('version_title', []);
            $types = $request->getParameter('version_type', []);
            $languages = $request->getParameter('version_language', []);
            $languageCodes = $request->getParameter('version_language_code', []);
            $years = $request->getParameter('version_year', []);
            $notes = $request->getParameter('version_notes', []);

            // Get existing IDs to track which to delete
            $existingIds = \Illuminate\Database\Capsule\Manager::table('dam_version_links')
                ->where('object_id', $objectId)
                ->pluck('id')
                ->toArray();
            $processedIds = [];

            foreach ($titles as $index => $title) {
                if (empty(trim($title))) continue;

                $data = [
                    'object_id' => $objectId,
                    'title' => trim($title),
                    'version_type' => $types[$index] ?? 'language',
                    'language_name' => $languages[$index] ?? null,
                    'language_code' => $languageCodes[$index] ?? null,
                    'year' => $years[$index] ?? null,
                    'notes' => $notes[$index] ?? null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                $id = $ids[$index] ?? null;
                if (!empty($id) && in_array($id, $existingIds)) {
                    // Update existing
                    \Illuminate\Database\Capsule\Manager::table('dam_version_links')
                        ->where('id', $id)
                        ->update($data);
                    $processedIds[] = (int)$id;
                } else {
                    // Insert new
                    $data['created_at'] = date('Y-m-d H:i:s');
                    $newId = \Illuminate\Database\Capsule\Manager::table('dam_version_links')->insertGetId($data);
                    $processedIds[] = $newId;
                }
            }

            // Delete removed entries
            $toDelete = array_diff($existingIds, $processedIds);
            if (!empty($toDelete)) {
                \Illuminate\Database\Capsule\Manager::table('dam_version_links')
                    ->whereIn('id', $toDelete)
                    ->delete();
            }
        } catch (\Exception $e) {
            error_log("DAM saveVersionLinks Error: " . $e->getMessage());
        }
    }

    /**
     * Save format holdings at institutions
     */
    protected function saveFormatHoldings()
    {
        if (!$this->resource->id) return;

        $request = $this->getRequest();
        $objectId = $this->resource->id;

        try {
            $ids = $request->getParameter('holding_id', []);
            $formats = $request->getParameter('holding_format', []);
            $formatDetails = $request->getParameter('holding_format_details', []);
            $institutions = $request->getParameter('holding_institution', []);
            $locations = $request->getParameter('holding_location', []);
            $accessions = $request->getParameter('holding_accession', []);
            $conditions = $request->getParameter('holding_condition', []);
            $accessStatuses = $request->getParameter('holding_access', []);
            $urls = $request->getParameter('holding_url', []);
            $accessNotes = $request->getParameter('holding_access_notes', []);
            $verified = $request->getParameter('holding_verified', []);
            $primaries = $request->getParameter('holding_primary', []);
            $notes = $request->getParameter('holding_notes', []);

            // Get existing IDs to track which to delete
            $existingIds = \Illuminate\Database\Capsule\Manager::table('dam_format_holdings')
                ->where('object_id', $objectId)
                ->pluck('id')
                ->toArray();
            $processedIds = [];

            foreach ($formats as $index => $format) {
                $institution = $institutions[$index] ?? '';
                if (empty(trim($institution))) continue;

                $id = $ids[$index] ?? null;
                $isPrimary = in_array($id, $primaries ?: []) ? 1 : 0;

                $data = [
                    'object_id' => $objectId,
                    'format_type' => $format ?? 'Other',
                    'format_details' => $formatDetails[$index] ?? null,
                    'holding_institution' => trim($institution),
                    'holding_location' => $locations[$index] ?? null,
                    'accession_number' => $accessions[$index] ?? null,
                    'condition_status' => $conditions[$index] ?? 'unknown',
                    'access_status' => $accessStatuses[$index] ?? 'unknown',
                    'access_url' => $urls[$index] ?? null,
                    'access_notes' => $accessNotes[$index] ?? null,
                    'verified_date' => !empty($verified[$index]) ? $verified[$index] : null,
                    'is_primary' => $isPrimary,
                    'notes' => $notes[$index] ?? null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                if (!empty($id) && in_array($id, $existingIds)) {
                    // Update existing
                    \Illuminate\Database\Capsule\Manager::table('dam_format_holdings')
                        ->where('id', $id)
                        ->update($data);
                    $processedIds[] = (int)$id;
                } else {
                    // Insert new
                    $data['created_at'] = date('Y-m-d H:i:s');
                    $newId = \Illuminate\Database\Capsule\Manager::table('dam_format_holdings')->insertGetId($data);
                    $processedIds[] = $newId;
                }
            }

            // Delete removed entries
            $toDelete = array_diff($existingIds, $processedIds);
            if (!empty($toDelete)) {
                \Illuminate\Database\Capsule\Manager::table('dam_format_holdings')
                    ->whereIn('id', $toDelete)
                    ->delete();
            }
        } catch (\Exception $e) {
            error_log("DAM saveFormatHoldings Error: " . $e->getMessage());
        }
    }

    /**
     * Save external reference links (ESAT, IMDb, etc.)
     */
    protected function saveExternalLinks()
    {
        if (!$this->resource->id) return;

        $request = $this->getRequest();
        $objectId = $this->resource->id;

        try {
            $ids = $request->getParameter('link_id', []);
            $types = $request->getParameter('link_type', []);
            $urls = $request->getParameter('link_url', []);
            $titles = $request->getParameter('link_title', []);
            $descriptions = $request->getParameter('link_description', []);
            $persons = $request->getParameter('link_person', []);
            $roles = $request->getParameter('link_role', []);
            $verified = $request->getParameter('link_verified', []);
            $primaries = $request->getParameter('link_primary', []);

            // Get existing IDs to track which to delete
            $existingIds = \Illuminate\Database\Capsule\Manager::table('dam_external_links')
                ->where('object_id', $objectId)
                ->pluck('id')
                ->toArray();
            $processedIds = [];

            foreach ($urls as $index => $url) {
                if (empty(trim($url))) continue;

                $id = $ids[$index] ?? null;
                $isPrimary = in_array($id, $primaries ?: []) ? 1 : 0;

                $data = [
                    'object_id' => $objectId,
                    'link_type' => $types[$index] ?? 'Other',
                    'url' => trim($url),
                    'title' => $titles[$index] ?? null,
                    'description' => $descriptions[$index] ?? null,
                    'person_name' => $persons[$index] ?? null,
                    'person_role' => $roles[$index] ?? null,
                    'verified_date' => !empty($verified[$index]) ? $verified[$index] : null,
                    'is_primary' => $isPrimary,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                if (!empty($id) && in_array($id, $existingIds)) {
                    // Update existing
                    \Illuminate\Database\Capsule\Manager::table('dam_external_links')
                        ->where('id', $id)
                        ->update($data);
                    $processedIds[] = (int)$id;
                } else {
                    // Insert new
                    $data['created_at'] = date('Y-m-d H:i:s');
                    $newId = \Illuminate\Database\Capsule\Manager::table('dam_external_links')->insertGetId($data);
                    $processedIds[] = $newId;
                }
            }

            // Delete removed entries
            $toDelete = array_diff($existingIds, $processedIds);
            if (!empty($toDelete)) {
                \Illuminate\Database\Capsule\Manager::table('dam_external_links')
                    ->whereIn('id', $toDelete)
                    ->delete();
            }
        } catch (\Exception $e) {
            error_log("DAM saveExternalLinks Error: " . $e->getMessage());
        }
    }
}
