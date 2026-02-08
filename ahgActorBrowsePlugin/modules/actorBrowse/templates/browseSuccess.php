<?php if (isset($pager) && $pager->getNbResults()) { ?>
  <?php decorate_with('layout_2col'); ?>
<?php } else { ?>
  <?php decorate_with('layout_1col'); ?>
<?php } ?>

<?php use_helper('Date'); ?>
<?php
// DisplayModeService integration
$displayModeService = null;
$currentDisplayMode = 'list';
try {
    $dmsPath = sfConfig::get('sf_plugins_dir') . '/ahgDisplayPlugin/lib/Services/DisplayModeService.php';
    if (file_exists($dmsPath)) {
        require_once $dmsPath;
        if (class_exists('AtomExtensions\Services\DisplayModeService')) {
            $displayModeService = new \AtomExtensions\Services\DisplayModeService();
            $currentDisplayMode = $displayModeService->getCurrentMode('actor');
            if ($sf_request->hasParameter('displayMode')) {
                $displayModeService->switchMode('actor', $sf_request->getParameter('displayMode'));
                $currentDisplayMode = $sf_request->getParameter('displayMode');
            }
        }
    }
} catch (Exception $e) {
    // DisplayModeService not available, use list mode
}
?>

<?php
$browseService = $sf_data->getRaw('browseService');
$entityTypeNames = $sf_data->getRaw('entityTypeNames');
$aggs = $sf_data->getRaw('aggs');
$search = $sf_data->getRaw('search');
$filterTags = $sf_data->getRaw('filterTags');
$selectedCulture = $sf_data->getRaw('selectedCulture');
?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-user me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column">
      <h1 class="mb-0" aria-describedby="heading-label">
        <?php if (isset($pager) && $pager->getNbResults()) { ?>
          <?php echo __('Showing %1% results', ['%1%' => $pager->getNbResults()]); ?>
        <?php } else { ?>
          <?php echo __('No results found'); ?>
        <?php } ?>
      </h1>
      <span class="small" id="heading-label">
        <?php echo sfConfig::get('app_ui_label_actor'); ?>
      </span>
    </div>
  </div>
<?php end_slot(); ?>

<?php if (isset($pager) && $pager->getNbResults()) { ?>

  <?php slot('sidebar'); ?>

    <h2 class="d-grid">
      <button
        class="btn btn-lg atom-btn-white collapsed text-wrap"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#collapse-aggregations"
        aria-expanded="false"
        aria-controls="collapse-aggregations">
        <?php echo sfConfig::get('app_ui_label_facetstitle'); ?>
      </button>
    </h2>

    <div class="collapse" id="collapse-aggregations">

      <?php echo get_partial('search/aggregation', [
          'id' => '#facet-languages',
          'label' => __('Language'),
          'name' => 'languages',
          'aggs' => $aggs,
          'filters' => $search->filters,
      ]); ?>

      <?php echo get_partial('search/aggregation', [
          'id' => '#facet-entitytype',
          'label' => __('Entity type'),
          'name' => 'entityType',
          'aggs' => $aggs,
          'filters' => $search->filters,
      ]); ?>

      <?php echo get_partial('search/aggregation', [
          'id' => '#facet-maintainingrepository',
          'label' => __('Maintained by'),
          'name' => 'repository',
          'aggs' => $aggs,
          'filters' => $search->filters,
      ]); ?>

      <?php echo get_partial('search/aggregation', [
          'id' => '#facet-occupation',
          'label' => __('Occupation'),
          'name' => 'occupation',
          'aggs' => $aggs,
          'filters' => $search->filters,
      ]); ?>

      <?php echo get_partial('search/aggregation', [
          'id' => '#facet-places',
          'label' => sfConfig::get('app_ui_label_place'),
          'name' => 'place',
          'aggs' => $aggs,
          'filters' => $search->filters,
      ]); ?>

      <?php echo get_partial('search/aggregation', [
          'id' => '#facet-subjects',
          'label' => sfConfig::get('app_ui_label_subject'),
          'name' => 'subject',
          'aggs' => $aggs,
          'filters' => $search->filters,
      ]); ?>

      <?php echo get_partial('search/aggregation', [
          'id' => '#facet-mediaTypes',
          'label' => sfConfig::get('app_ui_label_mediatype'),
          'name' => 'mediatypes',
          'aggs' => $aggs,
          'filters' => $search->filters,
      ]); ?>

    </div>

  <?php end_slot(); ?>

<?php } ?>

