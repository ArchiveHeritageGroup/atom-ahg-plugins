<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * The single ICIP access rule: what may this user do with this description?
 *
 * Reads active restrictions and cultural notices on the record and on any
 * ancestor whose entry applies to descendants (lft/rgt), and takes the most
 * restrictive outcome. Gender and initiated status are not recorded for users
 * and must not be: those restriction types open only through a steward link.
 *
 * Any failure while deciding is a deny.
 */
class IcipPolicy
{
    public const ALLOW = 'allow';
    public const ACKNOWLEDGE = 'acknowledge';
    public const METADATA_ONLY = 'metadata_only';
    public const DENY = 'deny';

    private const RANK = [self::ALLOW => 0, self::ACKNOWLEDGE => 1, self::METADATA_ONLY => 2, self::DENY => 3];

    private const GROUP_ADMINISTRATOR = 100;
    private const GROUP_EDITOR = 101;

    /**
     * @param int|null $userId      null for an anonymous visitor
     * @param int[]    $sessionAcks notices acknowledged in this anonymous session;
     *                              ignored for a signed-in user
     */
    public static function decide(int $objectId, ?int $userId = null, array $sessionAcks = []): string
    {
        return self::explain($objectId, $userId, $sessionAcks)['decision'];
    }

    /**
     * The decision with what produced it.
     *
     * @return array{decision: string, pending: array, visible: array, governed: bool, admin: bool, failed: bool}
     */
    public static function explain(int $objectId, ?int $userId = null, array $sessionAcks = []): array
    {
        try {
            $restrictions = self::activeRows('icip_access_restriction', $objectId);
            $notices = self::activeNotices($objectId);
            if ([] === $restrictions && [] === $notices) {
                return ['decision' => self::ALLOW, 'pending' => [], 'visible' => [], 'governed' => false, 'admin' => false, 'failed' => false];
            }
            $actor = self::actor($userId);
            $acked = $userId ? self::acknowledgedIds($userId, $notices) : array_map('intval', $sessionAcks);

            return self::evaluate($restrictions, $notices, $actor, $acked, self::ackDefault(), self::audienceEnabled($actor));
        } catch (\Throwable $e) {
            error_log('icip.policy.failed: '.$e->getMessage());

            return ['decision' => self::DENY, 'pending' => [], 'visible' => [], 'governed' => true, 'admin' => false, 'failed' => true];
        }
    }

    /**
     * The policy table, free of database access.
     *
     * @param object[] $restrictions rows with restriction_type, community_id
     * @param object[] $notices      rows with id, community_id, blocks_access,
     *                               requires_acknowledgement, display_public, display_staff
     * @param array    $actor        admin, editor (bools), stewardOf (int[])
     * @param int[]    $acked        notice ids this user has acknowledged
     */
    public static function evaluate(array $restrictions, array $notices, array $actor, array $acked, bool $ackDefault, bool $audienceEnabled): array
    {
        $decision = self::ALLOW;
        $staff = $actor['admin'] || $actor['editor'];
        $pending = [];
        $visible = [];

        foreach ($restrictions as $row) {
            $decision = self::stricter($decision, self::forRestriction((string) $row->restriction_type, $row->community_id, $actor));
        }

        foreach ($notices as $notice) {
            $shown = $audienceEnabled && (int) ($staff ? $notice->display_staff : $notice->display_public);
            if ($shown) {
                $visible[] = $notice;
            }

            if ((int) $notice->blocks_access) {
                $decision = self::stricter($decision, self::forBlocked($notice->community_id, $actor));

                continue;
            }

            // A notice this audience is not shown cannot be acknowledged by it.
            $needsAck = $shown && ((int) $notice->requires_acknowledgement || $ackDefault);
            if ($needsAck && !$actor['admin'] && !self::isSteward($notice->community_id, $actor)
                && !in_array((int) $notice->id, $acked, true)) {
                $pending[] = $notice;
                $decision = self::stricter($decision, self::ACKNOWLEDGE);
            }
        }

        return [
            'decision' => $decision,
            'pending' => self::DENY === $decision ? [] : $pending,
            'visible' => self::DENY === $decision ? [] : $visible,
            'governed' => [] !== $restrictions || [] !== $notices,
            'admin' => (bool) $actor['admin'],
            'failed' => false,
        ];
    }

