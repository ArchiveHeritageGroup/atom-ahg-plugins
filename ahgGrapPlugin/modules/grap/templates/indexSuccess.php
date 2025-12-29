<?php decorate_with('layout_2col.php') ?>

<?php
// Load data FIRST before any slots use $resource
$slug = sfContext::getInstance()->getRequest()->getParameter('slug');
$slugRow = Illuminate\Database\Capsule\Manager::table('slug')->where('slug', $slug)->first();
$resource = null;
$grapData = [];
$hasData = false;
$canEdit = false;

if ($slugRow) {
    $resource = QubitInformationObject::getById($slugRow->object_id);
    $grapRow = Illuminate\Database\Capsule\Manager::table('grap_heritage_asset')->where('object_id', $slugRow->object_id)->first();
    $grapData = $grapRow ? (array)$grapRow : [];
    $hasData = !empty($grapData);
    $canEdit = $resource ? ($sf_user->isAdministrator() || $sf_user->hasCredential('editor')) : false;
}
?>

<?php slot('sidebar') ?>
  <?php if ($resource): ?>
    <?php include_component('informationobject', 'contextMenu') ?>
  <?php endif; ?>
<?php end_slot() ?>

<?php slot('title') ?>
  <h1 class="multiline">
    <?php echo __('GRAP 103 Financial Data') ?>
    <span class="sub"><?php echo $resource ? render_title($resource) : '' ?></span>
  </h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php if (!$hasData): ?>
  <div class="messages status">
    <?php echo __('No GRAP financial data has been recorded for this item.') ?>
    <?php if ($canEdit): ?>
      <br><br>
      <?php echo link_to(__('Add GRAP data'), '/'.$resource->slug.'/grap/edit', ['class' => 'c-btn c-btn-submit']) ?>
    <?php endif ?>
  </div>
