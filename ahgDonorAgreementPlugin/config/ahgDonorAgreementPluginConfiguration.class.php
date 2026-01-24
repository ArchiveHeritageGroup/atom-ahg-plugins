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
        $routing = $event->getSubject();

        // Dashboard
        $routing->prependRoute('donor_dashboard', new sfRoute(
            '/donor/dashboard',
            ['module' => 'donorAgreement', 'action' => 'dashboard']
        ));

        // Agreement CRUD
        $routing->prependRoute('donor_agreement_browse', new sfRoute(
            '/donor/agreement/browse',
            ['module' => 'donorAgreement', 'action' => 'browse']
        ));
        $routing->prependRoute('donor_agreement_add', new sfRoute(
            '/donor/agreement/add',
            ['module' => 'donorAgreement', 'action' => 'add']
        ));
        $routing->prependRoute('donor_agreement_view', new sfRoute(
            '/donor/agreement/:id',
            ['module' => 'donorAgreement', 'action' => 'view'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('donor_agreement_edit', new sfRoute(
            '/donor/agreement/:id/edit',
            ['module' => 'donorAgreement', 'action' => 'edit'],
            ['id' => '\d+']
        ));
        $routing->prependRoute('donor_agreement_delete', new sfRoute(
            '/donor/agreement/:id/delete',
            ['module' => 'donorAgreement', 'action' => 'delete'],
            ['id' => '\d+']
        ));

        // Reminders
        $routing->prependRoute('donor_agreement_reminders', new sfRoute(
            '/donor/agreement/reminders',
            ['module' => 'donorAgreement', 'action' => 'reminders']
        ));

        // Autocomplete
        $routing->prependRoute('donor_autocomplete_accessions', new sfRoute(
            '/donor/autocomplete/accessions',
            ['module' => 'donorAgreement', 'action' => 'autocompleteAccessions']
        ));
        $routing->prependRoute('donor_autocomplete_records', new sfRoute(
            '/donor/autocomplete/records',
            ['module' => 'donorAgreement', 'action' => 'autocompleteRecords']
        ));
    }
}
