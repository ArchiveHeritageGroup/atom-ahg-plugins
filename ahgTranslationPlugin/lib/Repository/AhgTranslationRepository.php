<?php

class AhgTranslationRepository
{
    /**
     * Map UI field keys to DB columns in information_object_i18n.
     * NOTE: Column names align with AtoM 2.x schema.
     */
    public static function allowedFields(): array
    {
        return array(
            'title' => 'title',
            'scopeAndContent' => 'scope_and_content',
            'archivalHistory' => 'archival_history',
            'arrangement' => 'arrangement',
            'findingAids' => 'finding_aids',
            'accessConditions' => 'access_conditions',
            'reproductionConditions' => 'reproduction_conditions',
            'physicalCharacteristics' => 'physical_characteristics',
            'appraisal' => 'appraisal',
            'immediateSourceOfAcquisition' => 'immediate_source_of_acquisition',
        );
    }

    public function getSetting(string $key, $default = null)
    {
        $row = AhgTranslationDb::fetchOne(
            "SELECT setting_value FROM ahg_translation_settings WHERE setting_key = ?",
            array($key)
        );
        return $row ? $row['setting_value'] : $default;
    }

    public function setSetting(string $key, string $value): void
    {
        // Upsert pattern compatible with MySQL
        AhgTranslationDb::exec(
            "INSERT INTO ahg_translation_settings (setting_key, setting_value)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
            array($key, $value)
        );
    }

    public function getInformationObjectField(int $id, string $culture, string $column): ?string
    {
        $row = AhgTranslationDb::fetchOne(
            "SELECT `$column` AS v FROM information_object_i18n WHERE id = ? AND culture = ?",
            array($id, $culture)
        );
        if (!$row) return null;
        return $row['v'] !== null ? (string)$row['v'] : null;
    }

    public function ensureInformationObjectI18nRow(int $id, string $culture): void
    {
        $row = AhgTranslationDb::fetchOne(
            "SELECT id FROM information_object_i18n WHERE id = ? AND culture = ?",
            array($id, $culture)
        );
        if ($row) return;

        // Insert minimal row
        AhgTranslationDb::exec(
            "INSERT INTO information_object_i18n (id, culture) VALUES (?, ?)",
            array($id, $culture)
        );
    }

    public function updateInformationObjectField(int $id, string $culture, string $column, string $value): void
    {
        AhgTranslationDb::exec(
            "UPDATE information_object_i18n SET `$column` = ? WHERE id = ? AND culture = ?",
            array($value, $id, $culture)
        );
    }

    public function createDraft(array $data): array
    {
        // Deduplicate via unique key
        try {
            AhgTranslationDb::exec(
                "INSERT INTO ahg_translation_draft
                  (object_id, entity_type, field_name, source_culture, target_culture, source_hash, source_text, translated_text, status, created_by_user_id)
                 VALUES
                  (?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?)",
                array(
                    (int)$data['object_id'],
                    (string)($data['entity_type'] ?? 'information_object'),
                    (string)$data['field_name'],
                    (string)$data['source_culture'],
                    (string)$data['target_culture'],
                    (string)$data['source_hash'],
                    (string)$data['source_text'],
                    (string)$data['translated_text'],
                    $data['created_by_user_id'] !== null ? (int)$data['created_by_user_id'] : null,
                )
            );
            return array('ok' => true, 'draft_id' => (int)AhgTranslationDb::lastInsertId());
        } catch (Exception $e) {
            // Find existing
            $row = AhgTranslationDb::fetchOne(
                "SELECT id FROM ahg_translation_draft
                 WHERE object_id=? AND field_name=? AND source_culture=? AND target_culture=? AND source_hash=?",
                array(
                    (int)$data['object_id'],
                    (string)$data['field_name'],
                    (string)$data['source_culture'],
                    (string)$data['target_culture'],
                    (string)$data['source_hash'],
                )
            );
            if ($row) {
                return array('ok' => true, 'draft_id' => (int)$row['id'], 'deduped' => true);
            }
            return array('ok' => false, 'error' => $e->getMessage());
        }
    }

    public function getDraft(int $draftId): ?array
    {
        return AhgTranslationDb::fetchOne(
            "SELECT * FROM ahg_translation_draft WHERE id = ?",
            array($draftId)
        );
    }

    public function markDraftApplied(int $draftId): void
    {
        AhgTranslationDb::exec(
            "UPDATE ahg_translation_draft SET status='applied', applied_at=NOW() WHERE id=?",
            array($draftId)
        );
    }

    public function logAttempt(?int $objectId, ?string $field, ?string $src, ?string $tgt, array $result): void
    {
        AhgTranslationDb::exec(
            "INSERT INTO ahg_translation_log
              (object_id, field_name, source_culture, target_culture, endpoint, http_status, ok, error, elapsed_ms)
             VALUES
              (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            array(
                $objectId,
                $field,
                $src,
                $tgt,
                $result['endpoint'] ?? null,
                $result['http_status'] ?? null,
                !empty($result['ok']) ? 1 : 0,
                $result['error'] ?? null,
                $result['elapsed_ms'] ?? null,
            )
        );
    }
}
