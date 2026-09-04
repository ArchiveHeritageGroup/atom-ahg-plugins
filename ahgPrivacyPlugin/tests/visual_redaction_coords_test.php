<?php

/**
 * Pure-logic checks for VisualRedactionService::requireCoordinates().
 *
 *   php tests/visual_redaction_coords_test.php
 *
 * These coordinates are burned into a derivative file. The method previously
 * defaulted a missing value to zero, which paints the mask at the origin at
 * whatever size survived: the file then LOOKS redacted while the sensitive area is
 * untouched. A sister Heratio instance shipped exactly that, from a column holding
 * two key shapes - x/y on some rows, left/top on others - so the left/top case
 * below is a real defect elsewhere, guarded against here.
 */

require_once __DIR__ . '/../lib/Service/VisualRedactionService.php';

$rc = new ReflectionClass('ahgPrivacyPlugin\Service\VisualRedactionService');
$parse = $rc->getMethod('requireCoordinates');
$parse->setAccessible(true);

$passed = 0;
$failed = 0;

function check(string $label, callable $fn, $want): void
{
    global $passed, $failed;
    try {
        $got = $fn();
    } catch (Throwable $e) {
        $got = 'THROWS';
    }
    $ok = 'THROWS' === $want ? 'THROWS' === $got : $got === $want;
    $ok ? ++$passed : ++$failed;
    printf("  %-54s %s\n", $label, $ok ? 'PASS' : 'FAIL (got ' . var_export($got, true) . ')');
}

echo "\nAccepted\n";
check('a well-formed region parses', fn () => $parse->invoke(null, '{"x":0.1,"y":0.2,"width":0.3,"height":0.4}', 1),
    ['x' => 0.1, 'y' => 0.2, 'width' => 0.3, 'height' => 0.4]);
check('an already-decoded array is accepted', fn () => $parse->invoke(null, ['x' => 1, 'y' => 2, 'width' => 3, 'height' => 4], 2),
    ['x' => 1.0, 'y' => 2.0, 'width' => 3.0, 'height' => 4.0]);

echo "\nRefused rather than guessed\n";
check('the left/top shape throws', fn () => $parse->invoke(null, '{"left":0.1,"top":0.2,"width":0.3,"height":0.4}', 3), 'THROWS');
check('a missing x throws', fn () => $parse->invoke(null, '{"y":0.2,"width":0.3,"height":0.4}', 4), 'THROWS');
check('a non-numeric x throws', fn () => $parse->invoke(null, '{"x":"abc","y":0.2,"width":0.3,"height":0.4}', 5), 'THROWS');
check('malformed json throws', fn () => $parse->invoke(null, 'not json', 6), 'THROWS');
check('null throws', fn () => $parse->invoke(null, null, 7), 'THROWS');

echo "\nRefused because it would mask nothing while reporting success\n";
check('zero width throws', fn () => $parse->invoke(null, '{"x":0.1,"y":0.2,"width":0,"height":0.4}', 8), 'THROWS');
check('negative height throws', fn () => $parse->invoke(null, '{"x":0.1,"y":0.2,"width":0.3,"height":-1}', 9), 'THROWS');

printf("\n%d passed, %d failed\n", $passed, $failed);
exit(0 === $failed ? 0 : 1);
