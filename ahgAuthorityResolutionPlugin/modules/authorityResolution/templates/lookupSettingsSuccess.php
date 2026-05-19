<?php
/**
 * Authority Resolution - external lookup adapter settings (Task 6).
 *
 * One row per source (VIAF, Wikidata, GeoNames, TGN, GND, ISNI, SAGNC).
 * Admin can toggle enabled, tune rate_limit / cache_ttl, and edit the
 * license_note + license_url written into per-field provenance triples.
 * Also exposes the global precedence (comma-separated source list) and
 * GeoNames API username.
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * GPL-3.0-or-later.
 */
?>
<?php decorate_with('layout_1col'); ?>

<?php
  $sources         = $sf_data->getRaw('sources');
  $settings        = $sf_data->getRaw('settings');
  $precedence      = (string) $sf_data->getRaw('precedence');
  $geonamesUsername = (string) $sf_data->getRaw('geonamesUsername');

  $sourceInfo = [
    'viaf'     => ['label' => 'VIAF',     'desc' => 'Virtual International Authority File. No key; CC0.'],
    'wikidata' => ['label' => 'Wikidata', 'desc' => 'Wikidata wbsearchentities. No key; CC0.'],
    'geonames' => ['label' => 'GeoNames', 'desc' => 'GeoNames searchJSON. Free username required (see below); CC BY 4.0.'],
    'tgn'      => ['label' => 'Getty TGN','desc' => 'Getty Thesaurus of Geographic Names SPARQL. No key; ODbL 1.0. (stub)'],
    'gnd'      => ['label' => 'GND',      'desc' => 'Deutsche Nationalbibliothek Integrated Authority File (lobid). No key; CC0. (stub)'],
    'isni'     => ['label' => 'ISNI',     'desc' => 'International Standard Name Identifier SRU. Institutional creds required. (stub)'],
    'sagnc'    => ['label' => 'SAGNC',    'desc' => 'South African Geographical Names Council. (stub)'],
  ];
?>

<?php slot('title'); ?>
  <h1><i class="fas fa-sliders-h me-2"></i><?php echo __('Lookup settings'); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="<?php echo url_for('@ar_auth_res_index'); ?>"><?php echo __('Authority Resolution'); ?></a>
      </li>
      <li class="breadcrumb-item active"><?php echo __('Lookup settings'); ?></li>
    </ol>
  </nav>
  <?php $flash = $sf_user->getFlash('notice'); if ($flash): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars((string) $flash); ?></div>
  <?php endif; ?>
  <?php $flashErr = $sf_user->getFlash('error'); if ($flashErr): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars((string) $flashErr); ?></div>
  <?php endif; ?>
<?php end_slot(); ?>

<?php slot('content'); ?>

<form method="post" action="<?php echo url_for('@ar_auth_res_lookup_settings_save'); ?>">

  <div class="alert alert-warning small">
    <i class="fas fa-exclamation-triangle me-1"></i>
    <?php echo __('All sources default to disabled. Heratio will never make outbound HTTP calls until a source is explicitly enabled here.'); ?>
  </div>

  <div class="card mb-3">
    <div class="card-header"><strong><?php echo __('External authority sources'); ?></strong></div>
    <div class="table-responsive">
      <table class="table table-striped table-sm align-middle mb-0">
        <thead>
          <tr>
            <th style="width: 14%"><?php echo __('Source'); ?></th>
            <th style="width: 8%"><?php echo __('Enabled'); ?></th>
            <th style="width: 11%"><?php echo __('Rate limit'); ?> <small class="text-muted">(/min)</small></th>
            <th style="width: 12%"><?php echo __('Cache TTL'); ?> <small class="text-muted">(s)</small></th>
            <th style="width: 15%"><?php echo __('License note'); ?></th>
            <th style="width: 20%"><?php echo __('License URL'); ?></th>
            <th><?php echo __('Description'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sources as $src):
            $row = isset($settings[$src]) ? $settings[$src] : [];
            $info = $sourceInfo[$src] ?? ['label' => $src, 'desc' => ''];
            $enabled = ($row['enabled'] ?? '0') === '1';
          ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($info['label']); ?></strong>
                <br><code class="small"><?php echo htmlspecialchars($src); ?></code></td>
              <td>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox"
                         name="sources[<?php echo htmlspecialchars($src); ?>][enabled]"
                         value="1"<?php echo $enabled ? ' checked' : ''; ?>>
                </div>
              </td>
              <td>
                <input type="number" min="1" max="1000" class="form-control form-control-sm"
                       name="sources[<?php echo htmlspecialchars($src); ?>][rate_limit]"
                       value="<?php echo htmlspecialchars((string) ($row['rate_limit'] ?? '')); ?>">
              </td>
              <td>
                <input type="number" min="0" max="31536000" class="form-control form-control-sm"
                       name="sources[<?php echo htmlspecialchars($src); ?>][cache_ttl]"
                       value="<?php echo htmlspecialchars((string) ($row['cache_ttl'] ?? '')); ?>">
              </td>
              <td>
                <input type="text" class="form-control form-control-sm"
                       name="sources[<?php echo htmlspecialchars($src); ?>][license_note]"
                       value="<?php echo htmlspecialchars((string) ($row['license_note'] ?? '')); ?>">
              </td>
              <td>
                <input type="url" class="form-control form-control-sm"
                       name="sources[<?php echo htmlspecialchars($src); ?>][license_url]"
                       value="<?php echo htmlspecialchars((string) ($row['license_url'] ?? '')); ?>">
              </td>
              <td class="small text-muted"><?php echo htmlspecialchars($info['desc']); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header"><strong><?php echo __('Global lookup configuration'); ?></strong></div>
    <div class="card-body">
      <div class="mb-3">
        <label class="form-label fw-bold"><?php echo __('Source precedence'); ?></label>
        <input type="text" name="precedence" class="form-control"
               value="<?php echo htmlspecialchars($precedence); ?>"
               placeholder="viaf,wikidata,geonames,tgn,gnd,isni,sagnc">
        <div class="form-text">
          <?php echo __('Comma-separated source list. First source wins when two adapters disagree on a field.'); ?>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label fw-bold"><?php echo __('GeoNames username'); ?></label>
        <input type="text" name="geonames_username" class="form-control"
               value="<?php echo htmlspecialchars($geonamesUsername); ?>"
               placeholder="archivist123">
        <div class="form-text">
          <?php echo __('Required when GeoNames is enabled. Sign up:'); ?>
          <a href="https://www.geonames.org/login" target="_blank" rel="noopener">https://www.geonames.org/login</a>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex gap-2 mb-3">
    <button type="submit" class="btn btn-primary">
      <i class="fas fa-save me-1"></i><?php echo __('Save settings'); ?>
    </button>
    <a href="<?php echo url_for('@ar_auth_res_index'); ?>" class="btn btn-link">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to queue'); ?>
    </a>
  </div>

</form>

<?php end_slot(); ?>
