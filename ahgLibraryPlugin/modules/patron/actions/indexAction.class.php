<?php

use AtomFramework\Http\Controllers\AhgController;

class patronIndexAction extends AhgController
{
    public function execute($request)
    {
        // Load framework
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';

        // Load PatronService
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/PatronService.php';

        // Search parameters
        $this->q = $request->getParameter('q', '');
        $this->patronType = $request->getParameter('patron_type', '');
        $this->borrowingStatus = $request->getParameter('borrowing_status', '');
        $this->page = max(1, (int) $request->getParameter('page', 1));

        $service = PatronService::getInstance();

        $result = $service->search([
            'q'                => $this->q,
            'patron_type'      => $this->patronType,
            'borrowing_status' => $this->borrowingStatus,
            'page'             => $this->page,
            'limit'            => 25,
        ]);

        $this->results = $result['items'];
        $this->total = $result['total'];
        $this->totalPages = $result['pages'];

        // Get current checkout counts for each patron
        $db = \Illuminate\Database\Capsule\Manager::connection();
        $this->checkoutCounts = [];
        foreach ($this->results as $patron) {
            $this->checkoutCounts[$patron->id] = $db->table('library_checkout')
                ->where('patron_id', $patron->id)
                ->where('checkout_status', 'checked_out')
                ->count();
        }

        // Load patron type options from ahg_dropdown
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgCorePlugin/lib/Services/AhgTaxonomyService.php';
        $taxonomyService = new \ahgCorePlugin\Services\AhgTaxonomyService();
        $this->patronTypes = $taxonomyService->getTermsAsChoices('patron_type');
    }
}
