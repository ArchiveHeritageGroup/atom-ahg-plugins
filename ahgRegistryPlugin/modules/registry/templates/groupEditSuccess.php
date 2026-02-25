<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Edit Group'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('My Groups'), 'url' => url_for(['module' => 'registry', 'action' => 'myGroups'])],
  ['label' => __('Edit Group')],
]]); ?>

<div class="row justify-content-center">
  <div class="col-lg-9">

    <h1 class="h3 mb-4"><?php echo __('Edit Group'); ?></h1>

    <?php if (isset($errors) && count($errors) > 0): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $err): ?>
            <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php $g = $group; ?>

    <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'groupEdit', 'id' => $g->id]); ?>">

      <!-- Basic info -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-users me-2 text-primary"></i><?php echo __('Group Information'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label for="ge-name" class="form-label"><?php echo __('Group Name'); ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="ge-name" name="name" value="<?php echo htmlspecialchars($g->name ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="col-md-4">
              <label for="ge-type" class="form-label"><?php echo __('Group Type'); ?></label>
              <select class="form-select" id="ge-type" name="group_type">
                <?php
                  $gTypes = ['regional' => __('Regional'), 'topic' => __('Topic / Interest'), 'software' => __('Software / Technical'), 'institutional' => __('Institutional'), 'other' => __('Other')];
                  $selType = $g->group_type ?? 'regional';
                  foreach ($gTypes as $val => $label): ?>
                    <option value="<?php echo $val; ?>"<?php echo $selType === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label for="ge-desc" class="form-label"><?php echo __('Description'); ?></label>
              <textarea class="form-control" id="ge-desc" name="description" rows="4"><?php echo htmlspecialchars($g->description ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- Location -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-map-marker-alt me-2 text-danger"></i><?php echo __('Location'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4"><label class="form-label"><?php echo __('City'); ?></label><input type="text" class="form-control" name="city" value="<?php echo htmlspecialchars($g->city ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
            <div class="col-md-4"><label class="form-label"><?php echo __('Country'); ?></label><input type="text" class="form-control" name="country" value="<?php echo htmlspecialchars($g->country ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
            <div class="col-md-4"><label class="form-label"><?php echo __('Region'); ?></label><input type="text" class="form-control" name="region" value="<?php echo htmlspecialchars($g->region ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="ge-virtual" name="is_virtual" value="1"<?php echo !empty($g->is_virtual) ? ' checked' : ''; ?>>
                <label class="form-check-label" for="ge-virtual"><?php echo __('Virtual group'); ?></label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Communication -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-comments me-2 text-info"></i><?php echo __('Communication'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label"><?php echo __('Website'); ?></label><input type="url" class="form-control" name="website" value="<?php echo htmlspecialchars($g->website ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
            <div class="col-md-6"><label class="form-label"><?php echo __('Email'); ?></label><input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($g->email ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
            <div class="col-md-4"><label class="form-label"><?php echo __('Mailing List'); ?></label><input type="url" class="form-control" name="mailing_list_url" value="<?php echo htmlspecialchars($g->mailing_list_url ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
            <div class="col-md-4"><label class="form-label"><?php echo __('Slack'); ?></label><input type="url" class="form-control" name="slack_url" value="<?php echo htmlspecialchars($g->slack_url ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
            <div class="col-md-4"><label class="form-label"><?php echo __('Discord'); ?></label><input type="url" class="form-control" name="discord_url" value="<?php echo htmlspecialchars($g->discord_url ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
          </div>
        </div>
      </div>

      <!-- Meetings -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-calendar me-2 text-success"></i><?php echo __('Meetings'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label"><?php echo __('Frequency'); ?></label>
              <select class="form-select" name="meeting_frequency">
                <option value=""><?php echo __('-- N/A --'); ?></option>
                <?php $freqs = ['weekly' => __('Weekly'), 'biweekly' => __('Bi-weekly'), 'monthly' => __('Monthly'), 'quarterly' => __('Quarterly'), 'biannual' => __('Bi-annual'), 'annual' => __('Annual'), 'adhoc' => __('Ad hoc')]; $selFreq = $g->meeting_frequency ?? '';
                  foreach ($freqs as $val => $label): ?>
                    <option value="<?php echo $val; ?>"<?php echo $selFreq === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label"><?php echo __('Format'); ?></label>
              <select class="form-select" name="meeting_format">
                <option value=""><?php echo __('-- Select --'); ?></option>
                <?php $formats = ['in_person' => __('In Person'), 'online' => __('Online'), 'hybrid' => __('Hybrid')]; $selFmt = $g->meeting_format ?? '';
                  foreach ($formats as $val => $label): ?>
                    <option value="<?php echo $val; ?>"<?php echo $selFmt === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label"><?php echo __('Platform'); ?></label>
              <input type="text" class="form-control" name="meeting_platform" value="<?php echo htmlspecialchars($g->meeting_platform ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label"><?php echo __('Next Meeting Date/Time'); ?></label>
              <input type="datetime-local" class="form-control" name="next_meeting_at" value="<?php echo !empty($g->next_meeting_at) ? date('Y-m-d\TH:i', strtotime($g->next_meeting_at)) : ''; ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label"><?php echo __('Next Meeting Details'); ?></label>
              <input type="text" class="form-control" name="next_meeting_details" value="<?php echo htmlspecialchars($g->next_meeting_details ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('Agenda, location, or meeting link...'); ?>">
            </div>
          </div>
        </div>
      </div>

      <!-- Focus areas -->
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-tag me-2 text-warning"></i><?php echo __('Focus Areas'); ?></div>
        <div class="card-body">
          <?php
            $focusVal = '';
            if (!empty($g->focus_areas)) {
              $rawFocusAreas = sfOutputEscaper::unescape($g->focus_areas);
              $decoded = is_string($rawFocusAreas) ? json_decode($rawFocusAreas, true) : (array) $rawFocusAreas;
              $focusVal = is_array($decoded) ? implode(', ', $decoded) : ($g->focus_areas ?? '');
            }
          ?>
          <input type="text" class="form-control" name="focus_areas" value="<?php echo htmlspecialchars($focusVal, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('Comma-separated tags'); ?>">
        </div>
      </div>

      <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myGroups']); ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> <?php echo __('Save Changes'); ?></button>
      </div>

    </form>

  </div>
</div>

<?php end_slot(); ?>
