<?php decorate_with('layout_2col.php'); ?>

<?php slot('sidebar'); ?>

  <?php echo get_component('ahgSettings', 'menu'); ?>

<?php end_slot(); ?>

<?php slot('title'); ?>

  <h1>
    <i class="bi bi-shield-check me-2"></i>
    <?php echo __('ICIP Settings'); ?>
  </h1>

<?php end_slot(); ?>

<?php slot('content'); ?>

  <p class="lead text-muted mb-4">
    <?php echo __('Configure Indigenous Cultural and Intellectual Property management settings.'); ?>
  </p>

  <?php echo $form->renderGlobalErrors(); ?>

  <?php echo $form->renderFormTag(url_for(['module' => 'ahgSettings', 'action' => 'icipSettings'])); ?>

    <?php echo $form->renderHiddenFields(); ?>

    <!-- Display Settings -->
    <div class="accordion mb-3">
      <div class="accordion-item">
        <h2 class="accordion-header" id="display-heading">
          <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#display-collapse" aria-expanded="true" aria-controls="display-collapse">
            <?php echo __('Display Settings'); ?>
          </button>
        </h2>
        <div id="display-collapse" class="accordion-collapse collapse show" aria-labelledby="display-heading">
          <div class="accordion-body">
            <?php echo render_field(
                $form->enable_public_notices
                    ->label(__('Display cultural notices to public users'))
                    ->help(__('When enabled, cultural sensitivity notices will be displayed on the public view of records.'))
            ); ?>

            <?php echo render_field(
                $form->enable_staff_notices
                    ->label(__('Display cultural notices to staff'))
                    ->help(__('When enabled, cultural sensitivity notices will be displayed to authenticated staff members.'))
            ); ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Acknowledgement Settings -->
    <div class="accordion mb-3">
      <div class="accordion-item">
        <h2 class="accordion-header" id="ack-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ack-collapse" aria-expanded="false" aria-controls="ack-collapse">
            <?php echo __('Acknowledgement Settings'); ?>
          </button>
        </h2>
        <div id="ack-collapse" class="accordion-collapse collapse" aria-labelledby="ack-heading">
          <div class="accordion-body">
            <?php echo render_field(
                $form->require_acknowledgement_default
                    ->label(__('Require acknowledgement by default'))
                    ->help(__('When enabled, new sensitive cultural notices will require user acknowledgement before viewing content.'))
            ); ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Consent & Consultation Settings -->
    <div class="accordion mb-3">
      <div class="accordion-item">
        <h2 class="accordion-header" id="consent-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#consent-collapse" aria-expanded="false" aria-controls="consent-collapse">
            <?php echo __('Consent & Consultation'); ?>
          </button>
        </h2>
        <div id="consent-collapse" class="accordion-collapse collapse" aria-labelledby="consent-heading">
          <div class="accordion-body">
            <?php echo render_field(
                $form->consent_expiry_warning_days
                    ->label(__('Consent expiry warning (days)'))
                    ->help(__('Number of days before consent expiry to show warning in dashboard and reports. Default: 90 days.'))
            ); ?>

            <?php echo render_field(
                $form->default_consultation_follow_up_days
                    ->label(__('Default consultation follow-up (days)'))
                    ->help(__('Default number of days for consultation follow-up reminders. Default: 30 days.'))
            ); ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Local Contexts Integration -->
    <div class="accordion mb-3">
      <div class="accordion-item">
        <h2 class="accordion-header" id="lc-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#lc-collapse" aria-expanded="false" aria-controls="lc-collapse">
            <?php echo __('Local Contexts Integration'); ?>
          </button>
        </h2>
        <div id="lc-collapse" class="accordion-collapse collapse" aria-labelledby="lc-heading">
          <div class="accordion-body">
            <div class="alert alert-info">
              <i class="bi bi-info-circle me-2"></i>
              <?php echo __('Local Contexts Hub provides TK Labels for Indigenous communities. Visit %link% for more information.', ['%link%' => '<a href="https://localcontexts.org/" target="_blank">localcontexts.org</a>']); ?>
            </div>

            <?php echo render_field(
                $form->local_contexts_hub_enabled
                    ->label(__('Enable Local Contexts Hub API'))
                    ->help(__('When enabled, TK Labels can be synchronized with the Local Contexts Hub.'))
            ); ?>

            <?php echo render_field(
                $form->local_contexts_api_key
                    ->label(__('Local Contexts API Key'))
                    ->help(__('API key for accessing the Local Contexts Hub. Leave blank if not using API integration.'))
            ); ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Audit Settings -->
    <div class="accordion mb-3">
      <div class="accordion-item">
        <h2 class="accordion-header" id="audit-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#audit-collapse" aria-expanded="false" aria-controls="audit-collapse">
            <?php echo __('Audit & Logging'); ?>
          </button>
        </h2>
        <div id="audit-collapse" class="accordion-collapse collapse" aria-labelledby="audit-heading">
          <div class="accordion-body">
            <?php echo render_field(
                $form->audit_all_icip_access
                    ->label(__('Log all ICIP record access'))
                    ->help(__('When enabled, all access to records flagged with ICIP content will be logged for audit purposes. Requires ahgAuditTrailPlugin.'))
            ); ?>
          </div>
        </div>
      </div>
    </div>

    <section class="actions mb-3">
      <input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Save'); ?>">
      <a href="<?php echo url_for('icip/dashboard'); ?>" class="btn btn-outline-secondary ms-2">
        <i class="bi bi-arrow-right me-1"></i>
        <?php echo __('Go to ICIP Dashboard'); ?>
      </a>
    </section>

  </form>

<?php end_slot(); ?>
