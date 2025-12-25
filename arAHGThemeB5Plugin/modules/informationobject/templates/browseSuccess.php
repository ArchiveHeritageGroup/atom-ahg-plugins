<?php
use_stylesheet("/plugins/arAHGThemeB5Plugin/css/display-modes.css");
// Display mode - gallery/timeline use full width (1col), others use 2col
$currentDisplayMode = $sf_request->getParameter('displayMode', 'list');
$fullwidthModes = ['gallery', 'timeline'];
$isFullwidth = in_array($currentDisplayMode, $fullwidthModes);
?>
<?php if (isset($pager) && $pager->getNbResults() || sfConfig::get('app_enable_institutional_scoping')) { ?>
  <?php if ($isFullwidth) { ?>
    <?php decorate_with('layout_1col'); ?>
  <?php } else { ?>
    <?php decorate_with('layout_2col'); ?>
  <?php } ?>
<?php } else { ?>
  <?php decorate_with('layout_1col'); ?>
<?php } ?>

<?php use_helper('Date'); ?>
<?php use Illuminate\Database\Capsule\Manager as DB; ?>
<?php use AtomExtensions\Services\DisplayModeService; ?>

<?php
// Initialize Display Mode Service
$displayModeService = new DisplayModeService();
$currentDisplayMode = $displayModeService->getCurrentMode('informationobject');

// Handle displayMode parameter to save user preference
if ($sf_request->hasParameter('displayMode')) {
    $requestedMode = $sf_request->getParameter('displayMode');
    $displayModeService->switchMode('informationobject', $requestedMode);
    $currentDisplayMode = $requestedMode;
}
?>

<?php slot('title'); ?>
  <?php echo get_partial('default/printPreviewBar'); ?>

  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-file-alt me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column">
      <h1 class="mb-0" aria-describedby="heading-label">
        <?php if (isset($pager) && $pager->getNbResults()) { ?>
          <?php echo __('Showing %1% results', ['%1%' => $pager->getNbResults()]); ?>
        <?php } else { ?>
          <?php echo __('No results found'); ?>
        <?php } ?>
      </h1>
      <span class="small" id="heading-label">
        <?php echo sfConfig::get('app_ui_label_informationobject'); ?>
      </span>
    </div>
  </div>
<?php end_slot(); ?>

