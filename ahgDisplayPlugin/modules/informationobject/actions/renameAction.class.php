<?php

/*
 * Override of core AtoM rename action to preserve display_standard_id
 * BUG FIX #60: Propel save() resets display_standard_id to default (ISAD)
 */

use Illuminate\Database\Capsule\Manager as DB;

class InformationObjectRenameAction extends DefaultEditAction
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
                    $message .= ' '.__(
                        'Slug was adjusted to remove special characters or'.
                        ' because it has already been used for another'.
                        ' description.'
                    );
                }

                $this->getUser()->setFlash('notice', $message);

                // Always redirect to informationobject module for slug-based URL.
                // The UI overrides plugin dispatches to the correct viewer/template.
                $this->redirect([$this->resource, 'module' => 'informationobject']);
            }
        }
    }

    protected function earlyExecute()
    {
        $this->resource = $this->getRoute()->resource;

        if (
            !QubitAcl::check($this->resource, 'update')
            && !$this->getUser()->hasGroup(QubitAclGroup::EDITOR_ID)
        ) {
            QubitAcl::forwardUnauthorized();
        }
    }

    protected function addField($name)
    {
        if (in_array($name, InformationObjectRenameAction::$NAMES)) {
            if ('filename' == $name) {
                $this->form->setDefault(
                    $name,
                    $this->resource->digitalObjectsRelatedByobjectId[0]->name
                );
            } else {
                $this->form->setDefault($name, $this->resource[$name]);
            }

            $this->form->setValidator($name, new sfValidatorString());
            $this->form->setWidget($name, new sfWidgetFormInput());
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

        if (!empty($postedTitle)) {
            $this->resource->title = $postedTitle;
        }

        if (!empty($postedSlug)) {
            $slug = QubitSlug::getByObjectId($this->resource->id);

            $findingAid = new QubitFindingAid($this->resource);
            $oldFindingAidPath = $findingAid->getPath();

            if ($postedSlug != $slug->slug) {
                $slug->slug = InformationObjectSlugPreviewAction::determineAvailableSlug(
                    $postedSlug, $this->resource->id
                );
                $slug->save();

                $this->resource->slug = $slug->slug;
                $this->renameFindingAid($oldFindingAidPath);
            }
        }

        if (
            (null !== $postedFilename)
            && 0 !== count($this->resource->digitalObjectsRelatedByobjectId)
        ) {
            $fileParts = pathinfo($postedFilename);
            $filename = QubitSlug::slugify($fileParts['filename']).'.'.
                QubitSlug::slugify($fileParts['extension']);

            $digitalObject = $this->resource->digitalObjectsRelatedByobjectId[0];

            $basePath = sfConfig::get('sf_web_dir').$digitalObject->path;
            $oldFilePath = $basePath.DIRECTORY_SEPARATOR.$digitalObject->name;
            $newFilePath = $basePath.DIRECTORY_SEPARATOR.$filename;
            rename($oldFilePath, $newFilePath);
            chmod($newFilePath, 0644);

            $digitalObject->name = $filename;
            $digitalObject->save();

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
                    'Finding aid document could not be renamed to match the'.
                    ' new slug (old=%s, new=%s)',
                    $filepath,
                    $newPath
                ),
                'warning'
            );
        }
    }
}
