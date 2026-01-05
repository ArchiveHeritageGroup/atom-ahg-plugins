<?php
class heritageApiActions extends sfActions
{
    /**
     * Autocomplete for information objects
     */
    public function executeAutocomplete(sfWebRequest $request)
    {
        $term = $request->getParameter('term', '');
        $results = [];
        
        if (strlen($term) >= 2) {
            $results = \Illuminate\Database\Capsule\Manager::table('information_object as io')
                ->leftJoin('information_object_i18n as ioi', function($join) {
                    $join->on('io.id', '=', 'ioi.id')
                         ->where('ioi.culture', '=', 'en');
                })
                ->where(function($q) use ($term) {
                    $q->where('ioi.title', 'like', '%' . $term . '%')
                      ->orWhere('io.identifier', 'like', '%' . $term . '%');
                })
                ->whereNotNull('io.identifier')
                ->select('io.id', 'io.identifier', 'ioi.title')
                ->limit(15)
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'label' => ($item->identifier ? $item->identifier . ' - ' : '') . ($item->title ?: 'Untitled'),
                        'value' => $item->id
                    ];
                })
                ->toArray();
        }
        
        $this->getResponse()->setContentType('application/json');
        return $this->renderText(json_encode($results));
    }

    /**
     * Get asset summary
     */
    public function executeSummary(sfWebRequest $request)
    {
        $service = new HeritageAssetService();
        $stats = $service->getDashboardStats();
        
        $this->getResponse()->setContentType('application/json');
        return $this->renderText(json_encode($stats));
    }

    /**
     * Get asset details
     */
    public function executeAsset(sfWebRequest $request)
    {
        $service = new HeritageAssetService();
        $asset = $service->getAsset($request->getParameter('id'));
        
        if (!$asset) {
            $this->getResponse()->setStatusCode(404);
            return $this->renderText(json_encode(['error' => 'Asset not found']));
        }
        
        $this->getResponse()->setContentType('application/json');
        return $this->renderText(json_encode($asset));
    }
}
