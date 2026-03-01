<?php

namespace AhgAuthority\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Service #3: EAC-CPF Export Enrichment (#209)
 *
 * Enriches EAC-CPF XML export with external identifiers.
 * Adds <sources> and <otherRecordId> elements for linked authorities.
 */
class AuthorityEacExportService
{
    /**
     * Get external identifiers formatted for EAC-CPF export.
     */
    public function getEacIdentifiers(int $actorId): array
    {
        $identifiers = DB::table('ahg_actor_identifier')
            ->where('actor_id', $actorId)
            ->get()
            ->all();

        $otherRecordIds = [];
        $sources = [];

        foreach ($identifiers as $ident) {
            // <otherRecordId> elements
            $otherRecordIds[] = [
                'localType'  => $ident->identifier_type,
                'value'      => $ident->identifier_value,
                'uri'        => $ident->uri ?? '',
                'isVerified' => (bool) $ident->is_verified,
            ];

            // <source> elements
            if (!empty($ident->uri)) {
                $sources[] = [
                    'href'  => $ident->uri,
                    'label' => $ident->label ?? $this->getSourceLabel($ident->identifier_type),
                ];
            }
        }

        return [
            'otherRecordIds' => $otherRecordIds,
            'sources'        => $sources,
        ];
    }

    /**
     * Enrich an EAC-CPF XML string with external identifiers.
     */
    public function enrichEacXml(string $xml, int $actorId): string
    {
        $eacData = $this->getEacIdentifiers($actorId);

        if (empty($eacData['otherRecordIds']) && empty($eacData['sources'])) {
            return $xml;
        }

        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;

        if (!@$doc->loadXML($xml)) {
            return $xml;
        }

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('eac', 'urn:isbn:1-931666-33-4');

        // Add otherRecordId elements to <control>
        $controlNodes = $xpath->query('//eac:control');
        if ($controlNodes->length > 0) {
            $control = $controlNodes->item(0);

            foreach ($eacData['otherRecordIds'] as $orid) {
                $elem = $doc->createElementNS(
                    'urn:isbn:1-931666-33-4',
                    'otherRecordId'
                );
                $elem->setAttribute('localType', $orid['localType']);
                $elem->textContent = $orid['value'];
                $control->appendChild($elem);
            }
        }

        // Add source elements to <sources>
        if (!empty($eacData['sources'])) {
            $sourcesNodes = $xpath->query('//eac:control/eac:sources');
            $sourcesElem = null;

            if ($sourcesNodes->length > 0) {
                $sourcesElem = $sourcesNodes->item(0);
            } else {
                // Create <sources> if it doesn't exist
                if ($controlNodes->length > 0) {
                    $sourcesElem = $doc->createElementNS(
                        'urn:isbn:1-931666-33-4',
                        'sources'
                    );
                    $controlNodes->item(0)->appendChild($sourcesElem);
                }
            }

            if ($sourcesElem) {
                foreach ($eacData['sources'] as $src) {
                    $sourceElem = $doc->createElementNS(
                        'urn:isbn:1-931666-33-4',
                        'source'
                    );
                    $sourceElem->setAttribute('xlink:href', $src['href']);

                    $descNote = $doc->createElementNS(
                        'urn:isbn:1-931666-33-4',
                        'sourceEntry'
                    );
                    $descNote->textContent = $src['label'];
                    $sourceElem->appendChild($descNote);

                    $sourcesElem->appendChild($sourceElem);
                }
            }
        }

        return $doc->saveXML();
    }

    /**
     * Get display label for an authority source type.
     */
    protected function getSourceLabel(string $type): string
    {
        $labels = [
            'wikidata' => 'Wikidata',
            'viaf'     => 'Virtual International Authority File (VIAF)',
            'ulan'     => 'Getty Union List of Artist Names (ULAN)',
            'lcnaf'    => 'Library of Congress Name Authority File (LCNAF)',
            'isni'     => 'International Standard Name Identifier (ISNI)',
            'orcid'    => 'ORCID',
            'gnd'      => 'Gemeinsame Normdatei (GND)',
        ];

        return $labels[$type] ?? ucfirst($type);
    }

    /**
     * Export a single actor as EAC-CPF XML with enrichment.
     */
    public function exportActor(int $actorId): ?string
    {
        // Check if ahgExportPlugin has EAC-CPF export
        $plugins = \sfProjectConfiguration::getActive()->getPlugins();
        if (!in_array('ahgExportPlugin', $plugins)) {
            return null;
        }

        // Delegate to ahgExportPlugin's EAC builder if available
        $exportServiceFile = \sfConfig::get('sf_root_dir') .
            '/atom-ahg-plugins/ahgExportPlugin/lib/Services/EacCpfExportService.php';

        if (!file_exists($exportServiceFile)) {
            return null;
        }

        require_once $exportServiceFile;

        if (!class_exists('\\AhgExport\\Services\\EacCpfExportService')) {
            return null;
        }

        $exportService = new \AhgExport\Services\EacCpfExportService();
        $baseXml = $exportService->exportActor($actorId);

        if (!$baseXml) {
            return null;
        }

        return $this->enrichEacXml($baseXml, $actorId);
    }
}
