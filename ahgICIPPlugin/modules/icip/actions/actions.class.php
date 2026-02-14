<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * ICIP Module Actions
 *
 * Controller for Indigenous Cultural and Intellectual Property management
 *
 * @package ahgICIPPlugin
 */
class icipActions extends AhgController
{
    /**
     * Pre-execute: Check user authentication for admin pages
     */
    public function boot(): void
    {
// Public actions that don't require authentication
        $publicActions = ['acknowledge', 'apiSummary', 'apiCheckAccess'];

        if (!in_array($this->getActionName(), $publicActions)) {
            if (!$this->getUser()->isAuthenticated()) {
                $this->redirect('user/login');
            }
        }
    }

    // ========================================
    // DASHBOARD
    // ========================================

    /**
     * ICIP Dashboard
     */
    public function executeDashboard($request)
    {
        $this->stats = ahgICIPService::getDashboardStats();
        $this->pendingConsultations = ahgICIPService::getPendingConsultation(10);
        $this->expiringConsents = ahgICIPService::getExpiringConsents(90);

        // Recent activity
        $this->recentConsultations = DB::table('icip_consultation as c')
            ->join('icip_community as com', 'c.community_id', '=', 'com.id')
            ->select(['c.*', 'com.name as community_name'])
            ->orderBy('c.created_at', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    // ========================================
    // COMMUNITY MANAGEMENT
    // ========================================

    /**
     * Redirect /icip/community to /icip/communities
     */
    public function executeCommunity($request)
    {
        $this->redirect('icip/communities');
    }

    /**
     * List communities
     */
    public function executeCommunities($request)
    {
        $query = DB::table('icip_community')
            ->orderBy('name');

        // Filters
        if ($state = $request->getParameter('state')) {
            $query->where('state_territory', $state);
        }
        if ($request->getParameter('active_only', '1') === '1') {
            $query->where('is_active', 1);
        }
        if ($search = $request->getParameter('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('language_group', 'like', "%{$search}%")
                    ->orWhere('region', 'like', "%{$search}%");
            });
        }

        $this->communities = $query->get()->toArray();
        $this->states = ahgICIPService::getStateTerritories();
        $this->filters = [
            'state' => $state,
            'active_only' => $request->getParameter('active_only', '1'),
            'search' => $search,
        ];
    }

    /**
     * Add/Edit community
     */
    public function executeCommunityEdit($request)
    {
        $this->id = $request->getParameter('id');
        $this->community = null;
        $this->states = ahgICIPService::getStateTerritories();

        if ($this->id) {
            $this->community = DB::table('icip_community')
                ->where('id', $this->id)
                ->first();

            if (!$this->community) {
                $this->forward404('Community not found');
            }
        }

        if ($request->isMethod('post')) {
            $data = [
                'name' => $request->getParameter('name'),
                'alternate_names' => $request->getParameter('alternate_names') ? json_encode(array_filter(array_map('trim', explode(',', $request->getParameter('alternate_names'))))) : null,
                'language_group' => $request->getParameter('language_group'),
                'region' => $request->getParameter('region'),
                'state_territory' => $request->getParameter('state_territory'),
                'contact_name' => $request->getParameter('contact_name'),
                'contact_email' => $request->getParameter('contact_email'),
                'contact_phone' => $request->getParameter('contact_phone'),
                'contact_address' => $request->getParameter('contact_address'),
                'native_title_reference' => $request->getParameter('native_title_reference'),
                'prescribed_body_corporate' => $request->getParameter('prescribed_body_corporate'),
                'pbc_contact_email' => $request->getParameter('pbc_contact_email'),
                'notes' => $request->getParameter('notes'),
                'is_active' => $request->getParameter('is_active', 0) ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($this->id) {
                DB::table('icip_community')
                    ->where('id', $this->id)
                    ->update($data);
                $this->getUser()->setFlash('notice', 'Community updated successfully.');
            } else {
                $data['created_by'] = $this->getUser()->getAttribute('user_id');
                $data['created_at'] = date('Y-m-d H:i:s');
                $this->id = DB::table('icip_community')->insertGetId($data);
                $this->getUser()->setFlash('notice', 'Community created successfully.');
            }

            $this->redirect('icip/communities');
        }
    }

    /**
     * View community details
     */
    public function executeCommunityView($request)
    {
        $this->id = $request->getParameter('id');
        $this->community = DB::table('icip_community')
            ->where('id', $this->id)
            ->first();

        if (!$this->community) {
            $this->forward404('Community not found');
        }

        // Get related records
        $this->consents = DB::table('icip_consent as c')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('c.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->where('c.community_id', $this->id)
            ->select(['c.*', 'ioi.title as object_title'])
            ->orderBy('c.created_at', 'desc')
            ->limit(20)
            ->get()
            ->toArray();

        $this->consultations = DB::table('icip_consultation')
            ->where('community_id', $this->id)
            ->where('is_confidential', 0)
            ->orderBy('consultation_date', 'desc')
            ->limit(20)
            ->get()
            ->toArray();

        $this->states = ahgICIPService::getStateTerritories();
    }

    /**
     * Delete community
     */
    public function executeCommunityDelete($request)
    {
        $id = $request->getParameter('id');

        // Check for linked records
        $linkedConsents = DB::table('icip_consent')->where('community_id', $id)->count();
        $linkedNotices = DB::table('icip_cultural_notice')->where('community_id', $id)->count();

        if ($linkedConsents > 0 || $linkedNotices > 0) {
            $this->getUser()->setFlash('error', 'Cannot delete community with linked records. Deactivate instead.');
        } else {
            if (class_exists('\\Illuminate\\Database\\Capsule\\Manager')) {
                DB::table('icip_community')->where('id', $id)->delete();
            } else {
                $conn = \Propel::getConnection();
                $stmt = $conn->prepare('DELETE FROM icip_community WHERE id = ?');
                $stmt->execute([$id]);
            }
            $this->getUser()->setFlash('notice', 'Community deleted successfully.');
        }

        $this->redirect('icip/communities');
    }

    // ========================================
    // CONSENT MANAGEMENT
    // ========================================

    /**
     * List consent records
     */
    public function executeConsentList($request)
    {
        $query = DB::table('icip_consent as c')
            ->leftJoin('icip_community as com', 'c.community_id', '=', 'com.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('c.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 'c.information_object_id', '=', 's.object_id')
            ->select([
                'c.*',
                'com.name as community_name',
                'ioi.title as object_title',
                's.slug',
            ]);

        // Filters
        if ($status = $request->getParameter('status')) {
            $query->where('c.consent_status', $status);
        }
        if ($community = $request->getParameter('community_id')) {
            $query->where('c.community_id', $community);
        }

        $this->consents = $query->orderBy('c.created_at', 'desc')->get()->toArray();
        $this->statusOptions = ahgICIPService::getConsentStatusOptions();
        $this->communities = DB::table('icip_community')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get()
            ->toArray();
        $this->filters = [
            'status' => $status,
            'community_id' => $community,
        ];
    }

    /**
     * Add/Edit consent record
     */
    public function executeConsentEdit($request)
    {
        $this->id = $request->getParameter('id');
        $this->objectId = $request->getParameter('object_id');
        $this->consent = null;

        $this->statusOptions = ahgICIPService::getConsentStatusOptions();
        $this->scopeOptions = ahgICIPService::getConsentScopeOptions();
        $this->communities = DB::table('icip_community')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get()
            ->toArray();

        if ($this->id) {
            $this->consent = DB::table('icip_consent')
                ->where('id', $this->id)
                ->first();

            if (!$this->consent) {
                $this->forward404('Consent record not found');
            }
            $this->objectId = $this->consent->information_object_id;
        }

        // Get object info if we have an object ID
        $this->object = null;
        if ($this->objectId) {
            $this->object = DB::table('information_object as io')
                ->leftJoin('information_object_i18n as ioi', function ($join) {
                    $join->on('io.id', '=', 'ioi.id')
                        ->where('ioi.culture', '=', 'en');
                })
                ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
                ->where('io.id', $this->objectId)
                ->select(['io.id', 'io.identifier', 'ioi.title', 's.slug'])
                ->first();
        }

        if ($request->isMethod('post')) {
            $scopeArray = $request->getParameter('consent_scope', []);
            $data = [
                'information_object_id' => $request->getParameter('information_object_id'),
                'community_id' => $request->getParameter('community_id') ?: null,
                'consent_status' => $request->getParameter('consent_status'),
                'consent_scope' => !empty($scopeArray) ? json_encode($scopeArray) : null,
                'consent_date' => $request->getParameter('consent_date') ?: null,
                'consent_expiry_date' => $request->getParameter('consent_expiry_date') ?: null,
                'consent_granted_by' => $request->getParameter('consent_granted_by'),
                'consent_document_path' => $request->getParameter('consent_document_path'),
                'conditions' => $request->getParameter('conditions'),
                'restrictions' => $request->getParameter('restrictions'),
                'notes' => $request->getParameter('notes'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($this->id) {
                DB::table('icip_consent')
                    ->where('id', $this->id)
                    ->update($data);
            } else {
                $data['created_by'] = $this->getUser()->getAttribute('user_id');
                $data['created_at'] = date('Y-m-d H:i:s');
                $this->id = DB::table('icip_consent')->insertGetId($data);
            }

            // Update object summary
            ahgICIPService::updateObjectSummary($data['information_object_id']);

            $this->getUser()->setFlash('notice', 'Consent record saved successfully.');

            // Redirect back to object or list
            if ($this->object) {
                $this->redirect($this->object->slug . '/icip');
            }
            $this->redirect('icip/consent');
        }
    }

    /**
     * View consent details
     */
    public function executeConsentView($request)
    {
        $this->id = $request->getParameter('id');
        $this->consent = DB::table('icip_consent as c')
            ->leftJoin('icip_community as com', 'c.community_id', '=', 'com.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('c.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 'c.information_object_id', '=', 's.object_id')
            ->where('c.id', $this->id)
            ->select([
                'c.*',
                'com.name as community_name',
                'ioi.title as object_title',
                's.slug',
            ])
            ->first();

        if (!$this->consent) {
            $this->forward404('Consent record not found');
        }

        $this->statusOptions = ahgICIPService::getConsentStatusOptions();
        $this->scopeOptions = ahgICIPService::getConsentScopeOptions();
    }

    // ========================================
    // CONSULTATIONS
    // ========================================

    /**
     * List consultations
     */
    public function executeConsultations($request)
    {
        $query = DB::table('icip_consultation as c')
            ->join('icip_community as com', 'c.community_id', '=', 'com.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('c.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->select([
                'c.*',
                'com.name as community_name',
                'ioi.title as object_title',
            ]);

        // Filters
        if ($type = $request->getParameter('type')) {
            $query->where('c.consultation_type', $type);
        }
        if ($community = $request->getParameter('community_id')) {
            $query->where('c.community_id', $community);
        }
        if ($status = $request->getParameter('status')) {
            $query->where('c.status', $status);
        }

        $this->consultations = $query->orderBy('c.consultation_date', 'desc')->get()->toArray();
        $this->communities = DB::table('icip_community')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get()
            ->toArray();
        $this->filters = [
            'type' => $type,
            'community_id' => $community,
            'status' => $status,
        ];
    }

    /**
     * Add/Edit consultation
     */
    public function executeConsultationEdit($request)
    {
        $this->id = $request->getParameter('id');
        $this->objectId = $request->getParameter('object_id');
        $this->consultation = null;

        $this->communities = DB::table('icip_community')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get()
            ->toArray();

        $this->consultationTypes = [
            'initial_contact' => 'Initial Contact',
            'consent_request' => 'Consent Request',
            'access_request' => 'Access Request',
            'repatriation' => 'Repatriation',
            'digitisation' => 'Digitisation',
            'exhibition' => 'Exhibition',
            'publication' => 'Publication',
            'research' => 'Research',
            'general' => 'General',
            'follow_up' => 'Follow Up',
        ];

        $this->consultationMethods = [
            'in_person' => 'In Person',
            'phone' => 'Phone',
            'video' => 'Video Conference',
            'email' => 'Email',
            'letter' => 'Letter',
            'other' => 'Other',
        ];

        $this->statusOptions = [
            'scheduled' => 'Scheduled',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'follow_up_required' => 'Follow Up Required',
        ];

        if ($this->id) {
            $this->consultation = DB::table('icip_consultation')
                ->where('id', $this->id)
                ->first();

            if (!$this->consultation) {
                $this->forward404('Consultation not found');
            }
            $this->objectId = $this->consultation->information_object_id;
        }

        // Get object info if available
        $this->object = null;
        if ($this->objectId) {
            $this->object = DB::table('information_object as io')
                ->leftJoin('information_object_i18n as ioi', function ($join) {
                    $join->on('io.id', '=', 'ioi.id')
                        ->where('ioi.culture', '=', 'en');
                })
                ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
                ->where('io.id', $this->objectId)
                ->select(['io.id', 'io.identifier', 'ioi.title', 's.slug'])
                ->first();
        }

        if ($request->isMethod('post')) {
            $data = [
                'information_object_id' => $request->getParameter('information_object_id') ?: null,
                'community_id' => $request->getParameter('community_id'),
                'consultation_type' => $request->getParameter('consultation_type'),
                'consultation_date' => $request->getParameter('consultation_date'),
                'consultation_method' => $request->getParameter('consultation_method'),
                'location' => $request->getParameter('location'),
                'attendees' => $request->getParameter('attendees'),
                'community_representatives' => $request->getParameter('community_representatives'),
                'institution_representatives' => $request->getParameter('institution_representatives'),
                'summary' => $request->getParameter('summary'),
                'outcomes' => $request->getParameter('outcomes'),
                'follow_up_date' => $request->getParameter('follow_up_date') ?: null,
                'follow_up_notes' => $request->getParameter('follow_up_notes'),
                'is_confidential' => $request->getParameter('is_confidential', 0) ? 1 : 0,
                'status' => $request->getParameter('status'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($this->id) {
                DB::table('icip_consultation')
                    ->where('id', $this->id)
                    ->update($data);
            } else {
                $data['created_by'] = $this->getUser()->getAttribute('user_id');
                $data['created_at'] = date('Y-m-d H:i:s');
                $this->id = DB::table('icip_consultation')->insertGetId($data);
            }

            // Update object summary if linked
            if ($data['information_object_id']) {
                ahgICIPService::updateObjectSummary($data['information_object_id']);
            }

            $this->getUser()->setFlash('notice', 'Consultation saved successfully.');
            $this->redirect('icip/consultations');
        }
    }

    /**
     * View consultation details
     */
    public function executeConsultationView($request)
    {
        $this->id = $request->getParameter('id');
        $this->consultation = DB::table('icip_consultation as c')
            ->join('icip_community as com', 'c.community_id', '=', 'com.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('c.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 'c.information_object_id', '=', 's.object_id')
            ->where('c.id', $this->id)
            ->select([
                'c.*',
                'com.name as community_name',
                'ioi.title as object_title',
                's.slug',
            ])
            ->first();

        if (!$this->consultation) {
            $this->forward404('Consultation not found');
        }
    }

    // ========================================
    // TK LABELS
    // ========================================

    /**
     * Manage TK Labels
     */
    public function executeTkLabels($request)
    {
        // Get label types
        $this->labelTypes = DB::table('icip_tk_label_type')
            ->orderBy('category')
            ->orderBy('display_order')
            ->get()
            ->toArray();

        // Get applied labels with stats
        $this->appliedLabels = DB::table('icip_tk_label as l')
            ->join('icip_tk_label_type as t', 'l.label_type_id', '=', 't.id')
            ->select([
                't.code',
                't.name',
                't.category',
                DB::raw('COUNT(*) as usage_count'),
            ])
            ->groupBy('t.code', 't.name', 't.category')
            ->orderBy('usage_count', 'desc')
            ->get()
            ->toArray();

        // Recent label applications
        $this->recentLabels = DB::table('icip_tk_label as l')
            ->join('icip_tk_label_type as t', 'l.label_type_id', '=', 't.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('l.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 'l.information_object_id', '=', 's.object_id')
            ->leftJoin('icip_community as c', 'l.community_id', '=', 'c.id')
            ->select([
                'l.*',
                't.code as label_code',
                't.name as label_name',
                't.category',
                'ioi.title as object_title',
                's.slug',
                'c.name as community_name',
            ])
            ->orderBy('l.created_at', 'desc')
            ->limit(20)
            ->get()
            ->toArray();
    }

    // ========================================
    // CULTURAL NOTICES
    // ========================================

    /**
     * Manage Cultural Notices
     */
    public function executeNotices($request)
    {
        // Get notice types
        $this->noticeTypes = DB::table('icip_cultural_notice_type')
            ->orderBy('display_order')
            ->get()
            ->toArray();

        // Get applied notices
        $this->appliedNotices = DB::table('icip_cultural_notice as n')
            ->join('icip_cultural_notice_type as t', 'n.notice_type_id', '=', 't.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('n.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 'n.information_object_id', '=', 's.object_id')
            ->leftJoin('icip_community as c', 'n.community_id', '=', 'c.id')
            ->select([
                'n.*',
                't.code as notice_code',
                't.name as notice_name',
                't.severity',
                'ioi.title as object_title',
                's.slug',
                'c.name as community_name',
            ])
            ->orderBy('n.created_at', 'desc')
            ->limit(50)
            ->get()
            ->toArray();
    }

    /**
     * Manage Notice Types
     */
    public function executeNoticeTypes($request)
    {
        $this->noticeTypes = DB::table('icip_cultural_notice_type')
            ->orderBy('display_order')
            ->get()
            ->toArray();

        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action');
            $typeId = $request->getParameter('type_id');

            if ($action === 'add') {
                DB::table('icip_cultural_notice_type')->insert([
                    'code' => $request->getParameter('code'),
                    'name' => $request->getParameter('name'),
                    'description' => $request->getParameter('description'),
                    'default_text' => $request->getParameter('default_text'),
                    'severity' => $request->getParameter('severity', 'info'),
                    'requires_acknowledgement' => $request->getParameter('requires_acknowledgement', 0) ? 1 : 0,
                    'blocks_access' => $request->getParameter('blocks_access', 0) ? 1 : 0,
                    'display_public' => $request->getParameter('display_public', 1) ? 1 : 0,
                    'display_staff' => $request->getParameter('display_staff', 1) ? 1 : 0,
                    'display_order' => $request->getParameter('display_order', 100),
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $this->getUser()->setFlash('notice', 'Notice type added.');
            } elseif ($action === 'toggle' && $typeId) {
                $current = DB::table('icip_cultural_notice_type')
                    ->where('id', $typeId)
                    ->value('is_active');
                DB::table('icip_cultural_notice_type')
                    ->where('id', $typeId)
                    ->update(['is_active' => $current ? 0 : 1]);
            }

            $this->redirect('icip/notice-types');
        }
    }

    // ========================================
    // ACCESS RESTRICTIONS
    // ========================================

    /**
     * Manage Access Restrictions
     */
    public function executeRestrictions($request)
    {
        $this->restrictionTypes = ahgICIPService::getRestrictionTypes();

        $this->restrictions = DB::table('icip_access_restriction as r')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('r.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 'r.information_object_id', '=', 's.object_id')
            ->leftJoin('icip_community as c', 'r.community_id', '=', 'c.id')
            ->select([
                'r.*',
                'ioi.title as object_title',
                's.slug',
                'c.name as community_name',
            ])
            ->orderBy('r.created_at', 'desc')
            ->get()
            ->toArray();
    }

    // ========================================
    // REPORTS
    // ========================================

    /**
     * Reports overview
     */
    public function executeReports($request)
    {
        $this->stats = ahgICIPService::getDashboardStats();

        // Get consent summary by status
        $this->consentByStatus = DB::table('icip_consent')
            ->select('consent_status', DB::raw('COUNT(*) as count'))
            ->groupBy('consent_status')
            ->get()
            ->toArray();

        // Get records by community
        $this->recordsByCommunity = DB::table('icip_consent as c')
            ->join('icip_community as com', 'c.community_id', '=', 'com.id')
            ->select([
                'com.id',
                'com.name',
                'com.state_territory',
                DB::raw('COUNT(*) as record_count'),
            ])
            ->groupBy('com.id', 'com.name', 'com.state_territory')
            ->orderBy('record_count', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Pending consultations report
     */
    public function executeReportPending($request)
    {
        $this->records = ahgICIPService::getPendingConsultation(200);
        $this->statusOptions = ahgICIPService::getConsentStatusOptions();
    }

    /**
     * Expiring consents report
     */
    public function executeReportExpiry($request)
    {
        $days = (int) $request->getParameter('days', 90);
        $this->days = $days;
        $this->records = ahgICIPService::getExpiringConsents($days);
    }

    /**
     * Community-specific report
     */
    public function executeReportCommunity($request)
    {
        $this->id = $request->getParameter('id');
        $this->community = DB::table('icip_community')
            ->where('id', $this->id)
            ->first();

        if (!$this->community) {
            $this->forward404('Community not found');
        }

        // Get all related data
        $this->consents = DB::table('icip_consent as c')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('c.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 'c.information_object_id', '=', 's.object_id')
            ->where('c.community_id', $this->id)
            ->select(['c.*', 'ioi.title as object_title', 's.slug'])
            ->orderBy('c.created_at', 'desc')
            ->get()
            ->toArray();

        $this->consultations = DB::table('icip_consultation')
            ->where('community_id', $this->id)
            ->orderBy('consultation_date', 'desc')
            ->get()
            ->toArray();

        $this->notices = DB::table('icip_cultural_notice as n')
            ->join('icip_cultural_notice_type as t', 'n.notice_type_id', '=', 't.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('n.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->where('n.community_id', $this->id)
            ->select(['n.*', 't.name as notice_name', 'ioi.title as object_title'])
            ->get()
            ->toArray();

        $this->labels = DB::table('icip_tk_label as l')
            ->join('icip_tk_label_type as t', 'l.label_type_id', '=', 't.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('l.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->where('l.community_id', $this->id)
            ->select(['l.*', 't.name as label_name', 't.code', 'ioi.title as object_title'])
            ->get()
            ->toArray();

        $this->statusOptions = ahgICIPService::getConsentStatusOptions();
        $this->states = ahgICIPService::getStateTerritories();
    }

    // ========================================
    // OBJECT-SPECIFIC ICIP
    // ========================================

    /**
     * ICIP overview for a specific object
     */
    public function executeObjectIcip($request)
    {
        $slug = $request->getParameter('slug');
        $this->object = $this->getObjectBySlug($slug);

        if (!$this->object) {
            $this->forward404('Record not found');
        }

        $this->summary = ahgICIPService::getObjectSummary($this->object->id);
        $this->consents = ahgICIPService::getObjectConsent($this->object->id);
        $this->notices = ahgICIPService::getObjectNotices($this->object->id);
        $this->labels = ahgICIPService::getObjectTKLabels($this->object->id);
        $this->restrictions = ahgICIPService::getObjectRestrictions($this->object->id);
        $this->consultations = ahgICIPService::getObjectConsultations($this->object->id);

        $this->statusOptions = ahgICIPService::getConsentStatusOptions();
        $this->scopeOptions = ahgICIPService::getConsentScopeOptions();
        $this->restrictionTypes = ahgICIPService::getRestrictionTypes();
    }

    /**
     * Manage consent for an object
     */
    public function executeObjectConsent($request)
    {
        $slug = $request->getParameter('slug');
        $this->object = $this->getObjectBySlug($slug);

        if (!$this->object) {
            $this->forward404('Record not found');
        }

        $this->statusOptions = ahgICIPService::getConsentStatusOptions();
        $this->scopeOptions = ahgICIPService::getConsentScopeOptions();
        $this->communities = DB::table('icip_community')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get()
            ->toArray();

        $this->consents = ahgICIPService::getObjectConsent($this->object->id);

        if ($request->isMethod('post')) {
            $scopeArray = $request->getParameter('consent_scope', []);
            $data = [
                'information_object_id' => $this->object->id,
                'community_id' => $request->getParameter('community_id') ?: null,
                'consent_status' => $request->getParameter('consent_status'),
                'consent_scope' => !empty($scopeArray) ? json_encode($scopeArray) : null,
                'consent_date' => $request->getParameter('consent_date') ?: null,
                'consent_expiry_date' => $request->getParameter('consent_expiry_date') ?: null,
                'consent_granted_by' => $request->getParameter('consent_granted_by'),
                'conditions' => $request->getParameter('conditions'),
                'notes' => $request->getParameter('notes'),
                'created_by' => $this->getUser()->getAttribute('user_id'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            DB::table('icip_consent')->insert($data);
            ahgICIPService::updateObjectSummary($this->object->id);

            $this->getUser()->setFlash('notice', 'Consent record added.');
            $this->redirect($slug . '/icip');
        }
    }

    /**
     * Manage notices for an object
     */
    public function executeObjectNotices($request)
    {
        $slug = $request->getParameter('slug');
        $this->object = $this->getObjectBySlug($slug);

        if (!$this->object) {
            $this->forward404('Record not found');
        }

        $this->noticeTypes = DB::table('icip_cultural_notice_type')
            ->where('is_active', 1)
            ->orderBy('display_order')
            ->get()
            ->toArray();

        $this->communities = DB::table('icip_community')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get()
            ->toArray();

        $this->notices = ahgICIPService::getObjectNotices($this->object->id);

        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action');

            if ($action === 'add') {
                DB::table('icip_cultural_notice')->insert([
                    'information_object_id' => $this->object->id,
                    'notice_type_id' => $request->getParameter('notice_type_id'),
                    'custom_text' => $request->getParameter('custom_text') ?: null,
                    'community_id' => $request->getParameter('community_id') ?: null,
                    'applies_to_descendants' => $request->getParameter('applies_to_descendants', 1) ? 1 : 0,
                    'start_date' => $request->getParameter('start_date') ?: null,
                    'end_date' => $request->getParameter('end_date') ?: null,
                    'notes' => $request->getParameter('notes'),
                    'created_by' => $this->getUser()->getAttribute('user_id'),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $this->getUser()->setFlash('notice', 'Cultural notice added.');
            } elseif ($action === 'remove') {
                if (class_exists('\\Illuminate\\Database\\Capsule\\Manager')) {
                    DB::table('icip_cultural_notice')
                        ->where('id', $request->getParameter('notice_id'))
                        ->where('information_object_id', $this->object->id)
                        ->delete();
                } else {
                    $conn = \Propel::getConnection();
                    $stmt = $conn->prepare('DELETE FROM icip_cultural_notice WHERE id = ? AND information_object_id = ?');
                    $stmt->execute([$request->getParameter('notice_id'), $this->object->id]);
                }
                $this->getUser()->setFlash('notice', 'Cultural notice removed.');
            }

            ahgICIPService::updateObjectSummary($this->object->id);
            $this->redirect($slug . '/icip/notices');
        }
    }

    /**
     * Manage TK labels for an object
     */
    public function executeObjectLabels($request)
    {
        $slug = $request->getParameter('slug');
        $this->object = $this->getObjectBySlug($slug);

        if (!$this->object) {
            $this->forward404('Record not found');
        }

        $this->labelTypes = DB::table('icip_tk_label_type')
            ->where('is_active', 1)
            ->orderBy('category')
            ->orderBy('display_order')
            ->get()
            ->toArray();

        $this->communities = DB::table('icip_community')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get()
            ->toArray();

        $this->labels = ahgICIPService::getObjectTKLabels($this->object->id);

        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action');

            if ($action === 'add') {
                DB::table('icip_tk_label')->insertOrIgnore([
                    'information_object_id' => $this->object->id,
                    'label_type_id' => $request->getParameter('label_type_id'),
                    'community_id' => $request->getParameter('community_id') ?: null,
                    'applied_by' => $request->getParameter('applied_by', 'institution'),
                    'local_contexts_project_id' => $request->getParameter('local_contexts_project_id'),
                    'notes' => $request->getParameter('notes'),
                    'created_by' => $this->getUser()->getAttribute('user_id'),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $this->getUser()->setFlash('notice', 'TK Label added.');
            } elseif ($action === 'remove') {
                if (class_exists('\\Illuminate\\Database\\Capsule\\Manager')) {
                    DB::table('icip_tk_label')
                        ->where('id', $request->getParameter('label_id'))
                        ->where('information_object_id', $this->object->id)
                        ->delete();
                } else {
                    $conn = \Propel::getConnection();
                    $stmt = $conn->prepare('DELETE FROM icip_tk_label WHERE id = ? AND information_object_id = ?');
                    $stmt->execute([$request->getParameter('label_id'), $this->object->id]);
                }
                $this->getUser()->setFlash('notice', 'TK Label removed.');
            }

            ahgICIPService::updateObjectSummary($this->object->id);
            $this->redirect($slug . '/icip/labels');
        }
    }

    /**
     * Manage restrictions for an object
     */
    public function executeObjectRestrictions($request)
    {
        $slug = $request->getParameter('slug');
        $this->object = $this->getObjectBySlug($slug);

        if (!$this->object) {
            $this->forward404('Record not found');
        }

        $this->restrictionTypes = ahgICIPService::getRestrictionTypes();

        $this->communities = DB::table('icip_community')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get()
            ->toArray();

        $this->restrictions = ahgICIPService::getObjectRestrictions($this->object->id);

        if ($request->isMethod('post')) {
            $action = $request->getParameter('form_action');

            if ($action === 'add') {
                DB::table('icip_access_restriction')->insert([
                    'information_object_id' => $this->object->id,
                    'restriction_type' => $request->getParameter('restriction_type'),
                    'community_id' => $request->getParameter('community_id') ?: null,
                    'start_date' => $request->getParameter('start_date') ?: null,
                    'end_date' => $request->getParameter('end_date') ?: null,
                    'applies_to_descendants' => $request->getParameter('applies_to_descendants', 1) ? 1 : 0,
                    'override_security_clearance' => $request->getParameter('override_security_clearance', 1) ? 1 : 0,
                    'custom_restriction_text' => $request->getParameter('custom_restriction_text'),
                    'notes' => $request->getParameter('notes'),
                    'created_by' => $this->getUser()->getAttribute('user_id'),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $this->getUser()->setFlash('notice', 'Restriction added.');
            } elseif ($action === 'remove') {
                if (class_exists('\\Illuminate\\Database\\Capsule\\Manager')) {
                    DB::table('icip_access_restriction')
                        ->where('id', $request->getParameter('restriction_id'))
                        ->where('information_object_id', $this->object->id)
                        ->delete();
                } else {
                    $conn = \Propel::getConnection();
                    $stmt = $conn->prepare('DELETE FROM icip_access_restriction WHERE id = ? AND information_object_id = ?');
                    $stmt->execute([$request->getParameter('restriction_id'), $this->object->id]);
                }
                $this->getUser()->setFlash('notice', 'Restriction removed.');
            }

            ahgICIPService::updateObjectSummary($this->object->id);
            $this->redirect($slug . '/icip/restrictions');
        }
    }

    /**
     * Manage consultations for an object
     */
    public function executeObjectConsultations($request)
    {
        $slug = $request->getParameter('slug');
        $this->object = $this->getObjectBySlug($slug);

        if (!$this->object) {
            $this->forward404('Record not found');
        }

        $this->communities = DB::table('icip_community')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get()
            ->toArray();

        $this->consultations = ahgICIPService::getObjectConsultations($this->object->id);

        $this->consultationTypes = [
            'initial_contact' => 'Initial Contact',
            'consent_request' => 'Consent Request',
            'access_request' => 'Access Request',
            'repatriation' => 'Repatriation',
            'digitisation' => 'Digitisation',
            'exhibition' => 'Exhibition',
            'publication' => 'Publication',
            'research' => 'Research',
            'general' => 'General',
            'follow_up' => 'Follow Up',
        ];

        $this->consultationMethods = [
            'in_person' => 'In Person',
            'phone' => 'Phone',
            'video' => 'Video Conference',
            'email' => 'Email',
            'letter' => 'Letter',
            'other' => 'Other',
        ];
    }

    // ========================================
    // ACKNOWLEDGEMENT
    // ========================================

    /**
     * Record user acknowledgement of a notice
     */
    public function executeAcknowledge($request)
    {
        $noticeId = $request->getParameter('notice_id');
        $userId = $this->getUser()->getAttribute('user_id');

        if (!$userId) {
            return $this->renderJson(['success' => false, 'error' => 'Not authenticated']);
        }

        $notice = DB::table('icip_cultural_notice')->where('id', $noticeId)->first();
        if (!$notice) {
            return $this->renderJson(['success' => false, 'error' => 'Notice not found']);
        }

        $success = ahgICIPService::recordAcknowledgement($noticeId, $userId);

        if ($request->isXmlHttpRequest()) {
            return $this->renderJson(['success' => $success]);
        }

        // Redirect back
        $referer = $request->getReferer();
        $this->redirect($referer ?: '@homepage');
    }

    // ========================================
    // API ENDPOINTS
    // ========================================

    /**
     * API: Get ICIP summary for an object
     */
    public function executeApiSummary($request)
    {
        $objectId = (int) $request->getParameter('object_id');

        $summary = ahgICIPService::getObjectSummary($objectId);
        $data = [
            'object_id' => $objectId,
            'has_icip_content' => $summary ? (bool) $summary->has_icip_content : false,
            'consent_status' => $summary ? $summary->consent_status : null,
            'has_cultural_notices' => $summary ? (bool) $summary->has_cultural_notices : false,
            'has_tk_labels' => $summary ? (bool) $summary->has_tk_labels : false,
            'has_restrictions' => $summary ? (bool) $summary->has_restrictions : false,
            'requires_acknowledgement' => $summary ? (bool) $summary->requires_acknowledgement : false,
            'blocks_access' => $summary ? (bool) $summary->blocks_access : false,
        ];

        return $this->renderJson($data);
    }

    /**
     * API: Check access for an object
     */
    public function executeApiCheckAccess($request)
    {
        $objectId = (int) $request->getParameter('object_id');
        $userId = $this->getUser()->getAttribute('user_id');

        $access = ahgICIPService::checkAccess($objectId, $userId);

        return $this->renderJson($access);
    }

    // ========================================
    // HELPERS
    // ========================================

    /**
     * Get information object by slug
     */
    protected function getObjectBySlug(string $slug): ?object
    {
        return DB::table('information_object as io')
            ->join('slug as s', 'io.id', '=', 's.object_id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->where('s.slug', $slug)
            ->select([
                'io.id',
                'io.identifier',
                'io.level_of_description_id',
                'ioi.title',
                's.slug',
            ])
            ->first();
    }

    /**
     * Render JSON response
     */
    protected function renderJson(array $data): string
    {
        $this->getResponse()->setContentType('application/json');
        return $this->renderText(json_encode($data));
    }
}
