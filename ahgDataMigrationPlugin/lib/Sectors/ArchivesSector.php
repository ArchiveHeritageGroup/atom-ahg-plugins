<?php
namespace ahgDataMigrationPlugin\Sectors;

class ArchivesSector extends BaseSector
{
    protected string $code = 'archive';
    protected string $name = 'Archives (ISAD-G)';
    protected string $template = 'isad';
    protected string $icon = 'archive';
    protected array $fields = [
        'identifier' => ['type' => 'string', 'required' => true],
        'title' => ['type' => 'string', 'required' => true],
        'level_of_description' => ['type' => 'string'],
        'extent_and_medium' => ['type' => 'text'],
        'scope_and_content' => ['type' => 'text'],
        'arrangement' => ['type' => 'text'],
        'archival_history' => ['type' => 'text'],
        'acquisition' => ['type' => 'text'],
        'access_conditions' => ['type' => 'text'],
        'reproduction_conditions' => ['type' => 'text'],
        'physical_characteristics' => ['type' => 'text'],
        'finding_aids' => ['type' => 'text'],
        'location_of_originals' => ['type' => 'text'],
        'location_of_copies' => ['type' => 'text'],
        'related_units_of_description' => ['type' => 'text'],
        'rules' => ['type' => 'text'],
        'sources' => ['type' => 'text'],
        'revision_history' => ['type' => 'text'],
    ];
}
