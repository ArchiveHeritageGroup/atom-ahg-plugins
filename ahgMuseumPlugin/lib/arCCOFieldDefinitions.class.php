<?php
/**
 * CCO (Cataloguing Cultural Objects) Field Definitions
 * 
 * Maps CCO standard fields to AtoM database fields with help text,
 * validation rules, and vocabulary references.
 * 
 * Based on CCO: A Guide to Cataloguing Cultural Objects
 * http://cco.vrafoundation.org/
 * 
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgMuseumPlugin
 */

class arCCOFieldDefinitions
{
    // CCO field categories
    const CATEGORY_OBJECT_WORK = 'object_work';
    const CATEGORY_TITLES = 'titles';
    const CATEGORY_CREATION = 'creation';
    const CATEGORY_STYLES = 'styles_periods';
    const CATEGORY_MEASUREMENTS = 'measurements';
    const CATEGORY_MATERIALS = 'materials_techniques';
    const CATEGORY_SUBJECT = 'subject_matter';
    const CATEGORY_INSCRIPTIONS = 'inscriptions';
    const CATEGORY_STATE_EDITION = 'state_edition';
    const CATEGORY_DESCRIPTION = 'description';
    const CATEGORY_CONDITION = 'condition';
    const CATEGORY_LOCATION = 'current_location';
    const CATEGORY_RELATED = 'related_works';
    const CATEGORY_RIGHTS = 'rights';

    /**
     * Get all field definitions organized by category
     */
    public static function getAllCategories()
    {
        return [
            self::CATEGORY_OBJECT_WORK => self::getObjectWorkFields(),
            self::CATEGORY_TITLES => self::getTitleFields(),
            self::CATEGORY_CREATION => self::getCreationFields(),
            self::CATEGORY_STYLES => self::getStylePeriodFields(),
            self::CATEGORY_MEASUREMENTS => self::getMeasurementFields(),
            self::CATEGORY_MATERIALS => self::getMaterialsTechniquesFields(),
            self::CATEGORY_SUBJECT => self::getSubjectFields(),
            self::CATEGORY_INSCRIPTIONS => self::getInscriptionFields(),
            self::CATEGORY_STATE_EDITION => self::getStateEditionFields(),
            self::CATEGORY_DESCRIPTION => self::getDescriptionFields(),
            self::CATEGORY_CONDITION => self::getConditionFields(),
            self::CATEGORY_LOCATION => self::getLocationFields(),
            self::CATEGORY_RELATED => self::getRelatedWorksFields(),
            self::CATEGORY_RIGHTS => self::getRightsFields(),
        ];
    }

    /**
     * Object/Work Type fields (CCO Chapter 2)
     */
    public static function getObjectWorkFields()
    {
        return [
            'label' => 'Object/Work',
            'ccoChapter' => 2,
            'description' => 'Information that identifies the work, including type, components, and count.',
            'fields' => [
                'work_type' => [
                    'label' => 'Work Type',
                    'atomField' => 'radGeneralMaterialDesignation',
                    'dbColumn' => 'work_type',
                    'helpText' => 'The specific kind of object or work being described. Use controlled vocabulary (AAT preferred). Examples: painting, photograph, sculpture, textile.',
                    'longHelp' => 'Select the most specific term that describes the object. For composite works, list the primary type first. The Work Type helps users search and filter results, so consistency is important.',
                    'required' => true,
                    'vocabulary' => 'aat_object_types',
                    'repeatable' => true,
                    'ccoRef' => '2.1',
                    'examples' => ['oil painting', 'gelatin silver print', 'marble sculpture', 'wool tapestry'],
                    'validationRules' => ['notEmpty'],
                    'spectrumEquiv' => 'Object name'
                ],
                'work_type_qualifier' => [
                    'label' => 'Work Type Qualifier',
                    'atomField' => null,
                    'dbColumn' => 'work_type_qualifier',
                    'helpText' => 'Qualifies uncertainty about the work type: possibly, probably, formerly classified as.',
                    'required' => false,
                    'vocabulary' => null,
                    'repeatable' => false,
                    'ccoRef' => '2.1.1',
                    'options' => ['possibly', 'probably', 'formerly classified as']
                ],
                'components_count' => [
                    'label' => 'Components/Parts',
                    'atomField' => null,
                    'dbColumn' => 'components_count',
                    'helpText' => 'Number and description of physical components. Examples: "diptych (2 panels)", "portfolio of 12 prints", "3-piece place setting".',
                    'required' => false,
                    'vocabulary' => null,
                    'repeatable' => false,
                    'ccoRef' => '2.2'
                ],
                'object_number' => [
                    'label' => 'Object Number',
                    'atomField' => 'identifier',
                    'dbColumn' => 'object_number',
                    'helpText' => 'Unique identifier assigned by the repository. This should follow your institution\'s numbering system.',
                    'longHelp' => 'The accession or catalog number that uniquely identifies this object within your collection. Format varies by institution (e.g., "2024.001.001", "P-1234", "ACC.2024/001").',
                    'required' => true,
                    'vocabulary' => null,
                    'repeatable' => false,
                    'ccoRef' => '2.3',
                    'validationRules' => ['notEmpty', 'unique'],
                    'spectrumEquiv' => 'Object number'
                ]
            ]
        ];
    }

