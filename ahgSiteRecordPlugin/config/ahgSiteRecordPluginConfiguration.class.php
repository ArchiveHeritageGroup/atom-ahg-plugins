<?php

/**
 * Site Record - field recording for archaeological, heritage and rock art sites.
 *
 * Replaces RARI's standalone rock_forms application (issue #299). A site record
 * extends an existing authority record rather than duplicating it: the actor holds
 * the name, dates and ISAAR fields, and this plugin holds the field-recording
 * data - site number, region, coordinates, map sheet, and the recorded attributes.
 *
 * Panel condition assessments are NOT implemented here. ahgConditionPlugin already
 * has a data-driven template system (spectrum_condition_template, keyed by
 * material_type), so a rock art panel assessment is seed data - see
 * database/seeds/rock_art_panel_template.sql. Nothing in ahgConditionPlugin is
 * modified.
 *
 * Locality is the sensitive part. Precise coordinates are what enable looting and
 * vandalism, so nothing reads them directly - every path goes through
 * LocalityVisibilityService, which coarsens for anyone without clearance and
 * treats a record with no explicit setting as sensitive.
 */
class ahgSiteRecordPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Field recording for archaeological, heritage and rock art sites, with role-gated locality';
    public static $version = '1.0.0';

    public function initialize(): void
    {
        $this->registerAutoloader();

        $enabledModules = sfConfig::get('sf_enabled_modules', []);

        if (!in_array('siteRecord', $enabledModules, true)) {
            $enabledModules[] = 'siteRecord';
            sfConfig::set('sf_enabled_modules', $enabledModules);
        }

        $this->dispatcher->connect('routing.load_configuration', [$this, 'configureRouting']);

        // extension.json declares the panel, but something has to render it -
        // an AHG theme or ahgDisplayPlugin. With neither installed the panel
        // appears nowhere, so the plugin contributes its own renderer and stands
        // down when one of those is present. See SiteRecordPanelInjector.
        $this->dispatcher->connect(
            'response.filter_content',
            ['\AhgSiteRecordPlugin\Listeners\SiteRecordPanelInjector', 'filter']
        );

        // Withhold the raw ISAAR field that carries locality from readers without
        // clearance. Structuring locality into ahg_site_record gates the
        // structured copy, but the original field keeps showing the same thing to
        // everyone. OFF unless the instance sets site_record_gate_locality_field.
        // See LocalityFieldRedactor.
        $this->dispatcher->connect(
            'response.filter_content',
            ['\AhgSiteRecordPlugin\Listeners\LocalityFieldRedactor', 'filter']
        );

        // Only 'manage' and 'browse' are rendered without ahgThemeB5Plugin, so a
        // staff-facing entry has to go in 'manage' - anything else silently
        // renders nowhere.
        if (class_exists('AhgNav')) {
            AhgNav::register('manage', 'siteRecord', [
                'url' => '/index.php/site-record',
                'label' => 'Site records',
                'credentials' => ['contributor', 'editor', 'administrator'],
                'icon' => 'fas fa-map-location-dot',
                'weight' => 45,
            ]);
        }
    }

    /**
     * PSR-4 for this plugin's own namespace.
     *
     * Symfony 1.4's autoloader indexes classes by file scan and does not
     * understand namespaces, so a namespaced plugin registers its own - the same
     * approach ahgProvenancePlugin uses.
     */
    private function registerAutoloader(): void
    {
        $libPath = realpath(__DIR__.'/../lib');

        if (false === $libPath) {
            return;
        }

        spl_autoload_register(static function ($class) use ($libPath) {
            $prefix = 'AhgSiteRecordPlugin\\';

            if (0 !== strpos($class, $prefix)) {
                return false;
            }

            $file = $libPath.'/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';

            if (file_exists($file)) {
                require_once $file;

                return true;
            }

            return false;
        });
    }

    public function configureRouting(sfEvent $event): void
    {
        $router = new \AtomFramework\Routing\RouteLoader('siteRecord');

        $router->get('site_record_browse', '/site-record', 'browse');
        $router->get('site_record_view', '/site-record/:id', 'view', ['id' => '\d+']);
        $router->any('site_record_edit', '/site-record/:id/edit', 'edit', ['id' => '\d+']);

        // Creating a site record always starts from the authority record it
        // describes - there is no free-floating site.
        $router->any('site_record_add', '/site-record/add/:actorId', 'edit', ['actorId' => '\d+']);

        // Destructive, so POST only. The route accepting POST is not the guard -
        // see actions.class.php, which checks the method and the CSRF token.
        $router->post('site_record_delete', '/site-record/:id/delete', 'delete', ['id' => '\d+']);

        $router->register($event->getSubject());
    }
}
