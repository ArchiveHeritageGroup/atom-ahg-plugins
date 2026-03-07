<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Standards Schema & ERD'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Standards'), 'url' => url_for(['module' => 'registry', 'action' => 'standardBrowse'])],
  ['label' => __('Schema & ERD')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1"><?php echo __('Standards Schema & ERD'); ?></h1>
    <p class="text-muted mb-0"><?php echo __('Database structure, relationships, and field definitions for the Standards & Conformance module.'); ?></p>
  </div>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'standardBrowse']); ?>" class="btn btn-outline-primary">
    <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Standards'); ?>
  </a>
</div>

<!-- Record counts -->
<div class="row row-cols-2 row-cols-lg-4 g-3 mb-5">
  <?php
    $tableLabels = [
      'registry_standard' => ['Standards', 'fas fa-balance-scale', 'primary'],
      'registry_standard_extension' => ['Heratio Extensions', 'fas fa-puzzle-piece', 'success'],
      'registry_software_standard' => ['Conformance Records', 'fas fa-check-circle', 'info'],
      'registry_setup_guide' => ['Setup Guides', 'fas fa-book-open', 'secondary'],
    ];
    $rawCounts = sfOutputEscaper::unescape($counts);
    if (!is_array($rawCounts)) { $rawCounts = []; }
  ?>
  <?php foreach ($tableLabels as $tbl => $meta): ?>
  <div class="col">
    <div class="card h-100 text-center">
      <div class="card-body">
        <i class="<?php echo $meta[1]; ?> fa-2x text-<?php echo $meta[2]; ?> mb-2"></i>
        <div class="h4 mb-0"><?php echo number_format($rawCounts[$tbl] ?? 0); ?></div>
        <div class="small text-muted"><?php echo $meta[0]; ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ERD Diagram (ASCII) -->
<div class="card mb-5">
  <div class="card-header bg-dark text-white">
    <h5 class="mb-0"><i class="fas fa-project-diagram me-2"></i><?php echo __('Entity Relationship Diagram'); ?></h5>
  </div>
  <div class="card-body p-0">
    <pre class="p-4 mb-0" style="font-size: 0.78em; overflow-x: auto; background: #f8f9fa;">
┌─────────────────────────────────┐
│        registry_standard        │
├─────────────────────────────────┤
│ PK id                 BIGINT    │
│    name, acronym, slug          │
│    category, description        │
│    issuing_body, official_url   │
│    current_version              │
│    sector_applicability (JSON)  │
│    is_featured, is_active       │
└────────────┬────────────────────┘
             │
             │ 1:N (ON DELETE CASCADE)
             ▼
┌─────────────────────────────────┐
│  registry_standard_extension    │◄── Heratio deviations / additions
├─────────────────────────────────┤
│ PK id                 BIGINT    │
│ FK standard_id        BIGINT    │───► registry_standard.id
│    title, slug                  │
│    extension_type               │    addition│deviation│enhancement│wrapper
│    heratio_plugin               │    Which plugin implements it
│    heratio_version              │    Minimum framework version
│    is_active                    │
└─────────────────────────────────┘

         ┌────────────────────────────────────────────┐
         │                                            │
┌────────┴────────────────────────┐  ┌────────────────┴───────────────────┐
│  registry_software_standard     │  │     registry_setup_guide           │
│  (Conformance / Compliance)     │  │     (Implementation Guides)        │
├─────────────────────────────────┤  ├────────────────────────────────────┤
│ PK id                 BIGINT    │  │ PK id                 BIGINT       │
│ FK software_id        BIGINT ───│──│ FK software_id        BIGINT ──────│──┐
│ FK standard_id        BIGINT ───│  │    title, slug, category           │  │
│    conformance_level  VARCHAR   │  │    content (LONGTEXT/markdown)     │  │
│    notes              TEXT      │  │    difficulty, estimated_minutes   │  │
│ UNIQUE(software_id, standard_id)│  │    view_count, is_active           │  │
└──────────┬──────────────────────┘  └────────────────────────────────────┘  │
           │                                                                │
           └─────────────────┐  ┌───────────────────────────────────────────┘
                             ▼  ▼
                  ┌─────────────────────────────┐
                  │      registry_software       │  (existing table)
                  ├─────────────────────────────┤
                  │ PK id            BIGINT      │
                  │    name, slug, ...           │
                  └─────────────────────────────┘</pre>
  </div>
</div>

<!-- Foreign Keys -->
<?php
  $rawFKs = sfOutputEscaper::unescape($foreignKeys);
  if (!is_array($rawFKs)) { $rawFKs = []; }
?>
<?php if (!empty($rawFKs)): ?>
<div class="card mb-5">
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

<!-- Table Schemas -->
<?php
  $rawSchema = sfOutputEscaper::unescape($schema);
  if (!is_array($rawSchema)) { $rawSchema = []; }
?>
<?php foreach ($rawSchema as $tableName => $columns): ?>
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">
      <i class="fas fa-table me-2"></i><code><?php echo htmlspecialchars($tableName, ENT_QUOTES, 'UTF-8'); ?></code>
    </h5>
    <span class="badge bg-secondary"><?php echo count($columns); ?> <?php echo __('columns'); ?></span>
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

<!-- Legend -->
<div class="card bg-light border-0 mb-4">
  <div class="card-body">
    <h6 class="mb-2"><i class="fas fa-info-circle me-1"></i><?php echo __('Legend'); ?></h6>
    <div class="d-flex flex-wrap gap-3 small">
      <span><span class="badge bg-danger">PK</span> <?php echo __('Primary Key'); ?></span>
      <span><span class="badge bg-warning text-dark">FK</span> <?php echo __('Foreign Key / Index'); ?></span>
      <span><span class="badge bg-info text-dark">UQ</span> <?php echo __('Unique Constraint'); ?></span>
      <span><span class="badge bg-success"><i class="fas fa-puzzle-piece me-1"></i>Heratio +N</span> <?php echo __('Count of active Heratio extensions for a standard'); ?></span>
    </div>
    <div class="mt-2 small text-muted">
      <?php echo __('All foreign keys use ON DELETE CASCADE. The extension_count shown on standard cards is computed via a subquery on registry_standard_extension where is_active = 1.'); ?>
    </div>
  </div>
</div>

<?php end_slot(); ?>