    /**
     * Titles/Names fields (CCO Chapter 3)
     */
    public static function getTitleFields()
    {
        return [
            'label' => 'Titles/Names',
            'ccoChapter' => 3,
            'description' => 'Titles, names, or other identifying phrases for the work.',
            'fields' => [
                'title' => [
                    'label' => 'Title',
                    'atomField' => 'title',
                    'dbColumn' => 'title',
                    'helpText' => 'The primary title of the work. May be assigned by creator, owner, or cataloguer.',
                    'longHelp' => 'Enter the most commonly used or official title. If the work is untitled, create a descriptive title in square brackets: [Portrait of a woman in blue dress]. For non-English titles, you may add a translation in parentheses.',
                    'required' => true,
                    'vocabulary' => null,
                    'repeatable' => false,
                    'ccoRef' => '3.1',
                    'examples' => ['The Starry Night', 'Untitled', '[Landscape with cattle]', 'Guernica'],
                    'validationRules' => ['notEmpty']
                ],
                'title_type' => [
                    'label' => 'Title Type',
                    'atomField' => null,
                    'dbColumn' => 'title_type',
                    'helpText' => 'The source or nature of the title.',
                    'longHelp' => 'Repository: assigned by your institution | Creator: given by the artist | Inscribed: found on the work | Popular: commonly known title | Descriptive: based on subject depicted | Former: previously used title | Translated: translation of original title',
                    'required' => true,
                    'vocabulary' => 'cco_title_types',
                    'repeatable' => false,
                    'ccoRef' => '3.1.1',
                    'options' => [
                        'repository' => 'Repository (assigned by institution)',
                        'creator' => 'Creator (given by artist)',
                        'inscribed' => 'Inscribed (found on work)',
                        'popular' => 'Popular (commonly known)',
                        'descriptive' => 'Descriptive (based on subject)',
                        'former' => 'Former (previously used)',
                        'translated' => 'Translated',
                        'series' => 'Series title'
                    ],
                    'default' => 'repository'
                ],
                'title_language' => [
                    'label' => 'Title Language',
                    'atomField' => 'language',
                    'dbColumn' => 'title_language',
                    'helpText' => 'Language of the title (ISO 639-2 code).',
                    'required' => false,
                    'vocabulary' => 'iso639_2',
                    'repeatable' => false,
                    'ccoRef' => '3.1.2',
                    'default' => 'eng'
                ],
                'alternate_titles' => [
                    'label' => 'Alternate Titles',
                    'atomField' => 'alternateTitle',
                    'dbColumn' => 'alternate_titles',
                    'helpText' => 'Other titles by which the work is known. Include former titles, translations, and variant spellings.',
                    'required' => false,
                    'vocabulary' => null,
                    'repeatable' => true,
                    'ccoRef' => '3.2'
                ]
            ]
        ];
    }

