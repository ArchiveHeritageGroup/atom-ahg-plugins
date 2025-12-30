<?php

/**
 * Spectrum Procedure Template Service
 *
 * Manages configurable procedure templates per institution.
 * Templates define which procedures are active, their required fields,
 * workflow rules, and custom extensions.
 *
 * Templates are stored as JSON in the setting table or as files.
 *
 * @author  Johan Pieterse <johan@theahg.co.za>
 * @package ahgSpectrumPlugin
 */

use Illuminate\Database\Capsule\Manager as DB;

class ahgSpectrumTemplateService
{
    const SETTING_PREFIX = 'spectrum_template_';
    const DEFAULT_TEMPLATE = 'default';
    const SETTING_SCOPE = 'ahgSpectrumPlugin';

    // Template structure
    protected static $templateSchema = [
        'id' => 'string',
        'name' => 'string',
        'description' => 'string',
        'version' => 'string',
        'repository_id' => 'integer|null',
        'procedures' => [
            // procedure_id => configuration
        ],
        'workflow_rules' => [
            // rules for automatic transitions
        ],
        'custom_fields' => [
            // additional fields per procedure
        ],
        'notifications' => [
            // notification rules
        ],
        'metadata' => [
            // additional settings
        ],
    ];

    /**
     * Get procedure template for a repository
     */
    public function getTemplate($repositoryId = null)
    {
        // Try to get repository-specific template
        if ($repositoryId) {
            $template = $this->loadTemplate($repositoryId);
            if ($template) {
                return $template;
            }
        }

        // Fall back to default template
        return $this->getDefaultTemplate();
    }

    /**
     * Get the default template
     */
    public function getDefaultTemplate()
    {
        return [
            'id' => self::DEFAULT_TEMPLATE,
            'name' => 'Spectrum 5.0 Standard',
            'description' => 'Standard Spectrum 5.0 procedure configuration',
            'version' => '5.0',
            'repository_id' => null,
            'procedures' => $this->getDefaultProcedureConfig(),
            'workflow_rules' => $this->getDefaultWorkflowRules(),
            'custom_fields' => [],
            'notifications' => $this->getDefaultNotifications(),
            'metadata' => [
                'created_at' => '2024-01-01',
                'updated_at' => date('Y-m-d'),
                'author' => 'System',
            ],
        ];
    }

    /**
     * Get default procedure configuration
     */
    protected function getDefaultProcedureConfig()
    {
        $config = [];

        foreach (ahgSpectrumEventService::$procedures as $procId => $procedure) {
            $config[$procId] = [
                'enabled' => true,
                'required' => in_array($procId, ['object_entry', 'acquisition', 'cataloguing', 'location_movement']),
                'required_fields' => $procedure['required_fields'] ?? [],
                'optional_fields' => [],
                'approval_required' => in_array($procId, ['deaccession', 'disposal', 'loans_out']),
                'approval_roles' => ['administrator', 'editor'],
                'sla_days' => $this->getDefaultSLA($procId),
                'custom_statuses' => [],
                'form_sections' => $this->getDefaultFormSections($procId),
            ];
        }

        return $config;
    }

    /**
     * Get default SLA days for procedure
     */
    protected function getDefaultSLA($procedureId)
    {
        $slaMap = [
            'object_entry' => 2,
            'acquisition' => 30,
            'location_movement' => 1,
            'inventory' => 365,
            'cataloguing' => 30,
            'object_condition' => 7,
            'conservation' => 90,
            'risk_management' => 30,
            'insurance' => 365,
            'valuation' => 30,
            'audit' => 365,
            'rights_management' => 14,
            'reproduction' => 5,
            'loans_in' => 7,
            'loans_out' => 14,
            'loss_damage' => 1,
            'deaccession' => 90,
            'documentation_planning' => 30,
            'object_exit' => 2,
            'emergency_planning' => 365,
            'collections_review' => 365,
        ];

        return $slaMap[$procedureId] ?? 30;
    }

