<?php

class privacyActions extends sfActions
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
        \AhgCore\Core\AhgDb::init();
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
                $this->redirect(['module' => 'privacy', 'action' => 'dsarConfirmation', 'id' => $id]);
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

    /**
     * Lodge Privacy Complaint (public)
     */
    public function executeComplaint(sfWebRequest $request)
    {
        \AhgCore\Core\AhgDb::init();
        require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PrivacyService.php';

        $this->complaintTypes = [
            'unauthorized_access' => 'Unauthorized access to my personal information',
            'unauthorized_disclosure' => 'Unauthorized disclosure of my personal information',
            'inaccurate_data' => 'Inaccurate personal information held',
            'failure_to_respond' => 'Failure to respond to my access request',
            'excessive_collection' => 'Excessive collection of personal information',
            'unsolicited_marketing' => 'Unsolicited direct marketing',
            'security_breach' => 'Data security breach',
            'other' => 'Other privacy concern'
        ];

        if ($request->isMethod('post')) {
            try {
                $refNum = 'CMP-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                $id = \Illuminate\Database\Capsule\Manager::table('privacy_complaint')->insertGetId([
                    'reference_number' => $refNum,
                    'jurisdiction' => $request->getParameter('jurisdiction', 'popia'),
                    'complainant_name' => $request->getParameter('complainant_name'),
                    'complainant_email' => $request->getParameter('complainant_email'),
                    'complainant_phone' => $request->getParameter('complainant_phone'),
                    'complaint_type' => $request->getParameter('complaint_type'),
                    'description' => $request->getParameter('description'),
                    'date_of_incident' => $request->getParameter('date_of_incident') ?: null,
                    'status' => 'received',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                $this->getUser()->setFlash('success', 'Your complaint has been submitted. Reference: ' . $refNum);
                $this->redirect(['module' => 'privacy', 'action' => 'complaintConfirmation', 'id' => $id]);
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Failed to submit complaint: ' . $e->getMessage());
            }
        }
    }

    /**
     * Complaint Confirmation
     */
    public function executeComplaintConfirmation(sfWebRequest $request)
    {
        \AhgCore\Core\AhgDb::init();

        $this->complaint = \Illuminate\Database\Capsule\Manager::table('privacy_complaint')
            ->where('id', $request->getParameter('id'))
            ->first();

        if (!$this->complaint) {
            $this->forward404();
        }
    }
}