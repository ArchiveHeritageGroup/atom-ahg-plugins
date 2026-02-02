<?php

/**
 * Ead3Exporter - EAD3 (Encoded Archival Description version 3) Exporter
 *
 * Exports archival descriptions to EAD3 XML format following the
 * Library of Congress EAD3 schema.
 *
 * @see https://www.loc.gov/ead/
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage Exporters
 * @author     The Archive and Heritage Group (Pty) Ltd
 */

namespace AhgMetadataExport\Exporters;

class Ead3Exporter extends AbstractXmlExporter
{
    /**
     * EAD3 namespace
     */
    public const NS_EAD = 'http://ead3.archivists.org/schema/';

    /**
     * Level of description mapping from ISAD(G) to EAD
     */
    protected $levelMap = [
        'Fonds' => 'fonds',
        'fonds' => 'fonds',
        'Subfonds' => 'subfonds',
        'subfonds' => 'subfonds',
        'Collection' => 'collection',
        'collection' => 'collection',
        'Series' => 'series',
        'series' => 'series',
        'Subseries' => 'subseries',
        'subseries' => 'subseries',
        'File' => 'file',
        'file' => 'file',
        'Item' => 'item',
        'item' => 'item',
        'Part' => 'otherlevel',
        'Record group' => 'recordgrp',
        'recordgrp' => 'recordgrp',
    ];

