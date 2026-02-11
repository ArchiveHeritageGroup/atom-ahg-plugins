<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * Landing Page Builder Actions
 * 
 * Admin interface for drag-and-drop page building
 */
class landingPageBuilderActions extends AhgController
{
    /**
     * Pre-execute - check permissions
     */
    public function boot(): void
    {
// Public actions (no auth required)
        $publicActions = ['index'];

        // User actions (requires authentication, but not admin)
        $userActions = ['myDashboard', 'myDashboardEdit', 'myDashboardList', 'myDashboardCreate'];

        // Admin actions require administrator role
        $actionName = $this->getActionName();

        if (in_array($actionName, $publicActions)) {
            return; // No auth required
        }

        if (in_array($actionName, $userActions)) {
            // Require authentication but not admin
            if (!$this->getUser()->isAuthenticated()) {
                $this->redirect(['module' => 'user', 'action' => 'login']);
            }
            return;
        }

        // All other actions require administrator
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }
    }

    /**
     * Public landing page display
     */
    public function executeIndex($request)
    {
        $service = new \AtomExtensions\Services\LandingPageService();
        $slug = $request->getParameter('slug');
        
        $data = $service->getLandingPageForDisplay($slug);
        
        if (!$data) {
            $this->forward404();
        }

        $this->page = $data['page'];
        $this->blocks = $data['blocks'];
    }

    /**
     * Admin - List all landing pages
     */
    public function executeList($request)
    {
        $service = new \AtomExtensions\Services\LandingPageService();
        $this->pages = $service->getAllPages();
    }

    /**
     * Admin - Create new page form
     */
    public function executeCreate($request)
    {
        if ($request->isMethod('post')) {
            $service = new \AtomExtensions\Services\LandingPageService();
            $userId = $this->getUser()->getAttribute('user_id');
            
            $result = $service->createPage([
                'name' => $request->getParameter('name'),
                'slug' => $request->getParameter('slug'),
                'description' => $request->getParameter('description'),
                'is_default' => $request->getParameter('is_default', 0),
                'is_active' => $request->getParameter('is_active', 1)
            ], $userId);

            if ($result['success']) {
                $this->redirect(['module' => 'landingPageBuilder', 'action' => 'edit', 
                                'id' => $result['page_id']]);
            }

            $this->error = $result['error'];
        }
    }

    /**
     * Admin - Edit page (drag-and-drop builder)
     */
    public function executeEdit($request)
    {
        $service = new \AtomExtensions\Services\LandingPageService();
        $pageId = (int)$request->getParameter('id');
        
        $data = $service->getPageForEditor($pageId);
        
        if (!$data) {
            $this->forward404();
        }

        $this->page = $data['page'];
        $this->blocks = $data['blocks'];
        $this->blockTypes = $data['blockTypes'];
        $this->versions = $data['versions'];
    }

    /**
     * Admin - Update page settings (AJAX)
     */
    public function executeUpdateSettings($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $service = new \AtomExtensions\Services\LandingPageService();
        $userId = $this->getUser()->getAttribute('user_id');
        $pageId = (int)$request->getParameter('id');

        $result = $service->updatePage($pageId, [
            'name' => $request->getParameter('name'),
            'slug' => $request->getParameter('slug'),
            'description' => $request->getParameter('description'),
            'is_default' => $request->getParameter('is_default') ? 1 : 0,
            'is_active' => $request->getParameter('is_active') ? 1 : 0
        ], $userId);

        return $this->renderText(json_encode($result));
    }

    /**
     * Admin - Delete page (AJAX)
     */
    public function executeDelete($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $service = new \AtomExtensions\Services\LandingPageService();
        $userId = $this->getUser()->getAttribute('user_id');
        $pageId = (int)$request->getParameter('id');

        $result = $service->deletePage($pageId, $userId);

        return $this->renderText(json_encode($result));
    }

    /**
     * Admin - Add block (AJAX)
     */
    public function executeAddBlock($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $service = new \AtomExtensions\Services\LandingPageService();
        $userId = $this->getUser()->getAttribute('user_id');

        $pageId = (int)$request->getParameter('page_id');
        $blockTypeId = (int)$request->getParameter('block_type_id');
        $config = json_decode($request->getParameter('config', '{}'), true);
        $parentBlockId = $request->getParameter('parent_block_id');
        $columnSlot = $request->getParameter('column_slot');

        // Pass parent info for nested blocks
        $options = [];
        if ($parentBlockId) {
            $options['parent_block_id'] = (int)$parentBlockId;
            $options['column_slot'] = $columnSlot;
        }

        $result = $service->addBlock($pageId, $blockTypeId, $config, $userId, $options);

        if ($result['success']) {
            // Return the new block HTML for insertion
            $repository = new \AtomExtensions\Repositories\LandingPageRepository();
            $block = $repository->getBlockById($result['block_id']);
            
            $result['block'] = [
                'id' => $block->id,
                'type_label' => $block->type_label,
                'type_icon' => $block->type_icon,
                'machine_name' => $block->machine_name,
                'config' => $block->config,
                'config_schema' => $block->config_schema,
                'is_visible' => $block->is_visible,
                'position' => $block->position
            ];
        }

        return $this->renderText(json_encode($result));
    }

    /**
     * Admin - Update block (AJAX)
     */
    public function executeUpdateBlock($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $service = new \AtomExtensions\Services\LandingPageService();
        $userId = $this->getUser()->getAttribute('user_id');
        $blockId = (int)$request->getParameter('block_id');

        $data = [];
        
        // Handle config update
        if ($request->hasParameter('config')) {
            $data['config'] = json_decode($request->getParameter('config'), true);
        }

        // Handle style settings
        foreach (['title', 'css_classes', 'container_type', 'background_color', 
                  'text_color', 'padding_top', 'padding_bottom', 'col_span'] as $field) {
            if ($request->hasParameter($field)) {
                $data[$field] = $request->getParameter($field);
            }
        }

        $result = $service->updateBlock($blockId, $data, $userId);

        return $this->renderText(json_encode($result));
    }

    /**
     * Admin - Delete block (AJAX)
     */
    public function executeDeleteBlock($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $service = new \AtomExtensions\Services\LandingPageService();
        $userId = $this->getUser()->getAttribute('user_id');
        $blockId = (int)$request->getParameter('block_id');

        $result = $service->deleteBlock($blockId, $userId);

        return $this->renderText(json_encode($result));
    }

    /**
     * Admin - Reorder blocks (AJAX)
     */
    public function executeReorderBlocks($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $service = new \AtomExtensions\Services\LandingPageService();
        $userId = $this->getUser()->getAttribute('user_id');

        $pageId = (int)$request->getParameter('page_id');
        $order = json_decode($request->getParameter('order', '[]'), true);

        $result = $service->reorderBlocks($pageId, $order, $userId);

        return $this->renderText(json_encode($result));
    }

    /**
     * Admin - Duplicate block (AJAX)
     */
    public function executeDuplicateBlock($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $service = new \AtomExtensions\Services\LandingPageService();
        $userId = $this->getUser()->getAttribute('user_id');
        $blockId = (int)$request->getParameter('block_id');

        $result = $service->duplicateBlock($blockId, $userId);

        return $this->renderText(json_encode($result));
    }

    /**
     * Admin - Toggle block visibility (AJAX)
     */
    public function executeToggleVisibility($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $service = new \AtomExtensions\Services\LandingPageService();
        $userId = $this->getUser()->getAttribute('user_id');
        $blockId = (int)$request->getParameter('block_id');

        $result = $service->toggleBlockVisibility($blockId, $userId);

        return $this->renderText(json_encode($result));
    }

    /**
     * Admin - Save draft (AJAX)
     */
    public function executeSaveDraft($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $service = new \AtomExtensions\Services\LandingPageService();
        $userId = $this->getUser()->getAttribute('user_id');

        $pageId = (int)$request->getParameter('page_id');
        $notes = $request->getParameter('notes');

        $result = $service->saveDraft($pageId, $userId, $notes);

        return $this->renderText(json_encode($result));
    }

    /**
     * Admin - Publish page (AJAX)
     */
    public function executePublish($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $service = new \AtomExtensions\Services\LandingPageService();
        $userId = $this->getUser()->getAttribute('user_id');
        $pageId = (int)$request->getParameter('page_id');

        $result = $service->publish($pageId, $userId);

        return $this->renderText(json_encode($result));
    }

    /**
     * Admin - Restore version (AJAX)
     */
    public function executeRestoreVersion($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $service = new \AtomExtensions\Services\LandingPageService();
        $userId = $this->getUser()->getAttribute('user_id');
        $versionId = (int)$request->getParameter('version_id');

        $result = $service->restoreVersion($versionId, $userId);

        return $this->renderText(json_encode($result));
    }

    /**
     * Admin - Get block config form (AJAX)
     */
    public function executeGetBlockConfig($request)
    {
        $this->getResponse()->setContentType('application/json');

        $blockId = (int)$request->getParameter('block_id');
        $repository = new \AtomExtensions\Repositories\LandingPageRepository();
        $block = $repository->getBlockById($blockId);

        if (!$block) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Block not found']));
        }

        return $this->renderText(json_encode([
            'success' => true,
            'block' => [
                'id' => $block->id,
                'title' => $block->title,
                'type_label' => $block->type_label,
                'machine_name' => $block->machine_name,
                'config' => $block->config,
                'config_schema' => $block->config_schema,
                'default_config' => $block->default_config,
                'css_classes' => $block->css_classes,
                'container_type' => $block->container_type,
                'background_color' => $block->background_color,
                'text_color' => $block->text_color,
                'padding_top' => $block->padding_top,
                'padding_bottom' => $block->padding_bottom,
                'col_span' => $block->col_span,
                'is_visible' => $block->is_visible
            ]
        ]));
    }

    /**
     * Preview landing page
     */
    public function executePreview($request)
    {
        $service = new \AtomExtensions\Services\LandingPageService();
        $pageId = (int)$request->getParameter('id');
        
        $data = $service->getLandingPageForDisplay(null);
        
        // Override with specific page
        $repository = new \AtomExtensions\Repositories\LandingPageRepository();
        $page = $repository->getPageById($pageId);
        
        if (!$page) {
            $this->forward404();
        }

        $blocks = $repository->getPageBlocks($pageId, false); // Include hidden blocks
        
        $this->page = $page;
        $this->blocks = $blocks->map(function ($block) use ($service) {
            // Use reflection to access protected method
            $reflection = new ReflectionMethod($service, 'enrichBlockData');
            $reflection->setAccessible(true);
            return $reflection->invoke($service, $block);
        });
        $this->isPreview = true;

        $this->setTemplate('index');
    }

    /**
     * Move block to column (AJAX)
     */
    public function executeMoveToColumn($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $repository = new \AtomExtensions\Repositories\LandingPageRepository();

        $blockId = (int)$request->getParameter('block_id');
        $parentBlockId = $request->getParameter('parent_block_id');
        $columnSlot = $request->getParameter('column_slot');

        // Allow null for parent (move back to root)
        $parentBlockId = $parentBlockId ? (int)$parentBlockId : null;
        $columnSlot = $columnSlot ?: null;

        $result = $repository->moveBlockToColumn($blockId, $parentBlockId, $columnSlot);

        return $this->renderText(json_encode(['success' => $result]));
    }

    // =========================================================================
    // USER DASHBOARD ACTIONS
    // =========================================================================

    /**
     * User - View my dashboard
     */
    public function executeMyDashboard($request)
    {
        $service = new \AtomExtensions\Services\LandingPageService();
        $userId = $this->getUser()->getAttribute('user_id');

        if (!$userId) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $data = $service->getUserDashboardForDisplay($userId);

        if (!$data) {
            // User has no dashboard yet, redirect to create
            $this->redirect(['module' => 'landingPageBuilder', 'action' => 'myDashboardCreate']);
        }

        $this->page = $data['page'];
        $this->blocks = $data['blocks'];
        $this->isUserDashboard = true;
    }

    /**
     * User - Edit my dashboard
     */
    public function executeMyDashboardEdit($request)
    {
        $service = new \AtomExtensions\Services\LandingPageService();
        $userId = $this->getUser()->getAttribute('user_id');

        if (!$userId) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        // Get or create user's dashboard
        $user = $this->getUser()->getUserObject();
        $userName = $user ? $user->username : 'User';
        $dashboardData = $service->getOrCreateUserDashboard($userId, $userName);
        $pageId = $dashboardData['page']->id;

        // Get editor data
        $data = $service->getPageForEditor($pageId);

        if (!$data) {
            $this->forward404();
        }

        $this->page = $data['page'];
        $this->blocks = $data['blocks'];
        $this->blockTypes = $data['blockTypes'];
        $this->versions = $data['versions'];
        $this->isUserDashboard = true;

        $this->setTemplate('edit');
    }

    /**
     * User - List my dashboards
     */
    public function executeMyDashboardList($request)
    {
        $service = new \AtomExtensions\Services\LandingPageService();
        $userId = $this->getUser()->getAttribute('user_id');

        if (!$userId) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $this->pages = $service->getUserDashboards($userId);
        $this->isUserDashboard = true;
    }

    /**
     * User - Create new dashboard
     */
    public function executeMyDashboardCreate($request)
    {
        $service = new \AtomExtensions\Services\LandingPageService();
        $userId = $this->getUser()->getAttribute('user_id');

        if (!$userId) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        if ($request->isMethod('post')) {
            $result = $service->createUserDashboard($userId, [
                'name' => $request->getParameter('name', 'My Dashboard'),
                'slug' => $request->getParameter('slug'),
                'description' => $request->getParameter('description'),
                'is_default' => $request->getParameter('is_default', 1),
                'is_active' => 1,
            ]);

            if ($result['success']) {
                $this->redirect(['module' => 'landingPageBuilder', 'action' => 'myDashboardEdit']);
            }

            $this->error = $result['error'];
        }

        // Check if user already has dashboards
        $existingDashboards = $service->getUserDashboards($userId);
        $this->hasDashboards = $existingDashboards->isNotEmpty();
        $this->isUserDashboard = true;
    }
}