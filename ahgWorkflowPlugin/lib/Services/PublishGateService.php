<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Publish Gate Service
 *
 * Evaluates configurable publish-readiness rules against archival records.
 * Rules can be scoped by entity type, level of description, material type, and repository.
 * Integrates with WorkflowEventService for audit trail and IIIF validation when available.
 *
 * @version 1.0.0
 */
class PublishGateService
{
    /**
     * Evaluate all applicable gate rules for an object.
     *
     * @param int      $objectId Object to evaluate
     * @param int|null $userId   User triggering evaluation
     * @return array   [{rule_id, rule_name, rule_type, status, severity, error_message, details}]
     */
    public function evaluate(int $objectId, ?int $userId = null): array
    {
        $object = $this->getObjectContext($objectId);
        if (!$object) {
            return [];
        }

        $rules = $this->getApplicableRules(
            $object->entity_type ?? 'information_object',
            $object->level_of_description_id ?? null,
            $object->repository_id ?? null
        );

        $results = [];
        $culture = \AtomExtensions\Helpers\CultureHelper::getCulture();

        foreach ($rules as $rule) {
            $result = $this->evaluateRule($rule, $objectId, $object, $culture);
            $results[] = $result;

            // Store result
            DB::table('ahg_publish_gate_result')->insert([
                'object_id' => $objectId,
                'rule_id' => $rule->id,
                'status' => $result['status'],
                'details' => $result['details'],
                'evaluated_at' => date('Y-m-d H:i:s'),
                'evaluated_by' => $userId,
            ]);
        }

        // Emit gate_evaluated event
        $this->emitGateEvent('gate_evaluated', $objectId, $userId, [
            'rules_checked' => count($results),
            'passed' => count(array_filter($results, fn($r) => $r['status'] === 'passed')),
            'failed' => count(array_filter($results, fn($r) => $r['status'] === 'failed')),
            'warnings' => count(array_filter($results, fn($r) => $r['status'] === 'warning')),
        ]);

        return $results;
    }

    /**
     * Quick check: can this object be published (all blocker rules pass)?
     */
    public function canPublish(int $objectId): bool
    {
        $results = $this->evaluate($objectId);

        foreach ($results as $r) {
            if ($r['severity'] === 'blocker' && $r['status'] === 'failed') {
                return false;
            }
        }

        return true;
    }

    /**
     * Get only the failed blocker rules with messages.
     */
    public function getBlockers(int $objectId): array
    {
        $results = $this->evaluate($objectId);

        return array_values(array_filter($results, function ($r) {
            return $r['severity'] === 'blocker' && $r['status'] === 'failed';
        }));
    }

