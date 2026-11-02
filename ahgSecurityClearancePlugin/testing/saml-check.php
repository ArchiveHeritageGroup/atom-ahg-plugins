<?php
/**
 * SAML sign-in check (#200). Offline: plays the identity provider with a
 * throwaway key, builds signed responses and runs them through
 * SamlClient::finish(), so php-saml's own validation is what is tested.
 * No network, no database.
 *
 * Run: php ahgSecurityClearancePlugin/testing/saml-check.php
 */
require '/usr/share/nginx/archive/atom-framework/vendor/autoload.php';

use OneLogin\Saml2\Utils;

// Stand-ins for the two classes SamlClient needs from AtoM.
class SsoSettings
{
    public static $v = [];
    public static function get($k) { return self::$v[$k] ?? ''; }
}
class sfUser
{
    public $a = [];
    public function getAttribute($k) { return $this->a[$k] ?? null; }
    public function setAttribute($k, $v) { $this->a[$k] = $v; }
    public function getAttributeHolder() { $u = $this; return new class($u) { private $u; public function __construct($u) { $this->u = $u; } public function remove($k) { unset($this->u->a[$k]); } }; }
}
require __DIR__.'/../lib/Sso/SamlClient.php';

function keypair(): array
{
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    $csr = openssl_csr_new(['commonName' => 'test-idp'], $key);
    $crt = openssl_csr_sign($csr, null, $key, 1);
    openssl_x509_export($crt, $certPem);
    openssl_pkey_export($key, $keyPem);

    return [$keyPem, $certPem];
}

[$idpKey, $idpCert] = keypair();
[$otherKey, $otherCert] = keypair();

$base = 'https://archive.example.org';
SsoSettings::$v = [
    'sso_saml_idp_entity_id' => 'https://idp.example.org/idp',
    'sso_saml_idp_sso_url' => 'https://idp.example.org/sso',
    'sso_saml_idp_x509cert' => $idpCert,
    'sso_saml_email_attribute' => 'mail',
    'sso_saml_name_attribute' => 'displayName',
    'sso_saml_groups_attribute' => 'groups',
];
$_SERVER['HTTP_HOST'] = 'archive.example.org';
$_SERVER['HTTPS'] = 'on';
$_SERVER['SERVER_PORT'] = '443';
$_SERVER['REQUEST_URI'] = '/sso/saml/acs';

