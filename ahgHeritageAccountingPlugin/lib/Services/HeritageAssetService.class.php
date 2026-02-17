<?php
/**
 * Heritage Asset Service
 * Base service for heritage asset accounting - multi-standard support
 */

use Illuminate\Database\Capsule\Manager as DB;

class HeritageAssetService
{
    /**
     * Get all accounting standards
     */
    public function getAccountingStandards(): array
    {
        return DB::table('heritage_accounting_standard')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    /**
     * Get all asset classes
     */
    public function getAssetClasses(): array
    {
        return DB::table('heritage_asset_class')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    /**
     * Get heritage asset by ID
     */
    public function getAsset(int $id): ?object
    {
        return DB::table('heritage_asset as ha')
            ->leftJoin('heritage_accounting_standard as hs', 'ha.accounting_standard_id', '=', 'hs.id')
            ->leftJoin('heritage_asset_class as hc', 'ha.asset_class_id', '=', 'hc.id')
            ->leftJoin('information_object as io', function($join) { $join->on('ha.information_object_id', '=', 'io.id')->orOn('ha.object_id', '=', 'io.id'); })->leftJoin('information_object_i18n as ioi', function($join) { $join->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture()); })
            ->select([
                'ha.*',
                'hs.code as standard_code',
                'hs.name as standard_name',
                'hc.code as class_code',
                'hc.name as class_name',
                'io.identifier as object_identifier', 'ioi.title as object_title'
            ])
            ->where('ha.id', $id)
            ->first();
    }

    /**
     * Get heritage asset by object_id
     */
    public function getAssetByObjectId(int $objectId): ?object
    {
        return DB::table('heritage_asset as ha')
            ->leftJoin('heritage_accounting_standard as hs', 'ha.accounting_standard_id', '=', 'hs.id')
            ->leftJoin('heritage_asset_class as hc', 'ha.asset_class_id', '=', 'hc.id')
            ->select([
                'ha.*',
                'hs.code as standard_code',
                'hs.name as standard_name',
                'hc.code as class_code',
                'hc.name as class_name'
            ])
            ->where('ha.object_id', $objectId)
            ->first();
    }

    /**
     * Browse heritage assets with filters
     */
    public function browse(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $query = DB::table('heritage_asset as ha')
            ->leftJoin('heritage_accounting_standard as hs', 'ha.accounting_standard_id', '=', 'hs.id')
            ->leftJoin('heritage_asset_class as hc', 'ha.asset_class_id', '=', 'hc.id')
            ->leftJoin('information_object as io', function($join) { $join->on('ha.information_object_id', '=', 'io.id')->orOn('ha.object_id', '=', 'io.id'); })->leftJoin('information_object_i18n as ioi', function($join) { $join->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture()); })
            ->select([
                'ha.*',
                'hs.code as standard_code',
                'hs.name as standard_name',
                'hc.code as class_code',
                'hc.name as class_name',
                'io.identifier as object_identifier', 'ioi.title as object_title',
                'ioi.title as object_title'
            ]);

        // Apply filters
        if (!empty($filters['standard_id'])) {
            $query->where('ha.accounting_standard_id', $filters['standard_id']);
        }
        if (!empty($filters['class_id'])) {
            $query->where('ha.asset_class_id', $filters['class_id']);
        }
        if (!empty($filters['recognition_status'])) {
            $query->where('ha.recognition_status', $filters['recognition_status']);
        }
        if (!empty($filters['repository_id'])) {
            $query->where('io.repository_id', $filters['repository_id']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function($q) use ($search) {
                $q->where('ioi.title', 'like', $search)
                  ->orWhere('io.identifier', 'like', $search)
                  ->orWhere('ha.donor_name', 'like', $search);
            });
        }

        $total = $query->count();
        $items = $query->orderBy('ha.created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->toArray();

        return [
            'items' => $items,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    /**
     * Create heritage asset record
     */
    public function create(array $data): int
    {
        // Clean empty enum values
        $enumFields = ['acquisition_method', 'recognition_status', 'measurement_basis', 'condition_rating', 'heritage_significance'];
        foreach ($enumFields as $field) {
            if (isset($data[$field]) && $data[$field] === '') {
                $data[$field] = null;
            }
        }
        
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $id = DB::table('heritage_asset')->insertGetId($data);
        $this->logTransaction($id, $data['object_id'] ?? $data['information_object_id'] ?? null, 'create', null, $data);
        return $id;
    }

    /**
     * Update heritage asset record
     */
    public function update(int $id, array $data): bool
    {
        $oldValues = (array)(DB::table('heritage_asset')->where('id', $id)->first() ?? []);
        
        // Clean empty enum values
        $enumFields = ['acquisition_method', 'recognition_status', 'measurement_basis', 'condition_rating', 'heritage_significance'];
        foreach ($enumFields as $field) {
            if (isset($data[$field]) && $data[$field] === '') {
                $data[$field] = null;
            }
        }
        
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $result = DB::table('heritage_asset')
            ->where('id', $id)
            ->update($data);
        
        $asset = $this->getAsset($id);
        if ($asset) {
            $this->logTransaction($id, $asset->object_id, 'update', null, $data);
        }
        
        return $result > 0;
    }

    /**
     * Delete heritage asset record
     */
    public function delete(int $id): bool
    {
        $oldValues = (array)(DB::table('heritage_asset')->where('id', $id)->first() ?? []);
        $asset = $this->getAsset($id);
        if ($asset) {
            $this->logTransaction($id, $asset->object_id, 'delete', $asset->current_carrying_amount, null);
        }
        
        return DB::table('heritage_asset')->where('id', $id)->delete() > 0;
    }

    /**
     * Add valuation record
     */
    public function addValuation(int $assetId, array $data): int
    {
        $asset = $this->getAsset($assetId);
        if (!$asset) {
            throw new Exception('Asset not found');
        }

        $data['heritage_asset_id'] = $assetId;
        $data['previous_value'] = $asset->current_carrying_amount;
        $data['valuation_change'] = $data['new_value'] - $asset->current_carrying_amount;
        $data['created_at'] = date('Y-m-d H:i:s');

        $id = DB::table('heritage_valuation_history')->insertGetId($data);

        // Update asset
        $this->update($assetId, [
            'last_valuation_date' => $data['valuation_date'],
            'last_valuation_amount' => $data['new_value'],
            'current_carrying_amount' => $data['new_value'],
            'valuation_method' => $data['valuation_method'] ?? null,
            'valuer_name' => $data['valuer_name'] ?? null,
            'valuer_credentials' => $data['valuer_credentials'] ?? null
        ]);

        $this->logTransaction($assetId, $asset->object_id, 'valuation', $data['new_value'], $data);

        return $id;
    }

    /**
     * Add impairment assessment
     */
    public function addImpairment(int $assetId, array $data): int
    {
        $asset = $this->getAsset($assetId);
        if (!$asset) {
            throw new Exception('Asset not found');
        }

        $data['heritage_asset_id'] = $assetId;
        $data['carrying_amount_before'] = $asset->current_carrying_amount;
        $data['created_at'] = date('Y-m-d H:i:s');

        if ($data['impairment_identified'] ?? false) {
            $data['carrying_amount_after'] = $data['carrying_amount_before'] - ($data['impairment_loss'] ?? 0);
        }

        $id = DB::table('heritage_impairment_assessment')->insertGetId($data);

        // Update asset if impairment identified
        if ($data['impairment_identified'] ?? false) {
            $this->update($assetId, [
                'last_impairment_date' => $data['assessment_date'],
                'impairment_indicators' => 1,
                'impairment_indicators_details' => $data['impairment_indicators_details'] ?? null,
                'impairment_loss' => $asset->impairment_loss + ($data['impairment_loss'] ?? 0),
                'current_carrying_amount' => $data['carrying_amount_after']
            ]);
        }

        $this->logTransaction($assetId, $asset->object_id, 'impairment', $data['impairment_loss'] ?? 0, $data);

        return $id;
    }

    /**
     * Add movement record
     */
    public function addMovement(int $assetId, array $data): int
    {
        $asset = $this->getAsset($assetId);
        if (!$asset) {
            throw new Exception('Asset not found');
        }

        $data['heritage_asset_id'] = $assetId;
        $data['created_at'] = date('Y-m-d H:i:s');

        $id = DB::table('heritage_movement_register')->insertGetId($data);

        // Update asset location
        if (!empty($data['to_location'])) {
            $this->update($assetId, [
                'current_location' => $data['to_location']
            ]);
        }

        $this->logTransaction($assetId, $asset->object_id, 'movement', null, $data);

        return $id;
    }

    /**
     * Add journal entry
     */
    public function addJournal(int $assetId, array $data): int
    {
        $asset = $this->getAsset($assetId);
        if (!$asset) {
            throw new Exception('Asset not found');
        }

        $data['heritage_asset_id'] = $assetId;
        $data['created_at'] = date('Y-m-d H:i:s');

        $id = DB::table('heritage_journal_entry')->insertGetId($data);

        $this->logTransaction($assetId, $asset->object_id, 'journal_' . $data['journal_type'], $data['debit_amount'], $data);

        return $id;
    }

    /**
     * Get valuation history for asset
     */
    public function getValuationHistory(int $assetId): array
    {
        return DB::table('heritage_valuation_history')
            ->where('heritage_asset_id', $assetId)
            ->orderBy('valuation_date', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get impairment assessments for asset
     */
    public function getImpairmentAssessments(int $assetId): array
    {
        return DB::table('heritage_impairment_assessment')
            ->where('heritage_asset_id', $assetId)
            ->orderBy('assessment_date', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get movements for asset
     */
    public function getMovements(int $assetId): array
    {
        return DB::table('heritage_movement_register')
            ->where('heritage_asset_id', $assetId)
            ->orderBy('movement_date', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get journal entries for asset
     */
    public function getJournalEntries(int $assetId): array
    {
        return DB::table('heritage_journal_entry')
            ->where('heritage_asset_id', $assetId)
            ->orderBy('journal_date', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(?int $repositoryId = null, ?int $standardId = null): array
    {
        $query = DB::table('heritage_asset as ha')
            ->leftJoin('information_object as io', function($join) { $join->on('ha.information_object_id', '=', 'io.id')->orOn('ha.object_id', '=', 'io.id'); })->leftJoin('information_object_i18n as ioi', function($join) { $join->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture()); });

        if ($repositoryId) {
            $query->where('io.repository_id', $repositoryId);
        }
        if ($standardId) {
            $query->where('ha.accounting_standard_id', $standardId);
        }

        $total = (clone $query)->count();
        $recognised = (clone $query)->where('ha.recognition_status', 'recognised')->count();
        $notRecognised = (clone $query)->where('ha.recognition_status', 'not_recognised')->count();
        $pending = (clone $query)->where('ha.recognition_status', 'pending')->count();

        $totalValue = (clone $query)->where('ha.recognition_status', 'recognised')->sum('ha.current_carrying_amount');
        $totalImpairment = (clone $query)->sum('ha.impairment_loss');
        $totalRevaluationSurplus = (clone $query)->sum('ha.revaluation_surplus');

        // By class
        $byClass = DB::table('heritage_asset as ha')
            ->leftJoin('heritage_asset_class as hc', 'ha.asset_class_id', '=', 'hc.id')
            ->leftJoin('information_object as io', function($join) { $join->on('ha.information_object_id', '=', 'io.id')->orOn('ha.object_id', '=', 'io.id'); })->leftJoin('information_object_i18n as ioi', function($join) { $join->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture()); })
            ->select([
                'hc.name as class_name',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(ha.current_carrying_amount) as total_value')
            ])
            ->when($repositoryId, fn($q) => $q->where('io.repository_id', $repositoryId))
            ->when($standardId, fn($q) => $q->where('ha.accounting_standard_id', $standardId))
            ->groupBy('hc.id', 'hc.name')
            ->get()
            ->toArray();

        return [
            'total' => $total,
            'recognised' => $recognised,
            'not_recognised' => $notRecognised,
            'pending' => $pending,
            'total_value' => $totalValue,
            'total_impairment' => $totalImpairment,
            'total_revaluation_surplus' => $totalRevaluationSurplus,
            'by_class' => $byClass
        ];
    }

    /**
     * Log transaction
     */
    protected function logTransaction(?int $assetId, ?int $objectId, string $type, ?float $amount, ?array $data): void
    {
        DB::table('heritage_transaction_log')->insert([
            'heritage_asset_id' => $assetId,
            'object_id' => $objectId,
            'transaction_type' => $type,
            'transaction_date' => date('Y-m-d'),
            'amount' => $amount,
            'transaction_data' => $data ? json_encode($data) : null,
            'user_id' => sfContext::getInstance()->getUser()->getAttribute('user_id'),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    protected function logAudit(string $action, string $entityType, int $entityId, array $oldValues, array $newValues, ?string $title = null): void
    {
        try {
            $auditServicePath = \sfConfig::get('sf_root_dir') . '/plugins/ahgAuditTrailPlugin/lib/Services/AhgAuditService.php';
            if (file_exists($auditServicePath)) {
                require_once $auditServicePath;
            }

            if (class_exists('AhgAuditTrail\\Services\\AhgAuditService')) {
                $changedFields = [];
                foreach ($newValues as $key => $val) {
                    if (($oldValues[$key] ?? null) !== $val) {
                        $changedFields[] = $key;
                    }
                }
                if ($action === 'delete') {
                    $changedFields = array_keys($oldValues);
                }

                \AhgAuditTrail\Services\AhgAuditService::logAction(
                    $action,
                    $entityType,
                    $entityId,
                    [
                        'title' => $title,
                        'module' => $this->auditModule ?? 'ahgHeritageAccountingPlugin',
                        'action_name' => $action,
                        'old_values' => $oldValues,
                        'new_values' => $newValues,
                        'changed_fields' => $changedFields,
                    ]
                );
            }
        } catch (\Exception $e) {
            error_log("AUDIT ERROR: " . $e->getMessage());
        }
    }
    protected string $auditModule = 'ahgHeritageAccountingPlugin';
}
