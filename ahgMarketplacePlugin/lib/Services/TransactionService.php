<?php

namespace AtomAhgPlugins\ahgMarketplacePlugin\Services;

require_once dirname(__DIR__) . '/Repositories/TransactionRepository.php';
require_once dirname(__DIR__) . '/Repositories/SellerRepository.php';
require_once dirname(__DIR__) . '/Repositories/ListingRepository.php';
require_once dirname(__DIR__) . '/Repositories/SettingsRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\TransactionRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SellerRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\ListingRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SettingsRepository;

class TransactionService
{
    private TransactionRepository $txnRepo;
    private SellerRepository $sellerRepo;
    private ListingRepository $listingRepo;
    private SettingsRepository $settingsRepo;

    public function __construct()
    {
        $this->txnRepo = new TransactionRepository();
        $this->sellerRepo = new SellerRepository();
        $this->listingRepo = new ListingRepository();
        $this->settingsRepo = new SettingsRepository();
    }

    public function createFromFixedPrice(int $listingId, int $buyerId): array
    {
        $listing = $this->listingRepo->getById($listingId);
        if (!$listing || $listing->status !== 'active') {
            return ['success' => false, 'error' => 'Listing is not available'];
        }

        return $this->createTransaction($listing, $buyerId, 'fixed_price', $listing->price);
    }

    public function createFromOffer(int $offerId, int $buyerId): array
    {
        require_once dirname(__DIR__) . '/Repositories/OfferRepository.php';
        $offerRepo = new \AtomAhgPlugins\ahgMarketplacePlugin\Repositories\OfferRepository();
        $offer = $offerRepo->getById($offerId);

        if (!$offer || $offer->status !== 'accepted') {
            return ['success' => false, 'error' => 'Offer is not accepted'];
        }

        $listing = $this->listingRepo->getById($offer->listing_id);
        if (!$listing) {
            return ['success' => false, 'error' => 'Listing not found'];
        }

        $price = $offer->counter_amount ?? $offer->offer_amount;

        return $this->createTransaction($listing, $buyerId, 'offer', $price, $offerId);
    }

    public function createFromAuction(int $auctionId): array
    {
        require_once dirname(__DIR__) . '/Repositories/AuctionRepository.php';
        $auctionRepo = new \AtomAhgPlugins\ahgMarketplacePlugin\Repositories\AuctionRepository();
        $auction = $auctionRepo->getById($auctionId);

        if (!$auction || $auction->status !== 'ended' || !$auction->winner_id) {
            return ['success' => false, 'error' => 'Auction has no winner'];
        }

        $listing = $this->listingRepo->getById($auction->listing_id);
        if (!$listing) {
            return ['success' => false, 'error' => 'Listing not found'];
        }

        return $this->createTransaction($listing, $auction->winner_id, 'auction', $auction->winning_bid, null, $auctionId);
    }

