<?php
namespace ahgDataMigrationPlugin\Sectors;

class GallerySector extends BaseSector
{
    protected string $code = 'gallery';
    protected string $name = 'Gallery (CCO)';
    protected string $template = 'cco';
    protected string $icon = 'easel';
    protected array $fields = [
        'identifier' => ['type' => 'string', 'required' => true],
        'title' => ['type' => 'string', 'required' => true],
        'creator' => ['type' => 'string'],
        'creation_date' => ['type' => 'date'],
        'work_type' => ['type' => 'string'],
        'medium' => ['type' => 'text'],
        'dimensions' => ['type' => 'text'],
        'style_period' => ['type' => 'string'],
        'subject' => ['type' => 'text'],
        'provenance' => ['type' => 'text'],
        'exhibition_history' => ['type' => 'text'],
        'condition' => ['type' => 'text'],
        'inscriptions' => ['type' => 'text'],
        'credit_line' => ['type' => 'string'],
    ];
}
