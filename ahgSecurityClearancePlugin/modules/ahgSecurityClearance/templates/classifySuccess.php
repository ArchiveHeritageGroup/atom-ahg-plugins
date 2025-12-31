<?php use_helper('Date'); ?>

<?php decorate_with('layout_3col'); ?>

<?php slot('sidebar'); ?>
  <?php include_component('informationobject', 'contextMenu'); ?>
<?php end_slot(); ?>

<?php slot('title'); ?>
  <h1 class="multiline">
    <?php echo $currentClassification ? __('Reclassify record') : __('Classify record'); ?>
    <span class="sub"><?php echo render_title($resource); ?></span>
  </h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php if ($sf_request->getParameter('error')): ?>
    <div class="alert alert-danger">
      <?php if ('invalid' === $sf_request->getParameter('error')): ?>
        <?php echo __('Please select a valid classification level.'); ?>
      <?php elseif ('failed' === $sf_request->getParameter('error')): ?>
        <?php echo __('Failed to apply classification. Please try again.'); ?>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <section id="content">

    <!-- Record Info -->
    <div class="alert alert-light border mb-4">
      <h6 class="alert-heading"><?php echo render_title($resource); ?></h6>
      <?php if ($resource->identifier): ?>
        <small class="text-muted"><?php echo __('Identifier: %1%', ['%1%' => $resource->identifier]); ?></small>
      <?php endif; ?>
      <?php if ($currentClassification): ?>
        <hr class="my-2">
        <small>
          <strong><?php echo __('Current Classification:'); ?></strong>
          <span class="badge" style="background-color: <?php echo $currentClassification->classificationColor; ?>;">
            <?php echo $currentClassification->classificationName; ?>
          </span>
        </small>
      <?php endif; ?>
    </div>

    <form method="post" action="<?php echo url_for([$resource, 'module' => 'ahgSecurityClearance', 'action' => 'classify']); ?>">

      <!-- Classification Level -->
      <fieldset class="mb-4">
        <legend class="h6 border-bottom pb-2 mb-3">
          <i class="fas fa-lock me-2"></i><?php echo __('Classification Level'); ?>
        </legend>

        <div class="row">
          <?php foreach ($classifications as $c): ?>
            <div class="col-md-4 mb-3">
              <div class="form-check card h-100 <?php echo ($currentClassification && $currentClassification->classificationId == $c->id) ? 'border-primary' : ''; ?>">
                <div class="card-body">
                  <input class="form-check-input" type="radio" 
                         name="classification_id" 
                         id="classification_<?php echo $c->id; ?>"
                         value="<?php echo $c->id; ?>"
                         <?php echo ($currentClassification && $currentClassification->classificationId == $c->id) ? 'checked' : ''; ?>
                         required>
                  <label class="form-check-label w-100" for="classification_<?php echo $c->id; ?>">
                    <span class="badge w-100 py-2 mb-2" style="background-color: <?php echo $c->color; ?>;">
                      <i class="<?php echo $c->icon; ?> me-1"></i>
                      <?php echo $c->name; ?>
                    </span>
                    <small class="d-block text-muted"><?php echo __('Level %1%', ['%1%' => $c->level]); ?></small>
                  </label>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </fieldset>

      <!-- Classification Details -->
      <fieldset class="mb-4">
        <legend class="h6 border-bottom pb-2 mb-3">
          <i class="fas fa-file-alt me-2"></i><?php echo __('Classification Details'); ?>
        </legend>

        <div class="mb-3">
          <label for="reason" class="form-label"><?php echo __('Reason for Classification'); ?></label>
          <textarea name="reason" id="reason" class="form-control" rows="3"
                    placeholder="<?php echo __('Explain why this classification level is appropriate...'); ?>"><?php echo $currentClassification->reason ?? ''; ?></textarea>
          <div class="form-text"><?php echo __('Document the justification for this classification decision.'); ?></div>
        </div>

        <div class="mb-3">
          <label for="handling_instructions" class="form-label"><?php echo __('Special Handling Instructions'); ?></label>
          <textarea name="handling_instructions" id="handling_instructions" class="form-control" rows="2"
                    placeholder="<?php echo __('Any special handling requirements...'); ?>"><?php echo $currentClassification->handlingInstructions ?? ''; ?></textarea>
        </div>
      </fieldset>

      <!-- Review & Declassification -->
      <fieldset class="mb-4">
        <legend class="h6 border-bottom pb-2 mb-3">
          <i class="fas fa-calendar-alt me-2"></i><?php echo __('Review & Declassification'); ?>
        </legend>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="review_date" class="form-label"><?php echo __('Review Date'); ?></label>
            <input type="date" name="review_date" id="review_date" class="form-control"
                   value="<?php echo ($currentClassification && $currentClassification->reviewDate) ? date('Y-m-d', strtotime($currentClassification->reviewDate)) : ''; ?>"
                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
            <div class="form-text"><?php echo __('Date when classification should be reviewed.'); ?></div>
          </div>

          <div class="col-md-6 mb-3">
            <label for="declassify_date" class="form-label"><?php echo __('Auto-Declassify Date'); ?></label>
            <input type="date" name="declassify_date" id="declassify_date" class="form-control"
                   value="<?php echo ($currentClassification && $currentClassification->declassifyDate) ? date('Y-m-d', strtotime($currentClassification->declassifyDate)) : ''; ?>"
                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
            <div class="form-text"><?php echo __('Date when classification will be automatically removed.'); ?></div>
          </div>
        </div>

        <div class="mb-3">
          <label for="declassify_to_id" class="form-label"><?php echo __('Declassify To Level'); ?></label>
          <select name="declassify_to_id" id="declassify_to_id" class="form-control">
            <option value=""><?php echo __('-- Remove classification entirely --'); ?></option>
            <?php foreach ($classifications as $c): ?>
              <option value="<?php echo $c->id; ?>"
                      <?php echo ($currentClassification && $currentClassification->declassifyToId == $c->id) ? 'selected' : ''; ?>>
                <?php echo $c->name; ?> (<?php echo __('Level %1%', ['%1%' => $c->level]); ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text"><?php echo __('When auto-declassified, change to this level instead of making public.'); ?></div>
        </div>
      </fieldset>

      <!-- Inheritance -->
      <fieldset class="mb-4">
        <legend class="h6 border-bottom pb-2 mb-3">
          <i class="fas fa-sitemap me-2"></i><?php echo __('Inheritance'); ?>
        </legend>

        <div class="form-check">
          <input class="form-check-input" type="checkbox" 
                 name="inherit_to_children" id="inherit_to_children" value="1"
                 <?php echo (!$currentClassification || $currentClassification->inheritToChildren) ? 'checked' : ''; ?>>
          <label class="form-check-label" for="inherit_to_children">
            <?php echo __('Apply this classification to all child records'); ?>
          </label>
        </div>
        <div class="form-text"><?php echo __('If checked, all descendant records will inherit this classification level.'); ?></div>
      </fieldset>

      <!-- Actions -->
      <section class="actions">
        <ul>
          <li><?php echo link_to(__('Cancel'), [$resource, 'module' => 'ahgSecurityClearance', 'action' => 'object'], ['class' => 'c-btn']); ?></li>
          <?php if ($currentClassification): ?>
            <li><input class="c-btn c-btn-delete" type="submit" name="action_type" value="declassify" formnovalidate></li>
          <?php endif; ?>
          <li><input class="c-btn c-btn-submit" type="submit" name="action_type" value="classify"></li>
        </ul>
      </section>

    </form>

  </section>

<?php end_slot(); ?>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.form-check.card { cursor: pointer; }
.form-check.card:hover { border-color: #0d6efd; }
.form-check-input:checked + .form-check-label .badge { box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25); }
</style>
