<?php

use AtomFramework\Http\Controllers\AhgController;

class extendedRightsExportAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }
        $culture = $this->context->user->getCulture();
        
        // Load services with require_once
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgExtendedRightsPlugin/lib/Services/ExtendedRightsExportService.php';
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgExtendedRightsPlugin/lib/Services/ExtendedRightsService.php';
        
        $exportService = new \ahgExtendedRightsPlugin\Services\ExtendedRightsExportService($culture);
        $rightsService = new \ahgExtendedRightsPlugin\Services\ExtendedRightsService($culture);
        
        // Get records for dropdown
        $this->topLevelRecords = $rightsService->getTopLevelRecords(1000);
        
        $objectId = $request->getParameter('id');
        $format = $request->getParameter('format');
        
        // Handle export request
        if ($objectId || $format === 'csv' || $format === 'json-ld') {
            if ($objectId) {
                $this->data = $exportService->exportObjectRights((int)$objectId);
            } else {
                $this->data = $exportService->exportObjectRights();
            }
            
            // Handle CSV export
            if ($format === 'csv') {
                $this->exportCsv();
                return sfView::NONE;
            }
            
            // Handle JSON-LD export
            if ($format === 'json-ld') {
                $this->exportJsonLd($objectId);
                return sfView::NONE;
            }
        }
        
        $this->stats = $exportService->getRightsStatistics();
    }
    
    protected function exportCsv()
    {
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        $filename = 'rights_export_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // BOM for Excel UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Header row
        fputcsv($output, ['ID', 'Slug', 'Identifier', 'Title', 'Rights Statement', 'CC License']);
        
        // Data rows
        foreach ($this->data as $row) {
            fputcsv($output, [
                $row->id ?? '',
                $row->slug ?? '',
                $row->identifier ?? '',
                $row->title ?? '',
                $row->rights_statement_name ?? $row->rights_statement_code ?? '',
                $row->cc_license_name ?? $row->cc_license_code ?? '',
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    protected function exportJsonLd($objectId = null)
    {
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        $filename = $objectId ? "rights_{$objectId}.jsonld" : "rights_export_" . date('Y-m-d') . ".jsonld";
        
        header('Content-Type: application/ld+json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $siteUrl = sfConfig::get('app_siteBaseUrl', '');
        
        $jsonLd = [
            '@context' => [
                '@vocab' => 'http://schema.org/',
                'dcterms' => 'http://purl.org/dc/terms/',
                'edm' => 'http://www.europeana.eu/schemas/edm/',
                'cc' => 'http://creativecommons.org/ns#',
                'rs' => 'http://rightsstatements.org/vocab/',
            ],
            '@graph' => []
        ];
        
        foreach ($this->data as $row) {
            $item = [
                '@type' => 'ArchiveComponent',
                '@id' => rtrim($siteUrl, '/') . '/' . ($row->slug ?? ''),
                'identifier' => $row->identifier ?? null,
                'name' => $row->title ?? null,
            ];
            
            // Add rights statement
            if (!empty($row->rights_statement_uri)) {
                $item['dcterms:rights'] = [
                    '@id' => $row->rights_statement_uri,
                    '@type' => 'dcterms:RightsStatement',
                    'name' => $row->rights_statement_name ?? $row->rights_statement_code ?? null,
                ];
            }
            
            // Add Creative Commons license
            if (!empty($row->cc_license_uri)) {
                $item['cc:license'] = [
                    '@id' => $row->cc_license_uri,
                    '@type' => 'cc:License',
                    'name' => $row->cc_license_name ?? $row->cc_license_code ?? null,
                ];
            }
            
            $jsonLd['@graph'][] = $item;
        }
        
        echo json_encode($jsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
