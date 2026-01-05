<?php
/**
 * GRAP 103 Compliance Actions
 */
class grapComplianceActions extends sfActions
{
    /**
     * GRAP Dashboard
     */
    public function executeDashboard(sfWebRequest $request)
    {
        $service = new GrapComplianceService();
        $heritageService = new HeritageAssetService();
        
        $standardId = $service->getStandardId();
        $this->stats = $heritageService->getDashboardStats(null, $standardId);
        $this->complianceSummary = $service->getComplianceSummary();
        
        // Get GRAP assets
        $result = $heritageService->browse(['standard_id' => $standardId], 10, 0);
        $this->recentAssets = $result['items'];
    }

    /**
     * Run compliance check on asset
     */
    public function executeCheck(sfWebRequest $request)
    {
        $service = new GrapComplianceService();
        $heritageService = new HeritageAssetService();
        
        $this->asset = $heritageService->getAsset($request->getParameter('id'));
        if (!$this->asset) {
            $this->forward404('Asset not found');
        }
        
        $this->complianceResult = $service->checkCompliance($this->asset->id);
    }

    /**
     * Batch compliance check
     */
    public function executeBatchCheck(sfWebRequest $request)
    {
        $service = new GrapComplianceService();
        $heritageService = new HeritageAssetService();
        
        $standardId = $service->getStandardId();
        $result = $heritageService->browse(['standard_id' => $standardId], 1000, 0);
        
        $this->results = [];
        foreach ($result['items'] as $asset) {
            $this->results[] = [
                'asset' => $asset,
                'compliance' => $service->checkCompliance($asset->id)
            ];
        }
    }

    /**
     * National Treasury Report format
     */
    public function executeNationalTreasuryReport(sfWebRequest $request)
    {
        $service = new GrapComplianceService();
        $heritageService = new HeritageAssetService();
        
        $standardId = $service->getStandardId();
        $this->financialYear = $request->getParameter('fy', date('Y'));
        
        // Get all GRAP assets
        $result = $heritageService->browse(['standard_id' => $standardId], 1000, 0);
        $this->assets = $result['items'];
        
        // Summary by class
        $this->byClass = \Illuminate\Database\Capsule\Manager::table('heritage_asset as ha')
            ->leftJoin('heritage_asset_class as hc', 'ha.asset_class_id', '=', 'hc.id')
            ->where('ha.accounting_standard_id', $standardId)
            ->select([
                'hc.name as class_name',
                \Illuminate\Database\Capsule\Manager::raw('COUNT(*) as count'),
                \Illuminate\Database\Capsule\Manager::raw('SUM(ha.current_carrying_amount) as total_value'),
                \Illuminate\Database\Capsule\Manager::raw('SUM(ha.impairment_loss) as total_impairment'),
                \Illuminate\Database\Capsule\Manager::raw('SUM(ha.revaluation_surplus) as total_surplus')
            ])
            ->groupBy('hc.id', 'hc.name')
            ->get()
            ->toArray();
    }
}
