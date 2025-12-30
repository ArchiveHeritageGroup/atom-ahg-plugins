<?php
/**
 * CIDOC CRM Export Service
 *
 * Generates CIDOC-CRM compatible exports in JSON-LD and RDF formats.
 * Uses Laravel Illuminate Database
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgMuseumPlugin
 */

use Illuminate\Database\Capsule\Manager as DB;

class ahgCIDOCExportService
{
    // CIDOC-CRM namespaces
    const NS_CRM = 'http://www.cidoc-crm.org/cidoc-crm/';
    const NS_RDF = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
    const NS_RDFS = 'http://www.w3.org/2000/01/rdf-schema#';
    const NS_XSD = 'http://www.w3.org/2001/XMLSchema#';
    const NS_DC = 'http://purl.org/dc/elements/1.1/';
    const NS_DCT = 'http://purl.org/dc/terms/';
    const NS_SKOS = 'http://www.w3.org/2004/02/skos/core#';
    const NS_AAT = 'http://vocab.getty.edu/aat/';
    const NS_TGN = 'http://vocab.getty.edu/tgn/';
    const NS_ULAN = 'http://vocab.getty.edu/ulan/';

    // Export formats
    const FORMAT_JSONLD = 'jsonld';
    const FORMAT_RDFXML = 'rdfxml';
    const FORMAT_TURTLE = 'turtle';
    const FORMAT_NTRIPLES = 'ntriples';
    const FORMAT_CSV = 'csv';

    // Term type IDs (from AtoM)
    const TERM_CREATION_ID = 111;
    const TERM_PERSON_ID = 160;
    const TERM_CORPORATE_BODY_ID = 131;
    const ROOT_ID = 1;

    protected $baseUri;
    protected $format;
    protected $includeLinkedData = true;

    public function __construct()
    {
        $this->baseUri = sfConfig::get('app_siteBaseUrl', 'https://example.org');
    }

    public function setFormat($format)
    {
        $this->format = $format;
    }

    public function setIncludeLinkedData($include)
    {
        $this->includeLinkedData = $include;
    }

    /**
     * Export single information object
     */
    public function exportObject($informationObject)
    {
        // Get full data using Laravel
        $ioData = $this->getInformationObjectData($informationObject->id);
        $data = $this->mapInformationObjectData($ioData);

        switch ($this->format) {
            case self::FORMAT_RDFXML:
                return $this->toRDFXML($data);
            case self::FORMAT_TURTLE:
                return $this->toTurtle($data);
            case self::FORMAT_NTRIPLES:
                return $this->toNTriples($data);
            case self::FORMAT_CSV:
                return $this->toCSV($data);
            case self::FORMAT_JSONLD:
            default:
                return $this->toJSONLD($data);
        }
    }

