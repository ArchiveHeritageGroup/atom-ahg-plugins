<?php decorate_with('layout_1col'); ?>
<?php use_helper('Date'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Browse accessions'); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <div class="d-flex flex-wrap gap-2 mb-3">
    <?php echo get_component('search', 'inlineSearch', [
        'label' => __('Search accessions'),
        'landmarkLabel' => __('Accession'),
    ]); ?>

    <div class="d-flex flex-wrap gap-2 ms-auto">
      <?php echo get_partial('default/sortPickers', ['options' => $sortOptions]); ?>
    </div>
  </div>

  <div class="d-flex flex-wrap gap-2 mb-3">
    <a href="<?php echo url_for('@accession_intake_queue'); ?>" class="btn btn-sm btn-outline-primary">
      <i class="fas fa-inbox"></i> <?php echo __('Intake Queue'); ?>
    </a>
    <a href="<?php echo url_for('@accession_dashboard'); ?>" class="btn btn-sm btn-outline-secondary">
      <i class="fas fa-tachometer-alt"></i> <?php echo __('Dashboard'); ?>
    </a>
    <a href="<?php echo url_for('@accession_valuation_report'); ?>" class="btn btn-sm btn-outline-secondary">
      <i class="fas fa-chart-bar"></i> <?php echo __('Valuation Report'); ?>
    </a>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <div class="table-responsive mb-3">
    <table class="table table-bordered mb-0">
      <thead>
        <tr>
          <th>
            <?php echo __('Accession number'); ?>
          </th>
          <th>
            <?php echo __('Title'); ?>
          </th>
          <th>
            <?php echo __('Acquisition date'); ?>
          </th>
          <th>
            <?php echo __('Status'); ?>
          </th>
          <th>
            <?php echo __('Priority'); ?>
          </th>
          <?php if ('lastUpdated' == $sf_request->sort) { ?>
            <th>
              <?php echo __('Updated'); ?>
            </th>
          <?php } ?>
        </tr>
      </thead>
      <tbody>
        <?php $rawService = $sf_data->getRaw('browseService'); ?>
        <?php foreach ($sf_data->getRaw('pager')->getResults() as $doc) { ?>
          <?php $title = $rawService->extractI18nField($doc, 'title'); ?>
          <?php
            $statusBadge = '';
            $v2Status = $doc['v2_status'] ?? '';
            if ($v2Status) {
                $statusColors = [
                    'draft' => 'secondary',
                    'submitted' => 'info',
                    'under_review' => 'warning',
                    'accepted' => 'success',
                    'rejected' => 'danger',
                    'returned' => 'dark',
                ];
                $color = $statusColors[$v2Status] ?? 'secondary';
                $statusBadge = '<span class="badge bg-' . $color . '">' . htmlspecialchars(str_replace('_', ' ', ucfirst($v2Status))) . '</span>';
            }

            $priorityBadge = '';
            $v2Priority = $doc['v2_priority'] ?? '';
            if ($v2Priority) {
                $priorityColors = [
                    'low' => 'secondary',
                    'normal' => 'primary',
                    'high' => 'warning',
                    'urgent' => 'danger',
                ];
                $color = $priorityColors[$v2Priority] ?? 'secondary';
                $priorityBadge = '<span class="badge bg-' . $color . '">' . htmlspecialchars(ucfirst($v2Priority)) . '</span>';
            }
          ?>
          <tr>
            <td class="w-15">
              <?php echo link_to($doc['identifier'] ?? '', '@accession_view_override?slug=' . ($doc['slug'] ?? '')); ?>
            </td>
            <td>
              <?php echo link_to(render_title($title), '@accession_view_override?slug=' . ($doc['slug'] ?? '')); ?>
            </td>
            <td class="w-15">
              <?php echo isset($doc['date']) ? format_date($doc['date'], 'i') : ''; ?>
            </td>
            <td class="w-10">
              <?php echo $statusBadge; ?>
            </td>
            <td class="w-10">
              <?php echo $priorityBadge; ?>
            </td>
            <?php if ('lastUpdated' == $sf_request->sort) { ?>
              <td class="w-15">
                <?php echo isset($doc['updatedAt']) ? format_date($doc['updatedAt'], 'f') : ''; ?>
              </td>
            <?php } ?>
          </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>
<?php end_slot(); ?>

<?php slot('after-content'); ?>

  <?php echo get_partial('default/pager', ['pager' => $pager]); ?>

  <section class="actions mb-3">
    <?php echo link_to(__('Add new'), '@accession_add_override', ['class' => 'btn atom-btn-outline-light']); ?>
  </section>

<?php end_slot(); ?>
