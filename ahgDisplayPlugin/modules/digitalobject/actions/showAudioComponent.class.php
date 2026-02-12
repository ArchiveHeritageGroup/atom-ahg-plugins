<?php

class showAudioComponent extends AhgComponents
{
    public function execute($request)
    {
        // Get representation by usage type
        $this->representation = $this->resource->getRepresentationByUsage($this->usageType);

        // If we can't find a representation for this object, try their parent
        if (!$this->representation && ($parent = $this->resource->parent)) {
            $this->representation = $parent->getRepresentationByUsage($this->usageType);
        }

        if ($this->representation) {
            // DO NOT load mediaelement - we use enhanced native player
            // This is the B5 theme, so we skip legacy mediaelement
            
            $this->showMediaPlayer = true;

            list($this->width, $this->height) = QubitDigitalObject::getImageMaxDimensions($this->usageType);

            if (QubitTerm::CHAPTERS_ID != $this->usageType) {
                $this->representationFullPath = public_path($this->representation->getFullPath());
            }
        } else {
            $this->showMediaPlayer = false;
            $this->representation = QubitDigitalObject::getGenericRepresentation($this->resource->mimeType, $this->usageType);
        }
    }
}
