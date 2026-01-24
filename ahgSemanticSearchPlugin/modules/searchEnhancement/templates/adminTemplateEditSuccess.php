<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fa fa-edit me-2"></i><?php echo $isNew ? __('New Search Template') : __('Edit Search Template'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'settings', 'action' => 'ahgSettings']); ?>"><?php echo __('Settings'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'searchEnhancement', 'action' => 'adminTemplates']); ?>"><?php echo __('Search Templates'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo $isNew ? __('New') : __('Edit'); ?></li>
  </ol>
</nav>

<form method="post" class="needs-validation" novalidate>
  <div class="row">
    <div class="col-md-8">
      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Template Details'); ?></h5></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label"><?php echo __('Name'); ?> *</label>
              <input type="text" name="name" class="form-control" required 
                     value="<?php echo esc_entities($template->name ?? ''); ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label"><?php echo __('Slug'); ?></label>
              <input type="text" name="slug" class="form-control" 
                     value="<?php echo esc_entities($template->slug ?? ''); ?>" 
                     placeholder="<?php echo __('auto-generated'); ?>">
            </div>
            <div class="col-12">
              <label class="form-label"><?php echo __('Description'); ?></label>
              <textarea name="description" class="form-control" rows="2"><?php echo esc_entities($template->description ?? ''); ?></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label"><?php echo __('Category'); ?></label>
              <input type="text" name="category" class="form-control" 
                     value="<?php echo esc_entities($template->category ?? ''); ?>"
                     placeholder="e.g., Quick Searches, By Format">
            </div>
            <div class="col-md-6">
              <label class="form-label"><?php echo __('Entity Type'); ?></label>
              <select name="entity_type" class="form-select">
                <option value="informationobject" <?php echo ($template->entity_type ?? '') === 'informationobject' ? 'selected' : ''; ?>><?php echo __('Information Objects'); ?></option>
                <option value="actor" <?php echo ($template->entity_type ?? '') === 'actor' ? 'selected' : ''; ?>><?php echo __('Authority Records'); ?></option>
                <option value="repository" <?php echo ($template->entity_type ?? '') === 'repository' ? 'selected' : ''; ?>><?php echo __('Repositories'); ?></option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Search Parameters'); ?></h5></div>
        <div class="card-body">
          <label class="form-label"><?php echo __('JSON Parameters'); ?></label>
          <textarea name="search_params" class="form-control font-monospace" rows="6" required><?php 
            echo esc_entities($template->search_params ?? '{}'); 
          ?></textarea>
          <div class="form-text">
            <?php echo __('Example'); ?>: <code>{"sq0":"photographs","sf0":"title","onlyMedia":"1"}</code>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Appearance'); ?></h5></div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Icon'); ?></label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa <?php echo esc_entities($template->icon ?? 'fa-search'); ?>"></i></span>
              <input type="text" name="icon" class="form-control" 
                     value="<?php echo esc_entities($template->icon ?? 'fa-search'); ?>">
            </div>
            <div class="form-text"><?php echo __('FontAwesome class'); ?></div>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Color'); ?></label>
            <select name="color" class="form-select">
              <?php foreach (['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark'] as $color): ?>
              <option value="<?php echo $color; ?>" <?php echo ($template->color ?? 'primary') === $color ? 'selected' : ''; ?>>
                <?php echo ucfirst($color); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Sort Order'); ?></label>
            <input type="number" name="sort_order" class="form-control" 
                   value="<?php echo (int)($template->sort_order ?? 0); ?>">
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Visibility'); ?></h5></div>
        <div class="card-body">
          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                   <?php echo ($template->is_active ?? 1) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="is_active"><?php echo __('Active'); ?></label>
          </div>
          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured"
                   <?php echo ($template->is_featured ?? 0) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="is_featured"><?php echo __('Featured'); ?></label>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="show_on_homepage" value="1" id="show_on_homepage"
                   <?php echo ($template->show_on_homepage ?? 0) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="show_on_homepage"><?php echo __('Show on Homepage'); ?></label>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-between">
    <a href="<?php echo url_for(['module' => 'searchEnhancement', 'action' => 'adminTemplates']); ?>" class="btn btn-secondary">
      <i class="fa fa-arrow-left me-1"></i><?php echo __('Cancel'); ?>
    </a>
    <button type="submit" class="btn btn-primary">
      <i class="fa fa-save me-1"></i><?php echo __('Save Template'); ?>
    </button>
  </div>
</form>

<?php end_slot(); ?>
