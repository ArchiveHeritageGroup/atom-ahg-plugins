<?php

class vendorActions extends sfActions
{
    protected $service;

    public function preExecute()
    {
        error_log("VENDOR: preExecute started");
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgVendorPlugin/lib/Service/VendorService.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgVendorPlugin/lib/Repository/VendorRepository.php';
        $this->service = new \AtomFramework\Services\VendorService();
        error_log("VENDOR: preExecute completed");
    }

    public function executeIndex(sfWebRequest $request)
    {
        error_log("VENDOR: executeIndex started");
        $this->stats = $this->service->getDashboardStats();
        $this->overdueTransactions = $this->service->getOverdueTransactions();
        $this->activeTransactions = $this->service->getActiveTransactions()->take(10);
        $this->statusCounts = $this->service->getTransactionsByStatus();
        $this->monthlyStats = $this->service->getMonthlyStats(12);
        error_log("VENDOR: executeIndex completed");
    }

    public function executeList(sfWebRequest $request)
    {
        error_log("VENDOR: executeList started");
        
        try {
            $this->filters = [
                'status' => $request->getParameter('status'),
                'vendor_type' => $request->getParameter('vendor_type'),
                'service_type_id' => $request->getParameter('service_type_id'),
                'search' => $request->getParameter('search'),
                'has_insurance' => $request->getParameter('has_insurance'),
                'sort' => $request->getParameter('sort', 'name'),
                'direction' => $request->getParameter('direction', 'asc'),
            ];
            error_log("VENDOR: filters set");

            $this->vendors = $this->service->listVendors($this->filters);
            error_log("VENDOR: got " . $this->vendors->count() . " vendors");

            $this->serviceTypes = $this->service->listServiceTypes();
            error_log("VENDOR: got " . count($this->serviceTypes) . " service types");

            $this->vendorTypes = $this->service->getVendorTypes();
            error_log("VENDOR: got " . count($this->vendorTypes) . " vendor types");

            error_log("VENDOR: executeList completed successfully");
        } catch (\Exception $e) {
            error_log("VENDOR ERROR: " . $e->getMessage());
            error_log("VENDOR TRACE: " . $e->getTraceAsString());
            throw $e;
        }
    }

    public function executeView(sfWebRequest $request)
    {
        $slug = $request->getParameter('slug');
        $this->vendor = $this->service->getVendorBySlug($slug);

        if (!$this->vendor) {
            $this->forward404('Vendor not found');
        }

        $this->contacts = $this->service->getVendorContacts($this->vendor->id);
        $this->services = $this->service->getVendorServices($this->vendor->id);
        $this->stats = $this->service->getVendorStats($this->vendor->id);
        $this->transactions = $this->service->listTransactions(['vendor_id' => $this->vendor->id]);
    }

    public function executeAdd(sfWebRequest $request)
    {
        $this->form = [];
        $this->errors = [];
        $this->vendorTypes = $this->service->getVendorTypes();
        $this->serviceTypes = $this->service->listServiceTypes();

        if ($request->isMethod('post')) {
            $data = $this->extractVendorData($request);
            $result = $this->service->createVendor($data, $this->getUser()->getUserId());

            if ($result['success']) {
                $this->getUser()->setFlash('notice', 'Vendor created successfully');
                $vendor = $this->service->getVendor($result['vendor_id']);
                $this->redirect(['module' => 'vendor', 'action' => 'view', 'slug' => $vendor->slug]);
            }

            $this->errors = $result['errors'];
            $this->form = $data;
        }
    }

