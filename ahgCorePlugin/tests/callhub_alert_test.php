<?php

/**
 * Pure-logic checks for CallHub alert construction.
 *
 *   php tests/callhub_alert_test.php
 *
 * No database and no spool: period(), externalRef(), entityId(), title(), message()
 * and link() are pure. The parts that touch MySQL or the filesystem - claim(),
 * release(), writeSpool() - are deliberately not exercised here.
 *
 * Most of these assert properties that prevent duplicate tickets, which is the
 * requirement the whole design exists to satisfy.
 */

require_once __DIR__ . '/../lib/Services/CallHubAlertService.php';

use AhgCore\Services\CallHubAlertService as C;

$passed = 0;
$failed = 0;

function check(string $label, $got, $want): void
{
    global $passed, $failed;
    if ($got === $want) { ++$passed; printf("  PASS  %s\n", $label); return; }
    ++$failed;
    printf("  FAIL  %s\n        expected: %s\n        actual:   %s\n", $label, var_export($want, true), var_export($got, true));
}

$row = (object) [
    'id' => 4242,
    'level' => 'error',
    'message' => 'Call to a member function getId() on null',
    'exception_class' => 'Error',
    'file' => '/usr/share/nginx/archive/plugins/x/y.php',
    'line' => 88,
    'url' => '/index.php/repository/edit',
    'hostname' => 'psis.theahg.co.za',
    'occurrences' => 3,
    'created_at' => '2026-09-11 06:00:00',
    'last_seen_at' => '2026-09-11 07:00:00',
];

echo "\nThe period is what stops a recurring fault being swallowed for ever\n";

check('period is ISO year-week', (bool) preg_match('/^\d{4}-W\d{2}$/', C::period()), true);
$jan = mktime(0, 0, 0, 1, 1, 2026);
$dec = mktime(0, 0, 0, 12, 31, 2026);
check('a January and a December timestamp differ', C::period($jan) !== C::period($dec), true);
check('the same week gives the same period', C::period($jan), C::period($jan + 3600));

echo "\nThe reference identifies system, fault and episode\n";

$ref = C::externalRef('a1b2c3d4e5f6a7b8', '2026-W37');
check('reference shape', $ref, 'psis:a1b2c3d4e5f6:2026-W37');
check('same fault same week gives the same reference', C::externalRef('a1b2c3d4e5f6a7b8', '2026-W37'), $ref);
check('same fault next week differs', C::externalRef('a1b2c3d4e5f6a7b8', '2026-W38') !== $ref, true);
check('a different fault differs', C::externalRef('ffffffffffffffff', '2026-W37') !== $ref, true);

echo "\nThe entityId is a valid UUID and deterministic\n";

$id = C::entityId($ref);
check('UUID shape', (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-5[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $id), true);
check('deterministic', C::entityId($ref), $id);
check('differs for a different reference', C::entityId(C::externalRef('a1b2c3d4e5f6a7b8', '2026-W38')) !== $id, true);

echo "\nA ticket carries a summary, never the machine detail\n";

$msg = C::message($row, $ref, C::link($row));
check('title is one line', str_contains(C::title($row), "\n"), false);
check('title names the instance', str_starts_with(C::title($row), 'PSIS:'), true);
check('message states occurrences', str_contains($msg, 'Occurrences: 3'), true);
check('message carries the reference', str_contains($msg, $ref), true);
check('message links back to the log', str_contains($msg, '/ahgSettings/errorLog?id=4242'), true);

$payload = C::payload($row, $ref, 'AHG Internal', 'johan');
foreach (['trace', 'client_ip', 'user_agent', 'http_method'] as $f) {
    check(sprintf('payload omits %s', $f), array_key_exists($f, $payload), false);
}
check('payload files to the given project', $payload['projectId'], 'AHG Internal');
check('payload eventType is alert', $payload['eventType'], 'alert');
check('payload entityId matches the reference', $payload['entityId'], C::entityId($ref));

echo "\nOnly errors warrant a ticket\n";

check('ticket levels are errors only', C::TICKET_LEVELS, ['error']);
check('warning is not a ticket level', in_array('warning', C::TICKET_LEVELS, true), false);
check('notice is not a ticket level', in_array('notice', C::TICKET_LEVELS, true), false);

echo "\nA long message is truncated, not sent whole into a title\n";

$long = clone $row;
$long->message = str_repeat('x', 400);
// <= the cap, not < it: the cap is inclusive, and asserting the wrong boundary
// failed against correct code.
check('title respects the cap', mb_strlen(C::title($long)) <= C::MAX_TITLE, true);
check('a long title is exactly the cap', mb_strlen(C::title($long)), C::MAX_TITLE);
check('truncation is marked', str_contains(C::title($long), '...'), true);

echo "\nMissing fields degrade rather than throw\n";

$bare = (object) ['id' => 1, 'level' => 'error', 'message' => 'boom'];
check('link is null without a hostname', C::link($bare), null);
check('title still builds', str_contains(C::title($bare), 'boom'), true);
check('message still builds', str_contains(C::message($bare, $ref), 'Reference: ' . $ref), true);

printf("\n%d passed, %d failed\n", $passed, $failed);
exit(0 === $failed ? 0 : 1);
