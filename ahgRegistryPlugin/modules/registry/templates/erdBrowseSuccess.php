<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Schema & ERD Documentation'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Schema & ERD')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1"><i class="fas fa-project-diagram me-2"></i><?php echo __('Schema & ERD Documentation'); ?></h1>
    <p class="text-muted mb-0"><?php echo __('Database schemas, entity relationships, and field definitions for all Heratio plugins.'); ?></p>
  </div>
  <?php if (!empty($isAdmin)): ?>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminErd']); ?>" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-cog me-1"></i><?php echo __('Manage'); ?>
  </a>
  <?php endif; ?>
</div>

<?php
  $rawCategories = sfOutputEscaper::unescape($categories);
  if (!is_array($rawCategories)) { $rawCategories = []; }
  $catLabels = [
    'core' => ['Core', 'primary', 'fas fa-cube'],
    'sector' => ['GLAM Sectors', 'info', 'fas fa-layer-group'],
    'compliance' => ['Compliance & Accounting', 'danger', 'fas fa-shield-alt'],
    'collection' => ['Collection Management', 'success', 'fas fa-boxes'],
    'rights' => ['Rights Management', 'warning', 'fas fa-gavel'],
    'research' => ['Research & Public Access', 'secondary', 'fas fa-microscope'],
    'ai' => ['AI & Automation', 'purple', 'fas fa-brain'],
    'ingest' => ['Data Ingest', 'warning', 'fas fa-file-import'],
    'integration' => ['Integration', 'dark', 'fas fa-plug'],
    'exhibition' => ['Exhibition & Engagement', 'info', 'fas fa-palette'],
    'reporting' => ['Reporting & Admin', 'primary', 'fas fa-chart-bar'],
  ];

  $rawItems = sfOutputEscaper::unescape($items);
  if (!is_array($rawItems)) { $rawItems = []; }

  // Group items by category
  $grouped = [];
  foreach ($rawItems as $erd) {
      $cat = $erd->category ?? 'general';
      $grouped[$cat][] = $erd;
  }

  // Sort groups by catLabels order
  $catOrder = array_keys($catLabels);
  uksort($grouped, function ($a, $b) use ($catOrder) {
      $ia = array_search($a, $catOrder);
      $ib = array_search($b, $catOrder);
      if ($ia === false) { $ia = 999; }
      if ($ib === false) { $ib = 999; }
      return $ia - $ib;
  });
?>

<!-- Filters -->
<?php
  $rawVendors = sfOutputEscaper::unescape($vendors);
  if (!is_array($rawVendors)) { $rawVendors = []; }
  $rawSoftware = isset($softwareFilter) ? sfOutputEscaper::unescape($softwareFilter) : [];
  if (!is_array($rawSoftware)) { $rawSoftware = []; }
  $rawErdSoftware = isset($erdSoftware) ? sfOutputEscaper::unescape($erdSoftware) : [];
  if (!is_array($rawErdSoftware)) { $rawErdSoftware = []; }
  $_selVendor = sfOutputEscaper::unescape($selectedVendor);
  $_selCat = sfOutputEscaper::unescape($selectedCategory);
  $_selSw = isset($selectedSoftware) ? sfOutputEscaper::unescape($selectedSoftware) : '';
  $_baseUrl = url_for(['module' => 'registry', 'action' => 'erdBrowse']);

  $_carry = function ($except) use ($_selVendor, $_selCat, $_selSw) {
      $parts = [];
      if ($except !== 'vendor' && !empty($_selVendor)) { $parts[] = 'vendor=' . urlencode($_selVendor); }
      if ($except !== 'category' && !empty($_selCat)) { $parts[] = 'category=' . urlencode($_selCat); }
      if ($except !== 'software' && !empty($_selSw)) { $parts[] = 'software=' . urlencode($_selSw); }
      return empty($parts) ? '' : '?' . implode('&', $parts);
  };
