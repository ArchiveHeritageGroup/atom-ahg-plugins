<?php

namespace AtomExtensions\SharePoint\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * SharePointRetentionMapper — maps Purview retention labels to AtoM dispositions.
 *
 * Reads listItem.fields (_ComplianceTag, _ComplianceTagWrittenTime, _IsRecord)
 * from the Graph response and resolves a target AtoM disposition based on a
 * tenant-configurable map stored in ahg_settings:
 *
 *   group=sharepoint, key=retention_label_map, value=JSON object:
 *     {
 *       "Confidential-7yr": {
 *         "level_of_description_id": 12,
 *         "parent_id": 345,
 *         "security_classification_id": 3,
 *         "embargo_until_field": "_ComplianceTagWrittenTime",
 *         "embargo_offset_days": 2555
 *       },
 *       "Public": { "security_classification_id": 1 }
 *     }
 *
 * Unknown labels return the "default" entry; unmapped tenants get a passthrough.
 *
 * @phase 2
 */
class SharePointRetentionMapper
{
    /**
     * Resolve the AtoM disposition attributes for a given listItem.fields object.
     *
     * @param array $listItemFields Graph listItem.fields decoded JSON.
     * @return array{
     *   level_of_description_id?:int,
     *   parent_id?:int,
     *   security_classification_id?:int,
     *   embargo_until?:?string,
     *   is_record:bool,
     *   compliance_tag:?string
     * }
     */
    public function resolve(array $listItemFields): array
    {
        $tag = $listItemFields['_ComplianceTag'] ?? null;
        $written = $listItemFields['_ComplianceTagWrittenTime'] ?? null;
        $isRecord = (bool) ($listItemFields['_IsRecord'] ?? false);

        $base = [
            'compliance_tag' => $tag,
            'is_record' => $isRecord,
        ];

        if ($tag === null || $tag === '') {
            return $base + $this->lookupMap('default');
        }

        $entry = $this->lookupMap($tag);

        if (!empty($entry['embargo_until_field']) && $entry['embargo_until_field'] === '_ComplianceTagWrittenTime' && $written) {
            $offsetDays = (int) ($entry['embargo_offset_days'] ?? 0);
            try {
                $writtenDate = new \DateTimeImmutable($written);
                $entry['embargo_until'] = $writtenDate->modify("+{$offsetDays} days")->format('Y-m-d');
            } catch (\Throwable $e) {
                // skip on parse failure
            }
        }

        return $base + $entry;
    }

    /**
     * Look up a single entry from the configured map. Returns the 'default' entry
     * (or empty array) when the requested key is absent.
     */
    private function lookupMap(string $key): array
    {
        $row = DB::table('ahg_settings')
            ->where('setting_group', 'sharepoint')
            ->where('setting_key', 'retention_label_map')
            ->first();

        if ($row === null || empty($row->setting_value)) {
            return [];
        }

        $map = json_decode($row->setting_value, true);
        if (!is_array($map)) {
            return [];
        }

        return $map[$key] ?? ($map['default'] ?? []);
    }
}
