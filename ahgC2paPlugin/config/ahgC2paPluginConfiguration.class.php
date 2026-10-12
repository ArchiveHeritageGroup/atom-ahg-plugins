<?php
/**
 * PSIS / AtoM-AHG - ahgC2paPlugin Configuration (Symfony 1.4 sfPluginConfiguration).
 *
 * C2PA (Coalition for Content Provenance and Authenticity) content credentials:
 * generate, sign and embed C2PA 2.1 manifests on digital-object derivatives, and
 * verify them. PSIS twin of Heratio's ahg-c2pa package.
 *
 * Registers the `c2pa` (JSON API), `c2paVerify` (public pages) and `c2paAdmin`
 * (staff pages) modules + their routes via \AtomFramework\Routing\RouteLoader,
 * and two listeners: DigitalObjectCreateWatcher (auto-provenance for new
 * masters) and ContentCredentialsBadge (the Verify badge on record pages).
 * bin/atom commands (c2pa:verify, c2pa:smoke) are auto-discovered from
 * lib/Commands; symfony tasks (c2pa:provenance-backfill, c2pa:embed) from
 * lib/task.
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */
class ahgC2paPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'C2PA content credentials: sign, embed and verify provenance manifests on digital objects';
    public static $version = '0.2.0';
    public static $category = 'ahg';

    public function initialize()
    {
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);

        if (class_exists('AhgNav')) {
            AhgNav::register('manage', 'c2pa_coverage', [
                'route'       => '@ahg_c2pa_admin_coverage',
                'label'       => 'Content credentials',
                'credentials' => ['editor', 'administrator'],
                'weight'      => 62,
            ]);
        }

        // Per-request hooks: plugin classes are not autoloaded, so load them here.
        require_once __DIR__ . '/../lib/Listeners/DigitalObjectCreateWatcher.php';
        require_once __DIR__ . '/../lib/Listeners/ContentCredentialsBadge.php';
        $this->dispatcher->connect('context.load_factories', ['\AhgC2pa\Listeners\DigitalObjectCreateWatcher', 'arm']);
        $this->dispatcher->connect('response.filter_content', ['\AhgC2pa\Listeners\ContentCredentialsBadge', 'filter']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        foreach (['c2pa', 'c2paVerify', 'c2paAdmin'] as $module) {
            if (!in_array($module, $enabledModules, true)) {
                $enabledModules[] = $module;
            }
        }
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    public function addRoutes(sfEvent $event)
    {
        if (!class_exists('\\AtomFramework\\Routing\\RouteLoader')) {
            return;
        }

        $routing = $event->getSubject();
        if (!$routing instanceof sfRouting) {
            return;
        }

        $c2pa = new \AtomFramework\Routing\RouteLoader('c2pa');

        // Capability discovery + verify (POST) first; specific before catch-alls.
        $c2pa->any('ahg_c2pa_verify', '/c2pa/verify', 'verify');
        $c2pa->get('ahg_c2pa_manifest', '/c2pa/manifest/:id', 'manifest', ['id' => '\d+']);
        $c2pa->get('ahg_c2pa_manifests', '/c2pa/manifests/:id', 'manifests', ['id' => '\d+']);

        $c2pa->register($routing);

        // Public content-credentials pages (Heratio /verify/...). Multi-segment
        // paths, so the single-segment slug route never captures them.
        $verify = new \AtomFramework\Routing\RouteLoader('c2paVerify');
        $verify->any('ahg_c2pa_check', '/c2pa/check', 'check');
        $verify->get('ahg_c2pa_record', '/c2pa/record/:id', 'record', ['id' => '\d+']);
        $verify->get('ahg_c2pa_object', '/c2pa/object/:id', 'object', ['id' => '\d+']);
        $verify->get('ahg_c2pa_object_download', '/c2pa/object/:id/download', 'download', ['id' => '\d+']);
        $verify->get('ahg_c2pa_object_credentials', '/c2pa/object/:id/credentials', 'credentials', ['id' => '\d+']);
        $verify->get('ahg_c2pa_object_badge', '/c2pa/object/:id/badge', 'badge', ['id' => '\d+']);
        $verify->get('ahg_c2pa_object_badge_json', '/c2pa/object/:id/badge-json', 'badgeJson', ['id' => '\d+']);
        $verify->register($routing);

        // Staff provenance pages (Heratio /admin/c2pa/...).
        $admin = new \AtomFramework\Routing\RouteLoader('c2paAdmin');
        $admin->get('ahg_c2pa_admin_coverage', '/admin/c2pa/coverage', 'coverage');
        $admin->get('ahg_c2pa_admin_index', '/admin/c2pa/object/:id', 'index', ['id' => '\d+']);
        $admin->any('ahg_c2pa_admin_record', '/admin/c2pa/object/:id/record', 'record', ['id' => '\d+']);
        $admin->get('ahg_c2pa_admin_show', '/admin/c2pa/object/:id/record/:pid', 'show', ['id' => '\d+', 'pid' => '\d+']);
        $admin->get('ahg_c2pa_admin_manifest', '/admin/c2pa/object/:id/record/:pid/manifest', 'manifest', ['id' => '\d+', 'pid' => '\d+']);
        $admin->register($routing);

        // Capability discovery at /.well-known/c2pa-info. Symfony treats "." as a
        // segment separator, so a path starting "/." never matched and answered
        // 404. With "/" as the only separator the dot is plain text.
        $routing->prependRoute('ahg_c2pa_well_known', new \ExplicitRoute(
            '/.well-known/c2pa-info',
            ['module' => 'c2pa', 'action' => 'wellKnown'],
            [],
            ['segment_separators' => ['/']]
        ));
    }
}