    /**
     * Creation fields (CCO Chapter 4)
     */
    public static function getCreationFields()
    {
        return [
            'label' => 'Creation',
            'ccoChapter' => 4,
            'description' => 'Information about who created the work, when, and where.',
            'fields' => [
                'creator_display' => [
                    'label' => 'Creator (Display)',
                    'atomField' => null,
                    'dbColumn' => 'creator_display',
                    'helpText' => 'Creator name as it should appear in displays and labels. Format: Surname, Forename (Nationality, birth-death).',
                    'longHelp' => 'This is the display version of the creator\'s name. It may include life dates, nationality, and attribution qualifiers. Example: "Picasso, Pablo (Spanish, 1881-1973)" or "Attributed to Rembrandt van Rijn (Dutch, 1606-1669)"',
                    'required' => true,
                    'vocabulary' => null,
                    'repeatable' => false,
                    'ccoRef' => '4.1'
                ],
                'creator' => [
                    'label' => 'Creator (Authority)',
                    'atomField' => 'creators',
                    'dbColumn' => 'creator_id',
                    'helpText' => 'Link to authority record. Search ULAN or local authority file.',
                    'longHelp' => 'Link this work to an authority record for the creator. This enables browsing all works by this creator and provides biographical information. Use ULAN IDs when available.',
                    'required' => true,
                    'vocabulary' => 'ulan',
                    'repeatable' => true,
                    'ccoRef' => '4.1',
                    'linkedEntity' => 'QubitActor',
                    'spectrumEquiv' => 'Production person'
                ],
                'creator_role' => [
                    'label' => 'Creator Role',
                    'atomField' => null,
                    'dbColumn' => 'creator_role',
                    'helpText' => 'The role of each creator. Use AAT role terms.',
                    'required' => true,
                    'vocabulary' => 'aat_creator_roles',
                    'repeatable' => true,
                    'ccoRef' => '4.1.1',
                    'options' => [
                        'artist' => 'Artist',
                        'painter' => 'Painter',
                        'sculptor' => 'Sculptor',
                        'photographer' => 'Photographer',
                        'printmaker' => 'Printmaker',
                        'architect' => 'Architect',
                        'designer' => 'Designer',
                        'weaver' => 'Weaver',
                        'potter' => 'Potter/Ceramicist',
                        'metalsmith' => 'Metalsmith',
                        'jeweler' => 'Jeweler',
                        'engraver' => 'Engraver',
                        'illuminator' => 'Illuminator',
                        'calligrapher' => 'Calligrapher',
                        'draughtsman' => 'Draughtsman',
                        'publisher' => 'Publisher',
                        'printer' => 'Printer',
                        'manufacturer' => 'Manufacturer'
                    ]
                ],
                'attribution_qualifier' => [
                    'label' => 'Attribution Qualifier',
                    'atomField' => null,
                    'dbColumn' => 'attribution_qualifier',
                    'helpText' => 'Qualifies degree of certainty about attribution.',
                    'longHelp' => 'Use when attribution is uncertain: "attributed to" (probably by), "workshop of" (made in artist\'s workshop), "circle of" (contemporary follower), "school of" (trained by or influenced by), "manner of" (in the style of, but later), "after" (copy of known work), "copy after" (known to be a copy)',
                    'required' => false,
                    'vocabulary' => 'cco_attribution',
                    'repeatable' => false,
                    'ccoRef' => '4.1.2',
                    'options' => [
                        '' => '(No qualifier - certain attribution)',
                        'attributed_to' => 'Attributed to',
                        'workshop_of' => 'Workshop of',
                        'studio_of' => 'Studio of',
                        'circle_of' => 'Circle of',
                        'school_of' => 'School of',
                        'follower_of' => 'Follower of',
                        'manner_of' => 'Manner of',
                        'after' => 'After',
                        'copy_after' => 'Copy after',
                        'imitator_of' => 'Imitator of'
                    ]
                ],
                'creation_date_display' => [
                    'label' => 'Date (Display)',
                    'atomField' => 'eventDates',
                    'dbColumn' => 'creation_date_display',
                    'helpText' => 'Date as it should appear in displays. Include qualifiers like "ca.", "before", "after".',
                    'longHelp' => 'Enter the date in human-readable format. Use standard abbreviations: ca. (circa/approximately), bef. (before), aft. (after). Examples: "1889", "ca. 1650", "15th century", "1920-1925", "before 1750"',
                    'required' => true,
                    'vocabulary' => null,
                    'repeatable' => false,
                    'ccoRef' => '4.2',
                    'examples' => ['1889', 'ca. 1650', '15th century', '1920-1925', 'ca. 1900-1910']
                ],
                'creation_date_earliest' => [
                    'label' => 'Earliest Date',
                    'atomField' => 'eventStartDates',
                    'dbColumn' => 'creation_date_earliest',
                    'helpText' => 'Earliest possible creation date (YYYY or YYYY-MM-DD). Used for searching.',
                    'required' => false,
                    'vocabulary' => null,
                    'repeatable' => false,
                    'ccoRef' => '4.2.1',
                    'dataType' => 'date'
                ],
                'creation_date_latest' => [
                    'label' => 'Latest Date',
                    'atomField' => 'eventEndDates',
                    'dbColumn' => 'creation_date_latest',
                    'helpText' => 'Latest possible creation date (YYYY or YYYY-MM-DD). For certain dates, same as earliest.',
                    'required' => false,
                    'vocabulary' => null,
                    'repeatable' => false,
                    'ccoRef' => '4.2.2',
                    'dataType' => 'date'
                ],
                'creation_place' => [
                    'label' => 'Place of Creation',
                    'atomField' => 'placeAccessPoints',
                    'dbColumn' => 'creation_place',
                    'helpText' => 'Geographic location where work was created. Use TGN for standardized names.',
                    'required' => false,
                    'vocabulary' => 'tgn',
                    'repeatable' => true,
                    'ccoRef' => '4.3',
                    'spectrumEquiv' => 'Production place'
                ],
                'culture' => [
                    'label' => 'Culture/People',
                    'atomField' => null,
                    'dbColumn' => 'culture',
                    'helpText' => 'Cultural, ethnic, or national group. Important when creator is unknown.',
                    'required' => false,
                    'vocabulary' => 'aat_cultures',
                    'repeatable' => true,
                    'ccoRef' => '4.4',
                    'examples' => ['Zulu', 'Ndebele', 'San', 'Dutch', 'French', 'Italian Renaissance']
                ]
            ]
        ];
    }

