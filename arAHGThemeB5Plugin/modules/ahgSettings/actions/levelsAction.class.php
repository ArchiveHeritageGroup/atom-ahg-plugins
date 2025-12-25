<?php

use AtomExtensions\Services\AclService;
use AtomExtensions\Services\LevelOfDescriptionService;
use Illuminate\Database\Capsule\Manager as DB;

class ahgSettingsLevelsAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAdministrator()) {
            AclService::forwardUnauthorized();
        }

        $this->sectors = LevelOfDescriptionService::ALL_SECTORS;
        $this->currentSector = $request->getParameter('sector', 'archive');
        
        // Get all levels
        $this->allLevels = DB::table('term as t')
            ->join('term_i18n as ti', function($j) {
                $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 's.object_id', '=', 't.id')
            ->where('t.taxonomy_id', 34)
            ->orderBy('ti.name')
            ->select('t.id', 'ti.name', 's.slug')
            ->get();
        
        // Get levels for current sector
        $this->sectorLevels = LevelOfDescriptionService::getBySector($this->currentSector);
        
        // Get sector level IDs for checkbox state
        $this->sectorLevelIds = $this->sectorLevels->pluck('id')->toArray();
        
        // Handle form submission
        if ($request->isMethod('post')) {
            $action = $request->getParameter('action_type');
            
            if ($action === 'update_sector') {
                $this->updateSectorLevels($request);
            } elseif ($action === 'update_order') {
                $this->updateDisplayOrder($request);
            }
            
            $this->redirect(['module' => 'ahgSettings', 'action' => 'levels', 'sector' => $this->currentSector]);
        }
    }
    
    private function updateSectorLevels($request): void
    {
        $sector = $request->getParameter('sector');
        $levelIds = $request->getParameter('levels', []);
        
        // Remove all current sector mappings
        DB::table('level_of_description_sector')
            ->where('sector', $sector)
            ->delete();
        
        // Add selected levels
        $order = 10;
        foreach ($levelIds as $levelId) {
            DB::table('level_of_description_sector')->insert([
                'term_id' => (int) $levelId,
                'sector' => $sector,
                'display_order' => $order,
            ]);
            $order += 10;
        }
        
        $this->context->user->setFlash('notice', 'Sector levels updated successfully.');
    }
    
    private function updateDisplayOrder($request): void
    {
        $sector = $request->getParameter('sector');
        $orders = $request->getParameter('order', []);
        
        foreach ($orders as $levelId => $order) {
            DB::table('level_of_description_sector')
                ->where('term_id', $levelId)
                ->where('sector', $sector)
                ->update(['display_order' => (int) $order]);
        }
        
        $this->context->user->setFlash('notice', 'Display order updated.');
    }
}
