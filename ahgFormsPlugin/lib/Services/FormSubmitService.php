<?php

namespace ahgFormsPlugin\Services;

use AtomFramework\Services\Write\WriteServiceFactory;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * FormSubmitService - Persists a submitted configurable form back to AtoM.
 *
 * This is the "last mile" that makes ahgFormsPlugin templates usable: it reads
 * each field's ahg_form_field_mapping rows, applies any transformation, then
 * writes the values to the real AtoM tables via the framework write services
 * (information_object / accession + i18n) and the entity-inheritance chain for
 * property/note records. It records an audit row in ahg_form_submission_log and
 * clears the user's draft.
 *
 * Unmapped fields are reported back (not silently dropped) so the caller can
 * surface a warning — honest about what was and wasn't saved.
 */
class FormSubmitService
{
    /** @var array Tables handled directly by the IO/accession write services */
    private const IO_TABLES = ['information_object', 'information_object_i18n'];
    private const ACCESSION_TABLES = ['accession', 'accession_i18n'];

    /**
     * Persist a form submission.
     *
     * @param int      $templateId Template the form was rendered from
     * @param string   $type       'informationobject' | 'accession'
     * @param int|null $objectId   Existing record id (null = create)
     * @param array    $values     Posted values keyed by field_name
     * @param string   $culture    Culture code
     *
     * @return array{id:int,type:string,action:string,unmapped:array}
     */
    public function submit(int $templateId, string $type, ?int $objectId, array $values, string $culture = 'en'): array
    {
        $mappedFields = $this->loadMappedFields($templateId);

        $entityData = [];   // target_column => transformed value (IO or accession + i18n)
        $properties = [];   // [['name' => ..., 'value' => ...], ...]
        $notes = [];        // [['type_id' => ..., 'content' => ...], ...]
        $unmapped = [];

        foreach ($mappedFields as $row) {
            $raw = $values[$row->field_name] ?? null;
            $value = $this->normaliseValue($raw);

            // A field with no mapping cannot be persisted — record it.
            if (null === $row->target_table) {
                if (null !== $value && '' !== $value) {
                    $unmapped[] = $row->field_name;
                }
                continue;
            }

            $value = $this->applyTransformation($value, $row->transformation, $row->transformation_config);

            $table = $row->target_table;
            if (in_array($table, self::IO_TABLES, true) || in_array($table, self::ACCESSION_TABLES, true)) {
                // Skip empty values on create so DB defaults/NULL apply cleanly.
                $entityData[$row->target_column] = $value;
            } elseif ('property' === $table) {
                $properties[] = ['name' => $row->target_column, 'value' => (string) $value];
            } elseif ('note' === $table) {
                if (null !== $row->target_type_id) {
                    $notes[] = ['type_id' => (int) $row->target_type_id, 'content' => (string) $value];
                }
            } else {
                $unmapped[] = $row->field_name . ' (' . $table . ')';
            }
        }

        $isCreate = empty($objectId);

        if ('accession' === $type) {
            $savedId = $this->saveAccession($objectId, $entityData, $culture);
        } else {
            $savedId = $this->saveInformationObject($objectId, $entityData, $culture);
            // property/note records attach to the IO object id
            $this->writeProperties($savedId, $properties, $culture);
            $this->writeNotes($savedId, $notes, $culture);
        }

        $this->logSubmission($templateId, $type, $savedId, $isCreate ? 'create' : 'update', $values);
        $this->clearDraft($templateId, $type, $objectId);

        return [
            'id' => $savedId,
            'type' => $type,
            'action' => $isCreate ? 'create' : 'update',
            'unmapped' => array_values(array_unique($unmapped)),
        ];
    }

    /**
     * Load all fields for a template with their (possibly NULL) mapping rows.
     *
     * LEFT JOIN so fields with no mapping are still seen (and reported).
     */
    private function loadMappedFields(int $templateId): array
    {
        return DB::table('ahg_form_field as f')
            ->leftJoin('ahg_form_field_mapping as m', 'm.field_id', '=', 'f.id')
            ->where('f.template_id', $templateId)
            ->orderBy('f.sort_order')
            ->select([
                'f.field_name',
                'f.field_type',
                'm.target_table',
                'm.target_column',
                'm.target_type_id',
                'm.transformation',
                'm.transformation_config',
                'm.is_i18n',
            ])
            ->get()
            ->all();
    }

    /**
     * Create or update an information object from mapped column data.
     */
    private function saveInformationObject(?int $objectId, array $data, string $culture): int
    {
        $svc = WriteServiceFactory::informationObject();

        if (empty($objectId)) {
            return $svc->createInformationObject($data, $culture);
        }

        $svc->updateInformationObject($objectId, $data, $culture);

        return $objectId;
    }

    /**
     * Create or update an accession from mapped column data.
     */
    private function saveAccession(?int $objectId, array $data, string $culture): int
    {
        $svc = WriteServiceFactory::accession();

        if (empty($objectId)) {
            $result = $svc->createAccession($data, $culture);

            return (int) $result->id;
        }

        $svc->updateAccession($objectId, $data, $culture);

        return $objectId;
    }