    /**
     * Style/Period fields (CCO Chapter 5)
     */
    public static function getStylePeriodFields()
    {
        return [
            'label' => 'Styles/Periods',
            'ccoChapter' => 5,
            'description' => 'Style, period, group, school, or movement.',
            'fields' => [
                'style' => [
                    'label' => 'Style',
                    'atomField' => 'genreAccessPoints',
                    'dbColumn' => 'style',
                    'helpText' => 'Artistic style or movement. Use AAT style terms.',
                    'required' => false,
                    'vocabulary' => 'aat_styles',
                    'repeatable' => true,
                    'ccoRef' => '5.1',
                    'examples' => ['Impressionist', 'Art Deco', 'Baroque', 'Abstract Expressionist', 'Fauvism']
                ],
                'period' => [
                    'label' => 'Period',
                    'atomField' => null,
                    'dbColumn' => 'period',
                    'helpText' => 'Historical or stylistic period.',
                    'required' => false,
                    'vocabulary' => 'aat_periods',
                    'repeatable' => true,
                    'ccoRef' => '5.2',
                    'examples' => ['Renaissance', 'Victorian era', 'Apartheid era', 'Colonial period']
                ],
                'school_group' => [
                    'label' => 'School/Group',
                    'atomField' => null,
                    'dbColumn' => 'school_group',
                    'helpText' => 'Specific artistic school, group, or workshop.',
                    'required' => false,
                    'vocabulary' => null,
                    'repeatable' => true,
                    'ccoRef' => '5.3',
                    'examples' => ['Bauhaus', 'Pre-Raphaelites', 'New Group (South Africa)']
                ]
            ]
        ];
    }

