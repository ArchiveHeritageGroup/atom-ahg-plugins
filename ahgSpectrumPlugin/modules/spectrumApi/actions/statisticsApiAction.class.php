<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Spectrum Statistics API Action
 *
 * RESTful API for Spectrum procedure statistics and reporting.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgSpectrumPlugin
 */

class statisticsApiAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        $eventService = new ahgSpectrumEventService();

        $type = $request->getParameter('type', 'overview');
        $repositoryId = $request->getParameter('repository_id');
        $dateFrom = $request->getParameter('date_from');
        $dateTo = $request->getParameter('date_to');

        try {
            switch ($type) {
                case 'overview':
                    $result = $this->getOverview($eventService, $repositoryId, $dateFrom, $dateTo);
                    break;

                case 'procedures':
                    $result = $this->getProcedureStats($eventService, $repositoryId, $dateFrom, $dateTo);
                    break;

                case 'overdue':
                    $result = $this->getOverdueStats($eventService, $repositoryId);
                    break;

                case 'object':
                    $objectId = $request->getParameter('object_id');
                    if (!$objectId) {
                        throw new Exception('object_id required', 400);
                    }
                    $result = $this->getObjectStats($eventService, $objectId);
                    break;

                case 'timeline':
                    $result = $this->getTimeline($eventService, $repositoryId, $dateFrom, $dateTo);
                    break;

                default:
                    throw new Exception('Invalid type parameter', 400);
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

    protected function getOverview($eventService, $repositoryId, $dateFrom, $dateTo)
    {
        $stats = $eventService->getProcedureStatistics($repositoryId, $dateFrom, $dateTo);
        $overdue = $eventService->getOverdueProcedures($repositoryId);

        $totalObjects = 0;
        $totalCompleted = 0;
        $totalInProgress = 0;

        foreach ($stats as $stat) {
            $totalObjects = max($totalObjects, $stat['total_objects']);
            $totalCompleted += $stat['completed'];
            $totalInProgress += $stat['in_progress'];
        }

        return [
            'summary' => [
                'total_objects' => $totalObjects,
                'total_completed' => $totalCompleted,
                'total_in_progress' => $totalInProgress,
                'total_overdue' => count($overdue),
                'procedure_count' => count(ahgSpectrumEventService::$procedures)
            ],
            'filters' => [
                'repository_id' => $repositoryId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ]
        ];
    }

    protected function getProcedureStats($eventService, $repositoryId, $dateFrom, $dateTo)
    {
        $stats = $eventService->getProcedureStatistics($repositoryId, $dateFrom, $dateTo);

        $result = [];
        foreach ($stats as $procId => $stat) {
            $completionRate = $stat['total_objects'] > 0 
                ? round(($stat['completed'] / $stat['total_objects']) * 100, 1) 
                : 0;

            $result[] = [
                'procedure_id' => $procId,
                'name' => $stat['name'],
                'spectrum_ref' => $stat['spectrum_ref'],
                'category' => $stat['category'],
                'total_objects' => $stat['total_objects'],
                'completed' => $stat['completed'],
                'in_progress' => $stat['in_progress'],
                'pending_review' => $stat['pending_review'],
                'overdue' => $stat['overdue'],
                'completion_rate' => $completionRate
            ];
        }

        return [
            'procedures' => $result,
            'filters' => [
                'repository_id' => $repositoryId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ]
        ];
    }

    protected function getOverdueStats($eventService, $repositoryId)
    {
        $overdue = $eventService->getOverdueProcedures($repositoryId);

        // Group by procedure
        $byProcedure = [];
        foreach ($overdue as $item) {
            $procId = $item['procedure_id'];
            if (!isset($byProcedure[$procId])) {
                $byProcedure[$procId] = [
                    'procedure_id' => $procId,
                    'name' => ahgSpectrumEventService::$procedures[$procId]['name'] ?? $procId,
                    'count' => 0,
                    'max_days_overdue' => 0,
                    'avg_days_overdue' => 0,
                    'items' => []
                ];
            }
            $byProcedure[$procId]['count']++;
            $byProcedure[$procId]['max_days_overdue'] = max(
                $byProcedure[$procId]['max_days_overdue'], 
                $item['days_overdue']
            );
            $byProcedure[$procId]['items'][] = [
                'object_id' => $item['object_id'],
                'object_identifier' => $item['object_identifier'],
                'object_slug' => $item['object_slug'],
                'due_date' => $item['due_date'],
                'days_overdue' => (int)$item['days_overdue'],
                'assigned_to' => $item['assigned_to_name']
            ];
        }

        // Calculate averages
        foreach ($byProcedure as &$proc) {
            $totalDays = array_sum(array_column($proc['items'], 'days_overdue'));
            $proc['avg_days_overdue'] = round($totalDays / $proc['count'], 1);
        }

        return [
            'total_overdue' => count($overdue),
            'by_procedure' => array_values($byProcedure),
            'repository_id' => $repositoryId
        ];
    }

    protected function getObjectStats($eventService, $objectId)
    {
        $statuses = $eventService->getAllProcedureStatuses($objectId);
        $progress = $eventService->calculateObjectProgress($objectId);
        $events = $eventService->getObjectEvents($objectId, null, 10);

        $procedures = [];
        foreach ($statuses as $procId => $status) {
            $statusInfo = ahgSpectrumEventService::$statusLabels[$status['current_status']] ?? null;
            
            $procedures[] = [
                'procedure_id' => $procId,
                'name' => $status['name'],
                'spectrum_ref' => $status['spectrum_ref'],
                'category' => $status['category'],
                'current_status' => $status['current_status'],
                'status_label' => $statusInfo['label'] ?? $status['current_status'],
                'status_color' => $statusInfo['color'] ?? '#95a5a6',
                'due_date' => $status['due_date'],
                'last_update' => $status['last_update'],
                'assigned_to_id' => $status['assigned_to_id']
            ];
        }

        return [
            'object_id' => (int)$objectId,
            'progress' => $progress,
            'procedures' => $procedures,
            'recent_events' => array_slice($events, 0, 5)
        ];
    }

    protected function getTimeline($eventService, $repositoryId, $dateFrom, $dateTo)
    {
        // Get events grouped by date using Laravel Query Builder
        $query = \Illuminate\Database\Capsule\Manager::table('spectrum_event as e')
            ->join('information_object as io', 'e.object_id', '=', 'io.id')
            ->selectRaw('DATE(e.created_at) as event_date, e.event_type, COUNT(*) as event_count');

        if ($repositoryId) {
            $query->where('io.repository_id', $repositoryId);
        }

        if ($dateFrom) {
            $query->where('e.created_at', '>=', $dateFrom . ' 00:00:00');
        }

        if ($dateTo) {
            $query->where('e.created_at', '<=', $dateTo . ' 23:59:59');
        }

        $results = $query->groupBy('event_date', 'e.event_type')
            ->orderBy('event_date', 'desc')
            ->limit(365)
            ->get();

        // Organize by date
        $timeline = [];
        foreach ($results as $row) {
            $date = $row->event_date;
            if (!isset($timeline[$date])) {
                $timeline[$date] = [
                    'date' => $date,
                    'total' => 0,
                    'events' => []
                ];
            }
            $timeline[$date]['total'] += $row->event_count;
            $timeline[$date]['events'][$row->event_type] = (int)$row->event_count;
        }

        return [
            'timeline' => array_values($timeline),
            'filters' => [
                'repository_id' => $repositoryId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ]
        ];
    }

    protected function renderJson($data)
    {
        $this->getResponse()->setContent(json_encode($data, JSON_PRETTY_PRINT));
        return sfView::NONE;
    }
}
