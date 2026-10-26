<?php

// Relative, so the test runs wherever the plugin is installed - the same rule
// the plugin code itself follows.
require dirname(__DIR__).'/lib/Services/LocalityVisibilityService.php';

use AhgSiteRecordPlugin\Services\LocalityVisibilityService as L;

class FakeUser
{
    public function __construct(private bool $auth, private array $creds = []) {}
    public function isAuthenticated(): bool { return $this->auth; }
    public function hasCredential($c): bool { return in_array($c, $this->creds, true); }
}

function site(array $over = []): object
{
    return (object) array_merge([
        'latitude' => -29.1234567,
        'longitude' => 28.7654321,
        'coordinate_datum' => 'WGS84',
        'altitude_m' => 1840,
        'map_sheet' => '2328BD',
        'locality_original' => 'Map sheet: 2328BD, site code RSA FRN1, index x166',
        'locality_sensitive' => 1,
    ], $over);
}

$anon = new FakeUser(false);
$researcher = new FakeUser(true, ['authenticated']);
$editor = new FakeUser(true, ['authenticated', 'editor']);
$admin = new FakeUser(true, ['authenticated', 'administrator']);

$pass = 0; $fail = 0;
function check(string $label, $got, $want) {
    global $pass, $fail;
    $ok = $got === $want;
    $ok ? $pass++ : $fail++;
    printf("  %s %-58s got=%s want=%s\n", $ok ? 'ok  ' : 'FAIL', $label,
        var_export($got, true), var_export($want, true));
}

echo "--- who gets an exact position ---\n";
check('anonymous',            L::canSeeExact(site(), $anon),       false);
check('authenticated researcher', L::canSeeExact(site(), $researcher), false);
check('editor',               L::canSeeExact(site(), $editor),     true);
check('administrator',        L::canSeeExact(site(), $admin),      true);

echo "\n--- default is protected ---\n";
check('locality_sensitive NULL is sensitive', L::isSensitive(site(['locality_sensitive' => null])), true);
check('missing property is sensitive',        L::isSensitive((object) []),                          true);
check('explicitly public is not sensitive',   L::isSensitive(site(['locality_sensitive' => 0])),    false);
check('public record, anonymous sees exact',  L::canSeeExact(site(['locality_sensitive' => 0]), $anon), true);

echo "\n--- the default: the public sees no location (site_locality_public = none) ---\n";
check('setting defaults to none', L::publicMode(), 'none');
$a = L::present(site(), $anon);
check('exact flag',            $a['exact'],             false);
check('still known to exist',  $a['has_coordinates'],   true);
check('withheld',              $a['withheld'],          true);
check('no latitude',           $a['latitude'],          null);
check('no longitude',          $a['longitude'],         null);
check('no precision',          $a['precision_km'],      null);
check('map sheet withheld',    $a['map_sheet'],         null);
check('original text withheld', $a['locality_original'], null);
check('altitude withheld',     $a['altitude_m'],        null);
check('says it is restricted', is_string($a['note']) && false !== stripos($a['note'], 'restricted'), true);

echo "\n--- when RARI chooses an area: 10, 25 or 50 km ---\n";
foreach (['10', '25', '50'] as $km) {
    $c = L::present(site(), $anon, $km);
    $step = (int) $km / 111.0;
    check("{$km} km: precision stated", $c['precision_km'], (int) $km);
    check("{$km} km: not withheld", $c['withheld'], false);
    check("{$km} km: latitude on the grid", abs(fmod(abs($c['latitude']) + 1e-9, $step)) < 1e-6 || abs($step - fmod(abs($c['latitude']), $step)) < 1e-6, true);
    check("{$km} km: within half a cell of the site", abs($c['latitude'] - -29.1234567) <= $step / 2 + 1e-9, true);
    check("{$km} km: map sheet still withheld", $c['map_sheet'], null);
}
$wide = L::present(site(), $anon, '50');
$narrow = L::present(site(), $anon, '10');
check('50 km is coarser than 10 km (fewer distinct cells)', abs($wide['latitude'] - -29.1234567) >= 0 && $wide['precision_km'] > $narrow['precision_km'], true);

echo "\n--- what an editor receives ---\n";
$e = L::present(site(), $editor);
check('exact flag',   $e['exact'],     true);
check('latitude raw', $e['latitude'],  -29.1234567);
check('map sheet',    $e['map_sheet'], '2328BD');
check('no precision note', $e['precision_km'], null);

echo "\n--- coarsening cannot be reversed ---\n";
$near1 = L::present(site(['latitude' => -29.1234567, 'longitude' => 28.7654321]), $anon, '10');
$near2 = L::present(site(['latitude' => -29.1298765, 'longitude' => 28.7612345]), $anon, '10');
check('two nearby sites collapse to one cell', [$near1['latitude'], $near1['longitude']] === [$near2['latitude'], $near2['longitude']], true);

echo "\n--- no coordinates at all ---\n";
$n = L::present(site(['latitude' => null, 'longitude' => null]), $anon);
check('has_coordinates false', $n['has_coordinates'], false);
check('latitude null',         $n['latitude'],        null);

echo "\n--- export redaction ---\n";
$row = ['site_number' => 'FRN1', 'latitude' => -29.1234567, 'longitude' => 28.7654321,
        'map_sheet' => '2328BD', 'locality_original' => 'x', 'altitude_m' => 1840,
        'locality_precision_km' => null];
$r = L::redactRow($row, site(), $anon);
check('export lat withheld (default none)', $r['latitude'], null);
check('export precision withheld',     $r['locality_precision_km'], null);
check('export map sheet gone', $r['map_sheet'],          null);
check('export original gone',  $r['locality_original'],  null);
check('export altitude gone',  $r['altitude_m'],         null);
check('non-locality field kept', $r['site_number'],      'FRN1');
$r2 = L::redactRow($row, site(), $admin);
check('admin export keeps exact', $r2['latitude'],       -29.1234567);
check('admin export keeps sheet', $r2['map_sheet'],      '2328BD');

echo "\n--- no session (CLI) defaults to no clearance ---\n";
check('null user is not cleared', L::canSeeExact(site(), null), false);

printf("\n  %d passed, %d failed\n", $pass, $fail);
exit($fail > 0 ? 1 : 0);
