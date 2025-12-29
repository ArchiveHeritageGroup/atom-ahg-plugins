<?php
/**
 * Proxy cover images from Open Library to avoid mixed content issues
 */
class ahgLibraryPluginCoverProxyAction extends sfAction
{
    public function execute($request)
    {
        $isbn = preg_replace('/[^0-9X]/i', '', $request->getParameter('isbn', ''));
        $size = $request->getParameter('size', 'M'); // S, M, L
        
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
        curl_setopt($ch, CURLOPT_USERAGENT, 'AtoM/2.10');
        
        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        
        if ($httpCode !== 200 || empty($imageData)) {
            // Return a placeholder or 404
            $this->forward404('Cover not found');
        }
        
        // Output the image
        $this->getResponse()->setContentType($contentType ?: 'image/jpeg');
        $this->getResponse()->setHttpHeader('Cache-Control', 'public, max-age=86400');
        
        echo $imageData;
        
        return sfView::NONE;
    }
}
