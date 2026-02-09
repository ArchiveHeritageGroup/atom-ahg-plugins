<?php decorate_with('layout_1col'); ?>
<?php use_helper('Date'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('List users'); ?></h1>
<?php end_slot(); ?>

<?php slot('before-content'); ?>
  <div class="d-flex flex-wrap gap-2 mb-3">
    <?php echo get_component('search', 'inlineSearch', [
        'label' => __('Search users'),
        'landmarkLabel' => __('User'),
        'route' => url_for('@user_list_override'),
    ]); ?>

    <div class="d-flex flex-wrap gap-2 ms-auto">
      <?php echo get_partial('default/sortPickers', ['options' => $sortOptions]); ?>
    </div>
  </div>

  <ul class="nav nav-pills mb-3">
    <li class="nav-item">
      <?php $allParams = $sf_data->getRaw('sf_request')->getParameterHolder()->getAll(); ?>
      <?php $activeClass = ('onlyInactive' != $sf_request->filter) ? ' active' : ''; ?>
      <?php echo link_to(__('Show active only'), ['filter' => 'onlyActive'] + $allParams, ['class' => 'nav-link' . $activeClass]); ?>
    </li>
    <li class="nav-item">
      <?php $inactiveClass = ('onlyInactive' == $sf_request->filter) ? ' active' : ''; ?>
      <?php echo link_to(__('Show inactive only'), ['filter' => 'onlyInactive'] + $allParams, ['class' => 'nav-link' . $inactiveClass]); ?>
    </li>
  </ul>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <div class="table-responsive mb-3">
    <table class="table table-bordered mb-0">
      <thead>
        <tr>
          <th><?php echo __('User name'); ?></th>
          <th><?php echo __('Email'); ?></th>
          <th><?php echo __('User groups'); ?></th>
          <?php if ('username' != $sf_request->sort) { ?>
            <th><?php echo __('Updated'); ?></th>
          <?php } ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sf_data->getRaw('pager')->getResults() as $doc) { ?>
          <tr>
            <td>
              <?php echo link_to(esc_specialchars($doc['username']), '@user_view_override?slug=' . $doc['slug']); ?>
              <?php if (!$doc['active']) { ?>
                (<?php echo __('inactive'); ?>)
              <?php } ?>
              <?php if ($sf_user->user && $sf_user->user->id == $doc['id']) { ?>
                (<?php echo __('you'); ?>)
              <?php } ?>
            </td>
            <td>
              <?php echo esc_specialchars($doc['email']); ?>
            </td>
            <td>
              <?php echo esc_specialchars($doc['groups']); ?>
            </td>
            <?php if ('username' != $sf_request->sort) { ?>
              <td>
                <?php echo !empty($doc['updated_at']) ? format_date($doc['updated_at'], 'f') : ''; ?>
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
    <?php echo link_to(__('Add new'), '@user_add_override', ['class' => 'btn atom-btn-outline-light']); ?>
  </section>

<?php end_slot(); ?>
