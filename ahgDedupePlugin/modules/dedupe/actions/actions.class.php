<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Dedupe module actions.
 */
class dedupeActions extends AhgActions
{
    /**
     * Dashboard with statistics.
     */
    public function executeIndex(sfWebRequest $request)
    {
        // Check admin access
        if (!$this->context->user->hasCredential('administrator')) {
            $this->forward('admin', 'secure');
        }

        // Get statistics
        $this->stats = [
            'total' => DB::table('ahg_duplicate_detection')->count(),
            'pending' => DB::table('ahg_duplicate_detection')->where('status', 'pending')->count(),
            'confirmed' => DB::table('ahg_duplicate_detection')->where('status', 'confirmed')->count(),
            'dismissed' => DB::table('ahg_duplicate_detection')->where('status', 'dismissed')->count(),
            'merged' => DB::table('ahg_duplicate_detection')->where('status', 'merged')->count(),
        ];

        // By detection method
        $this->byMethod = DB::table('ahg_duplicate_detection')
            ->select('detection_method', DB::raw('COUNT(*) as count'))
            ->groupBy('detection_method')
            ->pluck('count', 'detection_method');

        // Recent detections
        $this->recentDetections = DB::table('ahg_duplicate_detection as dd')
            ->leftJoin('information_object_i18n as ioi_a', function ($join) {
                $join->on('dd.record_a_id', '=', 'ioi_a.id')
                    ->where('ioi_a.culture', '=', 'en');
            })
            ->leftJoin('information_object_i18n as ioi_b', function ($join) {
                $join->on('dd.record_b_id', '=', 'ioi_b.id')
                    ->where('ioi_b.culture', '=', 'en');
            })
            ->select([
                'dd.*',
                'ioi_a.title as title_a',
                'ioi_b.title as title_b',
            ])
            ->where('dd.status', 'pending')
            ->orderBy('dd.similarity_score', 'desc')
            ->limit(10)
            ->get();

        // Recent scans
        $this->recentScans = DB::table('ahg_dedupe_scan')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Active rules count
        $this->activeRules = DB::table('ahg_duplicate_rule')
            ->where('is_enabled', 1)
            ->count();
    }

    /**
     * Browse all detected duplicates.
     */
    public function executeBrowse(sfWebRequest $request)
    {
        if (!$this->context->user->hasCredential('administrator')) {
            $this->forward('admin', 'secure');
        }

        $this->status = $request->getParameter('status', 'pending');
        $this->method = $request->getParameter('method');
        $this->minScore = $request->getParameter('min_score', 0);
        $this->page = max(1, (int) $request->getParameter('page', 1));
        $this->perPage = 25;

        $query = DB::table('ahg_duplicate_detection as dd')
            ->leftJoin('information_object_i18n as ioi_a', function ($join) {
                $join->on('dd.record_a_id', '=', 'ioi_a.id')
                    ->where('ioi_a.culture', '=', 'en');
            })
            ->leftJoin('information_object_i18n as ioi_b', function ($join) {
                $join->on('dd.record_b_id', '=', 'ioi_b.id')
                    ->where('ioi_b.culture', '=', 'en');
            })
            ->leftJoin('information_object as io_a', 'dd.record_a_id', '=', 'io_a.id')
            ->leftJoin('information_object as io_b', 'dd.record_b_id', '=', 'io_b.id')
            ->select([
                'dd.*',
                'ioi_a.title as title_a',
                'ioi_b.title as title_b',
                'io_a.identifier as identifier_a',
                'io_b.identifier as identifier_b',
            ]);

        if ($this->status) {
            $query->where('dd.status', $this->status);
        }

        if ($this->method) {
            $query->where('dd.detection_method', $this->method);
        }

        if ($this->minScore > 0) {
            $query->where('dd.similarity_score', '>=', $this->minScore);
        }

        $this->total = $query->count();
        $this->totalPages = ceil($this->total / $this->perPage);

        $this->duplicates = $query
            ->orderBy('dd.similarity_score', 'desc')
            ->offset(($this->page - 1) * $this->perPage)
            ->limit($this->perPage)
            ->get();

        // Get available methods for filter
        $this->methods = DB::table('ahg_duplicate_detection')
            ->distinct()
            ->pluck('detection_method');
    }

    /**
     * View duplicate pair details.
     */
    public function executeView(sfWebRequest $request)
    {
        if (!$this->context->user->hasCredential('administrator')) {
            $this->forward('admin', 'secure');
        }

        $id = (int) $request->getParameter('id');

        $this->detection = DB::table('ahg_duplicate_detection')
            ->where('id', $id)
            ->first();

        if (!$this->detection) {
            $this->forward404();
        }

        // Load both records with full details
        $this->recordA = $this->loadFullRecord($this->detection->record_a_id);
        $this->recordB = $this->loadFullRecord($this->detection->record_b_id);
    }

