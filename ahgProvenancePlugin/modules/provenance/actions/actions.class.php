<?php

class provenanceActions extends sfActions
{
    /**
     * Dashboard / List view
     */
    public function executeIndex(sfWebRequest $request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('user/login');
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgProvenancePlugin/lib/Service/ProvenanceService.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgProvenancePlugin/lib/Repository/ProvenanceRepository.php';

        $service = new \AhgProvenancePlugin\Service\ProvenanceService();
        
        $this->stats = $service->getStatistics();
        $this->acquisitionTypes = $service->getAcquisitionTypes();
        $this->certaintyLevels = $service->getCertaintyLevels();
    }

    /**
     * View provenance for an information object
     */
    public function executeView(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgProvenancePlugin/lib/Service/ProvenanceService.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgProvenancePlugin/lib/Repository/ProvenanceRepository.php';

        $slug = $request->getParameter('slug');
        $this->resource = QubitInformationObject::getBySlug($slug);
        
        if (!$this->resource) {
            $this->forward404('Record not found');
        }

        $service = new \AhgProvenancePlugin\Service\ProvenanceService();
        $culture = $this->context->user->getCulture();
        
        $this->provenance = $service->getProvenanceForObject($this->resource->id, $culture);
        $this->eventTypes = $service->getEventTypes();
    }

    /**
     * Display visual D3.js timeline
     */
    /**
     * Display visual D3.js timeline
     */
    public function executeTimeline(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgProvenancePlugin/lib/Service/ProvenanceService.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgProvenancePlugin/lib/Repository/ProvenanceRepository.php';

        $slug = $request->getParameter('slug');
        $this->resource = QubitInformationObject::getBySlug($slug);

        if (!$this->resource) {
            $this->forward404('Record not found');
        }

        $service = new \AhgProvenancePlugin\Service\ProvenanceService();
        $culture = $this->context->user->getCulture();
        
        // Load provenance data first
        $this->provenance = $service->getProvenanceForObject($this->resource->id, $culture);
        
        // Then prepare timeline data for D3.js
        $this->timelineData = $this->prepareTimelineData();
    }

    public function executeEdit(sfWebRequest $request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('user/login');
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgProvenancePlugin/lib/Service/ProvenanceService.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgProvenancePlugin/lib/Repository/ProvenanceRepository.php';

        $slug = $request->getParameter('slug');
        $this->resource = QubitInformationObject::getBySlug($slug);
        
        if (!$this->resource) {
            $this->forward404('Record not found');
        }

        $service = new \AhgProvenancePlugin\Service\ProvenanceService();
        $culture = $this->context->user->getCulture();
        
        $this->provenance = $service->getProvenanceForObject($this->resource->id, $culture);
        $this->eventTypes = $service->getEventTypes();
        $this->acquisitionTypes = $service->getAcquisitionTypes();
        $this->certaintyLevels = $service->getCertaintyLevels();
        
        // Get agents for autocomplete
        $repo = new \AhgProvenancePlugin\Repository\ProvenanceRepository();
        // Get existing documents
        $this->documents = [];
        if ($this->provenance['exists']) {
            $this->documents = $repo->getDocuments($this->provenance['record']->id);
        }
        $this->agents = $repo->getAllAgents($culture);

        if ($request->isMethod('post')) {
            $this->processForm($request, $service, $culture);
        }
    }

    /**
     * Process edit form
     */
    protected function processForm(sfWebRequest $request, $service, string $culture)
    {
        $userId = $this->context->user->getAttribute('user_id');
        
        $data = [
            'acquisition_type' => $request->getParameter('acquisition_type'),
            'acquisition_date' => $request->getParameter('acquisition_date') ?: null,
            'acquisition_date_text' => $request->getParameter('acquisition_date_text'),
            'acquisition_price' => $request->getParameter('acquisition_price') ?: null,
            'acquisition_currency' => $request->getParameter('acquisition_currency'),
            'current_status' => $request->getParameter('current_status'),
            'custody_type' => $request->getParameter('custody_type'),
            'certainty_level' => $request->getParameter('certainty_level'),
            'has_gaps' => $request->getParameter('has_gaps') ? 1 : 0,
            'research_status' => $request->getParameter('research_status'),
            'nazi_era_provenance_checked' => $request->getParameter('nazi_era_provenance_checked') ? 1 : 0,
            'nazi_era_provenance_clear' => $request->getParameter('nazi_era_provenance_clear') !== '' ? $request->getParameter('nazi_era_provenance_clear') : null,
            'cultural_property_status' => $request->getParameter('cultural_property_status'),
            'is_complete' => $request->getParameter('is_complete') ? 1 : 0,
            'is_public' => $request->getParameter('is_public') ? 1 : 0,
            'provenance_summary' => $request->getParameter('provenance_summary'),
            'acquisition_notes' => $request->getParameter('acquisition_notes'),
            'gap_description' => $request->getParameter('gap_description'),
            'research_notes' => $request->getParameter('research_notes'),
            'nazi_era_notes' => $request->getParameter('nazi_era_notes'),
            'cultural_property_notes' => $request->getParameter('cultural_property_notes'),
            'created_by' => $userId
        ];

        // Handle current agent
        $agentName = $request->getParameter('current_agent_name');
        if ($agentName) {
            $agentType = $request->getParameter('current_agent_type', 'person');
            $data['provenance_agent_id'] = $service->findOrCreateAgent($agentName, $agentType);
        }

        // Check if record exists
        if ($this->provenance['exists']) {
            $data['id'] = $this->provenance['record']->id;
        }

        $recordId = $service->createRecord($this->resource->id, $data, $culture);

        // Process events
        $this->processEvents($request, $recordId, $service, $culture, $userId);
        // Process documents
        $this->processDocuments($request, $recordId, $userId);

        $this->getUser()->setFlash('notice', 'Provenance saved successfully.');
        $this->redirect(['module' => 'provenance', 'action' => 'view', 'slug' => $this->resource->slug]);
    }

