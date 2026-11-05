<?php

/**
 * Compliance autopilot rules (#153): the DPIA screen and the demonstration
 * record markers. No database.
 *   php ahgPrivacyPlugin/testing/autopilot-check.php
 * The page itself: testing/autopilot-check.js on a test instance.
 */
require_once dirname(__DIR__).'/lib/Service/ComplianceAutopilotService.php';

use ahgPrivacyPlugin\Service\ComplianceAutopilotService as A;

$fail = 0;
$ok = function (bool $c, string $m) use (&$fail) {
    echo ($c ? 'PASS ' : 'FAIL ').$m."\n";
    $fail += $c ? 0 : 1;
};

$plain = ['name' => A::AUTO_DPIA_NAME, 'purpose' => 'Cataloguing, preservation and provision of access to archival records.',
    'data_categories' => ['Email addresses', 'Phone numbers', 'National identifiers', 'Passport numbers', 'Payment card numbers', 'Bank account numbers', 'Tax numbers']];
$ok(!A::screen($plain)['high_risk'], 'every scan category on its own: no DPIA');
$ok(A::screen(['data_categories' => ['Medical history']])['triggers'] === ['special_category'], 'health data: special category');
$ok(A::screen(['purpose' => 'Facial recognition of photographs'])['triggers'] === ['biometric'], 'facial recognition: biometric');
$ok(A::screen(['purpose' => 'Systematic monitoring of readers'])['triggers'] === ['large_scale_profiling'], 'monitoring: profiling');
$ok(A::screen(['transfers_outside' => true])['triggers'] === ['cross_border_non_adequate'], 'transfer abroad without safeguards');
$ok(!A::screen(['transfers_outside' => true, 'safeguards' => 'Standard contractual clauses'])['high_risk'], 'transfer with safeguards: no trigger');
$ok(A::isDemonstrationRecord('[SYNTHETIC] Demo', ''), 'title marker');
$ok(A::isDemonstrationRecord('A letter', 'SYNTHETIC DEMONSTRATION RECORD - not real'), 'scope note marker');
$ok(!A::isDemonstrationRecord('Synthetic dyes in textiles', 'synthetic fibres'), 'the bare word is not a marker');

exit($fail ? 1 : 0);
