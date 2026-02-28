<?php

use AtomFramework\Http\Controllers\AhgApiController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * GET /api/v2/publish/readiness/:slug — Check publish gate status for an object
 */
class apiv2PublishReadinessAction extends AhgApiController
{
    public function GET($request)
    {
        if (!$this->hasScope('read') && !$this->hasScope('publish:read')) {
            return $this->error(403, 'Forbidden', 'publish:read scope required');
        }

        $slug = $request->getParameter('slug');

        // Resolve slug to object ID
        $slugRow = DB::table('slug')->where('slug', $slug)->first();
        if (!$slugRow) {
            return $this->notFound("Object not found for slug: {$slug}");
        }

        $objectId = (int) $slugRow->object_id;

        // Load PublishGateService
        $serviceFile = sfConfig::get('sf_root_dir') . '/plugins/ahgWorkflowPlugin/lib/Services/PublishGateService.php';
        if (!file_exists($serviceFile)) {
            return $this->error(503, 'Service Unavailable', 'Publish gate service not installed');
        }
        require_once $serviceFile;

        $gateService = new \PublishGateService();
        $results = $gateService->evaluate($objectId);

        $blockers = array_values(array_filter($results, fn($r) => $r['severity'] === 'blocker' && $r['status'] === 'failed'));
        $warnings = array_values(array_filter($results, fn($r) => $r['status'] === 'warning' || ($r['severity'] === 'warning' && $r['status'] === 'failed')));
        $canPublish = empty($blockers);

        $this->response->setHttpHeader('X-Correlation-Id', uniqid('gate-'));

        return $this->success([
            'object_id' => $objectId,
            'slug' => $slug,
            'can_publish' => $canPublish,
            'blockers' => array_map(fn($b) => ['rule' => $b['rule_name'], 'message' => $b['error_message'], 'details' => $b['details'] ?? null], $blockers),
            'warnings' => array_map(fn($w) => ['rule' => $w['rule_name'], 'message' => $w['error_message'], 'details' => $w['details'] ?? null], $warnings),
            'results' => array_map(fn($r) => [
                'rule' => $r['rule_name'],
                'type' => $r['rule_type'],
                'severity' => $r['severity'],
                'status' => $r['status'],
                'message' => $r['error_message'],
            ], $results),
        ]);
    }
}
