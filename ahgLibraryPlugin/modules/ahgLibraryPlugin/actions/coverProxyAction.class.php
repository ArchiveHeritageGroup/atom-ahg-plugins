<?php

class ahgLibraryPluginCoverProxyAction extends sfAction
{
    public function execute($request)
    {
        sfConfig::set('sf_web_debug', false);
        
        $isbn = preg_replace('/[^0-9X]/i', '', $request->getParameter('isbn', ''));
        $size = $request->getParameter('size', 'M');
        
        if (empty($isbn)) {
            $this->getResponse()->setStatusCode(404);
            return sfView::NONE;
        }
        
        if (!in_array($size, ['S', 'M', 'L'])) {
            $size = 'M';
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
            $this->getResponse()->setStatusCode(404);
            return sfView::NONE;
        }
        
        $this->getResponse()->clearHttpHeaders();
        $this->getResponse()->setHttpHeader('Content-Type', 'image/jpeg');
        $this->getResponse()->setHttpHeader('Cache-Control', 'public, max-age=86400');
        $this->getResponse()->setHttpHeader('Content-Length', strlen($imageData));
        $this->getResponse()->setContent($imageData);
        $this->getResponse()->send();
        
        exit;
    }
}
