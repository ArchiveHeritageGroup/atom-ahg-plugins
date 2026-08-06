<?php

class ahgAccessionManagePluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'First-class accession management with intake queue, appraisal, containers, and rights';
    public static $version = '2.0.0';

    public function initialize()
    {
        $this->registerAutoloader();

        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);

        // Stop an unresolvable donor value reaching base AtoM's relatedDonor
        // component. See dropUnresolvableDonor() for why.
        $this->dispatcher->connect('request.filter_parameters', [$this, 'dropUnresolvableDonor']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'accessionManage';
        $enabledModules[] = 'accession';
        $enabledModules[] = 'accessionIntake';
        $enabledModules[] = 'accessionAppraisal';
        $enabledModules[] = 'accessionContainer';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    /**
     * Remove a donor "resource" value that does not resolve to a real record.
     *
     * Base AtoM dereferences the parse result without checking it:
     *
     *   $this->relation->object = $params['_sf_route']->resource;
     *   if (null === $this->contactInformation = $this->relation->object->getPrimaryContact()) {
     *
     * (qtAccessionPlugin/modules/accession/actions/relatedDonorComponent.class.php:149)
     *
     * When the submitted value does not correspond to an existing donor, resource
     * is null and saving an accession dies with "Call to a member function
     * getPrimaryContact() on null" - a white screen that loses everything typed
     * into the form. Reproduced on archaeology 2026-08-05 and again 2026-08-06.
     *
     * qtAccessionPlugin is base AtoM and is not ours to patch. This plugin does
     * carry a corrected AccessionEditAction with a donorValueResolves() guard,
     * but it never runs: qtAccessionPlugin is in AtoM's hardcoded plugin list and
     * so registers the AccessionEditAction class name first, and the autoloader
     * keeps the first registration. Forcing our class to win would swap a
     * live-tested code path for one that has, on the evidence, never executed.
     * Not something to do to a running site.
     *
     * So the value is dropped before base ever sees it. The block is skipped
     * (guarded by isset), the accession saves with every other field intact, and
     * the donor link is simply not made. Losing one unresolvable field beats
     * losing the whole form.
     *
     * Resolution is checked against the slug table rather than the router,
     * because this runs before routing is necessarily usable.
     */
    public function dropUnresolvableDonor(sfEvent $event, $parameters)
    {
        if (!is_array($parameters) || empty($parameters['resource'])) {
            return $parameters;
        }

        // Only the accession forms; this event fires on every request.
        $module = $parameters['module'] ?? null;

        if ('accession' !== $module) {
            return $parameters;
        }

        $value = $parameters['resource'];

        if (!is_string($value) || '' === trim($value)) {
            return $parameters;
        }

        if ($this->slugExists($this->slugFromValue($value))) {
            return $parameters;
        }

        unset($parameters['resource']);

        try {
            $this->dispatcher->notify(new sfEvent(
                $this,
                'application.log',
                [sprintf('ahgAccessionManage: dropped unresolvable donor value "%s" before base relatedDonor could dereference it', $value)]
            ));
        } catch (Exception $e) {
            // Logging must never be the thing that breaks the save.
        }

        return $parameters;
    }

    /**
     * The trailing path segment of a resource URL, which is the slug.
     */
    protected function slugFromValue(string $value): string
    {
        $path = parse_url(trim($value), PHP_URL_PATH);

        if (!is_string($path) || '' === $path) {
            $path = trim($value);
        }

        $segments = array_values(array_filter(explode('/', $path), static function ($s) {
            return '' !== $s && 'index.php' !== $s;
        }));

        return $segments === [] ? '' : (string) end($segments);
    }

    protected function slugExists(string $slug): bool
    {
        if ('' === $slug) {
            return false;
        }

        try {
            $connection = Propel::getConnection();
            $statement = $connection->prepare('SELECT 1 FROM slug WHERE slug = ? LIMIT 1');
            $statement->execute([$slug]);

            return false !== $statement->fetchColumn();
        } catch (Exception $e) {
            // If the check itself cannot run, leave the value alone rather than
            // silently discarding something that may well be valid.
            return true;
        }
    }

    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgAccessionManage\\') === 0) {
                $relativePath = str_replace('AhgAccessionManage\\', '', $class);
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

        // =====================================================================
        // accession module routes (catch-all slug routes registered first = checked last)
        // =====================================================================
        $accession = new \AtomFramework\Routing\RouteLoader('accession');
        $accession->any('accession_view_override', '/accession/:slug', 'index', ['slug' => '[a-zA-Z0-9_.-]+']);
        $accession->any('accession_delete_override', '/accession/:slug/delete', 'delete', ['slug' => '[a-zA-Z0-9_.-]+']);
        $accession->any('accession_edit_override', '/accession/:slug/edit', 'edit', ['slug' => '[a-zA-Z0-9_.-]+']);
        $accession->register($routing);

        // Add route uses AddActionRoute to prevent stealing edit/view URLs
        $routing->prependRoute('accession_add_override', new \AddActionRoute(
            '/accession/add',
            ['module' => 'accession', 'action' => 'edit']
        ));

        // =====================================================================
        // accessionManage module routes (browse)
        // =====================================================================
        $manage = new \AtomFramework\Routing\RouteLoader('accessionManage');
        $manage->any('accession_browse_override', '/accession/browse', 'browse');
        $manage->any('accession_dashboard', '/admin/accessions/dashboard', 'dashboard');
        $manage->register($routing);

        // =====================================================================
        // accessionIntake module routes (M1: Intake Queue)
        // =====================================================================
        $intake = new \AtomFramework\Routing\RouteLoader('accessionIntake');

        // Catch-all :id routes first (checked last)
        $intake->any('accession_intake_submit', '/admin/accessions/:id/submit', 'submit', ['id' => '\d+']);
        $intake->any('accession_intake_review', '/admin/accessions/:id/review', 'review', ['id' => '\d+']);
        $intake->any('accession_intake_accept', '/admin/accessions/:id/accept', 'accept', ['id' => '\d+']);
        $intake->any('accession_intake_reject', '/admin/accessions/:id/reject', 'reject', ['id' => '\d+']);
        $intake->any('accession_intake_return', '/admin/accessions/:id/return', 'returnRevision', ['id' => '\d+']);
        $intake->any('accession_intake_timeline', '/admin/accessions/:id/timeline', 'timeline', ['id' => '\d+']);
        $intake->any('accession_intake_checklist', '/admin/accessions/:id/checklist', 'checklist', ['id' => '\d+']);
        $intake->any('accession_intake_attachments', '/admin/accessions/:id/attachments', 'attachments', ['id' => '\d+']);
        $intake->any('accession_intake_detail', '/admin/accessions/:id/intake', 'queueDetail', ['id' => '\d+']);

        // Specific routes (checked first)
        $intake->any('accession_intake_queue', '/admin/accessions/queue', 'queue');
        $intake->any('accession_intake_assign', '/admin/accessions/queue/assign', 'assign');
        $intake->any('accession_intake_config', '/admin/accessions/config', 'config');
        $intake->any('accession_intake_numbering', '/admin/accessions/numbering', 'numbering');

        // API routes
        $intake->any('accession_api_checklist_toggle', '/api/accession/checklist/:id/toggle', 'apiChecklistToggle', ['id' => '\d+']);
        $intake->any('accession_api_checklist_apply', '/api/accession/checklist/apply-template', 'apiChecklistApplyTemplate');
        $intake->any('accession_api_attachment_upload', '/api/accession/attachment/upload', 'apiAttachmentUpload');
        $intake->any('accession_api_attachment_delete', '/api/accession/attachment/:id/delete', 'apiAttachmentDelete', ['id' => '\d+']);

        $intake->register($routing);

        // =====================================================================
        // accessionAppraisal module routes (M2: Appraisal & Valuation)
        // =====================================================================
        $appraisal = new \AtomFramework\Routing\RouteLoader('accessionAppraisal');

        // :id routes first
        $appraisal->any('accession_appraisal_form', '/admin/accessions/:id/appraisal', 'appraisal', ['id' => '\d+']);
        $appraisal->any('accession_appraisal_save', '/admin/accessions/:id/appraisal/save', 'appraisalSave', ['id' => '\d+']);
        $appraisal->any('accession_valuation_view', '/admin/accessions/:id/valuation', 'valuation', ['id' => '\d+']);
        $appraisal->any('accession_valuation_add', '/admin/accessions/:id/valuation/add', 'valuationAdd', ['id' => '\d+']);

        // Specific routes
        $appraisal->any('accession_appraisal_templates', '/admin/accessions/appraisal-templates', 'appraisalTemplates');
        $appraisal->any('accession_valuation_report', '/admin/accessions/valuation-report', 'valuationReport');

        // API routes
        $appraisal->any('accession_api_appraisal_score', '/api/accession/appraisal/:id/score', 'apiAppraisalScore', ['id' => '\d+']);

        $appraisal->register($routing);

        // =====================================================================
        // accessionContainer module routes (M3: Containers & Rights)
        // =====================================================================
        $container = new \AtomFramework\Routing\RouteLoader('accessionContainer');

        // :id routes first
        $container->any('accession_containers_view', '/admin/accessions/:id/containers', 'containers', ['id' => '\d+']);
        $container->any('accession_rights_view', '/admin/accessions/:id/rights', 'rights', ['id' => '\d+']);

        // API routes
        $container->any('accession_api_container_save', '/api/accession/container/save', 'apiContainerSave');
        $container->any('accession_api_container_delete', '/api/accession/container/:id/delete', 'apiContainerDelete', ['id' => '\d+']);
        $container->any('accession_api_container_item_save', '/api/accession/container-item/save', 'apiContainerItemSave');
        $container->any('accession_api_container_item_delete', '/api/accession/container-item/:id/delete', 'apiContainerItemDelete', ['id' => '\d+']);
        $container->any('accession_api_container_item_link', '/api/accession/container-item/:id/link', 'apiContainerItemLink', ['id' => '\d+']);
        $container->any('accession_api_barcode_lookup', '/api/accession/barcode/lookup', 'apiBarcodeLookup');
        $container->any('accession_api_rights_save', '/api/accession/rights/save', 'apiRightsSave');
        $container->any('accession_api_rights_delete', '/api/accession/rights/:id/delete', 'apiRightsDelete', ['id' => '\d+']);
        $container->any('accession_api_rights_inherit', '/api/accession/rights/:id/inherit', 'apiRightsInherit', ['id' => '\d+']);

        $container->register($routing);
    }
}
