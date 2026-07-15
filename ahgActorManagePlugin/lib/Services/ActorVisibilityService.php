<?php

namespace AhgActorManage\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Authority-record (actor) visibility / publication status.
 *
 * AtoM has no publish/draft state for authority records. This service adds one,
 * backed by the `ahg_actor_visibility` table, primarily so records for living
 * individuals can be kept out of public view (GDPR / POPIA) without deleting
 * them. A row exists only for non-public actors; absence of a row = published.
 *
 * Suppression is PUBLIC-ONLY: authenticated staff always see every record. All
 * consumers should therefore gate on this only when the request is anonymous.
 */
class ActorVisibilityService
{
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_DRAFT = 'draft';

    /** Request-scoped cache of the hidden-from-public actor id set. */
    private static ?array $hiddenIds = null;

    /**
     * All actor ids that must be hidden from anonymous users right now:
     * status='draft' (indefinite) OR an embargo whose date is still in the future.
     *
     * Cached for the request. Returns int[]; empty array if the table is missing.
     *
     * @return int[]
     */
    public static function getHiddenActorIds(): array
    {
        if (self::$hiddenIds !== null) {
            return self::$hiddenIds;
        }

        try {
            $today = date('Y-m-d');
            $ids = DB::table('ahg_actor_visibility')
                ->where(function ($q) use ($today) {
                    $q->where('status', self::STATUS_DRAFT)
                        ->orWhere(function ($q2) use ($today) {
                            $q2->whereNotNull('embargo_until')
                                ->where('embargo_until', '>', $today);
                        });
                })
                ->pluck('actor_id')
                ->map(fn ($v) => (int) $v)
                ->all();

            self::$hiddenIds = $ids;
        } catch (\Throwable $e) {
            // Table may not exist yet (pre-install) - fail open to published.
            self::$hiddenIds = [];
        }

        return self::$hiddenIds;
    }

    /**
     * Is this actor hidden from anonymous users right now?
     */
    public static function isHiddenFromPublic(int $actorId): bool
    {
        if ($actorId <= 0) {
            return false;
        }

        return in_array($actorId, self::getHiddenActorIds(), true);
    }

    /**
     * Should this actor be shown to the current request?
     * Authenticated users always see it; anonymous users only if not hidden.
     */
    public static function isVisibleToCurrentUser(int $actorId): bool
    {
        try {
            $authenticated = \sfContext::getInstance()->getUser()->isAuthenticated();
        } catch (\Throwable $e) {
            $authenticated = false;
        }

        if ($authenticated) {
            return true;
        }

        return !self::isHiddenFromPublic($actorId);
    }

    /**
     * Full visibility row for an actor, or a synthetic "published" default.
     *
     * @return array{status:string,embargo_until:?string,reason:?string}
     */
    public static function getStatus(int $actorId): array
    {
        $default = ['status' => self::STATUS_PUBLISHED, 'embargo_until' => null, 'reason' => null];

        if ($actorId <= 0) {
            return $default;
        }

        try {
            $row = DB::table('ahg_actor_visibility')->where('actor_id', $actorId)->first();
        } catch (\Throwable $e) {
            return $default;
        }

        if (!$row) {
            return $default;
        }

        return [
            'status' => $row->status ?? self::STATUS_PUBLISHED,
            'embargo_until' => $row->embargo_until ?? null,
            'reason' => $row->reason ?? null,
        ];
    }

    /**
     * Set (or clear) an actor's visibility.
     *
     * A published actor with no embargo carries no row (the row is deleted), so
     * the common case stays absent from the table.
     */
    public static function setStatus(
        int $actorId,
        string $status = self::STATUS_PUBLISHED,
        ?string $embargoUntil = null,
        ?string $reason = null,
        ?int $userId = null
    ): void {
        if ($actorId <= 0) {
            return;
        }

        $status = self::STATUS_DRAFT === $status ? self::STATUS_DRAFT : self::STATUS_PUBLISHED;
        $embargoUntil = self::normalizeDate($embargoUntil);

        // Published + no active embargo => no row needed; remove any existing one.
        $isPublic = self::STATUS_PUBLISHED === $status
            && (null === $embargoUntil || $embargoUntil <= date('Y-m-d'));

        try {
            if ($isPublic) {
                DB::table('ahg_actor_visibility')->where('actor_id', $actorId)->delete();
            } else {
                // created_at is populated by the column DEFAULT on insert and left
                // untouched on update; we only ever set updated_at.
                DB::table('ahg_actor_visibility')->updateOrInsert(
                    ['actor_id' => $actorId],
                    [
                        'status' => $status,
                        'embargo_until' => $embargoUntil,
                        'reason' => $reason !== null && $reason !== '' ? mb_substr($reason, 0, 255) : null,
                        'set_by_user_id' => $userId,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                );
            }
        } catch (\Throwable $e) {
            error_log('ActorVisibilityService::setStatus failed for actor '.$actorId.': '.$e->getMessage());
        }

        // Invalidate the request cache.
        self::$hiddenIds = null;
    }

    /**
     * Normalize a user-supplied date to Y-m-d or null.
     */
    private static function normalizeDate(?string $date): ?string
    {
        if (null === $date || '' === trim($date)) {
            return null;
        }

        $ts = strtotime($date);
        if (false === $ts) {
            return null;
        }

        return date('Y-m-d', $ts);
    }
}
