<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * OdrlService - Open Digital Rights Language Policy Management
 *
 * Manages ODRL-based rights policies for research data, including
 * permissions, prohibitions, and obligations. Evaluates access
 * decisions against configured policies and logs all evaluations.
 *
 * Tables: research_rights_policy, research_access_decision
 *
 * @see https://www.w3.org/TR/odrl-model/
 * @package ahgResearchPlugin
 * @version 2.1.0
 */
class OdrlService
{
    /**
     * Valid policy types.
     */
    private const VALID_POLICY_TYPES = ['permission', 'prohibition', 'obligation'];

    /**
     * Valid action types.
     */
    private const VALID_ACTION_TYPES = ['use', 'reproduce', 'distribute', 'modify', 'archive', 'display'];

    // =========================================================================
    // POLICY CRUD
    // =========================================================================

    /**
     * Create a new ODRL rights policy.
     *
     * @param array $data Keys: target_type, target_id, policy_type (permission|prohibition|obligation),
     *                     action_type (use|reproduce|distribute|modify|archive|display),
     *                     constraints_json, policy_json, created_by
     * @return int The new policy ID
     */
    public function createPolicy(array $data): int
    {
        $constraintsJson = null;
        if (isset($data['constraints_json'])) {
            $constraintsJson = is_string($data['constraints_json'])
                ? $data['constraints_json']
                : json_encode($data['constraints_json']);
        }

        $policyJson = null;
        if (isset($data['policy_json'])) {
            $policyJson = is_string($data['policy_json'])
                ? $data['policy_json']
                : json_encode($data['policy_json']);
        }

        $policyId = DB::table('research_rights_policy')->insertGetId([
            'target_type' => $data['target_type'],
            'target_id' => (int) $data['target_id'],
            'policy_type' => $data['policy_type'],
            'action_type' => $data['action_type'],
            'constraints_json' => $constraintsJson,
            'policy_json' => $policyJson,
            'created_by' => isset($data['created_by']) ? (int) $data['created_by'] : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if (!empty($data['created_by'])) {
            $this->logEvent(
                (int) $data['created_by'],
                null,
                'policy_evaluated',
                'rights_policy',
                $policyId,
                $data['policy_type'] . ':' . $data['action_type'] . ' on ' . $data['target_type'] . ':' . $data['target_id']
            );
        }

        return $policyId;
    }

    /**
     * Get a policy by ID.
     *
     * @param int $id The policy ID
     * @return object|null The policy or null if not found
     */
    public function getPolicy(int $id): ?object
    {
        return DB::table('research_rights_policy')
            ->where('id', $id)
            ->first();
    }

    /**
     * Get all policies for a specific target.
     *
     * @param string $targetType The target entity type (e.g. 'collection', 'project', 'snapshot')
     * @param int $targetId The target entity ID
     * @return array List of policies for the target
     */
    public function getPolicies(string $targetType, int $targetId): array
    {
        return DB::table('research_rights_policy')
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->orderBy('policy_type')
            ->orderBy('action_type')
            ->get()
            ->toArray();
    }

    /**
     * Update an existing policy.
     *
     * @param int $id The policy ID
     * @param array $data Fields to update
     * @return bool True if the policy was updated
     */
    public function updatePolicy(int $id, array $data): bool
    {
        $allowed = ['target_type', 'target_id', 'policy_type', 'action_type', 'constraints_json', 'policy_json', 'created_by'];
        $updateData = array_intersect_key($data, array_flip($allowed));

        if (empty($updateData)) {
            return false;
        }

        if (isset($updateData['constraints_json']) && is_array($updateData['constraints_json'])) {
            $updateData['constraints_json'] = json_encode($updateData['constraints_json']);
        }

        if (isset($updateData['policy_json']) && is_array($updateData['policy_json'])) {
            $updateData['policy_json'] = json_encode($updateData['policy_json']);
        }

        if (isset($updateData['target_id'])) {
            $updateData['target_id'] = (int) $updateData['target_id'];
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        return DB::table('research_rights_policy')
            ->where('id', $id)
            ->update($updateData) > 0;
    }

    /**
     * Delete a policy.
     *
     * @param int $id The policy ID
     * @return bool True if the policy was deleted
     */
    public function deletePolicy(int $id): bool
    {
        // Delete related access decisions first
        DB::table('research_access_decision')
            ->where('policy_id', $id)
            ->delete();

        return DB::table('research_rights_policy')
            ->where('id', $id)
            ->delete() > 0;
    }

    // =========================================================================
    // ACCESS EVALUATION
    // =========================================================================

    /**
     * Evaluate whether a specific action is permitted for a researcher on a target.
     *
     * Queries all policies for the target, checks for prohibitions first
     * (prohibition takes precedence), then checks for matching permissions.
     * Logs a policy_evaluated event for audit purposes.
     *
     * @param string $targetType The target entity type
     * @param int $targetId The target entity ID
     * @param int $researcherId The researcher requesting access
     * @param string $action The action being requested (use|reproduce|distribute|modify|archive|display)
     * @return array ['permitted' => bool, 'policies' => [...matching policies], 'rationale' => string]
     */
    public function evaluateAccess(string $targetType, int $targetId, int $researcherId, string $action): array
    {
        $policies = DB::table('research_rights_policy')
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->where('action_type', $action)
            ->orderBy('policy_type')
            ->get()
            ->toArray();

        $matchingPolicies = [];
        $permitted = null;
        $rationale = '';

        // Check prohibitions first (prohibition overrides permission)
        foreach ($policies as $policy) {
            if ($policy->policy_type === 'prohibition') {
                // Check if any constraints exclude this researcher
                $constraintsMet = $this->evaluateConstraints($policy, $researcherId);

                if ($constraintsMet) {
                    $matchingPolicies[] = $policy;
                    $permitted = false;
                    $rationale = 'Action "' . $action . '" is prohibited on ' . $targetType . ':' . $targetId
                        . ' (policy #' . $policy->id . ')';
                    break;
                }
            }
        }

        // If no prohibition matched, check for permissions
        if ($permitted === null) {
            foreach ($policies as $policy) {
                if ($policy->policy_type === 'permission') {
                    $constraintsMet = $this->evaluateConstraints($policy, $researcherId);

                    if ($constraintsMet) {
                        $matchingPolicies[] = $policy;
                        $permitted = true;
                        $rationale = 'Action "' . $action . '" is permitted on ' . $targetType . ':' . $targetId
                            . ' (policy #' . $policy->id . ')';
                        break;
                    }
                }
            }
        }

        // Default: if no matching policies found, deny access
        if ($permitted === null) {
            $permitted = false;
            $rationale = 'No matching policy found for action "' . $action . '" on ' . $targetType . ':' . $targetId
                . '. Access denied by default.';
        }

        // Record the access decision
        $decisionPolicyId = !empty($matchingPolicies) ? $matchingPolicies[0]->id : 0;

        DB::table('research_access_decision')->insert([
            'policy_id' => $decisionPolicyId,
            'researcher_id' => $researcherId,
            'action_requested' => $action,
            'decision' => $permitted ? 'permitted' : 'denied',
            'rationale' => $rationale,
            'evaluated_at' => date('Y-m-d H:i:s'),
        ]);

        // Log the evaluation event
        $this->logEvent(
            $researcherId,
            null,
            'policy_evaluated',
            'rights_policy',
            $decisionPolicyId,
            ($permitted ? 'PERMITTED' : 'DENIED') . ': ' . $action . ' on ' . $targetType . ':' . $targetId
        );

        return [
            'permitted' => $permitted,
            'policies' => $matchingPolicies,
            'rationale' => $rationale,
        ];
    }

    // =========================================================================
    // CONSTRAINT EVALUATION (Private)
    // =========================================================================

    /**
     * Evaluate whether a policy's constraints apply to the given researcher.
     *
     * Parses the constraints_json field which may contain:
     * - researcher_ids: array of allowed/blocked researcher IDs
     * - date_from / date_to: temporal validity window
     * - max_uses: maximum number of uses (checked against access_decision count)
     *
     * A policy with no constraints always matches.
     *
     * @param object $policy The policy record
     * @param int $researcherId The researcher to evaluate
     * @return bool True if the constraints are satisfied (policy applies)
     */
    private function evaluateConstraints(object $policy, int $researcherId): bool
    {
        if (empty($policy->constraints_json)) {
            return true;
        }

        $constraints = json_decode($policy->constraints_json, true);

        if (!is_array($constraints)) {
            return true;
        }

        // Check researcher_ids constraint
        if (isset($constraints['researcher_ids']) && is_array($constraints['researcher_ids'])) {
            if (!in_array($researcherId, $constraints['researcher_ids'], false)) {
                return false;
            }
        }

        // Check temporal constraints
        $now = date('Y-m-d H:i:s');

        if (!empty($constraints['date_from']) && $now < $constraints['date_from']) {
            return false;
        }

        if (!empty($constraints['date_to']) && $now > $constraints['date_to']) {
            return false;
        }

        // Check usage limit constraint
        if (isset($constraints['max_uses']) && (int) $constraints['max_uses'] > 0) {
            $currentUses = DB::table('research_access_decision')
                ->where('policy_id', $policy->id)
                ->where('researcher_id', $researcherId)
                ->where('decision', 'permitted')
                ->count();

            if ($currentUses >= (int) $constraints['max_uses']) {
                return false;
            }
        }

        return true;
    }

    // =========================================================================
    // EVENT LOGGING
    // =========================================================================

    /**
     * Log a canonical event to the research activity log.
     *
     * @param int $researcherId The researcher performing the action
     * @param int|null $projectId The related project (if any)
     * @param string $type The activity type
     * @param string $entityType The entity type being acted upon
     * @param int $entityId The entity ID
     * @param string|null $title Optional descriptive title for the event
     */
    private function logEvent(int $researcherId, ?int $projectId, string $type, string $entityType, int $entityId, ?string $title = null): void
    {
        DB::table('research_activity_log')->insert([
            'researcher_id' => $researcherId,
            'project_id' => $projectId,
            'activity_type' => $type,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_title' => $title,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
