<?php
/**
 * Spectrum Event Service
 * 
 * Manages Spectrum procedure events as first-class entities linked to objects.
 * Events are stored in the database with full audit trail capabilities.
 * 
 * Database Schema (install via ahgSpectrumPlugin::install()):
 * 
 * CREATE TABLE spectrum_event (
 *   id INT AUTO_INCREMENT PRIMARY KEY,
 *   object_id INT NOT NULL,
 *   procedure_id VARCHAR(50) NOT NULL,
 *   event_type VARCHAR(50) NOT NULL,
 *   status_from VARCHAR(50),
 *   status_to VARCHAR(50),
 *   user_id INT,
 *   assigned_to_id INT,
 *   due_date DATE,
 *   completed_date DATE,
 *   location VARCHAR(255),
 *   notes TEXT,
 *   metadata JSON,
 *   created_at DATETIME NOT NULL,
 *   updated_at DATETIME NOT NULL,
 *   INDEX idx_object_procedure (object_id, procedure_id),
 *   INDEX idx_created (created_at),
 *   INDEX idx_user (user_id),
 *   FOREIGN KEY (object_id) REFERENCES information_object(id) ON DELETE CASCADE
 * );
 * 
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgSpectrumPlugin
 */

class arSpectrumEventService
{
    // Event Types
    const EVENT_CREATED = 'created';
    const EVENT_STATUS_CHANGE = 'status_change';
    const EVENT_ASSIGNMENT = 'assignment';
    const EVENT_DUE_DATE_SET = 'due_date_set';
    const EVENT_DUE_DATE_CHANGE = 'due_date_change';
    const EVENT_NOTE_ADDED = 'note_added';
    const EVENT_LOCATION_CHANGE = 'location_change';
    const EVENT_COMPLETED = 'completed';
    const EVENT_REOPENED = 'reopened';
    const EVENT_CANCELLED = 'cancelled';
    const EVENT_CONDITION_RECORDED = 'condition_recorded';
    const EVENT_VALUATION_RECORDED = 'valuation_recorded';
    const EVENT_INSURANCE_UPDATED = 'insurance_updated';
    const EVENT_MOVEMENT_RECORDED = 'movement_recorded';
    const EVENT_LOAN_INITIATED = 'loan_initiated';
    const EVENT_LOAN_RETURNED = 'loan_returned';
    const EVENT_ACQUISITION_RECORDED = 'acquisition_recorded';
    const EVENT_DEACCESSION_APPROVED = 'deaccession_approved';
    const EVENT_DISPOSAL_COMPLETED = 'disposal_completed';

    // Status Constants
    const STATUS_NOT_STARTED = 'not_started';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_PENDING_REVIEW = 'pending_review';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ON_HOLD = 'on_hold';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_OVERDUE = 'overdue';

