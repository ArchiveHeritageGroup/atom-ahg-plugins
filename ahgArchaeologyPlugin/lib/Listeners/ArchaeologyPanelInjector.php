<?php

declare(strict_types=1);

namespace AhgArchaeologyPlugin\Listeners;

use AhgCore\Core\AhgDb;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Puts an archaeology panel on a description page, so a site description links to
 * its stratigraphy and a context description links back to its sheet and matrix.
 *
 * Why this exists: a site, a context and a find are all information objects, so
 * AtoM serves them through its ordinary description view, which knows nothing
 * about this plugin. Without this, someone landing on
 * /blaauwbosch-farm-2026-excavation sees a normal description with no route to the
 * Harris Matrix at all - the excavation record and the catalogue record look like
 * two unrelated systems.
 *
 * Mirrors ahgSiteRecordPlugin's SiteRecordPanelInjector, including its lesson:
 * there is deliberately NO "is another plugin rendering this?" check. The marker
 * test below answers that question by observation rather than by presuming what
 * some theme might do with an extension.json declaration.
 */
class ArchaeologyPanelInjector
{
    /**
     * A description is not served by an 'informationobject' module. AtoM forwards
     * /{slug} to the module for the record's descriptive standard, so checking for
     * one module name means this silently never fires.
     */
    private const VIEW_MODULES = [
        'informationobject', 'sfIsadPlugin', 'sfDcPlugin', 'sfModsPlugin',
        'sfRadPlugin', 'arDacsPlugin',
    ];

    /** response.filter_content can fire more than once per request. */
    private static bool $injected = false;

    public static function filter(\sfEvent $event, $content)
    {
        try {
            return (new self())->inject($event, $content);
        } catch (\Throwable $e) {
            // A panel is an enhancement. It must never take a record page down.
            return $content;
        }
    }

    private function inject(\sfEvent $event, $content)
    {
        if (self::$injected) {
            return $content;
        }

        if (false !== stripos((string) $content, 'ahg-archaeology-panel')) {
            return $content;
        }

        $response = $event->getSubject();

        if (!$this->isHtmlGet($response)) {
            return $content;
        }

        $context = \sfContext::getInstance();

        if (!in_array($context->getModuleName(), self::VIEW_MODULES, true)
            || 'index' !== $context->getActionName()) {
            return $content;
        }

        $resource = $context->getActionStack()->getLastEntry()->getActionInstance()->resource ?? null;

        if (!$resource || !isset($resource->id)) {
            return $content;
        }

        $html = $this->render((int) $resource->id);

        if ('' === $html) {
            return $content;
        }

        self::$injected = true;

        $anchor = '</section>';
        $pos = strripos($content, $anchor);

        if (false === $pos) {
            return $content.$html;
        }

        return substr($content, 0, $pos).$html.substr($content, $pos);
    }

    private function isHtmlGet($response): bool
    {
        if (!isset($_SERVER['REQUEST_METHOD']) || 'GET' !== $_SERVER['REQUEST_METHOD']) {
            return false;
        }

        $type = method_exists($response, 'getContentType') ? (string) $response->getContentType() : 'text/html';

        return '' === $type || false !== stripos($type, 'html');
    }

