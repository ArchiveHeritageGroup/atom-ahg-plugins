<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

class researchActions extends AhgController
{
    protected $service;

    public function boot(): void
    {
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ResearchService.php';
        $this->service = new ResearchService();
    }

    public function executeIndex($request)
    {
        $this->redirect("research/dashboard");
    }

    public function executeDashboard($request)
    {
        $this->stats = $this->service->getDashboardStats();
        $this->researcher = null;
        if ($this->getUser()->isAuthenticated()) {
            $userId = $this->getUser()->getAttribute('user_id');
            $this->researcher = $this->service->getResearcherByUserId($userId);
        }
        $this->pendingResearchers = $this->service->getResearchers(['status' => 'pending']);
        $this->todayBookings = DB::table('research_booking as b')
            ->join('research_researcher as r', 'b.researcher_id', '=', 'r.id')
            ->join('research_reading_room as rm', 'b.reading_room_id', '=', 'rm.id')
            ->where('b.booking_date', date('Y-m-d'))
            ->whereIn('b.status', ['pending', 'confirmed'])
            ->select('b.*', 'r.first_name', 'r.last_name', 'rm.name as room_name')
            ->orderBy('b.start_time')->get()->toArray();
    }

    public function executeRegister($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->getUser()->setFlash('error', 'Please log in to register');
            $this->redirect('user/login');
        }
        $userId = $this->getUser()->getAttribute('user_id');
        $existing = $this->service->getResearcherByUserId($userId);
        if ($existing) {
            // If rejected, allow re-registration
            if ($existing->status === 'rejected') {
                $this->existingResearcher = $existing;
                // Will update existing record instead of creating new
            } else {
                $this->redirect('research/profile');
            }
        }
        $this->user = DB::table('user')->where('id', $userId)->first();
        if ($request->isMethod('post')) {
            try {
                $data = [
                    'user_id' => $userId,
                    'title' => $request->getParameter('title'),
                    'first_name' => $request->getParameter('first_name'),
                    'last_name' => $request->getParameter('last_name'),
                    'email' => $request->getParameter('email'),
                    'phone' => $request->getParameter('phone'),
                    'affiliation_type' => $request->getParameter('affiliation_type'),
                    'institution' => $request->getParameter('institution'),
                    'department' => $request->getParameter('department'),
                    'position' => $request->getParameter('position'),
                    'research_interests' => $request->getParameter('research_interests'),
                    'current_project' => $request->getParameter('current_project'),
                    'orcid_id' => $request->getParameter('orcid_id'),
                    'id_type' => $request->getParameter('id_type'),
                    'id_number' => $request->getParameter('id_number'),
                    'student_id' => $request->getParameter('student_id'),
                ];
                
                // If re-registering after rejection, update existing record
                if (isset($this->existingResearcher) && $this->existingResearcher) {
                    $data['status'] = 'pending';
                    $data['rejection_reason'] = null;
                    DB::table('research_researcher')
                        ->where('id', $this->existingResearcher->id)
                        ->update($data);
                    $this->getUser()->setFlash('success', 'Re-registration submitted for review');
                } else {
                    $this->service->registerResearcher($data);
                    $this->getUser()->setFlash('success', 'Registration submitted');
                }
                $this->redirect('research/registrationComplete');
            } catch (Exception $e) {
                if ($e->getMessage()) { $this->getUser()->setFlash('error', $e->getMessage()); }
            }
        }
    }

    public function executeProfile($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }
        if ($request->isMethod('post')) {
            $this->service->updateResearcher($this->researcher->id, [
                'title' => $request->getParameter('title'),
                'first_name' => $request->getParameter('first_name'),
                'last_name' => $request->getParameter('last_name'),
                'phone' => $request->getParameter('phone'),
                'affiliation_type' => $request->getParameter('affiliation_type'),
                'institution' => $request->getParameter('institution'),
                'department' => $request->getParameter('department'),
                'position' => $request->getParameter('position'),
                'research_interests' => $request->getParameter('research_interests'),
                'current_project' => $request->getParameter('current_project'),
                'orcid_id' => $request->getParameter('orcid_id'),
            ]);
            $this->getUser()->setFlash('success', 'Profile updated');
            $this->redirect('research/profile');
        }
        $this->bookings = $this->service->getResearcherBookings($this->researcher->id);
        $this->collections = $this->service->getCollections($this->researcher->id);
        $this->savedSearches = $this->service->getSavedSearches($this->researcher->id);
    }

    public function executeResearchers($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $this->researchers = $this->service->getResearchers([
            'status' => $request->getParameter('status'),
            'search' => $request->getParameter('q'),
        ]);
        $this->currentStatus = $request->getParameter('status');
    }

    public function executeViewResearcher($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $id = (int) $request->getParameter('id');
        $this->researcher = $this->service->getResearcher($id);
        if (!$this->researcher) { $this->forward404('Not found'); }
        if ($request->isMethod('post')) {
            $action = $request->getParameter('booking_action');
            $adminId = $this->getUser()->getAttribute('user_id');
            if ($action === 'approve') {
                $this->service->approveResearcher($id, $adminId);
                $this->getUser()->setFlash('success', 'Approved');
            } elseif ($action === 'suspend') {
                DB::table('research_researcher')->where('id', $id)->update(['status' => 'suspended']);
                $this->getUser()->setFlash('success', 'Suspended');
            }
            $this->redirect('research/viewResearcher?id=' . $id);
        }
        $this->bookings = $this->service->getResearcherBookings($id);
    }

    public function executeBookings($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $this->rooms = $this->service->getReadingRooms();
        $this->pendingBookings = DB::table('research_booking as b')
            ->join('research_researcher as r', 'b.researcher_id', '=', 'r.id')
            ->join('research_reading_room as rm', 'b.reading_room_id', '=', 'rm.id')
            ->where('b.status', 'pending')
            ->select('b.*', 'r.first_name', 'r.last_name', 'r.email', 'rm.name as room_name')
            ->orderBy('b.booking_date')->get()->toArray();
        $this->upcomingBookings = DB::table('research_booking as b')
            ->join('research_researcher as r', 'b.researcher_id', '=', 'r.id')
            ->join('research_reading_room as rm', 'b.reading_room_id', '=', 'rm.id')
            ->where('b.status', 'confirmed')->where('b.booking_date', '>=', date('Y-m-d'))
            ->select('b.*', 'r.first_name', 'r.last_name', 'rm.name as room_name')
            ->orderBy('b.booking_date')->limit(20)->get()->toArray();
    }

    public function executeBook($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher || $this->researcher->status !== 'approved') {
            $this->getUser()->setFlash('error', 'Must be approved researcher');
            $this->redirect('research/dashboard');
        }
        $this->rooms = $this->service->getReadingRooms();
        $this->objectSlug = $request->getParameter('object');
        $this->object = null;
        if ($this->objectSlug) {
            $this->object = DB::table('slug')
                ->join('information_object_i18n as i18n', function($join) {
                    $join->on('slug.object_id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
                })->where('slug.slug', $this->objectSlug)
                ->select('slug.object_id', 'i18n.title')->first();
        }
        if ($request->isMethod('post')) {
            $bookingId = $this->service->createBooking([
                'researcher_id' => $this->researcher->id,
                'reading_room_id' => $request->getParameter('reading_room_id'),
                'booking_date' => $request->getParameter('booking_date'),
                'start_time' => $request->getParameter('start_time'),
                'end_time' => $request->getParameter('end_time'),
                'purpose' => $request->getParameter('purpose'),
                'notes' => $request->getParameter('notes'),
            ]);
            foreach ($request->getParameter('materials', []) as $objectId) {
                $this->service->addMaterialRequest($bookingId, (int) $objectId);
            }
            $this->getUser()->setFlash('success', 'Booking submitted');
            $this->redirect('research/viewBooking?id=' . $bookingId);
        }
    }

    public function executeViewBooking($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $bookingId = (int) $request->getParameter('id');
        $this->booking = $this->service->getBooking($bookingId);

        if (!$this->booking) {
            $this->forward404('Booking not found');
        }

        if ($request->isMethod('post')) {
            $action = $request->getParameter('booking_action');
            $adminId = $this->getUser()->getAttribute('user_id');

            if ($action === 'confirm') {
                $this->service->confirmBooking($bookingId, $adminId);
                $this->getUser()->setFlash('success', 'Booking confirmed');
            } elseif ($action === 'cancel') {
                $this->service->cancelBooking($bookingId, 'Cancelled by staff');
                $this->getUser()->setFlash('success', 'Booking cancelled');
            } elseif ($action === 'noshow') {
                DB::table('research_booking')
                    ->where('id', $bookingId)
                    ->update(['status' => 'no_show']);
                $this->getUser()->setFlash('success', 'Marked as no-show');
            }

            $this->redirect('research/viewBooking?id=' . $bookingId);
        }
    }

    public function executeViewBooking_OLD($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $id = (int) $request->getParameter('id');
        $this->booking = $this->service->getBooking($id);
        if (!$this->booking) { $this->forward404('Not found'); }
        if ($request->isMethod('post')) {
            $action = $request->getParameter('booking_action');
            $adminId = $this->getUser()->getAttribute('user_id');
            match($action) {
                'confirm' => $this->service->confirmBooking($id, $adminId),
                'cancel' => $this->service->cancelBooking($id, $request->getParameter('reason')),
                'checkin' => $this->service->checkIn($id),
                'checkout' => $this->service->checkOut($id),
                default => null,
            };
            $this->getUser()->setFlash('success', ucfirst($action) . ' done');
            $this->redirect('research/viewBooking?id=' . $id);
        }
    }

    public function executeWorkspace($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }
        
        // Get all researcher data
        $this->collections = $this->service->getCollections($this->researcher->id);
        $this->savedSearches = $this->service->getSavedSearches($this->researcher->id);
        $this->annotations = $this->service->getAnnotations($this->researcher->id);
        
        // Get bookings
        $this->upcomingBookings = DB::table('research_booking as b')
            ->join('research_reading_room as rm', 'b.reading_room_id', '=', 'rm.id')
            ->where('b.researcher_id', $this->researcher->id)
            ->where('b.booking_date', '>=', date('Y-m-d'))
            ->whereIn('b.status', ['pending', 'confirmed'])
            ->select('b.*', 'rm.name as room_name')
            ->orderBy('b.booking_date')
            ->orderBy('b.start_time')
            ->limit(5)
            ->get()->toArray();
        
        $this->pastBookings = DB::table('research_booking as b')
            ->join('research_reading_room as rm', 'b.reading_room_id', '=', 'rm.id')
            ->where('b.researcher_id', $this->researcher->id)
            ->where(function($q) {
                $q->where('b.booking_date', '<', date('Y-m-d'))
                  ->orWhere('b.status', 'completed');
            })
            ->select('b.*', 'rm.name as room_name')
            ->orderBy('b.booking_date', 'desc')
            ->limit(5)
            ->get()->toArray();
        
        // Get stats
        $this->stats = [
            'total_bookings' => DB::table('research_booking')->where('researcher_id', $this->researcher->id)->count(),
            'total_collections' => count($this->collections),
            'total_saved_searches' => count($this->savedSearches),
            'total_annotations' => count($this->annotations),
            'total_items' => DB::table('research_collection_item as ci')
                ->join('research_collection as c', 'ci.collection_id', '=', 'c.id')
                ->where('c.researcher_id', $this->researcher->id)
                ->count(),
        ];
        
        // Handle collection actions
        if ($request->isMethod('post')) {
            $action = $request->getParameter('booking_action');
            if ($action === 'create_collection') {
                $name = trim($request->getParameter('collection_name'));
                $description = trim($request->getParameter('collection_description'));
                if ($name) {
                    $this->service->createCollection($this->researcher->id, [
                        'name' => $name,
                        'description' => $description,
                        'is_public' => $request->getParameter('is_public') ? 1 : 0,
                    ]);
                    $this->getUser()->setFlash('success', 'Collection created successfully.');
                    $this->redirect('research/workspace');
                }
            }
        }
    }

    public function executeSavedSearches($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }
        if ($request->isMethod('post')) {
            $action = $request->getParameter('booking_action');
            if ($action === 'save') {
                $this->service->saveSearch($this->researcher->id, [
                    'name' => $request->getParameter('name'),
                    'search_query' => $request->getParameter('search_query'),
                ]);
            } elseif ($action === 'delete') {
                $this->service->deleteSavedSearch((int) $request->getParameter('id'), $this->researcher->id);
            }
            $this->redirect('research/savedSearches');
        }
        $this->savedSearches = $this->service->getSavedSearches($this->researcher->id);
    }

    public function executeCollections($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }
        if ($request->isMethod('post') && $request->getParameter('do') === 'create') {
            $id = $this->service->createCollection($this->researcher->id, [
                'name' => $request->getParameter('name'),
                'description' => $request->getParameter('description'),
            ]);
            $this->redirect('research/viewCollection?id=' . $id);
        }
        $this->collections = $this->service->getCollections($this->researcher->id);
    }

    public function executeViewCollection($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) { $this->redirect('research/register'); }
        
        $id = (int) $request->getParameter('id');
        $this->collection = $this->service->getCollection($id);
        if (!$this->collection) { $this->forward404('Not found'); }
        
        // Verify ownership
        if ($this->collection->researcher_id != $researcher->id) {
            $this->getUser()->setFlash('error', 'Access denied');
            $this->redirect('research/collections');
        }
        
        if ($request->isMethod('post')) {
            $action = $request->getParameter('booking_action');
            
            if ($action === 'remove') {
                $this->service->removeFromCollection($id, (int) $request->getParameter('object_id'));
                $this->getUser()->setFlash('success', 'Item removed from collection');
                $this->redirect('research/viewCollection?id=' . $id);
            }
            
            if ($action === 'add_item') {
                $objectId = (int) $request->getParameter('object_id');
                $notes = trim($request->getParameter('notes', ''));
                $includeDescendants = $request->getParameter('include_descendants') ? true : false;
                if ($objectId > 0) {
                    $addedCount = 0;
                    $objectsToAdd = [$objectId];
                    if ($includeDescendants) {
                        $item = DB::table('information_object')->where('id', $objectId)->first();
                        if ($item) {
                            $descendants = DB::table('information_object')
                                ->where('lft', '>', $item->lft)
                                ->where('rgt', '<', $item->rgt)
                                ->pluck('id')->toArray();
                            $objectsToAdd = array_merge($objectsToAdd, $descendants);
                        }
                    }
                    foreach ($objectsToAdd as $oid) {
                        $exists = DB::table('research_collection_item')
                            ->where('collection_id', $id)
                            ->where('object_id', $oid)->exists();
                        if (!$exists) {
                            DB::table('research_collection_item')->insert([
                                'collection_id' => $id,
                                'object_id' => $oid,
                                'notes' => ($oid == $objectId) ? $notes : '',
                                'created_at' => date('Y-m-d H:i:s'),
                            ]);
                            $addedCount++;
                        }
                    }
                    if ($addedCount > 0) {
                        $this->getUser()->setFlash('success', $addedCount . ' item(s) added to collection');
                    } else {
                        $this->getUser()->setFlash('error', 'Item(s) already in collection');
                    }
                }
                $this->redirect('research/viewCollection?id=' . $id);
            }

            if ($action === 'update_notes') {
                $objectId = (int) $request->getParameter('object_id');
                $notes = trim($request->getParameter('notes'));
                DB::table('research_collection_item')
                    ->where('collection_id', $id)
                    ->where('object_id', $objectId)
                    ->update(['notes' => $notes]);
                $this->getUser()->setFlash('success', 'Notes updated');
                $this->redirect('research/viewCollection?id=' . $id);
            }
            
            if ($action === 'update') {
                $name = trim($request->getParameter('name'));
                $description = trim($request->getParameter('description'));
                $isPublic = $request->getParameter('is_public') ? 1 : 0;
                if ($name) {
                    DB::table('research_collection')->where('id', $id)->update([
                        'name' => $name,
                        'description' => $description,
                        'is_public' => $request->getParameter('is_public') ? 1 : 0,
                        'is_public' => $isPublic,
                        
                    ]);
                    $this->getUser()->setFlash('success', 'Collection updated');
                }
                $this->redirect('research/viewCollection?id=' . $id);
            }
            
            if ($action === 'delete') {
                DB::table('research_collection_item')->where('collection_id', $id)->delete();
                DB::table('research_collection')->where('id', $id)->delete();
                $this->getUser()->setFlash('success', 'Collection deleted');
                $this->redirect('research/collections');
            }
		}
    }

    public function executeAnnotations($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        if ($request->isMethod('post')) {
            $action = $request->getParameter('do');
            
            if ($action === 'delete') {
                $this->service->deleteAnnotation((int) $request->getParameter('id'), $this->researcher->id);
                $this->getUser()->setFlash('success', 'Note deleted');
                $this->redirect('research/annotations');
            }
            
            if ($action === 'create') {
                $title = trim($request->getParameter('title'));
                $content = trim($request->getParameter('content'));
                $objectId = (int) $request->getParameter('object_id') ?: null;
                if ($content) {
                    DB::table('research_annotation')->insert([
                        'researcher_id' => $this->researcher->id,
                        'object_id' => $objectId,
                        'title' => $title,
                        'content' => $content,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                    $this->getUser()->setFlash('success', 'Note created');
                }
                $this->redirect('research/annotations');
            }
            
            if ($action === 'update') {
                $id = (int) $request->getParameter('id');
                $title = trim($request->getParameter('title'));
                $content = trim($request->getParameter('content'));
                if ($content) {
                    DB::table('research_annotation')
                        ->where('id', $id)
                        ->where('researcher_id', $this->researcher->id)
                        ->update([
                            'title' => $title,
                            'content' => $content,
                            
                        ]);
                    $this->getUser()->setFlash('success', 'Note updated');
                }
                $this->redirect('research/annotations');
            }
        }
        
        $this->annotations = $this->service->getAnnotations($this->researcher->id);
    }

    public function executeCite($request)
    {
        $slug = $request->getParameter('slug');
        $object = DB::table('slug')->where('slug', $slug)->first();
        if (!$object) { $this->forward404('Not found'); }
        $this->objectId = $object->object_id;
        $this->styles = ['chicago', 'mla', 'turabian', 'apa', 'harvard', 'unisa'];
        $this->citations = [];
        foreach ($this->styles as $style) {
            $this->citations[$style] = $this->service->generateCitation($object->object_id, $style);
        }
        $researcherId = null;
        if ($this->getUser()->isAuthenticated()) {
            $userId = $this->getUser()->getAttribute('user_id');
            $r = $this->service->getResearcherByUserId($userId);
            if ($r) $researcherId = $r->id;
        }
        foreach ($this->citations as $style => $data) {
            if (!isset($data['error'])) {
                $this->service->logCitation($researcherId, $object->object_id, $style, $data['citation']);
            }
        }
	}

    // =========================================================================
    // PUBLIC REGISTRATION (No login required)
    // =========================================================================

    public function executePublicRegister($request)
    {
        if ($this->getUser()->isAuthenticated()) {
            $this->redirect('research/register');
        }

        if ($request->isMethod('post')) {
            $email = trim($request->getParameter('email'));
            $username = trim($request->getParameter('username'));
            $password = $request->getParameter('password');
            $confirmPassword = $request->getParameter('confirm_password');

            $errors = [];
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Valid email address is required';
            }
            if (empty($username) || strlen($username) < 3) {
                $errors[] = 'Username must be at least 3 characters';
            }
            if (empty($password) || strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters';
            }
            if ($password !== $confirmPassword) {
                $errors[] = 'Passwords do not match';
            }
            // Check for existing users - allow reactivation of rejected/deactivated users
            $existingUser = DB::table('user')->where('email', $email)->first();
            $existingByUsername = DB::table('user')->where('username', $username)->first();
            
            // If user exists, check if they can re-register
            if ($existingUser) {
                if ($existingUser->active) {
                    $errors[] = 'Email address is already registered';
                } else {
                    // Check if disabled due to rejection (can re-register) or other reason (cannot)
                    $wasRejected = DB::table('research_researcher_audit')
                        ->where('user_id', $existingUser->id)
                        ->where('status', 'rejected')
                        ->exists();
                    if (!$wasRejected) {
                        $errors[] = 'This account has been disabled. Please contact the administrator.';
                    }
                }
            }
            if ($existingByUsername && $existingByUsername->active && (!$existingUser || $existingByUsername->id != $existingUser->id)) {
                $errors[] = 'Username is already taken';
            }

            if (!empty($errors)) {
                $this->getUser()->setFlash('error', implode('<br>', $errors));
                return sfView::SUCCESS;
            }

            try {
                DB::beginTransaction();
                
                // Check if this is a reactivation of a previously rejected researcher
                // Only allow if they have a rejected entry in audit table
                $wasRejected = $existingUser && !$existingUser->active && 
                    DB::table('research_researcher_audit')
                        ->where('user_id', $existingUser->id)
                        ->where('status', 'rejected')
                        ->exists();
                
                if ($wasRejected) {
                    // Reactivate existing user with new password
                    $salt = md5(rand(100000, 999999) . $email);
                    $sha1Hash = sha1($salt . $password);
                    $passwordHash = password_hash($sha1Hash, PASSWORD_ARGON2I);
                    
                    DB::table('user')->where('id', $existingUser->id)->update([
                        'username' => $username,
                        'password_hash' => $passwordHash,
                        'salt' => $salt,
                        'active' => 0, // Keep inactive until approved
                        
                    ]);
                    $userId = $existingUser->id;
                } else {
                    $userId = $this->createAtomUser($username, $email, $password);
                }
                
                // Ensure user is in researcher group
                if (!DB::table('acl_user_group')->where('user_id', $userId)->where('group_id', 99)->exists()) {
                    DB::table('acl_user_group')->insert(['user_id' => $userId, 'group_id' => 99]);
                }
                $this->service->registerResearcher([
                    'user_id' => $userId,
                    'title' => $request->getParameter('title'),
                    'first_name' => $request->getParameter('first_name'),
                    'last_name' => $request->getParameter('last_name'),
                    'email' => $email,
                    'phone' => $request->getParameter('phone'),
                    'affiliation_type' => $request->getParameter('affiliation_type', 'independent'),
                    'institution' => $request->getParameter('institution'),
                    'department' => $request->getParameter('department'),
                    'position' => $request->getParameter('position'),
                    'research_interests' => $request->getParameter('research_interests'),
                    'current_project' => $request->getParameter('current_project'),
                    'orcid_id' => $request->getParameter('orcid_id'),
                    'id_type' => $request->getParameter('id_type'),
                    'id_number' => $request->getParameter('id_number'),
                    'student_id' => $request->getParameter('student_id'),
                ]);
                DB::commit();
                $this->getUser()->setFlash('success', 'Registration successful! Pending approval.');
                $this->redirect('research/registrationComplete');
            } catch (Exception $e) {
                DB::rollBack();
                if ($e->getMessage()) { $this->getUser()->setFlash('error', 'Registration failed: ' . $e->getMessage()); }
            }
        }
    }

    public function executeRegistrationComplete($request)
    {
    }

    protected function createAtomUser(string $username, string $email, string $password): int
    {
        // AtoM double-hash: SHA1(salt+password) then Argon2i
        $salt = md5(rand(100000, 999999) . $email);
        $sha1Hash = sha1($salt . $password);
        $passwordHash = password_hash($sha1Hash, PASSWORD_ARGON2I);
        $now = date('Y-m-d H:i:s');
        
        // AtoM inheritance: object -> actor -> user
        // 1. Insert into object table
        $objectId = DB::table('object')->insertGetId([
            'class_name' => 'QubitUser',
            'created_at' => $now,
            'updated_at' => $now,
            'serial_number' => 0,
        ]);
        
        // 2. Insert into actor table (user inherits from actor)
        DB::table('actor')->insert([
            'id' => $objectId,
            'corporate_body_identifiers' => null,
            'entity_type_id' => null,
            'description_status_id' => null,
            'description_detail_id' => null,
            'description_identifier' => null,
            'source_standard' => null,
            'source_culture' => 'en',
        ]);
        
        // 3. Insert into user table
        DB::table('user')->insert([
            'id' => $objectId,
            'username' => $username,
            'email' => $email,
            'password_hash' => $passwordHash,
            'salt' => $salt,
            'active' => 0,
        ]);
        
        // 4. Insert into slug table
        DB::table('slug')->insert([
            'object_id' => $objectId,
            'slug' => preg_replace('/[^a-zA-Z0-9-]/', '-', $username),
        ]);
        return $objectId;
    }

    // =========================================================================
    // PASSWORD RESET
    // =========================================================================

    public function executePasswordResetRequest($request)
    {
        if ($request->isMethod('post')) {
            $email = trim($request->getParameter('email'));
            $user = DB::table('user')->where('email', $email)->first();
            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+2 hours'));
                DB::table('research_password_reset')->updateOrInsert(
                    ['user_id' => $user->id],
                    ['token' => $token, 'expires_at' => $expires, 'created_at' => date('Y-m-d H:i:s')]
                );
                $resetUrl = $this->config('app_siteBaseUrl', 'https://psis.theahg.co.za') . '/index.php/research/passwordReset?token=' . $token;
                error_log("Password reset for {$email}: {$resetUrl}");
            }
            $this->getUser()->setFlash('success', 'If an account exists, you will receive reset instructions.');
            $this->redirect('research/passwordResetRequest');
        }
    }

    public function executePasswordReset($request)
    {
        $token = $request->getParameter('token');
        if (empty($token)) {
            $this->getUser()->setFlash('error', 'Invalid reset link');
            $this->redirect('research/passwordResetRequest');
        }
        $reset = DB::table('research_password_reset')
            ->where('token', $token)
            ->where('expires_at', '>', date('Y-m-d H:i:s'))
            ->first();
        if (!$reset) {
            $this->getUser()->setFlash('error', 'Reset link expired or invalid');
            $this->redirect('research/passwordResetRequest');
        }
        $this->token = $token;
        $this->user = DB::table('user')->where('id', $reset->user_id)->first();

        if ($request->isMethod('post')) {
            $password = $request->getParameter('password');
            $confirmPassword = $request->getParameter('confirm_password');
            if (strlen($password) < 8) {
                $this->getUser()->setFlash('error', 'Password must be at least 8 characters');
                return sfView::SUCCESS;
            }
            if ($password !== $confirmPassword) {
                $this->getUser()->setFlash('error', 'Passwords do not match');
                return sfView::SUCCESS;
            }
            $salt = bin2hex(random_bytes(16));
            $passwordHash = password_hash($password, PASSWORD_ARGON2I);
            DB::table('user')->where('id', $reset->user_id)->update([
                'password_hash' => $passwordHash,
                'salt' => $salt,
            ]);
            DB::table('research_password_reset')->where('user_id', $reset->user_id)->delete();
            $this->getUser()->setFlash('success', 'Password updated. You can now log in.');
            $this->redirect('user/login');
        }
    }

    // =========================================================================
    // ADMIN: READING ROOM MANAGEMENT
    // =========================================================================

    public function executeRooms($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->isAdministrator()) {
            $this->getUser()->setFlash('error', 'Administrator access required');
            $this->redirect('@homepage');
        }
        $this->rooms = $this->service->getReadingRooms(false);
    }

    public function executeEditRoom($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->isAdministrator()) {
            $this->getUser()->setFlash('error', 'Administrator access required');
            $this->redirect('@homepage');
        }
        $id = (int) $request->getParameter('id');
        $this->room = $id ? $this->service->getReadingRoom($id) : null;
        $this->isNew = !$this->room;

        if ($request->isMethod('post')) {
            $data = [
                'name' => $request->getParameter('name'),
                'code' => $request->getParameter('code'),
                'location' => $request->getParameter('location'),
                'capacity' => (int) $request->getParameter('capacity', 10),
                'description' => $request->getParameter('description'),
                'amenities' => $request->getParameter('amenities'),
                'rules' => $request->getParameter('rules'),
                'opening_time' => $request->getParameter('opening_time', '09:00:00'),
                'closing_time' => $request->getParameter('closing_time', '17:00:00'),
                'days_open' => $request->getParameter('days_open', 'Mon,Tue,Wed,Thu,Fri'),
                'is_active' => $request->getParameter('is_active') ? 1 : 0,
                'advance_booking_days' => (int) $request->getParameter('advance_booking_days', 14),
                'max_booking_hours' => (int) $request->getParameter('max_booking_hours', 4),
                'cancellation_hours' => (int) $request->getParameter('cancellation_hours', 24),
                
            ];
            if ($id && $this->room) {
                DB::table('research_reading_room')->where('id', $id)->update($data);
                $this->getUser()->setFlash('success', 'Reading room updated');
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                DB::table('research_reading_room')->insert($data);
                $this->getUser()->setFlash('success', 'Reading room created');
            }
            $this->redirect('research/rooms');
        }
    }

    // =========================================================================
    // ADMIN: RESEARCHER APPROVAL
    // =========================================================================

    public function executeApproveResearcher($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->isAdministrator()) {
            $this->getUser()->setFlash('error', 'Administrator access required');
            $this->redirect('@homepage');
        }
        $id = (int) $request->getParameter('id');
        $researcher = $this->service->getResearcher($id);
        if (!$researcher) {
            $this->forward404('Researcher not found');
        }
        $adminId = $this->getUser()->getAttribute('user_id');
        $this->service->approveResearcher($id, $adminId);
        DB::table('user')->where('id', $researcher->user_id)->update(['active' => 1]);
        $this->getUser()->setFlash('success', 'Researcher approved and account activated');
        $this->redirect('research/viewResearcher?id=' . $id);
    }

    public function executeRejectResearcher($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->isAdministrator()) {
            $this->getUser()->setFlash('error', 'Administrator access required');
            $this->redirect('@homepage');
        }
        $id = (int) $request->getParameter('id');
        $researcher = $this->service->getResearcher($id);
        if (!$researcher) {
            $this->forward404('Researcher not found');
        }
        $reason = $request->getParameter('reason', '');
        $adminId = $this->getUser()->getAttribute('user_id');
        
        // Move to audit table
        DB::table('research_researcher_audit')->insert([
            'original_id' => $researcher->id,
            'user_id' => $researcher->user_id,
            'title' => $researcher->title,
            'first_name' => $researcher->first_name,
            'last_name' => $researcher->last_name,
            'email' => $researcher->email,
            'phone' => $researcher->phone,
            'affiliation_type' => $researcher->affiliation_type,
            'institution' => $researcher->institution,
            'department' => $researcher->department,
            'position' => $researcher->position,
            'research_interests' => $researcher->research_interests,
            'current_project' => $researcher->current_project,
            'orcid_id' => $researcher->orcid_id,
            'id_type' => $researcher->id_type,
            'id_number' => $researcher->id_number,
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'archived_by' => $adminId,
            'archived_at' => date('Y-m-d H:i:s'),
            'original_created_at' => $researcher->created_at,
            'original_updated_at' => $researcher->updated_at,
        ]);
        
        // Delete from main table
        DB::table('research_researcher')->where('id', $id)->delete();
        
        // Deactivate the user account
        DB::table('user')->where('id', $researcher->user_id)->update(['active' => 0]);
        
        // Update access request to rejected
        DB::table('access_request')
            ->where('user_id', $researcher->user_id)
            ->where('request_type', 'researcher')
            ->where('status', 'pending')
            ->update([
                'status' => 'denied',
                'reviewed_by' => $adminId,
                'reviewed_at' => date('Y-m-d H:i:s'),
                'review_notes' => $reason,
            ]);
        
        $this->getUser()->setFlash('success', 'Researcher registration rejected and archived');
        $this->redirect('research/researchers');
    }

    public function executeAdminResetPassword($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->isAdministrator()) {
            $this->getUser()->setFlash('error', 'Administrator access required');
            $this->redirect('@homepage');
        }
        $researcherId = (int) $request->getParameter('id');
        $researcher = $this->service->getResearcher($researcherId);
        if (!$researcher) {
            $this->forward404('Researcher not found');
        }
        $newPassword = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 12);
        $salt = bin2hex(random_bytes(16));
        $passwordHash = sha1($salt . $newPassword);
        DB::table('user')->where('id', $researcher->user_id)->update([
            'password_hash' => $passwordHash,
            'salt' => $salt,
        ]);
        $this->getUser()->setFlash('success', "Password reset. New password: <strong>{$newPassword}</strong>");
        $this->redirect('research/viewResearcher?id=' . $researcherId);
    }

    // =========================================================================
    // CHECK-IN/CHECK-OUT
    // =========================================================================

    public function executeCheckIn($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }
        $bookingId = (int) $request->getParameter('id');
        DB::table('research_booking')->where('id', $bookingId)->update([
            'checked_in_at' => date('Y-m-d H:i:s'),
            'status' => 'confirmed',
        ]);
        $this->getUser()->setFlash('success', 'Researcher checked in');
        $this->redirect('research/viewBooking?id=' . $bookingId);
    }

    public function executeCheckOut($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }
        $bookingId = (int) $request->getParameter('id');
        DB::table('research_booking')->where('id', $bookingId)->update([
            'checked_out_at' => date('Y-m-d H:i:s'),
            'status' => 'completed',
        ]);
        DB::table('research_material_request')
            ->where('booking_id', $bookingId)
            ->where('status', '!=', 'returned')
            ->update(['status' => 'returned', 'returned_at' => date('Y-m-d H:i:s')]);
        $this->getUser()->setFlash('success', 'Researcher checked out');
        $this->redirect('research/bookings');
    }

    /**
     * AJAX: Add item to collection.
     */
    public function executeAddToCollection($request)
    {
        $this->getResponse()->setContentType('application/json');
        
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Not authenticated']));
        }
        
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        
        if (!$researcher || $researcher->status !== 'approved') {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Not an approved researcher']));
        }
        
        $collectionId = (int) $request->getParameter('collection_id');
        $objectId = (int) $request->getParameter('object_id');
        $notes = $request->getParameter('notes', '');
        
        // Verify collection belongs to researcher
        $collection = DB::table('research_collection')
            ->where('id', $collectionId)
            ->where('researcher_id', $researcher->id)
            ->first();
        
        if (!$collection) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Collection not found']));
        }
        
        // Check if already in collection
        $exists = DB::table('research_collection_item')
            ->where('collection_id', $collectionId)
            ->where('object_id', $objectId)
            ->exists();
        
        if ($exists) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Item already in collection']));
        }
        
        // Add to collection
        try {
            $this->service->addToCollection($collectionId, $objectId, $notes);
            return $this->renderText(json_encode(['success' => true, 'message' => 'Item added to collection']));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * AJAX: Create collection and optionally add item.
     */
    public function executeCreateCollectionAjax($request)
    {
        $this->getResponse()->setContentType('application/json');
        
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Not authenticated']));
        }
        
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        
        if (!$researcher || $researcher->status !== 'approved') {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Not an approved researcher']));
        }
        
        $name = trim($request->getParameter('name'));
        $description = trim($request->getParameter('description', ''));
        $objectId = (int) $request->getParameter('object_id');
        
        if (empty($name)) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Collection name is required']));
        }
        
        try {
            $collectionId = $this->service->createCollection($researcher->id, [
                'name' => $name,
                'description' => $description,
                        'is_public' => $request->getParameter('is_public') ? 1 : 0,
            ]);
            
            // If object_id provided, add it to the new collection
            if ($objectId > 0) {
                $this->service->addToCollection($collectionId, $objectId);
            }
            
            return $this->renderText(json_encode([
                'success' => true, 
                'message' => 'Collection created',
                'collection_id' => $collectionId,
            ]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * Generate Finding Aid PDF for a collection.
     */
    public function executeGenerateFindingAid($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }
        
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        
        if (!$researcher || $researcher->status !== 'approved') {
            $this->getUser()->setFlash('error', 'Not authorized');
            $this->redirect('research/workspace');
        }
        
        $collectionId = (int) $request->getParameter('id');
        $includeDescendants = $request->getParameter('include_descendants', 1);
        
        $data = $this->service->getCollectionFindingAidData($collectionId, $researcher->id);
        
        if (!$data) {
            $this->getUser()->setFlash('error', 'Collection not found');
            $this->redirect('research/workspace');
        }
        
        $this->data = $data;
        $this->includeDescendants = $includeDescendants;
        
        // Set for PDF output
        $this->setLayout(false);
        $this->getResponse()->setContentType('text/html');
	}

    /**
     * AJAX: Search items for Tom Select dropdown.
     */
    public function executeSearchItems($request)
    {
        $this->getResponse()->setContentType('application/json');
        
        $query = trim($request->getParameter('q', ''));
        if (strlen($query) < 2) {
            return $this->renderText(json_encode(['items' => []]));
        }
        
        $items = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('io.id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
            ->where('io.id', '!=', 1) // Exclude root
            ->where(function($q) use ($query) {
                $q->where('ioi.title', 'LIKE', '%' . $query . '%')
                  ->orWhere('io.identifier', 'LIKE', '%' . $query . '%');
            })
            ->select('io.id', 'io.identifier', 'ioi.title', 's.slug')
            ->orderBy('ioi.title')
            ->limit(20)
            ->get()
            ->map(function($item) {
                // Check if has children
                $hasChildren = \Illuminate\Database\Capsule\Manager::table('information_object')
                    ->where('parent_id', $item->id)
                    ->exists();
                return [
                    'id' => $item->id,
                    'title' => $item->title ?: 'Untitled [' . $item->id . ']',
                    'identifier' => $item->identifier,
                    'slug' => $item->slug,
                    'has_children' => $hasChildren,
                ];
            })
            ->toArray();
        
        return $this->renderText(json_encode(['items' => $items]));
    }

    /**
     * Request renewal of expired researcher status
     */
    public function executeRenewal($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);

        if (!$this->researcher) {
            $this->redirect('research/register');
        }

        // Only allow renewal for expired or soon-to-expire
        if (!in_array($this->researcher->status, ['expired', 'approved'])) {
            $this->getUser()->setFlash('error', 'Renewal not available for your current status');
            $this->redirect('research/profile');
        }

        if ($request->isMethod('post')) {
            $reason = trim($request->getParameter('reason', ''));

            // Create renewal request via access_request
            $requestId = DB::table('access_request')->insertGetId([
                'request_type' => 'researcher',
                'scope_type' => 'renewal',
                'user_id' => $userId,
                'reason' => $reason ?: 'Researcher registration renewal request',
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),

            ]);

            $this->getUser()->setFlash('success', 'Renewal request submitted. You will be notified when reviewed.');
            $this->redirect('research/profile');
        }
    }

    // =========================================================================
    // ORCID INTEGRATION
    // =========================================================================

    /**
     * Initiate ORCID OAuth connection.
     */
    public function executeOrcidConnect($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);

        if (!$researcher) {
            $this->getUser()->setFlash('error', 'You must be a registered researcher');
            $this->redirect('research/register');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/OrcidService.php';
        $orcidService = new OrcidService();

        if (!$orcidService->isConfigured()) {
            $this->getUser()->setFlash('error', 'ORCID integration is not configured. Please contact the administrator.');
            $this->redirect('research/profile');
        }

        $state = $orcidService->generateState();
        $this->getUser()->setAttribute('orcid_state', $state);

        $authUrl = $orcidService->getAuthorizationUrl($state);
        $this->redirect($authUrl);
    }

    /**
     * ORCID OAuth callback.
     */
    public function executeOrcidCallback($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $code = $request->getParameter('code');
        $state = $request->getParameter('state');
        $error = $request->getParameter('error');

        if ($error) {
            $this->getUser()->setFlash('error', 'ORCID authorization was cancelled or failed');
            $this->redirect('research/profile');
        }

        if (!$code || !$state) {
            $this->getUser()->setFlash('error', 'Invalid ORCID callback');
            $this->redirect('research/profile');
        }

        // Validate CSRF state token
        $savedState = $this->getUser()->getAttribute('orcid_state');
        $this->getUser()->setAttribute('orcid_state', null);

        if (!$savedState || $state !== $savedState) {
            $this->getUser()->setFlash('error', 'Invalid state token. Please try again.');
            $this->redirect('research/profile');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/OrcidService.php';
        $orcidService = new OrcidService();

        if (!$orcidService->isConfigured()) {
            $this->getUser()->setFlash('error', 'ORCID integration is not configured');
            $this->redirect('research/profile');
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);

        if (!$researcher) {
            $this->getUser()->setFlash('error', 'Researcher profile not found');
            $this->redirect('research/profile');
        }

        $result = $orcidService->verifyOrcid($researcher->id, $code);

        if (isset($result['error'])) {
            $this->getUser()->setFlash('error', $result['error']);
        } else {
            $this->getUser()->setFlash('success', 'ORCID connected successfully: ' . $result['orcid_id']);
        }

        $this->redirect('research/profile');
    }

    /**
     * Disconnect ORCID from researcher profile.
     */
    public function executeOrcidDisconnect($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);

        if (!$researcher) {
            $this->redirect('research/register');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/OrcidService.php';
        $orcidService = new OrcidService();

        $result = $orcidService->disconnectOrcid($researcher->id);

        if ($result['success']) {
            $this->getUser()->setFlash('success', 'ORCID disconnected');
        } else {
            $this->getUser()->setFlash('error', $result['error'] ?? 'Failed to disconnect ORCID');
        }

        $this->redirect('research/profile');
    }

    // =========================================================================
    // ADMIN: RESEARCHER TYPES
    // =========================================================================

    /**
     * List and manage researcher types.
     */
    public function executeAdminTypes($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->isAdministrator()) {
            $this->getUser()->setFlash('error', 'Administrator access required');
            $this->redirect('@homepage');
        }

        $this->types = $this->service->getResearcherTypes();
    }

    /**
     * Edit or create researcher type.
     */
    public function executeEditResearcherType($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->isAdministrator()) {
            $this->getUser()->setFlash('error', 'Administrator access required');
            $this->redirect('@homepage');
        }

        $id = (int) $request->getParameter('id');
        $this->type = $id ? $this->service->getResearcherType($id) : null;
        $this->isNew = !$this->type;

        if ($request->isMethod('post')) {
            $data = [
                'name' => $request->getParameter('name'),
                'code' => $request->getParameter('code'),
                'description' => $request->getParameter('description'),
                'max_booking_days_advance' => (int) $request->getParameter('max_booking_days_advance', 14),
                'max_booking_hours_per_day' => (int) $request->getParameter('max_booking_hours_per_day', 4),
                'max_materials_per_booking' => (int) $request->getParameter('max_materials_per_booking', 10),
                'can_remote_access' => $request->getParameter('can_remote_access') ? 1 : 0,
                'can_request_reproductions' => $request->getParameter('can_request_reproductions') ? 1 : 0,
                'can_export_data' => $request->getParameter('can_export_data') ? 1 : 0,
                'requires_id_verification' => $request->getParameter('requires_id_verification') ? 1 : 0,
                'auto_approve' => $request->getParameter('auto_approve') ? 1 : 0,
                'expiry_months' => (int) $request->getParameter('expiry_months', 12),
                'priority_level' => (int) $request->getParameter('priority_level', 5),
                'is_active' => $request->getParameter('is_active') ? 1 : 0,
                'sort_order' => (int) $request->getParameter('sort_order', 100),
            ];

            if ($id && $this->type) {
                $this->service->updateResearcherType($id, $data);
                $this->getUser()->setFlash('success', 'Researcher type updated');
            } else {
                $this->service->createResearcherType($data);
                $this->getUser()->setFlash('success', 'Researcher type created');
            }

            $this->redirect('research/adminTypes');
        }
    }

    // =========================================================================
    // RESEARCH PROJECTS
    // =========================================================================

    /**
     * List researcher's projects.
     */
    public function executeProjects($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);

        if (!$this->researcher) {
            $this->redirect('research/register');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ProjectService.php';
        $projectService = new ProjectService();

        $this->projects = $projectService->getProjects($this->researcher->id, [
            'status' => $request->getParameter('status'),
        ]);

        // Handle project creation
        if ($request->isMethod('post') && $request->getParameter('form_action') === 'create') {
            try {
                $projectId = $projectService->createProject($this->researcher->id, [
                    'title' => $request->getParameter('title'),
                    'description' => $request->getParameter('description'),
                    'project_type' => $request->getParameter('project_type', 'personal'),
                    'institution' => $request->getParameter('institution'),
                    'start_date' => $request->getParameter('start_date'),
                    'expected_end_date' => $request->getParameter('expected_end_date'),
                ]);
                $this->getUser()->setFlash('success', 'Project created');
                $this->redirect('research/viewProject?id=' . $projectId);
            } catch (Exception $e) {
                $this->getUser()->setFlash('error', $e->getMessage());
            }
        }
    }

    /**
     * View project details.
     */
    public function executeViewProject($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);

        if (!$this->researcher) {
            $this->redirect('research/register');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ProjectService.php';
        $projectService = new ProjectService();

        $projectId = (int) $request->getParameter('id');
        $this->project = $projectService->getProject($projectId, $this->researcher->id);

        if (!$this->project) {
            $this->forward404('Project not found');
        }

        $this->collaborators = $projectService->getCollaborators($projectId);
        $this->resources = DB::table('research_project_resource')
            ->where('project_id', $projectId)
            ->orderBy('added_at', 'desc')
            ->get()->toArray();
        $this->milestones = DB::table('research_project_milestone')
            ->where('project_id', $projectId)
            ->orderBy('sort_order')
            ->get()->toArray();
        $this->activities = DB::table('research_activity_log')
            ->where('project_id', $projectId)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()->toArray();
    }

    /**
     * Edit project.
     */
    public function executeEditProject($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);

        if (!$this->researcher) {
            $this->redirect('research/register');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ProjectService.php';
        $projectService = new ProjectService();

        $projectId = (int) $request->getParameter('id');
        $this->project = $projectService->getProject($projectId, $this->researcher->id);

        if (!$this->project) {
            $this->forward404('Project not found');
        }

        // Only owner can edit
        if ($this->project->owner_id != $this->researcher->id) {
            $this->getUser()->setFlash('error', 'Only the project owner can edit');
            $this->redirect('research/viewProject?id=' . $projectId);
        }

        if ($request->isMethod('post')) {
            $result = $projectService->updateProject($projectId, $this->researcher->id, [
                'title' => $request->getParameter('title'),
                'description' => $request->getParameter('description'),
                'project_type' => $request->getParameter('project_type'),
                'institution' => $request->getParameter('institution'),
                'supervisor' => $request->getParameter('supervisor'),
                'funding_source' => $request->getParameter('funding_source'),
                'start_date' => $request->getParameter('start_date'),
                'expected_end_date' => $request->getParameter('expected_end_date'),
                'status' => $request->getParameter('status'),
                'visibility' => $request->getParameter('visibility'),
            ]);

            if (isset($result['error'])) {
                $this->getUser()->setFlash('error', $result['error']);
            } else {
                $this->getUser()->setFlash('success', 'Project updated');
                $this->redirect('research/viewProject?id=' . $projectId);
            }
        }
    }

    /**
     * Manage project collaborators.
     */
    public function executeProjectCollaborators($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);

        if (!$this->researcher) {
            $this->redirect('research/register');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ProjectService.php';
        $projectService = new ProjectService();

        $projectId = (int) $request->getParameter('id');
        $this->project = $projectService->getProject($projectId, $this->researcher->id);

        if (!$this->project) {
            $this->forward404('Project not found');
        }

        $this->collaborators = $projectService->getCollaborators($projectId);

        // Handle remove collaborator
        if ($request->isMethod('post') && $request->getParameter('form_action') === 'remove') {
            $collaboratorId = (int) $request->getParameter('collaborator_id');
            DB::table('research_project_collaborator')
                ->where('id', $collaboratorId)
                ->where('project_id', $projectId)
                ->delete();
            $this->getUser()->setFlash('success', 'Collaborator removed');
            $this->redirect('research/projectCollaborators?id=' . $projectId);
        }
    }

    /**
     * Invite collaborator to project.
     */
    public function executeInviteCollaborator($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);

        if (!$this->researcher) {
            $this->redirect('research/register');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ProjectService.php';
        $projectService = new ProjectService();

        $projectId = (int) $request->getParameter('id');
        $this->project = $projectService->getProject($projectId, $this->researcher->id);

        if (!$this->project || $this->project->owner_id != $this->researcher->id) {
            $this->getUser()->setFlash('error', 'Only project owner can invite collaborators');
            $this->redirect('research/projects');
        }

        if ($request->isMethod('post')) {
            $email = trim($request->getParameter('email'));
            $role = $request->getParameter('role', 'contributor');

            $result = $projectService->inviteCollaborator($projectId, $this->researcher->id, $email, $role);

            if (isset($result['error'])) {
                $this->getUser()->setFlash('error', $result['error']);
            } else {
                $this->getUser()->setFlash('success', 'Invitation sent');
                $this->redirect('research/projectCollaborators?id=' . $projectId);
            }
        }
    }

    /**
     * Accept collaboration invitation.
     */
    public function executeAcceptInvitation($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);

        if (!$researcher) {
            $this->redirect('research/register');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ProjectService.php';
        $projectService = new ProjectService();

        $token = $request->getParameter('token');
        $result = $projectService->acceptInvitation($token, $researcher->id);

        if (isset($result['error'])) {
            $this->getUser()->setFlash('error', $result['error']);
            $this->redirect('research/projects');
        }

        $this->getUser()->setFlash('success', 'You have joined the project');
        $this->redirect('research/viewProject?id=' . $result['project_id']);
    }

    // =========================================================================
    // REPRODUCTION REQUESTS
    // =========================================================================

    /**
     * List reproduction requests.
     */
    public function executeReproductions($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);

        if (!$this->researcher) {
            $this->redirect('research/register');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ReproductionService.php';
        $reproductionService = new ReproductionService();

        $this->requests = $reproductionService->getRequests($this->researcher->id, [
            'status' => $request->getParameter('status'),
        ]);
    }

    /**
     * Create new reproduction request.
     */
    public function executeNewReproduction($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);

        if (!$this->researcher || $this->researcher->status !== 'approved') {
            $this->getUser()->setFlash('error', 'Must be an approved researcher');
            $this->redirect('research/dashboard');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ReproductionService.php';
        $reproductionService = new ReproductionService();

        if ($request->isMethod('post')) {
            $result = $reproductionService->createRequest($this->researcher->id, [
                'purpose' => $request->getParameter('purpose'),
                'intended_use' => $request->getParameter('intended_use'),
                'publication_details' => $request->getParameter('publication_details'),
                'delivery_method' => $request->getParameter('delivery_method', 'digital'),
                'urgency' => $request->getParameter('urgency', 'normal'),
                'special_instructions' => $request->getParameter('special_instructions'),
            ]);

            if (isset($result['error'])) {
                $this->getUser()->setFlash('error', $result['error']);
            } else {
                $this->getUser()->setFlash('success', 'Reproduction request created');
                $this->redirect('research/viewReproduction?id=' . $result['id']);
            }
        }
    }

    /**
     * View reproduction request details.
     */
    public function executeViewReproduction($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);

        if (!$this->researcher) {
            $this->redirect('research/register');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ReproductionService.php';
        $reproductionService = new ReproductionService();

        $requestId = (int) $request->getParameter('id');
        $this->reproductionRequest = $reproductionService->getRequest($requestId, $this->researcher->id);

        if (!$this->reproductionRequest) {
            $this->forward404('Request not found');
        }

        $this->items = $reproductionService->getItems($requestId);

        // Handle item actions
        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action');

            if ($action === 'add_item') {
                $objectId = (int) $request->getParameter('object_id');
                $result = $reproductionService->addItem($requestId, $this->researcher->id, [
                    'object_id' => $objectId,
                    'reproduction_type' => $request->getParameter('reproduction_type', 'digital_scan'),
                    'format' => $request->getParameter('format'),
                    'size' => $request->getParameter('size'),
                    'resolution' => $request->getParameter('resolution'),
                    'color_mode' => $request->getParameter('color_mode', 'color'),
                    'quantity' => (int) $request->getParameter('quantity', 1),
                    'special_instructions' => $request->getParameter('special_instructions'),
                ]);

                if (isset($result['error'])) {
                    $this->getUser()->setFlash('error', $result['error']);
                } else {
                    $this->getUser()->setFlash('success', 'Item added');
                }
                $this->redirect('research/viewReproduction?id=' . $requestId);
            }

            if ($action === 'remove_item') {
                $itemId = (int) $request->getParameter('item_id');
                $reproductionService->removeItem($requestId, $itemId, $this->researcher->id);
                $this->getUser()->setFlash('success', 'Item removed');
                $this->redirect('research/viewReproduction?id=' . $requestId);
            }

            if ($action === 'submit') {
                $result = $reproductionService->submitRequest($requestId, $this->researcher->id);
                if (isset($result['error'])) {
                    $this->getUser()->setFlash('error', $result['error']);
                } else {
                    $this->getUser()->setFlash('success', 'Request submitted');
                }
                $this->redirect('research/viewReproduction?id=' . $requestId);
            }
        }
    }

    // =========================================================================
    // BIBLIOGRAPHY MANAGEMENT
    // =========================================================================

    /**
     * List bibliographies.
     */
    public function executeBibliographies($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);

        if (!$this->researcher) {
            $this->redirect('research/register');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/BibliographyService.php';
        $bibliographyService = new BibliographyService();

        $this->bibliographies = $bibliographyService->getBibliographies($this->researcher->id);

        // Handle create
        if ($request->isMethod('post') && $request->getParameter('form_action') === 'create') {
            try {
                $bibliographyId = $bibliographyService->createBibliography($this->researcher->id, [
                    'name' => $request->getParameter('name'),
                    'description' => $request->getParameter('description'),
                    'citation_style' => $request->getParameter('citation_style', 'chicago'),
                ]);
                $this->getUser()->setFlash('success', 'Bibliography created');
                $this->redirect('research/viewBibliography?id=' . $bibliographyId);
            } catch (Exception $e) {
                $this->getUser()->setFlash('error', $e->getMessage());
            }
        }
    }

    /**
     * View bibliography details.
     */
    public function executeViewBibliography($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);

        if (!$this->researcher) {
            $this->redirect('research/register');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/BibliographyService.php';
        $bibliographyService = new BibliographyService();

        $bibliographyId = (int) $request->getParameter('id');
        $this->bibliography = $bibliographyService->getBibliography($bibliographyId, $this->researcher->id);

        if (!$this->bibliography) {
            $this->forward404('Bibliography not found');
        }

        $this->entries = DB::table('research_bibliography_entry')
            ->where('bibliography_id', $bibliographyId)
            ->orderBy('sort_order')
            ->get()->toArray();

        // Handle actions
        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action');

            if ($action === 'add_entry') {
                $objectId = (int) $request->getParameter('object_id');
                if ($objectId) {
                    $result = $bibliographyService->addEntryFromObject($bibliographyId, $this->researcher->id, $objectId);
                    if (isset($result['error'])) {
                        $this->getUser()->setFlash('error', $result['error']);
                    } else {
                        $this->getUser()->setFlash('success', 'Entry added');
                    }
                }
                $this->redirect('research/viewBibliography?id=' . $bibliographyId);
            }

            if ($action === 'remove_entry') {
                $entryId = (int) $request->getParameter('entry_id');
                DB::table('research_bibliography_entry')
                    ->where('id', $entryId)
                    ->where('bibliography_id', $bibliographyId)
                    ->delete();
                $this->getUser()->setFlash('success', 'Entry removed');
                $this->redirect('research/viewBibliography?id=' . $bibliographyId);
            }

            if ($action === 'delete') {
                DB::table('research_bibliography_entry')->where('bibliography_id', $bibliographyId)->delete();
                DB::table('research_bibliography')->where('id', $bibliographyId)->delete();
                $this->getUser()->setFlash('success', 'Bibliography deleted');
                $this->redirect('research/bibliographies');
            }
        }
    }

    /**
     * Export bibliography in various formats.
     */
    public function executeExportBibliography($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);

        if (!$researcher) {
            $this->redirect('research/register');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/BibliographyService.php';
        $bibliographyService = new BibliographyService();

        $bibliographyId = (int) $request->getParameter('id');
        $format = $request->getParameter('format', 'ris');

        $result = match ($format) {
            'bibtex' => $bibliographyService->exportBibTeX($bibliographyId, $researcher->id),
            'zotero' => $bibliographyService->exportZoteroRDF($bibliographyId, $researcher->id),
            'mendeley' => $bibliographyService->exportMendeleyJSON($bibliographyId, $researcher->id),
            'csl' => $bibliographyService->exportCSLJSON($bibliographyId, $researcher->id),
            default => $bibliographyService->exportRIS($bibliographyId, $researcher->id),
        };

        if (isset($result['error'])) {
            $this->getUser()->setFlash('error', $result['error']);
            $this->redirect('research/viewBibliography?id=' . $bibliographyId);
        }

        $this->getResponse()->setContentType($result['mime_type']);
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="' . $result['filename'] . '"');

        return $this->renderText($result['content']);
    }

    // =========================================================================
    // PRIVATE WORKSPACES (Enhanced)
    // =========================================================================

    /**
     * List researcher's workspaces.
     */
    public function executeWorkspaces($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);

        if (!$this->researcher) {
            $this->redirect('research/register');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/CollaborationService.php';
        $collaborationService = new CollaborationService();

        $this->workspaces = $collaborationService->getWorkspaces($this->researcher->id);

        // Handle create
        if ($request->isMethod('post') && $request->getParameter('form_action') === 'create') {
            $result = $collaborationService->createWorkspace($this->researcher->id, [
                'name' => $request->getParameter('name'),
                'description' => $request->getParameter('description'),
                'visibility' => $request->getParameter('visibility', 'private'),
            ]);

            if (isset($result['error'])) {
                $this->getUser()->setFlash('error', $result['error']);
            } else {
                $this->getUser()->setFlash('success', 'Workspace created');
                $this->redirect('research/viewWorkspace?id=' . $result['id']);
            }
        }
    }

    /**
     * View workspace details.
     */
    public function executeViewWorkspace($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);

        if (!$this->researcher) {
            $this->redirect('research/register');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/CollaborationService.php';
        $collaborationService = new CollaborationService();

        $workspaceId = (int) $request->getParameter('id');
        $this->workspaceData = $collaborationService->getWorkspace($workspaceId, $this->researcher->id);

        if (!$this->workspaceData) {
            $this->forward404('Workspace not found');
        }

        $this->members = $collaborationService->getMembers($workspaceId);
        $this->discussions = $collaborationService->getDiscussions($workspaceId);

        // Handle actions
        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action');

            if ($action === 'invite') {
                $email = trim($request->getParameter('email'));
                $role = $request->getParameter('role', 'member');
                $result = $collaborationService->addMember($workspaceId, $this->researcher->id, $email, $role);

                if (isset($result['error'])) {
                    $this->getUser()->setFlash('error', $result['error']);
                } else {
                    $this->getUser()->setFlash('success', 'Member invited');
                }
                $this->redirect('research/viewWorkspace?id=' . $workspaceId);
            }

            if ($action === 'create_discussion') {
                $result = $collaborationService->createDiscussion($workspaceId, $this->researcher->id, [
                    'title' => $request->getParameter('title'),
                    'content' => $request->getParameter('content'),
                ]);

                if (isset($result['error'])) {
                    $this->getUser()->setFlash('error', $result['error']);
                } else {
                    $this->getUser()->setFlash('success', 'Discussion created');
                }
                $this->redirect('research/viewWorkspace?id=' . $workspaceId);
            }

            if ($action === 'add_resource') {
                $result = $collaborationService->addResource($workspaceId, $this->researcher->id, [
                    'resource_type' => $request->getParameter('resource_type'),
                    'resource_id' => (int) $request->getParameter('resource_id'),
                    'title' => $request->getParameter('title'),
                    'notes' => $request->getParameter('notes'),
                ]);

                if (isset($result['error'])) {
                    $this->getUser()->setFlash('error', $result['error']);
                } else {
                    $this->getUser()->setFlash('success', 'Resource added');
                }
                $this->redirect('research/viewWorkspace?id=' . $workspaceId);
            }
        }
    }

    // =========================================================================
    // ADMIN: STATISTICS & ANALYTICS
    // =========================================================================

    /**
     * Admin statistics dashboard.
     */
    public function executeAdminStatistics($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->isAdministrator()) {
            $this->getUser()->setFlash('error', 'Administrator access required');
            $this->redirect('@homepage');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/StatisticsService.php';
        $statisticsService = new StatisticsService();

        $dateFrom = $request->getParameter('date_from', date('Y-m-01'));
        $dateTo = $request->getParameter('date_to', date('Y-m-d'));

        $this->stats = $statisticsService->getAdminStats($dateFrom, $dateTo);
        $this->mostViewed = $statisticsService->getMostViewedItems(10, $dateFrom, $dateTo);
        $this->mostCited = $statisticsService->getMostCitedItems(10, $dateFrom, $dateTo);
        $this->activeResearchers = $statisticsService->getActiveResearchers(10);

        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    // =========================================================================
    // API KEY MANAGEMENT
    // =========================================================================

    /**
     * Manage API keys.
     */
    public function executeApiKeys($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);

        if (!$this->researcher || $this->researcher->status !== 'approved') {
            $this->getUser()->setFlash('error', 'Must be an approved researcher');
            $this->redirect('research/dashboard');
        }

        $this->apiKeys = $this->service->getApiKeys($this->researcher->id);

        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action');

            if ($action === 'generate') {
                $name = trim($request->getParameter('name', 'API Key'));
                $permissions = $request->getParameter('permissions', []);
                $expiresAt = $request->getParameter('expires_at');

                $result = $this->service->generateApiKey($this->researcher->id, $name, $permissions, $expiresAt ?: null);

                if (isset($result['error'])) {
                    $this->getUser()->setFlash('error', $result['error']);
                } else {
                    $this->getUser()->setFlash('success', 'API key generated. Key: <strong>' . $result['key'] . '</strong> - Save this now, it will not be shown again.');
                }
                $this->redirect('research/apiKeys');
            }

            if ($action === 'revoke') {
                $keyId = (int) $request->getParameter('key_id');
                $this->service->revokeApiKey($keyId, $this->researcher->id);
                $this->getUser()->setFlash('success', 'API key revoked');
                $this->redirect('research/apiKeys');
            }
        }
    }

    // =========================================================================
    // RETRIEVAL QUEUE MANAGEMENT
    // =========================================================================

    /**
     * Retrieval queue dashboard for staff.
     */
    public function executeRetrievalQueue($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/RetrievalService.php';
        $retrievalService = new RetrievalService();

        $this->queueCounts = $retrievalService->getQueueCounts();
        $this->rooms = $this->service->getReadingRooms();

        $queueCode = $request->getParameter('queue', 'new');
        $queue = $retrievalService->getQueueByCode($queueCode);

        if ($queue) {
            $this->currentQueue = $queue;
            $this->requests = $retrievalService->getQueueRequests($queue->id);
        } else {
            $this->currentQueue = null;
            $this->requests = [];
        }

        // Handle status updates
        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action');
            $requestIds = $request->getParameter('request_ids', []);
            $userId = $this->getUser()->getAttribute('user_id');

            if ($action === 'update_status' && !empty($requestIds)) {
                $newStatus = $request->getParameter('new_status');
                $notes = $request->getParameter('notes');
                $count = $retrievalService->batchUpdateStatus($requestIds, $newStatus, $userId, $notes);
                $this->getUser()->setFlash('success', "{$count} request(s) updated");
            }

            $this->redirect('research/retrievalQueue?queue=' . $queueCode);
        }
    }

    /**
     * Print call slips.
     */
    public function executePrintCallSlips($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/RetrievalService.php';
        $retrievalService = new RetrievalService();

        $requestIds = $request->getParameter('ids');
        if (is_string($requestIds)) {
            $requestIds = array_map('intval', explode(',', $requestIds));
        }

        if (empty($requestIds)) {
            $this->getUser()->setFlash('error', 'No requests selected');
            $this->redirect('research/retrievalQueue');
        }

        $templateCode = $request->getParameter('template', 'call_slip_standard');
        $userId = $this->getUser()->getAttribute('user_id');

        // Mark as printed
        foreach ($requestIds as $requestId) {
            $retrievalService->markCallSlipPrinted($requestId, $userId);
        }

        // Generate print view
        $this->html = $retrievalService->renderBatchCallSlips($requestIds, $templateCode);
        $this->setLayout(false);
    }

    // =========================================================================
    // SEAT MANAGEMENT
    // =========================================================================

    /**
     * Manage reading room seats.
     */
    public function executeSeats($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->isAdministrator()) {
            $this->getUser()->setFlash('error', 'Administrator access required');
            $this->redirect('@homepage');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/SeatService.php';
        $seatService = new SeatService();

        $roomId = (int) $request->getParameter('room_id');
        $this->rooms = $this->service->getReadingRooms(false);
        $this->currentRoom = $roomId ? $this->service->getReadingRoom($roomId) : null;

        if ($roomId) {
            $this->seats = $seatService->getSeatsForRoom($roomId, false);
            $this->occupancy = $seatService->getRoomOccupancy($roomId);
        } else {
            $this->seats = [];
            $this->occupancy = null;
        }

        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action');

            if ($action === 'create') {
                $seatService->createSeat([
                    'reading_room_id' => $roomId,
                    'seat_number' => $request->getParameter('seat_number'),
                    'seat_label' => $request->getParameter('seat_label'),
                    'seat_type' => $request->getParameter('seat_type', 'standard'),
                    'zone' => $request->getParameter('zone'),
                    'has_power' => $request->getParameter('has_power') ? 1 : 0,
                    'has_lamp' => $request->getParameter('has_lamp') ? 1 : 0,
                    'has_computer' => $request->getParameter('has_computer') ? 1 : 0,
                    'notes' => $request->getParameter('notes'),
                ]);
                $this->getUser()->setFlash('success', 'Seat created');
            }

            if ($action === 'bulk_create') {
                $pattern = $request->getParameter('pattern');
                $seatType = $request->getParameter('seat_type', 'standard');
                $zone = $request->getParameter('zone');
                $count = $seatService->bulkCreateSeats($roomId, $pattern, $seatType, $zone);
                $this->getUser()->setFlash('success', "{$count} seat(s) created");
            }

            if ($action === 'update') {
                $seatId = (int) $request->getParameter('seat_id');
                $seatService->updateSeat($seatId, [
                    'seat_number' => $request->getParameter('seat_number'),
                    'seat_label' => $request->getParameter('seat_label'),
                    'seat_type' => $request->getParameter('seat_type'),
                    'zone' => $request->getParameter('zone'),
                    'has_power' => $request->getParameter('has_power') ? 1 : 0,
                    'has_lamp' => $request->getParameter('has_lamp') ? 1 : 0,
                    'has_computer' => $request->getParameter('has_computer') ? 1 : 0,
                    'is_active' => $request->getParameter('is_active') ? 1 : 0,
                    'notes' => $request->getParameter('notes'),
                ]);
                $this->getUser()->setFlash('success', 'Seat updated');
            }

            if ($action === 'delete') {
                $seatId = (int) $request->getParameter('seat_id');
                $seatService->deleteSeat($seatId);
                $this->getUser()->setFlash('success', 'Seat deactivated');
            }

            $this->redirect('research/seats?room_id=' . $roomId);
        }
    }

    /**
     * Seat assignment for a booking.
     */
    public function executeAssignSeat($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/SeatService.php';
        $seatService = new SeatService();

        $bookingId = (int) $request->getParameter('booking_id');
        $this->booking = $this->service->getBooking($bookingId);

        if (!$this->booking) {
            $this->forward404('Booking not found');
        }

        $this->availableSeats = $seatService->getAvailableSeats(
            $this->booking->reading_room_id,
            $this->booking->booking_date,
            $this->booking->start_time,
            $this->booking->end_time
        );

        $this->currentAssignment = $seatService->getSeatAssignment($bookingId);

        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action');
            $userId = $this->getUser()->getAttribute('user_id');

            if ($action === 'assign') {
                $seatId = (int) $request->getParameter('seat_id');
                try {
                    $seatService->assignSeat($bookingId, $seatId, $userId);
                    $this->getUser()->setFlash('success', 'Seat assigned');
                } catch (Exception $e) {
                    $this->getUser()->setFlash('error', $e->getMessage());
                }
            }

            if ($action === 'release') {
                $seatService->releaseSeat($bookingId, $userId);
                $this->getUser()->setFlash('success', 'Seat released');
            }

            if ($action === 'auto_assign') {
                $assignmentId = $seatService->autoAssignSeat($bookingId, [], $userId);
                if ($assignmentId) {
                    $this->getUser()->setFlash('success', 'Seat auto-assigned');
                } else {
                    $this->getUser()->setFlash('error', 'No available seats');
                }
            }

            $this->redirect('research/viewBooking?id=' . $bookingId);
        }
    }

    /**
     * Get seat map data (AJAX).
     */
    public function executeSeatMap($request)
    {
        $this->getResponse()->setContentType('application/json');

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/SeatService.php';
        $seatService = new SeatService();

        $roomId = (int) $request->getParameter('room_id');
        $date = $request->getParameter('date', date('Y-m-d'));
        $time = $request->getParameter('time', date('H:i:s'));

        $seatMap = $seatService->getSeatMapData($roomId, $date, $time);

        return $this->renderText(json_encode([
            'success' => true,
            'seats' => $seatMap,
        ]));
    }

    // =========================================================================
    // EQUIPMENT MANAGEMENT
    // =========================================================================

    /**
     * Manage reading room equipment.
     */
    public function executeEquipment($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->isAdministrator()) {
            $this->getUser()->setFlash('error', 'Administrator access required');
            $this->redirect('@homepage');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/EquipmentService.php';
        $equipmentService = new EquipmentService();

        $roomId = (int) $request->getParameter('room_id');
        $this->rooms = $this->service->getReadingRooms(false);
        $this->currentRoom = $roomId ? $this->service->getReadingRoom($roomId) : null;

        if ($roomId) {
            $this->equipment = $equipmentService->getEquipmentForRoom($roomId);
            $this->typeCounts = $equipmentService->getEquipmentTypeCounts($roomId);
        } else {
            $this->equipment = [];
            $this->typeCounts = [];
        }

        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action');

            if ($action === 'create') {
                $equipmentService->createEquipment([
                    'reading_room_id' => $roomId,
                    'name' => $request->getParameter('name'),
                    'code' => $request->getParameter('code'),
                    'equipment_type' => $request->getParameter('equipment_type'),
                    'brand' => $request->getParameter('brand'),
                    'model' => $request->getParameter('model'),
                    'serial_number' => $request->getParameter('serial_number'),
                    'description' => $request->getParameter('description'),
                    'location' => $request->getParameter('location'),
                    'requires_training' => $request->getParameter('requires_training') ? 1 : 0,
                    'max_booking_hours' => (int) $request->getParameter('max_booking_hours', 4),
                ]);
                $this->getUser()->setFlash('success', 'Equipment added');
            }

            if ($action === 'update') {
                $equipmentId = (int) $request->getParameter('equipment_id');
                $equipmentService->updateEquipment($equipmentId, [
                    'name' => $request->getParameter('name'),
                    'code' => $request->getParameter('code'),
                    'equipment_type' => $request->getParameter('equipment_type'),
                    'brand' => $request->getParameter('brand'),
                    'model' => $request->getParameter('model'),
                    'location' => $request->getParameter('location'),
                    'condition_status' => $request->getParameter('condition_status'),
                    'is_available' => $request->getParameter('is_available') ? 1 : 0,
                    'notes' => $request->getParameter('notes'),
                ]);
                $this->getUser()->setFlash('success', 'Equipment updated');
            }

            if ($action === 'maintenance') {
                $equipmentId = (int) $request->getParameter('equipment_id');
                $equipmentService->logMaintenance(
                    $equipmentId,
                    $request->getParameter('maintenance_description'),
                    $request->getParameter('new_condition', 'good'),
                    $request->getParameter('next_maintenance_date')
                );
                $this->getUser()->setFlash('success', 'Maintenance logged');
            }

            $this->redirect('research/equipment?room_id=' . $roomId);
        }
    }

    /**
     * Book equipment for a session.
     */
    public function executeBookEquipment($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);

        if (!$this->researcher || $this->researcher->status !== 'approved') {
            $this->getUser()->setFlash('error', 'Must be an approved researcher');
            $this->redirect('research/dashboard');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/EquipmentService.php';
        $equipmentService = new EquipmentService();

        $bookingId = (int) $request->getParameter('booking_id');
        $this->booking = $this->service->getBooking($bookingId);

        if (!$this->booking || $this->booking->researcher_id != $this->researcher->id) {
            $this->forward404('Booking not found');
        }

        $this->availableEquipment = $equipmentService->getAvailableEquipment(
            $this->booking->reading_room_id,
            $this->booking->booking_date,
            $this->booking->start_time,
            $this->booking->end_time
        );

        $this->bookedEquipment = $equipmentService->getBookingsForRoomBooking($bookingId);

        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action');

            if ($action === 'book') {
                $equipmentId = (int) $request->getParameter('equipment_id');
                try {
                    $equipment = $equipmentService->getEquipment($equipmentId);
                    $equipmentService->createBooking([
                        'booking_id' => $bookingId,
                        'researcher_id' => $this->researcher->id,
                        'equipment_id' => $equipmentId,
                        'booking_date' => $this->booking->booking_date,
                        'start_time' => $this->booking->start_time,
                        'end_time' => $this->booking->end_time,
                        'reading_room_id' => $equipment->reading_room_id,
                        'purpose' => $request->getParameter('purpose'),
                    ]);
                    $this->getUser()->setFlash('success', 'Equipment booked');
                } catch (Exception $e) {
                    $this->getUser()->setFlash('error', $e->getMessage());
                }
            }

            if ($action === 'cancel') {
                $equipmentBookingId = (int) $request->getParameter('equipment_booking_id');
                $equipmentService->cancelBooking($equipmentBookingId);
                $this->getUser()->setFlash('success', 'Equipment booking cancelled');
            }

            $this->redirect('research/bookEquipment?booking_id=' . $bookingId);
        }
    }

    // =========================================================================
    // WALK-IN VISITOR MANAGEMENT
    // =========================================================================

    /**
     * Register a walk-in visitor.
     */
    public function executeWalkIn($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/RetrievalService.php';
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/SeatService.php';
        $retrievalService = new RetrievalService();
        $seatService = new SeatService();

        $this->rooms = $this->service->getReadingRooms();
        $roomId = (int) $request->getParameter('room_id');
        $this->currentRoom = $roomId ? $this->service->getReadingRoom($roomId) : null;

        if ($roomId) {
            $this->currentWalkIns = $retrievalService->getCurrentWalkIns($roomId);
            $this->availableSeats = $seatService->getAvailableSeats($roomId, date('Y-m-d'), date('H:i:s'), '23:59:59');
        } else {
            $this->currentWalkIns = [];
            $this->availableSeats = [];
        }

        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action');
            $userId = $this->getUser()->getAttribute('user_id');

            if ($action === 'register') {
                $visitorId = $retrievalService->registerWalkIn([
                    'reading_room_id' => $roomId,
                    'first_name' => $request->getParameter('first_name'),
                    'last_name' => $request->getParameter('last_name'),
                    'email' => $request->getParameter('email'),
                    'phone' => $request->getParameter('phone'),
                    'id_type' => $request->getParameter('id_type'),
                    'id_number' => $request->getParameter('id_number'),
                    'organization' => $request->getParameter('organization'),
                    'purpose' => $request->getParameter('purpose'),
                    'research_topic' => $request->getParameter('research_topic'),
                    'rules_acknowledged' => $request->getParameter('rules_acknowledged') ? 1 : 0,
                    'seat_id' => $request->getParameter('seat_id') ?: null,
                    'checked_in_by' => $userId,
                ]);
                $this->getUser()->setFlash('success', 'Walk-in visitor registered');
            }

            if ($action === 'checkout') {
                $visitorId = (int) $request->getParameter('visitor_id');
                $retrievalService->checkOutWalkIn($visitorId, $userId);
                $this->getUser()->setFlash('success', 'Visitor checked out');
            }

            $this->redirect('research/walkIn?room_id=' . $roomId);
        }
    }

    // =========================================================================
    // ACTIVITIES (Classes, Events)
    // =========================================================================

    /**
     * List and manage activities.
     */
    public function executeActivities($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $this->rooms = $this->service->getReadingRooms();
        $status = $request->getParameter('status');
        $type = $request->getParameter('type');

        $query = DB::table('research_activity as a')
            ->leftJoin('research_reading_room as rm', 'a.reading_room_id', '=', 'rm.id');

        if ($status) {
            $query->where('a.status', $status);
        }

        if ($type) {
            $query->where('a.activity_type', $type);
        }

        $this->activities = $query->select('a.*', 'rm.name as room_name')
            ->orderBy('a.start_date', 'desc')
            ->limit(50)
            ->get()
            ->toArray();

        if ($request->isMethod('post') && $request->getParameter('form_action') === 'create') {
            $activityId = DB::table('research_activity')->insertGetId([
                'activity_type' => $request->getParameter('activity_type'),
                'title' => $request->getParameter('title'),
                'description' => $request->getParameter('description'),
                'organizer_name' => $request->getParameter('organizer_name'),
                'organizer_email' => $request->getParameter('organizer_email'),
                'organizer_phone' => $request->getParameter('organizer_phone'),
                'organization' => $request->getParameter('organization'),
                'expected_attendees' => (int) $request->getParameter('expected_attendees'),
                'reading_room_id' => $request->getParameter('reading_room_id') ?: null,
                'start_date' => $request->getParameter('start_date'),
                'end_date' => $request->getParameter('end_date') ?: null,
                'start_time' => $request->getParameter('start_time') ?: null,
                'end_time' => $request->getParameter('end_time') ?: null,
                'setup_requirements' => $request->getParameter('setup_requirements'),
                'av_requirements' => $request->getParameter('av_requirements'),
                'status' => 'requested',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->getUser()->setFlash('success', 'Activity request created');
            $this->redirect('research/viewActivity?id=' . $activityId);
        }
    }

    /**
     * View activity details.
     */
    public function executeViewActivity($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $activityId = (int) $request->getParameter('id');
        $this->activity = DB::table('research_activity as a')
            ->leftJoin('research_reading_room as rm', 'a.reading_room_id', '=', 'rm.id')
            ->where('a.id', $activityId)
            ->select('a.*', 'rm.name as room_name')
            ->first();

        if (!$this->activity) {
            $this->forward404('Activity not found');
        }

        $this->materials = DB::table('research_activity_material as am')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('am.object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->where('am.activity_id', $activityId)
            ->select('am.*', 'ioi.title as item_title')
            ->get()
            ->toArray();

        $this->participants = DB::table('research_activity_participant')
            ->where('activity_id', $activityId)
            ->orderBy('name')
            ->get()
            ->toArray();

        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action');
            $userId = $this->getUser()->getAttribute('user_id');

            if ($action === 'confirm' && $this->getUser()->isAdministrator()) {
                DB::table('research_activity')
                    ->where('id', $activityId)
                    ->update([
                        'status' => 'confirmed',
                        'confirmed_by' => $userId,
                        'confirmed_at' => date('Y-m-d H:i:s'),
                    ]);
                $this->getUser()->setFlash('success', 'Activity confirmed');
            }

            if ($action === 'cancel') {
                DB::table('research_activity')
                    ->where('id', $activityId)
                    ->update([
                        'status' => 'cancelled',
                        'cancelled_by' => $userId,
                        'cancelled_at' => date('Y-m-d H:i:s'),
                        'cancellation_reason' => $request->getParameter('cancellation_reason'),
                    ]);
                $this->getUser()->setFlash('success', 'Activity cancelled');
            }

            if ($action === 'add_participant') {
                DB::table('research_activity_participant')->insert([
                    'activity_id' => $activityId,
                    'name' => $request->getParameter('participant_name'),
                    'email' => $request->getParameter('participant_email'),
                    'role' => $request->getParameter('participant_role', 'visitor'),
                    'registered_at' => date('Y-m-d H:i:s'),
                ]);
                $this->getUser()->setFlash('success', 'Participant added');
            }

            $this->redirect('research/viewActivity?id=' . $activityId);
        }
    }

    // =========================================================================
    // ISSUE 149 PHASE 1: JOURNAL
    // =========================================================================

    protected function loadJournalService(): \JournalService
    {
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/JournalService.php';
        return new \JournalService();
    }

    public function executeJournal($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $journalService = $this->loadJournalService();
        $filters = [
            'project_id' => $request->getParameter('project_id') ?: null,
            'entry_type' => $request->getParameter('entry_type') ?: null,
            'date_from' => $request->getParameter('date_from') ?: null,
            'date_to' => $request->getParameter('date_to') ?: null,
            'search' => $request->getParameter('q') ?: null,
        ];
        $this->entries = $journalService->getEntries($this->researcher->id, $filters);
        $this->projects = DB::table('research_project as p')
            ->join('research_project_collaborator as pc', function ($j) use ($userId) {
                $j->on('p.id', '=', 'pc.project_id');
            })
            ->where('pc.researcher_id', $this->researcher->id)
            ->where('pc.status', 'accepted')
            ->select('p.id', 'p.title')
            ->orderBy('p.title')
            ->get()->toArray();
        $this->filters = $filters;

        if ($request->isMethod('post') && $request->getParameter('do') === 'create') {
            $content = $request->getParameter('content');
            if ($content) {
                $content = $this->service->sanitizeHtml($content);
                $journalService->createEntry($this->researcher->id, [
                    'title' => $request->getParameter('title'),
                    'content' => $content,
                    'content_format' => 'html',
                    'project_id' => $request->getParameter('project_id') ?: null,
                    'entry_type' => $request->getParameter('entry_type') ?: 'manual',
                    'time_spent_minutes' => $request->getParameter('time_spent_minutes') ?: null,
                    'tags' => $request->getParameter('tags'),
                    'entry_date' => $request->getParameter('entry_date') ?: date('Y-m-d'),
                ]);
                $this->getUser()->setFlash('success', 'Journal entry created');
            }
            $this->redirect('research/journal');
        }
    }

    public function executeJournalEntry($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $journalService = $this->loadJournalService();
        $id = (int) $request->getParameter('id');
        $this->entry = $journalService->getEntry($id);
        if (!$this->entry || $this->entry->researcher_id != $this->researcher->id) {
            $this->forward404('Entry not found');
        }

        $this->projects = DB::table('research_project as p')
            ->join('research_project_collaborator as pc', 'p.id', '=', 'pc.project_id')
            ->where('pc.researcher_id', $this->researcher->id)
            ->where('pc.status', 'accepted')
            ->select('p.id', 'p.title')->orderBy('p.title')->get()->toArray();

        if ($request->isMethod('post')) {
            $action = $request->getParameter('do');
            if ($action === 'delete') {
                $journalService->deleteEntry($id, $this->researcher->id);
                $this->getUser()->setFlash('success', 'Entry deleted');
                $this->redirect('research/journal');
            }
            $content = $this->service->sanitizeHtml($request->getParameter('content', ''));
            $journalService->updateEntry($id, $this->researcher->id, [
                'title' => $request->getParameter('title'),
                'content' => $content,
                'content_format' => 'html',
                'project_id' => $request->getParameter('project_id') ?: null,
                'time_spent_minutes' => $request->getParameter('time_spent_minutes') ?: null,
                'tags' => $request->getParameter('tags'),
                'entry_date' => $request->getParameter('entry_date') ?: $this->entry->entry_date,
            ]);
            $this->getUser()->setFlash('success', 'Entry updated');
            $this->redirect('research/journal/entry/' . $id);
        }
    }

    public function executeJournalNew($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $this->projects = DB::table('research_project as p')
            ->join('research_project_collaborator as pc', 'p.id', '=', 'pc.project_id')
            ->where('pc.researcher_id', $this->researcher->id)
            ->where('pc.status', 'accepted')
            ->select('p.id', 'p.title')->orderBy('p.title')->get()->toArray();

        if ($request->isMethod('post')) {
            $journalService = $this->loadJournalService();
            $content = $this->service->sanitizeHtml($request->getParameter('content', ''));
            if ($content) {
                $entryId = $journalService->createEntry($this->researcher->id, [
                    'title' => $request->getParameter('title'),
                    'content' => $content,
                    'content_format' => 'html',
                    'project_id' => $request->getParameter('project_id') ?: null,
                    'entry_type' => $request->getParameter('entry_type') ?: 'manual',
                    'time_spent_minutes' => $request->getParameter('time_spent_minutes') ?: null,
                    'tags' => $request->getParameter('tags'),
                    'entry_date' => $request->getParameter('entry_date') ?: date('Y-m-d'),
                ]);
                $this->getUser()->setFlash('success', 'Journal entry created');
                $this->redirect('research/journal/' . $entryId);
            }
        }
    }

    // =========================================================================
    // ISSUE 149 PHASE 2: REPORTS
    // =========================================================================

    protected function loadReportService(): \ReportService
    {
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ReportService.php';
        return new \ReportService();
    }

    public function executeReports($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $reportService = $this->loadReportService();
        $this->reports = $reportService->getReports($this->researcher->id, [
            'status' => $request->getParameter('status') ?: null,
            'project_id' => $request->getParameter('project_id') ?: null,
        ]);
        $this->currentStatus = $request->getParameter('status');
    }

    public function executeNewReport($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $reportService = $this->loadReportService();
        $this->templates = $reportService->getTemplates();
        $this->projects = DB::table('research_project as p')
            ->join('research_project_collaborator as pc', 'p.id', '=', 'pc.project_id')
            ->where('pc.researcher_id', $this->researcher->id)
            ->where('pc.status', 'accepted')
            ->select('p.id', 'p.title')->orderBy('p.title')->get()->toArray();

        if ($request->isMethod('post')) {
            $title = trim($request->getParameter('title'));
            $templateCode = $request->getParameter('template_type', 'custom');
            if ($title) {
                $reportId = $reportService->createFromTemplate($this->researcher->id, $templateCode, [
                    'title' => $title,
                    'description' => $request->getParameter('description'),
                    'project_id' => $request->getParameter('project_id') ?: null,
                ]);
                $this->getUser()->setFlash('success', 'Report created');
                $this->redirect('research/report/' . $reportId);
            }
        }
    }

    public function executeViewReport($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $reportService = $this->loadReportService();
        $id = (int) $request->getParameter('id');
        $this->report = $reportService->getReport($id);
        if (!$this->report || $this->report->researcher_id != $this->researcher->id) {
            $this->forward404('Report not found');
        }

        // Load peer reviews
        try {
            require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/PeerReviewService.php';
            $prService = new \PeerReviewService();
            $this->reviews = $prService->getReviews($id);
        } catch (\Exception $e) {
            $this->reviews = [];
        }

        // Load comments per section
        try {
            require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/CommentService.php';
            $commentService = new \CommentService();
            $this->sectionComments = [];
            foreach ($this->report->sections as $section) {
                $this->sectionComments[$section->id] = $commentService->getComments('report_section', $section->id);
            }
        } catch (\Exception $e) {
            $this->sectionComments = [];
        }

        if ($request->isMethod('post')) {
            $action = $request->getParameter('do');

            if ($action === 'update_status') {
                $reportService->updateReport($id, ['status' => $request->getParameter('status')]);
                $this->getUser()->setFlash('success', 'Status updated');
                $this->redirect('research/report/' . $id);
            }

            if ($action === 'add_section') {
                $reportService->addSection($id, [
                    'section_type' => $request->getParameter('section_type', 'text'),
                    'title' => $request->getParameter('section_title'),
                ]);
                $this->getUser()->setFlash('success', 'Section added');
                $this->redirect('research/report/' . $id);
            }

            if ($action === 'delete_section') {
                $reportService->deleteSection((int) $request->getParameter('section_id'));
                $this->getUser()->setFlash('success', 'Section deleted');
                $this->redirect('research/report/' . $id);
            }

            if ($action === 'delete_report') {
                $reportService->deleteReport($id, $this->researcher->id);
                $this->getUser()->setFlash('success', 'Report deleted');
                $this->redirect('research/reports');
            }

            if ($action === 'auto_populate' && $this->report->project_id) {
                $reportService->autoPopulateFromProject($id, $this->report->project_id);
                $this->getUser()->setFlash('success', 'Report populated from project data');
                $this->redirect('research/report/' . $id);
            }
        }
    }

    public function executeEditReport($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) { $this->redirect('research/register'); }

        $reportService = $this->loadReportService();
        $id = (int) $request->getParameter('id');
        $report = $reportService->getReport($id);
        if (!$report || $report->researcher_id != $researcher->id) {
            $this->forward404('Report not found');
        }

        if ($request->isMethod('post')) {
            $reportService->updateReport($id, [
                'title' => $request->getParameter('title'),
                'description' => $request->getParameter('description'),
                'project_id' => $request->getParameter('project_id') ?: null,
            ]);
            $this->getUser()->setFlash('success', 'Report updated');
        }
        $this->redirect('research/report/' . $id);
    }

    public function executeEditReportSection($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Not authenticated']));
        }

        $reportService = $this->loadReportService();
        $sectionId = (int) $request->getParameter('section_id');
        $content = $this->service->sanitizeHtml($request->getParameter('content', ''));

        $reportService->updateSection($sectionId, [
            'title' => $request->getParameter('title'),
            'content' => $content,
            'content_format' => 'html',
        ]);

        return $this->renderText(json_encode(['success' => true]));
    }

    public function executeReorderReportSections($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Not authenticated']));
        }

        $reportService = $this->loadReportService();
        $reportId = (int) $request->getParameter('id');
        $sectionIds = $request->getParameter('sections', []);

        if (is_string($sectionIds)) {
            $sectionIds = json_decode($sectionIds, true) ?: [];
        }

        $reportService->reorderSections($reportId, $sectionIds);

        return $this->renderText(json_encode(['success' => true]));
    }

    // =========================================================================
    // ISSUE 149 PHASE 3: EXPORT + IMPORT
    // =========================================================================

    protected function loadExportService(): \ReportExportService
    {
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ReportExportService.php';
        return new \ReportExportService();
    }

    public function executeExportReport($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }

        $id = (int) $request->getParameter('id');
        $format = $request->getParameter('format', 'pdf');
        $exportService = $this->loadExportService();

        if ($format === 'docx') {
            $file = $exportService->exportReportDocx($id);
            $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            $ext = 'docx';
        } else {
            $file = $exportService->exportReportPdf($id);
            $mime = 'application/pdf';
            $ext = 'pdf';
        }

        if (!$file) {
            $this->getUser()->setFlash('error', 'Export failed');
            $this->redirect('research/reports');
        }

        $this->getResponse()->setContentType($mime);
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="report.' . $ext . '"');
        $this->getResponse()->setContent(file_get_contents($file));
        @unlink($file);
        return sfView::NONE;
    }

    public function executeExportNotes($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) { $this->redirect('research/register'); }

        $format = $request->getParameter('format', 'pdf');
        $exportService = $this->loadExportService();

        if ($format === 'docx') {
            $file = $exportService->exportNotesDocx($researcher->id);
            $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            $ext = 'docx';
        } else {
            $file = $exportService->exportNotesPdf($researcher->id);
            $mime = 'application/pdf';
            $ext = 'pdf';
        }

        if (!$file) {
            $this->getUser()->setFlash('error', 'Export failed');
            $this->redirect('research/annotations');
        }

        $this->getResponse()->setContentType($mime);
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="notes.' . $ext . '"');
        $this->getResponse()->setContent(file_get_contents($file));
        @unlink($file);
        return sfView::NONE;
    }

    public function executeExportFindingAid($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) { $this->redirect('research/register'); }

        $collectionId = (int) $request->getParameter('id');
        $format = $request->getParameter('format', 'pdf');
        $exportService = $this->loadExportService();

        if ($format === 'docx') {
            $file = $exportService->exportFindingAidDocx($collectionId, $researcher->id);
            $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            $ext = 'docx';
        } else {
            $file = $exportService->exportFindingAidPdf($collectionId, $researcher->id);
            $mime = 'application/pdf';
            $ext = 'pdf';
        }

        if (!$file) {
            $this->getUser()->setFlash('error', 'Export failed');
            $this->redirect('research/collections');
        }

        $this->getResponse()->setContentType($mime);
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="finding-aid.' . $ext . '"');
        $this->getResponse()->setContent(file_get_contents($file));
        @unlink($file);
        return sfView::NONE;
    }

    public function executeExportJournal($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) { $this->redirect('research/register'); }

        $format = $request->getParameter('format', 'pdf');
        $exportService = $this->loadExportService();

        if ($format === 'docx') {
            $file = $exportService->exportJournalDocx($researcher->id);
            $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            $ext = 'docx';
        } else {
            $file = $exportService->exportJournalPdf($researcher->id);
            $mime = 'application/pdf';
            $ext = 'pdf';
        }

        if (!$file) {
            $this->getUser()->setFlash('error', 'Export failed');
            $this->redirect('research/journal');
        }

        $this->getResponse()->setContentType($mime);
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="journal.' . $ext . '"');
        $this->getResponse()->setContent(file_get_contents($file));
        @unlink($file);
        return sfView::NONE;
    }

    public function executeImportBibliography($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) { $this->redirect('research/register'); }

        $bibliographyId = (int) $request->getParameter('id');

        if ($request->isMethod('post')) {
            require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/BibliographyService.php';
            $bibService = new \BibliographyService();

            $format = $request->getParameter('import_format', 'bibtex');
            $content = '';

            // Handle file upload or pasted content
            if (!empty($_FILES['import_file']['tmp_name'])) {
                $content = file_get_contents($_FILES['import_file']['tmp_name']);
            } else {
                $content = $request->getParameter('import_content', '');
            }

            if (empty($content)) {
                $this->getUser()->setFlash('error', 'No content provided');
                $this->redirect('research/bibliography/' . $bibliographyId);
            }

            if ($format === 'ris') {
                $result = $bibService->importRIS($bibliographyId, $content);
            } else {
                $result = $bibService->importBibTeX($bibliographyId, $content);
            }

            $msg = "Imported {$result['imported']} of {$result['total']} entries.";
            if (!empty($result['errors'])) {
                $msg .= ' Errors: ' . implode('; ', array_slice($result['errors'], 0, 3));
            }
            $this->getUser()->setFlash($result['imported'] > 0 ? 'success' : 'error', $msg);
            $this->redirect('research/bibliography/' . $bibliographyId);
        }

        $this->redirect('research/bibliography/' . $bibliographyId);
    }

    // =========================================================================
    // ISSUE 149 PHASE 4: NOTIFICATIONS + DASHBOARD
    // =========================================================================

    protected function loadNotificationService(): \NotificationService
    {
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/NotificationService.php';
        return new \NotificationService();
    }

    public function executeNotifications($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $notifService = $this->loadNotificationService();

        if ($request->isMethod('post')) {
            $action = $request->getParameter('do');

            if ($action === 'mark_read') {
                $notifService->markAsRead((int) $request->getParameter('id'), $this->researcher->id);
            } elseif ($action === 'mark_all_read') {
                $notifService->markAllAsRead($this->researcher->id);
                $this->getUser()->setFlash('success', 'All notifications marked as read');
            } elseif ($action === 'update_preference') {
                $notifService->updatePreference($this->researcher->id, $request->getParameter('notification_type'), [
                    'email_enabled' => $request->getParameter('email_enabled') ? 1 : 0,
                    'in_app_enabled' => $request->getParameter('in_app_enabled') ? 1 : 0,
                    'digest_frequency' => $request->getParameter('digest_frequency', 'immediate'),
                ]);
                $this->getUser()->setFlash('success', 'Preferences updated');
            }
            $this->redirect('research/notifications');
        }

        $filters = [
            'type' => $request->getParameter('type') ?: null,
            'is_read' => $request->getParameter('filter') === 'unread' ? 0 : null,
        ];
        $this->notifications = $notifService->getNotifications($this->researcher->id, $filters);
        $this->unreadCount = $notifService->getUnreadCount($this->researcher->id);
        $this->preferences = $notifService->getPreferences($this->researcher->id);
        $this->currentFilter = $request->getParameter('filter', 'all');
    }

    public function executeNotificationsApi($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['count' => 0]));
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) {
            return $this->renderText(json_encode(['count' => 0]));
        }

        $notifService = $this->loadNotificationService();

        if ($request->isMethod('post') && $request->getParameter('do') === 'mark_read') {
            $notifService->markAsRead((int) $request->getParameter('id'), $researcher->id);
            return $this->renderText(json_encode(['success' => true]));
        }

        $count = $notifService->getUnreadCount($researcher->id);
        $recent = $notifService->getNotifications($researcher->id, ['is_read' => 0, 'limit' => 5]);

        return $this->renderText(json_encode(['count' => $count, 'notifications' => $recent]));
    }

    // =========================================================================
    // ISSUE 149 PHASE 5: VISUALIZATION DATA
    // =========================================================================

    public function executeVisualizationData($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/StatisticsService.php';
        $statsService = new \StatisticsService();

        $type = $request->getParameter('type', 'registrations_timeline');
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);

        $data = $statsService->getVisualizationData($type, [
            'researcher_id' => $researcher ? $researcher->id : null,
            'months' => (int) $request->getParameter('months', 12),
            'date_from' => $request->getParameter('date_from'),
            'date_to' => $request->getParameter('date_to'),
        ]);

        return $this->renderText(json_encode(['data' => $data]));
    }

    // =========================================================================
    // ISSUE 149 PHASE 6: INSTITUTIONAL SHARING
    // =========================================================================

    protected function loadShareService(): \InstitutionalShareService
    {
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/InstitutionalShareService.php';
        return new \InstitutionalShareService();
    }

    public function executeInstitutions($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->isAdministrator()) {
            $this->getUser()->setFlash('error', 'Administrator access required');
            $this->redirect('@homepage');
        }

        $shareService = $this->loadShareService();
        $this->institutions = $shareService->getInstitutions(false);
    }

    public function executeEditInstitution($request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->isAdministrator()) {
            $this->getUser()->setFlash('error', 'Administrator access required');
            $this->redirect('@homepage');
        }

        $shareService = $this->loadShareService();
        $id = (int) $request->getParameter('id');
        $this->institution = $id ? $shareService->getInstitution($id) : null;
        $this->isNew = !$this->institution;

        if ($request->isMethod('post')) {
            $data = [
                'name' => $request->getParameter('name'),
                'code' => $request->getParameter('code'),
                'description' => $request->getParameter('description'),
                'url' => $request->getParameter('url'),
                'contact_name' => $request->getParameter('contact_name'),
                'contact_email' => $request->getParameter('contact_email'),
                'is_active' => $request->getParameter('is_active') ? 1 : 0,
            ];

            if ($id && $this->institution) {
                $shareService->updateInstitution($id, $data);
                $this->getUser()->setFlash('success', 'Institution updated');
            } else {
                $shareService->createInstitution($data);
                $this->getUser()->setFlash('success', 'Institution created');
            }
            $this->redirect('research/admin/institutions');
        }
    }

    public function executeShareProject($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $projectId = (int) $request->getParameter('id');
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ProjectService.php';
        $projectService = new \ProjectService();
        $this->project = $projectService->getProject($projectId);
        if (!$this->project) { $this->forward404('Project not found'); }

        $shareService = $this->loadShareService();
        $this->shares = $shareService->getShares($projectId);
        $this->institutions = $shareService->getInstitutions();

        if ($request->isMethod('post')) {
            $action = $request->getParameter('do');

            if ($action === 'create_share') {
                $shareService->createShare($projectId, $this->researcher->id, [
                    'share_type' => $request->getParameter('share_type', 'view'),
                    'institution_id' => $request->getParameter('institution_id') ?: null,
                    'message' => $request->getParameter('message'),
                    'expires_at' => $request->getParameter('expires_at') ?: null,
                ]);
                $this->getUser()->setFlash('success', 'Share link created');
            } elseif ($action === 'revoke_share') {
                $shareService->revokeShare((int) $request->getParameter('share_id'));
                $this->getUser()->setFlash('success', 'Share revoked');
            }
            $this->redirect('research/project/' . $projectId . '/share');
        }
    }

    public function executeAcceptShare($request)
    {
        $token = $request->getParameter('token');
        $shareService = $this->loadShareService();
        $share = $shareService->getShareByToken($token);

        if (!$share || $share->status === 'revoked' || $share->status === 'expired') {
            $this->getUser()->setFlash('error', 'Share link is invalid, revoked, or expired');
            $this->redirect('@homepage');
        }

        if ($this->getUser()->isAuthenticated()) {
            $userId = $this->getUser()->getAttribute('user_id');
            $researcher = $this->service->getResearcherByUserId($userId);
            if ($researcher) {
                $shareService->acceptShare($share->id, $researcher->id);
                $this->getUser()->setFlash('success', 'Share accepted');
                $this->redirect('research/project/' . $share->project_id);
            }
        }

        // For unauthenticated users, redirect to external access
        $this->redirect('research/share/' . $token);
    }

    public function executeExternalAccess($request)
    {
        $token = $request->getParameter('token');
        $shareService = $this->loadShareService();
        $this->share = $shareService->getShareByToken($token);

        if (!$this->share || !in_array($this->share->status, ['pending', 'active'])) {
            $this->getUser()->setFlash('error', 'Share link is invalid or expired');
            $this->redirect('@homepage');
        }

        // If posting registration as external collaborator
        if ($request->isMethod('post') && $request->getParameter('do') === 'register_external') {
            $collabId = $shareService->addExternalCollaborator($this->share->id, [
                'name' => $request->getParameter('name'),
                'email' => $request->getParameter('email'),
                'institution' => $request->getParameter('institution'),
                'orcid_id' => $request->getParameter('orcid_id'),
                'role' => $this->share->share_type === 'view' ? 'viewer' : 'contributor',
            ]);

            // Activate share if still pending
            if ($this->share->status === 'pending') {
                $shareService->acceptShare($this->share->id, 0);
            }

            $collab = DB::table('research_external_collaborator')->where('id', $collabId)->first();
            $this->getUser()->setFlash('success', 'Access granted');
            $this->redirect('research/share/' . $token . '?access_token=' . $collab->access_token);
        }

        // If already has access token, load project data
        $accessToken = $request->getParameter('access_token');
        $this->externalUser = null;
        $this->projectData = null;

        if ($accessToken) {
            $this->externalUser = $shareService->authenticateExternal($accessToken);
            if ($this->externalUser) {
                require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ProjectService.php';
                $projectService = new \ProjectService();
                $this->projectData = $projectService->getProject($this->share->project_id);
                if ($this->projectData) {
                    $this->projectData->resources = $projectService->getResources($this->share->project_id);
                    $this->projectData->milestones = $projectService->getMilestones($this->share->project_id);
                }
            }
        }

        $this->setLayout(false);
    }

    // =========================================================================
    // ISSUE 149 PHASE 7: COMMENTS + PEER REVIEW
    // =========================================================================

    public function executeCommentApi($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Not authenticated']));
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Not a researcher']));
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/CommentService.php';
        $commentService = new \CommentService();

        $action = $request->getParameter('do', 'add');

        if ($action === 'add') {
            $id = $commentService->addComment(
                $researcher->id,
                $request->getParameter('entity_type'),
                (int) $request->getParameter('entity_id'),
                $request->getParameter('content'),
                $request->getParameter('parent_id') ? (int) $request->getParameter('parent_id') : null
            );
            return $this->renderText(json_encode(['success' => true, 'id' => $id]));
        }

        if ($action === 'resolve') {
            $commentService->resolveComment((int) $request->getParameter('id'), $researcher->id);
            return $this->renderText(json_encode(['success' => true]));
        }

        if ($action === 'delete') {
            $commentService->deleteComment((int) $request->getParameter('id'), $researcher->id);
            return $this->renderText(json_encode(['success' => true]));
        }

        if ($action === 'list') {
            $comments = $commentService->getComments(
                $request->getParameter('entity_type'),
                (int) $request->getParameter('entity_id')
            );
            return $this->renderText(json_encode(['success' => true, 'comments' => $comments]));
        }

        return $this->renderText(json_encode(['success' => false, 'error' => 'Unknown action']));
    }

    public function executeRequestReview($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) { $this->redirect('research/register'); }

        $reportId = (int) $request->getParameter('id');

        if ($request->isMethod('post')) {
            require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/PeerReviewService.php';
            $prService = new \PeerReviewService();

            $reviewerId = (int) $request->getParameter('reviewer_id');
            if ($reviewerId && $reviewerId !== $researcher->id) {
                $prService->requestReview($reportId, $researcher->id, $reviewerId);

                // Create notification for reviewer
                try {
                    $notifService = $this->loadNotificationService();
                    $report = DB::table('research_report')->where('id', $reportId)->first();
                    $notifService->createNotification(
                        $reviewerId,
                        'collaboration',
                        'Peer review requested',
                        $researcher->first_name . ' ' . $researcher->last_name . ' requested your review of "' . ($report->title ?? 'Report') . '"',
                        'research/report/' . $reportId,
                        'report',
                        $reportId
                    );
                } catch (\Exception $e) {
                    // Notification is non-critical
                }

                $this->getUser()->setFlash('success', 'Review requested');
            }
        }
        $this->redirect('research/report/' . $reportId);
    }

    public function executeSubmitReview($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) { $this->redirect('research/register'); }

        $reportId = (int) $request->getParameter('id');
        $reviewId = (int) $request->getParameter('review_id');

        if ($request->isMethod('post')) {
            require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/PeerReviewService.php';
            $prService = new \PeerReviewService();

            $action = $request->getParameter('do', 'submit');

            if ($action === 'decline') {
                $prService->declineReview($reviewId, $researcher->id);
                $this->getUser()->setFlash('success', 'Review declined');
            } else {
                $prService->submitReview($reviewId, $researcher->id, [
                    'feedback' => $request->getParameter('feedback'),
                    'rating' => $request->getParameter('rating') ?: null,
                ]);
                $this->getUser()->setFlash('success', 'Review submitted');
            }
        }
        $this->redirect('research/report/' . $reportId);
    }
}