    private function createTransaction(object $listing, int $buyerId, string $source, float $salePrice, ?int $offerId = null, ?int $auctionId = null): array
    {
        $seller = $this->sellerRepo->getById($listing->seller_id);
        if (!$seller) {
            return ['success' => false, 'error' => 'Seller not found'];
        }

        // Calculate commission
        $commissionRate = $seller->commission_rate ?? (float) $this->settingsRepo->get('default_commission_rate', 10);
        $commissionAmount = round($salePrice * ($commissionRate / 100), 2);
        $sellerAmount = round($salePrice - $commissionAmount, 2);

        // Calculate VAT
        $vatRate = (float) $this->settingsRepo->get('vat_rate', 15);
        $vatAmount = round($salePrice - ($salePrice / (1 + ($vatRate / 100))), 2);
        $totalWithVat = $salePrice; // Prices include VAT

        // Shipping
        $shippingCost = $listing->requires_shipping ? ($listing->shipping_domestic_price ?? 0) : 0;
        $insuranceCost = 0;
        $grandTotal = round($totalWithVat + $shippingCost + $insuranceCost, 2);

        $txnId = $this->txnRepo->create([
            'transaction_number' => $this->txnRepo->generateTransactionNumber(),
            'listing_id' => $listing->id,
            'seller_id' => $listing->seller_id,
            'buyer_id' => $buyerId,
            'source' => $source,
            'offer_id' => $offerId,
            'auction_id' => $auctionId,
            'sale_price' => $salePrice,
            'currency' => $listing->currency,
            'platform_commission_rate' => $commissionRate,
            'platform_commission_amount' => $commissionAmount,
            'seller_amount' => $sellerAmount,
            'vat_amount' => $vatAmount,
            'total_with_vat' => $totalWithVat,
            'shipping_cost' => $shippingCost,
            'insurance_cost' => $insuranceCost,
            'grand_total' => $grandTotal,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Mark listing as reserved/sold
        $this->listingRepo->update($listing->id, ['status' => 'reserved']);

        return [
            'success' => true,
            'transaction_id' => $txnId,
            'transaction' => $this->txnRepo->getById($txnId),
        ];
    }

    public function markPaid(int $txnId, string $gateway, string $gatewayTxnId, ?array $gatewayResponse = null): array
    {
        $txn = $this->txnRepo->getById($txnId);
        if (!$txn) {
            return ['success' => false, 'error' => 'Transaction not found'];
        }

        $this->txnRepo->update($txnId, [
            'payment_status' => 'paid',
            'payment_gateway' => $gateway,
            'payment_transaction_id' => $gatewayTxnId,
            'gateway_response' => $gatewayResponse ? json_encode($gatewayResponse) : null,
            'paid_at' => date('Y-m-d H:i:s'),
            'status' => 'paid',
        ]);

        // Mark listing as sold
        $this->listingRepo->update($txn->listing_id, [
            'status' => 'sold',
            'sold_at' => date('Y-m-d H:i:s'),
        ]);

        // Update seller stats
        $this->sellerRepo->incrementSales($txn->seller_id, $txn->seller_amount);

        return ['success' => true];
    }

    public function updateShipping(int $txnId, array $data): array
    {
        $txn = $this->txnRepo->getById($txnId);
        if (!$txn) {
            return ['success' => false, 'error' => 'Transaction not found'];
        }

        $updateData = [];
        if (isset($data['tracking_number'])) {
            $updateData['tracking_number'] = $data['tracking_number'];
        }
        if (isset($data['courier'])) {
            $updateData['courier'] = $data['courier'];
        }
        if (isset($data['shipping_status'])) {
            $updateData['shipping_status'] = $data['shipping_status'];
            if ($data['shipping_status'] === 'shipped') {
                $updateData['shipped_at'] = date('Y-m-d H:i:s');
                $updateData['status'] = 'shipping';
            } elseif ($data['shipping_status'] === 'delivered') {
                $updateData['delivered_at'] = date('Y-m-d H:i:s');
                $updateData['status'] = 'delivered';
            }
        }

        $this->txnRepo->update($txnId, $updateData);

        return ['success' => true];
    }

    public function confirmReceipt(int $txnId, int $buyerId): array
    {
        $txn = $this->txnRepo->getById($txnId);
        if (!$txn || $txn->buyer_id != $buyerId) {
            return ['success' => false, 'error' => 'Transaction not found'];
        }

        $this->txnRepo->update($txnId, [
            'buyer_confirmed_receipt' => 1,
            'receipt_confirmed_at' => date('Y-m-d H:i:s'),
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
        ]);

        // Create pending payout
        $coolingDays = (int) $this->settingsRepo->get('payout_cooling_period_days', 5);

        $this->txnRepo->createPayout([
            'seller_id' => $txn->seller_id,
            'transaction_id' => $txnId,
            'payout_number' => $this->txnRepo->generatePayoutNumber(),
            'amount' => $txn->seller_amount,
            'currency' => $txn->currency,
            'method' => 'bank_transfer', // Will be overridden by seller preference
            'status' => 'pending',
            'notes' => "Auto-created on receipt confirmation. Release after {$coolingDays}-day cooling period.",
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true];
    }

    public function getTransaction(int $id): ?object
    {
        return $this->txnRepo->getTransactionWithDetails($id);
    }

    public function getBuyerTransactions(int $userId, int $limit = 50, int $offset = 0): array
    {
        return $this->txnRepo->getBuyerTransactions($userId, $limit, $offset);
    }

    public function getSellerTransactions(int $sellerId, int $limit = 50, int $offset = 0): array
    {
        return $this->txnRepo->getSellerTransactions($sellerId, $limit, $offset);
    }

    public function getRevenueStats(?int $sellerId = null): array
    {
        return $this->txnRepo->getRevenueStats($sellerId);
    }

    public function getMonthlyRevenue(?int $sellerId = null, int $months = 12): array
    {
        return $this->txnRepo->getMonthlyRevenue($sellerId, $months);
    }
}
