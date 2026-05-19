<?php
/**
 * Authority Resolution - "Create new authority" pre-fill form (Task 6).
 *
 * Renders one PERSON / ORG / PLACE record form pre-filled from PrefillEngine
 * (external sources + mention context). Each pre-filled field carries a
 * provenance badge and hidden `_provenance[<field>][...]` inputs so the
 * server can replay the source attribution into Fuseki on submit.
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * GPL-3.0-or-later.
 */
?>
<?php decorate_with('layout_1col'); ?>

<?php
  $mention    = $sf_data->getRaw('mention');
  $prefill    = $sf_data->getRaw('prefill');
  $entityType = (string) $sf_data->getRaw('entity_type');

  $merged = is_array($prefill['merged_fields'] ?? null) ? $prefill['merged_fields'] : [];
  $lookups = is_array($prefill['lookup_results'] ?? null) ? $prefill['lookup_results'] : [];

  /**
   * Extract value + provenance for a field, returning ['value' => string, 'prov' => array|null].
   */
  $get = function ($key) use ($merged) {
    if (!isset($merged[$key])) {
      return ['value' => '', 'prov' => null];
    }
    $entry = $merged[$key];
    if (is_array($entry) && array_key_exists('value', $entry)) {
      return [
        'value' => (string) ($entry['value'] ?? ''),
        'prov'  => isset($entry['_provenance']) && is_array($entry['_provenance']) ? $entry['_provenance'] : null,
      ];
    }
    return ['value' => is_scalar($entry) ? (string) $entry : '', 'prov' => null];
  };

  $typeBadges = [
    'PERSON' => 'primary',
    'ORG'    => 'info',
    'PLACE'  => 'success',
  ];
?>

<?php slot('title'); ?>
  <h1>
    <i class="fas fa-plus-circle me-2"></i>
    <?php echo __('Create new authority record'); ?>
    <span class="badge bg-<?php echo $typeBadges[$entityType] ?? 'secondary'; ?> ms-2">
      <?php echo htmlspecialchars($entityType); ?>
    </span>
  </h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="<?php echo url_for('@ar_auth_res_index'); ?>"><?php echo __('Authority Resolution'); ?></a>
      </li>
      <li class="breadcrumb-item">
        <a href="<?php echo url_for('@ar_auth_res_review?id=' . (int) $mention->id); ?>">
          <?php echo __('Mention'); ?> #<?php echo (int) $mention->id; ?>
        </a>
      </li>
      <li class="breadcrumb-item active"><?php echo __('Create new'); ?></li>
    </ol>
  </nav>

  <?php $flashes = $sf_user->getFlash('notice'); if ($flashes): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars((string) $flashes); ?></div>
  <?php endif; ?>
  <?php $flashErr = $sf_user->getFlash('error'); if ($flashErr): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars((string) $flashErr); ?></div>
  <?php endif; ?>
<?php end_slot(); ?>

<?php slot('content'); ?>