<?php else: ?>

  <!-- Recognition & Measurement -->
  <section id="grapRecognitionSection">
    <h2><?php echo __('Recognition & measurement') ?></h2>

    <?php echo render_show(__('Recognition status'), 
        GrapHeritageAssetForm::getRecognitionStatusChoices()[$grapData['recognition_status'] ?? ''] ?? $grapData['recognition_status']) ?>

    <?php if (!empty($grapData['recognition_status_reason'])): ?>
      <?php echo render_show(__('Recognition status reason'), $grapData['recognition_status_reason']) ?>
    <?php endif ?>

    <?php echo render_show(__('Measurement basis'), 
        GrapHeritageAssetForm::getMeasurementBasisChoices()[$grapData['measurement_basis'] ?? ''] ?? $grapData['measurement_basis']) ?>

    <?php if (!empty($grapData['recognition_date'])): ?>
      <?php echo render_show(__('Recognition date'), Qubit::renderDate($grapData['recognition_date'])) ?>
    <?php endif ?>

    <?php if (!empty($grapData['initial_carrying_amount'])): ?>
      <?php echo render_show(__('Initial carrying amount'), 'R '.number_format($grapData['initial_carrying_amount'], 2)) ?>
    <?php endif ?>

    <?php if (!empty($grapData['current_carrying_amount'])): ?>
      <?php echo render_show(__('Current carrying amount'), 'R '.number_format($grapData['current_carrying_amount'], 2)) ?>
    <?php endif ?>
  </section>

  <!-- Classification -->
  <?php if (!empty($grapData['asset_class']) || !empty($grapData['asset_sub_class'])): ?>
    <section id="grapClassificationSection">
      <h2><?php echo __('Classification') ?></h2>

      <?php echo render_show(__('Asset class'), 
          GrapHeritageAssetForm::getAssetClassChoices()[$grapData['asset_class'] ?? ''] ?? $grapData['asset_class']) ?>

      <?php if (!empty($grapData['asset_sub_class'])): ?>
        <?php echo render_show(__('Asset sub-class'), $grapData['asset_sub_class']) ?>
      <?php endif ?>
    </section>
  <?php endif ?>

  <!-- Acquisition -->
  <?php if (!empty($grapData['acquisition_method']) || !empty($grapData['acquisition_date']) || !empty($grapData['cost_of_acquisition'])): ?>
    <section id="grapAcquisitionSection">
      <h2><?php echo __('Acquisition') ?></h2>

      <?php echo render_show(__('Acquisition method'), 
          GrapHeritageAssetForm::getAcquisitionMethodChoices()[$grapData['acquisition_method'] ?? ''] ?? $grapData['acquisition_method']) ?>

      <?php if (!empty($grapData['acquisition_date'])): ?>
        <?php echo render_show(__('Acquisition date'), Qubit::renderDate($grapData['acquisition_date'])) ?>
      <?php endif ?>

      <?php if (!empty($grapData['cost_of_acquisition'])): ?>
        <?php echo render_show(__('Cost of acquisition'), 'R '.number_format($grapData['cost_of_acquisition'], 2)) ?>
      <?php endif ?>

      <?php if (!empty($grapData['fair_value_at_acquisition'])): ?>
        <?php echo render_show(__('Fair value at acquisition'), 'R '.number_format($grapData['fair_value_at_acquisition'], 2)) ?>
      <?php endif ?>
    </section>
  <?php endif ?>

  <!-- Financial Classification -->
  <?php if (!empty($grapData['gl_account_code']) || !empty($grapData['cost_center']) || !empty($grapData['fund_source'])): ?>
    <section id="grapFinancialSection">
      <h2><?php echo __('Financial classification') ?></h2>

      <?php if (!empty($grapData['gl_account_code'])): ?>
        <?php echo render_show(__('GL account code'), $grapData['gl_account_code']) ?>
      <?php endif ?>

      <?php if (!empty($grapData['cost_center'])): ?>
        <?php echo render_show(__('Cost centre'), $grapData['cost_center']) ?>
      <?php endif ?>

      <?php if (!empty($grapData['fund_source'])): ?>
        <?php echo render_show(__('Fund source'), $grapData['fund_source']) ?>
      <?php endif ?>
    </section>
  <?php endif ?>

  <!-- Depreciation -->
  <?php if (!empty($grapData['depreciation_policy'])): ?>
    <section id="grapDepreciationSection">
      <h2><?php echo __('Depreciation') ?></h2>

      <?php echo render_show(__('Depreciation policy'), 
          GrapHeritageAssetForm::getDepreciationPolicyChoices()[$grapData['depreciation_policy'] ?? ''] ?? $grapData['depreciation_policy']) ?>

      <?php if (!empty($grapData['useful_life_years'])): ?>
        <?php echo render_show(__('Useful life'), $grapData['useful_life_years'].' years') ?>
      <?php endif ?>

      <?php if (!empty($grapData['residual_value'])): ?>
        <?php echo render_show(__('Residual value'), 'R '.number_format($grapData['residual_value'], 2)) ?>
      <?php endif ?>

      <?php if (!empty($grapData['depreciation_method'])): ?>
        <?php echo render_show(__('Depreciation method'), 
            GrapHeritageAssetForm::getDepreciationMethodChoices()[$grapData['depreciation_method'] ?? ''] ?? $grapData['depreciation_method']) ?>
      <?php endif ?>

      <?php if (!empty($grapData['accumulated_depreciation'])): ?>
        <?php echo render_show(__('Accumulated depreciation'), 'R '.number_format($grapData['accumulated_depreciation'], 2)) ?>
      <?php endif ?>
    </section>
  <?php endif ?>

  <!-- Revaluation -->
  <?php if (!empty($grapData['last_valuation_date']) || !empty($grapData['last_valuation_amount'])): ?>
    <section id="grapRevaluationSection">
      <h2><?php echo __('Revaluation') ?></h2>

      <?php if (!empty($grapData['last_valuation_date'])): ?>
        <?php echo render_show(__('Last valuation date'), Qubit::renderDate($grapData['last_valuation_date'])) ?>
      <?php endif ?>

      <?php if (!empty($grapData['last_valuation_amount'])): ?>
        <?php echo render_show(__('Last valuation amount'), 'R '.number_format($grapData['last_valuation_amount'], 2)) ?>
      <?php endif ?>

      <?php if (!empty($grapData['valuer_name'])): ?>
        <?php echo render_show(__('Valuer name'), $grapData['valuer_name']) ?>
      <?php endif ?>

      <?php if (!empty($grapData['valuer_credentials'])): ?>
        <?php echo render_show(__('Valuer credentials'), $grapData['valuer_credentials']) ?>
      <?php endif ?>

      <?php if (!empty($grapData['valuation_method'])): ?>
        <?php echo render_show(__('Valuation method'), 
            GrapHeritageAssetForm::getValuationMethodChoices()[$grapData['valuation_method'] ?? ''] ?? $grapData['valuation_method']) ?>
      <?php endif ?>

      <?php if (!empty($grapData['revaluation_frequency'])): ?>
        <?php echo render_show(__('Revaluation frequency'), 
            GrapHeritageAssetForm::getRevaluationFrequencyChoices()[$grapData['revaluation_frequency'] ?? ''] ?? $grapData['revaluation_frequency']) ?>
      <?php endif ?>
    </section>
  <?php endif ?>

  <!-- Disclosure -->
  <?php if (!empty($grapData['heritage_significance']) || !empty($grapData['condition_rating']) || !empty($grapData['restrictions_on_use']) || !empty($grapData['conservation_commitments'])): ?>
    <section id="grapDisclosureSection">
      <h2><?php echo __('GRAP 103 disclosure') ?></h2>

      <?php if (!empty($grapData['heritage_significance'])): ?>
        <?php echo render_show(__('Heritage significance'), 
            GrapHeritageAssetForm::getHeritageSignificanceChoices()[$grapData['heritage_significance'] ?? ''] ?? $grapData['heritage_significance']) ?>
      <?php endif ?>

      <?php if (!empty($grapData['condition_rating'])): ?>
        <?php echo render_show(__('Condition rating'), 
            GrapHeritageAssetForm::getConditionRatingChoices()[$grapData['condition_rating'] ?? ''] ?? $grapData['condition_rating']) ?>
      <?php endif ?>

      <?php if (!empty($grapData['restrictions_on_use'])): ?>
        <?php echo render_show(__('Restrictions on use or disposal'), $grapData['restrictions_on_use']) ?>
      <?php endif ?>

      <?php if (!empty($grapData['conservation_commitments'])): ?>
        <?php echo render_show(__('Conservation commitments'), $grapData['conservation_commitments']) ?>
      <?php endif ?>
    </section>
  <?php endif ?>

  <!-- Insurance -->
  <?php if (!empty($grapData['insurance_value']) || !empty($grapData['insurance_policy_number'])): ?>
    <section id="grapInsuranceSection">
      <h2><?php echo __('Insurance') ?></h2>

      <?php if (!empty($grapData['insurance_value'])): ?>
        <?php echo render_show(__('Insurance value'), 'R '.number_format($grapData['insurance_value'], 2)) ?>
      <?php endif ?>

      <?php if (!empty($grapData['insurance_policy_number'])): ?>
        <?php echo render_show(__('Insurance policy number'), $grapData['insurance_policy_number']) ?>
      <?php endif ?>

      <?php if (!empty($grapData['insurance_provider'])): ?>
        <?php echo render_show(__('Insurance provider'), $grapData['insurance_provider']) ?>
      <?php endif ?>

      <?php if (!empty($grapData['insurance_expiry_date'])): ?>
        <?php echo render_show(__('Insurance expiry date'), Qubit::renderDate($grapData['insurance_expiry_date'])) ?>
      <?php endif ?>
    </section>
  <?php endif ?>

  <!-- Location & Notes -->
  <?php if (!empty($grapData['current_location']) || !empty($grapData['notes'])): ?>
    <section id="grapNotesSection">
      <h2><?php echo __('Location & notes') ?></h2>

      <?php if (!empty($grapData['current_location'])): ?>
        <?php echo render_show(__('Current location'), $grapData['current_location']) ?>
      <?php endif ?>

      <?php if (!empty($grapData['notes'])): ?>
        <?php echo render_show(__('Notes'), $grapData['notes']) ?>
      <?php endif ?>
    </section>
  <?php endif ?>

<?php endif ?>

<?php end_slot() ?>

<?php slot('after-content') ?>
  <?php if ($canEdit): ?>
    <section class="actions">
      <ul>
        <li><?php echo link_to(__('Edit'), '/'.$resource->slug.'/grap/edit', ['class' => 'c-btn c-btn-submit']) ?></li>
        <li><?php echo link_to(__('Back to record'), [$resource, 'module' => 'informationobject'], ['class' => 'c-btn']) ?></li>
      </ul>
    </section>
  <?php endif ?>
<?php end_slot() ?>
