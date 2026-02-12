<?php

namespace AtomFramework\Console\Commands\Security;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

class UpdateCacheCommand extends BaseCommand
{
    protected string $name = 'security:update-cache';
    protected string $description = 'Update security classification cache for Cantaloupe watermarks';
    protected string $detailedDescription = <<<'EOF'
    Updates the security classification cache file used by Cantaloupe
    for applying watermarks to classified digital objects.

    The cache is written to /tmp/cantaloupe_classifications.json.

    Examples:
      php bin/atom security:update-cache
    EOF;

    protected function handle(): int
    {
        $cache = [];

        // Get all classified digital objects with their path hashes
        $results = DB::table('digital_object as do')
            ->join('object_security_classification as osc', 'do.object_id', '=', 'osc.object_id')
            ->join('security_classification as sc', 'osc.classification_id', '=', 'sc.id')
            ->where('osc.active', 1)
            ->select('do.path', 'sc.level', 'sc.code')
            ->get();

        foreach ($results as $row) {
            // Extract hash from path
            $parts = explode('/', $row->path);
            if (count($parts) >= 2) {
                $hash = $parts[count($parts) - 2];
                $cache[$hash] = [
                    'level' => $row->level,
                    'code' => $row->code,
                ];
            }
        }

        file_put_contents('/tmp/cantaloupe_classifications.json', json_encode($cache));
        chmod('/tmp/cantaloupe_classifications.json', 0644);

        $this->success(sprintf('Updated cache with %d classified objects', count($cache)));

        return 0;
    }
}
