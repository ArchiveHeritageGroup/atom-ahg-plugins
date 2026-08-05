<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Heritage Asset'); ?></h4>
    <ul class="list-unstyled">
        <li><a href="<?php echo url_for('@spectrum_index?slug=' . $resource->slug); ?>"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back to Collections Procedures'); ?></a></li>
        <li><a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'dashboard']); ?>"><i class="fas fa-layer-group me-2"></i><?php echo __('Collections Dashboard'); ?></a></li>
    </ul>
    <hr>
    <p class="small text-muted">
        <?php echo __('Heritage asset accounting complies with international standards including IPSAS 17/31 and local standards such as GRAP 103 (South Africa).'); ?>
    </p>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-landmark"></i> <?php echo __('Heritage Asset'); ?><small class="text-muted ms-2"><?php echo esc_entities($resource->title ?? $resource->slug); ?></small></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="heritage-assets-dashboard">
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        <?php echo __('Heritage Assets: Financial reporting for cultural property, museum collections, and archival materials per international accounting standards.'); ?>
    </div>

    <!-- This record's heritage asset values. The four institution-wide counters that
         used to sit here (Total Heritage Assets / Valued / Pending / Total Value) were
         computed from a single record, so they always read 1, 1 or 0, and 0 or 1 - a
         dashboard shape around one item. The collection-wide dashboard is a separate
         page; this one answers "what is recorded against this record". -->
    <?php if (!$grapData): ?>
      <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo __('No heritage asset record exists for this item.'); ?>
      </div>
    <?php else: ?>
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-coins me-2"></i><?php echo __('Valuation'); ?></h5>
        </div>
        <table class="table table-sm mb-0">
          <tbody>
            <?php
            $rows = [
                __('Asset number')        => $grapData->asset_number ?? null,
                __('Recognition status')  => $grapData->recognition_status ?? null,
                __('Current carrying amount') => $grapData->current_carrying_amount ?? null,
                __('Last valuation')      => $grapData->last_valuation_amount ?? null,
                __('Last valuation date') => $grapData->last_valuation_date ?? null,
                __('Valuation method')    => $grapData->valuation_method ?? null,
                __('Acquisition date')    => $grapData->acquisition_date ?? null,
                __('Acquisition method')  => $grapData->acquisition_method ?? null,
                __('Acquisition cost')    => $grapData->acquisition_cost ?? null,
                __('Insurance value')     => $grapData->insurance_value ?? null,
                __('Insurance expiry')    => $grapData->insurance_expiry_date ?? null,
            ];
            foreach ($rows as $label => $value): ?>
              <tr>
                <th class="w-25"><?php echo $label; ?></th>
                <td><?php echo ('' === (string) $value || null === $value) ? '&mdash;' : esc_entities((string) $value); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <!-- Compliance Status -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i><?php echo __('Compliance Checklist'); ?></h5>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo __('Asset Register Complete'); ?>
                        <span class="badge bg-<?php echo ($assetRegisterComplete ?? false) ? 'success' : 'danger'; ?>">
                            <?php echo ($assetRegisterComplete ?? false) ? __('Yes') : __('No'); ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo __('Valuations Current (< 5 years)'); ?>
                        <span class="badge bg-<?php echo ($valuationsCurrent ?? false) ? 'success' : 'warning'; ?>">
                            <?php echo ($valuationsCurrent ?? false) ? __('Yes') : __('Review Needed'); ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo __('Condition Assessments'); ?>
                        <span class="badge bg-<?php echo ($conditionComplete ?? false) ? 'success' : 'warning'; ?>">
                            <?php echo ($conditionComplete ?? false) ? __('Complete') : __('Incomplete'); ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo __('Depreciation Recorded'); ?>
                        <span class="badge bg-<?php echo ($depreciationRecorded ?? false) ? 'success' : 'secondary'; ?>">
                            <?php echo ($depreciationRecorded ?? false) ? __('Yes') : __('N/A - Heritage'); ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?php echo __('Insurance Valuations'); ?>
                        <span class="badge bg-<?php echo ($insuranceComplete ?? false) ? 'success' : 'warning'; ?>">
                            <?php echo ($insuranceComplete ?? false) ? __('Complete') : __('Incomplete'); ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i><?php echo __('Asset Categories'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($categories)): ?>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th><?php echo __('Category'); ?></th>
                                <th class="text-end"><?php echo __('Count'); ?></th>
                                <th class="text-end"><?php echo __('Value'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><?php echo esc_entities($cat['name'] ?? 'Uncategorized'); ?></td>
                                <td class="text-end"><?php echo number_format($cat['count'] ?? 0); ?></td>
                                <td class="text-end"><?php echo number_format($cat['value'] ?? 0, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="text-muted"><?php echo __('No category data available.'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Options -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-file-export me-2"></i><?php echo __('Export Heritage Assets Report'); ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <a href="<?php echo url_for('@spectrum_grap_dashboard?slug=' . $resource->slug . '&export=csv'); ?>" class="btn btn-outline-primary w-100">
                        <i class="fas fa-file-csv me-2"></i><?php echo __('Export to CSV'); ?>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="<?php echo url_for('@spectrum_grap_dashboard?slug=' . $resource->slug . '&export=xlsx'); ?>" class="btn btn-outline-success w-100">
                        <i class="fas fa-file-excel me-2"></i><?php echo __('Export to Excel'); ?>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="<?php echo url_for('@spectrum_grap_dashboard?slug=' . $resource->slug . '&export=pdf'); ?>" class="btn btn-outline-danger w-100">
                        <i class="fas fa-file-pdf me-2"></i><?php echo __('Export to PDF'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.heritage-assets-dashboard .card {
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.heritage-assets-dashboard .card-header {
    font-weight: bold;
}
</style>
<?php end_slot(); ?>
