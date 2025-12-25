<?php

class securityUpdateCacheTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
        ]);

        $this->namespace = 'security';
        $this->name = 'update-cache';
        $this->briefDescription = 'Update security classification cache for Cantaloupe watermarks';
    }

    protected function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);
        
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';
        
        $cache = [];
        
        // Get all classified digital objects with their path hashes
        $results = \Illuminate\Database\Capsule\Manager::table('digital_object as do')
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
                    'code' => $row->code
                ];
            }
        }
        
        file_put_contents('/tmp/cantaloupe_classifications.json', json_encode($cache));
        chmod('/tmp/cantaloupe_classifications.json', 0644);
        
        $this->logSection('security', sprintf('Updated cache with %d classified objects', count($cache)));
    }
}
