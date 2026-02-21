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

        // Set sidebar active key for all actions
        $this->sidebarActive = $this->getSidebarActiveKey();

        // Set unread notifications count for sidebar badge
        $this->unreadNotifications = 0;
        if ($this->getUser()->isAuthenticated()) {
            $userId = $this->getUser()->getAttribute('user_id');
            $researcher = $this->service->getResearcherByUserId($userId);
            if ($researcher) {
                try {
                    $this->unreadNotifications = (int) DB::table('research_notification')
                        ->where('researcher_id', $researcher->id)
                        ->where('is_read', 0)
                        ->count();
                } catch (\Exception $e) {
                    // Table may not exist yet
                }
            }
        }
    }

    protected function getSidebarActiveKey(): string
    {
        $action = $this->getContext()->getActionName();
        $map = [
            'dashboard'             => 'workspace',
            'workspace'             => 'workspace',
            'annotations'           => 'workspace',
            'savedSearches'         => 'workspace',
            'projects'              => 'projects',
            'viewProject'           => 'projects',
            'editProject'           => 'projects',
            'shareProject'          => 'projects',
            'projectCollaborators'  => 'projects',
            'snapshots'             => 'projects',
            'viewSnapshot'          => 'projects',
            'compareSnapshots'      => 'projects',
            'hypotheses'            => 'projects',
            'viewHypothesis'        => 'projects',
            'sourceAssessment'      => 'projects',
            'trustScore'            => 'projects',
            'assertions'            => 'projects',
            'viewAssertion'         => 'projects',
            'knowledgeGraph'        => 'projects',
            'extractionJobs'        => 'projects',
            'viewExtractionJob'     => 'projects',
            'validationQueue'       => 'projects',
            'documentTemplates'     => 'projects',
            'entityResolution'      => 'projects',
            'timelineBuilder'       => 'projects',
            'mapBuilder'            => 'projects',
            'annotationStudio'      => 'projects',
            'networkGraph'          => 'projects',
            'evidenceViewer'        => 'projects',
            'complianceDashboard'   => 'projects',
            'reproducibilityPack'   => 'projects',
            'packageProject'        => 'projects',
            'mintDoi'               => 'projects',
            'ethicsMilestones'      => 'projects',
            'assertionBatchReview'  => 'projects',
            'workspaces'            => 'workspaces',
            'viewWorkspace'         => 'workspaces',
            'inviteCollaborator'    => 'workspaces',
            'collections'           => 'collections',
            'viewCollection'        => 'collections',
            'journal'               => 'journal',
            'journalNew'            => 'journal',
            'journalEntry'          => 'journal',
            'bibliographies'        => 'bibliographies',
            'viewBibliography'      => 'bibliographies',
            'cite'                  => 'bibliographies',
            'reports'               => 'reports',
            'viewReport'            => 'reports',
            'newReport'             => 'reports',
            'reproductions'         => 'reproductions',
            'newReproduction'       => 'reproductions',
            'viewReproduction'      => 'reproductions',
            'book'                  => 'book',
            'bookEquipment'         => 'book',
            'bookings'              => 'bookings',
            'viewBooking'           => 'bookings',
            'notifications'         => 'notifications',
            'researchers'           => 'researchers',
            'viewResearcher'        => 'researchers',
            'rooms'                 => 'rooms',
            'editRoom'              => 'rooms',
            'seats'                 => 'seats',
            'assignSeat'            => 'seats',
            'equipment'             => 'equipment',
            'retrievalQueue'        => 'retrievalQueue',
            'walkIn'                => 'walkIn',
            'adminTypes'            => 'adminTypes',
            'editResearcherType'    => 'adminTypes',
            'adminStatistics'       => 'adminStatistics',
            'activities'            => 'activities',
            'viewActivity'          => 'activities',
            'institutions'          => 'institutions',
            'editInstitution'       => 'institutions',
            'profile'               => 'profile',
            'apiKeys'               => 'profile',
            'renewal'               => 'profile',
            'odrlPolicies'          => 'odrlPolicies',
        ];

        return $map[$action] ?? '';
    }

    public function executeIndex($request)
    {
        $this->redirect("research/dashboard");
    }

    public function executeDashboard($request)
    {
        $this->stats = $this->service->getDashboardStats();
        $this->researcher = null;
        $this->enhancedData = [];
        $this->unreadNotifications = 0;
        $this->recentActivity = [];
        if ($this->getUser()->isAuthenticated()) {
            $userId = $this->getUser()->getAttribute('user_id');
            $this->researcher = $this->service->getResearcherByUserId($userId);
            if ($this->researcher && $this->researcher->status === 'approved') {
                $this->enhancedData = $this->service->getEnhancedDashboardData($this->researcher->id);
                $this->unreadNotifications = $this->enhancedData['unread_notifications'] ?? 0;
                $this->recentActivity = $this->enhancedData['recent_activity'] ?? [];
            }
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
                // Send email notification
                $this->sendResearcherEmail('pending', $data);
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
                $this->sendResearcherEmail('approved', $this->researcher);
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

            // Auto-journal: booking created
            try {
                require_once sfConfig::get('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/JournalService.php';
                $journal = new JournalService();
                $bookingDate = $request->getParameter('booking_date');
                $journal->createAutoEntry(
                    $this->researcher->id,
                    'auto_booking',
                    'Booked visit for ' . $bookingDate,
                    'Created reading room booking',
                    null,
                    'booking',
                    $bookingId
                );
            } catch (\Exception $e) { /* silent */ }

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
                $this->sendBookingEmail($this->booking, 'confirmed');
                $this->getUser()->setFlash('success', 'Booking confirmed');
            } elseif ($action === 'cancel') {
                $this->service->cancelBooking($bookingId, 'Cancelled by staff');
                $this->sendBookingEmail($this->booking, 'cancelled');
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
                $searchName = $request->getParameter('name');
                $searchId = $this->service->saveSearch($this->researcher->id, [
                    'name' => $searchName,
                    'search_query' => $request->getParameter('search_query'),
                ]);

                // Auto-journal: saved search
                try {
                    require_once sfConfig::get('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/JournalService.php';
                    $journal = new JournalService();
                    $journal->createAutoEntry(
                        $this->researcher->id,
                        'auto_search',
                        'Saved search: ' . ($searchName ?: 'Untitled'),
                        'Saved a search query',
                        null,
                        'search',
                        $searchId
                    );
                } catch (\Exception $e) { /* silent */ }
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

                        // Auto-journal: items added to collection
                        try {
                            require_once sfConfig::get('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/JournalService.php';
                            $journal = new JournalService();
                            $collectionName = $this->collection->name ?? 'Collection';
                            $journal->createAutoEntry(
                                $researcher->id,
                                'auto_collection',
                                'Added ' . $addedCount . ' item(s) to ' . $collectionName,
                                'Added items to collection',
                                null,
                                'collection',
                                $id
                            );
                        } catch (\Exception $e) { /* silent */ }
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
                if (class_exists('\\Illuminate\\Database\\Capsule\\Manager')) {
                    DB::table('research_collection_item')->where('collection_id', $id)->delete();
                    DB::table('research_collection')->where('id', $id)->delete();
                } else {
                    $conn = \Propel::getConnection();
                    $stmt = $conn->prepare('DELETE FROM research_collection_item WHERE collection_id = ?');
                    $stmt->execute([$id]);
                    $stmt = $conn->prepare('DELETE FROM research_collection WHERE id = ?');
                    $stmt->execute([$id]);
                }
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
                $collectionId = (int) $request->getParameter('collection_id') ?: null;
                $entityType = $request->getParameter('entity_type', 'information_object');
                $tags = trim($request->getParameter('tags', ''));
                $visibility = $request->getParameter('visibility', 'private');
                $contentFormat = $request->getParameter('content_format', 'text');
                $validEntityTypes = ['information_object', 'actor', 'repository', 'accession', 'term'];
                if ($content) {
                    DB::table('research_annotation')->insert([
                        'researcher_id' => $this->researcher->id,
                        'object_id' => $objectId,
                        'entity_type' => in_array($entityType, $validEntityTypes) ? $entityType : 'information_object',
                        'collection_id' => $collectionId,
                        'title' => $title,
                        'content' => $content,
                        'tags' => $tags ?: null,
                        'content_format' => in_array($contentFormat, ['text', 'html']) ? $contentFormat : 'text',
                        'visibility' => in_array($visibility, ['private', 'shared', 'public']) ? $visibility : 'private',
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                    $newId = DB::getPdo()->lastInsertId();
                    $this->getUser()->setFlash('success', 'Note created');

                    // Auto-journal: annotation created
                    try {
                        require_once sfConfig::get('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/JournalService.php';
                        $journal = new JournalService();
                        $journal->createAutoEntry(
                            $this->researcher->id,
                            'auto_annotation',
                            'Created note: ' . ($title ?: 'Untitled'),
                            'Created research note',
                            null,
                            'annotation',
                            (int) $newId
                        );
                    } catch (\Exception $e) { /* silent */ }
                }
                $this->redirect('research/annotations');
            }

            if ($action === 'update') {
                $id = (int) $request->getParameter('id');
                $title = trim($request->getParameter('title'));
                $content = trim($request->getParameter('content'));
                $objectId = (int) $request->getParameter('object_id') ?: null;
                $collectionId = (int) $request->getParameter('collection_id') ?: null;
                $entityType = $request->getParameter('entity_type', 'information_object');
                $tags = trim($request->getParameter('tags', ''));
                $visibility = $request->getParameter('visibility', 'private');
                $contentFormat = $request->getParameter('content_format', 'text');
                $validEntityTypes = ['information_object', 'actor', 'repository', 'accession', 'term'];
                if ($content) {
                    DB::table('research_annotation')
                        ->where('id', $id)
                        ->where('researcher_id', $this->researcher->id)
                        ->update([
                            'title' => $title,
                            'content' => $content,
                            'object_id' => $objectId,
                            'entity_type' => in_array($entityType, $validEntityTypes) ? $entityType : 'information_object',
                            'collection_id' => $collectionId,
                            'tags' => $tags ?: null,
                            'content_format' => in_array($contentFormat, ['text', 'html']) ? $contentFormat : 'text',
                            'visibility' => in_array($visibility, ['private', 'shared', 'public']) ? $visibility : 'private',
                        ]);
                    $this->getUser()->setFlash('success', 'Note updated');

                    // Auto-journal: annotation updated
                    try {
                        require_once sfConfig::get('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/JournalService.php';
                        $journal = new JournalService();
                        $journal->createAutoEntry(
                            $this->researcher->id,
                            'auto_annotation',
                            'Updated note: ' . ($title ?: 'Untitled'),
                            'Updated research note',
                            null,
                            'annotation',
                            $id
                        );
                    } catch (\Exception $e) { /* silent */ }
                }
                $this->redirect('research/annotations');
            }
        }
        
        // Build annotations query with optional filters
        $q = $request->getParameter('q');
        $visibility = $request->getParameter('visibility');
        $tag = $request->getParameter('tag');

        if ($q) {
            $this->annotations = $this->service->searchAnnotations($this->researcher->id, $q);
        } else {
            $this->annotations = $this->service->getAnnotations($this->researcher->id);
        }

        // Apply post-query filters
        if ($visibility) {
            $this->annotations = array_filter($this->annotations, function ($a) use ($visibility) {
                return ($a->visibility ?? 'private') === $visibility;
            });
        }
        if ($tag) {
            $this->annotations = array_filter($this->annotations, function ($a) use ($tag) {
                if (empty($a->tags)) return false;
                $tags = array_map('trim', explode(',', $a->tags));
                return in_array($tag, $tags);
            });
        }
        $this->annotations = array_values($this->annotations);

        $this->collections = DB::table('research_collection')
            ->where('researcher_id', $this->researcher->id)
            ->orderBy('name')
            ->get();
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
                // Send email notification
                $this->sendResearcherEmail('pending', [
                    'user_id' => $userId,
                    'first_name' => $request->getParameter('first_name'),
                    'last_name' => $request->getParameter('last_name'),
                    'email' => $email,
                    'institution' => $request->getParameter('institution'),
                ]);
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
            'source_culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
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
            if (class_exists('\\Illuminate\\Database\\Capsule\\Manager')) {
                DB::table('research_password_reset')->where('user_id', $reset->user_id)->delete();
            } else {
                $conn = \Propel::getConnection();
                $stmt = $conn->prepare('DELETE FROM research_password_reset WHERE user_id = ?');
                $stmt->execute([$reset->user_id]);
            }
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
        // Send approval email
        $this->sendResearcherEmail('approved', $researcher);
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
        if (class_exists('\\Illuminate\\Database\\Capsule\\Manager')) {
            DB::table('research_researcher')->where('id', $id)->delete();
        } else {
            $conn = \Propel::getConnection();
            $stmt = $conn->prepare('DELETE FROM research_researcher WHERE id = ?');
            $stmt->execute([$id]);
        }

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
        
        // Send rejection email
        $this->sendResearcherEmail('rejected', $researcher, $reason);
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
                     ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
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
     * AJAX: Search entities by type for Tom Select dropdown.
     */
    public function executeSearchEntities($request)
    {
        $this->getResponse()->setContentType('application/json');
        $query = trim($request->getParameter('q', ''));
        $type = $request->getParameter('type', 'information_object');
        if (strlen($query) < 2) {
            return $this->renderText(json_encode(['items' => []]));
        }

        $items = [];
        if ($type === 'actor') {
            $items = DB::table('actor_i18n as ai')
                ->leftJoin('slug as s', 'ai.id', '=', 's.object_id')
                ->where('ai.culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
                ->where('ai.authorized_form_of_name', 'LIKE', '%' . $query . '%')
                ->select('ai.id', 'ai.authorized_form_of_name as title', 's.slug')
                ->orderBy('ai.authorized_form_of_name')
                ->limit(20)->get()->toArray();
        } elseif ($type === 'repository') {
            $items = DB::table('repository_i18n as ri')
                ->leftJoin('slug as s', 'ri.id', '=', 's.object_id')
                ->where('ri.culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
                ->where('ri.authorized_form_of_name', 'LIKE', '%' . $query . '%')
                ->select('ri.id', 'ri.authorized_form_of_name as title', 's.slug')
                ->orderBy('ri.authorized_form_of_name')
                ->limit(20)->get()->toArray();
        } elseif ($type === 'researcher') {
            $items = DB::table('research_researcher')
                ->where('status', 'approved')
                ->where(function ($q) use ($query) {
                    $q->where('email', 'LIKE', '%' . $query . '%')
                      ->orWhere('first_name', 'LIKE', '%' . $query . '%')
                      ->orWhere('last_name', 'LIKE', '%' . $query . '%')
                      ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $query . '%']);
                })
                ->select('id', 'first_name', 'last_name', 'email', 'institution')
                ->orderBy('last_name')
                ->limit(20)->get()->map(function ($r) {
                    return (object) [
                        'id' => $r->id,
                        'title' => $r->first_name . ' ' . $r->last_name,
                        'email' => $r->email,
                        'institution' => $r->institution,
                        'slug' => null,
                    ];
                })->toArray();
        } elseif ($type === 'collection') {
            $userId = $this->getUser()->getAttribute('user_id');
            $researcher = $this->service->getResearcherByUserId((int) $userId);
            $researcherId = $researcher ? $researcher->id : 0;
            $items = DB::table('research_collection')
                ->where('researcher_id', $researcherId)
                ->where('name', 'LIKE', '%' . $query . '%')
                ->select('id', 'name as title', 'description')
                ->orderBy('name')
                ->limit(20)->get()->map(function ($c) {
                    return (object) ['id' => $c->id, 'title' => $c->title, 'slug' => null];
                })->toArray();
        } elseif ($type === 'saved_search') {
            $userId = $this->getUser()->getAttribute('user_id');
            $researcher = $this->service->getResearcherByUserId((int) $userId);
            $researcherId = $researcher ? $researcher->id : 0;
            $items = DB::table('research_saved_search')
                ->where('researcher_id', $researcherId)
                ->where('name', 'LIKE', '%' . $query . '%')
                ->select('id', 'name as title')
                ->orderBy('name')
                ->limit(20)->get()->map(function ($s) {
                    return (object) ['id' => $s->id, 'title' => $s->title, 'slug' => null];
                })->toArray();
        } elseif ($type === 'project') {
            $userId = $this->getUser()->getAttribute('user_id');
            $researcher = $this->service->getResearcherByUserId((int) $userId);
            $researcherId = $researcher ? $researcher->id : 0;
            $items = DB::table('research_project')
                ->where(function ($q) use ($researcherId) {
                    $q->where('researcher_id', $researcherId)
                      ->orWhereExists(function ($sub) use ($researcherId) {
                          $sub->select(DB::raw(1))
                              ->from('research_project_collaborator')
                              ->whereColumn('research_project_collaborator.project_id', 'research_project.id')
                              ->where('research_project_collaborator.researcher_id', $researcherId);
                      });
                })
                ->where('title', 'LIKE', '%' . $query . '%')
                ->select('id', 'title')
                ->orderBy('title')
                ->limit(20)->get()->map(function ($p) {
                    return (object) ['id' => $p->id, 'title' => $p->title, 'slug' => null];
                })->toArray();
        } elseif ($type === 'snapshot') {
            $userId = $this->getUser()->getAttribute('user_id');
            $researcher = $this->service->getResearcherByUserId((int) $userId);
            $researcherId = $researcher ? $researcher->id : 0;
            $items = DB::table('research_snapshot as rs')
                ->join('research_project as rp', 'rs.project_id', '=', 'rp.id')
                ->where('rp.researcher_id', $researcherId)
                ->where('rs.label', 'LIKE', '%' . $query . '%')
                ->select('rs.id', 'rs.label as title')
                ->orderBy('rs.label')
                ->limit(20)->get()->map(function ($s) {
                    return (object) ['id' => $s->id, 'title' => $s->title, 'slug' => null];
                })->toArray();
        } elseif ($type === 'annotation') {
            $userId = $this->getUser()->getAttribute('user_id');
            $researcher = $this->service->getResearcherByUserId((int) $userId);
            $researcherId = $researcher ? $researcher->id : 0;
            $items = DB::table('research_annotation')
                ->where('researcher_id', $researcherId)
                ->where(function ($q) use ($query) {
                    $q->where('body', 'LIKE', '%' . $query . '%')
                      ->orWhere('annotation_type', 'LIKE', '%' . $query . '%');
                })
                ->select('id', DB::raw("CONCAT(annotation_type, ': ', LEFT(body, 60)) as title"))
                ->orderByDesc('created_at')
                ->limit(20)->get()->map(function ($a) {
                    return (object) ['id' => $a->id, 'title' => $a->title, 'slug' => null];
                })->toArray();
        } elseif ($type === 'assertion') {
            $userId = $this->getUser()->getAttribute('user_id');
            $researcher = $this->service->getResearcherByUserId((int) $userId);
            $researcherId = $researcher ? $researcher->id : 0;
            $items = DB::table('research_assertion')
                ->where('researcher_id', $researcherId)
                ->where('statement', 'LIKE', '%' . $query . '%')
                ->select('id', DB::raw("LEFT(statement, 80) as title"))
                ->orderByDesc('created_at')
                ->limit(20)->get()->map(function ($a) {
                    return (object) ['id' => $a->id, 'title' => $a->title, 'slug' => null];
                })->toArray();
        } elseif ($type === 'bibliography') {
            $userId = $this->getUser()->getAttribute('user_id');
            $researcher = $this->service->getResearcherByUserId((int) $userId);
            $researcherId = $researcher ? $researcher->id : 0;
            $items = DB::table('research_bibliography')
                ->where('researcher_id', $researcherId)
                ->where('name', 'LIKE', '%' . $query . '%')
                ->select('id', 'name as title')
                ->orderBy('name')
                ->limit(20)->get()->map(function ($b) {
                    return (object) ['id' => $b->id, 'title' => $b->title, 'slug' => null];
                })->toArray();
        } elseif ($type === 'accession') {
            $items = DB::table('accession as a')
                ->leftJoin('accession_i18n as ai', function ($j) { $j->on('a.id', '=', 'ai.id')->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture()); })
                ->where(function ($q) use ($query) {
                    $q->where('a.identifier', 'LIKE', '%' . $query . '%')
                      ->orWhere('ai.title', 'LIKE', '%' . $query . '%');
                })
                ->select('a.id', 'a.identifier', 'ai.title')
                ->orderBy('a.identifier')
                ->limit(20)->get()->map(function ($i) {
                    return (object) ['id' => $i->id, 'title' => $i->title ?: $i->identifier, 'slug' => null];
                })->toArray();
        } else {
            // Default: information_object (use existing searchItems logic)
            $items = DB::table('information_object as io')
                ->leftJoin('information_object_i18n as ioi', function ($j) { $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture()); })
                ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
                ->where('io.id', '!=', 1)
                ->where(function ($q) use ($query) {
                    $q->where('ioi.title', 'LIKE', '%' . $query . '%')
                      ->orWhere('io.identifier', 'LIKE', '%' . $query . '%');
                })
                ->select('io.id', 'ioi.title', 'io.identifier', 's.slug')
                ->orderBy('ioi.title')->limit(20)->get()->toArray();
        }

        return $this->renderText(json_encode(['items' => array_values(array_map(function ($i) {
            $o = is_object($i) ? $i : (object) $i;
            $item = ['id' => $o->id ?? null, 'title' => $o->title ?? 'Untitled', 'slug' => $o->slug ?? null];
            if (isset($o->email)) { $item['email'] = $o->email; }
            if (isset($o->institution)) { $item['institution'] = $o->institution; }
            if (isset($o->identifier)) { $item['identifier'] = $o->identifier; }
            if (isset($o->description)) { $item['description'] = $o->description; }
            return $item;
        }, $items))]));
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

        $this->clipboardItems = DB::table('research_clipboard_project as cp')
            ->leftJoin('information_object_i18n as i', function ($join) {
                $join->on('cp.object_id', '=', 'i.id')->where('i.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('slug', function ($join) {
                $join->on('cp.object_id', '=', 'slug.object_id');
            })
            ->where('cp.project_id', $projectId)
            ->select('cp.*', 'i.title as object_title', 'slug.slug as object_slug')
            ->orderByDesc('cp.is_pinned')
            ->orderByDesc('cp.created_at')
            ->get()->toArray();

        // Handle POST actions
        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action');

            if ($action === 'add_resource') {
                try {
                    $projectService->addResource($projectId, [
                        'resource_type' => $request->getParameter('resource_type', 'external_link'),
                        'object_id' => $request->getParameter('object_id') ? (int) $request->getParameter('object_id') : null,
                        'external_url' => $request->getParameter('external_url'),
                        'title' => $request->getParameter('title'),
                        'description' => $request->getParameter('notes'),
                    ], (int) $this->researcher->id);
                    $this->getUser()->setFlash('success', 'Resource linked');
                } catch (\Exception $e) {
                    $this->getUser()->setFlash('error', 'Failed to add resource: ' . $e->getMessage());
                }
                $this->redirect('/research/project/' . $projectId);
            }

            if ($action === 'remove_resource') {
                try {
                    DB::table('research_project_resource')
                        ->where('id', (int) $request->getParameter('resource_id'))
                        ->where('project_id', $projectId)
                        ->delete();
                    $this->getUser()->setFlash('success', 'Resource removed');
                } catch (\Exception $e) {
                    $this->getUser()->setFlash('error', 'Failed to remove resource');
                }
                $this->redirect('/research/project/' . $projectId);
            }
        }
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
            try {
                $projectService->updateProject($projectId, [
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
                $this->getUser()->setFlash('success', 'Project updated');
                $this->redirect('/research/project/' . $projectId);
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Failed to update: ' . $e->getMessage());
                $this->redirect('/research/project/' . $projectId . '/edit');
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
            if (class_exists('\\Illuminate\\Database\\Capsule\\Manager')) {
                DB::table('research_project_collaborator')
                    ->where('id', $collaboratorId)
                    ->where('project_id', $projectId)
                    ->delete();
            } else {
                $conn = \Propel::getConnection();
                $stmt = $conn->prepare('DELETE FROM research_project_collaborator WHERE id = ? AND project_id = ?');
                $stmt->execute([$collaboratorId, $projectId]);
            }
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
            $researcherId = (int) $request->getParameter('researcher_id');
            $email = trim($request->getParameter('email'));
            $externalEmail = trim($request->getParameter('external_email', ''));
            $role = $request->getParameter('role', 'contributor');

            $invitedResearcher = null;

            // Try by researcher_id first (Tom Select selection)
            if ($researcherId) {
                $invitedResearcher = DB::table('research_researcher')
                    ->where('id', $researcherId)
                    ->first();
            }

            // Fallback to email lookup
            if (!$invitedResearcher && $email) {
                $invitedResearcher = DB::table('research_researcher')
                    ->where('email', $email)
                    ->first();
            }

            // Handle external invite (not a registered researcher)
            if (!$invitedResearcher && $externalEmail) {
                // Send external invitation email with link to register
                $this->sendCollaboratorInviteEmail(null, $externalEmail, $this->project, $this->researcher, $role, true);
                $this->getUser()->setFlash('success', 'Registration invitation sent to ' . $externalEmail . '. They must register as a researcher first.');
                $this->redirect('/research/project/' . $projectId);
                return;
            }

            if (!$invitedResearcher) {
                $this->getUser()->setFlash('error', 'No researcher found. Try inviting them via external email.');
            } else if ($invitedResearcher->id == $this->researcher->id) {
                $this->getUser()->setFlash('error', 'You cannot invite yourself');
            } else {
                $result = $projectService->inviteCollaborator($projectId, (int) $invitedResearcher->id, $role, (int) $this->researcher->id);

                if (isset($result['error'])) {
                    $this->getUser()->setFlash('error', $result['error']);
                } else {
                    // Send invitation email
                    $this->sendCollaboratorInviteEmail($invitedResearcher, $invitedResearcher->email, $this->project, $this->researcher, $role, false);
                    $this->getUser()->setFlash('success', 'Invitation sent to ' . $invitedResearcher->email);
                    $this->redirect('/research/project/' . $projectId);
                }
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
            try {
                $newId = $reproductionService->createRequest($this->researcher->id, [
                    'purpose' => $request->getParameter('purpose'),
                    'intended_use' => $request->getParameter('intended_use'),
                    'publication_details' => $request->getParameter('publication_details'),
                    'delivery_method' => $request->getParameter('delivery_method', 'digital'),
                    'urgency' => $request->getParameter('urgency', 'normal'),
                    'special_instructions' => $request->getParameter('special_instructions'),
                ]);
                $this->getUser()->setFlash('success', 'Reproduction request created');
                $this->redirect('/research/reproduction/' . $newId);
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Failed to create request: ' . $e->getMessage());
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
                try {
                    $reproductionService->addItem($requestId, [
                        'object_id' => $objectId,
                        'reproduction_type' => $request->getParameter('reproduction_type', 'digital_scan'),
                        'format' => $request->getParameter('format'),
                        'size' => $request->getParameter('size'),
                        'resolution' => $request->getParameter('resolution'),
                        'color_mode' => $request->getParameter('color_mode', 'color'),
                        'quantity' => (int) $request->getParameter('quantity', 1),
                        'special_instructions' => $request->getParameter('special_instructions'),
                    ]);
                    $this->getUser()->setFlash('success', 'Item added');
                } catch (\Exception $e) {
                    $this->getUser()->setFlash('error', 'Failed to add item: ' . $e->getMessage());
                }
                $this->redirect('/research/reproduction/' . $requestId);
            }

            if ($action === 'remove_item') {
                $itemId = (int) $request->getParameter('item_id');
                $reproductionService->removeItem($requestId, $itemId, $this->researcher->id);
                $this->getUser()->setFlash('success', 'Item removed');
                $this->redirect('/research/reproduction/' . $requestId);
            }

            if ($action === 'submit') {
                $result = $reproductionService->submitRequest($requestId, $this->researcher->id);
                if (isset($result['error'])) {
                    $this->getUser()->setFlash('error', $result['error']);
                } else {
                    $this->getUser()->setFlash('success', 'Request submitted');
                    // Send email to admin about reproduction request
                    try {
                        $baseUrl = \sfConfig::get('app_siteBaseUrl', '');
                        $this->sendTemplatedEmail(
                            $this->getAdminNotifyEmail(),
                            'reproduction_request',
                            [
                                'name' => 'Admin',
                                'researcher_name' => $this->researcher->first_name . ' ' . $this->researcher->last_name,
                                'reference_number' => $this->reproductionRequest->reference_number ?? 'N/A',
                                'purpose' => $this->reproductionRequest->purpose ?? '',
                                'delivery_method' => $this->reproductionRequest->delivery_method ?? '',
                                'review_url' => $baseUrl . '/index.php/research/reproduction/' . $requestId,
                            ],
                            'New Reproduction Request: ' . ($this->reproductionRequest->reference_number ?? ''),
                            "Dear {name},\n\nA new reproduction request has been submitted.\n\nResearcher: {researcher_name}\nReference: {reference_number}\nPurpose: {purpose}\nDelivery: {delivery_method}\n\nReview at: {review_url}\n\nBest regards,\nThe Archive Team"
                        );
                        // Also send confirmation to researcher
                        $this->sendTemplatedEmail(
                            $this->researcher->email,
                            'reproduction_confirmation',
                            [
                                'name' => $this->researcher->first_name . ' ' . $this->researcher->last_name,
                                'reference_number' => $this->reproductionRequest->reference_number ?? 'N/A',
                                'purpose' => $this->reproductionRequest->purpose ?? '',
                            ],
                            'Reproduction Request Received: ' . ($this->reproductionRequest->reference_number ?? ''),
                            "Dear {name},\n\nYour reproduction request ({reference_number}) has been received and is being reviewed.\n\nPurpose: {purpose}\n\nYou will be notified when it has been processed.\n\nBest regards,\nThe Archive Team"
                        );
                    } catch (\Exception $e) {
                        // Email sending is non-blocking
                    }
                }
                $this->redirect('/research/reproduction/' . $requestId);
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
                    try {
                        $bibliographyService->addEntryFromObject($bibliographyId, $objectId);
                        $this->getUser()->setFlash('success', 'Entry added');
                    } catch (\Exception $e) {
                        $this->getUser()->setFlash('error', $e->getMessage());
                    }
                } else {
                    $this->getUser()->setFlash('error', 'Please select an archive item');
                }
                $this->redirect('research/viewBibliography?id=' . $bibliographyId);
            }

            if ($action === 'remove_entry') {
                $entryId = (int) $request->getParameter('entry_id');
                if (class_exists('\\Illuminate\\Database\\Capsule\\Manager')) {
                    DB::table('research_bibliography_entry')
                        ->where('id', $entryId)
                        ->where('bibliography_id', $bibliographyId)
                        ->delete();
                } else {
                    $conn = \Propel::getConnection();
                    $stmt = $conn->prepare('DELETE FROM research_bibliography_entry WHERE id = ? AND bibliography_id = ?');
                    $stmt->execute([$entryId, $bibliographyId]);
                }
                $this->getUser()->setFlash('success', 'Entry removed');
                $this->redirect('research/viewBibliography?id=' . $bibliographyId);
            }

            if ($action === 'delete') {
                if (class_exists('\\Illuminate\\Database\\Capsule\\Manager')) {
                    DB::table('research_bibliography_entry')->where('bibliography_id', $bibliographyId)->delete();
                    DB::table('research_bibliography')->where('id', $bibliographyId)->delete();
                } else {
                    $conn = \Propel::getConnection();
                    $stmt = $conn->prepare('DELETE FROM research_bibliography_entry WHERE bibliography_id = ?');
                    $stmt->execute([$bibliographyId]);
                    $stmt = $conn->prepare('DELETE FROM research_bibliography WHERE id = ?');
                    $stmt->execute([$bibliographyId]);
                }
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

        $mimeTypes = [
            'bibtex' => 'application/x-bibtex',
            'ris' => 'application/x-research-info-systems',
            'zotero' => 'application/rdf+xml',
            'mendeley' => 'application/json',
            'csl' => 'application/json',
        ];
        $extensions = [
            'bibtex' => 'bib',
            'ris' => 'ris',
            'zotero' => 'rdf',
            'mendeley' => 'json',
            'csl' => 'json',
        ];

        try {
            $content = match ($format) {
                'bibtex' => $bibliographyService->exportBibTeX($bibliographyId),
                'zotero' => $bibliographyService->exportZoteroRDF($bibliographyId),
                'mendeley' => $bibliographyService->exportMendeleyJSON($bibliographyId),
                'csl' => $bibliographyService->exportCSLJSON($bibliographyId),
                default => $bibliographyService->exportRIS($bibliographyId),
            };
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Export failed: ' . $e->getMessage());
            $this->redirect('research/viewBibliography?id=' . $bibliographyId);
        }

        $mime = $mimeTypes[$format] ?? 'text/plain';
        $ext = $extensions[$format] ?? 'txt';
        $filename = 'bibliography-' . $bibliographyId . '.' . $ext;

        $this->getResponse()->setContentType($mime);
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $this->renderText($content);
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

                // Look up researcher by email
                $invitee = $this->service->getResearcherByEmail($email);
                if (!$invitee) {
                    $this->getUser()->setFlash('error', 'No registered researcher found with email: ' . $email);
                } else {
                    $result = $collaborationService->addMember($workspaceId, (int) $invitee->id, $role, (int) $this->researcher->id);
                    if (isset($result['error'])) {
                        $this->getUser()->setFlash('error', $result['error']);
                    } else {
                        $this->getUser()->setFlash('success', 'Member invited');
                    }
                }
                $this->redirect('research/viewWorkspace?id=' . $workspaceId);
            }

            if ($action === 'create_discussion') {
                try {
                    $collaborationService->createDiscussion([
                        'workspace_id' => $workspaceId,
                        'researcher_id' => (int) $this->researcher->id,
                        'subject' => $request->getParameter('title'),
                        'content' => $request->getParameter('content'),
                    ]);
                    $this->getUser()->setFlash('success', 'Discussion created');
                } catch (\Exception $e) {
                    $this->getUser()->setFlash('error', $e->getMessage());
                }
                $this->redirect('/research/workspaces/' . $workspaceId);
            }

            if ($action === 'add_resource') {
                try {
                    $collaborationService->addResource($workspaceId, [
                        'resource_type' => $request->getParameter('resource_type'),
                        'resource_id' => $request->getParameter('resource_id') ? (int) $request->getParameter('resource_id') : null,
                        'external_url' => $request->getParameter('external_url'),
                        'title' => $request->getParameter('title'),
                        'description' => $request->getParameter('notes'),
                    ], (int) $this->researcher->id);
                    $this->getUser()->setFlash('success', 'Resource added');
                } catch (\Exception $e) {
                    $this->getUser()->setFlash('error', $e->getMessage());
                }
                $this->redirect('/research/workspaces/' . $workspaceId);
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
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
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
            $action = $request->getParameter('form_action');
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
            $this->redirect('/research/journal/' . $id);
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
                $this->redirect('/research/journal/' . $entryId);
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
                $this->redirect('/research/report/' . $reportId);
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

        // Load potential reviewers (approved researchers excluding self)
        $this->collaborators = DB::table('research_researcher')
            ->where('status', 'approved')
            ->where('id', '!=', $this->researcher->id)
            ->orderBy('last_name')
            ->get()->toArray();

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
            $action = $request->getParameter('form_action');

            if ($action === 'update_header' || $action === 'update_status') {
                $reportService->updateReport($id, ['status' => $request->getParameter('status')]);
                $this->getUser()->setFlash('success', 'Status updated');
                $this->redirect('/research/report/' . $id);
            }

            if ($action === 'add_section') {
                $reportService->addSection($id, [
                    'section_type' => $request->getParameter('section_type', 'text'),
                    'title' => $request->getParameter('title'),
                ]);
                $this->getUser()->setFlash('success', 'Section added');
                $this->redirect('/research/report/' . $id);
            }

            if ($action === 'update_section') {
                $sectionId = (int) $request->getParameter('section_id');
                $content = $this->service->sanitizeHtml($request->getParameter('content', ''));
                $reportService->updateSection($sectionId, [
                    'title' => $request->getParameter('title'),
                    'content' => $content,
                    'content_format' => 'html',
                ]);
                $this->getUser()->setFlash('success', 'Section updated');
                $this->redirect('/research/report/' . $id);
            }

            if ($action === 'move_section') {
                $sectionId = (int) $request->getParameter('section_id');
                $direction = $request->getParameter('direction');
                $reportService->moveSection($sectionId, $direction);
                $this->redirect('/research/report/' . $id);
            }

            if ($action === 'delete_section') {
                $reportService->deleteSection((int) $request->getParameter('section_id'));
                $this->getUser()->setFlash('success', 'Section deleted');
                $this->redirect('/research/report/' . $id);
            }

            if ($action === 'load_template') {
                $templateCode = $request->getParameter('template_code');
                $template = DB::table('research_report_template')->where('code', $templateCode)->first();
                if ($template) {
                    $sectionsConfig = json_decode($template->sections_config, true) ?: [];
                    $maxOrder = DB::table('research_report_section')
                        ->where('report_id', $id)->max('sort_order') ?? -1;
                    $count = 0;
                    foreach ($sectionsConfig as $sectionDef) {
                        $parts = explode(':', $sectionDef, 2);
                        $type = $parts[0];
                        $title = $parts[1] ?? ucwords(str_replace('_', ' ', $type));
                        $reportService->addSection($id, [
                            'section_type' => $type,
                            'title' => $title,
                            'sort_order' => ++$maxOrder,
                        ]);
                        $count++;
                    }
                    $this->getUser()->setFlash('success', $count . ' sections loaded from template');
                }
                $this->redirect('/research/report/' . $id);
            }

            if ($action === 'add_multiple') {
                $types = $request->getParameter('section_types');
                if (is_array($types) && !empty($types)) {
                    $maxOrder = DB::table('research_report_section')
                        ->where('report_id', $id)->max('sort_order') ?? -1;
                    foreach ($types as $type) {
                        $reportService->addSection($id, [
                            'section_type' => $type,
                            'title' => ucwords(str_replace('_', ' ', $type)),
                            'sort_order' => ++$maxOrder,
                        ]);
                    }
                    $this->getUser()->setFlash('success', count($types) . ' sections added');
                }
                $this->redirect('/research/report/' . $id);
            }

            if ($action === 'delete_report') {
                $reportService->deleteReport($id, $this->researcher->id);
                $this->getUser()->setFlash('success', 'Report deleted');
                $this->redirect('research/reports');
            }

            if ($action === 'auto_populate' && $this->report->project_id) {
                $reportService->autoPopulateFromProject($id, $this->report->project_id);
                $this->getUser()->setFlash('success', 'Report populated from project data');
                $this->redirect('/research/report/' . $id);
            }

            if ($action === 'request_review') {
                $reviewerId = (int) $request->getParameter('reviewer_id');
                if ($reviewerId && $reviewerId !== $this->researcher->id) {
                    try {
                        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/PeerReviewService.php';
                        $reviewService = new \PeerReviewService();
                        $reviewService->requestReview($id, $this->researcher->id, $reviewerId);

                        // Send email notification using template
                        $reviewer = DB::table('research_researcher')->where('id', $reviewerId)->first();
                        if ($reviewer && $reviewer->email) {
                            $baseUrl = sfConfig::get('app_siteBaseUrl', '');
                            $this->sendTemplatedEmail($reviewer->email, 'peer_review_request', [
                                'name' => $reviewer->first_name . ' ' . $reviewer->last_name,
                                'requester_name' => $this->researcher->first_name . ' ' . $this->researcher->last_name,
                                'report_title' => $this->report->title ?? 'Report',
                                'review_url' => $baseUrl . '/index.php/research/report/' . $id,
                            ], 'Peer Review Request: ' . ($this->report->title ?? 'Report'),
                               "Dear {name},\n\n{requester_name} has requested your peer review of the report \"{report_title}\".\n\nReview at: {review_url}\n\nBest regards,\nThe Archive Team");
                        }

                        // Create notification
                        try {
                            $notifService = $this->loadNotificationService();
                            $notifService->createNotification(
                                $reviewerId, 'collaboration', 'Peer review requested',
                                $this->researcher->first_name . ' ' . $this->researcher->last_name . ' requested your review of "' . ($this->report->title ?? 'Report') . '"',
                                'research/report/' . $id, 'report', $id
                            );
                        } catch (\Exception $e) {}

                        $this->getUser()->setFlash('success', 'Review request sent');
                    } catch (\Exception $e) {
                        $this->getUser()->setFlash('error', 'Failed to request review: ' . $e->getMessage());
                    }
                }
                $this->redirect('/research/report/' . $id);
            }

            if ($action === 'add_comment') {
                $commentContent = trim($request->getParameter('comment_content', ''));
                if ($commentContent) {
                    try {
                        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/CommentService.php';
                        $cs = new \CommentService();
                        $cs->addComment($this->researcher->id, 'report_section', (int) $request->getParameter('section_id'), $commentContent);
                        $this->getUser()->setFlash('success', 'Comment added');
                    } catch (\Exception $e) {
                        $this->getUser()->setFlash('error', 'Failed to add comment');
                    }
                }
                $this->redirect('/research/report/' . $id);
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
        $this->redirect('/research/report/' . $id);
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
        $noteId = (int) $request->getParameter('id') ?: null;
        $noteIds = $request->getParameter('ids') ? array_filter(array_map('intval', explode(',', $request->getParameter('ids')))) : null;
        $exportService = $this->loadExportService();

        if ($format === 'csv') {
            $file = $exportService->exportNotesCsv($researcher->id, $noteId, $noteIds);
            $mime = 'text/csv';
            $ext = 'csv';
        } elseif ($format === 'docx') {
            $file = $exportService->exportNotesDocx($researcher->id, $noteId, $noteIds);
            $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            $ext = 'docx';
        } else {
            $file = $exportService->exportNotesPdf($researcher->id, $noteId, $noteIds);
            $mime = 'application/pdf';
            $ext = 'pdf';
        }

        if (!$file) {
            $this->getUser()->setFlash('error', 'Export failed');
            $this->redirect('research/annotations');
        }

        $filename = $noteId ? 'note-' . $noteId : ($noteIds ? 'notes-selected' : 'notes');
        $this->getResponse()->setContentType($mime);
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="' . $filename . '.' . $ext . '"');
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
                $this->redirect('/research/bibliography/' . $bibliographyId);
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
            $this->redirect('/research/bibliography/' . $bibliographyId);
        }

        $this->redirect('/research/bibliography/' . $bibliographyId);
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
            $this->redirect('/research/admin/institutions');
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
            $this->redirect('/research/project/' . $projectId . '/share');
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
                $this->redirect('/research/project/' . $share->project_id);
            }
        }

        // For unauthenticated users, redirect to external access
        $this->redirect('/research/share/' . $token);
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
            $this->redirect('/research/share/' . $token . '?access_token=' . $collab->access_token);
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
        $this->redirect('/research/report/' . $reportId);
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
        $this->redirect('/research/report/' . $reportId);
    }

    /**
     * AJAX: Add AtoM clipboard items to a project.
     */
    public function executeClipboardToProject($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) {
            return $this->renderText(json_encode(['error' => 'Not a researcher']));
        }

        $projectId = (int) $request->getParameter('project_id');
        $slugsRaw = $request->getParameter('slugs', '');
        $notes = trim($request->getParameter('notes', ''));

        if (!$projectId || !$slugsRaw) {
            return $this->renderText(json_encode(['error' => 'Missing project_id or slugs']));
        }

        // Resolve slugs to object IDs
        $slugs = is_array($slugsRaw) ? $slugsRaw : array_filter(array_map('trim', explode(',', $slugsRaw)));
        $objectIds = [];
        if (!empty($slugs)) {
            $objectIds = DB::table('slug')
                ->whereIn('slug', $slugs)
                ->pluck('object_id')
                ->toArray();
        }

        if (empty($objectIds)) {
            return $this->renderText(json_encode(['error' => 'No valid items found']));
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ProjectService.php';
        $projectService = new ProjectService();

        // Verify access
        if (!$projectService->canAccess($projectId, $researcher->id, 'contributor')) {
            return $this->renderText(json_encode(['error' => 'Access denied']));
        }

        $added = $projectService->addClipboardItems($projectId, $researcher->id, $objectIds, $notes ?: null);

        return $this->renderText(json_encode([
            'success' => true,
            'added' => $added,
            'total_slugs' => count($slugs),
            'message' => $added . ' item(s) added to project',
        ]));
    }

    /**
     * AJAX: Manage clipboard items in a project (pin, remove, update notes).
     */
    public function executeManageClipboardItem($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) {
            return $this->renderText(json_encode(['error' => 'Not a researcher']));
        }

        $itemId = (int) $request->getParameter('item_id');
        $action = $request->getParameter('do', '');

        if (!$itemId) {
            return $this->renderText(json_encode(['error' => 'Missing item_id']));
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ProjectService.php';
        $projectService = new ProjectService();

        if ($action === 'pin') {
            $projectService->toggleClipboardPin($itemId, $researcher->id);
            return $this->renderText(json_encode(['success' => true, 'message' => 'Pin toggled']));
        } elseif ($action === 'remove') {
            $projectService->removeClipboardItem($itemId, $researcher->id);
            return $this->renderText(json_encode(['success' => true, 'message' => 'Item removed']));
        } elseif ($action === 'notes') {
            $notes = trim($request->getParameter('notes', ''));
            $projectService->updateClipboardNotes($itemId, $researcher->id, $notes ?: null);
            return $this->renderText(json_encode(['success' => true, 'message' => 'Notes updated']));
        }

        return $this->renderText(json_encode(['error' => 'Invalid action']));
    }

    /**
     * AJAX: Manage milestones (add/complete/delete).
     */
    public function executeManageMilestone($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) {
            return $this->renderText(json_encode(['error' => 'Not a researcher']));
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ProjectService.php';
        $projectService = new ProjectService();

        $action = $request->getParameter('do', '');

        if ($action === 'add') {
            $projectId = (int) $request->getParameter('project_id');
            $project = $projectService->getProject($projectId, $researcher->id);
            if (!$project || $project->owner_id != $researcher->id) {
                return $this->renderText(json_encode(['error' => 'Not authorized']));
            }
            $title = trim($request->getParameter('title', ''));
            if (!$title) {
                return $this->renderText(json_encode(['error' => 'Title is required']));
            }
            $id = $projectService->addMilestone($projectId, [
                'title' => $title,
                'description' => trim($request->getParameter('description', '')),
                'due_date' => $request->getParameter('due_date') ?: null,
                'status' => $request->getParameter('status', 'pending'),
            ]);
            return $this->renderText(json_encode(['success' => true, 'id' => $id, 'message' => 'Milestone added']));
        } elseif ($action === 'complete') {
            $milestoneId = (int) $request->getParameter('milestone_id');
            $projectService->completeMilestone($milestoneId, $researcher->id);
            return $this->renderText(json_encode(['success' => true, 'message' => 'Milestone completed']));
        } elseif ($action === 'delete') {
            $milestoneId = (int) $request->getParameter('milestone_id');
            $projectService->deleteMilestone($milestoneId);
            return $this->renderText(json_encode(['success' => true, 'message' => 'Milestone deleted']));
        }

        return $this->renderText(json_encode(['error' => 'Invalid action']));
    }

    /**
     * AJAX: Remove a collaborator from a project.
     */
    public function executeRemoveCollaborator($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) {
            return $this->renderText(json_encode(['error' => 'Not a researcher']));
        }

        $projectId = (int) $request->getParameter('project_id');
        $researcherId = (int) $request->getParameter('researcher_id');

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ProjectService.php';
        $projectService = new ProjectService();
        $project = $projectService->getProject($projectId, $researcher->id);

        if (!$project || $project->owner_id != $researcher->id) {
            return $this->renderText(json_encode(['error' => 'Not authorized']));
        }

        $projectService->removeCollaborator($projectId, $researcherId);
        return $this->renderText(json_encode(['success' => true, 'message' => 'Collaborator removed']));
    }

    /**
     * AJAX: Upload an image for a note (Quill editor).
     */
    public function executeUploadNoteImage($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) {
            return $this->renderText(json_encode(['error' => 'Not a researcher']));
        }

        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            return $this->renderText(json_encode(['error' => 'No file uploaded']));
        }

        $file = $_FILES['image'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            return $this->renderText(json_encode(['error' => 'File too large (max 5MB)']));
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            return $this->renderText(json_encode(['error' => 'Invalid file type. Allowed: JPEG, PNG, GIF, WebP']));
        }

        $uploadDir = sfConfig::get('sf_root_dir') . '/uploads/research/notes';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpg',
        };
        $filename = 'note_' . $researcher->id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

        if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
            return $this->renderText(json_encode(['error' => 'Failed to save file']));
        }

        return $this->renderText(json_encode([
            'url' => '/uploads/research/notes/' . $filename,
        ]));
    }

    /**
     * AJAX: Resolve an internal AtoM URL or search query to a record with thumbnail.
     * Used by TipTap "Embed Record" button to insert linked thumbnails into journal entries.
     *
     * GET params:
     *   q     - search query (title/identifier) or slug
     *   slug  - direct slug lookup
     *
     * Returns JSON: { results: [{ id, title, slug, url, thumbnailUrl, identifier }] }
     */
    public function executeResolveThumbnail($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }

        $slug = trim($request->getParameter('slug', ''));
        $query = trim($request->getParameter('q', ''));

        // Extract slug from a pasted URL (e.g., /index.php/some-slug or https://host/index.php/some-slug)
        if ($slug && preg_match('#(?:/index\.php)?/([a-z0-9][a-z0-9\-]+)$#i', $slug, $m)) {
            $slug = $m[1];
        }

        $culture = 'en';
        try {
            $culture = \sfContext::getInstance()->getUser()->getCulture() ?: 'en';
        } catch (\Exception $e) {
        }

        $results = [];

        if ($slug) {
            // Direct slug lookup
            $row = DB::table('slug as s')
                ->join('information_object_i18n as ioi', function ($j) use ($culture) {
                    $j->on('s.object_id', '=', 'ioi.id')->where('ioi.culture', '=', $culture);
                })
                ->leftJoin('information_object as io', 'io.id', '=', 's.object_id')
                ->where('s.slug', $slug)
                ->select('s.object_id as id', 'ioi.title', 's.slug', 'io.identifier')
                ->first();

            if ($row) {
                $row->thumbnailUrl = $this->resolveThumbnailUrl($row->id);
                $row->url = '/index.php/' . $row->slug;
                $results[] = $row;
            }
        } elseif ($query && strlen($query) >= 2) {
            // Search by title or identifier
            $rows = DB::table('information_object as io')
                ->leftJoin('information_object_i18n as ioi', function ($j) use ($culture) {
                    $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', $culture);
                })
                ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
                ->where('io.id', '!=', 1)
                ->where(function ($q) use ($query) {
                    $q->where('ioi.title', 'LIKE', '%' . $query . '%')
                      ->orWhere('io.identifier', 'LIKE', '%' . $query . '%');
                })
                ->select('io.id', 'ioi.title', 's.slug', 'io.identifier')
                ->orderBy('ioi.title')
                ->limit(12)
                ->get();

            foreach ($rows as $row) {
                $row->thumbnailUrl = $this->resolveThumbnailUrl($row->id);
                $row->url = '/index.php/' . $row->slug;
                $results[] = $row;
            }
        }

        return $this->renderText(json_encode(['results' => $results]));
    }

    /**
     * Resolve the thumbnail URL for an information object.
     */
    private function resolveThumbnailUrl(int $objectId): ?string
    {
        $thumb = DB::table('digital_object as m')
            ->join('digital_object as t', function ($j) {
                $j->on('t.parent_id', '=', 'm.id')->where('t.usage_id', '=', 142);
            })
            ->where('m.object_id', $objectId)
            ->where('m.usage_id', 140)
            ->select(DB::raw("CONCAT(t.path, t.name) as thumb_url"))
            ->first();

        return $thumb ? $thumb->thumb_url : null;
    }

    // =========================================================================
    // EMAIL NOTIFICATION HELPERS
    // =========================================================================

    /**
     * Send researcher-related email notification via core EmailService
     */
    protected function sendResearcherEmail($type, $researcher, $reason = '')
    {
        if (!$this->isResearchEmailEnabled() || !$this->loadEmailService()) {
            return;
        }
        try {
            // Convert array to object if needed (registration passes array)
            if (is_array($researcher)) {
                $researcher = (object) $researcher;
                if (empty($researcher->id) && !empty($researcher->user_id)) {
                    $dbResearcher = DB::table('research_researcher')
                        ->where('user_id', $researcher->user_id)
                        ->first();
                    if ($dbResearcher) {
                        $researcher->id = $dbResearcher->id;
                    }
                }
            }

            $email = $researcher->email ?? null;
            if (!$email) {
                return;
            }

            $baseUrl = \sfConfig::get('app_siteBaseUrl', '');
            $name = ($researcher->first_name ?? '') . ' ' . ($researcher->last_name ?? '');
            $placeholders = [
                'name' => trim($name),
                'email' => $email,
                'login_url' => $baseUrl . '/index.php/user/login',
                'reason' => $reason,
            ];

            switch ($type) {
                case 'pending':
                    $this->sendTemplatedEmail($email, 'researcher_pending', $placeholders,
                        'Registration Received',
                        "Dear {name},\n\nThank you for registering as a researcher. Your application is being reviewed.\n\nYou will be notified once approved.\n\nBest regards,\nThe Archive Team");
                    // Also notify admin
                    $this->sendTemplatedEmail($this->getAdminNotifyEmail(), 'admin_new_researcher', $placeholders,
                        'New Researcher Registration: ' . trim($name),
                        "Dear Admin,\n\nA new researcher has registered:\n\nName: {name}\nEmail: {email}\n\nPlease review and approve/reject.\n\nBest regards,\nThe Archive Team");
                    break;
                case 'approved':
                    $placeholders['login_url'] = $baseUrl . '/index.php/user/login';
                    $this->sendTemplatedEmail($email, 'researcher_approved', $placeholders,
                        'Registration Approved',
                        "Dear {name},\n\nYour researcher registration has been approved.\n\nYou can now log in and access the research workspace:\n{login_url}\n\nBest regards,\nThe Archive Team");
                    break;
                case 'rejected':
                    $this->sendTemplatedEmail($email, 'researcher_rejected', $placeholders,
                        'Registration Update',
                        "Dear {name},\n\nYour researcher registration could not be approved at this time.\n\nReason: {reason}\n\nPlease contact us if you have questions.\n\nBest regards,\nThe Archive Team");
                    break;
            }
        } catch (\Exception $e) {
            error_log('Research email notification failed: ' . $e->getMessage());
        }
    }

    /**
     * Check if research email notifications are enabled.
     */
    protected function isResearchEmailEnabled(): bool
    {
        try {
            $enabled = DB::table('ahg_settings')
                ->where('setting_key', 'research_email_notifications')
                ->value('setting_value');
            // Default to enabled if not set
            if ($enabled === null) {
                return true;
            }
            return $enabled !== 'false' && $enabled !== '0';
        } catch (\Exception $e) {
            return true; // default enabled
        }
    }

    /**
     * Ensure EmailService is loaded and enabled.
     */
    protected function loadEmailService(): bool
    {
        $emailServicePath = \sfConfig::get('sf_plugins_dir', '')
            . '/ahgCorePlugin/lib/Services/EmailService.php';
        if (!class_exists('AhgCore\Services\EmailService') && file_exists($emailServicePath)) {
            require_once $emailServicePath;
        }
        return class_exists('AhgCore\Services\EmailService') && \AhgCore\Services\EmailService::isEnabled();
    }

    /**
     * Send booking-related email notification using template from settings.
     */
    protected function sendBookingEmail($booking, $status)
    {
        if (!$this->isResearchEmailEnabled() || !$this->loadEmailService()) {
            return;
        }
        try {
            $researcher = DB::table('research_researcher')
                ->where('id', $booking->researcher_id)
                ->first();
            if (!$researcher || empty($researcher->email)) {
                return;
            }

            $placeholders = [
                'name' => $researcher->first_name . ' ' . $researcher->last_name,
                'date' => $booking->booking_date,
                'time' => substr($booking->start_time, 0, 5) . ' - ' . substr($booking->end_time, 0, 5),
                'room' => $booking->room_name ?? 'Reading Room',
            ];

            if ($status === 'confirmed') {
                $this->sendTemplatedEmail($researcher->email, 'booking_confirmed', $placeholders,
                    'Booking Confirmed',
                    "Dear {name},\n\nYour reading room booking has been confirmed.\n\nDate: {date}\nTime: {time}\nRoom: {room}\n\nBest regards,\nThe Archive Team");
            } elseif ($status === 'cancelled') {
                $this->sendTemplatedEmail($researcher->email, 'booking_cancelled', $placeholders,
                    'Booking Cancelled',
                    "Dear {name},\n\nYour reading room booking for {date} has been cancelled.\n\nIf you have questions, please contact us.\n\nBest regards,\nThe Archive Team");
            }
        } catch (\Exception $e) {
            error_log('Research booking email failed: ' . $e->getMessage());
        }
    }

    /**
     * Send collaborator invitation email using template from settings.
     */
    protected function sendCollaboratorInviteEmail($invitedResearcher, $email, $project, $inviter, $role, $isExternal = false)
    {
        if (!$this->isResearchEmailEnabled() || !$this->loadEmailService()) {
            return;
        }
        try {
            $baseUrl = \sfConfig::get('app_siteBaseUrl', '');
            $recipientName = $invitedResearcher
                ? $invitedResearcher->first_name . ' ' . $invitedResearcher->last_name
                : 'Colleague';

            $placeholders = [
                'name' => $recipientName,
                'inviter_name' => $inviter->first_name . ' ' . $inviter->last_name,
                'project_title' => $project->title,
                'role' => ucfirst($role),
                'project_url' => $baseUrl . '/index.php/research/project/' . $project->id,
                'register_url' => $baseUrl . '/index.php/research/register',
            ];

            $templateKey = $isExternal ? 'collaborator_external' : 'collaborator_invite';
            $fallbackSubject = 'You have been invited to collaborate on a research project';
            $fallbackBody = $isExternal
                ? "Dear Colleague,\n\n{inviter_name} has invited you to collaborate on the research project \"{project_title}\" as a {role}.\n\nTo accept this invitation, you first need to register as a researcher:\n{register_url}\n\nAfter registration and approval, you will be able to join the project.\n\nBest regards,\nThe Archive Team"
                : "Dear {name},\n\n{inviter_name} has invited you to collaborate on the research project \"{project_title}\" as a {role}.\n\nView the project and accept the invitation:\n{project_url}\n\nBest regards,\nThe Archive Team";

            $this->sendTemplatedEmail($email, $templateKey, $placeholders, $fallbackSubject, $fallbackBody);
        } catch (\Exception $e) {
            error_log('Collaborator invite email failed: ' . $e->getMessage());
        }
    }

    /**
     * Send a templated email using template from email_setting table.
     * Checks research_email_notifications toggle first.
     * Hardcoded fallback subject/body used ONLY if no template found in DB.
     */
    protected function sendTemplatedEmail($toEmail, $templateKey, array $placeholders, $fallbackSubject = '', $fallbackBody = '')
    {
        if (!$this->isResearchEmailEnabled() || !$this->loadEmailService()) {
            return false;
        }
        try {
            $subject = \AhgCore\Services\EmailService::getSetting('email_' . $templateKey . '_subject', $fallbackSubject);
            $body = \AhgCore\Services\EmailService::getSetting('email_' . $templateKey . '_body', $fallbackBody);
            $body = \AhgCore\Services\EmailService::parseTemplate($body, $placeholders);

            return \AhgCore\Services\EmailService::send($toEmail, $subject, $body);
        } catch (\Exception $e) {
            error_log('Research templated email failed (' . $templateKey . '): ' . $e->getMessage());
            return false;
        }
    }

    protected function getAdminNotifyEmail(): string
    {
        try {
            return DB::table('email_setting')
                ->where('setting_key', 'notify_admin_email')
                ->value('setting_value') ?: \sfConfig::get('app_siteAdminEmail', 'admin@theahg.co.za');
        } catch (\Exception $e) {
            return \sfConfig::get('app_siteAdminEmail', 'admin@theahg.co.za');
        }
    }

    // =========================================================================
    // ISSUE 159 PHASE 2a: SNAPSHOTS
    // =========================================================================

    protected function loadSnapshotService(): \SnapshotService
    {
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/SnapshotService.php';
        return new \SnapshotService();
    }

    public function executeSnapshots($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $projectId = (int) $request->getParameter('project_id');
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ProjectService.php';
        $projectService = new \ProjectService();
        $this->project = $projectService->getProject($projectId, $this->researcher->id);
        if (!$this->project) { $this->forward404('Project not found'); }

        $snapshotService = $this->loadSnapshotService();
        $this->snapshots = $snapshotService->getProjectSnapshots($projectId);
    }

    public function executeCreateSnapshot($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) { $this->redirect('research/register'); }

        $snapshotService = $this->loadSnapshotService();
        $projectId = (int) $request->getParameter('project_id');

        if ($request->isMethod('post')) {
            try {
                $collectionId = $request->getParameter('collection_id');
                if ($collectionId) {
                    $id = $snapshotService->freezeCollectionAsSnapshot($projectId, (int) $collectionId, $researcher->id);
                } else {
                    $id = $snapshotService->createSnapshot($projectId, $researcher->id, [
                        'title' => $request->getParameter('title'),
                        'description' => $request->getParameter('description'),
                    ]);
                }
                $this->getUser()->setFlash('success', 'Snapshot created');
                $this->redirect('/research/snapshot/' . $id);
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', $e->getMessage());
            }
        }
        $this->redirect('/research/snapshots/' . $projectId);
    }

    public function executeViewSnapshot($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $snapshotService = $this->loadSnapshotService();
        $id = (int) $request->getParameter('id');
        $this->snapshot = $snapshotService->getSnapshot($id);
        if (!$this->snapshot) { $this->forward404('Snapshot not found'); }

        $page = (int) $request->getParameter('page', 1);
        $this->items = $snapshotService->getSnapshotItems($id, $page, 50);
    }

    public function executeCompareSnapshots($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $snapshotService = $this->loadSnapshotService();
        $snapshotA = (int) $request->getParameter('snapshot_a');
        $snapshotB = (int) $request->getParameter('snapshot_b');

        $this->snapshotA = $snapshotService->getSnapshot($snapshotA);
        $this->snapshotB = $snapshotService->getSnapshot($snapshotB);
        if (!$this->snapshotA || !$this->snapshotB) { $this->forward404('Snapshot not found'); }

        $this->diff = $snapshotService->compareSnapshots($snapshotA, $snapshotB);
    }

    public function executeDeleteSnapshot($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $snapshotService = $this->loadSnapshotService();
        $id = (int) $request->getParameter('id');
        $snapshot = $snapshotService->getSnapshot($id);
        if (!$snapshot) { $this->forward404('Snapshot not found'); }

        if ($request->isMethod('post')) {
            $snapshotService->deleteSnapshot($id);
            $this->getUser()->setFlash('success', 'Snapshot deleted');
            $this->redirect('/research/snapshots/' . $snapshot->project_id);
        }
        $this->redirect('/research/snapshot/' . $id);
    }

    // =========================================================================
    // ISSUE 159 PHASE 2a: HYPOTHESES
    // =========================================================================

    protected function loadHypothesisService(): \HypothesisService
    {
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/HypothesisService.php';
        return new \HypothesisService();
    }

    public function executeHypotheses($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $projectId = (int) $request->getParameter('project_id');
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ProjectService.php';
        $projectService = new \ProjectService();
        $this->project = $projectService->getProject($projectId, $this->researcher->id);
        if (!$this->project) { $this->forward404('Project not found'); }

        $hypothesisService = $this->loadHypothesisService();
        $this->hypotheses = $hypothesisService->getProjectHypotheses($projectId);

        if ($request->isMethod('post') && $request->getParameter('form_action') === 'create') {
            try {
                $id = $hypothesisService->createHypothesis($projectId, $this->researcher->id, [
                    'statement' => $request->getParameter('statement'),
                    'tags' => $request->getParameter('tags'),
                ]);
                $this->getUser()->setFlash('success', 'Hypothesis created');
                $this->redirect('/research/hypothesis/' . $id);
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', $e->getMessage());
            }
        }
    }

    public function executeViewHypothesis($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $hypothesisService = $this->loadHypothesisService();
        $id = (int) $request->getParameter('id');
        $this->hypothesis = $hypothesisService->getHypothesis($id);
        if (!$this->hypothesis) { $this->forward404('Hypothesis not found'); }
    }

    public function executeUpdateHypothesis($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $hypothesisService = $this->loadHypothesisService();
        $id = (int) $request->getParameter('id');

        if ($request->isMethod('post')) {
            $formAction = $request->getParameter('form_action');
            try {
                if ($formAction === 'update_status') {
                    $hypothesisService->updateStatus($id, $request->getParameter('status'));
                    $this->getUser()->setFlash('success', 'Status updated');
                } elseif ($formAction === 'add_evidence') {
                    $hypothesisService->addEvidence($id, [
                        'source_type' => $request->getParameter('source_type'),
                        'source_id' => (int) $request->getParameter('source_id'),
                        'relationship' => $request->getParameter('relationship'),
                        'confidence' => $request->getParameter('confidence'),
                        'note' => $request->getParameter('note'),
                        'added_by' => (int) $this->service->getResearcherByUserId($this->getUser()->getAttribute('user_id'))->id,
                    ]);
                    $this->getUser()->setFlash('success', 'Evidence added');
                } elseif ($formAction === 'remove_evidence') {
                    $hypothesisService->removeEvidence((int) $request->getParameter('evidence_id'));
                    $this->getUser()->setFlash('success', 'Evidence removed');
                } elseif ($formAction === 'delete') {
                    $hypothesis = $hypothesisService->getHypothesis($id);
                    $projectId = $hypothesis->project_id ?? null;
                    $hypothesisService->deleteHypothesis($id);
                    $this->getUser()->setFlash('success', 'Hypothesis deleted');
                    if ($projectId) {
                        $this->redirect('/research/hypotheses/' . $projectId);
                    }
                    $this->redirect('/research/projects');
                } else {
                    $hypothesisService->updateHypothesis($id, [
                        'statement' => $request->getParameter('statement'),
                        'tags' => $request->getParameter('tags'),
                    ]);
                    $this->getUser()->setFlash('success', 'Hypothesis updated');
                }
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', $e->getMessage());
            }
        }
        $this->redirect('/research/hypothesis/' . $id);
    }

    // =========================================================================
    // ISSUE 159 PHASE 2a: SOURCE ASSESSMENT & TRUST SCORING
    // =========================================================================

    protected function loadTrustScoringService(): \TrustScoringService
    {
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/TrustScoringService.php';
        return new \TrustScoringService();
    }

    public function executeSourceAssessment($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $objectId = (int) $request->getParameter('object_id');
        $trustService = $this->loadTrustScoringService();
        $this->assessment = $trustService->getAssessment($objectId, $this->researcher->id);
        $this->history = $trustService->getAssessmentHistory($objectId);
        $this->metrics = $trustService->getQualityMetrics($objectId);
        $this->objectId = $objectId;

        // Get object title for display
        $this->objectTitle = DB::table('information_object_i18n')
            ->where('id', $objectId)->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
            ->value('title') ?? 'Object #' . $objectId;
    }

    public function executeSaveSourceAssessment($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) { $this->redirect('research/register'); }

        $objectId = (int) $request->getParameter('object_id');
        $trustService = $this->loadTrustScoringService();

        if ($request->isMethod('post')) {
            try {
                $trustService->assessSource($objectId, $researcher->id, [
                    'source_type' => $request->getParameter('source_type'),
                    'source_form' => $request->getParameter('source_form'),
                    'completeness' => $request->getParameter('completeness'),
                    'rationale' => $request->getParameter('rationale'),
                    'bias_context' => $request->getParameter('bias_context'),
                ]);
                $this->getUser()->setFlash('success', 'Assessment saved');
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', $e->getMessage());
            }
        }
        $this->redirect('/research/source-assessment/' . $objectId);
    }

    public function executeTrustScore($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            if ($request->getParameter('format') === 'json') {
                $this->getResponse()->setContentType('application/json');
                return $this->renderText(json_encode(['error' => 'Not authenticated']));
            }
            $this->redirect('user/login');
        }

        $objectId = (int) $request->getParameter('object_id');
        $trustService = $this->loadTrustScoringService();
        $score = $trustService->computeTrustScore($objectId);

        // JSON mode
        if ($request->getParameter('format') === 'json') {
            $this->getResponse()->setContentType('application/json');
            return $this->renderText(json_encode(['object_id' => $objectId, 'trust_score' => $score]));
        }

        // HTML mode
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $this->objectId = $objectId;
        $this->score = $score;
        $this->assessment = $trustService->getAssessment($objectId);
        $this->assessmentHistory = $trustService->getAssessmentHistory($objectId);
        $this->qualityMetrics = $trustService->getQualityMetrics($objectId);

        // Get object info for display
        $this->objectInfo = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function ($j) {
                $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'io.id')
            ->where('io.id', $objectId)
            ->select('io.id', 'i18n.title', 's.slug', 'io.identifier')
            ->first();

        $this->sidebarActive = $this->getSidebarActiveKey();
    }

    // =========================================================================
    // ISSUE 159 PHASE 2a: W3C WEB ANNOTATIONS v2
    // =========================================================================

    protected function loadWebAnnotationService(): \WebAnnotationService
    {
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/WebAnnotationService.php';
        return new \WebAnnotationService();
    }

    public function executeAnnotationStudio($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $objectId = (int) $request->getParameter('object_id');
        $annotationService = $this->loadWebAnnotationService();
        $this->annotations = $annotationService->getObjectAnnotations($objectId, null, [
            'researcher_id' => $this->researcher->id,
        ]);
        $this->objectId = $objectId;

        // Get object title
        $this->objectTitle = DB::table('information_object_i18n')
            ->where('id', $objectId)->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
            ->value('title') ?? 'Object #' . $objectId;

        // Load digital object image URL for the canvas
        $this->imageUrl = null;
        $do = DB::table('digital_object')->where('object_id', $objectId)->first();
        if ($do) {
            // Try reference image (usage_id=141) first, then master (usage_id=140)
            $ref = DB::table('digital_object')
                ->where('parent_id', $do->id)
                ->where('usage_id', 141)
                ->first();
            if ($ref) {
                $this->imageUrl = '/' . ltrim($ref->path, '/') . $ref->name;
            } else {
                // Fall back to master digital object
                $this->imageUrl = '/' . ltrim($do->path, '/') . $do->name;
            }
        }

        // Slug for "View Full Record" link
        $this->objectSlug = DB::table('slug')->where('object_id', $objectId)->value('slug');
    }

    public function executeCreateAnnotationV2($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) {
            return $this->renderText(json_encode(['error' => 'Not a researcher']));
        }

        $annotationService = $this->loadWebAnnotationService();

        if ($request->isMethod('post')) {
            try {
                $body = json_decode($request->getContent(), true) ?: [];

                // Delete annotation
                if (!empty($body['delete_annotation'])) {
                    $annId = (int) ($body['annotation_id'] ?? 0);
                    DB::table('research_annotation_target')->where('annotation_id', $annId)->delete();
                    DB::table('research_annotation_v2')->where('id', $annId)->delete();
                    return $this->renderText(json_encode(['success' => true]));
                }

                // Update annotation
                if (!empty($body['update_annotation'])) {
                    $annId = (int) ($body['annotation_id'] ?? 0);
                    $update = [];
                    if (isset($body['body'])) {
                        $update['body_json'] = json_encode($body['body']);
                    }
                    if (isset($body['motivation'])) {
                        $update['motivation'] = $body['motivation'];
                    }
                    if (!empty($update)) {
                        $update['updated_at'] = date('Y-m-d H:i:s');
                        DB::table('research_annotation_v2')->where('id', $annId)->update($update);
                    }
                    return $this->renderText(json_encode(['success' => true]));
                }

                // Delete target
                if (!empty($body['delete_target'])) {
                    $targetId = (int) ($body['target_id'] ?? 0);
                    DB::table('research_annotation_target')->where('id', $targetId)->delete();
                    return $this->renderText(json_encode(['success' => true]));
                }

                // Add target to existing annotation
                if (!empty($body['add_target'])) {
                    $annId = (int) ($body['annotation_id'] ?? 0);
                    DB::table('research_annotation_target')->insert([
                        'annotation_id' => $annId,
                        'source_type' => $body['source_type'] ?? 'information_object',
                        'source_id' => (int) ($body['source_id'] ?? 0),
                        'selector_type' => $body['selector_type'] ?? null,
                        'selector_json' => isset($body['selector_json']) ? json_encode($body['selector_json']) : null,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                    return $this->renderText(json_encode(['success' => true]));
                }

                // Create annotation
                $id = $annotationService->createAnnotation($researcher->id, array_merge($body, [
                    'project_id' => $request->getParameter('project_id') ?: ($body['project_id'] ?? null),
                ]));
                return $this->renderText(json_encode(['success' => true, 'id' => $id]));
            } catch (\Exception $e) {
                return $this->renderText(json_encode(['error' => $e->getMessage()]));
            }
        }
        return $this->renderText(json_encode(['error' => 'POST required']));
    }

    public function executeViewAnnotationV2($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }
        $annotationService = $this->loadWebAnnotationService();
        $id = (int) $request->getParameter('id');
        $annotation = $annotationService->getAnnotation($id);
        if (!$annotation) {
            return $this->renderText(json_encode(['error' => 'Not found']));
        }
        return $this->renderText(json_encode(['annotation' => $annotation]));
    }

    public function executeExportAnnotationsIIIF($request)
    {
        $this->getResponse()->setContentType('application/ld+json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }
        $annotationService = $this->loadWebAnnotationService();
        $objectId = (int) $request->getParameter('object_id');
        $annotationList = $annotationService->exportAsIIIFAnnotationList($objectId);
        return $this->renderText(json_encode($annotationList, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function executeImportAnnotationsIIIF($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) {
            return $this->renderText(json_encode(['error' => 'Not a researcher']));
        }

        $annotationService = $this->loadWebAnnotationService();
        $objectId = (int) $request->getParameter('object_id');

        if ($request->isMethod('post')) {
            try {
                $iiifData = json_decode($request->getContent(), true) ?: [];
                $count = $annotationService->importIIIFAnnotations($researcher->id, $objectId, $iiifData);
                return $this->renderText(json_encode(['success' => true, 'imported' => $count]));
            } catch (\Exception $e) {
                return $this->renderText(json_encode(['error' => $e->getMessage()]));
            }
        }
        return $this->renderText(json_encode(['error' => 'POST required']));
    }

    // =========================================================================
    // ISSUE 159 PHASE 2a: ASSERTIONS (KNOWLEDGE GRAPH)
    // =========================================================================

    protected function loadAssertionService(): \AssertionService
    {
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/AssertionService.php';
        return new \AssertionService();
    }

    public function executeAssertions($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $projectId = (int) $request->getParameter('project_id');
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ProjectService.php';
        $projectService = new \ProjectService();
        $this->project = $projectService->getProject($projectId, $this->researcher->id);
        if (!$this->project) { $this->forward404('Project not found'); }

        $assertionService = $this->loadAssertionService();
        $this->assertions = $assertionService->getProjectAssertions($projectId, [
            'assertion_type' => $request->getParameter('assertion_type'),
            'status' => $request->getParameter('status'),
        ]);
    }

    public function executeCreateAssertion($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) {
            return $this->renderText(json_encode(['error' => 'Not a researcher']));
        }

        $assertionService = $this->loadAssertionService();

        if ($request->isMethod('post')) {
            try {
                $body = json_decode($request->getContent(), true) ?: [];
                $id = $assertionService->createAssertion($researcher->id, $body);
                return $this->renderText(json_encode(['success' => true, 'id' => $id]));
            } catch (\Exception $e) {
                return $this->renderText(json_encode(['error' => $e->getMessage()]));
            }
        }
        return $this->renderText(json_encode(['error' => 'POST required']));
    }

    public function executeViewAssertion($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $assertionService = $this->loadAssertionService();
        $id = (int) $request->getParameter('id');
        $this->assertion = $assertionService->getAssertion($id);
        if (!$this->assertion) { $this->forward404('Assertion not found'); }

        $this->conflicts = $assertionService->detectConflicts($id);
    }

    public function executeUpdateAssertionStatus($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) {
            return $this->renderText(json_encode(['error' => 'Not a researcher']));
        }

        $assertionService = $this->loadAssertionService();
        $id = (int) $request->getParameter('id');

        if ($request->isMethod('post')) {
            try {
                $status = $request->getParameter('status') ?: json_decode($request->getContent(), true)['status'] ?? '';
                $assertionService->updateStatus($id, $status, $researcher->id);
                return $this->renderText(json_encode(['success' => true]));
            } catch (\Exception $e) {
                return $this->renderText(json_encode(['error' => $e->getMessage()]));
            }
        }
        return $this->renderText(json_encode(['error' => 'POST required']));
    }

    public function executeAddAssertionEvidence($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) {
            return $this->renderText(json_encode(['error' => 'Not a researcher']));
        }

        $assertionService = $this->loadAssertionService();
        $assertionId = (int) $request->getParameter('id');

        if ($request->isMethod('post')) {
            try {
                $body = json_decode($request->getContent(), true) ?: [];
                $body['added_by'] = $researcher->id;
                $evidenceId = $assertionService->addEvidence($assertionId, $body);
                return $this->renderText(json_encode(['success' => true, 'id' => $evidenceId]));
            } catch (\Exception $e) {
                return $this->renderText(json_encode(['error' => $e->getMessage()]));
            }
        }
        return $this->renderText(json_encode(['error' => 'POST required']));
    }

    public function executeAssertionConflicts($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }
        $assertionService = $this->loadAssertionService();
        $id = (int) $request->getParameter('id');
        $conflicts = $assertionService->detectConflicts($id);
        return $this->renderText(json_encode(['conflicts' => $conflicts]));
    }

    public function executeKnowledgeGraph($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $projectId = (int) $request->getParameter('project_id');
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ProjectService.php';
        $projectService = new \ProjectService();
        $this->project = $projectService->getProject($projectId, $this->researcher->id);
        if (!$this->project) { $this->forward404('Project not found'); }

        $this->projectId = $projectId;
    }

    public function executeKnowledgeGraphData($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/GraphService.php';
        $graphService = new \GraphService();

        $projectId = (int) $request->getParameter('project_id');
        $graph = $graphService->buildRelationshipGraph($projectId, [
            'assertion_type' => $request->getParameter('assertion_type'),
            'status' => $request->getParameter('status'),
        ]);
        return $this->renderText(json_encode($graph));
    }

    // =========================================================================
    // ISSUE 159 PHASE 2b: EXTRACTION ORCHESTRATION
    // =========================================================================

    protected function loadExtractionService(): \ExtractionOrchestrationService
    {
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ExtractionOrchestrationService.php';
        return new \ExtractionOrchestrationService();
    }

    public function executeExtractionJobs($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $projectId = (int) $request->getParameter('project_id');
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ProjectService.php';
        $projectService = new \ProjectService();
        $this->project = $projectService->getProject($projectId, $this->researcher->id);
        if (!$this->project) { $this->forward404('Project not found'); }

        $extractionService = $this->loadExtractionService();
        $this->jobs = $extractionService->getProjectJobs($projectId);
    }

    public function executeCreateExtractionJob($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) { $this->redirect('research/register'); }

        $extractionService = $this->loadExtractionService();
        $projectId = (int) $request->getParameter('project_id');

        if ($request->isMethod('post')) {
            try {
                $params = [];
                if ($request->getParameter('language')) { $params['language'] = $request->getParameter('language'); }
                if ($request->getParameter('model')) { $params['model'] = $request->getParameter('model'); }

                $jobId = $extractionService->createJob(
                    $projectId,
                    (int) $request->getParameter('collection_id'),
                    $researcher->id,
                    $request->getParameter('extraction_type'),
                    $params
                );
                $this->getUser()->setFlash('success', 'Extraction job created');
                $this->redirect('/research/extraction-job/' . $jobId);
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', $e->getMessage());
            }
        }
        $this->redirect('/research/extraction-jobs/' . $projectId);
    }

    public function executeViewExtractionJob($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $extractionService = $this->loadExtractionService();
        $id = (int) $request->getParameter('id');
        $this->job = $extractionService->getJob($id);
        if (!$this->job) { $this->forward404('Extraction job not found'); }

        $this->results = $extractionService->getJobResults($id, [
            'result_type' => $request->getParameter('result_type'),
        ]);
    }

    // =========================================================================
    // ISSUE 159 PHASE 2b: VALIDATION QUEUE
    // =========================================================================

    protected function loadValidationService(): \ValidationQueueService
    {
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ValidationQueueService.php';
        return new \ValidationQueueService();
    }

    public function executeValidationQueue($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $validationService = $this->loadValidationService();
        $page = max(1, (int) $request->getParameter('page', 1));
        $this->queue = $validationService->getQueue($this->researcher->id, [
            'status' => $request->getParameter('status', 'pending'),
            'result_type' => $request->getParameter('result_type'),
            'extraction_type' => $request->getParameter('extraction_type'),
            'min_confidence' => $request->getParameter('min_confidence'),
        ], $page, 25);
        $this->pendingCount = $validationService->getPendingCount($this->researcher->id);
        $this->stats = $validationService->getQueueStats($this->researcher->id);
    }

    public function executeValidateResult($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) {
            return $this->renderText(json_encode(['error' => 'Not a researcher']));
        }

        $validationService = $this->loadValidationService();
        $resultId = (int) $request->getParameter('id');

        if ($request->isMethod('post')) {
            try {
                $body = json_decode($request->getContent(), true) ?: [];
                $action = $body['form_action'] ?? $request->getParameter('form_action');

                if ($action === 'accept') {
                    $validationService->acceptResult($resultId, $researcher->id);
                    return $this->renderText(json_encode(['success' => true, 'action' => 'accepted']));
                } elseif ($action === 'reject') {
                    $reason = $body['reason'] ?? $request->getParameter('reason', '');
                    $validationService->rejectResult($resultId, $researcher->id, $reason);
                    return $this->renderText(json_encode(['success' => true, 'action' => 'rejected']));
                } elseif ($action === 'modify') {
                    $modifiedData = $body['modified_data'] ?? [];
                    $validationService->modifyResult($resultId, $researcher->id, $modifiedData);
                    return $this->renderText(json_encode(['success' => true, 'action' => 'modified']));
                }
                return $this->renderText(json_encode(['error' => 'Invalid action']));
            } catch (\Exception $e) {
                return $this->renderText(json_encode(['error' => $e->getMessage()]));
            }
        }
        return $this->renderText(json_encode(['error' => 'POST required']));
    }

    public function executeBulkValidate($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) {
            return $this->renderText(json_encode(['error' => 'Not a researcher']));
        }

        $validationService = $this->loadValidationService();

        if ($request->isMethod('post')) {
            try {
                $body = json_decode($request->getContent(), true) ?: [];
                $ids = $body['result_ids'] ?? [];
                $action = $body['form_action'] ?? 'accept';

                if ($action === 'accept') {
                    $count = $validationService->bulkAccept($ids, $researcher->id);
                    return $this->renderText(json_encode(['success' => true, 'count' => $count]));
                } elseif ($action === 'reject') {
                    $reason = $body['reason'] ?? '';
                    $count = $validationService->bulkReject($ids, $researcher->id, $reason);
                    return $this->renderText(json_encode(['success' => true, 'count' => $count]));
                }
                return $this->renderText(json_encode(['error' => 'Invalid action']));
            } catch (\Exception $e) {
                return $this->renderText(json_encode(['error' => $e->getMessage()]));
            }
        }
        return $this->renderText(json_encode(['error' => 'POST required']));
    }

    // =========================================================================
    // ISSUE 159 PHASE 2b: DOCUMENT TEMPLATES
    // =========================================================================

    public function executeDocumentTemplates($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $extractionService = $this->loadExtractionService();
        $this->templates = $extractionService->getDocumentTemplates();
    }

    public function executeEditDocumentTemplate($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $extractionService = $this->loadExtractionService();
        $id = $request->getParameter('id');

        if ($id) {
            $this->template = DB::table('research_document_template')->where('id', (int) $id)->first();
            if (!$this->template) { $this->forward404('Template not found'); }
        } else {
            $this->template = null;
        }

        if ($request->isMethod('post')) {
            // Handle delete
            if ($request->getParameter('form_action') === 'delete' && $id) {
                $extractionService->deleteDocumentTemplate((int) $id);
                $this->getUser()->setFlash('success', 'Template deleted');
                $this->redirect('research/document-templates');
            }

            try {
                $data = [
                    'name' => $request->getParameter('name'),
                    'document_type' => $request->getParameter('document_type'),
                    'description' => $request->getParameter('description'),
                    'fields_json' => $request->getParameter('fields_json') ?: '[]',
                    'created_by' => (int) $this->getUser()->getAttribute('user_id'),
                ];

                if ($id) {
                    $extractionService->updateDocumentTemplate((int) $id, $data);
                    $this->getUser()->setFlash('success', 'Template updated');
                } else {
                    $id = $extractionService->createDocumentTemplate($data);
                    $this->getUser()->setFlash('success', 'Template created');
                }
                $this->redirect('research/document-templates');
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', $e->getMessage());
            }
        }
    }

    // =========================================================================
    // ISSUE 159 PHASE 2c: ENTITY RESOLUTION
    // =========================================================================

    protected function loadEntityResolutionService(): \EntityResolutionService
    {
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/EntityResolutionService.php';
        return new \EntityResolutionService();
    }

    public function executeEntityResolution($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $erService = $this->loadEntityResolutionService();
        $page = max(1, (int) $request->getParameter('page', 1));
        $this->proposals = $erService->getProposals([
            'status' => $request->getParameter('status'),
            'entity_type' => $request->getParameter('entity_type'),
            'relationship_type' => $request->getParameter('relationship_type'),
        ], $page, 25);
    }

    public function executeProposeEntityMatch($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }

        $erService = $this->loadEntityResolutionService();

        if ($request->isMethod('post')) {
            try {
                $body = json_decode($request->getContent(), true) ?: [];
                $id = $erService->proposeMatch($body);
                return $this->renderText(json_encode(['success' => true, 'id' => $id]));
            } catch (\Exception $e) {
                return $this->renderText(json_encode(['error' => $e->getMessage()]));
            }
        }
        return $this->renderText(json_encode(['error' => 'POST required']));
    }

    public function executeResolveEntityMatch($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) {
            return $this->renderText(json_encode(['error' => 'Not a researcher']));
        }

        $erService = $this->loadEntityResolutionService();
        $id = (int) $request->getParameter('id');

        if ($request->isMethod('post')) {
            try {
                $body = json_decode($request->getContent(), true) ?: [];

                // Conflict check mode
                if (!empty($body['check_conflicts'])) {
                    $conflicts = $erService->getConflictingAssertions($id);
                    return $this->renderText(json_encode(['conflicts' => array_values($conflicts)]));
                }

                $status = $body['status'] ?? $request->getParameter('status');
                $erService->resolveMatch($id, $status, $researcher->id);
                return $this->renderText(json_encode(['success' => true]));
            } catch (\Exception $e) {
                return $this->renderText(json_encode(['error' => $e->getMessage()]));
            }
        }
        return $this->renderText(json_encode(['error' => 'POST required']));
    }

    public function executeFindEntityCandidates($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }

        $erService = $this->loadEntityResolutionService();
        $entityType = $request->getParameter('entity_type', 'actor');
        $entityId = (int) $request->getParameter('entity_id');
        $candidates = $erService->findCandidates($entityType, $entityId);
        return $this->renderText(json_encode(['candidates' => $candidates]));
    }

    // =========================================================================
    // ISSUE 159 PHASE 2c: TIMELINE
    // =========================================================================

    protected function loadTimelineService(): \TimelineService
    {
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/TimelineService.php';
        return new \TimelineService();
    }

    public function executeTimelineBuilder($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $projectId = (int) $request->getParameter('project_id');
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ProjectService.php';
        $projectService = new \ProjectService();
        $this->project = $projectService->getProject($projectId, $this->researcher->id);
        if (!$this->project) { $this->forward404('Project not found'); }

        $timelineService = $this->loadTimelineService();
        $this->events = $timelineService->getProjectTimeline($projectId);
        $this->projectId = $projectId;
    }

    public function executeTimelineEventApi($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) {
            return $this->renderText(json_encode(['error' => 'Not a researcher']));
        }

        $timelineService = $this->loadTimelineService();

        if ($request->isMethod('post')) {
            try {
                $body = json_decode($request->getContent(), true) ?: [];
                $action = $body['form_action'] ?? $request->getParameter('form_action', 'create');

                if ($action === 'create') {
                    $id = $timelineService->createEvent(
                        (int) ($body['project_id'] ?? $request->getParameter('project_id')),
                        $researcher->id,
                        $body
                    );
                    return $this->renderText(json_encode(['success' => true, 'id' => $id]));
                } elseif ($action === 'update') {
                    $timelineService->updateEvent((int) ($body['id'] ?? $request->getParameter('id')), $body);
                    return $this->renderText(json_encode(['success' => true]));
                } elseif ($action === 'delete') {
                    $timelineService->deleteEvent((int) ($body['id'] ?? $request->getParameter('id')));
                    return $this->renderText(json_encode(['success' => true]));
                } elseif ($action === 'auto_populate') {
                    $count = $timelineService->autoPopulateFromCollection(
                        (int) ($body['project_id'] ?? $request->getParameter('project_id')),
                        (int) ($body['collection_id'] ?? $request->getParameter('collection_id'))
                    );
                    return $this->renderText(json_encode(['success' => true, 'count' => $count]));
                }
                return $this->renderText(json_encode(['error' => 'Invalid action']));
            } catch (\Exception $e) {
                return $this->renderText(json_encode(['error' => $e->getMessage()]));
            }
        }
        return $this->renderText(json_encode(['error' => 'POST required']));
    }

    public function executeTimelineData($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }
        $timelineService = $this->loadTimelineService();
        $projectId = (int) $request->getParameter('project_id');
        $events = $timelineService->getProjectTimeline($projectId);
        return $this->renderText(json_encode(['events' => $events]));
    }

    // =========================================================================
    // ISSUE 159 PHASE 2c: MAP
    // =========================================================================

    protected function loadMapService(): \MapService
    {
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/MapService.php';
        return new \MapService();
    }

    public function executeMapBuilder($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $projectId = (int) $request->getParameter('project_id');
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ProjectService.php';
        $projectService = new \ProjectService();
        $this->project = $projectService->getProject($projectId, $this->researcher->id);
        if (!$this->project) { $this->forward404('Project not found'); }

        $mapService = $this->loadMapService();
        $this->points = $mapService->getProjectPoints($projectId);
        $this->projectId = $projectId;
    }

    public function executeMapPointApi($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) {
            return $this->renderText(json_encode(['error' => 'Not a researcher']));
        }

        $mapService = $this->loadMapService();

        if ($request->isMethod('post')) {
            try {
                $body = json_decode($request->getContent(), true) ?: [];
                $action = $body['form_action'] ?? $request->getParameter('form_action', 'create');

                if ($action === 'create') {
                    $id = $mapService->createPoint(
                        (int) ($body['project_id'] ?? $request->getParameter('project_id')),
                        $researcher->id,
                        $body
                    );
                    return $this->renderText(json_encode(['success' => true, 'id' => $id]));
                } elseif ($action === 'update') {
                    $mapService->updatePoint((int) ($body['id'] ?? $request->getParameter('id')), $body);
                    return $this->renderText(json_encode(['success' => true]));
                } elseif ($action === 'delete') {
                    $mapService->deletePoint((int) ($body['id'] ?? $request->getParameter('id')));
                    return $this->renderText(json_encode(['success' => true]));
                }
                return $this->renderText(json_encode(['error' => 'Invalid action']));
            } catch (\Exception $e) {
                return $this->renderText(json_encode(['error' => $e->getMessage()]));
            }
        }
        return $this->renderText(json_encode(['error' => 'POST required']));
    }

    public function executeMapData($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }
        $mapService = $this->loadMapService();
        $projectId = (int) $request->getParameter('project_id');
        $points = $mapService->getProjectPoints($projectId);
        return $this->renderText(json_encode(['points' => $points]));
    }

    // =========================================================================
    // ISSUE 159 PHASE 2c: NETWORK GRAPH EXPLORER
    // =========================================================================

    public function executeNetworkGraph($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $projectId = (int) $request->getParameter('project_id');
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ProjectService.php';
        $projectService = new \ProjectService();
        $this->project = $projectService->getProject($projectId, $this->researcher->id);
        if (!$this->project) { $this->forward404('Project not found'); }

        $this->projectId = $projectId;
    }

    public function executeNetworkGraphData($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/GraphService.php';
        $graphService = new \GraphService();

        $projectId = (int) $request->getParameter('project_id');
        $graph = $graphService->buildRelationshipGraph($projectId, [
            'assertion_type' => $request->getParameter('assertion_type'),
            'status' => $request->getParameter('status'),
        ]);
        return $this->renderText(json_encode($graph));
    }

    public function executeExportGraphGEXF($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/GraphService.php';
        $graphService = new \GraphService();

        $projectId = (int) $request->getParameter('project_id');
        $gexf = $graphService->exportGEXF($projectId);

        $this->getResponse()->setContentType('application/xml');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="graph-project-' . $projectId . '.gexf"');
        return $this->renderText($gexf);
    }

    public function executeExportGraphML($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/GraphService.php';
        $graphService = new \GraphService();

        $projectId = (int) $request->getParameter('project_id');
        $graphml = $graphService->exportGraphML($projectId);

        $this->getResponse()->setContentType('application/xml');
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="graph-project-' . $projectId . '.graphml"');
        return $this->renderText($graphml);
    }

    public function executeEvidenceViewer($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $this->objectId = (int) $request->getParameter('object_id');

        // Object title
        $this->objectTitle = DB::table('information_object_i18n')
            ->where('id', $this->objectId)
            ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
            ->value('title') ?? 'Object #' . $this->objectId;

        // Object slug for link
        $this->objectSlug = DB::table('slug')
            ->where('object_id', $this->objectId)
            ->value('slug');

        // Thumbnail — look for the _thumbnail derivative digital object
        $do = DB::table('digital_object')->where('object_id', $this->objectId)->first();
        $this->thumbnail = null;
        $this->iiifAvailable = (bool) $do;
        if ($do) {
            // AtoM stores thumbnails as child digital objects with usage_id = 142 (thumbnail)
            $thumb = DB::table('digital_object')
                ->where('parent_id', $do->id)
                ->where('usage_id', 142)
                ->first();
            if ($thumb) {
                $this->thumbnail = '/' . ltrim($thumb->path, '/') . $thumb->name;
            } else {
                // Fallback: try path + name_thumbnail convention
                $ext = pathinfo($do->name, PATHINFO_EXTENSION);
                $basename = pathinfo($do->name, PATHINFO_FILENAME);
                $thumbName = $basename . '_thumbnail.' . ($ext ?: 'png');
                $thumbPath = '/' . ltrim($do->path, '/') . $thumbName;
                if (file_exists(sfConfig::get('sf_web_dir', '/usr/share/nginx/archive') . $thumbPath)) {
                    $this->thumbnail = $thumbPath;
                }
            }
        }

        // Provenance — activity log for this object
        $this->provenance = DB::table('research_activity_log as al')
            ->leftJoin('research_researcher as r', 'al.researcher_id', '=', 'r.id')
            ->leftJoin('actor_i18n as ai', function($join) {
                $join->on('r.user_id', '=', 'ai.id')
                    ->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('al.entity_id', $this->objectId)
            ->orderBy('al.created_at', 'desc')
            ->limit(50)
            ->select('al.*', 'ai.authorized_form_of_name as first_name')
            ->get()->toArray();

        // Security clearance
        $this->securityClearance = null;
        try {
            $this->securityClearance = DB::table('object_security_classification')
                ->where('object_id', $this->objectId)
                ->first();
        } catch (\Exception $e) { /* table may not exist */ }

        // ODRL policies
        $this->odrlPolicies = [];
        try {
            $this->odrlPolicies = DB::table('research_rights_policy')
                ->where('target_id', $this->objectId)
                ->orderBy('created_at', 'desc')
                ->get()->toArray();
        } catch (\Exception $e) { /* table may not exist */ }

        // Trust score
        $trustService = $this->loadTrustScoringService();
        try {
            $this->trustScore = $trustService->computeTrustScore($this->objectId);
        } catch (\Exception $e) {
            $this->trustScore = null;
        }

        // Source assessment
        $this->sourceAssessment = DB::table('research_source_assessment')
            ->where('object_id', $this->objectId)
            ->orderBy('assessed_at', 'desc')
            ->first();

        // Annotations
        $annotationService = $this->loadWebAnnotationService();
        $this->annotations = $annotationService->getObjectAnnotations($this->objectId);

        // Assertions where this object is subject
        $assertionService = $this->loadAssertionService();
        $this->assertions = DB::table('research_assertion')
            ->where('subject_id', $this->objectId)
            ->orWhere('object_id', $this->objectId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()->toArray();

        // Quality metrics
        $this->qualityMetrics = DB::table('research_quality_metric')
            ->where('object_id', $this->objectId)
            ->orderBy('created_at', 'desc')
            ->get()->toArray();
    }

    public function executeComplianceDashboard($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $projectId = (int) $request->getParameter('project_id');
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ProjectService.php';
        $projectService = new \ProjectService();
        $this->project = $projectService->getProject($projectId, $this->researcher->id);
        if (!$this->project) { $this->forward404('Project not found'); }

        // Ethics milestones
        $this->ethicsMilestones = DB::table('research_project_milestone')
            ->where('project_id', $projectId)
            ->orderBy('sort_order')
            ->get()->toArray();

        // Compute overall ethics status
        $milestoneStatuses = array_column($this->ethicsMilestones, 'status');
        if (empty($milestoneStatuses)) {
            $this->ethicsStatus = 'not_started';
        } elseif (in_array('rejected', $milestoneStatuses)) {
            $this->ethicsStatus = 'rejected';
        } elseif (count(array_filter($milestoneStatuses, function($s) { return $s === 'completed' || $s === 'approved'; })) === count($milestoneStatuses)) {
            $this->ethicsStatus = 'approved';
        } else {
            $this->ethicsStatus = 'pending';
        }

        // ODRL policies for project
        $this->odrlPolicies = DB::table('research_rights_policy')
            ->where('project_id', $projectId)
            ->orderBy('created_at', 'desc')
            ->get()->toArray();
        $this->odrlPolicyCount = count($this->odrlPolicies);

        // Security levels of linked resources
        $resourceIds = DB::table('research_project_resource')
            ->where('project_id', $projectId)
            ->pluck('object_id');

        $this->sensitivityBreakdown = [];
        $this->sensitivitySummary = ['max_level' => 'none'];
        try {
            if ($resourceIds->count() > 0) {
                $clearances = DB::table('object_security_classification')
                    ->whereIn('object_id', $resourceIds)
                    ->get();
                $breakdown = [];
                $levelOrder = ['unclassified' => 0, 'confidential' => 1, 'secret' => 2, 'top_secret' => 3];
                $maxLevel = 'none';
                foreach ($clearances as $c) {
                    $lev = $c->level ?? 'unclassified';
                    $breakdown[$lev] = ($breakdown[$lev] ?? 0) + 1;
                    if (($levelOrder[$lev] ?? 0) > ($levelOrder[$maxLevel] ?? -1)) {
                        $maxLevel = $lev;
                    }
                }
                $this->sensitivityBreakdown = $breakdown;
                $this->sensitivitySummary = ['max_level' => $maxLevel];
            }
        } catch (\Exception $e) { /* security_clearance table may not exist */ }

        // Trust scores for project sources
        $this->trustScores = [];
        $this->avgTrustScore = null;
        try {
            $assessments = DB::table('research_source_assessment as sa')
                ->leftJoin('information_object_i18n as ioi', function($join) {
                    $join->on('sa.object_id', '=', 'ioi.id')
                        ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                })
                ->whereIn('sa.object_id', $resourceIds->count() > 0 ? $resourceIds : [0])
                ->select('sa.object_id', 'sa.trust_score as score', 'ioi.title')
                ->orderBy('sa.assessed_at', 'desc')
                ->limit(20)
                ->get()->toArray();
            $this->trustScores = $assessments;
            if (!empty($assessments)) {
                $scores = array_filter(array_column($assessments, 'score'), function($s) { return $s !== null; });
                $this->avgTrustScore = count($scores) > 0 ? array_sum($scores) / count($scores) / 10 : null;
            }
        } catch (\Exception $e) { /* silent */ }
    }

    // =========================================================================
    // ISSUE 159 PHASE 2d: RO-CRATE PACKAGING
    // =========================================================================

    protected function loadRoCrateService(): \RoCrateService
    {
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/RoCrateService.php';
        return new \RoCrateService();
    }

    public function executePackageProject($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            if ($request->getParameter('format') === 'json') {
                $this->getResponse()->setContentType('application/json');
                return $this->renderText(json_encode(['error' => 'Not authenticated']));
            }
            $this->redirect('user/login');
        }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $roCrateService = $this->loadRoCrateService();
        $projectId = (int) $request->getParameter('project_id');

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ProjectService.php';
        $projectService = new \ProjectService();
        $this->project = $projectService->getProject($projectId, $this->researcher->id);
        if (!$this->project) { $this->forward404('Project not found'); }

        // JSON download mode
        if ($request->getParameter('format') === 'json') {
            $this->getResponse()->setContentType('application/ld+json');
            try {
                $metadata = $roCrateService->generateManifest($projectId);
                return $this->renderText(json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            } catch (\Exception $e) {
                return $this->renderText(json_encode(['error' => $e->getMessage()]));
            }
        }

        // HTML mode — render roCrateSuccess.php template
        try {
            $this->roCrate = $roCrateService->generateManifest($projectId);
        } catch (\Exception $e) {
            $this->roCrate = ['error' => $e->getMessage(), 'items' => [], 'creators' => []];
        }
        $this->setTemplate('roCrate');
    }

    public function executePackageCollection($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) { $this->redirect('research/register'); }

        $roCrateService = $this->loadRoCrateService();
        $collectionId = (int) $request->getParameter('id');

        try {
            $filePath = $roCrateService->packageCollection($collectionId);
            $this->getUser()->setFlash('success', 'RO-Crate package generated');
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', $e->getMessage());
        }
        $this->redirect('/research/collection/' . $collectionId);
    }

    // =========================================================================
    // ISSUE 159 PHASE 2d: ODRL POLICIES
    // =========================================================================

    protected function loadOdrlService(): \OdrlService
    {
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/OdrlService.php';
        return new \OdrlService();
    }

    public function executeOdrlPolicies($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(url_for(['module' => 'user', 'action' => 'login']));
            return;
        }

        // JSON API mode: when target_type and target_id are provided
        $targetType = $request->getParameter('target_type');
        $targetId = $request->getParameter('target_id');
        if ($targetType && $targetId) {
            $this->getResponse()->setContentType('application/json');
            $odrlService = $this->loadOdrlService();
            $policies = $odrlService->getPolicies($targetType, (int) $targetId);
            return $this->renderText(json_encode(['policies' => $policies]));
        }

        // HTML page mode: list all policies
        $odrlService = $this->loadOdrlService();
        $filters = [];
        if ($request->getParameter('filter_target_type')) {
            $filters['target_type'] = $request->getParameter('filter_target_type');
        }
        if ($request->getParameter('filter_policy_type')) {
            $filters['policy_type'] = $request->getParameter('filter_policy_type');
        }
        if ($request->getParameter('filter_action_type')) {
            $filters['action_type'] = $request->getParameter('filter_action_type');
        }

        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $result = $odrlService->getAllPolicies($filters, $limit, $offset);

        $this->policies = $result;
        $this->currentPage = $page;
        $this->totalPages = ceil($result['total'] / $limit);
        $this->filters = $filters;
    }

    public function executeDeleteOdrlPolicy($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }

        $id = (int) $request->getParameter('id');
        if (!$id) {
            return $this->renderText(json_encode(['error' => 'Policy ID required']));
        }

        $odrlService = $this->loadOdrlService();
        try {
            $deleted = $odrlService->deletePolicy($id);
            return $this->renderText(json_encode(['success' => $deleted]));
        } catch (\Exception $e) {
            return $this->renderText(json_encode(['error' => $e->getMessage()]));
        }
    }

    public function executeCreateOdrlPolicy($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }

        $odrlService = $this->loadOdrlService();

        if ($request->isMethod('post')) {
            try {
                $body = json_decode($request->getContent(), true) ?: [];
                $body['created_by'] = (int) $this->getUser()->getAttribute('user_id');
                $id = $odrlService->createPolicy($body);
                return $this->renderText(json_encode(['success' => true, 'id' => $id]));
            } catch (\Exception $e) {
                return $this->renderText(json_encode(['error' => $e->getMessage()]));
            }
        }
        return $this->renderText(json_encode(['error' => 'POST required']));
    }

    public function executeEvaluateAccess($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) {
            return $this->renderText(json_encode(['error' => 'Not a researcher']));
        }

        $odrlService = $this->loadOdrlService();
        $targetType = $request->getParameter('target_type');
        $targetId = (int) $request->getParameter('target_id');
        $action = $request->getParameter('action_type', 'use');

        $result = $odrlService->evaluateAccess($targetType, $targetId, $researcher->id, $action);
        return $this->renderText(json_encode($result));
    }

    // =========================================================================
    // ISSUE 159 PHASE 2d: REPRODUCIBILITY
    // =========================================================================

    protected function loadReproducibilityService(): \ReproducibilityService
    {
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ReproducibilityService.php';
        return new \ReproducibilityService();
    }

    public function executeReproducibilityPack($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            if ($request->getParameter('format') === 'json') {
                $this->getResponse()->setContentType('application/json');
                return $this->renderText(json_encode(['error' => 'Not authenticated']));
            }
            $this->redirect('user/login');
        }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $reproService = $this->loadReproducibilityService();
        $projectId = (int) $request->getParameter('project_id');

        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ProjectService.php';
        $projectService = new \ProjectService();
        $this->project = $projectService->getProject($projectId, $this->researcher->id);
        if (!$this->project) { $this->forward404('Project not found'); }

        try {
            $pack = $reproService->generatePack($projectId);
        } catch (\Exception $e) {
            $pack = ['error' => $e->getMessage()];
        }

        // JSON download mode
        if ($request->getParameter('format') === 'json') {
            $this->getResponse()->setContentType('application/json');
            return $this->renderText(json_encode($pack, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        // HTML mode — render template
        $this->pack = $pack;
    }

    public function executeProjectJsonLd($request)
    {
        $this->getResponse()->setContentType('application/ld+json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }

        $reproService = $this->loadReproducibilityService();
        $projectId = (int) $request->getParameter('project_id');

        try {
            $jsonLd = $reproService->exportJsonLd($projectId);
            return $this->renderText($jsonLd);
        } catch (\Exception $e) {
            return $this->renderText(json_encode(['error' => $e->getMessage()]));
        }
    }

    public function executeMintDoi($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            if ($request->isMethod('post')) {
                $this->getResponse()->setContentType('application/json');
                return $this->renderText(json_encode(['error' => 'Not authenticated']));
            }
            $this->redirect('user/login');
        }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $reproService = $this->loadReproducibilityService();
        $projectId = (int) $request->getParameter('project_id');

        // POST = AJAX mint request
        if ($request->isMethod('post')) {
            $this->getResponse()->setContentType('application/json');
            try {
                $result = $reproService->mintDoi($projectId);
                return $this->renderText(json_encode($result));
            } catch (\Exception $e) {
                return $this->renderText(json_encode(['error' => $e->getMessage()]));
            }
        }

        // GET = HTML page
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ProjectService.php';
        $projectService = new \ProjectService();
        $this->project = $projectService->getProject($projectId, $this->researcher->id);
        if (!$this->project) { $this->forward404('Project not found'); }

        // Get current DOI status
        $this->currentDoi = $this->project->doi ?? null;
        $this->doiMintedAt = $this->project->doi_minted_at ?? null;

        // Build creators string from collaborators
        $collaborators = DB::table('research_project_collaborator as pc')
            ->join('research_researcher as r', 'pc.researcher_id', '=', 'r.id')
            ->join('actor_i18n as ai', function($join) {
                $join->on('r.user_id', '=', 'ai.id')
                    ->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('pc.project_id', $projectId)
            ->select('ai.authorized_form_of_name')
            ->get()->toArray();
        $names = array_map(function($c) { return $c->authorized_form_of_name ?? ''; }, $collaborators);
        $this->creatorsString = implode(', ', array_filter($names));
    }

    // =========================================================================
    // ISSUE 159 PHASE 2e: ENHANCED COLLABORATION
    // =========================================================================

    public function executeEthicsMilestones($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $projectId = (int) $request->getParameter('project_id');
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ProjectService.php';
        $projectService = new \ProjectService();
        $this->project = $projectService->getProject($projectId, $this->researcher->id);
        if (!$this->project) { $this->forward404('Project not found'); }

        $this->milestones = DB::table('research_project_milestone')
            ->where('project_id', $projectId)
            ->orderBy('sort_order')
            ->get()->toArray();

        if ($request->isMethod('post')) {
            $formAction = $request->getParameter('form_action');
            try {
                if ($formAction === 'add_milestone') {
                    $maxOrder = DB::table('research_project_milestone')
                        ->where('project_id', $projectId)
                        ->max('sort_order') ?? 0;

                    DB::table('research_project_milestone')->insert([
                        'project_id' => $projectId,
                        'title' => $request->getParameter('title'),
                        'description' => $request->getParameter('description'),
                        'milestone_type' => $request->getParameter('milestone_type', 'ethics'),
                        'status' => 'pending',
                        'sort_order' => $maxOrder + 1,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                    $this->getUser()->setFlash('success', 'Ethics milestone added');
                } elseif ($formAction === 'update_status') {
                    $milestoneId = (int) $request->getParameter('milestone_id');
                    $newStatus = $request->getParameter('status');
                    DB::table('research_project_milestone')
                        ->where('id', $milestoneId)
                        ->where('project_id', $projectId)
                        ->update(['status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')]);
                    $this->getUser()->setFlash('success', 'Milestone status updated');
                } elseif ($formAction === 'delete_milestone') {
                    $milestoneId = (int) $request->getParameter('milestone_id');
                    DB::table('research_project_milestone')
                        ->where('id', $milestoneId)
                        ->where('project_id', $projectId)
                        ->delete();
                    $this->getUser()->setFlash('success', 'Milestone deleted');
                }
                $this->redirect('/research/ethics-milestones/' . $projectId);
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', $e->getMessage());
            }
        }
    }

    public function executeAssertionBatchReview($request)
    {
        if (!$this->getUser()->isAuthenticated()) { $this->redirect('user/login'); }
        $userId = $this->getUser()->getAttribute('user_id');
        $this->researcher = $this->service->getResearcherByUserId($userId);
        if (!$this->researcher) { $this->redirect('research/register'); }

        $projectId = (int) $request->getParameter('project_id');
        $assertionService = $this->loadAssertionService();
        $this->assertions = $assertionService->getProjectAssertions($projectId, [
            'status' => 'proposed',
        ]);
        $this->projectId = $projectId;

        if ($request->isMethod('post') && $request->getParameter('form_action') === 'batch_review') {
            try {
                $ids = $request->getParameter('assertion_ids', []);
                $newStatus = $request->getParameter('new_status', 'verified');
                $count = 0;
                foreach ($ids as $id) {
                    $assertionService->updateStatus((int) $id, $newStatus, $this->researcher->id);
                    $count++;
                }
                $this->getUser()->setFlash('success', $count . ' assertions updated to ' . $newStatus);
                $this->redirect('/research/assertion-batch-review/' . $projectId);
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', $e->getMessage());
            }
        }
    }

    // =========================================================================
    // ISSUE 159 ENHANCEMENT 5: DISCOVERY — SEARCH DIFF + SNAPSHOT
    // =========================================================================

    public function executeDiffSearchResults($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) {
            return $this->renderText(json_encode(['error' => 'Not a researcher']));
        }

        $searchId = (int) $request->getParameter('id');

        // Verify ownership
        $search = DB::table('research_saved_search')
            ->where('id', $searchId)
            ->where('researcher_id', $researcher->id)
            ->first();

        if (!$search) {
            return $this->renderText(json_encode(['error' => 'Saved search not found']));
        }

        // Run the saved query to get current result IDs
        $currentIds = [];
        try {
            $query = $search->search_query;
            if (!empty($query)) {
                $currentIds = DB::table('information_object as io')
                    ->leftJoin('information_object_i18n as i18n', function ($join) {
                        $join->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
                    })
                    ->where('io.id', '>', 1) // skip root
                    ->where(function ($q) use ($query) {
                        $q->where('i18n.title', 'LIKE', '%' . $query . '%')
                            ->orWhere('i18n.scope_and_content', 'LIKE', '%' . $query . '%')
                            ->orWhere('io.identifier', 'LIKE', '%' . $query . '%');
                    })
                    ->pluck('io.id')
                    ->toArray();
            }
        } catch (\Exception $e) {
            // Silent — just use empty
        }

        $diff = $this->service->diffSearchResults($searchId, $currentIds);
        return $this->renderText(json_encode($diff));
    }

    public function executeSnapshotSearchResults($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }
        $userId = $this->getUser()->getAttribute('user_id');
        $researcher = $this->service->getResearcherByUserId($userId);
        if (!$researcher) {
            return $this->renderText(json_encode(['error' => 'Not a researcher']));
        }

        $searchId = (int) $request->getParameter('id');

        // Verify ownership
        $search = DB::table('research_saved_search')
            ->where('id', $searchId)
            ->where('researcher_id', $researcher->id)
            ->first();

        if (!$search) {
            return $this->renderText(json_encode(['error' => 'Saved search not found']));
        }

        // Run the saved query to get current result IDs
        $currentIds = [];
        try {
            $query = $search->search_query;
            if (!empty($query)) {
                $currentIds = DB::table('information_object as io')
                    ->leftJoin('information_object_i18n as i18n', function ($join) {
                        $join->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
                    })
                    ->where('io.id', '>', 1)
                    ->where(function ($q) use ($query) {
                        $q->where('i18n.title', 'LIKE', '%' . $query . '%')
                            ->orWhere('i18n.scope_and_content', 'LIKE', '%' . $query . '%')
                            ->orWhere('io.identifier', 'LIKE', '%' . $query . '%');
                    })
                    ->pluck('io.id')
                    ->toArray();
            }
        } catch (\Exception $e) {
            // Silent
        }

        $success = $this->service->snapshotSearchResults($searchId, $currentIds);
        return $this->renderText(json_encode(['success' => $success, 'count' => count($currentIds)]));
    }

    // =========================================================================
    // ISSUE 164: IIIF RESEARCH ROOMS
    // =========================================================================

    protected function loadRoomService(): \ResearchRoomService
    {
        require_once $this->config('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/ResearchRoomService.php';
        return new \ResearchRoomService();
    }

    /**
     * List IIIF research rooms for a project.
     * GET /research/project/:project_id/iiif-rooms
     */
    public function executeIiifRooms($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->forward($this->config('sf_secure_module'), $this->config('sf_secure_action'));
        }

        $projectId = (int) $request->getParameter('project_id');
        $roomService = $this->loadRoomService();

        $this->projectId = $projectId;
        $this->rooms = $roomService->listRooms($projectId);
        $this->userId = (int) $this->getUser()->getAttribute('user_id');

        // Load project name
        $this->project = \Illuminate\Database\Capsule\Manager::table('research_project')
            ->where('id', $projectId)
            ->first();

        $this->response->setTitle('Research Rooms');
        $this->setTemplate('rooms');
    }

    /**
     * View a research room with IIIF viewer + participants.
     * GET /research/room/:id
     */
    public function executeViewRoom($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->forward($this->config('sf_secure_module'), $this->config('sf_secure_action'));
        }

        $roomId = (int) $request->getParameter('id');
        $roomService = $this->loadRoomService();
        $userId = (int) $this->getUser()->getAttribute('user_id');

        $room = $roomService->getRoom($roomId);

        // Fall back to reading rooms table if not found in IIIF rooms
        if (!$room) {
            $readingRoom = DB::table('research_reading_room')->where('id', $roomId)->first();
            if ($readingRoom) {
                $this->redirect('/research/rooms/edit?id=' . $roomId);
            }
            $this->forward404('Room not found');
        }

        if (!$roomService->hasAccess($roomId, $userId) && !$this->getUser()->isAdministrator()) {
            $this->forward($this->config('sf_secure_module'), $this->config('sf_secure_action'));
        }

        $this->room = $room;
        $this->participants = $roomService->getParticipants($roomId);
        $this->manifests = $roomService->getManifests($roomId);
        $this->isOwner = $roomService->isOwner($roomId, $userId);
        $this->userId = $userId;

        $this->response->setTitle($room->name);
        $this->setTemplate('viewRoom');
    }

    /**
     * Create or update a research room.
     * GET/POST /research/room/create/:project_id
     */
    public function executeCreateRoom($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->forward($this->config('sf_secure_module'), $this->config('sf_secure_action'));
        }

        $projectId = (int) $request->getParameter('project_id');
        $userId = (int) $this->getUser()->getAttribute('user_id');

        if ($request->isMethod('post')) {
            $roomService = $this->loadRoomService();
            $name = trim($request->getParameter('name', ''));
            $description = trim($request->getParameter('description', ''));
            $maxParticipants = (int) $request->getParameter('max_participants', 10);

            if (empty($name)) {
                $this->getUser()->setFlash('error', 'Room name is required.');
                $this->redirect("research/project/{$projectId}/rooms");
            }

            $roomId = $roomService->createRoom($projectId, $name, $userId, $description ?: null, $maxParticipants);

            $this->getUser()->setFlash('notice', 'Research room created successfully.');
            $this->redirect("research/room/{$roomId}");
        }

        $this->projectId = $projectId;
        $this->response->setTitle('Create Research Room');
        $this->setTemplate('rooms'); // Re-use rooms list with create modal
    }

    /**
     * Update a research room.
     * POST /research/room/:id/update
     */
    public function executeUpdateRoom($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->forward($this->config('sf_secure_module'), $this->config('sf_secure_action'));
        }

        $roomId = (int) $request->getParameter('id');
        $roomService = $this->loadRoomService();
        $userId = (int) $this->getUser()->getAttribute('user_id');

        if (!$roomService->isOwner($roomId, $userId) && !$this->getUser()->isAdministrator()) {
            $this->forward($this->config('sf_secure_module'), $this->config('sf_secure_action'));
        }

        $data = [
            'name' => trim($request->getParameter('name', '')),
            'description' => trim($request->getParameter('description', '')),
            'status' => $request->getParameter('status', 'draft'),
            'max_participants' => (int) $request->getParameter('max_participants', 10),
        ];

        $roomService->updateRoom($roomId, $data);

        $this->getUser()->setFlash('notice', 'Room updated.');
        $this->redirect("research/room/{$roomId}");
    }

    /**
     * Room derivative manifest (IIIF Collection).
     * GET /research/room/:id/manifest.json
     */
    public function executeRoomManifest($request)
    {
        $roomId = (int) $request->getParameter('id');
        $roomService = $this->loadRoomService();

        $room = $roomService->getRoom($roomId);
        if (!$room || $room->status === 'archived') {
            $this->getResponse()->setStatusCode(404);
            return $this->renderText(json_encode(['error' => 'Room not found']));
        }

        $manifest = $roomService->generateRoomManifest($roomId);

        $this->getResponse()->setContentType('application/json');
        $this->getResponse()->setHttpHeader('Access-Control-Allow-Origin', '*');

        return $this->renderText(json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Export room annotations as W3C AnnotationCollection.
     * GET /research/room/:id/annotations.json
     */
    public function executeRoomAnnotationExport($request)
    {
        $roomId = (int) $request->getParameter('id');
        $roomService = $this->loadRoomService();

        $room = $roomService->getRoom($roomId);
        if (!$room) {
            $this->getResponse()->setStatusCode(404);
            return $this->renderText(json_encode(['error' => 'Room not found']));
        }

        $annotations = $roomService->exportAnnotations($roomId);

        $this->getResponse()->setContentType('application/ld+json');
        $this->getResponse()->setHttpHeader('Access-Control-Allow-Origin', '*');

        return $this->renderText(json_encode($annotations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

}