    public function executeEdit(sfWebRequest $request)
    {
        $slug = $request->getParameter('slug');
        $this->vendor = $this->service->getVendorBySlug($slug);

        if (!$this->vendor) {
            $this->forward404('Vendor not found');
        }

        $this->errors = [];
        $this->vendorTypes = $this->service->getVendorTypes();
        $this->serviceTypes = $this->service->listServiceTypes();
        $this->vendorServices = $this->service->getVendorServices($this->vendor->id);

        if ($request->isMethod('post')) {
            error_log('VENDOR EDIT: POST received for vendor ID ' . $this->vendor->id);
            $data = $this->extractVendorData($request);
            error_log('VENDOR EDIT: Data extracted: ' . json_encode($data));
            $result = $this->service->updateVendor($this->vendor->id, $data);
            error_log('VENDOR EDIT: Update result: ' . json_encode($result));

            if ($result['success']) {
                $serviceIds = $request->getParameter('service_ids', []);
                $this->syncVendorServices($this->vendor->id, $serviceIds);
                $this->getUser()->setFlash('notice', 'Vendor updated successfully');
                $vendor = $this->service->getVendor($this->vendor->id);
                $this->redirect(['module' => 'vendor', 'action' => 'view', 'slug' => $vendor->slug]);
            }

            $this->errors = $result['errors'];
        }
    }

    public function executeDelete(sfWebRequest $request)
    {
        $slug = $request->getParameter('slug');
        $vendor = $this->service->getVendorBySlug($slug);

        if (!$vendor) {
            $this->forward404('Vendor not found');
        }

        if ($request->isMethod('post')) {
            $result = $this->service->deleteVendor($vendor->id);
            if ($result['success']) {
                $this->getUser()->setFlash('notice', 'Vendor deleted successfully');
            } else {
                $this->getUser()->setFlash('error', $result['errors']['general'] ?? 'Cannot delete vendor');
            }
        }

        $this->redirect(['module' => 'vendor', 'action' => 'list']);
    }

    protected function extractVendorData(sfWebRequest $request): array
    {
        return [
            'name' => $request->getParameter('name'),
            'vendor_type' => $request->getParameter('vendor_type'),
            'registration_number' => $request->getParameter('registration_number'),
            'vat_number' => $request->getParameter('vat_number'),
            'street_address' => $request->getParameter('street_address'),
            'city' => $request->getParameter('city'),
            'province' => $request->getParameter('province'),
            'postal_code' => $request->getParameter('postal_code'),
            'country' => $request->getParameter('country', 'South Africa'),
            'phone' => $request->getParameter('phone'),
            'phone_alt' => $request->getParameter('phone_alt'),
            'fax' => $request->getParameter('fax'),
            'email' => $request->getParameter('email'),
            'website' => $request->getParameter('website'),
            'bank_name' => $request->getParameter('bank_name'),
            'bank_branch' => $request->getParameter('bank_branch'),
            'bank_account_number' => $request->getParameter('bank_account_number'),
            'bank_branch_code' => $request->getParameter('bank_branch_code'),
            'has_insurance' => $request->getParameter('has_insurance') ? 1 : 0,
            'insurance_provider' => $request->getParameter('insurance_provider'),
            'insurance_policy_number' => $request->getParameter('insurance_policy_number'),
            'insurance_expiry_date' => $request->getParameter('insurance_expiry_date') ?: null,
            'insurance_coverage_amount' => $request->getParameter('insurance_coverage_amount') ?: null,
            'status' => $request->getParameter('status', 'active'),
            'vendor_code' => $request->getParameter('vendor_code'),
            'bank_account_type' => $request->getParameter('bank_account_type'),
            'is_preferred' => $request->getParameter('is_preferred') ? 1 : 0,
            'is_bbbee_compliant' => $request->getParameter('is_bbbee_compliant') ? 1 : 0,
            'notes' => $request->getParameter('notes'),
        ];
    }

    protected function syncVendorServices(int $vendorId, array $serviceIds): void
    {
        $currentServices = $this->service->getVendorServices($vendorId);
        $currentIds = $currentServices->pluck('service_type_id')->toArray();

        foreach ($serviceIds as $serviceId) {
            if (!in_array($serviceId, $currentIds)) {
                $this->service->assignService($vendorId, (int)$serviceId);
            }
        }

        foreach ($currentIds as $currentId) {
            if (!in_array($currentId, $serviceIds)) {
                $this->service->removeService($vendorId, (int)$currentId);
            }
        }
    }

