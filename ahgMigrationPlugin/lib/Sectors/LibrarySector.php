<?php
namespace AhgMigration\Sectors;

class LibrarySector extends AbstractSector
{
    public function getId(): string { return 'library'; }
    public function getName(): string { return 'Library'; }
    public function getDescription(): string { return 'Bibliographic records following MARC/RDA standards'; }
    public function getPlugin(): ?string { return 'ahgLibraryPlugin'; }
    public function getStandard(): string { return 'MARC/RDA'; }
    
    public function getFields(): array
    {
        if (empty($this->fields)) {
            $this->fields = [
                // Title & Statement
                'identifier' => [
                    'label' => 'Call Number',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'identification',
                    'marc' => '050/090'
                ],
                'isbn' => [
                    'label' => 'ISBN',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'identification',
                    'marc' => '020'
                ],
                'issn' => [
                    'label' => 'ISSN',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'identification',
                    'marc' => '022'
                ],
                'title' => [
                    'label' => 'Title',
                    'type' => 'string',
                    'required' => true,
                    'group' => 'title',
                    'marc' => '245$a'
                ],
                'alternativeTitle' => [
                    'label' => 'Subtitle',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'title',
                    'marc' => '245$b'
                ],
                'uniformTitle' => [
                    'label' => 'Uniform Title',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'title',
                    'marc' => '240'
                ],
                'seriesTitle' => [
                    'label' => 'Series Title',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'title',
                    'marc' => '490'
                ],
                
                // Responsibility
                'creators' => [
                    'label' => 'Author / Creator',
                    'type' => 'actor',
                    'required' => false,
                    'group' => 'responsibility',
                    'multiple' => true,
                    'marc' => '100/110/111'
                ],
                'contributors' => [
                    'label' => 'Contributors',
                    'type' => 'actor',
                    'required' => false,
                    'group' => 'responsibility',
                    'multiple' => true,
                    'marc' => '700/710/711'
                ],
                'statementOfResponsibility' => [
                    'label' => 'Statement of Responsibility',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'responsibility',
                    'marc' => '245$c'
                ],
                
                // Publication
                'publisher' => [
                    'label' => 'Publisher',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'publication',
                    'marc' => '264$b'
                ],
                'placeOfPublication' => [
                    'label' => 'Place of Publication',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'publication',
                    'marc' => '264$a'
                ],
                'eventDates' => [
                    'label' => 'Publication Date',
                    'type' => 'object',
                    'required' => false,
                    'group' => 'publication',
                    'marc' => '264$c',
                    'children' => [
                        'description' => ['label' => 'Date Text', 'type' => 'string'],
                        'startDate' => ['label' => 'Date', 'type' => 'date']
                    ]
                ],
                'edition' => [
                    'label' => 'Edition',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'publication',
                    'marc' => '250'
                ],
                
                // Physical Description
                'extentAndMedium' => [
                    'label' => 'Extent (Pages)',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'physical',
                    'marc' => '300$a'
                ],
                'physicalDetails' => [
                    'label' => 'Physical Details',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'physical',
                    'marc' => '300$b'
                ],
                'dimensions' => [
                    'label' => 'Dimensions',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'physical',
                    'marc' => '300$c'
                ],
                
                // Notes
                'scopeAndContent' => [
                    'label' => 'Summary / Abstract',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'notes',
                    'marc' => '520'
                ],
                'tableOfContents' => [
                    'label' => 'Table of Contents',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'notes',
                    'marc' => '505'
                ],
                'generalNote' => [
                    'label' => 'General Note',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'notes',
                    'marc' => '500'
                ],
                
                // Subject Access
                'subjectAccessPoints' => [
                    'label' => 'Subjects',
                    'type' => 'terms',
                    'taxonomy' => 'subject',
                    'required' => false,
                    'group' => 'subjects',
                    'multiple' => true,
                    'marc' => '650'
                ],
                'genreAccessPoints' => [
                    'label' => 'Genre/Form',
                    'type' => 'terms',
                    'taxonomy' => 'genre',
                    'required' => false,
                    'group' => 'subjects',
                    'multiple' => true,
                    'marc' => '655'
                ],
                'placeAccessPoints' => [
                    'label' => 'Geographic Subject',
                    'type' => 'terms',
                    'taxonomy' => 'place',
                    'required' => false,
                    'group' => 'subjects',
                    'multiple' => true,
                    'marc' => '651'
                ],
                
                // Classification
                'deweyClassification' => [
                    'label' => 'Dewey Classification',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'classification',
                    'marc' => '082'
                ],
                'lcClassification' => [
                    'label' => 'LC Classification',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'classification',
                    'marc' => '050'
                ],
                
                // Language
                'language' => [
                    'label' => 'Language',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'description',
                    'marc' => '041/008'
                ],
                
                // Location
                'physicalStorage' => [
                    'label' => 'Location / Shelf',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'holdings'
                ],
                'barcode' => [
                    'label' => 'Barcode',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'holdings'
                ],
                'copyNumber' => [
                    'label' => 'Copy Number',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'holdings'
                ],
                
                // Digital
                'digitalObject' => [
                    'label' => 'Digital Object',
                    'type' => 'object',
                    'required' => false,
                    'group' => 'digital',
                    'children' => [
                        'path' => ['label' => 'File Path', 'type' => 'string'],
                        'uri' => ['label' => 'URL', 'type' => 'string']
                    ]
                ],
                
                // Admin
                'levelOfDescription' => [
                    'label' => 'Record Type',
                    'type' => 'taxonomy',
                    'required' => false,
                    'group' => 'admin',
                    'default' => 'Item'
                ]
            ];
        }
        return $this->fields;
    }
    
    public function getFieldGroups(): array
    {
        return [
            'identification' => ['label' => 'Identification Numbers'],
            'title' => ['label' => 'Title Information'],
            'responsibility' => ['label' => 'Creator & Responsibility'],
            'publication' => ['label' => 'Publication Information'],
            'physical' => ['label' => 'Physical Description'],
            'notes' => ['label' => 'Notes'],
            'subjects' => ['label' => 'Subjects'],
            'classification' => ['label' => 'Classification'],
            'description' => ['label' => 'Description'],
            'holdings' => ['label' => 'Holdings Information'],
            'digital' => ['label' => 'Digital Object'],
            'admin' => ['label' => 'Administration']
        ];
    }
    
    public function getLevels(): array
    {
        return ['Collection', 'Series', 'Item'];
    }
}
