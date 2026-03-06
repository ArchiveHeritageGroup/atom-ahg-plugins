<?php

/**
 * AHG stub for staticpage/delete action.
 * Replaces apps/qubit/modules/staticpage/actions/deleteAction.class.php.
 */
class StaticPageDeleteAction extends sfAction
{
    public function execute($request)
    {
        $this->form = new sfForm();

        $this->resource = $this->getRoute()->resource;

        // Check user authorization
        if ($this->resource->isProtected()) {
            QubitAcl::forwardUnauthorized();
        }

        if ($request->isMethod('delete')) {
            $this->form->bind($request->getPostParameters());

            if ($this->form->isValid()) {
                $this->resource->delete();

                // Invalidate static page content cache entry
                if (null !== $cache = QubitCache::getInstance()) {
                    foreach (sfConfig::get('app_i18n_languages') as $culture) {
                        $cacheKey = 'staticpage:'.$this->resource->id.':'.$culture;
                        $cache->remove($cacheKey);
                    }
                }

                $this->redirect(['module' => 'staticpage', 'action' => 'list']);
            }
        }
    }
}
