<?php

declare(strict_types=1);

namespace AhgLoan\Adapters;

/**
 * Archive Sector Adapter.
 *
 * Implements loan behavior specific to archives:
 * - Restricted access to originals
 * - Surrogate/copy lending preferred
 * - Research access management
 * - Reading room loans
 * - PAIA compliance (South Africa)
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class ArchiveAdapter implements SectorAdapterInterface
{
    /**
     * {@inheritDoc}
     */
    public function getSectorCode(): string
    {
        return 'archive';
    }

    /**
     * {@inheritDoc}
     */
    public function getSectorName(): string
    {
        return 'Archive';
    }

    /**
     * {@inheritDoc}
     */
    public function getPurposes(): array
    {
        return [
            'research' => 'Research Access',
            'exhibition' => 'Exhibition (Surrogate Preferred)',
            'publication' => 'Publication',
            'legal' => 'Legal/Evidentiary',
            'government' => 'Government Request',
            'repatriation' => 'Repatriation',
            'conservation' => 'Conservation Treatment',
            'digitization' => 'Digitization Project',
            'education' => 'Educational Program',
            'filming' => 'Documentary/Film',
            'other' => 'Other',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function validateLoanData(array $data): array
    {
        // Archives prefer surrogates
        $data['sector_data'] = $data['sector_data'] ?? [];

        // Check if original or surrogate
        if (empty($data['sector_data']['material_type'])) {
            $data['sector_data']['material_type'] = 'original';
        }

        // Warn if loaning originals
        if ('original' === $data['sector_data']['material_type']) {
            $data['sector_data']['original_warning'] = true;
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function enrichLoanData(array $data): array
    {
        $data['sector_label'] = 'Archival Loan';

        $sectorData = $data['sector_data'] ?? [];
        $data['material_type'] = $sectorData['material_type'] ?? 'original';
        $data['is_original'] = 'original' === $data['material_type'];
        $data['requires_reading_room'] = $sectorData['requires_reading_room'] ?? false;

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function enrichObjectData(array $data): array
    {
        $data['is_archival_material'] = true;
        $data['handling_level'] = 'archivist';
        $data['requires_gloves'] = true;

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function onLoanCreated(int $loanId, array $data): void
    {
        // Archive-specific actions
        // e.g., Check access restrictions, PAIA requirements
    }

    /**
     * {@inheritDoc}
     */
    public function onStatusChanged(int $loanId, string $previousStatus, string $newStatus): void
    {
        // Archive-specific status actions
    }

    /**
     * {@inheritDoc}
     */
    public function getWorkflowStates(): array
    {
        return [
            'draft' => ['label' => 'Draft', 'color' => 'secondary', 'phase' => 'request'],
            'submitted' => ['label' => 'Submitted', 'color' => 'info', 'phase' => 'request'],
            'access_review' => ['label' => 'Access Review', 'color' => 'warning', 'phase' => 'request'],
            'approved' => ['label' => 'Approved', 'color' => 'success', 'phase' => 'preparation'],
            'access_denied' => ['label' => 'Access Denied', 'color' => 'danger', 'phase' => 'closed'],
            'agreement_pending' => ['label' => 'Agreement Pending', 'color' => 'warning', 'phase' => 'preparation'],
            'preparing' => ['label' => 'Preparing Materials', 'color' => 'info', 'phase' => 'preparation'],
            'in_reading_room' => ['label' => 'In Reading Room', 'color' => 'primary', 'phase' => 'on_loan'],
            'dispatched' => ['label' => 'Dispatched', 'color' => 'info', 'phase' => 'transit'],
            'on_loan' => ['label' => 'On Loan', 'color' => 'primary', 'phase' => 'on_loan'],
            'return_requested' => ['label' => 'Return Requested', 'color' => 'warning', 'phase' => 'return'],
            'returned' => ['label' => 'Returned', 'color' => 'success', 'phase' => 'closed'],
            'closed' => ['label' => 'Closed', 'color' => 'secondary', 'phase' => 'closed'],
            'cancelled' => ['label' => 'Cancelled', 'color' => 'danger', 'phase' => 'closed'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getWorkflowTransitions(): array
    {
        return [
            'submit' => ['from' => ['draft'], 'to' => 'submitted', 'label' => 'Submit Request'],
            'review_access' => ['from' => ['submitted'], 'to' => 'access_review', 'label' => 'Review Access Rights'],
            'approve' => ['from' => ['access_review'], 'to' => 'approved', 'label' => 'Approve Access'],
            'deny_access' => ['from' => ['access_review'], 'to' => 'access_denied', 'label' => 'Deny Access'],
            'send_agreement' => ['from' => ['approved'], 'to' => 'agreement_pending', 'label' => 'Send Agreement'],
            'prepare' => ['from' => ['agreement_pending'], 'to' => 'preparing', 'label' => 'Start Preparation'],
            'to_reading_room' => ['from' => ['preparing'], 'to' => 'in_reading_room', 'label' => 'To Reading Room'],
            'dispatch' => ['from' => ['preparing'], 'to' => 'dispatched', 'label' => 'Dispatch'],
            'confirm_receipt' => ['from' => ['dispatched'], 'to' => 'on_loan', 'label' => 'Confirm Receipt'],
            'request_return' => ['from' => ['on_loan', 'in_reading_room'], 'to' => 'return_requested', 'label' => 'Request Return'],
            'receive_return' => ['from' => ['return_requested'], 'to' => 'returned', 'label' => 'Receive Return'],
            'close' => ['from' => ['returned', 'in_reading_room'], 'to' => 'closed', 'label' => 'Close'],
            'cancel' => ['from' => ['draft', 'submitted', 'access_review', 'approved'], 'to' => 'cancelled', 'label' => 'Cancel'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentTypes(): array
    {
        return [
            'agreement' => 'Loan Agreement',
            'access_form' => 'Access Request Form',
            'condition_report' => 'Condition Report',
            'paia_request' => 'PAIA Request',
            'reproduction_form' => 'Reproduction Request',
            'reading_room_register' => 'Reading Room Register',
            'insurance' => 'Insurance Certificate',
            'correspondence' => 'Correspondence',
            'other' => 'Other',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function requiresConditionReport(): bool
    {
        return true; // Required for originals
    }

    /**
     * {@inheritDoc}
     */
    public function requiresFacilityReport(): bool
    {
        return true; // Required for loans of originals
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultLoanDuration(): int
    {
        return 30; // 30 days - archives typically have shorter loans
    }

    /**
     * Get material types specific to archives.
     */
    public function getMaterialTypes(): array
    {
        return [
            'original' => 'Original Document',
            'certified_copy' => 'Certified Copy',
            'photocopy' => 'Photocopy',
            'digital_copy' => 'Digital Copy',
            'microfilm' => 'Microfilm/Microfiche',
            'facsimile' => 'Facsimile',
        ];
    }

    /**
     * Get access levels for archival materials.
     */
    public function getAccessLevels(): array
    {
        return [
            'open' => 'Open Access',
            'restricted' => 'Restricted',
            'closed' => 'Closed',
            'paia_required' => 'PAIA Application Required',
            'permission_required' => 'Permission Required',
        ];
    }
}