    /**
     * Measurements fields (CCO Chapter 6)
     */
    public static function getMeasurementFields()
    {
        return [
            'label' => 'Measurements',
            'ccoChapter' => 6,
            'description' => 'Physical dimensions and other measurements.',
            'fields' => [
                'dimensions_display' => [
                    'label' => 'Dimensions (Display)',
                    'atomField' => 'extentAndMedium',
                    'dbColumn' => 'dimensions_display',
                    'helpText' => 'Dimensions as displayed. Format: H × W × D (unit). Always specify unit.',
                    'longHelp' => 'For 2D works: height × width. For 3D: height × width × depth. Include frame dimensions separately if relevant. Examples: "73.7 × 92.1 cm", "45 × 30 × 15 cm (with base)"',
                    'required' => true,
                    'vocabulary' => null,
                    'repeatable' => false,
                    'ccoRef' => '6.1',
                    'spectrumEquiv' => 'Dimension'
                ],
                'height_value' => [
                    'label' => 'Height',
                    'atomField' => null,
                    'dbColumn' => 'height_value',
                    'helpText' => 'Height in centimeters (numeric only).',
                    'required' => false,
                    'dataType' => 'decimal',
                    'unit' => 'cm',
                    'ccoRef' => '6.2'
                ],
                'width_value' => [
                    'label' => 'Width',
                    'atomField' => null,
                    'dbColumn' => 'width_value',
                    'helpText' => 'Width in centimeters (numeric only).',
                    'required' => false,
                    'dataType' => 'decimal',
                    'unit' => 'cm',
                    'ccoRef' => '6.2'
                ],
                'depth_value' => [
                    'label' => 'Depth',
                    'atomField' => null,
                    'dbColumn' => 'depth_value',
                    'helpText' => 'Depth in centimeters (numeric only). For 3D objects.',
                    'required' => false,
                    'dataType' => 'decimal',
                    'unit' => 'cm',
                    'ccoRef' => '6.2'
                ],
                'weight_value' => [
                    'label' => 'Weight',
                    'atomField' => null,
                    'dbColumn' => 'weight_value',
                    'helpText' => 'Weight in kilograms (numeric only). For sculptures and heavy objects.',
                    'required' => false,
                    'dataType' => 'decimal',
                    'unit' => 'kg',
                    'ccoRef' => '6.3'
                ],
                'dimension_notes' => [
                    'label' => 'Dimension Notes',
                    'atomField' => null,
                    'dbColumn' => 'dimension_notes',
                    'helpText' => 'Additional notes: measured with/without frame, irregular dimensions, etc.',
                    'required' => false,
                    'repeatable' => false,
                    'ccoRef' => '6.4'
                ]
            ]
        ];
    }

