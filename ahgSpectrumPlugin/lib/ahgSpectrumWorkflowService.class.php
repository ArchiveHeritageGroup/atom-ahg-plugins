<?php

/**
 * Spectrum Workflow Integration Service
 *
 * Connects CCO cataloguing with Spectrum 5.0 procedures.
 * Provides shared status tracking, procedure timelines, and workflow triggers.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgMuseumPlugin
 */

use Illuminate\Database\Capsule\Manager as DB;

class ahgSpectrumWorkflowService
{
    // Spectrum 5.0 Primary Procedures
    const PROC_OBJECT_ENTRY = 'object_entry';
    const PROC_ACQUISITION = 'acquisition';
    const PROC_LOCATION = 'location_movement';
    const PROC_INVENTORY = 'inventory_control';
    const PROC_CATALOGUING = 'cataloguing';
    const PROC_CONDITION = 'condition_checking';
    const PROC_CONSERVATION = 'conservation';
    const PROC_RISK = 'risk_management';
    const PROC_INSURANCE = 'insurance';
    const PROC_VALUATION = 'valuation';
    const PROC_AUDIT = 'audit';
    const PROC_RIGHTS = 'rights_management';
    const PROC_REPRODUCTION = 'reproduction';
    const PROC_LOAN_IN = 'loans_in';
    const PROC_LOAN_OUT = 'loans_out';
    const PROC_LOSS = 'loss_damage';
    const PROC_DEACCESSION = 'deaccession';
    const PROC_DISPOSAL = 'disposal';
    const PROC_DOCUMENTATION = 'documentation_planning';
    const PROC_EXIT = 'object_exit';
    const PROC_RETROSPECTIVE = 'retrospective_documentation';

    // Procedure statuses
    const STATUS_NOT_STARTED = 'not_started';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_PENDING_REVIEW = 'pending_review';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ON_HOLD = 'on_hold';
    const STATUS_OVERDUE = 'overdue';

    // Property name for storage
    const PROPERTY_NAME = 'spectrumProcedures';

    // Status colors for UI
    public static $statusColors = [
        self::STATUS_NOT_STARTED => '#95a5a6',
        self::STATUS_IN_PROGRESS => '#3498db',
        self::STATUS_PENDING_REVIEW => '#f39c12',
        self::STATUS_COMPLETED => '#27ae60',
        self::STATUS_ON_HOLD => '#9b59b6',
        self::STATUS_OVERDUE => '#e74c3c',
    ];

