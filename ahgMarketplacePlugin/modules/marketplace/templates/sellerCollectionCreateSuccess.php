<?php decorate_with('layout_1col'); ?>

<?php
  $isEdit = isset($collection) && $collection;
  $pageTitle = $isEdit ? __('Edit Collection') : __('Create Collection');
?>

<?php slot('title'); ?><?php echo $pageTitle; ?> - <?php echo __('Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>"><?php echo __('Marketplace'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'dashboard']); ?>"><?php echo __('Dashboard'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerCollections']); ?>"><?php echo __('My Collections'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
  </ol>
</nav>

<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo $sf_user->getFlash('error'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="row">
  <div class="col-lg-8 mx-auto">

    <h1 class="h3 mb-4"><?php echo $pageTitle; ?></h1>

    <div class="card">
      <div class="card-body">
        <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerCollectionCreate'] + ($isEdit ? ['id' => $collection->id] : [])); ?>" enctype="multipart/form-data">

          <div class="mb-3">
            <label for="title" class="form-label"><?php echo __('Title'); ?> <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="title" name="title" value="<?php echo esc_entities($isEdit ? $collection->title : $sf_request->getParameter('title', '')); ?>" required maxlength="255">
          </div>

          <div class="mb-3">
            <label for="description" class="form-label"><?php echo __('Description'); ?></label>
            <textarea class="form-control" id="description" name="description" rows="4"><?php echo esc_entities($isEdit ? ($collection->description ?? '') : $sf_request->getParameter('description', '')); ?></textarea>
          </div>

          <div class="mb-3">
            <label for="cover_image" class="form-label"><?php echo __('Cover Image'); ?></label>
            <?php if ($isEdit && $collection->cover_image_path): ?>
              <div class="mb-2">
                <img src="<?php echo esc_entities($collection->cover_image_path); ?>" alt="" class="rounded" style="max-height: 120px;">
              </div>
            <?php endif; ?>
            <input type="file" class="form-control" id="cover_image" name="cover_image" accept="image/*">
            <div class="form-text"><?php echo __('Recommended: landscape orientation, at least 800x400px.'); ?></div>
          </div>

          <div class="mb-3">
            <label for="collection_type" class="form-label"><?php echo __('Collection Type'); ?></label>
            <select class="form-select" id="collection_type" name="collection_type">
              <?php $types = ['curated' => __('Curated'), 'exhibition' => __('Exhibition'), 'seasonal' => __('Seasonal'), 'featured' => __('Featured'), 'genre' => __('Genre'), 'sale' => __('Sale')]; ?>
              <?php $currentType = $isEdit ? ($collection->collection_type ?? 'curated') : $sf_request->getParameter('collection_type', 'curated'); ?>
              <?php foreach ($types as $val => $label): ?>
                <option value="<?php echo $val; ?>"<?php echo $currentType === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1"<?php echo ($isEdit ? !empty($collection->is_public) : true) ? ' checked' : ''; ?>>
            <label class="form-check-label" for="is_public"><?php echo __('Public (visible to everyone)'); ?></label>
          </div>

          <div class="d-flex justify-content-between">
            <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerCollections']); ?>" class="btn btn-outline-secondary">
              <i class="fas fa-arrow-left me-1"></i> <?php echo __('Cancel'); ?>
            </a>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save me-1"></i> <?php echo $isEdit ? __('Save Changes') : __('Create Collection'); ?>
            </button>
          </div>

        </form>
      </div>
    </div>

  </div>
</div>

<?php end_slot(); ?>
