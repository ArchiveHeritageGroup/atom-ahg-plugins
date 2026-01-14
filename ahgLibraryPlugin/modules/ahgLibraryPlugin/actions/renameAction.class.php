<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Library Rename Action - BUG FIX #60
 * Based on core InformationObjectRenameAction but preserves display_standard_id
 */
class ahgLibraryPluginRenameAction extends DefaultEditAction
{
    public static $NAMES = [
        'title',
        'slug',
        'filename',
    ];

    public function execute($request)
    {
        parent::execute($request);

        if ('POST' == $this->request->getMethod()) {
            ProjectConfiguration::getActive()->loadHelpers('I18N');

            $this->form->bind($request->getPostParameters());

            if ($this->form->isValid()) {
                $this->updateResource();

                $message = __('Description updated.');

                $postedSlug = $this->form->getValue('slug');

                if (
                    (null !== $postedSlug)
                    && $this->resource->slug != $postedSlug
                ) {
                    $message .= ' ' . __(
                        'Slug was adjusted to remove special characters or' .
                        ' because it has already been used for another' .
                        ' description.'
                    );
                }

                $this->getUser()->setFlash('notice', $message);

                // BUG FIX #60: Redirect to Library module
                $this->redirect(
                    ['module' => 'ahgLibraryPlugin', 'action' => 'index', 'slug' => $this->resource->slug]
                );
            }
        }
    }

    protected function earlyExecute()
    {
        $slug = $this->request->getParameter('slug');
        if ($slug) {
            $this->resource = QubitInformationObject::getBySlug($slug);
        } else {
            $this->resource = $this->getRoute()->resource;
        }

        if (!$this->resource) {
            $this->forward404();
        }

        // Check user authorization
        if (
            !QubitAcl::check($this->resource, 'update')
            && !$this->getUser()->hasGroup(QubitAclGroup::EDITOR_ID)
        ) {
            QubitAcl::forwardUnauthorized();
        }
    }

    protected function addField($name)
    {
        if (in_array($name, self::$NAMES)) {
            if ('filename' == $name) {
                if (count($this->resource->digitalObjectsRelatedByobjectId) > 0) {
                    $this->form->setDefault(
                        $name,
                        $this->resource->digitalObjectsRelatedByobjectId[0]->name
                    );
                    $this->form->setValidator($name, new sfValidatorString(['required' => false]));
                    $this->form->setWidget($name, new sfWidgetFormInput());
                }
            } else {
                $this->form->setDefault($name, $this->resource[$name]);
                $this->form->setValidator($name, new sfValidatorString(['required' => false]));
                $this->form->setWidget($name, new sfWidgetFormInput());
            }
        }
    }

    private function updateResource()
    {
        // BUG FIX #60: Preserve display_standard_id before any save operations
        $preservedDisplayStandardId = DB::table('information_object')
            ->where('id', $this->resource->id)
            ->value('display_standard_id');

        $postedTitle = $this->form->getValue('title');
        $postedSlug = $this->form->getValue('slug');
        $postedFilename = $this->form->getValue('filename');

        // Update title, if title sent
        if (!empty($postedTitle)) {
            $this->resource->title = $postedTitle;
        }

        // Attempt to update slug if slug sent
        if (!empty($postedSlug)) {
            $slug = QubitSlug::getByObjectId($this->resource->id);

            // Get finding aid path before rename
            $findingAid = new QubitFindingAid($this->resource);
            $oldFindingAidPath = $findingAid->getPath();

            // Attempt to change slug if submitted slug's different than current slug
            if ($postedSlug != $slug->slug) {
                $slug->slug = InformationObjectSlugPreviewAction::determineAvailableSlug(
                    $postedSlug, $this->resource->id
                );
                $slug->save();

                // Set $resource->slug so the new slug is used to generate the new Finding Aid filename
                $this->resource->slug = $slug->slug;
                $this->renameFindingAid($oldFindingAidPath);
            }
        }

        // Update digital object filename, if filename sent
        if (
            (null !== $postedFilename)
            && 0 !== count($this->resource->digitalObjectsRelatedByobjectId)
        ) {
            // Parse filename so special characters can be removed
            $fileParts = pathinfo($postedFilename);
            $filename = QubitSlug::slugify($fileParts['filename']) . '.' .
                QubitSlug::slugify($fileParts['extension']);

            $digitalObject = $this->resource->digitalObjectsRelatedByobjectId[0];

            // Rename master file
            $basePath = sfConfig::get('sf_web_dir') . $digitalObject->path;
            $oldFilePath = $basePath . DIRECTORY_SEPARATOR . $digitalObject->name;
            $newFilePath = $basePath . DIRECTORY_SEPARATOR . $filename;
            rename($oldFilePath, $newFilePath);
            chmod($newFilePath, 0644);

            // Change name in database
            $digitalObject->name = $filename;
            $digitalObject->save();

            // Regenerate derivatives
            digitalObjectRegenDerivativesTask::regenerateDerivatives(
                $digitalObject, ['keepTranscript' => true]
            );
        }

        $this->resource->save();

        // BUG FIX #60: Restore display_standard_id if it was changed during save
        if ($preservedDisplayStandardId !== null) {
            $currentId = DB::table('information_object')
                ->where('id', $this->resource->id)
                ->value('display_standard_id');

            if ($currentId != $preservedDisplayStandardId) {
                DB::table('information_object')
                    ->where('id', $this->resource->id)
                    ->update(['display_standard_id' => $preservedDisplayStandardId]);
            }
        }

        $this->resource->updateXmlExports();
    }

    /**
     * Rename the attached finding aid file when the description slug changes.
     */
    private function renameFindingAid(?string $filepath): void
    {
        if (empty($filepath)) {
            return;
        }

        $newPath = QubitFindingAidGenerator::generatePath($this->resource);

        $success = rename($filepath, $newPath);

        if (false === $success) {
            $this->logMessage(
                sprintf(
                    'Finding aid document could not be renamed to match the' .
                    ' new slug (old=%s, new=%s)',
                    $filepath,
                    $newPath
                ),
                'warning'
            );
        }
    }
}
