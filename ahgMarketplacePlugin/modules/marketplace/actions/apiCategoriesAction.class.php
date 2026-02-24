<?php

use AtomFramework\Http\Controllers\AhgController;

require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Repositories/SettingsRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SettingsRepository;

class marketplaceApiCategoriesAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        $sector = $request->getParameter('sector');

        // Validate sector if provided
        $validSectors = ['gallery', 'museum', 'archive', 'library', 'dam'];
        if ($sector && !in_array($sector, $validSectors, true)) {
            echo json_encode(['success' => false, 'error' => 'Invalid sector. Must be one of: ' . implode(', ', $validSectors)]);

            return sfView::NONE;
        }

        $settingsRepo = new SettingsRepository();
        $categories = $settingsRepo->getCategories($sector);

        $result = [];
        foreach ($categories as $category) {
            $result[] = [
                'id' => (int) $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'sector' => $category->sector,
                'description' => $category->description ?? null,
                'sort_order' => (int) ($category->sort_order ?? 0),
                'is_active' => (bool) $category->is_active,
            ];
        }

        echo json_encode([
            'success' => true,
            'sector' => $sector,
            'categories' => $result,
        ]);

        return sfView::NONE;
    }
}
