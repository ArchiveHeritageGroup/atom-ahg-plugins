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
            // SECURITY: the key's scope is not authorisation. Any signed-in user
            // can issue themselves a write key at /api/keys, so without AtoM's
            // own check a self-registered researcher could retitle or delete any
            // description through batch. Same check as the single endpoints.
            if (in_array($op['operation'] ?? '', ['update', 'delete'], true)) {
                $slug = DB::table('slug')->where('slug', (string) ($op['slug'] ?? ''))->value('object_id');
                $resource = $slug ? \QubitInformationObject::getById((int) $slug) : null;
                if (!$resource || !\AtomExtensions\Services\AclService::check($resource, $op['operation'])) {
                    return $result + ['error' => 'Not authorised for this description'];
                }
            }

            switch ($op['operation']) {
                case 'create':
                    // Batch create wrote lft/rgt as 0, breaking the description
                    // tree, and skipped the permission check. The single endpoint
                    // does both properly.
                    $result['error'] = 'Batch create is not supported; use POST /api/v2/descriptions';
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
        if (class_exists('\\AtomFramework\\Services\\Delete\\EntityDeleteService')) {
            \AtomFramework\Services\Delete\EntityDeleteService::delete($slugRecord->object_id);
        } else {
            DB::table('slug')->where('object_id', $slugRecord->object_id)->delete();
            DB::table('information_object_i18n')->where('id', $slugRecord->object_id)->delete();
            DB::table('information_object')->where('id', $slugRecord->object_id)->delete();
            DB::table('object')->where('id', $slugRecord->object_id)->delete();
        }

        return ['success' => true, 'id' => $slugRecord->object_id];
    }
}
