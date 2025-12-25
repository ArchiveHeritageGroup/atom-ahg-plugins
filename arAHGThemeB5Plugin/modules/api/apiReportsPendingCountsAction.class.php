<?php

/**
 * API Action for Reports Menu Pending Counts
 * Returns counts for menu badges (pending items that need attention)
 * 
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class apiReportsPendingCountsAction extends sfAction
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
            $sql = "SELECT COUNT(*) as count 
                    FROM access_request 
                    WHERE status = 'pending'";
            
            $conn = Propel::getConnection();
            $stmt = $conn->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $result['count'];
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
            $sql = "SELECT COUNT(*) as count 
                    FROM spectrum_loan_out 
                    WHERE status IN ('active', 'overdue')
                    AND loan_end_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
            
            $conn = Propel::getConnection();
            $stmt = $conn->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $result['count'];
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
            $sql = "SELECT COUNT(DISTINCT information_object_id) as count 
                    FROM spectrum_condition_check 
                    WHERE overall_condition >= 4
                    OR (next_check_date IS NOT NULL AND next_check_date <= CURDATE())";
            
            $conn = Propel::getConnection();
            $stmt = $conn->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $result['count'];
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
            $sql = "SELECT COUNT(*) as count 
                    FROM spectrum_valuation 
                    WHERE is_current = 1
                    AND next_valuation_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)";
            
            $conn = Propel::getConnection();
            $stmt = $conn->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $result['count'];
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
            $sql = "SELECT COUNT(*) as count 
                    FROM workflow_state 
                    WHERE current_state IN ('pending_approval', 'under_review', 'submitted')";
            
            $conn = Propel::getConnection();
            $stmt = $conn->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $result['count'];
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
            $sql = "SELECT COUNT(*) as count 
                    FROM user_security_clearance 
                    WHERE is_active = 1
                    AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)";
            
            $conn = Propel::getConnection();
            $stmt = $conn->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $result['count'];
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