    /**
     * Materials/Techniques fields (CCO Chapter 7)
     */
    public static function getMaterialsTechniquesFields()
    {
        return [
            'label' => 'Materials/Techniques',
            'ccoChapter' => 7,
            'description' => 'Physical materials and techniques used to create the work.',
            'fields' => [
                'materials_display' => [
                    'label' => 'Medium (Display)',
                    'atomField' => 'extentAndMedium',
                    'dbColumn' => 'materials_display',
                    'helpText' => 'Materials/medium as displayed. Examples: "oil on canvas", "bronze", "gelatin silver print".',
                    'longHelp' => 'Describe the materials and support in the order: medium on support. For prints: printing technique (medium) on paper type. Be specific when possible.',
                    'required' => true,
                    'vocabulary' => null,
                    'repeatable' => false,
                    'ccoRef' => '7.1',
                    'examples' => ['oil on canvas', 'bronze', 'gelatin silver print', 'watercolor on paper', 'carved wood with polychrome']
                ],
                'materials' => [
                    'label' => 'Materials (Indexed)',
                    'atomField' => 'subjectAccessPoints',
                    'dbColumn' => 'materials',
                    'helpText' => 'Individual materials for indexing. Use AAT material terms.',
                    'required' => false,
                    'vocabulary' => 'aat_materials',
                    'repeatable' => true,
                    'ccoRef' => '7.1.1',
                    'examples' => ['oil paint', 'canvas', 'bronze', 'marble', 'gold leaf', 'paper']
                ],
                'techniques' => [
                    'label' => 'Techniques',
                    'atomField' => null,
                    'dbColumn' => 'techniques',
                    'helpText' => 'Production techniques. Use AAT technique terms.',
                    'required' => false,
                    'vocabulary' => 'aat_techniques',
                    'repeatable' => true,
                    'ccoRef' => '7.2',
                    'examples' => ['casting', 'carving', 'engraving', 'weaving', 'throwing (pottery)']
                ],
                'support' => [
                    'label' => 'Support',
                    'atomField' => null,
                    'dbColumn' => 'support',
                    'helpText' => 'The base material on which the work is executed.',
                    'required' => false,
                    'vocabulary' => 'aat_supports',
                    'repeatable' => true,
                    'ccoRef' => '7.3',
                    'examples' => ['canvas', 'panel', 'paper', 'vellum', 'board']
                ]
            ]
        ];
    }

    /**
     * Subject Matter fields (CCO Chapter 8)
     */
    public static function getSubjectFields()
    {
        return [
            'label' => 'Subject Matter',
            'ccoChapter' => 8,
            'description' => 'What the work represents or depicts.',
            'fields' => [
                'subject_display' => [
                    'label' => 'Subject (Display)',
                    'atomField' => 'scopeAndContent',
                    'dbColumn' => 'subject_display',
                    'helpText' => 'Brief description of what is depicted or represented.',
                    'required' => false,
                    'vocabulary' => null,
                    'repeatable' => false,
                    'ccoRef' => '8.1'
                ],
                'subjects_depicted' => [
                    'label' => 'Subjects Depicted',
                    'atomField' => 'subjectAccessPoints',
                    'dbColumn' => 'subjects_depicted',
                    'helpText' => 'Subjects shown in the work. Use controlled vocabulary.',
                    'required' => false,
                    'vocabulary' => 'aat_subjects',
                    'repeatable' => true,
                    'ccoRef' => '8.2',
                    'examples' => ['landscapes', 'portraits', 'still lifes', 'religious subjects', 'historical events']
                ],
                'iconography' => [
                    'label' => 'Iconography',
                    'atomField' => null,
                    'dbColumn' => 'iconography',
                    'helpText' => 'Iconographic subjects (religious, mythological, allegorical themes).',
                    'required' => false,
                    'vocabulary' => 'iconclass',
                    'repeatable' => true,
                    'ccoRef' => '8.3'
                ],
                'named_subjects' => [
                    'label' => 'Named Subjects',
                    'atomField' => 'nameAccessPoints',
                    'dbColumn' => 'named_subjects',
                    'helpText' => 'Specific people, places, or events depicted.',
                    'required' => false,
                    'vocabulary' => null,
                    'repeatable' => true,
                    'ccoRef' => '8.4'
                ]
            ]
        ];
    }

