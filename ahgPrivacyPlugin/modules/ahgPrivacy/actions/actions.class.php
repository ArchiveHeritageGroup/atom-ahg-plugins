<?php

class ahgPrivacyActions extends sfActions
{
    protected function getService(): \ahgPrivacyPlugin\Service\PrivacyService
    {
        require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PrivacyService.php';
        return new \ahgPrivacyPlugin\Service\PrivacyService();
    }

    /**
     * Privacy Notice / Policy Page
     */
    public function executeIndex(sfWebRequest $request)
    {
        $service = $this->getService();
        $this->config = $service->getConfig('popia');
        $this->officers = $service->getOfficers('popia');
    }

    /**
     * Submit DSAR Request (public form)
     */
    public function executeDsarRequest(sfWebRequest $request)
    {
        // Bootstrap and require service
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PrivacyService.php';
        $this->requestTypes = \ahgPrivacyPlugin\Service\PrivacyService::getRequestTypes();

        if ($request->isMethod('post')) {
            $service = $this->getService();
            
            try {
                $id = $service->createDsar([
                    'jurisdiction' => 'popia',
                    'request_type' => $request->getParameter('request_type'),
                    'requestor_name' => $request->getParameter('requestor_name'),
                    'requestor_email' => $request->getParameter('requestor_email'),
                    'requestor_phone' => $request->getParameter('requestor_phone'),
                    'requestor_id_type' => $request->getParameter('requestor_id_type'),
                    'requestor_id_number' => $request->getParameter('requestor_id_number'),
                    'description' => $request->getParameter('description'),
                    'received_date' => date('Y-m-d')
                ]);

                $this->getUser()->setFlash('success', 'Your request has been submitted. Reference: DSAR-' . date('Ym') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT));
                $this->redirect(['module' => 'ahgPrivacy', 'action' => 'dsarConfirmation', 'id' => $id]);
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Failed to submit request: ' . $e->getMessage());
            }
        }
    }

    /**
     * DSAR Confirmation page
     */
    public function executeDsarConfirmation(sfWebRequest $request)
    {
        $service = $this->getService();
        $this->dsar = $service->getDsar($request->getParameter('id'));
        
        if (!$this->dsar) {
            $this->forward404();
        }
    }

    /**
     * Check DSAR Status (public)
     */
    public function executeDsarStatus(sfWebRequest $request)
    {
        $reference = $request->getParameter('reference');
        $email = $request->getParameter('email');

        if ($reference && $email) {
            $result = \Illuminate\Database\Capsule\Manager::table('privacy_dsar')
                ->where('reference_number', $reference)
                ->where('requestor_email', $email)
                ->first();

            if ($result) {
                $this->dsar = $result;
            } else {
                $this->getUser()->setFlash('error', 'No request found with that reference and email.');
            }
        }
    }
}
