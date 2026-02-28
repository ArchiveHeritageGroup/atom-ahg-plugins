<?php

use AtomFramework\Http\Controllers\AhgApiController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * GET /api/v2/audit — Browse audit log with filters
 * NOTE: old_values/new_values excluded by default (PII) — add ?include_values=true with audit:admin scope
 */
class apiv2AuditBrowseAction extends AhgApiController
{
    public function GET($request)
    {
        if (!$this->hasScope('read') && !$this->hasScope('audit:read')) {
            return $this->error(403, 'Forbidden', 'audit:read scope required');
        }

        // Check if ahg_audit_log table exists
        $tableExists = DB::select("SHOW TABLES LIKE 'ahg_audit_log'");
        if (empty($tableExists)) {
            return $this->error(404, 'Not Found', 'Audit trail not installed');
        }

        $limit = min((int) $request->getParameter('limit', 50), 200);
        $skip = (int) $request->getParameter('skip', 0);
        $includeValues = $request->getParameter('include_values') === 'true'
            && ($this->hasScope('audit:admin') || $this->isAdmin());

        // Build select columns
        $columns = ['id', 'action', 'entity_type', 'entity_id', 'user_id', 'description',
                     'changed_fields', 'ip_address', 'created_at'];
        if ($includeValues) {
            $columns[] = 'old_values';
            $columns[] = 'new_values';
        }

        $query = DB::table('ahg_audit_log')
            ->select($columns)
            ->orderByDesc('created_at');

        // Filters
        if ($entityType = $request->getParameter('entity_type')) {
            $query->where('entity_type', $entityType);
        }
        if ($entityId = $request->getParameter('entity_id')) {
            $query->where('entity_id', (int) $entityId);
        }
        if ($action = $request->getParameter('action')) {
            $actions = array_map('trim', explode(',', $action));
            $query->whereIn('action', $actions);
        }
        if ($userId = $request->getParameter('user_id')) {
            $query->where('user_id', (int) $userId);
        }
        if ($dateFrom = $request->getParameter('date_from')) {
            $query->where('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->getParameter('date_to')) {
            $query->where('created_at', '<=', $dateTo . ' 23:59:59');
        }

        $total = (clone $query)->count();
        $results = $query->skip($skip)->take($limit)->get()->toArray();

        // Decode JSON fields
        foreach ($results as &$r) {
            if (!empty($r->changed_fields)) {
                $r->changed_fields = json_decode($r->changed_fields, true);
            }
            if ($includeValues) {
                if (!empty($r->old_values)) {
                    $r->old_values = json_decode($r->old_values, true);
                }
                if (!empty($r->new_values)) {
                    $r->new_values = json_decode($r->new_values, true);
                }
            }
        }

        $this->response->setHttpHeader('X-Correlation-Id', uniqid('api-'));

        return $this->success([
            'results' => $results,
            'total' => $total,
            'limit' => $limit,
            'skip' => $skip,
            'includes_values' => $includeValues,
        ]);
    }
}
