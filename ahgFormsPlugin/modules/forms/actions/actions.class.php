<?php

/**
 * Forms module actions.
 */
class formsActions extends sfActions
{
    /**
     * Dashboard / index.
     */
    public function executeIndex(sfWebRequest $request)
    {
        $this->checkAdmin();

        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgFormsPlugin/lib/Services/FormService.php';
        $service = new \ahgFormsPlugin\Services\FormService();

        $this->templates = $service->getTemplates();
        $this->stats = $service->getStatistics();
    }

    /**
     * List templates.
     */
    public function executeTemplates(sfWebRequest $request)
    {
        $this->checkAdmin();

        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgFormsPlugin/lib/Services/FormService.php';
        $service = new \ahgFormsPlugin\Services\FormService();

        $type = $request->getParameter('type');
        $this->templates = $service->getTemplates($type);
        $this->currentType = $type;
    }

    /**
     * Create new template.
     */
    public function executeTemplateCreate(sfWebRequest $request)
    {
        $this->checkAdmin();

        if ($request->isMethod('post')) {
            require_once sfConfig::get('sf_root_dir') . '/plugins/ahgFormsPlugin/lib/Services/FormService.php';
            $service = new \ahgFormsPlugin\Services\FormService();

            $templateId = $service->createTemplate([
                'name' => $request->getParameter('name'),
                'description' => $request->getParameter('description'),
                'form_type' => $request->getParameter('form_type'),
                'config' => [
                    'layout' => $request->getParameter('layout', 'single'),
                ],
            ]);

            $this->redirect(['module' => 'forms', 'action' => 'builder', 'id' => $templateId]);
        }
    }

    /**
     * Edit template.
     */
    public function executeTemplateEdit(sfWebRequest $request)
    {
        $this->checkAdmin();

        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgFormsPlugin/lib/Services/FormService.php';
        $service = new \ahgFormsPlugin\Services\FormService();

        $this->template = $service->getTemplate((int) $request->getParameter('id'));
        $this->forward404Unless($this->template);

        if ($request->isMethod('post')) {
            $service->updateTemplate($this->template->id, [
                'name' => $request->getParameter('name'),
                'description' => $request->getParameter('description'),
                'config' => json_decode($request->getParameter('config_json'), true),
                'is_default' => $request->getParameter('is_default') ? true : false,
            ]);

            $this->redirect(['module' => 'forms', 'action' => 'templates']);
        }
    }

    /**
     * Delete template.
     */
    public function executeTemplateDelete(sfWebRequest $request)
    {
        $this->checkAdmin();

        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgFormsPlugin/lib/Services/FormService.php';
        $service = new \ahgFormsPlugin\Services\FormService();

        $service->deleteTemplate((int) $request->getParameter('id'));

        $this->redirect(['module' => 'forms', 'action' => 'templates']);
    }

    /**
     * Clone template.
     */
    public function executeTemplateClone(sfWebRequest $request)
    {
        $this->checkAdmin();

        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgFormsPlugin/lib/Services/FormService.php';
        $service = new \ahgFormsPlugin\Services\FormService();

        $sourceId = (int) $request->getParameter('id');
        $source = $service->getTemplate($sourceId);
        $this->forward404Unless($source);

        if ($request->isMethod('post')) {
            $newId = $service->cloneTemplate($sourceId, $request->getParameter('name'));
            $this->redirect(['module' => 'forms', 'action' => 'builder', 'id' => $newId]);
        }

        $this->template = $source;
    }

