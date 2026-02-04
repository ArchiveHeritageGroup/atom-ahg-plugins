<?php

/**
 * Export a mapping profile to JSON file.
 */
class exportMappingAction extends sfAction
{
    public function execute($request)
    {
        // Check user authentication
        if (!$this->context->user->isAuthenticated()) {
            $this->forward('admin', 'secure');
        }

        $mappingId = $request->getParameter('id');

        if (!$mappingId) {
            $this->getResponse()->setStatusCode(400);

            return $this->renderText('Missing mapping ID');
        }

        // Load mapping from database
        $mapping = \Illuminate\Database\Capsule\Manager::table('atom_data_mapping')
            ->where('id', $mappingId)
            ->first()
        ;

        if (!$mapping) {
            $this->getResponse()->setStatusCode(404);

            return $this->renderText('Mapping not found');
        }

        // Prepare export data
        $exportData = [
            'name' => $mapping->name,
            'description' => $mapping->description,
            'target_type' => $mapping->target_type,
            'source_template' => $mapping->source_template,
            'sector_code' => $mapping->sector_code ?? null,
            'field_mappings' => json_decode($mapping->field_mappings, true),
            'exported_at' => date('c'),
            'export_version' => '1.0',
        ];

        // Generate filename
        $filename = preg_replace('/[^a-z0-9_-]/i', '_', $mapping->name).'_mapping.json';

        // Send as download
        $this->getResponse()->setContentType('application/json');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="'.$filename.'"');
        $this->getResponse()->setHttpHeader('Cache-Control', 'no-cache, must-revalidate');

        return $this->renderText(json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
