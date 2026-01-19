<?php

declare(strict_types=1);

namespace AhgLoan\Adapters;

/**
 * Museum Sector Adapter.
 *
 * Implements loan behavior specific to museums:
 * - Spectrum 5.0 compliant workflows
 * - Condition reports required
 * - Facility reports for borrowers
 * - Insurance mandatory
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class MuseumAdapter implements SectorAdapterInterface
{
    /**
     * {@inheritDoc}
     */
    public function getSectorCode(): string
    {
        return 'museum';
    }

    /**
     * {@inheritDoc}
     */
    public function getSectorName(): string
    {
        return 'Museum';
    }

    /**
     * {@inheritDoc}
     */
    public function getPurposes(): array
    {
        return [
            'exhibition' => 'Exhibition/Display',
            'research' => 'Research/Study',
            'conservation' => 'Conservation Treatment',
            'photography' => 'Photography/Imaging',
            'education' => 'Educational Program',
            'filming' => 'Film/Television Production',
            'long_term' => 'Long-term Display',
            'touring' => 'Touring Exhibition',
            'other' => 'Other',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function validateLoanData(array $data): array
    {
        // Museums require insurance for loans out
        if (empty($data['insurance_value']) && 'out' === ($data['loan_type'] ?? 'out')) {
            // Allow but log warning - don't block creation
        }

        // Ensure partner institution is provided
        if (empty($data['partner_institution'])) {
            throw new \InvalidArgumentException('Partner institution is required for museum loans');
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function enrichLoanData(array $data): array
    {
        // Add museum-specific labels
        $data['sector_label'] = 'Museum Loan';
        $data['requires_courier'] = ($data['insurance_value'] ?? 0) > 100000;

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function enrichObjectData(array $data): array
    {
        // Add museum-specific object info
        $data['requires_climate_control'] = true;
        $data['handling_level'] = 'professional';

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function onLoanCreated(int $loanId, array $data): void
    {
        // Museum-specific post-creation actions
        // e.g., Auto-create condition report requirement
    }

    /**
     * {@inheritDoc}
     */
    public function onStatusChanged(int $loanId, string $previousStatus, string $newStatus): void
    {
        // Museum-specific status change actions
        // e.g., Notify curator when approved
    }

    /**
     * {@inheritDoc}
     */
    public function getWorkflowStates(): array
    {
        return [
            'draft' => ['label' => 'Draft', 'color' => 'secondary', 'phase' => 'request'],
            'submitted' => ['label' => 'Submitted', 'color' => 'info', 'phase' => 'request'],
            'under_review' => ['label' => 'Under Review', 'color' => 'warning', 'phase' => 'request'],
            'approved' => ['label' => 'Approved', 'color' => 'success', 'phase' => 'preparation'],
            'rejected' => ['label' => 'Rejected', 'color' => 'danger', 'phase' => 'closed'],
            'agreement_pending' => ['label' => 'Agreement Pending', 'color' => 'warning', 'phase' => 'preparation'],
            'agreement_signed' => ['label' => 'Agreement Signed', 'color' => 'success', 'phase' => 'preparation'],
            'insurance_pending' => ['label' => 'Insurance Pending', 'color' => 'warning', 'phase' => 'preparation'],
            'insurance_confirmed' => ['label' => 'Insurance Confirmed', 'color' => 'success', 'phase' => 'preparation'],
            'condition_check' => ['label' => 'Condition Check', 'color' => 'info', 'phase' => 'preparation'],
            'packing' => ['label' => 'Packing', 'color' => 'info', 'phase' => 'dispatch'],
            'dispatched' => ['label' => 'Dispatched', 'color' => 'primary', 'phase' => 'transit'],
            'in_transit' => ['label' => 'In Transit', 'color' => 'info', 'phase' => 'transit'],
            'received' => ['label' => 'Received by Borrower', 'color' => 'success', 'phase' => 'on_loan'],
            'on_display' => ['label' => 'On Display', 'color' => 'primary', 'phase' => 'on_loan'],
            'return_initiated' => ['label' => 'Return Initiated', 'color' => 'info', 'phase' => 'return'],
            'return_in_transit' => ['label' => 'Return In Transit', 'color' => 'info', 'phase' => 'return'],
            'returned' => ['label' => 'Returned', 'color' => 'success', 'phase' => 'return'],
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
            'submit' => ['from' => ['draft'], 'to' => 'submitted', 'label' => 'Submit for Review'],
            'review' => ['from' => ['submitted'], 'to' => 'under_review', 'label' => 'Start Review'],
            'approve' => ['from' => ['under_review'], 'to' => 'approved', 'label' => 'Approve'],
            'reject' => ['from' => ['submitted', 'under_review'], 'to' => 'rejected', 'label' => 'Reject'],
            'send_agreement' => ['from' => ['approved'], 'to' => 'agreement_pending', 'label' => 'Send Agreement'],
            'sign_agreement' => ['from' => ['agreement_pending'], 'to' => 'agreement_signed', 'label' => 'Agreement Signed'],
            'request_insurance' => ['from' => ['agreement_signed'], 'to' => 'insurance_pending', 'label' => 'Request Insurance'],
            'confirm_insurance' => ['from' => ['insurance_pending'], 'to' => 'insurance_confirmed', 'label' => 'Confirm Insurance'],
            'start_condition' => ['from' => ['insurance_confirmed'], 'to' => 'condition_check', 'label' => 'Start Condition Check'],
            'start_packing' => ['from' => ['condition_check'], 'to' => 'packing', 'label' => 'Start Packing'],
            'dispatch' => ['from' => ['packing'], 'to' => 'dispatched', 'label' => 'Dispatch'],
            'in_transit' => ['from' => ['dispatched'], 'to' => 'in_transit', 'label' => 'Mark In Transit'],
            'confirm_receipt' => ['from' => ['in_transit'], 'to' => 'received', 'label' => 'Confirm Receipt'],
            'put_on_display' => ['from' => ['received'], 'to' => 'on_display', 'label' => 'Put On Display'],
            'initiate_return' => ['from' => ['on_display', 'received'], 'to' => 'return_initiated', 'label' => 'Initiate Return'],
            'dispatch_return' => ['from' => ['return_initiated'], 'to' => 'return_in_transit', 'label' => 'Dispatch Return'],
            'receive_return' => ['from' => ['return_in_transit'], 'to' => 'returned', 'label' => 'Receive Return'],
            'close' => ['from' => ['returned'], 'to' => 'closed', 'label' => 'Close Loan'],
            'cancel' => ['from' => ['draft', 'submitted', 'under_review', 'approved'], 'to' => 'cancelled', 'label' => 'Cancel'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentTypes(): array
    {
        return [
            'agreement' => 'Loan Agreement',
            'condition_report' => 'Condition Report',
            'insurance' => 'Insurance Certificate',
            'facility_report' => 'Facility Report',
            'courier' => 'Courier/Shipping Documents',
            'packing_list' => 'Packing List',
            'receipt' => 'Receipt/Acknowledgment',
            'correspondence' => 'Correspondence',
            'photography' => 'Photography Release',
            'other' => 'Other',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function requiresConditionReport(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function requiresFacilityReport(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultLoanDuration(): int
    {
        return 180; // 6 months
    }
}
