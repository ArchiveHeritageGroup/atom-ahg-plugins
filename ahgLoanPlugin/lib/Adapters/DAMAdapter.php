<?php

declare(strict_types=1);

namespace AhgLoan\Adapters;

/**
 * Digital Asset Management (DAM) Sector Adapter.
 *
 * Implements loan behavior specific to digital assets:
 * - Licensing rather than physical loans
 * - Usage rights tracking
 * - Download/access management
 * - No physical shipping required
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class DAMAdapter implements SectorAdapterInterface
{
    /**
     * {@inheritDoc}
     */
    public function getSectorCode(): string
    {
        return 'dam';
    }

    /**
     * {@inheritDoc}
     */
    public function getSectorName(): string
    {
        return 'Digital Assets';
    }

    /**
     * {@inheritDoc}
     */
    public function getPurposes(): array
    {
        return [
            'publication' => 'Publication (Print)',
            'digital_publication' => 'Digital Publication',
            'website' => 'Website Use',
            'social_media' => 'Social Media',
            'exhibition' => 'Exhibition/Display',
            'education' => 'Educational Use',
            'research' => 'Research',
            'commercial' => 'Commercial Use',
            'broadcast' => 'Broadcast/Film',
            'merchandise' => 'Merchandise/Products',
            'internal' => 'Internal Use Only',
            'other' => 'Other',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function validateLoanData(array $data): array
    {
        // DAM "loans" are really licenses
        $data['sector_data'] = $data['sector_data'] ?? [];

        // Validate license terms
        if (empty($data['sector_data']['license_type'])) {
            $data['sector_data']['license_type'] = 'limited';
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function enrichLoanData(array $data): array
    {
        $data['sector_label'] = 'Digital Asset License';
        $data['is_digital'] = true;
        $data['requires_shipping'] = false;

        $sectorData = $data['sector_data'] ?? [];
        $data['license_type'] = $sectorData['license_type'] ?? 'limited';
        $data['usage_territory'] = $sectorData['usage_territory'] ?? 'worldwide';
        $data['usage_medium'] = $sectorData['usage_medium'] ?? 'all';

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function enrichObjectData(array $data): array
    {
        $data['is_digital_asset'] = true;
        $data['requires_download_link'] = true;
        $data['handling_level'] = 'digital';

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function onLoanCreated(int $loanId, array $data): void
    {
        // DAM-specific actions
        // e.g., Generate download link, track license usage
    }

    /**
     * {@inheritDoc}
     */
    public function onStatusChanged(int $loanId, string $previousStatus, string $newStatus): void
    {
        // DAM-specific status actions
        // e.g., Enable/disable download access
    }

    /**
     * {@inheritDoc}
     */
    public function getWorkflowStates(): array
    {
        return [
            'draft' => ['label' => 'Draft', 'color' => 'secondary', 'phase' => 'request'],
            'requested' => ['label' => 'License Requested', 'color' => 'info', 'phase' => 'request'],
            'under_review' => ['label' => 'Under Review', 'color' => 'warning', 'phase' => 'request'],
            'approved' => ['label' => 'Approved', 'color' => 'success', 'phase' => 'preparation'],
            'rejected' => ['label' => 'Rejected', 'color' => 'danger', 'phase' => 'closed'],
            'agreement_pending' => ['label' => 'License Agreement Pending', 'color' => 'warning', 'phase' => 'preparation'],
            'payment_pending' => ['label' => 'Payment Pending', 'color' => 'warning', 'phase' => 'preparation'],
            'active' => ['label' => 'License Active', 'color' => 'success', 'phase' => 'active'],
            'expired' => ['label' => 'License Expired', 'color' => 'secondary', 'phase' => 'closed'],
            'revoked' => ['label' => 'License Revoked', 'color' => 'danger', 'phase' => 'closed'],
            'cancelled' => ['label' => 'Cancelled', 'color' => 'danger', 'phase' => 'closed'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getWorkflowTransitions(): array
    {
        return [
            'submit' => ['from' => ['draft'], 'to' => 'requested', 'label' => 'Submit Request'],
            'review' => ['from' => ['requested'], 'to' => 'under_review', 'label' => 'Start Review'],
            'approve' => ['from' => ['under_review'], 'to' => 'approved', 'label' => 'Approve'],
            'reject' => ['from' => ['requested', 'under_review'], 'to' => 'rejected', 'label' => 'Reject'],
            'send_agreement' => ['from' => ['approved'], 'to' => 'agreement_pending', 'label' => 'Send Agreement'],
            'agreement_signed' => ['from' => ['agreement_pending'], 'to' => 'payment_pending', 'label' => 'Agreement Signed'],
            'skip_payment' => ['from' => ['agreement_pending'], 'to' => 'active', 'label' => 'No Payment Required'],
            'payment_received' => ['from' => ['payment_pending'], 'to' => 'active', 'label' => 'Payment Received'],
            'expire' => ['from' => ['active'], 'to' => 'expired', 'label' => 'License Expired'],
            'revoke' => ['from' => ['active'], 'to' => 'revoked', 'label' => 'Revoke License'],
            'renew' => ['from' => ['expired'], 'to' => 'active', 'label' => 'Renew License'],
            'cancel' => ['from' => ['draft', 'requested', 'under_review', 'approved', 'agreement_pending'], 'to' => 'cancelled', 'label' => 'Cancel'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentTypes(): array
    {
        return [
            'license_agreement' => 'License Agreement',
            'invoice' => 'Invoice',
            'receipt' => 'Payment Receipt',
            'usage_report' => 'Usage Report',
            'correspondence' => 'Correspondence',
            'credit_line' => 'Credit Line Requirements',
            'rights_clearance' => 'Rights Clearance',
            'other' => 'Other',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function requiresConditionReport(): bool
    {
        return false; // Digital assets don't need condition reports
    }

    /**
     * {@inheritDoc}
     */
    public function requiresFacilityReport(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultLoanDuration(): int
    {
        return 365; // 1 year license
    }

    /**
     * Get license types specific to DAM.
     */
    public function getLicenseTypes(): array
    {
        return [
            'limited' => 'Limited Use',
            'exclusive' => 'Exclusive',
            'non_exclusive' => 'Non-Exclusive',
            'royalty_free' => 'Royalty-Free',
            'rights_managed' => 'Rights-Managed',
            'editorial' => 'Editorial Use Only',
            'commercial' => 'Commercial',
            'educational' => 'Educational',
        ];
    }

    /**
     * Get usage territories.
     */
    public function getUsageTerritories(): array
    {
        return [
            'worldwide' => 'Worldwide',
            'africa' => 'Africa',
            'south_africa' => 'South Africa Only',
            'sadc' => 'SADC Region',
            'europe' => 'Europe',
            'north_america' => 'North America',
            'specific' => 'Specific Territory (see agreement)',
        ];
    }
}
