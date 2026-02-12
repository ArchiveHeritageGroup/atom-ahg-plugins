<?php

use AtomFramework\Http\Controllers\AhgController;

class graphqlPlaygroundAction extends AhgController
{
    public function execute($request)
    {
        // Only allow in dev/debug mode
        $isDebug = sfConfig::get('sf_debug', false) || sfConfig::get('sf_environment') === 'dev';

        if (!$isDebug) {
            $this->response->setStatusCode(404);
            $this->response->setContent('Not Found');

            return sfView::NONE;
        }

        $this->setTemplate('playground');

        return sfView::SUCCESS;
    }
}
