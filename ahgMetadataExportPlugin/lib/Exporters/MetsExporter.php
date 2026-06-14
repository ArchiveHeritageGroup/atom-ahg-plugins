<?php

namespace AhgMetadataExport\Exporters;

/**
 * METS Exporter — Metadata Encoding & Transmission Standard.
 *
 * Wraps an AtoM information object in a METS document: a descriptive metadata
 * section (dmdSec) carrying embedded Dublin Core, plus a minimal structMap.
 */
class MetsExporter extends AbstractXmlExporter
{
    private const NS_METS = 'http://www.loc.gov/METS/';
    private const NS_DC = 'http://purl.org/dc/elements/1.1/';
    private const NS_XLINK = 'http://www.w3.org/1999/xlink';

    protected function initializeNamespaces(): void
    {
        $this->primaryNamespace = self::NS_METS;
        $this->namespaces = ['mets' => self::NS_METS, 'dc' => self::NS_DC, 'xlink' => self::NS_XLINK];
    }

    public function getFormat(): string
    {
        return 'mets';
    }

    public function getFormatName(): string
    {
        return 'METS';
    }

    public function getSector(): string
    {
        return 'Archives';
    }

    protected function buildDocument($resource): \DOMDocument
    {
        $mets = $this->dom->createElementNS(self::NS_METS, 'mets');
        $mets->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xlink', self::NS_XLINK);
        $identifier = $resource->identifier ?? ('io-' . ($resource->id ?? ''));
        $mets->setAttribute('OBJID', (string) $identifier);
        $title = $this->getValue($resource, 'title') ?: 'Untitled';
        $mets->setAttribute('LABEL', $title);
        $mets->setAttribute('TYPE', 'archival description');
        $this->dom->appendChild($mets);

        // metsHdr
        $hdr = $this->createElement('metsHdr', null, self::NS_METS);
        $agent = $this->createElement('agent', null, self::NS_METS);
        $agent->setAttribute('ROLE', 'CREATOR');
        $agent->setAttribute('TYPE', 'ORGANIZATION');
        $agent->appendChild($this->createElement('name', 'The Archive and Heritage Group', self::NS_METS));
        $hdr->appendChild($agent);
        $mets->appendChild($hdr);

        // dmdSec > mdWrap (DC)
        $dmdSec = $this->createElement('dmdSec', null, self::NS_METS);
        $dmdSec->setAttribute('ID', 'dmd-1');
        $mdWrap = $this->createElement('mdWrap', null, self::NS_METS);
        $mdWrap->setAttribute('MDTYPE', 'DC');
        $xmlData = $this->createElement('xmlData', null, self::NS_METS);

        $this->appendDc($xmlData, 'title', $title);
        $this->appendDc($xmlData, 'identifier', (string) $identifier);
        $this->appendDc($xmlData, 'description', $this->getValue($resource, 'scopeAndContent'));
        $this->appendDc($xmlData, 'date', $this->getValue($resource, 'dates'));
        $this->appendDc($xmlData, 'format', $this->getValue($resource, 'extentAndMedium'));
        $this->appendDc($xmlData, 'rights', $this->getValue($resource, 'accessConditions'));

        $mdWrap->appendChild($xmlData);
        $dmdSec->appendChild($mdWrap);
        $mets->appendChild($dmdSec);

        // structMap
        $structMap = $this->createElement('structMap', null, self::NS_METS);
        $structMap->setAttribute('TYPE', 'logical');
        $div = $this->createElement('div', null, self::NS_METS);
        $div->setAttribute('TYPE', 'archival description');
        $div->setAttribute('LABEL', $title);
        $div->setAttribute('DMDID', 'dmd-1');
        $structMap->appendChild($div);
        $mets->appendChild($structMap);

        return $this->dom;
    }

    private function appendDc(\DOMElement $parent, string $name, ?string $value): void
    {
        if (null === $value || '' === $value) {
            return;
        }
        $el = $this->dom->createElementNS(self::NS_DC, 'dc:' . $name);
        $el->appendChild($this->dom->createTextNode($value));
        $parent->appendChild($el);
    }
}