    public function executeAddContact(sfWebRequest $request)
    {
        $slug = $request->getParameter('slug');
        $vendor = $this->service->getVendorBySlug($slug);

        if (!$vendor) {
            $this->forward404('Vendor not found');
        }

        if ($request->isMethod('post')) {
            $data = [
                'name' => $request->getParameter('contact_name'),
                'position' => $request->getParameter('position'),
                'department' => $request->getParameter('department'),
                'phone' => $request->getParameter('contact_phone'),
                'mobile' => $request->getParameter('mobile'),
                'email' => $request->getParameter('contact_email'),
                'is_primary' => $request->getParameter('is_primary') ? 1 : 0,
                'notes' => $request->getParameter('contact_notes'),
            ];

            $result = $this->service->addContact($vendor->id, $data);

            if ($result['success']) {
                $this->getUser()->setFlash('notice', 'Contact added successfully');
            } else {
                $this->getUser()->setFlash('error', implode(', ', $result['errors']));
            }
        }

        $this->redirect(['module' => 'vendor', 'action' => 'view', 'slug' => $slug]);
    }

    public function executeUpdateContact(sfWebRequest $request)
    {
        $slug = $request->getParameter('slug');
        $contactId = $request->getParameter('contact_id');

        if ($request->isMethod('post')) {
            $data = [
                'name' => $request->getParameter('contact_name'),
                'position' => $request->getParameter('position'),
                'department' => $request->getParameter('department'),
                'phone' => $request->getParameter('contact_phone'),
                'mobile' => $request->getParameter('mobile'),
                'email' => $request->getParameter('contact_email'),
                'is_primary' => $request->getParameter('is_primary') ? 1 : 0,
                'is_active' => $request->getParameter('is_active') ? 1 : 0,
                'notes' => $request->getParameter('contact_notes'),
            ];

            $this->service->updateContact((int)$contactId, $data);
            $this->getUser()->setFlash('notice', 'Contact updated');
        }

        $this->redirect(['module' => 'vendor', 'action' => 'view', 'slug' => $slug]);
    }

    public function executeDeleteContact(sfWebRequest $request)
    {
        $contactId = $request->getParameter('contact_id');
        $slug = $request->getParameter('slug');

        $this->service->deleteContact((int)$contactId);
        $this->getUser()->setFlash('notice', 'Contact deleted');

        $this->redirect(['module' => 'vendor', 'action' => 'view', 'slug' => $slug]);
    }

    public function executeTransactions(sfWebRequest $request)
    {
        $this->filters = [
            'status' => $request->getParameter('status'),
            'vendor_id' => $request->getParameter('vendor_id'),
            'service_type_id' => $request->getParameter('service_type_id'),
            'date_from' => $request->getParameter('date_from'),
            'date_to' => $request->getParameter('date_to'),
            'search' => $request->getParameter('search'),
            'overdue' => $request->getParameter('overdue'),
        ];

        $this->transactions = $this->service->listTransactions($this->filters);
        $this->vendors = $this->service->listVendors(['status' => 'active']);
        $this->serviceTypes = $this->service->listServiceTypes();
        $this->statusOptions = $this->service->getStatusOptions();
    }

    public function executeViewTransaction(sfWebRequest $request)
    {
        $id = (int)$request->getParameter('id');
        $this->transaction = $this->service->getTransaction($id);

        if (!$this->transaction) {
            $this->forward404('Transaction not found');
        }

        $this->items = $this->service->getTransactionItems($id);
        $this->history = $this->service->getTransactionHistory($id);
        $this->attachments = $this->service->getAttachments($id);
        $this->statusOptions = $this->service->getStatusOptions();
    }

