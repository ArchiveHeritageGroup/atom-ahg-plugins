<?php

namespace AhgAPI\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * WebhookService - Manages webhook registration, delivery, and retry logic
 *
 * Implements Issue #82: Webhook notification system
 *
 * Features:
 * - Webhook registration and management
 * - HMAC SHA-256 signature generation
 * - Event triggering (item.created, item.updated, item.deleted)
 * - Exponential backoff retry logic
 * - Delivery logging
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class WebhookService
{
    /**
     * Supported events
     */
    public const EVENT_CREATED = 'item.created';
    public const EVENT_UPDATED = 'item.updated';
    public const EVENT_DELETED = 'item.deleted';
    public const EVENT_PUBLISHED = 'item.published';
    public const EVENT_UNPUBLISHED = 'item.unpublished';

    /**
     * Supported entity types
     */
    public const ENTITY_DESCRIPTION = 'informationobject';
    public const ENTITY_AUTHORITY = 'actor';
    public const ENTITY_REPOSITORY = 'repository';
    public const ENTITY_ACCESSION = 'accession';
    public const ENTITY_TERM = 'term';

    /**
     * Delivery statuses
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_RETRYING = 'retrying';

    /**
     * Maximum retry attempts
     */
    public const MAX_RETRIES = 5;

    /**
     * Base delay for exponential backoff (seconds)
     */
    public const BASE_DELAY = 60;

    /**
     * Get all supported events
     */
    public static function getSupportedEvents(): array
    {
        return [
            self::EVENT_CREATED,
            self::EVENT_UPDATED,
            self::EVENT_DELETED,
            self::EVENT_PUBLISHED,
            self::EVENT_UNPUBLISHED,
        ];
    }

    /**
     * Get all supported entity types
     */
    public static function getSupportedEntityTypes(): array
    {
        return [
            self::ENTITY_DESCRIPTION,
            self::ENTITY_AUTHORITY,
            self::ENTITY_REPOSITORY,
            self::ENTITY_ACCESSION,
            self::ENTITY_TERM,
        ];
    }

    /**
     * Create a new webhook
     *
     * @param int $userId Owner user ID
     * @param array $data Webhook data (name, url, events, entity_types)
     * @return array Result with success status and webhook data or error
     */
    public static function create(int $userId, array $data): array
    {
        // Validate required fields
        if (empty($data['name']) || empty($data['url'])) {
            return ['success' => false, 'error' => 'Name and URL are required'];
        }

        // Validate URL format
        if (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
            return ['success' => false, 'error' => 'Invalid URL format'];
        }

        // Ensure HTTPS for security (unless localhost for testing)
        $parsedUrl = parse_url($data['url']);
        $isLocalhost = in_array($parsedUrl['host'] ?? '', ['localhost', '127.0.0.1']);
        if (!$isLocalhost && ($parsedUrl['scheme'] ?? '') !== 'https') {
            return ['success' => false, 'error' => 'Webhook URL must use HTTPS'];
        }

        // Validate events
        $events = $data['events'] ?? [self::EVENT_CREATED, self::EVENT_UPDATED, self::EVENT_DELETED];
        $validEvents = array_intersect($events, self::getSupportedEvents());
        if (empty($validEvents)) {
            return ['success' => false, 'error' => 'At least one valid event is required'];
        }

        // Validate entity types
        $entityTypes = $data['entity_types'] ?? [self::ENTITY_DESCRIPTION];
        $validEntityTypes = array_intersect($entityTypes, self::getSupportedEntityTypes());
        if (empty($validEntityTypes)) {
            return ['success' => false, 'error' => 'At least one valid entity type is required'];
        }

        // Generate secret for HMAC signing
        $secret = bin2hex(random_bytes(32));

        try {
            $webhookId = DB::table('ahg_webhook')->insertGetId([
                'user_id' => $userId,
                'name' => $data['name'],
                'url' => $data['url'],
                'secret' => $secret,
                'events' => json_encode(array_values($validEvents)),
                'entity_types' => json_encode(array_values($validEntityTypes)),
                'is_active' => 1,
                'failure_count' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return [
                'success' => true,
                'data' => [
                    'id' => $webhookId,
                    'name' => $data['name'],
                    'url' => $data['url'],
                    'secret' => $secret, // Only returned on creation
                    'events' => $validEvents,
                    'entity_types' => $validEntityTypes,
                    'is_active' => true,
                ],
            ];
        } catch (\Exception $e) {
            error_log('WebhookService::create error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to create webhook'];
        }
    }

    /**
     * Update an existing webhook
     *
     * @param int $webhookId Webhook ID
     * @param int $userId User ID (for authorization)
     * @param array $data Update data
     * @return array Result
     */
    public static function update(int $webhookId, int $userId, array $data): array
    {
        $webhook = DB::table('ahg_webhook')
            ->where('id', $webhookId)
            ->where('user_id', $userId)
            ->first();

        if (!$webhook) {
            return ['success' => false, 'error' => 'Webhook not found'];
        }

        $updateData = ['updated_at' => date('Y-m-d H:i:s')];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }

        if (isset($data['url'])) {
            if (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
                return ['success' => false, 'error' => 'Invalid URL format'];
            }
            $parsedUrl = parse_url($data['url']);
            $isLocalhost = in_array($parsedUrl['host'] ?? '', ['localhost', '127.0.0.1']);
            if (!$isLocalhost && ($parsedUrl['scheme'] ?? '') !== 'https') {
                return ['success' => false, 'error' => 'Webhook URL must use HTTPS'];
            }
            $updateData['url'] = $data['url'];
        }

        if (isset($data['events'])) {
            $validEvents = array_intersect($data['events'], self::getSupportedEvents());
            if (empty($validEvents)) {
                return ['success' => false, 'error' => 'At least one valid event is required'];
            }
            $updateData['events'] = json_encode(array_values($validEvents));
        }

        if (isset($data['entity_types'])) {
            $validEntityTypes = array_intersect($data['entity_types'], self::getSupportedEntityTypes());
            if (empty($validEntityTypes)) {
                return ['success' => false, 'error' => 'At least one valid entity type is required'];
            }
            $updateData['entity_types'] = json_encode(array_values($validEntityTypes));
        }

        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'] ? 1 : 0;
        }

        try {
            DB::table('ahg_webhook')
                ->where('id', $webhookId)
                ->update($updateData);

            return ['success' => true, 'data' => self::getById($webhookId)];
        } catch (\Exception $e) {
            error_log('WebhookService::update error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to update webhook'];
        }
    }

    /**
     * Delete a webhook
     *
     * @param int $webhookId Webhook ID
     * @param int $userId User ID (for authorization)
     * @return array Result
     */
    public static function delete(int $webhookId, int $userId): array
    {
        $webhook = DB::table('ahg_webhook')
            ->where('id', $webhookId)
            ->where('user_id', $userId)
            ->first();

        if (!$webhook) {
            return ['success' => false, 'error' => 'Webhook not found'];
        }

        try {
            // Delete delivery logs first
            DB::table('ahg_webhook_delivery')
                ->where('webhook_id', $webhookId)
                ->delete();

            // Delete webhook
            DB::table('ahg_webhook')
                ->where('id', $webhookId)
                ->delete();

            return ['success' => true];
        } catch (\Exception $e) {
            error_log('WebhookService::delete error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to delete webhook'];
        }
    }

    /**
     * Get webhook by ID
     *
     * @param int $webhookId Webhook ID
     * @return object|null Webhook data (without secret)
     */
    public static function getById(int $webhookId): ?object
    {
        $webhook = DB::table('ahg_webhook')
            ->where('id', $webhookId)
            ->first();

        if ($webhook) {
            $webhook->events = json_decode($webhook->events, true);
            $webhook->entity_types = json_decode($webhook->entity_types, true);
            unset($webhook->secret); // Never expose secret
        }

        return $webhook;
    }

    /**
     * Get webhooks for a user
     *
     * @param int $userId User ID
     * @param bool $activeOnly Only return active webhooks
     * @return array List of webhooks
     */
    public static function getByUser(int $userId, bool $activeOnly = false): array
    {
        $query = DB::table('ahg_webhook')
            ->where('user_id', $userId);

        if ($activeOnly) {
            $query->where('is_active', 1);
        }

        $webhooks = $query->orderBy('created_at', 'desc')->get();

        return $webhooks->map(function ($webhook) {
            $webhook->events = json_decode($webhook->events, true);
            $webhook->entity_types = json_decode($webhook->entity_types, true);
            unset($webhook->secret);
            return $webhook;
        })->all();
    }

    /**
     * Regenerate webhook secret
     *
     * @param int $webhookId Webhook ID
     * @param int $userId User ID (for authorization)
     * @return array Result with new secret
     */
    public static function regenerateSecret(int $webhookId, int $userId): array
    {
        $webhook = DB::table('ahg_webhook')
            ->where('id', $webhookId)
            ->where('user_id', $userId)
            ->first();

        if (!$webhook) {
            return ['success' => false, 'error' => 'Webhook not found'];
        }

        $newSecret = bin2hex(random_bytes(32));

        try {
            DB::table('ahg_webhook')
                ->where('id', $webhookId)
                ->update([
                    'secret' => $newSecret,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            return ['success' => true, 'secret' => $newSecret];
        } catch (\Exception $e) {
            error_log('WebhookService::regenerateSecret error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to regenerate secret'];
        }
    }

    /**
     * Trigger webhooks for an event
     *
     * This is the main entry point for webhook delivery.
     * Call this when an entity is created/updated/deleted.
     *
     * @param string $eventType Event type (e.g., 'item.created')
     * @param string $entityType Entity type (e.g., 'informationobject')
     * @param int $entityId Entity ID
     * @param array $payload Event payload data
     * @return int Number of webhooks triggered
     */
    public static function trigger(string $eventType, string $entityType, int $entityId, array $payload = []): int
    {
        // Find all active webhooks subscribed to this event and entity type
        $webhooks = DB::table('ahg_webhook')
            ->where('is_active', 1)
            ->where('failure_count', '<', self::MAX_RETRIES)
            ->get();

        $triggered = 0;

        foreach ($webhooks as $webhook) {
            $events = json_decode($webhook->events, true) ?? [];
            $entityTypes = json_decode($webhook->entity_types, true) ?? [];

            // Check if webhook is subscribed to this event and entity type
            if (!in_array($eventType, $events) || !in_array($entityType, $entityTypes)) {
                continue;
            }

            // Create delivery record
            $deliveryId = self::createDelivery($webhook->id, $eventType, $entityType, $entityId, $payload);

            // Attempt immediate delivery
            self::deliver($deliveryId);
            $triggered++;
        }

        return $triggered;
    }

    /**
     * Create a webhook delivery record
     *
     * @param int $webhookId Webhook ID
     * @param string $eventType Event type
     * @param string $entityType Entity type
     * @param int $entityId Entity ID
     * @param array $payload Event payload
     * @return int Delivery ID
     */
    public static function createDelivery(int $webhookId, string $eventType, string $entityType, int $entityId, array $payload): int
    {
        return DB::table('ahg_webhook_delivery')->insertGetId([
            'webhook_id' => $webhookId,
            'event_type' => $eventType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'payload' => json_encode($payload),
            'status' => self::STATUS_PENDING,
            'attempt_count' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Deliver a webhook
     *
     * Attempts to deliver the webhook payload to the configured URL.
     * On failure, schedules retry with exponential backoff.
     *
     * @param int $deliveryId Delivery ID
     * @return bool Success status
     */
    public static function deliver(int $deliveryId): bool
    {
        $delivery = DB::table('ahg_webhook_delivery')
            ->where('id', $deliveryId)
            ->first();

        if (!$delivery) {
            return false;
        }

        $webhook = DB::table('ahg_webhook')
            ->where('id', $delivery->webhook_id)
            ->first();

        if (!$webhook || !$webhook->is_active) {
            self::updateDeliveryStatus($deliveryId, self::STATUS_FAILED, null, 'Webhook inactive or deleted');
            return false;
        }

        // Increment attempt count
        $attemptCount = $delivery->attempt_count + 1;
        DB::table('ahg_webhook_delivery')
            ->where('id', $deliveryId)
            ->update([
                'attempt_count' => $attemptCount,
                'status' => self::STATUS_RETRYING,
            ]);

        // Build payload
        $payload = json_decode($delivery->payload, true) ?? [];
        $fullPayload = [
            'event' => $delivery->event_type,
            'entity_type' => $delivery->entity_type,
            'entity_id' => $delivery->entity_id,
            'timestamp' => date('c'),
            'delivery_id' => $deliveryId,
            'data' => $payload,
        ];

        $payloadJson = json_encode($fullPayload);

        // Generate HMAC signature
        $signature = self::generateSignature($payloadJson, $webhook->secret);

        // Attempt HTTP request
        $result = self::sendRequest($webhook->url, $payloadJson, $signature, $webhook->id);

        if ($result['success']) {
            // Success
            self::updateDeliveryStatus($deliveryId, self::STATUS_SUCCESS, $result['status_code'], $result['body']);

            // Reset failure count on webhook
            DB::table('ahg_webhook')
                ->where('id', $webhook->id)
                ->update([
                    'failure_count' => 0,
                    'last_triggered_at' => date('Y-m-d H:i:s'),
                ]);

            return true;
        }

        // Failed
        if ($attemptCount >= self::MAX_RETRIES) {
            // Max retries reached
            self::updateDeliveryStatus($deliveryId, self::STATUS_FAILED, $result['status_code'] ?? null, $result['error'] ?? 'Max retries exceeded');

            // Increment webhook failure count
            DB::table('ahg_webhook')
                ->where('id', $webhook->id)
                ->increment('failure_count');

            return false;
        }

        // Schedule retry with exponential backoff
        $delay = self::calculateBackoff($attemptCount);
        $nextRetry = date('Y-m-d H:i:s', time() + $delay);

        DB::table('ahg_webhook_delivery')
            ->where('id', $deliveryId)
            ->update([
                'status' => self::STATUS_RETRYING,
                'next_retry_at' => $nextRetry,
                'response_code' => $result['status_code'] ?? null,
                'response_body' => $result['error'] ?? null,
            ]);

        return false;
    }

    /**
     * Generate HMAC SHA-256 signature
     *
     * @param string $payload JSON payload
     * @param string $secret Webhook secret
     * @return string Signature
     */
    public static function generateSignature(string $payload, string $secret): string
    {
        return 'sha256=' . hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Verify webhook signature
     *
     * Use this in receiving applications to verify webhook authenticity.
     *
     * @param string $payload Raw payload body
     * @param string $signature Signature from X-Webhook-Signature header
     * @param string $secret Webhook secret
     * @return bool Valid signature
     */
    public static function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expected = self::generateSignature($payload, $secret);
        return hash_equals($expected, $signature);
    }

    /**
     * Send HTTP request to webhook URL
     *
     * @param string $url Webhook URL
     * @param string $payload JSON payload
     * @param string $signature HMAC signature
     * @param int $webhookId Webhook ID (for User-Agent)
     * @return array Result with success, status_code, body, error
     */
    protected static function sendRequest(string $url, string $payload, string $signature, int $webhookId): array
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Webhook-Signature: ' . $signature,
                'X-Webhook-ID: ' . $webhookId,
                'User-Agent: AtoM-Webhook/1.0',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $body = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'status_code' => null,
                'body' => null,
                'error' => $error,
            ];
        }

        // Consider 2xx status codes as success
        $success = $statusCode >= 200 && $statusCode < 300;

        return [
            'success' => $success,
            'status_code' => $statusCode,
            'body' => substr($body, 0, 1000), // Truncate response
            'error' => $success ? null : "HTTP $statusCode",
        ];
    }

    /**
     * Update delivery status
     *
     * @param int $deliveryId Delivery ID
     * @param string $status New status
     * @param int|null $responseCode HTTP response code
     * @param string|null $responseBody Response body or error
     */
    protected static function updateDeliveryStatus(int $deliveryId, string $status, ?int $responseCode, ?string $responseBody): void
    {
        $update = [
            'status' => $status,
            'response_code' => $responseCode,
            'response_body' => $responseBody ? substr($responseBody, 0, 5000) : null,
        ];

        if ($status === self::STATUS_SUCCESS) {
            $update['delivered_at'] = date('Y-m-d H:i:s');
        }

        DB::table('ahg_webhook_delivery')
            ->where('id', $deliveryId)
            ->update($update);
    }

    /**
     * Calculate exponential backoff delay
     *
     * @param int $attempt Current attempt number (1-based)
     * @return int Delay in seconds
     */
    protected static function calculateBackoff(int $attempt): int
    {
        // Exponential backoff: 60s, 120s, 240s, 480s, 960s
        return self::BASE_DELAY * pow(2, $attempt - 1);
    }

    /**
     * Process pending retries
     *
     * Run this periodically (e.g., via cron) to retry failed deliveries.
     *
     * @param int $limit Maximum deliveries to process
     * @return int Number of deliveries processed
     */
    public static function processRetries(int $limit = 100): int
    {
        $deliveries = DB::table('ahg_webhook_delivery')
            ->where('status', self::STATUS_RETRYING)
            ->where('next_retry_at', '<=', date('Y-m-d H:i:s'))
            ->where('attempt_count', '<', self::MAX_RETRIES)
            ->limit($limit)
            ->get();

        $processed = 0;

        foreach ($deliveries as $delivery) {
            self::deliver($delivery->id);
            $processed++;
        }

        return $processed;
    }

    /**
     * Get delivery logs for a webhook
     *
     * @param int $webhookId Webhook ID
     * @param int $limit Number of records
     * @param int $offset Offset for pagination
     * @return array Delivery logs
     */
    public static function getDeliveryLogs(int $webhookId, int $limit = 50, int $offset = 0): array
    {
        return DB::table('ahg_webhook_delivery')
            ->where('webhook_id', $webhookId)
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function ($delivery) {
                $delivery->payload = json_decode($delivery->payload, true);
                return $delivery;
            })
            ->all();
    }

    /**
     * Get delivery statistics for a webhook
     *
     * @param int $webhookId Webhook ID
     * @return array Statistics (total, success, failed, pending)
     */
    public static function getDeliveryStats(int $webhookId): array
    {
        $stats = DB::table('ahg_webhook_delivery')
            ->where('webhook_id', $webhookId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as success,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as pending
            ', [self::STATUS_SUCCESS, self::STATUS_FAILED, self::STATUS_PENDING, self::STATUS_RETRYING])
            ->first();

        return [
            'total' => (int) $stats->total,
            'success' => (int) $stats->success,
            'failed' => (int) $stats->failed,
            'pending' => (int) $stats->pending,
        ];
    }

    /**
     * Clean up old delivery logs
     *
     * @param int $daysToKeep Number of days to retain logs
     * @return int Number of records deleted
     */
    public static function cleanupOldDeliveries(int $daysToKeep = 30): int
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));

        return DB::table('ahg_webhook_delivery')
            ->where('created_at', '<', $cutoff)
            ->whereIn('status', [self::STATUS_SUCCESS, self::STATUS_FAILED])
            ->delete();
    }
}
