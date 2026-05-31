<?php

namespace ahgFormsPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * FormValueLoader - Reads an existing record's values back through a template's
 * field mappings so a configurable form can be prefilled for editing.
 *
 * Inverse of FormSubmitService: for each mapped field it pulls the current
 * value from the mapped target (information_object / accession core + i18n
 * columns, property values, note contents) and returns an array keyed by
 * field_name for FormRenderService.
 */
class FormValueLoader
{
    private const IO_TABLES = ['information_object', 'information_object_i18n'];
    private const ACCESSION_TABLES = ['accession', 'accession_i18n'];

    /**
     * Load current field values for a record.
     *
     * @param int    $templateId Template providing the field mappings
     * @param string $type       'informationobject' | 'accession'
     * @param int    $objectId   Existing record id
     * @param string $culture    Culture code
     *
     * @return array field_name => current value
     */
    public function load(int $templateId, string $type, int $objectId, string $culture = 'en'): array
    {
        $mappings = DB::table('ahg_form_field as f')
            ->join('ahg_form_field_mapping as m', 'm.field_id', '=', 'f.id')
            ->where('f.template_id', $templateId)
            ->select(['f.field_name', 'm.target_table', 'm.target_column', 'm.target_type_id'])
            ->get();

        if ($mappings->isEmpty()) {
            return [];
        }

        // Pre-load the core + i18n rows once.
        $coreTable = 'accession' === $type ? 'accession' : 'information_object';
        $i18nTable = $coreTable . '_i18n';

        $coreRow = (array) (DB::table($coreTable)->where('id', $objectId)->first() ?? []);
        $i18nRow = (array) (DB::table($i18nTable)
            ->where('id', $objectId)
            ->where('culture', $culture)
            ->first() ?? []);

        $values = [];

        foreach ($mappings as $m) {
            $table = $m->target_table;
            $col = $m->target_column;

            if (in_array($table, self::IO_TABLES, true) || in_array($table, self::ACCESSION_TABLES, true)) {
                $isI18n = str_ends_with($table, '_i18n');
                $row = $isI18n ? $i18nRow : $coreRow;
                if (array_key_exists($col, $row)) {
                    $values[$m->field_name] = $row[$col];
                }
            } elseif ('property' === $table) {
                $values[$m->field_name] = $this->loadProperty($objectId, $col, $culture);
            } elseif ('note' === $table && null !== $m->target_type_id) {
                $values[$m->field_name] = $this->loadNote($objectId, (int) $m->target_type_id, $culture);
            }
        }

        return $values;
    }

    /**
     * Read a property value by object + name.
     */
    private function loadProperty(int $objectId, string $name, string $culture): ?string
    {
        $row = DB::table('property as p')
            ->join('property_i18n as pi', 'pi.id', '=', 'p.id')
            ->where('p.object_id', $objectId)
            ->where('p.name', $name)
            ->where('pi.culture', $culture)
            ->value('pi.value');

        return null !== $row ? (string) $row : null;
    }

    /**
     * Read a note's content by object + type.
     */
    private function loadNote(int $objectId, int $typeId, string $culture): ?string
    {
        $row = DB::table('note as n')
            ->join('note_i18n as ni', 'ni.id', '=', 'n.id')
            ->where('n.object_id', $objectId)
            ->where('n.type_id', $typeId)
            ->where('ni.culture', $culture)
            ->value('ni.content');

        return null !== $row ? (string) $row : null;
    }
}
