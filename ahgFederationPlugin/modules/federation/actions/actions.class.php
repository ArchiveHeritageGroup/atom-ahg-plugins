<?php

/**
 * Federation module actions
 *
 * Provides admin UI for managing federation peers and harvesting.
 */
class federationActions extends AhgActions
{
    /**
     * Federation dashboard
     */
    public function executeIndex(sfWebRequest $request)
    {
        // Check authentication and admin access
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        require_once sfConfig::get('sf_plugins_dir') . '/ahgFederationPlugin/lib/FederationProvenance.php';

        $provenance = new \AhgFederation\FederationProvenance();

        // Get statistics
        $this->stats = $provenance->getStatistics();

        // Get all peers
        $this->peers = \Illuminate\Database\Capsule\Manager::table('federation_peer')
            ->orderBy('name')
            ->get()
            ->toArray();

        // Get recent harvest sessions
        $this->recentSessions = \Illuminate\Database\Capsule\Manager::table('federation_harvest_session as s')
            ->leftJoin('federation_peer as p', 's.peer_id', '=', 'p.id')
            ->select(['s.*', 'p.name as peer_name'])
            ->orderBy('s.started_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * List all federation peers
     */
    public function executePeers(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }


        $this->peers = \Illuminate\Database\Capsule\Manager::table('federation_peer')
            ->orderBy('name')
            ->get()
            ->toArray();

        // Get record counts for each peer
        $this->recordCounts = [];
        require_once sfConfig::get('sf_plugins_dir') . '/ahgFederationPlugin/lib/FederationProvenance.php';
        $provenance = new \AhgFederation\FederationProvenance();

        foreach ($this->peers as $peer) {
            $this->recordCounts[$peer->id] = $provenance->countRecordsFromPeer($peer->id);
        }
    }

    /**
     * Add a new federation peer
     */
    public function executeAddPeer(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        $this->peer = null;
        $this->isNew = true;

        if ($request->isMethod('post')) {
            $this->processPeerForm($request);
        }

        $this->setTemplate('editPeer');
    }

    /**
     * Edit an existing federation peer
     */
    public function executeEditPeer(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }


        $peerId = $request->getParameter('id');
        $this->peer = \Illuminate\Database\Capsule\Manager::table('federation_peer')
            ->where('id', $peerId)
            ->first();

        if (!$this->peer) {
            $this->forward404('Peer not found');
        }

        $this->isNew = false;

        if ($request->isMethod('post')) {
            if ($request->getParameter('delete')) {
                return $this->deletePeer($peerId);
            }
            $this->processPeerForm($request, $peerId);
        }
    }

