<?php

namespace AhgRegistry\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

class SyncRepository
{
    protected string $table = 'registry_sync_log';

    public function create(array $data): int
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');

        return DB::table($this->table)->insertGetId($data);
    }

    public function findByInstance(int $instanceId, array $params = []): array
    {
        $query = DB::table($this->table)->where('instance_id', $instanceId);

        if (!empty($params['event_type'])) {
            $query->where('event_type', $params['event_type']);
        }
        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        $total = $query->count();

        $sort = $params['sort'] ?? 'created_at';
        $direction = $params['direction'] ?? 'desc';
        $limit = $params['limit'] ?? 50;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $items = $query->orderBy($sort, $direction)
                       ->limit($limit)
                       ->offset($offset)
                       ->get();

        return ['items' => $items, 'total' => $total, 'page' => (int) $page];
    }

    public function getRecentByInstance(int $instanceId, int $limit = 20): array
    {
        return DB::table($this->table)
            ->where('instance_id', $instanceId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function countByEvent(array $filters = []): array
    {
        $query = DB::table($this->table);

        if (!empty($filters['instance_id'])) {
            $query->where('instance_id', $filters['instance_id']);
        }
        if (!empty($filters['since'])) {
            $query->where('created_at', '>=', $filters['since']);
        }

        return $query->selectRaw('event_type, status, COUNT(*) as cnt')
                     ->groupBy('event_type', 'status')
                     ->get()
                     ->all();
    }

    public function getErrorLogs(array $params = []): array
    {
        $query = DB::table($this->table)->where('status', 'error');

        if (!empty($params['instance_id'])) {
            $query->where('instance_id', $params['instance_id']);
        }
        if (!empty($params['since'])) {
            $query->where('created_at', '>=', $params['since']);
        }

        $total = $query->count();

        $limit = $params['limit'] ?? 50;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $items = $query->orderBy('created_at', 'desc')
                       ->limit($limit)
                       ->offset($offset)
                       ->get();

        return ['items' => $items, 'total' => $total, 'page' => (int) $page];
    }
}