    /**
     * Side-by-side comparison view.
     */
    public function executeCompare(sfWebRequest $request)
    {
        if (!$this->context->user->hasCredential('administrator')) {
            $this->forward('admin', 'secure');
        }

        $id = (int) $request->getParameter('id');

        $this->detection = DB::table('ahg_duplicate_detection')
            ->where('id', $id)
            ->first();

        if (!$this->detection) {
            $this->forward404();
        }

        $this->recordA = $this->loadFullRecord($this->detection->record_a_id);
        $this->recordB = $this->loadFullRecord($this->detection->record_b_id);

        // Get field-by-field comparison
        $this->comparison = $this->generateComparison($this->recordA, $this->recordB);
    }

    /**
     * Dismiss a false positive.
     */
    public function executeDismiss(sfWebRequest $request)
    {
        if (!$this->context->user->hasCredential('administrator')) {
            $this->forward('admin', 'secure');
        }

        $id = (int) $request->getParameter('id');
        $notes = $request->getParameter('notes', '');

        $updated = DB::table('ahg_duplicate_detection')
            ->where('id', $id)
            ->where('status', '!=', 'merged')
            ->update([
                'status' => 'dismissed',
                'reviewed_by' => $this->context->user->getUserId(),
                'reviewed_at' => date('Y-m-d H:i:s'),
                'review_notes' => $notes,
            ]);

        if ($request->isXmlHttpRequest()) {
            return $this->renderText(json_encode(['success' => $updated > 0]));
        }

        $this->redirect(['module' => 'dedupe', 'action' => 'browse']);
    }