    /**
     * Export template as JSON.
     */
    public function executeTemplateExport(sfWebRequest $request)
    {
        $this->checkAdmin();

        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgFormsPlugin/lib/Services/FormService.php';
        $service = new \ahgFormsPlugin\Services\FormService();

        $export = $service->exportTemplate((int) $request->getParameter('id'));

        $this->getResponse()->setHttpHeader('Content-Type', 'application/json');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="form-template-' . $request->getParameter('id') . '.json"');

        return $this->renderText(json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Import template from JSON.
     */
    public function executeTemplateImport(sfWebRequest $request)
    {
        $this->checkAdmin();

        if ($request->isMethod('post')) {
            $file = $request->getFiles('import_file');

            if ($file && $file['tmp_name']) {
                $json = file_get_contents($file['tmp_name']);
                $data = json_decode($json, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    require_once sfConfig::get('sf_root_dir') . '/plugins/ahgFormsPlugin/lib/Services/FormService.php';
                    $service = new \ahgFormsPlugin\Services\FormService();

                    $name = $request->getParameter('name') ?: null;
                    $templateId = $service->importTemplate($data, $name);

                    $this->getUser()->setFlash('notice', 'Template imported successfully!');
                    $this->redirect(['module' => 'forms', 'action' => 'builder', 'id' => $templateId]);
                } else {
                    $this->getUser()->setFlash('error', 'Invalid JSON file');
                }
            }
        }
    }

    /**
     * Form builder (drag-drop interface).
     */
    public function executeBuilder(sfWebRequest $request)
    {
        $this->checkAdmin();

        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgFormsPlugin/lib/Services/FormService.php';
        $service = new \ahgFormsPlugin\Services\FormService();

        $this->template = $service->getTemplate((int) $request->getParameter('id'));
        $this->forward404Unless($this->template);

        // Get fields for this template
        $this->fields = \Illuminate\Database\Capsule\Manager::table('ahg_form_field')
            ->where('template_id', $this->template->id)
            ->orderBy('sort_order')
            ->get();

        // Get available field types
        $this->fieldTypes = [
            'text' => 'Text Input',
            'textarea' => 'Text Area',
            'richtext' => 'Rich Text Editor',
            'date' => 'Date Picker',
            'daterange' => 'Date Range',
            'select' => 'Dropdown Select',
            'multiselect' => 'Multi-Select',
            'autocomplete' => 'Autocomplete',
            'checkbox' => 'Checkbox',
            'radio' => 'Radio Buttons',
            'file' => 'File Upload',
            'hidden' => 'Hidden Field',
            'heading' => 'Section Heading',
            'divider' => 'Divider',
        ];
    }

    /**
     * Add field to template (AJAX).
     */
    public function executeFieldAdd(sfWebRequest $request)
    {
        $this->checkAdmin();
        $this->getResponse()->setContentType('application/json');

        $templateId = (int) $request->getParameter('template_id');
        $fieldType = $request->getParameter('field_type');
        $label = $request->getParameter('label');
        $atomField = $request->getParameter('atom_field');

        // Get next sort order
        $maxSort = \Illuminate\Database\Capsule\Manager::table('ahg_form_field')
            ->where('template_id', $templateId)
            ->max('sort_order') ?? 0;

        $fieldId = \Illuminate\Database\Capsule\Manager::table('ahg_form_field')->insertGetId([
            'template_id' => $templateId,
            'field_name' => strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $label)),
            'field_type' => $fieldType,
            'label' => $label,
            'atom_field' => $atomField ?: null,
            'sort_order' => $maxSort + 1,
            'is_required' => 0,
            'is_readonly' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->renderText(json_encode(['success' => true, 'field_id' => $fieldId]));
    }

    /**
     * Get field data (AJAX).
     */
    public function executeFieldGet(sfWebRequest $request)
    {
        $this->checkAdmin();
        $this->getResponse()->setContentType('application/json');

        $fieldId = (int) $request->getParameter('field_id');
        $field = \Illuminate\Database\Capsule\Manager::table('ahg_form_field')
            ->where('id', $fieldId)
            ->first();

        if (!$field) {
            return $this->renderText(json_encode(['error' => 'Field not found']));
        }

        return $this->renderText(json_encode((array) $field));
    }

    /**
     * Update field (AJAX).
     */
    public function executeFieldUpdate(sfWebRequest $request)
    {
        $this->checkAdmin();
        $this->getResponse()->setContentType('application/json');

        $fieldId = (int) $request->getParameter('field_id');

        \Illuminate\Database\Capsule\Manager::table('ahg_form_field')
            ->where('id', $fieldId)
            ->update([
                'label' => $request->getParameter('label'),
                'field_name' => $request->getParameter('field_name'),
                'help_text' => $request->getParameter('help_text'),
                'placeholder' => $request->getParameter('placeholder'),
                'default_value' => $request->getParameter('default_value'),
                'is_required' => $request->getParameter('is_required') ? 1 : 0,
                'is_readonly' => $request->getParameter('is_readonly') ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return $this->renderText(json_encode(['success' => true]));
    }

    /**
     * Delete field (AJAX).
     */
    public function executeFieldDelete(sfWebRequest $request)
    {
        $this->checkAdmin();
        $this->getResponse()->setContentType('application/json');

        $fieldId = (int) $request->getParameter('field_id');

        \Illuminate\Database\Capsule\Manager::table('ahg_form_field')
            ->where('id', $fieldId)
            ->delete();

        return $this->renderText(json_encode(['success' => true]));
    }

    /**
     * Reorder fields (AJAX).
     */
    public function executeFieldReorder(sfWebRequest $request)
    {
        $this->checkAdmin();
        $this->getResponse()->setContentType('application/json');

        $data = json_decode($request->getContent(), true);
        $order = $data['order'] ?? [];

        foreach ($order as $item) {
            \Illuminate\Database\Capsule\Manager::table('ahg_form_field')
                ->where('id', $item['id'])
                ->update(['sort_order' => $item['sort']]);
        }

        return $this->renderText(json_encode(['success' => true]));
    }

    /**
     * Preview form template.
     */
    public function executePreview(sfWebRequest $request)
    {
        $this->checkAdmin();

        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgFormsPlugin/lib/Services/FormService.php';
        $service = new \ahgFormsPlugin\Services\FormService();

        $this->template = $service->getTemplate((int) $request->getParameter('id'));
        $this->forward404Unless($this->template);

        $this->fields = \Illuminate\Database\Capsule\Manager::table('ahg_form_field')
            ->where('template_id', $this->template->id)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Assignments list.
     */
    public function executeAssignments(sfWebRequest $request)
    {
        $this->checkAdmin();

        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgFormsPlugin/lib/Services/FormService.php';
        $service = new \ahgFormsPlugin\Services\FormService();

        $this->assignments = $service->getAssignments();
        $this->templates = $service->getTemplates();
    }

    /**
     * Create assignment.
     */
    public function executeAssignmentCreate(sfWebRequest $request)
    {
        $this->checkAdmin();

        if ($request->isMethod('post')) {
            require_once sfConfig::get('sf_root_dir') . '/plugins/ahgFormsPlugin/lib/Services/FormService.php';
            $service = new \ahgFormsPlugin\Services\FormService();

            $service->createAssignment([
                'template_id' => (int) $request->getParameter('template_id'),
                'repository_id' => $request->getParameter('repository_id') ?: null,
                'level_of_description_id' => $request->getParameter('level_of_description_id') ?: null,
                'collection_id' => $request->getParameter('collection_id') ?: null,
                'priority' => (int) $request->getParameter('priority', 100),
                'inherit_to_children' => $request->getParameter('inherit_to_children') ? true : false,
            ]);

            $this->redirect(['module' => 'forms', 'action' => 'assignments']);
        }

        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgFormsPlugin/lib/Services/FormService.php';
        $service = new \ahgFormsPlugin\Services\FormService();

        $this->templates = $service->getTemplates();
    }

    /**
     * Delete assignment.
     */
    public function executeAssignmentDelete(sfWebRequest $request)
    {
        $this->checkAdmin();

        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgFormsPlugin/lib/Services/FormService.php';
        $service = new \ahgFormsPlugin\Services\FormService();

        $service->deleteAssignment((int) $request->getParameter('id'));

        $this->redirect(['module' => 'forms', 'action' => 'assignments']);
    }

    /**
     * API: Save fields (AJAX).
     */
    public function executeApiSaveFields(sfWebRequest $request)
    {
        $this->checkAdmin();

        $this->getResponse()->setHttpHeader('Content-Type', 'application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['error' => 'POST required']));
        }

        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgFormsPlugin/lib/Services/FormService.php';
        $service = new \ahgFormsPlugin\Services\FormService();

        $templateId = (int) $request->getParameter('id');
        $fields = json_decode($request->getContent(), true);

        if (!$fields) {
            return $this->renderText(json_encode(['error' => 'Invalid JSON']));
        }

        // Process field updates
        foreach ($fields as $fieldData) {
            if (isset($fieldData['id'])) {
                $service->updateField((int) $fieldData['id'], $fieldData);
            } else {
                $service->addField($templateId, $fieldData);
            }
        }

        return $this->renderText(json_encode(['success' => true]));
    }

    /**
     * API: Reorder fields (AJAX).
     */
    public function executeApiReorderFields(sfWebRequest $request)
    {
        $this->checkAdmin();

        $this->getResponse()->setHttpHeader('Content-Type', 'application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['error' => 'POST required']));
        }

        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgFormsPlugin/lib/Services/FormService.php';
        $service = new \ahgFormsPlugin\Services\FormService();

        $templateId = (int) $request->getParameter('id');
        $order = json_decode($request->getContent(), true);

        $service->reorderFields($templateId, $order);

        return $this->renderText(json_encode(['success' => true]));
    }

    /**
     * API: Get rendered form (AJAX).
     */
    public function executeApiGetForm(sfWebRequest $request)
    {
        $this->getResponse()->setHttpHeader('Content-Type', 'application/json');

        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgFormsPlugin/lib/Services/FormService.php';
        $service = new \ahgFormsPlugin\Services\FormService();

        $type = $request->getParameter('type');
        $objectId = (int) $request->getParameter('id');

        // Get repository/level context
        $repositoryId = null;
        $levelId = null;

        if ($type === 'informationobject' && $objectId) {
            $obj = \Illuminate\Database\Capsule\Manager::table('information_object')
                ->where('id', $objectId)
                ->first();
            if ($obj) {
                $repositoryId = $obj->repository_id;
                $levelId = $obj->level_of_description_id;
            }
        }

        $template = $service->resolveTemplate($type === 'informationobject' ? 'information_object' : $type, $repositoryId, $levelId);

        if (!$template) {
            return $this->renderText(json_encode(['error' => 'No template found']));
        }

        return $this->renderText(json_encode([
            'template_id' => $template->id,
            'template_name' => $template->name,
            'config' => $template->config,
            'fields' => $template->fields,
        ]));
    }

    /**
     * API: Autosave draft (AJAX).
     */
    public function executeApiAutosave(sfWebRequest $request)
    {
        $this->getResponse()->setHttpHeader('Content-Type', 'application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['error' => 'POST required']));
        }

        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['template_id']) || !isset($data['object_type']) || !isset($data['form_data'])) {
            return $this->renderText(json_encode(['error' => 'Missing required fields']));
        }

        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgFormsPlugin/lib/Services/FormService.php';
        $service = new \ahgFormsPlugin\Services\FormService();

        $draftId = $service->saveDraft(
            (int) $data['template_id'],
            $data['object_type'],
            $data['object_id'] ?? null,
            $data['form_data']
        );

        return $this->renderText(json_encode([
            'success' => true,
            'draft_id' => $draftId,
            'saved_at' => date('Y-m-d H:i:s'),
        ]));
    }

    /**
     * Browse all form templates.
     */
    public function executeBrowse(sfWebRequest $request)
    {
        $this->checkAdmin();

        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgFormsPlugin/lib/Services/FormService.php';
        $service = new \ahgFormsPlugin\Services\FormService();

        // Get filter parameters
        $type = $request->getParameter('type');
        $search = $request->getParameter('search');

        // Get templates with optional filtering
        $query = \Illuminate\Database\Capsule\Manager::table('ahg_form_template')
            ->orderBy('name');

        if ($type) {
            $query->where('form_type', $type);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $this->templates = $query->get();

        // Get field counts for each template
        foreach ($this->templates as $template) {
            $template->field_count = \Illuminate\Database\Capsule\Manager::table('ahg_form_field')
                ->where('template_id', $template->id)
                ->count();
        }

        // Available form types for filtering
        $this->formTypes = [
            'information_object' => 'Information Object',
            'actor' => 'Authority Record',
            'repository' => 'Repository',
            'accession' => 'Accession',
            'deaccession' => 'Deaccession',
            'rights' => 'Rights',
        ];

        $this->currentType = $type;
        $this->currentSearch = $search;
    }

    /**
     * Template library.
     */
    public function executeLibrary(sfWebRequest $request)
    {
        $this->checkAdmin();

        // Pre-built templates available for installation
        $this->library = [
            [
                'id' => 'isadg-minimal',
                'name' => 'ISAD-G Minimal',
                'description' => 'Minimal ISAD(G) compliant form with essential fields only',
                'fields' => 8,
                'installed' => $this->isTemplateInstalled('ISAD-G Minimal'),
            ],
            [
                'id' => 'isadg-full',
                'name' => 'ISAD-G Full',
                'description' => 'Complete ISAD(G) form with all 26 elements across 7 areas',
                'fields' => 26,
                'installed' => $this->isTemplateInstalled('ISAD-G Full'),
            ],
            [
                'id' => 'dublin-core',
                'name' => 'Dublin Core Simple',
                'description' => 'Dublin Core 15 core elements',
                'fields' => 15,
                'installed' => $this->isTemplateInstalled('Dublin Core Simple'),
            ],
            [
                'id' => 'accession',
                'name' => 'Accession Standard',
                'description' => 'Standard accession registration form',
                'fields' => 15,
                'installed' => $this->isTemplateInstalled('Accession Standard'),
            ],
            [
                'id' => 'photo-collection',
                'name' => 'Photo Collection Item',
                'description' => 'Specialized form for photograph collections',
                'fields' => 19,
                'installed' => $this->isTemplateInstalled('Photo Collection Item'),
            ],
        ];
    }

    /**
     * Install template from library.
     */
    public function executeLibraryInstall(sfWebRequest $request)
    {
        $this->checkAdmin();

        // Templates are installed via SQL seed data
        // This action would re-run the relevant INSERT statements
        $this->getUser()->setFlash('notice', 'Library templates are installed automatically. Run the plugin installer to ensure all templates are present.');
        $this->redirect(['module' => 'forms', 'action' => 'library']);
    }

    /**
     * Check if admin.
     */
    protected function checkAdmin(): void
    {
        if (!$this->context->user->isAuthenticated() || !$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }
    }

    /**
     * Check if a template is installed.
     */
    protected function isTemplateInstalled(string $name): bool
    {
        return \Illuminate\Database\Capsule\Manager::table('ahg_form_template')
            ->where('name', $name)
            ->exists();
    }
}