    /**
     * Process peer form submission
     */
    protected function processPeerForm(sfWebRequest $request, ?int $peerId = null)
    {

        $data = [
            'name' => $request->getParameter('name'),
            'base_url' => rtrim($request->getParameter('base_url'), '/'),
            'oai_identifier' => $request->getParameter('oai_identifier') ?: null,
            'api_key' => $request->getParameter('api_key') ?: null,
            'description' => $request->getParameter('description') ?: null,
            'contact_email' => $request->getParameter('contact_email') ?: null,
            'default_metadata_prefix' => $request->getParameter('default_metadata_prefix') ?: 'oai_dc',
            'default_set' => $request->getParameter('default_set') ?: null,
            'harvest_interval_hours' => (int)$request->getParameter('harvest_interval_hours') ?: 24,
            'is_active' => $request->getParameter('is_active') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Validate
        $errors = [];
        if (empty($data['name'])) {
            $errors[] = 'Name is required';
        }
        if (empty($data['base_url'])) {
            $errors[] = 'Base URL is required';
        }
        if (!filter_var($data['base_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Base URL must be a valid URL';
        }

        if (!empty($errors)) {
            $this->getUser()->setFlash('error', implode(', ', $errors));
            return;
        }

        if ($peerId) {
            // Update existing
            \Illuminate\Database\Capsule\Manager::table('federation_peer')
                ->where('id', $peerId)
                ->update($data);
            $this->getUser()->setFlash('notice', 'Peer updated successfully.');
        } else {
            // Create new
            $data['created_at'] = date('Y-m-d H:i:s');
            $peerId = \Illuminate\Database\Capsule\Manager::table('federation_peer')->insertGetId($data);
            $this->getUser()->setFlash('notice', 'Peer created successfully.');
        }

        $this->redirect(['module' => 'federation', 'action' => 'peers']);
    }

    /**
     * Delete a federation peer
     */
    protected function deletePeer(int $peerId)
    {

        \Illuminate\Database\Capsule\Manager::table('federation_peer')
            ->where('id', $peerId)
            ->delete();

        $this->getUser()->setFlash('notice', 'Peer deleted successfully.');
        $this->redirect(['module' => 'federation', 'action' => 'peers']);
    }

    /**
     * Harvest management page for a peer
     */
    public function executeHarvest(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }


        $peerId = $request->getParameter('peerId');
        $this->peer = \Illuminate\Database\Capsule\Manager::table('federation_peer')
            ->where('id', $peerId)
            ->first();

        if (!$this->peer) {
            $this->forward404('Peer not found');
        }

        // Get harvest history
        $this->sessions = \Illuminate\Database\Capsule\Manager::table('federation_harvest_session')
            ->where('peer_id', $peerId)
            ->orderBy('started_at', 'desc')
            ->limit(20)
            ->get()
            ->toArray();

        // Get available sets from peer
        $this->sets = [];
        try {
            require_once sfConfig::get('sf_plugins_dir') . '/ahgFederationPlugin/lib/HarvestClient.php';
            $client = new \AhgFederation\HarvestClient();
            $this->sets = $client->listSets($this->peer->base_url);
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Could not fetch sets from peer: ' . $e->getMessage());
        }

        // Get available metadata formats
        $this->formats = [];
        try {
            $client = $client ?? new \AhgFederation\HarvestClient();
            $this->formats = $client->listMetadataFormats($this->peer->base_url);
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Could not fetch metadata formats from peer: ' . $e->getMessage());
        }
    }

    /**
     * View harvest log
     */
    public function executeLog(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }


        $peerId = $request->getParameter('peer_id');
        $action = $request->getParameter('filter_action');
        $page = max(1, (int)$request->getParameter('page', 1));
        $perPage = 50;

        $query = \Illuminate\Database\Capsule\Manager::table('federation_harvest_log as l')
            ->leftJoin('federation_peer as p', 'l.peer_id', '=', 'p.id')
            ->leftJoin('information_object as io', 'l.information_object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('io.id', '=', 'ioi.id')
                     ->where('ioi.culture', '=', sfContext::getInstance()->user->getCulture() ?: 'en');
            });

        if ($peerId) {
            $query->where('l.peer_id', $peerId);
        }

        if ($action) {
            $query->where('l.action', $action);
        }

        $this->total = $query->count();
        $this->pages = ceil($this->total / $perPage);
        $this->page = $page;

        $this->logs = $query
            ->select([
                'l.*',
                'p.name as peer_name',
                'ioi.title as object_title',
                'io.slug as object_slug',
            ])
            ->orderBy('l.harvest_date', 'desc')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get()
            ->toArray();

        // Get peers for filter dropdown
        $this->peers = \Illuminate\Database\Capsule\Manager::table('federation_peer')
            ->orderBy('name')
            ->get()
            ->toArray();

        $this->filterPeerId = $peerId;
        $this->filterAction = $action;
    }

    /**
     * AJAX: Test peer connection
     */
    public function executeTestPeer(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->isAdministrator()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Unauthorized']));
        }

        $baseUrl = $request->getParameter('base_url');

