<?php

use AtomFramework\Http\Controllers\AhgController;

class functionManageActions extends AhgController
{
    public function executeBrowse($request)
    {
        $culture = $this->culture();

        $this->response->setTitle(__('Browse functions') . ' - ' . $this->response->getTitle());

        // Sort options
        $this->sortOptions = [
            'alphabetic' => __('Name'),
            'lastUpdated' => __('Date modified'),
            'identifier' => __('Identifier'),
        ];

        // Sort defaults
        if ($this->getUser()->isAuthenticated()) {
            $sortSetting = $this->config('app_sort_browser_user', 'lastUpdated');
        } else {
            $sortSetting = $this->config('app_sort_browser_anonymous', 'lastUpdated');
        }

        $sort = $request->getParameter('sort', $sortSetting);
        $sortDir = 'asc';
        if ('lastUpdated' == $sort) {
            $sortDir = 'desc';
        }
        if ($request->sortDir && in_array($request->sortDir, ['asc', 'desc'])) {
            $sortDir = $request->sortDir;
        }

        $limit = (int) ($request->limit ?: $this->config('app_hits_per_page', 30));
        $page = (int) ($request->page ?: 1);

        // Handle global search redirect: ?query=X -> subquery=X
        $subquery = $request->getParameter('subquery', '');
        if (empty($subquery) && !empty($request->getParameter('query'))) {
            $subquery = $request->getParameter('query');
        }

        $service = new \AhgFunctionManage\Services\FunctionBrowseService($culture);

        $browseResult = $service->browse([
            'page' => $page,
            'limit' => $limit,
            'sort' => $sort,
            'sortDir' => $sortDir,
            'subquery' => $subquery,
        ]);

        $this->pager = new \AhgFunctionManage\SimplePager(
            $browseResult['hits'],
            $browseResult['total'],
            $browseResult['page'],
            $browseResult['limit']
        );

        // ACL for add button visibility
        $user = $this->getUser();
        $this->canCreate = $user->isAuthenticated()
            && ($user->hasGroup(QubitAclGroup::ADMINISTRATOR_ID)
                || $user->hasGroup(QubitAclGroup::EDITOR_ID));
    }

    /**
     * View a function record.
     */
    public function executeView($request)
    {
        $culture = $this->culture();
        $slug = $request->getParameter('slug');

        $this->func = \AhgFunctionManage\Services\FunctionCrudService::getBySlug($slug, $culture);
        if (!$this->func) {
            $this->forward404();
        }

        $title = $this->func['authorizedFormOfName'] ?: __('Untitled');
        $this->response->setTitle("{$title} - {$this->response->getTitle()}");

        // ACL
        $user = $this->getUser();
        $isAdmin = $user->isAuthenticated()
            && ($user->hasGroup(QubitAclGroup::ADMINISTRATOR_ID)
                || $user->hasGroup(QubitAclGroup::EDITOR_ID));

        $this->canEdit = $isAdmin;
        $this->canDelete = $isAdmin;
        $this->canCreate = $isAdmin;
    }

