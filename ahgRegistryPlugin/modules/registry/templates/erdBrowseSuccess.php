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

<!-- Category filter -->
<?php
  $rawCategories = sfOutputEscaper::unescape($categories);
  if (!is_array($rawCategories)) { $rawCategories = []; }
  $catLabels = [
    'core' => ['Core', 'primary'],
    'sector' => ['GLAM Sectors', 'info'],
    'compliance' => ['Compliance', 'danger'],
    'collection' => ['Collection Mgmt', 'success'],
    'rights' => ['Rights', 'warning'],
    'research' => ['Research', 'secondary'],
    'ai' => ['AI', 'purple'],
    'ingest' => ['Ingest', 'warning'],
    'integration' => ['Integration', 'dark'],
    'exhibition' => ['Exhibition', 'info'],
    'reporting' => ['Reporting', 'primary'],
  ];
?>
<div class="mb-4">
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'erdBrowse']); ?>" class="btn btn-sm <?php echo empty($selectedCategory) ? 'btn-dark' : 'btn-outline-dark'; ?> me-1 mb-1">
    <?php echo __('All'); ?>
  </a>
  <?php foreach ($rawCategories as $cat): ?>
    <?php $cl = $catLabels[$cat] ?? [$cat, 'secondary']; ?>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'erdBrowse']); ?>?category=<?php echo urlencode($cat); ?>"
       class="btn btn-sm <?php echo ($selectedCategory === $cat) ? 'btn-' . $cl[1] : 'btn-outline-' . $cl[1]; ?> me-1 mb-1">
      <?php echo htmlspecialchars($cl[0], ENT_QUOTES, 'UTF-8'); ?>
    </a>
  <?php endforeach; ?>
</div>

<!-- ERD tiles grid -->
<?php
  $rawItems = sfOutputEscaper::unescape($items);
  if (!is_array($rawItems)) { $rawItems = []; }
?>
<?php if (empty($rawItems)): ?>
  <div class="alert alert-info"><?php echo __('No ERD entries found.'); ?></div>
<?php else: ?>
<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-3">
  <?php foreach ($rawItems as $erd): ?>
  <?php
    $icon = $erd->icon ?? 'fas fa-database';
    $color = $erd->color ?? 'primary';
    $cat = $erd->category ?? 'general';
    $cl = $catLabels[$cat] ?? [$cat, 'secondary'];
    $tables = json_decode($erd->tables_json ?? '[]', true);
    $tableCount = is_array($tables) ? count($tables) : 0;
  ?>
  <div class="col">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'erdView', 'slug' => $erd->slug]); ?>" class="card h-100 text-decoration-none border-start border-<?php echo $color; ?> border-4">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <i class="<?php echo htmlspecialchars($icon, ENT_QUOTES, 'UTF-8'); ?> fa-2x text-<?php echo $color; ?> opacity-75"></i>
          <span class="badge bg-<?php echo $cl[1]; ?>"><?php echo htmlspecialchars($cl[0], ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <h6 class="card-title mb-1"><?php echo htmlspecialchars($erd->display_name, ENT_QUOTES, 'UTF-8'); ?></h6>
        <p class="card-text small text-muted mb-2"><?php echo htmlspecialchars(mb_strimwidth($erd->description ?? '', 0, 120, '...'), ENT_QUOTES, 'UTF-8'); ?></p>
        <div class="d-flex gap-2">
          <span class="badge bg-light text-dark border"><i class="fas fa-table me-1"></i><?php echo $tableCount; ?> <?php echo __('tables'); ?></span>
          <span class="badge bg-light text-dark border"><code class="small"><?php echo htmlspecialchars($erd->plugin_name, ENT_QUOTES, 'UTF-8'); ?></code></span>
        </div>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php end_slot(); ?>
