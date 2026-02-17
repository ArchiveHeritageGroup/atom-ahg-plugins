<?php

use AtomFramework\Http\Controllers\AhgController;
class securityClearanceActions extends AhgController
{
    public function boot(): void
    {
        require_once $this->config('sf_root_dir').'/atom-framework/src/Services/SecurityClearanceService.php';
    }

    /**
     * List all users and their clearances
     */
    public function executeIndex($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->hasCredential('administrator')) {
            $this->getUser()->setFlash('error', 'Administrator access required.');
            $this->redirect('@homepage');
        }

        // Get all users with their clearances
        $this->users = \Illuminate\Database\Capsule\Manager::table('user as u')
            ->leftJoin('user_security_clearance as usc', 'u.id', '=', 'usc.user_id')
            ->leftJoin('security_classification as sc', 'usc.classification_id', '=', 'sc.id')
            ->leftJoin('user as granter', 'usc.granted_by', '=', 'granter.id')
            ->select(
                'u.id',
                'u.username',
                'u.email',
                'u.active',
                'usc.id as clearance_id',
                'usc.classification_id',
                'usc.granted_at',
                'usc.expires_at',
                'usc.notes',
                'sc.name as classification_name',
                'sc.code as classification_code',
                'sc.level as classification_level',
                'granter.username as granted_by_name'
            )
            ->orderBy('u.username')
            ->get()
            ->toArray();

        $this->classifications = \AtomExtensions\Services\SecurityClearanceService::getAllClassifications();
        
