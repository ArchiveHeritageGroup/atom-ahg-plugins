<?php

/*
 * Extended sfIsaarPlugin edit action with contact information support.
 * Based on AtoM 2.10 sfIsaarPluginEditAction
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */

class sfIsaarPluginEditAction extends ActorEditAction
{
    // Arrays not allowed in class constants
    public static $NAMES = [
        'authorizedFormOfName',
        'corporateBodyIdentifiers',
        'datesOfExistence',
        'descriptionDetail',
        'descriptionIdentifier',
        'descriptionStatus',
        'entityType',
        'functions',
        'generalContext',
        'history',
        'institutionResponsibleIdentifier',
        'internalStructures',
        'language',
        'legalStatus',
        'maintainingRepository',
        'maintenanceNotes',
        'mandates',
        'otherName',
        'parallelName',
        'places',
        'placeAccessPoints',
        'revisionHistory',
        'rules',
        'script',
        'sources',
        'standardizedName',
        'subjectAccessPoints',
    ];

    protected function earlyExecute()
    {
        parent::earlyExecute();

        $this->isaar = new sfIsaarPlugin($this->resource);

        $title = $this->context->i18n->__('Add new authority record');
        if (isset($this->getRoute()->resource)) {
            if (1 > strlen($title = $this->resource)) {
                $title = $this->context->i18n->__('Untitled');
            }

            $title = $this->context->i18n->__('Edit %1%', ['%1%' => $title]);
        }

        $this->response->setTitle("{$title} - {$this->response->getTitle()}");

        $this->eventComponent = new sfIsaarPluginEventComponent($this->context, 'sfIsaarPlugin', 'event');
        $this->eventComponent->resource = $this->resource;
        $this->eventComponent->execute($this->request);

        $this->relatedAuthorityRecordComponent = new sfIsaarPluginRelatedAuthorityRecordComponent($this->context, 'sfIsaarPlugin', 'relatedAuthorityRecord');
        $this->relatedAuthorityRecordComponent->resource = $this->resource;
        $this->relatedAuthorityRecordComponent->execute($this->request);

        $this->occupationsComponent = new ActorOccupationsComponent($this->context, 'actor', 'occupations');
        $this->occupationsComponent->resource = $this->resource;
        $this->occupationsComponent->execute($this->request);
    }

    protected function addField($name)
    {
        switch ($name) {
            case 'maintenanceNotes':
                $this->form->setDefault('maintenanceNotes', $this->isaar->maintenanceNotes);
                $this->form->setValidator('maintenanceNotes', new sfValidatorString());
                $this->form->setWidget('maintenanceNotes', new sfWidgetFormTextarea());

                break;

            case 'descriptionIdentifier':
                if (sfConfig::get('app_prevent_duplicate_actor_identifiers', false)) {
                    $this->form->setDefault($name, $this->resource[$name]);
                    $identifierValidator = new QubitValidatorActorDescriptionIdentifier(['resource' => $this->resource]);
                    $this->form->setValidator($name, $identifierValidator);
                    $this->form->setWidget($name, new sfWidgetFormInput());
                } else {
                    return parent::addField($name);
                }

                break;

            default:
                return parent::addField($name);
        }
    }

    protected function processField($field)
    {
        switch ($field->getName()) {
            case 'maintenanceNotes':
                $this->isaar->maintenanceNotes = $this->form->getValue('maintenanceNotes');

                break;

            default:
                return parent::processField($field);
        }
    }

    protected function processForm()
    {
        $this->eventComponent->processForm();
        $this->relatedAuthorityRecordComponent->processForm();
        $this->occupationsComponent->processForm();

        // Process contact information (AHG extension)
        $this->processContactInformation();

        return parent::processForm();
    }

    /**
     * Process multiple contacts from form (AHG extension).
     */
    protected function processContactInformation(): void
    {
        $contactsData = $this->request->getParameter('contacts');

        if (empty($contactsData) || !is_array($contactsData)) {
            return;
        }

        // Load framework bootstrap
        $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($frameworkPath)) {
            require_once $frameworkPath;
        }

        // Get actor ID - for new actors we need to save first to get ID
        $actorId = $this->resource->id;

        if (empty($actorId)) {
            $this->resource->save();
            $actorId = $this->resource->id;
        }

        $culture = $this->context->user->getCulture();
        $repo = new \AtomFramework\Extensions\Contact\Repositories\ContactInformationRepository();

        foreach ($contactsData as $index => $contactData) {
            // Check if marked for deletion
            if (!empty($contactData['delete']) && !empty($contactData['id'])) {
                $repo->delete((int) $contactData['id']);
                continue;
            }

            // Check if any contact field has data
            $hasData = false;
            foreach ($contactData as $key => $value) {
                if (!in_array($key, ['id', 'delete', 'actor_id']) && !empty($value)) {
                    $hasData = true;
                    break;
                }
            }

            if (!$hasData) {
                continue;
            }

            // Prepare contact data
            $contactData['actor_id'] = $actorId;
            $contactData['source_culture'] = $culture;
            $contactData['primary_contact'] = !empty($contactData['primary_contact']) ? 1 : 0;

            // Save via repository
            $repo->saveFromForm($contactData);
        }
    }
}
