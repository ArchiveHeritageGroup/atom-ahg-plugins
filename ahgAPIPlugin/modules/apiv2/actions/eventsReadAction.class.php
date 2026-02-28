<?php

use AtomFramework\Http\Controllers\AhgApiController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * GET /api/v2/events/:id — Single event with related events (same correlation)
 */
class apiv2EventsReadAction extends AhgApiController
{
    public function GET($request)
    {
        if (!$this->hasScope('read') && !$this->hasScope('events:read')) {
            return $this->error(403, 'Forbidden', 'events:read scope required');
        }

        $id = (int) $request->getParameter('id');

        $event = DB::table('ahg_workflow_history as h')
            ->leftJoin('user as u', 'h.performed_by', '=', 'u.id')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('u.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
            })
            ->where('h.id', $id)
            ->select(
                'h.*',
                DB::raw('COALESCE(ai.authorized_form_of_name, u.username) as user_name')
            )
            ->first();

        if (!$event) {
            return $this->notFound('Event not found');
        }

        if (!empty($event->metadata)) {
            $event->metadata = json_decode($event->metadata, true);
        }

        // Get related events (same correlation ID)
        $relatedEvents = [];
        if (!empty($event->correlation_id)) {
            $relatedEvents = DB::table('ahg_workflow_history')
                ->where('correlation_id', $event->correlation_id)
                ->where('id', '!=', $id)
                ->orderBy('performed_at')
                ->select('id', 'action', 'object_id', 'performed_by', 'performed_at')
                ->get()
                ->toArray();
        }

        $this->response->setHttpHeader('X-Correlation-Id', $event->correlation_id ?? uniqid('api-'));

        return $this->success([
            'event' => $event,
            'related_events' => $relatedEvents,
        ]);
    }
}
