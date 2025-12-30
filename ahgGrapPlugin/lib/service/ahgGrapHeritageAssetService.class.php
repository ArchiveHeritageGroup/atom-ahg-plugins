<?php
/**
 * GRAP 103 Heritage Asset Service
 * 
 * Implements GRAP 103 - Heritage Assets accounting standard for South African
 * public sector entities. Manages heritage asset recognition, measurement,
 * impairment, de-recognition, and disclosure requirements.
 * 
 * GRAP 103 Key Requirements:
 * - Initial recognition criteria
 * - Measurement (cost or revalued amount)
 * - Subsequent costs
 * - Impairment
 * - De-recognition
 * - Disclosure
 * 
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgGrapPlugin
 * @see GRAP 103 - Heritage Assets (ASB South Africa)
 */

class ahgGrapHeritageAssetService
{
    // Recognition Status
    const STATUS_UNRECOGNISED = 'unrecognised';
    const STATUS_PENDING_RECOGNITION = 'pending_recognition';
    const STATUS_RECOGNISED = 'recognised';
    const STATUS_IMPAIRED = 'impaired';
    const STATUS_PENDING_DERECOGNITION = 'pending_derecognition';
    const STATUS_DERECOGNISED = 'derecognised';

    // Measurement Basis (GRAP 103.26-35)
    const MEASUREMENT_COST = 'cost';
    const MEASUREMENT_FAIR_VALUE = 'fair_value';
    const MEASUREMENT_NOMINAL = 'nominal';  // R1 for items that cannot be reliably measured

    // Asset Classes (GRAP 103.74)
    const CLASS_ART_COLLECTIONS = 'art_collections';
    const CLASS_MUSEUM_COLLECTIONS = 'museum_collections';
    const CLASS_LIBRARY_COLLECTIONS = 'library_collections';
    const CLASS_ARCHIVAL_COLLECTIONS = 'archival_collections';
    const CLASS_NATURAL_HERITAGE = 'natural_heritage';
    const CLASS_BUILT_HERITAGE = 'built_heritage';
    const CLASS_MONUMENTS = 'monuments';
    const CLASS_ARCHAEOLOGICAL = 'archaeological';
    const CLASS_OTHER = 'other';

    // Impairment Indicators (GRAP 21/26)
    const IMPAIRMENT_PHYSICAL_DAMAGE = 'physical_damage';
    const IMPAIRMENT_OBSOLESCENCE = 'obsolescence';
    const IMPAIRMENT_LOSS_OF_SERVICE = 'loss_of_service';
    const IMPAIRMENT_CHANGE_IN_USE = 'change_in_use';
    const IMPAIRMENT_DECISION_TO_HALT = 'decision_to_halt';

    // De-recognition Reasons (GRAP 103.52-56)
    const DEREC_DISPOSAL = 'disposal';
    const DEREC_DONATION = 'donation';
    const DEREC_DESTRUCTION = 'destruction';
    const DEREC_LOSS = 'loss';
    const DEREC_THEFT = 'theft';
    const DEREC_TRANSFER = 'transfer';
    const DEREC_NO_FUTURE_BENEFIT = 'no_future_benefit';

    // Spectrum Procedure Linkages
    public static $spectrumLinkages = [
        'acquisition' => [
            'grap_action' => 'initial_recognition',
            'description' => 'Initial recognition of heritage asset',
            'required_fields' => ['acquisition_date', 'measurement_basis', 'initial_value']
        ],
        'valuation' => [
            'grap_action' => 'revaluation',
            'description' => 'Subsequent measurement/revaluation',
            'required_fields' => ['valuation_date', 'fair_value', 'valuer']
        ],
        'object_condition' => [
            'grap_action' => 'impairment_assessment',
            'description' => 'Assessment for impairment indicators',
            'required_fields' => ['condition_date', 'condition_rating']
        ],
        'deaccession' => [
            'grap_action' => 'pending_derecognition',
            'description' => 'Initiate de-recognition process',
            'required_fields' => ['deaccession_date', 'deaccession_reason']
        ],
        'object_exit' => [
            'grap_action' => 'derecognition',
            'description' => 'Final de-recognition of heritage asset',
            'required_fields' => ['exit_date', 'disposal_proceeds']
        ],
        'loss_damage' => [
            'grap_action' => 'impairment_loss',
            'description' => 'Record impairment loss',
            'required_fields' => ['incident_date', 'damage_extent']
        ],
        'insurance' => [
            'grap_action' => 'disclosure_update',
            'description' => 'Update insurance/indemnity disclosure',
            'required_fields' => ['coverage_amount', 'policy_details']
        ]
    ];

