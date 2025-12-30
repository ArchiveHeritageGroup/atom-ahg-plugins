<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;

class ccoEditAction extends InformationObjectEditAction
{
    public static $NAMES = [
        'identifier',
        'title',
        'levelOfDescription',
        'extentAndMedium',
        'repository',
        'creators',
        'scopeAndContent',
        'accessConditions',
        'reproductionConditions',
        'language',
        'script',
        'physicalCharacteristics',
        'acquisition',
        'archivalHistory',
        'arrangement',
        'accruals',
        'descriptionIdentifier',
        'institutionResponsibleIdentifier',
        'rules',
        'sources',
        'revisionHistory',
        'descriptionStatus',
        'descriptionDetail',
        'languageOfDescription',
        'scriptOfDescription',
        'languageNotes',
        'findingAids',
        'locationOfOriginals',
        'locationOfCopies',
        'relatedUnitsOfDescription',
        'displayStandard',
        'displayStandardUpdateDescendants',
        'subjectAccessPoints',
        'placeAccessPoints',
        'genreAccessPoints',
        'nameAccessPoints',
        'relatedMaterialDescriptions',
    ];

    protected $museumData = [];
    protected $termService;
	
	protected $grapData = [];
	protected $grapService;

    public function execute($request)
    {
        parent::execute($request);
    }

	public function execute($request)
    {
        parent::execute($request);
        
        // Load actors for creator dropdown
        $this->actors = $this->getActors();
    }
    
    /**
     * Get all actors for dropdown
     */
    protected function getActors()
    {
        $culture = $this->getUser()->getCulture() ?? 'en';
        
        return DB::table('actor as a')
            ->leftJoin('actor_i18n as ai', function ($join) use ($culture) {
                $join->on('a.id', '=', 'ai.id')
                    ->where('ai.culture', '=', $culture);
            })
            ->leftJoin('actor_i18n as ai_en', function ($join) {
                $join->on('a.id', '=', 'ai_en.id')
                    ->where('ai_en.culture', '=', 'en');
            })
            ->whereNotNull(DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name)'))
            ->where(DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name)'), '!=', '')
            ->orderBy(DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name)'))
            ->pluck(DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name)'), 'a.id')
            ->toArray();
    }
	
    public function saveMuseumDataAfterCommit()
    {
        if ($this->pendingIdentifier) {
            $id = $this->fetchColumn(
                'SELECT id FROM information_object WHERE identifier = ? ORDER BY id DESC LIMIT 1',
                [$this->pendingIdentifier]
            );

            if ($id) {
                $this->resource = new stdClass();
                $this->resource->id = $id;

                foreach ($this->pendingMuseumData as $key => $value) {
                    $this->form->setDefault($key, $value);
                }

                $this->saveMuseumData();
            }
        }
    }

    protected function getUserCulture()
    {
        return sfContext::getInstance()->getUser()->getCulture();
    }

    protected function getTermService()
    {
        if (!$this->termService) {
            try {
                $this->termService = new \AtomExtensions\Services\TermService($this->getUserCulture());
            } catch (Exception $e) {
                $this->termService = null;
            }
        }

        return $this->termService;
    }

    protected function getCreationTypeId()
    {
        // CREATION_ID = 112 is a standard AtoM term ID for creation relation type
        return 112;
    }

