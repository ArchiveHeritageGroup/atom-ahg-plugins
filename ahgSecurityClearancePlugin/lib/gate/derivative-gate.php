<?php
/**
 * nginx auth_request gate for reference images and thumbnails (_141 / _142).
 *
 * Sending every derivative through AtoM's digitalobject/view check costs about
 * 0.9 s per thumbnail (a full Symfony start-up). This answers the common case in
 * about 15 ms: 200 when the file belongs to a published description that the
 * shared access filter does not restrict for anonymous visitors, so nginx serves
 * the file itself. Anything else gets 403, which nginx turns into the full AtoM
 * check (error_page 403 = @derivative_full_check). The gate can only grant what
 * an anonymous visitor may already see; it never refuses on its own, so staff
 * still reach drafts and restricted files through AtoM.
 *
 * Not reachable from outside: nginx calls it from an internal location with
 * SCRIPT_FILENAME pointing here, and passes the original URI in ORIGINAL_URI.
 */

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as DB;

function gate(int $code): void
{
    http_response_code($code);
    exit;
}

try {
    $uri = rawurldecode((string) parse_url((string) ($_SERVER['ORIGINAL_URI'] ?? ''), PHP_URL_PATH));
    $slash = strrpos($uri, '/');
    if (false === $slash || !preg_match('#^/uploads/r/.+_(141|142)\.[A-Za-z0-9]+$#', $uri)) {
        gate(403);
    }

    require dirname(__DIR__, 4).'/atom-framework/bootstrap.php';

    $file = DB::table('digital_object as d')
        ->leftJoin('digital_object as master', 'master.id', '=', 'd.parent_id')
        ->where('d.path', substr($uri, 0, $slash + 1))
        ->where('d.name', substr($uri, $slash + 1))
        ->first(['d.object_id', 'master.object_id as master_object_id']);
    $objectId = (int) ($file->object_id ?? 0) ?: (int) ($file->master_object_id ?? 0);
    if ($objectId <= 1) {
        gate(403); // unknown or not a description's file: AtoM decides
    }

    $published = (int) DB::table('status')
        ->where('object_id', $objectId)->where('type_id', 158) // publication status
        ->value('status_id');
    if (160 !== $published) { // not published
        gate(403);
    }

    // The anonymous restriction list is the same for every visitor and costs
    // about 400 ms to build, so it is cached for a minute.
    $key = 'ahg_derivative_gate_restricted';
    $restricted = function_exists('apcu_fetch') ? apcu_fetch($key) : false;
    if (!is_array($restricted)) {
        $restricted = array_flip(array_map('intval',
            \AtomExtensions\Services\Search\SearchAccessFilterService::getInstance()->getRestrictedObjectIds(null)));
        if (function_exists('apcu_store')) {
            apcu_store($key, $restricted, 60);
        }
    }

    gate(isset($restricted[$objectId]) ? 403 : 200);
} catch (\Throwable $e) {
    error_log('derivative-gate: '.$e->getMessage());
    gate(403); // fail to the full check, never open
}
