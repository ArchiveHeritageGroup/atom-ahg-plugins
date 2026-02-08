<?php

class rightsHolderManageActions extends sfActions
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
        $culture = $this->context->user->getCulture();

        $this->response->setTitle(__('Browse rights holders') . ' - ' . $this->response->getTitle());

        // Sort options
        $this->sortOptions = [
            'alphabetic' => $this->context->i18n->__('Name'),
            'lastUpdated' => $this->context->i18n->__('Date modified'),
            'identifier' => $this->context->i18n->__('Identifier'),
        ];

        // Sort defaults
        if ($this->getUser()->isAuthenticated()) {
            $sortSetting = sfConfig::get('app_sort_browser_user', 'lastUpdated');
        } else {
            $sortSetting = sfConfig::get('app_sort_browser_anonymous', 'lastUpdated');
        }

        $sort = $request->getParameter('sort', $sortSetting);
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

        $service = new \AhgRightsHolderManage\Services\RightsHolderBrowseService($culture);

        $browseResult = $service->browse([
            'page' => $page,
            'limit' => $limit,
            'sort' => $sort,
            'sortDir' => $sortDir,
            'subquery' => $subquery,
        ]);

        $this->pager = new \AhgRightsHolderManage\SimplePager(
            $browseResult['hits'],
            $browseResult['total'],
            $browseResult['page'],
            $browseResult['limit']
        );
    }
}
