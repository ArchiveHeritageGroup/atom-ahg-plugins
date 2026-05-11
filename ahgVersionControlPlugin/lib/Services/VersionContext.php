<?php

namespace AhgVersionControl\Services;

/**
 * VersionContext — request-scoped flags that govern automatic version capture.
 *
 *   skip()      — short-circuits the next save observer/listener; cleared on
 *                 enable() or on the next request (the static state lives only
 *                 for the lifetime of the PHP process).
 *   setSummary  — sets the change_summary that the next captured version will
 *                 record. Useful for "User edited Title" or "Imported from CSV".
 *   setUserId   — overrides the user_id for the next capture (default is the
 *                 current authenticated user resolved by the listener).
 *
 * The flags are intentionally request-scoped. Bulk-import code paths should:
 *
 *   VersionContext::skip();
 *   // …mass save loop…
 *   VersionContext::enable();
 *
 * After the loop the caller is responsible for invoking VersionWriter once on
 * the final state of each entity (e.g. via the Phase L backfill task).
 *
 * @phase D
 */
final class VersionContext
{
    private static bool $skipped = false;
    private static ?string $pendingSummary = null;
    private static ?int $pendingUserId = null;

    public static function skip(): void
    {
        self::$skipped = true;
    }

    public static function enable(): void
    {
        self::$skipped = false;
    }

    public static function isSkipped(): bool
    {
        return self::$skipped;
    }

    public static function setSummary(?string $summary): void
    {
        self::$pendingSummary = $summary;
    }

    /** Returns and clears the pending summary (one-shot). */
    public static function takeSummary(): ?string
    {
        $s = self::$pendingSummary;
        self::$pendingSummary = null;
        return $s;
    }

    public static function setUserId(?int $userId): void
    {
        self::$pendingUserId = $userId;
    }

    /** Returns and clears the pending user id (one-shot). */
    public static function takeUserId(): ?int
    {
        $u = self::$pendingUserId;
        self::$pendingUserId = null;
        return $u;
    }

    /** Reset all state (used by tests). */
    public static function reset(): void
    {
        self::$skipped = false;
        self::$pendingSummary = null;
        self::$pendingUserId = null;
    }
}