    /**
     * Export collection
     */
    public function exportCollection($repositoryId = null)
    {
        $query = DB::table('information_object')
            ->join('slug', 'information_object.id', '=', 'slug.object_id')
            ->where('information_object.id', '!=', self::ROOT_ID);

        if ($repositoryId) {
            $query->where('information_object.repository_id', $repositoryId);
        }

        $objects = [];
        foreach ($query->get() as $row) {
            $ioData = $this->getInformationObjectData($row->id);
            $objects[] = $this->mapInformationObjectData($ioData);
        }

        $data = [
            '@context' => $this->getContext(),
            '@graph' => $objects
        ];

        switch ($this->format) {
            case self::FORMAT_CSV:
                return $this->toCSV($data);
            case self::FORMAT_RDFXML:
                return $this->toRDFXML($data);
            case self::FORMAT_TURTLE:
                return $this->toTurtle($data);
            case self::FORMAT_NTRIPLES:
                return $this->toNTriples($data);
            case self::FORMAT_JSONLD:
            default:
                return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
    }

    /**
     * Get information object data using Laravel
     */
    protected function getInformationObjectData($id)
    {
        $io = DB::table('information_object')
            ->where('id', $id)
            ->first();

        $ioI18n = DB::table('information_object_i18n')
            ->where('id', $id)
            ->first();

        $slug = DB::table('slug')
            ->where('object_id', $id)
            ->first();

        // Get level of description
        $level = null;
        if ($io->level_of_description_id) {
            $level = DB::table('term_i18n')
                ->where('id', $io->level_of_description_id)
                ->first();
        }

        // Get repository
        $repository = null;
        if ($io->repository_id) {
            $repository = DB::table('actor_i18n')
                ->join('slug', 'actor_i18n.id', '=', 'slug.object_id')
                ->where('actor_i18n.id', $io->repository_id)
                ->first();
        }

        // Get creators (from events)
        $creators = DB::table('event')
            ->join('actor_i18n', 'event.actor_id', '=', 'actor_i18n.id')
            ->join('actor', 'event.actor_id', '=', 'actor.id')
            ->leftJoin('slug', 'actor.id', '=', 'slug.object_id')
            ->where('event.object_id', $id)
            ->where('event.type_id', self::TERM_CREATION_ID)
            ->select('actor.*', 'actor_i18n.authorized_form_of_name', 'slug.slug')
            ->get();

        // Get creation event for dates
        $creationEvent = DB::table('event')
            ->leftJoin('event_i18n', 'event.id', '=', 'event_i18n.id')
            ->where('event.object_id', $id)
            ->where('event.type_id', self::TERM_CREATION_ID)
            ->first();

        // Get subject access points
        $subjects = DB::table('object_term_relation')
            ->join('term_i18n', 'object_term_relation.term_id', '=', 'term_i18n.id')
            ->join('term', 'object_term_relation.term_id', '=', 'term.id')
            ->where('object_term_relation.object_id', $id)
            ->where('term.taxonomy_id', 35) // Subject taxonomy
            ->select('term.*', 'term_i18n.name')
            ->get();

        // Get place access points
        $places = DB::table('object_term_relation')
            ->join('term_i18n', 'object_term_relation.term_id', '=', 'term_i18n.id')
            ->join('term', 'object_term_relation.term_id', '=', 'term.id')
            ->where('object_term_relation.object_id', $id)
            ->where('term.taxonomy_id', 42) // Place taxonomy
            ->select('term.*', 'term_i18n.name')
            ->get();

        // Get digital objects
        $digitalObjects = DB::table('digital_object')
            ->where('object_id', $id)
            ->get();

        // Get parent
        $parent = null;
        if ($io->parent_id && $io->parent_id != self::ROOT_ID) {
            $parent = DB::table('information_object')
                ->join('information_object_i18n', 'information_object.id', '=', 'information_object_i18n.id')
                ->join('slug', 'information_object.id', '=', 'slug.object_id')
                ->where('information_object.id', $io->parent_id)
                ->first();
        }

        return [
            'io' => $io,
            'i18n' => $ioI18n,
            'slug' => $slug,
            'level' => $level,
            'repository' => $repository,
            'creators' => $creators,
            'creationEvent' => $creationEvent,
            'subjects' => $subjects,
            'places' => $places,
            'digitalObjects' => $digitalObjects,
            'parent' => $parent
        ];
    }

    /**
     * Map information object data to CIDOC-CRM
     */
    protected function mapInformationObjectData($ioData)
    {
        $io = $ioData['io'];
        $i18n = $ioData['i18n'];
        $slug = $ioData['slug'];

        $uri = $this->baseUri . '/' . ($slug ? $slug->slug : $io->id);

        $data = [
            '@id' => $uri,
            '@type' => 'crm:E22_Man-Made_Object',
            'rdfs:label' => $i18n->title ?? ''
        ];

        // Identifier
        if ($io->identifier) {
            $data['crm:P1_is_identified_by'] = [
                '@type' => 'crm:E42_Identifier',
                'rdfs:label' => $io->identifier,
                'crm:P2_has_type' => [
                    '@id' => 'aat:300404621',
                    'rdfs:label' => 'accession numbers'
                ]
            ];
        }

        // Title
        if (!empty($i18n->title)) {
            $data['crm:P102_has_title'] = [
                '@type' => 'crm:E35_Title',
                'rdfs:label' => $i18n->title
            ];
        }

        // Level of Description
        if ($ioData['level']) {
            $data['crm:P2_has_type'] = [
                '@type' => 'crm:E55_Type',
                'rdfs:label' => $ioData['level']->name
            ];
        }

        // Production Event
        $production = $this->mapProductionEventData($ioData);
        if ($production) {
            $data['crm:P108i_was_produced_by'] = $production;
        }

        // Physical Description
        if (!empty($i18n->extent_and_medium)) {
            $data['crm:P3_has_note'] = [
                '@type' => 'crm:E62_String',
                '@value' => $i18n->extent_and_medium
            ];
        }

        // Scope and Content
        if (!empty($i18n->scope_and_content)) {
            if (!isset($data['crm:P3_has_note'])) {
                $data['crm:P3_has_note'] = [];
            } elseif (isset($data['crm:P3_has_note']['@type'])) {
                $data['crm:P3_has_note'] = [$data['crm:P3_has_note']];
            }
            $data['crm:P3_has_note'][] = [
                '@type' => 'crm:E62_String',
                '@value' => $i18n->scope_and_content,
                'crm:P2_has_type' => [
                    '@id' => 'aat:300435416',
                    'rdfs:label' => 'descriptions'
                ]
            ];
        }

        // Repository
        if ($ioData['repository']) {
            $data['crm:P50_has_current_keeper'] = [
                '@id' => $this->baseUri . '/repository/' . ($ioData['repository']->slug ?? $io->repository_id),
                '@type' => 'crm:E40_Legal_Body',
                'rdfs:label' => $ioData['repository']->authorized_form_of_name
            ];
        }

        // Subject access points
        if (count($ioData['subjects']) > 0) {
            $subjects = [];
            foreach ($ioData['subjects'] as $subject) {
                $subjects[] = [
                    '@type' => 'crm:E55_Type',
                    'rdfs:label' => $subject->name
                ];
            }
            $data['crm:P129_is_about'] = $subjects;
        }

        // Place access points
        if (count($ioData['places']) > 0) {
            $places = [];
            foreach ($ioData['places'] as $place) {
                $places[] = [
                    '@type' => 'crm:E53_Place',
                    'rdfs:label' => $place->name
                ];
            }
            $data['crm:P7_took_place_at'] = $places;
        }

        // Digital objects
        if (count($ioData['digitalObjects']) > 0) {
            $dos = [];
            foreach ($ioData['digitalObjects'] as $do) {
                $dos[] = [
                    '@type' => 'crm:E36_Visual_Item',
                    'rdfs:label' => $do->name ?? 'Digital object',
                    'crm:P1_is_identified_by' => [
                        '@type' => 'crm:E42_Identifier',
                        '@value' => $this->baseUri . '/uploads/' . $do->path
                    ]
                ];
            }
            $data['crm:P138i_has_representation'] = $dos;
        }

        // Parent relationship
        if ($ioData['parent']) {
            $data['crm:P46i_forms_part_of'] = [
                '@id' => $this->baseUri . '/' . $ioData['parent']->slug,
                '@type' => 'crm:E22_Man-Made_Object',
                'rdfs:label' => $ioData['parent']->title
            ];
        }

        // Access conditions
        if (!empty($i18n->access_conditions)) {
            $data['crm:P104_is_subject_to'] = [
                '@type' => 'crm:E30_Right',
                'rdfs:label' => $i18n->access_conditions
            ];
        }

        return $data;
    }

    /**
     * Map production event data
     */
    protected function mapProductionEventData($ioData)
    {
        $production = [
            '@type' => 'crm:E12_Production'
        ];

        $hasData = false;

        // Creators
        if (count($ioData['creators']) > 0) {
            $creators = [];
            foreach ($ioData['creators'] as $creator) {
                $actorData = [
                    '@id' => $this->baseUri . '/actor/' . ($creator->slug ?? $creator->id),
                    'rdfs:label' => $creator->authorized_form_of_name
                ];

                // Determine type
                if ($creator->entity_type_id == self::TERM_PERSON_ID) {
                    $actorData['@type'] = 'crm:E21_Person';
                } elseif ($creator->entity_type_id == self::TERM_CORPORATE_BODY_ID) {
                    $actorData['@type'] = 'crm:E74_Group';
                } else {
                    $actorData['@type'] = 'crm:E39_Actor';
                }

                $creators[] = $actorData;
                $hasData = true;
            }
            $production['crm:P14_carried_out_by'] = $creators;
        }

        // Creation dates
        $event = $ioData['creationEvent'];
        if ($event) {
            $timeSpan = [
                '@type' => 'crm:E52_Time-Span'
            ];

            if (!empty($event->date)) {
                $timeSpan['rdfs:label'] = $event->date;
                $hasData = true;
            }

            if (!empty($event->start_date)) {
                $timeSpan['crm:P82a_begin_of_the_begin'] = [
                    '@type' => 'xsd:date',
                    '@value' => $event->start_date
                ];
                $hasData = true;
            }

            if (!empty($event->end_date)) {
                $timeSpan['crm:P82b_end_of_the_end'] = [
                    '@type' => 'xsd:date',
                    '@value' => $event->end_date
                ];
                $hasData = true;
            }

            if ($hasData) {
                $production['crm:P4_has_time-span'] = $timeSpan;
            }
        }

        return $hasData ? $production : null;
    }

    /**
     * Get JSON-LD context
     */
    protected function getContext()
    {
        return [
            'crm' => self::NS_CRM,
            'rdf' => self::NS_RDF,
            'rdfs' => self::NS_RDFS,
            'xsd' => self::NS_XSD,
            'dc' => self::NS_DC,
            'dct' => self::NS_DCT,
            'skos' => self::NS_SKOS,
            'aat' => self::NS_AAT,
            'tgn' => self::NS_TGN,
            'ulan' => self::NS_ULAN,
            'owl' => 'http://www.w3.org/2002/07/owl#'
        ];
    }

    /**
     * Convert to JSON-LD
     */
    protected function toJSONLD($data)
    {
        $output = [
            '@context' => $this->getContext(),
            '@graph' => [$data]
        ];

        return json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Convert to RDF/XML
     */
    protected function toRDFXML($data)
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        $rdf = $xml->createElementNS(self::NS_RDF, 'rdf:RDF');
        $rdf->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:crm', self::NS_CRM);
        $rdf->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:rdfs', self::NS_RDFS);
        $rdf->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:dc', self::NS_DC);
        $xml->appendChild($rdf);

        $this->addRDFXMLResource($xml, $rdf, $data);

        return $xml->saveXML();
    }

    protected function addRDFXMLResource($xml, $parent, $data)
    {
        $type = str_replace('crm:', '', $data['@type']);
        $resource = $xml->createElementNS(self::NS_CRM, 'crm:' . $type);

        if (isset($data['@id'])) {
            $resource->setAttributeNS(self::NS_RDF, 'rdf:about', $data['@id']);
        }

        if (isset($data['rdfs:label'])) {
            $label = $xml->createElementNS(self::NS_RDFS, 'rdfs:label', htmlspecialchars($data['rdfs:label']));
            $resource->appendChild($label);
        }

        $parent->appendChild($resource);
    }

    /**
     * Convert to Turtle
     */
    protected function toTurtle($data)
    {
        $prefixes = [
            '@prefix crm: <' . self::NS_CRM . '> .',
            '@prefix rdf: <' . self::NS_RDF . '> .',
            '@prefix rdfs: <' . self::NS_RDFS . '> .',
            '@prefix xsd: <' . self::NS_XSD . '> .',
            '@prefix aat: <' . self::NS_AAT . '> .',
            ''
        ];

        $turtle = implode("\n", $prefixes) . "\n";
        $turtle .= '<' . $data['@id'] . '> a ' . $data['@type'] . ' ;\n';
        $turtle .= '    rdfs:label "' . addslashes($data['rdfs:label'] ?? '') . '" .\n';

        return $turtle;
    }

    /**
     * Convert to N-Triples
     */
    protected function toNTriples($data)
    {
        $triples = [];
        $subject = '<' . $data['@id'] . '>';

        $triples[] = $subject . ' <' . self::NS_RDF . 'type> <' . self::NS_CRM . str_replace('crm:', '', $data['@type']) . '> .';

        if (isset($data['rdfs:label'])) {
            $triples[] = $subject . ' <' . self::NS_RDFS . 'label> "' . addslashes($data['rdfs:label']) . '" .';
        }

        return implode("\n", $triples);
    }

    /**
     * Convert to CSV format
     */
    public function toCSV($data)
    {
        $rows = [];
        $headers = ['URI', 'Type', 'Title', 'Identifier', 'Description', 'Creator', 'Date', 'Repository'];
        $rows[] = $headers;
        
        $objects = isset($data['@graph']) ? $data['@graph'] : [$data];
        
        foreach ($objects as $obj) {
            $creator = '';
            if (isset($obj['crm:P108i_was_produced_by']['crm:P14_carried_out_by'])) {
                $c = $obj['crm:P108i_was_produced_by']['crm:P14_carried_out_by'];
                $creator = isset($c['rdfs:label']) ? $c['rdfs:label'] : (isset($c[0]['rdfs:label']) ? $c[0]['rdfs:label'] : '');
            }
            $date = isset($obj['crm:P108i_was_produced_by']['crm:P4_has_time-span']['rdfs:label']) 
                ? $obj['crm:P108i_was_produced_by']['crm:P4_has_time-span']['rdfs:label'] : '';
            $repo = isset($obj['crm:P50_has_current_keeper']['rdfs:label']) 
                ? $obj['crm:P50_has_current_keeper']['rdfs:label'] : '';
            $note = '';
            if (isset($obj['crm:P3_has_note'])) {
                $n = $obj['crm:P3_has_note'];
                $note = isset($n['@value']) ? substr($n['@value'], 0, 200) : (isset($n[0]['@value']) ? substr($n[0]['@value'], 0, 200) : '');
            }
            $id = isset($obj['crm:P1_is_identified_by']['rdfs:label']) ? $obj['crm:P1_is_identified_by']['rdfs:label'] : '';
            
            $rows[] = [
                $obj['@id'] ?? '',
                str_replace('crm:', '', $obj['@type'] ?? ''),
                $obj['rdfs:label'] ?? '',
                $id,
                $note,
                $creator,
                $date,
                $repo
            ];
        }
        
        $output = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
	}
}
