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

        // SECURITY: require update authorization on the target object — previously
        // any authenticated user could write custom-field values on ANY object
        // (incl. records they cannot edit) by supplying an arbitrary object_id.
        $aclObj = \QubitObject::getById($objectId);
        if (!$aclObj || !\AtomExtensions\Services\AclService::check($aclObj, 'update')) {
            return $this->jsonResponse(['success' => false, 'error' => 'Not authorized to edit this record.']);
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

    /**
     * Faceted search over custom field values.
     *
     * SQL-backed rather than index-backed: AtoM here runs arOpenSearchPlugin,
     * whose IO mapping is `dynamic: strict` and which dispatches no indexing
     * event, so putting these fields in the search index would mean forking two
     * base AtoM files. See CustomFieldSearchService for the reasoning.
     */
    public function executeSearch($request)
    {
        $pluginDir = sfConfig::get('sf_plugins_dir') . '/ahgCustomFieldsPlugin';
        require_once $pluginDir . '/lib/Service/CustomFieldSearchService.php';

        $service = new \AhgCustomFieldsPlugin\Service\CustomFieldSearchService();

        $this->fields = $service->getFilterableFields();
        $this->service = $service;

        // Taxonomies whose terms are attached to records, offered as filters.
        $this->termTaxonomies = \Illuminate\Database\Capsule\Manager::table('taxonomy_i18n as ti')
            ->where('ti.culture', 'en')
            ->whereExists(function ($q) {
                $q->select(\Illuminate\Database\Capsule\Manager::raw(1))
                    ->from('term as t')
                    ->join('object_term_relation as otr', 'otr.term_id', '=', 't.id')
                    ->whereColumn('t.taxonomy_id', 'ti.id');
            })
            ->orderBy('ti.name')
            ->select('ti.id', 'ti.name')
            ->get();

        $this->filters = (array) $request->getParameter('cf', []);
        $this->termIds = array_filter((array) $request->getParameter('term', []));
        $this->keywords = trim((string) $request->getParameter('q', ''));
        $this->submitted = $request->hasParameter('cf') || $request->hasParameter('term') || '' !== $this->keywords;

        $this->results = [];
        $this->total = 0;

        if ($this->submitted) {
            $found = $service->search($this->filters, $this->termIds, $this->keywords);
            $this->results = $found['results'];
            $this->total = $found['total'];
        }
    }

}
