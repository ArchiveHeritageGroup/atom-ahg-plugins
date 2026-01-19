<?php

declare(strict_types=1);

namespace AhgLoan\Services\Workflow;

use AhgLoan\Contracts\WorkflowInterface;

/**
 * Abstract Workflow State Machine.
 *
 * Base implementation for Spectrum 5.0 procedure workflows.
 * Subclasses define specific states and transitions.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
abstract class AbstractWorkflow implements WorkflowInterface
{
    /** @var array<string, array> State definitions */
    protected array $states = [];

    /** @var array<string, array> Transition definitions */
    protected array $transitions = [];

    /** @var string Initial state identifier */
    protected string $initialState;

    /** @var string[] Final state identifiers */
    protected array $finalStates = [];

    /** @var array<string, callable[]> Event listeners */
    protected array $listeners = [];

    /**
     * {@inheritDoc}
     */
    public function getStates(): array
    {
        return $this->states;
    }

    /**
     * {@inheritDoc}
     */
    public function getTransitions(): array
    {
        return $this->transitions;
    }

    /**
     * {@inheritDoc}
     */
    public function getInitialState(): string
    {
        return $this->initialState;
    }

    /**
     * {@inheritDoc}
     */
    public function getFinalStates(): array
    {
        return $this->finalStates;
    }

    /**
     * {@inheritDoc}
     */
    public function canTransition(string $currentState, string $transition, array $context = []): bool
    {
        // Check transition exists
        if (!isset($this->transitions[$transition])) {
            return false;
        }

        $transitionDef = $this->transitions[$transition];

        // Check current state is valid source
        $fromStates = (array) ($transitionDef['from'] ?? []);
        if (!in_array($currentState, $fromStates) && !in_array('*', $fromStates)) {
            return false;
        }

        // Check role permissions
        if (!empty($transitionDef['roles']) && !empty($context['user_roles'])) {
            $allowedRoles = (array) $transitionDef['roles'];
            $userRoles = (array) $context['user_roles'];

            if (empty(array_intersect($allowedRoles, $userRoles)) && !in_array('*', $allowedRoles)) {
                return false;
            }
        }

        // Check guard conditions
        if (isset($transitionDef['guard']) && is_callable($transitionDef['guard'])) {
            if (!$transitionDef['guard']($currentState, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getAvailableTransitions(string $currentState, array $context = []): array
    {
        $available = [];

        foreach ($this->transitions as $name => $definition) {
            if ($this->canTransition($currentState, $name, $context)) {
                $available[$name] = [
                    'name' => $name,
                    'label' => $definition['label'] ?? ucfirst(str_replace('_', ' ', $name)),
                    'to' => $definition['to'],
                    'icon' => $definition['icon'] ?? null,
                    'color' => $definition['color'] ?? 'default',
                    'confirm' => $definition['confirm'] ?? false,
                    'confirm_message' => $definition['confirm_message'] ?? null,
                ];
            }
        }

        return $available;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(string $currentState, string $transition, array $context = []): string
    {
        if (!$this->canTransition($currentState, $transition, $context)) {
            throw new \InvalidArgumentException(
                "Transition '{$transition}' not allowed from state '{$currentState}'"
            );
        }

        $transitionDef = $this->transitions[$transition];
        $newState = $transitionDef['to'];

        // Fire before event
        $this->fireEvent('before_transition', [
            'transition' => $transition,
            'from' => $currentState,
            'to' => $newState,
            'context' => $context,
        ]);

        // Execute transition callback if defined
        if (isset($transitionDef['callback']) && is_callable($transitionDef['callback'])) {
            $transitionDef['callback']($currentState, $newState, $context);
        }

        // Fire after event
        $this->fireEvent('after_transition', [
            'transition' => $transition,
            'from' => $currentState,
            'to' => $newState,
            'context' => $context,
        ]);

        // Fire state-specific events
        $this->fireEvent("leave_{$currentState}", ['context' => $context]);
        $this->fireEvent("enter_{$newState}", ['context' => $context]);

        return $newState;
    }

    /**
     * {@inheritDoc}
     */
    public function getTransitionRoles(string $transition): array
    {
        return (array) ($this->transitions[$transition]['roles'] ?? ['*']);
    }

    /**
     * {@inheritDoc}
     */
    public function validate(): array
    {
        $errors = [];

        // Check initial state exists
        if (!isset($this->states[$this->initialState])) {
            $errors[] = "Initial state '{$this->initialState}' not defined in states";
        }

        // Check final states exist
        foreach ($this->finalStates as $finalState) {
            if (!isset($this->states[$finalState])) {
                $errors[] = "Final state '{$finalState}' not defined in states";
            }
        }

        // Validate transitions
        foreach ($this->transitions as $name => $def) {
            // Check 'from' states exist
            $fromStates = (array) ($def['from'] ?? []);
            foreach ($fromStates as $from) {
                if ('*' !== $from && !isset($this->states[$from])) {
                    $errors[] = "Transition '{$name}': source state '{$from}' not defined";
                }
            }

            // Check 'to' state exists
            if (!isset($def['to'])) {
                $errors[] = "Transition '{$name}': missing 'to' state";
            } elseif (!isset($this->states[$def['to']])) {
                $errors[] = "Transition '{$name}': target state '{$def['to']}' not defined";
            }
        }

        // Check all non-final states have outgoing transitions
        foreach ($this->states as $state => $def) {
            if (in_array($state, $this->finalStates)) {
                continue;
            }

            $hasOutgoing = false;
            foreach ($this->transitions as $transition) {
                $fromStates = (array) ($transition['from'] ?? []);
                if (in_array($state, $fromStates) || in_array('*', $fromStates)) {
                    $hasOutgoing = true;
                    break;
                }
            }

            if (!$hasOutgoing) {
                $errors[] = "Non-final state '{$state}' has no outgoing transitions";
            }
        }

        return $errors;
    }

    /**
     * Get state metadata.
     *
     * @param string $state State identifier
     *
     * @return array|null State definition or null if not found
     */
    public function getState(string $state): ?array
    {
        return $this->states[$state] ?? null;
    }

    /**
     * Check if state is final.
     */
    public function isFinalState(string $state): bool
    {
        return in_array($state, $this->finalStates);
    }

    /**
     * Get state label for display.
     */
    public function getStateLabel(string $state): string
    {
        return $this->states[$state]['label'] ?? ucfirst(str_replace('_', ' ', $state));
    }

    /**
     * Get state color for UI.
     */
    public function getStateColor(string $state): string
    {
        return $this->states[$state]['color'] ?? 'default';
    }

    /**
     * Register event listener.
     *
     * @param string   $event    Event name
     * @param callable $listener Callback function
     */
    public function on(string $event, callable $listener): self
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }

        $this->listeners[$event][] = $listener;

        return $this;
    }

    /**
     * Fire event to listeners.
     *
     * @param string $event Event name
     * @param array  $data  Event data
     */
    protected function fireEvent(string $event, array $data = []): void
    {
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $listener) {
                $listener($data);
            }
        }
    }

    /**
     * Generate workflow diagram in Mermaid format.
     *
     * @return string Mermaid diagram code
     */
    public function toMermaid(): string
    {
        $lines = ['stateDiagram-v2'];

        // Initial state
        $lines[] = "    [*] --> {$this->initialState}";

        // Transitions
        foreach ($this->transitions as $name => $def) {
            $fromStates = (array) ($def['from'] ?? []);
            $to = $def['to'];
            $label = $def['label'] ?? $name;

            foreach ($fromStates as $from) {
                if ('*' === $from) {
                    continue; // Skip wildcard in diagram
                }
                $lines[] = "    {$from} --> {$to} : {$label}";
            }
        }

        // Final states
        foreach ($this->finalStates as $final) {
            $lines[] = "    {$final} --> [*]";
        }

        return implode("\n", $lines);
    }

    /**
     * Export workflow definition as array.
     */
    public function toArray(): array
    {
        return [
            'identifier' => $this->getIdentifier(),
            'name' => $this->getName(),
            'initial_state' => $this->initialState,
            'final_states' => $this->finalStates,
            'states' => $this->states,
            'transitions' => array_map(function ($t) {
                // Remove non-serializable elements
                unset($t['guard'], $t['callback']);

                return $t;
            }, $this->transitions),
        ];
    }
}
