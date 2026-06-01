<?php
/**
 * PSIS / AtoM-AHG - ahgC2paPlugin Configuration (Symfony 1.4 sfPluginConfiguration).
 *
 * C2PA (Coalition for Content Provenance and Authenticity) content credentials:
 * generate, sign and embed C2PA 2.1 manifests on digital-object derivatives, and
 * verify them. PSIS twin of Heratio's ahg-c2pa package.
 *
 * Registers the `c2pa` module + its routes via \AtomFramework\Routing\RouteLoader
 * (same pattern as ahgPreservationPlugin / ahgAiCompliancePlugin). CLI commands
 * (c2pa:verify, c2pa:smoke) are auto-discovered from lib/Commands by the
 * atom-framework CommandRegistry - no registration needed here.
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */
class ahgC2paPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'C2PA content credentials: sign, embed and verify provenance manifests on digital objects';
    public static $version = '0.1.0';
    public static $category = 'ahg';

    public function initialize()
    {
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        if (!in_array('c2pa', $enabledModules, true)) {
            $enabledModules[] = 'c2pa';
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
        $c2pa->get('ahg_c2pa_well_known', '/.well-known/c2pa-info', 'wellKnown');
        $c2pa->any('ahg_c2pa_verify', '/c2pa/verify', 'verify');
        $c2pa->get('ahg_c2pa_manifest', '/c2pa/manifest/:id', 'manifest', ['id' => '\d+']);
        $c2pa->get('ahg_c2pa_manifests', '/c2pa/manifests/:id', 'manifests', ['id' => '\d+']);

        $c2pa->register($routing);
    }
}
