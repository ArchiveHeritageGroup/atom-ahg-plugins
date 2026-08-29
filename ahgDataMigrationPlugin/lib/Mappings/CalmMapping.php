<?php

namespace ahgDataMigrationPlugin\Mappings;

/**
 * Field mapping definitions for Axiell Calm archival exports.
 *
 * Calm is ISAD(G)-native, so most of this is renaming rather than
 * transformation - the opposite of PastPerfect or Vernon, which are
 * museum-shaped and need real restructuring.
 *
 * Two things do NOT map mechanically and are handled here:
 *
 *  - Hierarchy lives inside RefNo ("GB 123 ABC/1/2/3") and nowhere else.
 *    deriveParentRef() recovers the parent reference; see the note on that
 *    method for what still has to happen upstream of it.
 *  - Calm's Piece level has no AtoM equivalent. getLevelMapping() folds it
 *    into Item, which is a decision rather than a fact - see below.
 */
class CalmMapping
{
    /**
     * Calm catalogue (Archive) record to AtoM ISAD(G) fields.
     *
     * Field names follow Calm's export headings. Calm installations are
     * configurable and sites add local fields, so treat this as the common
     * core and expect to extend it per deployment rather than as a closed set.
     */
    public static function getCalmToAtomMapping(): array
    {
        return [
            // Identity Statement Area (ISAD 3.1)
            'RefNo'                 => 'identifier',
            'AltRefNo'              => 'alternativeIdentifiers',
            'Title'                 => 'title',
            'Date'                  => 'eventDates',
            'Level'                 => 'levelOfDescription',
            'Extent'                => 'extentAndMedium',
            'PhysicalDescription'   => 'extentAndMedium',

            // Context Area (ISAD 3.2)
            'AdminHistory'          => 'archivalHistory',
            'CustodialHistory'      => 'archivalHistory',
            'Acquisition'           => 'acquisition',
            'CreatorName'           => 'creators',

            // Content and Structure Area (ISAD 3.3)
            'Description'           => 'scopeAndContent',
            'Appraisal'             => 'appraisal',
            'Accruals'              => 'accruals',
            'Arrangement'           => 'arrangement',

            // Conditions of Access and Use Area (ISAD 3.4)
            'AccessConditions'      => 'accessConditions',
            'ClosedUntil'           => 'accessConditions',
            'Copyright'             => 'reproductionConditions',
            'Language'              => 'language',
            'PhysicalCharacteristics' => 'physicalCharacteristics',
            'FindingAids'           => 'findingAids',

            // Allied Materials Area (ISAD 3.5)
            'RelatedMaterial'       => 'relatedUnitsOfDescription',
            'PublicationNote'       => 'publicationNote',
            'Location'              => 'locationOfOriginals',

            // Notes and Control (ISAD 3.6, 3.7)
            'Notes'                 => 'generalNote',
            'CatalogueStatus'       => 'descriptionStatus',

            // Access points - these are LINKS in Calm, not text. See
            // getAuthorityFields(); mapping them straight to the access point
            // columns flattens the relationship to an actor record.
            'PersonName'            => 'nameAccessPoints',
            'CorpName'              => 'nameAccessPoints',
            'Subject'               => 'subjectAccessPoints',
            'Place'                 => 'placeAccessPoints',
        ];
    }

    /**
     * Calm Level values to AtoM levels of description.
     *
     * Calm's Piece sits below Item and AtoM ships no equivalent. Folding it
     * into Item is the safe default because it never loses a record, but it
     * DOES flatten a distinction the depositor made. Where Piece is used
     * meaningfully, add a Piece term to the levels taxonomy and override this
     * rather than accepting the collapse silently.
     */
    public static function getLevelMapping(): array
    {
        return [
            'Collection'  => 'Collection',
            'Fonds'       => 'Fonds',
            'Sub-fonds'   => 'Subfonds',
            'Subfonds'    => 'Subfonds',
            'Group'       => 'Fonds',
            'Sub-group'   => 'Subfonds',
            'Series'      => 'Series',
            'Sub-series'  => 'Subseries',
            'Subseries'   => 'Subseries',
            'File'        => 'File',
            'Item'        => 'Item',
            'Piece'       => 'Item',
        ];
    }

