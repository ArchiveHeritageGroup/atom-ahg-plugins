<?php

/**
 * Google Tag Manager stub.
 * Renders GTM script/noscript snippets if container ID is configured.
 */
class DefaultTagManagerComponent extends sfComponent
{
    public function execute($request)
    {
        $config = sfConfig::get('app_google_tag_manager_container_id', '');
        if (empty($config) || !isset($this->code) || empty($this->code)) {
            return sfView::NONE;
        }
        $this->containerId = $config;
    }
}
