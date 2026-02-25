<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Edit Group'); ?> - <?php echo __('Admin'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('Groups'), 'url' => url_for(['module' => 'registry', 'action' => 'adminGroups'])],
  ['label' => __('Edit')],
]]); ?>

<?php $g = $group; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo __('Edit Group'); ?>: <?php echo htmlspecialchars($g->name, ENT_QUOTES, 'UTF-8'); ?></h1>
  <div class="btn-group btn-group-sm">
    <a href="/registry/admin/groups/<?php echo (int) $g->id; ?>/members" class="btn btn-outline-primary">
      <i class="fas fa-users me-1"></i><?php echo __('Members'); ?>
      <span class="badge bg-primary ms-1"><?php echo (int) ($g->member_count ?? 0); ?></span>
    </a>
    <a href="/registry/groups/<?php echo htmlspecialchars($g->slug, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary" target="_blank">
      <i class="fas fa-external-link-alt me-1"></i><?php echo __('View'); ?>
    </a>
  </div>
</div>

<?php if (!empty($success)): ?>
  <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check me-1"></i><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<?php if (isset($errors) && count($errors) > 0): ?>
  <div class="alert alert-danger">
    <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<form method="post" action="/registry/admin/groups/<?php echo (int) $g->id; ?>/edit">
