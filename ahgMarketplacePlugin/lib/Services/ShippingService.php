<?php

namespace AtomAhgPlugins\ahgMarketplacePlugin\Services;

require_once dirname(__DIR__) . '/Repositories/ListingRepository.php';
require_once dirname(__DIR__) . '/Repositories/TransactionRepository.php';
require_once dirname(__DIR__) . '/Repositories/SettingsRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\ListingRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\TransactionRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SettingsRepository;

class ShippingService
{
    private ListingRepository $listingRepo;
    private TransactionRepository $txnRepo;
    private SettingsRepository $settingsRepo;

    public function __construct()
    {
        $this->listingRepo = new ListingRepository();
        $this->txnRepo = new TransactionRepository();
        $this->settingsRepo = new SettingsRepository();
    }

    // =========================================================================
    // Shipping Estimates
    // =========================================================================

    public function getShippingEstimate(int $listingId, string $country): array
    {
        $listing = $this->listingRepo->getById($listingId);
        if (!$listing) {
            return ['success' => false, 'error' => 'Listing not found'];
        }

        if ($listing->is_digital) {
            return [
                'success' => true,
                'type' => 'digital',
                'cost' => 0,
                'currency' => $listing->currency,
                'description' => 'Digital delivery - no shipping required',
            ];
        }

        if (!$listing->requires_shipping) {
            return [
                'success' => true,
                'type' => 'collection',
                'cost' => 0,
                'currency' => $listing->currency,
                'description' => 'Collection only - no shipping available',
            ];
        }

        // Determine if domestic or international
        $sellerCountry = $listing->shipping_from_country ?? $this->settingsRepo->get('default_country', 'ZA');
        $isDomestic = strtoupper($country) === strtoupper($sellerCountry);

        if ($isDomestic) {
            $cost = (float) ($listing->shipping_domestic_price ?? 0);
            $type = 'domestic';
            $description = 'Domestic shipping';
        } else {
            $cost = (float) ($listing->shipping_international_price ?? 0);
            $type = 'international';
            $description = 'International shipping to ' . strtoupper($country);

            if ($cost <= 0) {
                return [
                    'success' => false,
                    'error' => 'International shipping not available for this listing',
                ];
            }
        }

        return [
            'success' => true,
            'type' => $type,
            'cost' => $cost,
            'currency' => $listing->currency,
            'description' => $description,
            'from_country' => $sellerCountry,
            'to_country' => strtoupper($country),
        ];
    }

    // =========================================================================
    // Tracking
    // =========================================================================

    public function updateTracking(int $txnId, string $trackingNumber, string $courier): array
    {
        $txn = $this->txnRepo->getById($txnId);
        if (!$txn) {
            return ['success' => false, 'error' => 'Transaction not found'];
        }

        if (!in_array($txn->status, ['paid', 'shipping'])) {
            return ['success' => false, 'error' => 'Transaction is not in a shippable status'];
        }

        $this->txnRepo->update($txnId, [
            'tracking_number' => $trackingNumber,
            'courier' => $courier,
            'shipping_status' => 'shipped',
            'shipped_at' => date('Y-m-d H:i:s'),
            'status' => 'shipping',
        ]);

        return ['success' => true];
    }

    public function getTrackingInfo(int $txnId): ?array
    {
        $txn = $this->txnRepo->getById($txnId);
        if (!$txn) {
            return null;
        }

        return [
            'transaction_id' => $txn->id,
            'transaction_number' => $txn->transaction_number,
            'tracking_number' => $txn->tracking_number ?? null,
            'courier' => $txn->courier ?? null,
            'shipping_status' => $txn->shipping_status ?? 'pending',
            'shipped_at' => $txn->shipped_at ?? null,
            'delivered_at' => $txn->delivered_at ?? null,
            'buyer_confirmed_receipt' => (bool) ($txn->buyer_confirmed_receipt ?? false),
            'receipt_confirmed_at' => $txn->receipt_confirmed_at ?? null,
        ];
    }

    public function confirmDelivery(int $txnId): array
    {
        $txn = $this->txnRepo->getById($txnId);
        if (!$txn) {
            return ['success' => false, 'error' => 'Transaction not found'];
        }

        if ($txn->shipping_status === 'delivered') {
            return ['success' => false, 'error' => 'Delivery already confirmed'];
        }

        if (!in_array($txn->status, ['shipping', 'paid'])) {
            return ['success' => false, 'error' => 'Transaction is not in a shippable status'];
        }

        $this->txnRepo->update($txnId, [
            'shipping_status' => 'delivered',
            'delivered_at' => date('Y-m-d H:i:s'),
            'status' => 'delivered',
        ]);

        return ['success' => true];
    }
}
