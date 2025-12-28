<?php

use Illuminate\Database\Capsule\Manager as DB;

class securityActions extends sfActions
{
    public function executeAccessRequests(sfWebRequest $request)
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
            // Tables may not exist
        }
        
        // Get pending requests
        $this->pendingRequests = [];
        try {
            $this->pendingRequests = DB::table('security_access_request as sar')
                ->leftJoin('user as u', 'sar.user_id', '=', 'u.id')
                ->leftJoin('actor_i18n as ai', function($join) {
                    $join->on('u.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
                })
                ->leftJoin('information_object_i18n as ioi', function($join) {
                    $join->on('sar.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
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
            // Table may not exist
        }
    }
    
    public function executeApproveRequest(sfWebRequest $request)
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
                DB::table('security_access_log')->insert([
                    'user_id' => $accessRequest->user_id,
                    'object_id' => $accessRequest->object_id,
                    'action' => 'access_granted',
                    'details' => json_encode(['request_id' => $requestId, 'approved_by' => $userId]),
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        } catch (\Exception $e) {
            // Handle error
        }
        
        $this->redirect(['module' => 'security', 'action' => 'accessRequests']);
    }
    
    public function executeDenyRequest(sfWebRequest $request)
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
                DB::table('security_access_log')->insert([
                    'user_id' => $accessRequest->user_id,
                    'object_id' => $accessRequest->object_id,
                    'action' => 'access_denied',
                    'details' => json_encode(['request_id' => $requestId, 'denied_by' => $userId]),
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        } catch (\Exception $e) {
            // Handle error
        }
        
        $this->redirect(['module' => 'security', 'action' => 'accessRequests']);
    }
    
    public function executeViewRequest(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->forward('admin', 'secure');
        }
        
        $requestId = $request->getParameter('id');
        
        try {
            $this->accessRequest = DB::table('security_access_request as sar')
                ->leftJoin('user as u', 'sar.user_id', '=', 'u.id')
                ->leftJoin('actor_i18n as ai', function($join) {
                    $join->on('u.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
                })
                ->leftJoin('information_object_i18n as ioi', function($join) {
                    $join->on('sar.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
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
