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
 *   - ODRL use-prohibited records (research_rights_policy prohibition on 'use').
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

    /** @var array{unpublished:int,icip:int,odrl:int,redacted_objects:int} */
    protected $excluded = ['unpublished' => 0, 'icip' => 0, 'odrl' => 0, 'redacted_objects' => 0];

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
     * @return array{unpublished:int,icip:int,odrl:int,redacted_objects:int}
     */
    public function getExcluded(): array
    {
        return $this->excluded;
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
