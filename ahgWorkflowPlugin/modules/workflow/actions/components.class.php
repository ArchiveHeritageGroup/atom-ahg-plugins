<?php

/**
 * Workflow components — embeddable partials usable from any template via
 * include_component('workflow', '<name>', $params).
 *
 * Currently provides spectrumObjectPanel (Spectrum Phase C3 PSIS port).
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * Licensed under the GNU Affero General Public License v3.0 or later.
 */

class workflowComponents extends sfComponents
{
    /**
     * Per-object Spectrum compliance panel.
     *
     * Usage from any template that has an information object id available:
     *
     *   <?php include_component('workflow', 'spectrumObjectPanel', [
     *       'informationObjectId' => $resource->id,
     *   ]) ?>
     *
     * Renders nothing if the Spectrum compliance tables aren't present
     * (graceful no-op for installs that haven't run the migration yet).
     */
    public function executeSpectrumObjectPanel($request)
    {
        $this->summary = [];
        $this->informationObjectId = (int) ($this->informationObjectId ?? 0);

        if ($this->informationObjectId <= 0) {
            return;
        }

        try {
            $hasTable = \Illuminate\Database\Capsule\Manager::schema()->hasTable('ahg_workflow')
                && \Illuminate\Database\Capsule\Manager::schema()->hasTable('ahg_spectrum_object_compliance');
            if (!$hasTable) {
                return;
            }
        } catch (Exception $e) {
            return;
        }

        require_once dirname(dirname(dirname(dirname(__FILE__)))).'/lib/Services/SpectrumComplianceService.php';
        require_once dirname(dirname(dirname(dirname(__FILE__)))).'/lib/Services/SpectrumProcedureCatalog.php';

        try {
            $svc = new SpectrumComplianceService();
            $this->summary = $svc->objectSummary($this->informationObjectId);
            $this->statuses = SpectrumComplianceService::STATUSES;
        } catch (\Throwable $e) {
            $this->summary = [];
        }
    }
}
