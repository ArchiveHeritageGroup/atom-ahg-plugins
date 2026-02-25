<?php

namespace AhgRegistry\Services;

use Illuminate\Database\Capsule\Manager as DB;

class SyncService
{
    protected string $culture;

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;

        $svcPath = \sfConfig::get('sf_plugins_dir') . '/ahgRegistryPlugin/lib/Services/';
        require_once $svcPath . 'InstanceService.php';
    }

    // =========================================================================
    // Registration
    // =========================================================================

    /**
     * Register a new institution + instance, generate sync token, log event.
     */
    public function register(array $payload): array
    {
        if (empty($payload['institution_name'])) {
            return ['success' => false, 'error' => 'Institution name is required'];
        }
        if (empty($payload['instance_url'])) {
            return ['success' => false, 'error' => 'Instance URL is required'];
        }

        $instanceService = new InstanceService($this->culture);

        // Find or create institution
        $institution = null;
        if (!empty($payload['institution_slug'])) {
            $institution = DB::table('registry_institution')
                ->where('slug', $payload['institution_slug'])
                ->first();
        }

        if (!$institution) {
            // Create new institution
            $instSlug = $this->makeSlug($payload['institution_name'], 'registry_institution');
            $instId = DB::table('registry_institution')->insertGetId([
                'name' => $payload['institution_name'],
                'slug' => $instSlug,
                'institution_type' => $payload['institution_type'] ?? 'archive',
                'country' => $payload['country'] ?? null,
                'city' => $payload['city'] ?? null,
                'website' => $payload['website'] ?? null,
                'email' => $payload['email'] ?? null,
                'description' => $payload['description'] ?? null,
                'uses_atom' => !empty($payload['uses_atom']) ? 1 : 0,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $instId = $institution->id;
        }

        // Generate sync token
        $syncToken = $instanceService->generateSyncToken();

        // Create instance
        $instanceId = DB::table('registry_instance')->insertGetId([
            'institution_id' => $instId,
            'name' => $payload['instance_name'] ?? $payload['institution_name'],
            'url' => $payload['instance_url'],
            'instance_type' => $payload['instance_type'] ?? 'production',
            'software' => $payload['software'] ?? 'heratio',
            'software_version' => $payload['software_version'] ?? null,
            'hosting' => $payload['hosting'] ?? null,
            'sync_token' => $syncToken,
            'sync_enabled' => 1,
            'status' => 'online',
            'last_heartbeat_at' => date('Y-m-d H:i:s'),
            'record_count' => $payload['record_count'] ?? null,
            'digital_object_count' => $payload['digital_object_count'] ?? null,
            'storage_gb' => $payload['storage_gb'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Log event
        $this->logEvent(
            $instanceId,
            'register',
            $payload,
            $payload['ip_address'] ?? null,
            'success'
        );

        return [
            'success' => true,
            'institution_id' => $instId,
            'instance_id' => $instanceId,
            'sync_token' => $syncToken,
        ];
    }

    // =========================================================================
    // Heartbeat
    // =========================================================================

    /**
     * Process a heartbeat from an instance.
     */
    public function heartbeat(string $token, array $payload): array
    {
        $instanceService = new InstanceService($this->culture);

        $instance = $instanceService->validateSyncToken($token);
        if (!$instance) {
            return ['success' => false, 'error' => 'Invalid or disabled sync token'];
        }

        // Update heartbeat data
        $result = $instanceService->updateFromHeartbeat($instance->id, $payload);
        if (!$result['success']) {
            $this->logEvent($instance->id, 'heartbeat', $payload, $payload['ip_address'] ?? null, 'error', $result['error']);

            return $result;
        }

        // Log event
        $this->logEvent($instance->id, 'heartbeat', $payload, $payload['ip_address'] ?? null, 'success');

        // Check if a newer version is available for the software
        $latestVersion = null;
        if (!empty($payload['software_version']) && !empty($instance->software)) {
            $sw = DB::table('registry_software')
                ->where('slug', $instance->software)
                ->where('is_active', 1)
                ->first();

            if ($sw && $sw->latest_version && version_compare($sw->latest_version, $payload['software_version'], '>')) {
                $latestVersion = $sw->latest_version;
            }
        }

        return [
            'success' => true,
            'instance_id' => $instance->id,
            'latest_version' => $latestVersion,
        ];
    }

    // =========================================================================
    // Update
    // =========================================================================

    /**
     * Update instance and institution metadata from a sync payload.
     */
    public function update(string $token, array $payload): array
    {
        $instanceService = new InstanceService($this->culture);

        $instance = $instanceService->validateSyncToken($token);
        if (!$instance) {
            return ['success' => false, 'error' => 'Invalid or disabled sync token'];
        }

        // Update instance fields
        $instanceData = [];
        foreach (['name', 'url', 'instance_type', 'software', 'software_version', 'hosting', 'status', 'is_public', 'description'] as $field) {
            if (isset($payload['instance'][$field])) {
                $instanceData[$field] = $payload['instance'][$field];
            }
        }
        foreach (['record_count', 'digital_object_count'] as $intField) {
            if (isset($payload['instance'][$intField])) {
                $instanceData[$intField] = (int) $payload['instance'][$intField];
            }
        }
        if (isset($payload['instance']['storage_gb'])) {
            $instanceData['storage_gb'] = (float) $payload['instance']['storage_gb'];
        }
        if (!empty($payload['instance']['sync_data'])) {
            $instanceData['sync_data'] = is_array($payload['instance']['sync_data'])
                ? json_encode($payload['instance']['sync_data'])
                : $payload['instance']['sync_data'];
        }

        if (!empty($instanceData)) {
            $instanceData['last_sync_at'] = date('Y-m-d H:i:s');
            $instanceService->update($instance->id, $instanceData);
        }

        // Update institution fields
        if (!empty($payload['institution'])) {
            $instData = [];
            foreach (['name', 'description', 'website', 'email', 'phone', 'street_address', 'city', 'province_state', 'postal_code', 'country', 'collection_summary', 'total_holdings', 'digitization_percentage'] as $field) {
                if (isset($payload['institution'][$field])) {
                    $instData[$field] = $payload['institution'][$field];
                }
            }
            foreach (['glam_sectors', 'collection_strengths', 'descriptive_standards'] as $jsonField) {
                if (isset($payload['institution'][$jsonField])) {
                    $instData[$jsonField] = is_array($payload['institution'][$jsonField])
                        ? json_encode($payload['institution'][$jsonField])
                        : $payload['institution'][$jsonField];
                }
            }

            if (!empty($instData)) {
                $instData['updated_at'] = date('Y-m-d H:i:s');
                DB::table('registry_institution')
                    ->where('id', $instance->institution_id)
                    ->update($instData);
            }
        }

        // Log event
        $this->logEvent($instance->id, 'update', $payload, $payload['ip_address'] ?? null, 'success');

        return ['success' => true, 'instance_id' => $instance->id];
    }

    // =========================================================================
    // Status & Directory
    // =========================================================================

    /**
     * Get current sync status for an instance.
     */
    public function getStatus(string $token): array
    {
        $instanceService = new InstanceService($this->culture);
        $instance = $instanceService->validateSyncToken($token);
        if (!$instance) {
            return ['success' => false, 'error' => 'Invalid or disabled sync token'];
        }

        $institution = DB::table('registry_institution')
            ->where('id', $instance->institution_id)
            ->first();

        // Recent sync logs
        $recentLogs = DB::table('registry_sync_log')
            ->where('instance_id', $instance->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->all();

        return [
            'success' => true,
            'instance' => $instance,
            'institution' => $institution,
            'recent_logs' => $recentLogs,
        ];
    }

    /**
     * Get all active instances with recent heartbeats.
     */
    public function getActiveInstances(): array
    {
        return DB::table('registry_instance as ri')
            ->leftJoin('registry_institution as inst', 'inst.id', '=', 'ri.institution_id')
            ->where('ri.sync_enabled', 1)
            ->where('ri.status', 'online')
            ->select(
                'ri.*',
                'inst.name as institution_name',
                'inst.slug as institution_slug',
                'inst.country as institution_country'
            )
            ->orderBy('ri.last_heartbeat_at', 'desc')
            ->get()
            ->all();
    }

    /**
     * Get public JSON directory of active institutions (for API).
     */
    public function getDirectory(): array
    {
        $institutions = DB::table('registry_institution as inst')
            ->where('inst.is_active', 1)
            ->select(
                'inst.id',
                'inst.name',
                'inst.slug',
                'inst.institution_type',
                'inst.city',
                'inst.country',
                'inst.latitude',
                'inst.longitude',
                'inst.website',
                'inst.uses_atom',
                'inst.is_verified',
                'inst.logo_path'
            )
            ->orderBy('inst.name', 'asc')
            ->get()
            ->all();

        // Attach public instances
        foreach ($institutions as &$inst) {
            $inst->instances = DB::table('registry_instance')
                ->where('institution_id', $inst->id)
                ->where('is_public', 1)
                ->whereIn('status', ['online', 'maintenance'])
                ->select('id', 'name', 'url', 'instance_type', 'software', 'software_version', 'status')
                ->get()
                ->all();
        }

        return $institutions;
    }

    // =========================================================================
    // Event Logging
    // =========================================================================

    /**
     * Log a sync event.
     */
    public function logEvent(int $instanceId, string $eventType, ?array $payload = null, ?string $ipAddress = null, string $status = 'success', ?string $errorMessage = null): void
    {
        DB::table('registry_sync_log')->insert([
            'instance_id' => $instanceId,
            'event_type' => $eventType,
            'payload' => $payload ? json_encode($payload) : null,
            'ip_address' => $ipAddress,
            'status' => $status,
            'error_message' => $errorMessage,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Generate a unique slug for a table.
     */
    private function makeSlug(string $name, string $table): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        $slug = preg_replace('/-+/', '-', $slug);

        $baseSlug = $slug;
        $counter = 1;
        while (DB::table($table)->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
