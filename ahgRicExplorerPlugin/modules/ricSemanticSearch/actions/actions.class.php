<?php

class ricSemanticSearchActions extends sfActions
{
    public function executeIndex(sfWebRequest $request)
    {
        $this->searchApiUrl = sfConfig::get('app_ric_search_api', 'http://localhost:5001/api');
        $this->atomBaseUrl = sfConfig::get('app_siteBaseUrl', '');
    }
}
