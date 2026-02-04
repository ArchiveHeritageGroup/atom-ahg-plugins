<?php

use Illuminate\Database\Capsule\Manager as DB;

class contractActions extends sfActions
{
    protected function initFramework()
    {
        \AhgCore\Core\AhgDb::init();
    }

    /**
     * Get contract types from database
     */
    protected function getContractTypes()
    {
        $this->initFramework();
        return DB::table('ahg_contract_type')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Get statuses
     */
    protected function getStatuses()
    {
        return [
            'draft' => 'Draft',
            'pending_review' => 'Pending Review',
            'pending_signature' => 'Pending Signature',
            'active' => 'Active',
            'suspended' => 'Suspended',
            'expired' => 'Expired',
            'terminated' => 'Terminated',
            'renewed' => 'Renewed'
        ];
    }

    /**
     * Browse contracts
     */
    public function executeBrowse(sfWebRequest $request)
    {
        $this->initFramework();
        $this->types = $this->getContractTypes();
        $this->statuses = $this->getStatuses();

        $query = DB::table('ahg_contract as c')
            ->leftJoin('ahg_contract_type as ct', 'c.contract_type_id', '=', 'ct.id')
            ->leftJoin('ahg_vendors as v', 'c.vendor_id', '=', 'v.id')
            ->select(
                'c.*',
                'ct.name as contract_type_name',
                'ct.color as type_color',
                'v.name as vendor_name'
            );

        if ($status = $request->getParameter('status')) {
            $query->where('c.status', $status);
        }
        if ($typeId = $request->getParameter('type')) {
            $query->where('c.contract_type_id', $typeId);
        }
        if ($sq = $request->getParameter('sq')) {
            $query->where(function($q) use ($sq) {
                $q->where('c.contract_number', 'LIKE', "%{$sq}%")
                  ->orWhere('c.title', 'LIKE', "%{$sq}%")
                  ->orWhere('c.counterparty_name', 'LIKE', "%{$sq}%");
            });
        }

        $this->contracts = $query->orderBy('c.created_at', 'desc')->limit(100)->get()->toArray();
    }

    /**
     * Add new contract
     */
    public function executeAdd(sfWebRequest $request)
    {
        $this->initFramework();
        $this->types = $this->getContractTypes();
        $this->statuses = $this->getStatuses();
        $this->vendors = $this->getVendorsList();
        $this->contract = null;

        $this->vendorId = $request->getParameter('vendor_id');
        $this->vendor = null;
        if ($this->vendorId) {
            $this->vendor = DB::table('ahg_vendors')
                ->where('id', $this->vendorId)
                ->first();
        }

        if ($request->isMethod('post')) {
            $this->processForm($request);
        }
    }

    /**
     * Edit existing contract
     */
    public function executeEdit(sfWebRequest $request)
    {
        $this->initFramework();
        $this->types = $this->getContractTypes();
        $this->statuses = $this->getStatuses();
        $this->vendors = $this->getVendorsList();

        $id = (int)$request->getParameter('id');

        $this->contract = DB::table('ahg_contract')
            ->where('id', $id)
            ->first();

        if (!$this->contract) {
            $this->forward404('Contract not found');
        }

        $this->vendorId = $this->contract->vendor_id;
        $this->vendor = null;
        if ($this->vendorId) {
            $this->vendor = DB::table('ahg_vendors')
                ->where('id', $this->vendorId)
                ->first();
        }

        if ($request->isMethod('post')) {
            $this->processForm($request, $id);
        }
    }

    /**
     * View contract details
     */
    public function executeView(sfWebRequest $request)
    {
        $this->initFramework();
        $id = (int)$request->getParameter('id');

        $this->contract = DB::table('ahg_contract as c')
            ->leftJoin('ahg_contract_type as ct', 'c.contract_type_id', '=', 'ct.id')
            ->leftJoin('ahg_vendors as v', 'c.vendor_id', '=', 'v.id')
            ->where('c.id', $id)
            ->select(
                'c.*',
                'ct.name as contract_type_name',
                'ct.color as type_color',
                'ct.requires_witness',
                'v.name as vendor_name'
            )
            ->first();

        if (!$this->contract) {
            $this->forward404('Contract not found');
        }

        $this->documents = DB::table('ahg_contract_document')->where('contract_id', $id)->get()->toArray();
        $this->reminders = DB::table('ahg_contract_reminder')->where('contract_id', $id)->orderBy('reminder_date')->get()->toArray();
        $this->history = DB::table('ahg_contract_history')->where('contract_id', $id)->orderBy('created_at', 'desc')->limit(20)->get()->toArray();
    }

    /**
     * Reminders list
     */
    public function executeReminders(sfWebRequest $request)
    {
        $this->initFramework();
        $this->reminders = DB::table('ahg_contract_reminder as r')
            ->join('ahg_contract as c', 'r.contract_id', '=', 'c.id')
            ->leftJoin('ahg_contract_type as ct', 'c.contract_type_id', '=', 'ct.id')
            ->where('r.status', 'active')
            ->orderBy('r.reminder_date')
            ->select(
                'r.*',
                'c.title as contract_title',
                'c.contract_number',
                'c.counterparty_name',
                'ct.name as contract_type_name'
            )
            ->get()
            ->toArray();
    }

    /**
     * Get list of vendors for dropdown
     */
    protected function getVendorsList()
    {
        return DB::table('ahg_vendors')
            ->where('status', 'active')
            ->orderBy('name')
            ->select('id', 'name')
            ->get()
            ->toArray();
    }

    /**
     * Generate next contract number
     */
    protected function generateContractNumber($typeId)
    {
        $type = DB::table('ahg_contract_type')->where('id', $typeId)->first();
        $prefix = $type ? $type->prefix : 'CON';
        $year = date('Y');

        $lastNumber = DB::table('ahg_contract')
            ->where('contract_number', 'LIKE', "{$prefix}-{$year}-%")
            ->max(DB::raw("CAST(SUBSTRING_INDEX(contract_number, '-', -1) AS UNSIGNED)"));

        $nextNumber = ($lastNumber ?: 0) + 1;
        return sprintf('%s-%s-%04d', $prefix, $year, $nextNumber);
    }

    /**
     * Process form submission
     */
    protected function processForm(sfWebRequest $request, $id = null)
    {
        $this->initFramework();
        $data = $request->getParameter('contract');
        $isNew = !$id;

        try {
            DB::beginTransaction();

            $contractNumber = $data['contract_number'] ?? null;
            if (!$contractNumber && $isNew) {
                $contractNumber = $this->generateContractNumber($data['contract_type_id']);
            }

            $contractData = [
                'contract_type_id' => $data['contract_type_id'],
                'vendor_id' => $data['vendor_id'] ?: null,
                'contract_number' => $contractNumber,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'counterparty_name' => $data['counterparty_name'],
                'counterparty_type' => $data['counterparty_type'] ?? 'vendor',
                'counterparty_contact' => $data['counterparty_contact'] ?? null,
                'counterparty_representative' => $data['counterparty_representative'] ?? null,
                'counterparty_representative_title' => $data['counterparty_representative_title'] ?? null,
                'our_representative' => $data['our_representative'] ?? null,
                'our_representative_title' => $data['our_representative_title'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'effective_date' => $data['effective_date'] ?: null,
                'expiry_date' => $data['expiry_date'] ?: null,
                'review_date' => $data['review_date'] ?: null,
                'auto_renew' => isset($data['auto_renew']) ? 1 : 0,
                'has_financial_terms' => isset($data['has_financial_terms']) ? 1 : 0,
                'contract_value' => $data['contract_value'] ?: null,
                'currency' => $data['currency'] ?? 'ZAR',
                'payment_terms' => $data['payment_terms'] ?? null,
                'scope_of_work' => $data['scope_of_work'] ?? null,
                'deliverables' => $data['deliverables'] ?? null,
                'general_terms' => $data['general_terms'] ?? null,
                'special_conditions' => $data['special_conditions'] ?? null,
                'ip_terms' => $data['ip_terms'] ?? null,
                'confidentiality_terms' => $data['confidentiality_terms'] ?? null,
                'governing_law' => $data['governing_law'] ?? 'South Africa',
                'internal_notes' => $data['internal_notes'] ?? null,
                'risk_level' => $data['risk_level'] ?? 'low',
                'is_template' => isset($data['is_template']) ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => $this->getUser()->getAttribute('user_id'),
            ];

            // Handle logo upload
            if ($request->getParameter('remove_logo')) {
                if ($id) {
                    $existing = DB::table('ahg_contract')->where('id', $id)->first();
                    if ($existing && $existing->logo_path) {
                        $fullPath = sfConfig::get('sf_root_dir') . '/uploads' . $existing->logo_path;
                        if (file_exists($fullPath)) {
                            @unlink($fullPath);
                        }
                    }
                }
                $contractData['logo_path'] = null;
                $contractData['logo_filename'] = null;
            } elseif (isset($_FILES['contract_logo']) && $_FILES['contract_logo']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['contract_logo'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

                if (in_array($file['type'], $allowedTypes)) {
                    $uploadDir = sfConfig::get('sf_root_dir') . '/uploads/contracts/logos';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'logo_' . uniqid() . '.' . $ext;
                    $destPath = $uploadDir . '/' . $filename;

                    if (move_uploaded_file($file['tmp_name'], $destPath)) {
                        if ($id) {
                            $existing = DB::table('ahg_contract')->where('id', $id)->first();
                            if ($existing && $existing->logo_path) {
                                $oldPath = sfConfig::get('sf_root_dir') . '/uploads' . $existing->logo_path;
                                if (file_exists($oldPath)) {
                                    @unlink($oldPath);
                                }
                            }
                        }
                        $contractData['logo_path'] = '/contracts/logos/' . $filename;
                        $contractData['logo_filename'] = $file['name'];
                    }
                }
            }

            if ($id) {
                DB::table('ahg_contract')->where('id', $id)->update($contractData);
                $contractId = $id;
                $action = 'updated';
            } else {
                $contractData['created_at'] = date('Y-m-d H:i:s');
                $contractData['created_by'] = $this->getUser()->getAttribute('user_id');
                $contractId = DB::table('ahg_contract')->insertGetId($contractData);
                $action = 'created';
            }

            // Log history
            DB::table('ahg_contract_history')->insert([
                'contract_id' => $contractId,
                'action' => $action,
                'user_id' => $this->getUser()->getAttribute('user_id'),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            DB::commit();

            $this->getUser()->setFlash('notice', $id ? 'Contract updated.' : 'Contract created.');
            $this->redirect(['module' => 'contract', 'action' => 'view', 'id' => $contractId]);

        } catch (Exception $e) {
            DB::rollBack();
            $this->getUser()->setFlash('error', 'Error saving contract: ' . $e->getMessage());
        }
    }

    /**
     * Delete contract
     */
    public function executeDelete(sfWebRequest $request)
    {
        $this->initFramework();
        $id = (int)$request->getParameter('id');

        if ($request->isMethod('post')) {
            try {
                DB::beginTransaction();

                DB::table('ahg_contract_document')->where('contract_id', $id)->delete();
                DB::table('ahg_contract_reminder')->where('contract_id', $id)->delete();
                DB::table('ahg_contract_history')->where('contract_id', $id)->delete();
                DB::table('ahg_contract')->where('id', $id)->delete();

                DB::commit();

                $this->getUser()->setFlash('notice', 'Contract deleted.');
            } catch (Exception $e) {
                DB::rollBack();
                $this->getUser()->setFlash('error', 'Error deleting: ' . $e->getMessage());
            }
        }

        $this->redirect(['module' => 'contract', 'action' => 'browse']);
    }
}
