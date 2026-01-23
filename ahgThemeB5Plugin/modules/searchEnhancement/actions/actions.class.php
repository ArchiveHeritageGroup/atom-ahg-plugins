<?php

class searchEnhancementActions extends sfActions
{
    public function executeSaveSearch(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');
        
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Login required']));
        }
        
        $userId = $this->getUser()->getAttribute('user_id');
        $name = $request->getParameter('name');
        $searchParams = $request->getParameter('search_params', '');
        $notify = $request->getParameter('notify', 0);
        $entityType = $request->getParameter('entity_type', 'informationobject');
        
        if (empty($name)) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Name required']));
        }
        
        // Convert query string to JSON
        parse_str($searchParams, $paramsArray);
        $paramsJson = json_encode($paramsArray);
        
        try {
            $id = \Illuminate\Database\Capsule\Manager::table('saved_search')->insertGetId([
                'user_id' => $userId,
                'name' => $name,
                'search_params' => $paramsJson,
                'entity_type' => $entityType,
                'notify_on_new' => $notify ? 1 : 0,
                'notification_frequency' => 'weekly',
                'is_public' => $request->getParameter('is_public', 0) ? 1 : 0,
                'is_global' => ($this->getUser()->isAdministrator() && $request->getParameter('is_global', 0)) ? 1 : 0,
                'is_public' => $request->getParameter('is_public', 0) ? 1 : 0,
                'is_global' => ($this->getUser()->isAdministrator() && $request->getParameter('is_global', 0)) ? 1 : 0,
                'usage_count' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            return $this->renderText(json_encode(['success' => true, 'id' => $id]));
        } catch (Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }
    
    public function executeSavedSearches(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }
        
        $userId = $this->getUser()->getAttribute('user_id');
        $this->savedSearches = \Illuminate\Database\Capsule\Manager::table('saved_search')
            ->where('user_id', $userId)
            ->orderBy('updated_at', 'desc')
            ->get()
            ->toArray();
    }
    
    public function executeRunSavedSearch(sfWebRequest $request)
    {
        $id = (int)$request->getParameter('id');
        $search = \Illuminate\Database\Capsule\Manager::table('saved_search')->where('id', $id)->first();
        
        if (!$search) {
            $this->forward404();
        }
        
        // Update usage count
        \Illuminate\Database\Capsule\Manager::table('saved_search')
            ->where('id', $id)
            ->update([
                'usage_count' => \Illuminate\Database\Capsule\Manager::raw('usage_count + 1'),
                'last_used_at' => date('Y-m-d H:i:s')
            ]);
        
        $params = json_decode($search->search_params, true) ?: [];
        $url = url_for('@glam_browse') . '?' . http_build_query($params);
        
        $this->redirect($url);
    }
    
    public function executeRunTemplate(sfWebRequest $request)
    {
        $id = (int)$request->getParameter('id');
        $template = \Illuminate\Database\Capsule\Manager::table('search_template')->where('id', $id)->first();
        
        if (!$template) {
            $this->forward404();
        }
        
        $params = json_decode($template->search_params, true) ?: [];
        $url = url_for('@glam_browse') . '?' . http_build_query($params);
        
        $this->redirect($url);
    }
    
    public function executeHistory(sfWebRequest $request)
    {
        $userId = $this->getUser()->isAuthenticated() ? $this->getUser()->getAttribute('user_id') : null;
        $sessionId = session_id();
        
        $query = \Illuminate\Database\Capsule\Manager::table('search_history');
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId);
        }
        
        $this->history = $query->orderBy('created_at', 'desc')->limit(50)->get()->toArray();
    }
    
    public function executeDeleteSavedSearch(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');
        
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Login required']));
        }
        
        $id = (int)$request->getParameter('id');
        $userId = $this->getUser()->getAttribute('user_id');
        
        $deleted = \Illuminate\Database\Capsule\Manager::table('saved_search')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->delete();
        
        return $this->renderText(json_encode(['success' => $deleted > 0]));
    }
    
    public function executeAdminTemplates(sfWebRequest $request)
    {
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }
        
        $this->templates = \Illuminate\Database\Capsule\Manager::table('search_template')
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->toArray();
        
        $this->templatesByCategory = [];
        foreach ($this->templates as $t) {
            $cat = $t->category ?: 'Uncategorized';
            $this->templatesByCategory[$cat][] = $t;
        }
    }
}
