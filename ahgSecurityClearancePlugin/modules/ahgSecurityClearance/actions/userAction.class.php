<?php

use Illuminate\Database\Capsule\Manager as DB;

class arSecurityClearanceUserAction extends sfAction
{
    public function execute($request)
    {
        $slug = $request->getParameter('slug');
        $culture = $this->getUser()->getCulture();
        
        // Get user by slug using Laravel Query Builder
        $this->user = DB::table('user as u')
            ->join('actor as a', 'a.id', '=', 'u.id')
            ->join('slug', 'slug.object_id', '=', 'u.id')
            ->leftJoin('actor_i18n as ai', function($join) use ($culture) {
                $join->on('ai.id', '=', 'a.id')
                     ->where('ai.culture', '=', $culture);
            })
            ->where('slug.slug', $slug)
            ->select([
                'u.*',
                'a.entity_type_id',
                'ai.authorized_form_of_name',
                'slug.slug'
            ])
            ->first();
        
        if (!$this->user) {
            $this->forward404();
        }
        
        // Get security clearance
        $this->clearance = DB::table('security_clearance')
            ->where('user_id', $this->user->id)
            ->first();
    }
}
