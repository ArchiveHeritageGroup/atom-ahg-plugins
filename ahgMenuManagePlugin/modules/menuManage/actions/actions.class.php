<?php

use AtomFramework\Http\Controllers\AhgController;

class menuManageActions extends AhgController
{
    /**
     * List all menu items as a tree.
     *
     * Handles inline reordering via ?move=ID&before=ID or ?move=ID&after=ID.
     */
    public function executeList($request)
    {
        // Require administrator
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');

            return;
        }

        $culture = $this->culture();

        $this->response->setTitle(__('Menus') . ' - ' . $this->response->getTitle());

        // Handle inline reorder requests
        $moveId = (int) $request->getParameter('move');
        if ($moveId > 0) {
            $beforeId = (int) $request->getParameter('before');
            $afterId = (int) $request->getParameter('after');

            if ($beforeId > 0) {
                \AhgMenuManage\Services\MenuCrudService::moveBefore($moveId, $beforeId);
                $this->getUser()->setFlash('notice', __('Menu item reordered.'));
            } elseif ($afterId > 0) {
                \AhgMenuManage\Services\MenuCrudService::moveAfter($moveId, $afterId);
                $this->getUser()->setFlash('notice', __('Menu item reordered.'));
            }

            $this->redirect('@menu_list');
        }

        // Build the tree with sibling info for move buttons
        $tree = \AhgMenuManage\Services\MenuCrudService::getTree($culture);

        // Add prev/next sibling IDs for each item
        foreach ($tree as &$item) {
            $siblings = \AhgMenuManage\Services\MenuCrudService::getSiblingIds($item['id']);
            $item['prevSiblingId'] = $siblings['prev'];
            $item['nextSiblingId'] = $siblings['next'];
        }
        unset($item);

        $this->menuTree = $tree;
    }

    /**
     * Edit or create a menu item.
     */
    public function executeEdit($request)
    {
        // Require administrator
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');

            return;
        }

        $culture = $this->culture();
        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        $idParam = $request->getParameter('id');
        $this->menuId = null;

        if (!empty($idParam) && is_numeric($idParam)) {
            $this->menuId = (int) $idParam;
        }

        $this->isNew = empty($this->menuId);

        if (!$this->isNew) {
            $this->menuRecord = \AhgMenuManage\Services\MenuCrudService::getById($this->menuId, $culture);
            if (!$this->menuRecord) {
                $this->forward404();
            }

            $this->isProtected = $this->menuRecord['isProtected'];

            $title = $this->menuRecord['label'] ?: $this->menuRecord['name'] ?: __('Untitled');
            $this->response->setTitle(__('Edit %1%', ['%1%' => $title]) . ' - ' . $this->response->getTitle());
        } else {
            $this->menuRecord = [
                'id' => null,
                'parentId' => \AhgMenuManage\Services\MenuCrudService::ROOT_ID,
                'name' => '',
                'path' => '',
                'label' => '',
                'description' => '',
                'sourceCulture' => $culture,
                'isProtected' => false,
            ];
            $this->isProtected = false;
            $this->response->setTitle(__('Add menu') . ' - ' . $this->response->getTitle());
        }

        // Get parent choices for dropdown
        $this->parentChoices = \AhgMenuManage\Services\MenuCrudService::getParentChoices($culture);

        // Handle POST
        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters());

            $this->errors = [];
            $name = trim($request->getParameter('name', ''));
            $label = trim($request->getParameter('label', ''));
            $path = trim($request->getParameter('path', ''));
            $parentId = (int) $request->getParameter('parent_id', \AhgMenuManage\Services\MenuCrudService::ROOT_ID);
            $description = trim($request->getParameter('description', ''));

            // Validate: label is required
            if (empty($label)) {
                $this->errors[] = __('Label is required.');
            }

            // Validate: name is required and must be unique
            if (empty($name)) {
                $this->errors[] = __('Name is required.');
            } elseif ($this->isNew || (!$this->isProtected && $name !== $this->menuRecord['name'])) {
                // Check uniqueness
                $existing = \Illuminate\Database\Capsule\Manager::table('menu')
                    ->where('name', $name)
                    ->when(!$this->isNew, function ($q) {
                        return $q->where('id', '!=', $this->menuId);
                    })
                    ->exists();
                if ($existing) {
                    $this->errors[] = __('A menu with this name already exists.');
                }
            }

            if (empty($this->errors)) {
                $data = [
                    'label' => $label,
                    'path' => $path,
                    'parentId' => $parentId,
                    'description' => $description,
                ];

                // Only include name if not protected
                if ($this->isNew || !$this->isProtected) {
                    $data['name'] = $name;
                }

                if ($this->isNew) {
                    \AhgMenuManage\Services\MenuCrudService::create($data, $culture);
                    $this->getUser()->setFlash('notice', __('Menu item created.'));
                    $this->redirect('@menu_list');
                } else {
                    \AhgMenuManage\Services\MenuCrudService::update($this->menuId, $data, $culture);
                    $this->getUser()->setFlash('notice', __('Menu item updated.'));
                    $this->redirect('@menu_list');
                }
            }

            // If errors, update record with submitted values for re-display
            $this->menuRecord['label'] = $label;
            $this->menuRecord['path'] = $path;
            $this->menuRecord['description'] = $description;
            if (!$this->isProtected) {
                $this->menuRecord['name'] = $name;
            }
            $this->menuRecord['parentId'] = $parentId;
        }
    }

    /**
     * Delete a menu item (with confirmation).
     */
    public function executeDelete($request)
    {
        // Require administrator
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');

            return;
        }

        $culture = $this->culture();
        $this->form = new sfForm();
        $id = (int) $request->getParameter('id');

        $this->menuRecord = \AhgMenuManage\Services\MenuCrudService::getById($id, $culture);
        if (!$this->menuRecord) {
            $this->forward404();
        }

        $this->isProtected = $this->menuRecord['isProtected'];

        // Cannot delete protected menus
        if ($this->isProtected) {
            $this->getUser()->setFlash('error', __('This menu item is protected and cannot be deleted.'));
            $this->redirect('@menu_list');
        }

        if ($request->isMethod('delete')) {
            $this->form->bind($request->getPostParameters());
            if ($this->form->isValid()) {
                try {
                    \AhgMenuManage\Services\MenuCrudService::delete($id);
                    $this->getUser()->setFlash('notice', __('Menu item deleted.'));
                } catch (\RuntimeException $e) {
                    $this->getUser()->setFlash('error', $e->getMessage());
                }
                $this->redirect('@menu_list');
            }
        }
    }
}
