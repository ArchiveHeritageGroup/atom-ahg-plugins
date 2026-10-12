<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

class securityActions extends AhgController
{
    public function executeAccessRequests($request)
    {
        // Check admin permissions
        if (!$this->getUser()->isAuthenticated()) {
            $this->forward('admin', 'secure');
        }
        
        // Get counts
        $this->pendingCount = 0;
        $this->approvedTodayCount = 0;
        $this->deniedTodayCount = 0;
        $this->thisMonthCount = 0;
        
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');
        
        try {
            $this->pendingCount = DB::table('security_access_request')
                ->where('status', 'pending')
                ->count();
            
            $this->approvedTodayCount = DB::table('security_access_request')
                ->where('status', 'approved')
                ->whereDate('reviewed_at', $today)
                ->count();
            
            $this->deniedTodayCount = DB::table('security_access_request')
                ->where('status', 'denied')
                ->whereDate('reviewed_at', $today)
                ->count();
            
            $this->thisMonthCount = DB::table('security_access_request')
                ->whereDate('created_at', '>=', $monthStart)
                ->count();
        } catch (\Exception $e) {
            \class_exists('AhgCore\\Core\\AhgLog') && \AhgCore\Core\AhgLog::swallowed($e, basename(__FILE__).':'.__LINE__);
            // Tables may not exist
        }
        
        // Get pending requests
        $this->pendingRequests = [];
        try {
            $this->pendingRequests = DB::table('security_access_request as sar')
                ->leftJoin('user as u', 'sar.user_id', '=', 'u.id')
                ->leftJoin('actor_i18n as ai', function($join) {
                    $join->on('u.id', '=', 'ai.id')->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                })
                ->leftJoin('information_object_i18n as ioi', function($join) {
                    $join->on('sar.object_id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                })
                ->leftJoin('slug as s', 'sar.object_id', '=', 's.object_id')
                ->where('sar.status', 'pending')
                ->select(
                    'sar.*',
                    'u.username',
                    DB::raw('COALESCE(ai.authorized_form_of_name, u.username) as user_name'),
                    'ioi.title as object_title',
                    's.slug'
                )
                ->orderBy('sar.created_at', 'desc')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            \class_exists('AhgCore\\Core\\AhgLog') && \AhgCore\Core\AhgLog::swallowed($e, basename(__FILE__).':'.__LINE__);
            // Table may not exist
        }
    }
    
    public function executeApproveRequest($request)
    {
        if (!$request->isMethod('post')) {
            $this->forward404();
        }
        
        if (!$this->getUser()->isAuthenticated()) {
            $this->forward('admin', 'secure');
        }
        
        $requestId = $request->getParameter('id');
        $note = $request->getParameter('note', '');
        $userId = $this->getUser()->getAttribute('user_id');
        $durationHours = $request->getParameter('duration_hours', 24);
        
        try {
            $accessRequest = DB::table('security_access_request')
                ->where('id', $requestId)
                ->first();
                
            if ($accessRequest) {
                DB::table('security_access_request')
                    ->where('id', $requestId)
                    ->update([
                        'status' => 'approved',
                        'reviewed_by' => $userId,
                        'reviewed_at' => date('Y-m-d H:i:s'),
                        'review_notes' => $note,
                        'access_granted_until' => date('Y-m-d H:i:s', strtotime("+{$durationHours} hours")),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                
                // Log the action
                // Through logAccess: the direct insert named details, which the table
                // does not have, and would have broken the audit hash chain anyway.
                \SecurityClearanceService::logAccess((int) $accessRequest->user_id, $accessRequest->object_id ? (int) $accessRequest->object_id : null, null, null, 'access_granted', true, 'Request #'.$requestId.' approved by user '.$userId.($note ? ': '.$note : ''));
            }
        } catch (\Exception $e) {
            \class_exists('AhgCore\\Core\\AhgLog') && \AhgCore\Core\AhgLog::swallowed($e, basename(__FILE__).':'.__LINE__);
            // Handle error
        }
        
        $this->redirect(['module' => 'security', 'action' => 'accessRequests']);
    }
    
    public function executeDenyRequest($request)
    {
        if (!$request->isMethod('post')) {
            $this->forward404();
        }
        
        if (!$this->getUser()->isAuthenticated()) {
            $this->forward('admin', 'secure');
        }
        
        $requestId = $request->getParameter('id');
        $note = $request->getParameter('note', '');
        $userId = $this->getUser()->getAttribute('user_id');
        
        try {
            $accessRequest = DB::table('security_access_request')
                ->where('id', $requestId)
                ->first();
                
            if ($accessRequest) {
                DB::table('security_access_request')
                    ->where('id', $requestId)
                    ->update([
                        'status' => 'denied',
                        'reviewed_by' => $userId,
                        'reviewed_at' => date('Y-m-d H:i:s'),
                        'review_notes' => $note,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                
                // Log the action
                // Through logAccess: the direct insert named details, which the table
                // does not have, and would have broken the audit hash chain anyway.
                \SecurityClearanceService::logAccess((int) $accessRequest->user_id, $accessRequest->object_id ? (int) $accessRequest->object_id : null, null, null, 'access_denied', false, 'Request #'.$requestId.' denied by user '.$userId.($note ? ': '.$note : ''));
            }
        } catch (\Exception $e) {
            \class_exists('AhgCore\\Core\\AhgLog') && \AhgCore\Core\AhgLog::swallowed($e, basename(__FILE__).':'.__LINE__);
            // Handle error
        }
        
        $this->redirect(['module' => 'security', 'action' => 'accessRequests']);
    }
    
    public function executeViewRequest($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->forward('admin', 'secure');
        }
        
        $requestId = $request->getParameter('id');
        
        try {
            $this->accessRequest = DB::table('security_access_request as sar')
                ->leftJoin('user as u', 'sar.user_id', '=', 'u.id')
                ->leftJoin('actor_i18n as ai', function($join) {
                    $join->on('u.id', '=', 'ai.id')->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                })
                ->leftJoin('information_object_i18n as ioi', function($join) {
                    $join->on('sar.object_id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                })
                ->leftJoin('slug as s', 'sar.object_id', '=', 's.object_id')
                ->where('sar.id', $requestId)
                ->select(
                    'sar.*',
                    'u.username',
                    DB::raw('COALESCE(ai.authorized_form_of_name, u.username) as user_name'),
                    'ioi.title as object_title',
                    's.slug'
                )
                ->first();
                
            if (!$this->accessRequest) {
                $this->forward404();
            }
        } catch (\Exception $e) {
            $this->forward404();
        }
    }
}
