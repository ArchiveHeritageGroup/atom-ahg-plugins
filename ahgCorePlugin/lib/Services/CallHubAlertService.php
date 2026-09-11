<?php

namespace AhgCore\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Raise CallHub tickets for errors recorded in ahg_error_log.
 *
 * Delivery is via the workbench notification spool rather than CallHub's HTTP API.
 * That is deliberate: the spool needs no credential on this host, carries its own
 * retry and outbox so a CallHub outage loses nothing, and keeps a page render from
 * ever waiting on another application. Drained by a CLI task, never inline.
 *
 * A ticket is not an error record. CallHub raises work for a person; ahg_error_log
 * already holds the machine detail. So no stack trace, no client IP, no user agent
 * goes into a ticket - the body carries a summary and a link back to the admin log.
 * Putting client IPs into a ticket system is a data-retention question nobody asked
 * for.
 *
 * DUPLICATE PREVENTION is the whole design, in three parts:
 *
 *   1. Errors are claimed in ahg_error_alert before the spool file is written, and
 *      the table has a UNIQUE key on (signature, period). A duplicate is therefore
 *      impossible at the database level rather than merely unlikely.
 *   2. Claim first, write second. Writing first and recording after would duplicate
 *      on any crash between the two; claiming first can only ever lose an alert,
 *      which the release() path then retries.
 *   3. The period. CallHub deduplicates server-side on the reference and a repeated
 *      reference NEVER creates a second ticket, so a timeless reference would
 *      silently swallow a fault that recurs months later. The reference carries an
 *      ISO week: one ticket per fault per week, reopened weekly while it persists.
 */
class CallHubAlertService
{
    /** Spool watched by the workbench notification ingester. */
    public const SPOOL_DIR = '/var/spool/workbench/notifications';

    /** Only these levels warrant a ticket. Warnings stay in the log. */
    public const TICKET_LEVELS = ['error'];

    /** Accepted by CallHub. Anything else is coerced to DEFAULT_PRIORITY. */
    public const PRIORITIES = ['critical', 'high', 'medium', 'low'];

    /**
     * Medium, not high. High as a default means nobody ever chose it. PSIS
     * overrides this per instance via the callhub_priority setting - a fatal on a
     * live archive is high, the same fatal on a testbed is low, and only this side
     * knows which instance raised it.
     */
    public const DEFAULT_PRIORITY = 'medium';

    /** Identifies this instance in the reference and the ticket title. */
    public const SOURCE = 'psis';

    /**
     * ISO year and week for a timestamp, e.g. 2026-W37.
     *
     * A week rather than a day: a fault recurring daily for a month would otherwise
     * raise thirty tickets, which is the noise problem inverted.
     */
    public static function period(?int $at = null): string
    {
        return date('o-\WW', $at ?? time());
    }

    /**
     * Dedupe reference: source, signature, period.
     *
     * Readable on purpose. Whoever triages CallHub can tell which system a ticket
     * came from and hand-construct a reference to check whether one already exists.
     */
    public static function externalRef(string $signature, ?string $period = null, string $source = self::SOURCE): string
    {
        return sprintf('%s:%s:%s', $source, substr($signature, 0, 12), $period ?? self::period());
    }

    /**
     * A stable UUID for the reference, used as the spool entityId.
     *
     * The spool treats entityId as a UUID. Hashing the reference into UUIDv5 shape
     * keeps it both valid and deterministic, so the same fault in the same week
     * always produces the same id without needing to store one.
     */
    public static function entityId(string $externalRef): string
    {
        $h = sha1($externalRef);

        return sprintf(
            '%08s-%04s-5%03s-%04x-%012s',
            substr($h, 0, 8),
            substr($h, 8, 4),
            substr($h, 13, 3),
            (hexdec(substr($h, 16, 4)) & 0x3FFF) | 0x8000,
            substr($h, 20, 12)
        );
    }

    /** Longest title a ticket list can show without wrapping badly. */
    public const MAX_TITLE = 120;

    /**
     * One line, human, no stack trace.
     *
     * The cap applies to the FINISHED string, not to the message alone. Capping
     * only the message and then appending " on <url>" let the total run past the
     * limit whenever the URL was long - caught by the test rather than in a ticket
     * list.
     */
    public static function title(object $row, string $source = self::SOURCE): string
    {
        $what = trim((string) ($row->exception_class ?? '')) ?: 'Error';
        $msg = trim((string) ($row->message ?? ''));
        $where = trim((string) ($row->url ?? ''));

        $title = sprintf(
            '%s: %s%s',
            strtoupper($source),
            $msg !== '' ? $msg : $what,
            $where !== '' ? ' on ' . $where : ''
        );

        if (mb_strlen($title) > self::MAX_TITLE) {
            $title = mb_substr($title, 0, self::MAX_TITLE - 3) . '...';
        }

        return $title;
    }

