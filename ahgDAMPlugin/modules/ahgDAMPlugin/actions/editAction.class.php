<?php
use Illuminate\Database\Capsule\Manager as DB;

/*
 * DAM (IPTC/XMP) Edit Action
 */

class ahgDAMPluginEditAction extends InformationObjectEditAction
{
    public static $NAMES = [
        'accessConditions',
        'creators',
        'extentAndMedium',
        'identifier',
        'language',
        'languageOfDescription',
        'levelOfDescription',
        'nameAccessPoints',
        'genreAccessPoints',
        'placeAccessPoints',
        'repository',
        'reproductionConditions',
        'rules',
        'script',
        'scriptOfDescription',
        'scopeAndContent',
        'sources',
        'subjectAccessPoints',
        'title',
        'displayStandard',
        'displayStandardUpdateDescendants',
    ];

    protected function earlyExecute()
    {
        parent::earlyExecute();

        $title = $this->context->i18n->__('Add new DAM asset');
        if (isset($this->getRoute()->resource)) {
            if (1 > strlen($title = $this->resource->__toString())) {
                $title = $this->context->i18n->__('Untitled');
            }

            $title = $this->context->i18n->__('Edit %1%', ['%1%' => $title]);
        }

        $this->response->setTitle("{$title} - {$this->response->getTitle()}");

        // Load IPTC metadata
        $this->loadIptcMetadata();
        // Load item physical location
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/ItemPhysicalLocationRepository.php';
        $locRepo = new \AtomFramework\Repositories\ItemPhysicalLocationRepository();
        $this->itemLocation = ($this->resource && $this->resource->id) ? $locRepo->getLocationWithContainer($this->resource->id) ?? [] : [];
        // Load item physical location
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/ItemPhysicalLocationRepository.php';
        $locRepo = new \AtomFramework\Repositories\ItemPhysicalLocationRepository();
        $this->itemLocation = ($this->resource && $this->resource->id) ? $locRepo->getLocationWithContainer($this->resource->id) ?? [] : [];
    }

    protected function loadIptcMetadata()
    {
        if ($this->resource && $this->resource->id) {
            $this->iptc = \Illuminate\Database\Capsule\Manager::table('dam_iptc_metadata')
                ->where('object_id', $this->resource->id)
                ->first();
        } else {
            $this->iptc = null;
        }
    }

    protected function addField($name)
    {
        switch ($name) {
            case 'levelOfDescription':
                // Filter levels by DAM sector
                $this->form->setDefault('levelOfDescription', $this->context->routing->generate(null, [$this->resource->levelOfDescription, 'module' => 'term']));
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
                
                foreach ($levels as $level) {
                    $term = QubitTerm::getById($level->id);
                    if ($term) {
                        $choices[$this->context->routing->generate(null, [$term, 'module' => 'term'])] = $level->name;
                    }
                }
                
                $this->form->setWidget('levelOfDescription', new sfWidgetFormSelect(['choices' => $choices]));
                break;
                
            default:
                return parent::addField($name);
        }
    }

    protected function processField($field)
    {
        switch ($field->getName()) {
            default:
                return parent::processField($field);
        }
    }

    protected function processForm()
    {
        // Capture old values for audit trail
        $isNew = !isset($this->resource->id) || !$this->resource->id;
        $oldValues = [];
        if (!$isNew && $this->resource) {
            $oldValues = $this->captureCurrentValues($this->resource->id);
        }
        
        // Call parent first to save the information object
        parent::processForm();
        
        // Now save IPTC data - resource should have an ID now
        $this->saveIptcMetadataDirectly();
        // Save item physical location
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
            $locRepo->saveLocationData($this->resource->id, $locationData);
        }
        
