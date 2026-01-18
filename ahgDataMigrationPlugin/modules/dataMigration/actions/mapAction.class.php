<?php

class dataMigrationMapAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        $filepath = $this->getUser()->getAttribute('migration_file');
        $filename = $this->getUser()->getAttribute('migration_filename');
        $detection = $this->getUser()->getAttribute('migration_detection');

        if (!$filepath || !file_exists($filepath)) {
            $this->getUser()->setFlash('error', 'Session expired. Please upload file again.');
            $this->redirect(['module' => 'dataMigration', 'action' => 'index']);
        }

        // Get target type from request or session
        $targetType = $request->getParameter('target_type', $this->getUser()->getAttribute('migration_target_type', 'archives'));
        $this->getUser()->setAttribute('migration_target_type', $targetType);

        // Check if we should auto-load a mapping (after save)
        $this->autoLoadMappingId = $request->getParameter("load_mapping", null);

        // Get source fields from detection
        $sourceFields = $detection['headers'] ?? [];

        // Get target fields based on type
        $targetFields = $this->getTargetFields($targetType);
        // Sort target fields alphabetically by label, keeping standard and AHG separate
        $standardFields = array_filter($targetFields, fn($k) => strpos($k, "ahg") !== 0, ARRAY_FILTER_USE_KEY);
        $ahgFields = array_filter($targetFields, fn($k) => strpos($k, "ahg") === 0, ARRAY_FILTER_USE_KEY);
        asort($standardFields);
        asort($ahgFields);
        $targetFields = array_merge($standardFields, $ahgFields);

        // Build mapping rows
        $mappingRows = [];
        foreach ($sourceFields as $field) {
            $mappingRows[] = [
                'source_field' => $field,
                'atom_field' => $this->suggestMapping($field, $targetType),
                'constant_value' => '',
                'concat_constant' => false,
                'concatenate' => false,
                'concat_symbol' => '|',
                'transform' => '',
                'include' => true
            ];
        }

        // Get saved mappings
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        $savedMappings = \Illuminate\Database\Capsule\Manager::table('atom_data_mapping')
            ->orderBy('name')
            ->get()->toArray();

        $this->filename = $filename;
        $this->detection = $detection;
        $this->sourceFields = $sourceFields;
        $this->targetFields = $targetFields;
        $this->targetType = $targetType;
        $this->mappingRows = $mappingRows;
        $this->savedMappings = $savedMappings;
        $this->targetTypeLabels = $this->getTargetTypeLabels();
    }

    protected function getTargetTypeLabels(): array
    {
        return [
            'archives' => 'Archives (ISAD-G)',
            'library' => 'Library (MARC/MODS)',
            'museum' => 'Museum (SPECTRUM 5.1)',
            'gallery' => 'Gallery (CCO/VRA)',
            'dam' => 'Digital Asset Management',
        ];
    }

    protected function getTargetFields(string $type): array
    {
        switch ($type) {
            case 'museum':
                return $this->getMuseumFields();
            case 'library':
                return $this->getLibraryFields();
            case 'gallery':
                return $this->getGalleryFields();
            case 'dam':
                return $this->getDAMFields();
            case 'archives':
            default:
                return $this->getArchivesFields();
        }
    }

    protected function getArchivesFields(): array
    {
        return [
            // Standard ISAD-G Fields (alphabetically sorted)
            "accessConditions" => "Access Conditions",
            "accessionNumber" => "Accession Number",
            "accruals" => "Accruals",
            "acquisition" => "Acquisition / Immediate Source",
            "alternateTitle" => "Alternate Title",
            "alternativeIdentifierLabels" => "Alternative Identifier Labels",
            "alternativeIdentifiers" => "Alternative Identifiers",
            "appraisal" => "Appraisal",
            "archivalHistory" => "Archival History",
            "archivistNote" => "Archivist Note",
            "arrangement" => "Arrangement",
            "culture" => "Culture (language code)",
            "descriptionIdentifier" => "Description Identifier",
            "descriptionStatus" => "Description Status",
            "digitalObjectPath" => "Digital Object Path",
            "digitalObjectURI" => "Digital Object URI",
            "eventActorHistories" => "Event Actor Histories",
            "eventActors" => "Event Actors / Creators",
            "eventDates" => "Event Dates",
            "eventEndDates" => "Event End Dates",
            "eventStartDates" => "Event Start Dates",
            "eventTypes" => "Event Types",
            "extentAndMedium" => "Extent and Medium",
            "findingAids" => "Finding Aids",
            "generalNote" => "General Note",
            "genreAccessPoints" => "Genre Access Points",
            "identifier" => "Identifier / Reference Code",
            "institutionIdentifier" => "Institution Identifier",
            "language" => "Language of Material",
            "languageNote" => "Language and Script Notes",
            "languageOfDescription" => "Language of Description",
            "legacyId" => "Legacy ID",
            "levelOfDescription" => "Level of Description",
            "levelOfDetail" => "Level of Detail",
            "locationOfCopies" => "Location of Copies",
            "locationOfOriginals" => "Location of Originals",
            "nameAccessPoints" => "Name Access Points",
            "parentId" => "Parent ID",
            "physicalCharacteristics" => "Physical Characteristics",
            "physicalObjectLocation" => "Physical Object Location",
            "physicalObjectName" => "Physical Object Name",
            "physicalObjectType" => "Physical Object Type",
            "placeAccessPoints" => "Place Access Points",
            "publicationNote" => "Publication Note",
            "publicationStatus" => "Publication Status",
            "qubitParentSlug" => "Parent Slug",
            "relatedUnitsOfDescription" => "Related Units of Description",
            "repository" => "Repository",
            "reproductionConditions" => "Reproduction Conditions",
            "revisionHistory" => "Revision History",
            "rules" => "Rules / Conventions",
            "scopeAndContent" => "Scope and Content",
            "script" => "Script of Material",
            "scriptOfDescription" => "Script of Description",
            "sources" => "Sources",
            "subjectAccessPoints" => "Subject Access Points",
            "title" => "Title",
            // AHG Extended Fields (for Preservica/OPEX import)
            "ahgAccessLevel" => "AHG: Access Level",
            "ahgCopyrightStatus" => "AHG: Copyright Status",
            "ahgProvenanceEventCount" => "AHG: Provenance Event Count",
            "ahgProvenanceFirstDate" => "AHG: Provenance First Date",
            "ahgProvenanceHistory" => "AHG: Provenance History",
            "ahgProvenanceEventDates" => "AHG: Provenance Event Dates",
            "ahgProvenanceEventTypes" => "AHG: Provenance Event Types",
            "ahgProvenanceEventDescriptions" => "AHG: Provenance Event Descriptions",
            "ahgProvenanceEventAgents" => "AHG: Provenance Event Agents",
            'ahgAccessLevel' => 'AHG: Access Level',
            'ahgCopyrightStatus' => 'AHG: Copyright Status',
            'ahgRelationships' => 'AHG: Relationships',
            'ahgRightsBasis' => 'AHG: Rights Basis',
            'ahgRightsStatement' => 'AHG: Rights Statement',
            'ahgSecurityClassification' => 'AHG: Security Classification',
            'ahgConditionOverallRating' => 'AHG: Condition Overall Rating',
            'ahgConditionSummary' => 'AHG: Condition Summary',
            'ahgConditionRecommendations' => 'AHG: Condition Recommendations',
            'ahgConditionPriority' => 'AHG: Condition Priority',
            'ahgConditionContext' => 'AHG: Condition Context',
            'ahgConditionAssessmentDate' => 'AHG: Condition Assessment Date',
            'ahgConditionNextCheckDate' => 'AHG: Condition Next Check Date',
            'ahgConditionEnvironmentalNotes' => 'AHG: Environmental Notes',
            'ahgConditionHandlingNotes' => 'AHG: Handling Notes',
            'ahgConditionDisplayNotes' => 'AHG: Display Notes',
            'ahgConditionStorageNotes' => 'AHG: Storage Notes',
            "ahgProvenanceLastDate" => "AHG: Provenance Last Date",
            "ahgRelationships" => "AHG: Relationships",
            "ahgRightsBasis" => "AHG: Rights Basis",
            "ahgRightsStatement" => "AHG: Rights Statement",
            "ahgSecurityClassification" => "AHG: Security Classification",
            "ahgConditionOverallRating" => "AHG: Condition Overall Rating",
            "ahgConditionSummary" => "AHG: Condition Summary",
            "ahgConditionRecommendations" => "AHG: Condition Recommendations",
            "ahgConditionPriority" => "AHG: Condition Priority",
            "ahgConditionContext" => "AHG: Condition Context",
            "ahgConditionAssessmentDate" => "AHG: Condition Assessment Date",
            "ahgConditionNextCheckDate" => "AHG: Condition Next Check Date",
            "ahgConditionEnvironmentalNotes" => "AHG: Environmental Notes",
            "ahgConditionHandlingNotes" => "AHG: Handling Notes",
            "ahgConditionDisplayNotes" => "AHG: Display Notes",
            "ahgConditionStorageNotes" => "AHG: Storage Notes",
            "allFilenames" => "AHG: All Filenames",
            "digitalObjectChecksum" => "AHG: Digital Object Checksum",
            "digitalObjectMimeType" => "AHG: Digital Object MIME Type",
            "digitalObjectSize" => "AHG: Digital Object Size",
            "Filename" => "AHG: Filename",
        ];
    }
    protected function getMuseumFields(): array
    {
        return [
            // Object Identification
            'identifier' => 'Object Number',
            'culture' => 'Culture (language code)',
            'legacyId' => 'Legacy/Accession Number',
            'title' => 'Object Name / Title',
            'alternateTitle' => 'Other Name',
            'objectType' => 'Object Type / Classification',
            
            // Production
            'eventActors' => 'Maker / Artist / Producer',
            'eventDates' => 'Production Date',
            'eventStartDates' => 'Date Earliest',
            'eventEndDates' => 'Date Latest',
            'placeAccessPoints' => 'Production Place',
            'eventTypes' => 'Production Role',
            'culturalContext' => 'Culture / Period',
            
            // Physical Description
            'extentAndMedium' => 'Materials',
            'physicalCharacteristics' => 'Technique / Process',
            'dimensions' => 'Dimensions',
            'dimensionUnits' => 'Dimension Units',
            'inscriptions' => 'Inscriptions / Marks',
            'distinguishingFeatures' => 'Distinguishing Features',
            
            // Condition (SPECTRUM Condition Check)
            'conditionStatus' => 'Condition',
            'conditionDate' => 'Condition Date',
            'conditionNotes' => 'Condition Notes',
            'conservationPriority' => 'Conservation Priority',
            'hazards' => 'Hazards',
            
            // History & Association
            'scopeAndContent' => 'Description / Significance',
            'archivalHistory' => 'Object History / Provenance',
            'associatedPerson' => 'Associated Person',
            'associatedPlace' => 'Associated Place',
            'associatedEvent' => 'Associated Event / Date',
            
            // Acquisition (SPECTRUM Object Entry)
            'acquisition' => 'Acquisition Method',
            'acquisitionDate' => 'Acquisition Date',
            'acquisitionSource' => 'Acquisition Source',
            'acquisitionNotes' => 'Acquisition Notes',
            'creditLine' => 'Credit Line',
            
            // Location (SPECTRUM Location)
            'currentLocation' => 'Current Location',
            'normalLocation' => 'Normal/Home Location',
            'locationDate' => 'Location Date',
            'locationFitness' => 'Location Fitness',
            
            // Rights & Access
            'accessConditions' => 'Access Restrictions',
            'reproductionConditions' => 'Copyright / Rights',
            'language' => 'Language',
            
            // Subject & Classification
            'subjectAccessPoints' => 'Subject / Keywords',
            'genreAccessPoints' => 'Category / Classification',
            'nameAccessPoints' => 'Related People / Organisations',
            
            // Related Objects
            'relatedUnitsOfDescription' => 'Related Objects',
            'partOf' => 'Part Of / Collection',
            
            // Notes
            'generalNote' => 'Notes',
            'publicNote' => 'Public Note',
            'curatorNote' => 'Curator Note',
            
            // Digital Object
            'digitalObjectPath' => 'Image Filename / Path',
            'digitalObjectURI' => 'Image URL',
            
            // Valuation (GRAP 103)
            'valuationAmount' => 'Valuation Amount',
            'valuationCurrency' => 'Valuation Currency',
            'valuationDate' => 'Valuation Date',
            'valuationMethod' => 'Valuation Method',
            'valuationType' => 'Valuation Type',
            
            // AHG Provenance Fields
            'ahgProvenanceHistory' => 'AHG: Provenance History',
            'ahgProvenanceEventCount' => 'AHG: Provenance Event Count',
            'ahgProvenanceFirstDate' => 'AHG: Provenance First Date',
            'ahgProvenanceLastDate' => 'AHG: Provenance Last Date',
            'ahgProvenanceEventDates' => 'AHG: Provenance Event Dates',
            'ahgProvenanceEventTypes' => 'AHG: Provenance Event Types',
            'ahgProvenanceEventDescriptions' => 'AHG: Provenance Event Descriptions',
            'ahgProvenanceEventAgents' => 'AHG: Provenance Event Agents',
            'ahgAccessLevel' => 'AHG: Access Level',
            'ahgCopyrightStatus' => 'AHG: Copyright Status',
            'ahgRelationships' => 'AHG: Relationships',
            'ahgRightsBasis' => 'AHG: Rights Basis',
            'ahgRightsStatement' => 'AHG: Rights Statement',
            'ahgSecurityClassification' => 'AHG: Security Classification',
            'ahgConditionOverallRating' => 'AHG: Condition Overall Rating',
            'ahgConditionSummary' => 'AHG: Condition Summary',
            'ahgConditionRecommendations' => 'AHG: Condition Recommendations',
            'ahgConditionPriority' => 'AHG: Condition Priority',
            'ahgConditionContext' => 'AHG: Condition Context',
            'ahgConditionAssessmentDate' => 'AHG: Condition Assessment Date',
            'ahgConditionNextCheckDate' => 'AHG: Condition Next Check Date',
            'ahgConditionEnvironmentalNotes' => 'AHG: Environmental Notes',
            'ahgConditionHandlingNotes' => 'AHG: Handling Notes',
            'ahgConditionDisplayNotes' => 'AHG: Display Notes',
            'ahgConditionStorageNotes' => 'AHG: Storage Notes',

            // Hierarchy
            'parentId' => 'Parent ID',
            'qubitParentSlug' => 'Parent Slug',
        ];
    }

    protected function getLibraryFields(): array
    {
        return [
            // Identification
            'identifier' => 'Call Number / Control Number',
            'culture' => 'Culture (language code)',
            'legacyId' => 'Legacy ID / System Number',
            'isbn' => 'ISBN',
            'issn' => 'ISSN',
            'title' => 'Title',
            'alternateTitle' => 'Subtitle / Alternative Title',
            'uniformTitle' => 'Uniform Title',
            'seriesTitle' => 'Series Title',
            
            // Responsibility
            'eventActors' => 'Author / Creator',
            'author' => 'Author (Personal)',
            'corporateAuthor' => 'Corporate Author',
            'contributor' => 'Contributor',
            'editor' => 'Editor',
            'translator' => 'Translator',
            
            // Publication
            'publisher' => 'Publisher',
            'publicationPlace' => 'Place of Publication',
            'eventDates' => 'Publication Date',
            'edition' => 'Edition',
            
            // Physical Description
            'extentAndMedium' => 'Physical Description / Extent',
            'physicalCharacteristics' => 'Physical Details',
            'dimensions' => 'Dimensions',
            'format' => 'Format / Media Type',
            
            // Content
            'scopeAndContent' => 'Summary / Abstract',
            'tableOfContents' => 'Table of Contents',
            'language' => 'Language',
            'languageNote' => 'Language Note',
            
            // Subject Access
            'subjectAccessPoints' => 'Subject Headings (LCSH)',
            'genreAccessPoints' => 'Genre / Form',
            'placeAccessPoints' => 'Geographic Subject',
            'nameAccessPoints' => 'Name Subject',
            'classification' => 'Classification (DDC/LCC)',
            
            // Notes
            'generalNote' => 'General Note',
            'bibliographyNote' => 'Bibliography Note',
            'contentsNote' => 'Contents Note',
            'accessConditions' => 'Access Restrictions',
            'reproductionConditions' => 'Reproduction Rights',
            
            // Holdings
            'repository' => 'Library / Repository',
            'currentLocation' => 'Location / Shelf',
            'copyNumber' => 'Copy Number',
            'barcode' => 'Barcode',
            'itemStatus' => 'Item Status',
            
            // Links
            'relatedUnitsOfDescription' => 'Related Records',
            'digitalObjectPath' => 'Digital Object Path',
            'digitalObjectURI' => 'Digital Object URL',
            
            // Cataloguing
            'archivistNote' => 'Cataloguer Note',
            'rules' => 'Cataloguing Rules (RDA/AACR2)',
            
            // Parent
            // AHG Extended Fields
            'ahgProvenanceHistory' => 'AHG: Provenance History',
            'ahgProvenanceEventCount' => 'AHG: Provenance Event Count',
            'ahgProvenanceFirstDate' => 'AHG: Provenance First Date',
            'ahgProvenanceLastDate' => 'AHG: Provenance Last Date',
            'ahgProvenanceEventDates' => 'AHG: Provenance Event Dates',
            'ahgProvenanceEventTypes' => 'AHG: Provenance Event Types',
            'ahgProvenanceEventDescriptions' => 'AHG: Provenance Event Descriptions',
            'ahgProvenanceEventAgents' => 'AHG: Provenance Event Agents',
            'ahgAccessLevel' => 'AHG: Access Level',
            'ahgCopyrightStatus' => 'AHG: Copyright Status',
            'ahgRelationships' => 'AHG: Relationships',
            'ahgRightsBasis' => 'AHG: Rights Basis',
            'ahgRightsStatement' => 'AHG: Rights Statement',
            'ahgSecurityClassification' => 'AHG: Security Classification',
            'ahgConditionOverallRating' => 'AHG: Condition Overall Rating',
            'ahgConditionSummary' => 'AHG: Condition Summary',
            'ahgConditionRecommendations' => 'AHG: Condition Recommendations',
            'ahgConditionPriority' => 'AHG: Condition Priority',
            'ahgConditionContext' => 'AHG: Condition Context',
            'ahgConditionAssessmentDate' => 'AHG: Condition Assessment Date',
            'ahgConditionNextCheckDate' => 'AHG: Condition Next Check Date',
            'ahgConditionEnvironmentalNotes' => 'AHG: Environmental Notes',
            'ahgConditionHandlingNotes' => 'AHG: Handling Notes',
            'ahgConditionDisplayNotes' => 'AHG: Display Notes',
            'ahgConditionStorageNotes' => 'AHG: Storage Notes',
            'parentId' => 'Parent ID (for analytics)',
            'qubitParentSlug' => 'Parent Slug',
        ];
    }

    protected function getGalleryFields(): array
    {
        return [
            // Work Identification
            'identifier' => 'Accession Number / Work ID',
            'legacyId' => 'Legacy ID',
            'culture' => 'Culture (language code)',
            'title' => 'Title',
            'alternateTitle' => 'Alternate / Former Title',
            'workType' => 'Work Type (painting, sculpture, etc.)',
            
            // Creator
            'eventActors' => 'Artist / Creator',
            'creatorRole' => 'Creator Role',
            'creatorNationality' => 'Creator Nationality',
            'creatorBirthDate' => 'Creator Birth Date',
            'creatorDeathDate' => 'Creator Death Date',
            'creatorBiography' => 'Creator Biography',
            'attribution' => 'Attribution / Qualifier',
            
            // Creation
            'eventDates' => 'Creation Date',
            'eventStartDates' => 'Date Earliest',
            'eventEndDates' => 'Date Latest',
            'placeAccessPoints' => 'Creation Place',
            'culturalContext' => 'Style / Period / Movement',
            
            // Physical
            'extentAndMedium' => 'Materials / Medium',
            'physicalCharacteristics' => 'Technique',
            'dimensions' => 'Dimensions',
            'dimensionNotes' => 'Dimension Notes (frame, base)',
            'inscriptions' => 'Inscriptions / Signatures',
            'inscriptionLocation' => 'Inscription Location',
            'editionNumber' => 'Edition / State',
            
            // Content / Subject
            'scopeAndContent' => 'Description',
            'subjectAccessPoints' => 'Subject Terms (AAT/Iconclass)',
            'depictedPerson' => 'Depicted Person',
            'depictedPlace' => 'Depicted Place',
            'depictedEvent' => 'Depicted Event',
            'iconography' => 'Iconography / Narrative',
            
            // Context
            'archivalHistory' => 'Provenance',
            'exhibitionHistory' => 'Exhibition History',
            'publicationNote' => 'Literature / Bibliography',
            'relatedUnitsOfDescription' => 'Related Works',
            
            // Current Status
            'repository' => 'Repository / Collection',
            'currentLocation' => 'Current Location',
            'creditLine' => 'Credit Line',
            'accessConditions' => 'Access Status',
            'reproductionConditions' => 'Copyright / Rights',
            'rightsHolder' => 'Rights Holder',
            
            // Condition
            'conditionStatus' => 'Condition',
            'conditionDate' => 'Condition Date',
            'conditionNotes' => 'Condition Notes',
            
            // Valuation
            'valuationAmount' => 'Insurance Value',
            'valuationDate' => 'Valuation Date',
            
            // Cataloguing
            'generalNote' => 'Notes',
            'cataloguerNote' => 'Cataloguer Note',
            'catalogueDate' => 'Catalogue Date',
            // AHG Extended Fields
            'ahgProvenanceHistory' => 'AHG: Provenance History',
            'ahgProvenanceEventCount' => 'AHG: Provenance Event Count',
            'ahgProvenanceFirstDate' => 'AHG: Provenance First Date',
            'ahgProvenanceLastDate' => 'AHG: Provenance Last Date',
            'ahgProvenanceEventDates' => 'AHG: Provenance Event Dates',
            'ahgProvenanceEventTypes' => 'AHG: Provenance Event Types',
            'ahgProvenanceEventDescriptions' => 'AHG: Provenance Event Descriptions',
            'ahgProvenanceEventAgents' => 'AHG: Provenance Event Agents',
            'ahgAccessLevel' => 'AHG: Access Level',
            'ahgCopyrightStatus' => 'AHG: Copyright Status',
            'ahgRelationships' => 'AHG: Relationships',
            'ahgRightsBasis' => 'AHG: Rights Basis',
            'ahgRightsStatement' => 'AHG: Rights Statement',
            'ahgSecurityClassification' => 'AHG: Security Classification',
            'ahgConditionOverallRating' => 'AHG: Condition Overall Rating',
            'ahgConditionSummary' => 'AHG: Condition Summary',
            'ahgConditionRecommendations' => 'AHG: Condition Recommendations',
            'ahgConditionPriority' => 'AHG: Condition Priority',
            'ahgConditionContext' => 'AHG: Condition Context',
            'ahgConditionAssessmentDate' => 'AHG: Condition Assessment Date',
            'ahgConditionNextCheckDate' => 'AHG: Condition Next Check Date',
            'ahgConditionEnvironmentalNotes' => 'AHG: Environmental Notes',
            'ahgConditionHandlingNotes' => 'AHG: Handling Notes',
            'ahgConditionDisplayNotes' => 'AHG: Display Notes',
            'ahgConditionStorageNotes' => 'AHG: Storage Notes',
            
            // Digital
            'digitalObjectPath' => 'Image Path',
            'digitalObjectURI' => 'Image URL',
            'imageViewType' => 'Image View Type',
            
            // Parent
            'parentId' => 'Parent ID',
            'qubitParentSlug' => 'Parent Slug',
        ];
    }

    protected function getDAMFields(): array
    {
        return [
            // Asset Identification
            'identifier' => 'Asset ID / Filename',
            'legacyId' => 'Legacy ID',
            'culture' => 'Culture (language code)',
            'title' => 'Title / Caption',
            'alternateTitle' => 'Alternative Title',
            'assetType' => 'Asset Type (image, video, audio, document)',
            
            // Creator / Source
            'eventActors' => 'Creator / Photographer / Author',
            'creatorRole' => 'Creator Role',
            'copyright' => 'Copyright Holder',
            'source' => 'Source / Agency',
            
            // Dates
            'eventDates' => 'Creation Date',
            'captureDate' => 'Capture / Recording Date',
            'uploadDate' => 'Upload Date',
            'modifiedDate' => 'Modified Date',
            
            // Technical Metadata
            'fileFormat' => 'File Format',
            'mimeType' => 'MIME Type',
            'fileSize' => 'File Size',
            'dimensions' => 'Dimensions (pixels)',
            'resolution' => 'Resolution (DPI/PPI)',
            'duration' => 'Duration (audio/video)',
            'colourSpace' => 'Colour Space',
            'bitDepth' => 'Bit Depth',
            
            // Camera / Equipment
            'camera' => 'Camera / Equipment',
            'lens' => 'Lens',
            'focalLength' => 'Focal Length',
            'aperture' => 'Aperture',
            'shutterSpeed' => 'Shutter Speed',
            'iso' => 'ISO',
            'gpsCoordinates' => 'GPS Coordinates',
            
            // Content Description
            'scopeAndContent' => 'Description',
            'subjectAccessPoints' => 'Keywords / Tags',
            'placeAccessPoints' => 'Location / Place',
            'nameAccessPoints' => 'People / Names',
            'genreAccessPoints' => 'Category',
            
            // Rights & Usage
            'accessConditions' => 'Access Level',
            'reproductionConditions' => 'Usage Rights',
            'licenseType' => 'License Type',
            'releaseStatus' => 'Model/Property Release',
            'embargoDate' => 'Embargo Date',
            'expiryDate' => 'Rights Expiry Date',
            
            // Relationships
            'relatedUnitsOfDescription' => 'Related Assets',
            'parentId' => 'Parent Collection / Folder',
            'linkedRecord' => 'Linked Catalogue Record',
            // AHG Extended Fields
            'ahgProvenanceHistory' => 'AHG: Provenance History',
            'ahgProvenanceEventCount' => 'AHG: Provenance Event Count',
            'ahgProvenanceFirstDate' => 'AHG: Provenance First Date',
            'ahgProvenanceLastDate' => 'AHG: Provenance Last Date',
            'ahgProvenanceEventDates' => 'AHG: Provenance Event Dates',
            'ahgProvenanceEventTypes' => 'AHG: Provenance Event Types',
            'ahgProvenanceEventDescriptions' => 'AHG: Provenance Event Descriptions',
            'ahgProvenanceEventAgents' => 'AHG: Provenance Event Agents',
            'ahgAccessLevel' => 'AHG: Access Level',
            'ahgCopyrightStatus' => 'AHG: Copyright Status',
            'ahgRelationships' => 'AHG: Relationships',
            'ahgRightsBasis' => 'AHG: Rights Basis',
            'ahgRightsStatement' => 'AHG: Rights Statement',
            'ahgSecurityClassification' => 'AHG: Security Classification',
            'ahgConditionOverallRating' => 'AHG: Condition Overall Rating',
            'ahgConditionSummary' => 'AHG: Condition Summary',
            'ahgConditionRecommendations' => 'AHG: Condition Recommendations',
            'ahgConditionPriority' => 'AHG: Condition Priority',
            'ahgConditionContext' => 'AHG: Condition Context',
            'ahgConditionAssessmentDate' => 'AHG: Condition Assessment Date',
            'ahgConditionNextCheckDate' => 'AHG: Condition Next Check Date',
            'ahgConditionEnvironmentalNotes' => 'AHG: Environmental Notes',
            'ahgConditionHandlingNotes' => 'AHG: Handling Notes',
            'ahgConditionDisplayNotes' => 'AHG: Display Notes',
            'ahgConditionStorageNotes' => 'AHG: Storage Notes',
            
            // Status
            'status' => 'Status (active, archived, deleted)',
            'version' => 'Version',
            'workflow' => 'Workflow Stage',
            
            // Storage
            'repository' => 'Repository / Storage Location',
            'storagePath' => 'Storage Path',
            'thumbnailPath' => 'Thumbnail Path',
            'previewPath' => 'Preview Path',
            
            // Notes
            'generalNote' => 'Notes',
            'language' => 'Language',
            
            // Digital Object Paths
            'digitalObjectPath' => 'Master File Path',
            'digitalObjectURI' => 'Access URL',
        ];
    }

    protected function suggestMapping(string $sourceField, string $type): string
    {
        $originalField = strtolower(trim($sourceField));
        
        // Handle prefixed fields (dc:title, dcterms:extent, Identifier_Reference, etc.)
        $field = $originalField;
        
        // Dublin Core mappings - map to target standard
        $dcMappings = [
            'dc:title' => 'title',
            'dc:creator' => 'creators',
            'dc:subject' => 'subjectAccessPoints',
            'dc:description' => 'scopeAndContent',
            'dc:publisher' => 'repository',
            'dc:contributor' => 'nameAccessPoints',
            'dc:date' => 'eventDates',
            'dc:type' => 'levelOfDescription',
            'dc:format' => 'extentAndMedium',
            'dc:identifier' => 'identifier',
            'dc:source' => 'locationOfOriginals',
            'dc:language' => 'language',
            'dc:relation' => 'relatedUnitsOfDescription',
            'dc:coverage' => 'placeAccessPoints',
            'dc:rights' => 'accessConditions',
            'dcterms:extent' => 'extentAndMedium',
            'dcterms:provenance' => 'archivalHistory',
            'dcterms:accessrights' => 'accessConditions',
            'dcterms:created' => 'eventDates',
            'dcterms:modified' => 'revisionHistory',
            'dcterms:spatial' => 'placeAccessPoints',
            'dcterms:temporal' => 'eventDates',
            // EAD (Encoded Archival Description) prefixed
            'ead:unittitle' => 'title',
            'ead:unitid' => 'identifier',
            'ead:unitdate' => 'eventDates',
            'ead:origination' => 'creators',
            'ead:physdesc' => 'extentAndMedium',
            'ead:scopecontent' => 'scopeAndContent',
            'ead:bioghist' => 'archivalHistory',
            'ead:arrangement' => 'arrangement',
            'ead:accessrestrict' => 'accessConditions',
            'ead:userestrict' => 'reproductionConditions',
            'ead:langmaterial' => 'language',
            'ead:subject' => 'subjectAccessPoints',
            'ead:geogname' => 'placeAccessPoints',
            'ead:persname' => 'nameAccessPoints',
            // MODS prefixed
            'mods:title' => 'title',
            'mods:name' => 'creators',
            'mods:dateissued' => 'eventDates',
            'mods:datecreated' => 'eventDates',
            'mods:publisher' => 'repository',
            'mods:language' => 'language',
            'mods:extent' => 'extentAndMedium',
            'mods:abstract' => 'scopeAndContent',
            'mods:subject' => 'subjectAccessPoints',
            'mods:identifier' => 'identifier',
        ];
        
        // Check DC mappings first (before stripping special chars)
        if (isset($dcMappings[$originalField])) {
            return $dcMappings[$originalField];
        }
        
        // Handle Identifier_Type patterns (from OPEX)
        if (preg_match('/^identifier[_-]?(.*)/i', $originalField, $matches)) {
            $idType = strtolower($matches[1] ?? '');
            if (in_array($idType, ['reference', 'ref', 'code', 'number', ''])) {
                return 'identifier';
            }
            if (in_array($idType, ['atom_id', 'atomid', 'legacy', 'legacyid', 'source'])) {
                return 'legacyId';
            }
            return 'identifier';
        }
        
        // Strip special characters for standard matching
        $field = preg_replace('/[^a-z0-9]/', '', $originalField);

        // Common mappings across all types
        $commonMappings = [
            'title' => 'title',
            'name' => 'title',
            'description' => 'scopeAndContent',
            'desc' => 'scopeAndContent',
            'briefdescription' => 'scopeAndContent',
            'notes' => 'generalNote',
            'note' => 'generalNote',
            'dctitle' => 'title',
            'dccreator' => 'creators',
            'dcsubject' => 'subjectAccessPoints',
            'dcdescription' => 'scopeAndContent',
            'dcpublisher' => 'repository',
            'dccontributor' => 'nameAccessPoints',
            'dcdate' => 'eventDates',
            'dctype' => 'levelOfDescription',
            'dcformat' => 'extentAndMedium',
            'dcidentifier' => 'identifier',
            'dcsource' => 'locationOfOriginals',
            'dclanguage' => 'language',
            'dcrelation' => 'relatedUnitsOfDescription',
            'dccoverage' => 'placeAccessPoints',
            'dcrights' => 'accessConditions',
            'dctermsextent' => 'extentAndMedium',
            'dctermsprovenance' => 'archivalHistory',
            'dctermsaccessrights' => 'accessConditions',
            'dctermscreated' => 'eventDates',
            'dctermsmodified' => 'revisionHistory',
            // EAD (Encoded Archival Description) mappings
            'ead:unittitle' => 'title',
            'ead:unitid' => 'identifier',
            'ead:unitdate' => 'eventDates',
            'ead:origination' => 'creators',
            'ead:physdesc' => 'extentAndMedium',
            'ead:extent' => 'extentAndMedium',
            'ead:scopecontent' => 'scopeAndContent',
            'ead:bioghist' => 'archivalHistory',
            'ead:custodhist' => 'archivalHistory',
            'ead:arrangement' => 'arrangement',
            'ead:accessrestrict' => 'accessConditions',
            'ead:userestrict' => 'reproductionConditions',
            'ead:relatedmaterial' => 'relatedUnitsOfDescription',
            'ead:originalsloc' => 'locationOfOriginals',
            'ead:altformavail' => 'locationOfCopies',
            'ead:langmaterial' => 'language',
            'ead:subject' => 'subjectAccessPoints',
            'ead:geogname' => 'placeAccessPoints',
            'ead:persname' => 'nameAccessPoints',
            'ead:corpname' => 'nameAccessPoints',
            // MODS mappings
            'mods:title' => 'title',
            'mods:name' => 'creators',
            'mods:dateissued' => 'eventDates',
            'mods:datecreated' => 'eventDates',
            'mods:publisher' => 'repository',
            'mods:language' => 'language',
            'mods:extent' => 'extentAndMedium',
            'mods:abstract' => 'scopeAndContent',
            'mods:subject' => 'subjectAccessPoints',
            'mods:geographic' => 'placeAccessPoints',
            'mods:identifier' => 'identifier',
            'mods:accesscondition' => 'accessConditions',
            'dctermsspatial' => 'placeAccessPoints',
            'dctermstemporal' => 'eventDates',
            // AHG Extended Field mappings (map to standard AtoM fields for basic export)
            'ahgsecurityclassification' => 'accessConditions',
            'ahgaccesslevel' => 'accessConditions',
            'ahgrightsstatement' => 'reproductionConditions',
            'ahgrightsbasis' => 'reproductionConditions',
            'ahgcopyrightstatus' => 'reproductionConditions',
            'ahgprovenancehistory' => 'archivalHistory',
            'ahgprovenancefirstdate' => 'eventDates',
            'ahgprovenancelastdate' => 'eventDates',
            'ahgprovenanceeventcount' => 'generalNote',
            'ahgrelationships' => 'relatedUnitsOfDescription',
            'securitydescriptor' => 'accessConditions',
            'filename' => 'digitalObjectPath',
            'digitalobjectchecksum' => 'generalNote',
            'digitalobjectmimetype' => 'extentAndMedium',
            'digitalobjectsize' => 'extentAndMedium',
            'allfilenames' => 'digitalObjectPath',
        ];

        // Type-specific mappings
        $typeMappings = [
            'museum' => [
                'objectnumber' => 'identifier',
                'objectno' => 'identifier',
                'accession' => 'legacyId',
                'accessionnumber' => 'legacyId',
                'accessionno' => 'legacyId',
                'objectname' => 'title',
                'objecttype' => 'objectType',
                'maker' => 'eventActors',
                'artist' => 'eventActors',
                'creator' => 'eventActors',
                'productiondate' => 'eventDates',
                'datecreated' => 'eventDates',
                'date' => 'eventDates',
                'productionplace' => 'placeAccessPoints',
                'material' => 'extentAndMedium',
                'materials' => 'extentAndMedium',
                'medium' => 'extentAndMedium',
                'technique' => 'physicalCharacteristics',
                'dimensions' => 'dimensions',
                'dimension' => 'dimensions',
                'measurements' => 'dimensions',
                'condition' => 'conditionStatus',
                'conditionstatus' => 'conditionStatus',
                'location' => 'currentLocation',
                'currentlocation' => 'currentLocation',
                'subject' => 'subjectAccessPoints',
                'subjects' => 'subjectAccessPoints',
                'keywords' => 'subjectAccessPoints',
                'provenance' => 'archivalHistory',
                'acquisition' => 'acquisition',
                'acquisitionmethod' => 'acquisition',
                'imagefilename' => 'digitalObjectPath',
                'imagepath' => 'digitalObjectPath',
                'filename' => 'digitalObjectPath',
                'image' => 'digitalObjectPath',
                'photo' => 'digitalObjectPath',
            ],
            'library' => [
                'callnumber' => 'identifier',
                'controlnumber' => 'identifier',
                'isbn' => 'isbn',
                'issn' => 'issn',
                'author' => 'eventActors',
                'authors' => 'eventActors',
                'creator' => 'eventActors',
                'publisher' => 'publisher',
                'publicationdate' => 'eventDates',
                'pubdate' => 'eventDates',
                'publicationplace' => 'publicationPlace',
                'edition' => 'edition',
                'pages' => 'extentAndMedium',
                'extent' => 'extentAndMedium',
                'language' => 'language',
                'subject' => 'subjectAccessPoints',
                'subjects' => 'subjectAccessPoints',
                'genre' => 'genreAccessPoints',
                'classification' => 'classification',
                'abstract' => 'scopeAndContent',
                'summary' => 'scopeAndContent',
            ],
            'gallery' => [
                'accessionnumber' => 'identifier',
                'workid' => 'identifier',
                'artist' => 'eventActors',
                'creator' => 'eventActors',
                'creationdate' => 'eventDates',
                'datecreated' => 'eventDates',
                'medium' => 'extentAndMedium',
                'materials' => 'extentAndMedium',
                'dimensions' => 'dimensions',
                'measurements' => 'dimensions',
                'provenance' => 'archivalHistory',
                'exhibitions' => 'exhibitionHistory',
                'literature' => 'publicationNote',
                'creditline' => 'creditLine',
                'copyright' => 'reproductionConditions',
            ],
            'dam' => [
                'assetid' => 'identifier',
                'filename' => 'digitalObjectPath',
                'filepath' => 'digitalObjectPath',
                'caption' => 'title',
                'photographer' => 'creators',
                'creator' => 'creators',
                'datecaptured' => 'eventDates',
                'datetaken' => 'eventDates',
                'keywords' => 'subjectAccessPoints',
                'tags' => 'subjectAccessPoints',
                'copyright' => 'reproductionConditions',
                'rights' => 'accessConditions',
                'format' => 'extentAndMedium',
                'mimetype' => 'extentAndMedium',
                'size' => 'extentAndMedium',
                'width' => 'extentAndMedium',
                'height' => 'extentAndMedium',
                'duration' => 'extentAndMedium',
            ],
            'archives' => [
                'referencecode' => 'identifier',
                'unitid' => 'identifier',
                'unittitle' => 'title',
                'unitdate' => 'eventDates',
                'origination' => 'creators',
                'creator' => 'creators',
                'extent' => 'extentAndMedium',
                'scopecontent' => 'scopeAndContent',
                'arrangement' => 'arrangement',
                'accessrestrictions' => 'accessConditions',
                'userestrictions' => 'reproductionConditions',
                'phystech' => 'physicalCharacteristics',
                'bioghist' => 'archivalHistory',
                'custodhist' => 'archivalHistory',
                'acqinfo' => 'acquisition',
                'relatedmaterial' => 'relatedUnitsOfDescription',
                'separatedmaterial' => 'relatedUnitsOfDescription',
                'otherfindaid' => 'findingAids',
                'originalsloc' => 'locationOfOriginals',
                'altformavail' => 'locationOfCopies',
            ],
        ];

        // Check type-specific mappings first
        if (isset($typeMappings[$type][$field])) {
            return $typeMappings[$type][$field];
        }

        // Check common mappings
        if (isset($commonMappings[$field])) {
            return $commonMappings[$field];
        }

        return '';
    }
}