        // Stats
        $this->stats = [
            'total_users' => \Illuminate\Database\Capsule\Manager::table('user')->count(),
            'with_clearance' => \Illuminate\Database\Capsule\Manager::table('user_security_clearance')->count(),
            'top_secret' => \Illuminate\Database\Capsule\Manager::table('user_security_clearance as usc')
                ->join('security_classification as sc', 'usc.classification_id', '=', 'sc.id')
                ->where('sc.level', '>=', 4)
                ->count(),
        ];
    }

    /**
     * View single user's clearance details
     */
    public function executeView($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->hasCredential('administrator')) {
            $this->redirect('@homepage');
        }

        $userId = (int) $request->getParameter('id');

        $this->targetUser = \Illuminate\Database\Capsule\Manager::table('user')
            ->where('id', $userId)
            ->first();

        if (!$this->targetUser) {
            $this->forward404('User not found');
        }

        // Use getUserClearanceRecord to show expired clearances in admin view
        $this->clearance = \AtomExtensions\Services\SecurityClearanceService::getUserClearanceRecord($userId);
        $this->classifications = \AtomExtensions\Services\SecurityClearanceService::getAllClassifications();
        
        // Get clearance history
        $this->history = \Illuminate\Database\Capsule\Manager::table('user_security_clearance_log as log')
            ->leftJoin('security_classification as sc', 'log.classification_id', '=', 'sc.id')
            ->leftJoin('user as actor', 'log.changed_by', '=', 'actor.id')
            ->where('log.user_id', $userId)
            ->select(
                'log.*',
                'sc.name as classification_name',
                'sc.code as classification_code',
                'actor.username as changed_by_name'
            )
            ->orderByDesc('log.created_at')
            ->get()
            ->toArray();

        // Get object access grants
        $this->accessGrants = \Illuminate\Database\Capsule\Manager::table('object_access_grant as oag')
            ->leftJoin('user as granter', 'oag.granted_by', '=', 'granter.id')
            ->where('oag.user_id', $userId)
            ->where('oag.active', 1)
            ->select('oag.*', 'granter.username as granted_by_name')
            ->orderByDesc('oag.granted_at')
            ->get()
            ->toArray();

        // Get object titles for access grants
        foreach ($this->accessGrants as &$grant) {
            $grant->object_title = \AtomExtensions\Services\AccessRequestService::getObjectTitle(
                $grant->object_type, 
                $grant->object_id
            );
        }
    }

    /**
     * Grant or update clearance
     */
    public function executeGrant($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->hasCredential('administrator')) {
            $this->redirect('@homepage');
        }

        if ($request->isMethod('post')) {
            $userId = (int) $request->getParameter('user_id');
            $classificationId = (int) $request->getParameter('classification_id');
            $expiresAt = $request->getParameter('expires_at');
            $notes = trim($request->getParameter('notes'));
            $grantedBy = $this->getUser()->getAttribute('user_id');

            $expiresAt = !empty($expiresAt) ? $expiresAt : null;

            if ($classificationId === 0) {
                // Revoke clearance
                $success = \AtomExtensions\Services\SecurityClearanceService::revokeClearance(
                    $userId, $grantedBy, $notes ?: 'Clearance revoked by administrator'
                );
                $message = $success ? 'Clearance revoked successfully.' : 'Failed to revoke clearance.';
            } else {
                // Grant/update clearance
                $success = \AtomExtensions\Services\SecurityClearanceService::grantClearance(
                    $userId, $classificationId, $grantedBy, $expiresAt, $notes
                );
                $message = $success ? 'Clearance granted successfully.' : 'Failed to grant clearance.';
            }

            $this->getUser()->setFlash($success ? 'success' : 'error', $message);
        }

        $this->redirect('@security_clearances');
    }

    /**
     * Revoke clearance
     */
    public function executeRevoke($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->hasCredential('administrator')) {
            $this->redirect('@homepage');
        }

        $userId = (int) $request->getParameter('id');
        $grantedBy = $this->getUser()->getAttribute('user_id');
        $notes = $request->getParameter('notes', 'Clearance revoked by administrator');

        $success = \AtomExtensions\Services\SecurityClearanceService::revokeClearance($userId, $grantedBy, $notes);

        $this->getUser()->setFlash(
            $success ? 'success' : 'error',
            $success ? 'Clearance revoked.' : 'Failed to revoke clearance.'
        );

        $this->redirect('@security_clearances');
    }

    /**
     * Bulk grant clearances
     */
    public function executeBulkGrant($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->hasCredential('administrator')) {
            $this->redirect('@homepage');
        }

        if ($request->isMethod('post')) {
            $userIds = $request->getParameter('user_ids', []);
            $classificationId = (int) $request->getParameter('classification_id');
            $grantedBy = $this->getUser()->getAttribute('user_id');
            $notes = trim($request->getParameter('notes', 'Bulk grant by administrator'));

            $successCount = 0;
            foreach ($userIds as $userId) {
                if (\AtomExtensions\Services\SecurityClearanceService::grantClearance(
                    (int) $userId, $classificationId, $grantedBy, null, $notes
                )) {
                    $successCount++;
                }
            }

            $this->getUser()->setFlash('success', "Clearance granted to {$successCount} users.");
        }

        $this->redirect('@security_clearances');
    }

    /**
     * Revoke object access grant
     */
    public function executeRevokeAccess($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->hasCredential('administrator')) {
            $this->redirect('@homepage');
        }

        $grantId = (int) $request->getParameter('id');
        $userId = (int) $request->getParameter('user_id');
        $revokedBy = $this->getUser()->getAttribute('user_id');

        require_once $this->config('sf_root_dir').'/atom-framework/src/Services/AccessRequestService.php';
        $success = \AtomExtensions\Services\AccessRequestService::revokeObjectAccess($grantId, $revokedBy);

        $this->getUser()->setFlash(
            $success ? 'success' : 'error',
            $success ? 'Access revoked.' : 'Failed to revoke access.'
        );

        $this->redirect('@security_clearance_view?id=' . $userId);
    }

    /**
     * Security Dashboard
     */

    /**
     * Security Dashboard
     */
    public function executeDashboard($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->hasCredential('administrator')) {
            $this->getUser()->setFlash('error', 'Administrator access required.');
            $this->redirect('@homepage');
        }

        $db = \Illuminate\Database\Capsule\Manager::class;

        // Statistics
        $this->statistics = [
            'pending_requests' => $db::table('security_access_request')->where('status', 'pending')->count(),
            'expiring_clearances' => $db::table('user_security_clearance')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', date('Y-m-d', strtotime('+30 days')))
                ->where('expires_at', '>', date('Y-m-d'))
                ->count(),
            'recent_denials' => $db::table('security_access_request')
                ->where('status', 'denied')
                ->where('updated_at', '>=', date('Y-m-d', strtotime('-7 days')))
                ->count(),
            'reviews_due' => $db::table('object_declassification_schedule')
                ->where('scheduled_date', '<=', date('Y-m-d', strtotime('+30 days')))
                ->where('processed', 0)
                ->count(),
            'clearances_by_level' => $db::table('user_security_clearance as usc')
                ->join('security_classification as sc', 'usc.classification_id', '=', 'sc.id')
                ->select('sc.name', 'sc.color', $db::raw('COUNT(*) as count'))
                ->groupBy('sc.id', 'sc.name', 'sc.color')
                ->orderBy('sc.level')
                ->get()
                ->toArray(),
            'objects_by_level' => $db::table('object_security_classification as osc')
                ->join('security_classification as sc', 'osc.classification_id', '=', 'sc.id')
                ->select('sc.name', 'sc.color', $db::raw('COUNT(*) as count'))
                ->groupBy('sc.id', 'sc.name', 'sc.color')
                ->orderBy('sc.level')
                ->get()
                ->toArray(),
        ];

        // Pending requests - using security_access_request table
        $this->pendingRequests = $db::table('security_access_request as sar')
            ->join('user as u', 'sar.user_id', '=', 'u.id')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('sar.object_id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('sar.status', 'pending')
            ->select('sar.*', 'u.username', 'ioi.title as object_title', 'sar.id as request_id')
            ->orderByDesc('sar.created_at')
            ->limit(10)
            ->get()
            ->toArray();

        // Expiring clearances
        $this->expiringClearances = $db::table('user_security_clearance as usc')
            ->join('user as u', 'usc.user_id', '=', 'u.id')
            ->join('security_classification as sc', 'usc.classification_id', '=', 'sc.id')
            ->whereNotNull('usc.expires_at')
            ->where('usc.expires_at', '<=', date('Y-m-d', strtotime('+30 days')))
            ->where('usc.expires_at', '>', date('Y-m-d'))
            ->select(
                'usc.*', 
                'u.username', 
                'u.id as user_id',
                'sc.name as clearance_name', 
                'sc.color',
                $db::raw('DATEDIFF(usc.expires_at, CURDATE()) as days_remaining'),
                $db::raw("'none' as renewal_status")
            )
            ->orderBy('usc.expires_at')
            ->limit(10)
            ->get()
            ->toArray();

        // Due declassifications
        $this->dueDeclassifications = $db::table('object_declassification_schedule as ods')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('ods.object_id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('information_object as io', 'ods.object_id', '=', 'io.id')
            ->leftJoin('security_classification as sc_from', 'ods.from_classification_id', '=', 'sc_from.id')
            ->leftJoin('security_classification as sc_to', 'ods.to_classification_id', '=', 'sc_to.id')
            ->where('ods.scheduled_date', '<=', date('Y-m-d', strtotime('+30 days')))
            ->where('ods.processed', 0)
            ->select(
                'ods.*',
                'ioi.title',
                'io.identifier',
                'sc_from.name as from_classification',
                'sc_to.name as to_classification',
                'sc_to.id as to_classification_id'
            )
            ->orderBy('ods.scheduled_date')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Security Reports
     */
    public function executeReport($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->hasCredential('administrator')) {
            $this->getUser()->setFlash('error', 'Administrator access required.');
            $this->redirect('@homepage');
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $period = $request->getParameter('period', '30 days');
        $since = date('Y-m-d H:i:s', strtotime("-{$period}"));

        $this->period = $period;

        // Clearance statistics
        $this->clearanceStats = [
            'total_users' => $db::table('user')->where('active', 1)->count(),
            'with_clearance' => $db::table('user_security_clearance')->distinct('user_id')->count('user_id'),
            'without_clearance' => $db::table('user')
                ->where('active', 1)
                ->whereNotIn('id', function($q) {
                    $q->select('user_id')->from('user_security_clearance');
                })
                ->count(),
        ];

        // Clearances by level
        $this->clearancesByLevel = $db::table('user_security_clearance as usc')
            ->join('security_classification as sc', 'usc.classification_id', '=', 'sc.id')
            ->select('sc.name', 'sc.code', 'sc.color', 'sc.level', $db::raw('COUNT(*) as count'))
            ->groupBy('sc.id', 'sc.name', 'sc.code', 'sc.color', 'sc.level')
            ->orderBy('sc.level')
            ->get()
            ->toArray();

        // Classified objects by level
        $this->objectsByLevel = $db::table('object_security_classification as osc')
            ->join('security_classification as sc', 'osc.classification_id', '=', 'sc.id')
            ->select('sc.name', 'sc.code', 'sc.color', 'sc.level', $db::raw('COUNT(*) as count'))
            ->groupBy('sc.id', 'sc.name', 'sc.code', 'sc.color', 'sc.level')
            ->orderBy('sc.level')
            ->get()
            ->toArray();

        // Recent activity
        $this->recentActivity = $db::table('spectrum_audit_log as sal')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('sal.object_id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('sal.action_date', '>=', $since)
            ->where(function($q) {
                $q->where('sal.procedure_type', 'LIKE', '%security%')
                  ->orWhere('sal.procedure_type', 'LIKE', '%clearance%');
            })
            ->select('sal.*', 'ioi.title as object_title')
            ->orderByDesc('sal.action_date')
            ->limit(20)
            ->get()
            ->toArray();

        // Access requests summary - using security_access_request
        $this->requestStats = [
            'pending' => $db::table('security_access_request')->where('status', 'pending')->count(),
            'approved' => $db::table('security_access_request')
                ->where('status', 'approved')
                ->where('updated_at', '>=', $since)
                ->count(),
            'denied' => $db::table('security_access_request')
                ->where('status', 'denied')
                ->where('updated_at', '>=', $since)
                ->count(),
        ];
    }

    /**
     * Compartments management
     */
    public function executeCompartments($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->hasCredential('administrator')) {
            $this->getUser()->setFlash('error', 'Administrator access required.');
            $this->redirect('@homepage');
        }

        $db = \Illuminate\Database\Capsule\Manager::class;

        // Get all compartments
        $this->compartments = $db::table('security_compartment')
            ->orderBy('name')
            ->get()
            ->toArray();

        // Get user counts per compartment
        $this->userCounts = $db::table('user_compartment_access')
            ->select('compartment_id', $db::raw('COUNT(*) as count'))
            ->groupBy('compartment_id')
            ->pluck('count', 'compartment_id')
            ->toArray();
    }

    /**
     * Security Compliance Dashboard
     */
    public function executeSecurityCompliance($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('/user/login');
        }

        $this->stats = [
            'classified_objects' => \Illuminate\Database\Capsule\Manager::table('security_classification')->count(),
            'pending_reviews' => 0,
            'cleared_users' => \Illuminate\Database\Capsule\Manager::table('user_security_clearance')->count(),
            'access_logs_today' => \Illuminate\Database\Capsule\Manager::table('user_security_clearance_log')
                ->whereDate('created_at', date('Y-m-d'))->count(),
        ];
        $this->pendingReviews = [];
        $this->retentionSchedules = [];
        $this->recentLogs = \Illuminate\Database\Capsule\Manager::table('user_security_clearance_log')
            ->orderBy('created_at', 'desc')->limit(10)->get()->toArray();
    }
}
