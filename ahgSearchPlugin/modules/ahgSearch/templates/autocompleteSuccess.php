<?php $addDivider = false; ?>
<?php if ($descriptions['total'] > 0) { ?>
  <li>
    <h6 class="dropdown-header">
      <i class="fas fa-lg fa-file-alt me-2" aria-hidden="true"></i>
      <?php echo sfConfig::get('app_ui_label_informationobject'); ?>
    </h6>
  </li>
  <?php foreach ($descriptions['hits'] as $doc) { ?>
    <li>
      <?php echo link_to(
          render_title(get_search_i18n($doc, 'title'))
          .((isset($doc['levelOfDescriptionId']) && $doc['levelOfDescriptionId'])
              ? ' <span class="text-muted fst-italic">'
                  .($levelsOfDescription[$doc['levelOfDescriptionId']] ?? '')
                  .'</span>'
              : ''
          ),
          ['module' => 'informationobject', 'slug' => $doc['slug'] ?? ''],
          ['class' => 'dropdown-item text-wrap']
      ); ?>
    </li>
  <?php } ?>
  <?php if ($descriptions['total'] > 3) { ?>
    <li>
      <?php echo link_to(
          __('all matching descriptions'),
          ['module' => 'informationobject', 'action' => 'browse', 'topLod' => '0']
              + $sf_data->getRaw('allMatchingIoParams'),
          ['class' => 'dropdown-item text-wrap text-muted fst-italic']
      ); ?>
    </li>
  <?php } ?>
  <?php $addDivider = true; ?>
<?php } ?>

<?php if ($repositories['total'] > 0) { ?>
  <?php if ($addDivider) { ?>
    <li><hr class="dropdown-divider"></li>
  <?php } ?>
  <li>
    <h6 class="dropdown-header">
      <i class="fas fa-lg fa-university me-2" aria-hidden="true"></i>
      <?php echo sfConfig::get('app_ui_label_repository'); ?>
    </h6>
  </li>
  <?php foreach ($repositories['hits'] as $doc) { ?>
    <li>
      <?php echo link_to(
          render_title(get_search_i18n($doc, 'authorizedFormOfName')),
          ['module' => 'repository', 'slug' => $doc['slug'] ?? ''],
          ['class' => 'dropdown-item text-wrap']
      ); ?>
    </li>
  <?php } ?>
  <?php if ($repositories['total'] > 3) { ?>
    <li>
      <?php echo link_to(
          __('all matching institutions'),
          ['module' => 'repository', 'action' => 'browse']
              + $sf_data->getRaw('allMatchingParams'),
          ['class' => 'dropdown-item text-wrap text-muted fst-italic']
      ); ?>
    </li>
  <?php } ?>
  <?php $addDivider = true; ?>
<?php } ?>

<?php if ($actors['total'] > 0) { ?>
  <?php if ($addDivider) { ?>
    <li><hr class="dropdown-divider"></li>
  <?php } ?>
  <li>
    <h6 class="dropdown-header">
      <i class="fas fa-lg fa-user me-2" aria-hidden="true"></i>
      <?php echo sfConfig::get('app_ui_label_actor'); ?>
    </h6>
  </li>
  <?php foreach ($actors['hits'] as $doc) { ?>
    <li>
      <?php echo link_to(
          render_title(get_search_i18n($doc, 'authorizedFormOfName')),
          ['module' => 'actor', 'slug' => $doc['slug'] ?? ''],
          ['class' => 'dropdown-item text-wrap']
      ); ?>
    </li>
  <?php } ?>
  <?php if ($actors['total'] > 3) { ?>
    <li>
      <?php echo link_to(
          __('all matching people & organizations'),
          ['module' => 'actor', 'action' => 'browse']
              + $sf_data->getRaw('allMatchingParams'),
          ['class' => 'dropdown-item text-wrap text-muted fst-italic']
      ); ?>
    </li>
  <?php } ?>
  <?php $addDivider = true; ?>
<?php } ?>

<?php if ($places['total'] > 0) { ?>
  <?php if ($addDivider) { ?>
    <li><hr class="dropdown-divider"></li>
  <?php } ?>
  <li>
    <h6 class="dropdown-header">
      <i class="fas fa-lg fa-map-marker-alt me-2" aria-hidden="true"></i>
      <?php echo sfConfig::get('app_ui_label_place'); ?>
    </h6>
  </li>
  <?php foreach ($places['hits'] as $doc) { ?>
    <li>
      <?php echo link_to(
          render_title(get_search_i18n($doc, 'name')),
          ['module' => 'term', 'slug' => $doc['slug'] ?? ''],
          ['class' => 'dropdown-item text-wrap']
      ); ?>
    </li>
  <?php } ?>
  <?php if ($places['total'] > 3) { ?>
    <li>
      <?php echo link_to(
          __('all matching places'),
          ['module' => 'taxonomy', 'action' => 'index', 'slug' => 'places', 'subqueryField' => 'allLabels']
              + $sf_data->getRaw('allMatchingParams'),
          ['class' => 'dropdown-item text-wrap text-muted fst-italic']
      ); ?>
    </li>
  <?php } ?>
  <?php $addDivider = true; ?>
<?php } ?>

<?php if ($subjects['total'] > 0) { ?>
  <?php if ($addDivider) { ?>
    <li><hr class="dropdown-divider"></li>
  <?php } ?>
  <li>
    <h6 class="dropdown-header">
      <i class="fas fa-lg fa-tag me-2" aria-hidden="true"></i>
      <?php echo sfConfig::get('app_ui_label_subject'); ?>
    </h6>
  </li>
  <?php foreach ($subjects['hits'] as $doc) { ?>
    <li>
      <?php echo link_to(
          render_title(get_search_i18n($doc, 'name')),
          ['module' => 'term', 'slug' => $doc['slug'] ?? ''],
          ['class' => 'dropdown-item text-wrap']
      ); ?>
    </li>
  <?php } ?>
  <?php if ($subjects['total'] > 3) { ?>
    <li>
      <?php echo link_to(
          __('all matching subjects'),
          ['module' => 'taxonomy', 'action' => 'index', 'slug' => 'subjects', 'subqueryField' => 'allLabels']
              + $sf_data->getRaw('allMatchingParams'),
          ['class' => 'dropdown-item text-wrap text-muted fst-italic']
      ); ?>
    </li>
  <?php } ?>
<?php } ?>

<?php if (!empty($semanticTerms)) { ?>
  <li><hr class="dropdown-divider"></li>
  <li>
    <span class="dropdown-item-text small text-muted">
      <i class="fas fa-brain me-1"></i>
      <?php echo __('Expanded with: %1%', ['%1%' => implode(', ', $semanticTerms)]); ?>
    </span>
  </li>
<?php } ?>
