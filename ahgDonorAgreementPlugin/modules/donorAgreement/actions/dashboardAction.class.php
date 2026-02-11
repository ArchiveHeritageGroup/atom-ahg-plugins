<?php

use AtomFramework\Http\Controllers\AhgController;
class donorAgreementDashboardAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }
        
        // Initialize Laravel DB
        $this->initDatabase();
        
        $repositoryId = null;
        if ($request->hasParameter('repository')) {
            $repositoryId = (int) $request->getParameter('repository');
        }
        
        $this->selectedRepository = $repositoryId;
        $this->repositories = QubitRepository::getAll();
        
        // Get statistics
        $this->statistics = $this->getStatistics($repositoryId);
        
        // Get alerts
        $this->alerts = $this->getAlertCounts($repositoryId);
        
        // Get recent agreements
        $this->recentAgreements = $this->getRecentAgreements($repositoryId);
        
        // Get expiring soon
        $this->expiringSoon = $this->getExpiringSoon($repositoryId);
        
        // Get pending reminders
        $this->pendingReminders = $this->getPendingReminders($repositoryId);
        
        // Get chart data
        $this->typeChartData = json_encode($this->getTypeChartData($repositoryId));
        $this->trendChartData = json_encode($this->getTrendChartData($repositoryId));
        
        // Build dashboard data array for template compatibility
        $this->dashboardData = [
            'statistics' => $this->statistics,
            'recent_agreements' => $this->recentAgreements,
            'expiring_soon' => $this->expiringSoon,
            'pending_reminders' => $this->pendingReminders,
        ];
    }

    protected function initDatabase()
    {
        $bootstrap = $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
    }

    protected function getStatistics($repositoryId = null)
    {
        $db = \Illuminate\Database\Capsule\Manager::class;
        
        // Total donors
        $totalDonors = \Illuminate\Database\Capsule\Manager::table('donor')->count();
        
        // Active donors (donors with active agreements)
        $activeDonors = \Illuminate\Database\Capsule\Manager::table('donor as d')
            ->join('donor_agreement as da', 'd.id', '=', 'da.donor_id')
            ->where('da.status', 'active')
            ->distinct()
            ->count('d.id');
        
        // Total agreements
        $totalAgreements = \Illuminate\Database\Capsule\Manager::table('donor_agreement')->count();
        
        // Active agreements
        $activeAgreements = \Illuminate\Database\Capsule\Manager::table('donor_agreement')
            ->where('status', 'active')
            ->count();
        
        // Draft agreements
        $draftAgreements = \Illuminate\Database\Capsule\Manager::table('donor_agreement')
            ->where('status', 'draft')
            ->count();
        
        // Pending review
        $pendingReview = \Illuminate\Database\Capsule\Manager::table('donor_agreement')
            ->where('status', 'pending_review')
            ->count();
        
        // Expiring soon (within 30 days)
        $expiringSoon = \Illuminate\Database\Capsule\Manager::table('donor_agreement')
            ->where('status', 'active')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', date('Y-m-d', strtotime('+30 days')))
            ->whereDate('expiry_date', '>=', date('Y-m-d'))
            ->count();
        
        // Expired
        $expired = \Illuminate\Database\Capsule\Manager::table('donor_agreement')
            ->where('status', 'expired')
            ->count();
        
        // Terminated
        $terminated = \Illuminate\Database\Capsule\Manager::table('donor_agreement')
            ->where('status', 'terminated')
            ->count();
        
        // Review due
        $reviewDue = \Illuminate\Database\Capsule\Manager::table('donor_agreement')
            ->whereNotNull('review_date')
            ->whereDate('review_date', '<=', date('Y-m-d', strtotime('+30 days')))
            ->where('status', 'active')
            ->count();
        
        return [
            'total_donors' => $totalDonors,
            'active_donors' => $activeDonors,
            'inactive_donors' => $totalDonors - $activeDonors,
            'total_agreements' => $totalAgreements,
            'active_agreements' => $activeAgreements,
            'draft_agreements' => $draftAgreements,
            'pending_review' => $pendingReview,
            'expiring_soon' => $expiringSoon,
            'expired' => $expired,
            'terminated' => $terminated,
            'review_due' => $reviewDue,
        ];
    }

    protected function getAlertCounts($repositoryId = null)
    {
        // Pending reminders
        $pendingReminders = \Illuminate\Database\Capsule\Manager::table('donor_agreement_reminder')
            ->where('status', 'active')
            ->whereDate('reminder_date', '<=', date('Y-m-d'))
            ->where(function($q) {
                $q->whereNull('is_sent')->orWhere('is_sent', 0);
            })
            ->count();
        
        // Active restrictions
        $activeRestrictions = \Illuminate\Database\Capsule\Manager::table('donor_agreement_restriction')
            ->where(function($q) {
                $q->whereNull('release_date')
                  ->orWhereDate('release_date', '>', date('Y-m-d'));
            })
            ->count();
        
        return [
            'pending_reminders' => $pendingReminders,
            'active_restrictions' => $activeRestrictions,
        ];
    }

    protected function getRecentAgreements($repositoryId = null, $limit = 5)
    {
        return \Illuminate\Database\Capsule\Manager::table('donor_agreement as da')
            ->leftJoin('donor_agreement_i18n as dai', function($join) {
                $join->on('da.id', '=', 'dai.id')->where('dai.culture', '=', 'en');
            })
            ->leftJoin('agreement_type as at', 'da.agreement_type_id', '=', 'at.id')
            ->leftJoin('donor as d', 'da.donor_id', '=', 'd.id')
            ->leftJoin('actor as a', 'd.id', '=', 'a.id')
            ->leftJoin('actor_i18n as ai', function($join) {
                $join->on('a.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
            })
            ->select([
                'da.id',
                'da.agreement_number',
                'da.title',
                'da.status',
                'da.created_at',
                'at.name as agreement_type_name',
                'at.color as agreement_type_color',
                'ai.authorized_form_of_name as donor_name'
            ])
            ->orderBy('da.created_at', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }

    protected function getExpiringSoon($repositoryId = null, $limit = 5)
    {
        return \Illuminate\Database\Capsule\Manager::table('donor_agreement as da')
            ->leftJoin('donor_agreement_i18n as dai', function($join) {
                $join->on('da.id', '=', 'dai.id')->where('dai.culture', '=', 'en');
            })
            ->leftJoin('donor as d', 'da.donor_id', '=', 'd.id')
            ->leftJoin('actor as a', 'd.id', '=', 'a.id')
            ->leftJoin('actor_i18n as ai', function($join) {
                $join->on('a.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
            })
            ->where('da.status', 'active')
            ->whereNotNull('da.expiry_date')
            ->whereDate('da.expiry_date', '<=', date('Y-m-d', strtotime('+30 days')))
            ->whereDate('da.expiry_date', '>=', date('Y-m-d'))
            ->select([
                'da.id',
                'da.agreement_number',
                'da.title',
                'da.expiry_date',
                'ai.authorized_form_of_name as donor_name'
            ])
            ->orderBy('da.expiry_date', 'asc')
            ->limit($limit)
            ->get()
            ->all();
    }

    protected function getPendingReminders($repositoryId = null, $limit = 5)
    {
        return \Illuminate\Database\Capsule\Manager::table('donor_agreement_reminder as r')
            ->join('donor_agreement as da', 'r.donor_agreement_id', '=', 'da.id')
            ->leftJoin('donor_agreement_i18n as dai', function($join) {
                $join->on('da.id', '=', 'dai.id')->where('dai.culture', '=', 'en');
            })
            ->where('r.status', 'active')
            ->whereDate('r.reminder_date', '<=', date('Y-m-d', strtotime('+7 days')))
            ->where(function($q) {
                $q->whereNull('r.is_sent')->orWhere('r.is_sent', 0);
            })
            ->select([
                'r.id',
                'r.donor_agreement_id',
                'r.subject',
                'r.reminder_type',
                'r.reminder_date',
                'da.agreement_number'
            ])
            ->orderBy('r.reminder_date', 'asc')
            ->limit($limit)
            ->get()
            ->all();
    }

    protected function getTypeChartData($repositoryId = null)
    {
        $data = \Illuminate\Database\Capsule\Manager::table('donor_agreement as da')
            ->join('agreement_type as at', 'da.agreement_type_id', '=', 'at.id')
            ->select('at.name', \Illuminate\Database\Capsule\Manager::raw('COUNT(*) as count'))
            ->groupBy('at.id', 'at.name')
            ->get();
        
        $labels = [];
        $values = [];
        
        foreach ($data as $row) {
            $labels[] = $row->name;
            $values[] = $row->count;
        }
        
        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    protected function getTrendChartData($repositoryId = null)
    {
        $months = [];
        $counts = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = strtotime("-{$i} months");
            $yearMonth = date('Y-m', $date);
            $label = date('M Y', $date);
            
            $count = \Illuminate\Database\Capsule\Manager::table('donor_agreement')
                ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$yearMonth])
                ->count();
            
            $months[] = $label;
            $counts[] = $count;
        }
        
        return [
            'labels' => $months,
            'values' => $counts,
        ];
    }
}
