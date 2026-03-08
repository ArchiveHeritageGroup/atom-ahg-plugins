<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo sfOutputEscaper::unescape($erd->display_name); ?> — <?php echo __('Schema & ERD'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Schema & ERD'), 'url' => url_for(['module' => 'registry', 'action' => 'erdBrowse'])],
  ['label' => $erd->display_name],
]]); ?>

<?php
  $icon = $erd->icon ?? 'fas fa-database';
  $color = $erd->color ?? 'primary';
  $rawCounts = sfOutputEscaper::unescape($counts);
  if (!is_array($rawCounts)) { $rawCounts = []; }
  $rawSchema = sfOutputEscaper::unescape($schema);
  if (!is_array($rawSchema)) { $rawSchema = []; }
  $rawFKs = sfOutputEscaper::unescape($foreignKeys);
  if (!is_array($rawFKs)) { $rawFKs = []; }
?>

<div class="d-flex justify-content-between align-items-start mb-4">
  <div>
    <h1 class="h3 mb-1">
      <i class="<?php echo htmlspecialchars($icon, ENT_QUOTES, 'UTF-8'); ?> text-<?php echo $color; ?> me-2"></i><?php echo $erd->display_name; ?>
    </h1>
    <p class="text-muted mb-1"><?php echo $erd->description; ?></p>
    <span class="badge bg-light text-dark border"><code><?php echo $erd->plugin_name; ?></code></span>
  </div>
  <div class="d-flex gap-2">
    <?php if (!empty($isAdmin)): ?>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminErdEdit', 'id' => $erd->id]); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-edit me-1"></i><?php echo __('Edit'); ?>
    </a>
    <?php endif; ?>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'erdBrowse']); ?>" class="btn btn-outline-primary btn-sm">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('All Schemas'); ?>
    </a>
  </div>
</div>

<!-- Table counts -->
<?php if (!empty($rawCounts)): ?>
<div class="row row-cols-2 row-cols-lg-<?php echo min(count($rawCounts), 6); ?> g-3 mb-4">
  <?php foreach ($rawCounts as $tbl => $cnt): ?>
  <div class="col">
    <div class="card h-100 text-center">
      <div class="card-body py-2">
        <div class="h5 mb-0"><?php echo number_format($cnt); ?></div>
        <div class="small text-muted"><code><?php echo htmlspecialchars($tbl, ENT_QUOTES, 'UTF-8'); ?></code></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ERD Diagram Image -->
