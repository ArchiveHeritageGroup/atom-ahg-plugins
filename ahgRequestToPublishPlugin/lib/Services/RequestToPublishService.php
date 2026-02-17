<?php

declare(strict_types=1);

namespace ahgRequestToPublishPlugin\Services;

require_once __DIR__ . '/../Repositories/RequestToPublishRepository.php';

use ahgRequestToPublishPlugin\Repositories\RequestToPublishRepository;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Request to Publish Service
 *
 * Business logic for publication requests.
 *
 * @package    AtoM
 * @subpackage ahgRequestToPublishPlugin
 * @author     The Archive and Heritage Group
 */
class RequestToPublishService
{
    protected RequestToPublishRepository $repository;

    public function __construct()
    {
        $this->repository = new RequestToPublishRepository();
    }

    /**
     * Submit a new publication request
     */
    public function submitRequest(array $data): int
    {
        // Validate required fields
        $required = ['object_id', 'rtp_name', 'rtp_surname', 'rtp_email'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Field '$field' is required");
            }
        }

        // Validate email
        if (!filter_var($data['rtp_email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address');
        }

        return $this->repository->create($data);
    }

    /**
     * Approve a request
     */
    public function approveRequest(int $id, ?string $adminNotes = null): bool
    {
        $data = ['status_id' => 219]; // APPROVED_ID
        if ($adminNotes) {
            $data['rtp_admin_notes'] = $adminNotes;
        }
        return $this->repository->update($id, $data);
    }

    /**
     * Reject a request
     */
    public function rejectRequest(int $id, ?string $adminNotes = null): bool
    {
        $data = ['status_id' => 221]; // REJECTED_ID
        if ($adminNotes) {
            $data['rtp_admin_notes'] = $adminNotes;
        }
        return $this->repository->update($id, $data);
    }

    /**
     * Get request with related object info
     */
    public function getRequestWithObject(int $id): ?object
    {
        $request = $this->repository->findById($id);
        
        if (!$request) {
            return null;
        }

        // Get information object details
        if ($request->object_id) {
            $object = DB::table('information_object as io')
                ->leftJoin('information_object_i18n as ioi', function ($join) {
                    $join->on('io.id', '=', 'ioi.id')
                        ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                })
                ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
                ->where('io.id', $request->object_id)
                ->select('io.*', 'ioi.title', 's.slug')
                ->first();

            $request->object = $object;
        }

        return $request;
    }

    /**
     * Get digital objects for a request
     */
    public function getDigitalObjects(int $objectId): array
    {
        return DB::table('digital_object as do')
            ->where('do.object_id', $objectId)
            ->orWhere('do.information_object_id', $objectId)
            ->select('do.*')
            ->get()
            ->toArray();
    }

    /**
     * Check if user has pending request for object
     */
    public function hasPendingRequest(int $objectId, string $email): bool
    {
        return DB::table('request_to_publish')
            ->where('object_id', $objectId)
            ->where('rtp_email', $email)
            ->where('status_id', 220) // IN_REVIEW_ID
            ->exists();
    }

    /**
     * Get statistics for dashboard
     */
    public function getStatistics(): array
    {
        $counts = $this->repository->getStatusCounts();
        
        // Get recent requests (last 7 days)
        $recentCount = DB::table('request_to_publish')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        // Get average response time for completed requests
        $avgResponseTime = DB::table('request_to_publish')
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_hours')
            ->value('avg_hours');

        return [
            'total' => $counts['all'],
            'pending' => $counts['pending'],
            'approved' => $counts['approved'],
            'rejected' => $counts['rejected'],
            'recent_7_days' => $recentCount,
            'avg_response_hours' => round($avgResponseTime ?? 0, 1)
        ];
    }
}
