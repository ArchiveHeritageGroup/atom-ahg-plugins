<?php

class apiActions extends sfActions
{
    public function executeSearchInformationObjects(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');
        
        $query = trim($request->getParameter('q', ''));
        $limit = min((int)$request->getParameter('limit', 20), 50);
        
        if (strlen($query) < 2) {
            return $this->renderText(json_encode(['results' => [], 'error' => 'Query too short']));
        }
        
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        
        $results = \Illuminate\Database\Capsule\Manager::table('information_object')
            ->join('information_object_i18n', function($join) {
                $join->on('information_object.id', '=', 'information_object_i18n.id')
                     ->where('information_object_i18n.culture', '=', 'en');
            })
            ->leftJoin('slug', 'information_object.id', '=', 'slug.object_id')
            ->leftJoin('term_i18n as lod', function($join) {
                $join->on('information_object.level_of_description_id', '=', 'lod.id')
                     ->where('lod.culture', '=', 'en');
            })
            ->leftJoin('repository', 'information_object.repository_id', '=', 'repository.id')
            ->leftJoin('actor_i18n as repo_name', function($join) {
                $join->on('repository.id', '=', 'repo_name.id')
                     ->where('repo_name.culture', '=', 'en');
            })
            ->where(function($q) use ($query) {
                $q->where('information_object_i18n.title', 'LIKE', "%{$query}%")
                  ->orWhere('information_object.identifier', 'LIKE', "%{$query}%");
            })
            ->where('information_object.id', '!=', 1) // Exclude root
            ->select([
                'information_object.id',
                'information_object.identifier',
                'information_object_i18n.title',
                'slug.slug',
                'lod.name as level_of_description',
                'repo_name.authorized_form_of_name as repository'
            ])
            ->orderBy('information_object_i18n.title')
            ->limit($limit)
            ->get();
        
        return $this->renderText(json_encode([
            'results' => $results->toArray(),
            'count' => $results->count()
        ]));
    }
    
    public function executeAutocompleteGlam(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');
        
        $query = trim($request->getParameter('q', ''));
        $limit = min((int)$request->getParameter('limit', 10), 20);
        
        if (strlen($query) < 2) {
            return $this->renderText(json_encode([]));
        }
        
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        
        $results = \Illuminate\Database\Capsule\Manager::table('information_object')
            ->join('information_object_i18n', function($join) {
                $join->on('information_object.id', '=', 'information_object_i18n.id')
                     ->where('information_object_i18n.culture', '=', 'en');
            })
            ->leftJoin('slug', 'information_object.id', '=', 'slug.object_id')
            ->leftJoin('term_i18n as lod', function($join) {
                $join->on('information_object.level_of_description_id', '=', 'lod.id')
                     ->where('lod.culture', '=', 'en');
            })
            ->where(function($q) use ($query) {
                $q->where('information_object_i18n.title', 'LIKE', "%{$query}%")
                  ->orWhere('information_object.identifier', 'LIKE', "%{$query}%");
            })
            ->where('information_object.id', '!=', 1)
            ->select([
                'information_object.id',
                'information_object.identifier',
                'information_object_i18n.title',
                'slug.slug',
                'lod.name as level_of_description'
            ])
            ->orderBy('information_object_i18n.title')
            ->limit($limit)
            ->get();
        
        // Format for autocomplete
        $formatted = [];
        foreach ($results as $row) {
            $formatted[] = [
                'id' => $row->id,
                'value' => $row->title,
                'label' => $row->title . ($row->identifier ? ' [' . $row->identifier . ']' : ''),
                'identifier' => $row->identifier,
                'slug' => $row->slug,
                'level' => $row->level_of_description
            ];
        }
        
        return $this->renderText(json_encode($formatted));
    }
}
