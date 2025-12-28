<?php

// plugins/ahgAuditTrailPlugin/lib/Repositories/AuditAccessRepository.php

namespace AtoM\Framework\Plugins\AuditTrail\Repositories;

use AtoM\Framework\Plugins\AuditTrail\Models\AuditAccess;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\Capsule\Manager as Capsule;

class AuditAccessRepository
{
    public function create(array $data): AuditAccess
    {
        return AuditAccess::create($data);
    }

    public function getByEntity(string $entityType, int $entityId, int $limit = 100): Collection
    {
        return AuditAccess::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getByUser(int $userId, int $limit = 100): Collection
    {
        return AuditAccess::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getClassifiedAccess(?string $classification = null, int $limit = 100): Collection
    {
        $query = AuditAccess::whereNotNull('security_classification')
            ->orderBy('created_at', 'desc');
        
        if ($classification) {
            $query->where('security_classification', $classification);
        }
        
        return $query->limit($limit)->get();
    }

    public function getDeniedAccess(int $limit = 100): Collection
    {
        return AuditAccess::where('status', 'denied')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getDownloadStats(?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = Capsule::table('ahg_audit_access')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as downloads, SUM(file_size) as total_bytes')
            ->where('access_type', AuditAccess::ACCESS_DOWNLOAD)
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date', 'desc');
        
        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }
        
        return $query->get()->toArray();
    }
}