<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Migration Pathway Service
 *
 * Handles format migration pathway queries, recommendations,
 * and obsolescence analysis for digital preservation planning.
 */
class MigrationPathwayService
{
    /**
     * Get all migration pathways, optionally filtered.
     *
     * @param array $filters Optional filters: source_puid, target_puid, tool, recommended_only
     *
     * @return array
     */
    public function getPathways(array $filters = []): array
    {
        $query = DB::table('preservation_migration_pathway as mp')
            ->leftJoin('preservation_format as sf', 'mp.source_puid', '=', 'sf.puid')
            ->leftJoin('preservation_format as tf', 'mp.target_puid', '=', 'tf.puid')
            ->select([
                'mp.*',
                'sf.format_name as source_format_name',
                'sf.mime_type as source_mime_type',
                'sf.extension as source_extension',
                'tf.format_name as target_format_name',
                'tf.mime_type as target_mime_type',
                'tf.extension as target_extension',
                'tf.is_preservation_format as target_is_preservation',
            ]);

        if (!empty($filters['source_puid'])) {
            $query->where('mp.source_puid', $filters['source_puid']);
        }

        if (!empty($filters['target_puid'])) {
            $query->where('mp.target_puid', $filters['target_puid']);
        }

        if (!empty($filters['tool'])) {
            $query->where('mp.migration_tool', $filters['tool']);
        }

        if (!empty($filters['recommended_only'])) {
            $query->where('mp.is_recommended', 1);
        }

        if (!empty($filters['automated_only'])) {
            $query->where('mp.is_automated', 1);
        }

        $query->orderBy('mp.priority', 'asc')
            ->orderBy('mp.is_recommended', 'desc')
            ->orderBy('mp.fidelity_score', 'desc');

        return $query->get()->toArray();
    }

    /**
     * Get a specific pathway by ID.
     *
     * @param int $pathwayId
     *
     * @return object|null
     */
    public function getPathway(int $pathwayId): ?object
    {
        return DB::table('preservation_migration_pathway as mp')
            ->leftJoin('preservation_format as sf', 'mp.source_puid', '=', 'sf.puid')
            ->leftJoin('preservation_format as tf', 'mp.target_puid', '=', 'tf.puid')
            ->where('mp.id', $pathwayId)
            ->select([
                'mp.*',
                'sf.format_name as source_format_name',
                'sf.mime_type as source_mime_type',
                'tf.format_name as target_format_name',
                'tf.mime_type as target_mime_type',
            ])
            ->first();
    }

    /**
     * Get recommended pathway for a source format.
     *
     * @param string $sourcePuid PRONOM identifier
     *
     * @return object|null
     */
    public function getRecommendedPathway(string $sourcePuid): ?object
    {
        return DB::table('preservation_migration_pathway as mp')
            ->leftJoin('preservation_format as tf', 'mp.target_puid', '=', 'tf.puid')
            ->where('mp.source_puid', $sourcePuid)
            ->where('mp.is_recommended', 1)
            ->select([
                'mp.*',
                'tf.format_name as target_format_name',
                'tf.mime_type as target_mime_type',
                'tf.is_preservation_format',
            ])
            ->orderBy('mp.priority', 'asc')
            ->first();
    }

    /**
     * Get all pathways available for a source format.
     *
     * @param string $sourcePuid PRONOM identifier
     *
     * @return array
     */
    public function getPathwaysForFormat(string $sourcePuid): array
    {
        return $this->getPathways(['source_puid' => $sourcePuid]);
    }

    /**
     * Create a new migration pathway.
     *
     * @param array $data Pathway data
     *
     * @return int New pathway ID
     */
    public function createPathway(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        return DB::table('preservation_migration_pathway')->insertGetId([
            'source_puid' => $data['source_puid'],
            'target_puid' => $data['target_puid'],
            'migration_tool' => $data['migration_tool'],
            'migration_command' => $data['migration_command'] ?? null,
            'quality_impact' => $data['quality_impact'] ?? 'minimal',
            'fidelity_score' => $data['fidelity_score'] ?? null,
            'is_recommended' => $data['is_recommended'] ?? 0,
            'is_automated' => $data['is_automated'] ?? 1,
            'priority' => $data['priority'] ?? 100,
            'notes' => $data['notes'] ?? null,
            'created_at' => $now,
        ]);
    }

    /**
     * Update a migration pathway.
     *
     * @param int   $pathwayId
     * @param array $data
     *
     * @return bool
     */
    public function updatePathway(int $pathwayId, array $data): bool
    {
        $updateData = array_filter([
            'source_puid' => $data['source_puid'] ?? null,
            'target_puid' => $data['target_puid'] ?? null,
            'migration_tool' => $data['migration_tool'] ?? null,
            'migration_command' => $data['migration_command'] ?? null,
            'quality_impact' => $data['quality_impact'] ?? null,
            'fidelity_score' => $data['fidelity_score'] ?? null,
            'is_recommended' => isset($data['is_recommended']) ? (int) $data['is_recommended'] : null,
            'is_automated' => isset($data['is_automated']) ? (int) $data['is_automated'] : null,
            'priority' => $data['priority'] ?? null,
            'notes' => $data['notes'] ?? null,
        ], fn ($v) => $v !== null);

        if (empty($updateData)) {
            return false;
        }

        return DB::table('preservation_migration_pathway')
            ->where('id', $pathwayId)
            ->update($updateData) > 0;
    }

