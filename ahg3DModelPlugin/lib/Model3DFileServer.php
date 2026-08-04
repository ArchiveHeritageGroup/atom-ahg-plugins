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
 *   - only digital objects whose extension AND mime type are on the 3D allowlist
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
        $mime = strtolower((string) $digitalObject->mimeType);

        // Allowlist: this endpoint serves 3D models and nothing else.
        if (!isset(self::ALLOWED[$ext]) || !in_array($mime, self::ALLOWED[$ext], true)) {
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

        $safe = self::guard($absolute);
        if (null === $safe || !is_readable($safe)) {
            return null;
        }

        return ['path' => $safe, 'mime' => self::MIME_OUT[$ext], 'name' => $name];
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
