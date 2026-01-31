<?php

/**
 * DOI module actions.
 */
class doiActions extends sfActions
{
    /**
     * Dashboard / index.
     */
    public function executeIndex(sfWebRequest $request)
    {
        $this->checkAdmin();

        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgDoiPlugin/lib/Services/DoiService.php';
        $service = new \ahgDoiPlugin\Services\DoiService();

        $this->stats = $service->getStatistics();
        $this->recentDois = \Illuminate\Database\Capsule\Manager::table('ahg_doi as d')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('d.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->orderByDesc('d.minted_at')
            ->limit(10)
            ->select(['d.*', 'ioi.title as object_title'])
            ->get();
    }

    /**
     * Browse all DOIs.
     */
    public function executeBrowse(sfWebRequest $request)
    {
        $this->checkAdmin();

        $query = \Illuminate\Database\Capsule\Manager::table('ahg_doi as d')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('d.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', 'd.information_object_id', '=', 'slug.object_id')
            ->select(['d.*', 'ioi.title as object_title', 'slug.slug']);

        // Filter by status
        if ($status = $request->getParameter('status')) {
            $query->where('d.status', $status);
        }

        $this->dois = $query->orderByDesc('d.minted_at')->paginate(50);
        $this->currentStatus = $status;
    }

    /**
     * View single DOI.
     */
    public function executeView(sfWebRequest $request)
    {
        $this->checkAdmin();

        $this->doi = \Illuminate\Database\Capsule\Manager::table('ahg_doi as d')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('d.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', 'd.information_object_id', '=', 'slug.object_id')
            ->where('d.id', $request->getParameter('id'))
            ->select(['d.*', 'ioi.title as object_title', 'slug.slug'])
            ->first();

        $this->forward404Unless($this->doi);

        // Get activity log
        $this->logs = \Illuminate\Database\Capsule\Manager::table('ahg_doi_log')
            ->where('doi_id', $this->doi->id)
            ->orderByDesc('performed_at')
            ->limit(20)
            ->get();
    }

    /**
     * Mint DOI for a record.
     */
    public function executeMint(sfWebRequest $request)
    {
        $this->checkAdmin();

        $objectId = (int) $request->getParameter('id');

        if ($request->isMethod('post')) {
            require_once sfConfig::get('sf_root_dir') . '/plugins/ahgDoiPlugin/lib/Services/DoiService.php';
            $service = new \ahgDoiPlugin\Services\DoiService();

            $state = $request->getParameter('state', 'findable');
            $result = $service->mintDoi($objectId, $state);

            if ($result['success']) {
                $this->getUser()->setFlash('notice', "DOI minted: {$result['doi']}");
                $this->redirect(['module' => 'doi', 'action' => 'view', 'id' => $result['doi_id']]);
            } else {
                $this->getUser()->setFlash('error', "Minting failed: {$result['error']}");
            }
        }

        // Get record details
        $this->record = \Illuminate\Database\Capsule\Manager::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->where('io.id', $objectId)
            ->select(['io.*', 'ioi.title'])
            ->first();

        $this->forward404Unless($this->record);

        // Check if already has DOI
        $this->existingDoi = \Illuminate\Database\Capsule\Manager::table('ahg_doi')
            ->where('information_object_id', $objectId)
            ->first();
    }

    /**
     * Batch mint DOIs.
     */
    public function executeBatchMint(sfWebRequest $request)
    {
        $this->checkAdmin();

        if ($request->isMethod('post')) {
            $ids = $request->getParameter('object_ids', []);
            $state = $request->getParameter('state', 'findable');

            if (empty($ids)) {
                $this->getUser()->setFlash('error', 'No records selected');
                $this->redirect(['module' => 'doi', 'action' => 'batchMint']);
            }

            require_once sfConfig::get('sf_root_dir') . '/plugins/ahgDoiPlugin/lib/Services/DoiService.php';
            $service = new \ahgDoiPlugin\Services\DoiService();

            $queued = 0;
            foreach ($ids as $id) {
                $service->queueForMinting((int) $id, 'mint');
                ++$queued;
            }

            $this->getUser()->setFlash('notice', "{$queued} records queued for DOI minting");
            $this->redirect(['module' => 'doi', 'action' => 'queue']);
        }

        // Get records without DOIs
        $this->records = \Illuminate\Database\Capsule\Manager::table('information_object as io')
            ->leftJoin('ahg_doi as d', 'io.id', '=', 'd.information_object_id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->whereNull('d.id')
            ->where('io.id', '!=', 1)
            ->select(['io.id', 'ioi.title'])
            ->limit(100)
            ->get();
    }

    /**
     * Update DOI metadata.
     */
    public function executeUpdate(sfWebRequest $request)
    {
        $this->checkAdmin();

        $doiId = (int) $request->getParameter('id');

        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgDoiPlugin/lib/Services/DoiService.php';
        $service = new \ahgDoiPlugin\Services\DoiService();

        $result = $service->updateDoi($doiId);

        if ($result['success']) {
            $this->getUser()->setFlash('notice', 'DOI metadata updated');
        } else {
            $this->getUser()->setFlash('error', "Update failed: {$result['error']}");
        }

        $this->redirect(['module' => 'doi', 'action' => 'view', 'id' => $doiId]);
    }

    /**
     * Queue management.
     */
    public function executeQueue(sfWebRequest $request)
    {
        $this->checkAdmin();

        $this->queue = \Illuminate\Database\Capsule\Manager::table('ahg_doi_queue as q')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('q.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->orderByRaw("FIELD(q.status, 'processing', 'pending', 'failed', 'completed')")
            ->orderByDesc('q.scheduled_at')
            ->select(['q.*', 'ioi.title as object_title'])
            ->limit(100)
            ->get();
    }

    /**
     * Retry queue item.
     */
    public function executeQueueRetry(sfWebRequest $request)
    {
        $this->checkAdmin();

        $queueId = (int) $request->getParameter('id');

        \Illuminate\Database\Capsule\Manager::table('ahg_doi_queue')
            ->where('id', $queueId)
            ->update([
                'status' => 'pending',
                'attempts' => 0,
                'scheduled_at' => date('Y-m-d H:i:s'),
            ]);

        $this->getUser()->setFlash('notice', 'Item queued for retry');
        $this->redirect(['module' => 'doi', 'action' => 'queue']);
    }

    /**
     * Configuration.
     */
    public function executeConfig(sfWebRequest $request)
    {
        $this->checkAdmin();

        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgDoiPlugin/lib/Services/DoiService.php';
        $service = new \ahgDoiPlugin\Services\DoiService();

        $this->config = $service->getConfig();
        $this->repositories = \Illuminate\Database\Capsule\Manager::table('repository as r')
            ->leftJoin('repository_i18n as ri', function ($join) {
                $join->on('r.id', '=', 'ri.id')
                    ->where('ri.culture', '=', 'en');
            })
            ->select(['r.id', 'ri.authorized_form_of_name as name'])
            ->get();
    }

    /**
     * Save configuration.
     */
    public function executeConfigSave(sfWebRequest $request)
    {
        $this->checkAdmin();

        if (!$request->isMethod('post')) {
            $this->redirect(['module' => 'doi', 'action' => 'config']);
        }

        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgDoiPlugin/lib/Services/DoiService.php';
        $service = new \ahgDoiPlugin\Services\DoiService();

        $service->saveConfig([
            'datacite_repo_id' => $request->getParameter('datacite_repo_id'),
            'datacite_prefix' => $request->getParameter('datacite_prefix'),
            'datacite_password' => $request->getParameter('datacite_password'),
            'datacite_url' => $request->getParameter('datacite_url'),
            'environment' => $request->getParameter('environment'),
            'auto_mint' => $request->getParameter('auto_mint') ? true : false,
            'auto_mint_levels' => $request->getParameter('auto_mint_levels', []),
            'require_digital_object' => $request->getParameter('require_digital_object') ? true : false,
            'default_publisher' => $request->getParameter('default_publisher'),
            'default_resource_type' => $request->getParameter('default_resource_type'),
            'suffix_pattern' => $request->getParameter('suffix_pattern'),
        ]);

        $this->getUser()->setFlash('notice', 'Configuration saved');
        $this->redirect(['module' => 'doi', 'action' => 'config']);
    }

    /**
     * Test DataCite connection.
     */
    public function executeConfigTest(sfWebRequest $request)
    {
        $this->checkAdmin();

        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgDoiPlugin/lib/Services/DoiService.php';
        $service = new \ahgDoiPlugin\Services\DoiService();

        $result = $service->testConnection();

        $this->getResponse()->setHttpHeader('Content-Type', 'application/json');

        return $this->renderText(json_encode($result));
    }

    /**
     * Reports.
     */
    public function executeReport(sfWebRequest $request)
    {
        $this->checkAdmin();

        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgDoiPlugin/lib/Services/DoiService.php';
        $service = new \ahgDoiPlugin\Services\DoiService();

        $this->stats = $service->getStatistics();

        // Monthly minting stats
        $this->monthlyStats = \Illuminate\Database\Capsule\Manager::table('ahg_doi')
            ->selectRaw("DATE_FORMAT(minted_at, '%Y-%m') as month, COUNT(*) as count")
            ->whereNotNull('minted_at')
            ->groupBy('month')
            ->orderByDesc('month')
            ->limit(12)
            ->get();

        // By repository
        $this->byRepository = \Illuminate\Database\Capsule\Manager::table('ahg_doi as d')
            ->join('information_object as io', 'd.information_object_id', '=', 'io.id')
            ->leftJoin('repository_i18n as ri', function ($join) {
                $join->on('io.repository_id', '=', 'ri.id')
                    ->where('ri.culture', '=', 'en');
            })
            ->selectRaw('ri.authorized_form_of_name as repository, COUNT(*) as count')
            ->groupBy('io.repository_id', 'ri.authorized_form_of_name')
            ->orderByDesc('count')
            ->get();
    }

    /**
     * Resolve DOI to record (public).
     */
    public function executeResolve(sfWebRequest $request)
    {
        $doiString = '10.' . $request->getParameter('doi');

        $doi = \Illuminate\Database\Capsule\Manager::table('ahg_doi')
            ->where('doi', $doiString)
            ->first();

        if (!$doi) {
            $this->forward404();
        }

        $slug = \Illuminate\Database\Capsule\Manager::table('slug')
            ->where('object_id', $doi->information_object_id)
            ->value('slug');

        $this->redirect('/' . $slug);
    }

    /**
     * API: Mint DOI.
     */
    public function executeApiMint(sfWebRequest $request)
    {
        $this->getResponse()->setHttpHeader('Content-Type', 'application/json');

        if (!$this->context->user->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Unauthorized']));
        }

        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgDoiPlugin/lib/Services/DoiService.php';
        $service = new \ahgDoiPlugin\Services\DoiService();

        $result = $service->mintDoi((int) $request->getParameter('id'));

        return $this->renderText(json_encode($result));
    }

    /**
     * API: Get DOI status.
     */
    public function executeApiStatus(sfWebRequest $request)
    {
        $this->getResponse()->setHttpHeader('Content-Type', 'application/json');

        $doi = \Illuminate\Database\Capsule\Manager::table('ahg_doi')
            ->where('information_object_id', $request->getParameter('id'))
            ->first();

        if (!$doi) {
            return $this->renderText(json_encode(['has_doi' => false]));
        }

        return $this->renderText(json_encode([
            'has_doi' => true,
            'doi' => $doi->doi,
            'status' => $doi->status,
            'url' => 'https://doi.org/' . $doi->doi,
            'minted_at' => $doi->minted_at,
        ]));
    }

    /**
     * Check if admin.
     */
    protected function checkAdmin(): void
    {
        if (!$this->context->user->isAuthenticated() || !$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }
    }
}
