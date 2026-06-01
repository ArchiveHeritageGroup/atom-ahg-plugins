<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive ?? 'journal', 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php
$j = is_array($journal_record) ? $journal_record : [];
$a = is_array($article) ? $article : [];
$issues = is_array($issues) ? $issues : [];
$styles = is_array($styles) ? $styles : [];
$targetJournals = is_array($targetJournals) ? $targetJournals : [];
$validation = is_array($validation) ? $validation : [];
$isManuscript = (($j['kind'] ?? '') === 'manuscript');
$jid = (int) ($j['id'] ?? 0);
$isNew = empty($a);
$val = function ($k) use ($a) { return htmlspecialchars((string) ($a[$k] ?? '')); };
?>
<?php slot('title') ?>
<h1><i class="fas fa-file-lines text-primary me-2"></i><?php echo $isNew ? __('New article') : __('Edit article'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'researchjournal', 'action' => 'index']); ?>"><?php echo __('Journal Builder'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'researchjournal', 'action' => 'show', 'id' => $jid]); ?>"><?php echo htmlspecialchars((string) ($j['title'] ?? '')); ?></a></li>
    <li class="breadcrumb-item active"><?php echo $isNew ? __('New article') : $val('title'); ?></li>
  </ol>
</nav>

<?php if ($isManuscript && !empty($validation)): ?>
  <div class="alert alert-warning">
    <strong><i class="fas fa-exclamation-triangle me-1"></i><?php echo __('Submission checklist'); ?></strong>
    <ul class="mb-0 mt-2">
      <?php foreach ($validation as $problem): ?><li><?php echo htmlspecialchars((string) $problem); ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php elseif ($isManuscript && !$isNew): ?>
  <div class="alert alert-success"><i class="fas fa-check-circle me-1"></i><?php echo __('Manuscript passes all completeness and target-journal checks.'); ?></div>
<?php endif; ?>

<form method="post">
  <div class="row g-3">
    <div class="col-md-9">
      <div class="card mb-3">
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Title'); ?> <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" required maxlength="500" value="<?php echo $val('title'); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Authors'); ?></label>
            <input type="text" name="authors" class="form-control" value="<?php echo $val('authors'); ?>" placeholder="<?php echo __('e.g. Smith, J.; Doe, A.'); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Abstract'); ?></label>
            <textarea name="abstract" class="form-control" rows="3"><?php echo $val('abstract'); ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Keywords'); ?></label>
            <input type="text" name="keywords" class="form-control" maxlength="500" value="<?php echo $val('keywords'); ?>" placeholder="<?php echo __('Comma-separated'); ?>">
          </div>
          <div class="mb-2">
            <label class="form-label"><?php echo __('Body (Markdown)'); ?></label>
            <textarea name="body_markdown" class="form-control font-monospace" rows="16"><?php echo htmlspecialchars((string) ($a['body_markdown'] ?? '')); ?></textarea>
            <small class="text-muted"><?php echo __('Markdown is rendered to HTML and word-counted on save.'); ?></small>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="card mb-3">
        <div class="card-header"><h6 class="mb-0"><?php echo __('Placement'); ?></h6></div>
        <div class="card-body">
          <?php if (!$isManuscript): ?>
            <div class="mb-3">
              <label class="form-label"><?php echo __('Issue'); ?></label>
              <select name="issue_id" class="form-select">
                <option value=""><?php echo __('— Unassigned —'); ?></option>
                <?php foreach ($issues as $iss): ?>
                  <option value="<?php echo (int) $iss['id']; ?>" <?php echo (isset($a['issue_id']) && (int) $a['issue_id'] === (int) $iss['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(trim($iss['title'] ?: ('Vol ' . ($iss['volume'] ?? '') . ' No ' . ($iss['number'] ?? '')))); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Reference style'); ?></label>
            <select name="reference_style" class="form-select">
              <option value=""><?php echo __('— None —'); ?></option>
              <?php foreach ($styles as $style): ?>
                <option value="<?php echo htmlspecialchars($style); ?>" <?php echo (($a['reference_style'] ?? '') === $style) ? 'selected' : ''; ?>><?php echo htmlspecialchars($style); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <?php if ($isManuscript): ?>
            <div class="mb-3">
              <label class="form-label"><?php echo __('Target journal'); ?></label>
              <?php if (!empty($targetJournals)): ?>
                <select name="target_journal_id" class="form-select">
                  <option value=""><?php echo __('— None —'); ?></option>
                  <?php foreach ($targetJournals as $tj): ?>
                    <option value="<?php echo (int) $tj['id']; ?>" <?php echo (isset($a['target_journal_id']) && (int) $a['target_journal_id'] === (int) $tj['id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars((string) $tj['title']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              <?php else: ?>
                <input type="number" name="target_journal_id" class="form-control" value="<?php echo $val('target_journal_id'); ?>">
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label"><?php echo __('Status'); ?></label>
            <select name="status" class="form-select">
              <?php foreach (['draft', 'submitted', 'published'] as $s): ?>
                <option value="<?php echo $s; ?>" <?php echo (($a['status'] ?? 'draft') === $s) ? 'selected' : ''; ?>><?php echo __(ucfirst($s)); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label"><?php echo __('DOI'); ?></label>
            <input type="text" name="doi" class="form-control" maxlength="128" value="<?php echo $val('doi'); ?>">
          </div>
          <div class="mb-2">
            <label class="form-label"><?php echo __('Sort order'); ?></label>
            <input type="number" name="sort_order" class="form-control" value="<?php echo (int) ($a['sort_order'] ?? 0); ?>">
          </div>
          <?php if (!$isNew): ?>
            <p class="text-muted small mb-0"><?php echo __('Word count'); ?>: <strong><?php echo (int) ($a['word_count'] ?? 0); ?></strong></p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i><?php echo __('Save article'); ?></button>
    <a href="<?php echo url_for(['module' => 'researchjournal', 'action' => 'show', 'id' => $jid]); ?>" class="btn btn-secondary"><?php echo __('Cancel'); ?></a>
    <?php if (!$isNew): ?>
      <span class="ms-auto"></span>
    <?php endif; ?>
  </div>
</form>

<?php if (!$isNew): ?>
  <form method="post" class="mt-3" onsubmit="return confirm('<?php echo __('Delete this article?'); ?>');">
    <input type="hidden" name="form_action" value="delete">
    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash me-1"></i><?php echo __('Delete article'); ?></button>
  </form>

  <?php if (!empty($a['body_html'])): ?>
    <div class="card mt-4">
      <div class="card-header"><h6 class="mb-0"><i class="fas fa-eye me-2"></i><?php echo __('Preview'); ?></h6></div>
      <div class="card-body"><?php echo $a['body_html']; ?></div>
    </div>
  <?php endif; ?>
<?php endif; ?>
<?php end_slot() ?>