    /**
     * Merge duplicate records.
     */
    public function executeMerge(sfWebRequest $request)
    {
        if (!$this->context->user->hasCredential('administrator')) {
            $this->forward('admin', 'secure');
        }

        $id = (int) $request->getParameter('id');

        $this->detection = DB::table('ahg_duplicate_detection')
            ->where('id', $id)
            ->first();

        if (!$this->detection) {
            $this->forward404();
        }

        if ('merged' === $this->detection->status) {
            $this->getUser()->setFlash('error', 'This pair has already been merged.');
            $this->redirect(['module' => 'dedupe', 'action' => 'browse']);
        }

        $this->recordA = $this->loadFullRecord($this->detection->record_a_id);
        $this->recordB = $this->loadFullRecord($this->detection->record_b_id);

        if ($request->isMethod('post')) {
            $primaryId = (int) $request->getParameter('primary_id');
            $fieldChoices = $request->getParameter('field_choices', []);

            require_once sfConfig::get('sf_root_dir') . '/plugins/ahgDedupePlugin/lib/Services/DedupeService.php';
            $service = new \ahgDedupePlugin\Services\DedupeService();

            try {
                $result = $service->mergeRecords($id, $primaryId, $this->context->user->getUserId(), $fieldChoices);

                $this->getUser()->setFlash('notice', 'Records merged successfully. Merge log ID: ' . $result['merge_log_id']);
                $this->redirect(['module' => 'dedupe', 'action' => 'browse']);
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Merge failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Start a new scan.
     */
    public function executeScan(sfWebRequest $request)
    {
        if (!$this->context->user->hasCredential('administrator')) {
            $this->forward('admin', 'secure');
        }

        // Get repositories for filter (Repository extends Actor, so name is in actor_i18n)
        $this->repositories = DB::table('repository')
            ->join('actor_i18n', function ($join) {
                $join->on('repository.id', '=', 'actor_i18n.id')
                    ->where('actor_i18n.culture', '=', 'en');
            })
            ->select('repository.id', 'actor_i18n.authorized_form_of_name as name')
            ->orderBy('name')
            ->get();

        if ($request->isMethod('post')) {
            $repositoryId = $request->getParameter('repository_id') ? (int) $request->getParameter('repository_id') : null;

            require_once sfConfig::get('sf_root_dir') . '/plugins/ahgDedupePlugin/lib/Services/DedupeService.php';
            $service = new \ahgDedupePlugin\Services\DedupeService();

            try {
                $scanId = $service->startScan($repositoryId, $this->context->user->getUserId());
                $this->getUser()->setFlash('notice', "Scan job #{$scanId} started. Run 'php symfony dedupe:scan' to process.");
                $this->redirect(['module' => 'dedupe', 'action' => 'index']);
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Failed to start scan: ' . $e->getMessage());
            }
        }
    }

    /**
     * Manage detection rules.
     */
    public function executeRules(sfWebRequest $request)
    {
        if (!$this->context->user->hasCredential('administrator')) {
            $this->forward('admin', 'secure');
        }

        $this->rules = DB::table('ahg_duplicate_rule')
            ->leftJoin('actor_i18n', function ($join) {
                $join->on('ahg_duplicate_rule.repository_id', '=', 'actor_i18n.id')
                    ->where('actor_i18n.culture', '=', 'en');
            })
            ->select('ahg_duplicate_rule.*', 'actor_i18n.authorized_form_of_name as repository_name')
            ->orderBy('priority', 'desc')
            ->get();
    }

    /**
     * Create a new detection rule.
     */
    public function executeRuleCreate(sfWebRequest $request)
    {
        if (!$this->context->user->hasCredential('administrator')) {
            $this->forward('admin', 'secure');
        }

        $this->repositories = DB::table('repository')
            ->join('actor_i18n', function ($join) {
                $join->on('repository.id', '=', 'actor_i18n.id')
                    ->where('actor_i18n.culture', '=', 'en');
            })
            ->select('repository.id', 'actor_i18n.authorized_form_of_name as name')
            ->orderBy('name')
            ->get();

        $this->ruleTypes = [
            'title_similarity' => 'Title Similarity',
            'identifier_exact' => 'Identifier Exact Match',
            'identifier_fuzzy' => 'Identifier Fuzzy Match',
            'date_creator' => 'Date + Creator',
            'checksum' => 'File Checksum',
            'combined' => 'Combined Analysis',
            'custom' => 'Custom Rule',
        ];

        if ($request->isMethod('post')) {
            $data = [
                'repository_id' => $request->getParameter('repository_id') ?: null,
                'name' => $request->getParameter('name'),
                'rule_type' => $request->getParameter('rule_type'),
                'threshold' => (float) $request->getParameter('threshold', 0.8),
                'config_json' => $request->getParameter('config_json'),
                'is_enabled' => $request->getParameter('is_enabled') ? 1 : 0,
                'is_blocking' => $request->getParameter('is_blocking') ? 1 : 0,
                'priority' => (int) $request->getParameter('priority', 100),
                'created_at' => date('Y-m-d H:i:s'),
            ];

            DB::table('ahg_duplicate_rule')->insert($data);
            $this->getUser()->setFlash('notice', 'Rule created successfully.');
            $this->redirect(['module' => 'dedupe', 'action' => 'rules']);
        }
    }

    /**
     * Edit a detection rule.
     */
    public function executeRuleEdit(sfWebRequest $request)
    {
        if (!$this->context->user->hasCredential('administrator')) {
            $this->forward('admin', 'secure');
        }

        $id = (int) $request->getParameter('id');
        $this->rule = DB::table('ahg_duplicate_rule')->where('id', $id)->first();

        if (!$this->rule) {
            $this->forward404();
        }

        $this->repositories = DB::table('repository')
            ->join('actor_i18n', function ($join) {
                $join->on('repository.id', '=', 'actor_i18n.id')
                    ->where('actor_i18n.culture', '=', 'en');
            })
            ->select('repository.id', 'actor_i18n.authorized_form_of_name as name')
            ->orderBy('name')
            ->get();

        $this->ruleTypes = [
            'title_similarity' => 'Title Similarity',
            'identifier_exact' => 'Identifier Exact Match',
            'identifier_fuzzy' => 'Identifier Fuzzy Match',
            'date_creator' => 'Date + Creator',
            'checksum' => 'File Checksum',
            'combined' => 'Combined Analysis',
            'custom' => 'Custom Rule',
        ];

        if ($request->isMethod('post')) {
            $data = [
                'repository_id' => $request->getParameter('repository_id') ?: null,
                'name' => $request->getParameter('name'),
                'rule_type' => $request->getParameter('rule_type'),
                'threshold' => (float) $request->getParameter('threshold', 0.8),
                'config_json' => $request->getParameter('config_json'),
                'is_enabled' => $request->getParameter('is_enabled') ? 1 : 0,
                'is_blocking' => $request->getParameter('is_blocking') ? 1 : 0,
                'priority' => (int) $request->getParameter('priority', 100),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            DB::table('ahg_duplicate_rule')->where('id', $id)->update($data);
            $this->getUser()->setFlash('notice', 'Rule updated successfully.');
            $this->redirect(['module' => 'dedupe', 'action' => 'rules']);
        }
    }

    /**
     * Delete a detection rule.
     */
    public function executeRuleDelete(sfWebRequest $request)
    {
        if (!$this->context->user->hasCredential('administrator')) {
            $this->forward('admin', 'secure');
        }

        $id = (int) $request->getParameter('id');
        DB::table('ahg_duplicate_rule')->where('id', $id)->delete();

        $this->getUser()->setFlash('notice', 'Rule deleted.');
        $this->redirect(['module' => 'dedupe', 'action' => 'rules']);
    }

    /**
     * Generate duplicate report.
     */
    public function executeReport(sfWebRequest $request)
    {
        if (!$this->context->user->hasCredential('administrator')) {
            $this->forward('admin', 'secure');
        }

        // Statistics over time
        $this->monthlyStats = DB::table('ahg_duplicate_detection')
            ->select(
                DB::raw('DATE_FORMAT(detected_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "merged" THEN 1 ELSE 0 END) as merged'),
                DB::raw('SUM(CASE WHEN status = "dismissed" THEN 1 ELSE 0 END) as dismissed')
            )
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();

        // Top duplicate clusters
        $this->topClusters = DB::table('ahg_duplicate_detection as dd')
            ->join('information_object_i18n as ioi', function ($join) {
                $join->on('dd.record_a_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->select('dd.record_a_id', 'ioi.title', DB::raw('COUNT(*) as duplicate_count'))
            ->where('dd.status', 'pending')
            ->groupBy('dd.record_a_id', 'ioi.title')
            ->orderBy('duplicate_count', 'desc')
            ->limit(10)
            ->get();

        // Efficiency metrics
        $this->efficiency = [
            'total_detected' => DB::table('ahg_duplicate_detection')->count(),
            'total_merged' => DB::table('ahg_duplicate_detection')->where('status', 'merged')->count(),
            'total_dismissed' => DB::table('ahg_duplicate_detection')->where('status', 'dismissed')->count(),
            'avg_score' => DB::table('ahg_duplicate_detection')->where('status', 'merged')->avg('similarity_score'),
            'false_positive_rate' => 0,
        ];

        $reviewed = $this->efficiency['total_merged'] + $this->efficiency['total_dismissed'];
        if ($reviewed > 0) {
            $this->efficiency['false_positive_rate'] = round(($this->efficiency['total_dismissed'] / $reviewed) * 100, 1);
        }
    }

    /**
     * API: Check for duplicates in real-time.
     */
    public function executeApiCheck(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $title = $request->getParameter('title');
        $identifier = $request->getParameter('identifier');
        $repositoryId = $request->getParameter('repository_id');

        if (!$title && !$identifier) {
            return $this->renderText(json_encode(['error' => 'Title or identifier required']));
        }

        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgDedupePlugin/lib/Services/DedupeService.php';
        $service = new \ahgDedupePlugin\Services\DedupeService();

        $data = [
            'title' => $title,
            'identifier' => $identifier,
            'repository_id' => $repositoryId,
        ];

        $duplicates = $service->realtimeCheck($data);

        return $this->renderText(json_encode([
            'duplicates' => $duplicates,
            'count' => count($duplicates),
        ]));
    }

    /**
     * API: Real-time duplicate check during data entry.
     */
    public function executeApiRealtime(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $title = $request->getParameter('title', '');

        if (strlen($title) < 5) {
            return $this->renderText(json_encode(['matches' => []]));
        }

        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgDedupePlugin/lib/Services/DedupeService.php';
        $service = new \ahgDedupePlugin\Services\DedupeService();

        $matches = $service->realtimeCheck(['title' => $title], 5);

        return $this->renderText(json_encode(['matches' => $matches]));
    }

    /**
     * Load full record details.
     */
    protected function loadFullRecord($recordId)
    {
        return DB::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->leftJoin('actor_i18n as ri', function ($join) {
                $join->on('io.repository_id', '=', 'ri.id')
                    ->where('ri.culture', '=', 'en');
            })
            ->leftJoin('term_i18n as level', function ($join) {
                $join->on('io.level_of_description_id', '=', 'level.id')
                    ->where('level.culture', '=', 'en');
            })
            ->select([
                'io.*',
                'ioi.title',
                'ioi.scope_and_content',
                'ioi.extent_and_medium',
                'slug.slug',
                'ri.authorized_form_of_name as repository_name',
                'level.name as level_of_description',
            ])
            ->where('io.id', $recordId)
            ->first();
    }

    /**
     * Generate field-by-field comparison.
     */
    protected function generateComparison($recordA, $recordB)
    {
        $fields = [
            'title' => 'Title',
            'identifier' => 'Identifier',
            'level_of_description' => 'Level of Description',
            'repository_name' => 'Repository',
            'extent_and_medium' => 'Extent',
            'scope_and_content' => 'Scope and Content',
        ];

        $comparison = [];
        foreach ($fields as $field => $label) {
            $valueA = $recordA->{$field} ?? '';
            $valueB = $recordB->{$field} ?? '';
            $match = $valueA === $valueB;

            $comparison[] = [
                'field' => $field,
                'label' => $label,
                'value_a' => $valueA,
                'value_b' => $valueB,
                'match' => $match,
            ];
        }

        return $comparison;
    }
}
