<?php
namespace AhgMigration\Sectors;

class DamSector extends AbstractSector
{
    public function getId(): string { return 'dam'; }
    public function getName(): string { return 'Digital Assets (DAM)'; }
    public function getDescription(): string { return 'Digital asset management following Dublin Core'; }
    public function getPlugin(): ?string { return 'ahgDAMPlugin'; }
    public function getStandard(): string { return 'Dublin Core'; }
    
    public function getFields(): array
    {
        if (empty($this->fields)) {
            $this->fields = [
                // DC Core Elements
                'identifier' => [
                    'label' => 'Identifier',
                    'type' => 'string',
                    'required' => true,
                    'group' => 'core',
                    'dc' => 'dc:identifier'
                ],
                'title' => [
                    'label' => 'Title',
                    'type' => 'string',
                    'required' => true,
                    'group' => 'core',
                    'dc' => 'dc:title'
                ],
                'alternativeTitle' => [
                    'label' => 'Alternative Title',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'core',
                    'dc' => 'dcterms:alternative'
                ],
                'creators' => [
                    'label' => 'Creator',
                    'type' => 'actor',
                    'required' => false,
                    'group' => 'core',
                    'multiple' => true,
                    'dc' => 'dc:creator'
                ],
                'contributors' => [
                    'label' => 'Contributor',
                    'type' => 'actor',
                    'required' => false,
                    'group' => 'core',
                    'multiple' => true,
                    'dc' => 'dc:contributor'
                ],
                'publisher' => [
                    'label' => 'Publisher',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'core',
                    'dc' => 'dc:publisher'
                ],
                'eventDates' => [
                    'label' => 'Date',
                    'type' => 'object',
                    'required' => false,
                    'group' => 'core',
                    'dc' => 'dc:date',
                    'children' => [
                        'description' => ['label' => 'Date Text', 'type' => 'string'],
                        'startDate' => ['label' => 'Date', 'type' => 'date']
                    ]
                ],
                'assetType' => [
                    'label' => 'Type',
                    'type' => 'taxonomy',
                    'taxonomy' => 'dc_type',
                    'required' => false,
                    'group' => 'core',
                    'dc' => 'dc:type'
                ],
                'format' => [
                    'label' => 'Format',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'technical',
                    'dc' => 'dc:format'
                ],
                'language' => [
                    'label' => 'Language',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'core',
                    'dc' => 'dc:language'
                ],
                
                // Description
                'scopeAndContent' => [
                    'label' => 'Description',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'description',
                    'dc' => 'dc:description'
                ],
                'abstract' => [
                    'label' => 'Abstract',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'description',
                    'dc' => 'dcterms:abstract'
                ],
                
                // Subject & Classification
                'subjectAccessPoints' => [
                    'label' => 'Subject',
                    'type' => 'terms',
                    'taxonomy' => 'subject',
                    'required' => false,
                    'group' => 'subjects',
                    'multiple' => true,
                    'dc' => 'dc:subject'
                ],
                'placeAccessPoints' => [
                    'label' => 'Coverage (Spatial)',
                    'type' => 'terms',
                    'taxonomy' => 'place',
                    'required' => false,
                    'group' => 'subjects',
                    'multiple' => true,
                    'dc' => 'dc:coverage'
                ],
                'genreAccessPoints' => [
                    'label' => 'Genre',
                    'type' => 'terms',
                    'taxonomy' => 'genre',
                    'required' => false,
                    'group' => 'subjects',
                    'multiple' => true
                ],
                
                // Technical Metadata
                'mimeType' => [
                    'label' => 'MIME Type',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'technical'
                ],
                'fileSize' => [
                    'label' => 'File Size',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'technical'
                ],
                'duration' => [
                    'label' => 'Duration',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'technical'
                ],
                'resolution' => [
                    'label' => 'Resolution',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'technical'
                ],
                'colorSpace' => [
                    'label' => 'Color Space',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'technical'
                ],
                'dimensions' => [
                    'label' => 'Dimensions (pixels)',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'technical'
                ],
                
                // Rights
                'accessConditions' => [
                    'label' => 'Rights',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'rights',
                    'dc' => 'dc:rights'
                ],
                'license' => [
                    'label' => 'License',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'rights',
                    'dc' => 'dcterms:license'
                ],
                'copyright' => [
                    'label' => 'Copyright Statement',
                    'type' => 'text',
                    'required' => false,
                    'group' => 'rights'
                ],
                'copyrightHolder' => [
                    'label' => 'Copyright Holder',
                    'type' => 'actor',
                    'required' => false,
                    'group' => 'rights'
                ],
                
                // Relations
                'source' => [
                    'label' => 'Source',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'relations',
                    'dc' => 'dc:source'
                ],
                'relatedTo' => [
                    'label' => 'Related To',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'relations',
                    'dc' => 'dc:relation'
                ],
                'isPartOf' => [
                    'label' => 'Is Part Of',
                    'type' => 'string',
                    'required' => false,
                    'group' => 'relations',
                    'dc' => 'dcterms:isPartOf'
                ],
                
                // Digital Object (required for DAM)
                'digitalObject' => [
                    'label' => 'Digital File',
                    'type' => 'object',
                    'required' => true,
                    'group' => 'digital',
                    'children' => [
                        'path' => ['label' => 'File Path', 'type' => 'string'],
                        'uri' => ['label' => 'URL', 'type' => 'string'],
                        'checksum' => ['label' => 'Checksum', 'type' => 'string']
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
                ],
                'physicalStorage' => [
                    'label' => 'Storage Location',
                    'type' => 'string',
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
            'core' => ['label' => 'Core Metadata'],
            'description' => ['label' => 'Description'],
            'subjects' => ['label' => 'Subject & Classification'],
            'technical' => ['label' => 'Technical Metadata'],
            'rights' => ['label' => 'Rights & Licensing'],
            'relations' => ['label' => 'Relations'],
            'digital' => ['label' => 'Digital File'],
            'admin' => ['label' => 'Administration']
        ];
    }
    
    public function getLevels(): array
    {
        return ['Collection', 'Item'];
    }
}
