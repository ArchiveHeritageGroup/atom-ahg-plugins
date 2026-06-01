<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive ?? 'journal', 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php slot('title') ?>
<h1><i class="fas fa-book-open text-primary me-2"></i><?php echo __('Journal Builder'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php
$publications = is_array($publications) ? $publications : [];
$manuscripts  = is_array($manuscripts) ? $manuscripts : [];
$badge = function ($status) {
    $map = ['draft' => 'secondary', 'published' => 'success', 'archived' => 'dark'];
    $cls = $map[$status] ?? 'secondary';
    return '<span class="badge bg-' . $cls . '">' . htmlspecialchars(ucfirst((string) $status)) . '</span>';
};
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-muted mb-0"><?php echo __('Build institutional journals or draft a manuscript toward an external target journal.'); ?></p>
  <div class="d-flex gap-2">
    <a class="btn btn-primary" href="<?php echo url_for(['module' => 'researchjournal', 'action' => 'builder', 'kind' => 'publication']); ?>">
      <i class="fas fa-plus me-1"></i><?php echo __('New journal'); ?>
    </a>
    <a class="btn btn-outline-primary" href="<?php echo url_for(['module' => 'researchjournal', 'action' => 'builder', 'kind' => 'manuscript']); ?>">
      <i class="fas fa-file-alt me-1"></i><?php echo __('New manuscript'); ?>
    </a>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header"><h5 class="mb-0"><i class="fas fa-newspaper me-2"></i><?php echo __('Publications'); ?></h5></div>
  <div class="card-body p-0">
    <?php if (empty($publications)): ?>
      <p class="text-muted p-3 mb-0"><?php echo __('No journals yet.'); ?></p>
    <?php else: ?>
      <table class="table table-hover mb-0">
        <thead><tr>
          <th><?php echo __('Title'); ?></th><th><?php echo __('Publisher'); ?></th>
          <th><?php echo __('ISSN'); ?></th><th><?php echo __('Status'); ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($publications as $j): ?>
          <tr>
            <td><a href="<?php echo url_for(['module' => 'researchjournal', 'action' => 'show', 'id' => $j['id']]); ?>"><?php echo htmlspecialchars((string) $j['title']); ?></a></td>
            <td><?php echo htmlspecialchars((string) ($j['publisher'] ?? '')); ?></td>
            <td><?php echo htmlspecialchars((string) ($j['issn'] ?? '')); ?></td>
            <td><?php echo $badge($j['status'] ?? 'draft'); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-header"><h5 class="mb-0"><i class="fas fa-file-alt me-2"></i><?php echo __('Manuscripts'); ?></h5></div>
  <div class="card-body p-0">
    <?php if (empty($manuscripts)): ?>
      <p class="text-muted p-3 mb-0"><?php echo __('No manuscripts yet.'); ?></p>
    <?php else: ?>
      <table class="table table-hover mb-0">
        <thead><tr>
          <th><?php echo __('Title'); ?></th><th><?php echo __('Target journal'); ?></th>
          <th><?php echo __('Status'); ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($manuscripts as $j): ?>
          <tr>
            <td><a href="<?php echo url_for(['module' => 'researchjournal', 'action' => 'show', 'id' => $j['id']]); ?>"><?php echo htmlspecialchars((string) $j['title']); ?></a></td>
            <td><?php echo $j['target_journal_id'] ? '#' . (int) $j['target_journal_id'] : '<span class="text-muted">&mdash;</span>'; ?></td>
            <td><?php echo $badge($j['status'] ?? 'draft'); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
<?php end_slot() ?>