<div class="row">
  <div class="col-lg-8">

    <!-- Basic info -->
    <div class="card mb-4">
      <div class="card-header fw-semibold"><i class="fas fa-users me-2 text-primary"></i><?php echo __('Group Information'); ?></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label"><?php echo __('Group Name'); ?> <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($g->name ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label"><?php echo __('Group Type'); ?></label>
            <select class="form-select" name="group_type">
              <?php $gTypes = ['regional' => 'Regional', 'topic' => 'Topic / Interest', 'software' => 'Software / Technical', 'institutional' => 'Institutional', 'other' => 'Other']; $selType = $g->group_type ?? 'regional';
                foreach ($gTypes as $val => $label): ?>
                  <option value="<?php echo $val; ?>"<?php echo $selType === $val ? ' selected' : ''; ?>><?php echo __($label); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label"><?php echo __('Description'); ?></label>
            <textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($g->description ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- Organizer -->
    <div class="card mb-4">
      <div class="card-header fw-semibold"><i class="fas fa-user-tie me-2 text-success"></i><?php echo __('Organizer'); ?></div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label"><?php echo __('Name'); ?></label><input type="text" class="form-control" name="organizer_name" value="<?php echo htmlspecialchars($g->organizer_name ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
          <div class="col-md-6"><label class="form-label"><?php echo __('Email'); ?></label><input type="email" class="form-control" name="organizer_email" value="<?php echo htmlspecialchars($g->organizer_email ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
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
              <input class="form-check-input" type="checkbox" name="is_virtual" value="1"<?php echo !empty($g->is_virtual) ? ' checked' : ''; ?>>
              <label class="form-check-label"><?php echo __('Virtual / Online-only group'); ?></label>
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
          <div class="col-md-4"><label class="form-label"><?php echo __('Mailing List URL'); ?></label><input type="url" class="form-control" name="mailing_list_url" value="<?php echo htmlspecialchars($g->mailing_list_url ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
          <div class="col-md-4"><label class="form-label"><?php echo __('Slack URL'); ?></label><input type="url" class="form-control" name="slack_url" value="<?php echo htmlspecialchars($g->slack_url ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
          <div class="col-md-4"><label class="form-label"><?php echo __('Discord URL'); ?></label><input type="url" class="form-control" name="discord_url" value="<?php echo htmlspecialchars($g->discord_url ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
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
              <?php $freqs = ['weekly' => 'Weekly', 'biweekly' => 'Bi-weekly', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'annual' => 'Annual', 'adhoc' => 'Ad hoc']; $selFreq = $g->meeting_frequency ?? '';
                foreach ($freqs as $val => $label): ?>
                  <option value="<?php echo $val; ?>"<?php echo $selFreq === $val ? ' selected' : ''; ?>><?php echo __($label); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label"><?php echo __('Format'); ?></label>
            <select class="form-select" name="meeting_format">
              <option value=""><?php echo __('-- Select --'); ?></option>
              <?php $fmts = ['in_person' => 'In Person', 'virtual' => 'Virtual', 'hybrid' => 'Hybrid']; $selFmt = $g->meeting_format ?? '';
                foreach ($fmts as $val => $label): ?>
                  <option value="<?php echo $val; ?>"<?php echo $selFmt === $val ? ' selected' : ''; ?>><?php echo __($label); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4"><label class="form-label"><?php echo __('Platform'); ?></label><input type="text" class="form-control" name="meeting_platform" value="<?php echo htmlspecialchars($g->meeting_platform ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
          <div class="col-md-6"><label class="form-label"><?php echo __('Next Meeting'); ?></label><input type="datetime-local" class="form-control" name="next_meeting_at" value="<?php echo !empty($g->next_meeting_at) ? date('Y-m-d\TH:i', strtotime($g->next_meeting_at)) : ''; ?>"></div>
          <div class="col-md-6"><label class="form-label"><?php echo __('Details'); ?></label><input type="text" class="form-control" name="next_meeting_details" value="<?php echo htmlspecialchars($g->next_meeting_details ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
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
            $raw = sfOutputEscaper::unescape($g->focus_areas);
            if (!is_string($raw)) { $raw = (string) $raw; }
            $decoded = json_decode($raw, true);
            $focusVal = is_array($decoded) ? implode(', ', $decoded) : $raw;
          }
        ?>
        <input type="text" class="form-control" name="focus_areas" value="<?php echo htmlspecialchars($focusVal, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('Comma-separated, e.g.: atom, preservation, digitization'); ?>">
      </div>
    </div>

  </div>

  <!-- Sidebar: Admin controls -->
  <div class="col-lg-4">

    <div class="card mb-4 border-primary">
      <div class="card-header fw-semibold bg-primary text-white"><i class="fas fa-shield-alt me-2"></i><?php echo __('Admin Controls'); ?></div>
      <div class="card-body">

        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" name="is_active" value="1" id="ag-active"<?php echo (!isset($g->is_active) || $g->is_active) ? ' checked' : ''; ?>>
          <label class="form-check-label fw-semibold" for="ag-active"><?php echo __('Active'); ?></label>
          <div class="form-text"><?php echo __('Inactive groups are hidden from public listings.'); ?></div>
        </div>

        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" name="is_verified" value="1" id="ag-verified"<?php echo !empty($g->is_verified) ? ' checked' : ''; ?>>
          <label class="form-check-label fw-semibold" for="ag-verified"><?php echo __('Verified'); ?></label>
          <div class="form-text"><?php echo __('Shows verified badge on public profile.'); ?></div>
        </div>

        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="ag-featured"<?php echo !empty($g->is_featured) ? ' checked' : ''; ?>>
          <label class="form-check-label fw-semibold" for="ag-featured"><?php echo __('Featured'); ?></label>
          <div class="form-text"><?php echo __('Featured groups appear on the homepage.'); ?></div>
        </div>

        <hr>

        <dl class="small mb-0">
          <dt><?php echo __('Members'); ?></dt>
          <dd><?php echo (int) ($g->member_count ?? 0); ?></dd>
          <dt><?php echo __('Created'); ?></dt>
          <dd><?php echo date('j M Y', strtotime($g->created_at)); ?></dd>
          <dt><?php echo __('Updated'); ?></dt>
          <dd><?php echo date('j M Y H:i', strtotime($g->updated_at)); ?></dd>
          <dt><?php echo __('Slug'); ?></dt>
          <dd><code><?php echo htmlspecialchars($g->slug, ENT_QUOTES, 'UTF-8'); ?></code></dd>
        </dl>
      </div>
    </div>

    <div class="d-grid gap-2">
      <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> <?php echo __('Save Changes'); ?></button>
      <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminGroups']); ?>" class="btn btn-outline-secondary"><?php echo __('Back to Groups'); ?></a>
    </div>

  </div>
</div>
</form>

<?php end_slot(); ?>