    /**
     * Get default form sections for procedure
     */
    protected function getDefaultFormSections($procedureId)
    {
        $sections = [
            'basic' => [
                'label' => 'Basic Information',
                'fields' => ['status', 'assigned_to', 'due_date', 'notes'],
            ],
        ];

        // Add procedure-specific sections
        switch ($procedureId) {
            case 'object_entry':
                $sections['entry'] = [
                    'label' => 'Entry Details',
                    'fields' => ['entry_date', 'entry_reason', 'depositor', 'entry_method', 'entry_number'],
                ];
                break;

            case 'acquisition':
                $sections['acquisition'] = [
                    'label' => 'Acquisition Details',
                    'fields' => ['acquisition_date', 'acquisition_method', 'source', 'funding_source', 'acquisition_value'],
                ];
                break;

            case 'location_movement':
                $sections['location'] = [
                    'label' => 'Location Details',
                    'fields' => ['current_location', 'previous_location', 'movement_date', 'movement_reason', 'handler'],
                ];
                break;

            case 'object_condition':
                $sections['condition'] = [
                    'label' => 'Condition Assessment',
                    'fields' => ['condition_date', 'condition_checker', 'condition_rating', 'condition_details', 'recommendations'],
                ];
                break;

            case 'conservation':
                $sections['conservation'] = [
                    'label' => 'Conservation Treatment',
                    'fields' => ['conservator', 'treatment_type', 'treatment_date', 'materials_used', 'treatment_report'],
                ];
                break;

            case 'valuation':
                $sections['valuation'] = [
                    'label' => 'Valuation Details',
                    'fields' => ['valuation_date', 'valuer', 'value_amount', 'value_currency', 'valuation_type', 'valuation_basis'],
                ];
                break;

            case 'insurance':
                $sections['insurance'] = [
                    'label' => 'Insurance Details',
                    'fields' => ['insurer', 'policy_number', 'coverage_amount', 'coverage_type', 'policy_start', 'policy_end'],
                ];
                break;

            case 'loans_in':
            case 'loans_out':
                $sections['loan'] = [
                    'label' => 'Loan Details',
                    'fields' => ['lender', 'borrower', 'loan_start', 'loan_end', 'loan_purpose', 'loan_conditions', 'loan_agreement'],
                ];
                break;

            case 'deaccession':
                $sections['deaccession'] = [
                    'label' => 'Deaccession Details',
                    'fields' => ['deaccession_date', 'deaccession_reason', 'authorizer', 'disposal_method', 'disposal_recipient'],
                ];
                break;

            case 'loss_damage':
                $sections['incident'] = [
                    'label' => 'Incident Details',
                    'fields' => ['incident_date', 'incident_type', 'reporter', 'incident_description', 'damage_extent', 'action_taken'],
                ];
                break;
        }

        return $sections;
    }

    /**
     * Get default workflow rules
     */
    protected function getDefaultWorkflowRules()
    {
        return [
            // Auto-trigger rules
            'triggers' => [
                [
                    'when' => 'object_entry.completed',
                    'then' => 'acquisition.start',
                    'condition' => 'entry_reason == "acquisition"',
                ],
                [
                    'when' => 'object_entry.completed',
                    'then' => 'loans_in.start',
                    'condition' => 'entry_reason == "loan"',
                ],
                [
                    'when' => 'acquisition.completed',
                    'then' => 'cataloguing.start',
                    'condition' => null,
                ],
                [
                    'when' => 'acquisition.completed',
                    'then' => 'location_movement.start',
                    'condition' => null,
                ],
                [
                    'when' => 'loans_in.completed',
                    'then' => 'object_condition.start',
                    'condition' => null,
                ],
                [
                    'when' => 'loans_out.started',
                    'then' => 'object_condition.start',
                    'condition' => null,
                ],
                [
                    'when' => 'deaccession.approved',
                    'then' => 'object_exit.start',
                    'condition' => null,
                ],
            ],

            // Approval rules
            'approvals' => [
                [
                    'procedure' => 'deaccession',
                    'required_status' => 'pending_approval',
                    'approvers' => ['administrator'],
                    'min_approvals' => 1,
                ],
                [
                    'procedure' => 'disposal',
                    'required_status' => 'pending_approval',
                    'approvers' => ['administrator'],
                    'min_approvals' => 2,
                ],
                [
                    'procedure' => 'loans_out',
                    'required_status' => 'pending_approval',
                    'approvers' => ['administrator', 'editor'],
                    'min_approvals' => 1,
                ],
            ],

            // SLA escalation rules
            'escalations' => [
                [
                    'condition' => 'days_overdue >= 7',
                    'action' => 'notify_supervisor',
                    'repeat_interval' => 7,
                ],
                [
                    'condition' => 'days_overdue >= 30',
                    'action' => 'notify_administrator',
                    'repeat_interval' => 14,
                ],
            ],
        ];
    }

