<?php

use AtomFramework\Http\Controllers\AhgController;
class heritageApiActions extends AhgController
{
    /**
     * Autocomplete for information objects
     */
    public function executeAutocomplete($request)
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
                        'label' => ($item->identifier ? $item->identifier . ' - ' : '') . ($item->title ?: 'Untitled'), 'title' => $item->title ?: 'Untitled',
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
    public function executeSummary($request)
    {
        $service = new HeritageAssetService();
        $stats = $service->getDashboardStats();
        
        $this->getResponse()->setContentType('application/json');
        return $this->renderText(json_encode($stats));
    }

    /**
     * Get asset details
     */
    public function executeAsset($request)
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

    /**
     * Autocomplete for actors (donors)
     */
    public function executeActorAutocomplete($request)
    {
        $term = $request->getParameter('term', '');
        $results = [];
        
        if (strlen($term) >= 2) {
            $results = \Illuminate\Database\Capsule\Manager::table('actor')
                ->leftJoin('actor_i18n', function($join) {
                    $join->on('actor.id', '=', 'actor_i18n.id')
                         ->where('actor_i18n.culture', '=', 'en');
                })
                ->where('actor_i18n.authorized_form_of_name', 'like', '%' . $term . '%')
                ->select('actor.id', 'actor_i18n.authorized_form_of_name as name')
                ->limit(15)
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'label' => $item->name ?: 'Unknown',
                        'value' => $item->name ?: 'Unknown'
                    ];
                })
                ->toArray();
        }
        
        $this->getResponse()->setContentType('application/json');
        return $this->renderText(json_encode($results));
    }
}
