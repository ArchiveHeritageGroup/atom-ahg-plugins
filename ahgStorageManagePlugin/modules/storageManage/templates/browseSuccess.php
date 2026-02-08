<?php decorate_with('layout_1col'); ?>
<?php use_helper('Date'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Browse %1%', ['%1%' => sfConfig::get('app_ui_label_physicalobject')]); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <div class="d-inline-block mb-3">
    <?php echo get_component('search', 'inlineSearch', [
        'label' => __('Search %1%', ['%1%' => strtolower(sfConfig::get('app_ui_label_physicalobject'))]),
        'landmarkLabel' => __(sfConfig::get('app_ui_label_physicalobject')),
    ]); ?>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <div class="table-responsive mb-3">
    <table class="table table-bordered mb-0">
      <thead>
        <tr>
          <th class="sortable">
            <?php echo link_to(__('Name'), ['sort' => ('nameUp' == $sf_request->sort) ? 'nameDown' : 'nameUp'] + $sf_data->getRaw('sf_request')->getParameterHolder()->getAll(), ['title' => __('Sort'), 'class' => 'sortable']); ?>
            <?php if ('nameUp' == $sf_request->sort) { ?>
              <i class="fas fa-sort-up" aria-hidden="true"></i>
              <span class="visually-hidden"><?php echo __('Sort ascending'); ?></span>
            <?php } elseif ('nameDown' == $sf_request->sort) { ?>
              <i class="fas fa-sort-down" aria-hidden="true"></i>
              <span class="visually-hidden"><?php echo __('Sort descending'); ?></span>
            <?php } ?>
          </th>
          <th class="sortable">
            <?php echo link_to(__('Location'), ['sort' => ('locationUp' == $sf_request->sort) ? 'locationDown' : 'locationUp'] + $sf_data->getRaw('sf_request')->getParameterHolder()->getAll(), ['title' => __('Sort'), 'class' => 'sortable']); ?>
            <?php if ('locationUp' == $sf_request->sort) { ?>
              <i class="fas fa-sort-up" aria-hidden="true"></i>
              <span class="visually-hidden"><?php echo __('Sort ascending'); ?></span>
            <?php } elseif ('locationDown' == $sf_request->sort) { ?>
              <i class="fas fa-sort-down" aria-hidden="true"></i>
              <span class="visually-hidden"><?php echo __('Sort descending'); ?></span>
            <?php } ?>
          </th>
          <th>
            <?php echo __('Type'); ?>
          </th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sf_data->getRaw('pager')->getResults() as $doc) { ?>
          <tr>
            <td>
              <?php echo link_to(render_title($doc['name']), ['module' => 'physicalobject', 'slug' => $doc['slug']]); ?>
            </td>
            <td>
              <?php echo esc_entities($doc['location']); ?>
            </td>
            <td>
              <?php echo esc_entities($doc['type_name']); ?>
            </td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>
<?php end_slot(); ?>

<?php slot('after-content'); ?>
  <?php echo get_partial('default/pager', ['pager' => $pager]); ?>

  <?php if ($sf_user->hasCredential(['contributor', 'editor', 'administrator'], false)) { ?>
    <ul class="actions mb-3 nav gap-2">
      <li><?php echo link_to(__('Add new'), ['module' => 'physicalobject', 'action' => 'add'], ['class' => 'btn atom-btn-outline-light']); ?></li>
      <li><?php echo link_to(__('Export storage report'), ['module' => 'physicalobject', 'action' => 'holdingsReportExport'], ['class' => 'btn atom-btn-outline-light']); ?></li>
    </ul>
  <?php } ?>
<?php end_slot(); ?>
