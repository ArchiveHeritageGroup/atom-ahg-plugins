<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Serial subscriptions — browse and search.
 *
 * Displays subscription list with search/filter, plus renewal alerts.
 */
class serialIndexAction extends AhgController
{
    public function execute($request)
    {
        
        // Load framework
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';

        // Load SerialService
        $servicePath = \sfConfig::get('sf_plugins_dir')
            . '/ahgLibraryPlugin/lib/Service/SerialService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        // Flash messages
        $this->notice = $this->getUser()->getFlash('notice');
        $this->error = $this->getUser()->getFlash('error');

        // Search/filter params
        $params = [
            'q'                   => $request->getParameter('q', ''),
            'subscription_status' => $request->getParameter('subscription_status', ''),
            'page'                => $request->getParameter('page', 1),
        ];

        $this->q = $params['q'];
        $this->subscriptionStatus = $params['subscription_status'];

        try {
            if (!class_exists('SerialService')) {
                throw new \RuntimeException('SerialService not available.');
            }

            $service = SerialService::getInstance();

            // List subscriptions
            $result = $service->listSubscriptions($params);

            $this->results    = $result['items'];
            $this->total      = $result['total'];
            $this->page       = $result['page'];
            $this->totalPages = $result['pages'];

            // Renewals due in 30 days
            $this->renewalsDue = $service->getDueForRenewal(30);
        } catch (\Exception $e) {
            $this->results    = [];
            $this->total      = 0;
            $this->page       = 1;
            $this->totalPages = 0;
            $this->renewalsDue = [];
            $this->error = $e->getMessage();
        }
    }
}
