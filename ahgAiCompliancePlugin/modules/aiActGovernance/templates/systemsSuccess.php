<?php
$systems = $sf_data->getRaw('systems') ?: [];
$riskTone = ['prohibited' => 'dark', 'high' => 'danger', 'limited' => 'warning', 'minimal' => 'success'];
$listUrl = url_for(['module' => 'aiActGovernance', 'action' => 'systems']);
?>
<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-microchip me-2"></i><?php echo __('AI System Inventory'); ?></h1>
    <div>
      <a href="<?php echo url_for(['module' => 'aiActGovernance', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i><?php echo __('Dashboard'); ?></a>
      <a href="<?php echo url_for(['module' => 'aiActGovernance', 'action' => 'systemEdit']); ?>" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i><?php echo __('New system'); ?></a>
    </div>
  </div>

  <div class="card"><div class="card-body p-0">
    <table class="table table-hover mb-0 align-middle">
      <thead><tr><th><?php echo __('Name'); ?></th><th><?php echo __('Risk'); ?></th><th><?php echo __('Status'); ?></th><th><?php echo __('Role'); ?></th><th><?php echo __('Owner'); ?></th><th><?php echo __('Next review'); ?></th><th></th></tr></thead>
      <tbody>
      <?php if (empty($systems)): ?>
        <tr><td colspan="7" class="text-muted p-3"><?php echo __('No AI systems recorded yet.'); ?></td></tr>
      <?php else: foreach ($systems as $r): $tone = $riskTone[$r->risk_classification] ?? 'secondary'; ?>
        <tr>
          <td><strong><?php echo htmlspecialchars((string) $r->name); ?></strong><?php if (!$r->is_active): ?> <span class="badge bg-secondary"><?php echo __('inactive'); ?></span><?php endif; ?></td>
          <td><span class="badge bg-<?php echo $tone; ?>"><?php echo __(ucfirst((string) $r->risk_classification)); ?></span></td>
          <td><?php echo htmlspecialchars(ucfirst((string) $r->lifecycle_status)); ?></td>
          <td><?php echo htmlspecialchars(ucfirst((string) $r->role)); ?></td>
          <td><?php echo htmlspecialchars((string) ($r->owner ?? '')); ?></td>
          <td><?php echo htmlspecialchars((string) ($r->next_review_date ?? '')); ?></td>
          <td class="text-end">
            <a class="btn btn-outline-primary btn-sm" href="<?php echo url_for(['module' => 'aiActGovernance', 'action' => 'systemEdit', 'id' => $r->id]); ?>"><i class="fas fa-pen"></i></a>
            <form method="post" action="<?php echo $listUrl; ?>" class="d-inline" onsubmit="return confirm('<?php echo __('Delete this system?'); ?>');">
              <input type="hidden" name="id" value="<?php echo (int) $r->id; ?>">
              <button class="btn btn-outline-danger btn-sm" name="form_action" value="delete"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div></div>
</main>
