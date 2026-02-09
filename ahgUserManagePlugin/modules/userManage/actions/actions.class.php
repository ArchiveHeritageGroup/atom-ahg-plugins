<?php

class userManageActions extends sfActions
{
    public function preExecute()
    {
        parent::preExecute();

        sfContext::getInstance()->getConfiguration()->loadHelpers(['I18N', 'Url', 'Qubit', 'Text', 'Date']);

        if (!class_exists('Illuminate\Database\Capsule\Manager')) {
            $frameworkBoot = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
            if (file_exists($frameworkBoot)) {
                require_once $frameworkBoot;
            }
        }
    }

    public function executeBrowse(sfWebRequest $request)
    {
        // Admin-only access
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');

            return;
        }

        $culture = $this->context->user->getCulture();

        $this->response->setTitle(__('List users') . ' - ' . $this->response->getTitle());

        // Sort options
        $this->sortOptions = [
            'username' => $this->context->i18n->__('Username'),
            'email' => $this->context->i18n->__('Email'),
            'lastUpdated' => $this->context->i18n->__('Date modified'),
        ];

        // Sort defaults
        $sort = $request->getParameter('sort', 'username');
        $sortDir = 'asc';
        if ('lastUpdated' == $sort) {
            $sortDir = 'desc';
        }
        if ($request->sortDir && in_array($request->sortDir, ['asc', 'desc'])) {
            $sortDir = $request->sortDir;
        }

        $limit = (int) ($request->limit ?: sfConfig::get('app_hits_per_page', 30));
        $page = (int) ($request->page ?: 1);

        // Handle global search redirect: ?query=X -> subquery=X
        $subquery = $request->getParameter('subquery', '');
        if (empty($subquery) && !empty($request->getParameter('query'))) {
            $subquery = $request->getParameter('query');
        }

        // Active/inactive filter
        $this->filter = $request->getParameter('filter', '');

        $service = new \AhgUserManage\Services\UserBrowseService($culture);

        $browseResult = $service->browse([
            'page' => $page,
            'limit' => $limit,
            'sort' => $sort,
            'sortDir' => $sortDir,
            'subquery' => $subquery,
            'filter' => $this->filter,
        ]);

        $this->pager = new \AhgUserManage\SimplePager(
            $browseResult['hits'],
            $browseResult['total'],
            $browseResult['page'],
            $browseResult['limit']
        );
    }
}
