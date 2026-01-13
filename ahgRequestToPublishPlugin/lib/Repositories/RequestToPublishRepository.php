<?php

declare(strict_types=1);

namespace ahgRequestToPublishPlugin\Repositories;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;

/**
 * Request to Publish Repository
 *
 * Laravel Query Builder implementation for publication requests.
 * Uses AtoM's standard object + i18n table pattern.
 *
 * @package    AtoM
 * @subpackage ahgRequestToPublishPlugin
 * @author     The Archive and Heritage Group
 */
class RequestToPublishRepository
{
    protected string $table = 'request_to_publish';
    protected string $i18nTable = 'request_to_publish_i18n';
    protected string $culture = 'en';

    /**
     * Find request by ID
     */
    public function findById(int $id): ?object
    {
        return DB::table($this->table . ' as rtp')
            ->leftJoin($this->i18nTable . ' as i18n', function ($join) {
                $join->on('rtp.id', '=', 'i18n.id')
                    ->where('i18n.culture', '=', $this->culture);
            })
            ->leftJoin('slug as s', 'rtp.id', '=', 's.object_id')
            ->where('rtp.id', $id)
            ->select('rtp.*', 'i18n.*', 's.slug')
            ->first();
    }

    /**
     * Find request by slug
     */
    public function findBySlug(string $slug): ?object
    {
        return DB::table($this->table . ' as rtp')
            ->join('slug as s', 'rtp.id', '=', 's.object_id')
            ->leftJoin($this->i18nTable . ' as i18n', function ($join) {
                $join->on('rtp.id', '=', 'i18n.id')
                    ->where('i18n.culture', '=', $this->culture);
            })
            ->where('s.slug', $slug)
            ->select('rtp.*', 'i18n.*', 's.slug')
            ->first();
    }

    /**
     * Get all requests with pagination
     */
    public function paginate(int $page = 1, int $perPage = 25, ?string $status = null, ?string $sort = 'created_at', string $order = 'desc'): array
    {
        $query = DB::table($this->table . ' as rtp')
            ->leftJoin($this->i18nTable . ' as i18n', function ($join) {
                $join->on('rtp.id', '=', 'i18n.id')
                    ->where('i18n.culture', '=', $this->culture);
            })
            ->leftJoin('slug as s', 'rtp.id', '=', 's.object_id')
            ->leftJoin('information_object as io', 'i18n.object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $this->culture);
            })
            ->leftJoin('slug as ios', 'io.id', '=', 'ios.object_id')
            ->select(
                'rtp.id',
                'rtp.source_culture',
                'i18n.*',
                's.slug',
                'ioi.title as object_title',
                'io.identifier as object_identifier',
                'ios.slug as object_slug'
            );

        // Filter by status
        if ($status && $status !== 'all') {
            $query->where('i18n.status_id', $this->getStatusId($status));
        }

        // Get total count
        $total = $query->count();

        // Apply sorting
        $sortColumn = in_array($sort, ['created_at', 'status_id']) ? 'i18n.' . $sort : 'i18n.created_at';
        $query->orderBy($sortColumn, $order);

        // Apply pagination
        $offset = ($page - 1) * $perPage;
        $results = $query->offset($offset)->limit($perPage)->get();