    /**
     * Calm record types that are NOT catalogue records.
     *
     * Calm keeps these as separate record types with real links to catalogue
     * entries. They belong in the corresponding AtoM entity, not squashed into
     * a text field on the description - a link flattened to a string cannot be
     * restored later.
     */
    public static function getRecordTypeTargets(): array
    {
        return [
            'Archive'      => 'informationObject',
            'Persons'      => 'actor',
            'Corporate'    => 'actor',
            'Places'       => 'placeAccessPoint',
            'Subjects'     => 'subjectAccessPoint',
            'Accessions'   => 'accession',
            'Location'     => 'physicalObject',
            'Conservation' => 'conditionAssessment',
        ];
    }

    /**
     * Calm authority (Persons / Corporate) record to AtoM actor fields.
     */
    public static function getAuthorityToAtomMapping(): array
    {
        return [
            'Name'            => 'authorizedFormOfName',
            'Surname'         => 'authorizedFormOfName',
            'Forenames'       => 'authorizedFormOfName',
            'NonPreferredTerm'=> 'parallelFormsOfName',
            'DateRange'       => 'datesOfExistence',
            'Epithet'         => 'history',
            'History'         => 'history',
            'Activity'        => 'functions',
            'Place'           => 'places',
            'Source'          => 'sources',
            'RecordID'        => 'legacyId',
        ];
    }

    /**
     * Calm Accessions record to AtoM accession fields.
     */
    public static function getAccessionToAtomMapping(): array
    {
        return [
            'AccNo'          => 'identifier',
            'AccessionDate'  => 'date',
            'Title'          => 'title',
            'Description'    => 'scopeAndContent',
            'Extent'         => 'receivedExtentUnits',
            'Source'         => 'donorName',
            'AcquisitionType'=> 'acquisitionType',
            'Location'       => 'locationInformation',
        ];
    }

    /**
     * Recover a parent reference from a Calm RefNo.
     *
     * Calm encodes the tree in the reference code and nowhere else:
     * "GB 123 ABC/1/2" is the child of "GB 123 ABC/1". Returns null at the
     * top of the tree, where the caller should treat the record as a root.
     *
     * The default delimiter is "/", which is the Calm convention, but sites
     * do use others - pass the one the export actually uses rather than
     * assuming.
     *
     * This resolves ONE reference. It is deliberately not a hierarchy builder:
     * a real load also has to sort parents before children, synthesise missing
     * intermediate levels where a site skipped one, and reject cycles. Doing
     * that per row as it imports produces orphans whose lft/rgt are never set,
     * which reads as "the record imported fine" right up until the tree is
     * browsed.
     */
    public static function deriveParentRef(string $refNo, string $delimiter = '/'): ?string
    {
        $refNo = trim($refNo);
        if ('' === $refNo) {
            return null;
        }

        $pos = strrpos($refNo, $delimiter);
        if (false === $pos || 0 === $pos) {
            return null;
        }

        $parent = rtrim(substr($refNo, 0, $pos));

        return '' === $parent ? null : $parent;
    }

    /**
     * Split a Calm multi-value field into the separator AtoM CSV expects.
     *
     * Calm repeats values within a single exported cell; AtoM CSV uses "|".
     */
    public static function splitMultiValue(?string $value, string $sourceSeparator = ';'): string
    {
        if (null === $value || '' === trim($value)) {
            return '';
        }

        $parts = array_filter(array_map('trim', explode($sourceSeparator, $value)), static function ($v) {
            return '' !== $v;
        });

        return implode('|', $parts);
    }

    /**
     * All supported Calm source fields.
     */
    public static function getCalmSourceFields(): array
    {
        return array_keys(self::getCalmToAtomMapping());
    }

    /**
     * Fields that carry a relationship rather than a value, and so must be
     * resolved to an authority record instead of copied as text.
     */
    public static function getAuthorityFields(): array
    {
        return ['PersonName', 'CorpName', 'Place', 'Subject', 'CreatorName'];
    }
}
