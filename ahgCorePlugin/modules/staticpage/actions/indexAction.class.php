<?php

/**
 * AHG stub for staticpage/index action.
 * Replaces apps/qubit/modules/staticpage/actions/indexAction.class.php.
 *
 * Loads and displays a static page with purified, cached content.
 */
class StaticPageIndexAction extends sfAction
{
    public function execute($request)
    {
        $this->resource = $this->getRoute()->resource;

        if (1 > strlen($title = $this->resource->__toString())) {
            $title = $this->context->i18n->__('Untitled');
        }

        $this->response->setTitle("{$title} - {$this->response->getTitle()}");

        $this->content = $this->getPurifiedStaticPageContent();

        if (sfConfig::get('app_enable_institutional_scoping') && 'home' == $this->resource->slug) {
            // Remove the search-realm attribute
            $this->context->user->removeAttribute('search-realm');
        }
    }

    protected function getPurifiedStaticPageContent()
    {
        $culture = sfContext::getInstance()->getUser()->getCulture();
        $cacheKey = 'staticpage:'.$this->resource->id.':'.$culture;
        $cache = QubitCache::getInstance();

        if (null === $cache) {
            return;
        }

        if ($cache->has($cacheKey)) {
            return $cache->get($cacheKey);
        }

        $content = $this->resource->getContent(['cultureFallback' => true]);
        $content = QubitHtmlPurifier::getInstance()->purify($content);

        $cache->set($cacheKey, $content);

        return $content;
    }
}
