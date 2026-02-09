<?php
/**
 * Heritage Report Actions
 */
class heritageReportActions extends AhgActions
{
    public function executeIndex(sfWebRequest $request)
    {
        $service = new HeritageAssetService();
        $this->standards = $service->getAccountingStandards();
    }

    public function executeAssetRegister(sfWebRequest $request)
    {
        $service = new HeritageAssetService();
        $this->standards = $service->getAccountingStandards();
        $this->classes = $service->getAssetClasses();
        
        $filters = [
            'standard_id' => $request->getParameter('standard_id'),
            'class_id' => $request->getParameter('class_id'),
            'recognition_status' => $request->getParameter('status')
        ];
        
        $result = $service->browse(array_filter($filters), 1000, 0);
        $this->assets = $result['items'];
        $this->filters = $filters;
    }

    public function executeValuation(sfWebRequest $request)
    {
        $service = new HeritageAssetService();
        $this->standards = $service->getAccountingStandards();
        
        // Get assets with recent valuations
        $this->assets = \Illuminate\Database\Capsule\Manager::table('heritage_asset as ha')
            ->leftJoin('heritage_asset_class as hc', 'ha.asset_class_id', '=', 'hc.id')
            ->leftJoin('information_object as io', 'ha.object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->select(['ha.*', 'hc.name as class_name', 'io.identifier', 'ioi.title'])
            ->whereNotNull('ha.last_valuation_date')
            ->orderBy('ha.last_valuation_date', 'desc')
            ->limit(100)
            ->get()
            ->toArray();
    }

    public function executeMovement(sfWebRequest $request)
    {
        $from = $request->getParameter('from', date('Y-m-01'));
        $to = $request->getParameter('to', date('Y-m-d'));
        
        $this->movements = \Illuminate\Database\Capsule\Manager::table('heritage_movement_register as m')
            ->join('heritage_asset as ha', 'm.heritage_asset_id', '=', 'ha.id')
            ->leftJoin('information_object as io', 'ha.object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->select(['m.*', 'io.identifier', 'ioi.title'])
            ->whereBetween('m.movement_date', [$from, $to])
            ->orderBy('m.movement_date', 'desc')
            ->get()
            ->toArray();
        
        $this->from = $from;
        $this->to = $to;
    }
}