    // Asset Class Labels
    public static $assetClassLabels = [
        self::CLASS_ART_COLLECTIONS => 'Art Collections',
        self::CLASS_MUSEUM_COLLECTIONS => 'Museum Collections',
        self::CLASS_LIBRARY_COLLECTIONS => 'Library Collections',
        self::CLASS_ARCHIVAL_COLLECTIONS => 'Archival Collections',
        self::CLASS_NATURAL_HERITAGE => 'Natural Heritage',
        self::CLASS_BUILT_HERITAGE => 'Built Heritage',
        self::CLASS_MONUMENTS => 'Monuments and Memorials',
        self::CLASS_ARCHAEOLOGICAL => 'Archaeological Sites/Objects',
        self::CLASS_OTHER => 'Other Heritage Assets'
    ];

    // Status Labels
    public static $statusLabels = [
        self::STATUS_UNRECOGNISED => ['label' => 'Unrecognised', 'color' => '#95a5a6'],
        self::STATUS_PENDING_RECOGNITION => ['label' => 'Pending Recognition', 'color' => '#f39c12'],
        self::STATUS_RECOGNISED => ['label' => 'Recognised', 'color' => '#27ae60'],
        self::STATUS_IMPAIRED => ['label' => 'Impaired', 'color' => '#e67e22'],
        self::STATUS_PENDING_DERECOGNITION => ['label' => 'Pending De-recognition', 'color' => '#9b59b6'],
        self::STATUS_DERECOGNISED => ['label' => 'De-recognised', 'color' => '#c0392b']
    ];

    protected $conn;

    public function __construct()
    {
        $this->conn = Propel::getConnection();
    }

    /**
     * Get or create GRAP record for an object
     */
    public function getAssetRecord($objectId)
    {
        $sql = "SELECT * FROM grap_heritage_asset WHERE object_id = :object_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':object_id' => $objectId]);
        
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$record) {
            return $this->createDefaultRecord($objectId);
        }
        
        // Decode JSON fields
        if ($record['valuation_history']) {
            $record['valuation_history'] = json_decode($record['valuation_history'], true);
        }
        if ($record['impairment_history']) {
            $record['impairment_history'] = json_decode($record['impairment_history'], true);
        }
        if ($record['metadata']) {
            $record['metadata'] = json_decode($record['metadata'], true);
        }
        