    protected function earlyExecute()
    {
        parent::earlyExecute();
        $this->isad = new sfIsadPlugin($this->resource);

        $this->eventComponent = new sfIsadPluginEventComponent($this->context, 'sfIsadPlugin', 'event');
        $this->eventComponent->resource = $this->resource;
        $this->eventComponent->execute($this->request);

        $this->alternativeIdentifiersComponent = new InformationObjectAlternativeIdentifiersComponent($this->context, 'informationobject', 'alternativeIdentifiers');
        $this->alternativeIdentifiersComponent->resource = $this->resource;
        $this->alternativeIdentifiersComponent->execute($this->request);

        $this->notesComponent = new ObjectNotesComponent($this->context, 'object', 'notes');
        $this->notesComponent->resource = $this->resource;
        $this->notesComponent->execute($this->request, 'type=generalNote');

        $this->publicationNotesComponent = new ObjectNotesComponent($this->context, 'object', 'notes');
        $this->publicationNotesComponent->resource = $this->resource;
        $this->publicationNotesComponent->execute($this->request, 'type=publicationNote');

        $this->archivistsNotesComponent = new ObjectNotesComponent($this->context, 'object', 'notes');
        $this->archivistsNotesComponent->resource = $this->resource;
        $this->archivistsNotesComponent->execute($this->request, 'type=archivistsNote');

        $this->mask = sfConfig::get('app_identifier_mask_enabled', 0);

        $title = $this->context->i18n->__('Add new museum object');
        if (isset($this->getRoute()->resource)) {
            if (1 > strlen($title = $this->resource->__toString())) {
                $title = $this->context->i18n->__('Untitled');
            }
            $title = $this->context->i18n->__('Edit %1%', ['%1%' => $title]);
        } else {
            $this->handleIdentifierFromMask();
        }

        $this->response->setTitle("{$title} - {$this->response->getTitle()}");

        if ($this->resource && $this->resource->id) {
            $this->loadMuseumData();
        }

        if (isset($this->form)) {
            $this->addMuseumFields();
        }
		
		if ($this->resource && $this->resource->id) {
				$this->loadMuseumData();
		}

		if (isset($this->form)) {
			$this->addMuseumFields();
		}
    }

    protected function addField($name)
    {
        switch ($name) {
            case 'creators':
                $value = $choices = [];
                $relations = $this->getCreatorRelations($this->resource->id);
                foreach ($this->creators = $relations as $item) {
                    $choices[$value[] = $this->context->routing->generate(null, [$item->subject, 'module' => 'actor'])] = $item->subject;
                }
                $this->form->setDefault('creators', $value);
                $this->form->setValidator('creators', new sfValidatorPass());
                $this->form->setWidget('creators', new sfWidgetFormSelect(['choices' => $choices, 'multiple' => true]));

                break;

            case 'displayStandardUpdateDescendants':
                $this->form->setValidator('displayStandardUpdateDescendants', new sfValidatorBoolean());
                $this->form->setWidget('displayStandardUpdateDescendants', new sfWidgetFormInputCheckbox());

                break;

            default:
                return parent::addField($name);
        }
    }

