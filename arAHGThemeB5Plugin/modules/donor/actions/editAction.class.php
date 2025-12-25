<?php

/**
 * Donor edit action - Create/Edit donor with multiple contacts support.
 *
 * Pure Laravel Query Builder via DonorRepository.
 * Maintains compatibility with vanilla AtoM donor fields.
 */
class DonorEditAction extends sfAction
{
    public function execute($request)
    {
        // Initialize framework
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';

        // Check authentication
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        // Get current culture
        $culture = $this->context->user->getCulture();
        $repository = new \AtomExtensions\Repositories\DonorRepository($culture);

        // Check if editing existing donor
        $slug = $request->getParameter('slug');
        $this->isNew = empty($slug);

        if (!$this->isNew) {
            $this->donor = $repository->findBySlug($slug);
            if (null === $this->donor) {
                $this->forward404();
            }
            $title = $this->context->i18n->__('Edit %1%', ['%1%' => $this->donor->authorizedFormOfName]);
        } else {
            $this->donor = (object) [
                'id' => null,
                'authorizedFormOfName' => '',
                'slug' => '',
                'contactInformations' => collect([]),
            ];
            $title = $this->context->i18n->__('Add new donor');
        }

        $this->response->setTitle("{$title} - {$this->response->getTitle()}");

        // Initialize errors array
        $this->errors = [];

        // Handle form submission
        if ($request->isMethod('post')) {
            $authorizedFormOfName = trim($request->getParameter('authorizedFormOfName', ''));

            // Validation
            if (empty($authorizedFormOfName)) {
                $this->errors['authorizedFormOfName'] = $this->context->i18n->__('Authorized form of name is required.');
            }

            if (empty($this->errors)) {
                $data = [
                    'authorizedFormOfName' => $authorizedFormOfName,
                    'culture' => $culture,
                ];

                if ($this->isNew) {
                    $newId = $repository->create($data);

                    // Save contacts
                    $this->processContacts($newId, $request, $repository, $culture);

                    $newDonor = $repository->findById($newId);
                    $this->redirect(['module' => 'donor', 'action' => 'index', 'slug' => $newDonor->slug]);
                } else {
                    $repository->update($this->donor->id, $data);

                    // Save contacts
                    $this->processContacts($this->donor->id, $request, $repository, $culture);

                    $this->redirect(['module' => 'donor', 'action' => 'index', 'slug' => $this->donor->slug]);
                }
            }

            // Keep submitted values on error
            $this->donor->authorizedFormOfName = $authorizedFormOfName;
        }
    }

    /**
     * Process multiple contacts from form submission.
     */
    protected function processContacts(int $actorId, $request, $repository, string $culture): void
    {
        $contactsData = $request->getParameter('contacts');

        if (empty($contactsData) || !is_array($contactsData)) {
            return;
        }

        foreach ($contactsData as $index => $contactData) {
            // Check if marked for deletion
            if (!empty($contactData['delete']) && !empty($contactData['id'])) {
                $repository->deleteContact((int) $contactData['id']);
                continue;
            }

            // Check if any contact field has data
            $hasData = false;
            foreach ($contactData as $key => $value) {
                if (!in_array($key, ['id', 'delete']) && !empty($value)) {
                    $hasData = true;
                    break;
                }
            }

            if (!$hasData) {
                continue;
            }

            // Prepare contact data - map camelCase to expected format
            $mappedData = [
                'contactPerson' => trim($contactData['contactPerson'] ?? ''),
                'streetAddress' => trim($contactData['streetAddress'] ?? ''),
                'website' => trim($contactData['website'] ?? ''),
                'email' => trim($contactData['email'] ?? ''),
                'telephone' => trim($contactData['telephone'] ?? ''),
                'fax' => trim($contactData['fax'] ?? ''),
                'postalCode' => trim($contactData['postalCode'] ?? ''),
                'countryCode' => trim($contactData['countryCode'] ?? ''),
                'latitude' => trim($contactData['latitude'] ?? ''),
                'longitude' => trim($contactData['longitude'] ?? ''),
                // i18n fields
                'contactType' => trim($contactData['contactType'] ?? ''),
                'city' => trim($contactData['city'] ?? ''),
                'region' => trim($contactData['region'] ?? ''),
                'note' => trim($contactData['note'] ?? ''),
                // Extended fields
                'title' => trim($contactData['title'] ?? ''),
                'role' => trim($contactData['role'] ?? ''),
                'department' => trim($contactData['department'] ?? ''),
                'cell' => trim($contactData['cell'] ?? ''),
                'idNumber' => trim($contactData['idNumber'] ?? ''),
                'alternativeEmail' => trim($contactData['alternativeEmail'] ?? ''),
                'alternativePhone' => trim($contactData['alternativePhone'] ?? ''),
                'preferredContactMethod' => trim($contactData['preferredContactMethod'] ?? ''),
                'languagePreference' => trim($contactData['languagePreference'] ?? ''),
                'extendedNotes' => trim($contactData['extendedNotes'] ?? ''),
                'primaryContact' => !empty($contactData['primaryContact']) ? 1 : 0,
            ];

            $contactId = !empty($contactData['id']) ? (int) $contactData['id'] : null;

            $repository->saveContactInformation($actorId, $mappedData, $culture, $contactId);
        }
    }
}
