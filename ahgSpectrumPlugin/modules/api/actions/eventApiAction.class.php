<?php
/**
 * Spectrum Event API Action
 * 
 * RESTful API for Spectrum procedure events.
 * 
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgSpectrumPlugin
 */

class eventApiAction extends sfAction
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        $eventService = new arSpectrumEventService();

        $method = $request->getMethod();
        $objectId = $request->getParameter('object_id');
        $procedureId = $request->getParameter('procedure_id');
        $eventId = $request->getParameter('event_id');

        try {
            switch ($method) {
                case 'GET':
                    if ($eventId) {
                        $result = $this->getEvent($eventService, $eventId);
                    } elseif ($objectId) {
                        $result = $this->getObjectEvents($eventService, $objectId, $procedureId, $request);
                    } else {
                        $result = $this->getRecentEvents($eventService, $request);
                    }
                    break;

                case 'POST':
                    if (!$this->context->user->isAuthenticated()) {
                        throw new Exception('Authentication required', 401);
                    }
                    $result = $this->createEvent($eventService, $request);
                    break;

                default:
                    throw new Exception('Method not allowed', 405);
            }

            return $this->renderJson([
                'success' => true,
                'data' => $result
            ]);

        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            $this->getResponse()->setStatusCode($code);
            
            return $this->renderJson([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function getEvent($eventService, $eventId)
    {
        $conn = Propel::getConnection();
        $stmt = $conn->prepare("SELECT * FROM spectrum_event WHERE id = :id");
        $stmt->execute([':id' => $eventId]);
        
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$event) {
            throw new Exception('Event not found', 404);
        }

        return $this->formatEvent($event);
    }

    protected function getObjectEvents($eventService, $objectId, $procedureId, $request)
    {
        $limit = min((int)$request->getParameter('limit', 50), 200);
        $offset = (int)$request->getParameter('offset', 0);

        $events = $eventService->getObjectEvents($objectId, $procedureId, $limit, $offset);

        return [
            'object_id' => $objectId,
            'procedure_id' => $procedureId,
            'events' => array_map([$this, 'formatEvent'], $events),
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    protected function getRecentEvents($eventService, $request)
    {
        $repositoryId = $request->getParameter('repository_id');
        $procedureId = $request->getParameter('procedure_id');
        $limit = min((int)$request->getParameter('limit', 50), 200);

        $events = $eventService->getRecentEvents($repositoryId, $procedureId, $limit);

        return [
            'events' => array_map([$this, 'formatEvent'], $events)
        ];
    }

    protected function createEvent($eventService, $request)
    {
        $objectId = $request->getParameter('object_id');
        $procedureId = $request->getParameter('procedure_id');
        $eventType = $request->getParameter('event_type');

        if (!$objectId || !$procedureId || !$eventType) {
            throw new Exception('Missing required parameters: object_id, procedure_id, event_type', 400);
        }

        // Validate event type
        if (!isset(arSpectrumEventService::$eventTypeLabels[$eventType])) {
            throw new Exception('Invalid event type', 400);
        }

        $data = [
            'status_from' => $request->getParameter('status_from'),
            'status_to' => $request->getParameter('status_to'),
            'assigned_to_id' => $request->getParameter('assigned_to_id'),
            'due_date' => $request->getParameter('due_date'),
            'location' => $request->getParameter('location'),
            'notes' => $request->getParameter('notes'),
            'metadata' => $request->getParameter('metadata')
        ];

        $eventId = $eventService->createEvent($objectId, $procedureId, $eventType, $data);

        return [
            'event_id' => $eventId,
            'message' => 'Event created successfully'
        ];
    }

    protected function formatEvent($event)
    {
        $procedure = arSpectrumEventService::$procedures[$event['procedure_id']] ?? null;
        $eventTypeLabel = arSpectrumEventService::$eventTypeLabels[$event['event_type']] ?? $event['event_type'];

        return [
            'id' => (int)$event['id'],
            'object_id' => (int)$event['object_id'],
            'object_identifier' => $event['object_identifier'] ?? null,
            'procedure_id' => $event['procedure_id'],
            'procedure_name' => $procedure['name'] ?? $event['procedure_id'],
            'procedure_ref' => $procedure['spectrum_ref'] ?? null,
            'event_type' => $event['event_type'],
            'event_type_label' => $eventTypeLabel,
            'status_from' => $event['status_from'],
            'status_to' => $event['status_to'],
            'status_from_label' => arSpectrumEventService::$statusLabels[$event['status_from']]['label'] ?? null,
            'status_to_label' => arSpectrumEventService::$statusLabels[$event['status_to']]['label'] ?? null,
            'user_id' => $event['user_id'] ? (int)$event['user_id'] : null,
            'user_name' => $event['user_name'] ?? null,
            'assigned_to_id' => $event['assigned_to_id'] ? (int)$event['assigned_to_id'] : null,
            'assigned_to_name' => $event['assigned_to_name'] ?? null,
            'due_date' => $event['due_date'],
            'completed_date' => $event['completed_date'],
            'location' => $event['location'],
            'notes' => $event['notes'],
            'metadata' => $event['metadata'] ? json_decode($event['metadata'], true) : null,
            'created_at' => $event['created_at'],
            'updated_at' => $event['updated_at']
        ];
    }

    protected function renderJson($data)
    {
        $this->getResponse()->setContent(json_encode($data, JSON_PRETTY_PRINT));
        return sfView::NONE;
    }
}
