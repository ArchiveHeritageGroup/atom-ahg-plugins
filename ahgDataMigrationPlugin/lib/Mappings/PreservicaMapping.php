<?php

namespace ahgDataMigrationPlugin\Mappings;

/**
 * Field mapping definitions for Preservica OPEX and XIP/PAX formats.
 * 
 * Provides bidirectional mapping between Preservica metadata schemas
 * and AtoM ISAD(G) fields for import and export operations.
 */
class PreservicaMapping
{
    /**
     * OPEX (Open Preservation Exchange) to AtoM field mappings.
     * OPEX uses Dublin Core elements with Preservica extensions.
     */
    public static function getOpexToAtomMapping(): array
    {
        return [
            // Identity Statement Area (ISAD 3.1)
            'Identifier'            => 'identifier',
            'SourceID'              => 'legacyId',
            'Title'                 => 'title',
            'dc:title'              => 'title',
            'dc:date'               => 'eventDates',
            'dcterms:created'       => 'eventDates',
            'dc:type'               => 'levelOfDescription',
            'dc:format'             => 'extentAndMedium',
            'dcterms:extent'        => 'extentAndMedium',
            
            // Context Area (ISAD 3.2)
            'dc:creator'            => 'creators',
            'dc:contributor'        => 'nameAccessPoints',
            'dcterms:provenance'    => 'archivalHistory',
            'dc:publisher'          => 'repository',
            
            // Content Area (ISAD 3.3)
            'Description'           => 'scopeAndContent',
            'dc:description'        => 'scopeAndContent',
            'dc:subject'            => 'subjectAccessPoints',
            'dc:coverage'           => 'placeAccessPoints',
            'dcterms:spatial'       => 'placeAccessPoints',
            'dcterms:temporal'      => 'eventDates',
            'dc:language'           => 'language',
            
            // Access Area (ISAD 3.4)
            'SecurityDescriptor'    => 'accessConditions',
            'dcterms:accessRights'  => 'accessConditions',
            'dc:rights'             => 'reproductionConditions',
            'dcterms:license'       => 'reproductionConditions',
            
            // Digital Object
            'Filename'              => 'digitalObjectPath',
            'File'                  => 'digitalObjectPath',
            'Bitstream'             => 'digitalObjectPath',
            'FileSize'              => 'extentAndMedium',
            'Fixity'                => 'physicalCharacteristics',
            
            // Preservica-specific
            'PreservicaRef'         => 'alternativeIdentifiers',
            'IngestDate'            => 'revisionHistory',
        ];
    }

    /**
     * XIP/PAX to AtoM field mappings.
     * XIP is Preservica's internal XML format used in PAX packages.
     */
    public static function getXipToAtomMapping(): array
    {
        return [
            // Structural Object fields
            'Ref'                   => 'legacyId',
            'Title'                 => 'title',
            'Description'           => 'scopeAndContent',
            'SecurityTag'           => 'accessConditions',
            'Parent'                => 'parentId',
            
            // Content Object fields
            'ContentRef'            => 'alternativeIdentifiers',
            'ContentTitle'          => 'title',
            'ContentDescription'    => 'scopeAndContent',
            
            // Representation fields
            'RepresentationRef'     => 'alternativeIdentifiers',
            'RepresentationType'    => 'levelOfDescription',
            'RepresentationName'    => 'title',
            
            // Generation/Bitstream fields
            'Filename'              => 'digitalObjectPath',
            'FileSize'              => 'extentAndMedium',
            'PhysicalSize'          => 'extentAndMedium',
            'Fixities'              => 'physicalCharacteristics',
            'FormatPUID'            => 'physicalCharacteristics',
            'FormatName'            => 'physicalCharacteristics',
            'OriginalName'          => 'title',
            
            // Dublin Core in XIP
            'dc:title'              => 'title',
            'dc:creator'            => 'creators',
            'dc:subject'            => 'subjectAccessPoints',
            'dc:description'        => 'scopeAndContent',
            'dc:date'               => 'eventDates',
            'dc:type'               => 'levelOfDescription',
            'dc:format'             => 'extentAndMedium',
            'dc:identifier'         => 'identifier',
            'dc:source'             => 'locationOfOriginals',
            'dc:language'           => 'language',
            'dc:rights'             => 'reproductionConditions',
            'dc:coverage'           => 'placeAccessPoints',
            'dc:contributor'        => 'nameAccessPoints',
            'dc:publisher'          => 'repository',
        ];
    }

