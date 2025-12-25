<?php
/**
 * Enhanced Simple Search Options Dropdown
 * Includes: Global search, Advanced search, Search Templates, Saved Searches, History
 * 
 * Overrides: apps/qubit/modules/search/templates/_simpleSearchOptions.php
 */

// Load search service
require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
$searchService = new \App\Services\AdvancedSearchService();

$user = sfContext::getInstance()->getUser();
$isAuthenticated = $user->isAuthenticated();
$userId = $isAuthenticated ? $user->getAttribute('user_id') : null;
$sessionId = session_id();

// Get data
$templates = $searchService->getFeaturedTemplates();
$history = $searchService->getUserHistory($userId, $sessionId, 5);
$savedSearches = $isAuthenticated ? array_slice($searchService->getSavedSearches($userId), 0, 5) : [];
$popular = $searchService->getPopularSearches(5);
?>

<div class="dropdown-menu search-options-dropdown" id="search-options-dropdown">
  <!-- Global Search -->
  <div class="form-check px-3 py-2">
    <input class="form-check-input" type="radio" name="searchType" id="globalSearch" value="global" checked>
    <label class="form-check-label" for="globalSearch">
      <i class="fa fa-globe me-1"></i><?php echo __('Global search'); ?>
    </label>
  </div>
  
  <!-- Advanced Search -->
  <a class="dropdown-item" href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse', 'showAdvanced' => 'true']); ?>">
    <i class="fa fa-sliders-h me-2"></i><?php echo __('Advanced search'); ?>
  </a>
  
  <div class="dropdown-divider"></div>
  
  <!-- Quick Search Templates -->
  <?php if (!empty($templates)): ?>
  <h6 class="dropdown-header">
    <i class="fa fa-bolt me-1"></i><?php echo __('Quick Searches'); ?>
  </h6>
  <?php foreach ($templates as $template): ?>
  <a class="dropdown-item" href="<?php echo url_for(['module' => 'searchEnhancement', 'action' => 'runTemplate', 'id' => $template->id]); ?>">
    <i class="fa <?php echo esc_entities($template->icon); ?> me-2 text-<?php echo esc_entities($template->color); ?>"></i>
    <?php echo esc_entities($template->name); ?>
  </a>
  <?php endforeach; ?>
  <a class="dropdown-item text-muted small" href="<?php echo url_for(['module' => 'searchEnhancement', 'action' => 'adminTemplates']); ?>">
    <i class="fa fa-cog me-2"></i><?php echo __('Manage templates'); ?>
  </a>
  <div class="dropdown-divider"></div>
  <?php endif; ?>
  
  <!-- Saved Searches (Authenticated users) -->
  <?php if ($isAuthenticated && !empty($savedSearches)): ?>
  <h6 class="dropdown-header">
    <i class="fa fa-bookmark me-1"></i><?php echo __('Saved Searches'); ?>
  </h6>
  <?php foreach ($savedSearches as $saved): ?>
  <a class="dropdown-item" href="<?php echo url_for(['module' => 'searchEnhancement', 'action' => 'runSavedSearch', 'id' => $saved->id]); ?>">
    <i class="fa fa-bookmark-o me-2"></i>
    <?php echo esc_entities($saved->name); ?>
    <?php if ($saved->notify_new_results): ?>
      <i class="fa fa-bell text-info ms-1" title="<?php echo __('Notifications on'); ?>"></i>
    <?php endif; ?>
  </a>
  <?php endforeach; ?>
  <a class="dropdown-item text-muted small" href="<?php echo url_for(['module' => 'searchEnhancement', 'action' => 'savedSearches']); ?>">
    <i class="fa fa-list me-2"></i><?php echo __('All saved searches'); ?>
  </a>
  <div class="dropdown-divider"></div>
  <?php endif; ?>
  
  <!-- Recent Searches -->
  <?php if (!empty($history)): ?>
  <h6 class="dropdown-header">
    <i class="fa fa-history me-1"></i><?php echo __('Recent Searches'); ?>
  </h6>
  <?php foreach ($history as $item): ?>
  <?php 
    $params = json_decode($item->search_params, true) ?: [];
    $searchUrl = url_for(['module' => 'informationobject', 'action' => 'browse']) . '?' . http_build_query($params);
  ?>
  <a class="dropdown-item" href="<?php echo $searchUrl; ?>">
    <i class="fa fa-search me-2 text-muted"></i>
    <?php echo esc_entities(mb_substr($item->search_query ?: __('(Advanced)'), 0, 30)); ?>
    <small class="text-muted">(<?php echo $item->result_count; ?>)</small>
  </a>
  <?php endforeach; ?>
  <a class="dropdown-item text-muted small" href="<?php echo url_for(['module' => 'searchEnhancement', 'action' => 'history']); ?>">
    <i class="fa fa-clock me-2"></i><?php echo __('View all history'); ?>
  </a>
  <div class="dropdown-divider"></div>
  <?php endif; ?>
  
  <!-- Popular Searches -->
  <?php if (!empty($popular)): ?>
  <h6 class="dropdown-header">
    <i class="fa fa-fire me-1"></i><?php echo __('Popular'); ?>
  </h6>
  <?php foreach (array_slice($popular, 0, 3) as $p): ?>
  <?php 
    $params = json_decode($p->search_params, true) ?: [];
    $searchUrl = url_for(['module' => 'informationobject', 'action' => 'browse']) . '?' . http_build_query($params);
  ?>
  <a class="dropdown-item" href="<?php echo $searchUrl; ?>">
    <i class="fa fa-trending-up me-2 text-warning"></i>
    <?php echo esc_entities($p->search_query); ?>
    <small class="text-muted">(<?php echo $p->search_count; ?>)</small>
  </a>
  <?php endforeach; ?>
  <?php endif; ?>
</div>

<style>
.search-options-dropdown {
  min-width: 280px;
  max-height: 70vh;
  overflow-y: auto;
}
.search-options-dropdown .dropdown-header {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #6c757d;
  padding-top: 0.75rem;
}
.search-options-dropdown .dropdown-item {
  padding: 0.4rem 1rem;
  font-size: 0.9rem;
}
.search-options-dropdown .dropdown-item.text-muted {
  font-size: 0.8rem;
}
.search-options-dropdown .dropdown-item:hover {
  background-color: #f8f9fa;
}
</style>
