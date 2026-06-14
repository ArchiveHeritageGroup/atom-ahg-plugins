<?php
require_once dirname(__DIR__, 3) . '/lib/Services/AiActGovernanceService.php';
$risks = $sf_data->getRaw('risks') ?: [];
$bandTone = ['critical' => 'dark', 'high' => 'danger', 'medium' => 'warning', 'low' => 'success'];
$statusTone = ['open' => 'danger', 'mitigating' => 'warning', 'accepted' => 'info', 'closed' => 'success'];
$listUrl = url_for(['module' => 'aiActGovernance', 'action' => 'risks']);
?>
<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-triangle-exclamation me-2"></i><?php echo __('AI Risk Register'); ?> <small class="text-muted">(<?php echo __('Art. 9'); ?>)</small></h1>
    <div>
      <a href="<?php echo url_for(['module' => 'aiActGovernance', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i><?php echo __('Dashboard'); ?></a>
      <a href="<?php echo url_for(['module' => 'aiActGovernance', 'action' => 'riskEdit']); ?>" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i><?php echo __('New risk'); ?></a>
    </div>
  </div>
  <div class="card"><div class="card-body p-0">
    <table class="table table-hover mb-0 align-middle">
      <thead><tr><th><?php echo __('Risk'); ?></th><th><?php echo __('Category'); ?></th><th><?php echo __('System'); ?></th><th><?php echo __('Score'); ?></th><th><?php echo __('Status'); ?></th><th><?php echo __('Owner'); ?></th><th></th></tr></thead>
      <tbody>
      <?php if (empty($risks)): ?>
        <tr><td colspan="7" class="text-muted p-3"><?php echo __('No risks recorded yet.'); ?></td></tr>
      <?php else: foreach ($risks as $r):
          $score = (int) $r->likelihood * (int) $r->severity;
          $band = AiActGovernanceService::riskBand($score); ?>
        <tr>
          <td><strong><?php echo htmlspecialchars((string) $r->title); ?></strong></td>
          <td><?php echo htmlspecialchars(__(ucwords(str_replace('_', ' ', (string) $r->category)))); ?></td>
          <td><?php echo htmlspecialchars((string) ($r->system_name ?? '—')); ?></td>
          <td><span class="badge bg-<?php echo $bandTone[$band] ?? 'secondary'; ?>"><?php echo $score; ?> · <?php echo __(ucfirst($band)); ?></span></td>
          <td><span class="badge bg-<?php echo $statusTone[$r->status] ?? 'secondary'; ?>"><?php echo __(ucfirst((string) $r->status)); ?></span></td>
          <td><?php echo htmlspecialchars((string) ($r->owner ?? '')); ?></td>
          <td class="text-end">
            <a class="btn btn-outline-primary btn-sm" href="<?php echo url_for(['module' => 'aiActGovernance', 'action' => 'riskEdit', 'id' => $r->id]); ?>"><i class="fas fa-pen"></i></a>
            <form method="post" action="<?php echo $listUrl; ?>" class="d-inline" onsubmit="return confirm('<?php echo __('Delete this risk?'); ?>');">
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
