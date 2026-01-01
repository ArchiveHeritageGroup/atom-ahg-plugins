<?php
/**
 * Standalone cover proxy - bypasses Symfony
 */
$isbn = isset($_GET['isbn']) ? preg_replace('/[^0-9X]/i', '', $_GET['isbn']) : '';
$size = isset($_GET['size']) && in_array($_GET['size'], ['S', 'M', 'L']) ? $_GET['size'] : 'M';

if (empty($isbn)) {
    http_response_code(404);
    exit;
}

$url = "https://covers.openlibrary.org/b/isbn/{$isbn}-{$size}.jpg";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'AtoM/2.10 LibraryPlugin');
$imageData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || empty($imageData) || strlen($imageData) < 1000) {
    http_response_code(404);
    exit;
}

header('Content-Type: image/jpeg');
header('Cache-Control: public, max-age=86400');
header('Content-Length: ' . strlen($imageData));
echo $imageData;
