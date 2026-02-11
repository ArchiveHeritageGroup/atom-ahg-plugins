<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * Test Fuseki Connection Action
 * AJAX endpoint for testing Fuseki SPARQL endpoint connectivity
 */
class AhgSettingsFusekiTestAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');
        
        if (!$this->getUser()->isAdministrator()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Unauthorized']));
        }
        
        $data = json_decode($request->getContent(), true);
        $endpoint = $data['endpoint'] ?? '';
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        
        if (empty($endpoint)) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'No endpoint specified']));
        }
        
        try {
            $query = 'SELECT (COUNT(*) as ?count) WHERE { ?s ?p ?o }';
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $query,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/sparql-query',
                    'Accept: application/sparql-results+json'
                ],
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5
            ]);
            
            if (!empty($username)) {
                curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
            }
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                return $this->renderText(json_encode(['success' => false, 'error' => $error]));
            }
            
            if ($httpCode !== 200) {
                return $this->renderText(json_encode(['success' => false, 'error' => "HTTP $httpCode"]));
            }
            
            $result = json_decode($response, true);
            $count = $result['results']['bindings'][0]['count']['value'] ?? 'Unknown';
            
            return $this->renderText(json_encode([
                'success' => true,
                'triple_count' => $count
            ]));
            
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }
}
