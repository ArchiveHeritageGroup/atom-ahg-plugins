<?php
use AtomFramework\Http\Controllers\AhgActorEditController;

/*
 * This file is part of the Access to Memory (AtoM) software.
 *
 * Access to Memory (AtoM) is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Access to Memory (AtoM) is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Access to Memory (AtoM).  If not, see <http://www.gnu.org/licenses/>.
 */

// Load base ActorEditAction from core
require_once sfConfig::get('sf_root_dir').'/apps/qubit/modules/actor/actions/editAction.class.php';

/**
 * Actor - editIsaar.
 *
 * @author     Peter Van Garderen <peter@artefactual.com>
 */
class sfIsaarPluginEditAction extends AhgActorEditController
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

        $result = parent::processForm();

        // Persist the authority-record public-visibility control (draft/embargo).
        // These inputs live in the edit template (not the sfForm), so read them
        // straight from the request after the actor itself has been saved.
        if (isset($this->resource->id) && $this->resource->id > 0) {
            $status = $this->request->getParameter('publicationStatus', 'published');
            $embargo = $this->request->getParameter('embargoUntil');
            $reason = $this->request->getParameter('visibilityReason');

            $userId = null;
            try {
                $uid = (int) $this->context->getUser()->getAttribute('user_id');
                $userId = $uid > 0 ? $uid : null;
            } catch (\Throwable $e) {
                $userId = null;
            }

            \AhgActorManage\Services\ActorVisibilityService::setStatus(
                (int) $this->resource->id,
                'draft' === $status ? 'draft' : 'published',
                $embargo,
                $reason,
                $userId
            );
        }

        return $result;
    }
}
