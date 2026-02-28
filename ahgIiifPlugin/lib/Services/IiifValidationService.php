<?php
declare(strict_types=1);

namespace AhgIiif\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * IIIF Validation Service
 *
 * Validates IIIF manifests for structural correctness, compliance with
 * IIIF Presentation API 3.0, and checks derivative availability.
 * Results feed into the publish gate engine.
 *
 * @version 1.0.0
 * @see https://iiif.io/api/presentation/3.0/
 */
class IiifValidationService
{
    /**
     * Validate manifest structure and content for an object.
     *
     * @param int    $objectId Object to validate
     * @param string $culture  Culture code
     * @return array [{check, status, message}]
     */
    public function validateManifest(int $objectId, string $culture = 'en'): array
    {
        $results = [];

        // Get object info
        $object = DB::table('information_object as io')
            ->join('information_object_i18n as ioi', function ($join) use ($culture) {
                $join->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'io.id')
            ->where('io.id', $objectId)
            ->select('io.id', 'io.identifier', 'io.repository_id', 'ioi.title', 's.slug')
            ->first();

        if (!$object) {
            return [['check' => 'object_exists', 'status' => 'failed', 'message' => 'Object not found']];
        }

        // Check 1: Label (title) is non-empty
        $results[] = [
            'check' => 'label_present',
            'status' => !empty($object->title) ? 'passed' : 'failed',
            'message' => !empty($object->title) ? 'Label (title) present' : 'Manifest requires a non-empty label',
        ];

        // Check 2: Digital objects exist
        $digitalObjects = DB::table('digital_object')
            ->where('object_id', $objectId)
            ->select('id', 'name', 'mime_type', 'path', 'byte_size')
            ->get()
            ->toArray();

        $doCount = count($digitalObjects);
        $results[] = [
            'check' => 'has_canvases',
            'status' => $doCount > 0 ? 'passed' : 'failed',
            'message' => $doCount > 0
                ? "{$doCount} digital object(s) = {$doCount} potential canvas(es)"
                : 'At least one canvas with a painting annotation is required',
        ];

        if ($doCount === 0) {
            // No point checking further without digital objects
            $this->storeResults($objectId, $results);
            return $results;
        }

        // Check 3: Thumbnail availability
        $hasThumb = false;
        foreach ($digitalObjects as $do) {
            if (!empty($do->name)) {
                $hasThumb = true;
                break;
            }
        }
        $results[] = [
            'check' => 'thumbnail_present',
            'status' => $hasThumb ? 'passed' : 'warning',
            'message' => $hasThumb ? 'Thumbnail derivable from first canvas' : 'No named digital objects for thumbnail generation',
        ];

        // Check 4: Rights field
        $hasRights = DB::table('rights')
            ->where('object_id', $objectId)
            ->exists();

        $results[] = [
            'check' => 'rights_present',
            'status' => $hasRights ? 'passed' : 'warning',
            'message' => $hasRights ? 'Rights statement assigned (will appear in manifest)' : 'No rights statement — manifest will lack rights field',
        ];

        // Check 5: Required statement (institution attribution)
        $hasAttribution = false;
        if ($object->repository_id) {
            $repoName = DB::table('actor_i18n')
                ->where('id', $object->repository_id)
                ->where('culture', $culture)
                ->value('authorized_form_of_name');
            $hasAttribution = !empty($repoName);
        }
        $results[] = [
            'check' => 'required_statement',
            'status' => $hasAttribution ? 'passed' : 'warning',
            'message' => $hasAttribution ? 'Institution name available for requiredStatement' : 'No repository linked — requiredStatement will be missing',
        ];

        // Check 6: Image service health (probe Cantaloupe)
        $cantaloupeUrl = \sfConfig::get('app_iiif_cantaloupe_internal_url', 'http://127.0.0.1:8182');
        $serviceHealthy = $this->probeImageService($cantaloupeUrl);
        $results[] = [
            'check' => 'image_service',
            'status' => $serviceHealthy ? 'passed' : 'warning',
            'message' => $serviceHealthy ? 'Cantaloupe image service responding' : 'Cantaloupe not responding — image tiles may not load',
        ];

        // Check 7: Derivative files exist on disk
        $derivativeCheck = $this->checkDerivatives($objectId);
        $missingFiles = array_filter($derivativeCheck, fn($d) => !$d['exists']);
        $results[] = [
            'check' => 'derivatives_exist',
            'status' => empty($missingFiles) ? 'passed' : 'warning',
            'message' => empty($missingFiles)
                ? 'All derivative files exist on disk'
                : count($missingFiles) . ' derivative file(s) missing',
        ];

        // Store results
        $this->storeResults($objectId, $results);

        return $results;
    }

    /**
     * Check that digital object files exist on disk.
     *
     * @return array [{file, exists, path, issue}]
     */
    public function checkDerivatives(int $objectId): array
    {
        $rootDir = \sfConfig::get('sf_root_dir');

        $digitalObjects = DB::table('digital_object')
            ->where('object_id', $objectId)
            ->select('id', 'name', 'path', 'mime_type')
            ->get()
            ->toArray();

        $results = [];

        foreach ($digitalObjects as $do) {
            $filePath = $rootDir . '/' . ltrim($do->path ?? '', '/') . ($do->name ?? '');
            $exists = file_exists($filePath);

            $result = [
                'file' => $do->name ?? 'unknown',
                'exists' => $exists,
                'path' => $do->path . $do->name,
                'issue' => null,
            ];

            if (!$exists) {
                $result['issue'] = 'File not found on disk';
            }

            $results[] = $result;
        }

        return $results;
    }

    /**
     * Get past validation results for an object.
     */
    public function getValidationHistory(int $objectId, int $limit = 50): array
    {
        return DB::table('iiif_validation_result')
            ->where('object_id', $objectId)
            ->orderByDesc('validated_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Validate multiple objects, return summary counts.
     */
    public function batchValidate(array $objectIds): array
    {
        $summary = ['total' => count($objectIds), 'passed' => 0, 'failed' => 0, 'warnings' => 0];

        foreach ($objectIds as $id) {
            $results = $this->validateManifest((int) $id);
            $hasFailure = false;
            $hasWarning = false;

            foreach ($results as $r) {
                if ($r['status'] === 'failed') $hasFailure = true;
                if ($r['status'] === 'warning') $hasWarning = true;
            }

            if ($hasFailure) {
                $summary['failed']++;
            } elseif ($hasWarning) {
                $summary['warnings']++;
            } else {
                $summary['passed']++;
            }
        }

        return $summary;
    }

    /**
     * Get dashboard statistics for the validation overview.
     */
    public function getDashboardStats(): array
    {
        try {
            $exists = DB::select("SHOW TABLES LIKE 'iiif_validation_result'");
            if (empty($exists)) {
                return ['total' => 0, 'passed' => 0, 'failed' => 0, 'warning' => 0, 'recent_failures' => []];
            }

            $total = DB::table('iiif_validation_result')
                ->select(DB::raw('COUNT(DISTINCT object_id) as cnt'))
                ->value('cnt');

            $byStatus = DB::table('iiif_validation_result')
                ->select('status', DB::raw('COUNT(DISTINCT object_id) as cnt'))
                ->groupBy('status')
                ->pluck('cnt', 'status')
                ->toArray();

            $recentFailures = DB::table('iiif_validation_result as v')
                ->leftJoin('information_object_i18n as ioi', function ($join) {
                    $join->on('v.object_id', '=', 'ioi.id')
                         ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                })
                ->where('v.status', 'failed')
                ->select('v.object_id', 'v.validation_type', 'v.details', 'v.validated_at', 'ioi.title')
                ->orderByDesc('v.validated_at')
                ->limit(20)
                ->get()
                ->toArray();

            return [
                'total' => (int) $total,
                'passed' => (int) ($byStatus['passed'] ?? 0),
                'failed' => (int) ($byStatus['failed'] ?? 0),
                'warning' => (int) ($byStatus['warning'] ?? 0),
                'recent_failures' => $recentFailures,
            ];
        } catch (\Exception $e) {
            return ['total' => 0, 'passed' => 0, 'failed' => 0, 'warning' => 0, 'recent_failures' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Store validation results in the database.
     */
    private function storeResults(int $objectId, array $results, ?int $userId = null): void
    {
        try {
            $exists = DB::select("SHOW TABLES LIKE 'iiif_validation_result'");
            if (empty($exists)) {
                return;
            }

            $now = date('Y-m-d H:i:s');

            foreach ($results as $r) {
                DB::table('iiif_validation_result')->insert([
                    'object_id' => $objectId,
                    'validation_type' => $r['check'],
                    'status' => $r['status'],
                    'details' => $r['message'],
                    'validated_at' => $now,
                    'validated_by' => $userId,
                ]);
            }
        } catch (\Exception $e) {
            // Silently skip if table doesn't exist yet
        }
    }

    /**
     * Probe Cantaloupe image service health.
     */
    private function probeImageService(string $cantaloupeUrl): bool
    {
        try {
            $ctx = stream_context_create(['http' => ['timeout' => 2]]);
            $response = @file_get_contents("{$cantaloupeUrl}/iiif/2", false, $ctx);
            return $response !== false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
