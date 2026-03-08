<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php
  $isNew = empty($erd);
  $e = $isNew ? (object) [
    'id' => 0, 'plugin_name' => '', 'display_name' => '', 'category' => 'general',
    'description' => '', 'tables_json' => '[]', 'diagram' => '', 'notes' => '',
    'icon' => 'fas fa-database', 'color' => 'primary', 'sort_order' => 100, 'is_active' => 1,
  ] : $erd;
?>

<?php slot('title'); ?><?php echo $isNew ? __('Add ERD Entry') : __('Edit ERD Entry'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('ERD Documentation'), 'url' => url_for(['module' => 'registry', 'action' => 'adminErd'])],
  ['label' => $isNew ? __('Add') : __('Edit')],
]]); ?>

<h1 class="h3 mb-4"><?php echo $isNew ? __('Add ERD Entry') : __('Edit ERD Entry'); ?></h1>

<form method="post" enctype="multipart/form-data" action="<?php echo $isNew
  ? url_for(['module' => 'registry', 'action' => 'adminErdEdit'])
  : url_for(['module' => 'registry', 'action' => 'adminErdEdit', 'id' => $e->id]); ?>">

  <div class="row g-4">

    <!-- Left column -->
    <div class="col-lg-8">
      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Basic Information'); ?></h5></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label"><?php echo __('Plugin Name'); ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="plugin_name" value="<?php echo htmlspecialchars($e->plugin_name, ENT_QUOTES, 'UTF-8'); ?>" required placeholder="ahgExamplePlugin">
            </div>
            <div class="col-md-6">
              <label class="form-label"><?php echo __('Display Name'); ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="display_name" value="<?php echo htmlspecialchars($e->display_name, ENT_QUOTES, 'UTF-8'); ?>" required placeholder="Example Feature">
            </div>
            <div class="col-md-4">
              <label class="form-label"><?php echo __('Vendor'); ?></label>
              <?php
                $_vendors = \Illuminate\Database\Capsule\Manager::table('registry_vendor')
                    ->where('is_active', 1)->orderBy('name')->get()->all();
              ?>
              <select class="form-select" name="vendor_id">
                <option value=""><?php echo __('— None —'); ?></option>
                <?php foreach ($_vendors as $_v): ?>
                <option value="<?php echo (int) $_v->id; ?>" <?php echo ((int) ($e->vendor_id ?? 0) === (int) $_v->id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($_v->name, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label"><?php echo __('Category'); ?></label>
              <select class="form-select" name="form_category">
                <?php foreach (['core','sector','compliance','collection','rights','research','ai','ingest','integration','exhibition','reporting'] as $c): ?>
                <option value="<?php echo $c; ?>" <?php echo ($e->category === $c) ? 'selected' : ''; ?>><?php echo ucfirst($c); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label"><?php echo __('Icon'); ?></label>
              <?php
                $_currentIcon = $e->icon ?? 'fas fa-database';
                $_icons = [
                  'fas fa-database' => 'Database',
                  'fas fa-table' => 'Table',
                  'fas fa-project-diagram' => 'Diagram',
                  'fas fa-cube' => 'Cube',
                  'fas fa-cubes' => 'Cubes',
                  'fas fa-cog' => 'Settings',
                  'fas fa-cogs' => 'Settings (multi)',
                  'fas fa-plug' => 'Plugin',
                  'fas fa-puzzle-piece' => 'Puzzle',
                  'fas fa-layer-group' => 'Layers',
                  'fas fa-sitemap' => 'Sitemap',
                  'fas fa-archive' => 'Archive',
                  'fas fa-balance-scale' => 'Balance/Standards',
                  'fas fa-shield-alt' => 'Shield/Security',
                  'fas fa-user-shield' => 'User Shield',
                  'fas fa-lock' => 'Lock',
                  'fas fa-key' => 'Key',
                  'fas fa-gavel' => 'Gavel/Rights',
                  'fas fa-feather-alt' => 'Feather',
                  'fas fa-history' => 'History/Audit',
                  'fas fa-clipboard-check' => 'Clipboard Check',
                  'fas fa-clipboard-list' => 'Clipboard List',
                  'fas fa-check-circle' => 'Check Circle',
                  'fas fa-tasks' => 'Tasks',
                  'fas fa-th-list' => 'List',
                  'fas fa-book' => 'Book',
                  'fas fa-book-open' => 'Book Open',
                  'fas fa-landmark' => 'Landmark/Museum',
                  'fas fa-university' => 'University',
                  'fas fa-building' => 'Building',
                  'fas fa-images' => 'Images/IIIF',
                  'fas fa-photo-video' => 'Photo/Video',
                  'fas fa-paint-brush' => 'Paint Brush',
                  'fas fa-palette' => 'Palette',
                  'fas fa-camera' => 'Camera',
                  'fas fa-microscope' => 'Microscope',
                  'fas fa-brain' => 'Brain/AI',
                  'fas fa-robot' => 'Robot',
                  'fas fa-magic' => 'Magic',
                  'fas fa-file-import' => 'File Import',
                  'fas fa-file-export' => 'File Export',
                  'fas fa-file-contract' => 'File Contract',
                  'fas fa-file-alt' => 'File',
                  'fas fa-folder-open' => 'Folder Open',
                  'fas fa-exchange-alt' => 'Exchange/Loan',
                  'fas fa-boxes' => 'Boxes',
                  'fas fa-box-open' => 'Box Open',
                  'fas fa-route' => 'Route/Provenance',
                  'fas fa-address-book' => 'Address Book',
                  'fas fa-users' => 'Users',
                  'fas fa-user-cog' => 'User Settings',
                  'fas fa-fingerprint' => 'Fingerprint/DOI',
                  'fas fa-calculator' => 'Calculator',
                  'fas fa-chart-bar' => 'Chart Bar',
                  'fas fa-chart-pie' => 'Chart Pie',
                  'fas fa-chart-line' => 'Chart Line',
                  'fas fa-flag' => 'Flag',
                  'fas fa-globe' => 'Globe',
                  'fas fa-globe-africa' => 'Globe Africa',
                  'fas fa-map-marked-alt' => 'Map',
                  'fas fa-comment-dots' => 'Comment',
                  'fas fa-envelope' => 'Envelope',
                  'fas fa-newspaper' => 'Newspaper',
                  'fas fa-star' => 'Star',
                  'fas fa-tag' => 'Tag',
                  'fas fa-tags' => 'Tags',
                  'fas fa-search' => 'Search',
                  'fas fa-sync-alt' => 'Sync',
                  'fas fa-server' => 'Server',
                  'fas fa-code' => 'Code',
                  'fas fa-terminal' => 'Terminal',
                  'fas fa-wrench' => 'Wrench',
                  'fas fa-tools' => 'Tools',
                  'fas fa-hammer' => 'Hammer',
                  'fas fa-handshake' => 'Handshake',
                  'fas fa-shopping-cart' => 'Cart',
                  'fas fa-truck' => 'Truck',
                  'fas fa-barcode' => 'Barcode',
                  'fas fa-qrcode' => 'QR Code',
                ];
              ?>
              <input type="hidden" name="icon" id="iconInput" value="<?php echo htmlspecialchars($_currentIcon, ENT_QUOTES, 'UTF-8'); ?>">
              <div class="dropdown">
                <button class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-between" type="button" id="iconDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                  <span>
                    <i class="<?php echo htmlspecialchars($_currentIcon, ENT_QUOTES, 'UTF-8'); ?> me-2" id="iconPreview"></i>
                    <span id="iconLabel"><?php echo htmlspecialchars($_icons[$_currentIcon] ?? $_currentIcon, ENT_QUOTES, 'UTF-8'); ?></span>
                  </span>
                  <i class="fas fa-chevron-down ms-2 small"></i>
                </button>
                <div class="dropdown-menu p-2" style="width: 380px; max-height: 350px; overflow-y: auto;">
                  <input type="text" class="form-control form-control-sm mb-2" id="iconSearch" placeholder="<?php echo __('Search icons...'); ?>">
                  <div class="row row-cols-4 g-1" id="iconGrid">
                    <?php foreach ($_icons as $cls => $lbl): ?>
                    <div class="col icon-option" data-icon="<?php echo $cls; ?>" data-label="<?php echo htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8'); ?>">
                      <button type="button" class="btn btn-sm w-100 py-2 <?php echo ($cls === $_currentIcon) ? 'btn-primary' : 'btn-outline-secondary'; ?>" title="<?php echo htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8'); ?>" onclick="selectIcon('<?php echo $cls; ?>', '<?php echo htmlspecialchars($lbl, ENT_QUOTES, 'UTF-8'); ?>', this)">
                        <i class="<?php echo $cls; ?> fa-lg"></i>
                      </button>
                    </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label"><?php echo __('Color'); ?></label>
              <?php
                $_currentColor = $e->color ?? 'primary';
                $_colors = [
                  'primary'   => ['Primary',   '#0d6efd'],
                  'secondary' => ['Secondary', '#6c757d'],
                  'success'   => ['Success',   '#198754'],
                  'danger'    => ['Danger',    '#dc3545'],
                  'warning'   => ['Warning',   '#ffc107'],
                  'info'      => ['Info',      '#0dcaf0'],
                  'dark'      => ['Dark',      '#212529'],
                  'purple'    => ['Purple',    '#6f42c1'],
                ];
              ?>
              <input type="hidden" name="color" id="colorInput" value="<?php echo htmlspecialchars($_currentColor, ENT_QUOTES, 'UTF-8'); ?>">
              <div class="dropdown">
                <button class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-between" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <span>
                    <span class="d-inline-block rounded-circle me-2" id="colorSwatch" style="width:14px;height:14px;background:<?php echo $_colors[$_currentColor][1] ?? '#0d6efd'; ?>;vertical-align:middle;"></span>
                    <span id="colorLabel"><?php echo $_colors[$_currentColor][0] ?? ucfirst($_currentColor); ?></span>
                  </span>
                  <i class="fas fa-chevron-down ms-2 small"></i>
                </button>
                <div class="dropdown-menu p-2" style="min-width: 200px;">
                  <?php foreach ($_colors as $cVal => $cMeta): ?>
                  <button type="button" class="dropdown-item d-flex align-items-center py-2 <?php echo ($cVal === $_currentColor) ? 'active' : ''; ?>"
                          onclick="selectColor('<?php echo $cVal; ?>', '<?php echo $cMeta[0]; ?>', '<?php echo $cMeta[1]; ?>', this)">
                    <span class="d-inline-block rounded-circle me-2 flex-shrink-0" style="width:16px;height:16px;background:<?php echo $cMeta[1]; ?>;border:1px solid rgba(0,0,0,.15);"></span>
                    <?php echo $cMeta[0]; ?>
                  </button>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label"><?php echo __('Description'); ?></label>
              <textarea class="form-control" name="description" rows="2"><?php echo htmlspecialchars($e->description ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Tables (JSON Array)'); ?></h5></div>
        <div class="card-body">
          <textarea class="form-control font-monospace" name="tables_json" rows="4" placeholder='["table_one","table_two"]'><?php echo htmlspecialchars($e->tables_json ?? '[]', ENT_QUOTES, 'UTF-8'); ?></textarea>
          <div class="form-text"><?php echo __('JSON array of database table names. Schemas are rendered live from information_schema.'); ?></div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><?php echo __('ERD Diagram Image / Document'); ?></h5></div>
        <div class="card-body">
          <?php if (!empty($e->diagram_image)): ?>
          <div class="mb-3 p-3 bg-light rounded">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <strong class="small"><?php echo __('Current file:'); ?></strong>
              <input type="hidden" name="remove_diagram_image" id="remove_diagram_image" value="0">
              <button type="button" class="btn btn-outline-danger btn-sm" id="btnRemoveDiagram" onclick="toggleRemoveDiagram()">
                <i class="fas fa-trash-alt me-1"></i><?php echo __('Remove'); ?>
              </button>
            </div>
            <?php
              $imgPath = $e->diagram_image;
              $ext = strtolower(pathinfo($imgPath, PATHINFO_EXTENSION));
              $isImage = in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg']);
            ?>
            <div id="diagramPreview">
            <?php if ($isImage): ?>
              <img src="<?php echo htmlspecialchars($imgPath, ENT_QUOTES, 'UTF-8'); ?>" alt="ERD Diagram" class="img-fluid rounded border" style="max-height: 300px;">
            <?php else: ?>
              <a href="<?php echo htmlspecialchars($imgPath, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-file-pdf me-1"></i><?php echo basename($imgPath); ?>
              </a>
            <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
          <input type="file" class="form-control" name="diagram_image" accept="image/*,.pdf,.svg">
          <div class="form-text"><?php echo __('Upload an ERD diagram image (PNG, JPG, SVG, WebP) or PDF document. Complements or replaces the ASCII diagram below.'); ?></div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><?php echo __('ASCII ERD Diagram'); ?></h5></div>
        <div class="card-body">
          <textarea class="form-control font-monospace" name="diagram" rows="15" style="font-size: 0.8em;" placeholder="Paste ASCII diagram here..."><?php echo htmlspecialchars($e->diagram ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
          <div class="form-text"><?php echo __('Optional ASCII art ERD diagram. Displayed in a &lt;pre&gt; block.'); ?></div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Additional Notes'); ?></h5></div>
        <div class="card-body">
          <textarea class="form-control" name="notes" rows="4"><?php echo htmlspecialchars($e->notes ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
      </div>
    </div>

    <!-- Right column -->
    <div class="col-lg-4">
      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Settings'); ?></h5></div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Sort Order'); ?></label>
            <input type="number" class="form-control" name="sort_order" value="<?php echo (int) ($e->sort_order ?? 100); ?>">
          </div>
          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" <?php echo ($e->is_active ?? 1) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="is_active"><?php echo __('Active'); ?></label>
          </div>
        </div>
      </div>

      <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save me-1"></i><?php echo $isNew ? __('Create') : __('Save Changes'); ?>
        </button>
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminErd']); ?>" class="btn btn-outline-secondary">
          <?php echo __('Cancel'); ?>
        </a>
      </div>
    </div>

  </div>
</form>

<?php $n = sfConfig::get('csp_nonce', ''); $na = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>
<script <?php echo $na; ?>>
function selectColor(val, label, hex, btn) {
  document.getElementById('colorInput').value = val;
  document.getElementById('colorSwatch').style.background = hex;
  document.getElementById('colorLabel').textContent = label;
  btn.closest('.dropdown-menu').querySelectorAll('.dropdown-item').forEach(function(b) {
    b.classList.remove('active');
  });
  btn.classList.add('active');
}
function selectIcon(cls, label, btn) {
  document.getElementById('iconInput').value = cls;
  document.getElementById('iconPreview').className = cls + ' me-2';
  document.getElementById('iconLabel').textContent = label;
  document.querySelectorAll('#iconGrid button').forEach(function(b) {
    b.className = b.className.replace('btn-primary', 'btn-outline-secondary');
  });
  btn.className = btn.className.replace('btn-outline-secondary', 'btn-primary');
}
document.getElementById('iconSearch').addEventListener('input', function() {
  var q = this.value.toLowerCase();
  document.querySelectorAll('.icon-option').forEach(function(el) {
    var label = (el.getAttribute('data-label') || '').toLowerCase();
    var icon = (el.getAttribute('data-icon') || '').toLowerCase();
    el.style.display = (label.indexOf(q) !== -1 || icon.indexOf(q) !== -1) ? '' : 'none';
  });
});
document.querySelector('.dropdown-menu').addEventListener('click', function(e) {
  if (e.target.id === 'iconSearch' || e.target.tagName === 'INPUT') {
    e.stopPropagation();
  }
});
function toggleRemoveDiagram() {
  var inp = document.getElementById('remove_diagram_image');
  var btn = document.getElementById('btnRemoveDiagram');
  var preview = document.getElementById('diagramPreview');
  if (inp.value === '0') {
    inp.value = '1';
    btn.classList.remove('btn-outline-danger');
    btn.classList.add('btn-danger');
    btn.innerHTML = '<i class="fas fa-undo me-1"></i><?php echo __("Undo Remove"); ?>';
    if (preview) { preview.style.opacity = '0.3'; preview.style.textDecoration = 'line-through'; }
  } else {
    inp.value = '0';
    btn.classList.remove('btn-danger');
    btn.classList.add('btn-outline-danger');
    btn.innerHTML = '<i class="fas fa-trash-alt me-1"></i><?php echo __("Remove"); ?>';
    if (preview) { preview.style.opacity = '1'; preview.style.textDecoration = 'none'; }
  }
}
</script>

<?php end_slot(); ?>