    public function executeAddTransaction(sfWebRequest $request)
    {
        $this->form = [];
        $this->errors = [];
        $this->vendors = $this->service->listVendors(['status' => 'active']);
        $this->serviceTypes = $this->service->listServiceTypes();
        $this->conditionRatings = $this->service->getConditionRatings();

        $preselectedVendorSlug = $request->getParameter('vendor');
        if ($preselectedVendorSlug) {
            $vendor = $this->service->getVendorBySlug($preselectedVendorSlug);
            if ($vendor) {
                $this->form['vendor_id'] = $vendor->id;
            }
        }

        if ($request->isMethod('post')) {
            $data = $this->extractTransactionData($request);
            $result = $this->service->createTransaction($data, $this->getUser()->getUserId());

            if ($result['success']) {
                $informationObjectIds = $request->getParameter('information_object_ids', []);
                $conditions = $request->getParameter('conditions', []);
                $values = $request->getParameter('declared_values', []);
                $ratings = $request->getParameter('condition_ratings', []);

                foreach ($informationObjectIds as $index => $ioId) {
                    if (!empty($ioId)) {
                        $itemData = [
                            'condition_before' => $conditions[$index] ?? null,
                            'condition_before_rating' => $ratings[$index] ?? null,
                            'declared_value' => $values[$index] ?? null,
                        ];
                        $this->service->addItemToTransaction($result['transaction_id'], (int)$ioId, $itemData);
                    }
                }

                $this->getUser()->setFlash('notice', 'Transaction created successfully');
                $this->redirect(['module' => 'vendor', 'action' => 'viewTransaction', 'id' => $result['transaction_id']]);
            }

            $this->errors = $result['errors'];
            $this->form = $data;
        }
    }

    public function executeEditTransaction(sfWebRequest $request)
    {
        $id = (int)$request->getParameter('id');
        $this->transaction = $this->service->getTransaction($id);

        if (!$this->transaction) {
            $this->forward404('Transaction not found');
        }

        $this->errors = [];
        $this->vendors = $this->service->listVendors(['status' => 'active']);
        $this->serviceTypes = $this->service->listServiceTypes();
        $this->paymentStatuses = $this->service->getPaymentStatuses();

        if ($request->isMethod('post')) {
            $data = $this->extractTransactionData($request);
            $result = $this->service->updateTransaction($id, $data, $this->getUser()->getUserId());

            if ($result['success']) {
                $this->getUser()->setFlash('notice', 'Transaction updated successfully');
                $this->redirect(['module' => 'vendor', 'action' => 'viewTransaction', 'id' => $id]);
            }

            $this->errors = $result['errors'];
        }
    }

    public function executeUpdateTransactionStatus(sfWebRequest $request)
    {
        $id = (int)$request->getParameter('id');
        $status = $request->getParameter('status');
        $notes = $request->getParameter('notes');

        $result = $this->service->updateTransactionStatus($id, $status, $this->getUser()->getUserId(), $notes);

        if ($result['success']) {
            $this->getUser()->setFlash('notice', 'Status updated successfully');
        } else {
            $this->getUser()->setFlash('error', implode(', ', $result['errors']));
        }

        $this->redirect(['module' => 'vendor', 'action' => 'viewTransaction', 'id' => $id]);
    }

    protected function extractTransactionData(sfWebRequest $request): array
    {
        return [
            'vendor_id' => $request->getParameter('vendor_id'),
            'service_type_id' => $request->getParameter('service_type_id'),
            'request_date' => $request->getParameter('request_date'),
            'expected_return_date' => $request->getParameter('expected_return_date') ?: null,
            'estimated_cost' => $request->getParameter('estimated_cost') ?: null,
            'actual_cost' => $request->getParameter('actual_cost') ?: null,
            'quote_reference' => $request->getParameter('quote_reference'),
            'invoice_reference' => $request->getParameter('invoice_reference'),
            'invoice_date' => $request->getParameter('invoice_date') ?: null,
            'payment_status' => $request->getParameter('payment_status', 'not_invoiced'),
            'total_insured_value' => $request->getParameter('total_insured_value') ?: null,
            'insurance_arranged' => $request->getParameter('insurance_arranged') ? 1 : 0,
            'insurance_reference' => $request->getParameter('insurance_reference'),
            'shipping_method' => $request->getParameter('shipping_method'),
            'tracking_number' => $request->getParameter('tracking_number'),
            'courier_company' => $request->getParameter('courier_company'),
            'dispatch_notes' => $request->getParameter('dispatch_notes'),
            'internal_notes' => $request->getParameter('internal_notes'),
        ];
    }

