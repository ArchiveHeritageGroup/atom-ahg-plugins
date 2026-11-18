<?php

/**
 * Access-controlled delivery of 3D model files.
 *
 * Why this exists: a .glb/.gltf/.obj/.stl is always a MASTER digital object - there
 * is no meaningful derivative to fall back to. Since masters are gated behind
 * `readMaster` (issue #258), the raw /uploads/r/* URL 404s for anonymous users and
 * <model-viewer> hangs on "Loading 3D model...". Rather than reopen the master gate,
 * this serves 3D models under their own rule: readable if the DESCRIPTION is
 * readable.
 *
 * Deliberately narrow, because a file-serving endpoint is exactly the thing that
 * becomes an arbitrary-file-read bug:
 *   - only files whose extension is on the 3D allowlist AND whose mime type matches it,
 *     or, where base AtoM stored no usable type (a .glb is saved as "unknown"), whose
 *     first bytes are that format's signature (glb, gltf, usdz only)
 *   - access decided by QubitAcl 'read' on the parent information object
 *   - path resolved through PathGuard (realpath + boundary, fails closed)
 */
class Model3DFileServer
{
    /** Extension => expected mime prefix. Both must match. */
    private const ALLOWED = [
        'glb' => ['model/gltf-binary', 'application/octet-stream'],
        'gltf' => ['model/gltf+json', 'application/json', 'text/plain'],
        'obj' => ['text/plain', 'application/octet-stream', 'model/obj'],
        'stl' => ['application/sla', 'model/stl', 'application/octet-stream', 'text/plain'],
        'ply' => ['application/octet-stream', 'text/plain'],
        'usdz' => ['model/vnd.usdz+zip', 'application/octet-stream'],
    ];

    private const MIME_OUT = [
        'glb' => 'model/gltf-binary',
        'gltf' => 'model/gltf+json',
        'obj' => 'text/plain',
        'stl' => 'application/sla',
        'ply' => 'application/octet-stream',
        'usdz' => 'model/vnd.usdz+zip',
    ];

    /**
     * @return array{path:string,mime:string,name:string}|null null when the caller
     *                                                         must be refused
     */
    public static function resolve(int $digitalObjectId, $user): ?array
    {
        $digitalObject = \QubitDigitalObject::getById($digitalObjectId);
        if (!$digitalObject) {
            return null;
        }

        $name = (string) $digitalObject->name;
        $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        if (!isset(self::ALLOWED[$ext])) {
            return null;
        }

        // Access follows the DESCRIPTION, not readMaster. If you may read the record,
        // you may load its 3D model; if the record is restricted, so is the model.
        $resource = $digitalObject->object;
        if (!$resource || !\QubitAcl::check($resource, 'read')) {
            return null;
        }

        $relative = (string) $digitalObject->path . $name;
        $absolute = rtrim((string) \sfConfig::get('sf_web_dir'), '/') . '/' . ltrim($relative, '/');

        return self::servable($absolute, $name, strtolower((string) $digitalObject->mimeType));
    }

    /**
     * A model stored by this plugin's own upload form (uploads/3d/<object id>/...),
     * which has no digital object. Its /uploads URL is refused by a stock AtoM nginx
     * (only uploads/r/ is routed), so it is served here under the same rules.
     *
     * @return array{path:string,mime:string,name:string}|null
     */
    public static function resolveModel(int $modelId, $user): ?array
    {
        $model = \Illuminate\Database\Capsule\Manager::table('object_3d_model')->where('id', $modelId)->first();
        if (!$model) {
            return null;
        }

        $resource = \QubitInformationObject::getById((int) $model->object_id);
        if (!$resource || !\QubitAcl::check($resource, 'read')) {
            return null;
        }
        if (empty($model->is_public) && !($user && $user->isAuthenticated())) {
            return null;
        }

        $path = (string) $model->file_path;
        $relative = class_exists('\AtomFramework\Services\Model3DService')
            ? \AtomFramework\Services\Model3DService::relativeUploadPath($path)
            : ltrim(preg_replace('#^/?uploads/#', '', $path), '/');
        $absolute = rtrim((string) \sfConfig::get('sf_web_dir'), '/') . '/uploads/' . $relative;
        $name = basename($relative);
        if (!isset(self::ALLOWED[strtolower((string) pathinfo($name, PATHINFO_EXTENSION))])) {
            return null;
        }

        return self::servable($absolute, $name, '');
    }

    /** Guard the path, then require a matching mime type or the format's signature. */
    private static function servable(string $absolute, string $name, string $mime): ?array
    {
        $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        $safe = self::guard($absolute);
        if (null === $safe || !is_readable($safe)) {
            return null;
        }
        if (!in_array($mime, self::ALLOWED[$ext], true) && !self::signatureMatches($safe, $ext)) {
            return null;
        }

        return ['path' => $safe, 'mime' => self::MIME_OUT[$ext], 'name' => $name];
    }

    /** True when the file starts the way its format must. Only glb, gltf and usdz are checked. */
    private static function signatureMatches(string $path, string $ext): bool
    {
        $head = (string) @file_get_contents($path, false, null, 0, 4096);

        return match ($ext) {
            'glb' => str_starts_with($head, 'glTF'),
            'gltf' => str_starts_with(ltrim($head), '{') && str_contains($head, '"asset"'),
            'usdz' => str_starts_with($head, "PK\x03\x04"),
            default => false,
        };
    }

    /**
     * Constrain to the upload roots. Uses the framework's PathGuard when present and
     * falls back to an equivalent realpath + boundary check so this plugin still
     * refuses traversal on an install without the framework.
     */
    private static function guard(string $path): ?string
    {
        if (class_exists('\AtomExtensions\Services\PathGuard')) {
            return \AtomExtensions\Services\PathGuard::within($path, \AtomExtensions\Services\PathGuard::defaultRoots());
        }

        $real = realpath($path);
        if (false === $real) {
            return null;
        }

        $root = realpath(rtrim((string) \sfConfig::get('sf_web_dir'), '/') . '/uploads');
        if (false === $root) {
            return null;
        }

        return str_starts_with($real, $root . DIRECTORY_SEPARATOR) ? $real : null;
    }
}
