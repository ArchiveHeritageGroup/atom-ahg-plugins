<?php

class apiv2SyncChangesAction extends AhgApiAction
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
        return \Illuminate\Database\Capsule\Manager::table('information_object as io')
            ->join('slug', 'io.id', '=', 'slug.object_id')
            ->where('io.updated_at', '>=', $since)
            ->where('io.id', '!=', 1)
            ->limit($limit)
            ->select(['io.id', 'slug.slug', 'io.updated_at'])
            ->get()
            ->toArray();
    }

    protected function getChangedConditions($since, $limit)
    {
        return \Illuminate\Database\Capsule\Manager::table('spectrum_condition_check')
            ->where('check_date', '>=', $since)
            ->limit($limit)
            ->select(['id', 'object_id', 'check_date as updated_at'])
            ->get()
            ->toArray();
    }

    protected function getChangedAssets($since, $limit)
    {
        return \Illuminate\Database\Capsule\Manager::table('heritage_asset')
            ->where('updated_at', '>=', $since)
            ->limit($limit)
            ->select(['id', 'object_id', 'updated_at'])
            ->get()
            ->toArray();
    }
}