    /**
     * Delete a migration pathway.
     *
     * @param int $pathwayId
     *
     * @return bool
     */
    public function deletePathway(int $pathwayId): bool
    {
        return DB::table('preservation_migration_pathway')
            ->where('id', $pathwayId)
            ->delete() > 0;
    }

    // =========================================
    // OBSOLESCENCE TRACKING
    // =========================================

    /**
     * Get format obsolescence report.
     *
     * @param array $filters Optional filters: risk_level, urgency
     *
     * @return array
     */
    public function getObsolescenceReport(array $filters = []): array
    {
        $query = DB::table('preservation_format_obsolescence as fo')
            ->join('preservation_format as pf', 'fo.format_id', '=', 'pf.id')
            ->leftJoin('preservation_migration_pathway as mp', 'fo.recommended_pathway_id', '=', 'mp.id')
            ->select([
                'fo.*',
                'pf.format_name',
                'pf.mime_type',
                'pf.extension',
                'pf.risk_level as format_risk_level',
                'mp.target_puid as recommended_target_puid',
                'mp.migration_tool as recommended_tool',
            ]);

        if (!empty($filters['risk_level'])) {
            $query->where('fo.current_risk_level', $filters['risk_level']);
        }

        if (!empty($filters['urgency'])) {
            $query->where('fo.migration_urgency', $filters['urgency']);
        }

        if (!empty($filters['min_objects'])) {
            $query->where('fo.affected_object_count', '>=', (int) $filters['min_objects']);
        }

        $query->orderByRaw("FIELD(fo.current_risk_level, 'critical', 'high', 'medium', 'low')")
            ->orderByRaw("FIELD(fo.migration_urgency, 'critical', 'high', 'medium', 'low', 'none')")
            ->orderBy('fo.affected_object_count', 'desc');

        return $query->get()->toArray();
    }