    /**
     * The panel for this description, or '' when it is not part of a dig.
     *
     * One query per entity type, all guarded: an instance that installed the
     * plugin but never ran the schema must not fatal every description page.
     */
    private function render(int $ioId): string
    {
        if (!AhgDb::hasOptionalTable('archaeology_context')) {
            return '';
        }

        $site = DB::table('archaeology_site')->where('information_object_id', $ioId)->first();

        if ($site) {
            $contexts = DB::table('archaeology_context')->where('site_id', $site->id)->count();
            $rels = AhgDb::hasOptionalTable('archaeology_context_relationship')
                ? intdiv(DB::table('archaeology_context_relationship as r')
                    ->join('archaeology_context as c', 'c.id', '=', 'r.context_id')
                    ->where('c.site_id', $site->id)->count(), 2)
                : 0;

            return $this->panel('This description is an excavation site.', [
                ['Stratigraphy and Harris Matrix', '/archaeology/site/'.$site->id.'/contexts', 'primary'],
                ['Dig plan and map', '/archaeology/site/'.$site->id.'/plan', 'outline-secondary'],
                ['Site record', '/archaeology/site/'.$site->id, 'outline-secondary'],
            ], sprintf(
                'Site %s. %d context%s recorded, %d stratigraphic relationship%s.',
                $site->site_number, $contexts, 1 === $contexts ? '' : 's',
                $rels, 1 === $rels ? '' : 's'
            ));
        }

        $ctx = DB::table('archaeology_context as c')
            ->join('archaeology_site as s', 's.id', '=', 'c.site_id')
            ->where('c.information_object_id', $ioId)
            ->first(['c.id', 'c.context_number', 'c.site_id', 's.site_number']);

        if ($ctx) {
            return $this->panel('This description is a stratigraphic context.', [
                ['Context sheet', '/archaeology/context/'.$ctx->id, 'primary'],
                ['Harris Matrix for this site', '/archaeology/site/'.$ctx->site_id.'/contexts', 'outline-secondary'],
            ], sprintf('Context %s of site %s.', $ctx->context_number, $ctx->site_number));
        }

        if (AhgDb::hasOptionalTable('archaeology_object')) {
            $find = DB::table('archaeology_object as o')
                ->leftJoin('archaeology_context as c', 'c.id', '=', 'o.context_id')
                ->where('o.information_object_id', $ioId)
                ->first(['o.accession_number', 'o.context_id', 'o.site_id', 'c.context_number']);

            if ($find) {
                $links = [];

                if ($find->context_id) {
                    $links[] = ['Context '.$find->context_number, '/archaeology/context/'.$find->context_id, 'primary'];
                }

                if ($find->site_id) {
                    $links[] = ['Harris Matrix for this site', '/archaeology/site/'.$find->site_id.'/contexts', 'outline-secondary'];
                }

                return $this->panel('This description is an excavated find.', $links, sprintf(
                    'Accession %s%s.',
                    $find->accession_number,
                    $find->context_number ? ', recovered from context '.$find->context_number : ', context not recorded'
                ));
            }
        }

        return '';
    }

    /**
     * Bootstrap classes only, no inline style attributes.
     *
     * CSP nonces cover <style> and <script> ELEMENTS, never style="" attributes,
     * so a panel built with inline styles renders unstyled on any instance with a
     * policy - which is most of them.
     */
    private function panel(string $heading, array $links, string $detail): string
    {
        $buttons = '';

        foreach ($links as [$label, $href, $variant]) {
            $buttons .= sprintf(
                '<a class="btn btn-sm btn-%s me-2 mb-1" href="%s">%s</a>',
                htmlspecialchars($variant, ENT_QUOTES),
                htmlspecialchars(url_for_archaeology($href), ENT_QUOTES),
                htmlspecialchars($label, ENT_QUOTES)
            );
        }

        return '<div class="ahg-archaeology-panel card mt-3 mb-3">'
            .'<div class="card-body">'
            .'<h2 class="h6 card-title">'.htmlspecialchars($heading, ENT_QUOTES).'</h2>'
            .'<p class="text-muted small mb-2">'.htmlspecialchars($detail, ENT_QUOTES).'</p>'
            .$buttons
            .'</div></div>';
    }
}

if (!function_exists('AhgArchaeologyPlugin\Listeners\url_for_archaeology')) {
    /**
     * Prefix a plugin path with the front controller when the instance uses one.
     *
     * The description page this panel lands on may itself have been reached
     * through /index.php, and a bare /archaeology/... link would drop the front
     * controller and 404 on any install that has not enabled URL rewriting.
     */
    function url_for_archaeology(string $path): string
    {
        $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');

        if ('' !== $script && false !== stripos($script, 'index.php')) {
            return '/index.php'.$path;
        }

        return $path;
    }
}
