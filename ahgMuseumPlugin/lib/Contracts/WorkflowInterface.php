<?php

declare(strict_types=1);

namespace arMuseumMetadataPlugin\Contracts;

/**
 * Workflow State Machine Interface.
 *
 * Defines contract for managing workflow state transitions.
 * Supports Spectrum 5.0 procedures with configurable states and transitions.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
interface WorkflowInterface
{
    /**
     * Get workflow identifier.
     */
    public function getIdentifier(): string;

    /**
     * Get workflow display name.
     */
    public function getName(): string;

    /**
     * Get all states in this workflow.
     *
     * @return array<string, array> State definitions
     */
    public function getStates(): array;

    /**
     * Get all transitions in this workflow.
     *
     * @return array<string, array> Transition definitions
     */
    public function getTransitions(): array;

    /**
     * Get initial state for new instances.
     */
    public function getInitialState(): string;

    /**
     * Get final/completed states.
     *
     * @return string[] Final state identifiers
     */
    public function getFinalStates(): array;

    /**
     * Check if a transition is allowed from current state.
     *
     * @param string $currentState Current state
     * @param string $transition   Transition to check
     * @param array  $context      Additional context (user, permissions, etc.)
     *
     * @return bool True if transition allowed
     */
    public function canTransition(string $currentState, string $transition, array $context = []): bool;

    /**
     * Get available transitions from current state.
     *
     * @param string $currentState Current state
     * @param array  $context      Additional context
     *
     * @return array Available transitions with metadata
     */
    public function getAvailableTransitions(string $currentState, array $context = []): array;

    /**
     * Execute a transition.
     *
     * @param string $currentState Current state
     * @param string $transition   Transition to execute
     * @param array  $context      Additional context
     *
     * @return string New state after transition
     *
     * @throws \InvalidArgumentException If transition not allowed
     */
    public function apply(string $currentState, string $transition, array $context = []): string;

    /**
     * Get roles allowed to perform a transition.
     *
     * @param string $transition Transition identifier
     *
     * @return string[] Role identifiers
     */
    public function getTransitionRoles(string $transition): array;

    /**
     * Validate workflow configuration.
     *
     * @return array Validation errors (empty if valid)
     */
    public function validate(): array;
}
