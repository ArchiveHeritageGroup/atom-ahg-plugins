<?php

class staticPageManageActions extends AhgActions
{
    public function preExecute()
    {
        parent::preExecute();

        sfContext::getInstance()->getConfiguration()->loadHelpers(['I18N', 'Url', 'Qubit', 'Text', 'Date']);
    }

    /**
     * List all static pages.
     */
    public function executeList(sfWebRequest $request)
    {
        // Require administrator or editor
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');

            return;
        }

        $culture = $this->context->user->getCulture();

        $this->response->setTitle(__('Static pages') . ' - ' . $this->response->getTitle());

        $this->pages = \AhgStaticPage\Services\StaticPageCrudService::getAll($culture);
    }

    /**
     * Edit or create a static page.
     */
    public function executeEdit(sfWebRequest $request)
    {
        // Require administrator or editor
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');

            return;
        }

        $culture = $this->context->user->getCulture();
        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        // Resolve ID: can come from route parameter or 'home' keyword
        $idParam = $request->getParameter('id');
        $this->pageId = null;

        if ('home' === $idParam) {
            // Special route for home page editing
            $this->pageId = \AhgStaticPage\Services\StaticPageCrudService::getHomePageId();
            if (!$this->pageId) {
                $this->forward404(__('Home page not found.'));
            }
        } elseif (!empty($idParam) && is_numeric($idParam)) {
            $this->pageId = (int) $idParam;
        }

        $this->isNew = empty($this->pageId);

        if (!$this->isNew) {
            $this->pageRecord = \AhgStaticPage\Services\StaticPageCrudService::getById($this->pageId, $culture);
            if (!$this->pageRecord) {
                $this->forward404();
            }

            $this->isProtected = \AhgStaticPage\Services\StaticPageCrudService::isProtected($this->pageId);

            $title = $this->pageRecord['title'] ?: __('Untitled');
            $this->response->setTitle(__('Edit %1%', ['%1%' => $title]) . ' - ' . $this->response->getTitle());
        } else {
            $this->pageRecord = [
                'id' => null,
                'title' => '',
                'content' => '',
                'slug' => '',
                'sourceCulture' => $culture,
            ];
            $this->isProtected = false;
            $this->response->setTitle(__('Add static page') . ' - ' . $this->response->getTitle());
        }

        // Handle POST
        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters());

            $this->errors = [];
            $title = trim($request->getParameter('title', ''));
            $slug = trim($request->getParameter('slug', ''));
            $content = $request->getParameter('content', '');

            // Validate
            if (empty($title)) {
                $this->errors[] = __('Title is required.');
            }

            if (empty($this->errors)) {
                $data = [
                    'title' => $title,
                    'content' => $content,
                ];

                // Only include slug if not protected
                if (!$this->isProtected && !empty($slug)) {
                    $data['slug'] = $slug;
                }

                if ($this->isNew) {
                    $newId = \AhgStaticPage\Services\StaticPageCrudService::create($data, $culture);
                    $this->getUser()->setFlash('notice', __('Static page created.'));
                    $this->redirect('@staticpage_list');
                } else {
                    \AhgStaticPage\Services\StaticPageCrudService::update($this->pageId, $data, $culture);
                    $this->getUser()->setFlash('notice', __('Static page updated.'));
                    $this->redirect('@staticpage_list');
                }
            }

            // If errors, update record with submitted values for re-display
            $this->pageRecord['title'] = $title;
            $this->pageRecord['content'] = $content;
            if (!$this->isProtected) {
                $this->pageRecord['slug'] = $slug;
            }
        }
    }

    /**
     * Delete a static page (with confirmation).
     */
    public function executeDelete(sfWebRequest $request)
    {
        // Require administrator
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');

            return;
        }

        $culture = $this->context->user->getCulture();
        $this->form = new sfForm();
        $id = (int) $request->getParameter('id');

        $this->pageRecord = \AhgStaticPage\Services\StaticPageCrudService::getById($id, $culture);
        if (!$this->pageRecord) {
            $this->forward404();
        }

        // Cannot delete protected pages
        if (\AhgStaticPage\Services\StaticPageCrudService::isProtected($id)) {
            $this->getUser()->setFlash('error', __('This page is protected and cannot be deleted.'));
            $this->redirect('@staticpage_list');
        }

        if ($request->isMethod('delete')) {
            $this->form->bind($request->getPostParameters());
            if ($this->form->isValid()) {
                try {
                    \AhgStaticPage\Services\StaticPageCrudService::delete($id);
                    $this->getUser()->setFlash('notice', __('Static page deleted.'));
                } catch (\RuntimeException $e) {
                    $this->getUser()->setFlash('error', $e->getMessage());
                }
                $this->redirect('@staticpage_list');
            }
        }
    }
}
