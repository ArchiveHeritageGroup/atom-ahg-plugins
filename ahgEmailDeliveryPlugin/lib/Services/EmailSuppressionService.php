<?php

/**
 * EmailSuppressionService (#145) — bounce capture + suppression list + send-time gate.
 *
 * Parity with Heratio EmailBounceController / EmailSuppressionGate.
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * Licensed under the GNU Affero General Public License v3.0 or later.
 */

use Illuminate\Database\Capsule\Manager as DB;

class EmailSuppressionService
{
    public const REASONS = [
        'bounce' => 'Bounce', 'complaint' => 'Spam complaint',
        'manual' => 'Manual', 'unsubscribe' => 'Unsubscribe',
    ];

    /** Soft bounces are transient — only suppress after this many. */
    public const SOFT_BOUNCE_THRESHOLD = 3;

    private static string $table = 'ahg_email_suppression';

    public static function normalize(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * The gate. Call before sending to a recipient.
     * Hard bounces / complaints / manual block immediately.
     * Soft bounces only block once they cross the threshold.
     */
    public static function isSuppressed(string $email): bool
    {
        $email = self::normalize($email);
        if ($email === '') {
            return false;
        }
        $row = DB::table(self::$table)->where('email', $email)->first();
        if (!$row) {
            return false;
        }
        if ($row->reason === 'bounce' && $row->bounce_type === 'soft') {
            return (int) $row->bounce_count >= self::SOFT_BOUNCE_THRESHOLD;
        }
        return true;
    }

    /** Filter a list of addresses, returning only the deliverable ones. */
    public static function filterDeliverable(array $emails): array
    {
        return array_values(array_filter($emails, function ($e) {
            return !self::isSuppressed((string) $e);
        }));
    }

    /**
     * Add/refresh a suppression entry. Upserts on email; increments bounce_count
     * when the same address bounces again.
     */
    public function suppress(string $email, string $reason = 'manual', array $opts = []): bool
    {
        $email = self::normalize($email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $existing = DB::table(self::$table)->where('email', $email)->first();
        if ($existing) {
            DB::table(self::$table)->where('id', $existing->id)->update([
                'reason' => $reason,
                'bounce_type' => $opts['bounce_type'] ?? $existing->bounce_type,
                'source' => $opts['source'] ?? $existing->source,
                'detail' => $opts['detail'] ?? $existing->detail,
                'bounce_count' => (int) $existing->bounce_count + 1,
                'last_event_at' => $now,
            ]);
        } else {
            DB::table(self::$table)->insert([
                'email' => $email,
                'reason' => $reason,
                'bounce_type' => $opts['bounce_type'] ?? null,
                'source' => $opts['source'] ?? 'manual',
                'detail' => $opts['detail'] ?? null,
                'bounce_count' => 1,
                'created_by' => $opts['created_by'] ?? null,
                'created_at' => $now,
                'last_event_at' => $now,
            ]);
        }
        return true;
    }

    /** Remove a suppression (e.g. address recovered, manual override). */
    public function unsuppress(string $email): bool
    {
        $email = self::normalize($email);
        return DB::table(self::$table)->where('email', $email)->delete() > 0;
    }

    public function listAll(string $search = '', string $reason = ''): array
    {
        $q = DB::table(self::$table);
        if ($search !== '') {
            $q->where('email', 'like', '%'.$search.'%');
        }
        if ($reason !== '') {
            $q->where('reason', $reason);
        }
        return $q->orderByDesc('last_event_at')->limit(500)->get()->all();
    }

    public function stats(): array
    {
        $rows = DB::table(self::$table)
            ->select('reason', DB::raw('COUNT(*) AS n'))
            ->groupBy('reason')->get();
        $out = ['total' => 0];
        foreach ($rows as $r) {
            $out[$r->reason] = (int) $r->n;
            $out['total'] += (int) $r->n;
        }
        return $out;
    }

    /**
     * Ingest a provider bounce/complaint webhook payload. Supports common shapes:
     * Amazon SES/SNS, Mailgun, SendGrid event arrays, and a plain {email,type} body.
     * Returns the number of addresses suppressed.
     */
    public function ingestWebhook(array $payload): int
    {
        $count = 0;
        foreach ($this->extractEvents($payload) as $ev) {
            if (empty($ev['email'])) {
                continue;
            }
            $reason = $ev['reason'] ?? 'bounce';
            // Soft bounces are recorded but only gate after the threshold.
            $this->suppress($ev['email'], $reason, [
                'bounce_type' => $ev['bounce_type'] ?? null,
                'source' => 'webhook',
                'detail' => $ev['detail'] ?? null,
            ]);
            ++$count;
        }
        return $count;
    }

    /** Normalise heterogeneous provider payloads into [{email,reason,bounce_type,detail}]. */
    private function extractEvents(array $p): array
    {
        $events = [];

        // SES via SNS: {Message: "{...json...}"} or already-decoded notification.
        if (isset($p['Message']) && is_string($p['Message'])) {
            $decoded = json_decode($p['Message'], true);
            if (is_array($decoded)) {
                $p = $decoded;
            }
        }
        $notifType = $p['notificationType'] ?? $p['eventType'] ?? null;
        if ($notifType === 'Bounce' && isset($p['bounce']['bouncedRecipients'])) {
            $hard = ($p['bounce']['bounceType'] ?? '') === 'Permanent';
            foreach ($p['bounce']['bouncedRecipients'] as $r) {
                $events[] = [
                    'email' => $r['emailAddress'] ?? null, 'reason' => 'bounce',
                    'bounce_type' => $hard ? 'hard' : 'soft',
                    'detail' => $r['diagnosticCode'] ?? ($p['bounce']['bounceSubType'] ?? null),
                ];
            }
            return $events;
        }
        if ($notifType === 'Complaint' && isset($p['complaint']['complainedRecipients'])) {
            foreach ($p['complaint']['complainedRecipients'] as $r) {
                $events[] = ['email' => $r['emailAddress'] ?? null, 'reason' => 'complaint'];
            }
            return $events;
        }

        // SendGrid event webhook: a JSON array of events.
        if (isset($p[0]) && is_array($p[0])) {
            foreach ($p as $e) {
                $ev = $e['event'] ?? '';
                if (in_array($ev, ['bounce', 'dropped', 'blocked'], true)) {
                    $events[] = ['email' => $e['email'] ?? null, 'reason' => 'bounce',
                        'bounce_type' => ($ev === 'bounce' ? 'hard' : 'soft'), 'detail' => $e['reason'] ?? null];
                } elseif ($ev === 'spamreport') {
                    $events[] = ['email' => $e['email'] ?? null, 'reason' => 'complaint'];
                }
            }
            return $events;
        }

        // Mailgun: {event-data: {event, recipient, ...}} or flat {event, recipient}.
        $mg = $p['event-data'] ?? $p;
        if (isset($mg['event']) && isset($mg['recipient'])) {
            $ev = $mg['event'];
            if (in_array($ev, ['failed', 'bounced'], true)) {
                $severity = $mg['severity'] ?? 'permanent';
                $events[] = ['email' => $mg['recipient'], 'reason' => 'bounce',
                    'bounce_type' => ($severity === 'temporary' ? 'soft' : 'hard'),
                    'detail' => $mg['delivery-status']['message'] ?? null];
            } elseif ($ev === 'complained') {
                $events[] = ['email' => $mg['recipient'], 'reason' => 'complaint'];
            }
            return $events;
        }

        // Plain fallback: {email|recipient|address, type|reason}.
        $email = $p['email'] ?? $p['recipient'] ?? $p['address'] ?? null;
        if ($email) {
            $type = strtolower((string) ($p['type'] ?? $p['reason'] ?? 'bounce'));
            $events[] = [
                'email' => $email,
                'reason' => $type === 'complaint' ? 'complaint' : 'bounce',
                'bounce_type' => in_array($type, ['soft', 'hard'], true) ? $type : 'hard',
                'detail' => $p['detail'] ?? null,
            ];
        }
        return $events;
    }
}