    // Continue with remaining categories...
    public static function getInscriptionFields()
    {
        return [
            'label' => 'Inscriptions',
            'ccoChapter' => 9,
            'description' => 'Marks, inscriptions, and signatures on the work.',
            'fields' => [
                'inscriptions' => [
                    'label' => 'Inscriptions',
                    'atomField' => null,
                    'dbColumn' => 'inscriptions',
                    'helpText' => 'Any writing, marks, or labels on the work. Include location and transcription.',
                    'longHelp' => 'Record signatures, dates, dedications, labels, stamps, and marks. Format: "[text]" [location] [method]. Example: "Signed and dated lower right: Picasso 1937"',
                    'required' => false,
                    'repeatable' => true,
                    'ccoRef' => '9.1'
                ],
                'signature' => [
                    'label' => 'Signature',
                    'atomField' => null,
                    'dbColumn' => 'signature',
                    'helpText' => 'Artist signature details.',
                    'required' => false,
                    'repeatable' => false,
                    'ccoRef' => '9.2',
                    'examples' => ['Signed lower right', 'Monogrammed "JHP" lower left', 'Unsigned']
                ],
                'marks' => [
                    'label' => 'Marks/Labels',
                    'atomField' => null,
                    'dbColumn' => 'marks',
                    'helpText' => 'Collector marks, gallery labels, stamps.',
                    'required' => false,
                    'repeatable' => true,
                    'ccoRef' => '9.3'
                ]
            ]
        ];
    }

    public static function getStateEditionFields()
    {
        return [
            'label' => 'State/Edition',
            'ccoChapter' => 10,
            'description' => 'For prints, photographs, and multiples.',
            'fields' => [
                'edition_number' => [
                    'label' => 'Edition Number',
                    'atomField' => null,
                    'dbColumn' => 'edition_number',
                    'helpText' => 'The specific number within an edition. Format: X/Y (e.g., "5/100").',
                    'required' => false,
                    'repeatable' => false,
                    'ccoRef' => '10.1'
                ],
                'edition_size' => [
                    'label' => 'Edition Size',
                    'atomField' => null,
                    'dbColumn' => 'edition_size',
                    'helpText' => 'Total size of the edition.',
                    'required' => false,
                    'dataType' => 'integer',
                    'ccoRef' => '10.2'
                ],
                'state' => [
                    'label' => 'State',
                    'atomField' => null,
                    'dbColumn' => 'state',
                    'helpText' => 'For prints: which state (version) of the plate.',
                    'required' => false,
                    'ccoRef' => '10.3'
                ],
                'impression_quality' => [
                    'label' => 'Impression Quality',
                    'atomField' => null,
                    'dbColumn' => 'impression_quality',
                    'helpText' => 'Quality of the impression (for prints).',
                    'required' => false,
                    'options' => ['excellent', 'very good', 'good', 'fair', 'poor'],
                    'ccoRef' => '10.4'
                ]
            ]
        ];
    }

    public static function getDescriptionFields()
    {
        return [
            'label' => 'Description',
            'ccoChapter' => 11,
            'description' => 'Descriptive text about the work.',
            'fields' => [
                'description' => [
                    'label' => 'Description',
                    'atomField' => 'scopeAndContent',
                    'dbColumn' => 'description',
                    'helpText' => 'A narrative description of the work including visual elements and significance.',
                    'required' => false,
                    'repeatable' => false,
                    'ccoRef' => '11.1'
                ],
                'physical_description' => [
                    'label' => 'Physical Description',
                    'atomField' => 'physicalCharacteristics',
                    'dbColumn' => 'physical_description',
                    'helpText' => 'Detailed physical characteristics not covered elsewhere.',
                    'required' => false,
                    'repeatable' => false,
                    'ccoRef' => '11.2'
                ]
            ]
        ];
    }

    public static function getConditionFields()
    {
        return [
            'label' => 'Condition',
            'ccoChapter' => 12,
            'description' => 'Current physical condition.',
            'fields' => [
                'condition_summary' => [
                    'label' => 'Condition Summary',
                    'atomField' => null,
                    'dbColumn' => 'condition_summary',
                    'helpText' => 'Brief overall condition assessment.',
                    'required' => false,
                    'options' => ['excellent', 'good', 'fair', 'poor', 'needs conservation'],
                    'ccoRef' => '12.1',
                    'spectrumEquiv' => 'Condition'
                ],
                'condition_notes' => [
                    'label' => 'Condition Notes',
                    'atomField' => null,
                    'dbColumn' => 'condition_notes',
                    'helpText' => 'Detailed condition description. Link to full condition reports.',
                    'required' => false,
                    'ccoRef' => '12.2'
                ]
            ]
        ];
    }

