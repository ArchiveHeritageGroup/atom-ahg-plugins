<?php

/**
 * Shared Loan module actions.
 *
 * Handles loan management for all GLAM sectors.
 * Self-contained - does not depend on ahgMuseumPlugin.
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
        static $initialized = false;

        if (!$initialized) {
            require_once sfConfig::get('sf_root_dir').'/atom-framework/src/Database/DatabaseBootstrap.php';
            \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
            $initialized = true;
        }

        return \Illuminate\Database\Capsule\Manager::connection();
    }
}