<?php slot('before-content'); ?>
  <?php if (!empty($filterTags)) { ?>
    <div class="d-flex flex-wrap gap-2 mb-2">
      <?php foreach ($filterTags as $name => $tag) { ?>
        <?php
        $removeParams = $sf_data->getRaw('sf_request')->getParameterHolder()->getAll();
        unset($removeParams[$name]);
        unset($removeParams['page']);
        ?>
        <span class="badge bg-secondary d-inline-flex align-items-center gap-1">
          <?php echo esc_entities($tag['label']); ?>
          <a href="<?php echo url_for(['module' => 'actorBrowse', 'action' => 'browse'] + $removeParams); ?>"
             class="text-white text-decoration-none ms-1"
             title="<?php echo __('Remove filter'); ?>">
            <i class="fas fa-times" aria-hidden="true"></i>
          </a>
        </span>
      <?php } ?>
    </div>
  <?php } ?>

  <div class="d-inline-block mb-3">
    <?php echo get_component('search', 'inlineSearch', [
        'label' => __('Search %1%', ['%1%' => strtolower(sfConfig::get('app_ui_label_actor'))]),
        'landmarkLabel' => __(sfConfig::get('app_ui_label_actor')),
    ]); ?>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php echo get_partial('actor/advancedSearch', [
      'criteria' => $search->criteria,
      'form' => $form,
      'fieldOptions' => $sf_data->getRaw('fieldOptions'),
      'hiddenFields' => $sf_data->getRaw('hiddenFields'),
      'show' => $showAdvanced,
  ]); ?>

  <?php if (isset($pager) && $pager->getNbResults()) { ?>

    <div class="d-flex flex-wrap gap-2 mb-3">
      <?php if ($displayModeService): ?>
        <?php
        $displayModes = $displayModeService->getModeMetas('actor');
        if (count($displayModes) > 1 && $displayModeService->canOverride('actor')):
        ?>
          <div class="btn-group" role="group" aria-label="<?php echo __('Display mode'); ?>">
            <?php foreach ($displayModes as $mode => $meta): ?>
              <?php
              $modeParams = $sf_data->getRaw('sf_request')->getGetParameters();
              $modeParams['displayMode'] = $mode;
              $faIcon = str_replace('bi-', 'fa-', $meta['icon']);
              $faIcon = str_replace('fa-diagram-3', 'fa-sitemap', $faIcon);
              $faIcon = str_replace('fa-grid-3x3-gap', 'fa-th', $faIcon);
              $faIcon = str_replace('fa-list-ul', 'fa-list', $faIcon);
              $faIcon = str_replace('fa-clock-history', 'fa-history', $faIcon);
              ?>
              <a href="<?php echo url_for(['module' => 'actorBrowse', 'action' => 'browse'] + $modeParams); ?>"
                 class="btn btn-sm <?php echo $meta['active'] ? 'atom-btn-secondary' : 'atom-btn-white'; ?>"
                 title="<?php echo esc_entities($meta['description']); ?>">
                <i class="fas <?php echo $faIcon; ?>" aria-hidden="true"></i>
                <span class="visually-hidden"><?php echo $meta['name']; ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <div class="d-flex flex-wrap gap-2 ms-auto">
        <?php echo get_partial('default/sortPickers', ['options' => [
            'lastUpdated' => __('Date modified'),
            'alphabetic' => __('Name'),
            'identifier' => __('Identifier'),
        ]]); ?>
      </div>
    </div>

    <div id="content">
      <?php if (in_array($currentDisplayMode, ['list', 'tree'])) { ?>
        <?php foreach ($pager->getResults() as $doc) { ?>
          <?php echo include_partial('actorBrowse/actorSearchResult', [
              'doc' => $doc,
              'culture' => $selectedCulture,
              'entityTypeNames' => $entityTypeNames,
              'browseService' => $browseService,
              'clipboardType' => 'actor',
          ]); ?>
        <?php } ?>
      <?php } else { ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
          <?php foreach ($pager->getResults() as $doc) { ?>
            <div class="col">
              <div class="card h-100">
                <div class="card-body">
                  <h5 class="card-title">
                    <a href="<?php echo url_for(['module' => 'actor', 'slug' => $doc['slug']]); ?>">
                      <?php echo esc_entities($browseService->extractI18nField($doc, 'authorizedFormOfName') ?: __('[Untitled]')); ?>
                    </a>
                  </h5>
                  <?php if (!empty($doc['entityTypeId']) && isset($entityTypeNames[(int) $doc['entityTypeId']])): ?>
                    <p class="card-text small text-muted">
                      <?php echo esc_entities($entityTypeNames[(int) $doc['entityTypeId']]); ?>
                    </p>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php } ?>
        </div>
      <?php } ?>
    </div>
  <?php } ?>

<?php end_slot(); ?>

<?php slot('after-content'); ?>
  <?php echo get_partial('default/pager', ['pager' => $pager]); ?>
<?php end_slot(); ?>
