<?php
use Illuminate\Database\Capsule\Manager as DB;

class ahgGalleryPluginEditAction extends sfAction
{
    protected $resource;
    protected $galleryData;

    public function execute($request)
    {
        if (\AtomExtensions\Database\DatabaseBootstrap::getCapsule() === null) {
            \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
        }

        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        $slug = $request->getParameter('slug');

        if ($slug) {
            $this->resource = DB::table('information_object as io')
                ->join('information_object_i18n as ioi', function($j) {
                    $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
                })
                ->join('slug as s', 'io.id', '=', 's.object_id')
                ->where('s.slug', $slug)
                ->select('io.*', 'ioi.title', 'ioi.scope_and_content', 'ioi.extent_and_medium', 's.slug')
                ->first();

            if ($this->resource) {
                $this->galleryData = $this->loadGalleryData($this->resource->id);
                // Load item physical location
                require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/ItemPhysicalLocationRepository.php';
                $locRepo = new \AtomFramework\Repositories\ItemPhysicalLocationRepository();
                $this->itemLocation = $locRepo->getLocationWithContainer($this->resource->id) ?? [];
                $this->resourceId = $this->resource->id;
                $this->resourceSlug = $this->resource->slug;
            }
        }

        if (!$this->resource) {
            $this->resource = (object)[
                'id' => null,
                'title' => '',
                'identifier' => '',
                'level_of_description_id' => null,
                'scope_and_content' => '',
                'extent_and_medium' => '',
                'repository_id' => null,
                'slug' => null
            ];
            $this->galleryData = [];
            $this->itemLocation = [];
            $this->resourceId = null;
            $this->resourceSlug = null;
        }

        $this->templateId = $request->getParameter('template', 'generic');
        $this->availableTemplates = ahgCCOTemplates::getTemplates();
        $this->ccoData = $this->galleryData;
        
        $this->loadFormOptions();
        $this->fieldDefinitions = ahgCCOFieldDefinitions::getAllCategories();
        $this->createFormWidgets();
        $this->completeness = $this->calculateCompleteness();

        if ($request->isMethod('post')) {
            // Handle template switching - just redirect, do not save
            if ($request->getParameter('switch_template')) {
                if ($this->resourceSlug) {
                    $this->redirect('/index.php/gallery/edit/' . $this->resourceSlug . '?template=' . $this->templateId);
                } else {
                    $this->redirect('/index.php/gallery/add?template=' . $this->templateId);
                }
            }
            $this->processForm($request);
        }
    }