?>
<div class="card bg-light border-0 mb-4">
  <div class="card-body py-3">

    <!-- Software filter -->
    <?php if (!empty($rawSoftware)): ?>
    <div class="mb-2">
      <span class="small text-muted me-2"><i class="fas fa-cube me-1"></i><?php echo __('Software'); ?>:</span>
      <a href="<?php echo $_baseUrl . $_carry('software'); ?>"
         class="btn btn-sm <?php echo empty($_selSw) ? 'btn-dark' : 'btn-outline-dark'; ?> me-1 mb-1">
        <?php echo __('All'); ?>
      </a>
      <?php foreach ($rawSoftware as $sw): ?>
        <?php
          $carry = $_carry('software');
          $sep = empty($carry) ? '?' : '&';
          $swUrl = $_baseUrl . $carry . $sep . 'software=' . (int) $sw->id;
          $isActive = ((int) $_selSw === (int) $sw->id);
        ?>
        <a href="<?php echo $swUrl; ?>"
           class="btn btn-sm <?php echo $isActive ? 'btn-primary' : 'btn-outline-primary'; ?> me-1 mb-1">
          <?php echo htmlspecialchars($sw->name, ENT_QUOTES, 'UTF-8'); ?>
          <span class="badge bg-light text-dark ms-1"><?php echo (int) $sw->erd_count; ?></span>
        </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Vendor filter -->
    <div class="mb-2">
      <span class="small text-muted me-2"><i class="fas fa-building me-1"></i><?php echo __('Vendor'); ?>:</span>
      <a href="<?php echo $_baseUrl . $_carry('vendor'); ?>"
         class="btn btn-sm <?php echo empty($_selVendor) ? 'btn-dark' : 'btn-outline-dark'; ?> me-1 mb-1">
        <?php echo __('All'); ?>
      </a>
      <?php foreach ($rawVendors as $v): ?>
        <?php
          $carry = $_carry('vendor');
          $sep = empty($carry) ? '?' : '&';
          $vUrl = $_baseUrl . $carry . $sep . 'vendor=' . (int) $v->id;
          $isActive = ((int) $_selVendor === (int) $v->id);
        ?>
        <a href="<?php echo $vUrl; ?>"
           class="btn btn-sm <?php echo $isActive ? 'btn-success' : 'btn-outline-success'; ?> me-1 mb-1">
          <?php echo htmlspecialchars($v->name, ENT_QUOTES, 'UTF-8'); ?>
          <span class="badge bg-light text-dark ms-1"><?php echo (int) $v->erd_count; ?></span>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Category filter -->
    <div>
      <span class="small text-muted me-2"><i class="fas fa-tag me-1"></i><?php echo __('Category'); ?>:</span>
      <a href="<?php echo $_baseUrl . $_carry('category'); ?>"
         class="btn btn-sm <?php echo empty($_selCat) ? 'btn-dark' : 'btn-outline-dark'; ?> me-1 mb-1">
        <?php echo __('All'); ?> <span class="badge bg-light text-dark ms-1"><?php echo count($rawItems); ?></span>
      </a>
      <?php foreach ($rawCategories as $cat): ?>
        <?php
          $cl = $catLabels[$cat] ?? [$cat, 'secondary', 'fas fa-folder'];
          $catCount = count($grouped[$cat] ?? []);
          $carry = $_carry('category');
          $sep = empty($carry) ? '?' : '&';
          $cUrl = $_baseUrl . $carry . $sep . 'category=' . urlencode($cat);
        ?>
        <a href="<?php echo $cUrl; ?>"
           class="btn btn-sm <?php echo ($_selCat === $cat) ? 'btn-' . $cl[1] : 'btn-outline-' . $cl[1]; ?> me-1 mb-1">
          <?php echo htmlspecialchars($cl[0], ENT_QUOTES, 'UTF-8'); ?> <span class="badge bg-light text-dark ms-1"><?php echo $catCount; ?></span>
        </a>
      <?php endforeach; ?>
    </div>

  </div>
</div>

<!-- ERD tiles grouped by category -->
<?php if (empty($rawItems)): ?>
  <div class="alert alert-info"><?php echo __('No ERD entries found.'); ?></div>
<?php else: ?>

  <?php foreach ($grouped as $cat => $erdItems): ?>
  <?php $cl = $catLabels[$cat] ?? [$cat, 'secondary', 'fas fa-folder']; ?>

  <div class="mb-5">
    <div class="d-flex align-items-center mb-3">
      <i class="<?php echo $cl[2]; ?> fa-lg text-<?php echo $cl[1]; ?> me-2"></i>
      <h2 class="h5 mb-0"><?php echo htmlspecialchars($cl[0], ENT_QUOTES, 'UTF-8'); ?></h2>
      <span class="badge bg-<?php echo $cl[1]; ?> ms-2"><?php echo count($erdItems); ?></span>
    </div>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-3">
      <?php foreach ($erdItems as $erd): ?>
      <?php
        $icon = $erd->icon ?? 'fas fa-database';
        $color = $erd->color ?? 'primary';
        $tables = json_decode($erd->tables_json ?? '[]', true);
        $tableCount = is_array($tables) ? count($tables) : 0;
      ?>
      <div class="col">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'erdView', 'slug' => $erd->slug]); ?>" class="card h-100 text-decoration-none border-start border-<?php echo $color; ?> border-4">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <i class="<?php echo htmlspecialchars($icon, ENT_QUOTES, 'UTF-8'); ?> fa-2x text-<?php echo $color; ?> opacity-75"></i>
              <span class="badge bg-light text-dark border"><i class="fas fa-table me-1"></i><?php echo $tableCount; ?></span>
            </div>
            <h6 class="card-title mb-1"><?php echo $erd->display_name; ?></h6>
            <p class="card-text small text-muted mb-2"><?php echo htmlspecialchars(mb_strimwidth($erd->description ?? '', 0, 120, '...'), ENT_QUOTES, 'UTF-8'); ?></p>
            <code class="small text-muted"><?php echo $erd->plugin_name; ?></code>
            <?php if (!empty($rawErdSoftware[(int) $erd->id])): ?>
              <div class="mt-2">
                <?php foreach ($rawErdSoftware[(int) $erd->id] as $sw): ?>
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle small me-1"><i class="fas fa-cube me-1"></i><?php echo htmlspecialchars($sw->name, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <?php endforeach; ?>

<?php endif; ?>

<?php end_slot(); ?>
