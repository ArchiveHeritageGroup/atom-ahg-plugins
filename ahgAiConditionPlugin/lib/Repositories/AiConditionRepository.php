<?php

namespace ahgAiConditionPlugin\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Repository for AI Condition assessment database operations.
 */
class AiConditionRepository
{
    /**
     * Save an assessment result.
     */
    public function saveAssessment(array $data): int
    {
        return DB::table('ahg_ai_condition_assessment')->insertGetId([
            'information_object_id' => $data['information_object_id'] ?? null,
            'condition_report_id'   => $data['condition_report_id'] ?? null,
            'digital_object_id'     => $data['digital_object_id'] ?? null,
            'image_path'            => $data['image_path'] ?? null,
            'overlay_path'          => $data['overlay_path'] ?? null,
            'overall_score'         => $data['overall_score'] ?? null,
            'condition_grade'       => $data['condition_grade'] ?? null,
            'damage_count'          => $data['damage_count'] ?? 0,
            'recommendations'       => $data['recommendations'] ?? null,
            'model_version'         => $data['model_version'] ?? null,
            'processing_time_ms'    => $data['processing_time_ms'] ?? null,
            'confidence_threshold'  => $data['confidence_threshold'] ?? 0.25,
            'source'                => $data['source'] ?? 'manual',
            'api_client_id'         => $data['api_client_id'] ?? null,
            'created_by'            => $data['created_by'] ?? null,
            'created_at'            => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Save individual damage detections.
     */
    public function saveDamages(int $assessmentId, array $damages): void
    {
        foreach ($damages as $damage) {
            DB::table('ahg_ai_condition_damage')->insert([
                'assessment_id'  => $assessmentId,
                'damage_type'    => $damage['damage_type'] ?? 'unknown',
                'severity'       => $damage['severity'] ?? null,
                'confidence'     => $damage['confidence'] ?? 0,
                'bbox_x'         => $damage['bbox_x'] ?? null,
                'bbox_y'         => $damage['bbox_y'] ?? null,
                'bbox_w'         => $damage['bbox_w'] ?? null,
                'bbox_h'         => $damage['bbox_h'] ?? null,
                'area_percent'   => $damage['area_percent'] ?? null,
                'location_zone'  => $damage['location_zone'] ?? null,
                'description'    => $damage['description'] ?? null,
                'score_deduction' => $damage['score_deduction'] ?? null,
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Save history entry for trend tracking.
     */
    public function saveHistory(int $objectId, int $assessmentId, float $score, string $grade, int $damageCount): void
    {
        DB::table('ahg_ai_condition_history')->insert([
            'information_object_id' => $objectId,
            'assessment_id'         => $assessmentId,
            'score'                 => $score,
            'condition_grade'       => $grade,
            'damage_count'          => $damageCount,
            'assessed_at'           => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get assessment by ID with damages.
     */
    public function getAssessment(int $id): ?object
    {
        $assessment = DB::table('ahg_ai_condition_assessment as a')
            ->leftJoin('information_object_i18n as io', function ($join) {
                $join->on('a.information_object_id', '=', 'io.id')
                     ->where('io.culture', '=', 'en');
            })
            ->leftJoin('user as u', 'a.created_by', '=', 'u.id')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('u.id', '=', 'ai.id')
                     ->where('ai.culture', '=', 'en');
            })
            ->select(
                'a.*',
                'io.title as object_title',
                'ai.authorized_form_of_name as assessor_name'
            )
            ->where('a.id', $id)
            ->first();

        if ($assessment) {
            $assessment->damages = DB::table('ahg_ai_condition_damage')
                ->where('assessment_id', $id)
                ->orderBy('score_deduction', 'desc')
                ->get()
                ->all();
        }

        return $assessment;
    }

    /**
     * List assessments with pagination and filters.
     */
    public function listAssessments(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $query = DB::table('ahg_ai_condition_assessment as a')
            ->leftJoin('information_object_i18n as io', function ($join) {
                $join->on('a.information_object_id', '=', 'io.id')
                     ->where('io.culture', '=', 'en');
            })
            ->select(
                'a.id', 'a.information_object_id', 'a.overall_score',
                'a.condition_grade', 'a.damage_count', 'a.source',
                'a.is_confirmed', 'a.created_at', 'a.created_by',
                'io.title as object_title'
            );

        if (!empty($filters['condition_grade'])) {
            $query->where('a.condition_grade', $filters['condition_grade']);
        }
        if (!empty($filters['source'])) {
            $query->where('a.source', $filters['source']);
        }
        if (isset($filters['is_confirmed'])) {
            $query->where('a.is_confirmed', (int) $filters['is_confirmed']);
        }
        if (!empty($filters['search'])) {
            $query->where('io.title', 'like', '%' . $filters['search'] . '%');
        }

        $total = $query->count();
        $items = $query->orderBy('a.created_at', 'desc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->all();

        return [
            'items' => $items,
            'total' => $total,
            'page'  => $page,
            'pages' => ceil($total / $perPage),
        ];
    }

    /**
     * Get condition score history for an information object.
     */
    public function getHistory(int $objectId): array
    {
        return DB::table('ahg_ai_condition_history')
            ->where('information_object_id', $objectId)
            ->orderBy('assessed_at', 'asc')
            ->get()
            ->all();
    }

    /**
     * Confirm an assessment (human review).
     */
    public function confirmAssessment(int $id, int $userId): bool
    {
        return DB::table('ahg_ai_condition_assessment')
            ->where('id', $id)
            ->update([
                'is_confirmed' => 1,
                'confirmed_by' => $userId,
                'confirmed_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Get SaaS API clients.
     */
    public function getClients(): array
    {
        return DB::table('ahg_ai_service_client')
            ->orderBy('name')
            ->get()
            ->all();
    }

    /**
     * Save or update a SaaS client.
     */
    public function saveClient(array $data): int
    {
        if (!empty($data['id'])) {
            DB::table('ahg_ai_service_client')
                ->where('id', $data['id'])
                ->update([
                    'name'          => $data['name'],
                    'organization'  => $data['organization'] ?? null,
                    'email'         => $data['email'],
                    'tier'          => $data['tier'] ?? 'free',
                    'monthly_limit' => $data['monthly_limit'] ?? 50,
                    'is_active'     => $data['is_active'] ?? 1,
                ]);
            return (int) $data['id'];
        }

        return DB::table('ahg_ai_service_client')->insertGetId([
            'name'          => $data['name'],
            'organization'  => $data['organization'] ?? null,
            'email'         => $data['email'],
            'api_key'       => 'ahg_' . bin2hex(random_bytes(24)),
            'tier'          => $data['tier'] ?? 'free',
            'monthly_limit' => $data['monthly_limit'] ?? 50,
            'is_active'     => 1,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Revoke (deactivate) an API client.
     */
    public function revokeClient(int $id): bool
    {
        return DB::table('ahg_ai_service_client')
            ->where('id', $id)
            ->update(['is_active' => 0]) > 0;
    }

    /**
     * Get usage stats for a client.
     */
    public function getClientUsage(int $clientId): ?object
    {
        $yearMonth = date('Y-m');
        return DB::table('ahg_ai_service_usage')
            ->where('client_id', $clientId)
            ->where('year_month', $yearMonth)
            ->first();
    }

    /**
     * Get digital object images for an information object.
     */
    public function getDigitalObjects(int $objectId): array
    {
        return DB::table('digital_object')
            ->where('object_id', $objectId)
            ->whereNotNull('path')
            ->select('id', 'path', 'name', 'mime_type', 'byte_size')
            ->get()
            ->all();
    }

    /**
     * Get assessment stats for dashboard.
     */
    public function getStats(): array
    {
        $total = DB::table('ahg_ai_condition_assessment')->count();
        $confirmed = DB::table('ahg_ai_condition_assessment')->where('is_confirmed', 1)->count();
        $avgScore = DB::table('ahg_ai_condition_assessment')->avg('overall_score');

        $byGrade = DB::table('ahg_ai_condition_assessment')
            ->select('condition_grade', DB::raw('COUNT(*) as count'))
            ->groupBy('condition_grade')
            ->pluck('count', 'condition_grade')
            ->all();

        return [
            'total'     => $total,
            'confirmed' => $confirmed,
            'pending'   => $total - $confirmed,
            'avg_score' => round($avgScore ?? 0, 1),
            'by_grade'  => $byGrade,
        ];
    }
}
