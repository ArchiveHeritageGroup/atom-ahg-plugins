<?php

namespace AtomAhgPlugins\ahgMarketplacePlugin\Services;

require_once dirname(__DIR__) . '/Repositories/TransactionRepository.php';
require_once dirname(__DIR__) . '/Repositories/SellerRepository.php';
require_once dirname(__DIR__) . '/Repositories/SettingsRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\TransactionRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SellerRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SettingsRepository;

class PayoutService
{
    private TransactionRepository $txnRepo;
    private SellerRepository $sellerRepo;
    private SettingsRepository $settingsRepo;

    public function __construct()
    {
        $this->txnRepo = new TransactionRepository();
        $this->sellerRepo = new SellerRepository();
        $this->settingsRepo = new SettingsRepository();
    }

    // =========================================================================
    // Payout Processing
    // =========================================================================

    public function processPayout(int $payoutId, int $processedBy): array
    {
        $payout = $this->txnRepo->getPayoutById($payoutId);
        if (!$payout) {
            return ['success' => false, 'error' => 'Payout not found'];
        }

        if ($payout->status !== 'pending') {
            return ['success' => false, 'error' => 'Payout is not in pending status'];
        }

        // Check cooling period
        $coolingDays = (int) $this->settingsRepo->get('payout_cooling_period_days', 5);
        $createdAt = strtotime($payout->created_at);
        $releaseDate = strtotime("+{$coolingDays} days", $createdAt);

        if (time() < $releaseDate) {
            $remainingDays = ceil(($releaseDate - time()) / 86400);

            return ['success' => false, 'error' => "Cooling period not met. {$remainingDays} day(s) remaining."];
        }

        // Move to processing
        $this->txnRepo->updatePayout($payoutId, [
            'status' => 'processing',
            'processed_by' => $processedBy,
            'processed_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true, 'status' => 'processing'];
    }

    public function completePayout(int $payoutId, ?string $reference = null): array
    {
        $payout = $this->txnRepo->getPayoutById($payoutId);
        if (!$payout) {
            return ['success' => false, 'error' => 'Payout not found'];
        }

        if ($payout->status !== 'processing') {
            return ['success' => false, 'error' => 'Payout must be in processing status to complete'];
        }

        $updateData = [
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
        ];

        if ($reference) {
            $updateData['payment_reference'] = $reference;
        }

        $this->txnRepo->updatePayout($payoutId, $updateData);

        return ['success' => true];
    }

    public function batchProcess(array $payoutIds, int $processedBy): array
    {
        $results = [
            'processed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($payoutIds as $payoutId) {
            $result = $this->processPayout($payoutId, $processedBy);

            if ($result['success']) {
                $results['processed']++;
            } else {
                $results['skipped']++;
                $results['errors'][] = [
                    'payout_id' => $payoutId,
                    'error' => $result['error'],
                ];
            }
        }

        return $results;
    }

    // =========================================================================
    // Payout Queries
    // =========================================================================

    public function getSellerPayouts(int $sellerId, int $limit = 50, int $offset = 0): array
    {
        return $this->txnRepo->getSellerPayouts($sellerId, $limit, $offset);
    }

    public function getPendingPayouts(int $limit = 100): array
    {
        return $this->txnRepo->getPendingPayouts($limit);
    }

    public function getPayoutStats(?int $sellerId = null): array
    {
        $filters = [];
        if ($sellerId) {
            $filters['seller_id'] = $sellerId;
        }

        $allPayouts = $this->txnRepo->getAllPayoutsForAdmin($filters, 999999, 0);
        $items = $allPayouts['items'];

        $stats = [
            'total_payouts' => count($items),
            'total_amount' => 0,
            'pending_count' => 0,
            'pending_amount' => 0,
            'processing_count' => 0,
            'processing_amount' => 0,
            'completed_count' => 0,
            'completed_amount' => 0,
        ];

        foreach ($items as $payout) {
            $amount = (float) $payout->amount;
            $stats['total_amount'] += $amount;

            switch ($payout->status) {
                case 'pending':
                    $stats['pending_count']++;
                    $stats['pending_amount'] += $amount;
                    break;
                case 'processing':
                    $stats['processing_count']++;
                    $stats['processing_amount'] += $amount;
                    break;
                case 'completed':
                    $stats['completed_count']++;
                    $stats['completed_amount'] += $amount;
                    break;
            }
        }

        $stats['total_amount'] = round($stats['total_amount'], 2);
        $stats['pending_amount'] = round($stats['pending_amount'], 2);
        $stats['processing_amount'] = round($stats['processing_amount'], 2);
        $stats['completed_amount'] = round($stats['completed_amount'], 2);

        return $stats;
    }
}
