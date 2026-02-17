<?php

namespace AhgIngestPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

class IngestService
{
    // ─── AtoM CSV Field Definitions ─────────────────────────────────────

    /**
     * Standard columns accepted by AtoM CSV import, per standard.
     */
    public static function getTargetFields(string $standard = 'isadg'): array
    {
        $common = [
            'legacyId', 'parentId', 'qubitParentSlug', 'identifier',
            'title', 'levelOfDescription', 'extentAndMedium',
            'repository', 'archivalHistory', 'acquisition',
            'scopeAndContent', 'appraisal', 'accruals',
            'arrangement', 'accessConditions', 'reproductionConditions',
            'physicalCharacteristics', 'findingAids', 'relatedUnitsOfDescription',
            'locationOfOriginals', 'locationOfCopies', 'rules',
            'descriptionIdentifier', 'descriptionStatus', 'publicationStatus',
            'levelOfDetail', 'revisionHistory', 'sources',
            'culture', 'alternateTitle',
            'digitalObjectPath', 'digitalObjectURI', 'digitalObjectChecksum',
            'subjectAccessPoints', 'placeAccessPoints', 'nameAccessPoints',
            'genreAccessPoints', 'creators', 'creatorDates',
            'creatorDatesStart', 'creatorDatesEnd', 'creatorDateNotes',
            'creationDates', 'creationDatesStart', 'creationDatesEnd',
            'eventActors', 'eventTypes', 'eventDates',
            'eventStartDates', 'eventEndDates', 'eventPlaces',
            'physicalObjectName', 'physicalObjectLocation', 'physicalObjectType',
            'accessionNumber', 'copyrightStatus', 'copyrightExpires', 'copyrightHolder',
        ];

        $extras = [];

        if ($standard === 'rad') {
            $extras = [
                'radOtherTitleInformation', 'radTitleStatementOfResponsibility',
                'radStatementOfProjection', 'radStatementOfCoordinates',
                'radEdition', 'radStatementOfScaleCartographic',
            ];
        } elseif ($standard === 'dacs') {
            $extras = [
                'unitDates', 'unitDateActuated',
            ];
        } elseif ($standard === 'dc') {
            $extras = [
                'type', 'format', 'language', 'relation', 'coverage',
                'contributor', 'publisher', 'rights', 'date',
            ];
        } elseif ($standard === 'spectrum') {
            $extras = [
                'objectNumber', 'objectName', 'objectType',
                'materialComponent', 'technique', 'dimension',
                'inscription', 'condition', 'completeness',
            ];
        } elseif ($standard === 'cco') {
            $extras = [
                'workType', 'measurements', 'materialsTechniques',
                'stylePeriod', 'culturalContext',
            ];
        } elseif ($standard === 'mods') {
            $extras = [
                'genre', 'typeOfResource', 'abstract', 'tableOfContents',
                'originInfoPublisher', 'originInfoPlace', 'originInfoDateIssued',
                'issuance', 'frequency', 'classification', 'note',
            ];
        }

        return array_merge($common, $extras);
    }

    /**
     * Required fields per standard (ISAD(G) 2.1 numbering, Spectrum, CCO, DC, MODS).
     */
    public static function getRequiredFields(string $standard = 'isadg'): array
    {
        switch ($standard) {
            case 'isadg':
                // ISAD(G) 2nd ed mandatory elements: 3.1.1, 3.1.2, 3.1.3, 3.1.4, 3.1.5
                return ['identifier', 'title', 'creationDatesStart', 'levelOfDescription', 'extentAndMedium'];

            case 'rad':
                // RAD mandatory: title proper, dates, level, extent
                return ['title', 'creationDatesStart', 'levelOfDescription', 'extentAndMedium'];

            case 'dacs':
                // DACS required: reference code, title, date, extent, creator
                return ['identifier', 'title', 'creationDatesStart', 'levelOfDescription', 'extentAndMedium'];

            case 'dc':
                // Dublin Core: only title is truly required
                return ['title'];

            case 'mods':
                // MODS: title and typeOfResource
                return ['title', 'typeOfResource'];

            case 'spectrum':
                // Spectrum 5.1: Object number + object name mandatory for Object Entry
                return ['objectNumber', 'objectName', 'title', 'levelOfDescription'];

            case 'cco':
                // CCO: work type + title + creator mandatory
                return ['title', 'workType'];

            default:
                return ['title', 'levelOfDescription'];
        }
    }

    /**
     * Controlled vocabularies for validated fields per standard.
     */
    public static function getControlledVocabularies(string $standard = 'isadg'): array
    {
        $vocabularies = [];

        // Level of description — common across archival standards
        $vocabularies['levelOfDescription'] = [
            'label' => 'ISAD(G) Levels',
            'values' => ['Fonds', 'Subfonds', 'Collection', 'Series', 'Subseries', 'File', 'Item', 'Part'],
        ];

        $vocabularies['publicationStatus'] = [
            'label' => 'Publication Status',
            'values' => ['Draft', 'Published'],
        ];

        $vocabularies['copyrightStatus'] = [
            'label' => 'Copyright Status',
            'values' => ['Under copyright', 'Public domain', 'Unknown'],
        ];

        // ── Spectrum / Museum ──
        if ($standard === 'spectrum') {
            // AAT Object Names (common museum object types from Getty AAT)
            $vocabularies['objectName'] = [
                'label' => 'AAT Object Names',
                'values' => [
                    // Furnishings
                    'basket', 'bowl', 'box', 'cabinet', 'carpet', 'chair', 'chest', 'clock',
                    'cup', 'desk', 'dish', 'figurine', 'jar', 'jug', 'lamp', 'mirror',
                    'plate', 'pot', 'rug', 'table', 'tapestry', 'urn', 'vase',
                    // Tools & Equipment
                    'ax', 'bell', 'blade', 'coin', 'die (tool)', 'key', 'knife', 'medal',
                    'needle', 'ring', 'seal (tool)', 'spindle', 'stamp', 'sword', 'tool', 'weight',
                    // Costume & Adornment
                    'bracelet', 'brooch', 'buckle', 'button', 'crown', 'earring', 'hat',
                    'helmet', 'necklace', 'pendant', 'pin',
                    // Documents & Visual Works
                    'book', 'codex', 'document', 'drawing', 'engraving', 'etching', 'icon',
                    'illumination', 'lithograph', 'manuscript', 'map', 'mosaic', 'mural',
                    'painting', 'photograph', 'poster', 'print', 'scroll', 'watercolor',
                    // Sculpture
                    'bust', 'carving', 'mask', 'model', 'plaque', 'relief', 'sculpture', 'statue', 'stele',
                    // Architecture
                    'architectural element', 'brick', 'capital', 'column', 'frieze', 'tile',
                    // Natural Specimens
                    'fossil', 'mineral', 'rock', 'shell', 'specimen',
                ],
            ];

            $vocabularies['objectType'] = [
                'label' => 'AAT Object Types',
                'values' => [
                    'archaeological object', 'art object', 'ceremonial object', 'costume',
                    'decorative art', 'document', 'ethnographic object', 'furniture',
                    'industrial object', 'military object', 'musical instrument',
                    'natural history specimen', 'numismatic object', 'personal effect',
                    'photographic material', 'scientific instrument', 'textile', 'tool',
                    'toy', 'transport', 'weapon',
                ],
            ];

            $vocabularies['condition'] = [
                'label' => 'Condition Assessment',
                'values' => ['Good', 'Fair', 'Poor', 'Damaged', 'Fragile', 'Deteriorating', 'Unexamined'],
            ];

            $vocabularies['completeness'] = [
                'label' => 'Completeness',
                'values' => ['Complete', 'Incomplete', 'Fragmentary', 'Fragment'],
            ];
        }

        // ── CCO / Gallery ──
        if ($standard === 'cco') {
            // AAT Work Types (visual resources)
            $vocabularies['workType'] = [
                'label' => 'AAT Work Types',
                'values' => [
                    // Paintings
                    'painting', 'oil painting', 'watercolor painting', 'acrylic painting',
                    'tempera painting', 'fresco', 'mural', 'miniature painting',
                    // Prints
                    'print', 'engraving', 'etching', 'lithograph', 'woodcut', 'screenprint',
                    'aquatint', 'mezzotint', 'linocut', 'monotype',
                    // Drawings
                    'drawing', 'charcoal drawing', 'ink drawing', 'pastel', 'sketch',
                    'pencil drawing', 'pen and ink drawing',
                    // Sculpture
                    'sculpture', 'relief', 'bust', 'statue', 'figurine', 'installation',
                    'assemblage', 'mobile', 'carving', 'casting',
                    // Photography
                    'photograph', 'daguerreotype', 'albumen print', 'gelatin silver print',
                    'chromogenic print', 'digital photograph', 'photogram', 'tintype',
                    // Mixed/Other
                    'collage', 'mosaic', 'textile', 'tapestry', 'ceramic', 'pottery',
                    'glass', 'jewelry', 'metalwork', 'mixed media', 'video art',
                    'digital art', 'performance art', 'conceptual art',
                ],
            ];

            $vocabularies['stylePeriod'] = [
                'label' => 'AAT Style/Period',
                'values' => [
                    'Abstract', 'Abstract Expressionist', 'Art Deco', 'Art Nouveau',
                    'Baroque', 'Byzantine', 'Classical', 'Contemporary',
                    'Cubist', 'Dada', 'Early Modern', 'Expressionist',
                    'Gothic', 'Impressionist', 'Medieval', 'Minimalist',
                    'Modern', 'Neoclassical', 'Pop Art', 'Post-Impressionist',
                    'Post-Modern', 'Pre-Raphaelite', 'Realist', 'Renaissance',
                    'Rococo', 'Romantic', 'Surrealist',
                ],
            ];
        }

        // ── Dublin Core / DAM ──
        if ($standard === 'dc') {
            $vocabularies['type'] = [
                'label' => 'DCMI Type Vocabulary',
                'values' => [
                    'Collection', 'Dataset', 'Event', 'Image', 'InteractiveResource',
                    'MovingImage', 'PhysicalObject', 'Service', 'Software',
                    'Sound', 'StillImage', 'Text',
                ],
            ];
        }

        // ── MODS / Library ──
        if ($standard === 'mods') {
            $vocabularies['typeOfResource'] = [
                'label' => 'MODS Resource Types',
                'values' => [
                    'text', 'cartographic', 'notated music', 'sound recording',
                    'sound recording-musical', 'sound recording-nonmusical',
                    'still image', 'moving image', 'three dimensional object',
                    'software, multimedia', 'mixed material',
                ],
            ];

            $vocabularies['issuance'] = [
                'label' => 'MODS Issuance',
                'values' => ['monographic', 'continuing', 'serial', 'integrating resource'],
            ];
        }

        return $vocabularies;
    }

