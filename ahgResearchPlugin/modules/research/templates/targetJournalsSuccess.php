<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive ?? 'targetJournals', 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>

<?php slot('title') ?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
  <h1 class="mb-0"><i class="fas fa-bullseye text-primary me-2"></i><?php echo __('Where to Publish'); ?>
    <small class="text-muted"><?php echo __('(target-journal directory)'); ?></small></h1>
  <div class="d-flex gap-2">
    <a href="<?php echo url_for(['module' => 'research', 'action' => 'targetJournalBuilder']); ?>" class="btn btn-primary">
      <i class="fas fa-plus me-1"></i><?php echo __('Add Journal'); ?>
    </a>
    <form action="<?php echo url_for(['module' => 'research', 'action' => 'targetJournalSeedDhet']); ?>" method="post"
          onsubmit="return confirm('<?php echo __('Seed/refresh the DHET-accredited starter set (SA accreditation module)?'); ?>')">
      <button class="btn btn-outline-secondary"><i class="fas fa-seedling me-1"></i><?php echo __('Seed DHET starter'); ?></button>
    </form>
  </div>
</div>
<?php end_slot() ?>

<?php slot('content') ?>
<?php
$journals = isset($journals) && is_array($journals) ? $journals
    : (isset($journals) && is_iterable($journals) ? iterator_to_array($journals) : []);
?>
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>"><?php echo __('Research'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Where to Publish'); ?></li>
  </ol>
</nav>

<?php if ($sf_user->hasFlash('success')): ?>
  <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<form method="get" class="row g-2 mb-3">
  <div class="col-md-6"><input name="q" value="<?php echo htmlspecialchars($q ?? ''); ?>" class="form-control" placeholder="<?php echo __('Search title, scope, publisher, indexing…'); ?>"></div>
  <div class="col-md-3"><input name="market" value="<?php echo htmlspecialchars($market ?? ''); ?>" class="form-control" placeholder="<?php echo __('Market (e.g. ZA)'); ?>"></div>
  <div class="col-md-3"><button class="btn btn-outline-secondary w-100"><?php echo __('Filter'); ?></button></div>
</form>

<div class="card"><div class="card-body p-0">
  <?php if (count($journals)): ?>
    <table class="table mb-0 align-middle">
      <thead><tr>
        <th><?php echo __('Journal'); ?></th>
        <th><?php echo __('Scope'); ?></th>
        <th><?php echo __('Indexing'); ?></th>
        <th><?php echo __('Style'); ?></th>
        <th class="text-end"><?php echo __('Actions'); ?></th>
      </tr></thead>
      <tbody>
        <?php foreach ($journals as $j): ?>
          <?php $j = (array) $j; ?>
          <tr>
            <td>
              <a href="<?php echo url_for(['module' => 'research', 'action' => 'targetJournalShow', 'id' => $j['id']]); ?>"><?php echo htmlspecialchars($j['title']); ?></a>
              <?php if (!empty($j['open_access'])): ?><span class="badge bg-success ms-1">OA</span><?php endif; ?>
              <?php if (!empty($j['publisher'])): ?><br><small class="text-muted"><?php echo htmlspecialchars($j['publisher']); ?></small><?php endif; ?>
            </td>
            <td class="text-muted small"><?php echo htmlspecialchars(mb_strimwidth((string) ($j['subject_scope'] ?? ''), 0, 90, '…')); ?></td>
            <td class="small"><?php echo htmlspecialchars((string) ($j['accreditation'] ?? '')); ?></td>
            <td class="small"><?php echo htmlspecialchars($j['reference_style'] ?: '—'); ?></td>
            <td class="text-end">
              <a href="<?php echo url_for(['module' => 'research', 'action' => 'targetJournalBuilder', 'id' => $j['id']]); ?>" class="btn btn-sm btn-outline-secondary"><?php echo __('Edit'); ?></a>
              <form action="<?php echo url_for(['module' => 'research', 'action' => 'targetJournalDelete', 'id' => $j['id']]); ?>" method="post" class="d-inline"
                    onsubmit="return confirm('<?php echo __('Remove from directory?'); ?>')">
                <button class="btn btn-sm btn-outline-danger"><?php echo __('Delete'); ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="text-muted m-3"><?php echo __('The directory is empty. Use “Seed DHET starter” to load the South-African accreditation set, or add journals manually.'); ?></p>
  <?php endif; ?>
</div></div>
<p class="form-text mt-2"><?php echo __('The directory core is jurisdiction-neutral; the DHET list is the South-African accreditation module. Other markets seed from DOAJ / Scopus / Web of Science.'); ?></p>
<?php end_slot() ?>