    /**
     * Process provenance events from form
     */
    protected function processEvents(sfWebRequest $request, int $recordId, $service, string $culture, ?int $userId)
    {
        // Delete existing events first to avoid duplicates
        \Illuminate\Database\Capsule\Manager::table('provenance_event')->where('provenance_record_id', $recordId)->delete();
        $eventTypes = $request->getParameter('event_type', []);
        $eventDates = $request->getParameter('event_date', []);
        $eventDateTexts = $request->getParameter('event_date_text', []);
        $fromAgents = $request->getParameter('from_agent', []);
        $toAgents = $request->getParameter('to_agent', []);
        $eventLocations = $request->getParameter('event_location', []);
        $eventCertainties = $request->getParameter('event_certainty', []);
        $eventNotes = $request->getParameter('event_notes', []);

        foreach ($eventTypes as $i => $type) {
            if (empty($type)) continue;

            $eventData = [
                'event_type' => $type,
                'event_date' => !empty($eventDates[$i]) ? $eventDates[$i] : null,
                'event_date_text' => $eventDateTexts[$i] ?? null,
                'event_location' => $eventLocations[$i] ?? null,
                'certainty' => $eventCertainties[$i] ?? 'uncertain',
                'created_by' => $userId
            ];

            // Handle agents
            if (!empty($fromAgents[$i])) {
                $eventData['from_agent_id'] = $service->findOrCreateAgent($fromAgents[$i]);
            }
            if (!empty($toAgents[$i])) {
                $eventData['to_agent_id'] = $service->findOrCreateAgent($toAgents[$i]);
            }

            $service->addEvent($recordId, $eventData, $culture);
        }
    }

    /**
     * Process document uploads from form
     */

    /**
     * Prepare timeline data for D3.js visualization
     */
    protected function prepareTimelineData()
    {
        $timelineItems = [];
        
        // Get raw timeline from provenance
        $timeline = $this->provenance['timeline'] ?? [];
        if (empty($timeline)) {
            return json_encode([]);
        }
        if ($timeline instanceof sfOutputEscaperArrayDecorator) {
            $timeline = $timeline->getRawValue();
        }
        
        foreach ($timeline as $event) {
            $startDate = $event['date'] ?? null;
            if (true) { // Include all events
                $timelineItems[] = [
                    'type' => $event['type_label'] ?? $event['event_type'] ?? 'Event',
                    'label' => $event['from'] ?? $event['to'] ?? $event['type_label'] ?? 'Unknown',
                    'startDate' => $startDate,
                    'endDate' => null,
                    'description' => $event['description'] ?? null,
                    'category' => $this->categorizeEventType($event['event_type'] ?? ''),
                    'certainty' => $event['certainty'] ?? 'unknown',
                    'from' => $event['from'] ?? null,
                    'to' => $event['to'] ?? null,
                    'location' => $event['location'] ?? null
                ];
            }
        }
        
        // Sort by date
        usort($timelineItems, function($a, $b) {
            return strcmp($a['startDate'] ?? '', $b['startDate'] ?? '');
        });
        
        return json_encode($timelineItems);
    }

