<?php

use AtomFramework\Http\Controllers\AhgController;
class vendorActions extends AhgController
{
    protected $service;

    public function boot(): void
    {
        require_once $this->config('sf_plugins_dir') . '/ahgVendorPlugin/lib/Service/VendorService.php';
        require_once $this->config('sf_plugins_dir') . '/ahgVendorPlugin/lib/Repository/VendorRepository.php';
        $this->service = new \AtomFramework\Services\VendorService();
    }

    public function executeIndex($request)
    {
        return $this->renderBlade('index', [
            'stats' => $this->service->getDashboardStats(),
            'overdueTransactions' => $this->service->getOverdueTransactions(),
            'activeTransactions' => $this->service->getActiveTransactions()->take(10),
            'statusCounts' => $this->service->getTransactionsByStatus(),
            'monthlyStats' => $this->service->getMonthlyStats(12),
        ]);
    }

    public function executeList($request)
    {
        $filters = [
            'status' => $request->getParameter('status'),
            'vendor_type' => $request->getParameter('vendor_type'),
            'service_type_id' => $request->getParameter('service_type_id'),
            'search' => $request->getParameter('search'),
            'has_insurance' => $request->getParameter('has_insurance'),
            'sort' => $request->getParameter('sort', 'name'),
            'direction' => $request->getParameter('direction', 'asc'),
        ];

        return $this->renderBlade('list', [
            'filters' => $filters,
            'vendors' => $this->service->listVendors($filters),
            'serviceTypes' => $this->service->listServiceTypes(),
            'vendorTypes' => $this->service->getVendorTypes(),
        ]);
    }

    public function executeView($request)
    {
        $slug = $request->getParameter('slug');
        $vendor = $this->service->getVendorBySlug($slug);

        if (!$vendor) {
            $this->forward404('Vendor not found');
        }

        return $this->renderBlade('view', [
            'vendor' => $vendor,
            'contacts' => $this->service->getVendorContacts($vendor->id),
            'services' => $this->service->getVendorServices($vendor->id),
            'stats' => $this->service->getVendorStats($vendor->id),
            'transactions' => $this->service->listTransactions(['vendor_id' => $vendor->id]),
        ]);
    }

    public function executeAdd($request)
    {
        $form = [];
        $errors = [];
        $vendorTypes = $this->service->getVendorTypes();
        $serviceTypes = $this->service->listServiceTypes();

        if ($request->isMethod('post')) {
            $data = $this->extractVendorData($request);
            $result = $this->service->createVendor($data, $this->getUser()->getUserId());

            if ($result['success']) {
                $this->getUser()->setFlash('notice', 'Vendor created successfully');
                $vendor = $this->service->getVendor($result['vendor_id']);
                $this->redirect(['module' => 'vendor', 'action' => 'view', 'slug' => $vendor->slug]);
            }

            $errors = $result['errors'];
            $form = $data;
        }

        return $this->renderBlade('add', [
            'form' => $form,
            'errors' => $errors,
            'vendorTypes' => $vendorTypes,
            'serviceTypes' => $serviceTypes,
        ]);
    }

    public function executeEdit($request)
    {
        $slug = $request->getParameter('slug');
        $vendor = $this->service->getVendorBySlug($slug);

        if (!$vendor) {
            $this->forward404('Vendor not found');
        }

        $errors = [];
        $vendorTypes = $this->service->getVendorTypes();
        $serviceTypes = $this->service->listServiceTypes();
        $vendorServices = $this->service->getVendorServices($vendor->id);

        if ($request->isMethod('post')) {
            $data = $this->extractVendorData($request);
            $result = $this->service->updateVendor($vendor->id, $data);

            if ($result['success']) {
                $serviceIds = $request->getParameter('service_ids', []);
                $this->syncVendorServices($vendor->id, $serviceIds);
                $this->getUser()->setFlash('notice', 'Vendor updated successfully');
                $vendor = $this->service->getVendor($vendor->id);
                $this->redirect(['module' => 'vendor', 'action' => 'view', 'slug' => $vendor->slug]);
            }

            $errors = $result['errors'];
        }

        return $this->renderBlade('edit', [
            'vendor' => $vendor,
            'errors' => $errors,
            'vendorTypes' => $vendorTypes,
            'serviceTypes' => $serviceTypes,
            'vendorServices' => $vendorServices,
        ]);
    }

    public function executeDelete($request)
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

    public function executeAddContact($request)
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

