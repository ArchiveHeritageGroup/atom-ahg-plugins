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
// This assertion previously expected projectId === 'AHG Internal'. That encoded a
// mistake: projectId is a workbench projects.id uuid and CallHub assigns the client
// itself, so a client name must be dropped rather than forwarded.
check('payload drops a client name given as the project', array_key_exists('projectId', $payload), false);
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

// --- priority (added after Johan's 11 Sep 2026 rule: not everything is high) ---
// The workbench honours priority ONLY as an anchored "Priority: x" line in the body
// (callhubTickets.ts priorityFor), so these assert the literal line shape as much as
// the value. A spool file has no priority field - this line is the whole channel.
check('priority: unset falls back to the house default', C::priority(null), 'medium');
check('priority: empty falls back to the house default', C::priority(''), 'medium');
check('priority: unrecognised is coerced, never passed through', C::priority('HIGHEST'), 'medium');
check('priority: plausible-but-wrong is coerced', C::priority('urgent'), 'medium');
check('priority: a valid value is kept', C::priority('high'), 'high');
check('priority: case and space tolerated', C::priority('  HIGH  '), 'high');
check('priority: critical is valid', C::priority('critical'), 'critical');
check('priority: low is valid', C::priority('low'), 'low');

$prow = (object) [
    'id' => 42, 'level' => 'error', 'message' => 'Call to a member function add() on null',
    'file' => '/x/y.php', 'line' => 7, 'exception_class' => 'Error',
    'occurrences' => 3, 'created_at' => '2026-09-11 08:00:00', 'last_seen_at' => '2026-09-11 09:00:00',
    'hostname' => 'psis.theahg.co.za', 'url' => '/informationobject/browse',
];
$body = C::message($prow, 'psis:abc:2026-W37', null, 'high');

// This is the workbench's own regex. An anchored per-line match, so a trailing word
// on that line would silently miss and the ticket would fall back to their default.
$WB = '/^\s*Priority:\s*(critical|high|medium|low)\s*$/im';
check('body carries a line the workbench regex matches', (bool) preg_match($WB, $body), true);
check('and it carries the declared value', preg_match($WB, $body, $m) ? $m[1] : null, 'high');
check('the priority line comes first', str_starts_with($body, "Priority: high\n"), true);
check('a coerced priority still emits a matching line',
    (bool) preg_match($WB, C::message($prow, 'r', null, 'nonsense')), true);
check('and the coerced line reads medium',
    preg_match($WB, C::message($prow, 'r', null, 'nonsense'), $m2) ? $m2[1] : null, 'medium');

// Priority is descriptive. Reassessing a fault must never re-ticket it.
$p1 = C::payload($prow, 'psis:abc:2026-W37', 'AHG Internal', 'johan', 'low');
$p2 = C::payload($prow, 'psis:abc:2026-W37', 'AHG Internal', 'johan', 'critical');
check('entityId is unchanged by a priority change', $p1['entityId'], $p2['entityId']);
check('but the body does reflect it', $p1['message'] !== $p2['message'], true);

// --- projectId is a workbench uuid, not a client name (workbench, 11 Sep 2026) ---
// Sending "AHG Internal" here was my error: that is a CallHub CLIENT, which CallHub
// assigns itself. A wrong uuid sends a reader into the wrong project tree.
check('a client name is NOT sent as projectId',
    array_key_exists('projectId', C::payload($prow, 'r', 'AHG Internal', 'johan')), false);
check('an empty project omits the field',
    array_key_exists('projectId', C::payload($prow, 'r', '', 'johan')), false);
check('a non-uuid string is omitted',
    array_key_exists('projectId', C::payload($prow, 'r', 'psis', 'johan')), false);
check('a real uuid IS sent',
    C::payload($prow, 'r', '9ca93d45-4523-4de8-af72-e2a42b93d1ae', 'johan')['projectId'] ?? null,
    '9ca93d45-4523-4de8-af72-e2a42b93d1ae');
check('a uuid is normalised to lower case',
    C::payload($prow, 'r', '9CA93D45-4523-4DE8-AF72-E2A42B93D1AE', 'johan')['projectId'] ?? null,
    '9ca93d45-4523-4de8-af72-e2a42b93d1ae');

// --- critical-path escalation (workbench proposal, 11 Sep 2026) ---
// The list is a fact about the code path, not a reading of the message. These assert
// the boundary behaviour, because a sloppy suffix match is the way this goes wrong.
$crit = (object) ['file' => '/usr/share/nginx/archive/atom-framework/src/Console/Commands/Tools/ExpireDataCommand.php'];
$safe = (object) ['file' => '/usr/share/nginx/archive/atom-ahg-plugins/ahgCorePlugin/lib/Services/CallHubAlertService.php'];

check('a listed path is critical', C::isCriticalPath($crit->file), true);
check('an unlisted path is not', C::isCriticalPath($safe->file), false);
check('a relative listed path matches too',
    C::isCriticalPath('atom-ahg-plugins/ahgIntegrityPlugin/lib/Services/IntegrityService.php'), true);
check('null file is not critical', C::isCriticalPath(null), false);
check('empty file is not critical', C::isCriticalPath(''), false);

// The failure this guards: a suffix match that ignores the path boundary would
// escalate any file whose NAME merely ends with a listed basename.
check('a same-named file elsewhere does NOT match',
    C::isCriticalPath('/usr/share/nginx/archive/vendor/evil/ExpireDataCommand.php'), false);
check('a longer basename does NOT match',
    C::isCriticalPath('/usr/share/nginx/archive/atom-framework/src/Console/Commands/Tools/MyExpireDataCommand.php'), false);

// Escalation only, never demotion.
check('a critical path overrides a configured high', C::priorityFor($crit, 'high'), 'critical');
check('a critical path overrides a configured low', C::priorityFor($crit, 'low'), 'critical');
check('a critical path overrides an unset priority', C::priorityFor($crit, ''), 'critical');
check('an ordinary path keeps the configured priority', C::priorityFor($safe, 'high'), 'high');
check('an ordinary path with no config gets the default', C::priorityFor($safe, ''), 'medium');
check('a configured critical is never demoted', C::priorityFor($safe, 'critical'), 'critical');
check('a row with no file at all keeps the configured priority',
    C::priorityFor((object) ['level' => 'error'], 'high'), 'high');

// And it reaches the body the workbench actually reads.
$critBody = C::message($crit, 'psis:x:2026-W37', null, C::priorityFor($crit, 'medium'));
check('an escalated ticket says critical in the body',
    preg_match('/^\s*Priority:\s*(\w+)\s*$/im', $critBody, $mc) ? $mc[1] : null, 'critical');

// THE GUARD THAT MATTERS: a rename must break this test, not silently demote the path.
$atomRoot = dirname(dirname(dirname(__DIR__)));
$missing = C::criticalPathsMissing($atomRoot);
check(
    'every listed critical path still exists (a rename must fail here, not demote silently): '
        . ($missing ? implode(', ', $missing) : 'all present'),
    $missing,
    []
);
check('the list is small enough to maintain by hand (<= 12)', count(C::CRITICAL_PATHS) <= 12, true);

printf("\n%d passed, %d failed\n", $passed, $failed);
exit(0 === $failed ? 0 : 1);
