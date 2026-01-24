<?php

class securityClearanceComponents extends sfComponents
{
    public function executeWatermarkSelect(sfWebRequest $request)
    {
        $this->watermarkTypes = \Illuminate\Database\Capsule\Manager::table('watermark_type')
            ->where('active', 1)
            ->orderBy('sort_order')
            ->get();

        $this->currentWatermarkId = null;
        $this->watermarkEnabled = true;
        $this->customWatermarkId = null;

        if (isset($this->resource) && $this->resource->id) {
            $setting = \Illuminate\Database\Capsule\Manager::table('object_watermark_setting')
                ->where('object_id', $this->resource->id)
                ->first();
            if ($setting) {
                $this->currentWatermarkId = $setting->watermark_type_id;
                $this->watermarkEnabled = (bool) $setting->watermark_enabled;
                $this->customWatermarkId = $setting->custom_watermark_id;
            }
        }
    }
}
