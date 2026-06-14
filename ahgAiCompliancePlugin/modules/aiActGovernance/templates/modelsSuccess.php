<?php
$models = $sf_data->getRaw('models') ?: [];
$listUrl = url_for(['module' => 'aiActGovernance', 'action' => 'models']);
?>
<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-cubes me-2"></i><?php echo __('Model Registry'); ?></h1>
    <div>
      <a href="<?php echo url_for(['module' => 'aiActGovernance', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i><?php echo __('Dashboard'); ?></a>
      <a href="<?php echo url_for(['module' => 'aiActGovernance', 'action' => 'modelEdit']); ?>" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i><?php echo __('New model'); ?></a>
    </div>
  </div>
  <div class="card"><div class="card-body p-0">
    <table class="table table-hover mb-0 align-middle">
      <thead><tr><th><?php echo __('Model'); ?></th><th><?php echo __('Version'); ?></th><th><?php echo __('Modality'); ?></th><th><?php echo __('Provider'); ?></th><th><?php echo __('System'); ?></th><th></th></tr></thead>
      <tbody>
      <?php if (empty($models)): ?>
        <tr><td colspan="6" class="text-muted p-3"><?php echo __('No models registered yet.'); ?></td></tr>
      <?php else: foreach ($models as $r): ?>
        <tr>
          <td><strong><?php echo htmlspecialchars((string) $r->model_id); ?></strong><?php if (!$r->is_active): ?> <span class="badge bg-secondary"><?php echo __('inactive'); ?></span><?php endif; ?></td>
          <td><?php echo htmlspecialchars((string) ($r->version ?? '')); ?></td>
          <td><?php echo htmlspecialchars(ucfirst((string) $r->modality)); ?></td>
          <td><?php echo htmlspecialchars((string) ($r->provider ?? '')); ?></td>
          <td><?php echo htmlspecialchars((string) ($r->system_name ?? '—')); ?></td>
          <td class="text-end">
            <a class="btn btn-outline-primary btn-sm" href="<?php echo url_for(['module' => 'aiActGovernance', 'action' => 'modelEdit', 'id' => $r->id]); ?>"><i class="fas fa-pen"></i></a>
            <form method="post" action="<?php echo $listUrl; ?>" class="d-inline" onsubmit="return confirm('<?php echo __('Delete this model?'); ?>');">
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
