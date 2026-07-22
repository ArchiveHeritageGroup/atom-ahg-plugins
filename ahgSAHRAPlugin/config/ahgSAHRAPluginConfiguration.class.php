<?php

/**
 * ahgSAHRAPlugin - South African Heritage Resources Agency permit workflow.
 *
 * National Heritage Resources Act, 1999 (Act 25 of 1999). Implements the
 * researcher -> supervising professor -> SAHRA application lifecycle for
 * heritage permits (s.35 archaeology/palaeontology/meteorites, s.32 export,
 * s.34 structures, s.36 burial grounds & graves).
 *
 * @package    ahgSAHRAPlugin
 * @author     The Archive and Heritage Group
 * @copyright  2026 The Archive and Heritage Group (Pty) Ltd
 */
class ahgSAHRAPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'SAHRA / NHRA heritage permit workflow (Act 25 of 1999)';
    public static $version = '1.0.0';

    protected function registerAutoloader(): void
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'AhgSAHRA\\') === 0) {
                $relativePath = str_replace('AhgSAHRA\\', '', $class);
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

    public function contextLoadFactories(sfEvent $event): void
    {
        $context = $event->getSubject();
        $response = $context->getResponse();
        if ('sahra' === $context->getModuleName()) {
            $response->addStylesheet('/plugins/ahgSAHRAPlugin/css/sahra.css', 'last');
            $response->addJavascript('/plugins/ahgSAHRAPlugin/js/sahra.js', 'last');
        }
    }

    public function initialize(): void
    {
        $this->registerAutoloader();

        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'routingLoadConfiguration']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'sahra';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }

    public function routingLoadConfiguration(sfEvent $event): void
    {
        $routing = $event->getSubject();

        $sahra = new \AtomFramework\Routing\RouteLoader('sahra');

        // Dashboard
        $sahra->any('sahra_index', '/sahra', 'index');

        // Researcher: apply + my applications
        $sahra->any('sahra_apply', '/sahra/apply', 'applicationCreate');
        $sahra->any('sahra_create', '/sahra/apply/create', 'create');
        $sahra->any('sahra_my', '/sahra/my-applications', 'myApplications');

        // Supervisor (professor) endorsement queue + decisions
        $sahra->any('sahra_approvals', '/sahra/approvals', 'pendingApprovals');
        $sahra->any('sahra_endorse', '/sahra/permit/:id/endorse', 'endorse', ['id' => '\d+']);
        $sahra->any('sahra_reject', '/sahra/permit/:id/reject', 'reject', ['id' => '\d+']);

        // SAHRA submission queue + outcome (coordinator/admin)
        $sahra->any('sahra_queue', '/sahra/queue', 'sahraQueue');
        $sahra->any('sahra_submit', '/sahra/permit/:id/submit', 'submitToSahra', ['id' => '\d+']);

        // SAHRA reviewer queue + in-system decision (SAHRA officials)
        $sahra->any('sahra_review', '/sahra/review', 'sahraReview');
        $sahra->any('sahra_decision', '/sahra/permit/:id/decision', 'recordDecision', ['id' => '\d+']);
        $sahra->any('sahra_revoke', '/sahra/permit/:id/revoke', 'revoke', ['id' => '\d+']);
        $sahra->any('sahra_cancel', '/sahra/permit/:id/cancel', 'cancel', ['id' => '\d+']);

        // Reporting obligations
        $sahra->any('sahra_report_add', '/sahra/permit/:id/report/add', 'reportAdd', ['id' => '\d+']);
        $sahra->any('sahra_report_submit', '/sahra/report/:id/submit', 'reportSubmit', ['id' => '\d+']);

        // Permit detail
        $sahra->any('sahra_permit_view', '/sahra/permit/:id', 'permitView', ['id' => '\d+']);

        // All permits (admin), reports, config
        $sahra->any('sahra_permits', '/sahra/permits', 'permits');
        $sahra->any('sahra_reports', '/sahra/reports', 'reports');
        $sahra->any('sahra_config', '/sahra/config', 'config');
        $sahra->any('sahra_reviewer_add', '/sahra/config/reviewer/add', 'reviewerAdd');
        $sahra->any('sahra_reviewer_remove', '/sahra/config/reviewer/:id/remove', 'reviewerRemove', ['id' => '\d+']);

        $sahra->register($routing);
    }
}
