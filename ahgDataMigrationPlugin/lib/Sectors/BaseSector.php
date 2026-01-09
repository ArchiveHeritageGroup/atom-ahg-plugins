<?php
namespace ahgDataMigrationPlugin\Sectors;

abstract class BaseSector implements SectorInterface
{
    protected string $code = '';
    protected string $name = '';
    protected string $template = '';
    protected string $icon = 'folder';
    protected array $fields = [];

    public function getCode(): string { return $this->code; }
    public function getName(): string { return $this->name; }
    public function getFields(): array { return $this->fields; }
    public function getTemplate(): string { return $this->template; }
    public function getIcon(): string { return $this->icon; }
}
