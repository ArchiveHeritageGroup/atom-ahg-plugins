<?php

// plugins/ahgAuditTrailPlugin/lib/Repositories/AuditLogRepository.php

namespace AtoM\Framework\Plugins\AuditTrail\Repositories;

use AtoM\Framework\Plugins\AuditTrail\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\Capsule\Manager as Capsule;

class AuditLogRepository
{
    public function create(array $data): AuditLog
    {
        return AuditLog::create($data);
    }

    public function find(int $id): ?AuditLog
    {
        return AuditLog::find($id);
    }

    public function findByUuid(string $uuid): ?AuditLog
    {
        return AuditLog::where('uuid', $uuid)->first();
    }

    public function getFiltered(array $filters = [], int $limit = 50, int $page = 1): array
    {
        $query = AuditLog::query()->orderBy('created_at', 'desc');
        $this->applyFilters($query, $filters);
        
        $total = $query->count();
        $offset = ($page - 1) * $limit;
        $results = $query->offset($offset)->limit($limit)->get();
        
        return [
            'data' => $results,
            'total' => $total,
            'per_page' => $limit,
            'current_page' => $page,
            'last_page' => $total > 0 ? (int) ceil($total / $limit) : 1,
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => min($offset + $limit, $total),
        ];
    }

    public function getEntityHistory(string $entityType, int $entityId, int $limit = 100): Collection
    {
        return AuditLog::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getUserActivity(int $userId, ?Carbon $from = null, ?Carbon $to = null, int $limit = 100): Collection
    {
        $query = AuditLog::where('user_id', $userId)->orderBy('created_at', 'desc');
        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }
        return $query->limit($limit)->get();
    }

    public function getActivitySummary(?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = Capsule::table('ahg_audit_log')
            ->selectRaw('action, COUNT(*) as count')
            ->groupBy('action');
        
        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }
        
        $results = $query->get();
        $summary = [];
        foreach ($results as $row) {
            $summary[$row->action] = $row->count;
        }
        return $summary;
    }

    public function getEntityTypeStats(?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = Capsule::table('ahg_audit_log')
            ->selectRaw('entity_type, action, COUNT(*) as count')
            ->groupBy('entity_type', 'action');
        
        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }
        
        $stats = [];
        foreach ($query->get() as $row) {
            if (!isset($stats[$row->entity_type])) {
                $stats[$row->entity_type] = ['total' => 0, 'actions' => []];
            }
            $stats[$row->entity_type]['actions'][$row->action] = $row->count;
            $stats[$row->entity_type]['total'] += $row->count;
        }
        return $stats;
    }

    public function getUserStats(?Carbon $from = null, ?Carbon $to = null, int $limit = 20): Collection
    {
        $query = Capsule::table('ahg_audit_log')
            ->selectRaw('user_id, username, COUNT(*) as action_count, COUNT(DISTINCT DATE(created_at)) as active_days, MAX(created_at) as last_activity')
            ->whereNotNull('user_id')
            ->groupBy('user_id', 'username')
            ->orderByDesc('action_count')
            ->limit($limit);
        
        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }
        
        return $query->get();
    }

    public function getSecurityAudit(?string $classification = null, int $limit = 100): Collection
    {
        $query = AuditLog::whereNotNull('security_classification')
            ->orderBy('created_at', 'desc');
        
        if ($classification) {
            $query->where('security_classification', $classification);
        }
        
        return $query->limit($limit)->get();
    }

    public function getFailedActions(int $limit = 100): Collection
    {
        return AuditLog::whereIn('status', [AuditLog::STATUS_FAILURE, AuditLog::STATUS_DENIED])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    protected function applyFilters($query, array $filters): void
    {
        foreach (['user_id', 'action', 'entity_type', 'entity_id', 'status', 'ip_address', 'security_classification', 'module'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        if (!empty($filters['username'])) {
            $query->where('username', 'like', "%{$filters['username']}%");
        }
        if (!empty($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }
    }
}