    // Spectrum 5.0 Procedures
    public static $procedures = [
        'object_entry' => [
            'name' => 'Object Entry',
            'category' => 'pre_entry',
            'spectrum_ref' => '1',
            'description' => 'Managing and documenting the receipt of objects not yet accessioned',
            'required_fields' => ['entry_date', 'entry_reason', 'depositor'],
            'triggers' => ['acquisition', 'loans_in']
        ],
        'acquisition' => [
            'name' => 'Acquisition',
            'category' => 'acquisition',
            'spectrum_ref' => '2',
            'description' => 'Managing and documenting the addition of objects to collections',
            'required_fields' => ['acquisition_date', 'acquisition_method', 'source'],
            'triggers' => ['cataloguing', 'location_movement']
        ],
        'location_movement' => [
            'name' => 'Location and Movement Control',
            'category' => 'location',
            'spectrum_ref' => '3',
            'description' => 'Documenting and managing object locations and movements',
            'required_fields' => ['current_location', 'movement_date'],
            'triggers' => []
        ],
        'inventory' => [
            'name' => 'Inventory Control',
            'category' => 'inventory',
            'spectrum_ref' => '4',
            'description' => 'Checking and maintaining object location records',
            'required_fields' => ['inventory_date', 'inventory_method'],
            'triggers' => []
        ],
        'cataloguing' => [
            'name' => 'Cataloguing',
            'category' => 'documentation',
            'spectrum_ref' => '5',
            'description' => 'Compiling and maintaining information about objects',
            'required_fields' => ['cataloguer', 'catalogue_level'],
            'triggers' => []
        ],
        'object_condition' => [
            'name' => 'Object Condition Checking',
            'category' => 'care',
            'spectrum_ref' => '6',
            'description' => 'Recording and monitoring the physical condition of objects',
            'required_fields' => ['condition_date', 'condition_checker', 'condition_rating'],
            'triggers' => ['conservation']
        ],
        'conservation' => [
            'name' => 'Conservation and Collections Care',
            'category' => 'care',
            'spectrum_ref' => '7',
            'description' => 'Planning and documenting conservation treatments',
            'required_fields' => ['conservator', 'treatment_type'],
            'triggers' => []
        ],
        'risk_management' => [
            'name' => 'Risk Management',
            'category' => 'care',
            'spectrum_ref' => '8',
            'description' => 'Identifying, assessing and managing risks to collections',
            'required_fields' => ['risk_type', 'risk_level', 'assessor'],
            'triggers' => ['insurance']
        ],
        'insurance' => [
            'name' => 'Insurance and Indemnity',
            'category' => 'legal',
            'spectrum_ref' => '9',
            'description' => 'Managing insurance and government indemnity',
            'required_fields' => ['insurer', 'policy_number', 'coverage_amount'],
            'triggers' => []
        ],
        'valuation' => [
            'name' => 'Valuation Control',
            'category' => 'legal',
            'spectrum_ref' => '10',
            'description' => 'Recording and maintaining object valuations',
            'required_fields' => ['valuation_date', 'valuer', 'value_amount', 'value_currency'],
            'triggers' => ['insurance']
        ],
        'audit' => [
            'name' => 'Audit',
            'category' => 'accountability',
            'spectrum_ref' => '11',
            'description' => 'Systematically checking objects and their records',
            'required_fields' => ['audit_date', 'auditor', 'audit_type'],
            'triggers' => []
        ],
        'rights_management' => [
            'name' => 'Rights Management',
            'category' => 'legal',
            'spectrum_ref' => '12',
            'description' => 'Managing intellectual property and other rights',
            'required_fields' => ['rights_type', 'rights_holder'],
            'triggers' => ['reproduction']
        ],
        'reproduction' => [
            'name' => 'Reproduction',
            'category' => 'use',
            'spectrum_ref' => '13',
            'description' => 'Managing requests for and supply of reproductions',
            'required_fields' => ['reproduction_type', 'requestor'],
            'triggers' => []
        ],
        'loans_in' => [
            'name' => 'Loans In',
            'category' => 'loans',
            'spectrum_ref' => '14',
            'description' => 'Managing objects borrowed from other organizations',
            'required_fields' => ['lender', 'loan_start', 'loan_end', 'loan_purpose'],
            'triggers' => ['object_condition', 'insurance', 'location_movement']
        ],
        'loans_out' => [
            'name' => 'Loans Out',
            'category' => 'loans',
            'spectrum_ref' => '15',
            'description' => 'Managing loans of objects to other organizations',
            'required_fields' => ['borrower', 'loan_start', 'loan_end', 'loan_purpose'],
            'triggers' => ['object_condition', 'insurance', 'location_movement']
        ],
        'loss_damage' => [
            'name' => 'Loss and Damage',
            'category' => 'incidents',
            'spectrum_ref' => '16',
            'description' => 'Recording and managing loss or damage to objects',
            'required_fields' => ['incident_date', 'incident_type', 'reporter'],
            'triggers' => ['insurance', 'conservation']
        ],
        'deaccession' => [
            'name' => 'Deaccession and Disposal',
            'category' => 'exit',
            'spectrum_ref' => '17',
            'description' => 'Recording and managing removal of objects from collections',
            'required_fields' => ['deaccession_date', 'deaccession_reason', 'authorizer'],
            'triggers' => ['object_exit']
        ],
        'documentation_planning' => [
            'name' => 'Documentation Planning',
            'category' => 'planning',
            'spectrum_ref' => '18',
            'description' => 'Planning documentation work and setting standards',
            'required_fields' => ['plan_date', 'planner'],
            'triggers' => ['cataloguing']
        ],
        'object_exit' => [
            'name' => 'Object Exit',
            'category' => 'exit',
            'spectrum_ref' => '19',
            'description' => 'Managing and documenting the departure of objects',
            'required_fields' => ['exit_date', 'exit_reason', 'exit_destination'],
            'triggers' => []
        ],
        'emergency_planning' => [
            'name' => 'Emergency Planning',
            'category' => 'planning',
            'spectrum_ref' => '20',
            'description' => 'Planning for emergencies affecting collections',
            'required_fields' => ['plan_type', 'review_date'],
            'triggers' => []
        ],
        'collections_review' => [
            'name' => 'Collections Review',
            'category' => 'planning',
            'spectrum_ref' => '21',
            'description' => 'Reviewing collections against policy',
            'required_fields' => ['review_date', 'reviewer', 'review_scope'],
            'triggers' => ['deaccession']
        ]
    ];

