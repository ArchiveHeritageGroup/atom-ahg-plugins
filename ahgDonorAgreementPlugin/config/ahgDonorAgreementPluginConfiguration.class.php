<?php

class ahgDonorAgreementPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Donor and institution agreement management with contract uploads, reminders, and compliance tracking.';
    public static $version = '1.1.0';

    public function initialize(): void
    {
        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'donorAgreement';
        $enabledModules[] = 'donor';

        sfConfig::set('sf_enabled_modules', $enabledModules);

        $this->dispatcher->connect('routing.load_configuration', [$this, 'configureRouting']);
    }

    public function configureRouting(sfEvent $event): void
    {
        $router = new \AtomFramework\Routing\RouteLoader('donorAgreement');

        // Dashboard
        $router->get('donor_dashboard', '/donor/dashboard', 'dashboard');

        // Agreement CRUD
        $router->get('donor_agreement_browse', '/donor/agreement/browse', 'browse');
        $router->get('donor_agreement_add', '/donor/agreement/add', 'add');
        $router->get('donor_agreement_view', '/donor/agreement/:id', 'view', ['id' => '\d+']);
        $router->get('donor_agreement_edit', '/donor/agreement/:id/edit', 'edit', ['id' => '\d+']);
        $router->any('donor_agreement_delete', '/donor/agreement/:id/delete', 'delete', ['id' => '\d+']);

        // Reminders
        $router->get('donor_agreement_reminders', '/donor/agreement/reminders', 'reminders');

        // Autocomplete
        $router->get('donor_autocomplete_accessions', '/donor/autocomplete/accessions', 'autocompleteAccessions');
        $router->get('donor_autocomplete_records', '/donor/autocomplete/records', 'autocompleteRecords');

        $router->register($event->getSubject());
    }
}
