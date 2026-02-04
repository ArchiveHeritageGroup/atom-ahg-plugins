<?php

namespace AhgFederation;

/**
 * OAI-PMH Harvest Client
 *
 * Provides methods for fetching data from remote OAI-PMH repositories.
 * Supports all standard OAI-PMH verbs and handles resumption tokens.
 */
class HarvestClient
{
    protected string $userAgent = 'AHG-Federation-Harvester/1.0';
    protected int $timeout = 60;
    protected int $maxRetries = 3;
    protected int $retryDelay = 5;

    /**
     * Identify - Get repository information
     *
     * @param string $baseUrl Base URL of the OAI-PMH endpoint
     * @return array Repository identification data
     * @throws HarvestException
     */
    public function identify(string $baseUrl): array
    {
        $response = $this->request($baseUrl, ['verb' => 'Identify']);
        $xml = $this->parseResponse($response);

        $identify = $xml->Identify;
        if (!$identify) {
            throw new HarvestException('Invalid Identify response');
        }

        return [
            'repositoryName' => (string)$identify->repositoryName,
            'baseURL' => (string)$identify->baseURL,
            'protocolVersion' => (string)$identify->protocolVersion,
            'adminEmail' => (string)$identify->adminEmail,
            'earliestDatestamp' => (string)$identify->earliestDatestamp,
            'deletedRecord' => (string)$identify->deletedRecord,
            'granularity' => (string)$identify->granularity,
            'compression' => isset($identify->compression) ? (array)$identify->compression : [],
            'description' => isset($identify->description) ? $this->parseDescription($identify->description) : null,
        ];
    }

    /**
     * ListMetadataFormats - Get available metadata formats
     *
     * @param string $baseUrl Base URL of the OAI-PMH endpoint
     * @param string|null $identifier Optional identifier to check formats for specific record
     * @return array List of available metadata formats
     * @throws HarvestException
     */
    public function listMetadataFormats(string $baseUrl, ?string $identifier = null): array
    {
        $params = ['verb' => 'ListMetadataFormats'];
        if ($identifier) {
            $params['identifier'] = $identifier;
        }

        $response = $this->request($baseUrl, $params);
        $xml = $this->parseResponse($response);

        $formats = [];
        foreach ($xml->ListMetadataFormats->metadataFormat as $format) {
            $formats[] = [
                'metadataPrefix' => (string)$format->metadataPrefix,
                'schema' => (string)$format->schema,
                'metadataNamespace' => (string)$format->metadataNamespace,
            ];
        }

        return $formats;
    }

    /**
     * ListSets - Get available sets
     *
     * @param string $baseUrl Base URL of the OAI-PMH endpoint
     * @return array List of available sets
     * @throws HarvestException
     */
    public function listSets(string $baseUrl): array
    {
        $sets = [];
        $resumptionToken = null;

        do {
            $params = ['verb' => 'ListSets'];
            if ($resumptionToken) {
                $params = ['verb' => 'ListSets', 'resumptionToken' => $resumptionToken];
            }

            $response = $this->request($baseUrl, $params);
            $xml = $this->parseResponse($response);

            if (isset($xml->error)) {
                $errorCode = (string)$xml->error['code'];
                if ($errorCode === 'noSetHierarchy') {
                    return []; // Repository doesn't support sets
                }
                throw new HarvestException("OAI error: $errorCode - " . (string)$xml->error);
            }

            foreach ($xml->ListSets->set as $set) {
                $sets[] = [
                    'setSpec' => (string)$set->setSpec,
                    'setName' => (string)$set->setName,
                    'setDescription' => isset($set->setDescription) ? (string)$set->setDescription->children('http://purl.org/dc/elements/1.1/')->description : null,
                ];
            }

            $resumptionToken = isset($xml->ListSets->resumptionToken) ? (string)$xml->ListSets->resumptionToken : null;

        } while ($resumptionToken);

        return $sets;
    }

