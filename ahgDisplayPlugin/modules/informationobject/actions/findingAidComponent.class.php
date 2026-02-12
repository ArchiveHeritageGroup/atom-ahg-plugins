<?php

use Illuminate\Database\Capsule\Manager as DB;

class findingAidComponent extends AhgComponents
{
    public function execute($request)
    {
        // If Finding Aids are explicitly disabled in QubitSettings, don't show
        if ('1' !== sfConfig::get('app_findingAidsEnabled', '1')) {
            return sfView::NONE;
        }

        // Initialize all display flags
        $this->showDownload = false;
        $this->showStatus = false;
        $this->showUpload = false;
        $this->showGenerate = false;
        $this->showDelete = false;
        $this->path = null;
        $this->status = null;

        // Check if resource exists
        if (!isset($this->resource) || !$this->resource) {
            return sfView::NONE;
        }

        // Get collection root (finding aids are at collection level)
        if (QubitInformationObject::ROOT_ID != $this->resource->parentId) {
            $this->resource = $this->resource->getCollectionRoot();
        }

        if (!$this->resource) {
            return sfView::NONE;
        }

        // Use QubitFindingAid class for finding aid operations
        $findingAid = new QubitFindingAid($this->resource);

        // Public users can only see the download link if the file exists
        if (!$this->getUser()->isAuthenticated()) {
            if (empty($findingAid->getPath())) {
                return sfView::NONE;
            }

            $this->path = $findingAid->getPath();
            $this->showDownload = true;

            return;
        }

        // Get last job status
        $lastJobStatus = arFindingAidJob::getStatus($this->resource->id);

        // For authenticated users, if no job has been executed
        if (!isset($lastJobStatus)) {
            // Edge case: job status missing but file exists
            if (!empty($findingAid->getPath())) {
                $this->path = $findingAid->getPath();
                $this->showDownload = true;

                // Check ACL to show delete option
                if (\AtomExtensions\Services\AclService::check($this->resource, 'update')) {
                    $this->showDelete = true;
                }

                return;
            }

            // Show allowed actions or nothing
            if (!$this->showActions()) {
                return sfView::NONE;
            }

            return;
        }

        // If there is a job in progress, show only status
        if (QubitTerm::JOB_STATUS_IN_PROGRESS_ID == $lastJobStatus) {
            $this->showStatus = true;
            $this->status = $this->context->i18n->__('In progress');

            return;
        }

        // If the last job failed, show error status and allowed actions
        if (QubitTerm::JOB_STATUS_ERROR_ID == $lastJobStatus) {
            $this->showStatus = true;
            $this->showActions();
            $this->status = $this->context->i18n->__('Error');

            return;
        }

        // If the last job completed, get finding aid status property
        if (QubitTerm::JOB_STATUS_COMPLETED_ID == $lastJobStatus) {
            // If the property is missing, the finding aid was deleted
            if (empty($findingAid->getStatus())) {
                if (!$this->showActions()) {
                    return sfView::NONE;
                }

                return;
            }

            // If the property is set but the file is missing
            if (empty($findingAid->getPath())) {
                $this->showStatus = true;
                $this->showActions();
                $this->status = $this->context->i18n->__('File missing');

                return;
            }

            // Show status and download link
            $this->showStatus = true;
            $this->showDownload = true;
            $this->path = $findingAid->getPath();

            switch ((int) $findingAid->getStatus()) {
                case QubitFindingAid::GENERATED_STATUS:
                    $this->status = $this->context->i18n->__('Generated');
                    break;

                case QubitFindingAid::UPLOADED_STATUS:
                    $this->status = $this->context->i18n->__('Uploaded');
                    break;

                default:
                    $this->status = $this->context->i18n->__('Unknown');
            }

            // Check ACL to show delete option
            if (\AtomExtensions\Services\AclService::check($this->resource, 'update')) {
                $this->showDelete = true;
            }

            return;
        }

        // Unknown status - show status and actions
        $this->showStatus = true;
        $this->showActions();
        $this->status = $this->context->i18n->__('Unknown');
    }

    /**
     * Set action flags based on user permissions
     */
    protected function showActions()
    {
        // Actions only allowed for users with update permissions
        if (!\AtomExtensions\Services\AclService::check($this->resource, 'update')) {
            return false;
        }

        // Upload is allowed for drafts
        $this->showUpload = true;

        // Generate is allowed for published descriptions and for drafts
        // if the public finding aid setting is set to false
        $setting = QubitSetting::getByName('publicFindingAid');
        if (
            QubitTerm::PUBLICATION_STATUS_PUBLISHED_ID ==
                $this->resource->getPublicationStatus()->statusId
            || (
                isset($setting)
                && !$setting->getValue(['sourceCulture' => true])
            )
        ) {
            $this->showGenerate = true;
        }

        return true;
    }
}
