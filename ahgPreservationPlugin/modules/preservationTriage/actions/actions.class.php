<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Preservation triage dashboard: a ranked, collection-wide view of where
 * conservation and digital-preservation effort is most needed. Read-only.
 *
 * Scoring and data gathering live in PreservationTriageService so the check in
 * testing/preservation-triage-check.php exercises the same code.
 */
class preservationTriageActions extends AhgController
{
    /** Rows rendered on the page; the CSV export carries the full list. */
    private const PAGE_ROWS = 100;

    public function boot(): void
    {
        require_once $this->config('sf_plugins_dir').'/ahgPreservationPlugin/lib/Services/PreservationTriageService.php';
    }

    private function triage($request): array
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $years = (int) $request->getParameter('years');
        $service = new PreservationTriageService($years > 0 ? $years : null, null, $this->culture());

        return $service->build([
            'repository_id' => (int) $request->getParameter('repository_id'),
            'collection_id' => (int) $request->getParameter('collection_id'),
        ]);
    }

    public function executeIndex($request)
    {
        $result = $this->triage($request);
        $this->totalRows = count($result['rows']);
        $result['rows'] = array_slice($result['rows'], 0, self::PAGE_ROWS);
        $result['forecast'] = array_slice($result['forecast'], 0, self::PAGE_ROWS);
        $this->triage = $result;
        $this->pageRows = self::PAGE_ROWS;
        $this->filters = [
            'repository_id' => (int) $request->getParameter('repository_id'),
            'collection_id' => (int) $request->getParameter('collection_id'),
            'years' => $result['ageYears'],
        ];
    }

    public function executeExport($request)
    {
        $result = $this->triage($request);
        $controller = $this->getController();
        $csv = PreservationTriageService::toCsv($result['rows'], function ($slug) use ($controller) {
            return $controller->genUrl(['module' => 'informationobject', 'slug' => $slug], true);
        });

        $this->getResponse()->setContentType('text/csv; charset=utf-8');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="preservation-triage-'.date('Y-m-d').'.csv"');

        return $this->renderText("\xEF\xBB\xBF".$csv);
    }
}
