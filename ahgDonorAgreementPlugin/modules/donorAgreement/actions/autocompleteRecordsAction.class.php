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
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
    }
}
