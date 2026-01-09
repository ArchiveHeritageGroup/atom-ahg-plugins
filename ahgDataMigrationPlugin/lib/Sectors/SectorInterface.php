<?php
namespace ahgDataMigrationPlugin\Sectors;

interface SectorInterface
{
    public function getCode(): string;
    public function getName(): string;
    public function getFields(): array;
    public function getTemplate(): string;
    public function getIcon(): string;
}