<?php if (isset($pager) && $pager->getNbResults() || sfConfig::get('app_enable_institutional_scoping')) { ?>

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
      <!-- GLAM Type Facet -->
      <?php
      $glamTypes = [];
      if (isset($aggs['glamType']) && count($aggs['glamType']) > 0) {
          foreach ($aggs['glamType'] as $bucket) {
              if (isset($bucket['key'])) {
                  $glamTypes[] = (object)[
                      'object_type' => $bucket['key'],
                      'count' => $bucket['doc_count']
                  ];
              }
          }
      }
      $currentGlamType = $sf_request->getParameter('glamType');
      $glamIcons = [
          'archive' => 'fa-archive',
          'library' => 'fa-book',
          'museum' => 'fa-university',
          'gallery' => 'fa-palette',
          'dam' => 'fa-image',
          'universal' => 'fa-question-circle',
      ];
      $glamLabels = [
          'archive' => 'Archive',
          'library' => 'Library',
          'museum' => 'Museum',
          'gallery' => 'Gallery',
          'dam' => 'Photo/DAM',
          'universal' => 'Universal',
      ];
      ?>
      <?php if (!empty($glamTypes)): ?>
      <div class="accordion mb-3">
        <div class="accordion-item aggregation">
          <h2 class="accordion-header" id="heading-glamtype">
            <button
              class="accordion-button"
              type="button"
              data-bs-toggle="collapse"
              data-bs-target="#collapse-glamtype"
              aria-expanded="true"
              aria-controls="collapse-glamtype">
              <?php echo __('GLAM Type'); ?>
            </button>
          </h2>
          <div
            id="collapse-glamtype"
            class="accordion-collapse collapse show list-group list-group-flush"
            aria-labelledby="heading-glamtype">
            <?php echo link_to(
                __('All'),
                ['module' => 'informationobject', 'action' => 'browse'],
                ['class' => 'list-group-item list-group-item-action d-flex justify-content-between align-items-center']
            ); ?>
            <?php foreach ($glamTypes as $type): ?>
              <?php $icon = $glamIcons[$type->object_type] ?? 'fa-folder'; ?>
              <?php $label = $glamLabels[$type->object_type] ?? ucfirst($type->object_type); ?>
              <?php $isActive = ($currentGlamType === $type->object_type) ? ' active' : ''; ?>
              <?php echo link_to(
                  '<i class="fas '.$icon.' me-2"></i>'.__($label)
                  .'<span class="ms-auto text-nowrap">'.$type->count.'</span>',
                  ['module' => 'informationobject', 'action' => 'browse', 'glamType' => $type->object_type],
                  ['class' => 'list-group-item list-group-item-action d-flex justify-content-between align-items-center' . $isActive]
              ); ?>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($sf_user->getAttribute('search-realm') && sfConfig::get('app_enable_institutional_scoping')) { ?>
        <?php include_component('repository', 'holdingsInstitution', ['resource' => QubitRepository::getById($sf_user->getAttribute('search-realm'))]); ?>
      <?php } ?>

      <?php echo get_partial('search/aggregation', [
          'id' => '#facet-languages',
          'label' => __('Language'),
          'name' => 'languages',
          'aggs' => $aggs,
          'filters' => $search->filters, ]); ?>

      <?php echo get_partial('search/aggregation', [
          'id' => '#facet-collection',
          'label' => __('Part of'),
          'name' => 'collection',
          'aggs' => $aggs,
          'filters' => $search->filters, ]); ?>

      <?php if (sfConfig::get('app_multi_repository')) { ?>
        <?php echo get_partial('search/aggregation', [
            'id' => '#facet-repository',
            'label' => sfConfig::get('app_ui_label_repository'),
            'name' => 'repos',
            'aggs' => $aggs,
            'filters' => $search->filters, ]); ?>
      <?php } ?>

      <?php echo get_partial('search/aggregation', [
          'id' => '#facet-names',
          'label' => sfConfig::get('app_ui_label_creator'),
          'name' => 'creators',
          'aggs' => $aggs,
          'filters' => $search->filters, ]); ?>

      <?php echo get_partial('search/aggregation', [
          'id' => '#facet-names',
          'label' => sfConfig::get('app_ui_label_name'),
          'name' => 'names',
          'aggs' => $aggs,
          'filters' => $search->filters, ]); ?>

      <?php echo get_partial('search/aggregation', [
          'id' => '#facet-places',
          'label' => sfConfig::get('app_ui_label_place'),
          'name' => 'places',
          'aggs' => $aggs,
          'filters' => $search->filters, ]); ?>

      <?php echo get_partial('search/aggregation', [
          'id' => '#facet-subjects',
          'label' => sfConfig::get('app_ui_label_subject'),
          'name' => 'subjects',
          'aggs' => $aggs,
          'filters' => $search->filters, ]); ?>

      <?php echo get_partial('search/aggregation', [
          'id' => '#facet-genres',
          'label' => sfConfig::get('app_ui_label_genre'),
          'name' => 'genres',
          'aggs' => $aggs,
          'filters' => $search->filters, ]); ?>

      <?php echo get_partial('search/aggregation', [
          'id' => '#facet-levelOfDescription',
          'label' => __('Level of description'),
          'name' => 'levels',
          'aggs' => $aggs,
          'filters' => $search->filters, ]); ?>

      <?php echo get_partial('search/aggregation', [
          'id' => '#facet-mediaTypes',
          'label' => sfConfig::get('app_ui_label_mediatype'),
          'name' => 'mediatypes',
          'aggs' => $aggs,
          'filters' => $search->filters, ]); ?>

    </div>

  <?php end_slot(); ?>

<?php } ?>

<?php slot('before-content'); ?>
  <div class="d-flex flex-wrap gap-2">
    <?php if ($topLod) { ?>
      <?php $params = $sf_data->getRaw('sf_request')->getGetParameters(); ?>
      <?php $params['topLod'] = 0; ?>
      <?php unset($params['page']); ?>
      
<a href="<?php echo url_for(
            ['module' => 'informationobject', 'action' => 'browse']
            + $params
        ); ?>"
        class="btn btn-sm atom-btn-white align-self-start mw-100 filter-tag d-flex">
        <span class="visually-hidden">
          <?php echo __('Remove filter:'); ?>
        </span>
        <span class="text-truncate d-inline-block">
          <?php echo __('Only top-level descriptions'); ?>
        </span>
        <i aria-hidden="true" class="fas fa-times ms-2 align-self-center"></i>
      </a>
    <?php } ?>

    <?php echo get_partial('search/filterTags', ['filterTags' => $filterTags]); ?>
  </div>

