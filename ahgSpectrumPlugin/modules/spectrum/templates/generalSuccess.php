<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-building me-2"></i><?php echo __('General Spectrum Procedures'); ?></h1>
<?php end_slot(); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'dashboard']); ?>"><?php echo __('Spectrum Dashboard'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('General Procedures'); ?></li>
  </ol>
</nav>

<div class="alert alert-info mb-4">
    <i class="fas fa-info-circle me-2"></i>
    <?php echo __('General procedures apply at the institution level and are not tied to a specific object. Use these for organisation-wide workflows such as institutional audits, risk management, and documentation planning.'); ?>
</div>

<!-- Procedures Grid -->
<div class="card mb-4">
  <div class="card-header bg-info text-white">
    <h5 class="mb-0"><i class="fas fa-tasks me-2"></i><?php echo __('Institution-Level Procedures'); ?></h5>
  </div>
  <div class="card-body">
    <div class="row g-3">
      <?php
      $colors = ['primary', 'success', 'info', 'warning', 'secondary', 'dark'];
      $i = 0;
      foreach ($procedures as $key => $proc):
        $color = $colors[$i % count($colors)];
        $currentState = $procedureStatuses[$key] ?? null;
        $i++;
      ?>
      <div class="col-lg-3 col-md-4 col-sm-6">
        <div class="card h-100 border-<?php echo $color; ?>">
          <div class="card-body text-center p-3">
            <i class="fas <?php echo $proc['icon']; ?> fa-2x mb-2 text-<?php echo $color; ?>"></i>
            <h6 class="card-title mb-2"><?php echo $proc['label']; ?></h6>
            <?php if ($currentState): ?>
            <span class="badge bg-<?php echo $color; ?> mb-2"><?php echo ucwords(str_replace('_', ' ', $currentState)); ?></span>
            <?php endif; ?>
            <div>
              <a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'generalWorkflow', 'procedure_type' => $key]); ?>"
                 class="btn btn-sm btn-outline-<?php echo $color; ?>">
                <i class="fas fa-cog me-1"></i><?php echo __('Manage'); ?>
              </a>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Recent General Activity -->
<div class="card">
  <div class="card-header">
    <h5 class="mb-0"><i class="fas fa-history me-2"></i><?php echo __('Recent General Procedure Activity'); ?></h5>
  </div>
  <div class="card-body">
    <?php if (empty($recentHistory)): ?>
      <p class="text-muted mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('No general procedure activity recorded yet.'); ?></p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm table-hover">
          <thead>
            <tr>
              <th><?php echo __('Date'); ?></th>
              <th><?php echo __('Procedure'); ?></th>
              <th><?php echo __('Action'); ?></th>
              <th><?php echo __('User'); ?></th>
              <th><?php echo __('Notes'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentHistory as $entry): ?>
            <tr>
              <td><small><?php echo date('Y-m-d H:i', strtotime($entry->created_at)); ?></small></td>
              <td><?php echo ucwords(str_replace('_', ' ', $entry->procedure_type)); ?></td>
              <td>
                <span class="badge bg-secondary"><?php echo ucwords(str_replace('_', ' ', $entry->from_state)); ?></span>
                <i class="fas fa-arrow-right mx-1"></i>
                <span class="badge bg-primary"><?php echo ucwords(str_replace('_', ' ', $entry->to_state)); ?></span>
              </td>
              <td><small><?php echo esc_entities($entry->user_name ?? ''); ?></small></td>
              <td><small><?php echo esc_entities($entry->note ?? ''); ?></small></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
