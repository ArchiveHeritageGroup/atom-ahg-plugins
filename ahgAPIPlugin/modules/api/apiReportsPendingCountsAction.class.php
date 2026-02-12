<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * API Action for Reports Menu Pending Counts
 * Returns counts for menu badges (pending items that need attention)
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class apiReportsPendingCountsAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->context->user->isAuthenticated()) {
            return $this->renderJson(['error' => 'Unauthorized'], 401);
        }

        $counts = [];

        // Get pending access requests
        $counts['accessRequests'] = $this->getPendingAccessRequests();

        // Get pending loans (overdue or due soon)
        $counts['pendingLoans'] = $this->getPendingLoans();

        // Get condition alerts (items needing attention)
        $counts['conditionAlerts'] = $this->getConditionAlerts();

        // Get valuation alerts (valuations due)
        $counts['valuationAlerts'] = $this->getValuationAlerts();

        // Get pending approvals (workflow items)
        $counts['pendingApprovals'] = $this->getPendingApprovals();

        // Get clearance expiry warnings
        $counts['clearanceExpiry'] = $this->getClearanceExpiryCount();

        return $this->renderJson($counts);
    }

    /**
     * Get pending access requests count
     */
    protected function getPendingAccessRequests()
    {
        try {
            return (int) DB::table('access_request')
                ->where('status', 'pending')
                ->count();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get pending/overdue loans count
     */
    protected function getPendingLoans()
    {
        try {
            return (int) DB::table('spectrum_loan_out')
                ->whereIn('status', ['active', 'overdue'])
                ->whereRaw('loan_end_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)')
                ->count();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get condition alerts count (items with poor condition or needing check)
     */
    protected function getConditionAlerts()
    {
        try {
            return (int) DB::table('spectrum_condition_check')
                ->where(function ($query) {
                    $query->where('overall_condition', '>=', 4)
                        ->orWhereRaw('(next_check_date IS NOT NULL AND next_check_date <= CURDATE())');
                })
                ->distinct()
                ->count('information_object_id');
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get valuation alerts count (valuations due for renewal)
     */
    protected function getValuationAlerts()
    {
        try {
            return (int) DB::table('spectrum_valuation')
                ->where('is_current', 1)
                ->whereRaw('next_valuation_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)')
                ->count();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get pending workflow approvals count
     */
    protected function getPendingApprovals()
    {
        try {
            return (int) DB::table('workflow_state')
                ->whereIn('current_state', ['pending_approval', 'under_review', 'submitted'])
                ->count();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get clearance expiry warnings count
     */
    protected function getClearanceExpiryCount()
    {
        try {
            return (int) DB::table('user_security_clearance')
                ->where('is_active', 1)
                ->whereRaw('expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)')
                ->count();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Render JSON response
     */
    protected function renderJson($data, $statusCode = 200)
    {
        $this->getResponse()->setStatusCode($statusCode);
        echo json_encode($data);

        return sfView::NONE;
    }
}
