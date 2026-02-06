<?php echo get_component('default', 'updateCheck') ?>

<div class="container-fluid py-4">
  <div class="row">
    <div class="col-12">
      <?php if ($isAdmin): ?>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for('tenant_admin') ?>">Tenant Administration</a></li>
            <li class="breadcrumb-item active"><?php echo esc_specialchars($repository->name ?: 'Repository ' . $repository->id) ?></li>
          </ol>
        </nav>
      <?php endif; ?>

      <h1 class="mb-4">
        <i class="fas fa-palette me-2"></i>
        Branding: <?php echo esc_specialchars($repository->name ?: $repository->identifier) ?>
      </h1>

      <?php if ($sf_user->hasFlash('notice')): ?>
        <div class="alert alert-success alert-dismissible fade show">
          <?php echo $sf_user->getFlash('notice') ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if ($sf_user->hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
          <?php echo $sf_user->getFlash('error') ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <div class="row">
        <!-- Logo Upload -->
        <div class="col-md-4 mb-4">
          <div class="card h-100">
            <div class="card-header bg-secondary text-white">
              <h5 class="mb-0">
                <i class="fas fa-image me-2"></i>
                Logo
              </h5>
            </div>
            <div class="card-body text-center">
              <?php if (!empty($branding['logo'])): ?>
                <div class="mb-3">
                  <img src="<?php echo $branding['logo'] ?>" alt="Logo" class="img-fluid" style="max-height: 150px;">
                </div>
                <form action="<?php echo url_for('tenant_branding_logo_upload') ?>?delete=1" method="post" class="d-inline">
                  <input type="hidden" name="repository_id" value="<?php echo $repository->id ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete the logo?')">
                    <i class="fas fa-trash me-1"></i> Delete Logo
                  </button>
                </form>
              <?php else: ?>
                <div class="text-muted mb-3">
                  <i class="fas fa-image fa-4x mb-2"></i>
                  <p>No logo uploaded.</p>
                </div>
              <?php endif; ?>

              <hr>

              <form action="<?php echo url_for('tenant_branding_logo_upload') ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="repository_id" value="<?php echo $repository->id ?>">
                <div class="mb-3">
                  <label class="form-label">Upload Logo</label>
                  <input type="file" name="logo" class="form-control" accept="image/*" required>
                  <small class="text-muted">PNG, JPG, GIF, SVG, WebP. Max 2MB.</small>
                </div>
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-upload me-1"></i> Upload
                </button>
              </form>
            </div>
          </div>
        </div>

        <!-- Color Settings -->
        <div class="col-md-8 mb-4">
          <div class="card h-100">
            <div class="card-header bg-primary text-white">
              <h5 class="mb-0">
                <i class="fas fa-brush me-2"></i>
                Colors & Styles
              </h5>
            </div>
            <div class="card-body">
              <form action="<?php echo url_for('tenant_branding_save') ?>" method="post">
                <input type="hidden" name="repository_id" value="<?php echo $repository->id ?>">

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label">Primary Color</label>
                    <div class="input-group">
                      <input type="color" class="form-control form-control-color" name="primary_color_picker" value="<?php echo $branding['primary_color'] ?? '#336699' ?>" onchange="document.getElementById('primary_color').value = this.value">
                      <input type="text" class="form-control" id="primary_color" name="primary_color" value="<?php echo $branding['primary_color'] ?? '' ?>" placeholder="#336699" pattern="^#[0-9A-Fa-f]{6}$">
                    </div>
                    <small class="text-muted">Main brand color for buttons and links.</small>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Secondary Color</label>
                    <div class="input-group">
                      <input type="color" class="form-control form-control-color" name="secondary_color_picker" value="<?php echo $branding['secondary_color'] ?? '#6c757d' ?>" onchange="document.getElementById('secondary_color').value = this.value">
                      <input type="text" class="form-control" id="secondary_color" name="secondary_color" value="<?php echo $branding['secondary_color'] ?? '' ?>" placeholder="#6c757d" pattern="^#[0-9A-Fa-f]{6}$">
                    </div>
                    <small class="text-muted">Secondary brand color.</small>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Header Background</label>
                    <div class="input-group">
                      <input type="color" class="form-control form-control-color" name="header_bg_color_picker" value="<?php echo $branding['header_bg_color'] ?? '#212529' ?>" onchange="document.getElementById('header_bg_color').value = this.value">
                      <input type="text" class="form-control" id="header_bg_color" name="header_bg_color" value="<?php echo $branding['header_bg_color'] ?? '' ?>" placeholder="#212529" pattern="^#[0-9A-Fa-f]{6}$">
                    </div>
                    <small class="text-muted">Navigation bar background color.</small>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Header Text Color</label>
                    <div class="input-group">
                      <input type="color" class="form-control form-control-color" name="header_text_color_picker" value="<?php echo $branding['header_text_color'] ?? '#ffffff' ?>" onchange="document.getElementById('header_text_color').value = this.value">
                      <input type="text" class="form-control" id="header_text_color" name="header_text_color" value="<?php echo $branding['header_text_color'] ?? '' ?>" placeholder="#ffffff" pattern="^#[0-9A-Fa-f]{6}$">
                    </div>
                    <small class="text-muted">Navigation bar text and link color.</small>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Link Color</label>
                    <div class="input-group">
                      <input type="color" class="form-control form-control-color" name="link_color_picker" value="<?php echo $branding['link_color'] ?? '#0d6efd' ?>" onchange="document.getElementById('link_color').value = this.value">
                      <input type="text" class="form-control" id="link_color" name="link_color" value="<?php echo $branding['link_color'] ?? '' ?>" placeholder="#0d6efd" pattern="^#[0-9A-Fa-f]{6}$">
                    </div>
                    <small class="text-muted">Color for text links.</small>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label">Button Color</label>
                    <div class="input-group">
                      <input type="color" class="form-control form-control-color" name="button_color_picker" value="<?php echo $branding['button_color'] ?? '#198754' ?>" onchange="document.getElementById('button_color').value = this.value">
                      <input type="text" class="form-control" id="button_color" name="button_color" value="<?php echo $branding['button_color'] ?? '' ?>" placeholder="#198754" pattern="^#[0-9A-Fa-f]{6}$">
                    </div>
                    <small class="text-muted">Color for action buttons.</small>
                  </div>
                </div>

                <hr>

                <div class="mb-3">
                  <label class="form-label">Custom CSS</label>
                  <textarea name="custom_css" class="form-control font-monospace" rows="6" placeholder=".custom-class { color: #333; }"><?php echo esc_specialchars($branding['custom_css'] ?? '') ?></textarea>
                  <small class="text-muted">Advanced: Add custom CSS rules. Maximum 10,000 characters.</small>
                </div>

                <div class="d-flex justify-content-between">
                  <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Save Branding
                  </button>
                  <button type="reset" class="btn btn-outline-secondary">
                    <i class="fas fa-undo me-1"></i> Reset
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- Preview -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">
            <i class="fas fa-eye me-2"></i>
            Preview
          </h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <h6>Buttons</h6>
              <button class="btn btn-primary me-2" style="background-color: <?php echo $branding['primary_color'] ?? '#336699' ?>; border-color: <?php echo $branding['primary_color'] ?? '#336699' ?>;">Primary Button</button>
              <button class="btn btn-secondary me-2" style="background-color: <?php echo $branding['secondary_color'] ?? '#6c757d' ?>; border-color: <?php echo $branding['secondary_color'] ?? '#6c757d' ?>;">Secondary</button>
              <button class="btn" style="background-color: <?php echo $branding['button_color'] ?? '#198754' ?>; border-color: <?php echo $branding['button_color'] ?? '#198754' ?>; color: white;">Action</button>
            </div>
            <div class="col-md-6">
              <h6>Links</h6>
              <p>This is a <a href="#" style="color: <?php echo $branding['link_color'] ?? '#0d6efd' ?>;">sample link</a> with your configured color.</p>
            </div>
          </div>
          <hr>
          <h6>Header Preview</h6>
          <nav class="navbar navbar-dark p-2 rounded" style="background-color: <?php echo $branding['header_bg_color'] ?? '#212529' ?>;">
            <span class="navbar-brand mb-0 h1" style="color: <?php echo $branding['header_text_color'] ?? '#ffffff' ?>;">
              <?php if (!empty($branding['logo'])): ?>
                <img src="<?php echo $branding['logo'] ?>" alt="Logo" style="height: 30px; margin-right: 10px;">
              <?php endif; ?>
              <?php echo esc_specialchars($repository->name ?: 'Repository Name') ?>
            </span>
            <span style="color: <?php echo $branding['header_text_color'] ?? '#ffffff' ?>;">
              <a href="#" style="color: <?php echo $branding['header_text_color'] ?? '#ffffff' ?>; text-decoration: none; margin-right: 15px;">Home</a>
              <a href="#" style="color: <?php echo $branding['header_text_color'] ?? '#ffffff' ?>; text-decoration: none; margin-right: 15px;">Browse</a>
              <a href="#" style="color: <?php echo $branding['header_text_color'] ?? '#ffffff' ?>; text-decoration: none;">About</a>
            </span>
          </nav>
        </div>
      </div>

      <div class="mt-2">
        <a href="<?php echo url_for('tenant_users', ['id' => $repository->id]) ?>" class="btn btn-outline-secondary">
          <i class="fas fa-users me-1"></i> Manage Users
        </a>
        <?php if ($isAdmin): ?>
          <a href="<?php echo url_for('tenant_admin') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Tenant Administration
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
// Sync color pickers with text inputs
document.querySelectorAll('input[type="color"]').forEach(function(picker) {
  var textInput = document.getElementById(picker.name.replace('_picker', ''));
  if (textInput && textInput.value) {
    picker.value = textInput.value;
  }
});
</script>