    /**
     * Grouped field definitions for directory-import metadata entry.
     * Fields include: label, type, help, required, vocabulary (key in getControlledVocabularies).
     */
    public static function getMetadataFieldGroups(string $standard = 'isadg'): array
    {
        $req = self::getRequiredFields($standard);
        $r = function (string $field) use ($req) { return in_array($field, $req); };

        // ── ISAD(G) — default archival standard ──
        if ($standard === 'isadg' || $standard === 'rad' || $standard === 'dacs') {
            $groups = [
                'identity' => [
                    'label' => 'Identity Area (ISAD 3.1)',
                    'fields' => [
                        'identifier'          => ['label' => 'Reference Code / Identifier (3.1.1)', 'type' => 'text', 'required' => $r('identifier'),
                            'help' => 'Add counter suffix: enable below'],
                        'title'               => ['label' => 'Title (3.1.2)', 'type' => 'text', 'required' => true,
                            'help' => 'Prepended to auto-generated filename titles'],
                        'levelOfDescription'  => ['label' => 'Level of Description (3.1.4)', 'type' => 'select', 'required' => $r('levelOfDescription'),
                            'vocabulary' => 'levelOfDescription'],
                        'alternateTitle'      => ['label' => 'Alternate Title', 'type' => 'text'],
                        'extentAndMedium'     => ['label' => 'Extent and Medium (3.1.5)', 'type' => 'text', 'required' => $r('extentAndMedium')],
                        'creationDatesStart'  => ['label' => 'Date Start (3.1.3)', 'type' => 'text', 'required' => $r('creationDatesStart'),
                            'help' => 'YYYY-MM-DD or YYYY'],
                        'creationDatesEnd'    => ['label' => 'Date End', 'type' => 'text', 'help' => 'YYYY-MM-DD or YYYY'],
                    ],
                ],
                'context' => [
                    'label' => 'Context Area (ISAD 3.2)',
                    'fields' => [
                        'archivalHistory' => ['label' => 'Archival History (3.2.3)', 'type' => 'textarea'],
                        'acquisition'     => ['label' => 'Immediate Source of Acquisition (3.2.4)', 'type' => 'textarea'],
                        'creators'        => ['label' => 'Creator(s) (3.2.1)', 'type' => 'text', 'help' => 'Pipe-separated: creator1|creator2'],
                    ],
                ],
                'content' => [
                    'label' => 'Content & Structure Area (ISAD 3.3)',
                    'fields' => [
                        'scopeAndContent'         => ['label' => 'Scope and Content (3.3.1)', 'type' => 'textarea'],
                        'appraisal'               => ['label' => 'Appraisal (3.3.2)', 'type' => 'textarea'],
                        'accruals'                => ['label' => 'Accruals (3.3.3)', 'type' => 'text'],
                        'arrangement'             => ['label' => 'System of Arrangement (3.3.4)', 'type' => 'textarea'],
                        'physicalCharacteristics' => ['label' => 'Physical Characteristics', 'type' => 'text'],
                    ],
                ],
                'access' => [
                    'label' => 'Conditions of Access & Use (ISAD 3.4)',
                    'fields' => [
                        'accessConditions'       => ['label' => 'Conditions of Access (3.4.1)', 'type' => 'text'],
                        'reproductionConditions' => ['label' => 'Conditions of Reproduction (3.4.2)', 'type' => 'text'],
                        'findingAids'            => ['label' => 'Finding Aids (3.4.5)', 'type' => 'text'],
                    ],
                ],
                'allied' => [
                    'label' => 'Allied Materials (ISAD 3.5)',
                    'fields' => [
                        'locationOfOriginals'       => ['label' => 'Location of Originals (3.5.1)', 'type' => 'text'],
                        'locationOfCopies'          => ['label' => 'Location of Copies (3.5.2)', 'type' => 'text'],
                        'relatedUnitsOfDescription' => ['label' => 'Related Units (3.5.3)', 'type' => 'textarea'],
                    ],
                ],
                'accesspoints' => [
                    'label' => 'Access Points',
                    'fields' => [
                        'subjectAccessPoints' => ['label' => 'Subject Access Points', 'type' => 'text', 'help' => 'Pipe-separated: term1|term2'],
                        'placeAccessPoints'   => ['label' => 'Place Access Points', 'type' => 'text', 'help' => 'Pipe-separated'],
                        'nameAccessPoints'    => ['label' => 'Name Access Points', 'type' => 'text', 'help' => 'Pipe-separated'],
                        'genreAccessPoints'   => ['label' => 'Genre Access Points', 'type' => 'text', 'help' => 'Pipe-separated'],
                    ],
                ],
                'rights' => [
                    'label' => 'Rights',
                    'fields' => [
                        'copyrightStatus'  => ['label' => 'Copyright Status', 'type' => 'select', 'vocabulary' => 'copyrightStatus'],
                        'copyrightHolder'  => ['label' => 'Copyright Holder', 'type' => 'text'],
                        'copyrightExpires' => ['label' => 'Copyright Expiry Date', 'type' => 'text'],
                    ],
                ],
                'control' => [
                    'label' => 'Description Control (ISAD 3.7)',
                    'fields' => [
                        'publicationStatus' => ['label' => 'Publication Status', 'type' => 'select', 'vocabulary' => 'publicationStatus'],
                        'culture'           => ['label' => 'Language (culture code)', 'type' => 'text', 'help' => 'e.g. en, fr, af'],
                    ],
                ],
            ];

            if ($standard === 'rad') {
                $groups['identity']['label'] = 'Identity Area (RAD 1)';
                $groups['identity']['fields']['radOtherTitleInformation'] = ['label' => 'Other Title Information', 'type' => 'text'];
                $groups['identity']['fields']['radTitleStatementOfResponsibility'] = ['label' => 'Statement of Responsibility', 'type' => 'text'];
                $groups['identity']['fields']['radEdition'] = ['label' => 'Edition Statement', 'type' => 'text'];
                $groups['content']['fields']['radStatementOfProjection'] = ['label' => 'Statement of Projection (Cartographic)', 'type' => 'text'];
                $groups['content']['fields']['radStatementOfCoordinates'] = ['label' => 'Statement of Coordinates (Cartographic)', 'type' => 'text'];
                $groups['content']['fields']['radStatementOfScaleCartographic'] = ['label' => 'Statement of Scale (Cartographic)', 'type' => 'text'];
            }

            if ($standard === 'dacs') {
                $groups['identity']['label'] = 'Identity Elements (DACS 2)';
                $groups['identity']['fields']['unitDates'] = ['label' => 'Unit Dates', 'type' => 'text'];
                $groups['identity']['fields']['unitDateActuated'] = ['label' => 'Unit Date Actuated', 'type' => 'text'];
            }

            return $groups;
        }

        // ── Dublin Core ──
        if ($standard === 'dc') {
            return [
                'core' => [
                    'label' => 'Core Elements',
                    'fields' => [
                        'title'              => ['label' => 'Title', 'type' => 'text', 'required' => true,
                            'help' => 'Prepended to auto-generated filename titles'],
                        'type'               => ['label' => 'Type (DCMI)', 'type' => 'select', 'vocabulary' => 'type'],
                        'format'             => ['label' => 'Format', 'type' => 'text', 'help' => 'MIME type e.g. image/jpeg'],
                        'language'           => ['label' => 'Language', 'type' => 'text', 'help' => 'ISO 639 code e.g. en'],
                        'date'               => ['label' => 'Date', 'type' => 'text'],
                        'coverage'           => ['label' => 'Coverage', 'type' => 'text'],
                        'levelOfDescription' => ['label' => 'Level of Description', 'type' => 'select', 'vocabulary' => 'levelOfDescription'],
                    ],
                ],
                'description' => [
                    'label' => 'Description',
                    'fields' => [
                        'scopeAndContent' => ['label' => 'Description', 'type' => 'textarea'],
                        'extentAndMedium' => ['label' => 'Extent', 'type' => 'text'],
                    ],
                ],
                'agents' => [
                    'label' => 'Agents',
                    'fields' => [
                        'creators'    => ['label' => 'Creator', 'type' => 'text', 'help' => 'Pipe-separated'],
                        'contributor' => ['label' => 'Contributor', 'type' => 'text'],
                        'publisher'   => ['label' => 'Publisher', 'type' => 'text'],
                    ],
                ],
                'rights_access' => [
                    'label' => 'Rights & Access',
                    'fields' => [
                        'rights'           => ['label' => 'Rights', 'type' => 'text'],
                        'relation'         => ['label' => 'Relation', 'type' => 'text'],
                        'accessConditions' => ['label' => 'Access Conditions', 'type' => 'text'],
                    ],
                ],
                'accesspoints' => [
                    'label' => 'Access Points',
                    'fields' => [
                        'subjectAccessPoints' => ['label' => 'Subject', 'type' => 'text', 'help' => 'Pipe-separated'],
                        'placeAccessPoints'   => ['label' => 'Spatial (Place)', 'type' => 'text', 'help' => 'Pipe-separated'],
                    ],
                ],
                'control' => [
                    'label' => 'Control',
                    'fields' => [
                        'publicationStatus' => ['label' => 'Publication Status', 'type' => 'select', 'vocabulary' => 'publicationStatus'],
                        'culture'           => ['label' => 'Culture Code', 'type' => 'text', 'help' => 'e.g. en, fr, af'],
                    ],
                ],
            ];
        }

        // ── Spectrum 5.1 (Museum) ──
        if ($standard === 'spectrum') {
            return [
                'identification' => [
                    'label' => 'Object Identification (Spectrum 4.1)',
                    'fields' => [
                        'objectNumber'       => ['label' => 'Object Number', 'type' => 'text', 'required' => true,
                            'help' => 'Add counter suffix: enable below'],
                        'objectName'         => ['label' => 'Object Name (AAT)', 'type' => 'select', 'required' => true,
                            'vocabulary' => 'objectName'],
                        'objectType'         => ['label' => 'Object Type (AAT)', 'type' => 'select',
                            'vocabulary' => 'objectType'],
                        'title'              => ['label' => 'Title', 'type' => 'text', 'required' => true,
                            'help' => 'Prepended to auto-generated filename titles'],
                        'levelOfDescription' => ['label' => 'Level of Description', 'type' => 'select', 'required' => true,
                            'vocabulary' => 'levelOfDescription'],
                    ],
                ],
                'description' => [
                    'label' => 'Object Description (Spectrum 8)',
                    'fields' => [
                        'scopeAndContent'    => ['label' => 'Description / Content', 'type' => 'textarea'],
                        'materialComponent'  => ['label' => 'Material / Component', 'type' => 'text'],
                        'technique'          => ['label' => 'Technique', 'type' => 'text'],
                        'dimension'          => ['label' => 'Dimensions', 'type' => 'text'],
                        'inscription'        => ['label' => 'Inscription', 'type' => 'text'],
                        'extentAndMedium'    => ['label' => 'Extent and Medium', 'type' => 'text'],
                        'physicalCharacteristics' => ['label' => 'Physical Characteristics', 'type' => 'text'],
                    ],
                ],
                'condition' => [
                    'label' => 'Condition (Spectrum 4.2)',
                    'fields' => [
                        'condition'    => ['label' => 'Condition', 'type' => 'select', 'vocabulary' => 'condition'],
                        'completeness' => ['label' => 'Completeness', 'type' => 'select', 'vocabulary' => 'completeness'],
                    ],
                ],
                'history' => [
                    'label' => 'History & Context',
                    'fields' => [
                        'archivalHistory'    => ['label' => 'Ownership History', 'type' => 'textarea'],
                        'acquisition'        => ['label' => 'Acquisition', 'type' => 'textarea'],
                        'creators'           => ['label' => 'Maker / Creator', 'type' => 'text', 'help' => 'Pipe-separated'],
                        'creationDatesStart' => ['label' => 'Date Made (Start)', 'type' => 'text'],
                        'creationDatesEnd'   => ['label' => 'Date Made (End)', 'type' => 'text'],
                    ],
                ],
                'accesspoints' => [
                    'label' => 'Access Points',
                    'fields' => [
                        'subjectAccessPoints' => ['label' => 'Subject', 'type' => 'text', 'help' => 'Pipe-separated'],
                        'placeAccessPoints'   => ['label' => 'Place', 'type' => 'text', 'help' => 'Pipe-separated'],
                        'genreAccessPoints'   => ['label' => 'Genre', 'type' => 'text', 'help' => 'Pipe-separated'],
                    ],
                ],
                'control' => [
                    'label' => 'Control',
                    'fields' => [
                        'publicationStatus' => ['label' => 'Publication Status', 'type' => 'select', 'vocabulary' => 'publicationStatus'],
                        'accessConditions'  => ['label' => 'Access Conditions', 'type' => 'text'],
                        'culture'           => ['label' => 'Culture Code', 'type' => 'text', 'help' => 'e.g. en, fr, af'],
                    ],
                ],
            ];
        }

        // ── CCO (Gallery / Visual Resources) ──
        if ($standard === 'cco') {
            return [
                'identification' => [
                    'label' => 'Work Identification (CCO 1)',
                    'fields' => [
                        'title'              => ['label' => 'Title', 'type' => 'text', 'required' => true,
                            'help' => 'Prepended to auto-generated filename titles'],
                        'workType'           => ['label' => 'Work Type (AAT)', 'type' => 'select', 'required' => true,
                            'vocabulary' => 'workType'],
                        'levelOfDescription' => ['label' => 'Level of Description', 'type' => 'select',
                            'vocabulary' => 'levelOfDescription'],
                        'identifier'         => ['label' => 'Identifier / Accession Number', 'type' => 'text'],
                    ],
                ],
                'description' => [
                    'label' => 'Physical Description (CCO 6)',
                    'fields' => [
                        'scopeAndContent'      => ['label' => 'Description', 'type' => 'textarea'],
                        'materialsTechniques'  => ['label' => 'Materials & Techniques (AAT)', 'type' => 'text'],
                        'measurements'         => ['label' => 'Measurements', 'type' => 'text'],
                        'extentAndMedium'      => ['label' => 'Extent and Medium', 'type' => 'text'],
                    ],
                ],
                'context' => [
                    'label' => 'Style & Context (CCO 3)',
                    'fields' => [
                        'stylePeriod'        => ['label' => 'Style / Period (AAT)', 'type' => 'select', 'vocabulary' => 'stylePeriod'],
                        'culturalContext'     => ['label' => 'Cultural Context', 'type' => 'text'],
                        'creators'           => ['label' => 'Artist / Creator', 'type' => 'text', 'help' => 'Pipe-separated'],
                        'creationDatesStart' => ['label' => 'Creation Date (Start)', 'type' => 'text'],
                        'creationDatesEnd'   => ['label' => 'Creation Date (End)', 'type' => 'text'],
                    ],
                ],
                'provenance' => [
                    'label' => 'Provenance & Rights',
                    'fields' => [
                        'archivalHistory'  => ['label' => 'Provenance', 'type' => 'textarea'],
                        'copyrightStatus'  => ['label' => 'Copyright Status', 'type' => 'select', 'vocabulary' => 'copyrightStatus'],
                        'copyrightHolder'  => ['label' => 'Copyright Holder', 'type' => 'text'],
                    ],
                ],
                'accesspoints' => [
                    'label' => 'Access Points',
                    'fields' => [
                        'subjectAccessPoints' => ['label' => 'Subject', 'type' => 'text', 'help' => 'Pipe-separated'],
                        'placeAccessPoints'   => ['label' => 'Place', 'type' => 'text', 'help' => 'Pipe-separated'],
                        'genreAccessPoints'   => ['label' => 'Genre', 'type' => 'text', 'help' => 'Pipe-separated'],
                    ],
                ],
                'control' => [
                    'label' => 'Control',
                    'fields' => [
                        'publicationStatus' => ['label' => 'Publication Status', 'type' => 'select', 'vocabulary' => 'publicationStatus'],
                        'culture'           => ['label' => 'Culture Code', 'type' => 'text', 'help' => 'e.g. en, fr, af'],
                    ],
                ],
            ];
        }

        // ── MODS (Library) ──
        if ($standard === 'mods') {
            return [
                'titleInfo' => [
                    'label' => 'Title Information',
                    'fields' => [
                        'title'              => ['label' => 'Title', 'type' => 'text', 'required' => true,
                            'help' => 'Prepended to auto-generated filename titles'],
                        'alternateTitle'     => ['label' => 'Alternative Title', 'type' => 'text'],
                        'identifier'         => ['label' => 'Identifier (ISBN, ISSN, etc.)', 'type' => 'text'],
                        'levelOfDescription' => ['label' => 'Level of Description', 'type' => 'select', 'vocabulary' => 'levelOfDescription'],
                        'typeOfResource'     => ['label' => 'Type of Resource', 'type' => 'select', 'required' => true,
                            'vocabulary' => 'typeOfResource'],
                        'genre'              => ['label' => 'Genre', 'type' => 'text'],
                    ],
                ],
                'originInfo' => [
                    'label' => 'Origin Information',
                    'fields' => [
                        'originInfoPublisher'   => ['label' => 'Publisher', 'type' => 'text'],
                        'originInfoPlace'       => ['label' => 'Place of Publication', 'type' => 'text'],
                        'originInfoDateIssued'  => ['label' => 'Date Issued', 'type' => 'text'],
                        'issuance'              => ['label' => 'Issuance', 'type' => 'select',
                            'options' => ['monographic', 'continuing', 'serial', 'integrating resource']],
                        'frequency'             => ['label' => 'Frequency', 'type' => 'text', 'help' => 'For serials: monthly, quarterly, etc.'],
                    ],
                ],
                'description' => [
                    'label' => 'Content Description',
                    'fields' => [
                        'abstract'          => ['label' => 'Abstract', 'type' => 'textarea'],
                        'scopeAndContent'   => ['label' => 'Scope and Content', 'type' => 'textarea'],
                        'tableOfContents'   => ['label' => 'Table of Contents', 'type' => 'textarea'],
                        'extentAndMedium'   => ['label' => 'Physical Description / Extent', 'type' => 'text'],
                        'note'              => ['label' => 'Note', 'type' => 'textarea'],
                    ],
                ],
                'agents' => [
                    'label' => 'Names & Agents',
                    'fields' => [
                        'creators'     => ['label' => 'Creator / Author', 'type' => 'text', 'help' => 'Pipe-separated'],
                        'nameAccessPoints' => ['label' => 'Name Access Points', 'type' => 'text', 'help' => 'Pipe-separated'],
                    ],
                ],
                'classification' => [
                    'label' => 'Classification & Access Points',
                    'fields' => [
                        'classification'      => ['label' => 'Classification (DDC, LCC)', 'type' => 'text'],
                        'subjectAccessPoints' => ['label' => 'Subject', 'type' => 'text', 'help' => 'Pipe-separated'],
                        'placeAccessPoints'   => ['label' => 'Geographic Subject', 'type' => 'text', 'help' => 'Pipe-separated'],
                        'genreAccessPoints'   => ['label' => 'Genre / Form', 'type' => 'text', 'help' => 'Pipe-separated'],
                    ],
                ],
                'access' => [
                    'label' => 'Access & Rights',
                    'fields' => [
                        'accessConditions'       => ['label' => 'Access Conditions', 'type' => 'text'],
                        'reproductionConditions' => ['label' => 'Use and Reproduction', 'type' => 'text'],
                        'copyrightStatus'        => ['label' => 'Copyright Status', 'type' => 'select',
                            'vocabulary' => 'copyrightStatus'],
                    ],
                ],
                'control' => [
                    'label' => 'Record Control',
                    'fields' => [
                        'publicationStatus' => ['label' => 'Publication Status', 'type' => 'select',
                            'vocabulary' => 'publicationStatus'],
                        'culture'           => ['label' => 'Language Code', 'type' => 'text', 'help' => 'e.g. en, fr, af'],
                    ],
                ],
            ];
        }

        // Fallback: same as ISAD(G)
        return self::getMetadataFieldGroups('isadg');
    }

