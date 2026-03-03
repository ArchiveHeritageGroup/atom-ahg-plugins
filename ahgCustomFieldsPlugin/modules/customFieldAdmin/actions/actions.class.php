<?php

use AtomFramework\Http\Controllers\AhgController;

class customFieldAdminActions extends AhgController
{
    /**
     * Boot — handle AJAX actions.
     */
    public function boot(): void
    {
        $ajaxActions = ['save', 'delete', 'reorder'];
        if (in_array($this->getActionName(), $ajaxActions)) {
            ob_start();
        }
    }

    /**
     * JSON response helper.
     */
    protected function jsonResponse(array $data)
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    /**
     * Load the service (lazy — Symfony 1.x doesn't autoload namespaced plugin classes).
     */
    protected function getService(): \AhgCustomFieldsPlugin\Service\CustomFieldService
    {
        require_once sfConfig::get('sf_plugins_dir') . '/ahgCustomFieldsPlugin/lib/Service/CustomFieldService.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgCustomFieldsPlugin/lib/Repository/FieldDefinitionRepository.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgCustomFieldsPlugin/lib/Repository/FieldValueRepository.php';

        return new \AhgCustomFieldsPlugin\Service\CustomFieldService();
    }

    // ----------------------------------------------------------------
    // Index — list all field definitions
    // ----------------------------------------------------------------

    public function executeIndex($request)
    {
        $this->checkAdmin();

        $service = $this->getService();

        require_once sfConfig::get('sf_plugins_dir') . '/ahgCustomFieldsPlugin/lib/Repository/FieldDefinitionRepository.php';
        $repo = new \AhgCustomFieldsPlugin\Repository\FieldDefinitionRepository();

        $this->definitionsByEntity = $repo->getAllGroupedByEntity();
        $this->entityTypes = $service->getEntityTypes();
        $this->fieldTypes = $service->getFieldTypes();

        // Stats
        $totalDefs = 0;
        $activeDefs = 0;
        foreach ($this->definitionsByEntity as $entityDefs) {
            foreach ($entityDefs as $def) {
                $totalDefs++;
                if ($def->is_active) {
                    $activeDefs++;
                }
            }
        }
        $this->totalDefs = $totalDefs;
        $this->activeDefs = $activeDefs;
    }

    // ----------------------------------------------------------------
    // Edit — create or edit a field definition
    // ----------------------------------------------------------------

    public function executeEdit($request)
    {
        $this->checkAdmin();

        $service = $this->getService();

        $id = (int) $request->getParameter('id');

        if ($id > 0) {
            $this->definition = $service->getDefinition($id);
            $this->forward404Unless($this->definition);
        } else {
            $this->definition = null;
        }

        $this->entityTypes = $service->getEntityTypes();
        $this->fieldTypes = $service->getFieldTypes();
        $this->taxonomies = $service->getDropdownTaxonomies();

        require_once sfConfig::get('sf_plugins_dir') . '/ahgCustomFieldsPlugin/lib/Repository/FieldDefinitionRepository.php';
        $repo = new \AhgCustomFieldsPlugin\Repository\FieldDefinitionRepository();
        $this->fieldGroups = $repo->getFieldGroups();
    }

    // ----------------------------------------------------------------
    // Save — create or update
    // ----------------------------------------------------------------