    /**
     * AtoM to OPEX field mappings (for export).
     */
    public static function getAtomToOpexMapping(): array
    {
        return [
            // Identity Statement
            'identifier'            => 'Identifier',
            'legacyId'              => 'SourceID',
            'title'                 => 'dc:title',
            'levelOfDescription'    => 'dc:type',
            'extentAndMedium'       => 'dcterms:extent',
            'eventDates'            => 'dc:date',
            
            // Context
            'creators'              => 'dc:creator',
            'archivalHistory'       => 'dcterms:provenance',
            'repository'            => 'dc:publisher',
            
            // Content
            'scopeAndContent'       => 'dc:description',
            'subjectAccessPoints'   => 'dc:subject',
            'placeAccessPoints'     => 'dcterms:spatial',
            'language'              => 'dc:language',
            
            // Access
            'accessConditions'      => 'dcterms:accessRights',
            'reproductionConditions'=> 'dc:rights',
            
            // Digital
            'digitalObjectPath'     => 'File',
            'digitalObjectChecksum' => 'Fixity',
        ];
    }

    /**
     * AtoM to XIP field mappings (for export).
     */
    public static function getAtomToXipMapping(): array
    {
        return [
            // Structural Object
            'legacyId'              => 'Ref',
            'title'                 => 'Title',
            'scopeAndContent'       => 'Description',
            'accessConditions'      => 'SecurityTag',
            'parentId'              => 'Parent',
            
            // Dublin Core metadata
            'identifier'            => 'dc:identifier',
            'creators'              => 'dc:creator',
            'subjectAccessPoints'   => 'dc:subject',
            'eventDates'            => 'dc:date',
            'levelOfDescription'    => 'dc:type',
            'extentAndMedium'       => 'dc:format',
            'language'              => 'dc:language',
            'reproductionConditions'=> 'dc:rights',
            'placeAccessPoints'     => 'dc:coverage',
            'repository'            => 'dc:publisher',
        ];
    }

    /**
     * Get Preservica security descriptors mapped to AtoM access conditions.
     */
    public static function getSecurityDescriptorMapping(): array
    {
        return [
            'open'          => 'Open',
            'closed'        => 'Closed',
            'restricted'    => 'Restricted',
            'public'        => 'Open',
            'private'       => 'Closed',
            'internal'      => 'Restricted',
        ];
    }

    /**
     * Get level of description mapping from Preservica types.
     */
    public static function getLevelMapping(): array
    {
        return [
            'StructuralObject'  => 'Series',
            'InformationObject' => 'Item',
            'ContentObject'     => 'Item',
            'Asset'             => 'Item',
            'Folder'            => 'File',
            'Collection'        => 'Collection',
            'Fonds'             => 'Fonds',
        ];
    }

    /**
     * Get all supported source fields for OPEX format.
     */
    public static function getOpexSourceFields(): array
    {
        return array_keys(self::getOpexToAtomMapping());
    }

    /**
     * Get all supported source fields for XIP format.
     */
    public static function getXipSourceFields(): array
    {
        return array_keys(self::getXipToAtomMapping());
    }

    /**
     * Get target AtoM fields available for mapping.
     */
    public static function getAtomTargetFields(): array
    {
        return [
            'legacyId'              => 'Legacy ID',
            'parentId'              => 'Parent ID',
            'identifier'            => 'Identifier',
            'title'                 => 'Title',
            'levelOfDescription'    => 'Level of Description',
            'extentAndMedium'       => 'Extent and Medium',
            'repository'            => 'Repository',
            'archivalHistory'       => 'Archival History',
            'acquisition'           => 'Immediate Source of Acquisition',
            'scopeAndContent'       => 'Scope and Content',
            'appraisal'             => 'Appraisal',
            'accruals'              => 'Accruals',
            'arrangement'           => 'System of Arrangement',
            'accessConditions'      => 'Conditions Governing Access',
            'reproductionConditions'=> 'Conditions Governing Reproduction',
            'language'              => 'Language of Material',
            'script'                => 'Script of Material',
            'physicalCharacteristics'=> 'Physical Characteristics',
            'findingAids'           => 'Finding Aids',
            'locationOfOriginals'   => 'Existence/Location of Originals',
            'locationOfCopies'      => 'Existence/Location of Copies',
            'relatedUnitsOfDescription' => 'Related Units of Description',
            'publicationNote'       => 'Publication Note',
            'generalNote'           => 'General Note',
            'archivistNote'         => 'Archivist Note',
            'rules'                 => 'Rules or Conventions',
            'revisionHistory'       => 'Revision History',
            'eventDates'            => 'Date(s)',
            'eventTypes'            => 'Event Type',
            'eventActors'           => 'Event Actor',
            'eventPlaces'           => 'Event Place',
            'creators'              => 'Creator(s)',
            'subjectAccessPoints'   => 'Subject Access Points',
            'placeAccessPoints'     => 'Place Access Points',
            'nameAccessPoints'      => 'Name Access Points',
            'genreAccessPoints'     => 'Genre Access Points',
            'digitalObjectPath'     => 'Digital Object Path',
            'digitalObjectUri'      => 'Digital Object URI',
            'alternativeIdentifiers'=> 'Alternative Identifiers',
            'culture'               => 'Culture/Language',
        ];
    }
}
