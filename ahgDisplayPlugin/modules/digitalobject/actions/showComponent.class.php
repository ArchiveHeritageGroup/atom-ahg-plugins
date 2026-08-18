<?php
/**
 * AHG Theme - Digital Object Show Component with 3D support
 */
class DigitalObjectShowComponent extends AhgComponents
{
    protected static $extensions3D = ['glb', 'gltf', 'obj', 'stl', 'fbx', 'ply', 'dae'];

    public function execute($request)
    {
        // The caller normally passes `resource` as a component variable, e.g. base
        // AtoM's sfIsadPlugin/indexSuccess.php. That is the authoritative source and
        // must not be discarded: overwriting it with null here made every digital
        // object fall through to the no-resource branch below on any install without
        // the AHG theme, leaving the template without $link, $usageType, $iconOnly
        // and $editForm, and truncating the record page to an empty fragment.
        if (!isset($this->resource) || !$this->resource) {
            $this->resource = null;
        }

        // Method 1: Direct from request
        if (!$this->resource && isset($request->resource)) {
            $this->resource = $request->resource;
        }

        // Method 2: From sf_request attribute
        if (!$this->resource) {
            $sfRequest = $request->getAttribute('sf_request');
            if ($sfRequest && method_exists($sfRequest, 'getAttribute')) {
                $this->resource = $sfRequest->getAttribute('resource');
            }
        }
        
        // Method 3: From context
        if (!$this->resource) {
            $context = sfContext::getInstance();
            if ($context && $context->has('request')) {
                $mainRequest = $context->getRequest();
                if ($mainRequest && method_exists($mainRequest, 'getAttribute')) {
                    $this->resource = $mainRequest->getAttribute('resource');
                }
            }
        }

        if (!$this->resource) {
            $this->showComponent = 'showDownload';
            $this->accessWarning = '';

            // _show.php reads all four unconditionally. Returning without them
            // leaves the template rendering against undefined variables, which is
            // what turned a missing digital object into a blank record page.
            $this->usageType = $this->usageType ?? QubitTerm::THUMBNAIL_ID;
            $this->link = $this->link ?? null;
            $this->iconOnly = $this->iconOnly ?? false;
            $this->editForm = $this->editForm ?? false;

            return;
        }

        // Component variables win over request parameters, for the same reason as
        // the resource above: the caller passed them deliberately.
        $this->usageType = $this->usageType ?? $request->usageType ?? QubitTerm::THUMBNAIL_ID;
        $this->link = $this->link ?? $request->link ?? null;
        $this->iconOnly = $this->iconOnly ?? $request->iconOnly ?? false;
        $this->editForm = $this->editForm ?? false;

        // Check if it's a 3D model by extension
        $name = $this->resource->name ?? '';
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (in_array($extension, self::$extensions3D)) {
            $this->showComponent = 'show3D';
            return;
        }

        // Default AtoM logic
        if ($this->iconOnly) {
            $this->showComponent = 'showGenericIcon';
        } elseif (QubitTerm::REFERENCE_ID == $this->usageType) {
            if (0 >= strlen($this->resource->getPath())) {
                $this->showComponent = 'showGenericIcon';
            } else {
                switch ($this->resource->mediaTypeId) {
                    case QubitTerm::IMAGE_ID:
                        $this->showComponent = $this->resource->showAsCompoundDigitalObject() ? 'showCompound' : 'showImage';
                        break;
                    case QubitTerm::AUDIO_ID:
                        $this->showComponent = 'showAudio';
                        break;
                    case QubitTerm::VIDEO_ID:
                        $this->showComponent = 'showVideo';
                        break;
                    case QubitTerm::TEXT_ID:
                        $this->showComponent = $this->resource->showAsCompoundDigitalObject() ? 'showCompound' : 'showText';
                        break;
                    default:
                        $this->showComponent = 'showDownload';
                }
            }
        } elseif (QubitTerm::THUMBNAIL_ID == $this->usageType) {
            switch ($this->resource->mediaTypeId) {
                case QubitTerm::IMAGE_ID:
                case QubitTerm::TEXT_ID:
                    $this->showComponent = $this->resource->showAsCompoundDigitalObject() ? 'showCompound' : 'showImage';
                    break;
                case QubitTerm::AUDIO_ID:
                case QubitTerm::VIDEO_ID:
                    $this->showComponent = 'showImage';
                    break;
                default:
                    $this->showComponent = 'showDownload';
            }
        } else {
            $this->showComponent = 'showDownload';
        }

        $this->accessWarning = '';
        if (isset($this->resource->object)) {
            // QubitInformationObject has no getAccessWarning() - the method is a
            // PRIVATE static on QubitGrantedRight, so this call was a fatal on every
            // digital object render that reached it. Base AtoM's own showComponent
            // uses checkPremis(), which writes the warning into $denyReason.
            $denyReason = '';
            QubitGrantedRight::checkPremis(
                $this->resource->object->id,
                'readReference',
                $denyReason
            );
            $this->accessWarning = (string) $denyReason;
        }
    }
}
