<?php

use AtomFramework\Http\Controllers\AhgApiController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * POST /api/v2/publish/execute/:slug — Publish a record via API
 * Requires scopes: write + publish:write
 */
class apiv2PublishExecuteAction extends AhgApiController
{
    public function POST($request, $data = null)
    {
        if (!$this->hasScope('write') || !$this->hasScope('publish:write')) {
            return $this->error(403, 'Forbidden', 'write and publish:write scopes required');
        }

        $slug = $request->getParameter('slug');

        // Resolve slug to object ID
        $slugRow = DB::table('slug')->where('slug', $slug)->first();
        if (!$slugRow) {
            return $this->notFound("Object not found for slug: {$slug}");
        }

        $objectId = (int) $slugRow->object_id;
        $force = !empty($data['force']);

        // Force publish requires admin
        if ($force && !$this->isAdmin()) {
            return $this->error(403, 'Forbidden', 'Force publish requires administrator access');
        }

        // Load PublishGateService
        $serviceFile = sfConfig::get('sf_root_dir') . '/plugins/ahgWorkflowPlugin/lib/Services/PublishGateService.php';
        if (!file_exists($serviceFile)) {
            return $this->error(503, 'Service Unavailable', 'Publish gate service not installed');
        }
        require_once $serviceFile;

        $userId = (int) ($this->apiKeyInfo['user_id'] ?? 0);
        $gateService = new \PublishGateService();
        $result = $gateService->executePublish($objectId, $userId, $force);

        $correlationId = uniqid('pub-');
        $this->response->setHttpHeader('X-Correlation-Id', $correlationId);

        if ($result['published']) {
            return $this->success([
                'published' => true,
                'object_id' => $objectId,
                'slug' => $slug,
                'event_id' => $result['event_id'] ?? null,
                'forced' => $force,
            ]);
        }

        // Not published — return blockers
        return $this->success([
            'published' => false,
            'object_id' => $objectId,
            'slug' => $slug,
            'blockers' => array_map(fn($b) => ['rule' => $b['rule_name'], 'message' => $b['error_message']], $result['blockers']),
        ]);
    }
}
