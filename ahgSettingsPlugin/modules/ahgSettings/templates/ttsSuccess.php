<?php use_helper('I18N') ?>

<div class="container-fluid py-4">
  <div class="row">
    <!-- Left sidebar menu -->
    <div class="col-md-3">
      <?php include_component('ahgSettings', 'menu') ?>
    </div>

    <!-- Main content -->
    <div class="col-md-9">
      <h2><i class="fas fa-volume-up me-2"></i><?php echo __('Text-to-Speech Settings') ?></h2>
      <p class="text-muted"><?php echo __('Configure the read-aloud accessibility feature for record detail pages.') ?></p>

      <?php if ($sf_user->hasFlash('notice')): ?>
        <div class="alert alert-success alert-dismissible fade show">
          <?php echo $sf_user->getFlash('notice') ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif ?>

      <form method="post" action="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'tts']) ?>">

        <!-- Global Settings -->
        <div class="card mb-4">
          <div class="card-header"><i class="fas fa-cog me-2"></i><?php echo __('General') ?></div>
          <div class="card-body">

            <div class="mb-3">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="tts_enabled"
                       name="tts[all][enabled]" value="1"
                       <?php echo ($settings['all']['enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
                <label class="form-check-label" for="tts_enabled">
                  <strong><?php echo __('Enable Text-to-Speech') ?></strong>
                </label>
              </div>
              <div class="form-text"><?php echo __('Show the read-aloud button on record detail pages.') ?></div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="tts_rate"><?php echo __('Speech Rate') ?>: <span id="tts_rate_val"><?php echo htmlspecialchars($settings['all']['default_rate'] ?? '1.0') ?></span></label>
              <input type="range" class="form-range" id="tts_rate"
                     name="tts[all][default_rate]"
                     min="0.5" max="2.0" step="0.1"
                     value="<?php echo htmlspecialchars($settings['all']['default_rate'] ?? '1.0') ?>"
                     oninput="document.getElementById('tts_rate_val').textContent=this.value">
              <div class="form-text"><?php echo __('Playback speed (0.5 = slow, 2.0 = fast).') ?></div>
            </div>

            <div class="mb-3">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="tts_labels"
                       name="tts[all][read_labels]" value="1"
                       <?php echo ($settings['all']['read_labels'] ?? '1') === '1' ? 'checked' : '' ?>>
                <label class="form-check-label" for="tts_labels"><?php echo __('Read field labels') ?></label>
              </div>
              <div class="form-text"><?php echo __('Include field labels (e.g. "Scope and content:") when reading aloud.') ?></div>
            </div>

            <div class="mb-3">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="tts_shortcuts"
                       name="tts[all][keyboard_shortcuts]" value="1"
                       <?php echo ($settings['all']['keyboard_shortcuts'] ?? '1') === '1' ? 'checked' : '' ?>>
                <label class="form-check-label" for="tts_shortcuts"><?php echo __('Keyboard shortcuts') ?></label>
              </div>
              <div class="form-text"><?php echo __('Enable keyboard shortcuts for play/pause/stop (Alt+P, Alt+S).') ?></div>
            </div>

          </div>
        </div>

        <!-- Per-sector field selection -->
        <div class="card mb-4">
          <div class="card-header"><i class="fas fa-list-check me-2"></i><?php echo __('Fields to Read per Sector') ?></div>
          <div class="card-body">
            <p class="text-muted mb-3"><?php echo __('Select which metadata fields the TTS engine will read for each GLAM/DAM sector.') ?></p>

            <ul class="nav nav-tabs" role="tablist">
              <?php foreach (['archive', 'library', 'museum', 'gallery', 'dam'] as $idx => $sector): ?>
                <li class="nav-item">
                  <a class="nav-link <?php echo $idx === 0 ? 'active' : '' ?>"
                     data-bs-toggle="tab" href="#sector_<?php echo $sector ?>" role="tab">
                    <?php echo ucfirst($sector) ?>
                  </a>
                </li>
              <?php endforeach ?>
            </ul>

            <div class="tab-content p-3 border border-top-0 rounded-bottom">
              <?php foreach (['archive', 'library', 'museum', 'gallery', 'dam'] as $idx => $sector): ?>
                <?php
                  $currentFields = [];
                  if (!empty($settings[$sector]['fields_to_read'])) {
                      $decoded = json_decode($settings[$sector]['fields_to_read'], true);
                      if (is_array($decoded)) {
                          $currentFields = $decoded;
                      }
                  }
                ?>
                <div class="tab-pane fade <?php echo $idx === 0 ? 'show active' : '' ?>" id="sector_<?php echo $sector ?>">
                  <div class="row">
                    <?php foreach ($availableFieldsList as $field): ?>
                      <div class="col-md-4 mb-2">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox"
                                 id="tts_<?php echo $sector ?>_<?php echo $field ?>"
                                 name="tts[<?php echo $sector ?>][fields_to_read][]"
                                 value="<?php echo $field ?>"
                                 <?php echo in_array($field, $currentFields) ? 'checked' : '' ?>>
                          <label class="form-check-label" for="tts_<?php echo $sector ?>_<?php echo $field ?>">
                            <?php echo $field ?>
                          </label>
                        </div>
                      </div>
                    <?php endforeach ?>
                  </div>
                </div>
              <?php endforeach ?>
            </div>

          </div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i><?php echo __('Save') ?>
          </button>
          <a href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
            <?php echo __('Cancel') ?>
          </a>
        </div>

      </form>
    </div>
  </div>
</div>
