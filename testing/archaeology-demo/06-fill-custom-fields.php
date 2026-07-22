<?php
/**
 * Fill the custom fields on every demonstration find.
 *
 * Values are chosen from the find's own Object type and Material terms, so a
 * grindstone never acquires a bead series and a potsherd never acquires a
 * perforation diameter. The field set is deliberately shared across materials
 * (see archaeology_profile.sql) precisely so that not every field applies to
 * every find - leaving the inapplicable ones empty is the correct outcome, not
 * a gap to be filled.
 *
 * Existing values are never overwritten - only empty fields are filled.
 *
 * Usage: 06-fill-custom-fields.php [--apply]
 */
require '/usr/share/nginx/archeology/atom-framework/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

$apply = in_array('--apply', $argv, true);

$defs = DB::table('custom_field_definition')->get()->keyBy('field_key');

/** Deterministic pseudo-variation, so re-running produces the same data. */
function pick(array $options, int $seed): mixed { return $options[$seed % count($options)]; }
function vary(float $base, float $spread, int $seed): float { return round($base + ($seed % 7) * $spread, 1); }

// object type / material -> the fields that genuinely apply
$profiles = [
    'Bead' => ['diameter_min_mm', 'diameter_max_mm', 'perforation_mm', 'length_mm',
               'manufacture_technique', 'opacity', 'colour', 'bead_series', 'weight_g', 'completeness'],
    'Ostrich eggshell fragment' => ['diameter_min_mm', 'diameter_max_mm', 'perforation_mm',
               'manufacture_technique', 'colour', 'bead_series', 'thickness_mm', 'completeness'],
    'Potsherd' => ['fabric', 'firing_atmosphere', 'surface_treatment', 'decoration',
               'wall_thickness_mm', 'rim_diameter_mm', 'colour', 'weight_g', 'completeness'],
    'Vessel'   => ['manufacture_technique', 'opacity', 'colour', 'wall_thickness_mm', 'completeness', 'weight_g'],
    'Tool'     => ['lithic_type', 'raw_material', 'cortex', 'length_mm', 'width_mm', 'thickness_mm', 'weight_g', 'completeness'],
    'Worked stone' => ['lithic_type', 'raw_material', 'length_mm', 'width_mm', 'thickness_mm', 'weight_g', 'completeness'],
    'Grindstone' => ['lithic_type', 'raw_material', 'length_mm', 'width_mm', 'thickness_mm', 'weight_g', 'completeness'],
    'Slag'     => ['weight_g', 'length_mm', 'width_mm', 'colour', 'completeness'],
    'Metal fragment' => ['manufacture_technique', 'length_mm', 'width_mm', 'weight_g', 'colour', 'completeness'],
    'Bone tool' => ['length_mm', 'width_mm', 'weight_g', 'colour', 'completeness', 'manufacture_technique'],
    'Figurine' => ['length_mm', 'width_mm', 'thickness_mm', 'weight_g', 'fabric', 'firing_atmosphere', 'completeness'],
    'Architectural fragment' => ['length_mm', 'width_mm', 'thickness_mm', 'weight_g', 'raw_material', 'completeness'],
];

$values = [
    'colour' => ['blue', 'blue-green', 'yellow; green', 'cream', 'red-brown', 'grey', 'black', 'olive green'],
    'opacity' => ['opaque', 'translucent', 'transparent'],
    'manufacture_technique' => ['drawn', 'wound', 'moulded', 'ground', 'knapped', 'hand_built', 'cast', 'pecked'],
    'surface_treatment' => ['burnished', 'smoothed', 'slipped', 'rusticated', 'none'],
    'completeness' => ['complete', 'near_complete', 'fragment', 'fragment', 'fragment'],
    'fabric' => ['sandy', 'quartz_tempered', 'grog_tempered', 'fine_untempered', 'coarse'],
    'firing_atmosphere' => ['oxidised', 'reduced', 'oxidised_reduced_core'],
    'raw_material' => ['quartz', 'quartzite', 'chert', 'chalcedony', 'dolerite', 'hornfels'],
    'cortex' => ['none', '1_25', '26_50', '51_75'],
    'lithic_type' => ['flake', 'blade', 'core', 'scraper', 'grindstone', 'hammerstone', 'debitage'],
    'bead_series' => ['series_a', 'series_b', 'series_c', 'oes', 'unclassified'],
    'decoration' => ['Comb-stamped, two horizontal bands', 'Incised chevrons below the rim',
                     'Punctate impressions', 'Undecorated', 'Applied and notched cordon'],
];

