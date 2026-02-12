<?php
/**
 * AHG Theme - Digital Object Show Component with 3D support
 */
class DigitalObjectShowComponent extends AhgComponents
{
    protected static $extensions3D = ['glb', 'gltf', 'obj', 'stl', 'fbx', 'ply', 'dae'];

    public function execute($request)
    {
        // Try multiple ways to get the resource
        $this->resource = null;
        
        // Method 1: Direct from request
        if (isset($request->resource)) {
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
            return;
        }

        $this->usageType = $request->usageType;
        $this->link = $request->link;
        $this->iconOnly = $request->iconOnly;

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
            $this->accessWarning = QubitInformationObject::getAccessWarning($this->resource->object, $this->usageType);
        }
    }
}
