<?php

/**
 * Related rights component stub.
 */
class RightRelatedRightsComponent extends sfComponent
{
    public function execute($request)
    {
        if ($this->resource instanceof QubitAccession) {
            $this->ancestor = $this->resource;
        }

        $this->className = get_class($this->resource);
    }
}
