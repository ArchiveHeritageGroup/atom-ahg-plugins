<?php

declare(strict_types=1);

namespace AhgLoan\Adapters;

/**
 * Gallery Sector Adapter.
 *
 * Implements loan behavior specific to galleries:
 * - Artwork-focused workflows
 * - Sales consideration tracking
 * - Artist relationship management
 * - Consignment support
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class GalleryAdapter implements SectorAdapterInterface
{
    /**
     * {@inheritDoc}
     */
    public function getSectorCode(): string
    {
        return 'gallery';
    }

    /**
     * {@inheritDoc}
     */
    public function getSectorName(): string
    {
        return 'Gallery';
    }

    /**
     * {@inheritDoc}
     */
    public function getPurposes(): array
    {
        return [
            'exhibition' => 'Exhibition',
            'art_fair' => 'Art Fair',
            'consignment' => 'Consignment/Sale',
            'photography' => 'Photography/Catalogue',
            'restoration' => 'Restoration/Conservation',
            'framing' => 'Framing/Mounting',
            'valuation' => 'Valuation/Appraisal',
            'publication' => 'Publication/Reproduction',
            'filming' => 'Film/Media',
            'private_view' => 'Private Viewing',
            'other' => 'Other',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function validateLoanData(array $data): array
    {
        // Galleries may have consignment arrangements
        if ('consignment' === ($data['purpose'] ?? '')) {
            // Ensure commission rate is set
            $data['sector_data'] = $data['sector_data'] ?? [];
            $data['sector_data']['is_consignment'] = true;
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function enrichLoanData(array $data): array
    {
        $data['sector_label'] = 'Gallery Loan';

        // Check if this is a consignment
        $sectorData = $data['sector_data'] ?? [];
        $data['is_consignment'] = $sectorData['is_consignment'] ?? false;

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function enrichObjectData(array $data): array
    {
        // Gallery-specific - artwork details
        $data['is_artwork'] = true;
        $data['handling_level'] = 'art_handler';

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function onLoanCreated(int $loanId, array $data): void
    {
        // Gallery-specific actions
        // e.g., Notify artist if their work is being loaned
    }

    /**
     * {@inheritDoc}
     */
    public function onStatusChanged(int $loanId, string $previousStatus, string $newStatus): void
    {
        // Gallery-specific status actions
    }

    /**
     * {@inheritDoc}
     */
    public function getWorkflowStates(): array
    {
        return [
            'draft' => ['label' => 'Draft', 'color' => 'secondary', 'phase' => 'request'],
            'inquiry' => ['label' => 'Inquiry', 'color' => 'info', 'phase' => 'request'],
            'requested' => ['label' => 'Requested', 'color' => 'warning', 'phase' => 'request'],
            'approved' => ['label' => 'Approved', 'color' => 'success', 'phase' => 'preparation'],
            'declined' => ['label' => 'Declined', 'color' => 'danger', 'phase' => 'closed'],
            'agreed' => ['label' => 'Agreement Signed', 'color' => 'success', 'phase' => 'preparation'],
            'preparing' => ['label' => 'Preparing', 'color' => 'info', 'phase' => 'preparation'],
            'in_transit_out' => ['label' => 'In Transit (Out)', 'color' => 'info', 'phase' => 'transit'],
            'on_loan' => ['label' => 'On Loan', 'color' => 'primary', 'phase' => 'on_loan'],
            'on_display' => ['label' => 'On Display', 'color' => 'success', 'phase' => 'on_loan'],
            'sold' => ['label' => 'Sold', 'color' => 'success', 'phase' => 'closed'],
            'in_transit_return' => ['label' => 'In Transit (Return)', 'color' => 'info', 'phase' => 'return'],
            'returned' => ['label' => 'Returned', 'color' => 'success', 'phase' => 'closed'],
            'cancelled' => ['label' => 'Cancelled', 'color' => 'danger', 'phase' => 'closed'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getWorkflowTransitions(): array
    {
        return [
            'inquire' => ['from' => ['draft'], 'to' => 'inquiry', 'label' => 'Submit Inquiry'],
            'request' => ['from' => ['inquiry'], 'to' => 'requested', 'label' => 'Formal Request'],
            'approve' => ['from' => ['requested'], 'to' => 'approved', 'label' => 'Approve'],
            'decline' => ['from' => ['inquiry', 'requested'], 'to' => 'declined', 'label' => 'Decline'],
            'sign_agreement' => ['from' => ['approved'], 'to' => 'agreed', 'label' => 'Sign Agreement'],
            'prepare' => ['from' => ['agreed'], 'to' => 'preparing', 'label' => 'Start Preparation'],
            'dispatch' => ['from' => ['preparing'], 'to' => 'in_transit_out', 'label' => 'Dispatch'],
            'receive' => ['from' => ['in_transit_out'], 'to' => 'on_loan', 'label' => 'Confirm Receipt'],
            'display' => ['from' => ['on_loan'], 'to' => 'on_display', 'label' => 'Put On Display'],
            'mark_sold' => ['from' => ['on_loan', 'on_display'], 'to' => 'sold', 'label' => 'Mark as Sold'],
            'initiate_return' => ['from' => ['on_loan', 'on_display'], 'to' => 'in_transit_return', 'label' => 'Return'],
            'complete_return' => ['from' => ['in_transit_return'], 'to' => 'returned', 'label' => 'Complete Return'],
            'cancel' => ['from' => ['draft', 'inquiry', 'requested', 'approved'], 'to' => 'cancelled', 'label' => 'Cancel'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentTypes(): array
    {
        return [
            'agreement' => 'Loan Agreement',
            'consignment' => 'Consignment Agreement',
            'condition_report' => 'Condition Report',
            'insurance' => 'Insurance Certificate',
            'invoice' => 'Invoice',
            'receipt' => 'Receipt',
            'artist_approval' => 'Artist Approval',
            'photography' => 'Photography',
            'provenance' => 'Provenance Documentation',
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
        return false; // Galleries typically don't require facility reports
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultLoanDuration(): int
    {
        return 90; // 3 months
    }
}
