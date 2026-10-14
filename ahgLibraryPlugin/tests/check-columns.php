<?php
// Read-only check that the library services' queries match the schema:
// the hold, fine and loan-rule paths that used non-existent columns.
// Runs without Symfony (a stub sfConfig only gives the logger a path).
class sfConfig { public static function get($k, $d = null) { return 'sf_log_dir' === $k ? sys_get_temp_dir() : $d; } }
require dirname(__DIR__, 3) . '/atom-framework/bootstrap.php'; // run: php ahgLibraryPlugin/tests/check-columns.php
$lib = dirname(__DIR__) . '/lib/Service/';
foreach (['PatronService', 'CirculationService', 'HoldService', 'FineService'] as $c) { require_once $lib . $c . '.php'; }
$ok = true;
$t = function ($name, $fn) use (&$ok) { try { $r = $fn(); echo "ok   $name: " . json_encode($r) . "\n"; } catch (\Throwable $e) { $ok = false; echo "FAIL $name: " . $e->getMessage() . "\n"; } };
$t('loan rule monograph/public', fn () => CirculationService::getInstance()->getLoanRule('monograph', 'public')->loan_period_days ?? null);
$t('hold statistics', fn () => HoldService::getInstance()->getStatistics());
$t('hold queue', fn () => count(HoldService::getInstance()->getQueue(0)));
$t('fine statistics', fn () => FineService::getInstance()->getStatistics());
$t('fine balance', fn () => FineService::getInstance()->getPatronBalance(0));
exit($ok ? 0 : 1);
