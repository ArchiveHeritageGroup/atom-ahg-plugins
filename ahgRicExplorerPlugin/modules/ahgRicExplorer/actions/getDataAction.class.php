<?php
use Illuminate\Database\Capsule\Manager as DB;

class ahgRicExplorerGetDataAction extends sfAction
{
    protected $fusekiEndpoint = 'http://192.168.0.112:3030/ric/query';
    protected $baseUri = 'https://archives.theahg.co.za/ric/atom-psis';

    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');
        
        $recordId = $request->getParameter('id');
        if (!$recordId) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'No ID provided']));
        }
        
        $graphData = $this->buildGraphData($recordId);
        
        return $this->renderText(json_encode([
            'success' => true,
            'graphData' => $graphData
        ]));
    }
    
    protected function executeSparql($query)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->fusekiEndpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $query,
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . base64_encode('admin:admin123'),
                'Content-Type: application/sparql-query',
                'Accept: application/json'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 2
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || !$response) return null;
        return json_decode($response, true);
    }
    
    protected function buildGraphData($recordId)
    {
        $nodes = [];
        $edges = [];
        
        // Try SPARQL first
        $recordUris = [
            $this->baseUri . '/recordset/' . $recordId,
            $this->baseUri . '/record/' . $recordId
        ];
        $uriFilter = '<' . implode('>, <', $recordUris) . '>';
        
        $query = <<<SPARQL
PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>
SELECT DISTINCT ?subject ?subjectLabel ?predicate ?object ?objectLabel ?subjectType ?objectType WHERE {
  { ?subject ?predicate ?object . FILTER(?subject IN ({$uriFilter})) FILTER(isURI(?object)) }
  UNION
  { ?subject ?predicate ?object . FILTER(?object IN ({$uriFilter})) FILTER(isURI(?subject)) }
  OPTIONAL { ?subject rico:title ?subjectLabel }
  OPTIONAL { ?object rico:title ?objectLabel }
  OPTIONAL { ?subject a ?subjectType }
  OPTIONAL { ?object a ?objectType }
  FILTER(?predicate != <http://www.w3.org/1999/02/22-rdf-syntax-ns#type>)
} LIMIT 50
SPARQL;

        $result = $this->executeSparql($query);
        
        if ($result && isset($result['results']['bindings']) && count($result['results']['bindings']) > 0) {
            $nodeIndex = [];
            foreach ($result['results']['bindings'] as $row) {
                $subjectUri = $row['subject']['value'];
                $objectUri = $row['object']['value'];
                
                if (!isset($nodeIndex[$subjectUri])) {
                    $nodeIndex[$subjectUri] = true;
                    $nodes[] = [
                        'id' => $subjectUri,
                        'label' => isset($row['subjectLabel']) ? $row['subjectLabel']['value'] : $this->extractLabel($subjectUri),
                        'type' => isset($row['subjectType']) ? $this->extractType($row['subjectType']['value']) : 'Unknown'
                    ];
                }
                if (!isset($nodeIndex[$objectUri])) {
                    $nodeIndex[$objectUri] = true;
                    $nodes[] = [
                        'id' => $objectUri,
                        'label' => isset($row['objectLabel']) ? $row['objectLabel']['value'] : $this->extractLabel($objectUri),
                        'type' => isset($row['objectType']) ? $this->extractType($row['objectType']['value']) : 'Unknown'
                    ];
                }
                $edges[] = ['source' => $subjectUri, 'target' => $objectUri];
            }
        } else {
            // Fallback to database
            return $this->buildGraphFromDatabase($recordId);
        }
        
        return ['nodes' => $nodes, 'edges' => $edges];
    }
    
    protected function buildGraphFromDatabase($recordId)
    {
        $nodes = [];
        $edges = [];
        
        $record = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', 'io.id', '=', 'ioi.id')
            ->where('io.id', $recordId)
            ->select('io.*', 'ioi.title')
            ->first();
            
        if (!$record) return ['nodes' => $nodes, 'edges' => $edges];
        
        $recordUri = $this->baseUri . '/record/' . $recordId;
        $nodes[] = [
            'id' => $recordUri,
            'label' => $record->title ?: 'Record ' . $recordId,
            'type' => 'RecordSet'
        ];
        
        // Get creators via events
        $events = DB::table('event as e')
            ->leftJoin('actor as a', 'e.actor_id', '=', 'a.id')
            ->leftJoin('actor_i18n as ai', function($j) { $j->on('a.id', '=', 'ai.id')->where('ai.culture', '=', 'en'); })
            ->where('e.object_id', $recordId)
            ->whereNotNull('e.actor_id')
            ->select('a.id as actor_id', 'ai.authorized_form_of_name')
            ->get();
            
        foreach ($events as $event) {
            if ($event->actor_id) {
                $actorUri = $this->baseUri . '/person/' . $event->actor_id;
                $nodes[] = [
                    'id' => $actorUri,
                    'label' => $event->authorized_form_of_name ?: 'Actor ' . $event->actor_id,
                    'type' => 'Person'
                ];
                $edges[] = ['source' => $actorUri, 'target' => $recordUri];
            }
        }
        
        return ['nodes' => $nodes, 'edges' => $edges];
    }
    
    protected function extractLabel($uri) {
        if (preg_match('/\/(place|term)\/(\d+)$/', $uri, $m)) {
            $id = $m[2];
            $term = DB::table('term_i18n')->where('id', $id)->where('culture', 'en')->value('name');
            if ($term) return $term;
        }
        if (preg_match('/\/(person|actor|corporatebody|family)\/(\d+)$/', $uri, $m)) {
            $id = $m[2];
            $name = DB::table('actor_i18n')->where('id', $id)->where('culture', 'en')->value('authorized_form_of_name');
            if ($name) return $name;
        }
        if (preg_match('/\/(record|recordset)\/(\d+)$/', $uri, $m)) {
            $id = $m[2];
            $title = DB::table('information_object_i18n')->where('id', $id)->where('culture', 'en')->value('title');
            if ($title) return $title;
        }
        if (preg_match('/\/(\w+)\/(\d+)$/', $uri, $m)) return ucfirst($m[1]) . ' ' . $m[2];
        return 'Unknown';
    }
    
    protected function extractType($uri) {
        if (preg_match('/#(\w+)$/', $uri, $m)) return $m[1];
        return 'Unknown';
    }
}
