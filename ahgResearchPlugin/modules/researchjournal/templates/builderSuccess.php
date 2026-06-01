<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive ?? 'journal', 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php
$j = is_array($journal_record) ? $journal_record : [];
$isManuscript = ($kind === 'manuscript');
$isNew = empty($j);
$targetJournals = is_array($targetJournals) ? $targetJournals : [];
$val = function ($k) use ($j) { return htmlspecialchars((string) ($j[$k] ?? '')); };
?>
<?php slot('title') ?>
<h1>
  <i class="fas <?php echo $isManuscript ? 'fa-file-alt' : 'fa-newspaper'; ?> text-primary me-2"></i>
  <?php echo $isNew ? ($isManuscript ? __('New manuscript') : __('New journal')) : __('Edit details'); ?>
</h1>
<?php end_slot() ?>

<?php slot('content') ?>
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'researchjournal', 'action' => 'index']); ?>"><?php echo __('Journal Builder'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo $isNew ? __('New') : $val('title'); ?></li>
  </ol>
</nav>

<form method="post">
  <input type="hidden" name="kind" value="<?php echo htmlspecialchars($kind); ?>">

  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label"><?php echo __('Title'); ?> <span class="text-danger">*</span></label>
          <input type="text" name="title" class="form-control" required maxlength="255" value="<?php echo $val('title'); ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label"><?php echo __('Subtitle'); ?></label>
          <input type="text" name="subtitle" class="form-control" maxlength="255" value="<?php echo $val('subtitle'); ?>">
        </div>

        <?php if (!$isManuscript): ?>
          <div class="col-md-3">
            <label class="form-label"><?php echo __('ISSN'); ?></label>
            <input type="text" name="issn" class="form-control" maxlength="20" value="<?php echo $val('issn'); ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label"><?php echo __('eISSN'); ?></label>
            <input type="text" name="eissn" class="form-control" maxlength="20" value="<?php echo $val('eissn'); ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label"><?php echo __('Publisher'); ?></label>
            <input type="text" name="publisher" class="form-control" maxlength="255" value="<?php echo $val('publisher'); ?>">
          </div>
          <div class="col-12">
            <label class="form-label"><?php echo __('Aims & scope'); ?></label>
            <textarea name="aims_scope" class="form-control" rows="3"><?php echo $val('aims_scope'); ?></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label"><?php echo __('Editor name'); ?></label>
            <input type="text" name="editor_name" class="form-control" maxlength="255" value="<?php echo $val('editor_name'); ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label"><?php echo __('Editor email'); ?></label>
            <input type="email" name="editor_email" class="form-control" maxlength="255" value="<?php echo $val('editor_email'); ?>">
          </div>
        <?php else: ?>
          <div class="col-md-6">
            <label class="form-label"><?php echo __('Target journal'); ?></label>
            <?php if (!empty($targetJournals)): ?>
              <select name="target_journal_id" class="form-select">
                <option value=""><?php echo __('— None —'); ?></option>
                <?php foreach ($targetJournals as $tj): ?>
                  <option value="<?php echo (int) $tj['id']; ?>" <?php echo (isset($j['target_journal_id']) && (int) $j['target_journal_id'] === (int) $tj['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars((string) $tj['title']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <input type="number" name="target_journal_id" class="form-control" value="<?php echo $val('target_journal_id'); ?>">
              <small class="text-muted"><?php echo __('Target-journal directory (#114) not installed — enter an id manually if known.'); ?></small>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <div class="col-12">
          <label class="form-label"><?php echo __('Description'); ?></label>
          <textarea name="description" class="form-control" rows="3"><?php echo $val('description'); ?></textarea>
        </div>
        <div class="col-md-4">
          <label class="form-label"><?php echo __('DOI'); ?></label>
          <input type="text" name="doi" class="form-control" maxlength="128" value="<?php echo $val('doi'); ?>">
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i><?php echo __('Save'); ?></button>
    <a href="<?php echo url_for(['module' => 'researchjournal', 'action' => 'index']); ?>" class="btn btn-secondary"><?php echo __('Cancel'); ?></a>
  </div>
</form>
<?php end_slot() ?>
