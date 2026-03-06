<?php

/**
 * AHG stub for staticpage/list action.
 * Replaces apps/qubit/modules/staticpage/actions/listAction.class.php.
 *
 * Paginated list of static pages.
 */
class StaticPageListAction extends sfAction
{
    public function execute($request)
    {
        $title = $this->context->i18n->__('Static pages');
        $this->response->setTitle("{$title} - {$this->response->getTitle()}");

        if (!isset($request->limit)) {
            $request->limit = sfConfig::get('app_hits_per_page');
        }

        $criteria = new Criteria();

        // Page results
        $this->pager = new QubitPager('QubitStaticPage');
        $this->pager->setCriteria($criteria);
        $this->pager->setMaxPerPage($request->limit);
        $this->pager->setPage($request->page);
    }
}
