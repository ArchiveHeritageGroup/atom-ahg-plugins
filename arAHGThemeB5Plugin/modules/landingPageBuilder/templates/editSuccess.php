<?php use_helper('Date') ?>

<div class="landing-page-builder">
  <!-- Header Toolbar -->
  <div class="builder-toolbar bg-dark text-white py-2 px-3 d-flex align-items-center justify-content-between sticky-top">
    <div class="d-flex align-items-center gap-3">
      <a href="<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'list']) ?>" 
         class="btn btn-outline-light btn-sm">
        ← Back
      </a>
      <h5 class="mb-0"><?php echo esc_entities($page->name) ?></h5>
      <?php if ($page->is_default): ?>
        <span class="badge bg-primary">Default</span>
      <?php endif ?>
      <?php if (!$page->is_active): ?>
        <span class="badge bg-warning text-dark">Inactive</span>
      <?php endif ?>
    </div>
    
    <div class="d-flex align-items-center gap-2">
      <button type="button" class="btn btn-outline-light btn-sm" id="btn-preview" 
              data-url="<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'preview', 'id' => $page->id]) ?>">
        Preview
      </button>
      <button type="button" class="btn btn-outline-light btn-sm" id="btn-settings" 
              data-bs-toggle="offcanvas" data-bs-target="#pageSettingsPanel">
        Settings
      </button>
      <div class="dropdown">
        <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" 
                data-bs-toggle="dropdown">
          Versions
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <?php if (count($versions) > 0): ?>
            <?php foreach ($versions as $version): ?>
              <li>
                <a class="dropdown-item version-restore" href="#" data-version-id="<?php echo $version->id ?>">
                  <small class="text-muted">v<?php echo $version->version_number ?></small>
                  <?php echo $version->status ?>
                  <br>
                  <small><?php echo format_date($version->created_at, 'g') ?></small>
                </a>
              </li>
            <?php endforeach ?>
          <?php else: ?>
            <li><span class="dropdown-item text-muted">No versions yet</span></li>
          <?php endif ?>
        </ul>
      </div>
      <button type="button" class="btn btn-info btn-sm" id="btn-save-draft">
        Save Draft
      </button>
      <button type="button" class="btn btn-success btn-sm" id="btn-publish">
        Publish
      </button>
    </div>
  </div>

  <div class="builder-main d-flex">
    <!-- Block Palette (Left Sidebar) -->
    <div class="builder-palette bg-light border-end" style="width: 280px; min-height: calc(100vh - 56px);">
      <div class="p-3">
        <h6 class="text-uppercase text-muted small mb-3">+ Add Block</h6>
        
        <div class="block-types" id="block-palette">
          <?php
          // Group blocks by category
          $categories = [
              'Layout' => ['header_section', 'footer_section', 'row_1_col', 'row_2_col', 'row_3_col', 'divider', 'spacer'],
              'Content' => ['hero_banner', 'text_content', 'image_carousel'],
              'Data' => ['search_box', 'browse_panels', 'recent_items', 'featured_items', 'statistics', 'holdings_list'],
              'Other' => ['quick_links', 'repository_spotlight', 'map_block']
          ];
          
          foreach ($categories as $catName => $catBlocks):
          ?>
            <div class="block-category mb-2">
              <button class="btn btn-sm btn-outline-secondary w-100 text-start collapsed" 
                      type="button" data-bs-toggle="collapse" 
                      data-bs-target="#cat-<?php echo strtolower($catName) ?>">
                <?php echo $catName ?> ▾
              </button>
              <div class="collapse <?php echo $catName === 'Layout' ? 'show' : '' ?>" 
                   id="cat-<?php echo strtolower($catName) ?>">
                <?php foreach ($blockTypes as $type): ?>
                  <?php if (in_array($type->machine_name, $catBlocks)): ?>
                    <div class="block-type-item card mt-1 d-flex flex-row align-items-center" 
                         draggable="true"
                         data-type-id="<?php echo $type->id ?>"
                         data-machine-name="<?php echo $type->machine_name ?>">
                      <div class="drag-handle bg-secondary bg-opacity-25 px-2 py-2 rounded-start" 
                           style="cursor: grab;" title="Drag to canvas">
                        ⋮⋮
                      </div>
                      <div class="card-body py-2 px-2 flex-grow-1" style="cursor: pointer;" title="Click to add">
                        <div class="small fw-medium"><?php echo $type->label ?></div>
                      </div>
                    </div>
                  <?php endif ?>
                <?php endforeach ?>
              </div>
            </div>
          <?php endforeach ?>
        </div>
      </div>
    </div>

    <!-- Canvas (Center) -->
    <div class="builder-canvas flex-grow-1 bg-white" style="min-height: calc(100vh - 56px); overflow-y: auto;">
      <div class="canvas-header bg-light border-bottom p-2 d-flex align-items-center justify-content-between">
        <span class="small text-muted">
          <i class="bi bi-grid-3x3"></i> Canvas
          <span id="block-count">(<?php echo count($blocks) ?> blocks)</span>
        </span>
        <div>
          <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-collapse-all">
            −
          </button>
          <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-expand-all">
            +
          </button>
        </div>
      </div>
      
      <div class="canvas-body p-4">
        <div id="blocks-container" class="blocks-drop-zone" data-page-id="<?php echo $page->id ?>">
          <?php if (count($blocks) === 0): ?>
            <div class="empty-canvas text-center py-5" id="empty-message">
              <i class="bi bi-inbox display-1 text-muted"></i>
              <p class="text-muted mt-3">Drag blocks here to start building your page</p>
            </div>
          <?php else: ?>
            <?php foreach ($blocks as $block): ?>
              <?php include_partial('landingPageBuilder/blockCard', ['block' => $block]) ?>
            <?php endforeach ?>
          <?php endif ?>
        </div>
      </div>
    </div>

    <!-- Block Config Panel (Right Sidebar) -->
    <div class="builder-config bg-light border-start" id="config-panel" style="width: 350px; display: none;">
      <div class="config-header border-bottom p-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0">
          <i class="bi bi-sliders"></i> <span id="config-title">Block Settings</span>
        </h6>
        <button type="button" class="btn-close" id="close-config"></button>
      </div>
      <div class="config-body p-3" id="config-form-container">
        <!-- Dynamic form loaded here -->
      </div>
    </div>
  </div>

  <!-- Page Settings Offcanvas -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="pageSettingsPanel">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">Page Settings</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
      <form id="page-settings-form">
        <input type="hidden" name="id" value="<?php echo $page->id ?>">
        
        <div class="mb-3">
          <label class="form-label">Page Name</label>
          <input type="text" name="name" class="form-control" 
                 value="<?php echo esc_entities($page->name) ?>" required>
        </div>
        
        <div class="mb-3">
          <label class="form-label">URL Slug</label>
          <div class="input-group">
            <span class="input-group-text">/</span>
            <input type="text" name="slug" class="form-control" 
                   value="<?php echo esc_entities($page->slug) ?>" required>
          </div>
          <div class="form-text">URL: <?php echo sfConfig::get('app_siteBaseUrl') ?>/landing/<span id="slug-preview"><?php echo $page->slug ?></span></div>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3"><?php echo esc_entities($page->description) ?></textarea>
        </div>
        
        <div class="mb-3">
          <div class="form-check form-switch">
            <input type="checkbox" name="is_default" class="form-check-input" 
                   id="is_default" <?php echo $page->is_default ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_default">Set as default home page</label>
          </div>
        </div>
        
        <div class="mb-3">
          <div class="form-check form-switch">
            <input type="checkbox" name="is_active" class="form-check-input" 
                   id="is_active" <?php echo $page->is_active ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_active">Active (visible to public)</label>
          </div>
        </div>
        
        <div class="d-grid gap-2">
          <button type="submit" class="btn btn-primary">Save Settings</button>
        </div>
      </form>
      
      <hr class="my-4">
      
      <div class="text-danger">
        <h6>Danger Zone</h6>
        <?php if (!$page->is_default): ?>
          <button type="button" class="btn btn-outline-danger btn-sm" id="btn-delete-page">
            <i class="bi bi-trash"></i> Delete Page
          </button>
        <?php else: ?>
          <p class="small text-muted">Default page cannot be deleted</p>
        <?php endif ?>
      </div>
    </div>
  </div>
