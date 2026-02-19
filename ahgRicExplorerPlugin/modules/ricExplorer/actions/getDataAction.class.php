<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

class ricExplorerGetDataAction extends AhgController
{
    protected $fusekiEndpoint;
    protected $fusekiUsername;
    protected $fusekiPassword;
    protected $baseUri;
    protected $instanceId;

    public function boot(): void
    {
// Load from database settings (AHG Settings UI), fallback to sfConfig
        $config = $this->getConfigSettings();
        $this->fusekiEndpoint = ($config['fuseki_endpoint'] ?? $this->config('app_ric_fuseki_endpoint', 'http://localhost:3030/ric')) . '/query';
        $this->fusekiUsername = $config['fuseki_username'] ?? $this->config('app_ric_fuseki_username', 'admin');
        $this->fusekiPassword = $config['fuseki_password'] ?? $this->config('app_ric_fuseki_password', '');
        $this->baseUri = $config['ric_base_uri'] ?? $this->config('app_ric_base_uri', 'https://archives.theahg.co.za/ric');
        $this->instanceId = $config['ric_instance_id'] ?? $this->config('app_ric_instance_id', 'atom-psis');
    }

    protected function getConfigSettings(): array
    {
        try {
            // Read from ahg_settings table (AHG Settings UI) - fuseki section
            return DB::table('ahg_settings')
                ->where('setting_group', 'fuseki')
                ->pluck('setting_value', 'setting_key')
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        $recordId = $request->getParameter('id');
        if (!$recordId) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'No ID provided']));
        }

        if ($recordId === 'overview') {
            $graphData = $this->buildOverviewGraph();
        } else {
            $graphData = $this->buildGraphData($recordId);
        }

        return $this->renderText(json_encode([
            'success' => true,
            'graphData' => $graphData
        ]));
    }

    protected function buildOverviewGraph()
    {
        // Get top-level recordsets with their relationships
        $query = <<<SPARQL
PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>
SELECT ?s ?label ?type ?related ?relLabel ?relType ?pred WHERE {
  ?s a rico:RecordSet .
  ?s rico:title ?label .
  OPTIONAL {
    ?s ?pred ?related .
    FILTER(isURI(?related) && ?pred != <http://www.w3.org/1999/02/22-rdf-syntax-ns#type>)
    OPTIONAL { ?related rico:title ?relLabel }
    OPTIONAL { ?related a ?relType . FILTER(STRSTARTS(STR(?relType), "https://www.ica.org/standards/RiC/ontology#")) }
  }
} LIMIT 200
SPARQL;

        $result = $this->executeSparql($query);
        $nodes = [];
        $edges = [];
        $nodeIndex = [];

        if ($result && isset($result['results']['bindings'])) {
            foreach ($result['results']['bindings'] as $row) {
                $uri = $row['s']['value'];
                if (!isset($nodeIndex[$uri])) {
                    $nodeIndex[$uri] = true;
                    $nodes[] = [
                        'id' => $uri,
                        'label' => $row['label']['value'] ?? $this->extractLabel($uri),
                        'type' => 'RecordSet'
                    ];
                }
                if (isset($row['related'])) {
                    $relUri = $row['related']['value'];
                    if (!isset($nodeIndex[$relUri])) {
                        $nodeIndex[$relUri] = true;
                        $relType = isset($row['relType']) ? $this->extractType($row['relType']['value']) : $this->extractTypeFromUri($relUri);
                        $nodes[] = [
                            'id' => $relUri,
                            'label' => isset($row['relLabel']) ? $row['relLabel']['value'] : $this->extractLabel($relUri),
                            'type' => $relType
                        ];
                    }
                    $predLabel = isset($row['pred']) ? $this->extractLabel($row['pred']['value']) : '';
                    $edges[] = ['source' => $uri, 'target' => $relUri, 'label' => $predLabel];
                }
            }
        }

        $nodes = $this->enrichNodesWithSlugs($nodes);

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    protected function executeSparql($query)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->fusekiEndpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $query,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/sparql-query',
                'Accept: application/json'
            ],
            CURLOPT_USERPWD => $this->fusekiPassword ? "{$this->fusekiUsername}:{$this->fusekiPassword}" : null,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 3
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || !$response) return null;
        return json_decode($response, true);
    }
    
    protected function buildRecordUri($type, $id)
    {
        return $this->baseUri . '/' . $this->instanceId . '/' . $type . '/' . $id;
    }

    protected function buildGraphData($recordId)
    {
        $nodes = [];
        $edges = [];

        // Use correct URI pattern: {baseUri}/{instanceId}/{type}/{id}
        $recordUris = [
            $this->buildRecordUri('recordset', $recordId),
            $this->buildRecordUri('record', $recordId)
        ];
        $uriFilter = '<' . implode('>, <', $recordUris) . '>';

        // Step 1: Fast query - get relationships only (no OPTIONAL to avoid timeout on large stores)
        $query = <<<SPARQL
SELECT ?subject ?predicate ?object WHERE {
  { ?subject ?predicate ?object . FILTER(?subject IN ({$uriFilter})) FILTER(isURI(?object)) }
  UNION
  { ?subject ?predicate ?object . FILTER(?object IN ({$uriFilter})) FILTER(isURI(?subject)) }
  FILTER(?predicate != <http://www.w3.org/1999/02/22-rdf-syntax-ns#type>)
} LIMIT 100
SPARQL;

        $result = $this->executeSparql($query);

        if ($result && isset($result['results']['bindings']) && count($result['results']['bindings']) > 0) {
            $nodeIndex = [];
            $allUris = [];

            foreach ($result['results']['bindings'] as $row) {
                $subjectUri = $row['subject']['value'];
                $objectUri = $row['object']['value'];
                $predLabel = $this->extractLabel($row['predicate']['value']);

                if (!isset($nodeIndex[$subjectUri])) {
                    $nodeIndex[$subjectUri] = true;
                    $allUris[] = $subjectUri;
                }
                if (!isset($nodeIndex[$objectUri])) {
                    $nodeIndex[$objectUri] = true;
                    $allUris[] = $objectUri;
                }
                $edges[] = ['source' => $subjectUri, 'target' => $objectUri, 'label' => $predLabel];
            }

            // Step 2: Batch-fetch labels and types for discovered URIs
            $uriLabels = $this->fetchUriMetadata($allUris);

            foreach ($allUris as $uri) {
                $meta = $uriLabels[$uri] ?? [];
                $nodes[] = [
                    'id' => $uri,
                    'label' => $meta['label'] ?? $this->extractLabel($uri),
                    'type' => $meta['type'] ?? $this->extractTypeFromUri($uri)
                ];
            }
        } else {
            // Fallback to database
            return $this->buildGraphFromDatabase($recordId);
        }

        // Enrich nodes with AtoM slugs for correct URLs
        $nodes = $this->enrichNodesWithSlugs($nodes);

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    protected function fetchUriMetadata(array $uris)
    {
        if (empty($uris)) return [];

        // Batch SPARQL for labels and types - targeted query, much faster than OPTIONAL on full store
        $uriList = '<' . implode('>, <', $uris) . '>';
        $query = <<<SPARQL
PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>
SELECT ?uri ?label ?type WHERE {
  VALUES ?uri { {$uriList} }
  OPTIONAL { ?uri rico:title ?label }
  OPTIONAL { ?uri a ?type . FILTER(STRSTARTS(STR(?type), "https://www.ica.org/standards/RiC/ontology#")) }
}
SPARQL;

        $result = $this->executeSparql($query);
        $metadata = [];

        if ($result && isset($result['results']['bindings'])) {
            foreach ($result['results']['bindings'] as $row) {
                $uri = $row['uri']['value'];
                if (!isset($metadata[$uri])) $metadata[$uri] = [];
                if (isset($row['label'])) $metadata[$uri]['label'] = $row['label']['value'];
                if (isset($row['type'])) $metadata[$uri]['type'] = $this->extractType($row['type']['value']);
            }
        }

        return $metadata;
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

        $recordUri = $this->buildRecordUri('recordset', $recordId);
        $nodes[] = [
            'id' => $recordUri,
            'label' => $record->title ?: 'Record ' . $recordId,
            'type' => 'RecordSet'
        ];
        
        // Get creators via events
        $events = DB::table('event as e')
            ->leftJoin('actor as a', 'e.actor_id', '=', 'a.id')
            ->leftJoin('actor_i18n as ai', function($j) { $j->on('a.id', '=', 'ai.id')->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture()); })
            ->where('e.object_id', $recordId)
            ->whereNotNull('e.actor_id')
            ->select('a.id as actor_id', 'ai.authorized_form_of_name')
            ->get();
            
        foreach ($events as $event) {
            if ($event->actor_id) {
                $actorUri = $this->buildRecordUri('person', $event->actor_id);
                $nodes[] = [
                    'id' => $actorUri,
                    'label' => $event->authorized_form_of_name ?: 'Actor ' . $event->actor_id,
                    'type' => 'Person'
                ];
                $edges[] = ['source' => $actorUri, 'target' => $recordUri];
            }
        }
        
        $nodes = $this->enrichNodesWithSlugs($nodes);

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    protected function extractLabel($uri) {
        // Handle ontology predicate URIs (e.g., https://...#hasOrHadSubject)
        if (preg_match('/#(\w+)$/', $uri, $m)) {
            return $this->camelToReadable($m[1]);
        }
        if (preg_match('/\/(place|term|concept|documentaryformtype|carriertype|contenttype|recordstate|language)\/(\d+)$/', $uri, $m)) {
            $id = $m[2];
            $term = DB::table('term_i18n')->where('id', $id)->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())->value('name');
            if ($term) return $term;
        }
        if (preg_match('/\/(person|actor|corporatebody|family)\/(\d+)$/', $uri, $m)) {
            $id = $m[2];
            $name = DB::table('actor_i18n')->where('id', $id)->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())->value('authorized_form_of_name');
            if ($name) return $name;
        }
        if (preg_match('/\/(record|recordset)\/(\d+)$/', $uri, $m)) {
            $id = $m[2];
            $title = DB::table('information_object_i18n')->where('id', $id)->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())->value('title');
            if ($title) return $title;
        }
        if (preg_match('/\/(production|accumulation|activity|event)\/(\d+)$/', $uri, $m)) {
            $id = $m[2];
            $culture = \AtomExtensions\Helpers\CultureHelper::getCulture();
            $event = DB::table('event as e')
                ->leftJoin('event_i18n as ei', function ($j) use ($culture) {
                    $j->on('e.id', '=', 'ei.id')->where('ei.culture', '=', $culture);
                })
                ->leftJoin('term_i18n as ti', function ($j) use ($culture) {
                    $j->on('e.type_id', '=', 'ti.id')->where('ti.culture', '=', $culture);
                })
                ->where('e.id', $id)
                ->select('ti.name as type_name', 'ei.date as date_text')
                ->first();
            if ($event) {
                $label = $event->type_name ?: ucfirst($m[1]);
                if ($event->date_text) {
                    $label .= ': ' . $event->date_text;
                }
                return $label;
            }
        }
        if (preg_match('/\/instantiation\/(\d+)$/', $uri, $m)) {
            $id = $m[1];
            $do = DB::table('digital_object as d')
                ->leftJoin('information_object_i18n as ioi', function ($j) {
                    $j->on('d.object_id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                })
                ->where('d.id', $id)
                ->select('d.name', 'd.mime_type', 'ioi.title')
                ->first();
            if ($do) {
                $label = $do->name ?: ($do->title ? $do->title . ' (file)' : null);
                if ($label) {
                    $ext = pathinfo($label, PATHINFO_EXTENSION);
                    $base = pathinfo($label, PATHINFO_FILENAME);
                    // Shorten long filenames
                    if (mb_strlen($base) > 40) {
                        $base = mb_substr($base, 0, 37) . '...';
                    }
                    return $ext ? $base . '.' . $ext : $base;
                }
            }
        }
        if (preg_match('/\/(\w+)\/(\d+)$/', $uri, $m)) return ucfirst($m[1]) . ' ' . $m[2];
        return 'Unknown';
    }

    protected function extractType($uri) {
        if (preg_match('/#(\w+)$/', $uri, $m)) return $m[1];
        return 'Unknown';
    }

    protected function extractTypeFromUri($uri) {
        if (preg_match('/\/(\w+)\/\d+$/', $uri, $m)) {
            $map = [
                'recordset' => 'RecordSet', 'record' => 'Record', 'recordpart' => 'RecordPart',
                'person' => 'Person', 'family' => 'Family', 'corporatebody' => 'CorporateBody',
                'place' => 'Place', 'instantiation' => 'Instantiation',
                'production' => 'Production', 'accumulation' => 'Accumulation',
                'activity' => 'Activity', 'function' => 'Function',
                'concept' => 'Concept', 'term' => 'Concept',
                'documentaryformtype' => 'DocumentaryFormType', 'carriertype' => 'CarrierType',
                'contenttype' => 'ContentType', 'recordstate' => 'RecordState', 'language' => 'Language'
            ];
            return $map[strtolower($m[1])] ?? ucfirst($m[1]);
        }
        return 'Unknown';
    }

    protected function enrichNodesWithSlugs(array $nodes) {
        // Extract numeric IDs from URIs, batch-fetch slugs
        $idMap = []; // id => [node indices]
        foreach ($nodes as $idx => $node) {
            if (preg_match('/\/(\d+)$/', $node['id'], $m)) {
                $id = (int)$m[1];
                $idMap[$id][] = $idx;
            }
        }

        if (empty($idMap)) return $nodes;

        try {
            $slugs = DB::table('slug')
                ->whereIn('object_id', array_keys($idMap))
                ->pluck('slug', 'object_id')
                ->toArray();
        } catch (\Exception $e) {
            return $nodes;
        }

        foreach ($idMap as $id => $indices) {
            $slug = $slugs[$id] ?? null;
            foreach ($indices as $idx) {
                $nodes[$idx]['atomId'] = $id;
                if ($slug) {
                    $nodes[$idx]['atomUrl'] = '/index.php/' . $slug;
                }
            }
        }

        return $nodes;
    }

    protected function camelToReadable($str) {
        // hasOrHadSubject → Has Or Had Subject
        $result = preg_replace('/([a-z])([A-Z])/', '$1 $2', $str);
        return ucfirst($result);
    }
}
