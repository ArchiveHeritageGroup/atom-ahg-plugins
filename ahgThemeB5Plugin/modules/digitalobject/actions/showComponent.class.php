<?php
/**
 * AHG Theme - Digital Object Show Component with 3D support
 */
class DigitalObjectShowComponent extends AhgComponents
{
    protected static $extensions3D = ['glb', 'gltf', 'obj', 'stl', 'fbx', 'ply', 'dae'];

    public function execute($request)
    {
        // $this->resource is already set by get_component() via the var holder.
        // Do NOT overwrite it — just check if it was provided.
        if (!$this->resource) {
            $this->showComponent = 'showDownload';
            $this->accessWarning = '';
            return;
        }

        // Default var holder values (set by get_component or fall back to defaults)
        if (!isset($this->usageType)) {
            $this->usageType = QubitTerm::THUMBNAIL_ID;
        }
        if (!isset($this->link)) {
            $this->link = null;
        }
        if (!isset($this->iconOnly)) {
            $this->iconOnly = false;
        }

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
        if (isset($this->resource->object) && $this->resource->object instanceof QubitInformationObject) {
            $this->accessWarning = QubitInformationObject::getAccessWarning($this->resource->object, $this->usageType);
        }
    }
}
