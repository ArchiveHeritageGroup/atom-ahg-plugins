<?php

/**
 * Landing Page Builder Actions
 * 
 * Admin interface for drag-and-drop page building
 */
class landingPageBuilderActions extends sfActions
{
    /**
     * Pre-execute - check permissions
     */
    public function preExecute()
    {
        parent::preExecute();

        // Require administrator access for all actions except index (public display)
        $publicActions = ['index'];
        if (!in_array($this->getActionName(), $publicActions)) {
            if (!$this->context->user->isAdministrator()) {
                $this->forward('admin', 'secure');
            }
        }
    }

    /**
     * Public landing page display
     */
    public function executeIndex(sfWebRequest $request)
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
    public function executeList(sfWebRequest $request)
    {
        $service = new \AtomExtensions\Services\LandingPageService();
        $this->pages = $service->getAllPages();
    }

    /**
     * Admin - Create new page form
     */
    public function executeCreate(sfWebRequest $request)
    {
        if ($request->isMethod('post')) {
            $service = new \AtomExtensions\Services\LandingPageService();
            $userId = $this->context->user->getAttribute('user_id');
            
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
    public function executeEdit(sfWebRequest $request)
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
    public function executeUpdateSettings(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $service = new \AtomExtensions\Services\LandingPageService();
        $userId = $this->context->user->getAttribute('user_id');
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
    public function executeDelete(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $service = new \AtomExtensions\Services\LandingPageService();
        $userId = $this->context->user->getAttribute('user_id');
        $pageId = (int)$request->getParameter('id');

        $result = $service->deletePage($pageId, $userId);

        return $this->renderText(json_encode($result));
    }

    /**
     * Admin - Add block (AJAX)
     */
    public function executeAddBlock(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $service = new \AtomExtensions\Services\LandingPageService();
        $userId = $this->context->user->getAttribute('user_id');

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
    public function executeUpdateBlock(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $service = new \AtomExtensions\Services\LandingPageService();
        $userId = $this->context->user->getAttribute('user_id');
        $blockId = (int)$request->getParameter('block_id');

        $data = [];
        
        // Handle config update
        if ($request->hasParameter('config')) {
            $data['config'] = json_decode($request->getParameter('config'), true);
        }

        // Handle style settings
        foreach (['title', 'css_classes', 'container_type', 'background_color', 
                  'text_color', 'padding_top', 'padding_bottom'] as $field) {
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
    public function executeDeleteBlock(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $service = new \AtomExtensions\Services\LandingPageService();
        $userId = $this->context->user->getAttribute('user_id');
        $blockId = (int)$request->getParameter('block_id');

        $result = $service->deleteBlock($blockId, $userId);

        return $this->renderText(json_encode($result));
    }

    /**
     * Admin - Reorder blocks (AJAX)
     */
    public function executeReorderBlocks(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $service = new \AtomExtensions\Services\LandingPageService();
        $userId = $this->context->user->getAttribute('user_id');

        $pageId = (int)$request->getParameter('page_id');
        $order = json_decode($request->getParameter('order', '[]'), true);

        $result = $service->reorderBlocks($pageId, $order, $userId);

        return $this->renderText(json_encode($result));
    }

    /**
     * Admin - Duplicate block (AJAX)
     */
    public function executeDuplicateBlock(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $service = new \AtomExtensions\Services\LandingPageService();
        $userId = $this->context->user->getAttribute('user_id');
        $blockId = (int)$request->getParameter('block_id');

        $result = $service->duplicateBlock($blockId, $userId);

        return $this->renderText(json_encode($result));
    }

    /**
     * Admin - Toggle block visibility (AJAX)
     */
    public function executeToggleVisibility(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $service = new \AtomExtensions\Services\LandingPageService();
        $userId = $this->context->user->getAttribute('user_id');
        $blockId = (int)$request->getParameter('block_id');

        $result = $service->toggleBlockVisibility($blockId, $userId);

        return $this->renderText(json_encode($result));
    }

    /**
     * Admin - Save draft (AJAX)
     */
    public function executeSaveDraft(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $service = new \AtomExtensions\Services\LandingPageService();
        $userId = $this->context->user->getAttribute('user_id');

        $pageId = (int)$request->getParameter('page_id');
        $notes = $request->getParameter('notes');

        $result = $service->saveDraft($pageId, $userId, $notes);

        return $this->renderText(json_encode($result));
    }

    /**
     * Admin - Publish page (AJAX)
     */
    public function executePublish(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $service = new \AtomExtensions\Services\LandingPageService();
        $userId = $this->context->user->getAttribute('user_id');
        $pageId = (int)$request->getParameter('page_id');

        $result = $service->publish($pageId, $userId);

        return $this->renderText(json_encode($result));
    }

    /**
     * Admin - Restore version (AJAX)
     */
    public function executeRestoreVersion(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid method']));
        }

        $service = new \AtomExtensions\Services\LandingPageService();
        $userId = $this->context->user->getAttribute('user_id');
        $versionId = (int)$request->getParameter('version_id');

        $result = $service->restoreVersion($versionId, $userId);

        return $this->renderText(json_encode($result));
    }

    /**
     * Admin - Get block config form (AJAX)
     */
    public function executeGetBlockConfig(sfWebRequest $request)
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
                'is_visible' => $block->is_visible
            ]
        ]));
    }

    /**
     * Preview landing page
     */
    public function executePreview(sfWebRequest $request)
    {
        $service = new \AtomExtensions\Services\LandingPageService();
        $pageId = (int)$request->getParameter('id');
        
        $data = $service->getLandingPageForDisplay(null);
        
        // Override with specific page
        $repository = new \AtomExtensions\Repositories\LandingPageRepository();
        $page = $repository->getPageById($pageId);
        
        if (!$page) {
            $this->forward404();
        error_log("ACTION DEBUG: blocks count=" . count($blocks) . " first_id=" . ($blocks->first()->id ?? "none") . " first_config=" . json_encode($blocks->first()->config ?? []));
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
    public function executeMoveToColumn(sfWebRequest $request)
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
}