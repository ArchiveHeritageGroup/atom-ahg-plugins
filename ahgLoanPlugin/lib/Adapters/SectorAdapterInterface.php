<?php

declare(strict_types=1);

namespace AhgLoan\Adapters;

/**
 * Sector Adapter Interface.
 *
 * Defines the contract for sector-specific loan behavior.
 * Each GLAM sector (Museum, Gallery, Archive, DAM) implements
 * this interface to customize loan handling.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
interface SectorAdapterInterface
{
    /**
     * Get the sector code (e.g., 'museum', 'gallery', 'archive', 'dam').
     */
    public function getSectorCode(): string;

    /**
     * Get the sector display name.
     */
    public function getSectorName(): string;

    /**
     * Get sector-specific loan purposes.
     *
     * @return array<string, string> Purpose code => label
     */
    public function getPurposes(): array;

    /**
     * Validate loan data before creation.
     *
     * @param array $data Raw loan data
     *
     * @return array Validated/transformed data
     *
     * @throws \InvalidArgumentException If validation fails
     */
    public function validateLoanData(array $data): array;

    /**
     * Enrich loan data with sector-specific information.
     *
     * @param array $data Loan data from database
     *
     * @return array Enriched data
     */
    public function enrichLoanData(array $data): array;

    /**
     * Enrich object data with sector-specific information.
     *
     * @param array $data Object data
     *
     * @return array Enriched object data
     */
    public function enrichObjectData(array $data): array;

    /**
     * Called after a loan is created.
     *
     * @param int   $loanId Loan ID
     * @param array $data   Loan data
     */
    public function onLoanCreated(int $loanId, array $data): void;

    /**
     * Called when loan status changes.
     *
     * @param int    $loanId         Loan ID
     * @param string $previousStatus Previous status
     * @param string $newStatus      New status
     */
    public function onStatusChanged(int $loanId, string $previousStatus, string $newStatus): void;

    /**
     * Get sector-specific workflow states.
     *
     * @return array<string, array> State code => state configuration
     */
    public function getWorkflowStates(): array;

    /**
     * Get sector-specific workflow transitions.
     *
     * @return array<string, array> Transition code => transition configuration
     */
    public function getWorkflowTransitions(): array;

    /**
     * Get sector-specific document types.
     *
     * @return array<string, string> Type code => label
     */
    public function getDocumentTypes(): array;

    /**
     * Check if condition reports are required for this sector.
     */
    public function requiresConditionReport(): bool;

    /**
     * Check if facility reports are required for this sector.
     */
    public function requiresFacilityReport(): bool;

    /**
     * Get the default loan duration in days for this sector.
     */
    public function getDefaultLoanDuration(): int;
}