    /**
     * Get default notification settings
     */
    protected function getDefaultNotifications()
    {
        return [
            'assignment' => [
                'enabled' => true,
                'recipients' => ['assignee'],
                'template' => 'You have been assigned to {procedure} for {object}',
            ],
            'due_date_reminder' => [
                'enabled' => true,
                'days_before' => [7, 3, 1],
                'recipients' => ['assignee'],
                'template' => '{procedure} for {object} is due in {days} days',
            ],
            'overdue' => [
                'enabled' => true,
                'recipients' => ['assignee', 'supervisor'],
                'template' => '{procedure} for {object} is {days} days overdue',
            ],
            'completion' => [
                'enabled' => true,
                'recipients' => ['creator'],
                'template' => '{procedure} for {object} has been completed',
            ],
            'approval_required' => [
                'enabled' => true,
                'recipients' => ['approvers'],
                'template' => '{procedure} for {object} requires your approval',
            ],
        ];
    }

    /**
     * Save template for repository
     */
    public function saveTemplate($repositoryId, $template)
    {
        $template['repository_id'] = $repositoryId;
        $template['metadata']['updated_at'] = date('Y-m-d H:i:s');

        $settingName = self::SETTING_PREFIX . $repositoryId;
        $jsonValue = json_encode($template);

        // Check if setting exists
        $existing = DB::table('setting')
            ->where('name', $settingName)
            ->where('scope', self::SETTING_SCOPE)
            ->first();

        if ($existing) {
            // Update existing setting
            DB::table('setting_i18n')
                ->updateOrInsert(
                    ['id' => $existing->id, 'culture' => 'en'],
                    ['value' => $jsonValue]
                );
        } else {
            // Create new setting
            $settingId = DB::table('setting')->insertGetId([
                'name' => $settingName,
                'scope' => self::SETTING_SCOPE,
            ]);

            DB::table('setting_i18n')->insert([
                'id' => $settingId,
                'culture' => 'en',
                'value' => $jsonValue,
            ]);
        }

        return true;
    }

    /**
     * Load template for repository
     */
    public function loadTemplate($repositoryId)
    {
        $settingName = self::SETTING_PREFIX . $repositoryId;

        $setting = DB::table('setting as s')
            ->leftJoin('setting_i18n as si', 's.id', '=', 'si.id')
            ->where('s.name', $settingName)
            ->where('s.scope', self::SETTING_SCOPE)
            ->select('si.value')
            ->first();

        if ($setting && $setting->value) {
            return json_decode($setting->value, true);
        }

        return null;
    }

    /**
     * Delete template for repository
     */
    public function deleteTemplate($repositoryId)
    {
        $settingName = self::SETTING_PREFIX . $repositoryId;

        $setting = DB::table('setting')
            ->where('name', $settingName)
            ->where('scope', self::SETTING_SCOPE)
            ->first();

        if ($setting) {
            // Delete i18n first
            DB::table('setting_i18n')
                ->where('id', $setting->id)
                ->delete();

            // Delete setting
            DB::table('setting')
                ->where('id', $setting->id)
                ->delete();

            return true;
        }

        return false;
    }