    public function executeUpdateContact($request)
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

    public function executeDeleteContact($request)
    {
        $contactId = $request->getParameter('contact_id');
        $slug = $request->getParameter('slug');

        $this->service->deleteContact((int)$contactId);
        $this->getUser()->setFlash('notice', 'Contact deleted');

        $this->redirect(['module' => 'vendor', 'action' => 'view', 'slug' => $slug]);
    }

    public function executeTransactions($request)
    {
        $filters = [
            'status' => $request->getParameter('status'),
            'vendor_id' => $request->getParameter('vendor_id'),
            'service_type_id' => $request->getParameter('service_type_id'),
            'date_from' => $request->getParameter('date_from'),
            'date_to' => $request->getParameter('date_to'),
            'search' => $request->getParameter('search'),
            'overdue' => $request->getParameter('overdue'),
        ];

        return $this->renderBlade('transactions', [
            'filters' => $filters,
            'transactions' => $this->service->listTransactions($filters),
            'vendors' => $this->service->listVendors(['status' => 'active']),
            'serviceTypes' => $this->service->listServiceTypes(),
            'statusOptions' => $this->service->getStatusOptions(),
        ]);
    }

    public function executeViewTransaction($request)
    {
        $id = (int)$request->getParameter('id');
        $transaction = $this->service->getTransaction($id);

        if (!$transaction) {
            $this->forward404('Transaction not found');
        }

        return $this->renderBlade('viewTransaction', [
            'transaction' => $transaction,
            'items' => $this->service->getTransactionItems($id),
            'history' => $this->service->getTransactionHistory($id),
            'attachments' => $this->service->getAttachments($id),
            'statusOptions' => $this->service->getStatusOptions(),
        ]);
    }

    public function executeAddTransaction($request)
    {
        $form = [];
        $errors = [];
        $vendors = $this->service->listVendors(['status' => 'active']);
        $serviceTypes = $this->service->listServiceTypes();
        $conditionRatings = $this->service->getConditionRatings();

        $preselectedVendorSlug = $request->getParameter('vendor');
        if ($preselectedVendorSlug) {
            $vendor = $this->service->getVendorBySlug($preselectedVendorSlug);
            if ($vendor) {
                $form['vendor_id'] = $vendor->id;
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

            $errors = $result['errors'];
            $form = $data;
        }

        return $this->renderBlade('addTransaction', [
            'form' => $form,
            'errors' => $errors,
            'vendors' => $vendors,
            'serviceTypes' => $serviceTypes,
            'conditionRatings' => $conditionRatings,
        ]);
    }

    public function executeEditTransaction($request)
    {
        $id = (int)$request->getParameter('id');
        $transaction = $this->service->getTransaction($id);

        if (!$transaction) {
            $this->forward404('Transaction not found');
        }

        $errors = [];
        $vendors = $this->service->listVendors(['status' => 'active']);
        $serviceTypes = $this->service->listServiceTypes();
        $paymentStatuses = $this->service->getPaymentStatuses();

        if ($request->isMethod('post')) {
            $data = $this->extractTransactionData($request);
            $result = $this->service->updateTransaction($id, $data, $this->getUser()->getUserId());

            if ($result['success']) {
                $this->getUser()->setFlash('notice', 'Transaction updated successfully');
                $this->redirect(['module' => 'vendor', 'action' => 'viewTransaction', 'id' => $id]);
            }

            $errors = $result['errors'];
        }

        return $this->renderBlade('editTransaction', [
            'transaction' => $transaction,
            'errors' => $errors,
            'vendors' => $vendors,
            'serviceTypes' => $serviceTypes,
            'paymentStatuses' => $paymentStatuses,
        ]);
    }

    public function executeUpdateTransactionStatus($request)
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

    public function executeAddTransactionItem($request)
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

    public function executeUpdateTransactionItem($request)
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

    public function executeRemoveTransactionItem($request)
    {
        $itemId = (int)$request->getParameter('item_id');
        $transactionId = (int)$request->getParameter('transaction_id');

        $this->service->removeItemFromTransaction($itemId);
        $this->getUser()->setFlash('notice', 'Item removed from transaction');

        $this->redirect(['module' => 'vendor', 'action' => 'viewTransaction', 'id' => $transactionId]);
    }

    public function executeServiceTypes($request)
    {
        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action');
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

        return $this->renderBlade('serviceTypes', [
            'serviceTypes' => $this->service->listServiceTypes(false),
        ]);
    }
}