    public static function getLocationFields()
    {
        return [
            'label' => 'Current Location',
            'ccoChapter' => 13,
            'description' => 'Where the work is currently held.',
            'fields' => [
                'repository' => [
                    'label' => 'Repository',
                    'atomField' => 'repository',
                    'dbColumn' => 'repository_id',
                    'helpText' => 'The institution that holds the work.',
                    'required' => true,
                    'linkedEntity' => 'QubitRepository',
                    'ccoRef' => '13.1'
                ],
                'location_within_repository' => [
                    'label' => 'Location',
                    'atomField' => 'physicalObjectLocation',
                    'dbColumn' => 'location',
                    'helpText' => 'Specific location: gallery, storage area, shelf.',
                    'required' => false,
                    'ccoRef' => '13.2',
                    'spectrumEquiv' => 'Current location'
                ],
                'credit_line' => [
                    'label' => 'Credit Line',
                    'atomField' => null,
                    'dbColumn' => 'credit_line',
                    'helpText' => 'Standard credit text for publications and labels.',
                    'required' => false,
                    'ccoRef' => '13.3',
                    'examples' => ['Gift of John Smith, 2020', 'Purchased with funds from the Heritage Foundation']
                ]
            ]
        ];
    }

    public static function getRelatedWorksFields()
    {
        return [
            'label' => 'Related Works',
            'ccoChapter' => 14,
            'description' => 'Relationships to other works.',
            'fields' => [
                'related_works' => [
                    'label' => 'Related Works',
                    'atomField' => 'relatedUnitsOfDescription',
                    'dbColumn' => 'related_works',
                    'helpText' => 'Other works related to this one (studies, copies, pairs, series).',
                    'required' => false,
                    'repeatable' => true,
                    'ccoRef' => '14.1'
                ],
                'relationship_type' => [
                    'label' => 'Relationship Type',
                    'atomField' => null,
                    'dbColumn' => 'relationship_type',
                    'helpText' => 'Nature of the relationship.',
                    'required' => false,
                    'options' => [
                        'study_for' => 'Study for',
                        'copy_of' => 'Copy of',
                        'copy_after' => 'Copy after',
                        'pendant_to' => 'Pendant to',
                        'part_of' => 'Part of (series)',
                        'variant_of' => 'Variant of',
                        'model_for' => 'Model for',
                        'related_to' => 'Related to'
                    ],
                    'ccoRef' => '14.2'
                ]
            ]
        ];
    }

    public static function getRightsFields()
    {
        return [
            'label' => 'Rights',
            'ccoChapter' => 15,
            'description' => 'Rights and reproduction information.',
            'fields' => [
                'rights_statement' => [
                    'label' => 'Rights Statement',
                    'atomField' => 'accessConditions',
                    'dbColumn' => 'rights_statement',
                    'helpText' => 'Copyright status and usage rights.',
                    'required' => false,
                    'ccoRef' => '15.1'
                ],
                'copyright_holder' => [
                    'label' => 'Copyright Holder',
                    'atomField' => null,
                    'dbColumn' => 'copyright_holder',
                    'helpText' => 'Current copyright owner.',
                    'required' => false,
                    'ccoRef' => '15.2'
                ],
                'reproduction_conditions' => [
                    'label' => 'Reproduction Conditions',
                    'atomField' => 'reproductionConditions',
                    'dbColumn' => 'reproduction_conditions',
                    'helpText' => 'Terms for reproducing images of the work.',
                    'required' => false,
                    'ccoRef' => '15.3'
                ]
            ]
        ];
    }
}