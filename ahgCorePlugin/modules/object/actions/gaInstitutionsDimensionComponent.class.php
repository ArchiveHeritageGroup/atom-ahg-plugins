<?php

/**
 * Google Analytics institution dimension stub.
 * Returns NONE if GA dimension index is not configured.
 */
class ObjectGaInstitutionsDimensionComponent extends sfComponent
{
    public function execute($request)
    {
        $this->dimensionIndex = sfConfig::get('app_google_analytics_institutions_dimension_index', '');

        if (empty($this->dimensionIndex)) {
            return sfView::NONE;
        }

        switch (get_class($this->resource)) {
            case 'QubitInformationObject':
                $this->repository = $this->resource->getRepository(['inherit' => true]);
                break;

            case 'QubitActor':
                $this->repository = $this->resource->getMaintainingRepository();
                break;

            case 'QubitRepository':
                $this->repository = $this->resource;
                break;

            default:
                $this->repository = null;
                break;
        }

        if (!isset($this->repository)) {
            return sfView::NONE;
        }
    }
}
