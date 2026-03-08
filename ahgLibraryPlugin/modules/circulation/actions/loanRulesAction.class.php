<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Loan rules management.
 *
 * View and edit loan rules (loan period, max renewals, max checkouts, fines)
 * per material type or patron type.
 */
class circulationLoanRulesAction extends AhgController
{
    public function execute($request)
    {
        
        // Load framework
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';

        $this->notice = $this->getUser()->getFlash('notice');
        $this->error = $this->getUser()->getFlash('error');

        // Handle POST — save loan rules
        if ('POST' === $request->getMethod()) {
            $this->saveLoanRules($request);

            return;
        }

        // Load existing loan rules
        try {
            $this->loanRules = DB::table('library_loan_rule')
                ->orderBy('material_type')
                ->orderBy('patron_type')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            $this->loanRules = [];
        }

        // Load material types for dropdown
        try {
            $this->materialTypes = DB::table('library_item')
                ->distinct()
                ->whereNotNull('material_type')
                ->where('material_type', '!=', '')
                ->pluck('material_type')
                ->toArray();
        } catch (\Exception $e) {
            $this->materialTypes = [];
        }

        // Default material types if none in DB
        if (empty($this->materialTypes)) {
            $this->materialTypes = [
                'book', 'periodical', 'audiovisual', 'electronic',
                'manuscript', 'map', 'music', 'serial',
            ];
        }

        // Patron types
        $this->patronTypes = ['standard', 'staff', 'student', 'researcher', 'inter-library'];
    }

    /**
     * Save loan rules from POST data.
     */
    protected function saveLoanRules($request): void
    {
        $ruleId = $request->getParameter('rule_id');
        $materialType = trim($request->getParameter('material_type', ''));
        $patronType = trim($request->getParameter('patron_type', ''));
        $loanPeriodDays = (int) $request->getParameter('loan_period_days', 14);
        $maxRenewals = (int) $request->getParameter('max_renewals', 2);
        $maxCheckouts = (int) $request->getParameter('max_checkouts', 5);
        $finePerDay = (float) $request->getParameter('fine_per_day', 0);
        $isRenewable = $request->getParameter('is_renewable') ? 1 : 0;

        if (empty($materialType)) {
            $this->getUser()->setFlash('error', __('Material type is required.'));
            $this->redirect(['module' => 'circulation', 'action' => 'loanRules']);
        }

        try {
            $data = [
                'material_type' => $materialType,
                'patron_type' => $patronType ?: null,
                'loan_period_days' => $loanPeriodDays,
                'max_renewals' => $maxRenewals,
                'max_checkouts' => $maxCheckouts,
                'fine_per_day' => $finePerDay,
                'is_renewable' => $isRenewable,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if (!empty($ruleId)) {
                DB::table('library_loan_rule')
                    ->where('id', $ruleId)
                    ->update($data);
                $this->getUser()->setFlash('notice', __('Loan rule updated successfully.'));
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                DB::table('library_loan_rule')->insert($data);
                $this->getUser()->setFlash('notice', __('Loan rule created successfully.'));
            }
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', __('Failed to save loan rule: %1%', ['%1%' => $e->getMessage()]));
        }

        $this->redirect(['module' => 'circulation', 'action' => 'loanRules']);
    }
}
