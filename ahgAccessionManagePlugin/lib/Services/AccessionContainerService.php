<?php

namespace AhgAccessionManage\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Accession Container Service
 *
 * Container tracking and rights management for accessions.
 */
class AccessionContainerService
{
    /** Container types */
    const CONTAINER_TYPES = [
        'box', 'folder', 'envelope', 'crate', 'tube',
        'flat_file', 'digital_media', 'other',
    ];

    /** Condition statuses */
    const CONDITIONS = ['excellent', 'good', 'fair', 'poor', 'critical'];

    /** Rights basis options */
    const RIGHTS_BASIS = ['copyright', 'license', 'statute', 'policy', 'donor', 'other'];

    /** Restriction types */
    const RESTRICTION_TYPES = ['none', 'restricted', 'conditional', 'closed', 'partial'];

    /** Grant acts */
    const GRANT_ACTS = ['publish', 'disseminate', 'modify', 'migrate', 'replicate', 'delete', 'discover'];

    /** Grant restrictions */
    const GRANT_RESTRICTIONS = ['allow', 'disallow', 'conditional'];

    protected ?int $tenantId;
    protected ?AccessionIntakeService $intakeService;

    public function __construct(?int $tenantId = null, ?AccessionIntakeService $intakeService = null)
    {
        $this->tenantId = $tenantId;
        $this->intakeService = $intakeService;
    }

    /**
     * Apply tenant_id filter to a query builder instance.
     */
    public function scopeQuery($query)
    {
        if ($this->tenantId !== null) {
            $query->where('tenant_id', $this->tenantId);
        }

        return $query;
    }

    // =========================================================================
    // CONTAINERS
    // =========================================================================