    // Status labels with colors
    public static $statusLabels = [
        self::STATUS_NOT_STARTED => ['label' => 'Not Started', 'color' => '#95a5a6', 'icon' => 'circle-o'],
        self::STATUS_IN_PROGRESS => ['label' => 'In Progress', 'color' => '#3498db', 'icon' => 'spinner'],
        self::STATUS_PENDING_REVIEW => ['label' => 'Pending Review', 'color' => '#f39c12', 'icon' => 'eye'],
        self::STATUS_PENDING_APPROVAL => ['label' => 'Pending Approval', 'color' => '#e67e22', 'icon' => 'clock-o'],
        self::STATUS_APPROVED => ['label' => 'Approved', 'color' => '#9b59b6', 'icon' => 'thumbs-up'],
        self::STATUS_COMPLETED => ['label' => 'Completed', 'color' => '#27ae60', 'icon' => 'check-circle'],
        self::STATUS_ON_HOLD => ['label' => 'On Hold', 'color' => '#7f8c8d', 'icon' => 'pause-circle'],
        self::STATUS_CANCELLED => ['label' => 'Cancelled', 'color' => '#c0392b', 'icon' => 'times-circle'],
        self::STATUS_OVERDUE => ['label' => 'Overdue', 'color' => '#e74c3c', 'icon' => 'exclamation-circle']
    ];

    // Event type labels
    public static $eventTypeLabels = [
        self::EVENT_CREATED => 'Procedure Created',
        self::EVENT_STATUS_CHANGE => 'Status Changed',
        self::EVENT_ASSIGNMENT => 'Assignment Changed',
        self::EVENT_DUE_DATE_SET => 'Due Date Set',
        self::EVENT_DUE_DATE_CHANGE => 'Due Date Changed',
        self::EVENT_NOTE_ADDED => 'Note Added',
        self::EVENT_LOCATION_CHANGE => 'Location Changed',
        self::EVENT_COMPLETED => 'Procedure Completed',
        self::EVENT_REOPENED => 'Procedure Reopened',
        self::EVENT_CANCELLED => 'Procedure Cancelled',
        self::EVENT_CONDITION_RECORDED => 'Condition Recorded',
        self::EVENT_VALUATION_RECORDED => 'Valuation Recorded',
        self::EVENT_INSURANCE_UPDATED => 'Insurance Updated',
        self::EVENT_MOVEMENT_RECORDED => 'Movement Recorded',
        self::EVENT_LOAN_INITIATED => 'Loan Initiated',
        self::EVENT_LOAN_RETURNED => 'Loan Returned',
        self::EVENT_ACQUISITION_RECORDED => 'Acquisition Recorded',
        self::EVENT_DEACCESSION_APPROVED => 'Deaccession Approved',
        self::EVENT_DISPOSAL_COMPLETED => 'Disposal Completed'
    ];

    protected $conn;

    public function __construct()
    {
        $this->conn = Propel::getConnection();
    }

