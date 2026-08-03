<?php

/**
 * AHG stub for digitalobject/view action.
 * Replaces apps/qubit/modules/digitalobject/actions/viewAction.class.php.
 *
 * Serves digital object files with ACL checks and copyright popup.
 */
class DigitalObjectViewAction extends sfAction
{
    public function execute($request)
    {
        $pathinfo = pathinfo($request->getPathInfo());
        $pathinfo['dirname'] = str_replace("/{$request->module}/{$request->action}", '', $pathinfo['dirname']).'/';

        $this->resource = QubitDigitalObject::getByPathFile($pathinfo['dirname'], $pathinfo['basename']);

        // We are going to need this later
        $this->digitalObjectId = $this->resource->id;

        // Resource Found?
        if (null === $this->resource) {
            $this->forward404();
        }

        list($obj, $action) = $this->getObjAndAction();

        // #258: a derivative whose parent chain is broken - for example a
        // thumbnail row with parent_id NULL - yields a null $obj, and
        // QubitAcl::check then fails with "get_class(): Argument #1 must be of
        // type object, null given".
        //
        // Harmless while nginx served /uploads/r/ statically and these requests
        // never reached PHP. Once masters route through this action for the ACL
        // check, every request for an orphaned derivative fatals. Treat an
        // unresolvable owner as not found: there is no record whose permissions
        // could grant access to it.
        if (null === $obj) {
            $this->forward404();
        }

        // If access is denied, forward user to a 404 "Not found" page
        if (!QubitAcl::check($obj, $action)) {
            $this->forward404();
        }

        if ($this->needsPopup($action)) {
            $this->resource = $this->resource->object;

            $this->accessToken = bin2hex(random_bytes(32)); // URL friendly
            $this->context->user->setAttribute("token-{$this->digitalObjectId}", $this->accessToken, 'symfony/user/sfUser/copyrightStatementTmpAccess');

            $this->response->addMeta('robots', 'noindex,nofollow');
            $this->setTemplate('viewCopyrightStatement');

            $this->copyrightStatement = sfConfig::get('app_digitalobject_copyright_statement');

            return sfView::SUCCESS;
        }

        $this->setResponseHeaders();

        return sfView::HEADER_ONLY;
    }

    protected function needsPopup($action)
    {
        // Only if the user is reading the master digital object, and the resource
        // has a PREMIS conditional copyright restriction
        if ('readMaster' != $action || !$this->resource->hasConditionalCopyright()) {
            return false;
        }

        // Show the pop-up if a valid access token was not submitted
        return false === $this->isAccessTokenValid();
    }

    protected function setResponseHeaders()
    {
        $this->response->setContentType($this->resource->mimeType);

        // Using X-Accel-Redirect (Nginx) unless ATOM_XSENDFILE is set
        if (false === filter_var($_SERVER['ATOM_XSENDFILE'], FILTER_VALIDATE_BOOLEAN)) {
            $urlPath = preg_replace('\/?[^\/]+\.php$', '', $_SERVER['SCRIPT_NAME']);
            $this->response->setHttpHeader('X-Accel-Redirect', $urlPath.'/private'.$this->resource->getFullPath());
        } else {
            $this->response->setHttpHeader('X-Sendfile', sprintf(
                '%s/%s',
                sfConfig::get('sf_root_dir'),
                $this->resource->getFullPath()
            ));
        }
    }

    private function getObjAndAction()
    {
        switch ($this->resource->usageId) {
            case QubitTerm::MASTER_ID:
                $action = 'readMaster';
                $obj = $this->resource->object;

                break;

            case QubitTerm::REFERENCE_ID:
            case QubitTerm::CHAPTERS_ID:
            case QubitTerm::SUBTITLES_ID:
                $action = 'readReference';
                $obj = $this->resource->parent->object;

                break;

            case QubitTerm::THUMBNAIL_ID:
                $action = 'readThumbnail';
                $obj = $this->resource->parent->object;

                break;

            default:
                throw new sfException("Invalid usageId given in digitalobject/view: {$this->resource->usageId}");
        }

        return [$obj, $action];
    }

    private function isAccessTokenValid()
    {
        $providedToken = $this->request->token;
        $internalToken = $this->context->user->getAttribute("token-{$this->digitalObjectId}", null, 'symfony/user/sfUser/copyrightStatementTmpAccess');

        if (empty($providedToken) || empty($internalToken)) {
            return false;
        }

        if ($providedToken !== $internalToken) {
            return false;
        }

        return true;
    }
}
