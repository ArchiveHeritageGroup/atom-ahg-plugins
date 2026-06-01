<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive ?? 'targetJournals', 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>

<?php $journal = isset($journal) && $journal ? (array) $journal : null; ?>

<?php slot('title') ?>
<h1 class="mb-0"><i class="fas fa-bullseye text-primary me-2"></i><?php echo $journal ? __('Edit') : __('Add'); ?> <?php echo __('Target Journal'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>"><?php echo __('Research'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'targetJournals']); ?>"><?php echo __('Where to Publish'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo $journal ? __('Edit') : __('Add'); ?></li>
  </ol>
</nav>

<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<?php
$styles = isset($styles) && is_array($styles) ? $styles : ['APA', 'Harvard', 'Vancouver', 'Chicago', 'MLA', 'IEEE'];
$v = function ($key, $default = '') use ($journal) {
    return htmlspecialchars((string) ($journal[$key] ?? $default));
};
$formUrl = $journal
    ? url_for(['module' => 'research', 'action' => 'targetJournalBuilder', 'id' => $journal['id']])
    : url_for(['module' => 'research', 'action' => 'targetJournalBuilder']);
?>

<div style="max-width: 860px;">
  <form method="post" action="<?php echo $formUrl; ?>">
    <div class="mb-3"><label class="form-label"><?php echo __('Title'); ?> <span class="text-danger">*</span></label>
      <input name="title" class="form-control" required value="<?php echo $v('title'); ?>"></div>

    <div class="row">
      <div class="col-md-6 mb-3"><label class="form-label"><?php echo __('Publisher'); ?></label>
        <input name="publisher" class="form-control" value="<?php echo $v('publisher'); ?>"></div>
      <div class="col-md-3 mb-3"><label class="form-label">ISSN</label>
        <input name="issn" class="form-control" value="<?php echo $v('issn'); ?>"></div>
      <div class="col-md-3 mb-3"><label class="form-label">eISSN</label>
        <input name="eissn" class="form-control" value="<?php echo $v('eissn'); ?>"></div>
    </div>

    <div class="mb-3"><label class="form-label"><?php echo __('Subject scope (what it mainly accepts)'); ?></label>
      <textarea name="subject_scope" rows="3" class="form-control"><?php echo $v('subject_scope'); ?></textarea></div>

    <div class="row">
      <div class="col-md-6 mb-3"><label class="form-label"><?php echo __('Article types'); ?></label>
        <input name="article_types" class="form-control" value="<?php echo $v('article_types'); ?>" placeholder="research, review, case study"></div>
      <div class="col-md-6 mb-3"><label class="form-label"><?php echo __('Languages'); ?></label>
        <input name="languages" class="form-control" value="<?php echo $v('languages'); ?>"></div>
    </div>

    <div class="row">
      <div class="col-md-8 mb-3"><label class="form-label"><?php echo __('Accreditation / indexing'); ?></label>
        <input name="accreditation" class="form-control" value="<?php echo $v('accreditation'); ?>" placeholder="DHET, Scopus, Web of Science, DOAJ, Sabinet"></div>
      <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('Accreditation market'); ?></label>
        <input name="accreditation_market" class="form-control" value="<?php echo $v('accreditation_market'); ?>" placeholder="ZA"></div>
    </div>

    <hr><h2 class="h6"><?php echo __('Submission rules'); ?></h2>
    <div class="row">
      <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('Reference style'); ?></label>
        <select name="reference_style" class="form-select">
          <option value=""><?php echo __('— none —'); ?></option>
          <?php foreach ($styles as $s): ?>
            <option value="<?php echo htmlspecialchars($s); ?>" <?php echo (($journal['reference_style'] ?? '') === $s) ? 'selected' : ''; ?>><?php echo htmlspecialchars($s); ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('Max words'); ?></label>
        <input type="number" name="max_words" class="form-control" value="<?php echo $v('max_words'); ?>"></div>
      <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('Abstract max words'); ?></label>
        <input type="number" name="abstract_max_words" class="form-control" value="<?php echo $v('abstract_max_words'); ?>"></div>
    </div>

    <div class="mb-3"><label class="form-label"><?php echo __('Structure / required sections'); ?></label>
      <textarea name="structure_notes" rows="2" class="form-control"><?php echo $v('structure_notes'); ?></textarea></div>

    <div class="row">
      <div class="col-md-4 mb-3"><label class="form-label"><?php echo __('Peer review'); ?></label>
        <input name="peer_review" class="form-control" value="<?php echo $v('peer_review'); ?>" placeholder="double-blind"></div>
      <div class="col-md-2 mb-3"><label class="form-label"><?php echo __('Open access'); ?></label>
        <select name="open_access" class="form-select">
          <option value="0" <?php echo empty($journal['open_access']) ? 'selected' : ''; ?>><?php echo __('No'); ?></option>
          <option value="1" <?php echo !empty($journal['open_access']) ? 'selected' : ''; ?>><?php echo __('Yes'); ?></option>
        </select></div>
      <div class="col-md-3 mb-3"><label class="form-label">APC</label>
        <input name="apc_amount" class="form-control" value="<?php echo $v('apc_amount'); ?>"></div>
      <div class="col-md-3 mb-3"><label class="form-label"><?php echo __('Turnaround'); ?></label>
        <input name="turnaround" class="form-control" value="<?php echo $v('turnaround'); ?>"></div>
    </div>

    <div class="row">
      <div class="col-md-6 mb-3"><label class="form-label"><?php echo __('Homepage URL'); ?></label>
        <input name="homepage_url" class="form-control" value="<?php echo $v('homepage_url'); ?>"></div>
      <div class="col-md-6 mb-3"><label class="form-label"><?php echo __('Submission URL'); ?></label>
        <input name="submission_url" class="form-control" value="<?php echo $v('submission_url'); ?>"></div>
    </div>

    <div class="mb-3"><label class="form-label"><?php echo __('Notes'); ?></label>
      <textarea name="notes" rows="2" class="form-control"><?php echo $v('notes'); ?></textarea></div>

    <div class="mb-3"><label class="form-label"><?php echo __('Status'); ?></label>
      <select name="status" class="form-select">
        <?php foreach (['active', 'discontinued'] as $s): ?>
          <option value="<?php echo $s; ?>" <?php echo (($journal['status'] ?? 'active') === $s) ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
        <?php endforeach; ?>
      </select></div>

    <div class="d-flex gap-2">
      <button class="btn btn-primary"><?php echo __('Save'); ?></button>
      <a href="<?php echo $journal ? url_for(['module' => 'research', 'action' => 'targetJournalShow', 'id' => $journal['id']]) : url_for(['module' => 'research', 'action' => 'targetJournals']); ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
    </div>
  </form>
</div>
<?php end_slot() ?>
