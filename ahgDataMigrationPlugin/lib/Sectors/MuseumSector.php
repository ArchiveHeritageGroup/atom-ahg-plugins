<?php
namespace ahgDataMigrationPlugin\Sectors;

class MuseumSector extends BaseSector
{
    protected string $code = 'museum';
    protected string $name = 'Museum (SPECTRUM)';
    protected string $template = 'spectrum';
    protected string $icon = 'bank';
    protected array $fields = [
        'identifier' => ['type' => 'string', 'required' => true],
        'culture' => ['type' => 'string', 'default' => 'en'],
        'title' => ['type' => 'string', 'required' => true],
        'object_name' => ['type' => 'string'],
        'object_type' => ['type' => 'string'],
        'description' => ['type' => 'text'],
        'maker' => ['type' => 'string'],
        'production_date' => ['type' => 'date'],
        'production_place' => ['type' => 'string'],
        'materials' => ['type' => 'text'],
        'techniques' => ['type' => 'text'],
        'dimensions' => ['type' => 'text'],
        'inscription' => ['type' => 'text'],
        'condition' => ['type' => 'text'],
        'provenance' => ['type' => 'text'],
        'acquisition_date' => ['type' => 'date'],
        'acquisition_method' => ['type' => 'string'],
        'location' => ['type' => 'string'],
    ];
}