    /**
     * Get list of all custom templates
     */
    public function getAllTemplates()
    {
        $templates = [];

        $settings = DB::table('setting as s')
            ->leftJoin('setting_i18n as si', 's.id', '=', 'si.id')
            ->where('s.scope', self::SETTING_SCOPE)
            ->where('s.name', 'like', self::SETTING_PREFIX . '%')
            ->select('si.value')
            ->get();

        foreach ($settings as $setting) {
            if ($setting->value) {
                $template = json_decode($setting->value, true);
                if ($template) {
                    $templates[] = $template;
                }
            }
        }

        return $templates;
    }

    /**
     * Clone template for another repository
     */
    public function cloneTemplate($sourceRepositoryId, $targetRepositoryId)
    {
        $sourceTemplate = $this->loadTemplate($sourceRepositoryId);

        if (!$sourceTemplate) {
            $sourceTemplate = $this->getDefaultTemplate();
        }

        $newTemplate = $sourceTemplate;
        $newTemplate['id'] = 'template_' . $targetRepositoryId;
        $newTemplate['repository_id'] = $targetRepositoryId;
        $newTemplate['metadata']['cloned_from'] = $sourceRepositoryId;
        $newTemplate['metadata']['created_at'] = date('Y-m-d H:i:s');

        return $this->saveTemplate($targetRepositoryId, $newTemplate);
    }

    /**
     * Validate template structure
     */
    public function validateTemplate($template)
    {
        $errors = [];

        if (empty($template['id'])) {
            $errors[] = 'Template ID is required';
        }

        if (empty($template['name'])) {
            $errors[] = 'Template name is required';
        }

        if (!isset($template['procedures']) || !is_array($template['procedures'])) {
            $errors[] = 'Procedures configuration is required';
        } else {
            foreach ($template['procedures'] as $procId => $config) {
                if (!isset(ahgSpectrumEventService::$procedures[$procId])) {
                    $errors[] = sprintf('Unknown procedure: %s', $procId);
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get effective procedure configuration
     */
    public function getProcedureConfig($repositoryId, $procedureId)
    {
        $template = $this->getTemplate($repositoryId);

        $baseConfig = ahgSpectrumEventService::$procedures[$procedureId] ?? null;
        if (!$baseConfig) {
            return null;
        }

        $templateConfig = $template['procedures'][$procedureId] ?? [];

        return array_merge($baseConfig, $templateConfig);
    }

    /**
     * Check if procedure is enabled for repository
     */
    public function isProcedureEnabled($repositoryId, $procedureId)
    {
        $config = $this->getProcedureConfig($repositoryId, $procedureId);
        return $config && ($config['enabled'] ?? true);
    }

    /**
     * Check if approval is required for procedure
     */
    public function isApprovalRequired($repositoryId, $procedureId)
    {
        $config = $this->getProcedureConfig($repositoryId, $procedureId);
        return $config && ($config['approval_required'] ?? false);
    }

    /**
     * Get enabled procedures for repository
     */
    public function getEnabledProcedures($repositoryId)
    {
        $template = $this->getTemplate($repositoryId);
        $enabled = [];

        foreach (ahgSpectrumEventService::$procedures as $procId => $procedure) {
            $config = $template['procedures'][$procId] ?? ['enabled' => true];
            if ($config['enabled']) {
                $enabled[$procId] = array_merge($procedure, $config);
            }
        }

        return $enabled;
    }

    /**
     * Export template as JSON
     */
    public function exportTemplate($repositoryId)
    {
        $template = $this->loadTemplate($repositoryId) ?? $this->getDefaultTemplate();
        return json_encode($template, JSON_PRETTY_PRINT);
    }

    /**
     * Import template from JSON
     */
    public function importTemplate($repositoryId, $json)
    {
        $template = json_decode($json, true);

        if (!$template) {
            throw new Exception('Invalid JSON');
        }

        $validation = $this->validateTemplate($template);
        if (!$validation['valid']) {
            throw new Exception('Invalid template: ' . implode(', ', $validation['errors']));
        }

        return $this->saveTemplate($repositoryId, $template);
    }
}