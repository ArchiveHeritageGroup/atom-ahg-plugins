<?php
/**
 * Heratio Docs — GitHub Webhook Listener
 * Triggered by push to ArchiveHeritageGroup/atom-extensions-catalog
 */

define('WEBHOOK_SECRET', '94e504e3930f85d470aee9394a3597124944409025dbfa76a22a54473b5d63bb');
define('BUILD_SCRIPT',   '/usr/share/nginx/archive/atom-extensions-catalog/bin/build');
define('LOG_FILE',       '/var/log/heratio-docs-webhook.log');
define('ALLOWED_BRANCH', 'refs/heads/main');

function log_msg(string $msg): void {
    file_put_contents(LOG_FILE, '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL, FILE_APPEND);
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Read raw payload
$payload = file_get_contents('php://input');

// Verify GitHub signature
$sigHeader = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
if (empty($sigHeader)) {
    log_msg('ERROR: Missing X-Hub-Signature-256 header');
    http_response_code(401);
    exit('Unauthorized');
}

$expected = 'sha256=' . hash_hmac('sha256', $payload, WEBHOOK_SECRET);
if (!hash_equals($expected, $sigHeader)) {
    log_msg('ERROR: Invalid signature');
    http_response_code(401);
    exit('Unauthorized');
}

// Decode payload
$data = json_decode($payload, true);
$ref  = $data['ref'] ?? '';

log_msg("Received push event for ref: {$ref}");

// Only rebuild on push to main
if ($ref !== ALLOWED_BRANCH) {
    log_msg("Skipping — not main branch");
    http_response_code(200);
    exit('Skipped');
}

// Run build script asynchronously
$cmd = 'sudo ' . BUILD_SCRIPT . ' >> ' . LOG_FILE . ' 2>&1 &';
exec($cmd);

log_msg("Build triggered: {$cmd}");
http_response_code(200);
echo json_encode(['status' => 'build triggered', 'ref' => $ref]);
