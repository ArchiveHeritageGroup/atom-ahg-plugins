<?php

/**
 * Pure-logic checks for the configurable error trap threshold.
 *
 *   php tests/error_trap_level_test.php
 *
 * The point of most of these is to prove the DEFAULT changed nothing. Widening what
 * is trapped is a volume decision on a stack whose volume is unmeasured, so the
 * default must reproduce the previous behaviour exactly: E_USER_ERROR and
 * E_RECOVERABLE_ERROR, nothing else.
 */

require_once __DIR__ . '/../lib/Services/ErrorNotificationService.php';

use AhgCore\Services\ErrorNotificationService as E;

$passed = 0;
$failed = 0;

function check(string $label, $got, $want): void
{
    global $passed, $failed;
    if ($got === $want) { ++$passed; printf("  PASS  %s\n", $label); return; }
    ++$failed;
    printf("  FAIL  %s\n        expected: %s\n        actual:   %s\n", $label, var_export($want, true), var_export($got, true));
}

$rc = new ReflectionClass(E::class);
$levels = $rc->getConstant('TRAP_LEVELS');

// trapMap() caches in a static; clear it between cases.
function resetCache(ReflectionClass $rc): void
{
    $p = $rc->getProperty('trapMap');
    $p->setAccessible(true);
    $p->setValue(null, null);
}

echo "\nThe default must change nothing\n";

resetCache($rc);
check('default resolves to the error threshold', E::trapMap(), $levels['error']);
check(
    'the error threshold is exactly the two previously trapped levels',
    array_keys($levels['error']),
    [E_USER_ERROR, E_RECOVERABLE_ERROR]
);
check('a warning is NOT trapped by default', isset($levels['error'][E_WARNING]), false);
check('a notice is NOT trapped by default', isset($levels['error'][E_NOTICE]), false);
check('a deprecation is NOT trapped by default', isset($levels['error'][E_DEPRECATED]), false);

echo "\nThresholds widen, never narrow\n";

$order = ['off', 'error', 'warning', 'notice', 'all'];
check('the thresholds are declared widest last', E::trapLevelNames(), $order);

for ($i = 1; $i < count($order); $i++) {
    $narrow = $levels[$order[$i - 1]];
    $wide = $levels[$order[$i]];
    $missing = array_diff_key($narrow, $wide);
    check(sprintf("'%s' includes everything '%s' traps", $order[$i], $order[$i - 1]), $missing, []);
    check(sprintf("'%s' is strictly wider than '%s'", $order[$i], $order[$i - 1]), count($wide) > count($narrow), true);
}

echo "\nEach threshold traps what its name says\n";

check("'off' traps nothing", $levels['off'], []);
check("'warning' traps E_WARNING", $levels['warning'][E_WARNING] ?? null, 'warning');
check("'notice' traps E_NOTICE", $levels['notice'][E_NOTICE] ?? null, 'notice');
check("'all' traps E_DEPRECATED", $levels['all'][E_DEPRECATED] ?? null, 'notice');
check("'warning' does NOT trap notices", isset($levels['warning'][E_NOTICE]), false);
check("'notice' does NOT trap deprecations", isset($levels['notice'][E_DEPRECATED]), false);

echo "\nEvery mapped level names a level the log understands\n";

$valid = ['error', 'warning', 'notice'];
$bad = [];
foreach ($levels as $name => $map) {
    foreach ($map as $errno => $lvl) {
        if (!in_array($lvl, $valid, true)) { $bad[] = "$name/$errno=$lvl"; }
    }
}
check('no threshold maps to an unknown level', $bad, []);

printf("\n%d passed, %d failed\n", $passed, $failed);
exit(0 === $failed ? 0 : 1);