    /**
     * Categorize event type for timeline visualization
     */
    protected function categorizeEventType($type)
    {
        $type = strtolower($type ?? '');
        if (strpos($type, 'creat') !== false) return 'creation';
        if (strpos($type, 'sale') !== false || strpos($type, 'purchase') !== false) return 'sale';
        if (strpos($type, 'gift') !== false || strpos($type, 'donat') !== false) return 'gift';
        if (strpos($type, 'inherit') !== false || strpos($type, 'bequest') !== false) return 'inheritance';
        if (strpos($type, 'auction') !== false) return 'auction';
        if (strpos($type, 'transfer') !== false) return 'transfer';
        if (strpos($type, 'loan') !== false) return 'loan';
        if (strpos($type, 'theft') !== false || strpos($type, 'confisca') !== false) return 'theft';
        if (strpos($type, 'recov') !== false || strpos($type, 'restit') !== false) return 'recovery';
        return 'event';
    }
    protected function processDocuments(sfWebRequest $request, int $recordId, ?int $userId)
    {
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        
        $docTypes = $request->getParameter('doc_type', []);
        $docTitles = $request->getParameter('doc_title', []);
        $docDates = $request->getParameter('doc_date', []);
        $docUrls = $request->getParameter('doc_url', []);
        $docDescriptions = $request->getParameter('doc_description', []);
        
        $uploadDir = sfConfig::get('sf_upload_dir') . '/provenance';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        foreach ($docTypes as $i => $type) {
            if (empty($type)) continue;
            
            $filePath = null;
            $originalFilename = null;
            $mimeType = null;
            $fileSize = null;
            
            // Handle file upload
            if (isset($_FILES['doc_file']['tmp_name'][$i]) && $_FILES['doc_file']['error'][$i] === UPLOAD_ERR_OK) {
                $originalFilename = $_FILES['doc_file']['name'][$i];
                $mimeType = $_FILES['doc_file']['type'][$i];
                $fileSize = $_FILES['doc_file']['size'][$i];
                
                $ext = pathinfo($originalFilename, PATHINFO_EXTENSION);
                $filename = uniqid('prov_') . '.' . $ext;
                $filePath = '/uploads/provenance/' . $filename;
                
                move_uploaded_file($_FILES['doc_file']['tmp_name'][$i], $uploadDir . '/' . $filename);
            }
            
            $docData = [
                'provenance_record_id' => $recordId,
                'document_type' => $type,
                'title' => $docTitles[$i] ?? null,
                'description' => $docDescriptions[$i] ?? null,
                'document_date' => !empty($docDates[$i]) ? $docDates[$i] : null,
                'filename' => $filename ?? null,
                'original_filename' => $originalFilename,
                'file_path' => $filePath,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'external_url' => $docUrls[$i] ?? null,
                'is_public' => 0,
                'created_by' => $userId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            \Illuminate\Database\Capsule\Manager::table('provenance_document')->insert($docData);
        }
    }

    /**
     * AJAX: Delete document
     */
    public function executeDeleteDocument(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        
        $docId = $request->getParameter('id');
        
        // Get document to delete file
        $doc = \Illuminate\Database\Capsule\Manager::table('provenance_document')->where('id', $docId)->first();
        
        if ($doc && $doc->file_path) {
            $fullPath = sfConfig::get('sf_web_dir') . $doc->file_path;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
        
        \Illuminate\Database\Capsule\Manager::table('provenance_document')->where('id', $docId)->delete();
        
        return $this->renderText(json_encode(['success' => true]));
    }

    /**
     * AJAX: Search agents
     */
    public function executeSearchAgents(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgProvenancePlugin/lib/Service/ProvenanceService.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgProvenancePlugin/lib/Repository/ProvenanceRepository.php';

        $term = $request->getParameter('term', '');
        $service = new \AhgProvenancePlugin\Service\ProvenanceService();
        
        $results = $service->searchAgents($term);
        
        return $this->renderText(json_encode($results));
    }

    /**
     * AJAX: Add event
     */
    public function executeAddEvent(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->context->user->isAuthenticated()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Unauthorized']));
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgProvenancePlugin/lib/Service/ProvenanceService.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgProvenancePlugin/lib/Repository/ProvenanceRepository.php';

        $recordId = $request->getParameter('record_id');
        $service = new \AhgProvenancePlugin\Service\ProvenanceService();
        $userId = $this->context->user->getAttribute('user_id');

        $data = [
            'event_type' => $request->getParameter('event_type'),
            'event_date' => $request->getParameter('event_date') ?: null,
            'event_date_text' => $request->getParameter('event_date_text'),
            'event_location' => $request->getParameter('event_location'),
            'certainty' => $request->getParameter('certainty', 'uncertain'),
            'created_by' => $userId
        ];

        // Handle agents
        if ($request->getParameter('from_agent')) {
            $data['from_agent_id'] = $service->findOrCreateAgent($request->getParameter('from_agent'));
        }
        if ($request->getParameter('to_agent')) {
            $data['to_agent_id'] = $service->findOrCreateAgent($request->getParameter('to_agent'));
        }

        try {
            $eventId = $service->addEvent($recordId, $data);
            return $this->renderText(json_encode(['success' => true, 'event_id' => $eventId]));
        } catch (\Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * AJAX: Delete event
     */
    public function executeDeleteEvent(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->context->user->isAuthenticated()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Unauthorized']));
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgProvenancePlugin/lib/Repository/ProvenanceRepository.php';

        $eventId = $request->getParameter('event_id');
        $repo = new \AhgProvenancePlugin\Repository\ProvenanceRepository();

        try {
            $repo->deleteEvent($eventId);
            return $this->renderText(json_encode(['success' => true]));
        } catch (\Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * Component: Display provenance on information object
     */
    public function executeProvenanceDisplay(sfWebRequest $request)
    {
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgProvenancePlugin/lib/Service/ProvenanceService.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgProvenancePlugin/lib/Repository/ProvenanceRepository.php';

        $objectId = $request->getParameter('objectId');
        $service = new \AhgProvenancePlugin\Service\ProvenanceService();
        $culture = $this->context->user->getCulture();
        
        $this->provenance = $service->getProvenanceForObject($objectId, $culture);
        $this->objectId = $objectId;
    }
}
