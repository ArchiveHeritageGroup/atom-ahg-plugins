<?php

namespace AtomAhgPlugins\ahgMarketplacePlugin\Services;

require_once dirname(__DIR__) . '/Repositories/ReviewRepository.php';
require_once dirname(__DIR__) . '/Repositories/TransactionRepository.php';
require_once dirname(__DIR__) . '/Repositories/SellerRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\ReviewRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\TransactionRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SellerRepository;

class ReviewService
{
    private ReviewRepository $reviewRepo;
    private TransactionRepository $txnRepo;
    private SellerRepository $sellerRepo;

    public function __construct()
    {
        $this->reviewRepo = new ReviewRepository();
        $this->txnRepo = new TransactionRepository();
        $this->sellerRepo = new SellerRepository();
    }

    // =========================================================================
    // Review CRUD
    // =========================================================================

    public function createReview(int $txnId, int $reviewerId, int $rating, string $title, ?string $comment = null, string $type = 'buyer_to_seller'): array
    {
        $txn = $this->txnRepo->getById($txnId);
        if (!$txn) {
            return ['success' => false, 'error' => 'Transaction not found'];
        }

        if ($txn->status !== 'completed') {
            return ['success' => false, 'error' => 'Transaction must be completed before leaving a review'];
        }

        // Check if already reviewed
        if ($this->reviewRepo->hasReviewed($txnId, $reviewerId)) {
            return ['success' => false, 'error' => 'You have already reviewed this transaction'];
        }

        // Validate rating range
        if ($rating < 1 || $rating > 5) {
            return ['success' => false, 'error' => 'Rating must be between 1 and 5'];
        }

        // Determine reviewed seller
        $reviewedSellerId = null;
        if ($type === 'buyer_to_seller') {
            $reviewedSellerId = $txn->seller_id;
        }

        $id = $this->reviewRepo->create([
            'transaction_id' => $txnId,
            'reviewer_id' => $reviewerId,
            'reviewed_seller_id' => $reviewedSellerId,
            'review_type' => $type,
            'rating' => $rating,
            'title' => $title,
            'comment' => $comment,
            'is_visible' => 1,
            'flagged' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Update seller average rating
        if ($reviewedSellerId) {
            $this->sellerRepo->updateRating($reviewedSellerId);
        }

        return ['success' => true, 'id' => $id];
    }

    // =========================================================================
    // Review Queries
    // =========================================================================

    public function getSellerReviews(int $sellerId, int $limit = 20, int $offset = 0): array
    {
        return $this->reviewRepo->getSellerReviews($sellerId, $limit, $offset);
    }

    public function hasReviewed(int $txnId, int $userId): bool
    {
        return $this->reviewRepo->hasReviewed($txnId, $userId);
    }

    public function getRatingStats(int $sellerId): array
    {
        return $this->reviewRepo->getSellerRatingStats($sellerId);
    }

    // =========================================================================
    // Moderation
    // =========================================================================

    public function flagReview(int $reviewId, string $reason): array
    {
        $review = $this->reviewRepo->getById($reviewId);
        if (!$review) {
            return ['success' => false, 'error' => 'Review not found'];
        }

        $this->reviewRepo->update($reviewId, [
            'flagged' => 1,
            'flag_reason' => $reason,
            'flagged_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true];
    }

    public function moderateReview(int $reviewId, bool $visible): array
    {
        $review = $this->reviewRepo->getById($reviewId);
        if (!$review) {
            return ['success' => false, 'error' => 'Review not found'];
        }

        $this->reviewRepo->update($reviewId, [
            'is_visible' => $visible ? 1 : 0,
            'flagged' => 0,
            'moderated_at' => date('Y-m-d H:i:s'),
        ]);

        // Recalculate seller rating after moderation
        if ($review->reviewed_seller_id) {
            $this->sellerRepo->updateRating($review->reviewed_seller_id);
        }

        return ['success' => true];
    }
}
