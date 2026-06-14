<?php

namespace AhgMetadataExport\Exporters;

/**
 * EAC-CPF Exporter — Encoded Archival Context (Corporate bodies, Persons, Families).
 *
 * Maps an AtoM authority record (QubitActor) to an EAC-CPF instance.
 */
class EacCpfExporter extends AbstractXmlExporter
{
    private const NS_EAC = 'urn:isbn:1-931666-33-4';

    protected function initializeNamespaces(): void
    {
        $this->primaryNamespace = self::NS_EAC;
        $this->namespaces = ['eac' => self::NS_EAC];
    }

    public function getFormat(): string
    {
        return 'eac-cpf';
    }

    public function getFormatName(): string
    {
        return 'EAC-CPF';
    }

    public function getSector(): string
    {
        return 'Archives';
    }

    public function getSupportedResourceTypes(): array
    {
        return ['QubitActor'];
    }

    protected function buildDocument($resource): \DOMDocument
    {
        $root = $this->dom->createElementNS(self::NS_EAC, 'eac-cpf');
        $this->dom->appendChild($root);

        // control
        $control = $this->createElement('control', null, self::NS_EAC);
        $recordId = $resource->identifier ?? ('actor-' . ($resource->id ?? ''));
        $control->appendChild($this->createElement('recordId', (string) $recordId, self::NS_EAC));
        $maintenanceStatus = $this->createElement('maintenanceStatus', 'new', self::NS_EAC);
        $control->appendChild($maintenanceStatus);
        $agency = $this->createElement('maintenanceAgency', null, self::NS_EAC);
        $agency->appendChild($this->createElement('agencyName', 'The Archive and Heritage Group', self::NS_EAC));
        $control->appendChild($agency);
        $root->appendChild($control);

        // cpfDescription
        $cpf = $this->createElement('cpfDescription', null, self::NS_EAC);

        // identity
        $identity = $this->createElement('identity', null, self::NS_EAC);
        $entityType = $this->resolveEntityType($resource);
        $identity->appendChild($this->createElement('entityType', $entityType, self::NS_EAC));
        if ($name = $this->getValue($resource, 'authorizedFormOfName')) {
            $nameEntry = $this->createElement('nameEntry', null, self::NS_EAC);
            $nameEntry->appendChild($this->createElement('part', $name, self::NS_EAC));
            $identity->appendChild($nameEntry);
        }
        $cpf->appendChild($identity);

        // description
        $description = $this->createElement('description', null, self::NS_EAC);
        if ($dates = $this->getValue($resource, 'datesOfExistence')) {
            $existDates = $this->createElement('existDates', null, self::NS_EAC);
            $existDates->appendChild($this->createElement('dateRange', null, self::NS_EAC));
            $existDates->appendChild($this->createElement('date', $dates, self::NS_EAC));
            $description->appendChild($existDates);
        }
        if ($history = $this->getValue($resource, 'history')) {
            $biogHist = $this->createElement('biogHist', null, self::NS_EAC);
            $biogHist->appendChild($this->createElement('p', $history, self::NS_EAC));
            $description->appendChild($biogHist);
        }
        if ($places = $this->getValue($resource, 'places')) {
            $biogHist = $this->createElement('biogHist', null, self::NS_EAC);
            $biogHist->appendChild($this->createElement('p', $places, self::NS_EAC));
            $description->appendChild($biogHist);
        }
        $cpf->appendChild($description);

        $root->appendChild($cpf);

        return $this->dom;
    }

    /**
     * Map AtoM entity type to an EAC-CPF entityType value.
     */
    private function resolveEntityType($resource): string
    {
        $type = null;
        try {
            $t = $resource->getEntityType(['culture' => 'en']);
            $type = is_object($t) && method_exists($t, '__toString') ? (string) $t : (is_string($t) ? $t : null);
        } catch (\Exception $e) {
            // fall through
        }
        $type = strtolower((string) $type);
        if (str_contains($type, 'person')) {
            return 'person';
        }
        if (str_contains($type, 'family')) {
            return 'family';
        }

        return 'corporateBody';
    }
}