    /**
     * Get all procedure definitions
     */
    public static function getProcedures(): array
    {
        return [
            self::PROC_OBJECT_ENTRY => [
                'label' => 'Object Entry',
                'description' => 'Recording information about objects entering the museum temporarily or for acquisition consideration.',
                'category' => 'pre-entry',
                'icon' => 'fa-sign-in',
                'requiredFields' => ['object_number', 'entry_date', 'entry_reason', 'depositor'],
                'triggers' => [self::PROC_ACQUISITION, self::PROC_LOCATION],
                'spectrumRef' => 'Spectrum 5.0 - Object Entry',
            ],
            self::PROC_ACQUISITION => [
                'label' => 'Acquisition',
                'description' => 'Formally acquiring objects for the permanent collection.',
                'category' => 'acquisition',
                'icon' => 'fa-plus-circle',
                'requiredFields' => ['accession_number', 'acquisition_date', 'acquisition_method', 'source'],
                'triggers' => [self::PROC_CATALOGUING, self::PROC_LOCATION, self::PROC_VALUATION],
                'spectrumRef' => 'Spectrum 5.0 - Acquisition',
            ],
            self::PROC_LOCATION => [
                'label' => 'Location & Movement',
                'description' => 'Tracking object locations and movements within and outside the museum.',
                'category' => 'location',
                'icon' => 'fa-map-marker',
                'requiredFields' => ['current_location', 'movement_date', 'handler'],
                'triggers' => [self::PROC_CONDITION],
                'spectrumRef' => 'Spectrum 5.0 - Location and Movement Control',
            ],
            self::PROC_INVENTORY => [
                'label' => 'Inventory Control',
                'description' => 'Verifying and reconciling object locations and records.',
                'category' => 'control',
                'icon' => 'fa-list-alt',
                'requiredFields' => ['inventory_date', 'location_verified', 'inventoried_by'],
                'triggers' => [],
                'spectrumRef' => 'Spectrum 5.0 - Inventory Control',
            ],
            self::PROC_CATALOGUING => [
                'label' => 'Cataloguing',
                'description' => 'Creating and maintaining catalogue records.',
                'category' => 'documentation',
                'icon' => 'fa-book',
                'requiredFields' => ['title', 'creator', 'date', 'medium', 'dimensions'],
                'triggers' => [self::PROC_RIGHTS],
                'spectrumRef' => 'Spectrum 5.0 - Cataloguing',
                'ccoIntegration' => true,
            ],
            self::PROC_CONDITION => [
                'label' => 'Condition Checking',
                'description' => 'Recording and monitoring object condition.',
                'category' => 'care',
                'icon' => 'fa-heartbeat',
                'requiredFields' => ['condition_date', 'condition_summary', 'examiner'],
                'triggers' => [self::PROC_CONSERVATION],
                'spectrumRef' => 'Spectrum 5.0 - Condition Checking and Technical Assessment',
            ],
            self::PROC_CONSERVATION => [
                'label' => 'Conservation',
                'description' => 'Planning and documenting conservation treatments.',
                'category' => 'care',
                'icon' => 'fa-medkit',
                'requiredFields' => ['treatment_proposal', 'conservator', 'treatment_date'],
                'triggers' => [self::PROC_CONDITION],
                'spectrumRef' => 'Spectrum 5.0 - Conservation and Collections Care',
            ],
            self::PROC_VALUATION => [
                'label' => 'Valuation',
                'description' => 'Recording object valuations for insurance and reporting.',
                'category' => 'financial',
                'icon' => 'fa-dollar-sign',
                'requiredFields' => ['valuation_amount', 'valuation_date', 'valuer', 'valuation_type'],
                'triggers' => [self::PROC_INSURANCE],
                'spectrumRef' => 'Spectrum 5.0 - Valuation Control',
                'grapIntegration' => true,
            ],
            self::PROC_INSURANCE => [
                'label' => 'Insurance',
                'description' => 'Managing insurance for collections.',
                'category' => 'financial',
                'icon' => 'fa-shield-alt',
                'requiredFields' => ['insurance_value', 'policy_number', 'coverage_dates'],
                'triggers' => [],
                'spectrumRef' => 'Spectrum 5.0 - Insurance and Indemnity Management',
            ],
            self::PROC_LOAN_IN => [
                'label' => 'Loans In',
                'description' => 'Borrowing objects from other institutions or individuals.',
                'category' => 'loans',
                'icon' => 'fa-arrow-circle-down',
                'requiredFields' => ['lender', 'loan_dates', 'loan_purpose', 'insurance_value'],
                'triggers' => [self::PROC_LOCATION, self::PROC_CONDITION],
                'spectrumRef' => 'Spectrum 5.0 - Loans In',
            ],
            self::PROC_LOAN_OUT => [
                'label' => 'Loans Out',
                'description' => 'Lending objects to other institutions.',
                'category' => 'loans',
                'icon' => 'fa-arrow-circle-up',
                'requiredFields' => ['borrower', 'loan_dates', 'loan_purpose', 'facility_report'],
                'triggers' => [self::PROC_LOCATION, self::PROC_CONDITION, self::PROC_INSURANCE],
                'spectrumRef' => 'Spectrum 5.0 - Loans Out',
            ],
            self::PROC_LOSS => [
                'label' => 'Loss & Damage',
                'description' => 'Recording and responding to loss or damage.',
                'category' => 'risk',
                'icon' => 'fa-exclamation-triangle',
                'requiredFields' => ['incident_date', 'description', 'reported_by'],
                'triggers' => [self::PROC_INSURANCE, self::PROC_CONSERVATION],
                'spectrumRef' => 'Spectrum 5.0 - Loss and Damage',
            ],
            self::PROC_DEACCESSION => [
                'label' => 'Deaccession',
                'description' => 'Formally removing objects from the collection.',
                'category' => 'disposal',
                'icon' => 'fa-minus-circle',
                'requiredFields' => ['deaccession_date', 'reason', 'authorization'],
                'triggers' => [self::PROC_DISPOSAL],
                'spectrumRef' => 'Spectrum 5.0 - Deaccession and Disposal',
            ],
            self::PROC_DISPOSAL => [
                'label' => 'Disposal',
                'description' => 'Physically disposing of deaccessioned objects.',
                'category' => 'disposal',
                'icon' => 'fa-trash',
                'requiredFields' => ['disposal_date', 'disposal_method', 'recipient'],
                'triggers' => [self::PROC_EXIT],
                'spectrumRef' => 'Spectrum 5.0 - Deaccession and Disposal',
            ],
            self::PROC_EXIT => [
                'label' => 'Object Exit',
                'description' => 'Recording objects leaving the museum.',
                'category' => 'exit',
                'icon' => 'fa-sign-out',
                'requiredFields' => ['exit_date', 'exit_reason', 'recipient', 'authorization'],
                'triggers' => [],
                'spectrumRef' => 'Spectrum 5.0 - Object Exit',
            ],
        ];
    }

