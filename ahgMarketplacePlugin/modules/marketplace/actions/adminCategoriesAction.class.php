<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Repositories/SettingsRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SettingsRepository;

class marketplaceAdminCategoriesAction extends AhgController
{
    public function execute($request)
    {
        // Admin check
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }
        if (!$this->context->user->hasCredential('administrator')) {
            $this->getUser()->setFlash('error', 'Admin access required.');
            $this->redirect(['module' => 'marketplace', 'action' => 'browse']);
        }

        $settingsRepo = new SettingsRepository();

        // Handle POST actions
        if ($request->isMethod('post')) {
            $formAction = $request->getParameter('form_action');

            if ($formAction === 'create') {
                $name = trim($request->getParameter('name', ''));
                $sector = trim($request->getParameter('sector', ''));
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
                $sortOrder = (int) $request->getParameter('sort_order', 0);

                if (empty($name) || empty($sector)) {
                    $this->getUser()->setFlash('error', 'Name and sector are required.');
                } else {
                    $settingsRepo->createCategory([
                        'name' => $name,
                        'slug' => $slug,
                        'sector' => $sector,
                        'description' => trim($request->getParameter('description', '')),
                        'sort_order' => $sortOrder,
                        'is_active' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $this->getUser()->setFlash('notice', 'Category created.');
                }
            } elseif ($formAction === 'update') {
                $catId = (int) $request->getParameter('category_id');
                if ($catId) {
                    $updateData = [
                        'name' => trim($request->getParameter('name', '')),
                        'sector' => trim($request->getParameter('sector', '')),
                        'description' => trim($request->getParameter('description', '')),
                        'sort_order' => (int) $request->getParameter('sort_order', 0),
                        'is_active' => $request->getParameter('is_active', 0) ? 1 : 0,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];

                    // Regenerate slug from name
                    if (!empty($updateData['name'])) {
                        $updateData['slug'] = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $updateData['name']), '-'));
                    }

                    $settingsRepo->updateCategory($catId, $updateData);
                    $this->getUser()->setFlash('notice', 'Category updated.');
                }
            } elseif ($formAction === 'delete') {
                $catId = (int) $request->getParameter('category_id');
                if ($catId) {
                    $settingsRepo->deleteCategory($catId);
                    $this->getUser()->setFlash('notice', 'Category deleted.');
                }
            }

            $this->redirect(['module' => 'marketplace', 'action' => 'adminCategories']);
        }

        // Get all categories (including inactive)
        $this->categories = $settingsRepo->getCategories(null, false);
        $this->sectors = ['gallery', 'museum', 'archive', 'library', 'dam'];
    }
}
