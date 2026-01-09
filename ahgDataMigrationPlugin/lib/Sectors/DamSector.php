<?php
namespace ahgDataMigrationPlugin\Sectors;

class DamSector extends BaseSector
{
    protected string $code = 'dam';
    protected string $name = 'Digital Assets (DC)';
    protected string $template = 'dc';
    protected string $icon = 'file-earmark-image';
    protected array $fields = [
        'identifier' => ['type' => 'string', 'required' => true],
        'title' => ['type' => 'string', 'required' => true],
        'dc_creator' => ['type' => 'string'],
        'dc_description' => ['type' => 'text'],
        'dc_date' => ['type' => 'date'],
        'dc_type' => ['type' => 'string'],
        'dc_format' => ['type' => 'string'],
        'dc_source' => ['type' => 'string'],
        'dc_language' => ['type' => 'string'],
        'dc_rights' => ['type' => 'text'],
        'dc_subject' => ['type' => 'text'],
        'file_size' => ['type' => 'integer'],
        'mime_type' => ['type' => 'string'],
        'resolution' => ['type' => 'string'],
    ];
}
