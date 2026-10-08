<?php
/**
 * Single sign-on check (#200). Offline: signs ID tokens with a throwaway RSA
 * key and checks OidcClient::verifyIdToken accepts a good token and refuses bad
 * ones; also checks the group map parsing. No network, no database.
 *
 * Run: php ahgSecurityClearancePlugin/testing/sso-check.php
 */
foreach ([dirname(__DIR__, 3).'/atom-framework/vendor/autoload.php', '/usr/share/nginx/archive/atom-framework/vendor/autoload.php'] as $a) {
    if (file_exists($a)) { require $a; break; }
}
class_exists('Illuminate\Database\Capsule\Manager');
require dirname(__DIR__).'/lib/Sso/SsoSettings.php';
require dirname(__DIR__).'/lib/Sso/OidcClient.php';

use Firebase\JWT\JWT;

$key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
$d = openssl_pkey_get_details($key);
$b64u = fn ($s) => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
$jwks = ['keys' => [['kty' => 'RSA', 'kid' => 'k1', 'use' => 'sig', 'alg' => 'RS256', 'n' => $b64u($d['rsa']['n']), 'e' => $b64u($d['rsa']['e'])]]];
openssl_pkey_export($key, $pem);
$other = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
openssl_pkey_export($other, $otherPem);

SsoSettings::useValues(['sso_oidc_issuer' => 'https://accounts.google.com', 'sso_oidc_client_id' => 'client-1',
    'sso_oidc_allowed_domains' => 'theahg.co.za', 'sso_group_map' => "staff = editor\n bad line \nadmins=Administrator"]);
$good = ['iss' => 'https://accounts.google.com', 'aud' => 'client-1', 'exp' => time() + 300, 'iat' => time(),
    'nonce' => 'n1', 'email' => 'Johan@theahg.co.za', 'email_verified' => true, 'name' => 'Test'];
$sign = fn ($claims, $pem = null) => JWT::encode($claims, $pem ?? $GLOBALS['pem'], 'RS256', 'k1');

$fail = 0;
$ok = function ($c, $m) use (&$fail) { echo ($c ? 'PASS ' : 'FAIL ').$m."\n"; $fail += $c ? 0 : 1; };
$refused = function ($claims, $m, $pem = null) use ($ok, $sign, $jwks) {
    try { OidcClient::verifyIdToken($sign($claims, $pem), $jwks, 'n1'); $ok(false, $m); } catch (\Throwable $e) { $ok(true, $m); }
};

$c = OidcClient::verifyIdToken($sign($good), $jwks, 'n1');
$ok('johan@theahg.co.za' === $c['email'], 'good token accepted, e-mail lower-cased');
$c = OidcClient::verifyIdToken($sign(['iss' => 'accounts.google.com'] + $good), $jwks, 'n1');
$ok('johan@theahg.co.za' === $c['email'], 'Google issuer without https accepted');
$refused($good, 'token signed by another key refused', $otherPem);
$refused(['iss' => 'https://evil.example'] + $good, 'wrong issuer refused');
$refused(['aud' => 'other-client'] + $good, 'wrong audience refused');
$refused(['nonce' => 'n2'] + $good, 'wrong nonce refused');
$refused(['exp' => time() - 3600] + $good, 'expired token refused');
$refused(['email_verified' => false] + $good, 'unverified e-mail refused');
$refused(['email' => 'someone@gmail.com'] + $good, 'e-mail outside the allowed domains refused');
$ok(['staff' => 'editor', 'admins' => 'administrator'] === SsoSettings::groupMap(), 'group map parsed, bad line ignored, names lower-cased');
SsoSettings::useValues(['sso_oidc_allowed_domains' => ' theahg.co.za, @example.org ']);
$ok(['theahg.co.za', 'example.org'] === OidcClient::allowedDomains(), 'allowed domains list cleaned');
echo $fail ? "\n{$fail} failed\n" : "\nall passed\n";
exit($fail ? 1 : 0);
