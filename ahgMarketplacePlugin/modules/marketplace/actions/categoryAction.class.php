<?php

use AtomFramework\Http\Controllers\AhgController;

require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/MarketplaceService.php';
require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Repositories/SettingsRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\MarketplaceService;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SettingsRepository;

class marketplaceCategoryAction extends AhgController
{
    public function execute($request)
    {
        $sector = $request->getParameter('sector');
        $slug = $request->getParameter('slug');

        if (empty($sector) || empty($slug)) {
            $this->redirect(['module' => 'marketplace', 'action' => 'browse']);
        }

        // Validate sector
        $validSectors = ['gallery', 'museum', 'archive', 'library', 'dam'];
        if (!in_array($sector, $validSectors, true)) {
            $this->forward404();
        }

        $settingsRepo = new SettingsRepository();
        $service = new MarketplaceService();

        // Get category by sector + slug
        $category = $settingsRepo->getCategoryBySlug($sector, $slug);
        if (!$category) {
            $this->forward404();
        }

        // Get listings for this category with pagination
        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = 24;
        $offset = ($page - 1) * $limit;
        $sort = $request->getParameter('sort', 'newest');

        $results = $service->browse(
            ['sector' => $sector, 'category_id' => $category->id],
            $limit,
            $offset,
            $sort
        );

        $this->category = $category;
        $this->sector = $sector;
        $this->listings = $results['items'];
        $this->total = $results['total'];
        $this->page = $page;
    }
}
