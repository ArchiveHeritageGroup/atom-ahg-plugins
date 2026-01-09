<?php
namespace AhgMigration\Sectors;

class MuseumSector extends AbstractSector
{
    public function getId(): string { return 'museum'; }
    public function getName(): string { return 'Museum'; }
    public function getDescription(): string { return 'Museum object records following SPECTRUM 5.0 standard'; }
    public function getPlugin(): ?string { return 'ahgMuseumPlugin'; }
    public function getStandard(): string { return 'SPECTRUM 5.0'; }
    
    public function getFields(): array
    {
        if (empty($this->fields)) {
            $this->fields = [
                // Object Identification
                'identifier' => [
                    'label' => 'Object Number',
                    'type' => 'string',
                    'required' => true,
                    'group' => 'identification',
                    'spectrumUnit' => 'Object identification'
                ],
                'title' => [
                    'label' => 'Object Name / Title',
                    'type' => 'string',
                    'required' => true,
                    'group' => 'identification'
                ],
                'alternativeTitle' => [
                    'label' => 'Other Name',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'identification'
                ],
                'objectType' => [
                    'label' => 'Object Type',
                    'type' => 'taxonomy',
                    'taxonomy' => 'object_type',
                    'required' => false,
                    'group' => 'identification'
                ],
                'numberOfObjects' => [
                    'label' => 'Number of Objects',
                    'type' => 'integer',
                    'required' => false,
                    'group' => 'identification'
                ],
                
                // Object Description
                'scopeAndContent' => [
                    'label' => 'Brief Description',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'description'
                ],
                'physicalDescription' => [
                    'label' => 'Physical Description',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'description'
                ],
                'distinguishingFeatures' => [
                    'label' => 'Distinguishing Features',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'description'
                ],
                'inscriptions' => [
                    'label' => 'Inscriptions',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'description'
                ],
                
                // Physical Characteristics
                'materials' => [
                    'label' => 'Materials',
                    'type' => 'terms',
                    'taxonomy' => 'material',
                    'required' => false,
                    'group' => 'physical',
                    'multiple' => true
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
                    'label' => 'Dimensions',
                    'type' => 'text',
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
                'dimensionDepth' => [
                    'label' => 'Depth',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'physical'
                ],
                'dimensionWeight' => [
                    'label' => 'Weight',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'physical'
                ],
                'dimensionUnit' => [
                    'label' => 'Dimension Unit',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'physical'
                ],
                
                // Production
                'creators' => [
                    'label' => 'Maker / Artist',
                    'type' => 'actor',
                    'required' => false,
                    'group' => 'production',
                    'multiple' => true
                ],
                'makerRole' => [
                    'label' => 'Maker Role',
                    'type' => 'taxonomy',
                    'taxonomy' => 'maker_role',
                    'required' => false,
                    'group' => 'production'
                ],
                'eventDates' => [
                    'label' => 'Production Date',
                    'type' => 'object',
                    'required' => false,
                    'group' => 'production',
                    'children' => [
                        'description' => ['label' => 'Date Text', 'type' => 'string'],
                        'startDate' => ['label' => 'Earliest Date', 'type' => 'date'],
                        'endDate' => ['label' => 'Latest Date', 'type' => 'date']
                    ]
                ],
                'productionPlace' => [
                    'label' => 'Place of Production',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'production'
                ],
                
                // Acquisition
                'acquisitionMethod' => [
                    'label' => 'Acquisition Method',
                    'type' => 'taxonomy',
                    'taxonomy' => 'acquisition_method',
                    'required' => false,
                    'group' => 'acquisition'
                ],
                'acquisitionDate' => [
                    'label' => 'Acquisition Date',
                    'type' => 'date',
                    'required' => false,
                    'group' => 'acquisition'
                ],
                'acquisitionSource' => [
                    'label' => 'Acquisition Source',
                    'type' => 'actor',
                    'required' => false,
                    'group' => 'acquisition'
                ],
                'acquisition' => [
                    'label' => 'Acquisition Notes',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'acquisition'
                ],
                'provenance' => [
                    'label' => 'Provenance',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'acquisition'
                ],
                
                // Condition
                'condition' => [
                    'label' => 'Condition',
                    'type' => 'taxonomy',
                    'taxonomy' => 'condition_status',
                    'required' => false,
                    'group' => 'condition'
                ],
                'conditionDate' => [
                    'label' => 'Condition Check Date',
                    'type' => 'date',
                    'required' => false,
                    'group' => 'condition'
                ],
                'conditionNote' => [
                    'label' => 'Condition Notes',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'condition'
                ],
                
                // Location
                'physicalStorage' => [
                    'label' => 'Current Location',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'location'
                ],
                'normalLocation' => [
                    'label' => 'Normal Location',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'location'
                ],
                
                // Rights
                'accessConditions' => [
                    'label' => 'Access Restrictions',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'rights'
                ],
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
                
                // Access Points
                'subjectAccessPoints' => [
                    'label' => 'Subject',
                    'type' => 'terms',
                    'taxonomy' => 'subject',
                    'required' => false,
                    'group' => 'access_points',
                    'multiple' => true
                ],
                'placeAccessPoints' => [
                    'label' => 'Associated Place',
                    'type' => 'terms',
                    'taxonomy' => 'place',
                    'required' => false,
                    'group' => 'access_points',
                    'multiple' => true
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
                
                // Notes
                'generalNote' => [
                    'label' => 'General Notes',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'notes'
                ],
                
                // Internal
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
            'identification' => ['label' => 'Object Identification'],
            'description' => ['label' => 'Object Description'],
            'physical' => ['label' => 'Physical Characteristics'],
            'production' => ['label' => 'Production Information'],
            'acquisition' => ['label' => 'Acquisition & Provenance'],
            'condition' => ['label' => 'Condition'],
            'location' => ['label' => 'Location'],
            'rights' => ['label' => 'Rights & Access'],
            'access_points' => ['label' => 'Subject & Classification'],
            'digital' => ['label' => 'Digital Object'],
            'notes' => ['label' => 'Notes'],
            'admin' => ['label' => 'Administration']
        ];
    }
    
    public function getLevels(): array
    {
        return ['Collection', 'Item', 'Part'];
    }
}
