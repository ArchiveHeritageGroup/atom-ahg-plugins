<?php

use AtomFramework\Http\Controllers\AhgController;

class customFieldActions extends AhgController
{
    /**
     * Boot — handle AJAX actions.
     */
    public function boot(): void
    {
        $ajaxActions = ['saveValues', 'getValues'];
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
     * Load the service.
     */
    protected function getService(): \AhgCustomFieldsPlugin\Service\CustomFieldService
    {
        require_once sfConfig::get('sf_plugins_dir') . '/ahgCustomFieldsPlugin/lib/Service/CustomFieldService.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgCustomFieldsPlugin/lib/Repository/FieldDefinitionRepository.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgCustomFieldsPlugin/lib/Repository/FieldValueRepository.php';

        return new \AhgCustomFieldsPlugin\Service\CustomFieldService();
    }

    /**
     * Load the render service.
     */
    protected function getRenderService(): \AhgCustomFieldsPlugin\Service\CustomFieldRenderService
    {
        $this->getService(); // ensure dependencies loaded
        require_once sfConfig::get('sf_plugins_dir') . '/ahgCustomFieldsPlugin/lib/Service/CustomFieldRenderService.php';

        return new \AhgCustomFieldsPlugin\Service\CustomFieldRenderService();
    }

    // ----------------------------------------------------------------
    // Save values — POST from entity edit forms
    // ----------------------------------------------------------------

    public function executeSaveValues($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            return $this->jsonResponse(['success' => false, 'error' => 'Not authenticated']);
        }

        if (!$request->isMethod('post')) {
            return $this->jsonResponse(['success' => false, 'error' => 'POST required']);
        }

        $entityType = trim($request->getParameter('entity_type', ''));
        $objectId = (int) $request->getParameter('object_id', 0);
        $fieldValues = $request->getParameter('cf', []);

        if (empty($entityType) || $objectId <= 0) {
            return $this->jsonResponse(['success' => false, 'error' => 'Missing entity_type or object_id.']);
        }

        if (!is_array($fieldValues)) {
            $fieldValues = [];
        }

        $service = $this->getService();

        // Validate entity type
        $validEntityTypes = array_keys($service->getEntityTypes());
        if (!in_array($entityType, $validEntityTypes)) {
            return $this->jsonResponse(['success' => false, 'error' => 'Invalid entity type.']);
        }

        // Validate values against definitions
        $definitions = $service->getDefinitionsForEntity($entityType);
        $errors = [];

        foreach ($definitions as $def) {
            $key = $def->field_key;
            $val = $fieldValues[$key] ?? null;

            if ($def->is_repeatable && is_array($val)) {
                foreach ($val as $i => $v) {
                    $result = $service->validateValue($def, $v);
                    if ($result !== true) {
                        $errors[] = $result . ' (entry ' . ($i + 1) . ')';
                    }
                }
            } else {
                $result = $service->validateValue($def, $val);
                if ($result !== true) {
                    $errors[] = $result;
                }
            }
        }

        if (!empty($errors)) {
            return $this->jsonResponse(['success' => false, 'errors' => $errors]);
        }

        try {
            $service->saveValues($objectId, $entityType, $fieldValues);

            return $this->jsonResponse(['success' => true, 'message' => 'Custom fields saved.']);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => 'Save failed: ' . $e->getMessage()]);
        }
    }

    // ----------------------------------------------------------------
    // Get values — AJAX GET for an entity
    // ----------------------------------------------------------------

    public function executeGetValues($request)
    {
        $entityType = trim($request->getParameter('entityType', ''));
        $objectId = (int) $request->getParameter('objectId', 0);

        if (empty($entityType) || $objectId <= 0) {
            return $this->jsonResponse(['success' => false, 'error' => 'Missing entityType or objectId.']);
        }

        try {
            $service = $this->getService();
            $values = $service->getValuesForObject($objectId, $entityType);

            return $this->jsonResponse(['success' => true, 'values' => $values]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
