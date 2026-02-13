<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * PeerReviewService - Peer Review Workflow for Research Reports
 *
 * Manages review requests, submissions, and feedback.
 *
 * @package ahgResearchPlugin
 * @version 2.1.0
 */
class PeerReviewService
{
    /**
     * Request a peer review for a report.
     */
    public function requestReview(int $reportId, int $requestedBy, int $reviewerId): int
    {
        return DB::table('research_peer_review')->insertGetId([
            'report_id' => $reportId,
            'requested_by' => $requestedBy,
            'reviewer_id' => $reviewerId,
            'status' => 'pending',
            'requested_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get reviews for a report.
     */
    public function getReviews(int $reportId): array
    {
        return DB::table('research_peer_review as pr')
            ->join('research_researcher as reviewer', 'pr.reviewer_id', '=', 'reviewer.id')
            ->join('research_researcher as requester', 'pr.requested_by', '=', 'requester.id')
            ->where('pr.report_id', $reportId)
            ->select(
                'pr.*',
                'reviewer.first_name as reviewer_first_name',
                'reviewer.last_name as reviewer_last_name',
                'requester.first_name as requester_first_name',
                'requester.last_name as requester_last_name'
            )
            ->orderBy('pr.requested_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get pending reviews for a reviewer.
     */
    public function getPendingReviews(int $reviewerId): array
    {
        return DB::table('research_peer_review as pr')
            ->join('research_report as r', 'pr.report_id', '=', 'r.id')
            ->join('research_researcher as requester', 'pr.requested_by', '=', 'requester.id')
            ->where('pr.reviewer_id', $reviewerId)
            ->whereIn('pr.status', ['pending', 'in_progress'])
            ->select('pr.*', 'r.title as report_title',
                'requester.first_name as requester_first_name',
                'requester.last_name as requester_last_name')
            ->orderBy('pr.requested_at')
            ->get()
            ->toArray();
    }

    /**
     * Submit a completed review.
     */
    public function submitReview(int $reviewId, int $reviewerId, array $data): bool
    {
        return DB::table('research_peer_review')
            ->where('id', $reviewId)
            ->where('reviewer_id', $reviewerId)
            ->update([
                'status' => 'completed',
                'feedback' => $data['feedback'] ?? null,
                'rating' => $data['rating'] ?? null,
                'completed_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Decline a review request.
     */
    public function declineReview(int $reviewId, int $reviewerId): bool
    {
        return DB::table('research_peer_review')
            ->where('id', $reviewId)
            ->where('reviewer_id', $reviewerId)
            ->where('status', 'pending')
            ->update(['status' => 'declined']) > 0;
    }

    /**
     * Get a single review.
     */
    public function getReview(int $id): ?object
    {
        return DB::table('research_peer_review as pr')
            ->join('research_report as r', 'pr.report_id', '=', 'r.id')
            ->join('research_researcher as reviewer', 'pr.reviewer_id', '=', 'reviewer.id')
            ->join('research_researcher as requester', 'pr.requested_by', '=', 'requester.id')
            ->where('pr.id', $id)
            ->select('pr.*', 'r.title as report_title', 'r.researcher_id as report_owner_id',
                'reviewer.first_name as reviewer_first_name', 'reviewer.last_name as reviewer_last_name',
                'requester.first_name as requester_first_name', 'requester.last_name as requester_last_name')
            ->first();
    }
}
