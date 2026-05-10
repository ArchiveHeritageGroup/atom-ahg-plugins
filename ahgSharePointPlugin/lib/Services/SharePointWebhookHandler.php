<?php

namespace AtomExtensions\SharePoint\Services;

use AtomExtensions\SharePoint\Repositories\SharePointEventRepository;
use AtomExtensions\SharePoint\Repositories\SharePointSubscriptionRepository;

/**
 * SharePointWebhookHandler — pure logic for the public webhook receiver.
 *
 * Handles two flows:
 *   1. Subscription validation: query.validationToken present -> echo it.
 *   2. Notification delivery: validate clientState, INSERT sharepoint_event,
 *      enqueue 'sharepoint:ingest-event' job, return 202.
 *
 * Auth boundary is the clientState match. Notifications with mismatched
 * clientState are dropped (401-equivalent at the action layer).
 *
 * @phase 2
 */
class SharePointWebhookHandler
{
    public function __construct(
        private SharePointSubscriptionRepository $subscriptions,
        private SharePointEventRepository $events,
    ) {
    }

    /**
     * Validation handshake. Graph sends GET ?validationToken=... within 10s
     * of subscription create; we MUST echo it as text/plain 200.
     *
     * Returns null if not a validation request.
     */
    public function handleValidationToken(?string $validationToken): ?string
    {
        if ($validationToken === null || $validationToken === '') {
            return null;
        }
        return $validationToken;
    }

    /**
     * Process a notification batch (Graph delivers an array of notifications).
     *
     * @param array $payload Decoded JSON body.
     * @return array{accepted:int, dropped:int, queued_event_ids:array<int>}
     */
    public function handleNotifications(array $payload): array
    {
        $accepted = 0;
        $dropped = 0;
        $queuedIds = [];

        $notifications = $payload['value'] ?? [];
        foreach ($notifications as $note) {
            $subscriptionId = (string) ($note['subscriptionId'] ?? '');
            $clientState = (string) ($note['clientState'] ?? '');
            $resource = (string) ($note['resource'] ?? '');
            $changeType = (string) ($note['changeType'] ?? 'updated');
            $resourceData = $note['resourceData'] ?? [];

            $sub = $this->subscriptions->findBySubscriptionId($subscriptionId);
            if ($sub === null || !hash_equals((string) $sub->client_state, $clientState)) {
                $dropped++;
                continue;
            }

            $itemId = isset($resourceData['id']) ? (string) $resourceData['id'] : null;
            $etag = isset($resourceData['eTag']) ? (string) $resourceData['eTag'] : null;

            $eventId = $this->events->create([
                'subscription_id' => $sub->id,
                'drive_id' => $sub->drive_id,
                'sp_item_id' => $itemId,
                'sp_etag' => $etag,
                'change_type' => $changeType,
                'raw_payload' => json_encode($note, JSON_UNESCAPED_SLASHES),
            ]);

            $this->dispatchIngestJob($eventId);
            $accepted++;
            $queuedIds[] = $eventId;
        }

        return [
            'accepted' => $accepted,
            'dropped' => $dropped,
            'queued_event_ids' => $queuedIds,
        ];
    }

    /**
     * Enqueue the per-event ingest job.
     * Best-effort — if QueueService isn't available we mark queued anyway and
     * let cron `sharepoint:sync` fallback pick it up via delta poll.
     */
    private function dispatchIngestJob(int $eventId): void
    {
        if (!class_exists('\AtomFramework\Services\QueueService')) {
            return;
        }
        try {
            \AtomFramework\Services\QueueService::dispatch(
                'sharepoint:ingest-event',
                ['event_id' => $eventId],
                'integrations',
                priority: 5,
            );
            $this->events->update($eventId, ['status' => 'queued']);
        } catch (\Throwable $e) {
            $this->events->update($eventId, ['last_error' => 'queue dispatch failed: ' . $e->getMessage()]);
        }
    }
}