<?php if (!empty($erd->diagram_image)): ?>
<?php
  $_imgPath = $erd->diagram_image;
  $_ext = strtolower(pathinfo(sfOutputEscaper::unescape($_imgPath), PATHINFO_EXTENSION));
  $_isImg = in_array($_ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg']);
?>
<div class="card mb-4">
  <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="fas fa-project-diagram me-2"></i><?php echo __('Entity Relationship Diagram'); ?></h5>
    <a href="<?php echo htmlspecialchars($_imgPath, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-outline-light btn-sm">
      <i class="fas fa-external-link-alt me-1"></i><?php echo __('Open Full Size'); ?>
    </a>
  </div>
  <div class="card-body text-center p-3">
    <?php if ($_isImg): ?>
      <a href="<?php echo htmlspecialchars($_imgPath, ENT_QUOTES, 'UTF-8'); ?>" target="_blank">
        <img src="<?php echo htmlspecialchars($_imgPath, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo $erd->display_name; ?> ERD" class="img-fluid rounded" style="max-height: 800px;">
      </a>
    <?php elseif ($_ext === 'pdf'): ?>
      <embed src="<?php echo htmlspecialchars($_imgPath, ENT_QUOTES, 'UTF-8'); ?>" type="application/pdf" width="100%" height="700px" class="rounded border">
    <?php else: ?>
      <a href="<?php echo htmlspecialchars($_imgPath, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-primary">
        <i class="fas fa-download me-1"></i><?php echo __('Download Diagram'); ?>
      </a>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<!-- ASCII ERD Diagram -->
<?php if (!empty($erd->diagram)): ?>
<div class="card mb-4">
  <div class="card-header bg-dark text-white">
    <h5 class="mb-0"><i class="fas fa-project-diagram me-2"></i><?php echo !empty($erd->diagram_image) ? __('ASCII Diagram (Detail)') : __('Entity Relationship Diagram'); ?></h5>
  </div>
  <div class="card-body p-0">
    <pre class="p-4 mb-0 text-white" style="font-size: 0.78em; overflow-x: auto; background: #1a1a2e;"><?php echo htmlspecialchars($erd->diagram, ENT_QUOTES, 'UTF-8'); ?></pre>
  </div>
</div>
<?php endif; ?>

<!-- Foreign Keys -->
<?php if (!empty($rawFKs)): ?>
<div class="card mb-4">
  <div class="card-header">
    <h5 class="mb-0"><i class="fas fa-link me-2"></i><?php echo __('Foreign Key Relationships'); ?></h5>
  </div>
  <div class="table-responsive">
    <table class="table table-sm table-striped mb-0">
      <thead class="table-light">
        <tr>
          <th><?php echo __('Constraint'); ?></th>
          <th><?php echo __('Table'); ?></th>
          <th><?php echo __('Column'); ?></th>
          <th></th>
          <th><?php echo __('References'); ?></th>
          <th><?php echo __('Referenced Column'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rawFKs as $fk): ?>
        <tr>
          <td><code class="small"><?php echo htmlspecialchars($fk->CONSTRAINT_NAME, ENT_QUOTES, 'UTF-8'); ?></code></td>
          <td><code><?php echo htmlspecialchars($fk->TABLE_NAME, ENT_QUOTES, 'UTF-8'); ?></code></td>
          <td><span class="badge bg-warning text-dark"><?php echo htmlspecialchars($fk->COLUMN_NAME, ENT_QUOTES, 'UTF-8'); ?></span></td>
          <td><i class="fas fa-long-arrow-alt-right text-muted"></i></td>
          <td><code><?php echo htmlspecialchars($fk->REFERENCED_TABLE_NAME, ENT_QUOTES, 'UTF-8'); ?></code></td>
          <td><span class="badge bg-primary"><?php echo htmlspecialchars($fk->REFERENCED_COLUMN_NAME, ENT_QUOTES, 'UTF-8'); ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Table Schemas (live from information_schema) -->
<?php foreach ($rawSchema as $tableName => $columns): ?>
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">
      <i class="fas fa-table me-2"></i><code><?php echo htmlspecialchars($tableName, ENT_QUOTES, 'UTF-8'); ?></code>
    </h5>
    <div>
      <span class="badge bg-secondary me-1"><?php echo count($columns); ?> <?php echo __('columns'); ?></span>
      <span class="badge bg-primary"><?php echo number_format($rawCounts[$tableName] ?? 0); ?> <?php echo __('rows'); ?></span>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table table-sm table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th style="width: 30px;"></th>
          <th><?php echo __('Column'); ?></th>
          <th><?php echo __('Type'); ?></th>
          <th><?php echo __('Nullable'); ?></th>
          <th><?php echo __('Default'); ?></th>
          <th><?php echo __('Extra'); ?></th>
          <th><?php echo __('Comment'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($columns as $col): ?>
        <?php
          $isPK = $col->COLUMN_KEY === 'PRI';
          $isFK = $col->COLUMN_KEY === 'MUL';
          $isUNI = $col->COLUMN_KEY === 'UNI';
        ?>
        <tr>
          <td class="text-center">
            <?php if ($isPK): ?>
              <span class="badge bg-danger" title="Primary Key">PK</span>
            <?php elseif ($isFK): ?>
              <span class="badge bg-warning text-dark" title="Foreign Key / Index">FK</span>
            <?php elseif ($isUNI): ?>
              <span class="badge bg-info text-dark" title="Unique">UQ</span>
            <?php endif; ?>
          </td>
          <td><code><?php echo htmlspecialchars($col->COLUMN_NAME, ENT_QUOTES, 'UTF-8'); ?></code></td>
          <td><span class="text-muted small"><?php echo htmlspecialchars($col->COLUMN_TYPE, ENT_QUOTES, 'UTF-8'); ?></span></td>
          <td>
            <?php if ($col->IS_NULLABLE === 'YES'): ?>
              <span class="text-muted small">NULL</span>
            <?php else: ?>
              <span class="text-danger small">NOT NULL</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($col->COLUMN_DEFAULT !== null): ?>
              <code class="small"><?php echo htmlspecialchars($col->COLUMN_DEFAULT, ENT_QUOTES, 'UTF-8'); ?></code>
            <?php else: ?>
              <span class="text-muted small">-</span>
            <?php endif; ?>
          </td>
          <td><span class="text-muted small"><?php echo htmlspecialchars($col->EXTRA, ENT_QUOTES, 'UTF-8'); ?></span></td>
          <td><span class="text-muted small"><?php echo htmlspecialchars($col->COLUMN_COMMENT ?? '', ENT_QUOTES, 'UTF-8'); ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endforeach; ?>

<!-- Notes -->
<?php if (!empty($erd->notes)): ?>
<div class="card bg-light border-0 mb-4">
  <div class="card-body">
    <h6 class="mb-2"><i class="fas fa-info-circle me-1"></i><?php echo __('Notes'); ?></h6>
    <div class="small"><?php echo nl2br(htmlspecialchars($erd->notes, ENT_QUOTES, 'UTF-8')); ?></div>
  </div>
</div>
<?php endif; ?>

<!-- Notes -->
<?php include_partial('registry/notes', [
  'entityType' => 'erd',
  'entityId' => $erd->id,
  'returnUrl' => $sf_request->getUri(),
]); ?>

<!-- Legend -->
<div class="card bg-light border-0 mb-4">
  <div class="card-body">
    <h6 class="mb-2"><i class="fas fa-info-circle me-1"></i><?php echo __('Legend'); ?></h6>
    <div class="d-flex flex-wrap gap-3 small">
      <span><span class="badge bg-danger">PK</span> <?php echo __('Primary Key'); ?></span>
      <span><span class="badge bg-warning text-dark">FK</span> <?php echo __('Foreign Key / Index'); ?></span>
      <span><span class="badge bg-info text-dark">UQ</span> <?php echo __('Unique Constraint'); ?></span>
    </div>
    <div class="mt-2 small text-muted">
      <?php echo __('Table structures are read live from the database. Row counts reflect current data.'); ?>
    </div>
  </div>
</div>

<?php end_slot(); ?>