    public function executeSave($request)
    {
        $this->checkAdmin();

        if (!$request->isMethod('post')) {
            return $this->jsonResponse(['success' => false, 'error' => 'POST required']);
        }

        $service = $this->getService();

        $id = (int) $request->getParameter('id');
        $fieldLabel = trim($request->getParameter('field_label', ''));
        $fieldKey = trim($request->getParameter('field_key', ''));
        $fieldType = trim($request->getParameter('field_type', 'text'));
        $entityType = trim($request->getParameter('entity_type', ''));

        // Validation
        if (empty($fieldLabel)) {
            return $this->jsonResponse(['success' => false, 'error' => 'Field label is required.']);
        }
        if (empty($entityType)) {
            return $this->jsonResponse(['success' => false, 'error' => 'Entity type is required.']);
        }

        $validEntityTypes = array_keys($service->getEntityTypes());
        if (!in_array($entityType, $validEntityTypes)) {
            return $this->jsonResponse(['success' => false, 'error' => 'Invalid entity type.']);
        }

        $validFieldTypes = array_keys($service->getFieldTypes());
        if (!in_array($fieldType, $validFieldTypes)) {
            return $this->jsonResponse(['success' => false, 'error' => 'Invalid field type.']);
        }

        // Auto-generate key if creating
        if ($id === 0 && empty($fieldKey)) {
            $fieldKey = $service->generateFieldKey($fieldLabel);
        }

        if (empty($fieldKey)) {
            return $this->jsonResponse(['success' => false, 'error' => 'Field key is required.']);
        }

        // Check uniqueness
        require_once sfConfig::get('sf_plugins_dir') . '/ahgCustomFieldsPlugin/lib/Repository/FieldDefinitionRepository.php';
        $repo = new \AhgCustomFieldsPlugin\Repository\FieldDefinitionRepository();

        if (!$repo->isKeyUnique($fieldKey, $entityType, $id > 0 ? $id : null)) {
            return $this->jsonResponse(['success' => false, 'error' => 'Field key "' . $fieldKey . '" already exists for this entity type.']);
        }

        $data = [
            'field_key' => $fieldKey,
            'field_label' => $fieldLabel,
            'field_type' => $fieldType,
            'entity_type' => $entityType,
            'field_group' => trim($request->getParameter('field_group', '')) ?: null,
            'dropdown_taxonomy' => ($fieldType === 'dropdown') ? trim($request->getParameter('dropdown_taxonomy', '')) : null,
            'is_required' => $request->getParameter('is_required') ? 1 : 0,
            'is_searchable' => $request->getParameter('is_searchable') ? 1 : 0,
            'is_visible_public' => $request->getParameter('is_visible_public') ? 1 : 0,
            'is_visible_edit' => $request->getParameter('is_visible_edit', 1) ? 1 : 0,
            'is_repeatable' => $request->getParameter('is_repeatable') ? 1 : 0,
            'default_value' => trim($request->getParameter('default_value', '')) ?: null,
            'help_text' => trim($request->getParameter('help_text', '')) ?: null,
            'validation_rule' => trim($request->getParameter('validation_rule', '')) ?: null,
            'sort_order' => (int) $request->getParameter('sort_order', 0),
            'is_active' => $request->getParameter('is_active', 1) ? 1 : 0,
        ];

        try {
            if ($id > 0) {
                $service->updateDefinition($id, $data);
                return $this->jsonResponse(['success' => true, 'message' => 'Field updated successfully.', 'id' => $id]);
            } else {
                $newId = $service->createDefinition($data);
                return $this->jsonResponse(['success' => true, 'message' => 'Field created successfully.', 'id' => $newId]);
            }
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => 'Save failed: ' . $e->getMessage()]);
        }
    }

    // ----------------------------------------------------------------
    // Delete — soft or hard delete
    // ----------------------------------------------------------------

    public function executeDelete($request)
    {
        $this->checkAdmin();

        if (!$request->isMethod('post')) {
            return $this->jsonResponse(['success' => false, 'error' => 'POST required']);
        }

        $service = $this->getService();
        $id = (int) $request->getParameter('id');

        if ($id <= 0) {
            return $this->jsonResponse(['success' => false, 'error' => 'Invalid ID.']);
        }

        $def = $service->getDefinition($id);
        if (!$def) {
            return $this->jsonResponse(['success' => false, 'error' => 'Field not found.']);
        }

        $hardDelete = (bool) $request->getParameter('hard_delete', false);

        try {
            if ($hardDelete) {
                $deleted = $service->hardDeleteDefinition($id);
                if (!$deleted) {
                    return $this->jsonResponse([
                        'success' => false,
                        'error' => 'Cannot hard-delete: field has existing values. Use soft-delete instead.',
                    ]);
                }

                return $this->jsonResponse(['success' => true, 'message' => 'Field permanently deleted.']);
            }

            $service->deleteDefinition($id);

            return $this->jsonResponse(['success' => true, 'message' => 'Field deactivated.']);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => 'Delete failed: ' . $e->getMessage()]);
        }
    }

    // ----------------------------------------------------------------
    // Reorder — AJAX drag-drop
    // ----------------------------------------------------------------

    public function executeReorder($request)
    {
        $this->checkAdmin();

        if (!$request->isMethod('post')) {
            return $this->jsonResponse(['success' => false, 'error' => 'POST required']);
        }

        $orderedIds = $request->getParameter('ids');
        if (!is_array($orderedIds) || empty($orderedIds)) {
            return $this->jsonResponse(['success' => false, 'error' => 'No IDs provided.']);
        }

        try {
            $service = $this->getService();
            $service->reorderDefinitions($orderedIds);

            return $this->jsonResponse(['success' => true, 'message' => 'Order updated.']);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => 'Reorder failed: ' . $e->getMessage()]);
        }
    }

    // ----------------------------------------------------------------
    // Export — download field definitions as JSON
    // ----------------------------------------------------------------

    public function executeExport($request)
    {
        $this->checkAdmin();

        $service = $this->getService();
        $entityType = trim($request->getParameter('entity_type', ''));

        if (empty($entityType)) {
            // Export all
            $allDefs = [];
            foreach (array_keys($service->getEntityTypes()) as $et) {
                $defs = $service->exportDefinitions($et);
                if (!empty($defs)) {
                    $allDefs = array_merge($allDefs, $defs);
                }
            }
            $exportData = $allDefs;
        } else {
            $exportData = $service->exportDefinitions($entityType);
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="custom_fields_export.json"');
        echo json_encode($exportData, JSON_PRETTY_PRINT);
        exit;
    }

    // ----------------------------------------------------------------
    // Import — upload JSON field definitions
    // ----------------------------------------------------------------

    public function executeImport($request)
    {
        $this->checkAdmin();

        if (!$request->isMethod('post')) {
            return $this->jsonResponse(['success' => false, 'error' => 'POST required']);
        }

        $jsonInput = $request->getParameter('import_json', '');
        if (empty($jsonInput)) {
            return $this->jsonResponse(['success' => false, 'error' => 'No JSON data provided.']);
        }

        $definitions = json_decode($jsonInput, true);
        if (!is_array($definitions)) {
            return $this->jsonResponse(['success' => false, 'error' => 'Invalid JSON format.']);
        }

        try {
            $service = $this->getService();
            $count = $service->importDefinitions($definitions);

            return $this->jsonResponse(['success' => true, 'message' => $count . ' field(s) imported.']);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => 'Import failed: ' . $e->getMessage()]);
        }
    }
}
