<?php
/**
 * Put real object description into the demo finds.
 *
 * Decision (Johan, 2026-07-21): bead diameter, perforation, ceramic fabric and
 * the rest of the material-specific detail go in ISAD(G) 3.1.5 Extent and medium
 * as a manual text field - not CCO columns, not custom fields.
 *
 * The detail is therefore written to a CONSISTENT CONVENTION, so it stays
 * parseable if it is ever structured later:
 *
 *   <count> <material> <object>. <technique>. <colour/opacity>.
 *   Diameter x-y mm; length x-y mm; perforation x-y mm. <weight>.
 *
 * Condition and fabric observations go to 3.4.4 Physical characteristics.
 *
 * Usage: enrich_finds.php [--apply]
 */
require '/usr/share/nginx/archeology/atom-framework/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

$apply = in_array('--apply', $argv, true);

$finds = [
    'KRK-SF214' => [
        'extent' => '14 glass beads. Drawn, heat-rounded. Opaque blue (11) and translucent blue-green (3). Diameter 3.0-4.0 mm; length 2.0-3.0 mm; perforation 1.0-1.5 mm. Mean weight 0.08 g.',
        'physical' => 'Good. Surfaces lightly patinated; three beads with minor edge chipping. No adhering residue.',
    ],
    'KRK-SF215' => [
        'extent' => '24 ceramic sherds: 23 body sherds and 1 decorated rim sherd. Hand-built. Sandy fabric with fine quartz temper, oxidised exterior and reduced core. Rim diameter approximately 180 mm; wall thickness 6-8 mm. Total weight 1.34 kg.',
        'physical' => 'Fair. Burnished exterior surface, partly abraded. Comb-stamped decoration on the rim sherd, two horizontal bands. No refitting sherds identified.',
    ],
    'KRK-SF221' => [
        'extent' => '6 glass beads. Wound. Opaque yellow (4) and translucent green (2). Diameter 5.0-7.0 mm; length 4.0-6.0 mm; perforation 1.5-2.0 mm. Mean weight 0.21 g.',
        'physical' => 'Good. Spiral winding seam visible on all six. One bead with a small surface bubble burst.',
    ],
    'KRK-SF224' => [
        'extent' => '1 lower grindstone, broken; approximately two-thirds of the original present. Coarse-grained quartzite. 240 x 180 x 65 mm; weight 4.2 kg.',
        'physical' => 'Fair. Ground and polished working surface with pecked rejuvenation over about half the area. Break surface fresh, possibly post-depositional.',
    ],
    'KRK-SF230' => [
        'extent' => '1 torso fragment of a modelled clay figurine. Hand-modelled. Fine untempered clay, oxidised throughout. Surviving height 42 mm; width 28 mm; thickness 19 mm; weight 21 g.',
        'physical' => 'Fair. Traces of red ochre in surface hollows. Both breaks old and abraded. Surface finish smoothed, no visible tool marks.',
    ],
    'KRK-SF231' => [
        'extent' => '3 fragments of iron tap slag. Largest fragment 95 x 70 x 40 mm; total weight 610 g.',
        'physical' => 'Good. Dense, vitrified upper surface with clear flow structure. Lower surface irregular with adhering sand. No charcoal inclusions visible.',
    ],
    'KRK-SF238' => [
        'extent' => '35 ostrich eggshell items: 31 finished beads and 4 unfinished blanks. Diameter 4.0-6.0 mm; thickness 1.5-2.0 mm; perforation 1.5-2.5 mm. Total weight 14 g.',
        'physical' => 'Good. Blanks represent the chipping and drilling stages of manufacture. Finished beads ground to a regular circular outline; edges smoothed.',
    ],
    'KRK-S07' => [
        'extent' => '1 bulk charcoal sample, 120 g, from hearth fill. Collected by hand from a 200 x 200 mm area at the base of the feature.',
        'physical' => 'Submitted for radiocarbon dating. Fragments up to 18 mm; species identification not yet undertaken.',
    ],
    'KRK-S08' => [
        'extent' => '1 bulk sediment sample, 2.5 kg, from the occupation floor. Collected as a column through the full 60 mm thickness of the deposit.',
        'physical' => 'Retained for micromorphology. Kept undisturbed in a rigid container; not sieved.',
    ],
];

if (!$apply) {
    echo "  DRY RUN - re-run with --apply\n";
    foreach ($finds as $id => $d) {
        printf("  %-11s %s\n", $id, substr($d['extent'], 0, 96) . '...');
    }
    exit(0);
}

DB::connection()->beginTransaction();
try {
    $n = 0;
    foreach ($finds as $identifier => $d) {
        $id = DB::table('information_object')->where('identifier', $identifier)->value('id');
        if (!$id) {
            printf("  %-11s NOT FOUND\n", $identifier);

            continue;
        }
        DB::table('information_object_i18n')->where('id', $id)->where('culture', 'en')->update([
            'extent_and_medium' => $d['extent'],
            'physical_characteristics' => $d['physical'],
        ]);
        ++$n;
        printf("  %-11s updated\n", $identifier);
    }
    if ($n !== count($finds)) {
        throw new RuntimeException("expected " . count($finds) . " updates, made {$n}");
    }
    DB::connection()->commit();
    printf("\n  COMMITTED %d record(s)\n", $n);
} catch (Throwable $e) {
    DB::connection()->rollBack();
    echo '  ROLLED BACK: ' . $e->getMessage() . "\n";
    exit(1);
}
