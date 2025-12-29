<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * GRAP Asset Register Report Action.
 *
 * Full asset register with filtering and export.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class GrapReportAssetRegisterAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('admin/login');
        }

        $this->grapService = new GrapService();

        // Build filters
        $filters = [];
        if ($request->getParameter('asset_class')) {
            $filters['asset_class'] = $request->getParameter('asset_class');
        }
        if ($request->getParameter('gl_account_code')) {
            $filters['gl_account_code'] = $request->getParameter('gl_account_code');
        }
        if ($request->getParameter('recognition_status')) {
            $filters['recognition_status'] = $request->getParameter('recognition_status');
        }
        if ($request->getParameter('measurement_basis')) {
            $filters['measurement_basis'] = $request->getParameter('measurement_basis');
        }
        if ($request->getParameter('date_from')) {
            $filters['date_from'] = $request->getParameter('date_from');
        }
        if ($request->getParameter('date_to')) {
            $filters['date_to'] = $request->getParameter('date_to');
        }

        $this->filters = $filters;
        $this->assets = $this->getAssetRegisterFiltered($filters);

        // Calculate totals
        $this->totals = [
            'count' => count($this->assets),
            'carrying_amount' => array_sum(array_column($this->assets, 'current_carrying_amount')),
            'insurance_value' => array_sum(array_column($this->assets, 'insurance_value')),
            'initial_value' => array_sum(array_column($this->assets, 'initial_carrying_amount')),
            'cost_of_acquisition' => array_sum(array_column($this->assets, 'cost_of_acquisition')),
        ];

        // Handle CSV export
        if ('csv' === $request->getParameter('format')) {
            $csv = $this->grapService->exportToCsv($filters);
            $this->getResponse()->setContentType('text/csv');
            $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="grap_asset_register_'.date('Y-m-d').'.csv"');
            return $this->renderText($csv);
        }

        // Get filter options
        $this->assetClasses = GrapHeritageAssetForm::getAssetClassChoices();
        $this->recognitionStatuses = GrapHeritageAssetForm::getRecognitionStatusChoices();
        $this->measurementBases = GrapHeritageAssetForm::getMeasurementBasisChoices();
    }

    /**
     * Get asset register with extended filters.
     */
    protected function getAssetRegisterFiltered(array $filters): array
    {
        $query = DB::table('grap_heritage_asset as g')
            ->join('information_object as io', 'g.object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as io_i18n', function($join) {
                $join->on('io.id', '=', 'io_i18n.id')
                     ->where('io_i18n.culture', '=', 'en');
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->whereNull('g.derecognition_date')
            ->select(
                'g.*',
                'io.identifier as reference_code',
                'io_i18n.title',
                'slug.slug'
            );

        if (!empty($filters['asset_class'])) {
            $query->where('g.asset_class', $filters['asset_class']);
        }

        if (!empty($filters['gl_account_code'])) {
            $query->where('g.gl_account_code', 'LIKE', $filters['gl_account_code'].'%');
        }

        if (!empty($filters['recognition_status'])) {
            $query->where('g.recognition_status', $filters['recognition_status']);
        }

        if (!empty($filters['measurement_basis'])) {
            $query->where('g.measurement_basis', $filters['measurement_basis']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('g.acquisition_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('g.acquisition_date', '<=', $filters['date_to']);
        }

        $results = $query
            ->orderBy('g.gl_account_code')
            ->orderBy('g.asset_class')
            ->orderBy('io.identifier')
            ->get();

        return $results->map(fn ($row) => (array) $row)->toArray();
    }
}
