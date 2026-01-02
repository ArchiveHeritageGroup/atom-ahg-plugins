<?php
/**
 * CCO Cataloguing Edit Action
 *
 * Uses Laravel Illuminate Database
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgMuseumPlugin
 */

use Illuminate\Database\Capsule\Manager as DB;

class ahgMuseumPluginEditAction extends sfAction
{
    public static $NAMES = [
        'template',
        'work_type',
        'object_number',
        'title',
        'title_type',
        'creator_display',
        'creator',
        'creator_role',
        'attribution_qualifier',
        'creation_date_display',
        'creation_date_earliest',
        'creation_date_latest',
        'creation_place',
        'culture',
        'style',
        'period',
        'dimensions_display',
        'height_value',
        'width_value',
        'depth_value',
        'materials_display',
        'materials',
        'techniques',
        'support',
        'subject_display',
        'subjects_depicted',
        'inscriptions',
        'signature',
        'description',
        'condition_summary',
        'repository',
        'location_within_repository',
        'credit_line',
        'rights_statement',
    ];

    // Constants
    private const ROOT_INFORMATION_OBJECT_ID = 1;
    private const TERM_CREATION_ID = 111;
    private const GROUP_ADMINISTRATOR = 100;
    private const GROUP_EDITOR = 101;
    private const GROUP_CONTRIBUTOR = 102;

    protected $resource;
    protected $template;
    protected $ccoData;

    public function execute($request)
    {
        // Check permissions
        if (!$this->context->user->isAuthenticated()) {
            $this->forwardUnauthorized();
        }

        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        // Get or create resource
        $slug = $request->getParameter('slug');
        $resourceId = $request->getParameter('id');

        if ($slug) {
            $this->resource = $this->getInformationObjectBySlug($slug);
            if ($this->resource) {
                $this->ccoData = $this->loadCCOData($this->resource->id);
                // Load item physical location
                require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/ItemPhysicalLocationRepository.php';
                $locRepo = new \AtomFramework\Repositories\ItemPhysicalLocationRepository();
                $this->itemLocation = $locRepo->getLocationWithContainer($this->resource->id) ?? [];
			}
        } elseif ($resourceId) {
            $this->resource = $this->getInformationObjectById((int) $resourceId);
            if ($this->resource) {
                $this->ccoData = $this->loadCCOData($this->resource->id);
                // Load item physical location
                require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/ItemPhysicalLocationRepository.php';
                $locRepo = new \AtomFramework\Repositories\ItemPhysicalLocationRepository();
                $this->itemLocation = $locRepo->getLocationWithContainer($this->resource->id) ?? [];
            }
        }

        // Check update permission if existing resource
        if ($this->resource && !$this->checkAcl('update')) {
            $this->forwardUnauthorized();
        }

        // Check create permission if new resource
        if (!$this->resource && !$this->checkAcl('create')) {
            $this->forwardUnauthorized();
        }

        // Initialize for new resource
        if (!$this->resource) {
            $this->resource = null;
            $this->ccoData = [];
            $this->itemLocation = [];
        }

        error_log('Route resource: ' . ($this->resource ? $this->resource->id : 'NONE'));

        // Get template
        error_log('Loading template param: ' . $request->getParameter('template', 'NONE'));
        $templateId = $request->getParameter('template', ahgCCOTemplates::TEMPLATE_GENERIC);
        $this->template = ahgCCOTemplates::getTemplate($templateId);
        $this->templateId = $templateId;

        // Get all templates for selector
        $this->availableTemplates = ahgCCOTemplates::getTemplates();

        // Get field definitions
        $this->fieldDefinitions = ahgCCOFieldDefinitions::getAllCategories();

        // Get repositories for dropdown
        $this->repositories = $this->getRepositories();

        // Get actors for creator dropdown
        $this->actors = $this->getActors();

        // Add fields to form BEFORE binding
        foreach (self::$NAMES as $name) {
            $this->addField($name);
        }

        // Process form submission
        if ($request->isMethod('post')) {
            error_log('POST - resourceId param: ' . $request->getParameter('id') . ' | resource->id: ' . ($this->resource->id ?? 'NULL'));
            $this->form->bind($request->getPostParameters());

            if ($this->form->isValid()) {
                $this->processForm();

                // Validate against template
                $validation = ahgCCOTemplates::validateRecord($templateId, $this->ccoData);

                if (true) { // Always save for now
                    $this->saveRecord();
                    if ($request->getParameter('switch_template')) { 
                        // Stay on edit page with new template
                        $this->redirect('/index.php/ahgMuseumPlugin/edit?template=' . $this->templateId . '&id=' . $this->resource->id);
                    } else {
                        // Redirect to view page using slug
                        $slug = $this->resource->slug ?? null;
                        if ($slug) {
                            $this->redirect('/index.php/' . $slug);
                        } else {
                            $this->redirect('/index.php/informationobject/index/id/' . $this->resource->id);
                        }
                    }
                } else {
                    $this->validationErrors = $validation['errors'];
                    $this->validationWarnings = $validation['warnings'];
                }
            }
        }

        // Calculate current completeness
        error_log('Template will receive resource->id: ' . ($this->resource->id ?? 'NULL'));
        $this->resourceId = $this->resource->id ?? null;
        $this->resourceSlug = $this->resource->slug ?? null;
        $this->completeness = ahgCCOTemplates::calculateCompleteness($templateId, $this->ccoData);
    }