    /**
     * Edit or create a function record.
     */
    public function executeEdit($request)
    {
        $culture = $this->culture();
        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        // ACL — require editor/admin
        $user = $this->getUser();
        if (!$user->isAuthenticated()
            || !($user->hasGroup(QubitAclGroup::ADMINISTRATOR_ID) || $user->hasGroup(QubitAclGroup::EDITOR_ID))
        ) {
            QubitAcl::forwardUnauthorized();
        }

        $slug = $request->getParameter('slug');
        $this->isNew = empty($slug);

        // Load dropdown options
        $this->functionTypes = \AhgFunctionManage\Services\FunctionCrudService::getFunctionTypes($culture);
        $this->descriptionStatuses = \AhgFunctionManage\Services\FunctionCrudService::getDescriptionStatuses($culture);
        $this->descriptionDetails = \AhgFunctionManage\Services\FunctionCrudService::getDescriptionDetails($culture);

        if (!$this->isNew) {
            $this->func = \AhgFunctionManage\Services\FunctionCrudService::getBySlug($slug, $culture);
            if (!$this->func) {
                $this->forward404();
            }

            $title = $this->func['authorizedFormOfName'] ?: __('Untitled');
            $this->response->setTitle(__('Edit %1%', ['%1%' => $title]) . ' - ' . $this->response->getTitle());
        } else {
            $this->func = [
                'id' => null,
                'slug' => null,
                'authorizedFormOfName' => '',
                'classification' => '',
                'dates' => '',
                'description' => '',
                'history' => '',
                'legislation' => '',
                'institutionIdentifier' => '',
                'revisionHistory' => '',
                'rules' => '',
                'sources' => '',
                'typeId' => null,
                'descriptionStatusId' => null,
                'descriptionDetailId' => null,
                'descriptionIdentifier' => '',
                'sourceStandard' => '',
                'serialNumber' => 0,
            ];

            $this->response->setTitle(__('Add new function') . ' - ' . $this->response->getTitle());
        }

        // Handle POST
        if ($request->isMethod('post')) {
            $this->errors = [];

            $authorizedFormOfName = trim($request->getParameter('authorizedFormOfName', ''));

            if (empty($authorizedFormOfName)) {
                $this->errors[] = __('Authorized form of name is required.');
            }

            if (empty($this->errors)) {
                $data = [
                    'authorizedFormOfName' => $authorizedFormOfName,
                    'classification' => trim($request->getParameter('classification', '')),
                    'dates' => trim($request->getParameter('dates', '')),
                    'description' => trim($request->getParameter('description', '')),
                    'history' => trim($request->getParameter('history', '')),
                    'legislation' => trim($request->getParameter('legislation', '')),
                    'institutionIdentifier' => trim($request->getParameter('institutionIdentifier', '')),
                    'revisionHistory' => trim($request->getParameter('revisionHistory', '')),
                    'rules' => trim($request->getParameter('rules', '')),
                    'sources' => trim($request->getParameter('sources', '')),
                    'typeId' => $request->getParameter('typeId', ''),
                    'descriptionStatusId' => $request->getParameter('descriptionStatusId', ''),
                    'descriptionDetailId' => $request->getParameter('descriptionDetailId', ''),
                    'descriptionIdentifier' => trim($request->getParameter('descriptionIdentifier', '')),
                    'sourceStandard' => trim($request->getParameter('sourceStandard', '')),
                ];

                if ($this->isNew) {
                    $newId = \AhgFunctionManage\Services\FunctionCrudService::create($data, $culture);
                    $newSlug = \AhgCore\Services\ObjectService::getSlug($newId);
                    $this->redirect('@function_view_override?slug=' . $newSlug);
                } else {
                    \AhgFunctionManage\Services\FunctionCrudService::update($this->func['id'], $data, $culture);
                    $this->redirect('@function_view_override?slug=' . $this->func['slug']);
                }
            }

            // If errors, update func with submitted values for re-display
            $this->func['authorizedFormOfName'] = $authorizedFormOfName;
            $this->func['classification'] = $request->getParameter('classification', '');
            $this->func['dates'] = $request->getParameter('dates', '');
            $this->func['description'] = $request->getParameter('description', '');
            $this->func['history'] = $request->getParameter('history', '');
            $this->func['legislation'] = $request->getParameter('legislation', '');
            $this->func['institutionIdentifier'] = $request->getParameter('institutionIdentifier', '');
            $this->func['revisionHistory'] = $request->getParameter('revisionHistory', '');
            $this->func['rules'] = $request->getParameter('rules', '');
            $this->func['sources'] = $request->getParameter('sources', '');
            $this->func['typeId'] = $request->getParameter('typeId', '');
            $this->func['descriptionStatusId'] = $request->getParameter('descriptionStatusId', '');
            $this->func['descriptionDetailId'] = $request->getParameter('descriptionDetailId', '');
            $this->func['descriptionIdentifier'] = $request->getParameter('descriptionIdentifier', '');
            $this->func['sourceStandard'] = $request->getParameter('sourceStandard', '');
        }
    }

    /**
     * Delete a function record.
     */
    public function executeDelete($request)
    {
        $this->form = new sfForm();
        $culture = $this->culture();

        // ACL
        $user = $this->getUser();
        if (!$user->isAuthenticated()
            || !($user->hasGroup(QubitAclGroup::ADMINISTRATOR_ID) || $user->hasGroup(QubitAclGroup::EDITOR_ID))
        ) {
            QubitAcl::forwardUnauthorized();
        }

        $slug = $request->getParameter('slug');
        $this->func = \AhgFunctionManage\Services\FunctionCrudService::getBySlug($slug, $culture);
        if (!$this->func) {
            $this->forward404();
        }

        if ($request->isMethod('delete')) {
            $this->form->bind($request->getPostParameters());
            if ($this->form->isValid()) {
                \AhgFunctionManage\Services\FunctionCrudService::delete($this->func['id']);
                $this->redirect('@function_browse_override');
            }
        }
    }
}