function response(array $o): string
{
    $now = time();
    $t = static fn ($s) => gmdate('Y-m-d\TH:i:s\Z', $s);
    $notAfter = $t($now + ($o['ttl'] ?? 300));
    $assertion = '<saml:Assertion xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_a'.bin2hex(random_bytes(8)).'" Version="2.0" IssueInstant="'.$t($now).'">'
        .'<saml:Issuer>https://idp.example.org/idp</saml:Issuer>'
        .'<saml:Subject><saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">u123</saml:NameID>'
        .'<saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer"><saml:SubjectConfirmationData NotOnOrAfter="'.$notAfter.'" Recipient="https://archive.example.org/sso/saml/acs" InResponseTo="'.$o['inResponseTo'].'"/></saml:SubjectConfirmation></saml:Subject>'
        .'<saml:Conditions NotBefore="'.$t($now - 60).'" NotOnOrAfter="'.$notAfter.'"><saml:AudienceRestriction><saml:Audience>https://archive.example.org/sso/saml/metadata</saml:Audience></saml:AudienceRestriction></saml:Conditions>'
        .'<saml:AuthnStatement AuthnInstant="'.$t($now).'" SessionIndex="_s1"><saml:AuthnContext><saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:Password</saml:AuthnContextClassRef></saml:AuthnContext></saml:AuthnStatement>'
        .'<saml:AttributeStatement>'
        .'<saml:Attribute Name="mail"><saml:AttributeValue>'.$o['email'].'</saml:AttributeValue></saml:Attribute>'
        .'<saml:Attribute Name="displayName"><saml:AttributeValue>Test User</saml:AttributeValue></saml:Attribute>'
        .'<saml:Attribute Name="groups"><saml:AttributeValue>archivists</saml:AttributeValue><saml:AttributeValue>staff</saml:AttributeValue></saml:Attribute>'
        .'</saml:AttributeStatement></saml:Assertion>';
    $signed = Utils::addSign($assertion, $o['key'], $o['cert']);
    // The schema wants the signature right after the Issuer; addSign puts it
    // first. An enveloped signature does not cover its own position.
    $doc = new DOMDocument();
    $doc->loadXML($signed);
    $root = $doc->documentElement;
    $sig = $doc->getElementsByTagNameNS('http://www.w3.org/2000/09/xmldsig#', 'Signature')->item(0);
    $issuer = $doc->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:assertion', 'Issuer')->item(0);
    $root->insertBefore($sig, $issuer->nextSibling);
    $signed = $doc->saveXML($root);
    if (!empty($o['tamper'])) {
        $signed = str_replace($o['email'], 'attacker@example.org', $signed);
    }
    $xml = '<samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_r'.bin2hex(random_bytes(8)).'" Version="2.0" IssueInstant="'.$t($now).'" Destination="https://archive.example.org/sso/saml/acs" InResponseTo="'.$o['inResponseTo'].'">'
        .'<saml:Issuer>https://idp.example.org/idp</saml:Issuer>'
        .'<samlp:Status><samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/></samlp:Status>'
        .$signed.'</samlp:Response>';

    return base64_encode($xml);
}

$fail = 0;
function attempt(array $o, string $sessionRequestId = '_req1')
{
    $_POST['SAMLResponse'] = response($o + ['inResponseTo' => '_req1', 'key' => $GLOBALS['idpKey'], 'cert' => $GLOBALS['idpCert'], 'email' => 'Someone@Example.org']);
    $user = new sfUser();
    $user->setAttribute('ahg_sso_saml', ['request_id' => $sessionRequestId, 'next' => '/after', 'started' => time()]);
    try {
        return SamlClient::finish($user, 'https://archive.example.org');
    } catch (\Throwable $e) {
        return $e;
    }
}
function check(bool $ok, string $what) { global $fail; echo ($ok ? 'PASS ' : 'FAIL ').$what."\n"; $fail += $ok ? 0 : 1; }

$r = attempt([]);
check(is_array($r) && 'someone@example.org' === $r['email'] && 'Test User' === $r['name'] && ['archivists', 'staff'] === $r['groups'] && '/after' === $r['next'], 'signed response accepted: e-mail lower-cased, name, groups, return address');
check(attempt(['key' => $otherKey, 'cert' => $otherCert]) instanceof \Throwable, 'response signed by another key refused');
check(attempt(['tamper' => true]) instanceof \Throwable, 'assertion changed after signing refused');
check(attempt([], '_someOtherRequest') instanceof \Throwable, 'response to a different sign-in request refused');
// php-saml allows 3 minutes of clock drift, so expire it well beyond that.
check(attempt(['ttl' => -600]) instanceof \Throwable, 'response expired 10 minutes ago refused');
check(attempt(['email' => '']) instanceof \Throwable, 'response without an e-mail address refused');
$_SERVER['REQUEST_URI'] = '/elsewhere';
check(attempt([]) instanceof \Throwable, 'response posted to the wrong address refused');
$_SERVER['REQUEST_URI'] = '/sso/saml/acs';

$xml = SamlClient::metadata($base);
check(false !== strpos($xml, 'https://archive.example.org/sso/saml/acs') && false !== strpos($xml, 'entityID="https://archive.example.org/sso/saml/metadata"'), 'service provider metadata names our entity id and reply address');

echo $fail ? "$fail failed\n" : "all passed\n";
exit($fail ? 1 : 0);
