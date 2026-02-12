<?php

use AtomFramework\Http\Controllers\AhgController;

class DonorEditAction extends AhgController
{
    public function execute($request)
    {
        // Bootstrap Laravel QB
        if (!class_exists('Illuminate\Database\Capsule\Manager')) {
            require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        }

        $culture = $this->context->user->getCulture();
        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        // ACL check — donors require authenticated editor/admin
        $user = $this->context->user;
        if (!$user->isAuthenticated() || !($user->hasGroup(\AtomExtensions\Constants\AclConstants::ADMINISTRATOR_ID) || $user->hasGroup(\AtomExtensions\Constants\AclConstants::EDITOR_ID))) {
            \AtomExtensions\Services\AclService::forwardUnauthorized();
        }

        $slug = $request->getParameter('slug');
        $this->isNew = empty($slug);

        if (!$this->isNew) {
            // Edit existing donor
            $this->donor = \AhgDonorManage\Services\DonorCrudService::getBySlug($slug, $culture);
            if (!$this->donor) {
                $this->forward404();
            }

            $title = $this->donor['authorizedFormOfName'] ?: $this->context->i18n->__('Untitled');
            $this->response->setTitle($this->context->i18n->__('Edit %1%', ['%1%' => $title]) . ' - ' . $this->response->getTitle());
        } else {
            // Create new donor
            $this->donor = [
                'id' => null,
                'slug' => null,
                'authorizedFormOfName' => '',
                'contacts' => [],
                'accessions' => [],
                'serialNumber' => 0,
            ];

            $this->response->setTitle($this->context->i18n->__('Add new donor') . ' - ' . $this->response->getTitle());
        }

        // Load contact information for the edit form
        $this->contacts = $this->donor['contacts'] ?? [];

        // Handle POST
        if ($request->isMethod('post')) {
            $authorizedFormOfName = trim($request->getParameter('authorizedFormOfName', ''));

            if ($this->isNew) {
                // Create
                $newId = \AhgDonorManage\Services\DonorCrudService::create([
                    'authorizedFormOfName' => $authorizedFormOfName,
                    'contacts' => $this->parseContactsFromRequest($request, $culture),
                ], $culture);

                $newSlug = \AhgCore\Services\ObjectService::getSlug($newId);
                $this->redirect(['module' => 'donor', 'slug' => $newSlug]);
            } else {
                // Update
                \AhgDonorManage\Services\DonorCrudService::update($this->donor['id'], [
                    'authorizedFormOfName' => $authorizedFormOfName,
                ], $culture);

                // Handle contact information updates
                $this->processContactUpdates($request, $this->donor['id'], $culture);

                $this->redirect(['module' => 'donor', 'slug' => $this->donor['slug']]);
            }
        }
    }

    /**
     * Parse contact information from POST request.
     */
    protected function parseContactsFromRequest($request, string $culture): array
    {
        $contacts = [];
        $contact = [];
        foreach (['contact_person', 'street_address', 'city', 'region', 'postal_code', 'country_code', 'telephone', 'fax', 'email', 'website', 'note'] as $field) {
            $value = $request->getParameter($field);
            if (!empty($value)) {
                $contact[$field] = $value;
            }
        }
        if (!empty($contact)) {
            $contacts[] = $contact;
        }

        return $contacts;
    }

    /**
     * Process contact information updates.
     */
    protected function processContactUpdates($request, int $donorId, string $culture): void
    {
        $contactData = [];
        foreach (['contact_person', 'street_address', 'city', 'region', 'postal_code', 'country_code', 'telephone', 'fax', 'email', 'website', 'note'] as $field) {
            $value = $request->getParameter($field);
            if ($value !== null) {
                $contactData[$field] = $value;
            }
        }

        if (!empty($contactData)) {
            $existingContacts = \AhgCore\Services\ContactInformationService::getByActorId($donorId, $culture);
            if (!empty($existingContacts)) {
                \AhgCore\Services\ContactInformationService::save(
                    $donorId,
                    $contactData,
                    $culture,
                    $existingContacts[0]->id
                );
            } else {
                \AhgCore\Services\ContactInformationService::save($donorId, $contactData, $culture);
            }
        }
    }
}
