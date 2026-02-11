<?php

use AtomFramework\Http\Controllers\AhgController;
class ricSemanticSearchActions extends AhgController
{
    public function executeIndex($request)
    {
        $this->searchApiUrl = $this->config('app_ric_search_api', 'http://localhost:5001/api');
        $this->atomBaseUrl = $this->config('app_siteBaseUrl', '');
    }
}
