<?php

namespace AhgCustomFieldsPlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Faceted search over custom field values, backed by SQL.
 *
 * Why not the search index: AtoM here runs arOpenSearchPlugin, whose information
 * object mapping is `dynamic: strict` and which dispatches no event while
 * building documents. Adding custom fields to the index would mean forking two
 * base AtoM files and reconciling them on every upgrade. The values are typed
 * columns in one small table, so filtering them in SQL is both accurate and
 * fast, and costs no upgrade debt.
 *
 * The filter set is built from the field definitions themselves, so this works
 * for any profile - nothing here knows what a bead is.
 */
class CustomFieldSearchService
{
    /**
     * Field definitions that are marked searchable, in display order.
     *
     * @return object[]
     */
    public function getFilterableFields(string $entityType = 'informationobject'): array
    {
        return DB::table('custom_field_definition')
            ->where('entity_type', $entityType)
            ->where('is_active', 1)
            ->where('is_searchable', 1)
            ->orderBy('field_group')
            ->orderBy('sort_order')
            ->get()
            ->all();
    }

    /**
     * Options for a dropdown-backed filter.
     *
     * @return object[]
     */
    public function getDropdownOptions(?string $taxonomy): array
    {
        if (empty($taxonomy)) {
            return [];
        }

        return DB::table('ahg_dropdown')
            ->where('taxonomy', $taxonomy)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get()
            ->all();
    }

    /**
     * Taxonomies available as term filters, for axes recorded as term relations
     * rather than custom fields (phase, context type, object type, material).
     *
     * @return object[]
     */
    public function getTermFilters(): array
    {
        return DB::table('taxonomy_i18n as ti')
            ->join('object_term_relation as otr', function ($j) {
                $j->on('otr.term_id', '=', DB::raw('(SELECT t.id FROM term t WHERE t.taxonomy_id = ti.id LIMIT 1)'));
            })
            ->where('ti.culture', 'en')
            ->select('ti.id', 'ti.name')
            ->distinct()
            ->orderBy('ti.name')
            ->get()
            ->all();
    }

    /**
     * Run the filter.
     *
     * @param array $filters  field_key => value, or field_key with _min/_max suffix
     * @param array $termIds  term ids that must ALL be present on the record
     * @param int   $limit
     *
     * @return array{results: array, total: int}
     */
    public function search(array $filters, array $termIds = [], string $keywords = '', int $limit = 100): array
    {
        $defs = [];
        foreach ($this->getFilterableFields() as $d) {
            $defs[$d->field_key] = $d;
        }

        $query = DB::table('information_object as io')
            ->join('information_object_i18n as i18n', function ($j) {
                $j->on('i18n.id', '=', 'io.id')->where('i18n.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'io.id')
            ->leftJoin('term_i18n as lvl', function ($j) {
                $j->on('lvl.id', '=', 'io.level_of_description_id')->where('lvl.culture', '=', 'en');
            })
            ->where('io.id', '!=', 1);

        // Each custom-field filter becomes its own EXISTS, so several filters
        // are AND-ed across different fields rather than fighting over one join.
        foreach ($filters as $key => $raw) {
            if ('' === $raw || null === $raw) {
                continue;
            }

            $base = preg_replace('/_(min|max)$/', '', $key);
            $bound = preg_match('/_(min|max)$/', $key, $m) ? $m[1] : null;
            $def = $defs[$base] ?? null;
            if (!$def) {
                continue;
            }

            $query->whereExists(function ($sub) use ($def, $raw, $bound) {
                $sub->select(DB::raw(1))
                    ->from('custom_field_value as v')
                    ->whereColumn('v.object_id', 'io.id')
                    ->where('v.field_definition_id', $def->id);

                if ('number' === $def->field_type) {
                    if ('min' === $bound) {
                        $sub->where('v.value_number', '>=', (float) $raw);
                    } elseif ('max' === $bound) {
                        $sub->where('v.value_number', '<=', (float) $raw);
                    } else {
                        $sub->where('v.value_number', '=', (float) $raw);
                    }
                } elseif ('dropdown' === $def->field_type) {
                    $sub->where('v.value_dropdown', $raw);
                } else {
                    $sub->where('v.value_text', 'like', '%' . $raw . '%');
                }
            });
        }

        // Term filters - every selected term must be present, INHERITED DOWN THE
        // HIERARCHY. Phase and context type are recorded on the context, but the
        // question is nearly always about the finds inside it: "wound beads from
        // Phase 2" must return the beads, not the layer. A record therefore
        // matches if it carries the term itself, or if any ancestor does.
        foreach ($termIds as $termId) {
            $termId = (int) $termId;
            if ($termId <= 0) {
                continue;
            }
            $query->whereExists(function ($sub) use ($termId) {
                $sub->select(DB::raw(1))
                    ->from('object_term_relation as otr')
                    ->join('information_object as anc', 'anc.id', '=', 'otr.object_id')
                    ->where('otr.term_id', $termId)
                    ->where(function ($w) {
                        $w->whereColumn('anc.id', 'io.id')
                            ->orWhere(function ($a) {
                                $a->whereColumn('anc.lft', '<', 'io.lft')
                                    ->whereColumn('anc.rgt', '>', 'io.rgt');
                            });
                    });
            });
        }

        if ('' !== trim($keywords)) {
            $like = '%' . trim($keywords) . '%';
            $query->where(function ($q) use ($like) {
                $q->where('i18n.title', 'like', $like)
                    ->orWhere('io.identifier', 'like', $like)
                    ->orWhere('i18n.extent_and_medium', 'like', $like);
            });
        }

        $total = (clone $query)->count('io.id');

        $results = $query
            ->orderBy('io.identifier')
            ->limit($limit)
            ->select('io.id', 'io.identifier', 'i18n.title', 'i18n.extent_and_medium', 's.slug', 'lvl.name as level')
            ->get()
            ->all();

        return ['results' => $results, 'total' => $total];
    }
}