    /**
     * Forward to unauthorized page
     */
    protected function forwardUnauthorized(): void
    {
        $this->forward('admin', 'secure');
    }

    /**
     * Check ACL permission
     */
    protected function checkAcl(string $action): bool
    {
        $user = $this->getUser();

        if (!$user->isAuthenticated()) {
            return false;
        }

        $userId = $user->getAttribute('user_id');
        if (!$userId) {
            return false;
        }

        // Get user groups
        $userGroups = DB::table('acl_user_group')
            ->where('user_id', $userId)
            ->pluck('group_id')
            ->toArray();

        // Administrator can do everything
        if (in_array(self::GROUP_ADMINISTRATOR, $userGroups)) {
            return true;
        }

        // Editor can create and update
        if (in_array(self::GROUP_EDITOR, $userGroups)) {
            return true;
        }

        // Contributor can create
        if (in_array(self::GROUP_CONTRIBUTOR, $userGroups) && $action === 'create') {
            return true;
        }

        // Contributor can update own records
        if (in_array(self::GROUP_CONTRIBUTOR, $userGroups) && $action === 'update' && $this->resource) {
            $createdBy = DB::table('object')
                ->where('id', $this->resource->id)
                ->value('created_by');
            if ($createdBy == $userId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get information object by slug using Laravel
     */
    protected function getInformationObjectBySlug(string $slug): ?object
    {
        $culture = $this->getUser()->getCulture() ?? 'en';

        return DB::table('information_object as io')
            ->join('slug', 'io.id', '=', 'slug.object_id')
            ->leftJoin('information_object_i18n as ioi', function ($join) use ($culture) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('information_object_i18n as ioi_en', function ($join) {
                $join->on('io.id', '=', 'ioi_en.id')
                    ->where('ioi_en.culture', '=', 'en');
            })
            ->where('slug.slug', $slug)
            ->select([
                'io.*',
                'slug.slug',
                DB::raw('COALESCE(ioi.title, ioi_en.title) as title'),
                DB::raw('COALESCE(ioi.scope_and_content, ioi_en.scope_and_content) as scope_and_content'),
                DB::raw('COALESCE(ioi.extent_and_medium, ioi_en.extent_and_medium) as extent_and_medium'),
            ])
            ->first();
    }

    /**
     * Get information object by ID using Laravel
     */
    protected function getInformationObjectById(int $id): ?object
    {
        $culture = $this->getUser()->getCulture() ?? 'en';

        return DB::table('information_object as io')
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->leftJoin('information_object_i18n as ioi', function ($join) use ($culture) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('information_object_i18n as ioi_en', function ($join) {
                $join->on('io.id', '=', 'ioi_en.id')
                    ->where('ioi_en.culture', '=', 'en');
            })
            ->where('io.id', $id)
            ->select([
                'io.*',
                'slug.slug',
                DB::raw('COALESCE(ioi.title, ioi_en.title) as title'),
                DB::raw('COALESCE(ioi.scope_and_content, ioi_en.scope_and_content) as scope_and_content'),
                DB::raw('COALESCE(ioi.extent_and_medium, ioi_en.extent_and_medium) as extent_and_medium'),
            ])
            ->first();
    }

    /**
     * Get all repositories using Laravel
     */
    protected function getRepositories()
    {
        $culture = $this->getUser()->getCulture() ?? 'en';

        $repositories = DB::table('repository as r')
            ->leftJoin('actor_i18n as ai', function ($join) use ($culture) {
                $join->on('r.id', '=', 'ai.id')
                    ->where('ai.culture', '=', $culture);
            })
            ->leftJoin('actor_i18n as ai_en', function ($join) {
                $join->on('r.id', '=', 'ai_en.id')
                    ->where('ai_en.culture', '=', 'en');
            })
            ->whereNotNull(DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name)'))
            ->orderBy(DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name)'))
            ->select('r.id', DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name) as name'))
            ->get();

        $result = [];
        foreach ($repositories as $repo) {
            $result[$repo->id] = $repo->name;
        }

        return $result;
    }

    /**
     * Get all actors using Laravel
     */
    protected function getActors()
    {
        $culture = $this->getUser()->getCulture() ?? 'en';

        $actors = DB::table('actor as a')
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
            ->select('a.id', DB::raw('COALESCE(ai.authorized_form_of_name, ai_en.authorized_form_of_name) as name'))
            ->get();

        $result = [];
        foreach ($actors as $actor) {
            $result[$actor->id] = $actor->name;
        }
        
        return $result;
    }

    /**
     * Load CCO data using Laravel
     */
    protected function loadCCOData($resourceId)
    {
        if (!$resourceId) {
            return [];
        }

        $data = [];

        // Load from property table
        $property = DB::table('property')
            ->join('property_i18n', 'property.id', '=', 'property_i18n.id')
            ->where('property.object_id', $resourceId)
            ->where('property.name', 'ccoData')
            ->first();

        if ($property && $property->value) {
            $data = json_decode($property->value, true) ?: [];
        }

        // Load standard AtoM fields
        $info = DB::table('information_object')
            ->where('id', $resourceId)
            ->first();

        $culture = $this->getUser()->getCulture() ?? 'en';
        $infoI18n = DB::table('information_object_i18n')
            ->where('id', $resourceId)
            ->where('culture', $culture)
            ->first();

        // Fallback to English
        if (!$infoI18n) {
            $infoI18n = DB::table('information_object_i18n')
                ->where('id', $resourceId)
                ->where('culture', 'en')
                ->first();
        }

        if ($info) {
            if (empty($data['object_number'])) {
                $data['object_number'] = $info->identifier;
            }
            if (empty($data['repository']) && $info->repository_id) {
                $data['repository'] = $info->repository_id;
            }
        }

        if ($infoI18n) {
            if (empty($data['title'])) {
                $data['title'] = $infoI18n->title;
            }
            if (empty($data['dimensions_display'])) {
                $data['dimensions_display'] = $infoI18n->extent_and_medium;
            }
            if (empty($data['description'])) {
                $data['description'] = $infoI18n->scope_and_content;
            }
        }

        // Get creator from events
        if (empty($data['creator'])) {
            $creator = DB::table('event')
                ->where('event.object_id', $resourceId)
                ->where('event.type_id', self::TERM_CREATION_ID)
                ->whereNotNull('event.actor_id')
                ->first();

            if ($creator) {
                $data['creator'] = $creator->actor_id;
            }
        }

        return $data;
    }

    protected function addField($name)
    {
        // Find field definition
        $fieldDef = null;
        foreach ($this->fieldDefinitions as $category) {
            if (isset($category['fields'][$name])) {
                $fieldDef = $category['fields'][$name];
                break;
            }
        }

        if (!$fieldDef && $name !== 'template') {
            return;
        }

        // Check if field is visible in template
        if ($name !== 'template' && !ahgCCOTemplates::isFieldVisible($this->templateId, $name)) {
            return;
        }

        $value = isset($this->ccoData[$name]) ? $this->ccoData[$name] : null;

        switch ($name) {
            case 'template':
                $choices = [];
                foreach ($this->availableTemplates as $id => $template) {
                    $choices[$id] = $template['label'];
                }
                $this->form->setDefault($name, $this->templateId);
                $this->form->setValidator($name, new sfValidatorChoice(['choices' => array_keys($choices), 'required' => false]));
                $this->form->setWidget($name, new sfWidgetFormSelect(['choices' => $choices]));
                break;

            case 'repository':
                $choices = ['' => '-- Select Repository --'] + $this->repositories;
                $this->form->setDefault($name, $value);
                $this->form->setValidator($name, new sfValidatorChoice(['choices' => array_keys($choices), 'required' => false]));
                $this->form->setWidget($name, new sfWidgetFormSelect(['choices' => $choices], ['class' => 'form-control', 'id' => 'repository']));
                break;

            case 'creator':
                $actorChoices = ['' => '-- Select Creator --'] + $this->actors;
                $this->form->setDefault($name, $value);
                $this->form->setValidator($name, new sfValidatorString(['required' => false]));
                $this->form->setWidget($name, new sfWidgetFormSelect(['choices' => $actorChoices], ['class' => 'form-control']));
                break;

            case 'work_type':
            case 'creator_role':
            case 'creation_place':
            case 'culture':
            case 'style':
            case 'period':
            case 'support':
            case 'materials':
            case 'techniques':
            case 'subjects_depicted':
                $this->form->setDefault($name, $value);
                $this->form->setValidator($name, new sfValidatorString(['required' => false]));
                $this->form->setWidget($name, new sfWidgetFormInput([
                    'label' => $fieldDef['label'],
                ], [
                    'class' => 'form-control cco-autocomplete',
                    'data-vocabulary' => isset($fieldDef['vocabulary']) ? $fieldDef['vocabulary'] : '',
                    'data-help' => $fieldDef['helpText'],
                ]));
                break;

            case 'title_type':
            case 'attribution_qualifier':
            case 'condition_summary':
                $options = isset($fieldDef['options']) ? $fieldDef['options'] : [];
                $choices = is_array($options) && !isset($options[0]) ? $options : array_combine($options, $options);

                $this->form->setDefault($name, $value);
                $this->form->setValidator($name, new sfValidatorChoice([
                    'choices' => array_keys($choices),
                    'required' => false,
                ]));
                $this->form->setWidget($name, new sfWidgetFormSelect(['choices' => ['' => ''] + $choices]));
                break;

            case 'creation_date_earliest':
            case 'creation_date_latest':
                $this->form->setDefault($name, $value);
                $this->form->setValidator($name, new sfValidatorString(['required' => false]));
                $this->form->setWidget($name, new sfWidgetFormInput([], ['type' => 'date', 'class' => 'form-control']));
                break;

            case 'height_value':
            case 'width_value':
            case 'depth_value':
            case 'weight_value':
                $this->form->setDefault($name, $value);
                $this->form->setValidator($name, new sfValidatorNumber(['required' => false]));
                $this->form->setWidget($name, new sfWidgetFormInput([], ['type' => 'number', 'step' => '0.1', 'class' => 'form-control']));
                break;

            case 'description':
            case 'subject_display':
            case 'inscriptions':
                $this->form->setDefault($name, $value);
                $this->form->setValidator($name, new sfValidatorString(['required' => false]));
                $this->form->setWidget($name, new sfWidgetFormTextarea([], ['class' => 'form-control', 'rows' => 4]));
                break;

            default:
                $this->form->setDefault($name, $value);
                $this->form->setValidator($name, new sfValidatorString(['required' => false]));
                $this->form->setWidget($name, new sfWidgetFormInput([], ['class' => 'form-control']));
        }
    }

    protected function processForm()
    {
        $this->ccoData = [];
        foreach (self::$NAMES as $name) {
            $value = $this->form->getValue($name);
            if ($value !== null && $value !== '') {
                $this->ccoData[$name] = $value;
            }
        }
    }

	protected function saveRecord()
    {
        $isNew = !$this->resource || !isset($this->resource->id);
        $culture = $this->getUser()->getCulture() ?? 'en';
        $userId = $this->getUser()->getAttribute('user_id');
        $now = date('Y-m-d H:i:s');

        // Capture old values for audit trail
        $oldValues = [];
        if (!$isNew && $this->resource) {
            $oldValues = $this->captureCurrentValues($this->resource->id);
        }

        if ($isNew) {
            // Create new information object
            $resourceId = $this->createInformationObject();
        } else {
            $resourceId = $this->resource->id;
            $this->updateInformationObject($resourceId);
        }

        // Save CCO-specific data using Laravel
        $this->saveProperty($resourceId, 'ccoData', json_encode($this->ccoData));

        // Save CCO template
        $this->saveProperty($resourceId, 'ccoTemplate', $this->templateId);

        // Handle creator
        if (!empty($this->ccoData['creator'])) {
            $this->saveCreator($resourceId, $this->ccoData['creator']);
        }

        // Handle watermark settings
        $this->saveWatermarkSettings($resourceId);

        // Handle display standard
        $this->saveDisplayStandard($resourceId);

        // Set GLAM type to museum
        DB::table("display_object_config")->updateOrInsert(
            ["object_id" => $resourceId],
            [
                "object_type" => "museum",
                "updated_at" => date("Y-m-d H:i:s")
            ]
        );

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
            $locRepo->saveLocationData($resourceId, $locationData);
        }
        // Refresh resource
        $this->resource = $this->getInformationObjectById($resourceId);

        // Capture new values for audit trail
        $newValues = $this->captureCurrentValues($resourceId);

        // Log audit trail
        $this->logAudit($isNew ? 'create' : 'update', $resourceId, $oldValues, $newValues);
    }

    /**
     * Save display standard and optionally update descendants
     */
    protected function saveDisplayStandard(int $resourceId): void
    {
        $request = $this->getRequest();
        $displayStandardId = $request->getParameter('displayStandard');
        
        error_log("DISPLAY STANDARD DEBUG: received = " . var_export($displayStandardId, true));
        
        // Default to Museum (CCO) if not set - lookup by code
        if (empty($displayStandardId)) {
            $displayStandardId = $this->getTermIdByCode('museum', 70) ?? 353; // fallback to ISAD
        }
        
        error_log("DISPLAY STANDARD DEBUG: saving = " . $displayStandardId);
        
        // Update the record's display standard
        DB::table('information_object')
            ->where('id', $resourceId)
            ->update(['display_standard_id' => (int) $displayStandardId]);

        // If checkbox is ticked, update all descendants
        $updateDescendants = $request->getParameter('displayStandardUpdateDescendants');
        error_log("DISPLAY STANDARD DEBUG: updateDescendants = " . var_export($updateDescendants, true));
        
        if ($updateDescendants) {
            $this->updateDescendantsDisplayStandard($resourceId, (int) $displayStandardId);
        }
    }
	
    /**
     * Update display standard for all descendants
     */
    protected function updateDescendantsDisplayStandard(int $parentId, int $displayStandardId): void
    {
        // Get the parent's lft and rgt values
        $parent = DB::table('information_object')
            ->where('id', $parentId)
            ->select('lft', 'rgt')
            ->first();

        if (!$parent) {
            return;
        }

        // Update all descendants (records where lft > parent.lft AND rgt < parent.rgt)
        DB::table('information_object')
            ->where('lft', '>', $parent->lft)
            ->where('rgt', '<', $parent->rgt)
            ->update(['display_standard_id' => $displayStandardId]);

        error_log("Updated descendants of {$parentId} to display standard {$displayStandardId}");
    }
	
    /**
     * Capture current values for audit trail
     */
    /**
     * Capture current values for audit trail
     */
    protected function captureCurrentValues(int $resourceId): array
    {
        try {
            $culture = $this->getUser()->getCulture() ?? 'en';

            // Use COALESCE for culture fallback
            $io = DB::table('information_object as io')
                ->leftJoin('information_object_i18n as ioi', function ($join) use ($culture) {
                    $join->on('io.id', '=', 'ioi.id')
                        ->where('ioi.culture', '=', $culture);
                })
                ->leftJoin('information_object_i18n as ioi_en', function ($join) {
                    $join->on('io.id', '=', 'ioi_en.id')
                        ->where('ioi_en.culture', '=', 'en');
                })
                ->where('io.id', $resourceId)
                ->select([
                    'io.identifier',
                    'io.repository_id',
                    'io.display_standard_id',
                    DB::raw('COALESCE(ioi.title, ioi_en.title) as title'),
                    DB::raw('COALESCE(ioi.extent_and_medium, ioi_en.extent_and_medium) as extent_and_medium'),
                    DB::raw('COALESCE(ioi.scope_and_content, ioi_en.scope_and_content) as scope_and_content'),
                ])
                ->first();

            $ccoProperty = DB::table('property')
                ->leftJoin('property_i18n', 'property.id', '=', 'property_i18n.id')
                ->where('property.object_id', $resourceId)
                ->where('property.name', 'ccoData')
                ->value('property_i18n.value');

            $values = [];

            if ($io) {
                if ($io->identifier !== null) {
                    $values['identifier'] = $io->identifier;
                }
                if ($io->title !== null) {
                    $values['title'] = $io->title;
                }
                if ($io->repository_id !== null) {
                    $values['repository_id'] = $io->repository_id;
                }
                if ($io->extent_and_medium !== null) {
                    $values['extent_and_medium'] = $io->extent_and_medium;
                }
                if ($io->scope_and_content !== null) {
                    $values['scope_and_content'] = $io->scope_and_content;
                }
                if ($io->display_standard_id !== null) {
                    $values['display_standard_id'] = $io->display_standard_id;
                }
            }

            if ($ccoProperty) {
                $ccoData = json_decode($ccoProperty, true);
                if (is_array($ccoData)) {
                    $ccoData = array_filter($ccoData, function ($v) {
                        return $v !== null && $v !== '';
                    });
                    $values = array_merge($values, $ccoData);
                }
            }

            error_log("AUDIT CAPTURE: resourceId={$resourceId}, captured " . count($values) . " values");

            return $values;
        } catch (\Exception $e) {
            error_log("AUDIT CAPTURE ERROR: " . $e->getMessage());
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

            // Calculate changed fields
            $changedFields = [];
            foreach ($newValues as $key => $newVal) {
                $oldVal = $oldValues[$key] ?? null;
                if ($newVal !== $oldVal) {
                    $changedFields[] = $key;
                }
            }

            // Generate UUID
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
                'entity_type' => 'MuseumObject',
                'entity_id' => $resourceId,
                'entity_slug' => $this->resource->slug ?? null,
                'entity_title' => $newValues['title'] ?? null,
                'module' => 'ahgMuseumPlugin',
                'action_name' => 'edit',
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? null,
                'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
                'old_values' => !empty($oldValues) ? json_encode($oldValues) : null,
                'new_values' => !empty($newValues) ? json_encode($newValues) : null,
                'changed_fields' => !empty($changedFields) ? json_encode($changedFields) : null,
                'status' => 'success',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            error_log("AUDIT: Logged {$action} on MuseumObject {$resourceId} by {$username}");
        } catch (\Exception $e) {
            error_log("AUDIT ERROR: " . $e->getMessage());
        }
    }

    /**
     * Create new information object using Laravel
     */
    protected function createInformationObject(): int
    {
        $culture = $this->getUser()->getCulture() ?? 'en';
        $userId = $this->getUser()->getAttribute('user_id');
        $now = date('Y-m-d H:i:s');

        // Get parent lft/rgt for nested set
        $parent = DB::table('information_object')
            ->where('id', self::ROOT_INFORMATION_OBJECT_ID)
            ->first();

        // Create object record first
        $objectId = DB::table('object')->insertGetId([
            'class_name' => 'QubitInformationObject',
            'created_at' => $now,
            'updated_at' => $now,
            'serial_number' => 0,
        ]);

        // Calculate nested set values
        $lft = $parent->rgt;
        $rgt = $parent->rgt + 1;

        // Make room in nested set
        DB::table('information_object')
            ->where('rgt', '>=', $parent->rgt)
            ->increment('rgt', 2);

        DB::table('information_object')
            ->where('lft', '>', $parent->rgt)
            ->increment('lft', 2);

        // Insert information object
        DB::table('information_object')->insert([
            'id' => $objectId,
            'identifier' => $this->ccoData['object_number'] ?? null,
            'repository_id' => !empty($this->ccoData['repository']) && is_numeric($this->ccoData['repository'])
                ? (int) $this->ccoData['repository']
                : null,
            'parent_id' => self::ROOT_INFORMATION_OBJECT_ID,
            'lft' => $lft,
            'rgt' => $rgt,
            'source_culture' => $culture,
            'display_standard_id' => $this->getTermIdByCode('museum', 70) ?? 353, // Museum (CCO) standard
        ]);

        // Insert i18n record
        DB::table('information_object_i18n')->insert([
            'id' => $objectId,
            'culture' => $culture,
            'title' => $this->ccoData['title'] ?? null,
            'extent_and_medium' => $this->ccoData['dimensions_display'] ?? null,
            'scope_and_content' => $this->ccoData['description'] ?? null,
        ]);

        // Create slug
        $slug = $this->generateSlug($this->ccoData['title'] ?? 'untitled');
        DB::table('slug')->insert([
            'object_id' => $objectId,
            'slug' => $slug,
        ]);

        // Create publication status (159 = draft, 158 = publicationStatus type)
        DB::table('status')->insert([
            'object_id' => $objectId,
            'type_id' => 158,
            'status_id' => 159,
        ]);
		
        return $objectId;
    }

    /**
     * Update existing information object using Laravel
     */
    protected function updateInformationObject(int $resourceId): void
    {
        $culture = $this->getUser()->getCulture() ?? 'en';
        $now = date('Y-m-d H:i:s');

        // Update object timestamp
        DB::table('object')
            ->where('id', $resourceId)
            ->update(['updated_at' => $now]);

        // Update information object
        DB::table('information_object')
            ->where('id', $resourceId)
            ->update([
                'identifier' => $this->ccoData['object_number'] ?? null,
                'repository_id' => !empty($this->ccoData['repository']) && is_numeric($this->ccoData['repository'])
                    ? (int) $this->ccoData['repository']
                    : null,
                'display_standard_id' => $this->getTermIdByCode('museum', 70) ?? 353, // Museum (CCO) standard
            ]);

        // Update or insert i18n record
        $existingI18n = DB::table('information_object_i18n')
            ->where('id', $resourceId)
            ->where('culture', $culture)
            ->exists();

        if ($existingI18n) {
            DB::table('information_object_i18n')
                ->where('id', $resourceId)
                ->where('culture', $culture)
                ->update([
                    'title' => $this->ccoData['title'] ?? null,
                    'extent_and_medium' => $this->ccoData['dimensions_display'] ?? null,
                    'scope_and_content' => $this->ccoData['description'] ?? null,
                ]);
        } else {
            DB::table('information_object_i18n')->insert([
                'id' => $resourceId,
                'culture' => $culture,
                'title' => $this->ccoData['title'] ?? null,
                'extent_and_medium' => $this->ccoData['dimensions_display'] ?? null,
                'scope_and_content' => $this->ccoData['description'] ?? null,
            ]);
        }
    }

    /**
     * Generate unique slug
     */
    protected function generateSlug(string $title): string
    {
        // Convert to lowercase, replace spaces with hyphens, remove special chars
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        if (empty($slug)) {
            $slug = 'untitled';
        }

        // Ensure uniqueness
        $baseSlug = $slug;
        $counter = 1;

        while (DB::table('slug')->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Save property using Laravel
     */
    protected function saveProperty(int $objectId, string $name, string $value): void
    {
        $existingProperty = DB::table('property')
            ->where('object_id', $objectId)
            ->where('name', $name)
            ->first();

        if ($existingProperty) {
            DB::table('property_i18n')
                ->where('id', $existingProperty->id)
                ->update(['value' => $value]);
        } else {
            $propertyId = DB::table('property')->insertGetId([
                'object_id' => $objectId,
                'name' => $name,
                'source_culture' => 'en',
            ]);
            DB::table('property_i18n')->insert([
                'id' => $propertyId,
                'culture' => 'en',
                'value' => $value,
            ]);
        }
    }

    /**
     * Save watermark settings for the object
     */
    protected function saveWatermarkSettings($objectId)
    {
        $request = $this->getRequest();

        $watermarkEnabled = $request->getParameter('watermark_enabled') ? 1 : 0;
        $watermarkTypeId = $request->getParameter('watermark_type_id');
        $watermarkTypeId = ($watermarkTypeId === '' || $watermarkTypeId === null) ? null : (int) $watermarkTypeId;
        $customWatermarkId = $request->getParameter('custom_watermark_id');
        $customWatermarkId = ($customWatermarkId === '' || $customWatermarkId === null) ? null : (int) $customWatermarkId;
        $position = $request->getParameter('new_watermark_position', 'center');
        $opacity = ((int) $request->getParameter('new_watermark_opacity', 40)) / 100;

        // Handle custom watermark upload
        $newWatermarkName = $request->getParameter('new_watermark_name');
        $newWatermarkFile = $request->getFiles('new_watermark_file');

        if ($newWatermarkFile && !empty($newWatermarkFile['tmp_name']) && is_uploaded_file($newWatermarkFile['tmp_name'])) {
            $isGlobal = (bool) $request->getParameter('new_watermark_global');

            // Upload custom watermark using service
            require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
            $uploadedId = \AtomExtensions\Services\DerivativeWatermarkService::uploadCustomWatermark(
                $newWatermarkFile,
                $newWatermarkName ?: 'Custom Watermark',
                $isGlobal ? null : $objectId,
                $position,
                $opacity,
                sfContext::getInstance()->getUser()->getAttribute('user_id', 0)
            );

            if ($uploadedId) {
                $customWatermarkId = $uploadedId;
                $watermarkTypeId = null; // Clear system type when custom uploaded
            }
        }

        // Save to object_watermark_setting table
        $existing = DB::table('object_watermark_setting')
            ->where('object_id', $objectId)
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
            DB::table('object_watermark_setting')
                ->where('object_id', $objectId)
                ->update($data);
        } else {
            $data['object_id'] = $objectId;
            $data['created_at'] = date('Y-m-d H:i:s');
            DB::table('object_watermark_setting')->insert($data);
        }

        // Regenerate derivatives if requested
        if ($request->getParameter('regenerate_watermark')) {
            require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
            \AtomExtensions\Services\DerivativeWatermarkService::regenerateDerivatives($objectId);
        }

        error_log("Watermark settings saved for object $objectId: enabled=$watermarkEnabled");
    }

    /**
     * Save creator using Laravel
     */
    protected function saveCreator($resourceId, $creatorData)
    {
        if (empty($creatorData)) {
            return;
        }

        $actorId = null;
        $culture = $this->getUser()->getCulture() ?? 'en';

        if (is_numeric($creatorData)) {
            $actorId = (int) $creatorData;
        } else {
            // Search for existing actor
            $actor = DB::table('actor as a')
                ->join('actor_i18n as ai', 'a.id', '=', 'ai.id')
                ->where('ai.authorized_form_of_name', $creatorData)
                ->first();

            if ($actor) {
                $actorId = $actor->id;
            } else {
                // Create new actor using Laravel
                $actorId = $this->createActor($creatorData, $culture);
            }
        }

        if ($actorId) {
            // Check if event exists
            $existingEvent = DB::table('event')
                ->where('object_id', $resourceId)
                ->where('actor_id', $actorId)
                ->where('type_id', self::TERM_CREATION_ID)
                ->first();

            if (!$existingEvent) {
                // Create event using Laravel
                $this->createEvent($resourceId, $actorId, self::TERM_CREATION_ID);
            }
        }
    }

    /**
     * Create new actor using Laravel
     */
    protected function createActor(string $name, string $culture): int
    {
        $now = date('Y-m-d H:i:s');

        // Create object record
        $objectId = DB::table('object')->insertGetId([
            'class_name' => 'QubitActor',
            'created_at' => $now,
            'updated_at' => $now,
            'serial_number' => 0,
        ]);

        // Create actor record
        DB::table('actor')->insert([
            'id' => $objectId,
            'source_culture' => $culture,
        ]);

        // Create actor i18n record
        DB::table('actor_i18n')->insert([
            'id' => $objectId,
            'culture' => $culture,
            'authorized_form_of_name' => $name,
        ]);

        // Create slug
        $slug = $this->generateSlug($name);
        DB::table('slug')->insert([
            'object_id' => $objectId,
            'slug' => $slug,
        ]);

        return $objectId;
    }

    /**
     * Create event using Laravel
     */
    protected function createEvent(int $objectId, int $actorId, int $typeId): int
    {
        $now = date('Y-m-d H:i:s');
        $culture = $this->getUser()->getCulture() ?? 'en';

        // Create object record for event
        $eventObjectId = DB::table('object')->insertGetId([
            'class_name' => 'QubitEvent',
            'created_at' => $now,
            'updated_at' => $now,
            'serial_number' => 0,
        ]);

        // Create event record
        DB::table('event')->insert([
            'id' => $eventObjectId,
            'object_id' => $objectId,
            'actor_id' => $actorId,
            'type_id' => $typeId,
            'source_culture' => $culture,
        ]);

        return $eventObjectId;
    }
    /**
     * Get term ID by code and taxonomy
     */
    protected function getTermIdByCode(string $code, int $taxonomyId): ?int
    {
        $term = DB::table('term')
            ->where('code', $code)
            ->where('taxonomy_id', $taxonomyId)
            ->value('id');
        return $term ? (int) $term : null;
    }

}