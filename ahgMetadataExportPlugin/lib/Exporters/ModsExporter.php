<?php

namespace AhgMetadataExport\Exporters;

/**
 * MODS Exporter — Metadata Object Description Schema v3.7.
 *
 * Maps an AtoM information object to a MODS record. Mirrors the existing
 * AbstractXmlExporter pattern (default-namespace elements built on $this->dom).
 */
class ModsExporter extends AbstractXmlExporter
{
    private const NS_MODS = 'http://www.loc.gov/mods/v3';

    protected function initializeNamespaces(): void
    {
        $this->primaryNamespace = self::NS_MODS;
        $this->namespaces = ['mods' => self::NS_MODS];
    }

    public function getFormat(): string
    {
        return 'mods';
    }

    public function getFormatName(): string
    {
        return 'MODS 3.7';
    }

    public function getSector(): string
    {
        return 'Libraries';
    }

    protected function buildDocument($resource): \DOMDocument
    {
        $mods = $this->dom->createElementNS(self::NS_MODS, 'mods');
        $mods->setAttribute('version', '3.7');
        $this->dom->appendChild($mods);

        // titleInfo > title
        if ($title = $this->getValue($resource, 'title')) {
            $titleInfo = $this->createElement('titleInfo', null, self::NS_MODS);
            $titleInfo->appendChild($this->createElement('title', $title, self::NS_MODS));
            $mods->appendChild($titleInfo);
        }

        $mods->appendChild($this->createElement('typeOfResource', 'mixed material', self::NS_MODS));

        // originInfo > dateCreated
        if ($dates = $this->getValue($resource, 'dates')) {
            $originInfo = $this->createElement('originInfo', null, self::NS_MODS);
            $originInfo->appendChild($this->createElement('dateCreated', $dates, self::NS_MODS));
            $mods->appendChild($originInfo);
        }

        // physicalDescription > extent
        if ($extent = $this->getValue($resource, 'extentAndMedium')) {
            $pd = $this->createElement('physicalDescription', null, self::NS_MODS);
            $pd->appendChild($this->createElement('extent', $extent, self::NS_MODS));
            $mods->appendChild($pd);
        }

        // abstract (scope and content)
        if ($scope = $this->getValue($resource, 'scopeAndContent')) {
            $mods->appendChild($this->createElement('abstract', $scope, self::NS_MODS));
        }

        // identifier
        $identifier = $resource->identifier ?? $this->getValue($resource, 'identifier');
        if ($identifier) {
            $idEl = $this->createElement('identifier', $identifier, self::NS_MODS);
            $idEl->setAttribute('type', 'local');
            $mods->appendChild($idEl);
        }

        // accessCondition
        if ($access = $this->getValue($resource, 'accessConditions')) {
            $ac = $this->createElement('accessCondition', $access, self::NS_MODS);
            $ac->setAttribute('type', 'restriction on access');
            $mods->appendChild($ac);
        }
        if ($repro = $this->getValue($resource, 'reproductionConditions')) {
            $ac = $this->createElement('accessCondition', $repro, self::NS_MODS);
            $ac->setAttribute('type', 'use and reproduction');
            $mods->appendChild($ac);
        }

        return $this->dom;
    }
}
