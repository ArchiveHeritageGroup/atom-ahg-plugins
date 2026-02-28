<?php

use AtomFramework\Http\Controllers\AhgApiController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * GET /api/v2/events/correlation/:correlation_id — Events by correlation ID
 */
class apiv2EventsCorrelationAction extends AhgApiController
{
    public function GET($request)
    {
        if (!$this->hasScope('read') && !$this->hasScope('events:read')) {
            return $this->error(403, 'Forbidden', 'events:read scope required');
        }

        $correlationId = $request->getParameter('correlation_id');

        if (empty($correlationId)) {
            return $this->error(400, 'Bad Request', 'correlation_id is required');
        }

        $events = DB::table('ahg_workflow_history as h')
            ->leftJoin('user as u', 'h.performed_by', '=', 'u.id')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('u.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
            })
            ->where('h.correlation_id', $correlationId)
            ->select(
                'h.id', 'h.action', 'h.object_id', 'h.object_type',
                'h.performed_by as user_id',
                DB::raw('COALESCE(ai.authorized_form_of_name, u.username) as user_name'),
                'h.from_status', 'h.to_status', 'h.comment', 'h.metadata',
                'h.performed_at as created_at'
            )
            ->orderBy('h.performed_at')
            ->get()
            ->toArray();

        foreach ($events as &$e) {
            if (!empty($e->metadata)) {
                $e->metadata = json_decode($e->metadata, true);
            }
        }

        $this->response->setHttpHeader('X-Correlation-Id', $correlationId);

        return $this->success([
            'correlation_id' => $correlationId,
            'events' => $events,
            'count' => count($events),
        ]);
    }
}