$numeric = [
    'diameter_min_mm' => [3.0, 0.4], 'diameter_max_mm' => [5.5, 0.5],
    'perforation_mm' => [1.2, 0.2], 'length_mm' => [42.0, 8.0],
    'width_mm' => [28.0, 5.0], 'thickness_mm' => [9.0, 1.5],
    'wall_thickness_mm' => [7.0, 0.6], 'rim_diameter_mm' => [160.0, 15.0],
    'weight_g' => [85.0, 30.0],
];

$finds = DB::table('information_object as io')
    ->join('term_i18n as ti', function ($j) { $j->on('ti.id', '=', 'io.level_of_description_id')->where('ti.culture', '=', 'en'); })
    ->where('ti.name', 'Item')
    ->select('io.id', 'io.identifier')->get();

$plan = [];
foreach ($finds as $seed => $find) {
    // what is it made of / what is it?
    $terms = DB::table('object_term_relation as otr')
        ->join('term_i18n as ti', function ($j) { $j->on('ti.id', '=', 'otr.term_id')->where('ti.culture', '=', 'en'); })
        ->where('otr.object_id', $find->id)->pluck('ti.name')->all();

    $applicable = ['count', 'completeness'];
    foreach ($terms as $t) {
        if (isset($profiles[$t])) { $applicable = array_merge($applicable, $profiles[$t]); }
    }
    $applicable = array_values(array_unique($applicable));

    foreach ($applicable as $key) {
        if (!isset($defs[$key])) { continue; }
        $def = $defs[$key];
        $already = DB::table('custom_field_value')->where('field_definition_id', $def->id)->where('object_id', $find->id)->exists();
        if ($already) { continue; }

        $v = null;
        if (isset($numeric[$key])) {
            $v = vary($numeric[$key][0], $numeric[$key][1], $seed + strlen($key));
        } elseif (isset($values[$key])) {
            $v = pick($values[$key], $seed + strlen($key));
        } elseif ('count' === $key) {
            $v = 1 + (($seed * 3) % 40);
        }
        if (null === $v) { continue; }

        $plan[] = [$find->id, $def, $v, $find->identifier, $key];
    }
}

if (!$apply) {
    printf("  finds examined : %d\n  values to add  : %d\n", count($finds), count($plan));
    echo "\n  DRY RUN - re-run with --apply\n";
    exit(0);
}

DB::connection()->beginTransaction();
try {
    $n = 0;
    foreach ($plan as [$objectId, $def, $v, $ident, $key]) {
        $row = ['field_definition_id' => $def->id, 'object_id' => $objectId, 'sequence' => 0,
                'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
                'value_text' => null, 'value_number' => null, 'value_date' => null,
                'value_boolean' => null, 'value_dropdown' => null];
        match ($def->field_type) {
            'number' => $row['value_number'] = $v,
            'dropdown' => $row['value_dropdown'] = $v,
            default => $row['value_text'] = $v,
        };
        DB::table('custom_field_value')->insert($row);
        ++$n;
    }
    DB::connection()->commit();
    printf("  added %d value(s)\n", $n);
} catch (Throwable $e) {
    DB::connection()->rollBack();
    echo '  ROLLED BACK: ' . $e->getMessage() . "\n";
    exit(1);
}

// Keep browse facets ("Narrow your results by:") current after this seed.
require __DIR__ . '/_refresh_facets.php';
refresh_demo_facets();
