<?php

/**
 * Cover Proxy Action - proxies Open Library covers to avoid CSP issues
 */
class ahgLibraryPluginCoverProxyAction extends sfAction
{
    public function execute($request)
    {
        $isbn = preg_replace('/[^0-9X]/i', '', $request->getParameter('isbn', ''));
        $size = $request->getParameter('size', 'M');
        
        if (empty($isbn)) {
            $this->forward404('ISBN required');
        }
        
        // Validate size
        if (!in_array($size, ['S', 'M', 'L'])) {
            $size = 'M';
        }
        
        $url = "https://covers.openlibrary.org/b/isbn/{$isbn}-{$size}.jpg";
        
        // Fetch the image
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'AtoM/2.10 LibraryPlugin');
        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        
        if ($httpCode !== 200 || empty($imageData) || strlen($imageData) < 1000) {
            // Return placeholder or 404
            $this->getResponse()->setStatusCode(404);
            return sfView::NONE;
        }
        
        // Set headers and return image
        $this->getResponse()->setContentType($contentType ?: 'image/jpeg');
        $this->getResponse()->setHttpHeader('Cache-Control', 'public, max-age=86400');
        echo $imageData;
        
        return sfView::NONE;
    }
}