<div class="row g-3">

  <!-- ================ MAIN FORM ================ -->
  <div class="col-lg-8">

    <div class="card mb-3">
      <div class="card-header">
        <strong><i class="fas fa-magic me-1"></i><?php echo __('Pre-filled from external sources + context'); ?></strong>
      </div>
      <div class="card-body">

        <p class="small text-muted mb-3">
          <?php echo __('Source mention'); ?>: <strong><?php echo htmlspecialchars((string) $mention->entity_value); ?></strong>
          <?php if (!empty($mention->io_title)): ?>
            <?php echo __('from'); ?>
            <?php if (!empty($mention->io_slug)): ?>
              <a href="/<?php echo htmlspecialchars((string) $mention->io_slug); ?>" target="_blank" rel="noopener">
                <?php echo htmlspecialchars((string) $mention->io_title); ?>
              </a>
            <?php else: ?>
              <em><?php echo htmlspecialchars((string) $mention->io_title); ?></em>
            <?php endif; ?>
          <?php endif; ?>
        </p>

        <form method="post" action="<?php echo url_for('@ar_auth_res_create_new_submit?id=' . (int) $mention->id); ?>">

          <?php if ($entityType === 'PLACE'): ?>

            <?php $f = $get('name'); include_partial('authorityResolution/prefillField', [
              'name'  => 'name',
              'label' => __('Place name'),
              'value' => $f['value'],
              'prov'  => $f['prov'],
              'type'  => 'text',
              'help'  => __('Required. Becomes the term.name value.'),
            ]); ?>

            <div class="row g-2">
              <div class="col-md-6">
                <?php $f = $get('latitude'); include_partial('authorityResolution/prefillField', [
                  'name' => 'latitude', 'label' => __('Latitude'), 'value' => $f['value'], 'prov' => $f['prov'], 'type' => 'number',
                  'help' => __('Decimal degrees. Optional but if provided longitude is required.'),
                ]); ?>
              </div>
              <div class="col-md-6">
                <?php $f = $get('longitude'); include_partial('authorityResolution/prefillField', [
                  'name' => 'longitude', 'label' => __('Longitude'), 'value' => $f['value'], 'prov' => $f['prov'], 'type' => 'number',
                  'help' => __('Decimal degrees. Optional but if provided latitude is required.'),
                ]); ?>
              </div>
            </div>

            <input type="hidden" name="source_culture" value="en">

          <?php else: /* PERSON / ORG */ ?>

            <?php $f = $get('authorized_form_of_name'); include_partial('authorityResolution/prefillField', [
              'name'  => 'authorized_form_of_name',
              'label' => __('Authorized form of name'),
              'value' => $f['value'] !== '' ? $f['value'] : (string) ($mention->entity_value ?? ''),
              'prov'  => $f['prov'],
              'type'  => 'text',
              'help'  => __('ISAAR-CPF mandatory.'),
            ]); ?>

            <?php $f = $get('dates_of_existence'); include_partial('authorityResolution/prefillField', [
              'name'  => 'dates_of_existence',
              'label' => __('Dates of existence'),
              'value' => $f['value'],
              'prov'  => $f['prov'],
              'type'  => 'text',
              'help'  => __('ISAAR-CPF mandatory. e.g. "1818-1895".'),
            ]); ?>

            <?php $f = $get('history'); include_partial('authorityResolution/prefillField', [
              'name'  => 'history',
              'label' => __('History'),
              'value' => $f['value'],
              'prov'  => $f['prov'],
              'type'  => 'textarea',
              'rows'  => 4,
              'help'  => __('ISAAR-CPF mandatory. Biographical / institutional history.'),
            ]); ?>

            <?php $f = $get('places'); include_partial('authorityResolution/prefillField', [
              'name'  => 'places',
              'label' => __('Places'),
              'value' => $f['value'],
              'prov'  => $f['prov'],
              'type'  => 'textarea',
              'rows'  => 2,
            ]); ?>

            <?php $f = $get('mandates'); include_partial('authorityResolution/prefillField', [
              'name'  => 'mandates',
              'label' => __('Mandates'),
              'value' => $f['value'],
              'prov'  => $f['prov'],
              'type'  => 'textarea',
              'rows'  => 2,
            ]); ?>

            <?php $f = $get('functions'); include_partial('authorityResolution/prefillField', [
              'name'  => 'functions',
              'label' => __('Functions'),
              'value' => $f['value'],
              'prov'  => $f['prov'],
              'type'  => 'textarea',
              'rows'  => 2,
            ]); ?>

            <?php $f = $get('legal_status'); include_partial('authorityResolution/prefillField', [
              'name'  => 'legal_status',
              'label' => __('Legal status'),
              'value' => $f['value'],
              'prov'  => $f['prov'],
              'type'  => 'text',
            ]); ?>

            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label small mb-1"><?php echo __('Descriptive standard'); ?></label>
                <input type="text" name="descriptive_standard" value="ISAAR-CPF" class="form-control form-control-sm">
              </div>
              <div class="col-md-6">
                <label class="form-label small mb-1"><?php echo __('Source culture'); ?></label>
                <input type="text" name="source_culture" value="en" class="form-control form-control-sm">
              </div>
            </div>

          <?php endif; ?>

          <hr>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">
              <i class="fas fa-save me-1"></i><?php echo __('Create authority record'); ?>
            </button>
            <a href="<?php echo url_for('@ar_auth_res_review?id=' . (int) $mention->id); ?>" class="btn btn-link">
              <i class="fas fa-arrow-left me-1"></i><?php echo __('Cancel'); ?>
            </a>
          </div>

        </form>

      </div>
    </div>

  </div>

  <!-- ================ SIDEBAR: LOOKUP DEBUG ================ -->
  <div class="col-lg-4">

    <div class="card mb-3">
      <div class="card-header"><strong><i class="fas fa-info-circle me-1"></i><?php echo __('External lookup results'); ?></strong></div>
      <div class="card-body p-0">
        <?php if (empty($lookups)): ?>
          <p class="text-muted small p-3 mb-0">
            <?php echo __('No external sources are enabled. Configure them at'); ?>
            <a href="<?php echo url_for('@ar_auth_res_lookup_settings'); ?>"><?php echo __('Lookup settings'); ?></a>.
          </p>
        <?php else: ?>
          <ul class="list-group list-group-flush small">
            <?php foreach ($lookups as $src => $payload): ?>
              <?php
                $enabled = !empty($payload['enabled']);
                $count = isset($payload['results']) && is_array($payload['results']) ? count($payload['results']) : 0;
                $cached = !empty($payload['cached']);
                $error = isset($payload['error']) ? (string) $payload['error'] : '';
              ?>
              <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                <span>
                  <strong><?php echo htmlspecialchars((string) $src); ?></strong>
                  <?php if (!$enabled): ?>
                    <span class="badge bg-secondary ms-1"><?php echo __('disabled'); ?></span>
                  <?php elseif ($error !== ''): ?>
                    <span class="badge bg-warning text-dark ms-1" title="<?php echo htmlspecialchars($error); ?>"><?php echo __('error'); ?></span>
                  <?php else: ?>
                    <span class="badge bg-success ms-1"><?php echo (int) $count; ?> <?php echo __('hit(s)'); ?></span>
                    <?php if ($cached): ?>
                      <span class="badge bg-info text-dark ms-1"><?php echo __('cached'); ?></span>
                    <?php endif; ?>
                  <?php endif; ?>
                </span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><strong><i class="fas fa-shield-alt me-1"></i><?php echo __('After create'); ?></strong></div>
      <div class="card-body small">
        <ul class="mb-0 ps-3">
          <li><?php echo __('New record inserted via Qubit CTI (object + actor/term + i18n + slug).'); ?></li>
          <li><?php echo __('Per-field provenance written to Fuseki graph'); ?>:
            <code>urn:atom:auth-res:graph:field-provenance</code></li>
          <li><?php echo __('create_new decision row written to ahg_mention_decision.'); ?></li>
          <li><?php echo __('Mention state advances to new_record_created.'); ?></li>
        </ul>
      </div>
    </div>
  </div>

</div>

<?php end_slot(); ?>