    protected function createFormWidgets()
    { 
        $data = $this->galleryData;
        
        // Display Standard for admin panel
        $this->form->setWidget('displayStandard', new sfWidgetFormSelect(['choices' => ['' => '-- Select --'] + $this->displayStandards]));
        $this->form->setValidator('displayStandard', new sfValidatorString(['required' => false]));
        $this->form->setDefault('displayStandard', $this->resource->display_standard_id ?? $this->getTermIdByCode('gallery', 70) ?? 353);
        
        $this->form->setWidget('displayStandardUpdateDescendants', new sfWidgetFormInputCheckbox());
        $this->form->setValidator('displayStandardUpdateDescendants', new sfValidatorBoolean(['required' => false]));
        $this->form->setDefault('displayStandardUpdateDescendants', false);

        // Object/Work fields
        $this->addWidget('work_type', 'autocomplete', 'aat_object_types', $data['work_type'] ?? '');
        $this->addWidget('work_type_qualifier', 'select', ['possibly' => 'Possibly', 'probably' => 'Probably', 'formerly classified as' => 'Formerly classified as'], $data['work_type_qualifier'] ?? '');
        $this->addWidget('components_count', 'text', null, $data['components_count'] ?? '');
        $this->addWidget('object_number', 'text', null, $data['object_number'] ?? $this->resource->identifier ?? '');

        // Title fields
        $this->addWidget('title', 'text', null, $data['title'] ?? $this->resource->title ?? '');
        $this->addWidget('title_type', 'select', ['repository' => 'Repository', 'creator' => 'Creator', 'descriptive' => 'Descriptive', 'former' => 'Former', 'translated' => 'Translated'], $data['title_type'] ?? '');
        $this->addWidget('title_language', 'text', null, $data['title_language'] ?? '');
        $this->addWidget('alternate_titles', 'textarea', null, $data['alternate_titles'] ?? '');

        // Creation fields
        $this->addWidget('creator_display', 'text', null, $data['creator_display'] ?? '');
        $this->addWidget('creator', 'select', $this->creators, $data['creator'] ?? '');
        $this->addWidget('creator_role', 'autocomplete', 'aat_creator_roles', $data['creator_role'] ?? '');
        $this->addWidget('attribution_qualifier', 'select', ['attributed to' => 'Attributed to', 'workshop of' => 'Workshop of', 'circle of' => 'Circle of', 'follower of' => 'Follower of', 'manner of' => 'Manner of', 'school of' => 'School of', 'after' => 'After', 'copy after' => 'Copy after'], $data['attribution_qualifier'] ?? '');
        $this->addWidget('creation_date_display', 'text', null, $data['creation_date_display'] ?? '');
        $this->addWidget('creation_date_earliest', 'date', null, $data['creation_date_earliest'] ?? '');
        $this->addWidget('creation_date_latest', 'date', null, $data['creation_date_latest'] ?? '');
        $this->addWidget('creation_place', 'autocomplete', 'tgn', $data['creation_place'] ?? '');
        $this->addWidget('culture', 'autocomplete', 'aat_cultures', $data['culture'] ?? '');

        // Styles/Periods
        $this->addWidget('style', 'autocomplete', 'aat_styles', $data['style'] ?? '');
        $this->addWidget('period', 'autocomplete', 'aat_periods', $data['period'] ?? '');
        $this->addWidget('school_group', 'text', null, $data['school_group'] ?? '');

        // Measurements
        $this->addWidget('dimensions_display', 'textarea', null, $data['dimensions_display'] ?? $this->resource->extent_and_medium ?? '');
        $this->addWidget('height_value', 'text', null, $data['height_value'] ?? '');
        $this->addWidget('width_value', 'text', null, $data['width_value'] ?? '');
        $this->addWidget('depth_value', 'text', null, $data['depth_value'] ?? '');
        $this->addWidget('weight_value', 'text', null, $data['weight_value'] ?? '');
        $this->addWidget('dimension_notes', 'textarea', null, $data['dimension_notes'] ?? '');

        // Materials/Techniques
        $this->addWidget('materials_display', 'textarea', null, $data['materials_display'] ?? '');
        $this->addWidget('materials', 'autocomplete', 'aat_materials', $data['materials'] ?? '');
        $this->addWidget('techniques', 'autocomplete', 'aat_techniques', $data['techniques'] ?? '');
        $this->addWidget('support', 'autocomplete', 'aat_supports', $data['support'] ?? '');

        // Subject
        $this->addWidget('subject_display', 'textarea', null, $data['subject_display'] ?? '');
        $this->addWidget('subjects_depicted', 'autocomplete', 'aat_subjects', $data['subjects_depicted'] ?? '');
        $this->addWidget('iconography', 'text', null, $data['iconography'] ?? '');
        $this->addWidget('named_subjects', 'text', null, $data['named_subjects'] ?? '');

        // Inscriptions
        $this->addWidget('inscriptions', 'textarea', null, $data['inscriptions'] ?? '');
        $this->addWidget('signature', 'text', null, $data['signature'] ?? '');
        $this->addWidget('marks', 'textarea', null, $data['marks'] ?? '');

        // State/Edition (mostly hidden for paintings)
        $this->addWidget('edition_number', 'text', null, $data['edition_number'] ?? '');
        $this->addWidget('edition_size', 'text', null, $data['edition_size'] ?? '');
        $this->addWidget('state', 'text', null, $data['state'] ?? '');
        $this->addWidget('impression_quality', 'text', null, $data['impression_quality'] ?? '');

        // Description
        $this->addWidget('description', 'textarea', null, $data['description'] ?? $this->resource->scope_and_content ?? '');
        $this->addWidget('physical_description', 'textarea', null, $data['physical_description'] ?? '');

        // Condition
        $this->addWidget('condition_summary', 'textarea', null, $data['condition_summary'] ?? '');
        $this->addWidget('condition_notes', 'textarea', null, $data['condition_notes'] ?? '');

        // Location
        $this->addWidget('repository', 'select', $this->repositories, $this->resource->repository_id ?? '');
        $this->addWidget('location_within_repository', 'text', null, $data['location_within_repository'] ?? '');
        $this->addWidget('credit_line', 'text', null, $data['credit_line'] ?? '');

        // Related Works
        $this->addWidget('related_works', 'textarea', null, $data['related_works'] ?? '');
        $this->addWidget('relationship_type', 'select', ['part of' => 'Part of', 'companion to' => 'Companion to', 'preparatory study for' => 'Preparatory study for', 'copy of' => 'Copy of', 'version of' => 'Version of'], $data['relationship_type'] ?? '');

        // Rights
        $this->addWidget('rights_statement', 'textarea', null, $data['rights_statement'] ?? '');
        $this->addWidget('copyright_holder', 'text', null, $data['copyright_holder'] ?? '');
        $this->addWidget('reproduction_conditions', 'textarea', null, $data['reproduction_conditions'] ?? '');
    }

