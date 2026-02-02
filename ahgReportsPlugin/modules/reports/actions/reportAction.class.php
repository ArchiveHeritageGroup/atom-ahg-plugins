<?php
use AtomExtensions\Services\AclService;
/**
 * Centralized Report Action
 * 
 * Reads report configuration from report_definition table
 * Dynamically generates reports based on code parameter
 */
class reportsreportAction extends sfAction
{
    protected $db;
    protected $reportDef;
    protected $filters = [];
    protected $results = [];
    protected $pager;
    
    public function execute($request)
    {
        // Check permissions
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('user/login');
        }
        
        if (!$this->context->user->isAdministrator() 
            && !$this->context->user->hasCredential('contributor')) {
            AclService::forwardUnauthorized();
        }
        
        // Initialize database
        $this->initDb();
        
        // Get report code from URL
        $code = $request->getParameter('code');
        if (empty($code)) {
            $this->forward404('Report code required');
        }
        
        // Load report definition
        $this->reportDef = $this->db->table('report_definition')
            ->where('code', $code)
            ->where('is_active', 1)
            ->first();
            
        if (!$this->reportDef) {
            $this->forward404('Report not found: ' . $code);
        }
        
        // Parse filters from request
        $this->parseFilters($request);
        
        // Execute report query
        $this->executeReport($request);
        
        // Handle export
        $format = $request->getParameter('format', 'html');
        if ($format !== 'html') {
            return $this->exportReport($format);
        }
        
        // Pass to template
        $this->reportDef = $this->reportDef;
        $this->reportCode = $code;
        $this->reportName = $this->reportDef->name;
        $this->reportDescription = $this->reportDef->description;
        $this->reportCategory = $this->reportDef->category;
        $this->parameters = json_decode($this->reportDef->parameters ?? '{}', true);
        $this->outputFormats = explode(',', $this->reportDef->output_formats);
        $this->filters = (array) $this->filters;
        $this->results = $this->results;
        $this->pager = $this->pager ?? null;
        
