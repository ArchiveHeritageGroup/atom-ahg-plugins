<?php

// plugins/ahgAuditTrailPlugin/lib/Repositories/AuditAuthenticationRepository.php

namespace AtoM\Framework\Plugins\AuditTrail\Repositories;

use AtoM\Framework\Plugins\AuditTrail\Models\AuditAuthentication;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AuditAuthenticationRepository
{
    public function create(array $data): AuditAuthentication
    {
        return AuditAuthentication::create($data);
    }

    public function getByUser(int $userId, int $limit = 50): Collection
    {
        return AuditAuthentication::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getFailedAttempts(?string $ip = null, ?string $username = null, ?Carbon $since = null): int
    {
        $query = AuditAuthentication::where('event_type', AuditAuthentication::EVENT_FAILED_LOGIN);
        
        if ($ip) {
            $query->where('ip_address', $ip);
        }
        if ($username) {
            $query->where('username', $username);
        }
        if ($since) {
            $query->where('created_at', '>=', $since);
        }
        
        return $query->count();
    }

    public function getRecentLogins(int $limit = 50): Collection
    {
        return AuditAuthentication::where('event_type', AuditAuthentication::EVENT_LOGIN)
            ->where('status', 'success')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getSuspiciousActivity(int $limit = 100): Collection
    {
        return AuditAuthentication::where(function ($q) {
            $q->where('event_type', AuditAuthentication::EVENT_FAILED_LOGIN)
              ->orWhere('event_type', AuditAuthentication::EVENT_ACCOUNT_LOCKED)
              ->orWhere('status', 'failure');
        })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}