    protected function getCreatorRelations($objectId)
    {
        $creationTypeId = $this->getCreationTypeId();

        $results = \Illuminate\Database\Capsule\Manager::table('relation as r')
            ->join('actor as a', 'r.subject_id', '=', 'a.id')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('a.id', '=', 'ai.id')
                    ->where('ai.culture', '=', $this->getUserCulture());
            })
            ->where('r.object_id', $objectId)
            ->where('r.type_id', $creationTypeId)
            ->select('r.id', 'r.subject_id', 'r.object_id', 'r.type_id', 'ai.authorized_form_of_name')
            ->get();

        // Convert to objects compatible with template expectations
        $relations = [];
        foreach ($results as $row) {
            $relation = new \stdClass();
            $relation->id = $row->id;
            $relation->subjectId = $row->subject_id;
            $relation->objectId = $row->object_id;
            $relation->typeId = $row->type_id;

            // Create subject object for actor
            $relation->subject = new \stdClass();
            $relation->subject->id = $row->subject_id;
            $relation->subject->authorizedFormOfName = $row->authorized_form_of_name;
            $relation->subject->__toString = function () use ($row) {
                return $row->authorized_form_of_name ?? '';
            };

            $relations[] = $relation;
        }

        return $relations;
    }

    protected function processField($field)
    {
        switch ($field->getName()) {
            case 'creators':
                $value = $filtered = [];
                foreach ($this->form->getValue('creators') as $item) {
                    $path = parse_url($item, PHP_URL_PATH);
                    $params = $this->context->routing->parse($path);
                    $resource = $params['_sf_route']->resource;
                    $value[$resource->id] = $filtered[$resource->id] = $resource;
                }
                foreach ($this->creators as $item) {
                    if (isset($value[$item->subjectId])) {
                        unset($filtered[$item->subjectId]);
                    } else {
                        // Delete relation using framework
                        \Illuminate\Database\Capsule\Manager::table('relation')
                            ->where('id', $item->id)
                            ->delete();
                    }
                }
                foreach ($filtered as $item) {
                    // Insert new relation using framework
                    \Illuminate\Database\Capsule\Manager::table('relation')->insert([
                        'subject_id' => $item->id,
                        'object_id' => $this->resource->id,
                        'type_id' => $this->getCreationTypeId(),
                    ]);
                }

                break;

            default:
                return parent::processField($field);
        }
    }

    protected function processForm()
    {
        $this->eventComponent->processForm();
        $this->resource->sourceStandard = 'Museum (CCO)';

        $this->pendingMuseumData = $this->collectMuseumFormData();
        $this->pendingIdentifier = $this->form->getValue('identifier');

        parent::processForm();

        if (!$this->resource->id && $this->pendingIdentifier) {
            register_shutdown_function([$this, 'saveMuseumDataAfterCommit']);
        } elseif ($this->resource->id) {
            $this->saveMuseumData();
			$this->saveSecurityClassification();
        }
    }

    protected function collectMuseumFormData()
    {
        $fields = [
            'work_type', 'materials', 'techniques', 'creation_date_earliest',
            'creation_date_latest', 'object_type', 'classification', 'dimensions',
            'current_location', 'measurements', 'inscription', 'condition_notes',
            'provenance', 'style_period', 'cultural_context', 'creator_identity',
            'creator_role', 'creator_extent', 'creator_qualifier', 'creator_attribution',
            'creation_date_display', 'creation_date_qualifier', 'style', 'period',
            'cultural_group', 'movement', 'school', 'dynasty', 'subject_indexing_type',
            'subject_display', 'subject_extent', 'historical_context',
            'architectural_context', 'archaeological_context', 'object_class',
            'object_category', 'object_sub_category', 'edition_number', 'edition_size',
            'edition_description', 'state_description', 'state_identification',
            'facture_description', 'technique_cco', 'technique_qualifier',
            'physical_appearance', 'color', 'shape', 'orientation', 'condition_term',
            'condition_date', 'condition_description', 'condition_agent',
            'treatment_type', 'treatment_date', 'treatment_agent', 'treatment_description',
            'inscription_type', 'inscription_transcription', 'inscription_location',
            'inscription_language', 'inscription_translation', 'mark_type',
            'mark_description', 'mark_location', 'related_work_type',
            'related_work_relationship', 'related_work_label', 'related_work_id',
            'current_location_repository', 'current_location_geography',
            'current_location_coordinates', 'current_location_ref_number',
            'creation_place', 'creation_place_type', 'discovery_place',
            'discovery_place_type', 'provenance_text', 'ownership_history',
            'legal_status', 'rights_type', 'rights_holder', 'rights_date',
            'rights_remarks', 'cataloger_name', 'cataloging_date',
            'cataloging_institution', 'cataloging_remarks', 'record_type', 'record_level',
        ];

        $data = [];
        foreach ($fields as $field) {
            $data[$field] = $this->form->getValue('museum_'.$field);
        }

        return $data;
    }

    /**
     * Add all museum metadata fields to the form.
     */
    protected function addMuseumFields()
    {
        // Initialize taxonomy service
        $ccoService = new CcoTaxonomyService($this->context->user->getCulture());

        // ====================================================================
        // TAXONOMY-BASED SELECT FIELDS
        // ====================================================================

        // Work Type - from taxonomy
        $this->form->setWidget('museum_work_type', new sfWidgetFormSelect([
            'choices' => $ccoService->getWorkTypes(),
        ]));
        $this->form->setValidator('museum_work_type', new sfValidatorChoice([
            'choices' => array_keys($ccoService->getWorkTypes()),
            'required' => false,
        ]));
        $this->form->setDefault('museum_work_type', $this->museumData['work_type'] ?? '');

        // Materials - from taxonomy (multi-select)
        $this->form->setWidget('museum_materials', new sfWidgetFormSelect([
            'choices' => $ccoService->getMaterials(false),
            'multiple' => true,
        ], [
            'size' => 5,
        ]));
        $this->form->setValidator('museum_materials', new sfValidatorChoice([
            'choices' => array_keys($ccoService->getMaterials(false)),
            'required' => false,
            'multiple' => true,
        ]));
        $materials = isset($this->museumData['materials']) ? json_decode($this->museumData['materials'], true) : [];
        $this->form->setDefault('museum_materials', $materials ?: []);

        // Techniques - from taxonomy (multi-select)
        $this->form->setWidget('museum_techniques', new sfWidgetFormSelect([
            'choices' => $ccoService->getTechniques(false),
            'multiple' => true,
        ], [
            'size' => 5,
        ]));
        $this->form->setValidator('museum_techniques', new sfValidatorChoice([
            'choices' => array_keys($ccoService->getTechniques(false)),
            'required' => false,
            'multiple' => true,
        ]));
        $techniques = isset($this->museumData['techniques']) ? json_decode($this->museumData['techniques'], true) : [];
        $this->form->setDefault('museum_techniques', $techniques ?: []);

        // Creator Role - from taxonomy
        $this->addTaxonomySelectField('creator_role', $ccoService->getCreatorRoles());

        // Creator Qualifier (Attribution) - from taxonomy
        $this->addTaxonomySelectField('creator_qualifier', $ccoService->getAttributionQualifiers());

        // Date Qualifier - from taxonomy
        $this->addTaxonomySelectField('creation_date_qualifier', $ccoService->getDateQualifiers());

        // Subject Indexing Type - from taxonomy
        $this->addTaxonomySelectField('subject_indexing_type', $ccoService->getSubjectTypes());

        // ====================================================================
        // TEXT FIELDS - Object Identification
        // ====================================================================
        $this->addTextField('object_type');
        $this->addTextField('object_category');
        $this->addTextField('object_sub_category');
        $this->addTextField('object_class');
        $this->addTextField('classification');

        // ====================================================================
        // TEXT FIELDS - Physical Description
        // ====================================================================
        $this->addTextField('measurements', true);
        $this->addTextField('dimensions', true);
        $this->addTextField('shape');
        $this->addTextField('color');
        $this->addTextField('orientation');
        $this->addTextField('physical_appearance', true);

        // ====================================================================
        // TEXT FIELDS - Creation Information
        // ====================================================================
        $this->addTextField('creation_date_display');
        $this->addTextField('creation_date_earliest');
        $this->addTextField('creation_date_latest');

        // ====================================================================
        // TEXT FIELDS - Creator Information
        // ====================================================================
        $this->addTextField('creator_identity');
        $this->addTextField('creator_extent');
        $this->addTextField('creator_attribution');

        // ====================================================================
        // TEXT FIELDS - Style and Period
        // ====================================================================
        $this->addTextField('style');
        $this->addTextField('style_period');
        $this->addTextField('period');
        $this->addTextField('dynasty');
        $this->addTextField('movement');
        $this->addTextField('school');
        $this->addTextField('cultural_group');

        // ====================================================================
        // TEXT FIELDS - Technique
        // ====================================================================
        $this->addTextField('technique_cco');
        $this->addTextField('technique_qualifier');
        $this->addTextField('facture_description', true);

        // ====================================================================
        // TEXT FIELDS - Context
        // ====================================================================
        $this->addTextField('cultural_context', true);
        $this->addTextField('historical_context', true);
        $this->addTextField('archaeological_context', true);
        $this->addTextField('architectural_context', true);

        // ====================================================================
        // TEXT FIELDS - Subject
        // ====================================================================
        $this->addTextField('subject_display', true);
        $this->addTextField('subject_extent');

        // ====================================================================
        // TEXT FIELDS - Inscription
        // ====================================================================
        $this->addTextField('inscription', true);

        // ====================================================================
        // TEXT FIELDS - State/Edition
        // ====================================================================
        $this->addTextField('state_identification');
        $this->addTextField('state_description', true);
        $this->addTextField('edition_number');
        $this->addTextField('edition_size');
        $this->addTextField('edition_description', true);

        // ====================================================================
        // TEXT FIELDS - Condition
        // ====================================================================
        $this->addTextField('condition_notes', true);

        // ====================================================================
        // TEXT FIELDS - Location and Provenance
        // ====================================================================
        $this->addTextField('current_location');
        $this->addTextField('provenance', true);
    }

    /**
     * Helper to add taxonomy-based select field.
     */
    protected function addTaxonomySelectField(string $name, array $choices): void
    {
        $fieldName = 'museum_'.$name;
        $this->form->setWidget($fieldName, new sfWidgetFormSelect(['choices' => $choices]));
        $this->form->setValidator($fieldName, new sfValidatorChoice([
            'choices' => array_keys($choices),
            'required' => false,
        ]));
        $this->form->setDefault($fieldName, $this->museumData[$name] ?? '');
    }

    /**
     * Helper to add text or textarea field.
     */
    protected function addTextField(string $name, bool $textarea = false): void
    {
        $fieldName = 'museum_'.$name;

        if ($textarea) {
            $this->form->setWidget($fieldName, new sfWidgetFormTextarea());
        } else {
            $this->form->setWidget($fieldName, new sfWidgetFormInput());
        }

        $this->form->setValidator($fieldName, new sfValidatorString(['required' => false]));
        $this->form->setDefault($fieldName, $this->museumData[$name] ?? '');
    }

    protected function addSelectField($name, $choices)
    {
        $fieldName = 'museum_'.$name;
        $this->form->setWidget($fieldName, new sfWidgetFormSelect(['choices' => $choices]));
        $this->form->setValidator($fieldName, new sfValidatorChoice(['choices' => array_keys($choices), 'required' => false]));
        $this->form->setDefault($fieldName, $this->museumData[$name] ?? '');
    }

    protected function addDateField($name)
    {
        $fieldName = 'museum_'.$name;
        $this->form->setWidget($fieldName, new sfWidgetFormInput(['type' => 'date']));
        $this->form->setValidator($fieldName, new sfValidatorString(['required' => false]));
        $this->form->setDefault($fieldName, $this->museumData[$name] ?? '');
    }

    /**
     * Load museum metadata from database using Laravel Query Builder.
     */
    protected function loadMuseumData(): void
    {
        if (!$this->resource || !$this->resource->id) {
            $this->museumData = [];

            return;
        }

        try {
            $result = DB::table('museum_metadata')
                ->where('object_id', $this->resource->id)
                ->first();

            $this->museumData = $result ? (array) $result : [];
        } catch (\Exception $e) {
            error_log('Museum metadata load error: '.$e->getMessage());
            $this->museumData = [];
        }
    }

    /**
     * Save security classification from form.
     */

    protected function saveSecurityClassification(): void
    {
        if (!$this->resource || !$this->resource->id) {
            return;
        }

        try {
            require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';
            require_once sfConfig::get('sf_root_dir').'/atom-framework/src/Services/SecurityClearanceService.php';
            require_once sfConfig::get('sf_root_dir').'/atom-framework/src/Services/WatermarkSettingsService.php';
            require_once sfConfig::get('sf_root_dir').'/atom-framework/src/Services/DerivativeWatermarkService.php';

            $classificationId = $this->request->getParameter('security_classification_id');
            $userId = $this->context->user->getUserId();

            // Handle security classification
            if (empty($classificationId)) {
                $existing = \AtomExtensions\Services\SecurityClearanceService::getObjectClassification($this->resource->id);
                if ($existing) {
                    \AtomExtensions\Services\SecurityClearanceService::declassifyObject(
                        $this->resource->id,
                        $userId,
                        'Classification removed via edit form'
                    );
                }
            } else {
                \AtomExtensions\Services\SecurityClearanceService::classifyObject(
                    $this->resource->id,
                    (int) $classificationId,
                    $userId,
                    $this->request->getParameter('security_reason'),
                    $this->request->getParameter('security_review_date') ?: null,
                    $this->request->getParameter('security_declassify_date') ?: null,
                    null,
                    $this->request->getParameter('security_handling_instructions'),
                    (bool) $this->request->getParameter('security_inherit_to_children', false)
                );
            }


            // Handle watermark settings - save to object_watermark_setting table (separate from AtoM)
            $watermarkEnabled = $this->request->getParameter('watermark_enabled') ? 1 : 0;
            $watermarkTypeId = $this->request->getParameter('watermark_type_id');
            $watermarkTypeId = ($watermarkTypeId === '' || $watermarkTypeId === null) ? null : (int) $watermarkTypeId;
            $position = $this->request->getParameter('new_watermark_position', 'center');
            $opacity = ((int) $this->request->getParameter('new_watermark_opacity', 40)) / 100;
            $customWatermarkId = null;
            
            // Handle custom watermark upload
            $newWatermarkName = $this->request->getParameter('new_watermark_name');
            error_log("DEBUG UPLOAD CHECK: name=[" . $newWatermarkName . "] file_tmp=[" . ($newWatermarkFile["tmp_name"] ?? "NONE") . "]");
            $newWatermarkFile = $this->request->getFiles('new_watermark_file');
            
            if ($newWatermarkName && $newWatermarkFile && !empty($newWatermarkFile['tmp_name'])) {
                $isGlobal = (bool) $this->request->getParameter('new_watermark_global');
                $userId = $this->context->user->getAttribute('user_id');
                
                $customWatermarkId = \AtomExtensions\Services\DerivativeWatermarkService::uploadCustomWatermark(
                    $newWatermarkFile,
                    $newWatermarkName,
                    $isGlobal ? null : $this->resource->id,
                    $position,
                    $opacity,
                    $userId
                );
            } else {
                // Check if existing custom watermark selected
                $selectedCustom = $this->request->getParameter('custom_watermark_id');
                if ($selectedCustom && $selectedCustom !== '') {
                    $customWatermarkId = (int) $selectedCustom;
                }
            }
            
            // Save to object_watermark_setting table
            $existing = \Illuminate\Database\Capsule\Manager::table('object_watermark_setting')
                ->where('object_id', $this->resource->id)
                ->first();
            
            $data = [
                'watermark_enabled' => $watermarkEnabled,
                'watermark_type_id' => $watermarkTypeId,
                'custom_watermark_id' => $customWatermarkId,
                'position' => $position,
                'opacity' => $opacity,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            
            if ($existing) {
                \Illuminate\Database\Capsule\Manager::table('object_watermark_setting')
                    ->where('object_id', $this->resource->id)
                    ->update($data);
            } else {
                $data['object_id'] = $this->resource->id;
                $data['created_at'] = date('Y-m-d H:i:s');
                \Illuminate\Database\Capsule\Manager::table('object_watermark_setting')
                    ->insert($data);
            }
            
            // Regenerate derivatives if requested
            if ($this->request->getParameter('regenerate_derivatives')) {
                \AtomExtensions\Services\DerivativeWatermarkService::regenerateDerivatives($this->resource->id);
            }

        } catch (\Exception $e) {
            error_log('Security/Watermark save error: ' . $e->getMessage());
        }
    }

    protected function saveMuseumData(): void
    {
        if (!$this->resource || !$this->resource->id) {
            return;
        }

        try {
            $materials = $this->form->getValue('museum_materials');
            $techniques = $this->form->getValue('museum_techniques');

            $columns = [
                'work_type', 'object_type', 'classification', 'materials', 'techniques',
                'measurements', 'dimensions', 'creation_date_earliest', 'creation_date_latest',
                'inscription', 'condition_notes', 'provenance', 'current_location',
                'style_period', 'cultural_context', 'creator_identity', 'creator_role',
                'creator_extent', 'creator_qualifier', 'creator_attribution',
                'creation_date_display', 'creation_date_qualifier', 'style', 'period',
                'cultural_group', 'movement', 'school', 'dynasty', 'subject_indexing_type',
                'subject_display', 'subject_extent', 'historical_context',
                'architectural_context', 'archaeological_context', 'object_class',
                'object_category', 'object_sub_category', 'edition_number', 'edition_size',
                'edition_description', 'state_description', 'state_identification',
                'facture_description', 'technique_cco', 'technique_qualifier',
                'physical_appearance', 'color', 'shape', 'orientation', 'condition_term',
                'condition_date', 'condition_description', 'condition_agent',
                'treatment_type', 'treatment_date', 'treatment_agent', 'treatment_description',
                'inscription_type', 'inscription_transcription', 'inscription_location',
                'inscription_language', 'inscription_translation', 'mark_type',
                'mark_description', 'mark_location', 'related_work_type',
                'related_work_relationship', 'related_work_label', 'related_work_id',
                'current_location_repository', 'current_location_geography',
                'current_location_coordinates', 'current_location_ref_number',
                'creation_place', 'creation_place_type', 'discovery_place',
                'discovery_place_type', 'provenance_text', 'ownership_history',
                'legal_status', 'rights_type', 'rights_holder', 'rights_date',
                'rights_remarks', 'cataloger_name', 'cataloging_date',
                'cataloging_institution', 'cataloging_remarks', 'record_type', 'record_level',
            ];

            $data = [];
            foreach ($columns as $col) {
                if ('materials' === $col) {
                    $data[$col] = json_encode($materials ?: []);
                } elseif ('techniques' === $col) {
                    $data[$col] = json_encode($techniques ?: []);
                } else {
                    $data[$col] = $this->form->getValue('museum_'.$col);
                }
            }

            // Check if record exists
            $exists = DB::table('museum_metadata')
                ->where('object_id', $this->resource->id)
                ->exists();

            if ($exists) {
                // Update existing record
                DB::table('museum_metadata')
                    ->where('object_id', $this->resource->id)
                    ->update($data);
            } else {
                // Insert new record
                $data['object_id'] = $this->resource->id;
                DB::table('museum_metadata')->insert($data);
            }
        } catch (\Exception $e) {
            error_log('Museum metadata save error: '.$e->getMessage());
        }
    }

    protected function fetchOne($sql, $params = [])
    {
        $connection = Propel::getConnection();
        $statement = $connection->prepare($sql);
        $statement->execute($params);

        return $statement->fetch(PDO::FETCH_OBJ);
    }

    protected function fetchAll($sql, $params = [])
    {
        $connection = Propel::getConnection();
        $statement = $connection->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function fetchColumn($sql, $params = [])
    {
        $connection = Propel::getConnection();
        $statement = $connection->prepare($sql);
        $statement->execute($params);

        return $statement->fetchColumn();
    }

protected function executeQuery($sql, $params = [])
{
        return \Illuminate\Database\Capsule\Manager::statement($sql, $params);
    }

    /**
     * Generate identifier from mask setting using Laravel Query Builder.
     */
    protected function generateIdentifierFromMask()
    {
        $db = \Illuminate\Database\Capsule\Manager::table('setting')
            ->join('setting_i18n', 'setting.id', '=', 'setting_i18n.id');

        // Get mask
        $mask = (clone $db)
            ->where('setting.name', 'identifier_mask')
            ->value('setting_i18n.value');

        if (!$mask) {
            return '';
        }

        // Get counter
        $counter = (int) (clone $db)
            ->where('setting.name', 'identifier_counter')
            ->value('setting_i18n.value') ?: 1;

        // Replace date placeholders
        $identifier = str_replace(
            ['%Y%', '%y%', '%m%', '%d%'],
            [date('Y'), date('y'), date('m'), date('d')],
            $mask
        );

        // Replace counter placeholder
        if (preg_match('/%(\d*)i%/', $identifier, $matches)) {
            $padding = !empty($matches[1]) ? (int) $matches[1] : 0;
            $identifier = preg_replace('/%\d*i%/', str_pad($counter, $padding, '0', STR_PAD_LEFT), $identifier);
        }

        return $identifier;
    }

    private function handleIdentifierFromMask()
    {
        if ($this->mask) {
            $this->resource->identifier = $this->generateIdentifierFromMask();
        }
    }
}
