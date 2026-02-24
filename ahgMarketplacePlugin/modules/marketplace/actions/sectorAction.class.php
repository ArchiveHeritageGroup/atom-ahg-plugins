<?php

use AtomFramework\Http\Controllers\AhgController;

require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/MarketplaceService.php';
require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Repositories/SettingsRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\MarketplaceService;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SettingsRepository;

class marketplaceSectorAction extends AhgController
{
    public function execute($request)
    {
        $sector = $request->getParameter('sector');

        // Validate sector is a valid ENUM value
        $validSectors = ['gallery', 'museum', 'archive', 'library', 'dam'];
        if (empty($sector) || !in_array($sector, $validSectors, true)) {
            $this->forward404();
        }

        $service = new MarketplaceService();
        $settingsRepo = new SettingsRepository();

        // Get listings for this sector with pagination
        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = 24;
        $offset = ($page - 1) * $limit;
        $sort = $request->getParameter('sort', 'newest');

        $results = $service->browse(['sector' => $sector], $limit, $offset, $sort);

        // Get categories for this sector
        $categories = $settingsRepo->getCategories($sector);

        $this->sector = $sector;
        $this->listings = $results['items'];
        $this->total = $results['total'];
        $this->categories = $categories;
        $this->page = $page;
    }
}