</div>

<!-- Block Card Template (for JavaScript) -->
<template id="block-card-template">
  <div class="block-card card mb-3" data-block-id="">
    <div class="card-header d-flex align-items-center py-2 cursor-grab block-handle">
      <i class="bi bi-grip-vertical text-muted me-2"></i>
      <i class="bi block-icon me-2"></i>
      <span class="block-label flex-grow-1"></span>
      <div class="block-actions">
        <button type="button" class="btn btn-sm btn-link text-muted btn-visibility" title="Toggle visibility">
          <i class="bi bi-eye"></i>
        </button>
        <button type="button" class="btn btn-sm btn-link text-primary btn-edit" title="Edit">
          <i class="bi bi-pencil"></i>
        </button>
        <button type="button" class="btn btn-sm btn-link text-secondary btn-duplicate" title="Duplicate">
          <i class="bi bi-copy"></i>
        </button>
        <button type="button" class="btn btn-sm btn-link text-danger btn-delete" title="Delete">
          <i class="bi bi-trash"></i>
        </button>
      </div>
    </div>
    <div class="card-body block-preview p-3">
      <!-- Block preview content -->
    </div>
  </div>
</template>

<script>
const LandingPageBuilder = {
    pageId: <?php echo $page->id ?>,
    blocks: <?php echo json_encode($blocks->toArray()) ?>,
    blockTypes: <?php echo json_encode($blockTypes->toArray()) ?>,
    urls: {
        addBlock: '<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'addBlock']) ?>',
        updateBlock: '<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'updateBlock']) ?>',
        deleteBlock: '<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'deleteBlock']) ?>',
        duplicateBlock: '<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'duplicateBlock']) ?>',
        reorderBlocks: '<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'reorderBlocks']) ?>',
        toggleVisibility: '<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'toggleVisibility']) ?>',
        getBlockConfig: '<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'getBlockConfig']) ?>',
        updateSettings: '<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'updateSettings']) ?>',
        deletePage: '<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'delete']) ?>',
        saveDraft: '<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'saveDraft']) ?>',
        publish: '<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'publish']) ?>',
        restoreVersion: '<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'restoreVersion']) ?>',
        listPage: '<?php echo url_for(['module' => 'landingPageBuilder', 'action' => 'list']) ?>'
    }
};
</script>

<!-- Load Sortable.js and Landing Page Builder JS -->
<script src="/plugins/arAHGThemeB5Plugin/js/vendor/Sortable.min.js"></script>
<script src="/plugins/arAHGThemeB5Plugin/js/landing-page-builder.js"></script>
<script src="/plugins/arAHGThemeB5Plugin/js/landing-page-builder-columns.js"></script>
