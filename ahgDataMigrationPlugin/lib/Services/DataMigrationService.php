<?php

namespace ahgDataMigrationPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

class DataMigrationService
{
    protected $sourceSector;
    protected $targetSector;
    protected $fieldMapping = [];
    protected $errors = [];
    protected $warnings = [];
    protected $stats = ['total' => 0, 'migrated' => 0, 'skipped' => 0, 'errors' => 0];

    public function init(string $fromSector, string $toSector): self
    {
        require_once __DIR__ . '/../Sectors/SectorFactory.php';
        $factory = 'ahgDataMigrationPlugin\\Sectors\\SectorFactory';
        
        $this->sourceSector = $factory::get($fromSector);
        $this->targetSector = $factory::get($toSector);
        $this->fieldMapping = $this->buildDefaultMapping();
        $this->errors = [];
        $this->warnings = [];
        $this->stats = ['total' => 0, 'migrated' => 0, 'skipped' => 0, 'errors' => 0];
        
        return $this;
    }

    protected function buildDefaultMapping(): array
    {
        $factory = 'ahgDataMigrationPlugin\\Sectors\\SectorFactory';
        $comparison = $factory::compareFields(
            $this->sourceSector->getCode(),
            $this->targetSector->getCode()
        );
        
        $mapping = [];
        foreach ($comparison['common'] as $field) {
            $mapping[$field] = $field;
        }
        
        return $mapping;
    }

    public function setFieldMapping(array $mapping): self
    {
        $this->fieldMapping = array_merge($this->fieldMapping, $mapping);
        return $this;
    }

    public function getFieldMapping(): array
    {
        return $this->fieldMapping;
    }

    public function preview(array $objectIds): array
    {
        $preview = [];
        
        foreach ($objectIds as $objectId) {
            $sourceData = $this->loadSourceData($objectId);
            if (!$sourceData) {
                $preview[$objectId] = ['error' => 'Object not found'];
                continue;
            }
            
            $transformed = $this->transformData($sourceData);
            $validation = $this->validateTransformation($transformed);
            
            $preview[$objectId] = [
                'source' => $sourceData,
                'transformed' => $transformed,
                'validation' => $validation,
            ];
        }
        
        return $preview;
    }

    public function migrate(array $objectIds, bool $dryRun = false): array
    {
        $this->stats['total'] = count($objectIds);
        $results = [];
        
        DB::beginTransaction();
        
        try {
            foreach ($objectIds as $objectId) {
                $result = $this->migrateObject($objectId, $dryRun);
                $results[$objectId] = $result;
                
                if ($result['success']) {
                    $this->stats['migrated']++;
                } elseif ($result['skipped']) {
                    $this->stats['skipped']++;
                } else {
                    $this->stats['errors']++;
                }
            }
            
            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->errors[] = "Migration failed: " . $e->getMessage();
        }
        
        return [
            'stats' => $this->stats,
            'results' => $results,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }

    protected function migrateObject(int $objectId, bool $dryRun): array
    {
        $result = ['success' => false, 'skipped' => false, 'changes' => [], 'errors' => []];
        
        $sourceData = $this->loadSourceData($objectId);
        if (!$sourceData) {
            $result['errors'][] = 'Object not found';
            return $result;
        }
        
        $transformed = $this->transformData($sourceData);
        $validation = $this->validateTransformation($transformed);
        
        if (!$validation['valid']) {
            $result['errors'] = $validation['errors'];
            return $result;
        }
        
        if (!$dryRun) {
            $this->applyChanges($objectId, $transformed, $sourceData);
        }
        
        $result['success'] = true;
        $result['changes'] = $this->calculateChanges($sourceData, $transformed);
        
        return $result;
    }

    protected function loadSourceData(int $objectId): ?array
    {
        $io = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->where('io.id', $objectId)
            ->first();
        
        if (!$io) {
            return null;
        }
        
        return (array) $io;
    }

    protected function transformData(array $sourceData): array
    {
        $transformed = [];
        
        foreach ($this->fieldMapping as $sourceField => $targetField) {
            if (isset($sourceData[$sourceField])) {
                $transformed[$targetField] = $sourceData[$sourceField];
            }
        }
        
        $essentialFields = ['id', 'identifier', 'repository_id', 'parent_id'];
        foreach ($essentialFields as $field) {
            if (isset($sourceData[$field]) && !isset($transformed[$field])) {
                $transformed[$field] = $sourceData[$field];
            }
        }
        
        return $transformed;
    }

    protected function validateTransformation(array $transformed): array
    {
        $errors = [];
        $warnings = [];
        
        if (empty($transformed['title']) && empty($transformed['identifier'])) {
            $warnings[] = 'No title or identifier';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    protected function applyChanges(int $objectId, array $transformed, array $originalData): void
    {
        // Update display_object_config for new sector
        DB::table('display_object_config')
            ->updateOrInsert(
                ['object_id' => $objectId],
                [
                    'object_type' => $this->targetSector->getCode(),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]
            );
        
        // Log migration
        $this->logMigration($objectId, $originalData, $transformed);
    }

    protected function logMigration(int $objectId, array $original, array $transformed): void
    {
        try {
            $userId = null;
            if (class_exists('sfContext') && \sfContext::hasInstance()) {
                $userId = \sfContext::getInstance()->user->getAttribute('user_id');
            }
            
            DB::table('data_migration_log')->insert([
                'object_id' => $objectId,
                'source_sector' => $this->sourceSector->getCode(),
                'target_sector' => $this->targetSector->getCode(),
                'field_mapping' => json_encode($this->fieldMapping),
                'original_data' => json_encode($original),
                'transformed_data' => json_encode($transformed),
                'user_id' => $userId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            $this->warnings[] = 'Could not log migration: ' . $e->getMessage();
        }
    }

    protected function calculateChanges(array $source, array $transformed): array
    {
        $changes = [];
        foreach ($transformed as $field => $value) {
            $sourceValue = $source[$field] ?? null;
            if ($sourceValue !== $value) {
                $changes[$field] = ['from' => $sourceValue, 'to' => $value];
            }
        }
        return $changes;
    }

    public function getErrors(): array { return $this->errors; }
    public function getWarnings(): array { return $this->warnings; }
    public function getStats(): array { return $this->stats; }
}
