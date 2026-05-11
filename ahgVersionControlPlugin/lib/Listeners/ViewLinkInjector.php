<?php

namespace AhgVersionControl\Listeners;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * ViewLinkInjector — adds a "Version history (N)" banner to the legacy
 * IO/actor view page on `/{slug}`. Hooks `response.filter_content` (the same
 * hook SaveListener uses) and rewrites the body to insert the banner just
 * after the opening of the main page-content container.
 *
 * Only fires when:
 *   - It's an IO or actor view (module + action match)
 *   - Request method is GET
 *   - Response Content-Type starts with text/html
 *   - The entity has at least one captured version
 *
 * The injection is a small Bootstrap alert with a link to the standalone
 * /version-control/... page; failure to inject (no anchor found) is silent.
 *
 * @phase G
 */
final class ViewLinkInjector
{
    /**
     * Modules → entity type for the version-history URL.
     *
     * The legacy `/{slug}` route forwards to one of the descriptive-standard
     * plugins (`sfIsadPlugin`, `sfRadPlugin`, etc.) rather than the canonical
     * `informationobject` / `actor` modules, so they're included here too.
     */
    private const MODULE_MAP = [
        // Canonical
        'informationobject' => 'information_object',
        'actor'             => 'actor',
        'repository'        => 'actor', // repositories ARE actors in AtoM
        'donor'             => 'actor',
        'rightsholder'      => 'actor',
        // Information-object descriptive-standard view modules
        'sfIsadPlugin'      => 'information_object',
        'sfRadPlugin'       => 'information_object',
        'sfDcPlugin'        => 'information_object',
        'sfModsPlugin'      => 'information_object',
        'sfDacsPlugin'      => 'information_object',
        // Actor descriptive-standard view modules
        'sfIsaarPlugin'     => 'actor',
        // Repository descriptive-standard
        'sfIsdiahPlugin'    => 'actor',
    ];

    /** Actions on those modules that render a public view page. */
    private const VIEW_ACTIONS = ['index', 'view', 'show'];

    public function onResponseFilterContent(\sfEvent $event, string $content): string
    {
        try {
            $modified = $this->maybeInject($content);
            return $modified ?? $content;
        } catch (\Throwable $e) {
            error_log('ahgVersionControlPlugin ViewLinkInjector error: ' . $e->getMessage());
            return $content;
        }
    }

    private function maybeInject(string $content): ?string
    {
        if (!\sfContext::hasInstance()) {
            return null;
        }
        $context = \sfContext::getInstance();
        $request = $context->getRequest();
        $module  = $context->getModuleName();
        $action  = $context->getActionName();

        if (!isset(self::MODULE_MAP[$module])) {
            return null;
        }
        if (!in_array($action, self::VIEW_ACTIONS, true) && !$request->isMethod('GET')) {
            return null;
        }
        if ($request->isXmlHttpRequest()) {
            return null;
        }
        $response = $context->getResponse();
        $contentType = method_exists($response, 'getContentType') ? $response->getContentType() : '';
        if ($contentType !== '' && !str_starts_with($contentType, 'text/html')) {
            return null;
        }

        $entityId = $this->resolveEntityId($request);
        if ($entityId === null || $entityId <= 0) {
            return null;
        }

        // Map module → entity_type for our routes; also infer the actual type
        // for repository/donor (all actor sub-types).
        $entityType = self::MODULE_MAP[$module];
        $versionTable = $entityType === 'actor' ? 'actor_version' : 'information_object_version';
        $fk = $entityType === 'actor' ? 'actor_id' : 'information_object_id';

        try {
            $count = (int) DB::table($versionTable)->where($fk, $entityId)->count();
        } catch (\Throwable $e) {
            return null;
        }
        if ($count === 0) {
            return null;
        }

        $url = \url_for([
            'module' => 'versionControl',
            'action' => 'list',
            'entity' => $entityType,
            'id'     => $entityId,
        ]);
        $label = sprintf(\__('Version history (%d)'), $count);

        $banner = sprintf(
            '<div class="alert alert-info py-1 px-2 mb-2 d-inline-block" style="font-size:.9rem;">'
            . '<i class="fas fa-history me-1"></i><a href="%s">%s</a>'
            . '</div>',
            htmlspecialchars($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            htmlspecialchars($label, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        );

        // Insert just after the opening of #main-column / .container content, whichever we find.
        $patterns = [
            '#(<div[^>]+id=["\']main-column["\'][^>]*>)#i',
            '#(<main[^>]*>)#i',
            '#(<div[^>]+class=["\'][^"\']*content[^"\']*["\'][^>]*>)#i',
        ];
        foreach ($patterns as $p) {
            $new = preg_replace($p, '$1' . $banner, $content, 1, $count);
            if ($count > 0 && is_string($new)) {
                return $new;
            }
        }
        return null;
    }

    private function resolveEntityId(\sfWebRequest $request): ?int
    {
        $id = $request->getParameter('id');
        if (is_numeric($id) && (int) $id > 0) {
            return (int) $id;
        }
        $slug = $request->getParameter('slug');
        if (is_string($slug) && $slug !== '') {
            $row = DB::table('slug')->where('slug', $slug)->first();
            if ($row && !empty($row->object_id)) {
                return (int) $row->object_id;
            }
        }
        return null;
    }
}
