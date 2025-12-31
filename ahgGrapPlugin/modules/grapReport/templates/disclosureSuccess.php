<?php decorate_with('layout_1col.php') ?>

<?php slot('title') ?>
  <h1 class="text-success"><?php echo __('GRAP 103 Heritage Assets - Disclosure Note') ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>

<?php
// Get raw arrays to avoid sfOutputEscaperArrayDecorator issues
$summaryByClassRaw = $sf_data->getRaw('summaryByClass') ?: [];
$grandTotalsRaw = $sf_data->getRaw('grandTotals') ?: [];
$notRecognizedRaw = $sf_data->getRaw('notRecognized') ?: [];
$valuationScheduleRaw = $sf_data->getRaw('valuationSchedule') ?: [];
?>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
  .grap-disclosure-note { max-width: 900px; margin: 0 auto; }
  .grap-disclosure-note h3 { margin-top: 30px; border-bottom: 2px solid #1B5E20; padding-bottom: 5px; color: #1B5E20; }
  .grap-disclosure-note .total-row { background-color: #E8F5E9; font-weight: bold; }
  .grap-disclosure-note .report-header { margin-bottom: 30px; }
  .grap-disclosure-note .report-header h2 { color: #1B5E20; }
  .btn-grap { background-color: #1B5E20; border-color: #1B5E20; color: #fff; }
  .btn-grap:hover { background-color: #2E7D32; border-color: #2E7D32; color: #fff; }
  @media print {
    .grap-disclosure-note { font-size: 10pt; }
    .grap-disclosure-note h3 { font-size: 12pt; }
    .actions { display: none; }
  }
</style>

<div class="grap-disclosure-note">

  <div class="report-header text-center">
    <h2><?php echo __('Notes to the Financial Statements') ?></h2>
    <h3 style="border: none;"><?php echo __('Heritage Assets (GRAP 103)') ?></h3>
    <p><strong><?php echo __('Financial Year:') ?></strong> <?php echo $financialYear ?></p>
    <p><strong><?php echo __('Report Date:') ?></strong> <?php echo $reportDate ?></p>
  </div>

  <hr>

  <!-- Accounting Policy -->
  <section>
    <h3><?php echo __('Accounting Policy') ?></h3>
    <p>
      <?php echo __('Heritage assets are assets that have cultural, environmental, historical, natural, scientific, technological or artistic significance and are held indefinitely for the benefit of present and future generations.') ?>
    </p>
    <p>
      <?php echo __('Heritage assets are recognised when it is probable that future economic benefits or service potential will flow to the entity, and where the cost or fair value can be reliably measured.') ?>
    </p>
    <p>
      <?php echo __('Heritage assets are measured at cost or, where acquired through non-exchange transactions, at fair value at the date of acquisition. Heritage assets are not depreciated due to the uncertainty regarding their useful lives and indefinite nature.') ?>
    </p>
  </section>

  <!-- Main Disclosure Table -->
  <section>
    <h3><?php echo __('Heritage Assets by Class') ?></h3>
    <div class="table-responsive">
      <table class="table table-bordered">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Asset Class') ?></th>
            <th class="text-end"><?php echo __('Number of Assets') ?></th>
            <th class="text-end"><?php echo __('Recognised') ?></th>
            <th class="text-end"><?php echo __('Not Recognised') ?></th>
            <th class="text-end"><?php echo __('Cost (R)') ?></th>
            <th class="text-end"><?php echo __('Carrying Amount (R)') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($summaryByClassRaw as $row): ?>
            <tr>
              <td><?php echo esc_entities(GrapHeritageAssetForm::getAssetClassChoices()[$row['asset_class']] ?? $row['asset_class'] ?? __('Unclassified')) ?></td>
              <td class="text-end"><?php echo number_format($row['asset_count'] ?? 0) ?></td>
              <td class="text-end"><?php echo number_format($row['recognized_count'] ?? 0) ?></td>
              <td class="text-end"><?php echo number_format($row['not_recognized_count'] ?? 0) ?></td>
              <td class="text-end"><?php echo number_format($row['total_cost'] ?? 0, 2) ?></td>
              <td class="text-end"><?php echo number_format($row['total_carrying_amount'] ?? 0, 2) ?></td>
            </tr>
          <?php endforeach ?>
        </tbody>
        <tfoot>
          <tr class="total-row">
            <th><?php echo __('Total') ?></th>
            <th class="text-end"><?php echo number_format($grandTotalsRaw['asset_count'] ?? 0) ?></th>
            <th class="text-end"><?php echo number_format($grandTotalsRaw['recognized_count'] ?? 0) ?></th>
            <th class="text-end"><?php echo number_format($grandTotalsRaw['not_recognized_count'] ?? 0) ?></th>
            <th class="text-end"><?php echo number_format($grandTotalsRaw['total_cost'] ?? 0, 2) ?></th>
            <th class="text-end"><?php echo number_format($grandTotalsRaw['total_carrying_amount'] ?? 0, 2) ?></th>
          </tr>
        </tfoot>
      </table>
    </div>
  </section>

  <!-- Assets Not Recognised -->
  <?php if (!empty($notRecognizedRaw)): ?>
    <section>
      <h3><?php echo __('Heritage Assets Not Recognised') ?></h3>
      <p>
        <?php echo __('The following heritage assets have not been recognised as it was not practicable to reliably measure their value at the date of acquisition:') ?>
      </p>
      <div class="table-responsive">
        <table class="table table-bordered table-sm">
          <thead class="table-light">
            <tr>
              <th><?php echo __('Reference') ?></th>
              <th><?php echo __('Description') ?></th>
              <th><?php echo __('Class') ?></th>
              <th><?php echo __('Reason') ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($notRecognizedRaw as $asset): ?>
              <tr>
                <td><?php echo esc_entities($asset['reference_code'] ?? '-') ?></td>
                <td><?php echo esc_entities($asset['title'] ?? '-') ?></td>
                <td><?php echo esc_entities(GrapHeritageAssetForm::getAssetClassChoices()[$asset['asset_class']] ?? $asset['asset_class'] ?? '-') ?></td>
                <td><?php echo esc_entities($asset['recognition_status_reason'] ?? __('Cost/fair value cannot be reliably measured')) ?></td>
              </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endif ?>

  <!-- Insurance -->
  <section>
    <h3><?php echo __('Insurance Coverage') ?></h3>
    <p>
      <?php echo __('Heritage assets are insured for a total value of') ?>
      <strong>R <?php echo number_format($grandTotalsRaw['total_insurance_value'] ?? 0, 2) ?></strong>.
    </p>
  </section>

  <!-- Valuation Information -->
  <section>
    <h3><?php echo __('Revaluation') ?></h3>
    <p>
      <?php echo __('Heritage assets measured using the revaluation model are revalued at intervals sufficient to ensure that the carrying amount does not differ materially from fair value at the reporting date.') ?>
    </p>
    <?php
      $overdue = [];
      $neverValued = [];
      foreach ($valuationScheduleRaw as $v) {
          if (($v['valuation_status'] ?? '') === 'Overdue') {
              $overdue[] = $v;
          } elseif (($v['valuation_status'] ?? '') === 'Never valued') {
              $neverValued[] = $v;
          }
      }
    ?>
    <?php if (!empty($overdue)): ?>
      <div class="alert alert-warning">
        <strong><?php echo __('Note:') ?></strong>
        <?php echo count($overdue) ?> <?php echo __('assets are due for revaluation.') ?>
      </div>
    <?php endif ?>
    <?php if (!empty($neverValued)): ?>
      <div class="alert alert-info">
        <strong><?php echo __('Note:') ?></strong>
        <?php echo count($neverValued) ?> <?php echo __('assets have never been valued.') ?>
      </div>
    <?php endif ?>
  </section>

  <!-- Depreciation -->
  <section>
    <h3><?php echo __('Depreciation') ?></h3>
    <p>
      <?php echo __('Heritage assets are not depreciated as their residual values are expected to be greater than their carrying amounts or their useful lives are indefinite.') ?>
    </p>
    <?php if (($grandTotalsRaw['total_accumulated_depreciation'] ?? 0) > 0): ?>
      <p>
        <?php echo __('Accumulated depreciation on certain operational components:') ?>
        <strong>R <?php echo number_format($grandTotalsRaw['total_accumulated_depreciation'], 2) ?></strong>
      </p>
    <?php endif ?>
  </section>

  <!-- Impairment -->
  <?php if (($grandTotalsRaw['total_impairment'] ?? 0) > 0): ?>
    <section>
      <h3><?php echo __('Impairment') ?></h3>
      <p>
        <?php echo __('Total impairment losses recognised:') ?>
        <strong>R <?php echo number_format($grandTotalsRaw['total_impairment'], 2) ?></strong>
      </p>
    </section>
  <?php endif ?>

</div>

<!-- Actions -->
<div class="mt-4">
  <a href="<?php echo url_for('grapReport/index') ?>" class="btn btn-outline-secondary">
    <i class="fa fa-arrow-left"></i> <?php echo __('Back to Dashboard') ?>
  </a>
  <a href="javascript:window.print()" class="btn btn-grap">
    <i class="fa fa-print"></i> <?php echo __('Print') ?>
  </a>
</div>

<?php end_slot() ?>
