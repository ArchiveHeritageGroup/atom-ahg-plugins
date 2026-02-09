<?php

/*
 * AHG Display Plugin - Digital Object Upload Action
 *
 * Migrates base AtoM DigitalObjectUploadAction.
 * Handles file upload endpoint for the multi-file uploader.
 * Validates against repository upload limits.
 */

class DigitalObjectUploadAction extends sfAction
{
    public function execute($request)
    {
        ProjectConfiguration::getActive()->loadHelpers('Qubit');

        $uploadLimit = -1;
        $diskUsage = 0;
        $uploadFiles = [];
        $warning = null;

        $this->object = QubitObject::getBySlug($request->parentSlug);

        if (!isset($this->object)) {
            $this->forward404();
        }

        // Check user authorization
        if (!QubitAcl::check($this->object, 'update')) {
            throw new sfException();
        }

        // Check if uploads are allowed
        if (!QubitDigitalObject::isUploadAllowed()) {
            QubitAcl::forwardToSecureAction();
        }

        $repo = $this->object->getRepository(['inherit' => true]);

        if (isset($repo)) {
            $uploadLimit = $repo->uploadLimit;
            if (0 < $uploadLimit) {
                $uploadLimit *= pow(10, 9); // Convert to bytes
            }

            $diskUsage = $repo->getDiskUsage();
        }

        foreach ($_FILES as $file) {
            if (null != $repo && 0 <= $uploadLimit && $uploadLimit < $diskUsage + $file['size']) {
                $uploadFiles = ['error' => $this->context->i18n->__(
                    '%1% upload limit of %2% GB exceeded for %3%',
                    [
                        '%1%' => sfConfig::get('app_ui_label_digitalobject'),
                        '%2%' => $repo->uploadLimit,
                        '%4%' => $this->context->routing->generate(null, [$repo, 'module' => 'repository']),
                        '%3%' => $repo->__toString(),
                    ]
                )];

                continue;
            }

            try {
                $file = Qubit::moveUploadFile($file);
            } catch (Exception $e) {
                $uploadFiles = ['error' => $e->getMessage()];

                continue;
            }

            // Temp file characteristics
            $tmpFilePath = $file['tmp_name'];
            $tmpFileName = basename($tmpFilePath);
            $tmpFileMimeType = QubitDigitalObject::deriveMimeType($tmpFileName);

            $uploadFiles = [
                'name' => $file['name'],
                'md5sum' => md5_file($tmpFilePath),
                'size' => hr_filesize($file['size']),
                'tmpName' => $tmpFileName,
                'warning' => $warning,
            ];

            // Keep running total of disk usage
            $diskUsage += $file['size'];
        }

        // Pass file data back to caller for processing on form submit
        $this->response->setHttpHeader('Content-Type', 'application/json; charset=utf-8');

        return $this->renderText(json_encode($uploadFiles));
    }
}
