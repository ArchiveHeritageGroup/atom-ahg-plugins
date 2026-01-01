<?php

class ahgLibraryPluginCoverProxyAction extends sfAction
{
    public function execute($request)
    {
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        $isbn = preg_replace('/[^0-9X]/i', '', $request->getParameter('isbn', ''));
        $size = $request->getParameter('size', 'M');
        
        if (empty($isbn) || !in_array($size, ['S', 'M', 'L'])) {
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
        
        header_remove();
        header('Content-Type: image/jpeg');
        header('Cache-Control: public, max-age=86400');
        header('Content-Length: ' . strlen($imageData));
        echo $imageData;
        exit;
    }
}
