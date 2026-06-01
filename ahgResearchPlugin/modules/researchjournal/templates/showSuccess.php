<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive ?? 'journal', 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php
$j = is_array($journal_record) ? $journal_record : [];
$toc = is_array($toc) ? $toc : [];
$isManuscript = (($j['kind'] ?? '') === 'manuscript');
$jid = (int) ($j['id'] ?? 0);
$badge = function ($status) {
    $map = ['draft' => 'secondary', 'published' => 'success', 'archived' => 'dark'];
    $cls = $map[$status] ?? 'secondary';
    return '<span class="badge bg-' . $cls . '">' . htmlspecialchars(ucfirst((string) $status)) . '</span>';
};
?>
<?php slot('title') ?>
<h1>
  <i class="fas <?php echo $isManuscript ? 'fa-file-alt' : 'fa-newspaper'; ?> text-primary me-2"></i>
  <?php echo htmlspecialchars((string) ($j['title'] ?? '')); ?>
  <?php echo $badge($j['status'] ?? 'draft'); ?>
</h1>
<?php end_slot() ?>

<?php slot('content') ?>
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'researchjournal', 'action' => 'index']); ?>"><?php echo __('Journal Builder'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo htmlspecialchars((string) ($j['title'] ?? '')); ?></li>
  </ol>
</nav>

<div class="d-flex flex-wrap gap-2 mb-3">
  <a class="btn btn-outline-secondary" href="<?php echo url_for(['module' => 'researchjournal', 'action' => 'builder', 'id' => $jid]); ?>">
    <i class="fas fa-cog me-1"></i><?php echo __('Edit details'); ?>
  </a>
  <a class="btn btn-primary" href="<?php echo url_for(['module' => 'researchjournal', 'action' => 'article', 'journal_id' => $jid]); ?>">
    <i class="fas fa-plus me-1"></i><?php echo $isManuscript ? __('New manuscript article') : __('New article'); ?>
  </a>

  <form method="post" class="d-inline-flex align-items-center gap-1 ms-auto">
    <input type="hidden" name="form_action" value="set_status">
    <select name="status" class="form-select form-select-sm" style="width:auto">
      <?php foreach (['draft', 'published', 'archived'] as $s): ?>
        <option value="<?php echo $s; ?>" <?php echo (($j['status'] ?? '') === $s) ? 'selected' : ''; ?>><?php echo __(ucfirst($s)); ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-sm btn-outline-primary" type="submit"><?php echo __('Set status'); ?></button>
  </form>
  <form method="post" onsubmit="return confirm('<?php echo __('Delete this journal and all its issues/articles?'); ?>');">
    <input type="hidden" name="form_action" value="delete">
    <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fas fa-trash"></i></button>
  </form>
</div>

<!-- Table of contents -->
<div class="card mb-4">
  <div class="card-header"><h5 class="mb-0"><i class="fas fa-list-ol me-2"></i><?php echo __('Table of contents'); ?></h5></div>
  <div class="card-body">
    <?php if (empty($toc)): ?>
      <p class="text-muted mb-0"><?php echo __('No issues or articles yet.'); ?></p>
    <?php else: ?>
      <?php foreach ($toc as $issue): ?>
        <div class="mb-3">
          <h6 class="border-bottom pb-1">
            <?php
            if ($issue['id'] === null) {
                echo __('Unassigned');
            } else {
                $label = trim('Vol ' . ($issue['volume'] ?? '') . ' No ' . ($issue['number'] ?? ''));
                echo htmlspecialchars($issue['title'] ?: $label);
                if (!empty($issue['issue_date'])) {
                    echo ' <small class="text-muted">' . htmlspecialchars((string) $issue['issue_date']) . '</small>';
                }
                echo ' ' . $badge($issue['status'] ?? 'draft');
            }
            ?>
            <?php if ($issue['id'] !== null): ?>
              <form method="post" class="d-inline" onsubmit="return confirm('<?php echo __('Remove this issue? Articles will be unassigned.'); ?>');">
                <input type="hidden" name="form_action" value="delete_issue">
                <input type="hidden" name="issue_id" value="<?php echo (int) $issue['id']; ?>">
                <button class="btn btn-sm btn-link text-danger p-0 ms-2" type="submit"><i class="fas fa-times"></i></button>
              </form>
            <?php endif; ?>
          </h6>
          <?php if (empty($issue['articles'])): ?>
            <p class="text-muted small mb-0"><?php echo __('No articles.'); ?></p>
          <?php else: ?>
            <ul class="list-unstyled mb-0">
              <?php foreach ($issue['articles'] as $a): ?>
                <li class="mb-1">
                  <a href="<?php echo url_for(['module' => 'researchjournal', 'action' => 'article', 'id' => $a['id']]); ?>"><?php echo htmlspecialchars((string) $a['title']); ?></a>
                  <?php if (!empty($a['authors'])): ?><span class="text-muted small"> — <?php echo htmlspecialchars((string) $a['authors']); ?></span><?php endif; ?>
                  <span class="text-muted small">(<?php echo (int) ($a['word_count'] ?? 0); ?> <?php echo __('words'); ?>)</span>
                  <?php echo $badge($a['status'] ?? 'draft'); ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php if (!$isManuscript): ?>
<!-- Add issue -->
<div class="card">
  <div class="card-header"><h6 class="mb-0"><i class="fas fa-plus me-2"></i><?php echo __('Add issue'); ?></h6></div>
  <div class="card-body">
    <form method="post" class="row g-2 align-items-end">
      <input type="hidden" name="form_action" value="add_issue">
      <div class="col-md-2"><label class="form-label"><?php echo __('Volume'); ?></label><input type="text" name="volume" class="form-control" maxlength="40"></div>
      <div class="col-md-2"><label class="form-label"><?php echo __('Number'); ?></label><input type="text" name="number" class="form-control" maxlength="40"></div>
      <div class="col-md-3"><label class="form-label"><?php echo __('Title'); ?></label><input type="text" name="issue_title" class="form-control" maxlength="255"></div>
      <div class="col-md-2"><label class="form-label"><?php echo __('Date'); ?></label><input type="date" name="issue_date" class="form-control"></div>
      <div class="col-md-2">
        <label class="form-label"><?php echo __('Status'); ?></label>
        <select name="status" class="form-select"><option value="draft"><?php echo __('Draft'); ?></option><option value="published"><?php echo __('Published'); ?></option></select>
      </div>
      <div class="col-md-1"><button class="btn btn-primary w-100" type="submit"><i class="fas fa-plus"></i></button></div>
    </form>
  </div>
</div>
<?php endif; ?>
<?php end_slot() ?>
