<?php

/**
 * Validation-only action for CSV imports.
 *
 * Validates a CSV file against sector-specific rules without importing any data.
 * Returns a detailed validation report with row/column error tracking.
 */
class validateAction extends sfAction
{
    public function execute($request)
    {
        // Check user authentication
        if (!$this->context->user->isAuthenticated()) {
            $this->forward('admin', 'secure');
        }

        // Initialize variables
        $this->report = null;
        $this->error = null;
        $this->filename = '';
        $this->sector = '';
        $this->mappingId = null;

        // Only process on POST
        if (!$request->isMethod('post')) {
            return sfView::SUCCESS;
        }

        try {
            // Get parameters
            $this->filename = $request->getParameter('filename');
            $this->sector = $request->getParameter('sector', 'archive');
            $this->mappingId = $request->getParameter('mapping_id');

            // Validate file exists
            $uploadPath = sfConfig::get('sf_upload_dir').'/dataMigration/'.$this->filename;
            if (!file_exists($uploadPath)) {
                throw new RuntimeException('File not found: '.$this->filename);
            }

            // Load mapping if specified
            $mapping = [];
            if ($this->mappingId) {
                $mapping = $this->loadMapping($this->mappingId);
            }

            // Get validation options
            $options = [
                'schema' => $request->getParameter('validate_schema', true),
                'referential' => $request->getParameter('validate_referential', true),
                'duplicates' => $request->getParameter('validate_duplicates', true),
                'sector' => $request->getParameter('validate_sector', true),
                'checkDatabase' => $request->getParameter('check_database', true),
                'duplicateStrategy' => $request->getParameter('duplicate_strategy', 'identifier'),
            ];

            // Create validation service
            $validationService = new \ahgDataMigrationPlugin\Services\ValidationService($this->sector, $options);

            // Run validation
            $this->report = $validationService->validateOnly($uploadPath, $mapping);
        } catch (Exception $e) {
            $this->error = $e->getMessage();
        }

        // Handle AJAX request
        if ($request->isXmlHttpRequest()) {
            $this->getResponse()->setContentType('application/json');

            $response = [
                'success' => null === $this->error,
                'error' => $this->error,
            ];

            if (null !== $this->report) {
                $response['report'] = $this->report->toArray();
            }

            return $this->renderText(json_encode($response));
        }

        return sfView::SUCCESS;
    }

    /**
     * Load mapping from database.
     *
     * @return array<string, string>
     */
    protected function loadMapping(int $mappingId): array
    {
        $mapping = \Illuminate\Database\Capsule\Manager::table('atom_data_mapping')
            ->where('id', $mappingId)
            ->first()
        ;

        if (!$mapping) {
            return [];
        }

        $fieldMappings = json_decode($mapping->field_mappings, true);
        if (!is_array($fieldMappings)) {
            return [];
        }

        // Convert to source => target format
        $result = [];
        foreach ($fieldMappings as $field) {
            if (isset($field['source']) && isset($field['target'])) {
                $result[$field['source']] = $field['target'];
            }
        }

        return $result;
    }
}
