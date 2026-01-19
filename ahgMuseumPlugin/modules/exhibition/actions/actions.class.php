<?php

/**
 * Exhibition module actions.
 *
 * Handles exhibition management including browsing, creating,
 * editing, and managing exhibition objects and storylines.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class exhibitionActions extends sfActions
{
    /**
     * Browse/list exhibitions.
     */
    public function executeIndex(sfWebRequest $request)
    {
        $service = $this->getExhibitionService();

        // Get filters from request
        $filters = [];
        if ($status = $request->getParameter('status')) {
            $filters['status'] = $status;
        }
        if ($type = $request->getParameter('type')) {
            $filters['exhibition_type'] = $type;
        }
        if ($year = $request->getParameter('year')) {
            $filters['year'] = $year;
        }
        if ($search = $request->getParameter('search')) {
            $filters['search'] = $search;
        }

        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $result = $service->search($filters, $limit, $offset);

        $this->exhibitions = $result['results'];
        $this->total = $result['total'];
        $this->page = $page;
        $this->pages = ceil($result['total'] / $limit);
        $this->filters = $filters;

        // For dropdowns
        $this->types = $service->getTypes();
        $this->statuses = $service->getStatuses();

        // Statistics for sidebar
        $this->stats = $service->getStatistics();
    }

    /**
     * Show exhibition details.
     */
    public function executeShow(sfWebRequest $request)
    {
        $service = $this->getExhibitionService();

        $id = $request->getParameter('id');
        if (is_numeric($id)) {
            $this->exhibition = $service->get((int) $id, true);
        } else {
            $this->exhibition = $service->getBySlug($id);
        }

        if (!$this->exhibition) {
            $this->forward404('Exhibition not found');
        }

        // Get valid transitions for status buttons
        $this->validTransitions = $service->getValidTransitions($this->exhibition['status']);
        $this->statuses = $service->getStatuses();
    }

    /**
     * Create new exhibition form.
     */
    public function executeAdd(sfWebRequest $request)
    {
        $service = $this->getExhibitionService();

        $this->types = $service->getTypes();
        $this->venues = $this->getVenues();
        $this->curators = $this->getCurators();

        if ($request->isMethod('post')) {
            $data = [
                'title' => $request->getParameter('title'),
                'subtitle' => $request->getParameter('subtitle'),
                'description' => $request->getParameter('description'),
                'theme' => $request->getParameter('theme'),
                'exhibition_type' => $request->getParameter('exhibition_type'),
                'opening_date' => $request->getParameter('opening_date') ?: null,
                'closing_date' => $request->getParameter('closing_date') ?: null,
                'venue_id' => $request->getParameter('venue_id') ?: null,
                'venue_name' => $request->getParameter('venue_name'),
                'curator_id' => $request->getParameter('curator_id') ?: null,
                'curator_name' => $request->getParameter('curator_name'),
                'organized_by' => $request->getParameter('organized_by'),
                'budget_amount' => $request->getParameter('budget_amount') ?: null,
                'budget_currency' => $request->getParameter('budget_currency') ?: 'ZAR',
                'project_code' => $request->getParameter('project_code'),
                'notes' => $request->getParameter('notes'),
            ];

            try {
                $userId = $this->getUser()->getAttribute('user_id', 1);
                $exhibitionId = $service->create($data, $userId);

                $this->getUser()->setFlash('notice', 'Exhibition created successfully');
                $this->redirect(['module' => 'exhibition', 'action' => 'show', 'id' => $exhibitionId]);
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Error creating exhibition: '.$e->getMessage());
                $this->formData = $data;
            }
        }
    }

    /**
     * Edit exhibition.
     */
    public function executeEdit(sfWebRequest $request)
    {
        $service = $this->getExhibitionService();

        $this->exhibition = $service->get((int) $request->getParameter('id'));
        if (!$this->exhibition) {
            $this->forward404('Exhibition not found');
        }

        $this->types = $service->getTypes();
        $this->venues = $this->getVenues();
        $this->curators = $this->getCurators();

        if ($request->isMethod('post')) {
            $data = [
                'title' => $request->getParameter('title'),
                'subtitle' => $request->getParameter('subtitle'),
                'description' => $request->getParameter('description'),
                'theme' => $request->getParameter('theme'),
                'exhibition_type' => $request->getParameter('exhibition_type'),
                'opening_date' => $request->getParameter('opening_date') ?: null,
                'closing_date' => $request->getParameter('closing_date') ?: null,
                'venue_id' => $request->getParameter('venue_id') ?: null,
                'venue_name' => $request->getParameter('venue_name'),
                'curator_id' => $request->getParameter('curator_id') ?: null,
                'curator_name' => $request->getParameter('curator_name'),
                'organized_by' => $request->getParameter('organized_by'),
                'budget_amount' => $request->getParameter('budget_amount') ?: null,
                'budget_currency' => $request->getParameter('budget_currency') ?: 'ZAR',
                'project_code' => $request->getParameter('project_code'),
                'notes' => $request->getParameter('notes'),
                'expected_visitors' => $request->getParameter('expected_visitors') ?: null,
                'admission_fee' => $request->getParameter('admission_fee') ?: null,
                'is_free_admission' => $request->getParameter('is_free_admission') ? 1 : 0,
            ];

            try {
                $userId = $this->getUser()->getAttribute('user_id', 1);
                $service->update($this->exhibition['id'], $data, $userId);

                $this->getUser()->setFlash('notice', 'Exhibition updated successfully');
                $this->redirect(['module' => 'exhibition', 'action' => 'show', 'id' => $this->exhibition['id']]);
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Error updating exhibition: '.$e->getMessage());
            }
        }
    }

    /**
     * Change exhibition status (AJAX).
     */
    public function executeTransition(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $service = $this->getExhibitionService();
        $exhibitionId = (int) $request->getParameter('id');
        $newStatus = $request->getParameter('status');
        $reason = $request->getParameter('reason');

        try {
            $userId = $this->getUser()->getAttribute('user_id', 1);
            $service->transitionStatus($exhibitionId, $newStatus, $userId, $reason);

            return $this->renderText(json_encode([
                'success' => true,
                'message' => 'Status changed to '.$newStatus,
            ]));
        } catch (\Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * Manage objects in exhibition.
     */
    public function executeObjects(sfWebRequest $request)
    {
        $service = $this->getExhibitionService();

        $this->exhibition = $service->get((int) $request->getParameter('id'), true);
        if (!$this->exhibition) {
            $this->forward404('Exhibition not found');
        }

        $this->sections = $this->exhibition['sections'] ?? [];
        $this->objects = $this->exhibition['objects'] ?? [];
        $this->objectStatuses = $service::OBJECT_STATUSES;
    }

    /**
     * Add object to exhibition (AJAX).
     */
    public function executeAddObject(sfWebRequest $request)
    {
        $service = $this->getExhibitionService();

        // Get exhibition ID from route parameter 'id'
        $exhibitionId = (int) $request->getParameter('id');
        // Get object ID from form field 'museum_object_id'
        $objectId = (int) $request->getParameter('museum_object_id');

        $data = [
            'section_id' => $request->getParameter('section_id') ?: null,
            'display_position' => $request->getParameter('display_location'),
            'label_text' => $request->getParameter('display_notes'),
            'insurance_value' => $request->getParameter('insurance_value') ?: null,
            'requires_loan' => $request->getParameter('requires_loan') ? true : false,
            'lender_institution' => $request->getParameter('lender_institution'),
        ];

        try {
            $id = $service->addObject($exhibitionId, $objectId, $data);
            $this->getUser()->setFlash('notice', 'Object added to exhibition');
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
        }

        $this->redirect(['module' => 'exhibition', 'action' => 'objects', 'id' => $exhibitionId]);
    }

    /**
     * Update object in exhibition.
     */
    public function executeUpdateObject(sfWebRequest $request)
    {
        $service = $this->getExhibitionService();

        $exhibitionId = (int) $request->getParameter('id');
        $exhibitionObjectId = (int) $request->getParameter('object_id');

        $data = [
            'section_id' => $request->getParameter('section_id') ?: null,
            'display_position' => $request->getParameter('display_location'),
            'sequence_order' => $request->getParameter('display_order') ?: 0,
            'installation_notes' => $request->getParameter('display_notes'),
        ];

        try {
            $service->updateObject($exhibitionObjectId, $data);
            $this->getUser()->setFlash('notice', 'Object updated');
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
        }

        $this->redirect(['module' => 'exhibition', 'action' => 'objects', 'id' => $exhibitionId]);
    }

    /**
     * Remove object from exhibition.
     */
    public function executeRemoveObject(sfWebRequest $request)
    {
        $service = $this->getExhibitionService();

        $exhibitionId = (int) $request->getParameter('id');
        $exhibitionObjectId = (int) $request->getParameter('object_id');

        try {
            $service->removeObject($exhibitionObjectId);
            $this->getUser()->setFlash('notice', 'Object removed from exhibition');
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
        }

        $this->redirect(['module' => 'exhibition', 'action' => 'objects', 'id' => $exhibitionId]);
    }

    /**
     * Reorder objects via drag and drop (AJAX).
     */
    public function executeReorderObjects(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        try {
            $service = $this->getExhibitionService();

            // Get JSON body
            $content = file_get_contents('php://input');
            $data = json_decode($content, true);

            if (empty($data['order']) || !is_array($data['order'])) {
                return $this->renderText(json_encode(['success' => false, 'error' => 'Invalid order data']));
            }

            $service->reorderObjects($data['order']);

            return $this->renderText(json_encode(['success' => true]));
        } catch (\Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    /**
     * Manage sections.
     */
    public function executeSections(sfWebRequest $request)
    {
        $service = $this->getExhibitionService();

        $this->exhibition = $service->get((int) $request->getParameter('id'));
        if (!$this->exhibition) {
            $this->forward404('Exhibition not found');
        }

        $this->sections = $service->getSections($this->exhibition['id']);

        if ($request->isMethod('post')) {
            $data = [
                'title' => $request->getParameter('title'),
                'subtitle' => $request->getParameter('subtitle'),
                'description' => $request->getParameter('description'),
                'narrative' => $request->getParameter('narrative'),
                'section_type' => $request->getParameter('section_type') ?: 'gallery',
                'gallery_name' => $request->getParameter('gallery_name'),
                'floor_level' => $request->getParameter('floor_level'),
                'square_meters' => $request->getParameter('square_meters') ?: null,
                'theme' => $request->getParameter('theme'),
                'color_scheme' => $request->getParameter('color_scheme'),
            ];

            try {
                $service->addSection($this->exhibition['id'], $data);
                $this->getUser()->setFlash('notice', 'Section added');
                $this->redirect(['module' => 'exhibition', 'action' => 'sections', 'id' => $this->exhibition['id']]);
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
            }
        }
    }

    /**
     * Update a section.
     */
    public function executeUpdateSection(sfWebRequest $request)
    {
        $this->forward404Unless($request->isMethod('post'));

        $service = $this->getExhibitionService();
        $exhibitionId = (int) $request->getParameter('id');
        $sectionId = (int) $request->getParameter('section_id');

        $exhibition = $service->get($exhibitionId);
        if (!$exhibition) {
            $this->forward404('Exhibition not found');
        }

        $data = [
            'title' => $request->getParameter('name') ?: $request->getParameter('title'),
            'description' => $request->getParameter('description'),
            'gallery_name' => $request->getParameter('gallery_name'),
            'theme' => $request->getParameter('theme'),
            'display_order' => $request->getParameter('display_order') ?: 0,
        ];

        try {
            $service->updateSection($sectionId, $data);
            $this->getUser()->setFlash('notice', 'Section updated');
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
        }

        $this->redirect(['module' => 'exhibition', 'action' => 'sections', 'id' => $exhibitionId]);
    }

    /**
     * Delete a section.
     */
    public function executeDeleteSection(sfWebRequest $request)
    {
        $this->forward404Unless($request->isMethod('post'));

        $service = $this->getExhibitionService();
        $exhibitionId = (int) $request->getParameter('id');
        $sectionId = (int) $request->getParameter('section_id');

        $exhibition = $service->get($exhibitionId);
        if (!$exhibition) {
            $this->forward404('Exhibition not found');
        }

        try {
            $service->deleteSection($sectionId);
            $this->getUser()->setFlash('notice', 'Section deleted');
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
        }

        $this->redirect(['module' => 'exhibition', 'action' => 'sections', 'id' => $exhibitionId]);
    }

    /**
     * Manage storylines.
     */
    public function executeStorylines(sfWebRequest $request)
    {
        $service = $this->getExhibitionService();

        $this->exhibition = $service->get((int) $request->getParameter('id'));
        if (!$this->exhibition) {
            $this->forward404('Exhibition not found');
        }

        $this->storylines = $service->getStorylines($this->exhibition['id']);

        if ($request->isMethod('post')) {
            $data = [
                'title' => $request->getParameter('title'),
                'description' => $request->getParameter('description'),
                'narrative_type' => $request->getParameter('narrative_type') ?: 'thematic',
                'introduction' => $request->getParameter('introduction'),
                'is_primary' => $request->getParameter('is_primary') ? true : false,
                'target_audience' => $request->getParameter('target_audience') ?: 'all',
                'estimated_duration_minutes' => $request->getParameter('duration') ?: null,
            ];

            try {
                $userId = $this->getUser()->getAttribute('user_id', 1);
                $service->createStoryline($this->exhibition['id'], $data, $userId);
                $this->getUser()->setFlash('notice', 'Storyline created');
                $this->redirect(['module' => 'exhibition', 'action' => 'storylines', 'id' => $this->exhibition['id']]);
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
            }
        }
    }

    /**
     * Add storyline to exhibition.
     */
    public function executeAddStoryline(sfWebRequest $request)
    {
        $this->forward404Unless($request->isMethod('post'));

        $service = $this->getExhibitionService();
        $exhibitionId = (int) $request->getParameter('id');

        $exhibition = $service->get($exhibitionId);
        if (!$exhibition) {
            $this->forward404('Exhibition not found');
        }

        $data = [
            'title' => $request->getParameter('title'),
            'description' => $request->getParameter('description'),
            'narrative_type' => $request->getParameter('type') ?: 'general',
            'target_audience' => $request->getParameter('target_audience') ?: 'all',
            'estimated_duration_minutes' => $request->getParameter('duration_minutes') ?: null,
        ];

        try {
            $userId = $this->getUser()->getAttribute('user_id', 1);
            $service->createStoryline($exhibitionId, $data, $userId);
            $this->getUser()->setFlash('notice', 'Storyline created');
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
        }

        $this->redirect(['module' => 'exhibition', 'action' => 'storylines', 'id' => $exhibitionId]);
    }

    /**
     * Update storyline.
     */
    public function executeUpdateStoryline(sfWebRequest $request)
    {
        $this->forward404Unless($request->isMethod('post'));

        $service = $this->getExhibitionService();
        $exhibitionId = (int) $request->getParameter('id');
        $storylineId = (int) $request->getParameter('storyline_id');

        $exhibition = $service->get($exhibitionId);
        if (!$exhibition) {
            $this->forward404('Exhibition not found');
        }

        $data = [
            'title' => $request->getParameter('title'),
            'description' => $request->getParameter('description'),
            'narrative_type' => $request->getParameter('type') ?: 'general',
            'target_audience' => $request->getParameter('target_audience') ?: 'all',
        ];

        try {
            $service->updateStoryline($storylineId, $data);
            $this->getUser()->setFlash('notice', 'Storyline updated');
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
        }

        $this->redirect(['module' => 'exhibition', 'action' => 'storylines', 'id' => $exhibitionId]);
    }

    /**
     * Delete storyline.
     */
    public function executeDeleteStoryline(sfWebRequest $request)
    {
        $this->forward404Unless($request->isMethod('post'));

        $service = $this->getExhibitionService();
        $exhibitionId = (int) $request->getParameter('id');
        $storylineId = (int) $request->getParameter('storyline_id');

        $exhibition = $service->get($exhibitionId);
        if (!$exhibition) {
            $this->forward404('Exhibition not found');
        }

        try {
            $service->deleteStoryline($storylineId);
            $this->getUser()->setFlash('notice', 'Storyline deleted');
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
        }

        $this->redirect(['module' => 'exhibition', 'action' => 'storylines', 'id' => $exhibitionId]);
    }

    /**
     * View/edit storyline with stops.
     */
    public function executeStoryline(sfWebRequest $request)
    {
        $service = $this->getExhibitionService();

        $this->storyline = $service->getStorylineWithStops((int) $request->getParameter('id'));
        if (!$this->storyline) {
            $this->forward404('Storyline not found');
        }

        $this->exhibition = $service->get($this->storyline['exhibition_id']);
        $this->exhibitionObjects = $service->getObjects($this->storyline['exhibition_id']);
    }

    /**
     * Add stop to storyline.
     */
    public function executeAddStop(sfWebRequest $request)
    {
        $this->forward404Unless($request->isMethod('post'));

        $service = $this->getExhibitionService();
        $exhibitionId = (int) $request->getParameter('id');
        $storylineId = (int) $request->getParameter('storyline_id');
        $exhibitionObjectId = $request->getParameter('exhibition_object_id') ?: null;

        $data = [
            'title' => $request->getParameter('title'),
            'narrative_text' => $request->getParameter('narrative_content'),
            'sequence_order' => $request->getParameter('stop_order') ?: null,
            'audio_duration_seconds' => $request->getParameter('duration_seconds') ?: null,
            'audio_file_path' => $request->getParameter('audio_url'),
            'video_url' => $request->getParameter('video_url'),
        ];

        try {
            $service->addStorylineStop($storylineId, $exhibitionObjectId, $data);
            $this->getUser()->setFlash('notice', 'Stop added to storyline');
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
        }

        $this->redirect(['module' => 'exhibition', 'action' => 'storyline', 'id' => $exhibitionId, 'storyline_id' => $storylineId]);
    }

    /**
     * Update stop in storyline.
     */
    public function executeUpdateStop(sfWebRequest $request)
    {
        $this->forward404Unless($request->isMethod('post'));

        $service = $this->getExhibitionService();
        $exhibitionId = (int) $request->getParameter('id');
        $storylineId = (int) $request->getParameter('storyline_id');
        $stopId = (int) $request->getParameter('stop_id');

        $data = [
            'title' => $request->getParameter('title'),
            'narrative_text' => $request->getParameter('narrative_content'),
            'sequence_order' => $request->getParameter('stop_order') ?: null,
            'audio_duration_seconds' => $request->getParameter('duration_seconds') ?: null,
            'exhibition_object_id' => $request->getParameter('exhibition_object_id') ?: null,
        ];

        try {
            $service->updateStorylineStop($stopId, $data);
            $this->getUser()->setFlash('notice', 'Stop updated');
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
        }

        $this->redirect(['module' => 'exhibition', 'action' => 'storyline', 'id' => $exhibitionId, 'storyline_id' => $storylineId]);
    }

    /**
     * Delete stop from storyline.
     */
    public function executeDeleteStop(sfWebRequest $request)
    {
        $this->forward404Unless($request->isMethod('post'));

        $service = $this->getExhibitionService();
        $exhibitionId = (int) $request->getParameter('id');
        $storylineId = (int) $request->getParameter('storyline_id');
        $stopId = (int) $request->getParameter('stop_id');

        try {
            $service->deleteStorylineStop($stopId);
            $this->getUser()->setFlash('notice', 'Stop deleted');
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
        }

        $this->redirect(['module' => 'exhibition', 'action' => 'storyline', 'id' => $exhibitionId, 'storyline_id' => $storylineId]);
    }

    /**
     * Exhibition events.
     */
    public function executeEvents(sfWebRequest $request)
    {
        $service = $this->getExhibitionService();

        $this->exhibition = $service->get((int) $request->getParameter('id'));
        if (!$this->exhibition) {
            $this->forward404('Exhibition not found');
        }

        $this->events = $service->getEvents($this->exhibition['id']);

        if ($request->isMethod('post')) {
            $data = [
                'title' => $request->getParameter('title'),
                'event_type' => $request->getParameter('event_type'),
                'description' => $request->getParameter('description'),
                'event_date' => $request->getParameter('event_date'),
                'start_time' => $request->getParameter('start_time'),
                'end_time' => $request->getParameter('end_time'),
                'max_attendees' => $request->getParameter('max_attendees') ?: null,
                'requires_registration' => $request->getParameter('requires_registration') ? true : false,
                'is_free' => $request->getParameter('is_free') ? true : false,
                'ticket_price' => $request->getParameter('ticket_price') ?: null,
                'presenter_name' => $request->getParameter('presenter_name'),
            ];

            try {
                $userId = $this->getUser()->getAttribute('user_id', 1);
                $service->createEvent($this->exhibition['id'], $data, $userId);
                $this->getUser()->setFlash('notice', 'Event created');
                $this->redirect(['module' => 'exhibition', 'action' => 'events', 'id' => $this->exhibition['id']]);
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
            }
        }
    }

    /**
     * Add event to exhibition.
     */
    public function executeAddEvent(sfWebRequest $request)
    {
        $this->forward404Unless($request->isMethod('post'));

        $service = $this->getExhibitionService();
        $exhibitionId = (int) $request->getParameter('id');

        $exhibition = $service->get($exhibitionId);
        if (!$exhibition) {
            $this->forward404('Exhibition not found');
        }

        $data = [
            'title' => $request->getParameter('title'),
            'event_type' => $request->getParameter('event_type'),
            'description' => $request->getParameter('description'),
            'event_date' => $request->getParameter('event_date'),
            'start_time' => $request->getParameter('start_time'),
            'end_time' => $request->getParameter('end_time'),
            'location' => $request->getParameter('location'),
            'max_attendees' => $request->getParameter('max_attendees') ?: null,
            'requires_registration' => $request->getParameter('requires_registration') ? true : false,
            'is_free' => $request->getParameter('is_free') ? true : false,
            'ticket_price' => $request->getParameter('ticket_price') ?: null,
            'presenter_name' => $request->getParameter('presenter_name'),
        ];

        try {
            $userId = $this->getUser()->getAttribute('user_id', 1);
            $service->createEvent($exhibitionId, $data, $userId);
            $this->getUser()->setFlash('notice', 'Event created');
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
        }

        $this->redirect(['module' => 'exhibition', 'action' => 'events', 'id' => $exhibitionId]);
    }

    /**
     * Update event.
     */
    public function executeUpdateEvent(sfWebRequest $request)
    {
        $this->forward404Unless($request->isMethod('post'));

        $service = $this->getExhibitionService();
        $exhibitionId = (int) $request->getParameter('id');
        $eventId = (int) $request->getParameter('event_id');

        $exhibition = $service->get($exhibitionId);
        if (!$exhibition) {
            $this->forward404('Exhibition not found');
        }

        $data = [
            'title' => $request->getParameter('title'),
            'event_type' => $request->getParameter('event_type'),
            'description' => $request->getParameter('description'),
            'event_date' => $request->getParameter('event_date'),
            'start_time' => $request->getParameter('start_time'),
            'end_time' => $request->getParameter('end_time'),
            'location' => $request->getParameter('location'),
            'max_attendees' => $request->getParameter('max_attendees') ?: null,
            'requires_registration' => $request->getParameter('requires_registration') ? true : false,
            'is_free' => $request->getParameter('is_free') ? true : false,
            'ticket_price' => $request->getParameter('ticket_price') ?: null,
            'presenter_name' => $request->getParameter('presenter_name'),
        ];

        try {
            $service->updateEvent($eventId, $data);
            $this->getUser()->setFlash('notice', 'Event updated');
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
        }

        $this->redirect(['module' => 'exhibition', 'action' => 'events', 'id' => $exhibitionId]);
    }

    /**
     * Delete event.
     */
    public function executeDeleteEvent(sfWebRequest $request)
    {
        $this->forward404Unless($request->isMethod('post'));

        $service = $this->getExhibitionService();
        $exhibitionId = (int) $request->getParameter('id');
        $eventId = (int) $request->getParameter('event_id');

        $exhibition = $service->get($exhibitionId);
        if (!$exhibition) {
            $this->forward404('Exhibition not found');
        }

        try {
            $service->deleteEvent($eventId);
            $this->getUser()->setFlash('notice', 'Event deleted');
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
        }

        $this->redirect(['module' => 'exhibition', 'action' => 'events', 'id' => $exhibitionId]);
    }

    /**
     * Exhibition checklists.
     */
    public function executeChecklists(sfWebRequest $request)
    {
        $service = $this->getExhibitionService();

        $this->exhibition = $service->get((int) $request->getParameter('id'));
        if (!$this->exhibition) {
            $this->forward404('Exhibition not found');
        }

        $this->checklists = $service->getChecklists($this->exhibition['id']);
        $this->templates = $service->getChecklistTemplates();

        // Create checklist from template
        if ($request->isMethod('post') && $request->getParameter('template_id')) {
            $assignedTo = $request->getParameter('assigned_to') ?: null;

            try {
                $service->createChecklistFromTemplate(
                    $this->exhibition['id'],
                    (int) $request->getParameter('template_id'),
                    $assignedTo
                );
                $this->getUser()->setFlash('notice', 'Checklist created');
                $this->redirect(['module' => 'exhibition', 'action' => 'checklists', 'id' => $this->exhibition['id']]);
            } catch (\Exception $e) {
                $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
            }
        }
    }

    /**
     * Create checklist from template.
     */
    public function executeCreateChecklist(sfWebRequest $request)
    {
        $this->forward404Unless($request->isMethod('post'));

        $service = $this->getExhibitionService();
        $exhibitionId = (int) $request->getParameter('id');

        $exhibition = $service->get($exhibitionId);
        if (!$exhibition) {
            $this->forward404('Exhibition not found');
        }

        $templateId = (int) $request->getParameter('template_id');
        $assignedTo = $request->getParameter('assigned_to') ?: null;

        try {
            $service->createChecklistFromTemplate($exhibitionId, $templateId, $assignedTo);
            $this->getUser()->setFlash('notice', 'Checklist created');
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
        }

        $this->redirect(['module' => 'exhibition', 'action' => 'checklists', 'id' => $exhibitionId]);
    }

    /**
     * Add item to checklist.
     */
    public function executeAddChecklistItem(sfWebRequest $request)
    {
        $this->forward404Unless($request->isMethod('post'));

        $service = $this->getExhibitionService();
        $exhibitionId = (int) $request->getParameter('id');
        $checklistId = (int) $request->getParameter('checklist_id');

        $exhibition = $service->get($exhibitionId);
        if (!$exhibition) {
            $this->forward404('Exhibition not found');
        }

        $data = [
            'name' => $request->getParameter('task_name'),
            'description' => $request->getParameter('notes'),
        ];

        try {
            $service->addChecklistItem($checklistId, $data);
            $this->getUser()->setFlash('notice', 'Item added to checklist');
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', 'Error: '.$e->getMessage());
        }

        $this->redirect(['module' => 'exhibition', 'action' => 'checklists', 'id' => $exhibitionId]);
    }

    /**
     * Complete checklist item (AJAX).
     */
    public function executeCompleteItem(sfWebRequest $request)
    {
        $service = $this->getExhibitionService();

        // Exhibition ID is in URL, item ID is in POST data
        $exhibitionId = (int) $request->getParameter('id');
        $itemId = (int) $request->getParameter('item_id');
        $userId = $this->getUser()->getAttribute('user_id', 1);
        $notes = $request->getParameter('notes');

        $service->completeChecklistItem($itemId, $userId, $notes);

        $this->redirect(['module' => 'exhibition', 'action' => 'checklists', 'id' => $exhibitionId]);
    }

    /**
     * Generate object list report.
     */
    public function executeObjectList(sfWebRequest $request)
    {
        $service = $this->getExhibitionService();

        $this->exhibition = $service->get((int) $request->getParameter('id'));
        if (!$this->exhibition) {
            $this->forward404('Exhibition not found');
        }

        $this->objects = $service->generateObjectList($this->exhibition['id']);
        $this->sections = $service->getSections($this->exhibition['id']);

        // Export format
        $format = $request->getParameter('format');
        if ('csv' === $format) {
            $this->setLayout(false);
            $response = $this->getResponse();
            $response->setContentType('text/csv');
            $response->setHttpHeader('Content-Disposition', 'attachment; filename="exhibition_objects.csv"');

            return $this->renderPartial('objectListCsv');
        }
    }

    /**
     * Dashboard showing all active exhibitions.
     */
    public function executeDashboard(sfWebRequest $request)
    {
        $service = $this->getExhibitionService();

        // Current exhibitions
        $current = $service->search(['status' => 'open'], 10, 0);
        $this->currentExhibitions = $current['results'];

        // Upcoming
        $upcoming = $service->search(['upcoming' => true], 10, 0);
        $this->upcomingExhibitions = $upcoming['results'];

        // Installation phase
        $installing = $service->search(['status' => 'installation'], 10, 0);
        $this->installingExhibitions = $installing['results'];

        // Statistics
        $this->stats = $service->getStatistics();
    }

    /**
     * Search objects for adding to exhibition (AJAX).
     */
    public function executeSearchObjects(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $query = $request->getParameter('q');
        $exhibitionId = (int) $request->getParameter('exhibition_id');

        // Get existing object IDs in exhibition
        $existingIds = \Illuminate\Database\Capsule\Manager::table('exhibition_object')
            ->where('exhibition_id', $exhibitionId)
            ->pluck('information_object_id')
            ->toArray();

        // Search objects
        $objects = \Illuminate\Database\Capsule\Manager::table('information_object as io')
            ->join('information_object_i18n as ioi', function ($join) {
                $join->on('ioi.id', '=', 'io.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('digital_object as do', 'do.object_id', '=', 'io.id')
            ->where('io.id', '!=', 1) // Exclude root
            ->whereNotIn('io.id', $existingIds ?: [0])
            ->where(function ($q) use ($query) {
                $q->where('io.identifier', 'LIKE', "%{$query}%")
                    ->orWhere('ioi.title', 'LIKE', "%{$query}%");
            })
            ->select('io.id', 'io.identifier as object_number', 'ioi.title', 'do.path as thumbnail')
            ->limit(20)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'object_number' => $row->object_number,
                    'title' => $row->title,
                    'thumbnail' => $row->thumbnail ? '/uploads/'.$row->thumbnail : null,
                ];
            })
            ->all();

        return $this->renderText(json_encode(['objects' => $objects]));
    }

    /**
     * Get exhibition service.
     */
    protected function getExhibitionService()
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_plugins_dir').'/ahgMuseumPlugin/lib/Services/Exhibition/ExhibitionService.php';

        return new \arMuseumMetadataPlugin\Services\Exhibition\ExhibitionService(
            \Illuminate\Database\Capsule\Manager::connection()
        );
    }

    /**
     * Get venues for dropdown.
     */
    protected function getVenues()
    {
        return \Illuminate\Database\Capsule\Manager::table('exhibition_venue')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->all();
    }

    /**
     * Get curators (staff members) for dropdown.
     */
    protected function getCurators()
    {
        // This would query users with curator role
        // For now, return empty - can be populated from user table
        return [];
    }
}