    protected function addWidget($name, $type, $choices = null, $default = '')
    {
        switch ($type) {
            case 'select':
                $options = ['' => '-- Select --'];
                if ($choices) {
                    $options = $options + $choices;
                }
                $this->form->setWidget($name, new sfWidgetFormSelect(['choices' => $options]));
                break;
            case 'textarea':
                $this->form->setWidget($name, new sfWidgetFormTextarea());
                break;
            case 'date':
                $this->form->setWidget($name, new sfWidgetFormInput(['type' => 'date']));
                break;
            case 'autocomplete':
                $this->form->setWidget($name, new sfWidgetFormInput([], [
                    'class' => 'form-control gallery-autocomplete',
                    'data-vocabulary' => $choices ?? '',
                ]));
                break;
            default:
                $this->form->setWidget($name, new sfWidgetFormInput());
        }
        $this->form->setValidator($name, new sfValidatorString(['required' => false]));
        $this->form->setDefault($name, $default);
    }
    protected function calculateCompleteness()
    {
        $required = ['title', 'object_number', 'work_type'];
        $filled = 0;
        foreach ($required as $field) {
            if (!empty($this->galleryData[$field]) || 
                ($field === 'title' && !empty($this->resource->title)) ||
                ($field === 'object_number' && !empty($this->resource->identifier))) {
                $filled++;
            }
        }
        return count($required) > 0 ? round(($filled / count($required)) * 100) : 0;
    }

    protected function loadGalleryData($resourceId)
    {
        $prop = DB::table('property as p')
            ->join('property_i18n as pi', function($j) {
                $j->on('p.id', '=', 'pi.id')->where('pi.culture', '=', 'en');
            })
            ->where('p.object_id', $resourceId)
            ->where('p.name', 'galleryData')
            ->select('pi.value')
            ->first();

        $data = $prop ? (json_decode($prop->value, true) ?: []) : [];

        if ($this->resource) {
            $data['title'] = $data['title'] ?? $this->resource->title;
            $data['object_number'] = $data['object_number'] ?? $this->resource->identifier;
            $data['dimensions_display'] = $data['dimensions_display'] ?? $this->resource->extent_and_medium;
            $data['description'] = $data['description'] ?? $this->resource->scope_and_content;
        }

        return $data;
    }

