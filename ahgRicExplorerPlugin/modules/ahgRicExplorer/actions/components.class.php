<?php
use Illuminate\Database\Capsule\Manager as DB;
/**
 * RiC Explorer Component - Fixed Label Extraction
 * 
 * Properly extracts entity names instead of IDs for graph visualization.
 */

class ahgRicExplorerComponents extends sfComponents
{
  protected $fusekiEndpoint = 'http://192.168.0.112:3030/ric/query';
  protected $baseUri = 'https://archives.theahg.co.za/ric/atom-psis';
  
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
      CURLOPT_TIMEOUT => 10,
      CURLOPT_CONNECTTIMEOUT => 5
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$response) {
      error_log("RiC SPARQL error: HTTP $httpCode - $error");
      return null;
    }
    
    return json_decode($response, true);
  }
  
  public function executeRicPanel()
  {
    $this->ricData = [
      'creators' => [],
      'relatedRecords' => [],
      'graphData' => ['nodes' => [], 'edges' => []]
    ];
    
    if (!isset($this->resource) || !$this->resource->id) {
      return sfView::SUCCESS;
    }
    
    $recordId = $this->resource->id;
    
    // Try both URI patterns
    $recordUris = [
      $this->baseUri . '/recordset/' . $recordId,
      $this->baseUri . '/record/' . $recordId
    ];
    
    $this->ricData['creators'] = $this->fetchCreators($recordUris, $recordId);
    $this->ricData['relatedRecords'] = $this->fetchRelatedRecords($recordUris, $recordId);
    $this->ricData['graphData'] = $this->buildGraphData($recordUris, $recordId);
    
    return sfView::SUCCESS;
  }
  
  protected function fetchCreators($recordUris, $recordId)
  {
    $creators = [];
    $uriFilter = '<' . implode('>, <', $recordUris) . '>';
    
    $query = <<<SPARQL
PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>

SELECT DISTINCT ?creator ?name ?dates ?type WHERE {
  {
    ?record rico:hasCreator ?creator .
    FILTER(?record IN ({$uriFilter}))
  } UNION {
    ?activity rico:resultsOrResultedIn ?record ;
              rico:hasOrHadParticipant ?creator .
    FILTER(?record IN ({$uriFilter}))
  }
  
  # Get the name - try multiple paths
  {
    ?creator rico:hasAgentName ?nameNode .
    ?nameNode rico:textualValue ?name .
  } UNION {
    ?creator rico:hasAgentName/rico:textualValue ?name .
  }
  
  OPTIONAL { ?creator rico:hasBeginningDate ?dates }
  OPTIONAL { ?creator a ?type }
}
LIMIT 20
SPARQL;
    
    $result = $this->executeSparql($query);
    
    if ($result && isset($result['results']['bindings'])) {
      foreach ($result['results']['bindings'] as $row) {
        $creatorUri = $row['creator']['value'];
        $actorId = $this->extractIdFromUri($creatorUri);
        
        $creators[] = [
          'uri' => $creatorUri,
          'name' => isset($row['name']) ? $row['name']['value'] : 'Unknown Creator',
          'dates' => isset($row['dates']) ? $row['dates']['value'] : null,
          'type' => isset($row['type']) ? $this->extractTypeFromUri($row['type']['value']) : 'Agent',
          'object' => $actorId ? DB::table("actor")->join("slug", "slug.object_id", "=", "actor.id")->leftJoin("actor_i18n", "actor_i18n.id", "=", "actor.id")->where("actor.id", $actorId)->select("actor.*", "actor_i18n.authorized_form_of_name", "slug.slug")->first() : null
        ];
      }
    }
    
    return $creators;
  }
  
  protected function fetchRelatedRecords($recordUris, $recordId)
  {
    $related = [];
    $uriFilter = '<' . implode('>, <', $recordUris) . '>';
    
    $query = <<<SPARQL
PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>

SELECT DISTINCT ?record ?title ?identifier WHERE {
  ?sourceRecord rico:hasCreator ?creator .
  ?record rico:hasCreator ?creator .
  ?record rico:title ?title .
  OPTIONAL { ?record rico:identifier ?identifier }
  
  FILTER(?sourceRecord IN (<{$uriFilter}>))
  FILTER(?record NOT IN (<{$uriFilter}>))
}
LIMIT 20
SPARQL;
    
    $result = $this->executeSparql($query);
    
    if ($result && isset($result['results']['bindings'])) {
      foreach ($result['results']['bindings'] as $row) {
        $recUri = $row['record']['value'];
        $objectId = $this->extractIdFromUri($recUri);
        
        $related[] = [
          'uri' => $recUri,
          'title' => isset($row['title']) ? $row['title']['value'] : 'Untitled Record',
          'identifier' => isset($row['identifier']) ? $row['identifier']['value'] : null,
          'object' => $objectId ? DB::table("information_object as io")->join("slug", "slug.object_id", "=", "io.id")->leftJoin("information_object_i18n as ioi", "ioi.id", "=", "io.id")->where("io.id", $objectId)->select("io.*", "ioi.title", "slug.slug")->first() : null
        ];
      }
    }
    
    return $related;
  }
  
  protected function buildGraphData($recordUris, $recordId)
  {
    $nodes = [];
    $edges = [];
    $nodeIndex = [];
    
    $uriFilter = '<' . implode('>, <', $recordUris) . '>';
    
    // Query to get connected entities with their labels
    $query = <<<SPARQL
PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

SELECT DISTINCT ?subject ?subjectLabel ?subjectType ?predicate ?object ?objectLabel ?objectType 
       ?objectDate ?objectDateStart ?objectDateEnd ?objectDesc ?objectParticipant ?objectParticipantLabel WHERE {
  {
    # Outgoing relations from our record
    ?subject ?predicate ?object .
    FILTER(?subject IN ({$uriFilter}))
    FILTER(isURI(?object))
    FILTER(?predicate != <http://www.w3.org/1999/02/22-rdf-syntax-ns#type>)
    
    # Get subject label (it's our record)
    OPTIONAL { ?subject rico:title ?subjectLabel }
    OPTIONAL { ?subject a ?subjectType }
    
    # Get object label - try multiple patterns
    OPTIONAL { 
      ?object rico:title ?objTitle .
      BIND(?objTitle AS ?objectLabel)
    }
    OPTIONAL { 
      ?object rico:hasAgentName/rico:textualValue ?agentName .
      BIND(?agentName AS ?objectLabel)
    }
    OPTIONAL { 
      ?object rico:hasPlaceName/rico:textualValue ?placeName .
      BIND(?placeName AS ?objectLabel)
    }
    OPTIONAL { 
      ?object rico:hasOrHadName/rico:textualValue ?thingName .
      BIND(?thingName AS ?objectLabel)
    }
    OPTIONAL { ?object a ?objectType }
    # Get dates
    OPTIONAL { 
      ?object rico:isOrWasAssociatedWithDate ?dateNode .
      OPTIONAL { ?dateNode rico:expressedDate ?objectDate }
      OPTIONAL { ?dateNode rico:hasBeginningDate ?objectDateStart }
      OPTIONAL { ?dateNode rico:hasEndDate ?objectDateEnd }
    }
    # Get description
    OPTIONAL { ?object rico:description ?objectDesc }
    OPTIONAL { ?object rico:scopeAndContent ?objectDesc }
    # Get participant for activities
    OPTIONAL { 
      ?object rico:hasOrHadParticipant ?objectParticipant .
      ?objectParticipant rico:hasAgentName/rico:textualValue ?objectParticipantLabel
    }
  }
  UNION
  {
    # Incoming relations to our record
    ?subject ?predicate ?object .
    FILTER(?object IN ({$uriFilter}))
    FILTER(isURI(?subject))
    FILTER(?predicate != <http://www.w3.org/1999/02/22-rdf-syntax-ns#type>)
    
    # Get object label (it's our record)
    OPTIONAL { ?object rico:title ?objectLabel }
    OPTIONAL { ?object a ?objectType }
    
    # Get subject label
    OPTIONAL { 
      ?subject rico:title ?subTitle .
      BIND(?subTitle AS ?subjectLabel)
    }
    OPTIONAL { 
      ?subject rico:hasAgentName/rico:textualValue ?subAgentName .
      BIND(?subAgentName AS ?subjectLabel)
    }
    OPTIONAL { 
      ?subject rico:hasPlaceName/rico:textualValue ?subPlaceName .
      BIND(?subPlaceName AS ?subjectLabel)
    }
    OPTIONAL { 
      ?subject rico:hasActivityType ?activityType .
      BIND(?activityType AS ?subjectLabel)
    }
    OPTIONAL { ?subject a ?subjectType }
    # Get dates for subject
    OPTIONAL { 
      ?subject rico:isOrWasAssociatedWithDate ?subDateNode .
      OPTIONAL { ?subDateNode rico:expressedDate ?objectDate }
      OPTIONAL { ?subDateNode rico:hasBeginningDate ?objectDateStart }
      OPTIONAL { ?subDateNode rico:hasEndDate ?objectDateEnd }
    }
    # Get description for subject
    OPTIONAL { ?subject rico:description ?objectDesc }
    # Get participant for activities
    OPTIONAL { 
      ?subject rico:hasOrHadParticipant ?objectParticipant .
      ?objectParticipant rico:hasAgentName/rico:textualValue ?objectParticipantLabel
    }
  }
}
LIMIT 100
SPARQL;
    
    $result = $this->executeSparql($query);
    
    if ($result && isset($result['results']['bindings'])) {
      foreach ($result['results']['bindings'] as $row) {
        $subjectUri = $row['subject']['value'];
        $objectUri = $row['object']['value'];
        
        // Get labels - use extracted name or fallback to ID extraction
        $subjectLabel = isset($row['subjectLabel']) ? $row['subjectLabel']['value'] : $this->extractLabelFromUri($subjectUri);
        $objectLabel = isset($row['objectLabel']) ? $row['objectLabel']['value'] : $this->extractLabelFromUri($objectUri);
        
        $subjectType = isset($row['subjectType']) ? $this->extractTypeFromUri($row['subjectType']['value']) : 'Unknown';
        $objectType = isset($row['objectType']) ? $this->extractTypeFromUri($row['objectType']['value']) : 'Unknown';
        
        $predicateLabel = $this->extractPredicateLabel($row['predicate']['value']);
        
        // Add subject node if not exists
        if (!isset($nodeIndex[$subjectUri])) {
          $nodeIndex[$subjectUri] = count($nodes);
          $nodes[] = [
            'id' => $subjectUri,
            'label' => $this->truncateLabel($subjectLabel, 35),
            'name' => $subjectLabel,
            'type' => $subjectType,
            'identifier' => $this->extractIdFromUri($subjectUri),
            'date' => isset($row['objectDate']) ? $row['objectDate']['value'] : '',
            'dateStart' => isset($row['objectDateStart']) ? $row['objectDateStart']['value'] : '',
            'dateEnd' => isset($row['objectDateEnd']) ? $row['objectDateEnd']['value'] : '',
            'description' => isset($row['objectDesc']) ? $row['objectDesc']['value'] : '',
            'participant' => isset($row['objectParticipantLabel']) ? $row['objectParticipantLabel']['value'] : ''
          ];
        }
        
        // Add object node if not exists
        if (!isset($nodeIndex[$objectUri])) {
          $nodeIndex[$objectUri] = count($nodes);
          $nodes[] = [
            'id' => $objectUri,
            'label' => $this->truncateLabel($objectLabel, 35),
            'name' => $objectLabel,
            'type' => $objectType,
            'identifier' => $this->extractIdFromUri($objectUri),
            'date' => isset($row['objectDate']) ? $row['objectDate']['value'] : '',
            'dateStart' => isset($row['objectDateStart']) ? $row['objectDateStart']['value'] : '',
            'dateEnd' => isset($row['objectDateEnd']) ? $row['objectDateEnd']['value'] : '',
            'description' => isset($row['objectDesc']) ? $row['objectDesc']['value'] : '',
            'participant' => isset($row['objectParticipantLabel']) ? $row['objectParticipantLabel']['value'] : ''
          ];
        }
        
        // Add edge
        $edges[] = [
          'source' => $subjectUri,
          'target' => $objectUri,
          'label' => $predicateLabel
        ];
      }
    }
    
    // If no SPARQL results, try to build from local database
    if (empty($nodes)) {
      return $this->buildGraphFromDatabase($recordId);
    }
    
    error_log("RiC Graph: " . count($nodes) . " nodes, " . count($edges) . " edges"); return ['nodes' => $nodes, 'edges' => $edges];
  }
  
  /**
   * Fallback: Build graph from AtoM database if SPARQL fails
   */
  protected function buildGraphFromDatabase($recordId)
  {
    $nodes = [];
    $edges = [];
    
    $record = \QubitInformationObject::getById($recordId);
    if (!$record) {
      return ['nodes' => $nodes, 'edges' => $edges];
    }
    
    // Add current record as center node
    $recordUri = $this->baseUri . '/recordset/' . $recordId;
    $nodes[] = [
      'id' => $recordUri,
      'label' => $this->truncateLabel($record->getTitle(['cultureFallback' => true]), 35),
      'name' => $record->getTitle(['cultureFallback' => true]),
      'type' => 'RecordSet',
      'identifier' => $record->identifier
    ];
    
    // Get events/creators
    $criteria = new Criteria;
    // Criteria replaced - see Laravel query below
    $events = DB::table("event as e")
        ->leftJoin("event_i18n as ei", function($j) { $j->on("e.id", "=", "ei.id")->where("ei.culture", "=", "en"); })
        ->leftJoin("actor as a", "e.actor_id", "=", "a.id")
        ->leftJoin("actor_i18n as ai", function($j) { $j->on("a.id", "=", "ai.id")->where("ai.culture", "=", "en"); })
        ->where("e.object_id", $recordId)
        ->select("e.*", "ei.date", "ei.description", "ai.authorized_form_of_name as actor_name", "a.entity_type_id")
        ->get();
    
    foreach ($events as $event) {
      if ($event->actor) {
        $actorUri = $this->baseUri . '/person/' . $event->actor->id;
        $actorName = $event->actor->getAuthorizedFormOfName(['cultureFallback' => true]);
        
        if (!isset($nodeIndex[$actorUri])) {
          $nodeIndex[$actorUri] = true;
          
          $actorType = 'Person';
          if ($event->actor->entityTypeId) {
            $term = DB::table("term")->join("term_i18n", "term_i18n.id", "=", "term.id")->where("term.id", $event->actor->entityTypeId)->select("term.*", "term_i18n.name")->first();
            if ($term) {
              $typeName = strtolower($term->getName(['cultureFallback' => true]));
              if (strpos($typeName, 'corporate') !== false) {
                $actorType = 'CorporateBody';
              } elseif (strpos($typeName, 'family') !== false) {
                $actorType = 'Family';
              }
            }
          }
          
          $nodes[] = [
            'id' => $actorUri,
            'label' => $this->truncateLabel($actorName, 35),
            'name' => $actorName,
            'type' => $actorType,
            'identifier' => $event->actor->id
          ];
          
          // Add edge
          $edges[] = [
            'source' => $actorUri,
            'target' => $recordUri,
            'label' => 'hasCreator'
          ];
        }
      }
    }
    
    return ['nodes' => $nodes, 'edges' => $edges];
  }
  
  protected function extractIdFromUri($uri)
  {
    if (preg_match('/\/(\d+)$/', $uri, $matches)) {
      return (int)$matches[1];
    }
    return null;
  }
  
  protected function extractTypeFromUri($uri)
  {
    if (preg_match('/#(\w+)$/', $uri, $matches)) {
      return $matches[1];
    }
    if (preg_match('/\/(\w+)$/', $uri, $matches)) {
      return $matches[1];
    }
    return 'Unknown';
  }
  
  protected function extractLabelFromUri($uri)
  {
    // Try to extract meaningful label from URI
    // e.g., /recordset/776 -> "Record 776"
    // e.g., /person/900140 -> "Person 900140"
    
    if (preg_match('/\/(recordset|record|person|corporatebody|family|place|activity|production|accumulation)\/(\d+)$/', $uri, $matches)) {
      $type = ucfirst($matches[1]);
      $id = $matches[2];
      
      // Try to get actual name from database
      switch (strtolower($matches[1])) {
        case 'recordset':
        case 'record':
          $obj = DB::table("information_object as io")->join("slug", "slug.object_id", "=", "io.id")->leftJoin("information_object_i18n as ioi", "ioi.id", "=", "io.id")->where("io.id", $id)->select("io.*", "ioi.title", "slug.slug")->first();
          if ($obj) return $obj->getTitle(['cultureFallback' => true]) ?: "$type $id";
          break;
        case 'person':
        case 'corporatebody':
        case 'family':
          $obj = DB::table("actor as a")->join("slug", "slug.object_id", "=", "a.id")->leftJoin("actor_i18n as ai", function($j) { $j->on("a.id", "=", "ai.id")->where("ai.culture", "=", "en"); })->where("a.id", $id)->select("a.*", "ai.authorized_form_of_name", "slug.slug")->first();
          if ($obj) return $obj->getAuthorizedFormOfName(['cultureFallback' => true]) ?: "$type $id";
          break;
        case 'place':
          $obj = DB::table("term")->join("term_i18n", "term_i18n.id", "=", "term.id")->where("term.id", $id)->select("term.*", "term_i18n.name")->first();
          if ($obj) return $obj->getName(['cultureFallback' => true]) ?: "$type $id";
          break;
      }
      
      return "$type $id";
    }
    
    // Last resort: extract last segment
    if (preg_match('/[#\/]([^#\/]+)$/', $uri, $matches)) {
      return ucfirst(preg_replace('/([a-z])([A-Z])/', '$1 $2', $matches[1]));
    }
    
    return 'Unknown';
  }
  
  protected function extractPredicateLabel($uri)
  {
    $predicateMap = [
      'hasCreator' => 'created by',
      'hasAccumulator' => 'accumulated by',
      'isOrWasPartOf' => 'part of',
      'includes' => 'includes',
      'hasOrHadSubject' => 'about',
      'hasOrHadPlaceOfOrigin' => 'from',
      'isOrWasHeldBy' => 'held by',
      'hasInstantiation' => 'has copy',
      'resultsOrResultedIn' => 'produced',
      'hasOrHadParticipant' => 'by',
      'precedes' => 'before',
      'follows' => 'after'
    ];
    
    if (preg_match('/#(\w+)$/', $uri, $matches)) {
      $pred = $matches[1];
      return isset($predicateMap[$pred]) ? $predicateMap[$pred] : $this->camelToWords($pred);
    }
    
    return '';
  }
  
  protected function camelToWords($str)
  {
    return strtolower(preg_replace('/([a-z])([A-Z])/', '$1 $2', $str));
  }
  
  protected function truncateLabel($str, $length)
  {
    if (!$str) return 'Unknown';
    if (strlen($str) <= $length) return $str;
    return substr($str, 0, $length - 3) . '...';
  }
}