    /**
     * ListRecords - Get records with full metadata (Generator)
     *
     * @param string $baseUrl Base URL of the OAI-PMH endpoint
     * @param array $params Harvest parameters (metadataPrefix, from, until, set)
     * @return \Generator Yields individual records
     * @throws HarvestException
     */
    public function listRecords(string $baseUrl, array $params): \Generator
    {
        $requestParams = ['verb' => 'ListRecords'];
        $requestParams['metadataPrefix'] = $params['metadataPrefix'] ?? 'oai_dc';

        if (!empty($params['from'])) {
            $requestParams['from'] = $params['from'];
        }
        if (!empty($params['until'])) {
            $requestParams['until'] = $params['until'];
        }
        if (!empty($params['set'])) {
            $requestParams['set'] = $params['set'];
        }

        $resumptionToken = null;

        do {
            if ($resumptionToken) {
                $requestParams = ['verb' => 'ListRecords', 'resumptionToken' => $resumptionToken];
            }

            $response = $this->request($baseUrl, $requestParams);
            $xml = $this->parseResponse($response);

            if (isset($xml->error)) {
                $errorCode = (string)$xml->error['code'];
                if ($errorCode === 'noRecordsMatch') {
                    return; // No records found, not an error
                }
                throw new HarvestException("OAI error: $errorCode - " . (string)$xml->error);
            }

            foreach ($xml->ListRecords->record as $record) {
                yield $this->parseRecord($record, $params['metadataPrefix'] ?? 'oai_dc');
            }

            $resumptionToken = isset($xml->ListRecords->resumptionToken) ? (string)$xml->ListRecords->resumptionToken : null;

        } while ($resumptionToken);
    }

    /**
     * ListIdentifiers - Get record identifiers only (Generator)
     *
     * @param string $baseUrl Base URL of the OAI-PMH endpoint
     * @param array $params Harvest parameters (metadataPrefix, from, until, set)
     * @return \Generator Yields individual record headers
     * @throws HarvestException
     */
    public function listIdentifiers(string $baseUrl, array $params): \Generator
    {
        $requestParams = ['verb' => 'ListIdentifiers'];
        $requestParams['metadataPrefix'] = $params['metadataPrefix'] ?? 'oai_dc';

        if (!empty($params['from'])) {
            $requestParams['from'] = $params['from'];
        }
        if (!empty($params['until'])) {
            $requestParams['until'] = $params['until'];
        }
        if (!empty($params['set'])) {
            $requestParams['set'] = $params['set'];
        }

        $resumptionToken = null;

        do {
            if ($resumptionToken) {
                $requestParams = ['verb' => 'ListIdentifiers', 'resumptionToken' => $resumptionToken];
            }

            $response = $this->request($baseUrl, $requestParams);
            $xml = $this->parseResponse($response);

            if (isset($xml->error)) {
                $errorCode = (string)$xml->error['code'];
                if ($errorCode === 'noRecordsMatch') {
                    return; // No records found, not an error
                }
                throw new HarvestException("OAI error: $errorCode - " . (string)$xml->error);
            }

            foreach ($xml->ListIdentifiers->header as $header) {
                yield $this->parseHeader($header);
            }

            $resumptionToken = isset($xml->ListIdentifiers->resumptionToken) ? (string)$xml->ListIdentifiers->resumptionToken : null;

        } while ($resumptionToken);
    }

    /**
     * GetRecord - Get a single record
     *
     * @param string $baseUrl Base URL of the OAI-PMH endpoint
     * @param string $identifier Record identifier
     * @param string $metadataPrefix Metadata format prefix
     * @return array Parsed record data
     * @throws HarvestException
     */
    public function getRecord(string $baseUrl, string $identifier, string $metadataPrefix): array
    {
        $response = $this->request($baseUrl, [
            'verb' => 'GetRecord',
            'identifier' => $identifier,
            'metadataPrefix' => $metadataPrefix,
        ]);

        $xml = $this->parseResponse($response);

        if (isset($xml->error)) {
            $errorCode = (string)$xml->error['code'];
            throw new HarvestException("OAI error: $errorCode - " . (string)$xml->error);
        }

        return $this->parseRecord($xml->GetRecord->record, $metadataPrefix);
    }