        return [
            'results' => $results,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'pages' => (int) ceil($total / $perPage)
        ];
    }

    /**
     * Count by status
     */
    public function countByStatus(?string $status = null): int
    {
        $query = DB::table($this->i18nTable)
            ->where('culture', $this->culture);
        
        if ($status && $status !== 'all') {
            $query->where('status_id', $this->getStatusId($status));
        }
        
        return $query->count();
    }

    /**
     * Create new request
     */
    public function create(array $data): int
    {
        // First create object record
        $objectId = DB::table('object')->insertGetId([
            'class_name' => 'QubitRequestToPublish',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Create base record with nested set values
        DB::table($this->table)->insert([
            'id' => $objectId,
            'parent_id' => null,
            'rtp_type_id' => null,
            'lft' => 0,
            'rgt' => 1,
            'source_culture' => $this->culture
        ]);

        // Set default status (pending/in_review = 220)
        $statusId = $data['status_id'] ?? 220;

        // Create i18n record with all data fields
        DB::table($this->i18nTable)->insert([
            'id' => $objectId,
            'culture' => $this->culture,
            'object_id' => $data['object_id'] ?? null,
            'rtp_name' => $data['rtp_name'] ?? null,
            'rtp_surname' => $data['rtp_surname'] ?? null,
            'rtp_email' => $data['rtp_email'] ?? null,
            'rtp_phone' => $data['rtp_phone'] ?? null,
            'rtp_institution' => $data['rtp_institution'] ?? null,
            'rtp_planned_use' => $data['rtp_planned_use'] ?? null,
            'rtp_motivation' => $data['rtp_motivation'] ?? null,
            'rtp_need_image_by' => $data['rtp_need_image_by'] ?? null,
            'status_id' => $statusId,
            'created_at' => date('Y-m-d H:i:s'),
            'completed_at' => null,
            'unique_identifier' => null
        ]);

        // Create slug
        $slug = $this->generateSlug($objectId);
        DB::table('slug')->insert([
            'object_id' => $objectId,
            'slug' => $slug
        ]);

        return $objectId;
    }

    /**
     * Update request
     */
    public function update(int $id, array $data): bool
    {
        $updateData = [];
        
        // Map allowed fields
        $allowedFields = ['rtp_name', 'rtp_surname', 'rtp_email', 'rtp_phone', 
                         'rtp_institution', 'rtp_planned_use', 'rtp_motivation', 
                         'rtp_need_image_by', 'status_id', 'rtp_admin_notes'];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        // If status changed to approved/rejected, set completed_at
        if (isset($data['status_id']) && in_array($data['status_id'], [219, 221])) {
            $updateData['completed_at'] = date('Y-m-d H:i:s');
        }

        if (empty($updateData)) {
            return false;
        }

        return DB::table($this->i18nTable)
            ->where('id', $id)
            ->where('culture', $this->culture)
            ->update($updateData) > 0;
    }

    /**
     * Delete request
     */
    public function delete(int $id): bool
    {
        // Delete slug first
        DB::table('slug')->where('object_id', $id)->delete();
        
        // Delete i18n record
        DB::table($this->i18nTable)->where('id', $id)->delete();
        
        // Delete base record
        DB::table($this->table)->where('id', $id)->delete();
        
        // Delete object record
        DB::table('object')->where('id', $id)->delete();

        return true;
    }

    /**
     * Get status counts for tabs
     */
    public function getStatusCounts(): array
    {
        $counts = DB::table($this->i18nTable)
            ->where('culture', $this->culture)
            ->select('status_id', DB::raw('COUNT(*) as count'))
            ->groupBy('status_id')
            ->pluck('count', 'status_id')
            ->toArray();

        return [
            'all' => array_sum($counts),
            'pending' => $counts[220] ?? 0,   // IN_REVIEW_ID
            'approved' => $counts[219] ?? 0,   // APPROVED_ID
            'rejected' => $counts[221] ?? 0    // REJECTED_ID
        ];
    }

    /**
     * Get status ID from string
     */
    protected function getStatusId(string $status): int
    {
        return match (strtolower($status)) {
            'pending', 'in_review' => 220,
            'approved' => 219,
            'rejected' => 221,
            default => 220
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabel(int $statusId): string
    {
        return match ($statusId) {
            219 => 'Approved',
            220 => 'Pending',
            221 => 'Rejected',
            default => 'Unknown'
        };
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClass(int $statusId): string
    {
        return match ($statusId) {
            219 => 'bg-success',
            220 => 'bg-warning text-dark',
            221 => 'bg-danger',
            default => 'bg-secondary'
        };
    }

    /**
     * Generate unique slug
     */
    protected function generateSlug(int $id): string
    {
        $baseSlug = 'request-to-publish-' . $id;
        $slug = $baseSlug;
        $counter = 1;

        while (DB::table('slug')->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
