<?php

class extendedRightsExportAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $culture = $this->context->user->getCulture();

        // Load services with require_once
        require_once sfConfig::get('sf_root_dir').'/atom-framework/app/Services/Rights/ExtendedRightsExportService.php';
        require_once sfConfig::get('sf_root_dir').'/atom-framework/app/Services/Rights/ExtendedRightsService.php';

        $exportService = new \App\Services\Rights\ExtendedRightsExportService($culture);
        $rightsService = new \App\Services\Rights\ExtendedRightsService($culture);

        // Get records for dropdown
        $this->topLevelRecords = $rightsService->getTopLevelRecords(1000);

        $objectId = $request->getParameter('id');
        $format = $request->getParameter('format');

        // Handle export request
        if ($objectId || $format === 'csv') {
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
        }

        $this->stats = $exportService->getRightsStatistics();
    }

    protected function exportCsv()
    {
        $response = $this->getResponse();
        $response->setContentType('text/csv');
        $response->setHttpHeader('Content-Disposition', 'attachment; filename="rights_export_'.date('Y-m-d').'.csv"');

        $output = fopen('php://output', 'w');

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
    }
}
