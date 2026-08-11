<?php

/**
 * What a procedure produces when it reaches a state.
 *
 * A handler never writes the destination. It describes what it would write, and
 * that description is stored as a proposal for whoever is accountable for the
 * destination to accept or refuse. Approving a valuation and recognising it in
 * the accounts are two different acts by two different people, and the software
 * should not collapse them.
 *
 * apply() runs only when a human accepts, and only then may it write.
 */
interface SpectrumOutcomeHandler
{
    /**
     * Short label for the review list.
     */
    public function label(): string;

    /**
     * Build what this outcome would do, or null if there is nothing to propose.
     *
     * Returning null is normal, not an error - a valuation flow driven to
     * approved with no valuation recorded has nothing to propose, and should say
     * so rather than invent a figure.
     *
     * @return array{summary: string, payload: array}|null
     */
    public function propose(string $procedureType, int $recordId): ?array;

    /**
     * Write the proposal to its destination. Called only on acceptance.
     *
     * @return string a human-readable note of what was actually written
     *
     * @throws RuntimeException if it cannot be applied; the proposal is marked
     *                          failed and the reason recorded
     */
    public function apply(array $payload, int $recordId, ?int $userId): string;
}