    // ─── Session Management ─────────────────────────────────────────────

    public function createSession(int $userId, array $config): int
    {
        return DB::table('ingest_session')->insertGetId([
            'user_id' => $userId,
            'title' => $config['title'] ?? null,
            'sector' => $config['sector'] ?? 'archive',
            'standard' => $config['standard'] ?? 'isadg',
            'repository_id' => $config['repository_id'] ?? null,
            'parent_id' => $config['parent_id'] ?? null,
            'parent_placement' => $config['parent_placement'] ?? 'top_level',
            'new_parent_title' => $config['new_parent_title'] ?? null,
            'new_parent_level' => $config['new_parent_level'] ?? null,
            'output_create_records' => $config['output_create_records'] ?? 1,
            'output_generate_sip' => $config['output_generate_sip'] ?? 0,
            'output_generate_aip' => $config['output_generate_aip'] ?? 0,
            'output_generate_dip' => $config['output_generate_dip'] ?? 0,
            'output_sip_path' => $config['output_sip_path'] ?? null,
            'output_aip_path' => $config['output_aip_path'] ?? null,
            'output_dip_path' => $config['output_dip_path'] ?? null,
            'derivative_thumbnails' => $config['derivative_thumbnails'] ?? 1,
            'derivative_reference' => $config['derivative_reference'] ?? 1,
            'derivative_normalize_format' => $config['derivative_normalize_format'] ?? null,
            'security_classification_id' => $config['security_classification_id'] ?? null,
            'process_ner' => $config['process_ner'] ?? 0,
            'process_ocr' => $config['process_ocr'] ?? 0,
            'process_virus_scan' => $config['process_virus_scan'] ?? 1,
            'process_summarize' => $config['process_summarize'] ?? 0,
            'process_spellcheck' => $config['process_spellcheck'] ?? 0,
            'process_translate' => $config['process_translate'] ?? 0,
            'process_translate_lang' => $config['process_translate_lang'] ?? null,
            'process_format_id' => $config['process_format_id'] ?? 0,
            'process_face_detect' => $config['process_face_detect'] ?? 0,
            'status' => 'configure',
            'config' => json_encode($config),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function updateSession(int $id, array $data): void
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        DB::table('ingest_session')->where('id', $id)->update($data);
    }

    public function getSession(int $id): ?object
    {
        return DB::table('ingest_session')->where('id', $id)->first();
    }

    public function getSessions(int $userId, ?string $status = null): array
    {
        $q = DB::table('ingest_session')->where('user_id', $userId);
        if ($status) {
            $q->where('status', $status);
        }

        return $q->orderByDesc('updated_at')->get()->toArray();
    }

    public function updateSessionStatus(int $id, string $status): void
    {
        DB::table('ingest_session')->where('id', $id)->update([
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // ─── File Handling ──────────────────────────────────────────────────

    public function processUpload(int $sessionId, array $fileInfo): int
    {
        // Determine file type
        if (($fileInfo['mime_type'] ?? '') === 'directory') {
            $fileType = 'directory';
        } else {
            $ext = strtolower(pathinfo($fileInfo['original_name'], PATHINFO_EXTENSION));
            $typeMap = ['csv' => 'csv', 'zip' => 'zip', 'xml' => 'ead'];
            $fileType = $typeMap[$ext] ?? 'csv';
        }

        $fileId = DB::table('ingest_file')->insertGetId([
            'session_id' => $sessionId,
            'file_type' => $fileType,
            'original_name' => $fileInfo['original_name'],
            'stored_path' => $fileInfo['stored_path'],
            'file_size' => $fileInfo['file_size'] ?? 0,
            'mime_type' => $fileInfo['mime_type'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Auto-detect CSV format
        if ($fileType === 'csv') {
            $detection = $this->detectCsvFormat($fileInfo['stored_path']);
            DB::table('ingest_file')->where('id', $fileId)->update([
                'row_count' => $detection['row_count'],
                'delimiter' => $detection['delimiter'],
                'encoding' => $detection['encoding'],
                'headers' => json_encode($detection['headers']),
            ]);
        } elseif ($fileType === 'zip') {
            $extractDir = dirname($fileInfo['stored_path']) . '/extracted_' . $sessionId;
            $this->extractZip($fileId, $extractDir);
        } elseif ($fileType === 'directory') {
            // Count files in directory
            $fileCount = 0;
            if (is_dir($fileInfo['stored_path'])) {
                $iter = new \FilesystemIterator($fileInfo['stored_path'], \FilesystemIterator::SKIP_DOTS);
                foreach ($iter as $item) {
                    if ($item->isFile()) {
                        $fileCount++;
                    }
                }
            }
            DB::table('ingest_file')->where('id', $fileId)->update([
                'row_count' => $fileCount,
            ]);
        }

        return $fileId;
    }

    public function detectCsvFormat(string $filePath): array
    {
        $result = [
            'delimiter' => ',',
            'encoding' => 'UTF-8',
            'headers' => [],
            'row_count' => 0,
            'sample_rows' => [],
        ];

        if (!file_exists($filePath)) {
            return $result;
        }

        $content = file_get_contents($filePath, false, null, 0, 8192);

        // Detect encoding
        $detected = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
        $result['encoding'] = $detected ?: 'UTF-8';

        // Convert if needed
        if ($result['encoding'] !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $result['encoding']);
        }

        // Detect delimiter
        $delimiters = [',' => 0, ';' => 0, "\t" => 0, '|' => 0];
        $firstLine = strtok($content, "\n");
        foreach ($delimiters as $d => &$count) {
            $count = substr_count($firstLine, $d);
        }
        arsort($delimiters);
        $result['delimiter'] = array_key_first($delimiters);

        // Parse headers and count rows
        $handle = fopen($filePath, 'r');
        if ($handle) {
            $headers = fgetcsv($handle, 0, $result['delimiter']);
            if ($headers) {
                $result['headers'] = array_map('trim', $headers);
            }

            $rowCount = 0;
            $samples = [];
            while (($row = fgetcsv($handle, 0, $result['delimiter'])) !== false) {
                $rowCount++;
                if ($rowCount <= 10) {
                    $samples[] = $row;
                }
            }
            $result['row_count'] = $rowCount;
            $result['sample_rows'] = $samples;
            fclose($handle);
        }

        return $result;
    }

    public function extractZip(int $fileId, string $extractTo): array
    {
        $file = DB::table('ingest_file')->where('id', $fileId)->first();
        if (!$file) {
            return [];
        }

        if (!is_dir($extractTo)) {
            mkdir($extractTo, 0755, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($file->stored_path) !== true) {
            return [];
        }

        $zip->extractTo($extractTo);
        $zip->close();

        DB::table('ingest_file')->where('id', $fileId)->update([
            'extracted_path' => $extractTo,
        ]);

        return $this->scanDirectory($extractTo);
    }

    public function scanDirectory(string $dirPath): array
    {
        $files = [];
        if (!is_dir($dirPath)) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dirPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $files[] = [
                'path' => $file->getPathname(),
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'type' => $file->getExtension(),
                'mime' => mime_content_type($file->getPathname()),
            ];
        }

        return $files;
    }

    public function getFiles(int $sessionId): array
    {
        return DB::table('ingest_file')
            ->where('session_id', $sessionId)
            ->get()
            ->toArray();
    }

    // ─── Row Parsing ────────────────────────────────────────────────────

    public function parseRows(int $sessionId): int
    {
        // Try CSV first
        $file = DB::table('ingest_file')
            ->where('session_id', $sessionId)
            ->where('file_type', 'csv')
            ->first();

        if ($file && file_exists($file->stored_path)) {
            return $this->parseRowsFromCsv($sessionId, $file);
        }

        // Try directory import
        $dirFile = DB::table('ingest_file')
            ->where('session_id', $sessionId)
            ->where('file_type', 'directory')
            ->first();

        if ($dirFile && is_dir($dirFile->stored_path)) {
            return $this->parseRowsFromDirectory($sessionId, $dirFile);
        }

        return 0;
    }

    /**
     * Parse rows from a CSV file.
     */
    private function parseRowsFromCsv(int $sessionId, object $file): int
    {
        // Clear existing rows
        DB::table('ingest_row')->where('session_id', $sessionId)->delete();

        $delimiter = $file->delimiter ?: ',';
        $headers = json_decode($file->headers, true) ?: [];
        $handle = fopen($file->stored_path, 'r');
        if (!$handle) {
            return 0;
        }

        // Skip header row
        fgetcsv($handle, 0, $delimiter);

        $rowNum = 0;
        while (($cols = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNum++;
            $data = [];
            foreach ($headers as $i => $header) {
                $data[$header] = $cols[$i] ?? '';
            }

            DB::table('ingest_row')->insert([
                'session_id' => $sessionId,
                'row_number' => $rowNum,
                'legacy_id' => $data['legacyId'] ?? $data['legacy_id'] ?? null,
                'parent_id_ref' => $data['parentId'] ?? $data['parent_id'] ?? $data['qubitParentSlug'] ?? null,
                'level_of_description' => $data['levelOfDescription'] ?? $data['level_of_description'] ?? null,
                'title' => $data['title'] ?? null,
                'data' => json_encode($data),
                'digital_object_path' => $data['digitalObjectPath'] ?? $data['digital_object_path'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        fclose($handle);

        return $rowNum;
    }

    /**
     * Parse rows from a directory of files (one row per file).
     */
    private function parseRowsFromDirectory(int $sessionId, object $dirFile): int
    {
        // Clear existing rows
        DB::table('ingest_row')->where('session_id', $sessionId)->delete();

        $dirPath = $dirFile->stored_path;
        $files = [];

        $iter = new \DirectoryIterator($dirPath);
        foreach ($iter as $item) {
            if ($item->isDot() || !$item->isFile()) {
                continue;
            }
            $files[] = [
                'name' => $item->getFilename(),
                'path' => $item->getPathname(),
                'size' => $item->getSize(),
            ];
        }

        // Sort by filename
        usort($files, function ($a, $b) {
            return strnatcasecmp($a['name'], $b['name']);
        });

        $rowNum = 0;
        foreach ($files as $f) {
            $rowNum++;
            $titleBase = pathinfo($f['name'], PATHINFO_FILENAME);
            // Convert underscores/hyphens to spaces for a readable title
            $title = ucfirst(str_replace(['_', '-'], ' ', $titleBase));

            $data = [
                'title' => $title,
                'digitalObjectPath' => $f['path'],
                'levelOfDescription' => 'Item',
            ];

            DB::table('ingest_row')->insert([
                'session_id' => $sessionId,
                'row_number' => $rowNum,
                'level_of_description' => 'Item',
                'title' => $title,
                'data' => json_encode($data),
                'enriched_data' => json_encode($data),
                'digital_object_path' => $f['path'],
                'digital_object_matched' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $rowNum;
    }

    // ─── Mapping ────────────────────────────────────────────────────────

    public function autoMapColumns(int $sessionId, string $standard = 'isadg'): array
    {
        $file = DB::table('ingest_file')
            ->where('session_id', $sessionId)
            ->where('file_type', 'csv')
            ->first();

        if (!$file) {
            return [];
        }

        $sourceHeaders = json_decode($file->headers, true) ?: [];
        $targetFields = self::getTargetFields($standard);

        // Clear existing mappings
        DB::table('ingest_mapping')->where('session_id', $sessionId)->delete();

        // Known aliases (source variant → AtoM field)
        $aliases = [
            'legacy_id' => 'legacyId',
            'legacyid' => 'legacyId',
            'parent_id' => 'parentId',
            'parentid' => 'parentId',
            'parent_slug' => 'qubitParentSlug',
            'level_of_description' => 'levelOfDescription',
            'levelofdescription' => 'levelOfDescription',
            'level' => 'levelOfDescription',
            'extent_and_medium' => 'extentAndMedium',
            'extent' => 'extentAndMedium',
            'scope_and_content' => 'scopeAndContent',
            'scope' => 'scopeAndContent',
            'description' => 'scopeAndContent',
            'archival_history' => 'archivalHistory',
            'custodial_history' => 'archivalHistory',
            'access_conditions' => 'accessConditions',
            'conditions_of_access' => 'accessConditions',
            'reproduction_conditions' => 'reproductionConditions',
            'conditions_of_reproduction' => 'reproductionConditions',
            'finding_aids' => 'findingAids',
            'publication_status' => 'publicationStatus',
            'digital_object_path' => 'digitalObjectPath',
            'digital_object_uri' => 'digitalObjectURI',
            'digital_object' => 'digitalObjectPath',
            'filename' => 'digitalObjectPath',
            'file_path' => 'digitalObjectPath',
            'subject_access_points' => 'subjectAccessPoints',
            'subjects' => 'subjectAccessPoints',
            'place_access_points' => 'placeAccessPoints',
            'places' => 'placeAccessPoints',
            'name_access_points' => 'nameAccessPoints',
            'names' => 'nameAccessPoints',
            'genre_access_points' => 'genreAccessPoints',
            'genres' => 'genreAccessPoints',
            'creator' => 'creators',
            'date' => 'creationDates',
            'creation_date' => 'creationDates',
            'start_date' => 'creationDatesStart',
            'end_date' => 'creationDatesEnd',
            'accession_number' => 'accessionNumber',
            'copyright_status' => 'copyrightStatus',
            'physical_location' => 'physicalObjectLocation',
            'storage_location' => 'physicalObjectLocation',
            'alternate_title' => 'alternateTitle',
            'ref_code' => 'identifier',
            'reference_code' => 'identifier',
            'ref' => 'identifier',
        ];

        $mappings = [];
        $order = 0;

        foreach ($sourceHeaders as $source) {
            $order++;
            $sourceLower = strtolower(trim($source));
            $sourceNorm = str_replace([' ', '-', '_'], '', $sourceLower);
            $target = null;
            $confidence = 'none';

            // Exact match
            if (in_array($source, $targetFields, true)) {
                $target = $source;
                $confidence = 'exact';
            }

            // Alias match
            if (!$target && isset($aliases[$sourceLower])) {
                $target = $aliases[$sourceLower];
                $confidence = 'exact';
            }

            // Normalized alias match
            if (!$target && isset($aliases[$sourceNorm])) {
                $target = $aliases[$sourceNorm];
                $confidence = 'fuzzy';
            }

            // Case-insensitive target match
            if (!$target) {
                foreach ($targetFields as $tf) {
                    if (strtolower($tf) === $sourceLower || str_replace('_', '', $sourceLower) === strtolower($tf)) {
                        $target = $tf;
                        $confidence = 'fuzzy';
                        break;
                    }
                }
            }

            $mapId = DB::table('ingest_mapping')->insertGetId([
                'session_id' => $sessionId,
                'source_column' => $source,
                'target_field' => $target,
                'is_ignored' => $target ? 0 : 1,
                'sort_order' => $order,
            ]);

            $mappings[] = [
                'id' => $mapId,
                'source_column' => $source,
                'target_field' => $target,
                'confidence' => $confidence,
                'is_ignored' => $target ? 0 : 1,
            ];
        }

        return $mappings;
    }

    public function saveMappings(int $sessionId, array $mappings): void
    {
        foreach ($mappings as $map) {
            if (isset($map['id'])) {
                DB::table('ingest_mapping')->where('id', $map['id'])->update([
                    'target_field' => $map['target_field'] ?? null,
                    'is_ignored' => $map['is_ignored'] ?? 0,
                    'default_value' => $map['default_value'] ?? null,
                    'transform' => $map['transform'] ?? null,
                ]);
            }
        }
    }

    public function getMappings(int $sessionId): array
    {
        return DB::table('ingest_mapping')
            ->where('session_id', $sessionId)
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    /**
     * Load a saved mapping profile from ahgDataMigrationPlugin's atom_data_mapping table.
     */
    public function loadMappingProfile(int $sessionId, int $mappingId): void
    {
        try {
            $profile = DB::table('atom_data_mapping')->where('id', $mappingId)->first();
        } catch (\Exception $e) {
            return; // Table may not exist if DataMigration plugin not installed
        }

        if (!$profile || !$profile->field_mappings) {
            return;
        }

        $fieldMappings = json_decode($profile->field_mappings, true);
        if (!is_array($fieldMappings)) {
            return;
        }

        // Apply profile mappings to current session
        $existing = $this->getMappings($sessionId);
        foreach ($existing as $map) {
            foreach ($fieldMappings as $fm) {
                $srcMatch = ($fm['source'] ?? '') === $map->source_column
                    || strtolower($fm['source'] ?? '') === strtolower($map->source_column);
                if ($srcMatch && !empty($fm['target'])) {
                    DB::table('ingest_mapping')->where('id', $map->id)->update([
                        'target_field' => $fm['target'],
                        'is_ignored' => 0,
                        'default_value' => $fm['default'] ?? null,
                        'transform' => $fm['transform'] ?? null,
                    ]);
                    break;
                }
            }
        }
    }

    public function getSavedMappingProfiles(): array
    {
        try {
            return DB::table('atom_data_mapping')
                ->orderBy('name')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            return []; // Table may not exist
        }
    }

    // ─── Enrichment ─────────────────────────────────────────────────────

    /**
     * Apply shared metadata to all rows (directory import mode).
     */
    public function applyMetadataToRows(int $sessionId, array $metadata): void
    {
        // Extract counter settings before filtering
        $enableCounter = !empty($metadata['_enable_counter']);
        $counterPrefix = $metadata['_counter_prefix'] ?? '';
        $counterStart = max(1, (int) ($metadata['_counter_start'] ?? 1));
        $counterPadding = max(1, min(10, (int) ($metadata['_counter_padding'] ?? 4)));

        // Remove counter meta-keys from field metadata
        unset($metadata['_enable_counter'], $metadata['_counter_prefix'], $metadata['_counter_start'], $metadata['_counter_padding']);

        // Filter out empty values
        $metadata = array_filter($metadata, function ($v) {
            return $v !== '' && $v !== null;
        });

        $rows = DB::table('ingest_row')
            ->where('session_id', $sessionId)
            ->orderBy('row_number')
            ->get();

        $counter = $counterStart;

        foreach ($rows as $row) {
            $data = json_decode($row->data, true) ?: [];
            $enriched = json_decode($row->enriched_data, true) ?: [];

            // Merge metadata into both data and enriched_data
            foreach ($metadata as $field => $value) {
                // title is per-row (auto-generated from filename), only override if explicitly set
                if ($field === 'title' && !empty($value)) {
                    // Use as prefix: "prefix - filename title"
                    if (!empty($row->title)) {
                        $data[$field] = $value . ' - ' . $row->title;
                    } else {
                        $data[$field] = $value;
                    }
                } else {
                    $data[$field] = $value;
                }
                $enriched[$field] = $data[$field];
            }

            // Apply counter/suffix to identifier
            if ($enableCounter) {
                $identifier = $counterPrefix . str_pad($counter, $counterPadding, '0', STR_PAD_LEFT);
                $data['identifier'] = $identifier;
                $enriched['identifier'] = $identifier;
                $counter++;
            }

            $update = [
                'data' => json_encode($data),
                'enriched_data' => json_encode($enriched),
            ];

            // Update level_of_description column if provided
            if (isset($metadata['levelOfDescription'])) {
                $update['level_of_description'] = $metadata['levelOfDescription'];
            }

            DB::table('ingest_row')->where('id', $row->id)->update($update);
        }
    }

    public function enrichRows(int $sessionId): void
    {
        $session = $this->getSession($sessionId);
        if (!$session) {
            return;
        }

        $mappings = $this->getMappings($sessionId);
        $mappingLookup = [];
        foreach ($mappings as $m) {
            if ($m->target_field && !$m->is_ignored) {
                $mappingLookup[$m->source_column] = $m;
            }
        }

        $rows = DB::table('ingest_row')
            ->where('session_id', $sessionId)
            ->orderBy('row_number')
            ->get();

        foreach ($rows as $row) {
            $data = json_decode($row->data, true) ?: [];
            $enriched = [];

            // Apply mappings: remap source columns to target fields
            foreach ($data as $sourceCol => $value) {
                if (isset($mappingLookup[$sourceCol])) {
                    $map = $mappingLookup[$sourceCol];
                    $targetField = $map->target_field;
                    $val = $value;

                    // Apply default value if empty
                    if (empty($val) && !empty($map->default_value)) {
                        $val = $map->default_value;
                    }

                    // Apply transforms
                    if (!empty($map->transform) && !empty($val)) {
                        $val = $this->applyTransform($val, $map->transform);
                    }

                    $enriched[$targetField] = $val;
                }
            }

            // Auto-generate defaults
            if (empty($enriched['culture'])) {
                $enriched['culture'] = 'en';
            }
            if (empty($enriched['publicationStatus'])) {
                $enriched['publicationStatus'] = 'Draft';
            }

            DB::table('ingest_row')->where('id', $row->id)->update([
                'enriched_data' => json_encode($enriched),
                'title' => $enriched['title'] ?? $row->title,
                'level_of_description' => $enriched['levelOfDescription'] ?? $row->level_of_description,
                'legacy_id' => $enriched['legacyId'] ?? $row->legacy_id,
                'parent_id_ref' => $enriched['parentId'] ?? $enriched['qubitParentSlug'] ?? $row->parent_id_ref,
            ]);
        }
    }

    protected function applyTransform(string $value, string $transform): string
    {
        switch ($transform) {
            case 'uppercase':
                return strtoupper($value);
            case 'lowercase':
                return strtolower($value);
            case 'trim':
                return trim($value);
            case 'titlecase':
                return ucwords(strtolower($value));
            case 'date_iso':
                $ts = strtotime($value);
                return $ts ? date('Y-m-d', $ts) : $value;
            case 'strip_html':
                return strip_tags($value);
            default:
                return $value;
        }
    }

    public function extractFileMetadata(int $sessionId): void
    {
        $rows = DB::table('ingest_row')
            ->where('session_id', $sessionId)
            ->whereNotNull('digital_object_path')
            ->where('digital_object_path', '!=', '')
            ->get();

        foreach ($rows as $row) {
            $filePath = $row->digital_object_path;

            // Try to resolve relative paths from extracted ZIP
            if (!file_exists($filePath)) {
                $file = DB::table('ingest_file')
                    ->where('session_id', $sessionId)
                    ->whereNotNull('extracted_path')
                    ->first();
                if ($file && $file->extracted_path) {
                    $candidate = $file->extracted_path . '/' . $filePath;
                    if (file_exists($candidate)) {
                        $filePath = $candidate;
                    }
                }
            }

            if (!file_exists($filePath)) {
                continue;
            }

            $metadata = null;
            if (class_exists('\AtomFramework\Helpers\EmbeddedMetadataParser')) {
                $metadata = \AtomFramework\Helpers\EmbeddedMetadataParser::extract($filePath);
            }

            // Generate checksum
            $checksum = hash_file('sha256', $filePath);

            DB::table('ingest_row')->where('id', $row->id)->update([
                'metadata_extracted' => $metadata ? json_encode($metadata) : null,
                'checksum_sha256' => $checksum,
                'digital_object_matched' => 1,
            ]);
        }
    }

    public function matchDigitalObjects(int $sessionId, string $strategy = 'filename'): int
    {
        $file = DB::table('ingest_file')
            ->where('session_id', $sessionId)
            ->whereNotNull('extracted_path')
            ->first();

        if (!$file || !$file->extracted_path) {
            return 0;
        }

        $availableFiles = $this->scanDirectory($file->extracted_path);
        $fileIndex = [];
        foreach ($availableFiles as $f) {
            $key = strtolower($f['name']);
            $fileIndex[$key] = $f['path'];
            // Also index without extension
            $noExt = strtolower(pathinfo($f['name'], PATHINFO_FILENAME));
            $fileIndex[$noExt] = $f['path'];
        }

        $matched = 0;
        $rows = DB::table('ingest_row')
            ->where('session_id', $sessionId)
            ->get();

        foreach ($rows as $row) {
            $doPath = $row->digital_object_path;
            if (empty($doPath)) {
                // Try matching by legacyId or title
                if ($strategy === 'legacyId' && $row->legacy_id) {
                    $key = strtolower($row->legacy_id);
                } elseif ($strategy === 'title' && $row->title) {
                    $key = strtolower(preg_replace('/[^a-z0-9]/', '', strtolower($row->title)));
                } else {
                    continue;
                }
            } else {
                $key = strtolower(basename($doPath));
            }

            if (isset($fileIndex[$key])) {
                DB::table('ingest_row')->where('id', $row->id)->update([
                    'digital_object_path' => $fileIndex[$key],
                    'digital_object_matched' => 1,
                ]);
                $matched++;
            } elseif (isset($fileIndex[strtolower(pathinfo($key, PATHINFO_FILENAME))])) {
                DB::table('ingest_row')->where('id', $row->id)->update([
                    'digital_object_path' => $fileIndex[strtolower(pathinfo($key, PATHINFO_FILENAME))],
                    'digital_object_matched' => 1,
                ]);
                $matched++;
            }
        }

        return $matched;
    }

    // ─── Validation ─────────────────────────────────────────────────────

    public function validateSession(int $sessionId): array
    {
        $session = $this->getSession($sessionId);
        if (!$session) {
            return ['total' => 0, 'valid' => 0, 'warnings' => 0, 'errors' => 0];
        }

        // Clear previous validations
        DB::table('ingest_validation')->where('session_id', $sessionId)->delete();

        $standard = $session->standard;
        $required = self::getRequiredFields($standard);

        $rows = DB::table('ingest_row')
            ->where('session_id', $sessionId)
            ->where('is_excluded', 0)
            ->get();

        $stats = ['total' => count($rows), 'valid' => 0, 'warnings' => 0, 'errors' => 0];

        // Track legacyIds for duplicate detection
        $legacyIds = [];
        $checksums = [];

        foreach ($rows as $row) {
            $enriched = json_decode($row->enriched_data, true) ?: [];
            if (empty($enriched)) {
                $enriched = json_decode($row->data, true) ?: [];
            }
            $rowErrors = 0;
            $rowWarnings = 0;

            // Required field checks
            foreach ($required as $field) {
                if (empty($enriched[$field])) {
                    $this->addValidation($sessionId, $row->row_number, 'error', $field,
                        "Required field '{$field}' is empty");
                    $rowErrors++;
                }
            }

            // Level of description validation
            if (!empty($enriched['levelOfDescription'])) {
                $validLevels = [
                    'Fonds', 'Subfonds', 'Collection', 'Series', 'Subseries',
                    'File', 'Item', 'Part', 'Class', 'Sub-item',
                ];
                $lvl = $enriched['levelOfDescription'];
                if (!in_array($lvl, $validLevels, true) && !in_array(ucfirst(strtolower($lvl)), $validLevels, true)) {
                    $this->addValidation($sessionId, $row->row_number, 'warning', 'levelOfDescription',
                        "Level of description '{$lvl}' may not be recognized");
                    $rowWarnings++;
                }
            }

            // Date format validation
            foreach (['creationDatesStart', 'creationDatesEnd', 'eventStartDates', 'eventEndDates'] as $dateField) {
                if (!empty($enriched[$dateField])) {
                    $val = $enriched[$dateField];
                    if (!preg_match('/^\d{4}(-\d{2}(-\d{2})?)?$/', $val) && !strtotime($val)) {
                        $this->addValidation($sessionId, $row->row_number, 'warning', $dateField,
                            "Date '{$val}' may not be in a recognized format (YYYY-MM-DD preferred)");
                        $rowWarnings++;
                    }
                }
            }

            // Hierarchy: parentId references must exist as legacyId within the batch
            if (!empty($row->parent_id_ref) && $session->parent_placement === 'csv_hierarchy') {
                // We'll validate parent references in a second pass below
            }

            // Duplicate legacyId detection
            if (!empty($row->legacy_id)) {
                if (isset($legacyIds[$row->legacy_id])) {
                    $this->addValidation($sessionId, $row->row_number, 'error', 'legacyId',
                        "Duplicate legacyId '{$row->legacy_id}' (also on row {$legacyIds[$row->legacy_id]})");
                    $rowErrors++;
                } else {
                    $legacyIds[$row->legacy_id] = $row->row_number;
                }
            }

            // Duplicate checksum detection
            if (!empty($row->checksum_sha256)) {
                if (isset($checksums[$row->checksum_sha256])) {
                    $this->addValidation($sessionId, $row->row_number, 'warning', 'digitalObjectPath',
                        "Duplicate file checksum (same file as row {$checksums[$row->checksum_sha256]})");
                    $rowWarnings++;
                } else {
                    $checksums[$row->checksum_sha256] = $row->row_number;
                }
            }

            // Digital object file existence
            if (!empty($row->digital_object_path) && !$row->digital_object_matched) {
                $this->addValidation($sessionId, $row->row_number, 'warning', 'digitalObjectPath',
                    "Digital object file not found: " . basename($row->digital_object_path));
                $rowWarnings++;
            }

            // Update row validity
            $isValid = ($rowErrors === 0) ? 1 : 0;
            DB::table('ingest_row')->where('id', $row->id)->update(['is_valid' => $isValid]);

            if ($rowErrors > 0) {
                $stats['errors'] += $rowErrors;
            } elseif ($rowWarnings > 0) {
                $stats['warnings'] += $rowWarnings;
            }

            if ($isValid) {
                $stats['valid']++;
            }
        }

        // Second pass: validate parent references
        if ($session->parent_placement === 'csv_hierarchy') {
            foreach ($rows as $row) {
                if (!empty($row->parent_id_ref) && !isset($legacyIds[$row->parent_id_ref])) {
                    // Check if it exists in AtoM already
                    $exists = DB::table('slug')->where('slug', $row->parent_id_ref)->exists()
                        || DB::table('information_object')
                            ->join('information_object_i18n', 'information_object.id', '=', 'information_object_i18n.id')
                            ->where('information_object_i18n.title', $row->parent_id_ref)
                            ->exists();

                    if (!$exists) {
                        $this->addValidation($sessionId, $row->row_number, 'error', 'parentId',
                            "Parent reference '{$row->parent_id_ref}' not found in batch or AtoM");
                        $stats['errors']++;
                    }
                }
            }
        }

        return $stats;
    }

    protected function addValidation(int $sessionId, int $rowNumber, string $severity, ?string $field, string $message): void
    {
        DB::table('ingest_validation')->insert([
            'session_id' => $sessionId,
            'row_number' => $rowNumber,
            'severity' => $severity,
            'field_name' => $field,
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getValidationErrors(int $sessionId, ?string $severity = null): array
    {
        $q = DB::table('ingest_validation')->where('session_id', $sessionId);
        if ($severity) {
            $q->where('severity', $severity);
        }

        return $q->orderBy('row_number')->orderBy('severity')->get()->toArray();
    }

    public function excludeRow(int $sessionId, int $rowNumber, bool $exclude = true): void
    {
        DB::table('ingest_row')
            ->where('session_id', $sessionId)
            ->where('row_number', $rowNumber)
            ->update(['is_excluded' => $exclude ? 1 : 0]);
    }

    public function fixRow(int $sessionId, int $rowNumber, string $field, $value): void
    {
        $row = DB::table('ingest_row')
            ->where('session_id', $sessionId)
            ->where('row_number', $rowNumber)
            ->first();

        if (!$row) {
            return;
        }

        $enriched = json_decode($row->enriched_data, true) ?: [];
        $enriched[$field] = $value;

        $update = ['enriched_data' => json_encode($enriched)];

        if ($field === 'title') {
            $update['title'] = $value;
        } elseif ($field === 'levelOfDescription') {
            $update['level_of_description'] = $value;
        } elseif ($field === 'legacyId') {
            $update['legacy_id'] = $value;
        }

        DB::table('ingest_row')
            ->where('session_id', $sessionId)
            ->where('row_number', $rowNumber)
            ->update($update);
    }

    // ─── Preview ────────────────────────────────────────────────────────

    public function buildHierarchyTree(int $sessionId): array
    {
        $rows = DB::table('ingest_row')
            ->where('session_id', $sessionId)
            ->orderBy('row_number')
            ->get()
            ->toArray();

        // Build lookup by legacyId
        $byLegacy = [];
        foreach ($rows as $row) {
            if (!empty($row->legacy_id)) {
                $byLegacy[$row->legacy_id] = $row;
            }
        }

        // Build tree
        $tree = [];
        $nodeMap = [];

        foreach ($rows as $row) {
            $node = [
                'row_number' => $row->row_number,
                'title' => $row->title ?: '[Untitled]',
                'level' => $row->level_of_description ?: '',
                'legacy_id' => $row->legacy_id,
                'is_valid' => $row->is_valid,
                'is_excluded' => $row->is_excluded,
                'has_do' => !empty($row->digital_object_path) && $row->digital_object_matched,
                'children' => [],
            ];

            $parentRef = $row->parent_id_ref;

            if (!empty($parentRef) && isset($byLegacy[$parentRef])) {
                // Has a parent within the batch
                $parentRow = $byLegacy[$parentRef]->row_number;
                if (isset($nodeMap[$parentRow])) {
                    $nodeMap[$parentRow]['children'][] = &$node;
                } else {
                    $tree[] = &$node;
                }
            } else {
                $tree[] = &$node;
            }

            $nodeMap[$row->row_number] = &$node;
            unset($node);
        }

        return $tree;
    }

    public function getPreviewRow(int $sessionId, int $rowNumber): ?object
    {
        return DB::table('ingest_row')
            ->where('session_id', $sessionId)
            ->where('row_number', $rowNumber)
            ->first();
    }

    public function getRowCount(int $sessionId): int
    {
        return DB::table('ingest_row')
            ->where('session_id', $sessionId)
            ->where('is_excluded', 0)
            ->count();
    }

    // ─── Templates ──────────────────────────────────────────────────────

    public function generateCsvTemplate(string $sector, string $standard = 'isadg'): string
    {
        $fields = self::getTargetFields($standard);

        // Add sector-specific fields at the start
        $sectorFields = [];
        if ($sector === 'museum' || $sector === 'gallery') {
            $sectorFields = ['objectNumber', 'objectName', 'artist', 'medium', 'dimensions'];
        } elseif ($sector === 'library') {
            $sectorFields = ['isbn', 'author', 'publisher', 'callNumber'];
        } elseif ($sector === 'dam') {
            $sectorFields = ['assetId', 'assetType', 'resolution', 'colorSpace'];
        }

        $allFields = array_unique(array_merge($sectorFields, $fields));

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $allFields);
        // Add one empty example row
        fputcsv($handle, array_fill(0, count($allFields), ''));
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    // ─── Cleanup ────────────────────────────────────────────────────────

    public function deleteSession(int $id): void
    {
        // Delete uploaded files from disk
        $files = DB::table('ingest_file')->where('session_id', $id)->get();
        foreach ($files as $file) {
            if ($file->stored_path && file_exists($file->stored_path)) {
                @unlink($file->stored_path);
            }
            if ($file->extracted_path && is_dir($file->extracted_path)) {
                $this->removeDirectory($file->extracted_path);
            }
        }

        // Cascade delete from DB
        DB::table('ingest_validation')->where('session_id', $id)->delete();
        DB::table('ingest_mapping')->where('session_id', $id)->delete();
        DB::table('ingest_row')->where('session_id', $id)->delete();
        DB::table('ingest_file')->where('session_id', $id)->delete();
        DB::table('ingest_job')->where('session_id', $id)->delete();
        DB::table('ingest_session')->where('id', $id)->delete();
    }

    protected function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }
}
