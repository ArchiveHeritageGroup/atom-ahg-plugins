<?php

class donorAutocompleteAction extends sfAction
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');
        
        if (!$this->context->user->isAuthenticated()) {
            return $this->renderText(json_encode([]));
        }
        
        $query = $request->getParameter('query', '');
        
        if (strlen($query) < 1) {
            return $this->renderText(json_encode([]));
        }
        
        \AhgCore\Core\AhgDb::init();
        
        $results = \Illuminate\Database\Capsule\Manager::table('donor')
            ->join('actor', 'donor.id', '=', 'actor.id')
            ->join('actor_i18n', 'actor.id', '=', 'actor_i18n.id')
            ->select([
                'donor.id',
                'actor_i18n.authorized_form_of_name as name',
            ])
            ->where('actor_i18n.culture', 'en')
            ->where('actor_i18n.authorized_form_of_name', 'LIKE', '%' . $query . '%')
            ->orderBy('actor_i18n.authorized_form_of_name')
            ->limit(20)
            ->get();
        
        $formatted = [];
        foreach ($results as $donor) {
            $formatted[] = [
                'id' => $donor->id,
                'text' => $donor->name,
                'name' => $donor->name,
            ];
        }
        
        return $this->renderText(json_encode($formatted));
    }
}
