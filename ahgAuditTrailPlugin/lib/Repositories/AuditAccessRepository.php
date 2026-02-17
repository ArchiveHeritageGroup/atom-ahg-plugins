<?php
// plugins/ahgAuditTrailPlugin/lib/Repositories/AuditAccessRepository.php
namespace AtoM\Framework\Plugins\AuditTrail\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\Capsule\Manager as DB;

class AuditAccessRepository
{
    /**
     * Get classified access records from security_access_log
     */
    public function getClassifiedAccess(?string $classification = null, int $limit = 100): Collection
    {
        $query = DB::table('security_access_log as sal')
            ->leftJoin('user as u', 'sal.user_id', '=', 'u.id')
            ->leftJoin('term as t', 'sal.classification_id', '=', 't.id')
            ->leftJoin('term_i18n as ti', function($j) {
                $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('information_object_i18n as ioi', function($j) {
                $j->on('sal.object_id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->orderBy('sal.created_at', 'desc');
        
        if ($classification) {
            $query->where('ti.name', $classification);
        }
        
        return $query->limit($limit)
            ->select([
                'sal.*',
                'u.username',
                'ti.name as classification_name',
                'ioi.title as entity_title'
            ])
            ->get();
    }

    /**
     * Get denied access records
     */
    public function getDeniedAccess(int $limit = 100): Collection
    {
        return DB::table('security_access_log as sal')
            ->leftJoin('user as u', 'sal.user_id', '=', 'u.id')
            ->leftJoin('term as t', 'sal.classification_id', '=', 't.id')
            ->leftJoin('term_i18n as ti', function($j) {
                $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('information_object_i18n as ioi', function($j) {
                $j->on('sal.object_id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('sal.access_granted', 0)
            ->orderBy('sal.created_at', 'desc')
            ->limit($limit)
            ->select([
                'sal.*',
                'u.username',
                'ti.name as classification_name',
                'ioi.title as entity_title'
            ])
            ->get();
    }

    /**
     * Get access by entity
     */
    public function getByEntity(string $entityType, int $entityId, int $limit = 100): Collection
    {
        return DB::table('security_access_log as sal')
            ->leftJoin('user as u', 'sal.user_id', '=', 'u.id')
            ->leftJoin('term_i18n as ti', function($j) {
                $j->on('sal.classification_id', '=', 'ti.id')->where('ti.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('sal.object_id', $entityId)
            ->orderBy('sal.created_at', 'desc')
            ->limit($limit)
            ->select([
                'sal.*',
                'u.username',
                'ti.name as classification_name'
            ])
            ->get();
    }

    /**
     * Get access by user
     */
    public function getByUser(int $userId, int $limit = 100): Collection
    {
        return DB::table('security_access_log as sal')
            ->leftJoin('term_i18n as ti', function($j) {
                $j->on('sal.classification_id', '=', 'ti.id')->where('ti.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('information_object_i18n as ioi', function($j) {
                $j->on('sal.object_id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('sal.user_id', $userId)
            ->orderBy('sal.created_at', 'desc')
            ->limit($limit)
            ->select([
                'sal.*',
                'ti.name as classification_name',
                'ioi.title as entity_title'
            ])
            ->get();
    }

    /**
     * Get download stats (from ahg_audit_access if used for downloads)
     */
    public function getDownloadStats(?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = DB::table('ahg_audit_access')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as downloads, SUM(file_size) as total_bytes')
            ->where('access_type', 'download')
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

    /**
     * Create access record
     */
    public function create(array $data): int
    {
        return DB::table('security_access_log')->insertGetId([
            'user_id' => $data['user_id'] ?? null,
            'object_id' => $data['entity_id'] ?? $data['object_id'] ?? 0,
            'classification_id' => $data['classification_id'] ?? 0,
            'action' => $data['access_type'] ?? $data['action'] ?? 'view',
            'access_granted' => ($data['status'] ?? 'success') === 'success' ? 1 : 0,
            'denial_reason' => $data['denial_reason'] ?? null,
            'ip_address' => $data['ip_address'] ?? $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($data['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
