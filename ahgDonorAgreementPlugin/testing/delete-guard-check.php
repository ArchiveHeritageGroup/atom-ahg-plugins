<?php
// Offline check: php ahgDonorAgreementPlugin/testing/delete-guard-check.php
// Uses stubs only, so it touches no database and needs no Symfony.
namespace AtomFramework\Http\Controllers {
    class AhgController
    {
        public $user;
        public function getUser() { return $this->user; }
        public function forward404($m = '') { throw new \RuntimeException('404'); }
        public function redirect($to) { throw new \RuntimeException('redirect'); }
    }
}
namespace AtomFramework\Services {
    class CsrfService
    {
        public static $sent;
        public static function getTokenFromRequest() { return self::$sent; }
        public static function validateToken(string $t): bool { return 'good' === $t; }
    }
}
namespace AtomFramework\Services\DonorAgreement {
    class DonorAgreementService
    {
        public static $deleted = 0;
        public function delete(int $id): bool { ++self::$deleted; return true; }
    }
}
namespace AtomExtensions\Services {
    class AclService { public static function forwardToSecureAction(): void {} }
}
namespace AhgCore\Core {
    class AhgDb { public static function init() {} }
}
namespace {
    class sfView { const NONE = 'None'; }
    class U
    {
        public function __construct(public bool $auth, public array $creds) {}
        public function isAuthenticated() { return $this->auth; }
        public function hasCredential($c, $and = true) { return (bool) array_intersect($c, $this->creds); }
        public function setFlash($k, $v) {}
    }
    class Req
    {
        public function __construct(public string $m) {}
        public function isMethod($x) { return $x === $this->m; }
        public function getParameter($k) { return 4; }
    }
    require __DIR__ . '/../modules/donorAgreement/actions/deleteAction.class.php';

    $cases = [
        ['signed out, POST', false, [], 'post', 'good', false],
        ['editor, GET', true, ['editor'], 'get', 'good', false],
        ['admin, GET', true, ['administrator'], 'get', 'good', false],
        ['researcher, POST, good token', true, ['researcher'], 'post', 'good', false],
        ['contributor, POST, good token', true, ['contributor'], 'post', 'good', false],
        ['editor, POST, no token', true, ['editor'], 'post', null, false],
        ['editor, POST, wrong token', true, ['editor'], 'post', 'bad', false],
        ['editor, POST, good token', true, ['editor'], 'post', 'good', true],
        ['admin, POST, good token', true, ['administrator'], 'post', 'good', true],
    ];
    $fail = 0;
    foreach ($cases as [$name, $auth, $creds, $method, $token, $want]) {
        \AtomFramework\Services\DonorAgreement\DonorAgreementService::$deleted = 0;
        \AtomFramework\Services\CsrfService::$sent = $token;
        $a = new donorAgreementDeleteAction();
        $a->user = new U($auth, $creds);
        try { $a->execute(new Req($method)); } catch (\RuntimeException $e) {}
        $got = 1 === \AtomFramework\Services\DonorAgreement\DonorAgreementService::$deleted;
        if ($got !== $want) { ++$fail; echo "FAIL $name\n"; }
    }
    echo $fail ? "$fail failed\n" : 'All ' . count($cases) . " checks passed\n";
    exit($fail ? 1 : 0);
}
