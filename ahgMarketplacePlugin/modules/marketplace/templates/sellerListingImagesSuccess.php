<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Manage Images'); ?> - <?php echo esc_entities($listing->title); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>"><?php echo __('Marketplace'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'dashboard']); ?>"><?php echo __('Dashboard'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerListings']); ?>"><?php echo __('My Listings'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerListingEdit', 'id' => $listing->id]); ?>"><?php echo esc_entities($listing->title); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Images'); ?></li>
  </ol>
</nav>

<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo $sf_user->getFlash('notice'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo $sf_user->getFlash('error'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<h1 class="h3 mb-2"><?php echo __('Manage Images for: %1%', ['%1%' => esc_entities($listing->title)]); ?></h1>
<p class="text-muted mb-4">
  <?php echo __('%1% of %2% images uploaded', ['%1%' => count($images), '%2%' => (int) ($maxImages ?? 20)]); ?>
</p>

<!-- Upload form -->
<div class="card mb-4">
  <div class="card-header fw-semibold"><?php echo __('Upload New Image'); ?></div>
  <div class="card-body">
    <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerListingImages', 'id' => $listing->id]); ?>" enctype="multipart/form-data">
      <input type="hidden" name="form_action" value="upload">
      <div class="row align-items-end">
        <div class="col-md-5">
          <label for="image_file" class="form-label"><?php echo __('Image File'); ?> <span class="text-danger">*</span></label>
          <input type="file" class="form-control" id="image_file" name="image_file" accept="image/jpeg,image/png,image/gif,image/webp" required>
          <div class="form-text"><?php echo __('JPEG, PNG, GIF or WebP. Max 10MB.'); ?></div>
        </div>
        <div class="col-md-5">
          <label for="caption" class="form-label"><?php echo __('Caption'); ?></label>
          <input type="text" class="form-control" id="caption" name="caption" maxlength="500" placeholder="<?php echo __('Optional image caption'); ?>">
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary w-100">
            <i class="fas fa-upload me-1"></i> <?php echo __('Upload'); ?>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Current images -->
<?php if (!empty($images)): ?>
  <div class="card">
    <div class="card-header fw-semibold"><?php echo __('Current Images'); ?></div>
    <div class="card-body">
      <div class="row g-3">
        <?php foreach ($images as $img): ?>
          <div class="col-6 col-md-4 col-lg-3">
            <div class="card h-100">
              <div class="position-relative">
                <img src="<?php echo esc_entities($img->file_path); ?>" alt="<?php echo esc_entities($img->caption ?? ''); ?>" class="card-img-top" style="height: 180px; object-fit: cover;">
                <?php if (!empty($img->is_primary)): ?>
                  <span class="badge bg-success position-absolute top-0 start-0 m-2"><?php echo __('Primary'); ?></span>
                <?php endif; ?>
                <span class="badge bg-secondary position-absolute top-0 end-0 m-2">#<?php echo (int) $img->sort_order; ?></span>
              </div>
              <div class="card-body py-2 px-3">
                <?php if ($img->caption): ?>
                  <p class="small mb-2"><?php echo esc_entities($img->caption); ?></p>
                <?php endif; ?>
                <div class="d-flex gap-1">
                  <?php if (empty($img->is_primary)): ?>
                    <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerListingImages', 'id' => $listing->id]); ?>" class="d-inline">
                      <input type="hidden" name="form_action" value="set_primary">
                      <input type="hidden" name="image_id" value="<?php echo (int) $img->id; ?>">
                      <button type="submit" class="btn btn-sm btn-outline-success" title="<?php echo __('Set as Primary'); ?>">
                        <i class="fas fa-star"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                  <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerListingImages', 'id' => $listing->id]); ?>" class="d-inline">
                    <input type="hidden" name="form_action" value="delete">
                    <input type="hidden" name="image_id" value="<?php echo (int) $img->id; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="<?php echo __('Delete'); ?>" onclick="return confirm('<?php echo __('Delete this image?'); ?>');">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-images fa-3x text-muted mb-3 d-block"></i>
      <h5><?php echo __('No images uploaded yet'); ?></h5>
      <p class="text-muted"><?php echo __('Upload images to showcase your listing.'); ?></p>
    </div>
  </div>
<?php endif; ?>

<div class="mt-4">
  <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerListingEdit', 'id' => $listing->id]); ?>" class="btn btn-outline-secondary">
    <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to Listing'); ?>
  </a>
</div>

<?php end_slot(); ?>