    /**
     * Write property records (object -> property -> property_i18n).
     *
     * On update, existing same-named properties are replaced to avoid dupes.
     */
    private function writeProperties(int $objectId, array $properties, string $culture): void
    {
        foreach ($properties as $prop) {
            if ('' === $prop['value']) {
                continue;
            }

            // Replace any existing property of the same name on this object.
            $existing = DB::table('property')
                ->where('object_id', $objectId)
                ->where('name', $prop['name'])
                ->pluck('id')
                ->all();
            if (!empty($existing)) {
                DB::table('property_i18n')->whereIn('id', $existing)->delete();
                DB::table('property')->whereIn('id', $existing)->delete();
                DB::table('object')->whereIn('id', $existing)->delete();
            }

            $now = date('Y-m-d H:i:s');
            $propId = DB::table('object')->insertGetId([
                'class_name' => 'QubitProperty',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('property')->insert([
                'id' => $propId,
                'object_id' => $objectId,
                'name' => $prop['name'],
                'source_culture' => $culture,
            ]);
            DB::table('property_i18n')->insert([
                'id' => $propId,
                'culture' => $culture,
                'value' => $prop['value'],
            ]);
        }
    }

    /**
     * Write note records (object -> note -> note_i18n).
     */
    private function writeNotes(int $objectId, array $notes, string $culture): void
    {
        foreach ($notes as $note) {
            if ('' === $note['content']) {
                continue;
            }

            $now = date('Y-m-d H:i:s');
            $noteId = DB::table('object')->insertGetId([
                'class_name' => 'QubitNote',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('note')->insert([
                'id' => $noteId,
                'object_id' => $objectId,
                'type_id' => $note['type_id'],
                'scope' => 'QubitInformationObject',
                'source_culture' => $culture,
            ]);
            DB::table('note_i18n')->insert([
                'id' => $noteId,
                'culture' => $culture,
                'content' => $note['content'],
            ]);
        }
    }

    /**
     * Apply a declared transformation to a value.
     */
    private function applyTransformation($value, ?string $transformation, ?string $config)
    {
        if (null === $transformation || null === $value || is_array($value)) {
            return $value;
        }

        switch ($transformation) {
            case 'uppercase':
                return mb_strtoupper((string) $value);
            case 'lowercase':
                return mb_strtolower((string) $value);
            case 'trim':
                return trim((string) $value);
            case 'ucfirst':
                return ucfirst((string) $value);
            case 'date_format':
                $cfg = $config ? json_decode($config, true) : [];
                $fmt = $cfg['format'] ?? 'Y-m-d';
                $ts = strtotime((string) $value);

                return $ts ? date($fmt, $ts) : $value;
            default:
                return $value;
        }
    }

    /**
     * Collapse a posted value into a scalar/string for persistence.
     *
     * daterange arrays become "start - end"; multiselect arrays become a
     * pipe-joined string; checkboxes already arrive as "1" or absent.
     */
    private function normaliseValue($raw)
    {
        if (is_array($raw)) {
            if (isset($raw['start']) || isset($raw['end'])) {
                $start = trim((string) ($raw['start'] ?? ''));
                $end = trim((string) ($raw['end'] ?? ''));
                if ('' === $start && '' === $end) {
                    return null;
                }

                return trim($start . ' - ' . $end, ' -');
            }

            $parts = array_filter(array_map('strval', $raw), static fn ($v) => '' !== $v);

            return implode('|', $parts);
        }

        return $raw;
    }

    /**
     * Record an audit row in ahg_form_submission_log.
     */
    private function logSubmission(int $templateId, string $type, int $objectId, string $action, array $values): void
    {
        try {
            DB::table('ahg_form_submission_log')->insert([
                'template_id' => $templateId,
                'object_type' => $type,
                'object_id' => $objectId,
                'user_id' => $this->currentUserId() ?? 0,
                'action' => $action,
                'form_data' => json_encode($values),
                'submitted_at' => date('Y-m-d H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : null,
            ]);
        } catch (\Throwable $e) {
            // Audit logging must never block a successful save.
        }
    }

    /**
     * Remove the user's draft for this template/object after a successful save.
     */
    private function clearDraft(int $templateId, string $type, ?int $objectId): void
    {
        try {
            DB::table('ahg_form_draft')
                ->where('template_id', $templateId)
                ->where('object_type', $type)
                ->where('object_id', $objectId)
                ->where('user_id', $this->currentUserId())
                ->delete();
        } catch (\Throwable $e) {
            // Non-fatal.
        }
    }

    /**
     * Resolve the current AtoM user id, if any.
     */
    private function currentUserId(): ?int
    {
        if (class_exists('sfContext') && \sfContext::hasInstance()) {
            $user = \sfContext::getInstance()->getUser();
            if ($user && method_exists($user, 'getAttribute')) {
                return $user->getAttribute('user_id');
            }
        }

        return null;
    }
}
