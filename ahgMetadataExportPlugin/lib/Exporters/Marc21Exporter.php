<?php

/**
 * Marc21Exporter - MARC21 (MARCXML) Exporter
 *
 * Exports bibliographic records to MARC21 XML format.
 *
 * @see https://www.loc.gov/standards/marcxml/
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage Exporters
 * @author     The Archive and Heritage Group (Pty) Ltd
 */

namespace AhgMetadataExport\Exporters;

class Marc21Exporter extends AbstractXmlExporter
{
    /**
     * MARC21 namespace
     */
    public const NS_MARC = 'http://www.loc.gov/MARC21/slim';

    /**
     * {@inheritdoc}
     */
    protected function initializeNamespaces(): void
    {
        $this->primaryNamespace = self::NS_MARC;
        $this->primaryPrefix = '';
        $this->namespaces = [
            'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormat(): string
    {
        return 'marc21';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatName(): string
    {
        return 'MARC21';
    }

    /**
     * {@inheritdoc}
     */
    public function getSector(): string
    {
        return 'Libraries';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildDocument($resource): \DOMDocument
    {
        // Create collection wrapper
        $collection = $this->dom->createElementNS(self::NS_MARC, 'collection');
        $this->dom->appendChild($collection);

        // Add namespace declarations
        $this->addNamespace($collection, 'xsi', $this->namespaces['xsi']);
        $collection->setAttributeNS(
            $this->namespaces['xsi'],
            'xsi:schemaLocation',
            self::NS_MARC.' http://www.loc.gov/standards/marcxml/schema/MARC21slim.xsd'
        );

        // Build main record
        $record = $this->buildRecord($resource);
        $collection->appendChild($record);

        // If including children, add them as separate records
        if ($this->options['includeChildren']) {
            $children = $this->getChildren($resource);
            foreach ($children as $child) {
                $childRecord = $this->buildRecord($child);
                $collection->appendChild($childRecord);
            }
        }

        return $this->dom;
    }

    /**
     * Build a single MARC record
     *
     * @param mixed $resource
     *
     * @return \DOMElement
     */
    protected function buildRecord($resource): \DOMElement
    {
        $record = $this->dom->createElementNS(self::NS_MARC, 'record');

        // Leader
        $leader = $this->buildLeader($resource);
        $record->appendChild($leader);

        // Control fields
        $this->addControlFields($record, $resource);

        // Data fields
        $this->addDataFields($record, $resource);

        return $record;
    }

    /**
     * Build leader field
     *
     * @param mixed $resource
     *
     * @return \DOMElement
     */
    protected function buildLeader($resource): \DOMElement
    {
        $leader = $this->dom->createElementNS(self::NS_MARC, 'leader');

        // Build 24-character leader
        // Positions: 00-04 = record length (calculated), 05 = record status (n=new)
        // 06 = type of record, 07 = bibliographic level, etc.
        $level = $this->getLevelOfDescription($resource);
        $typeCode = $this->getRecordTypeCode($level);
        $levelCode = $this->getBibliographicLevelCode($level);

        $leaderStr = '00000'          // 00-04: Record length (system fills)
            .'n'              // 05: Record status (n = new)
            .$typeCode         // 06: Type of record
            .$levelCode        // 07: Bibliographic level
            .' '              // 08: Type of control
            .'a'              // 09: Character coding (a = UCS/Unicode)
            .'2'              // 10: Indicator count
            .'2'              // 11: Subfield code count
            .'00000'          // 12-16: Base address of data
            .' '              // 17: Encoding level
            .'u'              // 18: Descriptive cataloging form (u = unknown)
            .' '              // 19: Multipart resource record level
            .'4500';          // 20-23: Entry map

        $leader->appendChild($this->dom->createTextNode($leaderStr));

        return $leader;
    }

    /**
     * Add control fields (001-009)
     *
     * @param \DOMElement $record
     * @param mixed       $resource
     */
    protected function addControlFields(\DOMElement $record, $resource): void
    {
        // 001 - Control Number
        $identifier = $this->getIdentifier($resource);
        $this->addControlField($record, '001', $identifier);

        // 003 - Control Number Identifier
        $repo = $this->getRepository($resource);
        if ($repo && $repo['identifier']) {
            $this->addControlField($record, '003', $repo['identifier']);
        }

        // 005 - Date and Time of Latest Transaction
        $this->addControlField($record, '005', date('YmdHis').'.0');

        // 008 - Fixed-Length Data Elements
        $field008 = $this->build008Field($resource);
        $this->addControlField($record, '008', $field008);
    }

    /**
     * Add a control field
     *
     * @param \DOMElement $record
     * @param string      $tag
     * @param string      $value
     */
    protected function addControlField(\DOMElement $record, string $tag, string $value): void
    {
        $controlfield = $this->dom->createElementNS(self::NS_MARC, 'controlfield');
        $controlfield->setAttribute('tag', $tag);
        $controlfield->appendChild($this->dom->createTextNode($value));
        $record->appendChild($controlfield);
    }

    /**
     * Build 008 field (Fixed-Length Data Elements)
     *
     * @param mixed $resource
     *
     * @return string 40-character fixed field
     */
    protected function build008Field($resource): string
    {
        $dateRange = $this->getDateRange($resource);

        // Extract year from dates
        $startYear = '    ';
        $endYear = '    ';

        if ($dateRange['start']) {
            $year = $this->extractYear($dateRange['start']);
            if ($year) {
                $startYear = str_pad($year, 4, ' ');
            }
        }

        if ($dateRange['end']) {
            $year = $this->extractYear($dateRange['end']);
            if ($year) {
                $endYear = str_pad($year, 4, ' ');
            }
        }

        // Date type
        $dateType = ($startYear !== '    ' && $endYear !== '    ' && $startYear !== $endYear) ? 'd' : 's';

        // Language code
        $langCode = 'eng'; // Default
        if (method_exists($resource, 'getLanguage')) {
            $languages = $resource->getLanguage();
            foreach ($languages as $lang) {
                if (isset($lang->code) && 3 === strlen($lang->code)) {
                    $langCode = $lang->code;
                    break;
                }
            }
        }

        // Build the field
        // 00-05: Date entered on file
        // 06: Type of date
        // 07-10: Date 1
        // 11-14: Date 2
        // 15-17: Place of publication
        // 18-34: Specific to material type
        // 35-37: Language
        // 38: Modified record
        // 39: Cataloging source

        return date('ymd')          // 00-05: Date entered
            .$dateType              // 06: Type of date
            .$startYear             // 07-10: Date 1
            .$endYear               // 11-14: Date 2
            .'xx '                  // 15-17: Place (unknown)
            .'                 '    // 18-34: Specific material characteristics
            .$langCode              // 35-37: Language
            .' '                    // 38: Modified record
            .'d';                   // 39: Cataloging source (d = other)
    }

    /**
     * Add data fields
     *
     * @param \DOMElement $record
     * @param mixed       $resource
     */
    protected function addDataFields(\DOMElement $record, $resource): void
    {
        // 040 - Cataloging Source
        $this->add040CatalogingSource($record, $resource);

        // 1XX - Main Entry
        $this->addMainEntry($record, $resource);

        // 245 - Title Statement
        $this->add245TitleStatement($record, $resource);

        // 260/264 - Publication, Distribution
        $this->addPublicationInfo($record, $resource);

        // 300 - Physical Description
        $this->add300PhysicalDescription($record, $resource);

        // 500-599 - Notes
        $this->addNotes($record, $resource);

        // 6XX - Subject Access Fields
        $this->addSubjectFields($record, $resource);

        // 7XX - Added Entries
        $this->addAddedEntries($record, $resource);

        // 856 - Electronic Location
        $this->add856ElectronicLocation($record, $resource);
    }

    /**
     * Add a data field
     *
     * @param \DOMElement $record
     * @param string      $tag
     * @param string      $ind1
     * @param string      $ind2
     * @param array       $subfields Array of [code => value]
     */
    protected function addDataField(\DOMElement $record, string $tag, string $ind1, string $ind2, array $subfields): void
    {
        if (empty($subfields)) {
            return;
        }

        $datafield = $this->dom->createElementNS(self::NS_MARC, 'datafield');
        $datafield->setAttribute('tag', $tag);
        $datafield->setAttribute('ind1', $ind1);
        $datafield->setAttribute('ind2', $ind2);

        foreach ($subfields as $code => $value) {
            if (null === $value || '' === $value) {
                continue;
            }

            // Handle numeric keys for repeated subfields
            if (is_array($value)) {
                foreach ($value as $v) {
                    $subfield = $this->dom->createElementNS(self::NS_MARC, 'subfield');
                    $subfield->setAttribute('code', $code);
                    $subfield->appendChild($this->dom->createTextNode($v));
                    $datafield->appendChild($subfield);
                }
            } else {
                $subfield = $this->dom->createElementNS(self::NS_MARC, 'subfield');
                $subfield->setAttribute('code', $code);
                $subfield->appendChild($this->dom->createTextNode($value));
                $datafield->appendChild($subfield);
            }
        }

        // Only append if we added subfields
        if ($datafield->hasChildNodes()) {
            $record->appendChild($datafield);
        }
    }

    /**
     * Add 040 - Cataloging Source
     *
     * @param \DOMElement $record
     * @param mixed       $resource
     */
    protected function add040CatalogingSource(\DOMElement $record, $resource): void
    {
        $repo = $this->getRepository($resource);
        $code = $repo['identifier'] ?? 'XXX';

        $this->addDataField($record, '040', ' ', ' ', [
            'a' => $code,      // Original cataloging agency
            'b' => 'eng',      // Language of cataloging
            'c' => $code,      // Transcribing agency
        ]);
    }

    /**
     * Add main entry (100, 110, or 111)
     *
     * @param \DOMElement $record
     * @param mixed       $resource
     */
    protected function addMainEntry(\DOMElement $record, $resource): void
    {
        $creators = $this->getCreators($resource);

        if (empty($creators)) {
            return;
        }

        $creator = $creators[0]; // First creator is main entry
        $name = $creator['name'] ?? '';
        $type = $creator['type'] ?? 'Person';

        if (!$name) {
            return;
        }

        switch ($type) {
            case 'Corporate body':
            case 'corporate':
                // 110 - Main Entry - Corporate Name
                $this->addDataField($record, '110', '2', ' ', [
                    'a' => $name,
                ]);
                break;

            case 'Family':
            case 'family':
                // 100 with ind1=3 for family name
                $this->addDataField($record, '100', '3', ' ', [
                    'a' => $name,
                ]);
                break;

            case 'Person':
            case 'person':
            default:
                // 100 - Main Entry - Personal Name
                // ind1: 0=forename, 1=surname, 3=family name
                $ind1 = $this->isInvertedName($name) ? '1' : '0';
                $this->addDataField($record, '100', $ind1, ' ', [
                    'a' => $name,
                ]);
                break;
        }
    }

    /**
     * Add 245 - Title Statement
     *
     * @param \DOMElement $record
     * @param mixed       $resource
     */
    protected function add245TitleStatement(\DOMElement $record, $resource): void
    {
        $title = $this->getValue($resource, 'title') ?? 'Untitled';
        $creators = $this->getCreators($resource);

        // Determine ind1 based on whether there's a main entry
        $ind1 = empty($creators) ? '0' : '1';

        // Calculate non-filing characters (articles at start)
        $ind2 = $this->calculateNonFilingCharacters($title);

        $subfields = [
            'a' => $title,
        ];

        // Add statement of responsibility if we have creators
        if (!empty($creators)) {
            $names = array_map(function ($c) {
                return $c['name'] ?? '';
            }, $creators);
            $names = array_filter($names);
            if (!empty($names)) {
                $subfields['c'] = implode(' ; ', $names);
            }
        }

        $this->addDataField($record, '245', $ind1, (string) $ind2, $subfields);
    }

    /**
     * Add publication information (264)
     *
     * @param \DOMElement $record
     * @param mixed       $resource
     */
    protected function addPublicationInfo(\DOMElement $record, $resource): void
    {
        $dateRange = $this->getDateRange($resource);
        $places = $this->getPlaces($resource);
        $repo = $this->getRepository($resource);

        $subfields = [];

        // Place
        if (!empty($places)) {
            $subfields['a'] = $places[0];
        }

        // Publisher (repository as publisher for archival materials)
        if ($repo && $repo['name']) {
            $subfields['b'] = $repo['name'];
        }

        // Date
        if ($dateRange['display']) {
            $subfields['c'] = $dateRange['display'];
        } elseif ($dateRange['start']) {
            $subfields['c'] = $this->extractYear($dateRange['start']);
        }

        if (!empty($subfields)) {
            // 264 ind2: 0=production, 1=publication, 2=distribution, 3=manufacture
            $this->addDataField($record, '264', ' ', '0', $subfields);
        }
    }

    /**
     * Add 300 - Physical Description
     *
     * @param \DOMElement $record
     * @param mixed       $resource
     */
    protected function add300PhysicalDescription(\DOMElement $record, $resource): void
    {
        $extent = $this->getValue($resource, 'extentAndMedium');

        if ($extent) {
            $this->addDataField($record, '300', ' ', ' ', [
                'a' => $extent,
            ]);
        }
    }

    /**
     * Add notes (5XX fields)
     *
     * @param \DOMElement $record
     * @param mixed       $resource
     */
    protected function addNotes(\DOMElement $record, $resource): void
    {
        // 500 - General Note
        $notes = $this->getValue($resource, 'notes');
        if ($notes) {
            $this->addDataField($record, '500', ' ', ' ', ['a' => $notes]);
        }

        // 520 - Summary/Abstract (Scope and Content)
        $scope = $this->getValue($resource, 'scopeAndContent');
        if ($scope) {
            $this->addDataField($record, '520', ' ', ' ', ['a' => $scope]);
        }

        // 545 - Biographical or Historical Data
        $creators = $this->getCreators($resource);
        foreach ($creators as $creator) {
            if (isset($creator['id'])) {
                try {
                    $actor = \QubitActor::getById($creator['id']);
                    if ($actor) {
                        $history = $this->getValue($actor, 'history');
                        if ($history) {
                            $this->addDataField($record, '545', ' ', ' ', ['a' => $history]);
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    // Ignore
                }
            }
        }

        // 506 - Restrictions on Access Note
        $access = $this->getValue($resource, 'accessConditions');
        if ($access) {
            $this->addDataField($record, '506', ' ', ' ', ['a' => $access]);
        }

        // 540 - Terms Governing Use and Reproduction
        $repro = $this->getValue($resource, 'reproductionConditions');
        if ($repro) {
            $this->addDataField($record, '540', ' ', ' ', ['a' => $repro]);
        }

        // 555 - Cumulative Index/Finding Aids Note
        $findingAid = $this->getValue($resource, 'findingAids');
        if ($findingAid) {
            $this->addDataField($record, '555', ' ', ' ', ['a' => $findingAid]);
        }

        // 544 - Location of Other Archival Materials
        $related = $this->getValue($resource, 'relatedUnitsOfDescription');
        if ($related) {
            $this->addDataField($record, '544', ' ', ' ', ['a' => $related]);
        }

        // 351 - Organization and Arrangement
        $arrangement = $this->getValue($resource, 'arrangement');
        if ($arrangement) {
            $this->addDataField($record, '351', ' ', ' ', ['a' => $arrangement]);
        }
    }

    /**
     * Add subject fields (6XX)
     *
     * @param \DOMElement $record
     * @param mixed       $resource
     */
    protected function addSubjectFields(\DOMElement $record, $resource): void
    {
        // 650 - Subject Added Entry - Topical Term
        $subjects = $this->getSubjects($resource);
        foreach ($subjects as $subject) {
            $this->addDataField($record, '650', ' ', '4', ['a' => $subject]);
        }

        // 651 - Subject Added Entry - Geographic Name
        $places = $this->getPlaces($resource);
        foreach ($places as $place) {
            $this->addDataField($record, '651', ' ', '4', ['a' => $place]);
        }
    }

    /**
     * Add added entries (7XX)
     *
     * @param \DOMElement $record
     * @param mixed       $resource
     */
    protected function addAddedEntries(\DOMElement $record, $resource): void
    {
        $creators = $this->getCreators($resource);

        // Skip first creator (main entry), add rest as added entries
        $addedCreators = array_slice($creators, 1);

        foreach ($addedCreators as $creator) {
            $name = $creator['name'] ?? '';
            $type = $creator['type'] ?? 'Person';

            if (!$name) {
                continue;
            }

            switch ($type) {
                case 'Corporate body':
                case 'corporate':
                    $this->addDataField($record, '710', '2', ' ', ['a' => $name]);
                    break;

                case 'Family':
                case 'family':
                    $this->addDataField($record, '700', '3', ' ', ['a' => $name]);
                    break;

                default:
                    $ind1 = $this->isInvertedName($name) ? '1' : '0';
                    $this->addDataField($record, '700', $ind1, ' ', ['a' => $name]);
                    break;
            }
        }

        // 710 - Repository as corporate added entry
        $repo = $this->getRepository($resource);
        if ($repo && $repo['name']) {
            // Check if already added as creator
            $repoIsCreator = false;
            foreach ($creators as $creator) {
                if ($creator['name'] === $repo['name']) {
                    $repoIsCreator = true;
                    break;
                }
            }

            if (!$repoIsCreator) {
                $this->addDataField($record, '710', '2', ' ', ['a' => $repo['name']]);
            }
        }
    }

    /**
     * Add 856 - Electronic Location and Access
     *
     * @param \DOMElement $record
     * @param mixed       $resource
     */
    protected function add856ElectronicLocation(\DOMElement $record, $resource): void
    {
        // Link to AtoM record
        $uri = $this->getResourceUri($resource);
        $this->addDataField($record, '856', '4', '0', [
            'u' => $uri,
            'z' => 'View record in AtoM',
        ]);

        // Digital objects
        if ($this->options['includeDigitalObjects']) {
            $digitalObjects = $this->getDigitalObjects($resource);
            foreach ($digitalObjects as $do) {
                if (isset($do->path)) {
                    $doUri = rtrim($this->baseUri, '/').'/'.ltrim($do->path, '/');
                    $this->addDataField($record, '856', '4', '0', [
                        'u' => $doUri,
                        'y' => $do->name ?? 'Digital object',
                    ]);
                }
            }
        }
    }

    /**
     * Get record type code for leader position 06
     *
     * @param string|null $level
     *
     * @return string
     */
    protected function getRecordTypeCode(?string $level): string
    {
        // Type of record codes:
        // a = Language material
        // c = Notated music
        // d = Manuscript notated music
        // e = Cartographic material
        // f = Manuscript cartographic material
        // g = Projected medium
        // i = Nonmusical sound recording
        // j = Musical sound recording
        // k = Two-dimensional nonprojectable graphic
        // m = Computer file
        // o = Kit
        // p = Mixed materials
        // r = Three-dimensional artifact
        // t = Manuscript language material

        // For archival materials, typically use 'p' (mixed) or 't' (manuscript)
        return 'p'; // Mixed materials
    }

    /**
     * Get bibliographic level code for leader position 07
     *
     * @param string|null $level
     *
     * @return string
     */
    protected function getBibliographicLevelCode(?string $level): string
    {
        // Bibliographic level codes:
        // a = Monographic component part
        // b = Serial component part
        // c = Collection
        // d = Subunit
        // i = Integrating resource
        // m = Monograph/Item
        // s = Serial

        $map = [
            'Fonds' => 'c',
            'Collection' => 'c',
            'Series' => 'c',
            'Subseries' => 'd',
            'File' => 'd',
            'Item' => 'm',
        ];

        return $map[$level] ?? 'c';
    }

    /**
     * Extract year from date string
     *
     * @param mixed $date
     *
     * @return string|null
     */
    protected function extractYear($date): ?string
    {
        if (!$date) {
            return null;
        }

        if (preg_match('/(\d{4})/', (string) $date, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Check if name is inverted (surname, forename)
     *
     * @param string $name
     *
     * @return bool
     */
    protected function isInvertedName(string $name): bool
    {
        return false !== strpos($name, ',');
    }

    /**
     * Calculate non-filing characters (articles at start of title)
     *
     * @param string $title
     *
     * @return int
     */
    protected function calculateNonFilingCharacters(string $title): int
    {
        $articles = [
            'the ' => 4,
            'a ' => 2,
            'an ' => 3,
            'de ' => 3,
            'die ' => 4,
            'la ' => 3,
            'le ' => 3,
            'les ' => 4,
        ];

        $lowerTitle = strtolower($title);
        foreach ($articles as $article => $length) {
            if (0 === strpos($lowerTitle, $article)) {
                return $length;
            }
        }

        return 0;
    }
}