    /**
     * Create a new procedure event
     */
    public function createEvent($objectId, $procedureId, $eventType, $data = [])
    {
        $now = date('Y-m-d H:i:s');
        $userId = sfContext::getInstance()->user->getAttribute('user_id');

        $sql = "INSERT INTO spectrum_event 
                (object_id, procedure_id, event_type, status_from, status_to, 
                 user_id, assigned_to_id, due_date, completed_date, location, 
                 notes, metadata, created_at, updated_at)
                VALUES 
                (:object_id, :procedure_id, :event_type, :status_from, :status_to,
                 :user_id, :assigned_to_id, :due_date, :completed_date, :location,
                 :notes, :metadata, :created_at, :updated_at)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':object_id' => $objectId,
            ':procedure_id' => $procedureId,
            ':event_type' => $eventType,
            ':status_from' => $data['status_from'] ?? null,
            ':status_to' => $data['status_to'] ?? null,
            ':user_id' => $userId,
            ':assigned_to_id' => $data['assigned_to_id'] ?? null,
            ':due_date' => $data['due_date'] ?? null,
            ':completed_date' => $data['completed_date'] ?? null,
            ':location' => $data['location'] ?? null,
            ':notes' => $data['notes'] ?? null,
            ':metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
            ':created_at' => $now,
            ':updated_at' => $now
        ]);

        return $this->conn->lastInsertId();
    }

    /**
     * Get events for an object
     */
    public function getObjectEvents($objectId, $procedureId = null, $limit = 100, $offset = 0)
    {
        $sql = "SELECT e.*, 
                       u.username as user_name,
                       a.username as assigned_to_name
                FROM spectrum_event e
                LEFT JOIN user u ON e.user_id = u.id
                LEFT JOIN user a ON e.assigned_to_id = a.id
                WHERE e.object_id = :object_id";
        
        $params = [':object_id' => $objectId];

        if ($procedureId) {
            $sql .= " AND e.procedure_id = :procedure_id";
            $params[':procedure_id'] = $procedureId;
        }

        $sql .= " ORDER BY e.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get current procedure status for an object
     */
    public function getProcedureStatus($objectId, $procedureId)
    {
        $sql = "SELECT status_to as current_status, 
                       assigned_to_id, due_date, location,
                       created_at as last_update
                FROM spectrum_event
                WHERE object_id = :object_id 
                  AND procedure_id = :procedure_id
                  AND status_to IS NOT NULL
                ORDER BY created_at DESC
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':object_id' => $objectId,
            ':procedure_id' => $procedureId
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return [
                'current_status' => self::STATUS_NOT_STARTED,
                'assigned_to_id' => null,
                'due_date' => null,
                'location' => null,
                'last_update' => null
            ];
        }

        // Check for overdue
        if ($result['due_date'] && 
            $result['current_status'] !== self::STATUS_COMPLETED &&
            $result['current_status'] !== self::STATUS_CANCELLED &&
            strtotime($result['due_date']) < time()) {
            $result['current_status'] = self::STATUS_OVERDUE;
        }

        return $result;
    }

    /**
     * Get all procedure statuses for an object
     */
    public function getAllProcedureStatuses($objectId)
    {
        $statuses = [];
        
        foreach (self::$procedures as $procedureId => $procedure) {
            $status = $this->getProcedureStatus($objectId, $procedureId);
            $statuses[$procedureId] = array_merge($procedure, $status);
        }

        return $statuses;
    }

    /**
     * Update procedure status
     */
    public function updateStatus($objectId, $procedureId, $newStatus, $notes = null, $metadata = [])
    {
        $currentStatus = $this->getProcedureStatus($objectId, $procedureId);

        $data = [
            'status_from' => $currentStatus['current_status'],
            'status_to' => $newStatus,
            'notes' => $notes,
            'metadata' => $metadata
        ];

        // Set completed date if completing
        if ($newStatus === self::STATUS_COMPLETED) {
            $data['completed_date'] = date('Y-m-d');
        }

        $eventType = self::EVENT_STATUS_CHANGE;
        if ($newStatus === self::STATUS_COMPLETED) {
            $eventType = self::EVENT_COMPLETED;
        } elseif ($currentStatus['current_status'] === self::STATUS_COMPLETED && 
                  $newStatus !== self::STATUS_COMPLETED) {
            $eventType = self::EVENT_REOPENED;
        } elseif ($newStatus === self::STATUS_CANCELLED) {
            $eventType = self::EVENT_CANCELLED;
        }

        $eventId = $this->createEvent($objectId, $procedureId, $eventType, $data);

        // Trigger downstream procedures if completed
        if ($newStatus === self::STATUS_COMPLETED) {
            $this->triggerDownstreamProcedures($objectId, $procedureId);
        }

        return $eventId;
    }

    /**
     * Assign procedure to user
     */
    public function assignProcedure($objectId, $procedureId, $assignedToId, $notes = null)
    {
        $data = [
            'assigned_to_id' => $assignedToId,
            'notes' => $notes
        ];

        return $this->createEvent($objectId, $procedureId, self::EVENT_ASSIGNMENT, $data);
    }

    /**
     * Set due date for procedure
     */
    public function setDueDate($objectId, $procedureId, $dueDate, $notes = null)
    {
        $currentStatus = $this->getProcedureStatus($objectId, $procedureId);
        
        $eventType = $currentStatus['due_date'] ? self::EVENT_DUE_DATE_CHANGE : self::EVENT_DUE_DATE_SET;

        $data = [
            'due_date' => $dueDate,
            'notes' => $notes,
            'metadata' => ['previous_due_date' => $currentStatus['due_date']]
        ];

        return $this->createEvent($objectId, $procedureId, $eventType, $data);
    }

    /**
     * Add note to procedure
     */
    public function addNote($objectId, $procedureId, $notes, $metadata = [])
    {
        return $this->createEvent($objectId, $procedureId, self::EVENT_NOTE_ADDED, [
            'notes' => $notes,
            'metadata' => $metadata
        ]);
    }

    /**
     * Record location change
     */
    public function recordLocationChange($objectId, $newLocation, $notes = null, $metadata = [])
    {
        $data = [
            'location' => $newLocation,
            'notes' => $notes,
            'metadata' => $metadata
        ];

        return $this->createEvent($objectId, 'location_movement', self::EVENT_LOCATION_CHANGE, $data);
    }

    /**
     * Record condition check
     */
    public function recordCondition($objectId, $conditionRating, $notes = null, $metadata = [])
    {
        $metadata['condition_rating'] = $conditionRating;

        return $this->createEvent($objectId, 'object_condition', self::EVENT_CONDITION_RECORDED, [
            'notes' => $notes,
            'metadata' => $metadata
        ]);
    }

    /**
     * Record valuation
     */
    public function recordValuation($objectId, $value, $currency, $valuerId = null, $notes = null)
    {
        return $this->createEvent($objectId, 'valuation', self::EVENT_VALUATION_RECORDED, [
            'notes' => $notes,
            'metadata' => [
                'value' => $value,
                'currency' => $currency,
                'valuer_id' => $valuerId
            ]
        ]);
    }

    /**
     * Trigger downstream procedures when one completes
     */
    protected function triggerDownstreamProcedures($objectId, $completedProcedureId)
    {
        $procedure = self::$procedures[$completedProcedureId] ?? null;
        
        if (!$procedure || empty($procedure['triggers'])) {
            return;
        }

        foreach ($procedure['triggers'] as $triggeredProcedureId) {
            $status = $this->getProcedureStatus($objectId, $triggeredProcedureId);
            
            // Only trigger if not started
            if ($status['current_status'] === self::STATUS_NOT_STARTED) {
                $this->createEvent($objectId, $triggeredProcedureId, self::EVENT_CREATED, [
                    'status_to' => self::STATUS_IN_PROGRESS,
                    'notes' => sprintf('Auto-triggered by completion of %s', 
                        self::$procedures[$completedProcedureId]['name']),
                    'metadata' => ['triggered_by' => $completedProcedureId]
                ]);
            }
        }
    }

    /**
     * Get events across all objects for reporting
     */
    public function getRecentEvents($repositoryId = null, $procedureId = null, $limit = 100)
    {
        $sql = "SELECT e.*, 
                       io.identifier as object_identifier,
                       io.slug as object_slug,
                       u.username as user_name
                FROM spectrum_event e
                JOIN information_object io ON e.object_id = io.id
                LEFT JOIN user u ON e.user_id = u.id";

        $where = [];
        $params = [];

        if ($repositoryId) {
            $where[] = "io.repository_id = :repository_id";
            $params[':repository_id'] = $repositoryId;
        }

        if ($procedureId) {
            $where[] = "e.procedure_id = :procedure_id";
            $params[':procedure_id'] = $procedureId;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY e.created_at DESC LIMIT :limit";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get overdue procedures
     */
    public function getOverdueProcedures($repositoryId = null)
    {
        $sql = "SELECT e.object_id, e.procedure_id, e.due_date, e.assigned_to_id,
                       io.identifier as object_identifier, io.slug as object_slug,
                       u.username as assigned_to_name,
                       DATEDIFF(CURDATE(), e.due_date) as days_overdue
                FROM spectrum_event e
                JOIN information_object io ON e.object_id = io.id
                LEFT JOIN user u ON e.assigned_to_id = u.id
                WHERE e.due_date < CURDATE()
                  AND e.id = (
                      SELECT MAX(e2.id) FROM spectrum_event e2 
                      WHERE e2.object_id = e.object_id 
                        AND e2.procedure_id = e.procedure_id
                        AND e2.status_to IS NOT NULL
                  )
                  AND e.status_to NOT IN (:completed, :cancelled)";

        $params = [
            ':completed' => self::STATUS_COMPLETED,
            ':cancelled' => self::STATUS_CANCELLED
        ];

        if ($repositoryId) {
            $sql .= " AND io.repository_id = :repository_id";
            $params[':repository_id'] = $repositoryId;
        }

        $sql .= " ORDER BY days_overdue DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get procedure statistics
     */
    public function getProcedureStatistics($repositoryId = null, $dateFrom = null, $dateTo = null)
    {
        $stats = [];

        foreach (self::$procedures as $procedureId => $procedure) {
            $sql = "SELECT 
                        COUNT(DISTINCT e.object_id) as total_objects,
                        SUM(CASE WHEN latest.status = :completed THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN latest.status = :in_progress THEN 1 ELSE 0 END) as in_progress,
                        SUM(CASE WHEN latest.status = :pending_review THEN 1 ELSE 0 END) as pending_review,
                        SUM(CASE WHEN latest.due_date < CURDATE() AND latest.status NOT IN (:completed2, :cancelled) THEN 1 ELSE 0 END) as overdue
                    FROM (
                        SELECT e.object_id, 
                               (SELECT status_to FROM spectrum_event e2 
                                WHERE e2.object_id = e.object_id 
                                  AND e2.procedure_id = :procedure_id
                                  AND e2.status_to IS NOT NULL
                                ORDER BY e2.created_at DESC LIMIT 1) as status,
                               (SELECT due_date FROM spectrum_event e3
                                WHERE e3.object_id = e.object_id
                                  AND e3.procedure_id = :procedure_id2
                                  AND e3.due_date IS NOT NULL
                                ORDER BY e3.created_at DESC LIMIT 1) as due_date
                        FROM spectrum_event e
                        JOIN information_object io ON e.object_id = io.id
                        WHERE e.procedure_id = :procedure_id3";

            $params = [
                ':procedure_id' => $procedureId,
                ':procedure_id2' => $procedureId,
                ':procedure_id3' => $procedureId,
                ':completed' => self::STATUS_COMPLETED,
                ':completed2' => self::STATUS_COMPLETED,
                ':in_progress' => self::STATUS_IN_PROGRESS,
                ':pending_review' => self::STATUS_PENDING_REVIEW,
                ':cancelled' => self::STATUS_CANCELLED
            ];

            if ($repositoryId) {
                $sql .= " AND io.repository_id = :repository_id";
                $params[':repository_id'] = $repositoryId;
            }

            if ($dateFrom) {
                $sql .= " AND e.created_at >= :date_from";
                $params[':date_from'] = $dateFrom;
            }

            if ($dateTo) {
                $sql .= " AND e.created_at <= :date_to";
                $params[':date_to'] = $dateTo;
            }

            $sql .= " GROUP BY e.object_id) as latest";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stats[$procedureId] = array_merge($procedure, [
                'total_objects' => (int)($result['total_objects'] ?? 0),
                'completed' => (int)($result['completed'] ?? 0),
                'in_progress' => (int)($result['in_progress'] ?? 0),
                'pending_review' => (int)($result['pending_review'] ?? 0),
                'overdue' => (int)($result['overdue'] ?? 0)
            ]);
        }

        return $stats;
    }

    /**
     * Calculate procedure progress for an object
     */
    public function calculateObjectProgress($objectId)
    {
        $statuses = $this->getAllProcedureStatuses($objectId);
        
        $total = count($statuses);
        $completed = 0;
        $inProgress = 0;
        $overdue = 0;

        foreach ($statuses as $status) {
            if ($status['current_status'] === self::STATUS_COMPLETED) {
                $completed++;
            } elseif (in_array($status['current_status'], [
                self::STATUS_IN_PROGRESS, 
                self::STATUS_PENDING_REVIEW,
                self::STATUS_PENDING_APPROVAL
            ])) {
                $inProgress++;
            } elseif ($status['current_status'] === self::STATUS_OVERDUE) {
                $overdue++;
            }
        }

        return [
            'total' => $total,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'overdue' => $overdue,
            'percentage' => $total > 0 ? round(($completed / $total) * 100) : 0
        ];
    }
}