    protected function loadFormOptions()
    {
        $this->workTypes = DB::table('term as t')
            ->join('term_i18n as ti', function($j) {
                $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', 'en');
            })
            ->where('t.taxonomy_id', 35)
            ->orderBy('ti.name')
            ->pluck('ti.name', 't.id')
            ->toArray();

        $this->materials = DB::table('term as t')
            ->join('term_i18n as ti', function($j) {
                $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', 'en');
            })
            ->where('t.taxonomy_id', 56)
            ->orderBy('ti.name')
            ->pluck('ti.name', 't.id')
            ->toArray();

        $this->repositories = DB::table('repository')
            ->join('actor_i18n as ai', function($j) {
                $j->on('repository.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
            })
            ->orderBy('ai.authorized_form_of_name')
            ->pluck('ai.authorized_form_of_name', 'repository.id')
            ->toArray();

        $this->creators = DB::table('actor')
            ->join('actor_i18n as ai', function($j) {
                $j->on('actor.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
            })
            ->whereNotNull('ai.authorized_form_of_name')
            ->where('ai.authorized_form_of_name', '!=', '')
            ->orderBy('ai.authorized_form_of_name')
            ->pluck('ai.authorized_form_of_name', 'actor.id')
            ->toArray();

        // Display standards
        $this->displayStandards = DB::table('term as t')
            ->join('term_i18n as ti', function($j) {
                $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', 'en');
            })
            ->where('t.taxonomy_id', 70)
            ->orderBy('ti.name')
            ->pluck('ti.name', 't.id')
            ->toArray();
    }

    protected function processForm($request)
    {
        $hiddenId = $request->getParameter('id');
        $isNew = empty($hiddenId) && (!isset($this->resource->id) || !$this->resource->id);
        if ($hiddenId && (!isset($this->resource->id) || !$this->resource->id)) {
            // Get slug for existing record
            $slugRow = DB::table('slug')->where('object_id', (int)$hiddenId)->first();
            $this->resource = (object)['id' => (int)$hiddenId, 'slug' => $slugRow->slug ?? null];
        }
        
        // Capture old values for audit trail
        $oldValues = [];
        if (!$isNew && isset($this->resource->id) && $this->resource->id) {
            $oldValues = $this->captureCurrentValues($this->resource->id);
        }

        // Collect all field data
        $fields = ['work_type', 'work_type_qualifier', 'components_count', 'object_number',
                   'title', 'title_type', 'title_language', 'alternate_titles',
                   'creator_display', 'creator', 'creator_role', 'attribution_qualifier',
                   'creation_date_display', 'creation_date_earliest', 'creation_date_latest',
                   'creation_place', 'culture', 'style', 'period', 'school_group',
                   'dimensions_display', 'height_value', 'width_value', 'depth_value',
                   'weight_value', 'dimension_notes', 'materials_display', 'materials',
                   'techniques', 'support', 'subject_display', 'subjects_depicted',
                   'iconography', 'named_subjects', 'inscriptions', 'signature', 'marks',
                   'edition_number', 'edition_size', 'state', 'impression_quality',
                   'description', 'physical_description', 'condition_summary', 'condition_notes',
                   'location_within_repository', 'credit_line', 'related_works',
                   'relationship_type', 'rights_statement', 'copyright_holder', 'reproduction_conditions'];

        $this->galleryData = [];
            $this->itemLocation = [];
        foreach ($fields as $field) {
            $this->galleryData[$field] = $request->getParameter($field);
        }

        if ($isNew) {
            // Get parent from request parameter (slug) or use root
            $parentSlug = $request->getParameter('parent');
            $parentId = 1; // ROOT_INFORMATION_OBJECT_ID
            
            if ($parentSlug) {
                $parentRecord = DB::table('slug')
                    ->join('information_object', 'slug.object_id', '=', 'information_object.id')
                    ->where('slug.slug', $parentSlug)
                    ->select('information_object.id')
                    ->first();
                if ($parentRecord) {
                    $parentId = $parentRecord->id;
                }
            }
            
            // Get parent lft/rgt for nested set
            $parent = DB::table('information_object')
                ->where('id', $parentId)
                ->first();
            
            // Create object record
            $objectId = DB::table('object')->insertGetId([
                'class_name' => 'QubitInformationObject',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
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
            
            DB::table('information_object')->insert([
                'id' => $objectId,
                'identifier' => $request->getParameter('object_number'),
                'level_of_description_id' => null,
                'repository_id' => $request->getParameter('repository') ?: null,
                'parent_id' => $parentId,
                'lft' => $lft,
                'rgt' => $rgt,
                'source_culture' => 'en',
                'display_standard_id' => $this->getTermIdByCode('gallery', 70) ?? 353,
            ]);
            DB::table('information_object_i18n')->insert([
                'id' => $objectId,
                'culture' => 'en',
                'title' => $request->getParameter('title'),
                'scope_and_content' => $request->getParameter('description'),
                'extent_and_medium' => $request->getParameter('dimensions_display'),
            ]);
            $slug = $this->generateSlug($request->getParameter('title'));
            DB::table('slug')->insert([
                'object_id' => $objectId,
                'slug' => $slug,
            ]);
            DB::table('status')->insert([
                'object_id' => $objectId,
                'type_id' => 158,
                'status_id' => 159,
            ]);

            $resourceId = $objectId;
            $this->resource = (object)['id' => $objectId, 'slug' => $slug];
        } else {
            $resourceId = $this->resource->id;

            DB::table('information_object')
                ->where('id', $resourceId)
                ->update([
                    'identifier' => $request->getParameter('object_number'),
                    'repository_id' => $request->getParameter('repository') ?: null,
                ]);

            DB::table('information_object_i18n')
                ->where('id', $resourceId)
                ->where('culture', 'en')
                ->update([
                    'title' => $request->getParameter('title'),
                    'scope_and_content' => $request->getParameter('description'),
                    'extent_and_medium' => $request->getParameter('dimensions_display'),
                ]);
        }

        $this->saveProperty($resourceId, 'galleryData', json_encode($this->galleryData));

        // Set display type for GLAM facet
        DB::table('display_object_config')->updateOrInsert(
            ['object_id' => $resourceId],
            ['object_type' => 'gallery', 'updated_at' => date('Y-m-d H:i:s')]
        );

        if (!empty($this->galleryData['creator'])) {
            $this->saveCreator($resourceId, $this->galleryData['creator']);
        }
        // Save item physical location
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/ItemPhysicalLocationRepository.php';
        $locRepo = new \AtomFramework\Repositories\ItemPhysicalLocationRepository();
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

        // Capture new values and log audit trail
        $newValues = $this->captureCurrentValues($resourceId);
        $this->logAudit($isNew ? 'create' : 'update', $resourceId, $oldValues, $newValues);

        $this->redirect(['module' => 'ahgGalleryPlugin', 'action' => 'index', 'slug' => $this->resource->slug ?? $this->resourceSlug]);
    }

    protected function saveCreator($resourceId, $actorId)
    {
        $existing = DB::table('event')
            ->where('object_id', $resourceId)
            ->where('type_id', 111)
            ->first();
        if ($existing) {
            DB::table('event')->where('id', $existing->id)->update(['actor_id' => $actorId]);
        } else {
            $eventObjectId = DB::table('object')->insertGetId([
                'class_name' => 'QubitEvent',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            DB::table('event')->insert([
                'id' => $eventObjectId,
                'object_id' => $resourceId,
                'type_id' => 111,
                'actor_id' => $actorId,
                'source_culture' => 'en',
            ]);
        }
    }

    protected function saveProperty($objectId, $name, $value)
    {
        $existing = DB::table('property')
            ->where('object_id', $objectId)
            ->where('name', $name)
            ->first();

        if ($existing) {
            DB::table('property_i18n')
                ->where('id', $existing->id)
                ->where('culture', 'en')
                ->update(['value' => $value]);
        } else {
            $propId = DB::table('property')->insertGetId([
                'object_id' => $objectId,
                'name' => $name,
                'source_culture' => 'en',
            ]);
            DB::table('property_i18n')->insert([
                'id' => $propId,
                'culture' => 'en',
                'value' => $value,
            ]);
        }
    }

    protected function generateSlug($title)
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        if (empty($slug)) $slug = 'untitled';

        $baseSlug = $slug;
        $counter = 1;
        while (DB::table('slug')->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        return $slug;
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
            
            $galleryProperty = DB::table('property')
                ->leftJoin('property_i18n', 'property.id', '=', 'property_i18n.id')
                ->where('property.object_id', $resourceId)
                ->where('property.name', 'galleryData')
                ->value('property_i18n.value');
            
            $values = [];
            if ($io) {
                if ($io->identifier) $values['identifier'] = $io->identifier;
                if ($io->title) $values['title'] = $io->title;
                if ($io->scope_and_content) $values['description'] = $io->scope_and_content;
                if ($io->extent_and_medium) $values['dimensions_display'] = $io->extent_and_medium;
            }
            
            if ($galleryProperty) {
                $galleryData = json_decode($galleryProperty, true);
                if (is_array($galleryData)) {
                    $galleryFields = ['work_type', 'creator_display', 'creation_date_display', 
                        'materials_display', 'subject_display', 'condition_summary'];
                    foreach ($galleryFields as $field) {
                        if (!empty($galleryData[$field])) {
                            $values[$field] = $galleryData[$field];
                        }
                    }
                }
            }
            
            return $values;
        } catch (\Exception $e) {
            error_log("Gallery AUDIT CAPTURE ERROR: " . $e->getMessage());
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
                'entity_type' => 'GalleryWork',
                'entity_id' => $resourceId,
                'entity_slug' => $this->resource->slug ?? null,
                'entity_title' => $newValues['title'] ?? null,
                'module' => 'ahgGalleryPlugin',
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
            error_log("Gallery AUDIT ERROR: " . $e->getMessage());
        }
    }

}
