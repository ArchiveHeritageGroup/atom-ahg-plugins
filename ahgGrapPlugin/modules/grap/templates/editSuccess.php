<?php decorate_with('layout_1col.php'); ?>

<?php
// Load data directly since action variables not passing through
$slug = sfContext::getInstance()->getRequest()->getParameter('slug');
$slugRow = Illuminate\Database\Capsule\Manager::table('slug')->where('slug', $slug)->first();
if ($slugRow) {
    $resource = QubitInformationObject::getById($slugRow->object_id);
    $grapRow = Illuminate\Database\Capsule\Manager::table('grap_heritage_asset')->where('object_id', $slugRow->object_id)->first();
    $grapData = $grapRow ? (array)$grapRow : ['object_id' => $slugRow->object_id];
}
$form = new GrapHeritageAssetForm($grapData);
?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0" aria-describedby="heading-label">
      <?php echo __('GRAP 103 Financial Data'); ?>
    </h1>
    <span class="small" id="heading-label">
      <?php echo render_title($resource); ?>
    </span>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php echo $form->renderGlobalErrors(); ?>

  <form method="post" id="editForm">
    <?php echo $form->renderHiddenFields(); ?>

    <div class="accordion mb-3">
      
      <!-- Recognition & Measurement -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="recognition-heading">
          <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#recognition-collapse" aria-expanded="true" aria-controls="recognition-collapse">
            <?php echo __('Recognition & Measurement'); ?>
          </button>
        </h2>
        <div id="recognition-collapse" class="accordion-collapse collapse show" aria-labelledby="recognition-heading">
          <div class="accordion-body">
            <?php echo render_field($form->recognition_status
                ->help(__('GRAP 103.14-21: Whether the asset is recognised in the financial statements.'))
                ->label(__('Recognition status'))); ?>

            <?php echo render_field($form->recognition_status_reason
                ->help(__('Explain why the asset is or is not recognised.'))
                ->label(__('Recognition status reason')), null, ['class' => 'resizable']); ?>

            <?php echo render_field($form->measurement_basis
                ->help(__('GRAP 103.22-49: The basis on which the carrying amount is measured.'))
                ->label(__('Measurement basis'))); ?>

            <?php echo render_field($form->recognition_date
                ->help(__('Date the asset was initially recognised.'))
                ->label(__('Recognition date'))); ?>

            <?php echo render_field($form->initial_carrying_amount
                ->help(__('The carrying amount when first recognised.'))
                ->label(__('Initial carrying amount (R)'))); ?>

            <?php echo render_field($form->current_carrying_amount
                ->help(__('Current amount after deducting depreciation and impairment.'))
                ->label(__('Current carrying amount (R)'))); ?>
          </div>
        </div>
      </div>

      <!-- Classification -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="classification-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#classification-collapse" aria-expanded="false" aria-controls="classification-collapse">
            <?php echo __('Classification'); ?>
          </button>
        </h2>
        <div id="classification-collapse" class="accordion-collapse collapse" aria-labelledby="classification-heading">
          <div class="accordion-body">
            <?php echo render_field($form->asset_class
                ->help(__('GRAP 103.10-13: Classification for financial reporting.'))
                ->label(__('Asset class'))); ?>

            <?php echo render_field($form->asset_sub_class
                ->help(__('Further sub-classification within the asset class.'))
                ->label(__('Asset sub-class'))); ?>
          </div>
        </div>
      </div>

      <!-- Acquisition -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="acquisition-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#acquisition-collapse" aria-expanded="false" aria-controls="acquisition-collapse">
            <?php echo __('Acquisition'); ?>
          </button>
        </h2>
        <div id="acquisition-collapse" class="accordion-collapse collapse" aria-labelledby="acquisition-heading">
          <div class="accordion-body">
            <?php echo render_field($form->acquisition_method
                ->help(__('How the heritage asset was acquired.'))
                ->label(__('Acquisition method'))); ?>

            <?php echo render_field($form->acquisition_date
                ->help(__('Date the asset was acquired.'))
                ->label(__('Acquisition date'))); ?>

            <?php echo render_field($form->cost_of_acquisition
                ->help(__('Purchase price or cash equivalent.'))
                ->label(__('Cost of acquisition (R)'))); ?>

            <?php echo render_field($form->fair_value_at_acquisition
                ->help(__('Fair value at date of acquisition.'))
                ->label(__('Fair value at acquisition (R)'))); ?>
          </div>
        </div>
      </div>

      <!-- Financial Classification -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="financial-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#financial-collapse" aria-expanded="false" aria-controls="financial-collapse">
            <?php echo __('Financial Classification'); ?>
          </button>
        </h2>
        <div id="financial-collapse" class="accordion-collapse collapse" aria-labelledby="financial-heading">
          <div class="accordion-body">
            <?php echo render_field($form->gl_account_code
                ->help(__('General ledger account code.'))
                ->label(__('GL account code'))); ?>

            <?php echo render_field($form->cost_center
                ->help(__('Cost centre responsible for the asset.'))
                ->label(__('Cost centre'))); ?>

            <?php echo render_field($form->fund_source
                ->help(__('Source of funding for the acquisition.'))
                ->label(__('Fund source'))); ?>
          </div>
        </div>
      </div>

      <!-- Depreciation -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="depreciation-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#depreciation-collapse" aria-expanded="false" aria-controls="depreciation-collapse">
            <?php echo __('Depreciation'); ?>
          </button>
        </h2>
        <div id="depreciation-collapse" class="accordion-collapse collapse" aria-labelledby="depreciation-heading">
          <div class="accordion-body">
            <?php echo render_field($form->depreciation_policy
                ->help(__('GRAP 103.50-58: Heritage assets are generally not depreciated.'))
                ->label(__('Depreciation policy'))); ?>

            <?php echo render_field($form->useful_life_years
                ->help(__('Estimated useful life in years.'))
                ->label(__('Useful life (years)'))); ?>

            <?php echo render_field($form->residual_value
                ->help(__('Estimated residual value at end of useful life.'))
                ->label(__('Residual value (R)'))); ?>

            <?php echo render_field($form->depreciation_method
                ->help(__('Method used to calculate depreciation.'))
                ->label(__('Depreciation method'))); ?>

            <?php echo render_field($form->accumulated_depreciation
                ->help(__('Total depreciation charged since recognition.'))
                ->label(__('Accumulated depreciation (R)'))); ?>
          </div>
        </div>
      </div>

      <!-- Revaluation -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="revaluation-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#revaluation-collapse" aria-expanded="false" aria-controls="revaluation-collapse">
            <?php echo __('Revaluation'); ?>
          </button>
        </h2>
        <div id="revaluation-collapse" class="accordion-collapse collapse" aria-labelledby="revaluation-heading">
          <div class="accordion-body">
            <?php echo render_field($form->last_valuation_date
                ->help(__('Date of most recent valuation.'))
                ->label(__('Last valuation date'))); ?>

            <?php echo render_field($form->last_valuation_amount
                ->help(__('Value determined at last valuation.'))
                ->label(__('Last valuation amount (R)'))); ?>

            <?php echo render_field($form->valuer_name
                ->help(__('Name of person or firm who performed the valuation.'))
                ->label(__('Valuer name'))); ?>

            <?php echo render_field($form->valuer_credentials
                ->help(__('Professional qualifications of the valuer.'))
                ->label(__('Valuer credentials'))); ?>

            <?php echo render_field($form->valuation_method
                ->help(__('Method used to determine fair value.'))
                ->label(__('Valuation method'))); ?>

            <?php echo render_field($form->revaluation_frequency
                ->help(__('How often the asset is revalued.'))
                ->label(__('Revaluation frequency'))); ?>
          </div>
        </div>
      </div>

      <!-- Disclosure -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="disclosure-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#disclosure-collapse" aria-expanded="false" aria-controls="disclosure-collapse">
            <?php echo __('GRAP 103 Disclosure'); ?>
          </button>
        </h2>
        <div id="disclosure-collapse" class="accordion-collapse collapse" aria-labelledby="disclosure-heading">
          <div class="accordion-body">
            <?php echo render_field($form->heritage_significance
                ->help(__('GRAP 103.70-79: Level of heritage significance.'))
                ->label(__('Heritage significance'))); ?>

            <?php echo render_field($form->condition_rating
                ->help(__('Current physical condition of the asset.'))
                ->label(__('Condition rating'))); ?>

            <?php echo render_field($form->restrictions_on_use
                ->help(__('Restrictions on use, sale, or disposal.'))
                ->label(__('Restrictions on use or disposal')), null, ['class' => 'resizable']); ?>

            <?php echo render_field($form->conservation_commitments
                ->help(__('Commitments to conserve or maintain the asset.'))
                ->label(__('Conservation commitments')), null, ['class' => 'resizable']); ?>
          </div>
        </div>
      </div>

      <!-- Insurance -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="insurance-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#insurance-collapse" aria-expanded="false" aria-controls="insurance-collapse">
            <?php echo __('Insurance'); ?>
          </button>
        </h2>
        <div id="insurance-collapse" class="accordion-collapse collapse" aria-labelledby="insurance-heading">
          <div class="accordion-body">
            <?php echo render_field($form->insurance_value
                ->help(__('Value for insurance purposes.'))
                ->label(__('Insurance value (R)'))); ?>

            <?php echo render_field($form->insurance_policy_number
                ->help(__('Reference number of insurance policy.'))
                ->label(__('Insurance policy number'))); ?>

            <?php echo render_field($form->insurance_provider
                ->help(__('Name of insurance company.'))
                ->label(__('Insurance provider'))); ?>

            <?php echo render_field($form->insurance_expiry_date
                ->help(__('Date when insurance coverage expires.'))
                ->label(__('Insurance expiry date'))); ?>
          </div>
        </div>
      </div>

      <!-- Location & Notes -->
      <div class="accordion-item">
        <h2 class="accordion-header" id="notes-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#notes-collapse" aria-expanded="false" aria-controls="notes-collapse">
            <?php echo __('Location & Notes'); ?>
          </button>
        </h2>
        <div id="notes-collapse" class="accordion-collapse collapse" aria-labelledby="notes-heading">
          <div class="accordion-body">
            <?php echo render_field($form->current_location
                ->help(__('Physical location of the asset.'))
                ->label(__('Current location'))); ?>

            <?php echo render_field($form->notes
                ->help(__('Additional notes or comments.'))
                ->label(__('Notes')), null, ['class' => 'resizable']); ?>
          </div>
        </div>
      </div>

    </div>

    <ul class="actions mb-3 nav gap-2">
      <li><?php echo link_to(__('Cancel'), [$resource, 'module' => 'informationobject'], ['class' => 'btn atom-btn-outline-light', 'role' => 'button']); ?></li>
      <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Save'); ?>"></li>
    </ul>

  </form>

<?php end_slot(); ?>