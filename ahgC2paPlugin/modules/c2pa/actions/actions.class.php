<?php
/**
 * PSIS / AtoM-AHG - C2PA content-credentials module.
 *
 * Routes (registered in ahgC2paPluginConfiguration):
 *   GET  /c2pa/manifests/:id     -> executeManifests  (JSON list of manifests for an IO)
 *   GET  /c2pa/manifest/:id      -> executeManifest    (JSON one stored manifest by row id)
 *   POST /c2pa/verify            -> executeVerify       (verify a posted manifest JSON)
 *   GET  /.well-known/c2pa-info  -> executeWellKnown    (capability + signing-key info)
 *
 * Dual-stack: extends AtomFramework\Http\Controllers\AhgController so the same
 * action methods run under Symfony (index.php) and Heratio standalone.
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */

use AhgC2pa\Services\C2paService;
use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

class c2paActions extends AhgController
{
    // API controller: no CSRF, JSON only.
    protected bool $csrfProtection = false;

    public function boot(): void
    {
        require_once dirname(__DIR__, 3) . '/lib/c2pa_bootstrap.php';
        \C2paBootstrap::load();
    }

    /**
     * List every stored C2PA manifest for an information object.
     */
    public function executeManifests($request)
    {
        $ioId = (int) $request->getParameter('id');
        if ($ioId <= 0) {
            return $this->renderJsonError('invalid information object id', 400);
        }

        if (!$this->tableExists('ahg_c2pa_manifest')) {
            return $this->renderJson(['information_object_id' => $ioId, 'manifests' => []]);
        }

        $rows = DB::table('ahg_c2pa_manifest')
            ->where('information_object_id', $ioId)
            ->orderByDesc('created_at')
            ->get(['id', 'action', 'model_id', 'model_version', 'sidecar_path', 'kid', 'claim_signature', 'created_at']);

        $manifests = [];
        foreach ($rows as $row) {
            $manifests[] = [
                'id'            => (int) $row->id,
                'action'        => (string) $row->action,
                'model_id'      => (string) $row->model_id,
                'model_version' => $row->model_version,
                'sidecar_path'  => $row->sidecar_path,
                'kid'           => (string) $row->kid,
                'signature'     => (string) $row->claim_signature,
                'created_at'    => (string) $row->created_at,
            ];
        }

        return $this->renderJson([
            'information_object_id' => $ioId,
            'count'                 => count($manifests),
            'manifests'             => $manifests,
        ]);
    }

    /**
     * Return a single stored manifest's full canonical JSON by row id.
     */
    public function executeManifest($request)
    {
        $id = (int) $request->getParameter('id');
        if ($id <= 0) {
            return $this->renderJsonError('invalid manifest id', 400);
        }
        if (!$this->tableExists('ahg_c2pa_manifest')) {
            return $this->renderJsonError('ahg_c2pa_manifest table not installed', 404);
        }

        $row = DB::table('ahg_c2pa_manifest')->where('id', $id)->first();
        if ($row === null) {
            return $this->renderJsonError('manifest not found', 404);
        }

        $manifest = json_decode((string) $row->manifest_json, true);

        return $this->renderJson([
            'id'                    => (int) $row->id,
            'information_object_id' => (int) $row->information_object_id,
            'action'                => (string) $row->action,
            'kid'                   => (string) $row->kid,
            'created_at'            => (string) $row->created_at,
            'manifest'              => is_array($manifest) ? $manifest : null,
        ]);
    }

    /**
     * Verify a manifest posted as JSON (raw body or `manifest` form field).
     * Re-hashes assertions + verifies the Ed25519 claim signature.
     */
    public function executeVerify($request)
    {
        $payload = $this->readJsonBody($request);
        if ($payload === null) {
            return $this->renderJsonError('POST a JSON manifest body or a `manifest` field', 400);
        }

        $result = C2paService::verify($payload, C2paService::publicKeyResolver());

        return $this->renderJson([
            'ok'               => $result['ok'],
            'errors'           => $result['errors'],
            'assertion_hashes' => $result['assertion_hashes'],
        ], $result['ok'] ? 200 : 422);
    }

    /**
     * Capability discovery: which C2PA features are available on this host.
     */
    public function executeWellKnown($request)
    {
        $signer = \C2paBootstrap::loadSigner();
        $service = new C2paService($signer);

        $activeKid = null;
        try {
            if ($this->tableExists('ai_inference_key')) {
                $keyRow = DB::table('ai_inference_key')->where('active', 1)->orderByDesc('id')->first(['kid']);
                $activeKid = $keyRow === null ? null : (string) $keyRow->kid;
            }
        } catch (\Throwable) {
            $activeKid = null;
        }

        return $this->renderJson([
            'spec'              => 'https://c2pa.org/specifications/specifications/2.1/specs/C2PA_Specification.html',
            'signing_available' => $service->canSign(),
            'active_kid'        => $activeKid,
            'embed_available'   => $service->canEmbed(),
            'c2patool'          => $service->toolPath(),
            'crypto_library'    => class_exists(\AhgInferenceReceipts\Signer::class),
            'manifest_store'    => $this->tableExists('ahg_c2pa_manifest'),
        ]);
    }

    private function tableExists(string $table): bool
    {
        try {
            return DB::schema()->hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<string,mixed>|null
     */
    private function readJsonBody($request): ?array
    {
        $raw = '';
        if (is_object($request) && method_exists($request, 'getContent')) {
            $raw = (string) $request->getContent();
        }
        if ($raw === '') {
            $raw = (string) file_get_contents('php://input');
        }

        $decoded = null;
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
        }

        // Fallback: a `manifest` form/query parameter containing JSON.
        if (!is_array($decoded) && is_object($request) && method_exists($request, 'getParameter')) {
            $field = $request->getParameter('manifest');
            if (is_string($field) && $field !== '') {
                $decoded = json_decode($field, true);
            }
        }

        return is_array($decoded) ? $decoded : null;
    }
}
