<?php
use Illuminate\Database\Capsule\Manager as DB;

class researchActions extends sfActions
{
    protected $service;

    public function preExecute()
    {
        require_once sfConfig::get('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ResearchService.php';
        $this->service = new ResearchService();
    }

    public function executeIndex(sfWebRequest $request)
    {
        $this->redirect("research/dashboard");
    }

    public function executeDashboard(sfWebRequest $request)
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

    public function executeRegister(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->getUser()->setFlash('error', 'Please log in to register');
            $this->redirect('user/login');
        }
        $userId = $this->getUser()->getAttribute('user_id');
        if ($this->service->getResearcherByUserId($userId)) {
            $this->redirect('research/profile');
        }
        $this->user = DB::table('user')->where('id', $userId)->first();
        if ($request->isMethod('post')) {
            try {
                $this->service->registerResearcher([
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
                ]);
                $this->getUser()->setFlash('success', 'Registration submitted');
                $this->redirect('research/dashboard');
            } catch (Exception $e) {
                $this->getUser()->setFlash('error', $e->getMessage());
            }
        }
    }

    public function executeProfile(sfWebRequest $request)
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

    public function executeResearchers(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $this->researchers = $this->service->getResearchers([
            'status' => $request->getParameter('status'),
            'search' => $request->getParameter('q'),
        ]);
        $this->currentStatus = $request->getParameter('status');
    }

    public function executeViewResearcher(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $id = (int) $request->getParameter('id');
        $this->researcher = $this->service->getResearcher($id);
        if (!$this->researcher) { $this->forward404('Not found'); }
        if ($request->isMethod('post')) {
            $action = $request->getParameter('do');
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

    public function executeBookings(sfWebRequest $request)
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

    public function executeBook(sfWebRequest $request)
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
            ]);
            foreach ($request->getParameter('materials', []) as $objectId) {
                $this->service->addMaterialRequest($bookingId, (int) $objectId);
            }
            $this->getUser()->setFlash('success', 'Booking submitted');
            $this->redirect('research/viewBooking?id=' . $bookingId);
        }
    }

    public function executeViewBooking(sfWebRequest $request)
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
            $action = $request->getParameter('action');
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

    public function executeViewBooking_OLD(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $id = (int) $request->getParameter('id');
        $this->booking = $this->service->getBooking($id);
        if (!$this->booking) { $this->forward404('Not found'); }
        if ($request->isMethod('post')) {
            $action = $request->getParameter('do');
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

    public function executeWorkspace(sfWebRequest $request)
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
            $action = $request->getParameter('action');
            if ($action === 'create_collection') {
                $name = trim($request->getParameter('collection_name'));
                $description = trim($request->getParameter('collection_description'));
                if ($name) {
                    $this->service->createCollection($this->researcher->id, [
                        'name' => $name,
                        'description' => $description,
                    ]);
                    $this->getUser()->setFlash('success', 'Collection created successfully.');
                    $this->redirect('research/workspace');
                }
            }
        }
    }

    public function executeSavedSearches(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }
        if ($request->isMethod('post')) {
            $action = $request->getParameter('do');
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

    public function executeCollections(sfWebRequest $request)
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

    public function executeViewCollection(sfWebRequest $request)
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
            $action = $request->getParameter('do');
            
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
                        'is_public' => $isPublic,
                        'updated_at' => date('Y-m-d H:i:s'),
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

    public function executeAnnotations(sfWebRequest $request)
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
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    $this->getUser()->setFlash('success', 'Note updated');
                }
                $this->redirect('research/annotations');
            }
        }
        
        $this->annotations = $this->service->getAnnotations($this->researcher->id);
    }

    public function executeCite(sfWebRequest $request)
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

    public function executePublicRegister(sfWebRequest $request)
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
            if (DB::table('user')->where('email', $email)->exists()) {
                $errors[] = 'Email address is already registered';
            }
            if (DB::table('user')->where('username', $username)->exists()) {
                $errors[] = 'Username is already taken';
            }

            if (!empty($errors)) {
                $this->getUser()->setFlash('error', implode('<br>', $errors));
                return sfView::SUCCESS;
            }

            try {
                DB::beginTransaction();
                $userId = $this->createAtomUser($username, $email, $password);
                DB::table('acl_user_group')->insert(['user_id' => $userId, 'group_id' => 99]);
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
                ]);
                DB::commit();
                $this->getUser()->setFlash('success', 'Registration successful! Pending approval.');
                $this->redirect('research/registrationComplete');
            } catch (Exception $e) {
                DB::rollBack();
                $this->getUser()->setFlash('error', 'Registration failed: ' . $e->getMessage());
            }
        }
    }

    public function executeRegistrationComplete(sfWebRequest $request)
    {
    }

    protected function createAtomUser(string $username, string $email, string $password): int
    {
        $salt = base64_encode(random_bytes(32));
        $passwordHash = sha1($salt . $password);
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
        
        return $objectId;
    }

    // =========================================================================
    // PASSWORD RESET
    // =========================================================================

    public function executePasswordResetRequest(sfWebRequest $request)
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
                $resetUrl = sfConfig::get('app_siteBaseUrl', 'https://psis.theahg.co.za') . '/index.php/research/passwordReset?token=' . $token;
                error_log("Password reset for {$email}: {$resetUrl}");
            }
            $this->getUser()->setFlash('success', 'If an account exists, you will receive reset instructions.');
            $this->redirect('research/passwordResetRequest');
        }
    }

    public function executePasswordReset(sfWebRequest $request)
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
            $salt = base64_encode(random_bytes(32));
            $passwordHash = sha1($salt . $password);
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

    public function executeRooms(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->isAdministrator()) {
            $this->getUser()->setFlash('error', 'Administrator access required');
            $this->redirect('@homepage');
        }
        $this->rooms = $this->service->getReadingRooms(false);
    }

    public function executeEditRoom(sfWebRequest $request)
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
                'updated_at' => date('Y-m-d H:i:s'),
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

    public function executeApproveResearcher(sfWebRequest $request)
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

    public function executeRejectResearcher(sfWebRequest $request)
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
        DB::table('research_researcher')->where('id', $id)->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->getUser()->setFlash('success', 'Researcher registration rejected');
        $this->redirect('research/researchers');
    }

    public function executeAdminResetPassword(sfWebRequest $request)
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
        $salt = base64_encode(random_bytes(32));
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

    public function executeCheckIn(sfWebRequest $request)
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

    public function executeCheckOut(sfWebRequest $request)
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
    public function executeAddToCollection(sfWebRequest $request)
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
    public function executeCreateCollectionAjax(sfWebRequest $request)
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
    public function executeGenerateFindingAid(sfWebRequest $request)
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
    public function executeSearchItems(sfWebRequest $request)
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
}