    public function executeAddTransactionItem(sfWebRequest $request)
    {
        $transactionId = (int)$request->getParameter('transaction_id');

        if ($request->isMethod('post')) {
            $ioId = (int)$request->getParameter('information_object_id');
            $data = [
                'condition_before' => $request->getParameter('condition_before'),
                'condition_before_rating' => $request->getParameter('condition_before_rating'),
                'declared_value' => $request->getParameter('declared_value') ?: null,
                'service_description' => $request->getParameter('service_description'),
            ];

            $result = $this->service->addItemToTransaction($transactionId, $ioId, $data);

            if ($result['success']) {
                $this->getUser()->setFlash('notice', 'Item added to transaction');
            } else {
                $this->getUser()->setFlash('error', implode(', ', $result['errors']));
            }
        }

        $this->redirect(['module' => 'vendor', 'action' => 'viewTransaction', 'id' => $transactionId]);
    }

    public function executeUpdateTransactionItem(sfWebRequest $request)
    {
        $itemId = (int)$request->getParameter('item_id');
        $transactionId = (int)$request->getParameter('transaction_id');

        if ($request->isMethod('post')) {
            $data = [
                'condition_after' => $request->getParameter('condition_after'),
                'condition_after_rating' => $request->getParameter('condition_after_rating'),
                'service_completed' => $request->getParameter('service_completed') ? 1 : 0,
                'service_notes' => $request->getParameter('service_notes'),
                'item_cost' => $request->getParameter('item_cost') ?: null,
            ];

            $this->service->updateTransactionItem($itemId, $data);
            $this->getUser()->setFlash('notice', 'Item updated');
        }

        $this->redirect(['module' => 'vendor', 'action' => 'viewTransaction', 'id' => $transactionId]);
    }

    public function executeRemoveTransactionItem(sfWebRequest $request)
    {
        $itemId = (int)$request->getParameter('item_id');
        $transactionId = (int)$request->getParameter('transaction_id');

        $this->service->removeItemFromTransaction($itemId);
        $this->getUser()->setFlash('notice', 'Item removed from transaction');

        $this->redirect(['module' => 'vendor', 'action' => 'viewTransaction', 'id' => $transactionId]);
    }

    public function executeServiceTypes(sfWebRequest $request)
    {
        error_log("SERVICE_TYPES: Method=" . $request->getMethod());
        
        // Handle POST actions
        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action');
            error_log("SERVICE_TYPES: POST action=" . $action);
            error_log("SERVICE_TYPES: name=" . $request->getParameter('name'));
            $id = $request->getParameter('id');
            $name = trim($request->getParameter('name', ''));
            $description = trim($request->getParameter('description', ''));
            $isActive = $request->getParameter('is_active') ? 1 : 0;
            
            try {
                switch ($action) {
                    case 'add':
                        if (empty($name)) {
                            $this->getUser()->setFlash('error', 'Name is required.');
                            break;
                        }
                        $this->service->addServiceType($name, $description, $isActive);
                        $this->getUser()->setFlash('success', 'Service type added successfully.');
                        break;
                        
                    case 'edit':
                        if (empty($id) || empty($name)) {
                            $this->getUser()->setFlash('error', 'Invalid request.');
                            break;
                        }
                        $this->service->updateServiceType($id, $name, $description, $isActive);
                        $this->getUser()->setFlash('success', 'Service type updated successfully.');
                        break;
                        
                    case 'delete':
                        if (empty($id)) {
                            $this->getUser()->setFlash('error', 'Invalid request.');
                            break;
                        }
                        $this->service->deleteServiceType($id);
                        $this->getUser()->setFlash('success', 'Service type deleted successfully.');
                        break;
                }
            } catch (Exception $e) {
                $this->getUser()->setFlash('error', 'Error: ' . $e->getMessage());
            }
            
            $this->redirect('ahg_vend_service_types');
        }
        
        $this->serviceTypes = $this->service->listServiceTypes(false);
    }
}
