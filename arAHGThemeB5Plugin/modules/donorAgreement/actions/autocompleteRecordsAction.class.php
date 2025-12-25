<?php

class donorAgreementAutocompleteRecordsAction extends sfAction
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');
        
        $query = $request->getParameter('query', '');
        if (strlen($query) < 2) {
            return $this->renderText(json_encode([]));
        }

        // Initialize Laravel DB
        $this->initDatabase();

        $results = \Illuminate\Database\Capsule\Manager::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
            ->where(function($q) use ($query) {
                $q->where('ioi.title', 'like', '%' . $query . '%')
                  ->orWhere('io.identifier', 'like', '%' . $query . '%');
            })
            ->whereNotNull('io.identifier')
            ->select(['io.id', 'io.identifier', 'ioi.title', 's.slug'])
            ->orderBy('ioi.title')
            ->limit(20)
            ->get();

        $data = [];
        foreach ($results as $row) {
            $data[] = [
                'id' => $row->id,
                'identifier' => $row->identifier,
                'title' => $row->title ?: $row->identifier,
                'slug' => $row->slug,
            ];
        }

        return $this->renderText(json_encode($data));
    }

    protected function initDatabase()
    {
        static $initialized = false;
        if ($initialized) return;

        require_once sfConfig::get('sf_root_dir') . '/atom-framework/vendor/autoload.php';
        $config = include(sfConfig::get('sf_root_dir') . '/config/config.php');
        $dsn = $config['all']['propel']['param']['dsn'];
        preg_match('/dbname=([^;]+)/', $dsn, $matches);
        $dbname = $matches[1] ?? 'archive';
        preg_match('/host=([^;]+)/', $dsn, $hostMatches);
        $host = $hostMatches[1] ?? 'localhost';

        $capsule = new \Illuminate\Database\Capsule\Manager();
        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => $host,
            'database' => $dbname,
            'username' => $config['all']['propel']['param']['username'],
            'password' => $config['all']['propel']['param']['password'],
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);
        $capsule->setAsGlobal();
        $initialized = true;
    }
}
