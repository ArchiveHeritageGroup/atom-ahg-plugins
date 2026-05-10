<?php

namespace AtomExtensions\SharePoint\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * SharePointMappingService — projects Graph driveItem + listItem.fields
 * into the same shape ingest_row.data uses.
 *
 * Mapping rows live in sharepoint_mapping (per-drive, optionally per-content-type).
 * Transforms supported (Phase 2 v1):
 *   - date_iso          : parse arbitrary date string -> YYYY-MM-DD
 *   - html_strip        : strip_tags($value)
 *   - taxonomy_lookup   : look up term by name in given taxonomy (slow path)
 *
 * @phase 2
 */
class SharePointMappingService
{
    /**
     * Project a SharePoint item into the ingest_row.data payload shape.
     *
     * @param int   $driveId    sharepoint_drive.id
     * @param array $driveItem  Graph driveItem JSON.
     * @param array $fields     Graph listItem.fields JSON.
     * @return array<string, mixed> Keyed by AtoM target_field.
     */
    public function project(int $driveId, array $driveItem, array $fields): array
    {
        $rules = $this->loadMapping($driveId);
        $out = [];

        foreach ($rules as $rule) {
            $value = $this->readSource($rule->source_field, $driveItem, $fields);
            if ($value === null && $rule->default_value !== null) {
                $value = $rule->default_value;
            }
            if ($rule->transform) {
                $value = $this->applyTransform($rule->transform, $value);
            }
            if ($value !== null && $value !== '') {
                $out[$rule->target_field] = $value;
            }
        }

        // Always include the SP source identifiers as out-of-band metadata
        // for downstream provenance/audit.
        $out['_sharepoint_drive_id'] = $driveId;
        $out['_sharepoint_item_id'] = $driveItem['id'] ?? null;
        $out['_sharepoint_etag'] = $driveItem['eTag'] ?? null;

        return $out;
    }

    /** @return array<int, \stdClass> */
    private function loadMapping(int $driveId): array
    {
        return DB::table('sharepoint_mapping')
            ->where('drive_id', $driveId)
            ->orderBy('sort_order')
            ->get()
            ->all();
    }

    private function readSource(string $sourceField, array $driveItem, array $fields)
    {
        // Convention:
        //   "fields.X"     -> listItem.fields[X]
        //   "X"            -> driveItem[X]
        //   "X.Y"          -> nested driveItem path
        if (str_starts_with($sourceField, 'fields.')) {
            $key = substr($sourceField, strlen('fields.'));
            return $fields[$key] ?? null;
        }
        if (str_contains($sourceField, '.')) {
            $current = $driveItem;
            foreach (explode('.', $sourceField) as $part) {
                if (!is_array($current) || !array_key_exists($part, $current)) {
                    return null;
                }
                $current = $current[$part];
            }
            return $current;
        }
        return $driveItem[$sourceField] ?? null;
    }

    private function applyTransform(string $transform, $value)
    {
        if ($value === null) {
            return null;
        }
        return match ($transform) {
            'date_iso' => $this->toIsoDate((string) $value),
            'html_strip' => trim(strip_tags((string) $value)),
            'lowercase' => strtolower((string) $value),
            'uppercase' => strtoupper((string) $value),
            // taxonomy_lookup deferred — needs a taxonomy_id parameter, see plan.
            default => $value,
        };
    }

    private function toIsoDate(string $raw): ?string
    {
        try {
            return (new \DateTimeImmutable($raw))->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