    /**
     * Simulate what would be visible if published.
     *
     * @return array {title, identifier, scope_and_content, dates, rights, digital_objects, access_conditions, repository}
     */
    public function simulatePublish(int $objectId, string $culture = 'en'): array
    {
        $io = DB::table('information_object as io')
            ->join('information_object_i18n as ioi', function ($join) use ($culture) {
                $join->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'io.id')
            ->where('io.id', $objectId)
            ->select('io.id', 'io.identifier', 'io.level_of_description_id', 'io.repository_id',
                     'ioi.title', 'ioi.scope_and_content', 'ioi.extent_and_medium',
                     'ioi.access_conditions', 'ioi.reproduction_conditions',
                     'ioi.archival_history', 'ioi.arrangement', 's.slug')
            ->first();

        if (!$io) {
            return ['error' => 'Object not found'];
        }

        // Get level label
        $level = null;
        if ($io->level_of_description_id) {
            $level = DB::table('term_i18n')
                ->where('id', $io->level_of_description_id)
                ->where('culture', $culture)
                ->value('name');
        }

        // Get repository name
        $repository = null;
        if ($io->repository_id) {
            $repository = DB::table('actor_i18n')
                ->where('id', $io->repository_id)
                ->where('culture', $culture)
                ->value('authorized_form_of_name');
        }

        // Get dates
        $dates = DB::table('event as e')
            ->join('event_i18n as ei', function ($join) use ($culture) {
                $join->on('e.id', '=', 'ei.id')->where('ei.culture', '=', $culture);
            })
            ->where('e.object_id', $objectId)
            ->select('e.start_date', 'e.end_date', 'ei.date', 'e.type_id')
            ->get()
            ->toArray();

        // Get digital objects
        $digitalObjects = DB::table('digital_object')
            ->where('object_id', $objectId)
            ->select('id', 'name', 'mime_type', 'byte_size')
            ->get()
            ->toArray();

        // Get rights
        $rights = DB::table('rights as r')
            ->leftJoin('rights_i18n as ri', function ($join) use ($culture) {
                $join->on('r.id', '=', 'ri.id')->where('ri.culture', '=', $culture);
            })
            ->where('r.object_id', $objectId)
            ->select('r.id', 'r.basis_id', 'ri.rights_note', 'ri.copyright_note', 'ri.license_note')
            ->get()
            ->toArray();

        return [
            'object_id' => $objectId,
            'slug' => $io->slug,
            'title' => $io->title,
            'identifier' => $io->identifier,
            'level' => $level,
            'repository' => $repository,
            'scope_and_content' => $io->scope_and_content,
            'extent_and_medium' => $io->extent_and_medium,
            'access_conditions' => $io->access_conditions,
            'reproduction_conditions' => $io->reproduction_conditions,
            'archival_history' => $io->archival_history,
            'arrangement' => $io->arrangement,
            'dates' => $dates,
            'digital_objects' => $digitalObjects,
            'rights' => $rights,
        ];
    }

    /**
     * Execute publish: evaluate gates, then set publication status if passed.
     *
     * @param int  $objectId Object to publish
     * @param int  $userId   User performing the action
     * @param bool $force    Force publish even with blockers (requires admin)
     * @return array {published, blockers, event_id, results}
     */
    public function executePublish(int $objectId, int $userId, bool $force = false): array
    {
        $results = $this->evaluate($objectId, $userId);

        $blockers = array_values(array_filter($results, function ($r) {
            return $r['severity'] === 'blocker' && $r['status'] === 'failed';
        }));

        if (!empty($blockers) && !$force) {
            $this->emitGateEvent('gate_failed', $objectId, $userId, [
                'blockers' => array_map(fn($b) => $b['error_message'], $blockers),
            ]);

            return [
                'published' => false,
                'blockers' => $blockers,
                'results' => $results,
            ];
        }

        // Set publication status to Published (160)
        DB::table('status')
            ->where('object_id', $objectId)
            ->where('type_id', 158) // Publication status taxonomy
            ->update(['status_id' => 160]); // 160 = Published

        // If no status row exists, create one
        $exists = DB::table('status')
            ->where('object_id', $objectId)
            ->where('type_id', 158)
            ->exists();

        if (!$exists) {
            DB::table('status')->insert([
                'object_id' => $objectId,
                'type_id' => 158,
                'status_id' => 160,
            ]);
        }

        // Emit appropriate event
        $eventAction = $force ? 'gate_overridden' : 'gate_passed';
        $eventId = $this->emitGateEvent($eventAction, $objectId, $userId, [
            'forced' => $force,
            'blocker_count' => count($blockers),
        ]);

        // Also emit the publish event
        $this->emitGateEvent('publish', $objectId, $userId, [
            'gate_status' => $force ? 'overridden' : 'passed',
        ]);

        return [
            'published' => true,
            'blockers' => $blockers,
            'event_id' => $eventId,
            'results' => $results,
        ];
    }

    // =========================================================================
    // RULE EVALUATION
    // =========================================================================

    /**
     * Evaluate a single rule against an object.
     */
    private function evaluateRule(object $rule, int $objectId, object $context, string $culture): array
    {
        $base = [
            'rule_id' => $rule->id,
            'rule_name' => $rule->name,
            'rule_type' => $rule->rule_type,
            'severity' => $rule->severity,
            'error_message' => $rule->error_message,
        ];

        try {
            $result = match ($rule->rule_type) {
                'field_required' => $this->evaluateFieldRequired($objectId, $rule->field_name, $culture),
                'field_not_empty' => $this->evaluateFieldNotEmpty($objectId, $rule->field_name, $culture),
                'has_digital_object' => $this->evaluateHasDigitalObject($objectId),
                'has_rights' => $this->evaluateHasRights($objectId),
                'has_access_condition' => $this->evaluateHasAccessCondition($objectId, $culture),
                'security_cleared' => $this->evaluateSecurityCleared($objectId),
                'iiif_ready' => $this->evaluateIiifReady($objectId, $culture),
                'custom_sql' => $this->evaluateCustomSql($objectId, $rule->rule_config),
                default => ['status' => 'skipped', 'details' => "Unknown rule type: {$rule->rule_type}"],
            };
        } catch (\Exception $e) {
            $result = ['status' => 'skipped', 'details' => "Error: {$e->getMessage()}"];
        }

        return array_merge($base, $result);
    }

    private function evaluateFieldRequired(int $objectId, ?string $fieldName, string $culture): array
    {
        if (!$fieldName) {
            return ['status' => 'skipped', 'details' => 'No field name specified'];
        }

        // Check i18n table first
        $value = DB::table('information_object_i18n')
            ->where('id', $objectId)
            ->where('culture', $culture)
            ->value($fieldName);

        if ($value === null) {
            // Try main table
            $value = DB::table('information_object')
                ->where('id', $objectId)
                ->value($fieldName);
        }

        if ($value !== null && trim((string) $value) !== '') {
            return ['status' => 'passed', 'details' => "Field '{$fieldName}' has value"];
        }

        return ['status' => 'failed', 'details' => "Field '{$fieldName}' is missing or empty"];
    }

    private function evaluateFieldNotEmpty(int $objectId, ?string $fieldName, string $culture): array
    {
        if (!$fieldName) {
            return ['status' => 'skipped', 'details' => 'No field name specified'];
        }

        $value = DB::table('information_object_i18n')
            ->where('id', $objectId)
            ->where('culture', $culture)
            ->value($fieldName);

        if ($value === null) {
            $value = DB::table('information_object')
                ->where('id', $objectId)
                ->value($fieldName);
        }

        if ($value !== null && strlen(trim(strip_tags((string) $value))) > 0) {
            return ['status' => 'passed', 'details' => "Field '{$fieldName}' is populated"];
        }

        return ['status' => 'failed', 'details' => "Field '{$fieldName}' is empty"];
    }

    private function evaluateHasDigitalObject(int $objectId): array
    {
        $count = DB::table('digital_object')
            ->where('object_id', $objectId)
            ->count();

        if ($count > 0) {
            return ['status' => 'passed', 'details' => "{$count} digital object(s) attached"];
        }

        return ['status' => 'failed', 'details' => 'No digital objects attached'];
    }

    private function evaluateHasRights(int $objectId): array
    {
        $count = DB::table('rights')
            ->where('object_id', $objectId)
            ->count();

        if ($count > 0) {
            return ['status' => 'passed', 'details' => "{$count} rights statement(s) assigned"];
        }

        return ['status' => 'failed', 'details' => 'No rights statements assigned'];
    }

    private function evaluateHasAccessCondition(int $objectId, string $culture): array
    {
        $value = DB::table('information_object_i18n')
            ->where('id', $objectId)
            ->where('culture', $culture)
            ->value('access_conditions');

        if ($value !== null && trim((string) $value) !== '') {
            return ['status' => 'passed', 'details' => 'Access conditions defined'];
        }

        return ['status' => 'failed', 'details' => 'Access conditions not set'];
    }

    private function evaluateSecurityCleared(int $objectId): array
    {
        try {
            $exists = DB::select("SHOW TABLES LIKE 'security_classification_record'");
            if (empty($exists)) {
                return ['status' => 'skipped', 'details' => 'Security classification module not installed'];
            }

            $record = DB::table('security_classification_record')
                ->where('object_id', $objectId)
                ->first();

            if (!$record) {
                return ['status' => 'passed', 'details' => 'No security classification (unrestricted)'];
            }

            if (isset($record->is_cleared) && $record->is_cleared) {
                return ['status' => 'passed', 'details' => 'Security clearance granted'];
            }

            return ['status' => 'failed', 'details' => 'Security clearance not granted'];
        } catch (\Exception $e) {
            return ['status' => 'skipped', 'details' => 'Security classification check unavailable'];
        }
    }

    /**
     * IIIF readiness check — delegates to IiifValidationService when available.
     * Added in Phase 4 (#184), initially returns skipped until that service exists.
     */
    public function evaluateIiifReady(int $objectId, string $culture = 'en'): array
    {
        // Check if object has digital objects at all
        $hasDigital = DB::table('digital_object')
            ->where('object_id', $objectId)
            ->exists();

        if (!$hasDigital) {
            return ['status' => 'skipped', 'details' => 'No digital objects — IIIF check not applicable'];
        }

        // Try to use IiifValidationService if available
        try {
            $serviceFile = \sfConfig::get('sf_root_dir') . '/plugins/ahgIiifPlugin/lib/Services/IiifValidationService.php';
            if (file_exists($serviceFile)) {
                require_once $serviceFile;
                $validator = new \AhgIiif\Services\IiifValidationService();
                $validation = $validator->validateManifest($objectId, $culture);

                $failures = array_filter($validation, fn($v) => $v['status'] === 'failed');
                if (empty($failures)) {
                    return ['status' => 'passed', 'details' => 'IIIF manifest validates successfully'];
                }

                $messages = array_map(fn($v) => $v['message'], $failures);
                return ['status' => 'failed', 'details' => 'IIIF issues: ' . implode('; ', $messages)];
            }
        } catch (\Exception $e) {
            // Fall through to basic check
        }

        // Basic fallback: just check derivatives exist
        $derivatives = DB::table('digital_object')
            ->where('object_id', $objectId)
            ->whereNotNull('name')
            ->count();

        if ($derivatives > 0) {
            return ['status' => 'passed', 'details' => 'Digital objects present (full IIIF validation requires ahgIiifPlugin)'];
        }

        return ['status' => 'failed', 'details' => 'Digital object names missing'];
    }

    private function evaluateCustomSql(int $objectId, ?string $ruleConfig): array
    {
        if (!$ruleConfig) {
            return ['status' => 'skipped', 'details' => 'No custom SQL configured'];
        }

        $config = json_decode($ruleConfig, true);
        if (!$config || empty($config['sql'])) {
            return ['status' => 'skipped', 'details' => 'Invalid rule config'];
        }

        try {
            // Custom SQL must return a count > 0 to pass
            // The SQL should contain :object_id placeholder
            $sql = str_replace(':object_id', (string) $objectId, $config['sql']);
            $result = DB::selectOne($sql);

            if ($result && (int) (array_values((array) $result)[0] ?? 0) > 0) {
                return ['status' => 'passed', 'details' => 'Custom check passed'];
            }

            return ['status' => 'failed', 'details' => $config['fail_message'] ?? 'Custom check failed'];
        } catch (\Exception $e) {
            return ['status' => 'skipped', 'details' => "SQL error: {$e->getMessage()}"];
        }
    }

    // =========================================================================
    // RULE ADMINISTRATION
    // =========================================================================

    /**
     * Get rules, optionally filtered.
     */
    public function getRules(?string $entityType = null, ?int $levelId = null, ?int $repositoryId = null): array
    {
        $query = DB::table('ahg_publish_gate_rule')
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($entityType) {
            $query->where('entity_type', $entityType);
        }
        if ($levelId !== null) {
            $query->where(function ($q) use ($levelId) {
                $q->whereNull('level_of_description_id')
                  ->orWhere('level_of_description_id', $levelId);
            });
        }
        if ($repositoryId !== null) {
            $query->where(function ($q) use ($repositoryId) {
                $q->whereNull('repository_id')
                  ->orWhere('repository_id', $repositoryId);
            });
        }

        return $query->get()->toArray();
    }

    /**
     * Create a gate rule.
     */
    public function createRule(array $data): int
    {
        return DB::table('ahg_publish_gate_rule')->insertGetId([
            'name' => $data['name'],
            'rule_type' => $data['rule_type'],
            'entity_type' => $data['entity_type'] ?? 'information_object',
            'level_of_description_id' => $data['level_of_description_id'] ?? null,
            'material_type' => $data['material_type'] ?? null,
            'repository_id' => $data['repository_id'] ?? null,
            'field_name' => $data['field_name'] ?? null,
            'rule_config' => $data['rule_config'] ?? null,
            'error_message' => $data['error_message'],
            'severity' => $data['severity'] ?? 'blocker',
            'is_active' => $data['is_active'] ?? 1,
            'sort_order' => $data['sort_order'] ?? 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update a gate rule.
     */
    public function updateRule(int $ruleId, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return DB::table('ahg_publish_gate_rule')
            ->where('id', $ruleId)
            ->update($data) > 0;
    }

    /**
     * Delete a gate rule.
     */
    public function deleteRule(int $ruleId): bool
    {
        // Also remove cached results for this rule
        DB::table('ahg_publish_gate_result')->where('rule_id', $ruleId)->delete();

        return DB::table('ahg_publish_gate_rule')
            ->where('id', $ruleId)
            ->delete() > 0;
    }

    /**
     * Get past evaluation results for an object.
     */
    public function getResultHistory(int $objectId, int $limit = 50): array
    {
        return DB::table('ahg_publish_gate_result as r')
            ->join('ahg_publish_gate_rule as ru', 'r.rule_id', '=', 'ru.id')
            ->leftJoin('user as u', 'r.evaluated_by', '=', 'u.id')
            ->where('r.object_id', $objectId)
            ->select('r.*', 'ru.name as rule_name', 'ru.rule_type', 'ru.severity', 'u.username as evaluator')
            ->orderByDesc('r.evaluated_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Get object context: level, repository, entity type.
     */
    private function getObjectContext(int $objectId): ?object
    {
        $obj = DB::table('information_object')
            ->where('id', $objectId)
            ->select('id', 'level_of_description_id', 'repository_id', 'parent_id')
            ->first();

        if (!$obj) {
            return null;
        }

        $obj->entity_type = 'information_object';

        // Inherit repository from parent if not set
        if (!$obj->repository_id && $obj->parent_id > 1) {
            $obj->repository_id = DB::table('information_object')
                ->where('id', $obj->parent_id)
                ->value('repository_id');
        }

        return $obj;
    }

    /**
     * Get applicable rules for an object's context.
     */
    private function getApplicableRules(string $entityType, ?int $levelId, ?int $repositoryId): array
    {
        return DB::table('ahg_publish_gate_rule')
            ->where('is_active', 1)
            ->where(function ($q) use ($entityType) {
                $q->where('entity_type', $entityType)
                  ->orWhereNull('entity_type');
            })
            ->where(function ($q) use ($levelId) {
                $q->whereNull('level_of_description_id')
                  ->orWhere('level_of_description_id', $levelId);
            })
            ->where(function ($q) use ($repositoryId) {
                $q->whereNull('repository_id')
                  ->orWhere('repository_id', $repositoryId);
            })
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    /**
     * Emit a gate-related workflow event if WorkflowEventService is available.
     */
    private function emitGateEvent(string $action, int $objectId, ?int $userId, array $metadata = []): int
    {
        try {
            require_once \sfConfig::get('sf_root_dir') . '/plugins/ahgWorkflowPlugin/lib/Services/WorkflowEventService.php';
            $eventService = new WorkflowEventService();

            return $eventService->emit($action, [
                'object_id' => $objectId,
                'performed_by' => $userId ?? 0,
                'metadata' => $metadata,
                'comment' => "Publish gate: {$action}",
            ]);
        } catch (\Exception $e) {
            return 0;
        }
    }
}