    /**
     * Make HTTP request to OAI endpoint
     */
    protected function request(string $baseUrl, array $params): string
    {
        $url = $baseUrl . '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_HTTPHEADER => [
                'Accept: text/xml, application/xml',
            ],
        ]);

        $retries = 0;
        $response = false;
        $error = '';

        while ($retries < $this->maxRetries) {
            $response = curl_exec($ch);

            if ($response !== false) {
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($httpCode === 200) {
                    break;
                }
                if ($httpCode === 503) {
                    // Service temporarily unavailable, retry
                    $retries++;
                    sleep($this->retryDelay * $retries);
                    continue;
                }
                throw new HarvestException("HTTP error: $httpCode for URL: $url");
            }

            $error = curl_error($ch);
            $retries++;
            sleep($this->retryDelay * $retries);
        }

        curl_close($ch);

        if ($response === false) {
            throw new HarvestException("Failed to fetch: $url - $error");
        }

        return $response;
    }

    /**
     * Parse XML response
     */
    protected function parseResponse(string $response): \SimpleXMLElement
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($response);

        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            throw new HarvestException('Failed to parse XML response: ' . $this->formatXmlErrors($errors));
        }

        // Register OAI namespace for XPath queries
        $xml->registerXPathNamespace('oai', 'http://www.openarchives.org/OAI/2.0/');

        return $xml;
    }

    /**
     * Parse record from XML
     */
    protected function parseRecord(\SimpleXMLElement $record, string $metadataPrefix): array
    {
        $header = $this->parseHeader($record->header);

        $result = [
            'header' => $header,
            'metadata' => null,
            'about' => null,
        ];

        // Check if deleted
        if ($header['status'] === 'deleted') {
            return $result;
        }

        // Parse metadata based on format
        if (isset($record->metadata)) {
            $result['metadata'] = $this->parseMetadata($record->metadata, $metadataPrefix);
            $result['rawMetadata'] = $record->metadata->asXML();
        }

        // Parse about section if present
        if (isset($record->about)) {
            $result['about'] = $record->about->asXML();
        }

        return $result;
    }

    /**
     * Parse record header
     */
    protected function parseHeader(\SimpleXMLElement $header): array
    {
        return [
            'identifier' => (string)$header->identifier,
            'datestamp' => (string)$header->datestamp,
            'setSpec' => isset($header->setSpec) ? array_map('strval', iterator_to_array($header->setSpec)) : [],
            'status' => isset($header['status']) ? (string)$header['status'] : 'active',
        ];
    }

    /**
     * Parse metadata element based on format
     */
    protected function parseMetadata(\SimpleXMLElement $metadata, string $metadataPrefix): array
    {
        switch ($metadataPrefix) {
            case 'oai_dc':
                return $this->parseDublinCore($metadata);

            case 'oai_heritage':
                return $this->parseHeritage($metadata);

            case 'oai_ead':
                return $this->parseEad($metadata);

            default:
                // Return raw XML for unknown formats
                return ['raw' => $metadata->asXML()];
        }
    }

    /**
     * Parse Dublin Core metadata
     */
    protected function parseDublinCore(\SimpleXMLElement $metadata): array
    {
        $dc = $metadata->children('http://www.openarchives.org/OAI/2.0/oai_dc/');
        if (!$dc->dc) {
            $dc = $metadata->children('http://purl.org/dc/elements/1.1/');
        }

        $result = [];
        foreach ($dc->children('http://purl.org/dc/elements/1.1/') as $element) {
            $name = $element->getName();
            if (!isset($result[$name])) {
                $result[$name] = [];
            }
            $result[$name][] = (string)$element;
        }

        return $result;
    }

    /**
     * Parse Heritage Platform metadata
     */
    protected function parseHeritage(\SimpleXMLElement $metadata): array
    {
        $ns = 'https://heritage.example.org/oai/heritage/';
        $heritage = $metadata->children($ns);

        $result = [
            'identifier' => (string)$heritage->identifier,
            'title' => (string)$heritage->title,
            'description' => (string)$heritage->description,
            'levelOfDescription' => (string)$heritage->levelOfDescription,
            'extent' => (string)$heritage->extent,
            'referenceCode' => (string)$heritage->referenceCode,
            'accessConditions' => (string)$heritage->accessConditions,
            'provenance' => (string)$heritage->provenance,
            'publicationStatus' => (string)$heritage->publicationStatus,
            'parentIdentifier' => (string)$heritage->parentIdentifier,
            'collectionIdentifier' => (string)$heritage->collectionIdentifier,
            'createdAt' => (string)$heritage->createdAt,
            'updatedAt' => (string)$heritage->updatedAt,
        ];

        // Repository
        if (isset($heritage->repository)) {
            $result['repository'] = [
                'name' => (string)$heritage->repository->name,
                'identifier' => (string)$heritage->repository->identifier,
            ];
        }

        // Dates
        $result['dates'] = [];
        foreach ($heritage->date as $date) {
            $dateEntry = [
                'type' => (string)$date['type'],
                'start' => (string)$date->start,
                'end' => (string)$date->end,
                'display' => (string)$date->display,
            ];
            $result['dates'][] = $dateEntry;
        }

        // Creators
        $result['creators'] = [];
        foreach ($heritage->creator as $creator) {
            $result['creators'][] = [
                'name' => (string)$creator->name,
                'dates' => (string)$creator->dates,
                'type' => (string)$creator->type,
            ];
        }

        // Subjects
        $result['subjects'] = [];
        foreach ($heritage->subject as $subject) {
            $result['subjects'][] = [
                'term' => (string)$subject->term,
                'taxonomy' => (string)$subject['taxonomy'],
            ];
        }

        // Places
        $result['places'] = [];
        foreach ($heritage->place as $place) {
            $result['places'][] = (string)$place->name;
        }

        // Digital objects
        $result['digitalObjects'] = [];
        foreach ($heritage->digitalObject as $digitalObject) {
            $result['digitalObjects'][] = [
                'reference' => (string)$digitalObject->reference,
                'mimeType' => (string)$digitalObject->mimeType,
                'byteSize' => (string)$digitalObject->byteSize,
                'checksum' => (string)$digitalObject->checksum,
                'checksumType' => (string)$digitalObject->checksumType,
                'mediaType' => (string)$digitalObject->mediaType,
            ];
        }

        // Notes
        $result['notes'] = [];
        foreach ($heritage->note as $note) {
            $result['notes'][] = [
                'type' => (string)$note['type'],
                'content' => (string)$note,
            ];
        }

        // Languages
        $result['languages'] = [];
        foreach ($heritage->language as $language) {
            $result['languages'][] = (string)$language;
        }

        return $result;
    }

    /**
     * Parse EAD metadata (simplified)
     */
    protected function parseEad(\SimpleXMLElement $metadata): array
    {
        // EAD parsing is complex, return raw XML for now
        return ['raw' => $metadata->asXML()];
    }

    /**
     * Parse repository description
     */
    protected function parseDescription(\SimpleXMLElement $description): array
    {
        $result = [];

        // Try to parse oai-identifier
        $oaiId = $description->children('http://www.openarchives.org/OAI/2.0/oai-identifier/');
        if ($oaiId->{'oai-identifier'}) {
            $result['oaiIdentifier'] = [
                'scheme' => (string)$oaiId->{'oai-identifier'}->scheme,
                'repositoryIdentifier' => (string)$oaiId->{'oai-identifier'}->repositoryIdentifier,
                'delimiter' => (string)$oaiId->{'oai-identifier'}->delimiter,
                'sampleIdentifier' => (string)$oaiId->{'oai-identifier'}->sampleIdentifier,
            ];
        }

        return $result;
    }

    /**
     * Format XML errors for display
     */
    protected function formatXmlErrors(array $errors): string
    {
        $messages = [];
        foreach ($errors as $error) {
            $messages[] = sprintf('Line %d: %s', $error->line, trim($error->message));
        }
        return implode('; ', $messages);
    }

    /**
     * Set timeout for requests
     */
    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * Set max retries for failed requests
     */
    public function setMaxRetries(int $retries): self
    {
        $this->maxRetries = $retries;
        return $this;
    }

    /**
     * Set retry delay in seconds
     */
    public function setRetryDelay(int $seconds): self
    {
        $this->retryDelay = $seconds;
        return $this;
    }

    /**
     * Set user agent string
     */
    public function setUserAgent(string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }
}

/**
 * Exception for harvest errors
 */
class HarvestException extends \Exception
{
}
