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
     * Edit provenance record
     */
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

        $this->getUser()->setFlash('notice', 'Provenance saved successfully.');
        $this->redirect(['module' => 'provenance', 'action' => 'view', 'slug' => $this->resource->slug]);
    }

    /**
     * Process provenance events from form
     */
    protected function processEvents(sfWebRequest $request, int $recordId, $service, string $culture, ?int $userId)
    {
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
