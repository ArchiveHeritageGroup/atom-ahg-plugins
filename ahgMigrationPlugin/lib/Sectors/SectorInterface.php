<?php
namespace AhgMigration\Sectors;

interface SectorInterface
{
    /**
     * Get sector identifier
     */
    public function getId(): string;
    
    /**
     * Get sector display name
     */
    public function getName(): string;
    
    /**
     * Get sector description
     */
    public function getDescription(): string;
    
    /**
     * Get associated plugin (if any)
     */
    public function getPlugin(): ?string;
    
    /**
     * Get standard/schema this sector follows
     */
    public function getStandard(): string;
    
    /**
     * Get all available fields with metadata
     */
    public function getFields(): array;
    
    /**
     * Get required fields
     */
    public function getRequiredFields(): array;
    
    /**
     * Get field groups for UI organization
     */
    public function getFieldGroups(): array;
    
    /**
     * Get hierarchy levels available for this sector
     */
    public function getLevels(): array;
    
    /**
     * Validate mapped data against sector requirements
     */
    public function validate(array $data): array;
}
