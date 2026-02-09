<?php
use Illuminate\Database\Capsule\Manager as DB;

class semanticSearchAdminActions extends AhgActions
{
    public function preExecute()
    {
        // Allow testExpand to be called publicly (for search modal)
        $publicActions = ['testExpand'];
        $currentAction = $this->getActionName();

        if (in_array($currentAction, $publicActions)) {
            return; // Skip auth for public actions
        }

        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        // Check admin permission
        if (!$this->context->user->hasCredential('administrator')) {
            $this->forward('admin', 'secure');
        }
    }

    protected function getUserId(): ?int
    {
        return $this->getUser()->getAttribute('user_id');
    }

    /**
     * Dashboard
     */
    public function executeIndex(sfWebRequest $request)
    {
        // Get stats from thesaurus tables
        $this->stats = [
            'terms' => [
                'total' => DB::table('ahg_thesaurus_term')->count(),
                'wordnet' => DB::table('ahg_thesaurus_term')->where('source', 'wordnet')->count(),
                'wikidata' => DB::table('ahg_thesaurus_term')->where('source', 'wikidata')->count(),
                'local' => DB::table('ahg_thesaurus_term')->where('source', 'local')->count(),
            ],
            'synonyms' => [
                'total' => DB::table('ahg_thesaurus_synonym')->count(),
                'exact' => DB::table('ahg_thesaurus_synonym')->where('relationship_type', 'exact')->count(),
                'broader' => DB::table('ahg_thesaurus_synonym')->where('relationship_type', 'broader')->count(),
                'narrower' => DB::table('ahg_thesaurus_synonym')->where('relationship_type', 'narrower')->count(),
                'related' => DB::table('ahg_thesaurus_synonym')->where('relationship_type', 'related')->count(),
            ],
            'embeddings' => DB::table('ahg_thesaurus_embedding')->count(),
        ];

        // Recent sync logs
        $this->recentSyncs = DB::table('ahg_thesaurus_sync_log')
            ->orderByDesc('started_at')
            ->limit(5)
            ->get();

        // Recent search logs
        $this->recentSearches = DB::table('ahg_semantic_search_log')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Get current settings
        $this->settings = $this->loadSettings();
    }

    /**
     * Configuration page
     */
    public function executeConfig(sfWebRequest $request)
    {
        $this->settings = $this->loadSettings();

        if ($request->isMethod('post')) {
            $this->saveSettings($request->getPostParameters());
            $this->getUser()->setFlash('success', 'Settings saved successfully');
            $this->redirect(['module' => 'semanticSearchAdmin', 'action' => 'config']);
        }
    }

    /**
     * Term browser
     */
    public function executeTerms(sfWebRequest $request)
    {
        $source = $request->getParameter('source');
        $search = $request->getParameter('q');
        $page = max(1, (int) $request->getParameter('page', 1));
        $perPage = 50;

        $query = DB::table('ahg_thesaurus_term')
            ->select('ahg_thesaurus_term.*')
            ->selectRaw('(SELECT COUNT(*) FROM ahg_thesaurus_synonym WHERE term_id = ahg_thesaurus_term.id) as synonym_count');

        if ($source) {
            $query->where('source', $source);
        }

        if ($search) {
            $query->where('term', 'LIKE', "%{$search}%");
        }

        // Get total count for pagination
        $this->totalCount = $query->count();
        $this->currentPage = $page;
        $this->perPage = $perPage;
        $this->totalPages = max(1, ceil($this->totalCount / $perPage));

        // Get paginated results
        $offset = ($page - 1) * $perPage;
        $this->terms = $query->orderBy('term')->offset($offset)->limit($perPage)->get();

        $this->sources = DB::table('ahg_thesaurus_term')
            ->selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->get();
    }

    /**
     * View term details
     */
    public function executeTermView(sfWebRequest $request)
    {
        $this->term = DB::table('ahg_thesaurus_term')
            ->where('id', $request->getParameter('id'))
            ->first();

        if (!$this->term) {
            $this->forward404();
        }

        $this->synonyms = DB::table('ahg_thesaurus_synonym')
            ->where('term_id', $this->term->id)
            ->orderBy('weight', 'desc')
            ->get();

        $this->embedding = DB::table('ahg_thesaurus_embedding')
            ->where('term_id', $this->term->id)
            ->first();
    }