        if (empty($baseUrl)) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Base URL is required']));
        }

        try {
            require_once sfConfig::get('sf_plugins_dir') . '/ahgFederationPlugin/lib/HarvestClient.php';

            $client = new \AhgFederation\HarvestClient();
            $client->setTimeout(30);

            $identify = $client->identify($baseUrl);
            $formats = $client->listMetadataFormats($baseUrl);

            return $this->renderText(json_encode([
                'success' => true,
                'identify' => $identify,
                'formats' => $formats,
            ]));

        } catch (\Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * AJAX: Run harvest
     */
    public function executeRunHarvest(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->isAdministrator()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Unauthorized']));
        }

        $peerId = $request->getParameter('peerId');


        $peer = \Illuminate\Database\Capsule\Manager::table('federation_peer')
            ->where('id', $peerId)
            ->first();

        if (!$peer) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Peer not found']));
        }

        // Create harvest session
        $sessionId = \Illuminate\Database\Capsule\Manager::table('federation_harvest_session')->insertGetId([
            'peer_id' => $peerId,
            'started_at' => date('Y-m-d H:i:s'),
            'status' => 'running',
            'metadata_prefix' => $request->getParameter('metadata_prefix') ?: $peer->default_metadata_prefix ?: 'oai_dc',
            'harvest_from' => $request->getParameter('from') ?: null,
            'harvest_until' => $request->getParameter('until') ?: null,
            'harvest_set' => $request->getParameter('set') ?: $peer->default_set,
            'is_full_harvest' => $request->getParameter('full_harvest') ? 1 : 0,
            'initiated_by' => $this->getUser()->getAttribute('user_id'),
        ]);

        try {
            require_once sfConfig::get('sf_plugins_dir') . '/ahgFederationPlugin/lib/HarvestClient.php';
            require_once sfConfig::get('sf_plugins_dir') . '/ahgFederationPlugin/lib/HarvestService.php';
            require_once sfConfig::get('sf_plugins_dir') . '/ahgFederationPlugin/lib/FederationProvenance.php';

            $service = new \AhgFederation\HarvestService();

            $options = [
                'metadataPrefix' => $request->getParameter('metadata_prefix') ?: $peer->default_metadata_prefix,
                'from' => $request->getParameter('from') ?: null,
                'until' => $request->getParameter('until') ?: null,
                'set' => $request->getParameter('set') ?: $peer->default_set,
                'fullHarvest' => (bool)$request->getParameter('full_harvest'),
            ];

            $result = $service->harvestPeer($peerId, $options);

            // Update session
            \Illuminate\Database\Capsule\Manager::table('federation_harvest_session')
                ->where('id', $sessionId)
                ->update([
                    'completed_at' => date('Y-m-d H:i:s'),
                    'status' => $result->isSuccessful() ? 'completed' : 'failed',
                    'records_total' => $result->stats['total'],
                    'records_created' => $result->stats['created'],
                    'records_updated' => $result->stats['updated'],
                    'records_deleted' => $result->stats['deleted'],
                    'records_skipped' => $result->stats['skipped'],
                    'records_errors' => $result->stats['errors'],
                    'error_message' => !empty($result->stats['errorMessages'])
                        ? implode("\n", array_slice($result->stats['errorMessages'], 0, 10))
                        : null,
                ]);

            // Update peer last harvest status
            \Illuminate\Database\Capsule\Manager::table('federation_peer')
                ->where('id', $peerId)
                ->update([
                    'last_harvest_status' => $result->isSuccessful() ? 'success' : 'failed',
                    'last_harvest_records' => $result->stats['total'],
                ]);

            return $this->renderText(json_encode([
                'success' => true,
                'result' => $result->toArray(),
                'sessionId' => $sessionId,
            ]));

        } catch (\Exception $e) {
            // Update session with error
            \Illuminate\Database\Capsule\Manager::table('federation_harvest_session')
                ->where('id', $sessionId)
                ->update([
                    'completed_at' => date('Y-m-d H:i:s'),
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'sessionId' => $sessionId,
            ]));
        }
    }

    /**
     * AJAX: Get harvest status
     */
    public function executeHarvestStatus(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Unauthorized']));
        }


        $peerId = $request->getParameter('peerId');

        // Get latest session
        $session = \Illuminate\Database\Capsule\Manager::table('federation_harvest_session')
            ->where('peer_id', $peerId)
            ->orderBy('started_at', 'desc')
            ->first();

        if (!$session) {
            return $this->renderText(json_encode([
                'success' => true,
                'status' => 'idle',
                'session' => null,
            ]));
        }

        return $this->renderText(json_encode([
            'success' => true,
            'status' => $session->status,
            'session' => $session,
        ]));
    }
}