        // Load filter options
        $this->loadFilterOptions();
    }
    
    protected function initDb()
    {
        if (\AtomExtensions\Database\DatabaseBootstrap::getCapsule() === null) {
            \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
        }
        $this->db = \Illuminate\Database\Capsule\Manager::connection();
    }
    
    protected function parseFilters($request)
    {
        $params = json_decode($this->reportDef->parameters ?? '{}', true);
        foreach (array_keys($params) as $key) {
            $value = $request->getParameter($key);
            if (!empty($value)) {
                $this->filters[$key] = $value;
            }
        }
        
        // GLAM type filter (applies to all collection reports)
        $glamType = $request->getParameter('glam_type');
        if (!empty($glamType)) {
            $this->filters['glam_type'] = $glamType;
        }

        // Standard filters
        $this->filters['page'] = max(1, (int) $request->getParameter('page', 1));
        $this->filters['limit'] = min(100, max(10, (int) $request->getParameter('limit', 25)));
        $this->filters['sort'] = $request->getParameter('sort', 'id');
        $this->filters['dir'] = $request->getParameter('dir', 'desc');
    }
    
    protected function executeReport($request)
    {
        $code = $this->reportDef->code;
        $method = 'report' . str_replace('_', '', ucwords($code, '_'));
        
        if (method_exists($this, $method)) {
            $this->$method();
        } else {
            // Fallback to generic query based on category
            $this->genericReport();
        }
    }
    
    protected function genericReport()
    {
        $this->results = [];
        $this->pager = null;
    }
    
    // =========================================================================
    // COLLECTION REPORTS
    // =========================================================================
    
    protected function reportCollectionOverview()
    {
        $query = $this->db->table('information_object as io')
            ->join('object as o', 'io.id', '=', 'o.id')
            ->join('information_object_i18n as i18n', function($join) {
                $join->on('io.id', '=', 'i18n.id')
                     ->where('i18n.culture', '=', 'en');
            })
            ->leftJoin('term_i18n as level', function($join) {
                $join->on('io.level_of_description_id', '=', 'level.id')
                     ->where('level.culture', '=', 'en');
            })
            ->leftJoin('actor_i18n as repo', function($join) {
                $join->on('io.repository_id', '=', 'repo.id')
                     ->where('repo.culture', '=', 'en');
            })
            ->select(
                'io.id',
                'i18n.title',
                'io.identifier',
                'level.name as level_of_description',
                'repo.authorized_form_of_name as repository',
                'o.created_at',
                'o.updated_at'
            )
            ->where('io.id', '!=', 1);
        
        // Apply GLAM filter
        if (!empty($this->filters['glam_type'])) {
            $query->join('display_object_config as doc', 'io.id', '=', 'doc.object_id')
                  ->where('doc.object_type', $this->filters['glam_type']);
        }
        
        // Apply filters
        if (!empty($this->filters['repository_id'])) {
            $query->where('io.repository_id', $this->filters['repository_id']);
        }
        if (!empty($this->filters['level_of_description'])) {
            $query->where('io.level_of_description_id', $this->filters['level_of_description']);
        }
        if (!empty($this->filters['date_from'])) {
            $query->where('o.created_at', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->where('o.created_at', '<=', $this->filters['date_to'] . ' 23:59:59');
        }
        
        // Debug
        error_log('Collection Overview SQL: ' . $query->toSql());
        error_log('Bindings: ' . json_encode($query->getBindings()));
        
        $this->paginateQuery($query);
    }
    
    protected function reportCollectionGrowth()
    {
        $period = $this->filters['period'] ?? 'monthly';
        $dateFormat = $period === 'yearly' ? '%Y' : '%Y-%m';
        
        $query = $this->db->table('information_object as io')
            ->join('object as o', 'io.id', '=', 'o.id')
            ->select(
                $this->db->raw("DATE_FORMAT(o.created_at, '{$dateFormat}') as period"),
                $this->db->raw('COUNT(*) as count')
            )
            ->where('io.id', '!=', 1)
            ->groupBy('period')
            ->orderBy('period', 'desc');
        
        if (!empty($this->filters['date_from'])) {
            $query->where('created_at', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->where('created_at', '<=', $this->filters['date_to'] . ' 23:59:59');
        }
        
        $this->results = $query->get()->toArray();
    }
    
    protected function reportMetadataCompleteness()
    {
        $fields = [
            'title' => 'Title',
            'scope_and_content' => 'Scope & Content',
            'arrangement' => 'Arrangement',
            'access_conditions' => 'Access Conditions',
            'reproduction_conditions' => 'Reproduction Conditions',
            'physical_characteristics' => 'Physical Description',
            'finding_aids' => 'Finding Aids',
            'location_of_originals' => 'Location of Originals',
            'location_of_copies' => 'Location of Copies',
            'rules' => 'Rules',
            'sources' => 'Sources',
            'revision_history' => 'Revision History'
        ];
        
        $total = $this->db->table('information_object')->where('id', '!=', 1)->count();
        
        $results = [];
        foreach ($fields as $field => $label) {
            $count = $this->db->table('information_object_i18n')
                ->where('culture', 'en')
                ->whereNotNull($field)
                ->where($field, '!=', '')
                ->count();
            
            $results[] = (object)[
                'field' => $label,
                'completed' => $count,
                'total' => $total,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0
            ];
        }
        
        $this->results = $results;
    }
    
    protected function reportDigitalObjects()
    {
        $query = $this->db->table('digital_object as do')
            ->join('information_object as io', 'do.object_id', '=', 'io.id')
            ->join('information_object_i18n as i18n', function($join) {
                $join->on('io.id', '=', 'i18n.id')
                     ->where('i18n.culture', '=', 'en');
            })
            ->leftJoin('term_i18n as media', function($join) {
                $join->on('do.media_type_id', '=', 'media.id')
                     ->where('media.culture', '=', 'en');
            })
            ->select(
                'do.id',
                'i18n.title',
                'do.name as filename',
                'do.mime_type',
                'media.name as media_type',
                'do.byte_size',
                'do.created_at'
            );
        
        if (!empty($this->filters['media_type'])) {
            $query->where('do.media_type_id', $this->filters['media_type']);
        }
        if (!empty($this->filters['repository_id'])) {
            $query->where('io.repository_id', $this->filters['repository_id']);
        }
        
        $this->paginateQuery($query);
    }
    
    protected function reportByCreator()
    {
        $query = $this->db->table('event as e')
            ->join('actor_i18n as a', function($join) {
                $join->on('e.actor_id', '=', 'a.id')
                     ->where('a.culture', '=', 'en');
            })
            ->select(
                'e.actor_id',
                'a.authorized_form_of_name as creator',
                $this->db->raw('COUNT(DISTINCT e.object_id) as record_count')
            )
            ->where('e.type_id', 111) // Creation event
            ->groupBy('e.actor_id', 'a.authorized_form_of_name')
            ->orderBy('record_count', 'desc');
        
        $this->results = $query->limit(100)->get()->toArray();
    }
    
    // =========================================================================
    // ACQUISITION REPORTS
    // =========================================================================
    
    protected function reportAccessionsByDonor()
    {
        $query = $this->db->table('accession as a')
            ->leftJoin('donor as d', 'a.id', '=', 'd.id')
            ->leftJoin('actor_i18n as donor_name', function($join) {
                $join->on('d.id', '=', 'donor_name.id')
                     ->where('donor_name.culture', '=', 'en');
            })
            ->select(
                'd.id as donor_id',
                'donor_name.authorized_form_of_name as donor_name',
                $this->db->raw('COUNT(a.id) as accession_count'),
                $this->db->raw('MIN(a.date) as first_accession'),
                $this->db->raw('MAX(a.date) as last_accession')
            )
            ->groupBy('d.id', 'donor_name.authorized_form_of_name')
            ->orderBy('accession_count', 'desc');
        
        if (!empty($this->filters['date_from'])) {
            $query->where('a.date', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->where('a.date', '<=', $this->filters['date_to']);
        }
        
        $this->results = $query->get()->toArray();
    }
    
    protected function reportDeaccessions()
    {
        $query = $this->db->table('deaccession as d')
            ->join('information_object_i18n as i18n', function($join) {
                $join->on('d.object_id', '=', 'i18n.id')
                     ->where('i18n.culture', '=', 'en');
            })
            ->select(
                'd.id',
                'i18n.title',
                'd.date',
                'd.description',
                'd.scope',
                'd.reason'
            );
        
        if (!empty($this->filters['date_from'])) {
            $query->where('d.date', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->where('d.date', '<=', $this->filters['date_to']);
        }
        
        $this->paginateQuery($query);
    }
    
    protected function reportAcquisitionsValue()
    {
        $query = $this->db->table('accession as a')
            ->leftJoin('accession_i18n as ai', function($join) {
                $join->on('a.id', '=', 'ai.id')
                     ->where('ai.culture', '=', 'en');
            })
            ->select(
                'a.id',
                'a.identifier',
                'ai.title',
                'a.date',
                $this->db->raw("COALESCE(a.appraisal, 0) as value")
            )
            ->orderBy('a.date', 'desc');
        
        if (!empty($this->filters['date_from'])) {
            $query->where('a.date', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->where('a.date', '<=', $this->filters['date_to']);
        }
        
        $this->paginateQuery($query);
    }
    
    // =========================================================================
    // ACCESS REPORTS
    // =========================================================================
    
    protected function reportAccessStatistics()
    {
        // Check if access_log table exists
        $hasAccessLog = $this->db->getSchemaBuilder()->hasTable('access_log');
        
        if ($hasAccessLog) {
            $query = $this->db->table('access_log')
                ->select(
                    $this->db->raw('DATE(created_at) as date'),
                    $this->db->raw('COUNT(*) as views'),
                    $this->db->raw('COUNT(DISTINCT user_id) as unique_users'),
                    $this->db->raw("SUM(CASE WHEN action = 'download' THEN 1 ELSE 0 END) as downloads")
                )
                ->groupBy($this->db->raw('DATE(created_at)'))
                ->orderBy('date', 'desc');
            
            if (!empty($this->filters['date_from'])) {
                $query->where('created_at', '>=', $this->filters['date_from']);
            }
            if (!empty($this->filters['date_to'])) {
                $query->where('created_at', '<=', $this->filters['date_to'] . ' 23:59:59');
            }
            
            $this->results = $query->limit(90)->get()->toArray();
        } else {
            $this->results = [];
        }
    }
    
    protected function reportPopularRecords()
    {
        $limit = $this->filters['limit'] ?? 50;
        
        // Check if access_log exists
        $hasAccessLog = $this->db->getSchemaBuilder()->hasTable('access_log');
        
        if ($hasAccessLog) {
            $query = $this->db->table('access_log as al')
                ->join('information_object_i18n as i18n', function($join) {
                    $join->on('al.object_id', '=', 'i18n.id')
                         ->where('i18n.culture', '=', 'en');
                })
                ->select(
                    'al.object_id',
                    'i18n.title',
                    $this->db->raw('COUNT(*) as view_count')
                )
                ->where('al.object_type', 'information_object')
                ->groupBy('al.object_id', 'i18n.title')
                ->orderBy('view_count', 'desc')
                ->limit($limit);
            
            $this->results = $query->get()->toArray();
        } else {
            $this->results = [];
        }
    }
    
    protected function reportDownloads()
    {
        $hasAccessLog = $this->db->getSchemaBuilder()->hasTable('access_log');
        
        if ($hasAccessLog) {
            $query = $this->db->table('access_log as al')
                ->join('digital_object as do', 'al.object_id', '=', 'do.id')
                ->join('information_object_i18n as i18n', function($join) {
                    $join->on('do.object_id', '=', 'i18n.id')
                         ->where('i18n.culture', '=', 'en');
                })
                ->select(
                    'do.id',
                    'i18n.title',
                    'do.name as filename',
                    'do.mime_type',
                    $this->db->raw('COUNT(*) as download_count')
                )
                ->where('al.action', 'download')
                ->groupBy('do.id', 'i18n.title', 'do.name', 'do.mime_type')
                ->orderBy('download_count', 'desc');
            
            $this->results = $query->limit(100)->get()->toArray();
        } else {
            $this->results = [];
        }
    }
    
    protected function reportSearchAnalytics()
    {
        $hasSearchLog = $this->db->getSchemaBuilder()->hasTable('search_log');
        
        if ($hasSearchLog) {
            $query = $this->db->table('search_log')
                ->select(
                    'query',
                    $this->db->raw('COUNT(*) as search_count'),
                    $this->db->raw('AVG(results_count) as avg_results')
                )
                ->groupBy('query')
                ->orderBy('search_count', 'desc');
            
            if (!empty($this->filters['date_from'])) {
                $query->where('created_at', '>=', $this->filters['date_from']);
            }
            if (!empty($this->filters['date_to'])) {
                $query->where('created_at', '<=', $this->filters['date_to'] . ' 23:59:59');
            }
            
            $this->results = $query->limit(100)->get()->toArray();
        } else {
            $this->results = [];
        }
    }
    
    // =========================================================================
    // RESEARCHER REPORTS
    // =========================================================================
    
    protected function reportResearcherRegistrations()
    {
        $query = $this->db->table('researcher as r')
            ->join('user as u', 'r.user_id', '=', 'u.id')
            ->select(
                'r.id',
                'u.username',
                'u.email',
                'r.created_at',
                'r.status'
            )
            ->orderBy('r.created_at', 'desc');
        
        if (!empty($this->filters['date_from'])) {
            $query->where('r.created_at', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->where('r.created_at', '<=', $this->filters['date_to'] . ' 23:59:59');
        }
        
        // Check if researcher table exists
        if ($this->db->getSchemaBuilder()->hasTable('researcher')) {
            $this->paginateQuery($query);
        } else {
            $this->results = [];
        }
    }
    
    protected function reportResearcherActivity()
    {
        if ($this->db->getSchemaBuilder()->hasTable('researcher')) {
            $query = $this->db->table('researcher as r')
                ->join('user as u', 'r.user_id', '=', 'u.id')
                ->leftJoin('reading_room_visit as v', 'r.id', '=', 'v.researcher_id')
                ->select(
                    'r.id',
                    'u.username',
                    'u.email',
                    $this->db->raw('COUNT(v.id) as visit_count'),
                    $this->db->raw('MAX(v.visit_date) as last_visit')
                )
                ->groupBy('r.id', 'u.username', 'u.email')
                ->orderBy('visit_count', 'desc');
            
            $this->results = $query->limit(100)->get()->toArray();
        } else {
            $this->results = [];
        }
    }
    
    protected function reportReadingRoom()
    {
        if ($this->db->getSchemaBuilder()->hasTable('reading_room_visit')) {
            $query = $this->db->table('reading_room_visit as v')
                ->leftJoin('researcher as r', 'v.researcher_id', '=', 'r.id')
                ->leftJoin('user as u', 'r.user_id', '=', 'u.id')
                ->leftJoin('reading_room as rr', 'v.reading_room_id', '=', 'rr.id')
                ->select(
                    'v.id',
                    'u.username as researcher',
                    'rr.name as reading_room',
                    'v.visit_date',
                    'v.purpose',
                    'v.materials_consulted'
                )
                ->orderBy('v.visit_date', 'desc');
            
            if (!empty($this->filters['date_from'])) {
                $query->where('v.visit_date', '>=', $this->filters['date_from']);
            }
            if (!empty($this->filters['date_to'])) {
                $query->where('v.visit_date', '<=', $this->filters['date_to']);
            }
            
            $this->paginateQuery($query);
        } else {
            $this->results = [];
        }
    }
    
    protected function reportMaterialRequests()
    {
        if ($this->db->getSchemaBuilder()->hasTable('material_request')) {
            $query = $this->db->table('material_request as mr')
                ->leftJoin('researcher as r', 'mr.researcher_id', '=', 'r.id')
                ->leftJoin('user as u', 'r.user_id', '=', 'u.id')
                ->leftJoin('information_object_i18n as i18n', function($join) {
                    $join->on('mr.object_id', '=', 'i18n.id')
                         ->where('i18n.culture', '=', 'en');
                })
                ->select(
                    'mr.id',
                    'u.username as researcher',
                    'i18n.title as material',
                    'mr.request_date',
                    'mr.status',
                    'mr.notes'
                )
                ->orderBy('mr.request_date', 'desc');
            
            if (!empty($this->filters['status'])) {
                $query->where('mr.status', $this->filters['status']);
            }
            
            $this->paginateQuery($query);
        } else {
            $this->results = [];
        }
    }
    
    // =========================================================================
    // PRESERVATION REPORTS
    // =========================================================================
    
    protected function reportConditionOverview()
    {
        if ($this->db->getSchemaBuilder()->hasTable('condition_report')) {
            $query = $this->db->table('condition_report')
                ->select(
                    'overall_condition as condition',
                    $this->db->raw('COUNT(*) as count')
                )
                ->groupBy('overall_condition')
                ->orderBy('count', 'desc');
            
            $this->results = $query->get()->toArray();
            
            // Add summary stats
            $this->summary = [
                'total' => $this->db->table('condition_report')->count(),
                'urgent' => $this->db->table('condition_report')->where('priority', 'urgent')->count(),
                'in_treatment' => $this->db->table('condition_report')->where('status', 'in_treatment')->count()
            ];
        } else {
            $this->results = [];
            $this->summary = ['total' => 0, 'urgent' => 0, 'in_treatment' => 0];
        }
    }
    
    protected function reportConservationActions()
    {
        if ($this->db->getSchemaBuilder()->hasTable('conservation_treatment')) {
            $query = $this->db->table('conservation_treatment as ct')
                ->leftJoin('information_object_i18n as i18n', function($join) {
                    $join->on('ct.object_id', '=', 'i18n.id')
                         ->where('i18n.culture', '=', 'en');
                })
                ->select(
                    'ct.id',
                    'i18n.title',
                    'ct.treatment_type',
                    'ct.treatment_date',
                    'ct.conservator',
                    'ct.status'
                )
                ->orderBy('ct.treatment_date', 'desc');
            
            if (!empty($this->filters['date_from'])) {
                $query->where('ct.treatment_date', '>=', $this->filters['date_from']);
            }
            if (!empty($this->filters['date_to'])) {
                $query->where('ct.treatment_date', '<=', $this->filters['date_to']);
            }
            
            $this->paginateQuery($query);
        } else {
            $this->results = [];
        }
    }
    
    protected function reportPreservationPriorities()
    {
        if ($this->db->getSchemaBuilder()->hasTable('condition_report')) {
            $query = $this->db->table('condition_report as cr')
                ->leftJoin('information_object_i18n as i18n', function($join) {
                    $join->on('cr.object_id', '=', 'i18n.id')
                         ->where('i18n.culture', '=', 'en');
                })
                ->select(
                    'cr.id',
                    'i18n.title',
                    'cr.overall_condition',
                    'cr.priority',
                    'cr.recommended_action',
                    'cr.report_date'
                )
                ->whereIn('cr.priority', ['urgent', 'high'])
                ->orderByRaw("FIELD(cr.priority, 'urgent', 'high', 'medium', 'low')")
                ->orderBy('cr.report_date', 'desc');
            
            $this->paginateQuery($query);
        } else {
            $this->results = [];
        }
    }
    
    protected function reportFormatInventory()
    {
        $query = $this->db->table('digital_object')
            ->select(
                'mime_type',
                $this->db->raw('COUNT(*) as count'),
                $this->db->raw('SUM(byte_size) as total_size'),
                $this->db->raw('AVG(byte_size) as avg_size')
            )
            ->groupBy('mime_type')
            ->orderBy('count', 'desc');
        
        $this->results = $query->get()->toArray();
    }
    
    // =========================================================================
    // COMPLIANCE REPORTS
    // =========================================================================
    
    protected function reportAuditTrail()
    {
        if ($this->db->getSchemaBuilder()->hasTable('audit_log')) {
            $query = $this->db->table('audit_log as al')
                ->leftJoin('user as u', 'al.user_id', '=', 'u.id')
                ->select(
                    'al.id',
                    'u.username',
                    'al.action',
                    'al.object_type',
                    'al.object_id',
                    'al.changes',
                    'al.created_at'
                )
                ->orderBy('al.created_at', 'desc');
            
            if (!empty($this->filters['date_from'])) {
                $query->where('al.created_at', '>=', $this->filters['date_from']);
            }
            if (!empty($this->filters['date_to'])) {
                $query->where('al.created_at', '<=', $this->filters['date_to'] . ' 23:59:59');
            }
            if (!empty($this->filters['user_id'])) {
                $query->where('al.user_id', $this->filters['user_id']);
            }
            if (!empty($this->filters['action_type'])) {
                $query->where('al.action', $this->filters['action_type']);
            }
            
            $this->paginateQuery($query);
        } else {
            $this->results = [];
        }
    }
    
    protected function reportRightsExpiry()
    {
        $monthsAhead = $this->filters['months_ahead'] ?? 6;
        $expiryDate = date('Y-m-d', strtotime("+{$monthsAhead} months"));
        
        $query = $this->db->table('rights as r')
            ->join('information_object_i18n as i18n', function($join) {
                $join->on('r.object_id', '=', 'i18n.id')
                     ->where('i18n.culture', '=', 'en');
            })
            ->select(
                'r.id',
                'i18n.title',
                'r.end_date',
                'r.rights_holder',
                $this->db->raw("DATEDIFF(r.end_date, CURDATE()) as days_until_expiry")
            )
            ->whereNotNull('r.end_date')
            ->where('r.end_date', '<=', $expiryDate)
            ->where('r.end_date', '>=', date('Y-m-d'))
            ->orderBy('r.end_date', 'asc');
        
        $this->paginateQuery($query);
    }
    
    protected function reportPaiaRequests()
    {
        if ($this->db->getSchemaBuilder()->hasTable('access_request')) {
            $query = $this->db->table('access_request as ar')
                ->leftJoin('user as u', 'ar.user_id', '=', 'u.id')
                ->select(
                    'ar.id',
                    'u.username as requester',
                    'ar.request_type',
                    'ar.status',
                    'ar.created_at',
                    'ar.processed_at',
                    'ar.reason'
                )
                ->orderBy('ar.created_at', 'desc');
            
            if (!empty($this->filters['status'])) {
                $query->where('ar.status', $this->filters['status']);
            }
            if (!empty($this->filters['date_from'])) {
                $query->where('ar.created_at', '>=', $this->filters['date_from']);
            }
            if (!empty($this->filters['date_to'])) {
                $query->where('ar.created_at', '<=', $this->filters['date_to'] . ' 23:59:59');
            }
            
            $this->paginateQuery($query);
        } else {
            $this->results = [];
        }
    }
    
    protected function reportRetentionSchedule()
    {
        $monthsAhead = $this->filters['months_ahead'] ?? 12;
        $retentionDate = date('Y-m-d', strtotime("+{$monthsAhead} months"));
        
        if ($this->db->getSchemaBuilder()->hasColumn('information_object', 'retention_end_date')) {
            $query = $this->db->table('information_object as io')
                ->join('information_object_i18n as i18n', function($join) {
                    $join->on('io.id', '=', 'i18n.id')
                         ->where('i18n.culture', '=', 'en');
                })
                ->select(
                    'io.id',
                    'i18n.title',
                    'io.retention_end_date',
                    $this->db->raw("DATEDIFF(io.retention_end_date, CURDATE()) as days_until_retention")
                )
                ->whereNotNull('io.retention_end_date')
                ->where('io.retention_end_date', '<=', $retentionDate)
                ->orderBy('io.retention_end_date', 'asc');
            
            $this->paginateQuery($query);
        } else {
            $this->results = [];
        }
    }
    
    protected function reportSecurityClearance()
    {
        if ($this->db->getSchemaBuilder()->hasTable('security_classification')) {
            $query = $this->db->table('security_classification as sc')
                ->join('information_object_i18n as i18n', function($join) {
                    $join->on('sc.object_id', '=', 'i18n.id')
                         ->where('i18n.culture', '=', 'en');
                })
                ->select(
                    'sc.clearance_level',
                    $this->db->raw('COUNT(*) as count')
                )
                ->groupBy('sc.clearance_level')
                ->orderByRaw("FIELD(sc.clearance_level, 'top_secret', 'secret', 'confidential', 'restricted', 'public')");
            
            $this->results = $query->get()->toArray();
        } else {
            $this->results = [];
        }
    }
    
    // =========================================================================
    // HELPER METHODS
    // =========================================================================
    
    protected function paginateQuery($query)
    {
        $page = $this->filters['page'];
        $limit = $this->filters['limit'];
        $sort = $this->filters['sort'];
        $dir = $this->filters['dir'];
        
        // Get results first with sorting and pagination
        $results = $query
            ->orderBy($sort, $dir)
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();
        
        // Convert to array properly
        $this->results = array_map(function($item) {
            return (object)(array)$item;
        }, $results->all());
        
        // Estimate total from results (if less than limit, we're on last page)
        $resultCount = count($this->results);
        if ($resultCount < $limit) {
            $total = (($page - 1) * $limit) + $resultCount;
        } else {
            // Do a separate count query
            $total = $this->db->table('information_object')->where('id', '!=', 1)->count();
        }
        
        $this->pager = (object)[
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => $total > 0 ? ceil($total / $limit) : 1,
            'hasNext' => ($page * $limit) < $total,
            'hasPrev' => $page > 1
        ];
    }
    
    protected function loadFilterOptions()
    {
        // Repositories (name is in actor_i18n since repository extends actor)
        $this->repositories = $this->db->table('repository')
            ->join('actor_i18n', function($join) {
                $join->on('repository.id', '=', 'actor_i18n.id')
                     ->where('actor_i18n.culture', '=', 'en');
            })
            ->select('repository.id', 'actor_i18n.authorized_form_of_name as name')
            ->orderBy('name')
            ->get()
            ->toArray();
        
        // Levels of description
        $this->levels = $this->db->table('term')
            ->join('term_i18n', function($join) {
                $join->on('term.id', '=', 'term_i18n.id')
                     ->where('term_i18n.culture', '=', 'en');
            })
            ->where('term.taxonomy_id', 34)
            ->select('term.id', 'term_i18n.name')
            ->orderBy('term.lft')
            ->get()
            ->toArray();
        
        // Media types
        $this->mediaTypes = $this->db->table('term')
            ->join('term_i18n', function($join) {
                $join->on('term.id', '=', 'term_i18n.id')
                     ->where('term_i18n.culture', '=', 'en');
            })
            ->where('term.taxonomy_id', 46)
            ->select('term.id', 'term_i18n.name')
            ->orderBy('term_i18n.name')
            ->get()
            ->toArray();
        
        // GLAM Types
        $this->glamTypes = $this->db->table('display_object_config')
            ->select('object_type')
            ->distinct()
            ->orderBy('object_type')
            ->pluck('object_type')
            ->toArray();

        // Users (for audit reports)
        $this->users = $this->db->table('user')
            ->select('id', 'username')
            ->where('active', 1)
            ->orderBy('username')
            ->get()
            ->toArray();
    }
    
    protected function exportReport($format)
    {
        $filename = $this->reportDef->code . '_' . date('Y-m-d_His');
        
        switch ($format) {
            case 'csv':
                return $this->exportCsv($filename);
            case 'xlsx':
                return $this->exportXlsx($filename);
            case 'pdf':
                return $this->exportPdf($filename);
            case 'json':
                return $this->exportJson($filename);
            default:
                return sfView::SUCCESS;
        }
    }
    
    protected function exportCsv($filename)
    {
        $response = $this->getResponse();
        $response->setContentType('text/csv');
        $response->setHttpHeader('Content-Disposition', 'attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        if (!empty($this->results)) {
            // Header row
            $first = (array) $this->results[0];
            fputcsv($output, array_keys($first));
            
            // Data rows
            foreach ($this->results as $row) {
                fputcsv($output, (array) $row);
            }
        }
        
        fclose($output);
        
        return sfView::NONE;
    }
    
    protected function exportJson($filename)
    {
        $response = $this->getResponse();
        $response->setContentType('application/json');
        $response->setHttpHeader('Content-Disposition', 'attachment; filename="' . $filename . '.json"');
        
        echo json_encode([
            'report' => $this->reportDef->name,
            'generated' => date('Y-m-d H:i:s'),
            'filters' => $this->filters,
            'data' => $this->results
        ], JSON_PRETTY_PRINT);
        
        return sfView::NONE;
    }
    
    protected function exportXlsx($filename)
    {
        // Fallback to CSV if no Excel library
        return $this->exportCsv($filename);
    }
    
    protected function exportPdf($filename)
    {
        $response = $this->getResponse();

        // Try to use TCPDF if available
        $tcpdfPath = sfConfig::get('sf_root_dir') . '/vendor/tecnickcom/tcpdf/tcpdf.php';
        if (file_exists($tcpdfPath)) {
            require_once $tcpdfPath;
            return $this->exportPdfWithTcpdf($filename);
        }

        // Fallback: Generate HTML for browser print-to-PDF
        return $this->exportPdfAsHtml($filename);
    }

    /**
     * Export PDF using TCPDF library
     */
    protected function exportPdfWithTcpdf($filename)
    {
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

        // Set document info
        $pdf->SetCreator('AtoM Reports');
        $pdf->SetAuthor('AtoM');
        $pdf->SetTitle($this->reportDef->name ?? 'Report');
        $pdf->SetSubject('Report Export');

        // Remove header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->setFooterData(['Report generated: ' . date('Y-m-d H:i:s')]);

        // Set margins
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 15);

        // Add page
        $pdf->AddPage();

        // Title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, $this->reportDef->name ?? 'Report', 0, 1, 'C');
        $pdf->Ln(5);

        // Date
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1, 'R');
        $pdf->Ln(5);

        if (!empty($this->results)) {
            // Build HTML table
            $html = '<table border="1" cellpadding="4" cellspacing="0">';

            // Header
            $html .= '<tr style="background-color:#4472C4;color:#ffffff;font-weight:bold;">';
            $first = (array) $this->results[0];
            foreach (array_keys($first) as $col) {
                $html .= '<th>' . htmlspecialchars($this->formatColumnHeader($col)) . '</th>';
            }
            $html .= '</tr>';

            // Data rows
            $rowNum = 0;
            foreach ($this->results as $row) {
                $bgColor = ($rowNum % 2 == 0) ? '#ffffff' : '#f2f2f2';
                $html .= '<tr style="background-color:' . $bgColor . ';">';
                foreach ((array) $row as $value) {
                    $html .= '<td>' . htmlspecialchars($value ?? '') . '</td>';
                }
                $html .= '</tr>';
                $rowNum++;
            }

            $html .= '</table>';

            $pdf->SetFont('helvetica', '', 8);
            $pdf->writeHTML($html, true, false, true, false, '');
        } else {
            $pdf->SetFont('helvetica', 'I', 12);
            $pdf->Cell(0, 10, 'No data found', 0, 1, 'C');
        }

        // Output
        $response = $this->getResponse();
        $response->setContentType('application/pdf');
        $response->setHttpHeader('Content-Disposition', 'attachment; filename="' . $filename . '.pdf"');

        echo $pdf->Output($filename . '.pdf', 'S');

        return sfView::NONE;
    }

    /**
     * Fallback: Export as printable HTML (user can print to PDF)
     */
    protected function exportPdfAsHtml($filename)
    {
        $response = $this->getResponse();
        $response->setContentType('text/html');

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($this->reportDef->name ?? 'Report') . '</title>
    <style>
        @media print {
            body { margin: 0; padding: 20px; }
            .no-print { display: none; }
        }
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1 { color: #333; margin-bottom: 5px; }
        .meta { color: #666; margin-bottom: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th { background-color: #4472C4; color: white; padding: 8px; text-align: left; border: 1px solid #ddd; }
        td { padding: 6px 8px; border: 1px solid #ddd; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .print-btn { margin: 20px 0; padding: 10px 20px; background: #4472C4; color: white; border: none; cursor: pointer; font-size: 14px; }
        .print-btn:hover { background: #3461a8; }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-btn" onclick="window.print()">Print / Save as PDF</button>
    </div>
    <h1>' . htmlspecialchars($this->reportDef->name ?? 'Report') . '</h1>
    <div class="meta">Generated: ' . date('Y-m-d H:i:s') . '</div>';

        if (!empty($this->results)) {
            $html .= '<table>';

            // Header
            $html .= '<tr>';
            $first = (array) $this->results[0];
            foreach (array_keys($first) as $col) {
                $html .= '<th>' . htmlspecialchars($this->formatColumnHeader($col)) . '</th>';
            }
            $html .= '</tr>';

            // Data rows
            foreach ($this->results as $row) {
                $html .= '<tr>';
                foreach ((array) $row as $value) {
                    $html .= '<td>' . htmlspecialchars($value ?? '') . '</td>';
                }
                $html .= '</tr>';
            }

            $html .= '</table>';
        } else {
            $html .= '<p><em>No data found</em></p>';
        }

        $html .= '
    <div class="no-print" style="margin-top: 20px;">
        <button class="print-btn" onclick="window.print()">Print / Save as PDF</button>
    </div>
</body>
</html>';

        echo $html;

        return sfView::NONE;
    }

    /**
     * Format column header for display
     */
    protected function formatColumnHeader($column)
    {
        // Convert snake_case or camelCase to Title Case
        $column = preg_replace('/([a-z])([A-Z])/', '$1 $2', $column);
        $column = str_replace('_', ' ', $column);
        return ucwords($column);
    }
}
