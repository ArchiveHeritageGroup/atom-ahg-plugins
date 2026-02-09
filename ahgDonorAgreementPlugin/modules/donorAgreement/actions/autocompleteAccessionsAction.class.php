<?php

class donorAgreementAutocompleteAccessionsAction extends sfAction
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

        $results = \Illuminate\Database\Capsule\Manager::table('accession as acc')
            ->leftJoin('accession_i18n as acci', function($join) {
                $join->on('acc.id', '=', 'acci.id')->where('acci.culture', '=', 'en');
            })
            ->where(function($q) use ($query) {
                $q->where('acci.title', 'like', '%' . $query . '%')
                  ->orWhere('acc.identifier', 'like', '%' . $query . '%');
            })
            ->select(['acc.id', 'acc.identifier', 'acci.title'])
            ->orderBy('acc.identifier')
            ->limit(20)
            ->get();

        $data = [];
        foreach ($results as $row) {
            $data[] = [
                'id' => $row->id,
                'identifier' => $row->identifier,
                'title' => $row->title ?: $row->identifier,
            ];
        }

        return $this->renderText(json_encode($data));
    }

    protected function initDatabase()
    {
        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
    }
}
