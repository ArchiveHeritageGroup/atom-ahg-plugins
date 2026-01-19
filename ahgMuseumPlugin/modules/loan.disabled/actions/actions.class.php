<?php

/**
 * Loan module actions.
 *
 * Handles loan management including browsing, creating, editing,
 * and managing loan objects, documents, and workflow transitions.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class loanActions extends sfActions
{
    /**
     * Browse/list loans.
     */
    public function executeIndex(sfWebRequest $request)
    {
        $service = $this->getLoanService();

        // Get filters from request
        $filters = [];
        if ($type = $request->getParameter('type')) {
            $filters['loan_type'] = $type;
        }
        if ($status = $request->getParameter('status')) {
            $filters['status'] = $status;
        }
        if ($partner = $request->getParameter('partner')) {
            $filters['partner'] = $partner;
        }
        if ($overdue = $request->getParameter('overdue')) {
            $filters['overdue'] = true;
        }
        if ($search = $request->getParameter('search')) {
            $filters['search'] = $search;
        }

        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $result = $service->search($filters, $limit, $offset);

        $this->loans = $result['results'];
        $this->total = $result['total'];
        $this->page = $page;
        $this->pages = ceil($result['total'] / $limit);
        $this->filters = $filters;

        // Statistics for sidebar
        $this->stats = $service->getStatistics();
        $this->overdue = $service->getOverdue();
        $this->dueSoon = $service->getDueSoon(30);

        // For dropdowns
        $this->purposes = $service->getPurposes();
        $this->insuranceTypes = $service->getInsuranceTypes();
    }

    /**
     * Show loan details.
     */
    public function executeShow(sfWebRequest $request)
    {
        $service = $this->getLoanService();

        $id = $request->getParameter('id');
        $this->loan = $service->get((int) $id);

        if (!$this->loan) {
            $this->forward404('Loan not found');
        }

        // Get workflow transitions
        if (!empty($this->loan['workflow'])) {
            $workflowService = $this->getWorkflowEngine();
            $this->availableTransitions = $workflowService->getAvailableTransitions($this->loan['workflow']['id']);
        } else {
            $this->availableTransitions = [];
        }

        // For dropdowns
        $this->purposes = $service->getPurposes();
        $this->insuranceTypes = $service->getInsuranceTypes();
    }

    /**
     * Create new loan form.
     */
    public function executeAdd(sfWebRequest $request)
    {
        $service = $this->getLoanService();

        $this->loanType = $request->getParameter('type', 'out');
        $this->purposes = $service->getPurposes();
        $this->insuranceTypes = $service->getInsuranceTypes();

        // Pre-populate from exhibition if specified
        $this->exhibitionId = $request->getParameter('exhibition_id');
        $this->exhibition = null;
        if ($this->exhibitionId) {
            $exhibitionService = $this->getExhibitionService();
            $this->exhibition = $exhibitionService->get((int) $this->exhibitionId);
        }

        if ($request->isMethod('post')) {
            $userId = $this->getUser()->getAttribute('user_id', 1);

            $data = [
                'title' => $request->getParameter('title'),
                'description' => $request->getParameter('description'),
                'purpose' => $request->getParameter('purpose'),
                'partner_institution' => $request->getParameter('partner_institution'),
                'partner_contact_name' => $request->getParameter('partner_contact_name'),
                'partner_contact_email' => $request->getParameter('partner_contact_email'),
                'partner_contact_phone' => $request->getParameter('partner_contact_phone'),
                'partner_address' => $request->getParameter('partner_address'),
                'start_date' => $request->getParameter('start_date') ?: null,
                'end_date' => $request->getParameter('end_date') ?: null,
                'insurance_type' => $request->getParameter('insurance_type'),
                'insurance_value' => $request->getParameter('insurance_value') ?: null,
                'insurance_currency' => $request->getParameter('insurance_currency') ?: 'ZAR',
                'insurance_provider' => $request->getParameter('insurance_provider'),
                'loan_fee' => $request->getParameter('loan_fee') ?: null,
                'notes' => $request->getParameter('notes'),
            ];

            try {
                $loanId = $service->create($this->loanType, $data, $userId);
                $this->getUser()->setFlash('notice', 'Loan created successfully');
                $this->redirect(['module' => 'loan', 'action' => 'show', 'id' => $loanId]);
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
            }
        }
    }

    /**
     * Edit loan.
     */
    public function executeEdit(sfWebRequest $request)
    {
        $service = $this->getLoanService();

        $id = (int) $request->getParameter('id');
        $this->loan = $service->get($id);

        if (!$this->loan) {
            $this->forward404('Loan not found');
        }

        $this->purposes = $service->getPurposes();
        $this->insuranceTypes = $service->getInsuranceTypes();

        if ($request->isMethod('post')) {
            $data = [
                'title' => $request->getParameter('title'),
                'description' => $request->getParameter('description'),
                'purpose' => $request->getParameter('purpose'),
                'partner_institution' => $request->getParameter('partner_institution'),
                'partner_contact_name' => $request->getParameter('partner_contact_name'),
                'partner_contact_email' => $request->getParameter('partner_contact_email'),
                'partner_contact_phone' => $request->getParameter('partner_contact_phone'),
                'partner_address' => $request->getParameter('partner_address'),
                'start_date' => $request->getParameter('start_date') ?: null,
                'end_date' => $request->getParameter('end_date') ?: null,
                'insurance_type' => $request->getParameter('insurance_type'),
                'insurance_value' => $request->getParameter('insurance_value') ?: null,
                'insurance_currency' => $request->getParameter('insurance_currency') ?: 'ZAR',
                'insurance_provider' => $request->getParameter('insurance_provider'),
                'loan_fee' => $request->getParameter('loan_fee') ?: null,
                'notes' => $request->getParameter('notes'),
            ];

            try {
                $service->update($id, $data);
                $this->getUser()->setFlash('notice', 'Loan updated successfully');
                $this->redirect(['module' => 'loan', 'action' => 'show', 'id' => $id]);
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
            }
        }
    }

    /**
     * Add object to loan.
     */
    public function executeAddObject(sfWebRequest $request)
    {
        $service = $this->getLoanService();

        $loanId = (int) $request->getParameter('id');

        if ($request->isMethod('post')) {
            $objectData = [
                'information_object_id' => (int) $request->getParameter('information_object_id'),
                'object_title' => $request->getParameter('object_title'),
                'object_identifier' => $request->getParameter('object_identifier'),
                'insurance_value' => $request->getParameter('insurance_value') ?: null,
                'special_requirements' => $request->getParameter('special_requirements'),
                'display_requirements' => $request->getParameter('display_requirements'),
            ];

            try {
                $service->addObject($loanId, $objectData);
                $this->getUser()->setFlash('notice', 'Object added to loan');
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
            }
        }

        $this->redirect(['module' => 'loan', 'action' => 'show', 'id' => $loanId]);
    }

    /**
     * Remove object from loan.
     */
    public function executeRemoveObject(sfWebRequest $request)
    {
        $service = $this->getLoanService();

        $loanId = (int) $request->getParameter('id');
        $objectId = (int) $request->getParameter('object_id');

        try {
            $service->removeObject($loanId, $objectId);
            $this->getUser()->setFlash('notice', 'Object removed from loan');
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
        }

        $this->redirect(['module' => 'loan', 'action' => 'show', 'id' => $loanId]);
    }

    /**
     * Transition loan workflow state.
     */
    public function executeTransition(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $service = $this->getLoanService();

        $loanId = (int) $request->getParameter('id');
        $transition = $request->getParameter('transition');
        $comment = $request->getParameter('comment');
        $userId = $this->getUser()->getAttribute('user_id', 1);

        try {
            $result = $service->transition($loanId, $transition, $userId, $comment);

            return $this->renderText(json_encode([
                'success' => true,
                'new_state' => $result['current_state'],
            ]));
        } catch (\Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * Extend loan period.
     */
    public function executeExtend(sfWebRequest $request)
    {
        $service = $this->getLoanService();

        $loanId = (int) $request->getParameter('id');

        if ($request->isMethod('post')) {
            $newEndDate = $request->getParameter('new_end_date');
            $reason = $request->getParameter('reason');
            $userId = $this->getUser()->getAttribute('user_id', 1);

            try {
                $service->extend($loanId, $newEndDate, $reason, $userId);
                $this->getUser()->setFlash('notice', 'Loan extended successfully');
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
            }
        }

        $this->redirect(['module' => 'loan', 'action' => 'show', 'id' => $loanId]);
    }

    /**
     * Record loan return.
     */
    public function executeReturn(sfWebRequest $request)
    {
        $service = $this->getLoanService();

        $loanId = (int) $request->getParameter('id');

        if ($request->isMethod('post')) {
            $returnDate = $request->getParameter('return_date') ?: date('Y-m-d');
            $notes = $request->getParameter('notes');
            $userId = $this->getUser()->getAttribute('user_id', 1);

            try {
                $service->recordReturn($loanId, $returnDate, $notes, $userId);
                $this->getUser()->setFlash('notice', 'Return recorded successfully');
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
            }
        }

        $this->redirect(['module' => 'loan', 'action' => 'show', 'id' => $loanId]);
    }

    /**
     * Generate loan agreement document.
     */
    public function executeAgreement(sfWebRequest $request)
    {
        $loanId = (int) $request->getParameter('id');
        $format = $request->getParameter('format', 'html');
        $template = $request->getParameter('template', 'standard');

        $service = $this->getLoanService();
        $loan = $service->get($loanId);

        if (!$loan) {
            $this->forward404('Loan not found');
        }

        $generator = $this->getLoanAgreementGenerator();

        // Set institution details from settings
        $generator->setInstitution([
            'name' => sfConfig::get('app_institution_name', 'The Archive and Heritage Group'),
            'address' => sfConfig::get('app_institution_address', ''),
            'phone' => sfConfig::get('app_institution_phone', ''),
            'email' => sfConfig::get('app_institution_email', ''),
        ]);

        try {
            $content = $generator->generate($loanId, $template, $format);

            if ('pdf' === $format) {
                $this->getResponse()->setContentType('application/pdf');
                $this->getResponse()->setHttpHeader('Content-Disposition', 'inline; filename="loan_agreement_'.$loan['loan_number'].'.pdf"');
            } else {
                $this->getResponse()->setContentType('text/html');
            }

            return $this->renderText($content);
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Error generating agreement: '.$e->getMessage());
            $this->redirect(['module' => 'loan', 'action' => 'show', 'id' => $loanId]);
        }
    }

    /**
     * Upload document to loan.
     */
    public function executeUploadDocument(sfWebRequest $request)
    {
        $service = $this->getLoanService();

        $loanId = (int) $request->getParameter('id');
        $loan = $service->get($loanId);

        if (!$loan) {
            $this->forward404('Loan not found');
        }

        if ($request->isMethod('post') && $request->hasFile('document')) {
            $file = $request->getFiles('document');
            $documentType = $request->getParameter('document_type', 'other');
            $description = $request->getParameter('description');

            $uploadDir = sfConfig::get('sf_upload_dir').'/loan_documents/'.$loanId;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = time().'_'.preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
            $filePath = $uploadDir.'/'.$fileName;

            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                try {
                    $service->addDocument($loanId, $documentType, $filePath, [
                        'file_name' => $file['name'],
                        'mime_type' => $file['type'],
                        'file_size' => $file['size'],
                        'description' => $description,
                        'uploaded_by' => $this->getUser()->getAttribute('user_id', 1),
                    ]);
                    $this->getUser()->setFlash('notice', 'Document uploaded successfully');
                } catch (\Exception $e) {
                    $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
                }
            } else {
                $this->getUser()->setFlash('error', 'Failed to upload file');
            }
        }

        $this->redirect(['module' => 'loan', 'action' => 'show', 'id' => $loanId]);
    }

    /**
     * Search objects for adding to loan (AJAX).
     */
    public function executeSearchObjects(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $query = $request->getParameter('q', '');
        if (strlen($query) < 2) {
            return $this->renderText(json_encode(['objects' => []]));
        }

        $db = $this->getDatabase();

        $objects = $db->table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('ioi.id', '=', 'io.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->where(function ($q) use ($query) {
                $q->where('ioi.title', 'LIKE', "%{$query}%")
                    ->orWhere('io.identifier', 'LIKE', "%{$query}%");
            })
            ->where('io.id', '>', 1)
            ->select('io.id', 'io.identifier', 'ioi.title')
            ->limit(20)
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();

        return $this->renderText(json_encode(['objects' => $objects]));
    }

    /**
     * Get loan service.
     */
    private function getLoanService()
    {
        static $service = null;

        if (null === $service) {
            require_once sfConfig::get('sf_plugins_dir').'/ahgMuseumPlugin/lib/Services/Loan/LoanService.php';

            $db = $this->getDatabase();
            $workflow = $this->getWorkflowEngine();

            $service = new \arMuseumMetadataPlugin\Services\Loan\LoanService($db, $workflow);
        }

        return $service;
    }

    /**
     * Get loan agreement generator.
     */
    private function getLoanAgreementGenerator()
    {
        require_once sfConfig::get('sf_plugins_dir').'/ahgMuseumPlugin/lib/Services/Loan/LoanAgreementGenerator.php';

        return new \arMuseumMetadataPlugin\Services\Loan\LoanAgreementGenerator(
            $this->getLoanService(),
            sfConfig::get('sf_plugins_dir').'/ahgMuseumPlugin/templates/loan'
        );
    }

    /**
     * Get exhibition service.
     */
    private function getExhibitionService()
    {
        require_once sfConfig::get('sf_plugins_dir').'/ahgMuseumPlugin/lib/Services/Exhibition/ExhibitionService.php';

        return new \AtomExtensions\Services\Exhibition\ExhibitionService();
    }

    /**
     * Get workflow engine.
     */
    private function getWorkflowEngine()
    {
        static $engine = null;

        if (null === $engine) {
            require_once sfConfig::get('sf_plugins_dir').'/ahgMuseumPlugin/lib/Services/Workflow/WorkflowEngine.php';

            $engine = new \arMuseumMetadataPlugin\Services\Workflow\WorkflowEngine($this->getDatabase());
        }

        return $engine;
    }

    /**
     * Get database connection.
     */
    private function getDatabase()
    {
        static $initialized = false;

        if (!$initialized) {
            require_once sfConfig::get('sf_root_dir').'/atom-framework/src/Database/DatabaseBootstrap.php';
            \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
            $initialized = true;
        }

        return \Illuminate\Database\Capsule\Manager::connection();
    }
}
