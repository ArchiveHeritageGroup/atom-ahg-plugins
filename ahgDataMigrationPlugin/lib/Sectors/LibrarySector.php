<?php
namespace ahgDataMigrationPlugin\Sectors;

class LibrarySector extends BaseSector
{
    protected string $code = 'library';
    protected string $name = 'Library (MARC/RDA)';
    protected string $template = 'marc';
    protected string $icon = 'book';
    protected array $fields = [
        'identifier' => ['type' => 'string', 'required' => true],
        'culture' => ['type' => 'string', 'default' => 'en'],
        'title' => ['type' => 'string', 'required' => true],
        'author' => ['type' => 'string'],
        'publisher' => ['type' => 'string'],
        'publication_date' => ['type' => 'date'],
        'publication_place' => ['type' => 'string'],
        'edition' => ['type' => 'string'],
        'extent' => ['type' => 'string'],
        'isbn' => ['type' => 'string'],
        'issn' => ['type' => 'string'],
        'call_number' => ['type' => 'string'],
        'subjects' => ['type' => 'text'],
        'summary' => ['type' => 'text'],
        'notes' => ['type' => 'text'],
        'language' => ['type' => 'string'],
        'series' => ['type' => 'string'],
    ];
}
