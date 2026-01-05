<?php

class ahgDonorAgreementPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Donor and institution agreement management with contract uploads, reminders, and compliance tracking.';
    public static $version = '1.0.0';

    public function initialize(): void
    {
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

        // Documents
        $routing->prependRoute('donor_agreement_document_add', new sfRoute(
            '/donor/agreement/:id/document/add',
            ['module' => 'donorAgreement', 'action' => 'addDocument'],
            ['id' => '\d+']
        ));

        $routing->prependRoute('donor_agreement_document_download', new sfRoute(
            '/donor/agreement/document/:id/download',
            ['module' => 'donorAgreement', 'action' => 'downloadDocument'],
            ['id' => '\d+']
        ));

        // Reminders
        $routing->prependRoute('donor_agreement_reminder_add', new sfRoute(
            '/donor/agreement/:id/reminder/add',
            ['module' => 'donorAgreement', 'action' => 'addReminder'],
            ['id' => '\d+']
        ));

        // Autocomplete
        $routing->prependRoute('donor_autocomplete', new sfRoute(
            '/donor/autocomplete',
            ['module' => 'donorAgreement', 'action' => 'autocompleteDonor']
        ));
    }
}
