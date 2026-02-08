<?php decorate_with('layout_2col'); ?>
<?php use_helper('Date'); ?>
<?php use AtomExtensions\Services\DisplayModeService; ?>
<?php
$displayModeService = new DisplayModeService();
$currentDisplayMode = $displayModeService->getCurrentMode('repository');
if ($sf_request->hasParameter('displayMode')) {
    $displayModeService->switchMode('repository', $sf_request->getParameter('displayMode'));
    $currentDisplayMode = $sf_request->getParameter('displayMode');
}
?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-university me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column">
      <h1 class="mb-0" aria-describedby="heading-label">
        <?php echo __('Showing %1% results', ['%1%' => $pager->getNbResults()]); ?>
      </h1>
      <span class="small" id="heading-label">
        <?php echo sfConfig::get('app_ui_label_repository'); ?>
      </span>
    </div>
  </div>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>

  <h2 class="d-grid">
    <button
      class="btn btn-lg atom-btn-white collapsed text-wrap"
      type="button"
      data-bs-toggle="collapse"
      data-bs-target="#collapse-aggregations"
      aria-expanded="true"
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
        'filters' => $search->filters, ]); ?>

    <?php echo get_partial('search/aggregation', [
        'id' => '#facet-archivetype',
        'label' => __('Archive type'),
        'name' => 'types',
        'aggs' => $aggs,
        'filters' => $search->filters, ]); ?>

    <?php echo get_partial('search/aggregation', [
        'id' => '#facet-province',
        'label' => __('Geographic Region'),
        'name' => 'regions',
        'aggs' => $aggs,
        'filters' => $search->filters, ]); ?>

    <?php echo get_partial('search/aggregation', [
        'id' => '#facet-geographicsubregion',
        'label' => __('Geographic Subregion'),
        'name' => 'geographicSubregions',
        'aggs' => $aggs,
        'filters' => $search->filters, ]); ?>

    <?php echo get_partial('search/aggregation', [
        'id' => '#facet-locality',
        'label' => __('Locality'),
        'name' => 'locality',
        'aggs' => $aggs,
        'filters' => $search->filters, ]); ?>

    <?php echo get_partial('search/aggregation', [
        'id' => '#facet-thematicarea',
        'label' => __('Thematic Area'),
        'name' => 'thematicAreas',
        'aggs' => $aggs,
        'filters' => $search->filters, ]); ?>

  </div>

<?php end_slot(); ?>

<?php slot('before-content'); ?>

  <div class="d-inline-block mb-3">
    <?php echo get_component('search', 'inlineSearch', [
        'label' => __('Search %1%', ['%1%' => strtolower(sfConfig::get('app_ui_label_repository'))]),
        'landmarkLabel' => __(sfConfig::get('app_ui_label_repository')),
    ]); ?>
  </div>

  <div class="accordion mb-3" role="search">
    <div class="accordion-item">
      <h2 class="accordion-header" id="heading-adv-search">
        <button class="accordion-button<?php echo $show ? '' : ' collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-adv-search" aria-expanded="<?php echo $show ? 'true' : 'false'; ?>" aria-controls="collapse-adv-search">
          <?php echo __('Advanced search options'); ?>
        </button>
      </h2>
      <div id="collapse-adv-search" class="accordion-collapse collapse<?php echo $show ? ' show' : ''; ?>" aria-labelledby="heading-adv-search">
        <div class="accordion-body">
          <?php include_partial('repositoryBrowse/advancedFilters', [
              'thematicAreas' => $thematicAreas,
              'repositoryTypes' => $repositoryTypes,
              'regions' => $regions,
              'hiddenFields' => $hiddenFields,
          ]); ?>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex flex-wrap gap-2 mb-3">
    <!-- Display Mode Toggle -->
    <?php
    $displayModes = $displayModeService->getModeMetas('repository');
    if (count($displayModes) > 1 && $displayModeService->canOverride('repository')):
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
          <a href="<?php echo url_for(['module' => 'repository', 'action' => 'browse'] + $modeParams); ?>"
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
          'alphabetic' => __('Name'),
          'identifier' => __('Identifier'),
      ]]); ?>
    </div>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <?php if (in_array($currentDisplayMode, ['list', 'tree'])) { ?>
    <?php include_partial('repositoryBrowse/browseTableView', [
        'pager' => $pager,
        'selectedCulture' => $selectedCulture,
        'thematicAreaNames' => $thematicAreaNames,
        'browseService' => $browseService,
    ]); ?>
  <?php } else { ?>
    <?php include_partial('repositoryBrowse/browseCardView', [
        'pager' => $pager,
        'selectedCulture' => $selectedCulture,
    ]); ?>
  <?php } ?>
<?php end_slot(); ?>

<?php slot('after-content'); ?>
  <?php echo get_partial('default/pager', ['pager' => $pager]); ?>
<?php end_slot(); ?>