    /**
     * Get procedure status for an object
     */
    public static function getObjectProcedureStatus($objectId): array
    {
        $statuses = [];
        $procedures = self::getProcedures();

        // Load from database
        $property = self::getProperty($objectId, self::PROPERTY_NAME);
        $stored = $property ? (json_decode($property->value, true) ?: []) : [];

        foreach ($procedures as $procId => $procDef) {
            $statuses[$procId] = [
                'procedure' => $procDef,
                'status' => $stored[$procId]['status'] ?? self::STATUS_NOT_STARTED,
                'lastUpdate' => $stored[$procId]['lastUpdate'] ?? null,
                'completedDate' => $stored[$procId]['completedDate'] ?? null,
                'notes' => $stored[$procId]['notes'] ?? null,
                'assignedTo' => $stored[$procId]['assignedTo'] ?? null,
                'dueDate' => $stored[$procId]['dueDate'] ?? null,
                'events' => $stored[$procId]['events'] ?? [],
            ];

            // Check if overdue
            if (
                $statuses[$procId]['dueDate'] &&
                $statuses[$procId]['status'] !== self::STATUS_COMPLETED &&
                strtotime($statuses[$procId]['dueDate']) < time()
            ) {
                $statuses[$procId]['status'] = self::STATUS_OVERDUE;
            }
        }

        return $statuses;
    }

    /**
     * Update procedure status
     */
    public static function updateProcedureStatus($objectId, $procedureId, $status, $notes = null, $userId = null): array
    {
        $property = self::getProperty($objectId, self::PROPERTY_NAME);
        $data = $property ? (json_decode($property->value, true) ?: []) : [];

        // Create event
        $event = [
            'timestamp' => date('c'),
            'userId' => $userId,
            'oldStatus' => $data[$procedureId]['status'] ?? null,
            'newStatus' => $status,
            'notes' => $notes,
        ];

        if (!isset($data[$procedureId])) {
            $data[$procedureId] = ['events' => []];
        }

        $data[$procedureId]['status'] = $status;
        $data[$procedureId]['lastUpdate'] = date('c');
        $data[$procedureId]['notes'] = $notes;
        $data[$procedureId]['events'][] = $event;

        if ($status === self::STATUS_COMPLETED) {
            $data[$procedureId]['completedDate'] = date('c');
        }

        // Save property
        self::setProperty($objectId, self::PROPERTY_NAME, json_encode($data));

        // Trigger downstream procedures
        if ($status === self::STATUS_COMPLETED) {
            self::triggerDownstreamProcedures($objectId, $procedureId);
        }

        return $data[$procedureId];
    }

    /**
     * Trigger downstream procedures when one completes
     */
    protected static function triggerDownstreamProcedures($objectId, $completedProcedure): void
    {
        $procedures = self::getProcedures();

        if (!isset($procedures[$completedProcedure]['triggers'])) {
            return;
        }

        foreach ($procedures[$completedProcedure]['triggers'] as $triggeredProc) {
            $current = self::getObjectProcedureStatus($objectId);

            // Only trigger if not already started
            if ($current[$triggeredProc]['status'] === self::STATUS_NOT_STARTED) {
                self::updateProcedureStatus(
                    $objectId,
                    $triggeredProc,
                    self::STATUS_IN_PROGRESS,
                    sprintf('Automatically triggered by completion of %s', $procedures[$completedProcedure]['label'])
                );
            }
        }
    }