        return $record;
    }

    /**
     * Create default GRAP record
     */
    protected function createDefaultRecord($objectId)
    {
        return [
            'id' => null,
            'object_id' => $objectId,
            'recognition_status' => self::STATUS_UNRECOGNISED,
            'asset_class' => null,
            'measurement_basis' => null,
            'initial_recognition_date' => null,
            'initial_cost' => null,
            'carrying_amount' => null,
            'accumulated_impairment' => 0,
            'revaluation_surplus' => 0,
            'last_valuation_date' => null,
            'last_valuation_amount' => null,
            'valuation_history' => [],
            'impairment_history' => [],
            'derecognition_date' => null,
            'derecognition_reason' => null,
            'disposal_proceeds' => null,
            'gain_loss_on_disposal' => null,
            'useful_life' => 'indefinite',
            'depreciation_method' => 'none',
            'residual_value' => null,
            'funding_source' => null,
            'donor_restrictions' => null,
            'insurance_value' => null,
            'metadata' => [],
            'created_at' => null,
            'updated_at' => null
        ];
    }

    /**
     * Save GRAP heritage asset record
     */
    public function saveAssetRecord($objectId, $data)
    {
        $existing = $this->getAssetRecord($objectId);
        $now = date('Y-m-d H:i:s');

        // Encode JSON fields
        $valuationHistory = isset($data['valuation_history']) 
            ? json_encode($data['valuation_history']) 
            : json_encode($existing['valuation_history'] ?? []);
        $impairmentHistory = isset($data['impairment_history']) 
            ? json_encode($data['impairment_history']) 
            : json_encode($existing['impairment_history'] ?? []);
        $metadata = isset($data['metadata']) 
            ? json_encode($data['metadata']) 
            : json_encode($existing['metadata'] ?? []);

        if ($existing['id']) {
            // Update
            $sql = "UPDATE grap_heritage_asset SET
                    recognition_status = :recognition_status,
                    asset_class = :asset_class,
                    measurement_basis = :measurement_basis,
                    initial_recognition_date = :initial_recognition_date,
                    initial_cost = :initial_cost,
                    current_carrying_amount = :carrying_amount,
                    accumulated_impairment = :accumulated_impairment,
                    revaluation_surplus = :revaluation_surplus,
                    last_valuation_date = :last_valuation_date,
                    last_valuation_amount = :last_valuation_amount,
                    valuation_history = :valuation_history,
                    impairment_history = :impairment_history,
                    derecognition_date = :derecognition_date,
                    derecognition_reason = :derecognition_reason,
                    disposal_proceeds = :disposal_proceeds,
                    gain_loss_on_disposal = :gain_loss_on_disposal,
                    useful_life = :useful_life,
                    depreciation_method = :depreciation_method,
                    residual_value = :residual_value,
                    funding_source = :funding_source,
                    donor_restrictions = :donor_restrictions,
                    insurance_value = :insurance_value,
                    metadata = :metadata,
                    updated_at = :updated_at
                    WHERE object_id = :object_id";
        } else {
            // Insert
            $sql = "INSERT INTO grap_heritage_asset 
                    (object_id, recognition_status, asset_class, measurement_basis,
                     initial_recognition_date, initial_cost, current_carrying_amount,
                     accumulated_impairment, revaluation_surplus, last_valuation_date,
                     last_valuation_amount, valuation_history, impairment_history,
                     derecognition_date, derecognition_reason, disposal_proceeds,
                     gain_loss_on_disposal, useful_life, depreciation_method,
                     residual_value, funding_source, donor_restrictions, insurance_value,
                     metadata, created_at, updated_at)
                    VALUES
                    (:object_id, :recognition_status, :asset_class, :measurement_basis,
                     :initial_recognition_date, :initial_cost, :carrying_amount,
                     :accumulated_impairment, :revaluation_surplus, :last_valuation_date,
                     :last_valuation_amount, :valuation_history, :impairment_history,
                     :derecognition_date, :derecognition_reason, :disposal_proceeds,
                     :gain_loss_on_disposal, :useful_life, :depreciation_method,
                     :residual_value, :funding_source, :donor_restrictions, :insurance_value,
                     :metadata, :created_at, :updated_at)";
        }

        $stmt = $this->conn->prepare($sql);
        $params = [
            ':object_id' => $objectId,
            ':recognition_status' => $data['recognition_status'] ?? $existing['recognition_status'],
            ':asset_class' => $data['asset_class'] ?? $existing['asset_class'],
            ':measurement_basis' => $data['measurement_basis'] ?? $existing['measurement_basis'],
            ':initial_recognition_date' => $data['initial_recognition_date'] ?? $existing['initial_recognition_date'],
            ':initial_cost' => $data['initial_cost'] ?? $existing['initial_cost'],
            ':carrying_amount' => $data['carrying_amount'] ?? $existing['carrying_amount'],
            ':accumulated_impairment' => $data['accumulated_impairment'] ?? $existing['accumulated_impairment'] ?? 0,
            ':revaluation_surplus' => $data['revaluation_surplus'] ?? $existing['revaluation_surplus'] ?? 0,
            ':last_valuation_date' => $data['last_valuation_date'] ?? $existing['last_valuation_date'],
            ':last_valuation_amount' => $data['last_valuation_amount'] ?? $existing['last_valuation_amount'],
            ':valuation_history' => $valuationHistory,
            ':impairment_history' => $impairmentHistory,
            ':derecognition_date' => $data['derecognition_date'] ?? $existing['derecognition_date'],
            ':derecognition_reason' => $data['derecognition_reason'] ?? $existing['derecognition_reason'],
            ':disposal_proceeds' => $data['disposal_proceeds'] ?? $existing['disposal_proceeds'],
            ':gain_loss_on_disposal' => $data['gain_loss_on_disposal'] ?? $existing['gain_loss_on_disposal'],
            ':useful_life' => $data['useful_life'] ?? $existing['useful_life'] ?? 'indefinite',
            ':depreciation_method' => $data['depreciation_method'] ?? $existing['depreciation_method'] ?? 'none',
            ':residual_value' => $data['residual_value'] ?? $existing['residual_value'],
            ':funding_source' => $data['funding_source'] ?? $existing['funding_source'],
            ':donor_restrictions' => $data['donor_restrictions'] ?? $existing['donor_restrictions'],
            ':insurance_value' => $data['insurance_value'] ?? $existing['insurance_value'],
            ':metadata' => $metadata,
            ':updated_at' => $now
        ];

        if (!$existing['id']) {
            $params[':created_at'] = $now;
        }

        $stmt->execute($params);

        // Log the transaction
        $this->logTransaction($objectId, 'update', $data);

        return $existing['id'] ? $existing['id'] : $this->conn->lastInsertId();
    }

    /**
     * Perform initial recognition (GRAP 103.14-25)
     */
    public function performInitialRecognition($objectId, $data)
    {
        // Validate required fields
        if (empty($data['asset_class'])) {
            throw new Exception('Asset class is required for recognition');
        }
        if (empty($data['measurement_basis'])) {
            throw new Exception('Measurement basis is required for recognition');
        }
        if (empty($data['initial_cost']) && $data['measurement_basis'] !== self::MEASUREMENT_NOMINAL) {
            throw new Exception('Initial cost/value is required for recognition');
        }

        $recognitionDate = $data['initial_recognition_date'] ?? date('Y-m-d');
        $initialCost = $data['initial_cost'] ?? 1; // R1 for nominal

        $saveData = [
            'recognition_status' => self::STATUS_RECOGNISED,
            'asset_class' => $data['asset_class'],
            'measurement_basis' => $data['measurement_basis'],
            'initial_recognition_date' => $recognitionDate,
            'initial_cost' => $initialCost,
            'carrying_amount' => $initialCost,
            'funding_source' => $data['funding_source'] ?? null,
            'donor_restrictions' => $data['donor_restrictions'] ?? null,
            'metadata' => [
                'recognition_authorised_by' => $data['authorised_by'] ?? null,
                'recognition_notes' => $data['notes'] ?? null
            ]
        ];

        $this->saveAssetRecord($objectId, $saveData);
        $this->logTransaction($objectId, 'initial_recognition', $saveData);

        return true;
    }

    /**
     * Record revaluation (GRAP 103.36-45)
     */
    public function recordRevaluation($objectId, $newValue, $valuationDate, $valuer, $notes = null)
    {
        $record = $this->getAssetRecord($objectId);

        if ($record['recognition_status'] !== self::STATUS_RECOGNISED && 
            $record['recognition_status'] !== self::STATUS_IMPAIRED) {
            throw new Exception('Asset must be recognised before revaluation');
        }

        $previousValue = $record['carrying_amount'];
        $difference = $newValue - $previousValue;

        // Update valuation history
        $history = $record['valuation_history'] ?? [];
        $history[] = [
            'date' => $valuationDate,
            'previous_value' => $previousValue,
            'new_value' => $newValue,
            'difference' => $difference,
            'valuer' => $valuer,
            'notes' => $notes
        ];

        // Calculate revaluation surplus/deficit
        $revaluationSurplus = $record['revaluation_surplus'] ?? 0;
        if ($difference > 0) {
            // Increase - credit to revaluation surplus (unless reversing previous decrease)
            $revaluationSurplus += $difference;
        } else {
            // Decrease - first reduce surplus, then recognise as expense
            if ($revaluationSurplus >= abs($difference)) {
                $revaluationSurplus += $difference; // Reduces surplus
            } else {
                $revaluationSurplus = 0; // Remaining goes to expense
            }
        }

        $saveData = [
            'carrying_amount' => $newValue,
            'last_valuation_date' => $valuationDate,
            'last_valuation_amount' => $newValue,
            'revaluation_surplus' => $revaluationSurplus,
            'valuation_history' => $history
        ];

        $this->saveAssetRecord($objectId, $saveData);
        $this->logTransaction($objectId, 'revaluation', array_merge($saveData, [
            'valuer' => $valuer,
            'notes' => $notes
        ]));

        return [
            'previous_value' => $previousValue,
            'new_value' => $newValue,
            'difference' => $difference,
            'revaluation_surplus' => $revaluationSurplus
        ];
    }

    /**
     * Record impairment (GRAP 21/26)
     */
    public function recordImpairment($objectId, $impairmentAmount, $indicator, $details = null)
    {
        $record = $this->getAssetRecord($objectId);

        if ($record['recognition_status'] !== self::STATUS_RECOGNISED) {
            throw new Exception('Asset must be recognised before recording impairment');
        }

        $previousCarrying = $record['carrying_amount'];
        $newCarrying = $previousCarrying - $impairmentAmount;

        if ($newCarrying < 0) {
            throw new Exception('Impairment cannot exceed carrying amount');
        }

        // Update impairment history
        $history = $record['impairment_history'] ?? [];
        $history[] = [
            'date' => date('Y-m-d'),
            'amount' => $impairmentAmount,
            'indicator' => $indicator,
            'previous_carrying' => $previousCarrying,
            'new_carrying' => $newCarrying,
            'details' => $details
        ];

        $accumulatedImpairment = ($record['accumulated_impairment'] ?? 0) + $impairmentAmount;

        $saveData = [
            'recognition_status' => self::STATUS_IMPAIRED,
            'carrying_amount' => $newCarrying,
            'accumulated_impairment' => $accumulatedImpairment,
            'impairment_history' => $history
        ];

        $this->saveAssetRecord($objectId, $saveData);
        $this->logTransaction($objectId, 'impairment', $saveData);

        return [
            'impairment_amount' => $impairmentAmount,
            'previous_carrying' => $previousCarrying,
            'new_carrying' => $newCarrying,
            'accumulated_impairment' => $accumulatedImpairment
        ];
    }

    /**
     * Reverse impairment
     */
    public function reverseImpairment($objectId, $reversalAmount, $reason)
    {
        $record = $this->getAssetRecord($objectId);

        if ($record['recognition_status'] !== self::STATUS_IMPAIRED) {
            throw new Exception('No impairment to reverse');
        }

        $maxReversal = $record['accumulated_impairment'];
        if ($reversalAmount > $maxReversal) {
            throw new Exception('Reversal cannot exceed accumulated impairment');
        }

        $previousCarrying = $record['carrying_amount'];
        $newCarrying = $previousCarrying + $reversalAmount;
        $newAccumulated = $record['accumulated_impairment'] - $reversalAmount;

        // Update impairment history
        $history = $record['impairment_history'] ?? [];
        $history[] = [
            'date' => date('Y-m-d'),
            'amount' => -$reversalAmount,
            'indicator' => 'reversal',
            'previous_carrying' => $previousCarrying,
            'new_carrying' => $newCarrying,
            'details' => $reason
        ];

        $newStatus = $newAccumulated > 0 ? self::STATUS_IMPAIRED : self::STATUS_RECOGNISED;

        $saveData = [
            'recognition_status' => $newStatus,
            'carrying_amount' => $newCarrying,
            'accumulated_impairment' => $newAccumulated,
            'impairment_history' => $history
        ];

        $this->saveAssetRecord($objectId, $saveData);
        $this->logTransaction($objectId, 'impairment_reversal', $saveData);

        return [
            'reversal_amount' => $reversalAmount,
            'new_carrying' => $newCarrying,
            'remaining_impairment' => $newAccumulated
        ];
    }

    /**
     * Initiate de-recognition (GRAP 103.52-56)
     */
    public function initiateDerecognition($objectId, $reason, $expectedProceeds = null)
    {
        $record = $this->getAssetRecord($objectId);

        if ($record['recognition_status'] === self::STATUS_DERECOGNISED) {
            throw new Exception('Asset already de-recognised');
        }

        $saveData = [
            'recognition_status' => self::STATUS_PENDING_DERECOGNITION,
            'derecognition_reason' => $reason,
            'metadata' => array_merge($record['metadata'] ?? [], [
                'expected_proceeds' => $expectedProceeds,
                'derecognition_initiated' => date('Y-m-d'),
                'initiated_by' => sfContext::getInstance()->user->getAttribute('user_id')
            ])
        ];

        $this->saveAssetRecord($objectId, $saveData);
        $this->logTransaction($objectId, 'derecognition_initiated', $saveData);

        return true;
    }

    /**
     * Complete de-recognition
     */
    public function completeDerecognition($objectId, $disposalProceeds = 0, $disposalDate = null)
    {
        $record = $this->getAssetRecord($objectId);

        if ($record['recognition_status'] !== self::STATUS_PENDING_DERECOGNITION) {
            throw new Exception('Asset must be pending de-recognition');
        }

        $carryingAmount = $record['carrying_amount'] ?? 0;
        $gainLoss = $disposalProceeds - $carryingAmount;

        $saveData = [
            'recognition_status' => self::STATUS_DERECOGNISED,
            'derecognition_date' => $disposalDate ?? date('Y-m-d'),
            'disposal_proceeds' => $disposalProceeds,
            'gain_loss_on_disposal' => $gainLoss,
            'carrying_amount' => 0
        ];

        $this->saveAssetRecord($objectId, $saveData);
        $this->logTransaction($objectId, 'derecognition_completed', $saveData);

        return [
            'carrying_amount' => $carryingAmount,
            'disposal_proceeds' => $disposalProceeds,
            'gain_loss' => $gainLoss
        ];
    }

    /**
     * Log GRAP transaction for audit trail
     */
    protected function logTransaction($objectId, $transactionType, $data)
    {
        $sql = "INSERT INTO grap_transaction_log 
                (object_id, transaction_type, transaction_data, user_id, created_at)
                VALUES (:object_id, :transaction_type, :transaction_data, :user_id, :created_at)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':object_id' => $objectId,
            ':transaction_type' => $transactionType,
            ':transaction_data' => json_encode($data),
            ':user_id' => sfContext::getInstance()->user->getAttribute('user_id'),
            ':created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get transaction history
     */
    public function getTransactionHistory($objectId, $limit = 100)
    {
        $sql = "SELECT t.*, u.username 
                FROM grap_transaction_log t
                LEFT JOIN user u ON t.user_id = u.id
                WHERE t.object_id = :object_id
                ORDER BY t.created_at DESC
                LIMIT :limit";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':object_id', $objectId);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($results as &$row) {
            $row['transaction_data'] = json_decode($row['transaction_data'], true);
        }

        return $results;
    }

    /**
     * Get linked Spectrum procedure status
     */
    public function getLinkedSpectrumStatus($objectId)
    {
        $linked = [];

        // Check if ahgSpectrumPlugin is installed
        if (class_exists('arSpectrumEventService')) {
            $spectrumService = new arSpectrumEventService();
            
            foreach (self::$spectrumLinkages as $procedureId => $linkage) {
                $status = $spectrumService->getProcedureStatus($objectId, $procedureId);
                $linked[$procedureId] = [
                    'procedure' => $procedureId,
                    'grap_action' => $linkage['grap_action'],
                    'description' => $linkage['description'],
                    'status' => $status['current_status'] ?? 'not_started',
                    'last_update' => $status['last_update'] ?? null
                ];
            }
        }

        return $linked;
    }

    /**
     * Get repository-wide asset summary
     */
    public function getAssetSummary($repositoryId = null, $financialYear = null)
    {
        $where = "WHERE 1=1";
        $params = [];

        if ($repositoryId) {
            $where .= " AND io.repository_id = :repository_id";
            $params[':repository_id'] = $repositoryId;
        }

        $sql = "SELECT 
                    g.asset_class,
                    g.recognition_status,
                    COUNT(*) as count,
                    SUM(COALESCE(g.current_carrying_amount, 0)) as total_carrying_amount,
                    SUM(COALESCE(g.impairment_loss, 0)) as total_impairment_loss,
                    SUM(COALESCE(g.revaluation_surplus, 0)) as total_revaluation_surplus
                FROM grap_heritage_asset g
                JOIN information_object io ON g.object_id = io.id
                {$where}
                GROUP BY g.asset_class, g.recognition_status";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get assets by class for reporting
     */
    public function getAssetsByClass($assetClass, $repositoryId = null, $status = null)
    {
        $where = "WHERE g.asset_class = :asset_class";
        $params = [':asset_class' => $assetClass];

        if ($repositoryId) {
            $where .= " AND io.repository_id = :repository_id";
            $params[':repository_id'] = $repositoryId;
        }

        if ($status) {
            $where .= " AND g.recognition_status = :status";
            $params[':status'] = $status;
        }

        $sql = "SELECT g.*, io.identifier, io.slug
                FROM grap_heritage_asset g
                JOIN information_object io ON g.object_id = io.id
                {$where}
                ORDER BY g.current_carrying_amount DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