<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php echo get_component(
      'informationobject',
      'advancedSearch',
      [
          'criteria' => $search->criteria,
          'template' => $template,
          'form' => $form,
          'topLod' => $topLod,
          'hiddenFields' => $hiddenFields,
      ]
    ); ?>
  <?php include_partial('search/advancedSearchEnhancements'); ?>

  <?php if (isset($pager) && $pager->getNbResults()) { ?>

    <div class="d-flex flex-wrap gap-2 mb-3">
      <?php echo get_partial('default/printPreviewButton'); ?>

      <?php if ('yes' === sfConfig::get('app_treeview_show_browse_hierarchy_page', 'no')) { ?>
        
<a class="btn btn-sm atom-btn-white"
          href="<?php echo url_for(['module' => 'browse', 'action' => 'hierarchy']); ?>">
          <i class="fas fa-sitemap me-1" aria-hidden="true"></i>
          <?php echo __('Hierarchy'); ?>
        </a>
      <?php } ?>

      <?php if ($sf_user->isAuthenticated()) { ?>
        
<a class="btn btn-sm atom-btn-white"
          href="<?php echo url_for(array_merge(
              $sf_data->getRaw('sf_request')->getParameterHolder()->getAll(),
              ['module' => 'informationobject', 'action' => 'exportCsv']
          )); ?>">
          <i class="fas fa-upload me-1" aria-hidden="true"></i>
          <?php echo __('Export CSV'); ?>
        </a>
      <?php } ?>

      <!-- Display Mode Toggle -->
      <?php $displayModes = $displayModeService->getModeMetas('informationobject'); ?>
      <?php if (count($displayModes) > 1 && $displayModeService->canOverride('informationobject')): ?>
        <div class="btn-group ms-2" role="group" aria-label="<?php echo __('Display mode'); ?>">
          <?php foreach ($displayModes as $mode => $meta): ?>
            <?php
            $modeParams = $sf_data->getRaw('sf_request')->getGetParameters();
            $modeParams['displayMode'] = $mode;
            $faIcon = str_replace('bi-', 'fa-', $meta['icon']);
            $faIcon = str_replace('fa-diagram-3', 'fa-sitemap', $faIcon);
            $faIcon = str_replace('fa-grid-3x3-gap', 'fa-th', $faIcon);
            $faIcon = str_replace('fa-list-ul', 'fa-list', $faIcon);
            $faIcon = str_replace('fa-clock-history', 'fa-history', $faIcon);
            $faIcon = str_replace('fa-images', 'fa-image', $faIcon);
            ?>
            <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse'] + $modeParams); ?>"
               class="btn btn-sm <?php echo $meta['active'] ? 'atom-btn-secondary' : 'atom-btn-white'; ?>"
               title="<?php echo htmlspecialchars($meta['description']); ?>">
              <i class="fas <?php echo $faIcon; ?>" aria-hidden="true"></i>
              <span class="visually-hidden"><?php echo $meta['name']; ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="d-flex flex-wrap gap-2 ms-auto">
        <?php echo get_partial('default/sortPickers', ['options' => [
            'lastUpdated' => __('Date modified'),
            'alphabetic' => __('Title'),
            'relevance' => __('Relevance'),
            'identifier' => __('Identifier'),
            'referenceCode' => __('Reference code'),
            'startDate' => __('Start date'),
            'endDate' => __('End date'),
        ]]); ?>
      </div>
    </div>

    <?php if (in_array($currentDisplayMode, ['list', 'tree'])) { ?>
      <!-- LIST/TABLE VIEW -->
      <div id="content">
        <?php if (
            !isset($sf_request->onlyMedia)
            && isset($aggs['digitalobjects'])
            && 0 < $aggs['digitalobjects']['doc_count']
        ) { ?>
          <div class="d-grid d-sm-flex gap-2 align-items-center p-3 border-bottom">
            <?php echo __(
                '%1% results with digital objects',
                ['%1%' => $aggs['digitalobjects']['doc_count']]
            ); ?>
            <?php $params = $sf_data->getRaw('sf_request')->getGetParameters(); ?>
            <?php unset($params['page']); ?>
            
<a class="btn btn-sm atom-btn-white ms-auto text-wrap"
              href="<?php echo url_for(
                  ['module' => 'informationobject', 'action' => 'browse']
                  + $params
                  + ['onlyMedia' => true]
              ); ?>">
              <i class="fas fa-search me-1" aria-hidden="true"></i>
              <?php echo __('Show results with digital objects'); ?>
            </a>
          </div>
        <?php } ?>
        <?php echo get_partial(
            'informationobject/tableViewResults',
            ['pager' => $pager, 'selectedCulture' => $selectedCulture]
        ); ?>
      </div>

    <?php } elseif (in_array($currentDisplayMode, ['grid', 'gallery', 'timeline'])) { ?>
      <!-- GRID/CARD VIEW -->
      <?php if (
          !isset($sf_request->onlyMedia)
          && isset($aggs['digitalobjects'])
          && 0 < $aggs['digitalobjects']['doc_count']
      ) { ?>
        <div class="d-flex mb-3">
          <?php $params = $sf_data->getRaw('sf_request')->getGetParameters(); ?>
          <?php unset($params['page']); ?>
          
<a class="btn btn-sm atom-btn-white ms-auto text-wrap"
            href="<?php echo url_for(
                ['module' => 'informationobject', 'action' => 'browse']
                + $params
                + ['onlyMedia' => true]
            ); ?>">
            <i class="fas fa-search me-1" aria-hidden="true"></i>
            <?php echo __(
                'Show %1% results with digital objects',
                ['%1%' => $aggs['digitalobjects']['doc_count']]
            ); ?>
          </a>
        </div>
      <?php } ?>
      <?php echo get_partial(
          'informationobject/cardViewResults',
          ['pager' => $pager, 'selectedCulture' => $selectedCulture]
      ); ?>

    <?php } ?>

  <?php } ?>

<?php end_slot(); ?>

<?php if (isset($pager)) { ?>
  <?php slot('after-content'); ?>
    <?php echo get_partial('default/pager', ['pager' => $pager]); ?>
  <?php end_slot(); ?>
<?php } ?>
