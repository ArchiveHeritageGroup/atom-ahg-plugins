<?php
namespace AhgMigration\Sectors;

class GallerySector extends AbstractSector
{
    public function getId(): string { return 'gallery'; }
    public function getName(): string { return 'Gallery'; }
    public function getDescription(): string { return 'Art and visual resources following CCO/VRA standards'; }
    public function getPlugin(): ?string { return 'ahgGalleryPlugin'; }
    public function getStandard(): string { return 'CCO/VRA Core'; }
    
    public function getFields(): array
    {
        if (empty($this->fields)) {
            $this->fields = [
                // Work Identification
                'identifier' => [
                    'label' => 'Accession Number',
                    'type' => 'string',
                    'required' => true,
                    'group' => 'identification'
                ],
                'title' => [
                    'label' => 'Title',
                    'type' => 'string',
                    'required' => true,
                    'group' => 'identification'
                ],
                'alternativeTitle' => [
                    'label' => 'Alternative Title',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'identification'
                ],
                'workType' => [
                    'label' => 'Work Type',
                    'type' => 'taxonomy',
                    'taxonomy' => 'work_type',
                    'required' => false,
                    'group' => 'identification'
                ],
                
                // Creator
                'creators' => [
                    'label' => 'Artist / Creator',
                    'type' => 'actor',
                    'required' => false,
                    'group' => 'creator',
                    'multiple' => true
                ],
                'creatorRole' => [
                    'label' => 'Creator Role',
                    'type' => 'taxonomy',
                    'taxonomy' => 'creator_role',
                    'required' => false,
                    'group' => 'creator'
                ],
                'attributionQualifier' => [
                    'label' => 'Attribution',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'creator',
                    'note' => 'e.g., attributed to, studio of, circle of'
                ],
                'culture' => [
                    'label' => 'Culture',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'creator'
                ],
                
                // Date
                'eventDates' => [
                    'label' => 'Date',
                    'type' => 'object',
                    'required' => false,
                    'group' => 'date',
                    'children' => [
                        'description' => ['label' => 'Date Display', 'type' => 'string'],
                        'startDate' => ['label' => 'Earliest Date', 'type' => 'date'],
                        'endDate' => ['label' => 'Latest Date', 'type' => 'date']
                    ]
                ],
                
                // Physical Description
                'medium' => [
                    'label' => 'Medium / Materials',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'physical'
                ],
                'support' => [
                    'label' => 'Support',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'physical'
                ],
                'technique' => [
                    'label' => 'Technique',
                    'type' => 'terms',
                    'taxonomy' => 'technique',
                    'required' => false,
                    'group' => 'physical',
                    'multiple' => true
                ],
                'dimensions' => [
                    'label' => 'Dimensions Display',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'physical'
                ],
                'dimensionHeight' => [
                    'label' => 'Height',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'physical'
                ],
                'dimensionWidth' => [
                    'label' => 'Width',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'physical'
                ],
                'editionNumber' => [
                    'label' => 'Edition',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'physical'
                ],
                'inscriptions' => [
                    'label' => 'Inscriptions / Marks',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'physical'
                ],
                
                // Subject
                'scopeAndContent' => [
                    'label' => 'Description',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'subject'
                ],
                'subjectAccessPoints' => [
                    'label' => 'Subject',
                    'type' => 'terms',
                    'taxonomy' => 'subject',
                    'required' => false,
                    'group' => 'subject',
                    'multiple' => true
                ],
                'stylePeriod' => [
                    'label' => 'Style / Period',
                    'type' => 'terms',
                    'taxonomy' => 'style_period',
                    'required' => false,
                    'group' => 'subject',
                    'multiple' => true
                ],
                
                // Provenance & History
                'provenance' => [
                    'label' => 'Provenance',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'history'
                ],
                'exhibitionHistory' => [
                    'label' => 'Exhibition History',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'history'
                ],
                'publicationHistory' => [
                    'label' => 'Publication History',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'history'
                ],
                
                // Rights
                'copyright' => [
                    'label' => 'Copyright',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'rights'
                ],
                'creditLine' => [
                    'label' => 'Credit Line',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'rights'
                ],
                'accessConditions' => [
                    'label' => 'Access Restrictions',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'rights'
                ],
                
                // Location
                'physicalStorage' => [
                    'label' => 'Current Location',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'location'
                ],
                
                // Digital
                'digitalObject' => [
                    'label' => 'Digital Image',
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
                ],
                'generalNote' => [
                    'label' => 'Notes',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'admin'
                ]
            ];
        }
        return $this->fields;
    }
    
    public function getFieldGroups(): array
    {
        return [
            'identification' => ['label' => 'Work Identification'],
            'creator' => ['label' => 'Creator Information'],
            'date' => ['label' => 'Date'],
            'physical' => ['label' => 'Physical Description'],
            'subject' => ['label' => 'Subject & Style'],
            'history' => ['label' => 'History & Provenance'],
            'rights' => ['label' => 'Rights'],
            'location' => ['label' => 'Location'],
            'digital' => ['label' => 'Digital Image'],
            'admin' => ['label' => 'Administration']
        ];
    }
    
    public function getLevels(): array
    {
        return ['Collection', 'Series', 'Item'];
    }
}
