<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Change Summary Service
 *
 * Computes field-level diffs between current database values and proposed changes.
 * Generates human-readable summaries for display in the editor save confirmation.
 *
 * @version 1.0.0
 */
class ChangeSummaryService
{
    /**
     * Compute field-level diffs between current and proposed values.
     *
     * @param int    $objectId  Information object ID
     * @param array  $newValues Proposed field values (field_name => value)
     * @param string $culture   Culture code
     * @return array [{field, old_value, new_value, label}]
     */
    public function computeDiff(int $objectId, array $newValues, string $culture = 'en'): array
    {
        // Load current i18n values
        $currentI18n = DB::table('information_object_i18n')
            ->where('id', $objectId)
            ->where('culture', $culture)
            ->first();

        // Load current main table values
        $currentMain = DB::table('information_object')
            ->where('id', $objectId)
            ->first();

        if (!$currentI18n && !$currentMain) {
            return [];
        }

        $diffs = [];
        $labels = $this->getFieldLabels();

        foreach ($newValues as $field => $newValue) {
            // Get current value from i18n or main table
            $oldValue = null;
            if ($currentI18n && property_exists($currentI18n, $field)) {
                $oldValue = $currentI18n->$field;
            } elseif ($currentMain && property_exists($currentMain, $field)) {
                $oldValue = $currentMain->$field;
            }

            // Normalize for comparison
            $oldNorm = trim((string) ($oldValue ?? ''));
            $newNorm = trim((string) ($newValue ?? ''));

            if ($oldNorm !== $newNorm) {
                $diffs[] = [
                    'field' => $field,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                    'label' => $labels[$field] ?? $this->humanizeFieldName($field),
                ];
            }
        }

        return $diffs;
    }

    /**
     * Format diffs into a human-readable summary string.
     */
    public function formatSummary(array $diffs): string
    {
        if (empty($diffs)) {
            return 'No changes detected.';
        }

        $parts = [];
        foreach ($diffs as $d) {
            $label = $d['label'];
            $oldEmpty = empty(trim(strip_tags((string) ($d['old_value'] ?? ''))));
            $newEmpty = empty(trim(strip_tags((string) ($d['new_value'] ?? ''))));

            if ($oldEmpty && !$newEmpty) {
                $parts[] = "Added {$label}";
            } elseif (!$oldEmpty && $newEmpty) {
                $parts[] = "Removed {$label}";
            } else {
                $oldSnippet = $this->truncate(strip_tags((string) $d['old_value']), 40);
                $newSnippet = $this->truncate(strip_tags((string) $d['new_value']), 40);
                $parts[] = "Changed {$label} from \"{$oldSnippet}\" to \"{$newSnippet}\"";
            }
        }

        return implode('; ', $parts) . '.';
    }

    /**
     * Get field display labels (covers ISAD-G, DACS, DC, RAD, MODS common fields).
     */
    public function getFieldLabels(?string $standard = null): array
    {
        $labels = [
            // ISAD(G) / common
            'title' => 'Title',
            'alternate_title' => 'Alternate Title',
            'identifier' => 'Identifier',
            'scope_and_content' => 'Scope and Content',
            'extent_and_medium' => 'Extent and Medium',
            'archival_history' => 'Archival History',
            'acquisition' => 'Immediate Source of Acquisition',
            'arrangement' => 'System of Arrangement',
            'access_conditions' => 'Conditions Governing Access',
            'reproduction_conditions' => 'Conditions Governing Reproduction',
            'physical_characteristics' => 'Physical Characteristics',
            'finding_aids' => 'Finding Aids',
            'location_of_originals' => 'Location of Originals',
            'location_of_copies' => 'Location of Copies',
            'related_units_of_description' => 'Related Units of Description',
            'rules' => 'Rules or Conventions',
            'institution_responsible_identifier' => 'Institution Identifier',
            'edition' => 'Edition',
            'sources' => 'Sources',
            'revision_history' => 'Revision History',

            // DACS-specific
            'appraisal' => 'Appraisal',
            'accruals' => 'Accruals',

            // Dublin Core
            'subject' => 'Subject',
            'description' => 'Description',
            'publisher' => 'Publisher',
            'contributor' => 'Contributor',
            'type' => 'Type',
            'format' => 'Format',
            'source' => 'Source',
            'language' => 'Language',
            'coverage' => 'Coverage',
            'rights' => 'Rights',

            // Main table fields
            'repository_id' => 'Repository',
            'level_of_description_id' => 'Level of Description',
            'publication_status_id' => 'Publication Status',
        ];

        return $labels;
    }

    /**
     * Get recent changes for an object from the audit log.
     */
    public function getRecentChanges(int $objectId, int $limit = 20): array
    {
        try {
            $exists = DB::select("SHOW TABLES LIKE 'ahg_audit_log'");
            if (empty($exists)) {
                return [];
            }

            $labels = $this->getFieldLabels();

            $entries = DB::table('ahg_audit_log')
                ->where('entity_type', 'information_object')
                ->where('entity_id', $objectId)
                ->whereIn('action', ['update', 'create', 'workflow:publish', 'workflow:unpublish'])
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get()
                ->toArray();

            foreach ($entries as &$entry) {
                // Decode changed_fields if present
                if (!empty($entry->changed_fields)) {
                    $fields = json_decode($entry->changed_fields, true);
                    if (is_array($fields)) {
                        $entry->changed_field_labels = array_map(
                            fn($f) => $labels[$f] ?? $this->humanizeFieldName($f),
                            $fields
                        );
                    }
                }
            }

            return $entries;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Convert snake_case field name to human-readable label.
     */
    private function humanizeFieldName(string $field): string
    {
        return ucfirst(str_replace('_', ' ', $field));
    }

    /**
     * Truncate a string to a given length.
     */
    private function truncate(string $text, int $length): string
    {
        $text = trim($text);
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length) . '...';
    }
}
