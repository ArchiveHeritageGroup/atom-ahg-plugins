<?php
/**
 * GRAP Export Action
 * 
 * Generates exports for National Treasury and board reporting.
 * 
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgGrapPlugin
 */

class grapExportAction extends sfAction
{
    public function execute($request)
    {
        $this->exportService = new ahgGrapExportService();

        $exportType = $request->getParameter('type');
        $repositoryId = $request->getParameter('repository_id');
        $financialYear = $request->getParameter('financial_year');

        // Handle download
        if ($request->getParameter('download')) {
            return $this->handleDownload($exportType, $repositoryId, $financialYear, $request);
        }

        // Display export form
        $this->repositories = $this->getRepositories();
        $this->exportTypes = [
            'asset_register' => [
                'name' => 'Heritage Asset Register (NT Format)',
                'description' => 'National Treasury compliant asset register for annual reporting',
                'format' => 'csv'
            ],
            'disclosure_note' => [
                'name' => 'GRAP 103 Disclosure Note',
                'description' => 'Annual Financial Statement disclosure note format',
                'format' => 'csv'
            ],
            'multi_year_trend' => [
                'name' => 'Multi-Year Trend Analysis',
                'description' => 'Heritage asset trends over multiple financial years',
                'format' => 'csv'
            ],
            'board_pack' => [
                'name' => 'Board Pack Summary',
                'description' => 'Executive summary for board reporting',
                'format' => 'pdf'
            ],
            'impairment_schedule' => [
                'name' => 'Impairment Schedule',
                'description' => 'Detailed impairment losses for the financial year',
                'format' => 'csv'
            ],
            'derecognition_schedule' => [
                'name' => 'De-recognition Schedule',
                'description' => 'Assets disposed/de-recognised during the financial year',
                'format' => 'csv'
            ],
            'revaluation_schedule' => [
                'name' => 'Revaluation Schedule',
                'description' => 'Revaluations performed during the financial year',
                'format' => 'csv'
            ]
        ];

        // Financial year options (last 5 years)
        $this->financialYears = [];
        $currentYear = (int)date('Y');
        for ($i = 0; $i < 5; $i++) {
            $year = $currentYear - $i;
            $this->financialYears[$year] = $year . '/' . ($year + 1) . ' (Ending ' . $year . '-03-31)';
        }
    }

    protected function handleDownload($exportType, $repositoryId, $financialYear, $request)
    {
        $result = null;

        switch ($exportType) {
            case 'asset_register':
                $result = $this->exportService->exportAssetRegisterNT($repositoryId, $financialYear);
                break;

            case 'disclosure_note':
                $fyEnd = $financialYear ? $financialYear . '-03-31' : null;
                $result = $this->exportService->exportDisclosureNote($repositoryId, $fyEnd);
                break;

            case 'multi_year_trend':
                $years = (int)$request->getParameter('years', 5);
                $result = $this->exportService->exportMultiYearTrend($repositoryId, $years);
                break;

            case 'board_pack':
                $result = $this->exportService->exportBoardPack($repositoryId);
                break;

            case 'impairment_schedule':
                $fyEnd = $financialYear ? $financialYear . '-03-31' : null;
                $result = $this->exportService->exportImpairmentSchedule($repositoryId, $fyEnd);
                break;

            case 'derecognition_schedule':
                $fyEnd = $financialYear ? $financialYear . '-03-31' : null;
                $result = $this->exportService->exportDerecognitionSchedule($repositoryId, $fyEnd);
                break;

            case 'revaluation_schedule':
                $fyEnd = $financialYear ? $financialYear . '-03-31' : null;
                $result = $this->exportService->exportRevaluationSchedule($repositoryId, $fyEnd);
                break;

            default:
                $this->getUser()->setFlash('error', 'Unknown export type');
                $this->redirect(['module' => 'grap', 'action' => 'export']);
                return;
        }

        if ($result) {
            $response = $this->getResponse();
            $response->setContentType($result['mime_type']);
            $response->setHttpHeader('Content-Disposition', 'attachment; filename="' . $result['filename'] . '"');
            $response->setHttpHeader('Content-Length', strlen($result['content']));
            $response->setContent($result['content']);

            return sfView::NONE;
        }

        $this->getUser()->setFlash('error', 'Export generation failed');
        $this->redirect(['module' => 'grap', 'action' => 'export']);
    }

    protected function getRepositories()
    {
        $repositories = [];
        
        // Use Laravel DB
        $rows = \Illuminate\Database\Capsule\Manager::table('repository')
            ->join('actor_i18n', 'repository.id', '=', 'actor_i18n.id')
            ->where('actor_i18n.culture', '=', 'en')
            ->select('repository.id', 'actor_i18n.authorized_form_of_name')
            ->orderBy('actor_i18n.authorized_form_of_name')
            ->get();
        
        foreach ($rows as $row) {
            $repositories[$row->id] = $row->authorized_form_of_name;
        }
        
        return $repositories;
    }
}
