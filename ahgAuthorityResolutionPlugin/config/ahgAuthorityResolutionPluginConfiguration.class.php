<?php

/**
 * ahgAuthorityResolutionPlugin Configuration
 *
 * Registers the authorityResolution module + its admin routes for the
 * Task 5 review UI. Routing uses AtomFramework\Routing\RouteLoader so the
 * URL definitions stay close to the action method names.
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * Licensed under the GNU General Public License v3.0 or later, matching
 * the parent atom-ahg-plugins repository.
 */
class ahgAuthorityResolutionPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Authority Resolution Engine: evidence-based entity disambiguation with provenance.';
    public static $version = '0.1.0';
    public static $category = 'authority';

    public function initialize()
    {
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        if (!in_array('authorityResolution', $enabledModules, true)) {
            $enabledModules[] = 'authorityResolution';
        }
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    public function addRoutes(sfEvent $event)
    {
        $r = new \AtomFramework\Routing\RouteLoader('authorityResolution');

        // Queue list
        $r->any('ar_auth_res_index', '/admin/authorityResolution', 'index');

        // Review screen + decision actions
        $r->any('ar_auth_res_review', '/admin/authorityResolution/:id/review', 'review', ['id' => '\d+']);
        $r->post('ar_auth_res_link', '/admin/authorityResolution/:id/link', 'link', ['id' => '\d+']);
        $r->post('ar_auth_res_link_different', '/admin/authorityResolution/:id/link-different', 'linkDifferent', ['id' => '\d+']);
        // Task 6: create-new sub-workflow (GET form + POST submit)
        $r->get('ar_auth_res_create_new', '/admin/authorityResolution/:id/create-new', 'createNew', ['id' => '\d+']);
        $r->post('ar_auth_res_create_new_submit', '/admin/authorityResolution/:id/create-new-submit', 'createNewSubmit', ['id' => '\d+']);
        $r->post('ar_auth_res_park', '/admin/authorityResolution/:id/park', 'park', ['id' => '\d+']);
        $r->post('ar_auth_res_reject', '/admin/authorityResolution/:id/reject', 'reject', ['id' => '\d+']);

        // Task 7: park queue (dedicated screen + un-park + dashboard JSON)
        $r->any('ar_auth_res_park_list', '/admin/authorityResolution/park', 'parkList');
        $r->post('ar_auth_res_unpark', '/admin/authorityResolution/park/:id/unpark', 'unpark', ['id' => '\d+']);
        $r->any('ar_auth_res_park_dashboard_json', '/admin/authorityResolution/park/dashboard.json', 'parkDashboardJson');

        // JSON typeahead for "link different"
        $r->any('ar_auth_res_lookup', '/admin/authorityResolution/lookup', 'lookup');

        // Task 6: external-lookup adapter settings (admin)
        $r->get('ar_auth_res_lookup_settings', '/admin/authorityResolution/settings/lookup', 'lookupSettings');
        $r->post('ar_auth_res_lookup_settings_save', '/admin/authorityResolution/settings/lookup', 'lookupSettingsSave');

        $r->register($event->getSubject());
    }
}