    /**
     * Create a container for an accession.
     */
    public function createContainer(int $accessionId, array $data): int
    {
        $now = date('Y-m-d H:i:s');

        $id = DB::table('accession_container')->insertGetId([
            'accession_id' => $accessionId,
            'container_type' => $data['container_type'] ?? 'box',
            'label' => $data['label'],
            'barcode' => $data['barcode'] ?? null,
            'location_id' => $data['location_id'] ?? null,
            'location_detail' => $data['location_detail'] ?? null,
            'dimensions' => $data['dimensions'] ?? null,
            'item_count' => $data['item_count'] ?? null,
            'weight_kg' => $data['weight_kg'] ?? null,
            'condition_status' => $data['condition_status'] ?? null,
            'notes' => $data['notes'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'tenant_id' => $this->tenantId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($this->intakeService) {
            $this->intakeService->addTimelineEvent(
                $accessionId,
                AccessionIntakeService::EVENT_CONTAINERIZED,
                null,
                'Container added: ' . $data['label'],
                ['container_id' => $id]
            );
        }

        return $id;
    }

    /**
     * Update a container.
     */
    public function updateContainer(int $containerId, array $data): bool
    {
        $container = DB::table('accession_container')->where('id', $containerId)->first();
        if (!$container) {
            return false;
        }

        $update = [];
        $fields = [
            'container_type', 'label', 'barcode', 'location_id', 'location_detail',
            'dimensions', 'item_count', 'weight_kg', 'condition_status', 'notes', 'sort_order',
        ];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $update[$f] = $data[$f];
            }
        }

        if (!empty($update)) {
            $update['updated_at'] = date('Y-m-d H:i:s');
            DB::table('accession_container')->where('id', $containerId)->update($update);
        }

        return true;
    }

    /**
     * Delete a container and its items.
     */
    public function deleteContainer(int $containerId): bool
    {
        $container = DB::table('accession_container')->where('id', $containerId)->first();
        if (!$container) {
            return false;
        }

        DB::table('accession_container_item')->where('container_id', $containerId)->delete();
        DB::table('accession_container')->where('id', $containerId)->delete();

        return true;
    }

    /**
     * Get containers for an accession with item counts.
     */
    public function getContainers(int $accessionId): array
    {
        $query = DB::table('accession_container as c')
            ->leftJoin('physical_object as po', 'c.location_id', '=', 'po.id')
            ->leftJoin('physical_object_i18n as poi', function ($j) {
                $j->on('po.id', '=', 'poi.id')
                    ->where('poi.culture', '=', 'en');
            })
            ->where('c.accession_id', $accessionId)
            ->select(
                'c.*',
                'poi.name as location_name',
                DB::raw('(SELECT COUNT(*) FROM accession_container_item WHERE container_id = c.id) as actual_item_count')
            )
            ->orderBy('c.sort_order')
            ->orderBy('c.label');

        $this->scopeQuery($query);

        return $query->get()->all();
    }

    // =========================================================================
    // CONTAINER ITEMS
    // =========================================================================

    /**
     * Add an item to a container.
     */
    public function addContainerItem(int $containerId, array $data): int
    {
        return DB::table('accession_container_item')->insertGetId([
            'container_id' => $containerId,
            'information_object_id' => $data['information_object_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'quantity' => $data['quantity'] ?? 1,
            'format' => $data['format'] ?? null,
            'date_range' => $data['date_range'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'tenant_id' => $this->tenantId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update a container item.
     */
    public function updateContainerItem(int $itemId, array $data): bool
    {
        $update = [];
        $fields = ['title', 'description', 'quantity', 'format', 'date_range', 'sort_order', 'information_object_id'];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $update[$f] = $data[$f];
            }
        }

        if (empty($update)) {
            return false;
        }

        DB::table('accession_container_item')->where('id', $itemId)->update($update);

        return true;
    }

    /**
     * Delete a container item.
     */
    public function deleteContainerItem(int $itemId): void
    {
        DB::table('accession_container_item')->where('id', $itemId)->delete();
    }

    /**
     * Link a container item to an information object.
     */
    public function linkItemToIO(int $itemId, int $ioId): bool
    {
        $item = DB::table('accession_container_item')->where('id', $itemId)->first();
        if (!$item) {
            return false;
        }

        DB::table('accession_container_item')
            ->where('id', $itemId)
            ->update(['information_object_id' => $ioId]);

        return true;
    }

    /**
     * Get items in a container with linked IO titles.
     */
    public function getContainerItems(int $containerId): array
    {
        return DB::table('accession_container_item as ci')
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('ci.information_object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->where('ci.container_id', $containerId)
            ->select(
                'ci.*',
                'ioi.title as io_title'
            )
            ->orderBy('ci.sort_order')
            ->get()
            ->all();
    }

    /**
     * Lookup a container by barcode.
     */
    public function lookupBarcode(string $barcode): ?object
    {
        $query = DB::table('accession_container as c')
            ->join('accession as a', 'c.accession_id', '=', 'a.id')
            ->leftJoin('accession_i18n as ai', function ($j) {
                $j->on('a.id', '=', 'ai.id')
                    ->where('ai.culture', '=', 'en');
            })
            ->leftJoin('slug', 'a.id', '=', 'slug.object_id')
            ->where('c.barcode', $barcode)
            ->select(
                'c.*',
                'a.identifier as accession_identifier',
                'ai.title as accession_title',
                'slug.slug as accession_slug'
            );

        $this->scopeQuery($query);

        return $query->first();
    }

    // =========================================================================
    // RIGHTS
    // =========================================================================

    /**
     * Create a right for an accession.
     */
    public function createRight(int $accessionId, array $data): int
    {
        $now = date('Y-m-d H:i:s');

        $id = DB::table('accession_rights')->insertGetId([
            'accession_id' => $accessionId,
            'rights_basis' => $data['rights_basis'],
            'rights_holder' => $data['rights_holder'] ?? null,
            'rights_holder_id' => $data['rights_holder_id'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'restriction_type' => $data['restriction_type'] ?? 'none',
            'conditions' => $data['conditions'] ?? null,
            'grant_act' => $data['grant_act'] ?? null,
            'grant_restriction' => $data['grant_restriction'] ?? null,
            'notes' => $data['notes'] ?? null,
            'inherit_to_children' => $data['inherit_to_children'] ?? 1,
            'tenant_id' => $this->tenantId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($this->intakeService) {
            $this->intakeService->addTimelineEvent(
                $accessionId,
                AccessionIntakeService::EVENT_RIGHTS_ASSIGNED,
                null,
                'Right added: ' . $data['rights_basis'],
                ['right_id' => $id]
            );
        }

        return $id;
    }

    /**
     * Update a right.
     */
    public function updateRight(int $rightId, array $data): bool
    {
        $right = DB::table('accession_rights')->where('id', $rightId)->first();
        if (!$right) {
            return false;
        }

        $update = [];
        $fields = [
            'rights_basis', 'rights_holder', 'rights_holder_id', 'start_date', 'end_date',
            'restriction_type', 'conditions', 'grant_act', 'grant_restriction', 'notes',
            'inherit_to_children',
        ];
        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                $update[$f] = $data[$f];
            }
        }

        if (!empty($update)) {
            $update['updated_at'] = date('Y-m-d H:i:s');
            DB::table('accession_rights')->where('id', $rightId)->update($update);
        }

        return true;
    }

    /**
     * Delete a right and its inherited records.
     */
    public function deleteRight(int $rightId): bool
    {
        $right = DB::table('accession_rights')->where('id', $rightId)->first();
        if (!$right) {
            return false;
        }

        DB::table('accession_rights_inherited')->where('rights_id', $rightId)->delete();
        DB::table('accession_rights')->where('id', $rightId)->delete();

        return true;
    }

    /**
     * Get rights for an accession.
     */
    public function getRights(int $accessionId): array
    {
        $query = DB::table('accession_rights')
            ->where('accession_id', $accessionId)
            ->orderBy('created_at', 'desc');

        $this->scopeQuery($query);

        return $query->get()->all();
    }

    /**
     * Inherit rights to linked child information objects.
     */
    public function inheritRightsToChildren(int $rightId, int $userId): int
    {
        $right = DB::table('accession_rights')->where('id', $rightId)->first();
        if (!$right || !$right->inherit_to_children) {
            return 0;
        }

        // Find all IOs linked to this accession via container items
        $linkedIOs = DB::table('accession_container_item as ci')
            ->join('accession_container as c', 'ci.container_id', '=', 'c.id')
            ->where('c.accession_id', $right->accession_id)
            ->whereNotNull('ci.information_object_id')
            ->pluck('ci.information_object_id')
            ->unique()
            ->all();

        // Also check for IOs linked via the relation table (accession → io)
        $relatedIOs = DB::table('relation')
            ->where('subject_id', $right->accession_id)
            ->where('type_id', \QubitTerm::ACCESSION_ID ?? 178)
            ->pluck('object_id')
            ->all();

        $allIOs = array_unique(array_merge($linkedIOs, $relatedIOs));

        $now = date('Y-m-d H:i:s');
        $count = 0;

        foreach ($allIOs as $ioId) {
            // Skip if already inherited
            $exists = DB::table('accession_rights_inherited')
                ->where('rights_id', $rightId)
                ->where('information_object_id', $ioId)
                ->exists();

            if (!$exists) {
                DB::table('accession_rights_inherited')->insert([
                    'rights_id' => $rightId,
                    'information_object_id' => $ioId,
                    'applied_at' => $now,
                    'applied_by' => $userId,
                    'tenant_id' => $this->tenantId,
                ]);
                $count++;
            }
        }

        if ($this->intakeService && $count > 0) {
            $this->intakeService->addTimelineEvent(
                $right->accession_id,
                AccessionIntakeService::EVENT_RIGHTS_ASSIGNED,
                $userId,
                sprintf('Rights inherited to %d information objects', $count),
                ['right_id' => $rightId, 'io_count' => $count]
            );
        }

        return $count;
    }

    /**
     * Get rights inherited from accession for an IO.
     */
    public function getInheritedRights(int $ioId): array
    {
        return DB::table('accession_rights_inherited as ri')
            ->join('accession_rights as ar', 'ri.rights_id', '=', 'ar.id')
            ->join('accession as a', 'ar.accession_id', '=', 'a.id')
            ->leftJoin('slug', 'a.id', '=', 'slug.object_id')
            ->where('ri.information_object_id', $ioId)
            ->select(
                'ar.*',
                'ri.applied_at',
                'ri.applied_by',
                'a.identifier as accession_identifier',
                'slug.slug as accession_slug'
            )
            ->get()
            ->all();
    }

    // =========================================================================
    // NUMBERING
    // =========================================================================

    /**
     * Generate the next accession number for a repository.
     */
    public function generateNextNumber(?int $repositoryId = null): string
    {
        return DB::transaction(function () use ($repositoryId) {
            // Get or create sequence
            $seq = DB::table('accession_numbering_sequence')
                ->where('repository_id', $repositoryId)
                ->where('tenant_id', $this->tenantId)
                ->lockForUpdate()
                ->first();

            if (!$seq) {
                $mask = DB::table('accession_config')
                    ->where('config_key', 'numbering_mask')
                    ->where('tenant_id', $this->tenantId)
                    ->value('config_value')
                    ?? '{YEAR}-{SEQ:5}';

                $seqId = DB::table('accession_numbering_sequence')->insertGetId([
                    'repository_id' => $repositoryId,
                    'mask' => $mask,
                    'last_sequence' => 0,
                    'last_year' => (int) date('Y'),
                    'tenant_id' => $this->tenantId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $seq = DB::table('accession_numbering_sequence')->where('id', $seqId)->first();
            }

            $currentYear = (int) date('Y');
            $nextSeq = $seq->last_sequence + 1;

            // Reset sequence if year changed
            if ($seq->last_year !== null && $seq->last_year != $currentYear) {
                $nextSeq = 1;
            }

            DB::table('accession_numbering_sequence')
                ->where('id', $seq->id)
                ->update([
                    'last_sequence' => $nextSeq,
                    'last_year' => $currentYear,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            // Build number from mask
            $mask = $seq->mask;
            $number = $mask;
            $number = str_replace('{YEAR}', (string) $currentYear, $number);
            $number = str_replace('{MONTH}', date('m'), $number);
            $number = str_replace('{DAY}', date('d'), $number);

            // Handle {SEQ:n} token
            if (preg_match('/\{SEQ:(\d+)\}/', $number, $m)) {
                $padLen = (int) $m[1];
                $number = str_replace($m[0], str_pad((string) $nextSeq, $padLen, '0', STR_PAD_LEFT), $number);
            } else {
                $number = str_replace('{SEQ}', (string) $nextSeq, $number);
            }

            // Handle {REPO} token
            if ($repositoryId && strpos($number, '{REPO}') !== false) {
                $repoCode = DB::table('repository')
                    ->where('id', $repositoryId)
                    ->value('identifier') ?? '';
                $number = str_replace('{REPO}', $repoCode, $number);
            }

            return $number;
        });
    }

    // =========================================================================
    // CONFIGURATION
    // =========================================================================

    /**
     * Get an accession config value.
     */
    public function getConfig(string $key, ?string $default = null): ?string
    {
        $query = DB::table('accession_config')
            ->where('config_key', $key);

        if ($this->tenantId !== null) {
            $query->where('tenant_id', $this->tenantId);
        } else {
            $query->whereNull('tenant_id');
        }

        return $query->value('config_value') ?? $default;
    }

    /**
     * Set an accession config value.
     */
    public function setConfig(string $key, ?string $value): void
    {
        $now = date('Y-m-d H:i:s');

        $exists = DB::table('accession_config')
            ->where('config_key', $key)
            ->where('tenant_id', $this->tenantId)
            ->exists();

        if ($exists) {
            DB::table('accession_config')
                ->where('config_key', $key)
                ->where('tenant_id', $this->tenantId)
                ->update([
                    'config_value' => $value,
                    'updated_at' => $now,
                ]);
        } else {
            DB::table('accession_config')->insert([
                'config_key' => $key,
                'config_value' => $value,
                'tenant_id' => $this->tenantId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Get all config values.
     */
    public function getAllConfig(): array
    {
        $query = DB::table('accession_config');

        if ($this->tenantId !== null) {
            $query->where('tenant_id', $this->tenantId);
        } else {
            $query->whereNull('tenant_id');
        }

        return $query->pluck('config_value', 'config_key')->all();
    }

    // =========================================================================
    // CLEANUP
    // =========================================================================

    /**
     * Delete all container and rights data for an accession.
     */
    public function deleteAllForAccession(int $accessionId): void
    {
        // Delete container items
        $containerIds = DB::table('accession_container')
            ->where('accession_id', $accessionId)
            ->pluck('id')
            ->all();
        if (!empty($containerIds)) {
            DB::table('accession_container_item')
                ->whereIn('container_id', $containerIds)
                ->delete();
        }

        DB::table('accession_container')->where('accession_id', $accessionId)->delete();

        // Delete rights + inherited
        $rightIds = DB::table('accession_rights')
            ->where('accession_id', $accessionId)
            ->pluck('id')
            ->all();
        if (!empty($rightIds)) {
            DB::table('accession_rights_inherited')
                ->whereIn('rights_id', $rightIds)
                ->delete();
        }

        DB::table('accession_rights')->where('accession_id', $accessionId)->delete();
    }
}