    /**
     * A notice that can be acknowledged: it exists, is in force, its type is
     * active, and it does not hard-block. A blocking notice is never lifted by
     * acknowledging it.
     */
    public static function isAcknowledgeable(int $noticeId): bool
    {
        $today = date('Y-m-d');

        return DB::table('icip_cultural_notice as n')
            ->join('icip_cultural_notice_type as t', 't.id', '=', 'n.notice_type_id')
            ->where('n.id', $noticeId)
            ->where('t.is_active', 1)
            ->where('t.blocks_access', 0)
            ->where(function ($q) use ($today) {
                $q->whereNull('n.start_date')->orWhere('n.start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('n.end_date')->orWhere('n.end_date', '>=', $today);
            })
            ->exists();
    }

    private static function forRestriction(string $type, $communityId, array $actor): string
    {
        if ($actor['admin'] || self::isSteward($communityId, $actor)) {
            return self::ALLOW;
        }
        if ('under_consultation' === $type) {
            return $actor['editor'] ? self::ALLOW : self::METADATA_ONLY;
        }

        // Every other type, including any value this code does not know.
        return $actor['editor'] ? self::METADATA_ONLY : self::DENY;
    }

    private static function forBlocked($communityId, array $actor): string
    {
        if ($actor['admin'] || self::isSteward($communityId, $actor)) {
            return self::ALLOW;
        }

        return $actor['editor'] ? self::METADATA_ONLY : self::DENY;
    }

    private static function isSteward($communityId, array $actor): bool
    {
        return null !== $communityId && in_array((int) $communityId, $actor['stewardOf'], true);
    }

    private static function stricter(string $a, string $b): string
    {
        return self::RANK[$a] >= self::RANK[$b] ? $a : $b;
    }

    private static function actor(?int $userId): array
    {
        $actor = ['admin' => false, 'editor' => false, 'stewardOf' => []];
        if (!$userId) {
            return $actor;
        }

        $groups = array_map('intval', DB::table('acl_user_group')->where('user_id', $userId)->pluck('group_id')->all());
        $actor['admin'] = in_array(self::GROUP_ADMINISTRATOR, $groups, true);
        $actor['editor'] = in_array(self::GROUP_EDITOR, $groups, true);
        if (DB::schema()->hasTable('icip_community_steward')) {
            $actor['stewardOf'] = array_map('intval', DB::table('icip_community_steward')
                ->where('user_id', $userId)->pluck('community_id')->all());
        }

        return $actor;
    }

    /** Ids of the record's ancestors. */
    private static function ancestorIds(int $objectId): array
    {
        $node = DB::table('information_object')->where('id', $objectId)->first(['lft', 'rgt']);
        if (!$node) {
            return [];
        }

        return array_map('intval', DB::table('information_object')
            ->where('lft', '<', $node->lft)->where('rgt', '>', $node->rgt)
            ->pluck('id')->all());
    }

    /** Limits a query to rows in force on this record, own or inherited. */
    private static function scope($query, string $alias, int $objectId, array $ancestors)
    {
        $today = date('Y-m-d');

        return $query
            ->where(function ($q) use ($alias, $objectId, $ancestors) {
                $q->where("{$alias}.information_object_id", $objectId);
                if ($ancestors) {
                    $q->orWhere(function ($w) use ($alias, $ancestors) {
                        $w->whereIn("{$alias}.information_object_id", $ancestors)
                            ->where("{$alias}.applies_to_descendants", 1);
                    });
                }
            })
            ->where(function ($q) use ($alias, $today) {
                $q->whereNull("{$alias}.start_date")->orWhere("{$alias}.start_date", '<=', $today);
            })
            ->where(function ($q) use ($alias, $today) {
                $q->whereNull("{$alias}.end_date")->orWhere("{$alias}.end_date", '>=', $today);
            });
    }

    private static function activeRows(string $table, int $objectId): array
    {
        if (!DB::schema()->hasTable($table)) {
            return []; // not installed: it cannot have restricted anything
        }

        return self::scope(DB::table($table.' as r'), 'r', $objectId, self::ancestorIds($objectId))
            ->get(['r.id', 'r.restriction_type', 'r.community_id'])->all();
    }

    private static function activeNotices(int $objectId): array
    {
        if (!DB::schema()->hasTable('icip_cultural_notice')) {
            return [];
        }

        return self::scope(DB::table('icip_cultural_notice as n'), 'n', $objectId, self::ancestorIds($objectId))
            ->join('icip_cultural_notice_type as t', 't.id', '=', 'n.notice_type_id')
            ->where('t.is_active', 1)
            ->orderBy('t.display_order')
            ->get([
                'n.id', 'n.community_id', 'n.custom_text', 't.name', 't.default_text', 't.severity',
                't.blocks_access', 't.requires_acknowledgement', 't.display_public', 't.display_staff',
            ])->all();
    }

    private static function acknowledgedIds(int $userId, array $notices): array
    {
        if ([] === $notices) {
            return [];
        }

        return array_map('intval', DB::table('icip_notice_acknowledgement')
            ->where('user_id', $userId)
            ->whereIn('notice_id', array_map(static fn ($n) => (int) $n->id, $notices))
            ->pluck('notice_id')->all());
    }

    private static function ackDefault(): bool
    {
        return '1' === (string) self::config('require_acknowledgement_default', '0');
    }

    private static function audienceEnabled(array $actor): bool
    {
        $key = ($actor['admin'] || $actor['editor']) ? 'enable_staff_notices' : 'enable_public_notices';

        return '1' === (string) self::config($key, '1');
    }

    private static function config(string $key, string $default)
    {
        return DB::table('icip_config')->where('config_key', $key)->value('config_value') ?? $default;
    }
}
