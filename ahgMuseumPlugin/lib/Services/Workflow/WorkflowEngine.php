<?php

declare(strict_types=1);

namespace arMuseumMetadataPlugin\Services\Workflow;

use arMuseumMetadataPlugin\Contracts\WorkflowInterface;
use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Workflow Engine.
 *
 * Manages workflow instances, executes transitions, and maintains
 * workflow history. Provides centralized workflow management for
 * Spectrum 5.0 procedures.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class WorkflowEngine
{
    /** @var array<string, WorkflowInterface> Registered workflows */
    private array $workflows = [];

    private ConnectionInterface $db;
    private LoggerInterface $logger;

    public function __construct(
        ConnectionInterface $db,
        ?LoggerInterface $logger = null
    ) {
        $this->db = $db;
        $this->logger = $logger ?? new NullLogger();

        // Register default workflows
        $this->registerDefaultWorkflows();
    }

    /**
     * Register a workflow.
     *
     * @param WorkflowInterface $workflow Workflow instance
     */
    public function register(WorkflowInterface $workflow): self
    {
        $this->workflows[$workflow->getIdentifier()] = $workflow;

        return $this;
    }

    /**
     * Get a registered workflow.
     *
     * @param string $identifier Workflow identifier
     *
     * @return WorkflowInterface|null Workflow or null if not found
     */
    public function getWorkflow(string $identifier): ?WorkflowInterface
    {
        return $this->workflows[$identifier] ?? null;
    }

    /**
     * Get all registered workflows.
     *
     * @return array<string, WorkflowInterface>
     */
    public function getWorkflows(): array
    {
        return $this->workflows;
    }

    /**
     * Create a new workflow instance.
     *
     * @param string $workflowId Workflow identifier
     * @param string $entityType Entity type (e.g., 'loan', 'entry')
     * @param int    $entityId   Entity ID
     * @param int    $userId     User creating the instance
     * @param array  $metadata   Additional metadata
     *
     * @return int Instance ID
     */
    public function createInstance(
        string $workflowId,
        string $entityType,
        int $entityId,
        int $userId,
        array $metadata = []
    ): int {
        $workflow = $this->getWorkflow($workflowId);
        if (!$workflow) {
            throw new \InvalidArgumentException("Unknown workflow: {$workflowId}");
        }

        $now = date('Y-m-d H:i:s');
        $initialState = $workflow->getInitialState();

        $instanceId = $this->db->table('workflow_instance')->insertGetId([
            'workflow_id' => $workflowId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'current_state' => $initialState,
            'is_complete' => false,
            'metadata' => json_encode($metadata),
            'created_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Log initial state
        $this->logTransition($instanceId, null, $initialState, 'create', $userId, null);

        $this->logger->info('Workflow instance created', [
            'instance_id' => $instanceId,
            'workflow_id' => $workflowId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'initial_state' => $initialState,
        ]);

        return $instanceId;
    }

    /**
     * Get workflow instance by ID.
     *
     * @param int $instanceId Instance ID
     *
     * @return array|null Instance data
     */
    public function getInstance(int $instanceId): ?array
    {
        $instance = $this->db->table('workflow_instance')
            ->where('id', $instanceId)
            ->first();

        if (!$instance) {
            return null;
        }

        $data = (array) $instance;
        $data['metadata'] = json_decode($data['metadata'] ?? '{}', true);

        // Add workflow info
        $workflow = $this->getWorkflow($data['workflow_id']);
        if ($workflow) {
            $data['workflow_name'] = $workflow->getName();
            $data['state_info'] = $workflow->getState($data['current_state']);
            $data['available_transitions'] = $workflow->getAvailableTransitions(
                $data['current_state'],
                $this->getTransitionContext($instanceId)
            );
            $data['progress'] = method_exists($workflow, 'getProgress')
                ? $workflow->getProgress($data['current_state'])
                : 0;
        }

        return $data;
    }

    /**
     * Get workflow instance for an entity.
     *
     * @param string $entityType Entity type
     * @param int    $entityId   Entity ID
     *
     * @return array|null Instance data or null
     */
    public function getInstanceForEntity(string $entityType, int $entityId): ?array
    {
        $instance = $this->db->table('workflow_instance')
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('is_complete', false)
            ->orderByDesc('created_at')
            ->first();

        if (!$instance) {
            return null;
        }

        return $this->getInstance($instance->id);
    }

    /**
     * Execute a transition on a workflow instance.
     *
     * @param int         $instanceId Instance ID
     * @param string      $transition Transition to execute
     * @param int         $userId     User executing the transition
     * @param string|null $comment    Optional comment
     * @param array       $data       Additional data for transition
     *
     * @return array Updated instance data
     */
    public function transition(
        int $instanceId,
        string $transition,
        int $userId,
        ?string $comment = null,
        array $data = []
    ): array {
        $instance = $this->getInstance($instanceId);
        if (!$instance) {
            throw new \InvalidArgumentException("Instance not found: {$instanceId}");
        }

        $workflow = $this->getWorkflow($instance['workflow_id']);
        if (!$workflow) {
            throw new \InvalidArgumentException("Workflow not found: {$instance['workflow_id']}");
        }

        $currentState = $instance['current_state'];
        $context = array_merge(
            $this->getTransitionContext($instanceId),
            ['data' => $data]
        );

        // Execute transition
        $newState = $workflow->apply($currentState, $transition, $context);

        $now = date('Y-m-d H:i:s');
        $isComplete = $workflow->isFinalState($newState);

        // Update instance
        $this->db->table('workflow_instance')
            ->where('id', $instanceId)
            ->update([
                'current_state' => $newState,
                'is_complete' => $isComplete,
                'updated_at' => $now,
                'completed_at' => $isComplete ? $now : null,
            ]);

        // Log transition
        $this->logTransition($instanceId, $currentState, $newState, $transition, $userId, $comment);

        $this->logger->info('Workflow transition executed', [
            'instance_id' => $instanceId,
            'workflow_id' => $instance['workflow_id'],
            'transition' => $transition,
            'from_state' => $currentState,
            'to_state' => $newState,
            'user_id' => $userId,
        ]);

        return $this->getInstance($instanceId);
    }

    /**
     * Get transition history for an instance.
     *
     * @param int $instanceId Instance ID
     *
     * @return array History records
     */
    public function getHistory(int $instanceId): array
    {
        return $this->db->table('workflow_history')
            ->where('workflow_instance_id', $instanceId)
            ->orderBy('created_at')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Get active instances by workflow.
     *
     * @param string $workflowId Workflow identifier
     * @param int    $limit      Maximum results
     *
     * @return array Active instances
     */
    public function getActiveInstances(string $workflowId, int $limit = 50): array
    {
        return $this->db->table('workflow_instance')
            ->where('workflow_id', $workflowId)
            ->where('is_complete', false)
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => $this->getInstance($r->id))
            ->all();
    }

    /**
     * Get instances by state.
     *
     * @param string $workflowId Workflow identifier
     * @param string $state      State to filter by
     * @param int    $limit      Maximum results
     *
     * @return array Matching instances
     */
    public function getInstancesByState(string $workflowId, string $state, int $limit = 50): array
    {
        return $this->db->table('workflow_instance')
            ->where('workflow_id', $workflowId)
            ->where('current_state', $state)
            ->where('is_complete', false)
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => $this->getInstance($r->id))
            ->all();
    }

    /**
     * Get workflow statistics.
     *
     * @param string $workflowId Workflow identifier
     *
     * @return array Statistics
     */
    public function getStatistics(string $workflowId): array
    {
        $workflow = $this->getWorkflow($workflowId);
        if (!$workflow) {
            return [];
        }

        $stats = [
            'workflow_id' => $workflowId,
            'workflow_name' => $workflow->getName(),
            'total_instances' => 0,
            'active_instances' => 0,
            'completed_instances' => 0,
            'by_state' => [],
            'avg_completion_days' => 0,
        ];

        // Total counts
        $counts = $this->db->table('workflow_instance')
            ->selectRaw('is_complete, COUNT(*) as count')
            ->where('workflow_id', $workflowId)
            ->groupBy('is_complete')
            ->get();

        foreach ($counts as $row) {
            if ($row->is_complete) {
                $stats['completed_instances'] = (int) $row->count;
            } else {
                $stats['active_instances'] = (int) $row->count;
            }
        }
        $stats['total_instances'] = $stats['active_instances'] + $stats['completed_instances'];

        // By state
        $byState = $this->db->table('workflow_instance')
            ->selectRaw('current_state, COUNT(*) as count')
            ->where('workflow_id', $workflowId)
            ->where('is_complete', false)
            ->groupBy('current_state')
            ->get();

        foreach ($byState as $row) {
            $stats['by_state'][$row->current_state] = (int) $row->count;
        }

        // Average completion time
        $avgTime = $this->db->table('workflow_instance')
            ->where('workflow_id', $workflowId)
            ->where('is_complete', true)
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(DATEDIFF(completed_at, created_at)) as avg_days')
            ->first();

        $stats['avg_completion_days'] = round($avgTime->avg_days ?? 0, 1);

        return $stats;
    }

    /**
     * Get all workflow statistics.
     *
     * @return array Statistics by workflow
     */
    public function getAllStatistics(): array
    {
        $stats = [];

        foreach ($this->workflows as $id => $workflow) {
            $stats[$id] = $this->getStatistics($id);
        }

        return $stats;
    }

    /**
     * Log a transition to history.
     */
    private function logTransition(
        int $instanceId,
        ?string $fromState,
        string $toState,
        string $transition,
        int $userId,
        ?string $comment
    ): void {
        $this->db->table('workflow_history')->insert([
            'workflow_instance_id' => $instanceId,
            'from_state' => $fromState,
            'to_state' => $toState,
            'transition' => $transition,
            'user_id' => $userId,
            'comment' => $comment,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get context for transition checks.
     */
    private function getTransitionContext(int $instanceId): array
    {
        // This would be expanded to include user roles, permissions, etc.
        return [
            'instance_id' => $instanceId,
            'user_roles' => ['administrator'], // Would come from session/auth
        ];
    }

    /**
     * Register default Spectrum 5.0 workflows.
     */
    private function registerDefaultWorkflows(): void
    {
        $this->register(new LoanOutWorkflow());
        $this->register(new LoanInWorkflow());
        $this->register(new ObjectEntryWorkflow());
        $this->register(new ExhibitionWorkflow());
    }

    /**
     * Export workflow as Mermaid diagram.
     *
     * @param string $workflowId Workflow identifier
     *
     * @return string|null Mermaid diagram or null
     */
    public function exportMermaid(string $workflowId): ?string
    {
        $workflow = $this->getWorkflow($workflowId);
        if (!$workflow) {
            return null;
        }

        return $workflow->toMermaid();
    }

    /**
     * Validate all registered workflows.
     *
     * @return array Validation errors by workflow
     */
    public function validateAll(): array
    {
        $errors = [];

        foreach ($this->workflows as $id => $workflow) {
            $workflowErrors = $workflow->validate();
            if (!empty($workflowErrors)) {
                $errors[$id] = $workflowErrors;
            }
        }

        return $errors;
    }
}
