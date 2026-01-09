<?php
namespace AhgMigration\Sectors;

class ArchivesSector extends AbstractSector
{
    public function getId(): string { return 'archives'; }
    public function getName(): string { return 'Archives'; }
    public function getDescription(): string { return 'Archival descriptions following ISAD(G) standard'; }
    public function getPlugin(): ?string { return null; } // Core AtoM
    public function getStandard(): string { return 'ISAD(G)'; }
    
    public function getFields(): array
    {
        if (empty($this->fields)) {
            $this->fields = [
                // Identity Area (ISAD 3.1)
                'identifier' => [
                    'label' => 'Reference Code / Identifier',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'identity',
                    'isadField' => '3.1.1'
                ],
                'title' => [
                    'label' => 'Title',
                    'type' => 'string',
                    'required' => true,
                    'group' => 'identity',
                    'isadField' => '3.1.2'
                ],
                'levelOfDescription' => [
                    'label' => 'Level of Description',
                    'type' => 'taxonomy',
                    'taxonomy' => 'level_of_description',
                    'required' => false,
                    'group' => 'identity',
                    'isadField' => '3.1.4'
                ],
                'extentAndMedium' => [
                    'label' => 'Extent and Medium',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'identity',
                    'isadField' => '3.1.5'
                ],
                'alternativeTitle' => [
                    'label' => 'Alternative Title',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'identity'
                ],
                
                // Date fields (ISAD 3.1.3)
                'eventDates' => [
                    'label' => 'Date(s)',
                    'type' => 'object',
                    'required' => false,
                    'group' => 'identity',
                    'isadField' => '3.1.3',
                    'children' => [
                        'description' => ['label' => 'Date Expression', 'type' => 'string'],
                        'startDate' => ['label' => 'Start Date', 'type' => 'date'],
                        'endDate' => ['label' => 'End Date', 'type' => 'date'],
                        'type' => ['label' => 'Date Type', 'type' => 'taxonomy']
                    ]
                ],
                
                // Context Area (ISAD 3.2)
                'creators' => [
                    'label' => 'Creator(s)',
                    'type' => 'actor',
                    'required' => false,
                    'group' => 'context',
                    'isadField' => '3.2.1',
                    'multiple' => true
                ],
                'repository' => [
                    'label' => 'Repository',
                    'type' => 'repository',
                    'required' => false,
                    'group' => 'context'
                ],
                'archivalHistory' => [
                    'label' => 'Archival History',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'context',
                    'isadField' => '3.2.3'
                ],
                'acquisition' => [
                    'label' => 'Immediate Source of Acquisition',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'context',
                    'isadField' => '3.2.4'
                ],
                
                // Content Area (ISAD 3.3)
                'scopeAndContent' => [
                    'label' => 'Scope and Content',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'content',
                    'isadField' => '3.3.1'
                ],
                'appraisal' => [
                    'label' => 'Appraisal, Destruction and Scheduling',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'content',
                    'isadField' => '3.3.2'
                ],
                'accruals' => [
                    'label' => 'Accruals',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'content',
                    'isadField' => '3.3.3'
                ],
                'arrangement' => [
                    'label' => 'System of Arrangement',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'content',
                    'isadField' => '3.3.4'
                ],
                
                // Conditions Area (ISAD 3.4)
                'accessConditions' => [
                    'label' => 'Conditions Governing Access',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'conditions',
                    'isadField' => '3.4.1'
                ],
                'reproductionConditions' => [
                    'label' => 'Conditions Governing Reproduction',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'conditions',
                    'isadField' => '3.4.2'
                ],
                'language' => [
                    'label' => 'Language of Material',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'conditions',
                    'isadField' => '3.4.3'
                ],
                'script' => [
                    'label' => 'Script of Material',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'conditions',
                    'isadField' => '3.4.3'
                ],
                'physicalCharacteristics' => [
                    'label' => 'Physical Characteristics',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'conditions',
                    'isadField' => '3.4.4'
                ],
                'findingAids' => [
                    'label' => 'Finding Aids',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'conditions',
                    'isadField' => '3.4.5'
                ],
                
                // Allied Materials Area (ISAD 3.5)
                'locationOfOriginals' => [
                    'label' => 'Existence and Location of Originals',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'allied',
                    'isadField' => '3.5.1'
                ],
                'locationOfCopies' => [
                    'label' => 'Existence and Location of Copies',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'allied',
                    'isadField' => '3.5.2'
                ],
                'relatedUnitsOfDescription' => [
                    'label' => 'Related Units of Description',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'allied',
                    'isadField' => '3.5.3'
                ],
                'publicationNote' => [
                    'label' => 'Publication Note',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'allied',
                    'isadField' => '3.5.4'
                ],
                
                // Notes Area (ISAD 3.6)
                'generalNote' => [
                    'label' => 'General Note',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'notes',
                    'isadField' => '3.6.1'
                ],
                
                // Control Area (ISAD 3.7)
                'archivistNote' => [
                    'label' => "Archivist's Note",
                    'type' => 'text',
                    'required' => false,
                    'group' => 'control',
                    'isadField' => '3.7.1'
                ],
                'rules' => [
                    'label' => 'Rules or Conventions',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'control',
                    'isadField' => '3.7.2'
                ],
                
                // Access Points
                'subjectAccessPoints' => [
                    'label' => 'Subject Access Points',
                    'type' => 'terms',
                    'taxonomy' => 'subject',
                    'required' => false,
                    'group' => 'access_points',
                    'multiple' => true
                ],
                'placeAccessPoints' => [
                    'label' => 'Place Access Points',
                    'type' => 'terms',
                    'taxonomy' => 'place',
                    'required' => false,
                    'group' => 'access_points',
                    'multiple' => true
                ],
                'nameAccessPoints' => [
                    'label' => 'Name Access Points',
                    'type' => 'terms',
                    'taxonomy' => 'name',
                    'required' => false,
                    'group' => 'access_points',
                    'multiple' => true
                ],
                'genreAccessPoints' => [
                    'label' => 'Genre Access Points',
                    'type' => 'terms',
                    'taxonomy' => 'genre',
                    'required' => false,
                    'group' => 'access_points',
                    'multiple' => true
                ],
                
                // Physical Storage
                'physicalStorage' => [
                    'label' => 'Physical Storage',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'admin'
                ],
                
                // Digital Object
                'digitalObject' => [
                    'label' => 'Digital Object',
                    'type' => 'object',
                    'required' => false,
                    'group' => 'digital',
                    'children' => [
                        'path' => ['label' => 'File Path', 'type' => 'string'],
                        'uri' => ['label' => 'External URI', 'type' => 'string']
                    ]
                ],
                
                // Hierarchy (internal)
                '_parentIdentifier' => [
                    'label' => 'Parent Identifier',
                    'type' => 'reference',
                    'required' => false,
                    'group' => 'hierarchy',
                    'internal' => true
                ],
                '_parentSlug' => [
                    'label' => 'Parent Slug',
                    'type' => 'reference',
                    'required' => false,
                    'group' => 'hierarchy',
                    'internal' => true
                ]
            ];
        }
        return $this->fields;
    }
    
    public function getFieldGroups(): array
    {
        return [
            'identity' => ['label' => 'Identity Area', 'isad' => '3.1'],
            'context' => ['label' => 'Context Area', 'isad' => '3.2'],
            'content' => ['label' => 'Content and Structure Area', 'isad' => '3.3'],
            'conditions' => ['label' => 'Conditions of Access and Use Area', 'isad' => '3.4'],
            'allied' => ['label' => 'Allied Materials Area', 'isad' => '3.5'],
            'notes' => ['label' => 'Notes Area', 'isad' => '3.6'],
            'control' => ['label' => 'Description Control Area', 'isad' => '3.7'],
            'access_points' => ['label' => 'Access Points'],
            'admin' => ['label' => 'Administration'],
            'digital' => ['label' => 'Digital Object'],
            'hierarchy' => ['label' => 'Hierarchy']
        ];
    }
    
    public function getLevels(): array
    {
        return [
            'Fonds', 'Sub-fonds', 'Collection', 'Record group', 'Sub-group',
            'Series', 'Sub-series', 'File', 'Item', 'Part', 'Class'
        ];
    }
}