    /**
     * Priority is OURS to declare, and the workbench honours it only as a literal
     * "Priority: x" line in the body (callhubTickets.ts priorityFor). A spool file
     * has no priority field - the watcher drops unknown keys - so this line IS the
     * channel. Without it every PSIS ticket comes out high, which is the exact
     * flattening Johan objected to on 11 September 2026.
     *
     * Anything unrecognised becomes the house default rather than being passed
     * through, so a typo in a setting cannot produce a ticket with no priority.
     */
    public static function priority(?string $configured): string
    {
        $p = strtolower(trim((string) $configured));

        return in_array($p, self::PRIORITIES, true) ? $p : self::DEFAULT_PRIORITY;
    }

    /** Summary plus a link back to the log. The detail stays on our side. */
    public static function message(object $row, string $externalRef, ?string $link = null, ?string $priority = null): string
    {
        $lines = [
            // Must stay on its own line and first: the workbench matches it with
            // an anchored per-line regex.
            'Priority: ' . self::priority($priority),
            'Level: ' . (string) ($row->level ?? '?'),
            'Occurrences: ' . (string) ($row->occurrences ?? 1),
            'First seen: ' . (string) ($row->created_at ?? '?'),
            'Last seen: ' . (string) ($row->last_seen_at ?? $row->created_at ?? '?'),
        ];
        if (!empty($row->file)) {
            $lines[] = 'Location: ' . (string) $row->file . (isset($row->line) ? ':' . (int) $row->line : '');
        }
        if (!empty($row->exception_class)) {
            $lines[] = 'Exception: ' . (string) $row->exception_class;
        }
        $lines[] = 'Reference: ' . $externalRef;
        if ($link) {
            $lines[] = '';
            $lines[] = 'Full detail, stack trace and request context: ' . $link;
        }

        return implode("\n", $lines);
    }

    /** Deep link to the row in the admin error log. */
    public static function link(object $row): ?string
    {
        $host = trim((string) ($row->hostname ?? ''));
        if ($host === '' || !isset($row->id)) {
            return null;
        }

        return sprintf('https://%s/index.php/ahgSettings/errorLog?id=%d', $host, (int) $row->id);
    }

    /**
     * Claim a (signature, period) pair. False when it is already claimed.
     *
     * The unique key does the work: a concurrent second drain loses the insert and
     * gets false, rather than both writing a spool file.
     */
    public static function claim(string $signature, string $period, string $externalRef, int $errorLogId): bool
    {
        try {
            DB::table('ahg_error_alert')->insert([
                'signature' => $signature,
                'period' => $period,
                'external_ref' => $externalRef,
                'error_log_id' => $errorLogId,
                'sent_at' => date('Y-m-d H:i:s'),
            ]);

            return true;
        } catch (\Throwable $e) {
            // Duplicate key, or the table is absent. Either way: do not send.
            return false;
        }
    }

    /** Give a claim back when the spool write failed, so the next run retries. */
    public static function release(string $externalRef): void
    {
        try {
            DB::table('ahg_error_alert')->where('external_ref', $externalRef)->delete();
        } catch (\Throwable $e) {
            // best effort
        }
    }

    /**
     * Write one notification into the spool.
     *
     * chmod 644 before the move, because mktemp creates 0600 and the watcher runs as
     * another user - that silently quarantined four alerts elsewhere. Written to a
     * temporary name and moved, so the watcher never sees a half-written file.
     */
    public static function writeSpool(array $payload, string $dir = self::SPOOL_DIR): bool
    {
        try {
            if (!is_dir($dir) || !is_writable($dir)) {
                error_log('[callhub] spool not writable: ' . $dir);

                return false;
            }

            $tmp = tempnam($dir, '.psis-alert-');
            if (false === $tmp) {
                return false;
            }

            $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            if (false === $json || false === file_put_contents($tmp, $json)) {
                @unlink($tmp);

                return false;
            }

            @chmod($tmp, 0644);
            $final = $dir . '/psis-alert-' . ($payload['entityId'] ?? uniqid()) . '.json';

            if (!@rename($tmp, $final)) {
                @unlink($tmp);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            error_log('[callhub] spool write failed: ' . $e->getMessage());

            return false;
        }
    }

    /** Build the spool payload for one error row. */
    /**
     * $project is a WORKBENCH projects.id uuid, not a client name. It is the join
     * between a ticket and a workbench project, and CallHub assigns the client
     * itself - so "AHG Internal" (a client) must never be sent here.
     *
     * Omitted entirely when unset. A wrong uuid sends whoever clicks through into
     * the wrong project tree, which is worse than no link at all, so this field is
     * absent until someone names the workbench project a PSIS fault is fixed in.
     */
    public static function payload(object $row, string $externalRef, string $project, string $username, ?string $priority = null): array
    {
        $payload = [
            'username' => $username,
            'title' => self::title($row),
            'message' => self::message($row, $externalRef, self::link($row), $priority),
            'eventType' => 'alert',
            'entityId' => self::entityId($externalRef),
            'webLink' => self::link($row),
            'source' => self::SOURCE,
            'externalRef' => $externalRef,
        ];

        // Only a uuid is a project. Anything else is a misconfiguration, and sending
        // it would be silently wrong rather than visibly absent.
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', trim($project))) {
            $payload['projectId'] = strtolower(trim($project));
        }

        return $payload;
    }
}
