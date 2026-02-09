<?php

/**
 * Shared Loan module actions.
 *
 * Handles loan management for all GLAM sectors.
 * Self-contained - does not depend on ahgMuseumPlugin.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class loanActions extends AhgActions
{
    /**
     * Browse/list loans.
     */
    public function executeIndex(sfWebRequest $request)
    {
        $service = $this->getLoanService();
        $sector = $request->getParameter('sector', 'museum');
        $objectId = $request->getParameter('object_id');

        $filters = ['sector' => $sector];
        if ($objectId) {
            $filters['object_id'] = $objectId;
        }
        if ($type = $request->getParameter('type')) {
            $filters['loan_type'] = $type;
        }
        if ($status = $request->getParameter('status')) {
            $filters['status'] = $status;
        }
        if ($request->getParameter('overdue')) {
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
        $this->sector = $sector;
        $this->objectId = $objectId;
        $this->sectorName = $this->getSectorName($sector);

        $this->stats = $service->getStatistics($sector);
        $this->purposes = $this->getPurposes($sector);
        $this->insuranceTypes = $this->getInsuranceTypes();
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

        $this->sector = $this->loan['sector'] ?? 'museum';
        $this->sectorName = $this->getSectorName($this->sector);
        $this->purposes = $this->getPurposes($this->sector);
        $this->insuranceTypes = $this->getInsuranceTypes();
    }

    /**
     * Create new loan form.
     */
    public function executeAdd(sfWebRequest $request)
    {
        $service = $this->getLoanService();
        $sector = $request->getParameter('sector', 'museum');
        $objectId = $request->getParameter('object_id');

        $this->loanType = $request->getParameter('type', 'out');
        $this->sector = $sector;
        $this->objectId = $objectId;
        $this->sectorName = $this->getSectorName($sector);
        $this->purposes = $this->getPurposes($sector);
        $this->insuranceTypes = $this->getInsuranceTypes();

        // Pre-populate object info if provided
        $this->object = null;
        if ($objectId) {
            $db = $this->getDatabase();
            $this->object = $db->table('information_object as io')
                ->leftJoin('information_object_i18n as ioi', function ($join) {
                    $join->on('ioi.id', '=', 'io.id')->where('ioi.culture', '=', 'en');
                })
                ->where('io.id', '=', $objectId)
                ->select('io.id', 'io.identifier', 'ioi.title')
                ->first();
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
                'sector' => $sector,
            ];

            try {
                $loanId = $service->create($this->loanType, $data, $userId);

                // Add object if specified
                if ($objectId && $this->object) {
                    $service->addObject($loanId, [
                        'information_object_id' => $objectId,
                        'object_title' => $this->object->title ?? '',
                        'object_identifier' => $this->object->identifier ?? '',
                    ]);
                }

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

        $this->sector = $this->loan['sector'] ?? 'museum';
        $this->sectorName = $this->getSectorName($this->sector);
        $this->purposes = $this->getPurposes($this->sector);
        $this->insuranceTypes = $this->getInsuranceTypes();

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
     * Generate loan agreement document.
     */
    public function executeAgreement(sfWebRequest $request)
    {
        $service = $this->getLoanService();

        $id = (int) $request->getParameter('id');
        $loan = $service->get($id);

        if (!$loan) {
            $this->forward404('Loan not found');
        }

        // Load the agreement generator
        require_once sfConfig::get('sf_plugins_dir').'/ahgLoanPlugin/lib/Services/Loan/LoanAgreementGenerator.php';
        $generator = new \AhgLoan\Services\Loan\LoanAgreementGenerator($service);

        // Set institution details from settings if available
        $generator->setInstitution([
            'name' => sfConfig::get('app_siteTitle', 'The Archive and Heritage Group'),
            'address' => sfConfig::get('app_siteAddress', ''),
            'phone' => sfConfig::get('app_sitePhone', ''),
            'email' => sfConfig::get('app_siteEmail', ''),
        ]);

        $format = $request->getParameter('format', 'html');
        $template = $request->getParameter('template', 'standard');

        try {
            $content = $generator->generate($id, $template, $format);

            if ('pdf' === $format) {
                $this->getResponse()->setContentType('application/pdf');
                $this->getResponse()->setHttpHeader('Content-Disposition', 'inline; filename="loan-agreement-'.$loan['loan_number'].'.pdf"');
            } else {
                $this->getResponse()->setContentType('text/html; charset=utf-8');
            }

            echo $content;

            return sfView::NONE;
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Error generating agreement: '.$e->getMessage());
            $this->redirect(['module' => 'loan', 'action' => 'show', 'id' => $id]);
        }
    }

    /**
     * Extend loan period.
     */
    public function executeExtend(sfWebRequest $request)
    {
        $service = $this->getLoanService();
        $id = (int) $request->getParameter('id');
        $loan = $service->get($id);

        if (!$loan) {
            $this->forward404('Loan not found');
        }

        if ($request->isMethod('post')) {
            $newEndDate = $request->getParameter('new_end_date');
            $reason = $request->getParameter('reason');

            if (!$newEndDate) {
                $this->getUser()->setFlash('error', 'New end date is required');
                $this->redirect(['module' => 'loan', 'action' => 'show', 'id' => $id]);
            }

            try {
                $service->update($id, ['end_date' => $newEndDate]);

                // Log the extension
                $db = $this->getDatabase();
                $db->table('ahg_loan_history')->insert([
                    'loan_id' => $id,
                    'action' => 'extended',
                    'details' => json_encode([
                        'previous_end_date' => $loan['end_date'],
                        'new_end_date' => $newEndDate,
                        'reason' => $reason,
                    ]),
                    'user_id' => $this->getUser()->getAttribute('user_id', 1),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                $this->getUser()->setFlash('notice', 'Loan period extended successfully');
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
            }
        }

        $this->redirect(['module' => 'loan', 'action' => 'show', 'id' => $id]);
    }

    /**
     * Record loan return.
     */
    public function executeReturn(sfWebRequest $request)
    {
        $service = $this->getLoanService();
        $id = (int) $request->getParameter('id');
        $loan = $service->get($id);

        if (!$loan) {
            $this->forward404('Loan not found');
        }

        if ($request->isMethod('post')) {
            $returnDate = $request->getParameter('return_date');
            $notes = $request->getParameter('notes');

            try {
                $service->update($id, [
                    'return_date' => $returnDate,
                    'status' => 'returned',
                ]);

                // Log the return
                $db = $this->getDatabase();
                $db->table('ahg_loan_history')->insert([
                    'loan_id' => $id,
                    'action' => 'returned',
                    'details' => json_encode([
                        'return_date' => $returnDate,
                        'notes' => $notes,
                    ]),
                    'user_id' => $this->getUser()->getAttribute('user_id', 1),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                $this->getUser()->setFlash('notice', 'Return recorded successfully');
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
            }
        }

        $this->redirect(['module' => 'loan', 'action' => 'show', 'id' => $id]);
    }

    /**
     * Add object to loan.
     */
    public function executeAddObject(sfWebRequest $request)
    {
        $service = $this->getLoanService();
        $id = (int) $request->getParameter('id');

        if (!$service->get($id)) {
            $this->forward404('Loan not found');
        }

        if ($request->isMethod('post')) {
            $objectId = $request->getParameter('information_object_id');

            if (!$objectId) {
                $this->getUser()->setFlash('error', 'Please select an object');
                $this->redirect(['module' => 'loan', 'action' => 'show', 'id' => $id]);
            }

            try {
                $service->addObject($id, [
                    'information_object_id' => (int) $objectId,
                    'object_title' => $request->getParameter('object_title', ''),
                    'object_identifier' => $request->getParameter('object_identifier', ''),
                    'insurance_value' => $request->getParameter('insurance_value') ?: null,
                    'special_requirements' => $request->getParameter('special_requirements'),
                    'display_requirements' => $request->getParameter('display_requirements'),
                ]);

                $this->getUser()->setFlash('notice', 'Object added to loan');
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
            }
        }

        $this->redirect(['module' => 'loan', 'action' => 'show', 'id' => $id]);
    }

    /**
     * Remove object from loan.
     */
    public function executeRemoveObject(sfWebRequest $request)
    {
        $service = $this->getLoanService();
        $loanId = (int) $request->getParameter('id');

        if (!$service->get($loanId)) {
            $this->forward404('Loan not found');
        }

        if ($request->isMethod('post')) {
            $objectId = (int) $request->getParameter('object_id');

            try {
                $service->removeObject($loanId, $objectId);
                $this->getUser()->setFlash('notice', 'Object removed from loan');
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
            }
        }

        $this->redirect(['module' => 'loan', 'action' => 'show', 'id' => $loanId]);
    }

    /**
     * Upload document to loan.
     */
    public function executeUploadDocument(sfWebRequest $request)
    {
        $service = $this->getLoanService();
        $id = (int) $request->getParameter('id');
        $loan = $service->get($id);

        if (!$loan) {
            $this->forward404('Loan not found');
        }

        if ($request->isMethod('post') && $request->hasFile('document')) {
            $file = $request->getFile('document');

            if ($file['error'] === UPLOAD_ERR_OK) {
                try {
                    $uploadDir = sfConfig::get('sf_upload_dir').'/loans/'.$id;
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $filename = time().'_'.preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
                    $filepath = $uploadDir.'/'.$filename;

                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        $db = $this->getDatabase();
                        $db->table('ahg_loan_documents')->insert([
                            'loan_id' => $id,
                            'document_type' => $request->getParameter('document_type'),
                            'filename' => $filename,
                            'original_name' => $file['name'],
                            'file_path' => $filepath,
                            'file_size' => $file['size'],
                            'mime_type' => $file['type'],
                            'description' => $request->getParameter('description'),
                            'uploaded_by' => $this->getUser()->getAttribute('user_id', 1),
                            'created_at' => date('Y-m-d H:i:s'),
                        ]);

                        $this->getUser()->setFlash('notice', 'Document uploaded successfully');
                    } else {
                        $this->getUser()->setFlash('error', 'Failed to move uploaded file');
                    }
                } catch (\Exception $e) {
                    $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
                }
            } else {
                $this->getUser()->setFlash('error', 'Upload error: '.$file['error']);
            }
        }

        $this->redirect(['module' => 'loan', 'action' => 'show', 'id' => $id]);
    }

    /**
     * Search objects (AJAX).
     */
    public function executeSearchObjects(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $query = $request->getParameter('q', '');
        $results = ['objects' => []];

        if (strlen($query) >= 2) {
            $db = $this->getDatabase();
            $objects = $db->table('information_object as io')
                ->leftJoin('information_object_i18n as ioi', function ($join) {
                    $join->on('ioi.id', '=', 'io.id')->where('ioi.culture', '=', 'en');
                })
                ->where(function ($q) use ($query) {
                    $q->where('io.identifier', 'LIKE', "%{$query}%")
                      ->orWhere('ioi.title', 'LIKE', "%{$query}%");
                })
                ->whereNotNull('io.identifier')
                ->select('io.id', 'io.identifier', 'ioi.title')
                ->limit(20)
                ->get();

            foreach ($objects as $obj) {
                $results['objects'][] = [
                    'id' => $obj->id,
                    'identifier' => $obj->identifier,
                    'title' => $obj->title,
                ];
            }
        }

        echo json_encode($results);

        return sfView::NONE;
    }

    /**
     * Workflow transition (AJAX).
     */
    public function executeTransition(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $id = (int) $request->getParameter('id');
        $transition = $request->getParameter('transition');
        $comment = $request->getParameter('comment', '');

        $result = ['success' => false];

        try {
            $service = $this->getLoanService();
            $loan = $service->get($id);

            if (!$loan) {
                $result['error'] = 'Loan not found';
                echo json_encode($result);

                return sfView::NONE;
            }

            // Perform the transition
            $service->transition($id, $transition, $this->getUser()->getAttribute('user_id', 1), $comment);
            $result['success'] = true;
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        echo json_encode($result);

        return sfView::NONE;
    }

    /**
     * Get human-readable sector name.
     */
    private function getSectorName(string $sector): string
    {
        return [
            'museum' => 'Museum',
            'gallery' => 'Gallery',
            'archive' => 'Archive',
            'library' => 'Library',
            'dam' => 'Digital Assets',
        ][$sector] ?? ucfirst($sector);
    }

    /**
     * Get purposes for sector.
     */
    private function getPurposes(string $sector): array
    {
        $common = [
            'exhibition' => 'Exhibition',
            'research' => 'Research',
            'conservation' => 'Conservation',
            'photography' => 'Photography',
            'education' => 'Education',
        ];

        $sectorSpecific = [
            'museum' => ['touring' => 'Touring Exhibition'],
            'gallery' => ['sale' => 'Potential Sale', 'consignment' => 'Consignment'],
            'dam' => ['licensing' => 'Licensing', 'publication' => 'Publication'],
            'library' => ['interlibrary' => 'Interlibrary Loan'],
        ];

        return array_merge($common, $sectorSpecific[$sector] ?? []);
    }

    /**
     * Get insurance types.
     */
    private function getInsuranceTypes(): array
    {
        return [
            'borrower' => 'Borrower Covers',
            'lender' => 'Lender Covers',
            'shared' => 'Shared Coverage',
            'government' => 'Government Indemnity',
            'self' => 'Self-Insured',
            'none' => 'No Insurance Required',
        ];
    }

    /**
     * Get loan service.
     */
    private function getLoanService()
    {
        static $service = null;

        if (null === $service) {
            require_once sfConfig::get('sf_plugins_dir').'/ahgLoanPlugin/lib/Services/Loan/LoanService.php';
            $db = $this->getDatabase();
            $service = new \AhgLoan\Services\Loan\LoanService($db);
        }

        return $service;
    }

    /**
     * Get database connection.
     */
    private function getDatabase()
    {
        return \Illuminate\Database\Capsule\Manager::connection();
    }
}
