<?php
$attestations = $sf_data->getRaw('attestations') ?: [];
$statusTone = ['draft' => 'secondary', 'attested' => 'success', 'expired' => 'warning', 'revoked' => 'danger'];
$today = date('Y-m-d');
$listUrl = url_for(['module' => 'aiActGovernance', 'action' => 'attestations']);
?>
<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-file-signature me-2"></i><?php echo __('Conformity & Oversight Attestations'); ?></h1>
    <div>
      <a href="<?php echo url_for(['module' => 'aiActGovernance', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i><?php echo __('Dashboard'); ?></a>
      <a href="<?php echo url_for(['module' => 'aiActGovernance', 'action' => 'attestationEdit']); ?>" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i><?php echo __('New attestation'); ?></a>
    </div>
  </div>
  <div class="card"><div class="card-body p-0">
    <table class="table table-hover mb-0 align-middle">
      <thead><tr><th><?php echo __('Type'); ?></th><th><?php echo __('System'); ?></th><th><?php echo __('Status'); ?></th><th><?php echo __('Attested by'); ?></th><th><?php echo __('Next review'); ?></th><th></th></tr></thead>
      <tbody>
      <?php if (empty($attestations)): ?>
        <tr><td colspan="6" class="text-muted p-3"><?php echo __('No attestations recorded yet.'); ?></td></tr>
      <?php else: foreach ($attestations as $r):
          $overdue = $r->next_review_date && $r->next_review_date < $today && in_array($r->status, ['draft', 'attested'], true); ?>
        <tr>
          <td><?php echo htmlspecialchars(__(ucwords(str_replace('_', ' ', (string) $r->type)))); ?></td>
          <td><?php echo htmlspecialchars((string) ($r->system_name ?? '—')); ?></td>
          <td><span class="badge bg-<?php echo $statusTone[$r->status] ?? 'secondary'; ?>"><?php echo __(ucfirst((string) $r->status)); ?></span></td>
          <td><?php echo htmlspecialchars((string) ($r->attested_by ?? '')); ?></td>
          <td><?php echo htmlspecialchars((string) ($r->next_review_date ?? '')); ?><?php if ($overdue): ?> <span class="badge bg-warning"><?php echo __('overdue'); ?></span><?php endif; ?></td>
          <td class="text-end">
            <a class="btn btn-outline-primary btn-sm" href="<?php echo url_for(['module' => 'aiActGovernance', 'action' => 'attestationEdit', 'id' => $r->id]); ?>"><i class="fas fa-pen"></i></a>
            <form method="post" action="<?php echo $listUrl; ?>" class="d-inline" onsubmit="return confirm('<?php echo __('Delete this attestation?'); ?>');">
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