    /**
     * Get timeline of all procedure events for an object
     */
    public static function getObjectTimeline($objectId): array
    {
        $statuses = self::getObjectProcedureStatus($objectId);
        $timeline = [];

        foreach ($statuses as $procId => $procStatus) {
            foreach ($procStatus['events'] as $event) {
                $timeline[] = [
                    'procedure' => $procId,
                    'procedureLabel' => $procStatus['procedure']['label'],
                    'timestamp' => $event['timestamp'],
                    'oldStatus' => $event['oldStatus'],
                    'newStatus' => $event['newStatus'],
                    'notes' => $event['notes'],
                    'userId' => $event['userId'],
                ];
            }
        }

        // Sort by timestamp descending
        usort($timeline, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return $timeline;
    }

    /**
     * Calculate overall workflow progress
     */
    public static function calculateWorkflowProgress($objectId): array
    {
        $statuses = self::getObjectProcedureStatus($objectId);

        $total = count($statuses);
        $completed = 0;
        $inProgress = 0;
        $overdue = 0;

        foreach ($statuses as $procStatus) {
            switch ($procStatus['status']) {
                case self::STATUS_COMPLETED:
                    $completed++;
                    break;
                case self::STATUS_IN_PROGRESS:
                case self::STATUS_PENDING_REVIEW:
                    $inProgress++;
                    break;
                case self::STATUS_OVERDUE:
                    $overdue++;
                    break;
            }
        }

        return [
            'total' => $total,
            'completed' => $completed,
            'inProgress' => $inProgress,
            'overdue' => $overdue,
            'notStarted' => $total - $completed - $inProgress - $overdue,
            'percentComplete' => $total > 0 ? round(($completed / $total) * 100) : 0,
        ];
    }

    /**
     * Link CCO cataloguing to Spectrum cataloguing procedure
     */
    public static function syncCataloguing($objectId, $ccoCompleteness): void
    {
        // When CCO cataloguing reaches certain thresholds, update Spectrum status
        if ($ccoCompleteness >= 100) {
            self::updateProcedureStatus(
                $objectId,
                self::PROC_CATALOGUING,
                self::STATUS_COMPLETED,
                'CCO cataloguing complete (100%)'
            );
        } elseif ($ccoCompleteness >= 80) {
            self::updateProcedureStatus(
                $objectId,
                self::PROC_CATALOGUING,
                self::STATUS_PENDING_REVIEW,
                sprintf('CCO cataloguing at %d%% - ready for review', $ccoCompleteness)
            );
        } elseif ($ccoCompleteness >= 30) {
            $current = self::getObjectProcedureStatus($objectId);
            if ($current[self::PROC_CATALOGUING]['status'] === self::STATUS_NOT_STARTED) {
                self::updateProcedureStatus(
                    $objectId,
                    self::PROC_CATALOGUING,
                    self::STATUS_IN_PROGRESS,
                    sprintf('CCO cataloguing started (%d%%)', $ccoCompleteness)
                );
            }
        }
    }

    /**
     * Get objects with specific procedure status
     */
    public static function getObjectsByProcedureStatus($procedureId, $status, $limit = 50): array
    {
        // Query properties table for objects with spectrum procedures
        $properties = DB::table('property as p')
            ->leftJoin('property_i18n as pi', function ($join) {
                $join->on('p.id', '=', 'pi.id')
                    ->where('pi.culture', '=', 'en');
            })
            ->where('p.name', self::PROPERTY_NAME)
            ->whereNotNull('pi.value')
            ->select('p.object_id', 'pi.value')
            ->limit($limit * 2) // Get more to filter
            ->get();

        $results = [];

        foreach ($properties as $prop) {
            $data = json_decode($prop->value, true);
            if ($data && isset($data[$procedureId]) && $data[$procedureId]['status'] === $status) {
                $results[] = [
                    'object_id' => $prop->object_id,
                    'status' => $data[$procedureId]['status'],
                    'lastUpdate' => $data[$procedureId]['lastUpdate'] ?? null,
                    'dueDate' => $data[$procedureId]['dueDate'] ?? null,
                ];
            }

            if (count($results) >= $limit) {
                break;
            }
        }

        return $results;
    }

    /**
     * Get pending procedures for a user
     */
    public static function getPendingProceduresForUser($userId): array
    {
        $pending = [];

        // Query properties with spectrum procedures
        $properties = DB::table('property as p')
            ->leftJoin('property_i18n as pi', function ($join) {
                $join->on('p.id', '=', 'pi.id')
                    ->where('pi.culture', '=', 'en');
            })
            ->where('p.name', self::PROPERTY_NAME)
            ->whereNotNull('pi.value')
            ->select('p.object_id', 'pi.value')
            ->get();

        foreach ($properties as $prop) {
            $data = json_decode($prop->value, true);
            if (!$data) {
                continue;
            }

            foreach ($data as $procId => $procData) {
                if (
                    isset($procData['assignedTo']) &&
                    $procData['assignedTo'] == $userId &&
                    in_array($procData['status'] ?? '', [self::STATUS_IN_PROGRESS, self::STATUS_PENDING_REVIEW, self::STATUS_OVERDUE])
                ) {
                    $pending[] = [
                        'object_id' => $prop->object_id,
                        'procedure_id' => $procId,
                        'status' => $procData['status'],
                        'dueDate' => $procData['dueDate'] ?? null,
                        'notes' => $procData['notes'] ?? null,
                    ];
                }
            }
        }

        // Sort by due date
        usort($pending, function ($a, $b) {
            if (!$a['dueDate']) {
                return 1;
            }
            if (!$b['dueDate']) {
                return -1;
            }
            return strtotime($a['dueDate']) - strtotime($b['dueDate']);
        });

        return $pending;
    }

    /**
     * Check if procedure can be started (dependencies met)
     */
    public static function canStartProcedure($objectId, $procedureId): array
    {
        $procedures = self::getProcedures();
        $statuses = self::getObjectProcedureStatus($objectId);

        // Find procedures that trigger this one
        foreach ($procedures as $procId => $procDef) {
            if (in_array($procedureId, $procDef['triggers'] ?? [])) {
                // This procedure should be triggered by $procId
                // Check if $procId is completed
                if ($statuses[$procId]['status'] !== self::STATUS_COMPLETED) {
                    return [
                        'canStart' => false,
                        'reason' => sprintf('Waiting for %s to complete', $procDef['label']),
                        'blockedBy' => $procId,
                    ];
                }
            }
        }

        return ['canStart' => true];
    }

    /**
     * Assign procedure to user
     */
    public static function assignProcedure($objectId, $procedureId, $userId, $dueDate = null): bool
    {
        $property = self::getProperty($objectId, self::PROPERTY_NAME);
        $data = $property ? (json_decode($property->value, true) ?: []) : [];

        if (!isset($data[$procedureId])) {
            $data[$procedureId] = ['events' => []];
        }

        $data[$procedureId]['assignedTo'] = $userId;
        $data[$procedureId]['dueDate'] = $dueDate;
        $data[$procedureId]['lastUpdate'] = date('c');

        // Add event
        $data[$procedureId]['events'][] = [
            'timestamp' => date('c'),
            'userId' => null,
            'oldStatus' => null,
            'newStatus' => null,
            'notes' => sprintf('Assigned to user %d%s', $userId, $dueDate ? ", due {$dueDate}" : ''),
        ];

        self::setProperty($objectId, self::PROPERTY_NAME, json_encode($data));

        return true;
    }

    /**
     * Get overdue procedures
     */
    public static function getOverdueProcedures($repositoryId = null, $limit = 100): array
    {
        $overdue = [];
        $today = date('Y-m-d');

        $query = DB::table('property as p')
            ->leftJoin('property_i18n as pi', function ($join) {
                $join->on('p.id', '=', 'pi.id')
                    ->where('pi.culture', '=', 'en');
            })
            ->where('p.name', self::PROPERTY_NAME)
            ->whereNotNull('pi.value')
            ->select('p.object_id', 'pi.value');

        if ($repositoryId) {
            $query->join('information_object as io', 'p.object_id', '=', 'io.id')
                ->where('io.repository_id', $repositoryId);
        }

        $properties = $query->limit($limit * 5)->get();

        foreach ($properties as $prop) {
            $data = json_decode($prop->value, true);
            if (!$data) {
                continue;
            }

            foreach ($data as $procId => $procData) {
                if (
                    isset($procData['dueDate']) &&
                    $procData['dueDate'] < $today &&
                    isset($procData['status']) &&
                    $procData['status'] !== self::STATUS_COMPLETED
                ) {
                    $overdue[] = [
                        'object_id' => $prop->object_id,
                        'procedure_id' => $procId,
                        'due_date' => $procData['dueDate'],
                        'days_overdue' => (int) ((time() - strtotime($procData['dueDate'])) / 86400),
                        'assigned_to' => $procData['assignedTo'] ?? null,
                        'status' => $procData['status'],
                    ];
                }
            }

            if (count($overdue) >= $limit) {
                break;
            }
        }

        // Sort by days overdue descending
        usort($overdue, function ($a, $b) {
            return $b['days_overdue'] - $a['days_overdue'];
        });

        return array_slice($overdue, 0, $limit);
    }

    /**
     * Check if a state is a final state (no outgoing transitions)
     *
     * @param string $procedureType Procedure type
     * @param string $state State to check
     * @return bool True if state is final (no outgoing transitions)
     */
    public static function isFinalState(string $procedureType, string $state): bool
    {
        $config = DB::table('spectrum_workflow_config')
            ->where('procedure_type', $procedureType)
            ->where('is_active', 1)
            ->first();

        if (!$config) {
            // Fallback: treat 'completed' and 'resolved' as final if no config
            return in_array($state, ['completed', 'resolved']);
        }

        $configData = json_decode($config->config_json, true);
        $transitions = $configData['transitions'] ?? [];

        // Check if this state appears in any "from" array
        foreach ($transitions as $transition) {
            $fromStates = $transition['from'] ?? [];
            if (in_array($state, $fromStates)) {
                // State has outgoing transitions, not final
                return false;
            }
        }

        // No outgoing transitions found, this is a final state
        return true;
    }

    /**
     * Get all final states for a procedure type
     *
     * @param string $procedureType Procedure type
     * @return array List of final state names
     */
    public static function getFinalStates(string $procedureType): array
    {
        $config = DB::table('spectrum_workflow_config')
            ->where('procedure_type', $procedureType)
            ->where('is_active', 1)
            ->first();

        if (!$config) {
            return ['completed', 'resolved'];
        }

        $configData = json_decode($config->config_json, true);
        $states = $configData['states'] ?? [];
        $transitions = $configData['transitions'] ?? [];

        // Collect all states that appear in "from" arrays
        $statesWithOutgoing = [];
        foreach ($transitions as $transition) {
            $fromStates = $transition['from'] ?? [];
            $statesWithOutgoing = array_merge($statesWithOutgoing, $fromStates);
        }
        $statesWithOutgoing = array_unique($statesWithOutgoing);

        // Final states are those without outgoing transitions
        return array_values(array_diff($states, $statesWithOutgoing));
    }

    /**
     * Get property from database
     */
    protected static function getProperty(int $objectId, string $name): ?object
    {
        $property = DB::table('property as p')
            ->leftJoin('property_i18n as pi', function ($join) {
                $join->on('p.id', '=', 'pi.id')
                    ->where('pi.culture', '=', 'en');
            })
            ->where('p.object_id', $objectId)
            ->where('p.name', $name)
            ->select('p.id', 'p.object_id', 'p.name', 'pi.value')
            ->first();

        return $property;
    }

    /**
     * Set property in database
     */
    protected static function setProperty(int $objectId, string $name, string $value): void
    {
        $existing = DB::table('property')
            ->where('object_id', $objectId)
            ->where('name', $name)
            ->first();

        if ($existing) {
            // Update existing
            DB::table('property_i18n')
                ->updateOrInsert(
                    ['id' => $existing->id, 'culture' => 'en'],
                    ['value' => $value]
                );
        } else {
            // Create new property
            $propertyId = DB::table('property')->insertGetId([
                'object_id' => $objectId,
                'name' => $name,
            ]);

            DB::table('property_i18n')->insert([
                'id' => $propertyId,
                'culture' => 'en',
                'value' => $value,
            ]);
        }
    }
}