        // Capture new values and log audit trail
        $newValues = $this->captureCurrentValues($this->resource->id);
        $this->logAudit($isNew ? 'create' : 'update', $this->resource->id, $oldValues, $newValues);
    }

    protected function saveIptcMetadataDirectly()
    {
        $request = $this->getRequest();
        
        try {
            $objectId = $this->resource->id;
            
            if (!$objectId) {
                error_log("DAM IPTC Save Error: No object ID");
                return;
            }
            
            $data = [
                'creator' => $request->getParameter('iptc_creator') ?: null,
                'creator_job_title' => $request->getParameter('iptc_creator_job_title') ?: null,
                'creator_email' => $request->getParameter('iptc_creator_email') ?: null,
                'creator_website' => $request->getParameter('iptc_creator_website') ?: null,
                'creator_phone' => $request->getParameter('iptc_creator_phone') ?: null,
                'creator_city' => $request->getParameter('iptc_creator_city') ?: null,
                'creator_address' => $request->getParameter('iptc_creator_address') ?: null,
                'headline' => $request->getParameter('iptc_headline') ?: null,
                'caption' => $request->getParameter('iptc_caption') ?: null,
                'keywords' => $request->getParameter('iptc_keywords') ?: null,
                'iptc_subject_code' => $request->getParameter('iptc_subject_code') ?: null,
                'intellectual_genre' => $request->getParameter('iptc_intellectual_genre') ?: null,
                'persons_shown' => $request->getParameter('iptc_persons_shown') ?: null,
                'date_created' => $request->getParameter('iptc_date_created') ?: null,
                'city' => $request->getParameter('iptc_city') ?: null,
                'state_province' => $request->getParameter('iptc_state_province') ?: null,
                'country' => $request->getParameter('iptc_country') ?: null,
                'country_code' => $request->getParameter('iptc_country_code') ?: null,
                'sublocation' => $request->getParameter('iptc_sublocation') ?: null,
                'credit_line' => $request->getParameter('iptc_credit_line') ?: null,
                'source' => $request->getParameter('iptc_source') ?: null,
                'copyright_notice' => $request->getParameter('iptc_copyright_notice') ?: null,
                'rights_usage_terms' => $request->getParameter('iptc_rights_usage_terms') ?: null,
                'license_type' => $request->getParameter('iptc_license_type') ?: null,
                'license_url' => $request->getParameter('iptc_license_url') ?: null,
                'license_expiry' => $request->getParameter('iptc_license_expiry') ?: null,
                'model_release_status' => $request->getParameter('iptc_model_release_status') ?: 'none',
                'model_release_id' => $request->getParameter('iptc_model_release_id') ?: null,
                'property_release_status' => $request->getParameter('iptc_property_release_status') ?: 'none',
                'property_release_id' => $request->getParameter('iptc_property_release_id') ?: null,
                'artwork_title' => $request->getParameter('iptc_artwork_title') ?: null,
                'artwork_creator' => $request->getParameter('iptc_artwork_creator') ?: null,
                'artwork_date' => $request->getParameter('iptc_artwork_date') ?: null,
                'artwork_source' => $request->getParameter('iptc_artwork_source') ?: null,
                'artwork_copyright' => $request->getParameter('iptc_artwork_copyright') ?: null,
                'title' => $request->getParameter('iptc_title') ?: null,
                'job_id' => $request->getParameter('iptc_job_id') ?: null,
                'instructions' => $request->getParameter('iptc_instructions') ?: null,
            ];

            // Debug log
            error_log("DAM IPTC Save: objectId={$objectId}, creator=" . ($data['creator'] ?? 'null'));

            // Check if record exists
            $exists = DB::selectOne("SELECT id FROM dam_iptc_metadata WHERE object_id = ?", [$objectId]);

            if ($exists) {
                // Update using Laravel DB
                $setClauses = [];
                $values = [];
                foreach ($data as $key => $value) {
                    $setClauses[] = "`{$key}` = ?";
                    $values[] = $value;
                }
                $setClauses[] = "`updated_at` = NOW()";
                $values[] = $objectId;
                
                $sql = "UPDATE dam_iptc_metadata SET " . implode(', ', $setClauses) . " WHERE object_id = ?";
                DB::statement($sql, $values);
            } else {
                // Insert
                $columns = array_keys($data);
                $columns[] = 'object_id';
                $columns[] = 'created_at';
                $columns[] = 'updated_at';
                
                $values = array_values($data);
                $values[] = $objectId;
                $values[] = date('Y-m-d H:i:s');
                $values[] = date('Y-m-d H:i:s');
                
                $placeholders = array_fill(0, count($values), '?');
                
                $sql = "INSERT INTO dam_iptc_metadata (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
                DB::statement($sql, $values);
            }

            // Also set the display_object_config to DAM type
            $configExists = DB::selectOne("SELECT object_id FROM display_object_config WHERE object_id = ?", [$objectId]);
            
            if ($configExists) {
                DB::statement("UPDATE display_object_config SET object_type = 'dam', updated_at = NOW() WHERE object_id = ?", [$objectId]);
            } else {
                DB::statement("INSERT INTO display_object_config (object_id, object_type, created_at, updated_at) VALUES (?, 'dam', NOW(), NOW())", [$objectId]);
            }
            
        } catch (\Exception $e) {
            error_log("DAM IPTC Save Error: " . $e->getMessage());
        }
    }

    /**
     * Capture current values for audit trail
     */
    protected function captureCurrentValues(int $resourceId): array
    {
        try {
            $io = DB::table('information_object as io')
                ->leftJoin('information_object_i18n as ioi', function ($join) {
                    $join->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
                })
                ->where('io.id', $resourceId)
                ->select(['io.identifier', 'ioi.title', 'ioi.scope_and_content', 'ioi.extent_and_medium'])
                ->first();
            
            $iptc = DB::table('dam_iptc_metadata')
                ->where('object_id', $resourceId)
                ->first();
            
            $values = [];
            if ($io) {
                if ($io->identifier) $values['identifier'] = $io->identifier;
                if ($io->title) $values['title'] = $io->title;
                if ($io->scope_and_content) $values['scope_and_content'] = $io->scope_and_content;
            }
            
            if ($iptc) {
                $iptcFields = ['creator', 'headline', 'caption', 'keywords', 'date_created', 
                    'city', 'country', 'credit_line', 'copyright_notice'];
                foreach ($iptcFields as $field) {
                    if (!empty($iptc->$field)) {
                        $values['iptc_' . $field] = $iptc->$field;
                    }
                }
            }
            
            return $values;
        } catch (\Exception $e) {
            error_log("DAM AUDIT CAPTURE ERROR: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Log audit trail entry
     */
    protected function logAudit(string $action, int $resourceId, array $oldValues, array $newValues): void
    {
        try {
            $user = $this->getUser();
            $userId = $user->getAttribute('user_id');
            $username = null;

            if ($userId) {
                $userRecord = DB::table('user')->where('id', $userId)->first();
                $username = $userRecord->username ?? null;
            }

            $changedFields = [];
            foreach ($newValues as $key => $newVal) {
                $oldVal = $oldValues[$key] ?? null;
                if ($newVal !== $oldVal) {
                    $changedFields[] = $key;
                }
            }

            $uuid = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            DB::table('ahg_audit_log')->insert([
                'uuid' => $uuid,
                'user_id' => $userId,
                'username' => $username,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'session_id' => session_id() ?: null,
                'action' => $action,
                'entity_type' => 'DAMAsset',
                'entity_id' => $resourceId,
                'entity_slug' => $this->resource->slug ?? null,
                'entity_title' => $newValues['title'] ?? null,
                'module' => 'ahgDAMPlugin',
                'action_name' => 'edit',
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
                'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
                'old_values' => !empty($oldValues) ? json_encode($oldValues) : null,
                'new_values' => !empty($newValues) ? json_encode($newValues) : null,
                'changed_fields' => !empty($changedFields) ? json_encode($changedFields) : null,
                'status' => 'success',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            error_log("DAM AUDIT ERROR: " . $e->getMessage());
        }
    }

}