    /**
     * {@inheritdoc}
     */
    protected function initializeNamespaces(): void
    {
        $this->primaryNamespace = self::NS_EAD;
        $this->primaryPrefix = '';
        $this->namespaces = [
            'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
            'xlink' => 'http://www.w3.org/1999/xlink',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormat(): string
    {
        return 'ead3';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatName(): string
    {
        return 'EAD3';
    }

    /**
     * {@inheritdoc}
     */
    public function getSector(): string
    {
        return 'Archives';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildDocument($resource): \DOMDocument
    {
        // Create root element
        $ead = $this->createElement('ead', null, self::NS_EAD);
        $this->dom->appendChild($ead);

        // Add namespace declarations
        $this->addNamespace($ead, 'xsi', $this->namespaces['xsi']);
        $this->addNamespace($ead, 'xlink', $this->namespaces['xlink']);
        $ead->setAttributeNS(
            $this->namespaces['xsi'],
            'xsi:schemaLocation',
            self::NS_EAD.' https://www.loc.gov/ead/ead3.xsd'
        );

        // Add control section (replaces eadheader in EAD 2002)
        $control = $this->buildControl($resource);
        $ead->appendChild($control);

        // Add archdesc
        $archdesc = $this->buildArchdesc($resource);
        $ead->appendChild($archdesc);

        return $this->dom;
    }

    /**
     * Build the control element (EAD3 header)
     *
     * @param mixed $resource
     *
     * @return \DOMElement
     */
    protected function buildControl($resource): \DOMElement
    {
        $control = $this->createElement('control', null, self::NS_EAD);

        // Record ID
        $recordid = $this->createElement('recordid', $this->getIdentifier($resource), self::NS_EAD);
        $control->appendChild($recordid);

        // File description
        $filedesc = $this->createElement('filedesc', null, self::NS_EAD);
        $control->appendChild($filedesc);

        // Title statement
        $titlestmt = $this->createElement('titlestmt', null, self::NS_EAD);
        $filedesc->appendChild($titlestmt);

        $title = $this->getValue($resource, 'title');
        if ($title) {
            $titleproper = $this->createElement('titleproper', $title, self::NS_EAD);
            $titlestmt->appendChild($titleproper);
        }

        // Repository info
        $repo = $this->getRepository($resource);
        if ($repo && $repo['name']) {
            $publicationstmt = $this->createElement('publicationstmt', null, self::NS_EAD);
            $filedesc->appendChild($publicationstmt);

            $publisher = $this->createElement('publisher', $repo['name'], self::NS_EAD);
            $publicationstmt->appendChild($publisher);
        }

        // Maintenance status
        $maintenancestatus = $this->createElement('maintenancestatus', null, self::NS_EAD);
        $maintenancestatus->setAttribute('value', 'derived');
        $control->appendChild($maintenancestatus);

        // Maintenance agency
        $maintenanceagency = $this->createElement('maintenanceagency', null, self::NS_EAD);
        $control->appendChild($maintenanceagency);

        $agencyname = $this->createElement(
            'agencyname',
            $repo['name'] ?? 'Archive',
            self::NS_EAD
        );
        $maintenanceagency->appendChild($agencyname);

        // Language declaration
        $languagedeclaration = $this->createElement('languagedeclaration', null, self::NS_EAD);
        $control->appendChild($languagedeclaration);

        $language = $this->createElement('language', null, self::NS_EAD);
        $language->setAttribute('langcode', 'eng');
        $languagedeclaration->appendChild($language);

        $script = $this->createElement('script', null, self::NS_EAD);
        $script->setAttribute('scriptcode', 'Latn');
        $languagedeclaration->appendChild($script);

        // Maintenance history
        $maintenancehistory = $this->createElement('maintenancehistory', null, self::NS_EAD);
        $control->appendChild($maintenancehistory);

        $maintenanceevent = $this->createElement('maintenanceevent', null, self::NS_EAD);
        $maintenancehistory->appendChild($maintenanceevent);

        $eventtype = $this->createElement('eventtype', null, self::NS_EAD);
        $eventtype->setAttribute('value', 'derived');
        $maintenanceevent->appendChild($eventtype);

        $eventdatetime = $this->createElement('eventdatetime', date('c'), self::NS_EAD);
        $maintenanceevent->appendChild($eventdatetime);

        $agenttype = $this->createElement('agenttype', null, self::NS_EAD);
        $agenttype->setAttribute('value', 'machine');
        $maintenanceevent->appendChild($agenttype);

        $agent = $this->createElement('agent', 'AtoM AHG Metadata Export Plugin', self::NS_EAD);
        $maintenanceevent->appendChild($agent);

        return $control;
    }

    /**
     * Build the archdesc element
     *
     * @param mixed $resource
     *
     * @return \DOMElement
     */
    protected function buildArchdesc($resource): \DOMElement
    {
        $level = $this->mapLevel($this->getLevelOfDescription($resource));

        $archdesc = $this->createElement('archdesc', null, self::NS_EAD);
        $archdesc->setAttribute('level', $level);

        // Add did (required)
        $did = $this->buildDid($resource);
        $archdesc->appendChild($did);

        // Add optional elements
        $this->addBioghist($archdesc, $resource);
        $this->addScopeContent($archdesc, $resource);
        $this->addArrangement($archdesc, $resource);
        $this->addAccessRestrict($archdesc, $resource);
        $this->addUseRestrict($archdesc, $resource);
        $this->addRelatedMaterial($archdesc, $resource);
        $this->addControlAccess($archdesc, $resource);
        $this->addDaoset($archdesc, $resource);

        // Add children as component elements (dsc)
        if ($this->options['includeChildren']) {
            $children = $this->getChildren($resource);
            if (!empty($children)) {
                $dsc = $this->createElement('dsc', null, self::NS_EAD);
                $archdesc->appendChild($dsc);

                foreach ($children as $child) {
                    $c = $this->buildComponent($child, 1);
                    if ($c) {
                        $dsc->appendChild($c);
                    }
                }
            }
        }

        return $archdesc;
    }

    /**
     * Build the did (descriptive identification) element
     *
     * @param mixed $resource
     *
     * @return \DOMElement
     */
    protected function buildDid($resource): \DOMElement
    {
        $did = $this->createElement('did', null, self::NS_EAD);

        // Unit ID (reference code)
        $identifier = $resource->identifier ?? $this->getIdentifier($resource);
        if ($identifier) {
            $unitid = $this->createElement('unitid', $identifier, self::NS_EAD);
            $did->appendChild($unitid);
        }

        // Unit title
        $title = $this->getValue($resource, 'title');
        if ($title) {
            $unittitle = $this->createElement('unittitle', $title, self::NS_EAD);
            $did->appendChild($unittitle);
        }

        // Unit date
        $dateRange = $this->getDateRange($resource);
        if ($dateRange['display'] || $dateRange['start']) {
            $unitdate = $this->createElement('unitdate', null, self::NS_EAD);

            if ($dateRange['display']) {
                $unitdate->appendChild($this->dom->createTextNode($dateRange['display']));
            }
            if ($dateRange['start']) {
                $unitdate->setAttribute('normal', $this->formatDateNormal($dateRange));
            }

            $did->appendChild($unitdate);
        }

        // Extent (physical description)
        $extent = $this->getValue($resource, 'extentAndMedium');
        if ($extent) {
            $physdescstructured = $this->createElement('physdescstructured', null, self::NS_EAD);
            $physdescstructured->setAttribute('coverage', 'whole');
            $physdescstructured->setAttribute('physdescstructuredtype', 'materialtype');

            $quantity = $this->createElement('quantity', '1', self::NS_EAD);
            $physdescstructured->appendChild($quantity);

            $unittype = $this->createElement('unittype', $extent, self::NS_EAD);
            $physdescstructured->appendChild($unittype);

            $did->appendChild($physdescstructured);
        }

        // Origination (creator)
        $creators = $this->getCreators($resource);
        foreach ($creators as $creator) {
            if ($creator['name']) {
                $origination = $this->createElement('origination', null, self::NS_EAD);
                $origination->setAttribute('label', 'Creator');

                // Use appropriate name element based on type
                $nameElement = $this->getCreatorNameElement($creator);
                $origination->appendChild($nameElement);

                $did->appendChild($origination);
            }
        }

        // Repository
        $repo = $this->getRepository($resource);
        if ($repo && $repo['name']) {
            $repository = $this->createElement('repository', null, self::NS_EAD);
            $corpname = $this->createElement('corpname', null, self::NS_EAD);

            $part = $this->createElement('part', $repo['name'], self::NS_EAD);
            $corpname->appendChild($part);

            $repository->appendChild($corpname);
            $did->appendChild($repository);
        }

        // Language of material
        $languages = $this->getLanguages($resource);
        if (!empty($languages)) {
            $langmaterial = $this->createElement('langmaterial', null, self::NS_EAD);
            foreach ($languages as $lang) {
                $language = $this->createElement('language', null, self::NS_EAD);
                $language->setAttribute('langcode', $lang['code'] ?? 'und');
                if ($lang['name']) {
                    $language->appendChild($this->dom->createTextNode($lang['name']));
                }
                $langmaterial->appendChild($language);
            }
            $did->appendChild($langmaterial);
        }

        return $did;
    }

    /**
     * Build a component (c) element for children
     *
     * @param mixed $resource
     * @param int   $depth
     *
     * @return \DOMElement|null
     */
    protected function buildComponent($resource, int $depth): ?\DOMElement
    {
        // Check depth limit
        if ($this->options['maxDepth'] > 0 && $depth > $this->options['maxDepth']) {
            return null;
        }

        // Check draft status
        if (!$this->options['includeDrafts']) {
            $status = $resource->publicationStatus ?? null;
            if ($status && 'Draft' === (string) $status) {
                return null;
            }
        }

        $level = $this->mapLevel($this->getLevelOfDescription($resource));

        $c = $this->createElement('c', null, self::NS_EAD);
        $c->setAttribute('level', $level);

        // Add did
        $did = $this->buildDid($resource);
        $c->appendChild($did);

        // Add scope and content if available
        $this->addScopeContent($c, $resource);

        // Add access restrictions
        $this->addAccessRestrict($c, $resource);

        // Add digital objects
        $this->addDaoset($c, $resource);

        // Recursively add children
        $children = $this->getChildren($resource);
        foreach ($children as $child) {
            $childC = $this->buildComponent($child, $depth + 1);
            if ($childC) {
                $c->appendChild($childC);
            }
        }

        return $c;
    }

    /**
     * Add bioghist element (biographical/historical note)
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addBioghist(\DOMElement $parent, $resource): void
    {
        // Get from creator(s)
        $creators = $this->getCreators($resource);
        foreach ($creators as $creator) {
            // Try to get history from actor
            if (isset($creator['id'])) {
                $history = $this->getActorHistory($creator['id']);
                if ($history) {
                    $bioghist = $this->createElement('bioghist', null, self::NS_EAD);
                    $p = $this->createElement('p', $history, self::NS_EAD);
                    $bioghist->appendChild($p);
                    $parent->appendChild($bioghist);

                    break;
                }
            }
        }
    }

    /**
     * Add scopecontent element
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addScopeContent(\DOMElement $parent, $resource): void
    {
        $content = $this->getValue($resource, 'scopeAndContent');
        if ($content) {
            $scopecontent = $this->createElement('scopecontent', null, self::NS_EAD);
            $p = $this->createElement('p', $content, self::NS_EAD);
            $scopecontent->appendChild($p);
            $parent->appendChild($scopecontent);
        }
    }

    /**
     * Add arrangement element
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addArrangement(\DOMElement $parent, $resource): void
    {
        $arrangement = $this->getValue($resource, 'arrangement');
        if ($arrangement) {
            $arrangementEl = $this->createElement('arrangement', null, self::NS_EAD);
            $p = $this->createElement('p', $arrangement, self::NS_EAD);
            $arrangementEl->appendChild($p);
            $parent->appendChild($arrangementEl);
        }
    }

    /**
     * Add accessrestrict element
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addAccessRestrict(\DOMElement $parent, $resource): void
    {
        $access = $this->getValue($resource, 'accessConditions');
        if ($access) {
            $accessrestrict = $this->createElement('accessrestrict', null, self::NS_EAD);
            $p = $this->createElement('p', $access, self::NS_EAD);
            $accessrestrict->appendChild($p);
            $parent->appendChild($accessrestrict);
        }
    }

    /**
     * Add userestrict element
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addUseRestrict(\DOMElement $parent, $resource): void
    {
        $use = $this->getValue($resource, 'reproductionConditions');
        if ($use) {
            $userestrict = $this->createElement('userestrict', null, self::NS_EAD);
            $p = $this->createElement('p', $use, self::NS_EAD);
            $userestrict->appendChild($p);
            $parent->appendChild($userestrict);
        }
    }

    /**
     * Add relatedmaterial element
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addRelatedMaterial(\DOMElement $parent, $resource): void
    {
        $related = $this->getValue($resource, 'relatedUnitsOfDescription');
        if ($related) {
            $relatedmaterial = $this->createElement('relatedmaterial', null, self::NS_EAD);
            $p = $this->createElement('p', $related, self::NS_EAD);
            $relatedmaterial->appendChild($p);
            $parent->appendChild($relatedmaterial);
        }
    }

    /**
     * Add controlaccess element (subjects, names, places)
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addControlAccess(\DOMElement $parent, $resource): void
    {
        $subjects = $this->getSubjects($resource);
        $places = $this->getPlaces($resource);

        if (empty($subjects) && empty($places)) {
            return;
        }

        $controlaccess = $this->createElement('controlaccess', null, self::NS_EAD);

        // Add subjects
        foreach ($subjects as $subject) {
            $subjectEl = $this->createElement('subject', null, self::NS_EAD);
            $part = $this->createElement('part', $subject, self::NS_EAD);
            $subjectEl->appendChild($part);
            $controlaccess->appendChild($subjectEl);
        }

        // Add places
        foreach ($places as $place) {
            $geogname = $this->createElement('geogname', null, self::NS_EAD);
            $part = $this->createElement('part', $place, self::NS_EAD);
            $geogname->appendChild($part);
            $controlaccess->appendChild($geogname);
        }

        $parent->appendChild($controlaccess);
    }

    /**
     * Add daoset element for digital objects
     *
     * @param \DOMElement $parent
     * @param mixed       $resource
     */
    protected function addDaoset(\DOMElement $parent, $resource): void
    {
        $digitalObjects = $this->getDigitalObjects($resource);

        if (empty($digitalObjects)) {
            return;
        }

        $daoset = $this->createElement('daoset', null, self::NS_EAD);
        $daoset->setAttribute('label', 'Digital Objects');

        foreach ($digitalObjects as $do) {
            $dao = $this->createElement('dao', null, self::NS_EAD);
            $dao->setAttribute('daotype', 'derived');

            // Link to digital object
            $href = $this->getDigitalObjectUrl($do);
            if ($href) {
                $dao->setAttributeNS($this->namespaces['xlink'], 'xlink:href', $href);
                $dao->setAttributeNS($this->namespaces['xlink'], 'xlink:actuate', 'onRequest');
                $dao->setAttributeNS($this->namespaces['xlink'], 'xlink:show', 'new');
            }

            // Add description
            $daodesc = $this->createElement('daodesc', null, self::NS_EAD);
            $p = $this->createElement('p', $do->name ?? 'Digital Object', self::NS_EAD);
            $daodesc->appendChild($p);
            $dao->appendChild($daodesc);

            $daoset->appendChild($dao);
        }

        $parent->appendChild($daoset);
    }

    /**
     * Map ISAD(G) level to EAD3 level
     *
     * @param string|null $level
     *
     * @return string
     */
    protected function mapLevel(?string $level): string
    {
        if (!$level) {
            return 'otherlevel';
        }

        return $this->levelMap[$level] ?? 'otherlevel';
    }

    /**
     * Get creator name element based on type
     *
     * @param array $creator
     *
     * @return \DOMElement
     */
    protected function getCreatorNameElement(array $creator): \DOMElement
    {
        $type = $creator['type'] ?? null;

        switch ($type) {
            case 'Corporate body':
            case 'corporate':
                $element = $this->createElement('corpname', null, self::NS_EAD);
                break;
            case 'Family':
            case 'family':
                $element = $this->createElement('famname', null, self::NS_EAD);
                break;
            case 'Person':
            case 'person':
            default:
                $element = $this->createElement('persname', null, self::NS_EAD);
                break;
        }

        $part = $this->createElement('part', $creator['name'], self::NS_EAD);
        $element->appendChild($part);

        return $element;
    }

    /**
     * Format date range for EAD normal attribute
     *
     * @param array $dateRange
     *
     * @return string
     */
    protected function formatDateNormal(array $dateRange): string
    {
        $start = $dateRange['start'] ? $this->formatDate($dateRange['start'], 'Y-m-d') : null;
        $end = $dateRange['end'] ? $this->formatDate($dateRange['end'], 'Y-m-d') : null;

        if ($start && $end && $start !== $end) {
            return $start.'/'.$end;
        }

        return $start ?? $end ?? '';
    }

    /**
     * Get languages from resource
     *
     * @param mixed $resource
     *
     * @return array
     */
    protected function getLanguages($resource): array
    {
        $languages = [];

        if (method_exists($resource, 'getLanguage')) {
            $langs = $resource->getLanguage();
            foreach ($langs as $lang) {
                $languages[] = [
                    'code' => $lang->code ?? 'und',
                    'name' => $lang->name ?? null,
                ];
            }
        }

        return $languages;
    }

    /**
     * Get actor history by ID
     *
     * @param int $actorId
     *
     * @return string|null
     */
    protected function getActorHistory(int $actorId): ?string
    {
        try {
            $actor = \QubitActor::getById($actorId);
            if ($actor) {
                return $this->getValue($actor, 'history');
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return null;
    }

    /**
     * Get digital object URL
     *
     * @param mixed $digitalObject
     *
     * @return string|null
     */
    protected function getDigitalObjectUrl($digitalObject): ?string
    {
        if (isset($digitalObject->path)) {
            return rtrim($this->baseUri, '/').'/'.ltrim($digitalObject->path, '/');
        }

        if (isset($digitalObject->uri)) {
            return $digitalObject->uri;
        }

        return null;
    }
}
