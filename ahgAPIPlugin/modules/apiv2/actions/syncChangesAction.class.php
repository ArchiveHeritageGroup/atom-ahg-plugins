<?php

use AtomFramework\Http\Controllers\AhgApiController;

require_once sfConfig::get('sf_plugins_dir').'/ahgAPIPlugin/lib/ApiVisibility.php';

class apiv2SyncChangesAction extends AhgApiController
{
    public function GET($request)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }

        $since = $request->getParameter('since');
        $types = $request->getParameter('types', 'descriptions,conditions,assets');
        $limit = min($request->getParameter('limit', 100), 1000);

        if (empty($since)) {
            return $this->error(400, 'Bad Request', 'since parameter required (ISO datetime)');
        }

        $changes = [];
        $typeList = explode(',', $types);

        // Get changed descriptions
        if (in_array('descriptions', $typeList)) {
            $changes['descriptions'] = $this->getChangedDescriptions($since, $limit);
        }

        // Get changed conditions
        if (in_array('conditions', $typeList)) {
            $changes['conditions'] = $this->getChangedConditions($since, $limit);
        }

        // Get changed assets
        if (in_array('assets', $typeList)) {
            $changes['assets'] = $this->getChangedAssets($since, $limit);
        }

        return $this->success([
            'since' => $since,
            'server_time' => date('c'),
            'changes' => $changes
        ]);
    }

    protected function getChangedDescriptions($since, $limit)
    {
        // updated_at lives on object, not information_object.
        $query = \Illuminate\Database\Capsule\Manager::table('information_object as io')
            ->join('object as o', 'io.id', '=', 'o.id')
            ->join('slug', 'io.id', '=', 'slug.object_id')
            ->where('o.updated_at', '>=', $since)
            ->where('io.id', '!=', 1);
        ApiVisibility::applyToQuery($query, $this->getUser());

        return $query->limit($limit)
            ->select(['io.id', 'slug.slug', 'o.updated_at'])
            ->get()
            ->toArray();
    }

    protected function getChangedConditions($since, $limit)
    {
        // Each type belongs to an optional plugin: no table means no changes.
        if (!\Illuminate\Database\Capsule\Manager::schema()->hasTable('spectrum_condition_check')) {
            return [];
        }
        $query = \Illuminate\Database\Capsule\Manager::table('spectrum_condition_check')
            ->where('check_date', '>=', $since);
        ApiVisibility::applyToQuery($query, $this->getUser(), 'spectrum_condition_check.object_id');

        return $query->limit($limit)
            ->select(['id', 'object_id', 'check_date as updated_at'])
            ->get()
            ->toArray();
    }

    protected function getChangedAssets($since, $limit)
    {
        if (!\Illuminate\Database\Capsule\Manager::schema()->hasTable('heritage_asset')) {
            return [];
        }
        $query = \Illuminate\Database\Capsule\Manager::table('heritage_asset')
            ->where('updated_at', '>=', $since);
        ApiVisibility::applyToQuery($query, $this->getUser(), 'heritage_asset.object_id');

        return $query->limit($limit)
            ->select(['id', 'object_id', 'updated_at'])
            ->get()
            ->toArray();
    }
}
