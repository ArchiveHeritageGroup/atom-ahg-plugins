<?php

use AtomFramework\Http\Controllers\AhgApiController;
use Illuminate\Database\Capsule\Manager as DB;

class apiv2BatchAction extends AhgApiController
{
    public function POST($request, $data = null)
    {
        if (!$this->hasScope('write')) {
            return $this->error(403, 'Forbidden', 'Write scope required');
        }

        if (empty($data['operations']) || !is_array($data['operations'])) {
            return $this->error(400, 'Bad Request', 'operations array is required');
        }

        $maxOperations = 100;
        if (count($data['operations']) > $maxOperations) {
            return $this->error(400, 'Bad Request', "Maximum $maxOperations operations per batch");
        }

        $results = [
            'total' => count($data['operations']),
            'success' => 0,
            'failed' => 0,
            'operations' => []
        ];

        foreach ($data['operations'] as $index => $op) {
            $opResult = $this->processOperation($op, $index);
            $results['operations'][] = $opResult;
            
            if ($opResult['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
        }

        return $this->success($results);
    }

    protected function processOperation(array $op, int $index): array
    {
        $result = [
            'index' => $index,
            'operation' => $op['operation'] ?? 'unknown',
            'entity' => $op['entity'] ?? 'unknown',
            'success' => false
        ];

        try {
            switch ($op['operation']) {
                case 'create':
                    $result = array_merge($result, $this->handleCreate($op));
                    break;
                case 'update':
                    $result = array_merge($result, $this->handleUpdate($op));
                    break;
                case 'delete':
                    $result = array_merge($result, $this->handleDelete($op));
                    break;
                default:
                    $result['error'] = 'Unknown operation: ' . ($op['operation'] ?? 'null');
            }
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    protected function handleCreate(array $op): array
    {
        // Simplified create for batch - delegates to repository
        if ($op['entity'] === 'description' && !empty($op['data']['title'])) {
            $objectId = DB::table('object')->insertGetId([
                'class_name' => 'QubitInformationObject',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            DB::table('information_object')->insert([
                'id' => $objectId,
                'parent_id' => $op['data']['parent_id'] ?? 1,
                'source_culture' => 'en',
                'lft' => 0, 'rgt' => 0, // Simplified - needs proper nested set
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            DB::table('information_object_i18n')->insert([
                'id' => $objectId,
                'culture' => 'en',
                'title' => $op['data']['title']
            ]);

            $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $op['data']['title']));
            DB::table('slug')->insert(['object_id' => $objectId, 'slug' => $slug . '-' . $objectId]);

            return ['success' => true, 'id' => $objectId];
        }

        return ['success' => false, 'error' => 'Invalid create operation'];
    }

    protected function handleUpdate(array $op): array
    {
        if (empty($op['slug'])) {
            return ['success' => false, 'error' => 'slug required for update'];
        }

        $slugRecord = DB::table('slug')->where('slug', $op['slug'])->first();
        if (!$slugRecord) {
            return ['success' => false, 'error' => 'Record not found'];
        }

        if (!empty($op['data']['title'])) {
            DB::table('information_object_i18n')
                ->where('id', $slugRecord->object_id)
                ->update(['title' => $op['data']['title']]);
        }

        DB::table('object')
            ->where('id', $slugRecord->object_id)
            ->update(['updated_at' => date('Y-m-d H:i:s')]);

        return ['success' => true, 'id' => $slugRecord->object_id];
    }

    protected function handleDelete(array $op): array
    {
        if (empty($op['slug'])) {
            return ['success' => false, 'error' => 'slug required for delete'];
        }

        $slugRecord = DB::table('slug')->where('slug', $op['slug'])->first();
        if (!$slugRecord) {
            return ['success' => false, 'error' => 'Record not found'];
        }

        // Simplified delete - production would need full cascade
        DB::table('slug')->where('object_id', $slugRecord->object_id)->delete();
        DB::table('information_object_i18n')->where('id', $slugRecord->object_id)->delete();
        DB::table('information_object')->where('id', $slugRecord->object_id)->delete();
        DB::table('object')->where('id', $slugRecord->object_id)->delete();

        return ['success' => true, 'id' => $slugRecord->object_id];
    }
}
