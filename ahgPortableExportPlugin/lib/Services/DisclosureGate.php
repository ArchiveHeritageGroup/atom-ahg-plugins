<?php

namespace AhgPortableExportPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * #1389 — disclosure gate for portable/offline exports.
 *
 * An offline package leaves the building ungated, so over-inclusion is
 * unrecoverable. Before anything is written we remove records that must not
 * enter such a package, fail-closed:
 *
 *   - unpublished descriptions (status type 158 / status 160), unless the
 *     `portable_export_include_unpublished` setting is explicitly on;
 *   - ICIP/TK protocol-restricted records (icip_access_restriction), including
 *     whole subtrees flagged applies_to_descendants;
 *   - ODRL use-prohibited records (research_rights_policy prohibition on 'use');
 *   - records the EXPORTING USER is not permitted to see (per-user ACL) — the
 *     same deny-set that hides records from that user's search/browse:
 *     security classification above clearance, donor-closed items, and active
 *     full embargoes (honouring that user's embargo exceptions). Admins are
 *     unrestricted. This makes an offline package role/security-scoped: a user
 *     can only export what they themselves have view rights to.
 *
 * Each excluded id is counted once, in that precedence order.
 *
 * Ported from Heratio AhgCore\Services\DisclosureGate to the PSIS/Symfony stack.
 */
class DisclosureGate
{
    // AtoM publication status (QubitTerm constants).
    const STATUS_TYPE_PUBLICATION = 158;
    const STATUS_PUBLISHED = 160;

    /** @var array<int,bool>|null */
    protected $icip = null;

    /** @var array<int,bool>|null */
    protected $odrl = null;

    /** @var int[]|null per-user ACL deny-set (lazy) */
    protected $aclRestricted = null;

    /** @var bool the per-user ACL lookup threw — fail closed, withhold the whole scope */
    protected $aclFailed = false;

    /**
     * User id whose view rights scope this export. 0 = anonymous (public
     * baseline); a positive id = that specific user; null (only when the gate is
     * built with no argument) = per-user ACL gating disabled.
     *
     * @var int|null
     */
    protected $aclUserId = null;

    /** @var bool whether per-user ACL gating applies to this run */
    protected $aclGating = false;

    /** @var array{unpublished:int,icip:int,odrl:int,acl:int,redacted_objects:int} */
    protected $excluded = ['unpublished' => 0, 'icip' => 0, 'odrl' => 0, 'acl' => 0, 'redacted_objects' => 0];

    /**
     * @param int|null $aclUserId user whose view rights bound this export (the
     *                            export row's user_id): a positive id scopes to
     *                            that user, 0 applies the anonymous/public
     *                            baseline, null disables per-user ACL gating
     *                            (legacy zero-arg construction only)
     */
    public function __construct(?int $aclUserId = null)
    {
        $this->aclUserId = $aclUserId;
        $this->aclGating = ($aclUserId !== null);
    }

    /**
     * Filter a list of IO ids down to those allowed into an offline package.
     *
     * @param int[] $ioIds
     *
     * @return int[] the kept ids (order preserved)
     */
    public function filter(array $ioIds): array
    {
        $ioIds = array_values(array_unique(array_map('intval', $ioIds)));
        if (empty($ioIds)) {
            return [];
        }

        $includeUnpublished = $this->includeUnpublished();

        $published = [];
        if (!$includeUnpublished) {
            $published = array_flip(
                DB::table('status')
                    ->whereIn('object_id', $ioIds)
                    ->where('type_id', self::STATUS_TYPE_PUBLICATION)
                    ->where('status_id', self::STATUS_PUBLISHED)
                    ->pluck('object_id')
                    ->map('intval')
                    ->all()
            );
        }

        $icip = array_flip($this->icipRestrictedIds());
        $odrl = array_flip($this->odrlRestrictedIds());
        $acl = array_flip($this->aclRestrictedIds());

        $kept = [];
        foreach ($ioIds as $id) {
            if (!$includeUnpublished && !isset($published[$id])) {
                $this->excluded['unpublished']++;
                continue;
            }
            if (isset($icip[$id])) {
                $this->excluded['icip']++;
                continue;
            }
            if (isset($odrl[$id])) {
                $this->excluded['odrl']++;
                continue;
            }
            if ($this->aclFailed || isset($acl[$id])) {
                $this->excluded['acl']++;
                continue;
            }
            $kept[] = $id;
        }

        return $kept;
    }

    /**
     * Record that N asset/object files were withheld for PII redaction — set by
     * the asset copier, which is the only stage that inspects object-level
     * redaction.
     */
    public function addRedactedObjects(int $count): void
    {
        $this->excluded['redacted_objects'] += max(0, $count);
    }

    /**
     * @return array{unpublished:int,icip:int,odrl:int,acl:int,redacted_objects:int}
     */
    public function getExcluded(): array
    {
        return $this->excluded;
    }

    /**
     * IO/object ids the exporting user is NOT permitted to see, per the same
     * per-user deny-set that scopes their search/browse results (security
     * classification above clearance, donor-closed items, active full embargoes,
     * honouring that user's embargo exceptions). Admins get an empty set.
     *
     * Runs against the export row's stored user_id, so it is correct even though
     * the export executes in a background task with no session. Fails CLOSED: if
     * the ACL service is unavailable it withholds nothing extra only when no user
     * is scoped; when a user IS scoped and the lookup throws, the whole scope is
     * treated as restricted so nothing leaks.
     *
     * @return int[]
     */
    public function aclRestrictedIds(): array
    {
        if ($this->aclRestricted !== null) {
            return $this->aclRestricted;
        }

        if (!$this->aclGating) {
            return $this->aclRestricted = [];
        }

        // A positive id scopes to that user; 0 (anonymous) resolves to the public
        // baseline, which SearchAccessFilterService expresses as a null user.
        $lookupUser = ($this->aclUserId !== null && $this->aclUserId > 0) ? $this->aclUserId : null;

        try {
            $ids = \AtomExtensions\Services\Search\SearchAccessFilterService::getInstance()
                ->getRestrictedObjectIds($lookupUser);

            return $this->aclRestricted = array_map('intval', $ids);
        } catch (\Throwable $e) {
            // Fail closed: a scoped user with a broken ACL lookup exports nothing
            // (the filter loop treats the whole scope as ACL-restricted).
            $this->aclFailed = true;

            return $this->aclRestricted = [];
        }
    }

    /**
     * IO ids carrying an ICIP/TK access restriction, including descendant
     * subtrees where applies_to_descendants is set.
     *
     * @return int[]
     */
    public function icipRestrictedIds(): array
    {
        if ($this->icip !== null) {
            return $this->icip;
        }

        $ids = [];
        if ($this->tableExists('icip_access_restriction')) {
            foreach (DB::table('icip_access_restriction')->pluck('information_object_id') as $id) {
                $ids[(int) $id] = true;
            }

            $subtree = DB::table('information_object as io')
                ->join('icip_access_restriction as r', 'r.applies_to_descendants', '=', DB::raw('1'))
                ->join('information_object as anc', 'anc.id', '=', 'r.information_object_id')
                ->whereColumn('io.lft', '>=', 'anc.lft')
                ->whereColumn('io.lft', '<=', 'anc.rgt')
                ->pluck('io.id');
            foreach ($subtree as $id) {
                $ids[(int) $id] = true;
            }
        }

        return $this->icip = array_keys($ids);
    }

    /**
     * IO ids carrying an ODRL 'use' prohibition.
     *
     * @return int[]
     */
    public function odrlRestrictedIds(): array
    {
        if ($this->odrl !== null) {
            return $this->odrl;
        }

        $ids = [];
        if ($this->tableExists('research_rights_policy')) {
            foreach (
                DB::table('research_rights_policy')
                    ->whereIn('target_type', ['archival_description', 'information_object'])
                    ->where('action_type', 'use')
                    ->where('policy_type', 'prohibition')
                    ->pluck('target_id') as $id
            ) {
                $ids[(int) $id] = true;
            }
        }

        return $this->odrl = array_keys($ids);
    }

    protected function includeUnpublished(): bool
    {
        $val = DB::table('ahg_settings')
            ->where('setting_key', 'portable_export_include_unpublished')
            ->value('setting_value');

        return (string) $val === '1' || $val === 'true';
    }

    protected function tableExists(string $table): bool
    {
        try {
            return DB::schema()->hasTable($table);
        } catch (\Throwable $e) {
            // Fallback: information_schema probe if the schema builder isn't wired.
            $db = DB::connection()->getDatabaseName();
            $row = DB::table('information_schema.tables')
                ->where('table_schema', $db)
                ->where('table_name', $table)
                ->count();

            return $row > 0;
        }
    }
}
