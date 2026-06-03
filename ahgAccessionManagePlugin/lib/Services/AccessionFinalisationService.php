<?php

namespace AhgAccessionManage\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * AccessionFinalisationService — accession finalisation gate, workflow upsert,
 * rights inheritance and accession numbering. PSIS-parity port of the Heratio
 * AhgAccessionManage\Services\AccessionService finalisation/workflow methods
 * (#118 descriptive-manage gap). Settings come from sfConfig (app_*).
 *
 * @package ahgAccessionManagePlugin
 */
class AccessionFinalisationService
{
    private static function settingBool(string $key, bool $default = false): bool
    {
        if (!class_exists('\sfConfig')) {
            return $default;
        }
        $v = \sfConfig::get('app_' . $key, $default ? '1' : '0');

        return in_array((string) $v, ['1', 'true', 'on', 'yes'], true);
    }

    private static function settingValue(string $key, $default = '')
    {
        if (!class_exists('\sfConfig')) {
            return $default;
        }

        return \sfConfig::get('app_' . $key, $default);
    }

    public static function containerBarcodesEnabled(): bool
    {
        return self::settingBool('accession_allow_container_barcodes', false);
    }

    public static function rightsInheritanceEnabled(): bool
    {
        return self::settingBool('accession_rights_inheritance_enabled', false);
    }

    /**
     * Finalisation prerequisites. Returns human-readable strings naming each
     * requirement still missing; empty array = ready to finalise. Honours the
     * accession_require_donor_agreement / accession_require_appraisal settings
     * (a false setting skips that check).
     */
    public static function finalisationBlockers(int $accessionId): array
    {
        $blockers = [];

        if (self::settingBool('accession_require_donor_agreement')) {
            $hasAgreement = DB::table('accession_attachment')
                ->where('accession_id', $accessionId)
                ->where('category', 'donor_agreement')
                ->exists();
            if (!$hasAgreement) {
                $blockers[] = 'Donor agreement attachment missing';
            }
        }

        if (self::settingBool('accession_require_appraisal')) {
            $hasAppraisal = DB::table('accession_appraisal')
                ->where('accession_id', $accessionId)
                ->where('recommendation', '!=', 'pending')
                ->exists();
            if (!$hasAppraisal) {
                $blockers[] = 'Appraisal not completed';
            }
        }

        return $blockers;
    }

    public static function canFinalise(int $accessionId): bool
    {
        return empty(self::finalisationBlockers($accessionId));
    }

    /** Upsert the accession_v2 workflow row. */
    public static function upsertWorkflow(int $accessionId, array $fields): void
    {
        $fields['updated_at'] = date('Y-m-d H:i:s');
        if (DB::table('accession_v2')->where('accession_id', $accessionId)->exists()) {
            DB::table('accession_v2')->where('accession_id', $accessionId)->update($fields);
        } else {
            DB::table('accession_v2')->insert(array_merge(
                ['accession_id' => $accessionId, 'created_at' => date('Y-m-d H:i:s')],
                $fields
            ));
        }
    }

    /**
     * Copy the accession's inheritable PREMIS rights links to a newly-created
     * IO. No-op when accession_rights_inheritance_enabled is false. Idempotent.
     * Returns the number of rights newly linked.
     */
    public static function inheritRightsToIo(int $accessionId, int $ioId, ?int $userId = null): int
    {
        if (!self::rightsInheritanceEnabled()) {
            return 0;
        }

        $rows = DB::table('accession_rights')
            ->where('accession_id', $accessionId)
            ->where('inherit_to_children', 1)
            ->select('id')
            ->get();

        $applied = 0;
        foreach ($rows as $r) {
            $exists = DB::table('accession_rights_inherited')
                ->where('rights_id', $r->id)
                ->where('information_object_id', $ioId)
                ->exists();
            if (!$exists) {
                DB::table('accession_rights_inherited')->insert([
                    'rights_id' => $r->id,
                    'information_object_id' => $ioId,
                    'applied_by' => $userId,
                ]);
                ++$applied;
            }
        }

        return $applied;
    }

    /**
     * Next accession number from the accession_numbering_mask setting
     * (default ACC-{YYYY}-{####}). Sequence = max numeric tail with the
     * matching prefix this year + 1.
     */
    public static function nextAccessionNumber(): string
    {
        $mask = (string) self::settingValue('accession_numbering_mask', 'ACC-{YYYY}-{####}');
        $year = (int) date('Y');

        $prefixTemplate = str_replace('{YYYY}', (string) $year, $mask);
        $hashStart = strpos($prefixTemplate, '{');
        $prefix = $hashStart === false ? $prefixTemplate : substr($prefixTemplate, 0, $hashStart);

        $maxSeq = 0;
        if ($prefix !== '') {
            foreach (DB::table('accession')->where('identifier', 'LIKE', $prefix . '%')->pluck('identifier') as $ident) {
                $tail = substr((string) $ident, strlen($prefix));
                if (preg_match('/^(\d+)/', $tail, $m) && (int) $m[1] > $maxSeq) {
                    $maxSeq = (int) $m[1];
                }
            }
        }

        $next = $maxSeq + 1;
        $rendered = str_replace('{YYYY}', (string) $year, $mask);

        return (string) preg_replace_callback('/\{(#+)\}/', function ($m) use ($next) {
            return str_pad((string) $next, strlen($m[1]), '0', STR_PAD_LEFT);
        }, $rendered);
    }
}
