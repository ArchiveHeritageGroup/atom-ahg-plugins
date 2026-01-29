<?php decorate_with('layout_2col.php'); ?>

<?php slot('sidebar'); ?>
  <?php echo get_component('ahgSettings', 'menu'); ?>
<?php end_slot(); ?>

<?php slot('title'); ?>
  <h1><i class="fas fa-hashtag me-2"></i><?php echo __('Numbering Schemes'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <form method="get" class="d-inline-flex gap-2">
      <select name="sector" class="form-select form-select-sm" style="width: auto;">
        <option value=""><?php echo __('All Sectors'); ?></option>
        <?php foreach ($sectors as $code => $label): ?>
          <option value="<?php echo $code; ?>" <?php echo $sectorFilter === $code ? 'selected' : ''; ?>>
            <?php echo __($label); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-sm btn-outline-secondary"><?php echo __('Filter'); ?></button>
    </form>
  </div>
  <a href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'numberingSchemeEdit']); ?>" class="btn btn-primary">
    <i class="fas fa-plus me-1"></i><?php echo __('Add Scheme'); ?>
  </a>
</div>

<?php if (empty($schemes)): ?>
  <div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    <?php echo __('No numbering schemes configured. Click "Add Scheme" to create one.'); ?>
  </div>
<?php else: ?>

<div class="table-responsive">
  <table class="table table-hover">
    <thead class="table-dark">
      <tr>
        <th><?php echo __('Name'); ?></th>
        <th><?php echo __('Sector'); ?></th>
        <th><?php echo __('Pattern'); ?></th>
        <th><?php echo __('Preview'); ?></th>
        <th><?php echo __('Counter'); ?></th>
        <th><?php echo __('Reset'); ?></th>
        <th class="text-center"><?php echo __('Default'); ?></th>
        <th class="text-center"><?php echo __('Active'); ?></th>
        <th><?php echo __('Actions'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php
      $service = \AtomExtensions\Services\NumberingService::getInstance();
      foreach ($schemes as $scheme):
          $previews = $service->previewMultiple($scheme->sector, 1);
          $preview = $previews[0] ?? '-';
      ?>
      <tr>
        <td>
          <strong><?php echo esc_entities($scheme->name); ?></strong>
          <?php if ($scheme->description): ?>
            <br><small class="text-muted"><?php echo esc_entities($scheme->description); ?></small>
          <?php endif; ?>
        </td>
        <td>
          <span class="badge bg-<?php echo match ($scheme->sector) {
              'archive' => 'primary',
              'library' => 'success',
              'museum' => 'info',
              'gallery' => 'warning',
              'dam' => 'secondary',
              default => 'dark'
          }; ?>">
            <?php echo ucfirst($scheme->sector); ?>
          </span>
        </td>
        <td><code><?php echo esc_entities($scheme->pattern); ?></code></td>
        <td><code class="text-success"><?php echo esc_entities($preview); ?></code></td>
        <td><?php echo number_format($scheme->current_sequence); ?></td>
        <td>
          <?php echo match ($scheme->sequence_reset) {
              'yearly' => __('Yearly'),
              'monthly' => __('Monthly'),
              default => __('Never')
          }; ?>
        </td>
        <td class="text-center">
          <?php if ($scheme->is_default): ?>
            <i class="fas fa-star text-warning" title="<?php echo __('Default'); ?>"></i>
          <?php else: ?>
            <a href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'numberingSchemes', 'do' => 'setDefault', 'id' => $scheme->id]); ?>"
               class="text-muted" title="<?php echo __('Set as default'); ?>">
              <i class="far fa-star"></i>
            </a>
          <?php endif; ?>
        </td>
        <td class="text-center">
          <?php if ($scheme->is_active): ?>
            <i class="fas fa-check-circle text-success"></i>
          <?php else: ?>
            <i class="fas fa-times-circle text-danger"></i>
          <?php endif; ?>
        </td>
        <td>
          <div class="btn-group btn-group-sm">
            <a href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'numberingSchemeEdit', 'id' => $scheme->id]); ?>"
               class="btn btn-outline-primary" title="<?php echo __('Edit'); ?>">
              <i class="fas fa-edit"></i>
            </a>
            <a href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'numberingSchemes', 'do' => 'resetSequence', 'id' => $scheme->id]); ?>"
               class="btn btn-outline-warning" title="<?php echo __('Reset sequence'); ?>"
               onclick="return confirm('<?php echo __('Reset sequence to 0?'); ?>');">
              <i class="fas fa-redo"></i>
            </a>
            <a href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'numberingSchemes', 'do' => 'delete', 'id' => $scheme->id]); ?>"
               class="btn btn-outline-danger" title="<?php echo __('Delete'); ?>"
               onclick="return confirm('<?php echo __('Delete this scheme?'); ?>');">
              <i class="fas fa-trash"></i>
            </a>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php endif; ?>

<!-- Token Reference -->
<div class="card mt-4">
  <div class="card-header bg-secondary text-white">
    <i class="fas fa-code me-2"></i><?php echo __('Available Tokens'); ?>
  </div>
  <div class="card-body">
    <div class="row">
      <?php foreach ($tokens as $token => $description): ?>
      <div class="col-md-4 mb-2">
        <code><?php echo esc_entities($token); ?></code>
        <small class="text-muted d-block"><?php echo esc_entities($description); ?></small>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php end_slot(); ?>
