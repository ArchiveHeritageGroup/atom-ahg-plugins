<?php

use AtomFramework\Http\Controllers\AhgController;

class patronEditAction extends AhgController
{
    public function execute($request)
    {
        // Load framework
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';

        // Load PatronService
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/PatronService.php';

        // Load patron type options from ahg_dropdown
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgCorePlugin/lib/Services/AhgTaxonomyService.php';
        $taxonomyService = new \ahgCorePlugin\Services\AhgTaxonomyService();
        $this->patronTypes = $taxonomyService->getDropdownValues('patron_type');

        $service = PatronService::getInstance();
        $id = (int) $request->getParameter('id');

        // Load existing patron if editing
        $this->patron = null;
        if ($id) {
            $this->patron = $service->find($id);
            if (!$this->patron) {
                $this->forward404();
            }
        }

        // Handle POST
        if ($request->isMethod('post')) {
            $firstName = trim($request->getParameter('first_name', ''));
            $lastName = trim($request->getParameter('last_name', ''));

            // Validate required fields
            if (empty($firstName) || empty($lastName)) {
                $this->getUser()->setFlash('error', __('First name and last name are required.'));

                return sfView::SUCCESS;
            }

            $data = [
                'first_name'    => $firstName,
                'last_name'     => $lastName,
                'email'         => $request->getParameter('email') ?: null,
                'phone'         => $request->getParameter('phone') ?: null,
                'patron_type'   => $request->getParameter('patron_type', 'general'),
                'max_checkouts' => (int) $request->getParameter('max_checkouts', 5),
                'max_holds'     => (int) $request->getParameter('max_holds', 3),
                'expiry_date'   => $request->getParameter('expiry_date') ?: null,
                'notes'         => $request->getParameter('notes') ?: null,
            ];

            if ($this->patron) {
                $service->update($id, $data);
                $this->getUser()->setFlash('notice', __('Patron updated successfully.'));
                $this->redirect(['module' => 'patron', 'action' => 'view', 'id' => $id]);
            } else {
                $newId = $service->create($data);
                $this->getUser()->setFlash('notice', __('Patron created successfully.'));
                $this->redirect(['module' => 'patron', 'action' => 'view', 'id' => $newId]);
            }
        }
    }
}
