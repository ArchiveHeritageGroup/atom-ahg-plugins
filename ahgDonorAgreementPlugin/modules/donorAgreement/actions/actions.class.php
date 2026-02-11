<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

class donorAgreementActions extends AhgController
{
    protected function initFramework()
    {
    }

    /**
     * Get agreement types from database
     */
    protected function getAgreementTypes()
    {
        $this->initFramework();
        return DB::table('agreement_type')
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
            'expired' => 'Expired',
            'terminated' => 'Terminated'
        ];
    }

    /**
     * Browse agreements
     */
    public function executeBrowse($request)
    {
        $this->initFramework();
        $this->types = $this->getAgreementTypes();
        $this->statuses = $this->getStatuses();

        $query = DB::table('donor_agreement as da')
            ->leftJoin('donor_agreement_i18n as dai', function($j) {
                $j->on('da.id', '=', 'dai.id')->where('dai.culture', '=', 'en');
            })
            ->leftJoin('agreement_type as at', 'da.agreement_type_id', '=', 'at.id')
            ->leftJoin('donor as d', 'da.donor_id', '=', 'd.id')
            ->leftJoin('actor as a', 'd.id', '=', 'a.id')
            ->leftJoin('actor_i18n as ai', function($j) {
                $j->on('a.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
            })
            ->select(
                'da.*',
                'dai.title',
                'dai.description',
                'at.name as agreement_type_name',
                'ai.authorized_form_of_name as donor_name'
            );

        if ($status = $request->getParameter('status')) {
            $query->where('da.status', $status);
        }
        if ($typeId = $request->getParameter('type')) {
            $query->where('da.agreement_type_id', $typeId);
        }
        if ($sq = $request->getParameter('sq')) {
            $query->where(function($q) use ($sq) {
                $q->where('da.agreement_number', 'LIKE', "%{$sq}%")
                  ->orWhere('dai.title', 'LIKE', "%{$sq}%")
                  ->orWhere('ai.authorized_form_of_name', 'LIKE', "%{$sq}%");
            });
        }

        $this->agreements = $query->orderBy('da.created_at', 'desc')->limit(100)->get()->toArray();
    }

    /**
     * Add new agreement
     */
    public function executeAdd($request)
    {
        $this->initFramework();
        $this->types = $this->getAgreementTypes();
        $this->statuses = $this->getStatuses();
        $this->donors = $this->getDonorsList();
        $this->agreement = null;

        $this->donorId = $request->getParameter('donor_id');
        $this->donor = null;
        if ($this->donorId) {
            $this->donor = DB::table('donor as d')
                ->join('actor as a', 'd.id', '=', 'a.id')
                ->join('actor_i18n as ai', function($j) {
                    $j->on('a.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
                })
                ->where('d.id', $this->donorId)
                ->select('d.id', 'ai.authorized_form_of_name as name')
                ->first();
        }

        if ($request->isMethod('post')) {
            $this->processForm($request);
        }
    }

    /**
     * Edit existing agreement
     */
    public function executeEdit($request)
    {
        $this->initFramework();
        $this->types = $this->getAgreementTypes();
        $this->statuses = $this->getStatuses();
        $this->donors = $this->getDonorsList();

        $id = (int)$request->getParameter('id');

        $this->agreement = DB::table('donor_agreement as da')
            ->leftJoin('donor_agreement_i18n as dai', function($j) {
                $j->on('da.id', '=', 'dai.id')->where('dai.culture', '=', 'en');
            })
            ->where('da.id', $id)
            ->select('da.*', 'dai.title', 'dai.description')
            ->first();

        if (!$this->agreement) {
            $this->forward404('Agreement not found');
        }

        $this->donorId = $this->agreement->donor_id;
        $this->donor = null;
        if ($this->donorId) {
            $this->donor = DB::table('donor as d')
                ->join('actor as a', 'd.id', '=', 'a.id')
                ->join('actor_i18n as ai', function($j) {
                    $j->on('a.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
                })
                ->where('d.id', $this->donorId)
                ->select('d.id', 'ai.authorized_form_of_name as name')
                ->first();
        }

        if ($request->isMethod('post')) {
            $this->processForm($request, $id);
        }
    }

    /**
     * View agreement details
     */
    public function executeView($request)
    {
        $this->initFramework();
        $id = (int)$request->getParameter('id');

        $this->agreement = DB::table('donor_agreement as da')
            ->leftJoin('donor_agreement_i18n as dai', function($j) {
                $j->on('da.id', '=', 'dai.id')->where('dai.culture', '=', 'en');
            })
            ->leftJoin('agreement_type as at', 'da.agreement_type_id', '=', 'at.id')
            ->leftJoin('donor as d', 'da.donor_id', '=', 'd.id')
            ->leftJoin('actor as a', 'd.id', '=', 'a.id')
            ->leftJoin('actor_i18n as ai', function($j) {
                $j->on('a.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
            })
            ->where('da.id', $id)
            ->select(
                'da.*',
                'dai.title',
                'dai.description',
                'at.name as agreement_type_name',
                'ai.authorized_form_of_name as donor_name'
            )
            ->first();

        if (!$this->agreement) {
            $this->forward404('Agreement not found');
        }

        $this->rights = DB::table('donor_agreement_right')->where('donor_agreement_id', $id)->get()->toArray();
        $this->restrictions = DB::table('donor_agreement_restriction')->where('donor_agreement_id', $id)->get()->toArray();
        $this->documents = DB::table('donor_agreement_document')->where('donor_agreement_id', $id)->get()->toArray();
        $this->reminders = DB::table('donor_agreement_reminder')->where('donor_agreement_id', $id)->orderBy('reminder_date')->get()->toArray();
    }

    /**
     * Reminders list
     */
    public function executeReminders($request)
    {
        $this->initFramework();
        $this->reminders = DB::table('donor_agreement_reminder as r')
            ->join('donor_agreement as da', 'r.donor_agreement_id', '=', 'da.id')
            ->leftJoin('donor_agreement_i18n as dai', function($j) {
                $j->on('da.id', '=', 'dai.id')->where('dai.culture', '=', 'en');
            })
            ->leftJoin('donor as d', 'da.donor_id', '=', 'd.id')
            ->leftJoin('actor as a', 'd.id', '=', 'a.id')
            ->leftJoin('actor_i18n as ai', function($j) {
                $j->on('a.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
            })
            ->where('r.is_active', 1)
            ->orderBy('r.reminder_date')
            ->select(
                'r.*',
                'dai.title as agreement_title',
                'da.agreement_number',
                'ai.authorized_form_of_name as donor_name'
            )
            ->get()
            ->toArray();
    }

    /**
     * Get list of donors for dropdown
     */
    protected function getDonorsList()
    {
        return DB::table('donor as d')
            ->join('actor as a', 'd.id', '=', 'a.id')
            ->join('actor_i18n as ai', function($j) {
                $j->on('a.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
            })
            ->orderBy('ai.authorized_form_of_name')
            ->select('d.id', 'ai.authorized_form_of_name as name')
            ->get()
            ->toArray();
    }

    /**
     * Process form submission
     */
    protected function processForm(sfWebRequest $request, $id = null)
    {
        $this->initFramework();
        $data = $request->getParameter('agreement');
        $isNew = !$id;

        // Capture old values for audit
        $oldValues = [];
        if ($id) {
            $oldValues = $this->captureAgreementValues($id);
        }

        try {
            DB::beginTransaction();

            $agreementData = [
                'donor_id' => $data['donor_id'] ?: null,
                'agreement_type_id' => $data['agreement_type_id'],
                'agreement_number' => $data['agreement_number'] ?: null,
                'status' => $data['status'] ?: 'draft',
                'effective_date' => $data['effective_date'] ?: null,
                'expiry_date' => $data['expiry_date'] ?: null,
                'review_date' => $data['review_date'] ?: null,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            // Handle logo upload
            if ($request->getParameter('remove_logo')) {
                // Remove existing logo
                if ($id) {
                    $existing = DB::table('donor_agreement')->where('id', $id)->first();
                    if ($existing && $existing->logo_path) {
                        $fullPath = $this->config('sf_root_dir') . '/uploads' . $existing->logo_path;
                        if (file_exists($fullPath)) {
                            @unlink($fullPath);
                        }
                    }
                }
                $agreementData['logo_path'] = null;
                $agreementData['logo_filename'] = null;
            } elseif (isset($_FILES['agreement_logo']) && $_FILES['agreement_logo']['error'] === UPLOAD_ERR_OK) {
                // Upload new logo
                $file = $_FILES['agreement_logo'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

                if (in_array($file['type'], $allowedTypes)) {
                    $uploadDir = $this->config('sf_root_dir') . '/uploads/agreements/logos';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'logo_' . uniqid() . '.' . $ext;
                    $destPath = $uploadDir . '/' . $filename;

                    if (move_uploaded_file($file['tmp_name'], $destPath)) {
                        // Remove old logo if exists
                        if ($id) {
                            $existing = DB::table('donor_agreement')->where('id', $id)->first();
                            if ($existing && $existing->logo_path) {
                                $oldPath = $this->config('sf_root_dir') . '/uploads' . $existing->logo_path;
                                if (file_exists($oldPath)) {
                                    @unlink($oldPath);
                                }
                            }
                        }
                        $agreementData['logo_path'] = '/agreements/logos/' . $filename;
                        $agreementData['logo_filename'] = $file['name'];
                    }
                }
            }

            if ($id) {
                DB::table('donor_agreement')->where('id', $id)->update($agreementData);
                $agreementId = $id;
            } else {
                $agreementData['created_at'] = date('Y-m-d H:i:s');
                $agreementId = DB::table('donor_agreement')->insertGetId($agreementData);
            }

            // Save i18n
            DB::table('donor_agreement_i18n')->updateOrInsert(
                ['id' => $agreementId, 'culture' => 'en'],
                ['title' => $data['title'], 'description' => $data['description'] ?? null]
            );

            DB::commit();

            // Capture new values and log audit
            $newValues = $this->captureAgreementValues($agreementId);
            $this->logAudit($isNew ? 'create' : 'update', $agreementId, $oldValues, $newValues);

            $this->getUser()->setFlash('notice', $id ? 'Agreement updated.' : 'Agreement created.');
            $this->redirect(['module' => 'donorAgreement', 'action' => 'view', 'id' => $agreementId]);

        } catch (Exception $e) {
            DB::rollBack();
            $this->getUser()->setFlash('error', 'Error saving agreement: ' . $e->getMessage());
        }
    }

    /**
     * Delete agreement
     */
    public function executeDelete($request)
    {
        $this->initFramework();
        $id = (int)$request->getParameter('id');

        if ($request->isMethod('post')) {
            // Capture values before delete for audit
            $oldValues = $this->captureAgreementValues($id);

            try {
                DB::beginTransaction();

                DB::table('donor_agreement_right')->where('donor_agreement_id', $id)->delete();
                DB::table('donor_agreement_restriction')->where('donor_agreement_id', $id)->delete();
                DB::table('donor_agreement_document')->where('donor_agreement_id', $id)->delete();
                
                $reminderIds = DB::table('donor_agreement_reminder')->where('donor_agreement_id', $id)->pluck('id');
                if ($reminderIds->count() > 0) {
                    DB::table('donor_agreement_reminder_log')->whereIn('donor_agreement_reminder_id', $reminderIds)->delete();
                }
                DB::table('donor_agreement_reminder')->where('donor_agreement_id', $id)->delete();
                DB::table('donor_agreement_i18n')->where('id', $id)->delete();
                DB::table('donor_agreement')->where('id', $id)->delete();

                DB::commit();

                // Log delete audit
                $this->logAudit('delete', $id, $oldValues, []);

                $this->getUser()->setFlash('notice', 'Agreement deleted.');
            } catch (Exception $e) {
                DB::rollBack();
                $this->getUser()->setFlash('error', 'Error deleting: ' . $e->getMessage());
            }
        }

        $this->redirect(['module' => 'donorAgreement', 'action' => 'browse']);
    }

    /**
     * Capture agreement values for audit trail
     */
    protected function captureAgreementValues(int $id): array
    {
        try {
            $row = DB::table('donor_agreement as da')
                ->leftJoin('donor_agreement_i18n as dai', function($j) {
                    $j->on('da.id', '=', 'dai.id')->where('dai.culture', '=', 'en');
                })
                ->where('da.id', $id)
                ->select('da.*', 'dai.title', 'dai.description')
                ->first();

            if (!$row) return [];

            $values = [];
            foreach ((array)$row as $key => $val) {
                if ($val !== null && $val !== '') $values[$key] = $val;
            }
            return $values;
        } catch (Exception $e) {
            error_log("DonorAgreement captureValues ERROR: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Log audit trail entry via AhgAuditService
     */
    protected function logAudit(string $action, int $id, array $oldValues, array $newValues): void
    {
        try {
            // Use AhgAuditService if available
            $auditServicePath = $this->config('sf_root_dir') . '/plugins/ahgAuditTrailPlugin/lib/Services/AhgAuditService.php';
            if (file_exists($auditServicePath)) {
                require_once $auditServicePath;
            }

            if (class_exists('AhgAuditTrail\\Services\\AhgAuditService')) {
                $changedFields = [];
                foreach ($newValues as $key => $val) {
                    if (($oldValues[$key] ?? null) !== $val) {
                        $changedFields[] = $key;
                    }
                }
                if ($action === 'delete') {
                    $changedFields = array_keys($oldValues);
                }

                \AhgAuditTrail\Services\AhgAuditService::logAction(
                    $action,
                    'DonorAgreement',
                    $id,
                    [
                        'title' => $newValues['title'] ?? $oldValues['title'] ?? null,
                        'module' => 'ahgDonorAgreementPlugin',
                        'action_name' => $action,
                        'old_values' => $oldValues,
                        'new_values' => $newValues,
                        'changed_fields' => $changedFields,
                    ]
                );
            }
        } catch (Exception $e) {
            error_log("DonorAgreement AUDIT ERROR: " . $e->getMessage());
        }
    }
}