    /**
     * Add custom term
     */
    public function executeTermAdd(sfWebRequest $request)
    {
        if ($request->isMethod('post')) {
            $term = trim($request->getParameter('term'));
            $synonyms = array_filter(array_map('trim', explode("\n", $request->getParameter('synonyms'))));

            if (empty($term)) {
                $this->getUser()->setFlash('error', 'Term is required');
                return;
            }

            // Check if term exists
            $existing = DB::table('ahg_thesaurus_term')->where('term', $term)->first();

            if ($existing) {
                $termId = $existing->id;
            } else {
                $termId = DB::table('ahg_thesaurus_term')->insertGetId([
                    'term' => strtolower($term),
                    'source' => 'local',
                    'domain' => $request->getParameter('domain', 'general'),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            // Add synonyms
            foreach ($synonyms as $synonym) {
                $synonym = strtolower(trim($synonym));
                if (empty($synonym)) continue;

                DB::table('ahg_thesaurus_synonym')->updateOrInsert(
                    ['term_id' => $termId, 'synonym_text' => $synonym],
                    [
                        'relationship_type' => $request->getParameter('relationship', 'exact'),
                        'weight' => (float)$request->getParameter('weight', 0.8),
                        'source' => 'local',
                    ]
                );
            }

            $this->getUser()->setFlash('success', 'Term and synonyms added successfully');
            $this->redirect(['module' => 'semanticSearchAdmin', 'action' => 'termView', 'id' => $termId]);
        }
    }

    /**
     * Sync logs
     */
    public function executeSyncLogs(sfWebRequest $request)
    {
        $this->logs = DB::table('ahg_thesaurus_sync_log')
            ->orderByDesc('started_at')
            ->limit(50)
            ->get();
    }

    /**
     * Search logs
     */
    public function executeSearchLogs(sfWebRequest $request)
    {
        $this->logs = DB::table('ahg_semantic_search_log')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        // Get popular searches
        $this->popularSearches = DB::table('ahg_semantic_search_log')
            ->selectRaw('original_query, COUNT(*) as count')
            ->groupBy('original_query')
            ->orderByDesc('count')
            ->limit(20)
            ->get();
    }

    /**
     * Run sync (AJAX)
     */
    public function executeRunSync(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $syncType = $request->getParameter('type', 'local');
        $result = ['success' => false, 'message' => 'Unknown sync type'];

        try {
            // Load services from plugin
            require_once sfConfig::get('sf_plugins_dir') . '/ahgSemanticSearchPlugin/lib/Services/ThesaurusService.php';

            $thesaurus = new \AtomFramework\Services\SemanticSearch\ThesaurusService();

            switch ($syncType) {
                case 'local':
                    $stats = $thesaurus->importLocalSynonyms();
                    $result = [
                        'success' => true,
                        'message' => sprintf('Imported %d terms with %d synonyms', $stats['terms'], $stats['synonyms']),
                    ];
                    break;

                case 'wordnet':
                    require_once sfConfig::get('sf_plugins_dir') . '/ahgSemanticSearchPlugin/lib/Services/WordNetSyncService.php';
                    $wordnet = new \AtomFramework\Services\SemanticSearch\WordNetSyncService();
                    // Sync all domains for comprehensive vocabulary coverage
                    $allStats = $wordnet->syncAllDomains();
                    $totals = $allStats['totals'];
                    $result = [
                        'success' => true,
                        'message' => sprintf('Synced %d terms (%d new, %d updated) with %d synonyms from WordNet',
                            $totals['total_terms_processed'],
                            $totals['total_terms_added'],
                            $totals['total_terms_updated'],
                            $totals['total_synonyms_added']),
                    ];
                    break;

                case 'elasticsearch':
                    $path = $thesaurus->exportToElasticsearch();
                    $result = [
                        'success' => true,
                        'message' => sprintf('Exported synonyms to %s', $path),
                    ];
                    break;

                default:
                    $result = ['success' => false, 'message' => 'Unknown sync type: ' . $syncType];
            }
        } catch (Exception $e) {
            $result = ['success' => false, 'message' => $e->getMessage()];
        }

        echo json_encode($result);
        return sfView::NONE;
    }

    /**
     * Test query expansion (AJAX)
     */
    public function executeTestExpand(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $query = $request->getParameter('query', '');
        $result = ['success' => false, 'expansions' => []];

        try {
            require_once sfConfig::get('sf_plugins_dir') . '/ahgSemanticSearchPlugin/lib/Services/ThesaurusService.php';
            $thesaurus = new \AtomFramework\Services\SemanticSearch\ThesaurusService();

            $expansionResult = $thesaurus->expandQuery($query);

            // Extract just the expanded_terms for the frontend
            // Format: { "archive": ["repository", "depot", ...] }
            $expansions = $expansionResult['expanded_terms'] ?? [];

            $result = [
                'success' => true,
                'original' => $query,
                'expansions' => $expansions,
            ];
        } catch (Exception $e) {
            $result = ['success' => false, 'message' => $e->getMessage()];
        }

        echo json_encode($result);
        return sfView::NONE;
    }

    /**
     * Load settings from database
     */
    protected function loadSettings(): array
    {
        $defaults = [
            'semantic_search_enabled' => true,
            'semantic_expansion_limit' => 5,
            'semantic_min_weight' => 0.6,
            'semantic_show_expansion' => true,
            'semantic_log_searches' => true,
            'semantic_wordnet_enabled' => true,
            'semantic_wikidata_enabled' => false,
            'semantic_local_synonyms' => true,
            'semantic_ollama_enabled' => false,
            'semantic_ollama_endpoint' => 'http://localhost:11434',
            'semantic_ollama_model' => 'nomic-embed-text',
            'semantic_es_synonyms_path' => '/etc/elasticsearch/synonyms/ahg_synonyms.txt',
        ];

        $settings = [];
        try {
            $rows = DB::table('ahg_settings')
                ->where('setting_group', 'semantic_search')
                ->get();

            foreach ($rows as $row) {
                $value = $row->setting_value;
                if ($row->setting_type === 'boolean') {
                    $value = $value === 'true' || $value === '1';
                } elseif ($row->setting_type === 'integer') {
                    $value = (int)$value;
                } elseif ($row->setting_type === 'float') {
                    $value = (float)$value;
                }
                $settings[$row->setting_key] = $value;
            }
        } catch (Exception $e) {
            // Table might not exist
        }

        return array_merge($defaults, $settings);
    }

    /**
     * Save settings to database
     */
    protected function saveSettings(array $data): void
    {
        $userId = $this->getUserId();
        $settingsData = $data['settings'] ?? $data;

        $booleanFields = [
            'semantic_search_enabled', 'semantic_show_expansion', 'semantic_log_searches',
            'semantic_wordnet_enabled', 'semantic_wikidata_enabled', 'semantic_local_synonyms',
            'semantic_ollama_enabled'
        ];

        $intFields = ['semantic_expansion_limit'];
        $floatFields = ['semantic_min_weight'];

        foreach ($settingsData as $key => $value) {
            if (!str_starts_with($key, 'semantic_')) {
                continue;
            }

            if (in_array($key, $booleanFields)) {
                $storedValue = ($value === 'on' || $value === '1' || $value === 'true') ? 'true' : 'false';
                $type = 'boolean';
            } elseif (in_array($key, $intFields)) {
                $storedValue = (string)(int)$value;
                $type = 'integer';
            } elseif (in_array($key, $floatFields)) {
                $storedValue = (string)(float)$value;
                $type = 'float';
            } else {
                $storedValue = (string)$value;
                $type = 'string';
            }

            DB::table('ahg_settings')->updateOrInsert(
                ['setting_key' => $key],
                [
                    'setting_value' => $storedValue,
                    'setting_type' => $type,
                    'setting_group' => 'semantic_search',
                    'updated_by' => $userId,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]
            );
        }
    }
}
