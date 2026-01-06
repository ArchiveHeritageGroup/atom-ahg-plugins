<?php

class donorAgreementBrowseAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';

        $service = new \AtomFramework\Services\DonorAgreement\DonorAgreementService();

        $filters = [
            'status' => $request->getParameter('status'),
            'type' => $request->getParameter('type'),
            'donor_id' => $request->getParameter('donor'),
            'repository_id' => $request->getParameter('repository'),
            'search' => $request->getParameter('q'),
            'expiring' => $request->getParameter('expiring'),
        ];

        $page = max(1, (int) $request->getParameter('page', 1));

        $this->result = $service->browse(array_filter($filters), $page);
        $this->agreements = $this->result['data'] ?? [];
        $this->filters = $filters;
        $this->statuses = $service->getStatuses();
        $this->types = $service->getTypes();
    }
}
