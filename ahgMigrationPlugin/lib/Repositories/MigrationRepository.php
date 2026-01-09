<?php
namespace AhgMigration\Repositories;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;

class MigrationRepository
{
    // =========================================================================
    // MIGRATION JOBS
    // =========================================================================
    
    public function createJob(array $data): int
    {
        return DB::table('atom_migration_job')->insertGetId([
            'name' => $data['name'] ?? 'Migration ' . date('Y-m-d H:i'),
            'source_system' => $data['source_system'],
            'source_format' => $data['source_format'],
            'source_file' => $data['source_file'] ?? null,
            'source_file_hash' => $data['source_file_hash'] ?? null,
            'source_headers' => json_encode($data['source_headers'] ?? []),
            'destination_sector' => $data['destination_sector'],
            'destination_repository_id' => $data['destination_repository_id'] ?? null,
            'destination_parent_id' => $data['destination_parent_id'] ?? null,
            'template_id' => $data['template_id'] ?? null,
            'field_mappings' => json_encode($data['field_mappings'] ?? []),
            'transformations' => json_encode($data['transformations'] ?? []),
            'default_values' => json_encode($data['default_values'] ?? []),
            'output_mode' => $data['output_mode'] ?? 'direct',
            'import_options' => json_encode($data['import_options'] ?? []),
            'status' => 'pending',
            'created_by' => $data['created_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function getJob(int $id): ?object
    {
        $job = DB::table('atom_migration_job')->where('id', $id)->first();
        if ($job) {
            $job->source_headers = json_decode($job->source_headers, true) ?: [];
            $job->field_mappings = json_decode($job->field_mappings, true) ?: [];
            $job->transformations = json_decode($job->transformations, true) ?: [];
            $job->default_values = json_decode($job->default_values, true) ?: [];
            $job->import_options = json_decode($job->import_options, true) ?: [];
            $job->validation_errors = json_decode($job->validation_errors, true) ?: [];
        }
        return $job;
    }
    
    public function updateJob(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // JSON encode array fields
        $jsonFields = ['source_headers', 'field_mappings', 'transformations', 
                       'default_values', 'import_options', 'validation_errors'];
        foreach ($jsonFields as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = json_encode($data[$field]);
            }
        }
        
        return DB::table('atom_migration_job')->where('id', $id)->update($data) > 0;
    }
    
    public function deleteJob(int $id): bool
    {
        return DB::table('atom_migration_job')->where('id', $id)->delete() > 0;
    }
    
    public function getJobs(array $filters = [], int $limit = 50, int $offset = 0): Collection
    {
        $query = DB::table('atom_migration_job');
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['source_system'])) {
            $query->where('source_system', $filters['source_system']);
        }
        if (!empty($filters['destination_sector'])) {
            $query->where('destination_sector', $filters['destination_sector']);
        }
        if (!empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }
        
        return $query->orderBy('created_at', 'desc')
                     ->limit($limit)
                     ->offset($offset)
                     ->get();
    }
    
    public function incrementCounter(int $jobId, string $counter, int $amount = 1): void
    {
        DB::table('atom_migration_job')
            ->where('id', $jobId)
            ->increment($counter, $amount);
    }

    // =========================================================================
    // MIGRATION TEMPLATES
    // =========================================================================
    
    public function getTemplates(?string $sourceSystem = null, ?string $sector = null): Collection
    {
        $query = DB::table('atom_migration_template')->where('is_enabled', 1);
        
        if ($sourceSystem) {
            $query->where('source_system', $sourceSystem);
        }
        if ($sector) {
            $query->where('destination_sector', $sector);
        }
        
        return $query->orderBy('is_system', 'desc')
                     ->orderBy('usage_count', 'desc')
                     ->orderBy('name')
                     ->get()
                     ->map(fn($t) => $this->decodeTemplate($t));
    }
    
    public function getTemplate(int $id): ?object
    {
        $t = DB::table('atom_migration_template')->where('id', $id)->first();
        return $t ? $this->decodeTemplate($t) : null;
    }
    
    public function getTemplateBySlug(string $slug): ?object
    {
        $t = DB::table('atom_migration_template')->where('slug', $slug)->first();
        return $t ? $this->decodeTemplate($t) : null;
    }
    
    public function findTemplate(string $sourceSystem, string $sector): ?object
    {
        $t = DB::table('atom_migration_template')
            ->where('source_system', $sourceSystem)
            ->where('destination_sector', $sector)
            ->where('is_enabled', 1)
            ->orderBy('is_system', 'desc')
            ->orderBy('usage_count', 'desc')
            ->first();
        return $t ? $this->decodeTemplate($t) : null;
    }
    
    public function saveTemplate(array $data): int
    {
        $record = [
            'name' => $data['name'],
            'slug' => $data['slug'] ?? $this->generateSlug($data['name']),
            'description' => $data['description'] ?? null,
            'source_system' => $data['source_system'],
            'source_format' => $data['source_format'],
            'destination_sector' => $data['destination_sector'],
            'field_mappings' => json_encode($data['field_mappings']),
            'transformations' => json_encode($data['transformations'] ?? []),
            'hierarchy_config' => json_encode($data['hierarchy_config'] ?? null),
            'default_values' => json_encode($data['default_values'] ?? []),
            'is_system' => $data['is_system'] ?? 0,
            'is_enabled' => 1,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if (!empty($data['id'])) {
            // Don't allow editing system templates
            $existing = $this->getTemplate($data['id']);
            if ($existing && $existing->is_system) {
                throw new \RuntimeException('Cannot modify system templates');
            }
            DB::table('atom_migration_template')->where('id', $data['id'])->update($record);
            return $data['id'];
        }
        
        $record['created_at'] = date('Y-m-d H:i:s');
        $record['created_by'] = $data['created_by'] ?? null;
        return DB::table('atom_migration_template')->insertGetId($record);
    }
    
    public function deleteTemplate(int $id): bool
    {
        // Don't allow deleting system templates
        $t = $this->getTemplate($id);
        if ($t && $t->is_system) {
            throw new \RuntimeException('Cannot delete system templates');
        }
        return DB::table('atom_migration_template')->where('id', $id)->delete() > 0;
    }
    
    public function incrementTemplateUsage(int $id): void
    {
        DB::table('atom_migration_template')->where('id', $id)->increment('usage_count');
    }
    
    protected function decodeTemplate(object $t): object
    {
        $t->field_mappings = json_decode($t->field_mappings, true) ?: [];
        $t->transformations = json_decode($t->transformations, true) ?: [];
        $t->hierarchy_config = json_decode($t->hierarchy_config, true);
        $t->default_values = json_decode($t->default_values, true) ?: [];
        return $t;
    }

    // =========================================================================
    // MIGRATION LOG
    // =========================================================================
    
    public function logRecord(array $data): int
    {
        return DB::table('atom_migration_log')->insertGetId([
            'job_id' => $data['job_id'],
            'row_number' => $data['row_number'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'record_type' => $data['record_type'],
            'atom_object_id' => $data['atom_object_id'] ?? null,
            'atom_slug' => $data['atom_slug'] ?? null,
            'action' => $data['action'],
            'parent_source_id' => $data['parent_source_id'] ?? null,
            'source_data' => json_encode($data['source_data'] ?? null),
            'mapped_data' => json_encode($data['mapped_data'] ?? null),
            'error_message' => $data['error_message'] ?? null,
            'warning_message' => $data['warning_message'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function logBatch(array $records): int
    {
        $rows = array_map(function($data) {
            return [
                'job_id' => $data['job_id'],
                'row_number' => $data['row_number'] ?? null,
                'source_id' => $data['source_id'] ?? null,
                'record_type' => $data['record_type'],
                'atom_object_id' => $data['atom_object_id'] ?? null,
                'atom_slug' => $data['atom_slug'] ?? null,
                'action' => $data['action'],
                'parent_source_id' => $data['parent_source_id'] ?? null,
                'source_data' => json_encode($data['source_data'] ?? null),
                'mapped_data' => json_encode($data['mapped_data'] ?? null),
                'error_message' => $data['error_message'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }, $records);
        
        return DB::table('atom_migration_log')->insert($rows) ? count($rows) : 0;
    }
    
    public function getJobLogs(int $jobId, ?string $action = null, int $limit = 100, int $offset = 0): Collection
    {
        $query = DB::table('atom_migration_log')->where('job_id', $jobId);
        
        if ($action) {
            $query->where('action', $action);
        }
        
        return $query->orderBy('row_number')
                     ->limit($limit)
                     ->offset($offset)
                     ->get()
                     ->map(function($r) {
                         $r->source_data = json_decode($r->source_data, true);
                         $r->mapped_data = json_decode($r->mapped_data, true);
                         return $r;
                     });
    }
    
    public function getLogStats(int $jobId): array
    {
        return [
            'created' => DB::table('atom_migration_log')
                ->where('job_id', $jobId)->where('action', 'created')->count(),
            'updated' => DB::table('atom_migration_log')
                ->where('job_id', $jobId)->where('action', 'updated')->count(),
            'skipped' => DB::table('atom_migration_log')
                ->where('job_id', $jobId)->where('action', 'skipped')->count(),
            'error' => DB::table('atom_migration_log')
                ->where('job_id', $jobId)->where('action', 'error')->count(),
        ];
    }
    
    public function getImportedObjectIds(int $jobId, string $recordType = 'information_object'): array
    {
        return DB::table('atom_migration_log')
            ->where('job_id', $jobId)
            ->where('record_type', $recordType)
            ->where('action', 'created')
            ->whereNotNull('atom_object_id')
            ->pluck('atom_object_id')
            ->toArray();
    }

    // =========================================================================
    // STAGED RECORDS (Preview/Validation)
    // =========================================================================
    
    public function stageRecord(array $data): int
    {
        return DB::table('atom_migration_staged')->insertGetId([
            'job_id' => $data['job_id'],
            'row_number' => $data['row_number'],
            'source_id' => $data['source_id'] ?? null,
            'record_type' => $data['record_type'] ?? 'information_object',
            'parent_source_id' => $data['parent_source_id'] ?? null,
            'hierarchy_level' => $data['hierarchy_level'] ?? 0,
            'sort_order' => $data['sort_order'] ?? $data['row_number'],
            'source_data' => json_encode($data['source_data']),
            'mapped_data' => isset($data['mapped_data']) ? json_encode($data['mapped_data']) : null,
            'validation_status' => $data['validation_status'] ?? 'pending',
            'validation_messages' => json_encode($data['validation_messages'] ?? []),
            'import_status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function stageBatch(array $records): int
    {
        $rows = array_map(function($data) {
            return [
                'job_id' => $data['job_id'],
                'row_number' => $data['row_number'],
                'source_id' => $data['source_id'] ?? null,
                'record_type' => $data['record_type'] ?? 'information_object',
                'parent_source_id' => $data['parent_source_id'] ?? null,
                'hierarchy_level' => $data['hierarchy_level'] ?? 0,
                'sort_order' => $data['sort_order'] ?? $data['row_number'],
                'source_data' => json_encode($data['source_data']),
                'mapped_data' => isset($data['mapped_data']) ? json_encode($data['mapped_data']) : null,
                'validation_status' => $data['validation_status'] ?? 'pending',
                'validation_messages' => json_encode($data['validation_messages'] ?? []),
                'import_status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ];
        }, $records);
        
        return DB::table('atom_migration_staged')->insert($rows) ? count($rows) : 0;
    }
    
    public function getStagedRecords(int $jobId, int $limit = 100, int $offset = 0): Collection
    {
        return DB::table('atom_migration_staged')
            ->where('job_id', $jobId)
            ->orderBy('hierarchy_level')
            ->orderBy('sort_order')
            ->orderBy('row_number')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(function($r) {
                $r->source_data = json_decode($r->source_data, true);
                $r->mapped_data = json_decode($r->mapped_data, true);
                $r->validation_messages = json_decode($r->validation_messages, true);
                return $r;
            });
    }
    
    public function getStagedPreview(int $jobId, int $limit = 10): Collection
    {
        return $this->getStagedRecords($jobId, $limit, 0);
    }
    
    public function updateStagedRecord(int $id, array $data): bool
    {
        $update = [];
        
        if (isset($data['mapped_data'])) {
            $update['mapped_data'] = json_encode($data['mapped_data']);
        }
        if (isset($data['validation_status'])) {
            $update['validation_status'] = $data['validation_status'];
        }
        if (isset($data['validation_messages'])) {
            $update['validation_messages'] = json_encode($data['validation_messages']);
        }
        if (isset($data['import_status'])) {
            $update['import_status'] = $data['import_status'];
        }
        
        return !empty($update) && 
               DB::table('atom_migration_staged')->where('id', $id)->update($update) > 0;
    }
    
    public function getStagedStats(int $jobId): array
    {
        return [
            'total' => DB::table('atom_migration_staged')
                ->where('job_id', $jobId)->count(),
            'valid' => DB::table('atom_migration_staged')
                ->where('job_id', $jobId)->where('validation_status', 'valid')->count(),
            'warning' => DB::table('atom_migration_staged')
                ->where('job_id', $jobId)->where('validation_status', 'warning')->count(),
            'error' => DB::table('atom_migration_staged')
                ->where('job_id', $jobId)->where('validation_status', 'error')->count(),
            'pending' => DB::table('atom_migration_staged')
                ->where('job_id', $jobId)->where('validation_status', 'pending')->count(),
        ];
    }
    
    public function clearStagedRecords(int $jobId): int
    {
        return DB::table('atom_migration_staged')->where('job_id', $jobId)->delete();
    }
    
    public function getPendingStaged(int $jobId, int $limit = 100): Collection
    {
        return DB::table('atom_migration_staged')
            ->where('job_id', $jobId)
            ->where('import_status', 'pending')
            ->whereIn('validation_status', ['valid', 'warning'])
            ->orderBy('hierarchy_level')
            ->orderBy('sort_order')
            ->limit($limit)
            ->get()
            ->map(function($r) {
                $r->source_data = json_decode($r->source_data, true);
                $r->mapped_data = json_decode($r->mapped_data, true);
                return $r;
            });
    }

    // =========================================================================
    // HELPERS
    // =========================================================================
    
    protected function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Ensure uniqueness
        $baseSlug = $slug;
        $counter = 1;
        while (DB::table('atom_migration_template')->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
}
