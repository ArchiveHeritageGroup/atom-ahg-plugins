<?php

use AtomFramework\Http\Controllers\AhgApiController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * GET /api/v2/events — Browse workflow events with filters
 */
class apiv2EventsBrowseAction extends AhgApiController
{
    public function GET($request)
    {
        if (!$this->hasScope('read') && !$this->hasScope('events:read')) {
            return $this->error(403, 'Forbidden', 'events:read scope required');
        }

        $limit = min((int) $request->getParameter('limit', 50), 200);
        $skip = (int) $request->getParameter('skip', 0);

        $query = DB::table('ahg_workflow_history as h')
            ->leftJoin('user as u', 'h.performed_by', '=', 'u.id')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('u.id', '=', 'ai.id')->where('ai.culture', '=', 'en');
            })
            ->select(
                'h.id', 'h.action', 'h.object_id', 'h.object_type',
                'h.performed_by as user_id',
                DB::raw('COALESCE(ai.authorized_form_of_name, u.username) as user_name'),
                'h.correlation_id', 'h.from_status', 'h.to_status',
                'h.comment', 'h.metadata', 'h.performed_at as created_at'
            )
            ->orderByDesc('h.performed_at');

        // Filters
        if ($objectId = $request->getParameter('object_id')) {
            $query->where('h.object_id', (int) $objectId);
        }
        if ($action = $request->getParameter('action')) {
            $actions = array_map('trim', explode(',', $action));
            $query->whereIn('h.action', $actions);
        }
        if ($userId = $request->getParameter('user_id')) {
            $query->where('h.performed_by', (int) $userId);
        }
        if ($dateFrom = $request->getParameter('date_from')) {
            $query->where('h.performed_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->getParameter('date_to')) {
            $query->where('h.performed_at', '<=', $dateTo . ' 23:59:59');
        }
        if ($correlationId = $request->getParameter('correlation_id')) {
            $query->where('h.correlation_id', $correlationId);
        }

        $total = (clone $query)->count();
        $results = $query->skip($skip)->take($limit)->get()->toArray();

        // Decode metadata JSON
        foreach ($results as &$r) {
            if (!empty($r->metadata)) {
                $r->metadata = json_decode($r->metadata, true);
            }
        }

        $this->response->setHttpHeader('X-Correlation-Id', uniqid('api-'));

        return $this->success([
            'results' => $results,
            'total' => $total,
            'limit' => $limit,
            'skip' => $skip,
        ]);
    }
}
