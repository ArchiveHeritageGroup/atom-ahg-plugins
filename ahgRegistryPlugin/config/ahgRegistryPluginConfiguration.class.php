<?php

class ahgRegistryPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'GLAM Community Hub & Registry — directory of institutions, vendors, software, user groups, discussions, blog, and sync API.';
    public static $version = '1.0.0';

    public function initialize()
    {
        $this->registerAutoloader();

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'registry';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }

    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgRegistry\\') === 0) {
                $relativePath = str_replace('AhgRegistry\\', '', $class);
                $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativePath);
                $filePath = __DIR__ . '/../lib/' . $relativePath . '.php';

                if (file_exists($filePath)) {
                    require_once $filePath;
                    return true;
                }
            }

            return false;
        });
    }

    public function loadRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();
        $loader = new \AtomFramework\Routing\RouteLoader('registry');

        // ============================================================
        // Sync API routes (token auth)
        // ============================================================
        $loader->post('registry_api_sync_register', '/registry/api/sync/register', 'apiSyncRegister');
        $loader->post('registry_api_sync_heartbeat', '/registry/api/sync/heartbeat', 'apiSyncHeartbeat');
        $loader->post('registry_api_sync_update', '/registry/api/sync/update', 'apiSyncUpdate');
        $loader->get('registry_api_sync_status', '/registry/api/sync/status', 'apiSyncStatus');
        $loader->get('registry_api_directory', '/registry/api/directory', 'apiDirectory');
        $loader->get('registry_api_software_latest', '/registry/api/software/:slug/latest', 'apiSoftwareLatest', ['slug' => '[a-z0-9-]+']);

        // ============================================================
        // Admin routes
        // ============================================================
        $loader->any('registry_admin', '/registry/admin', 'adminDashboard');
        $loader->any('registry_admin_institutions', '/registry/admin/institutions', 'adminInstitutions');
        $loader->post('registry_admin_institution_verify', '/registry/admin/institutions/verify', 'adminInstitutionVerify');
        $loader->any('registry_admin_vendors', '/registry/admin/vendors', 'adminVendors');
        $loader->post('registry_admin_vendor_verify', '/registry/admin/vendors/verify', 'adminVendorVerify');
        $loader->any('registry_admin_software', '/registry/admin/software', 'adminSoftware');
        $loader->post('registry_admin_software_verify', '/registry/admin/software/verify', 'adminSoftwareVerify');
        $loader->any('registry_admin_groups', '/registry/admin/groups', 'adminGroups');
        $loader->any('registry_admin_group_verify', '/registry/admin/groups/verify', 'adminGroupVerify');

        // Admin: Standards
        $loader->any('registry_admin_standards', '/registry/admin/standards', 'adminStandards');
        $loader->any('registry_admin_standard_edit', '/registry/admin/standards/:id/edit', 'adminStandardEdit', ['id' => '\d+']);
        $loader->any('registry_admin_standard_new', '/registry/admin/standards/new', 'adminStandardEdit');
        $loader->any('registry_admin_extension_edit', '/registry/admin/standards/:standardId/extensions/:id/edit', 'adminExtensionEdit', ['standardId' => '\d+', 'id' => '\d+']);
        $loader->any('registry_admin_extension_new', '/registry/admin/standards/:standardId/extensions/new', 'adminExtensionEdit', ['standardId' => '\d+']);
        $loader->post('registry_admin_extension_delete', '/registry/admin/standards/extensions/:id/delete', 'adminExtensionDelete', ['id' => '\d+']);
        $loader->any('registry_admin_setup_guides', '/registry/admin/setup-guides', 'adminSetupGuides');
        $loader->any('registry_admin_discussions', '/registry/admin/discussions', 'adminDiscussions');
        $loader->any('registry_admin_blog', '/registry/admin/blog', 'adminBlog');
        $loader->any('registry_admin_reviews', '/registry/admin/reviews', 'adminReviews');
        $loader->any('registry_admin_sync', '/registry/admin/sync', 'adminSync');
        $loader->any('registry_admin_settings', '/registry/admin/settings', 'adminSettings');
        $loader->any('registry_admin_footer', '/registry/admin/footer', 'adminFooter');
        $loader->any('registry_admin_email', '/registry/admin/email', 'adminEmail');
        $loader->any('registry_admin_import', '/registry/admin/import', 'adminImport');
        $loader->any('registry_admin_users', '/registry/admin/users', 'adminUsers');
        $loader->any('registry_admin_user_manage', '/registry/admin/users/manage', 'adminUserManage');
        $loader->any('registry_admin_user_edit', '/registry/admin/users/:id/edit', 'adminUserEdit', ['id' => '\d+']);
        $loader->post('registry_admin_user_reset_password', '/registry/admin/users/:id/reset-password', 'adminUserResetPassword', ['id' => '\d+']);

        // Admin CRUD: edit any entity by ID
        $loader->any('registry_admin_institution_edit', '/registry/admin/institutions/:id/edit', 'institutionEdit', ['id' => '\d+']);
        $loader->any('registry_admin_vendor_edit', '/registry/admin/vendors/:id/edit', 'vendorEdit', ['id' => '\d+']);
        $loader->any('registry_admin_software_edit', '/registry/admin/software/:id/edit', 'myVendorSoftwareEdit', ['id' => '\d+']);
        $loader->any('registry_admin_group_edit', '/registry/admin/groups/:id/edit', 'adminGroupEdit', ['id' => '\d+']);
        $loader->any('registry_admin_group_members', '/registry/admin/groups/:id/members', 'adminGroupMembers', ['id' => '\d+']);
        $loader->post('registry_admin_group_email', '/registry/admin/groups/:id/email', 'adminGroupEmail', ['id' => '\d+']);

        // ============================================================
        // Self-service: Institution
        // ============================================================
        $loader->any('registry_my_institution', '/registry/my/institution', 'myInstitutionDashboard');
        $loader->any('registry_my_institution_register', '/registry/my/institution/register', 'institutionRegister');
        $loader->any('registry_my_institution_edit', '/registry/my/institution/edit', 'institutionEdit');
        $loader->any('registry_my_institution_contacts', '/registry/my/institution/contacts', 'myInstitutionContacts');
        $loader->any('registry_my_institution_contact_add', '/registry/my/institution/contacts/add', 'myInstitutionContactAdd');
        $loader->any('registry_my_institution_contact_edit', '/registry/my/institution/contacts/:id/edit', 'myInstitutionContactEdit', ['id' => '\d+']);
        $loader->any('registry_my_institution_instances', '/registry/my/institution/instances', 'myInstitutionInstances');
        $loader->any('registry_my_institution_instance_add', '/registry/my/institution/instances/add', 'myInstitutionInstanceAdd');
        $loader->any('registry_my_institution_instance_edit', '/registry/my/institution/instances/:id/edit', 'myInstitutionInstanceEdit', ['id' => '\d+']);
        $loader->any('registry_my_institution_software', '/registry/my/institution/software', 'myInstitutionSoftware');
        $loader->any('registry_my_institution_vendors', '/registry/my/institution/vendors', 'myInstitutionVendors');
        $loader->any('registry_my_institution_review', '/registry/my/institution/review/:type/:id', 'myInstitutionReview', ['type' => '[a-z]+', 'id' => '\d+']);

        // ============================================================
        // Self-service: Vendor
        // ============================================================
        $loader->any('registry_my_vendor', '/registry/my/vendor', 'myVendorDashboard');
        $loader->any('registry_my_vendor_register', '/registry/my/vendor/register', 'vendorRegister');
        $loader->any('registry_my_vendor_edit', '/registry/my/vendor/edit', 'vendorEdit');
        $loader->any('registry_my_vendor_contacts', '/registry/my/vendor/contacts', 'myVendorContacts');
        $loader->any('registry_my_vendor_contact_add', '/registry/my/vendor/contacts/add', 'myVendorContactAdd');
        $loader->any('registry_my_vendor_contact_edit', '/registry/my/vendor/contacts/:id/edit', 'myVendorContactEdit', ['id' => '\d+']);
        $loader->any('registry_my_vendor_clients', '/registry/my/vendor/clients', 'myVendorClients');
        $loader->any('registry_my_vendor_client_add', '/registry/my/vendor/clients/add', 'myVendorClientAdd');
        $loader->any('registry_my_vendor_software', '/registry/my/vendor/software', 'myVendorSoftware');
        $loader->any('registry_my_vendor_software_add', '/registry/my/vendor/software/add', 'myVendorSoftwareAdd');
        $loader->any('registry_my_vendor_software_edit', '/registry/my/vendor/software/:id/edit', 'myVendorSoftwareEdit', ['id' => '\d+']);
        $loader->any('registry_my_vendor_software_releases', '/registry/my/vendor/software/:id/releases', 'myVendorSoftwareReleases', ['id' => '\d+']);
        $loader->any('registry_my_vendor_software_release_add', '/registry/my/vendor/software/:id/releases/add', 'myVendorSoftwareReleaseAdd', ['id' => '\d+']);
        $loader->any('registry_my_vendor_software_upload', '/registry/my/vendor/software/:id/upload', 'myVendorSoftwareUpload', ['id' => '\d+']);
        $loader->any('registry_my_vendor_call_log', '/registry/my/vendor/call-log', 'myVendorCallLog');
        $loader->any('registry_my_vendor_call_log_add', '/registry/my/vendor/call-log/add', 'myVendorCallLogAdd');
        $loader->any('registry_my_vendor_call_log_edit', '/registry/my/vendor/call-log/:id/edit', 'myVendorCallLogEdit', ['id' => '\d+']);
        $loader->any('registry_my_vendor_call_log_view', '/registry/my/vendor/call-log/:id', 'myVendorCallLogView', ['id' => '\d+']);

        // ============================================================
        // Self-service: Groups & Blog
        // ============================================================
        $loader->any('registry_my_groups', '/registry/my/groups', 'myGroups');
        $loader->any('registry_my_group_create', '/registry/my/groups/create', 'groupCreate');
        $loader->any('registry_my_group_edit', '/registry/my/groups/:id/edit', 'groupEdit', ['id' => '\d+']);
        $loader->any('registry_my_group_members', '/registry/my/groups/:id/members', 'groupMembersManage', ['id' => '\d+']);
        $loader->any('registry_my_blog', '/registry/my/blog', 'myBlog');
        $loader->any('registry_my_blog_new', '/registry/my/blog/new', 'blogNew');
        $loader->any('registry_my_blog_edit', '/registry/my/blog/:id/edit', 'blogEdit', ['id' => '\d+']);

        // ============================================================
        // Public: Community, Groups, Discussions, Blog
        // ============================================================
        $loader->any('registry_community', '/registry/community', 'community');
        $loader->any('registry_groups', '/registry/groups', 'groupBrowse');
        $loader->any('registry_group_join', '/registry/groups/:slug/join', 'groupJoin', ['slug' => '[a-z0-9-]+']);
        $loader->any('registry_group_leave', '/registry/groups/:slug/leave', 'groupLeave', ['slug' => '[a-z0-9-]+']);
        $loader->post('registry_group_toggle_notifications', '/registry/groups/:slug/notifications', 'groupToggleNotifications', ['slug' => '[a-z0-9-]+']);
        $loader->any('registry_group_discussions', '/registry/groups/:slug/discussions', 'discussionList', ['slug' => '[a-z0-9-]+']);
        $loader->any('registry_group_discussion_new', '/registry/groups/:slug/discussions/new', 'discussionNew', ['slug' => '[a-z0-9-]+']);
        $loader->any('registry_group_discussion_view', '/registry/groups/:slug/discussions/:id', 'discussionView', ['slug' => '[a-z0-9-]+', 'id' => '\d+']);
        $loader->post('registry_group_discussion_reply', '/registry/groups/:slug/discussions/:id/reply', 'discussionReply', ['slug' => '[a-z0-9-]+', 'id' => '\d+']);
        $loader->any('registry_group_members', '/registry/groups/:slug/members', 'groupMembers', ['slug' => '[a-z0-9-]+']);
        $loader->any('registry_group_view', '/registry/groups/:slug', 'groupView', ['slug' => '[a-z0-9-]+']);

        $loader->any('registry_blog', '/registry/blog', 'blogList');
        $loader->post('registry_blog_reply', '/registry/blog/:slug/reply', 'blogReply', ['slug' => '[a-z0-9-]+']);
        $loader->any('registry_blog_view', '/registry/blog/:slug', 'blogView', ['slug' => '[a-z0-9-]+']);

        // ============================================================
        // Public: Browse, Search, Map
        // ============================================================
        $loader->any('registry_institutions', '/registry/institutions', 'institutionBrowse');
        $loader->any('registry_vendors', '/registry/vendors', 'vendorBrowse');
        $loader->any('registry_software', '/registry/software', 'softwareBrowse');
        $loader->any('registry_software_releases', '/registry/software/:slug/releases', 'softwareReleases', ['slug' => '[a-z0-9-]+']);
        $loader->any('registry_software_guides', '/registry/software/:slug/setup', 'setupGuideBrowse', ['slug' => '[a-z0-9-]+']);
        $loader->any('registry_software_guide_view', '/registry/software/:slug/setup/:guideSlug', 'setupGuideView', ['slug' => '[a-z0-9-]+', 'guideSlug' => '[a-z0-9-]+']);
        $loader->any('registry_standards', '/registry/standards', 'standardBrowse');
        $loader->any('registry_instance_view', '/registry/instances/:id', 'instanceView', ['id' => '\d+']);
        $loader->any('registry_search', '/registry/search', 'search');
        $loader->any('registry_map', '/registry/map', 'map');

        // ============================================================
        // Public: Detail views (catch-all slug — must be FIRST = checked LAST)
        // ============================================================
        $loader->any('registry_institution_view', '/registry/institutions/:slug', 'institutionView', ['slug' => '[a-z0-9-]+']);
        $loader->any('registry_vendor_view', '/registry/vendors/:slug', 'vendorView', ['slug' => '[a-z0-9-]+']);
        $loader->any('registry_software_view', '/registry/software/:slug', 'softwareView', ['slug' => '[a-z0-9-]+']);
        $loader->any('registry_standard_view', '/registry/standards/:slug', 'standardView', ['slug' => '[a-z0-9-]+']);

        // Favorites
        $loader->post('registry_favorite_toggle', '/registry/favorite/toggle', 'favoriteToggle');
        $loader->any('registry_my_favorites', '/registry/my/favorites', 'myFavorites');

        // Newsletter (public)
        $loader->any('registry_newsletter_subscribe', '/registry/newsletter/subscribe', 'newsletterSubscribe');
        $loader->any('registry_newsletter_unsubscribe', '/registry/newsletter/unsubscribe', 'newsletterUnsubscribe');
        $loader->any('registry_newsletter_view', '/registry/newsletters/:id', 'newsletterView', ['id' => '\d+']);
        $loader->any('registry_newsletters', '/registry/newsletters', 'newsletterBrowse');
        $loader->any('registry_admin_newsletters', '/registry/admin/newsletters', 'adminNewsletters');
        $loader->any('registry_admin_newsletter_new', '/registry/admin/newsletters/new', 'adminNewsletterForm');
        $loader->any('registry_admin_newsletter_edit', '/registry/admin/newsletters/:id/edit', 'adminNewsletterForm', ['id' => '\d+']);
        $loader->post('registry_admin_newsletter_send', '/registry/admin/newsletters/:id/send', 'adminNewsletterSend', ['id' => '\d+']);
        $loader->any('registry_admin_subscribers', '/registry/admin/subscribers', 'adminSubscribers');

        // Auth routes
        $loader->any('registry_login', '/registry/login', 'login');
        $loader->any('registry_register', '/registry/register', 'register');
        $loader->any('registry_logout', '/registry/logout', 'logout');
        $loader->any('registry_oauth_start', '/registry/oauth/:provider', 'oauthStart', ['provider' => '[a-z]+']);
        $loader->any('registry_oauth_callback', '/registry/oauth/:provider/callback', 'oauthCallback', ['provider' => '[a-z]+']);

        // Registry home (catch-all for /registry)
        $loader->any('registry_home', '/registry', 'index');

        $loader->register($routing);
    }
}
