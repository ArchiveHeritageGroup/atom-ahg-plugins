<?php

use AtomExtensions\Services\AclService;
use AtomExtensions\Services\LevelOfDescriptionService;
use Illuminate\Database\Capsule\Manager as DB;

class settingsLevelsAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAdministrator()) {
            AclService::forwardUnauthorized();
        }

        // Get available sectors based on enabled plugins
        $this->availableSectors = LevelOfDescriptionService::getAvailableSectors();
        $this->currentSector = $request->getParameter('sector', 'archive');

        // Validate current sector is available
        if (!in_array($this->currentSector, $this->availableSectors)) {
            $this->currentSector = 'archive';
        }

        // Get counts per sector for badges
        $this->sectorCounts = LevelOfDescriptionService::countBySector();

        // Get levels appropriate for this sector (not all levels)
        $this->sectorAvailableLevels = LevelOfDescriptionService::getLevelsForSector($this->currentSector);

        // Get levels currently assigned to this sector
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

            $this->redirect(['module' => 'settings', 'action' => 'levels', 'sector' => $this->currentSector]);
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
