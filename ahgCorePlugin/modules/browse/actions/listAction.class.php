<?php

/**
 * AHG stub for browse/list action.
 * Replaces apps/qubit/modules/browse/actions/listAction.class.php.
 *
 * Redirect dispatcher for browse list types.
 */
class BrowseListAction extends sfAction
{
    public function execute($request)
    {
        $this->browseList = $this->request->browseList;
        $this->forward404Unless($this->browseList);

        switch ($this->browseList) {
            case 'subject':
                $this->context->user->setAttribute('browse_list', 'subject');
                $this->redirect(['module' => 'taxonomy', 'id' => QubitTaxonomy::SUBJECT_ID]);

                break;

            case 'materialtype':
                $this->context->user->setAttribute('browse_list', 'materialtype');
                $this->redirect(['module' => 'taxonomy', 'id' => QubitTaxonomy::MATERIAL_TYPE_ID]);

                break;

            case 'place':
                $this->context->user->setAttribute('browse_list', 'place');
                $this->redirect(['module' => 'taxonomy', 'id' => QubitTaxonomy::PLACE_ID]);

                break;

            case 'actor':
                $this->context->user->setAttribute('browse_list', 'actor');
                $this->redirect(['module' => 'actor', 'action' => 'browse']);

                break;

            case 'function':
                $this->context->user->setAttribute('browse_list', 'function');
                $this->redirect(['module' => 'function', 'action' => 'list']);

                break;

            case 'name':
                $this->context->user->setAttribute('browse_list', 'name');
                $this->redirect(['module' => 'actor', 'action' => 'browse']);

                break;

            case 'repository':
                $this->context->user->setAttribute('browse_list', 'repository');
                $this->redirect(['module' => 'repository', 'action' => 'browse']);

                break;

            case 'mediatype':
                $this->context->user->setAttribute('browse_list', 'mediatype');
                $this->redirect(['module' => 'digitalobject', 'action' => 'list']);

                break;

            case 'informationobject':
                $this->context->user->setAttribute('browse_list', 'informationobject');
                $this->redirect(['module' => 'informationobject', 'action' => 'browse']);

                break;

            case 'recentUpdates':
                $this->context->user->setAttribute('browse_list', 'recentUpdates');
                $this->informationObjects = informationObjectPeer::getRecentChanges(10);
                $this->setTemplate('recentList');

                break;

            default:
                $this->forward404();
        }
    }
}