    /**
     * Get obsolescence alerts (critical and high urgency items).
     *
     * @return array
     */
    public function getObsolescenceAlerts(): array
    {
        return DB::table('preservation_format_obsolescence as fo')
            ->join('preservation_format as pf', 'fo.format_id', '=', 'pf.id')
            ->whereIn('fo.migration_urgency', ['critical', 'high'])
            ->where('fo.affected_object_count', '>', 0)
            ->select([
                'fo.*',
                'pf.format_name',
                'pf.mime_type',
            ])
            ->orderByRaw("FIELD(fo.migration_urgency, 'critical', 'high')")
            ->orderBy('fo.affected_object_count', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Update obsolescence counts for all tracked formats.
     * This should be run periodically to keep counts current.
     *
     * @return array Summary of updates
     */
    public function refreshObsolescenceCounts(): array
    {
        $updated = 0;
        $added = 0;
        $now = date('Y-m-d H:i:s');

        // Get all formats with digital objects
        $formatCounts = DB::table('preservation_object_format as pof')
            ->join('digital_object as do', 'pof.digital_object_id', '=', 'do.id')
            ->whereNotNull('pof.puid')
            ->select([
                'pof.puid',
                DB::raw('COUNT(DISTINCT pof.digital_object_id) as object_count'),
                DB::raw('COALESCE(SUM(do.byte_size), 0) as total_size'),
            ])
            ->groupBy('pof.puid')
            ->get();

        foreach ($formatCounts as $fc) {
            // Check if format exists in registry
            $format = DB::table('preservation_format')
                ->where('puid', $fc->puid)
                ->first();

            if (!$format) {
                continue;
            }

            // Check if obsolescence record exists
            $existing = DB::table('preservation_format_obsolescence')
                ->where('puid', $fc->puid)
                ->first();

            if ($existing) {
                // Update counts
                DB::table('preservation_format_obsolescence')
                    ->where('id', $existing->id)
                    ->update([
                        'affected_object_count' => $fc->object_count,
                        'storage_size_bytes' => $fc->total_size,
                        'last_assessed_at' => $now,
                    ]);
                ++$updated;
            } else {
                // Create new obsolescence record for at-risk formats
                if (in_array($format->risk_level, ['high', 'critical'])) {
                    DB::table('preservation_format_obsolescence')->insert([
                        'format_id' => $format->id,
                        'puid' => $fc->puid,
                        'current_risk_level' => $format->risk_level,
                        'migration_urgency' => $format->risk_level === 'critical' ? 'high' : 'medium',
                        'affected_object_count' => $fc->object_count,
                        'storage_size_bytes' => $fc->total_size,
                        'recommended_action' => 'Review and plan migration to preservation format',
                        'last_assessed_at' => $now,
                        'created_at' => $now,
                    ]);
                    ++$added;
                }
            }
        }

        return [
            'updated' => $updated,
            'added' => $added,
            'assessed_at' => $now,
        ];
    }

    /**
     * Assess a specific format for obsolescence risk.
     *
     * @param string $puid PRONOM identifier
     *
     * @return array Assessment results
     */
    public function assessFormat(string $puid): array
    {
        $format = DB::table('preservation_format')
            ->where('puid', $puid)
            ->first();

        if (!$format) {
            return ['error' => 'Format not found in registry'];
        }

        // Count affected objects
        $objectCount = DB::table('preservation_object_format')
            ->where('puid', $puid)
            ->count();

        // Get total storage
        $storageSize = DB::table('preservation_object_format as pof')
            ->join('digital_object as do', 'pof.digital_object_id', '=', 'do.id')
            ->where('pof.puid', $puid)
            ->sum('do.byte_size');

        // Get available pathways
        $pathways = $this->getPathwaysForFormat($puid);

        // Get recommended pathway
        $recommendedPathway = $this->getRecommendedPathway($puid);

        // Determine urgency based on risk and object count
        $urgency = 'none';
        if ($format->risk_level === 'critical') {
            $urgency = $objectCount > 0 ? 'critical' : 'high';
        } elseif ($format->risk_level === 'high') {
            $urgency = $objectCount > 100 ? 'high' : ($objectCount > 0 ? 'medium' : 'low');
        } elseif ($format->risk_level === 'medium' && $objectCount > 500) {
            $urgency = 'low';
        }

        return [
            'puid' => $puid,
            'format_name' => $format->format_name,
            'mime_type' => $format->mime_type,
            'risk_level' => $format->risk_level,
            'affected_objects' => $objectCount,
            'storage_bytes' => $storageSize,
            'migration_urgency' => $urgency,
            'available_pathways' => count($pathways),
            'recommended_pathway' => $recommendedPathway,
            'is_preservation_format' => (bool) $format->is_preservation_format,
        ];
    }

    /**
     * Get migration pathway statistics.
     *
     * @return array
     */
    public function getPathwayStats(): array
    {
        $totalPathways = DB::table('preservation_migration_pathway')->count();

        $byTool = DB::table('preservation_migration_pathway')
            ->select('migration_tool', DB::raw('COUNT(*) as count'))
            ->groupBy('migration_tool')
            ->pluck('count', 'migration_tool')
            ->toArray();

        $byQuality = DB::table('preservation_migration_pathway')
            ->select('quality_impact', DB::raw('COUNT(*) as count'))
            ->groupBy('quality_impact')
            ->pluck('count', 'quality_impact')
            ->toArray();

        $recommended = DB::table('preservation_migration_pathway')
            ->where('is_recommended', 1)
            ->count();

        $automated = DB::table('preservation_migration_pathway')
            ->where('is_automated', 1)
            ->count();

        // Formats with pathways
        $formatsWithPathways = DB::table('preservation_migration_pathway')
            ->distinct()
            ->count('source_puid');

        // At-risk formats without pathways
        $atRiskWithoutPathways = DB::table('preservation_format as pf')
            ->leftJoin('preservation_migration_pathway as mp', 'pf.puid', '=', 'mp.source_puid')
            ->whereIn('pf.risk_level', ['high', 'critical'])
            ->whereNull('mp.id')
            ->count();

        return [
            'total_pathways' => $totalPathways,
            'by_tool' => $byTool,
            'by_quality_impact' => $byQuality,
            'recommended_count' => $recommended,
            'automated_count' => $automated,
            'formats_with_pathways' => $formatsWithPathways,
            'at_risk_without_pathways' => $atRiskWithoutPathways,
        ];
    }

    /**
     * Get list of available migration tools.
     *
     * @return array
     */
    public function getAvailableTools(): array
    {
        return DB::table('preservation_migration_pathway')
            ->distinct()
            ->pluck('migration_tool')
            ->toArray();
    }

    /**
     * Validate that a migration tool is installed and available.
     *
     * @param string $tool Tool name
     *
     * @return array
     */
    public function validateTool(string $tool): array
    {
        $toolPaths = [
            'imagemagick' => ['convert', 'magick'],
            'ffmpeg' => ['ffmpeg'],
            'ghostscript' => ['gs', 'ghostscript'],
            'libreoffice' => ['libreoffice', 'soffice'],
        ];

        $result = [
            'tool' => $tool,
            'available' => false,
            'path' => null,
            'version' => null,
        ];

        $commands = $toolPaths[$tool] ?? [$tool];

        foreach ($commands as $cmd) {
            $path = trim(shell_exec("which $cmd 2>/dev/null") ?? '');
            if ($path) {
                $result['available'] = true;
                $result['path'] = $path;

                // Try to get version
                $versionCmd = match ($tool) {
                    'imagemagick' => "$cmd -version 2>&1 | head -1",
                    'ffmpeg' => "$cmd -version 2>&1 | head -1",
                    'ghostscript' => "$cmd --version 2>&1",
                    'libreoffice' => "$cmd --version 2>&1",
                    default => null,
                };

                if ($versionCmd) {
                    $result['version'] = trim(shell_exec($versionCmd) ?? '');
                }
                break;
            }
        }

        return $result;
    }
}
