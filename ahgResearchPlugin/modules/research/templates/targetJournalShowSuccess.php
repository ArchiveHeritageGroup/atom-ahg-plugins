<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive ?? 'targetJournals', 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>

<?php $journal = (array) $journal; ?>

<?php slot('title') ?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
  <div>
    <h1 class="mb-0"><?php echo htmlspecialchars($journal['title']); ?></h1>
    <?php if (!empty($journal['publisher'])): ?><div class="text-muted"><?php echo htmlspecialchars($journal['publisher']); ?></div><?php endif; ?>
    <small class="text-muted">
      <?php if (!empty($journal['issn'])): ?>ISSN <?php echo htmlspecialchars($journal['issn']); ?><?php endif; ?>
      <?php if (!empty($journal['eissn'])): ?> · eISSN <?php echo htmlspecialchars($journal['eissn']); ?><?php endif; ?>
      <?php if (!empty($journal['open_access'])): ?> · <span class="badge bg-success"><?php echo __('Open access'); ?></span><?php endif; ?>
      <?php if (($journal['status'] ?? 'active') !== 'active'): ?> · <span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst($journal['status'])); ?></span><?php endif; ?>
    </small>
  </div>
  <a href="<?php echo url_for(['module' => 'research', 'action' => 'targetJournalBuilder', 'id' => $journal['id']]); ?>" class="btn btn-outline-secondary"><?php echo __('Edit'); ?></a>
</div>
<?php end_slot() ?>

<?php slot('content') ?>
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>"><?php echo __('Research'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'targetJournals']); ?>"><?php echo __('Where to Publish'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo htmlspecialchars($journal['title']); ?></li>
  </ol>
</nav>

<?php if ($sf_user->hasFlash('success')): ?>
  <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
<?php endif; ?>

<div style="max-width: 820px;">
  <?php if (!empty($journal['subject_scope'])): ?>
    <h2 class="h6 mt-3"><?php echo __('Scope — what it accepts'); ?></h2>
    <p><?php echo nl2br(htmlspecialchars($journal['subject_scope'])); ?></p>
  <?php endif; ?>

  <h2 class="h6 mt-3"><?php echo __('Submission rules'); ?></h2>
  <table class="table table-sm">
    <tbody>
      <?php
      $rows = [
          __('Article types')                  => $journal['article_types'] ?? null,
          __('Accreditation / indexing')       => $journal['accreditation'] ?? null,
          __('Accreditation market')           => $journal['accreditation_market'] ?? null,
          __('Reference style')                => $journal['reference_style'] ?? null,
          __('Max words')                      => $journal['max_words'] ?? null,
          __('Abstract max words')             => $journal['abstract_max_words'] ?? null,
          __('Structure / required sections')  => $journal['structure_notes'] ?? null,
          __('Peer review')                    => $journal['peer_review'] ?? null,
          __('APC')                            => $journal['apc_amount'] ?? null,
          __('Turnaround')                     => $journal['turnaround'] ?? null,
          __('Languages')                      => $journal['languages'] ?? null,
      ];
      ?>
      <?php foreach ($rows as $label => $val): ?>
        <?php if ($val !== null && $val !== ''): ?>
          <tr><th class="text-muted" style="width: 220px;"><?php echo $label; ?></th><td><?php echo nl2br(htmlspecialchars((string) $val)); ?></td></tr>
        <?php endif; ?>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php if (!empty($journal['notes'])): ?>
    <p class="text-muted"><em><?php echo nl2br(htmlspecialchars($journal['notes'])); ?></em></p>
  <?php endif; ?>

  <div class="d-flex gap-3">
    <?php if (!empty($journal['homepage_url'])): ?>
      <a href="<?php echo htmlspecialchars($journal['homepage_url']); ?>" target="_blank" rel="noopener"><i class="fas fa-globe me-1"></i><?php echo __('Homepage'); ?></a>
    <?php endif; ?>
    <?php if (!empty($journal['submission_url'])): ?>
      <a href="<?php echo htmlspecialchars($journal['submission_url']); ?>" target="_blank" rel="noopener"><i class="fas fa-paper-plane me-1"></i><?php echo __('Submit'); ?></a>
    <?php endif; ?>
  </div>

  <p class="mt-3"><a href="<?php echo url_for(['module' => 'research', 'action' => 'targetJournals']); ?>" class="btn btn-outline-secondary btn-sm">&larr; <?php echo __('Back to directory'); ?></a></p>
</div>
<?php end_slot() ?>
