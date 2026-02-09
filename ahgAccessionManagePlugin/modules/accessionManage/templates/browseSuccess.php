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
          <tr>
            <td class="w-20">
              <?php echo link_to($doc['identifier'] ?? '', '@accession_view_override?slug=' . ($doc['slug'] ?? '')); ?>
            </td>
            <td>
              <?php echo link_to(render_title($title), '@accession_view_override?slug=' . ($doc['slug'] ?? '')); ?>
            </td>
            <td class="w-20">
              <?php echo isset($doc['date']) ? format_date($doc['date'], 'i') : ''; ?>
            </td>
            <?php if ('lastUpdated' == $sf_request->sort) { ?>
              <td class="w-20">
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
