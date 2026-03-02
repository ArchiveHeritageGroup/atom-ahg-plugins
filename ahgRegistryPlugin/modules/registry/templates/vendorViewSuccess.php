<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php $detail = $vendor['vendor']; ?>

<?php slot('title'); ?><?php echo htmlspecialchars($detail->name, ENT_QUOTES, 'UTF-8'); ?> - <?php echo __('Vendor'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Vendors'), 'url' => url_for(['module' => 'registry', 'action' => 'vendorBrowse'])],
  ['label' => htmlspecialchars($detail->name, ENT_QUOTES, 'UTF-8')],
]]); ?>

<!-- Banner -->
<?php if (!empty($detail->banner_path)): ?>
<div class="mb-4 rounded-3 overflow-hidden" style="max-height: 250px;">
  <img src="<?php echo htmlspecialchars($detail->banner_path, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="w-100" style="object-fit: cover; max-height: 250px;">
</div>
<?php endif; ?>

<div class="row">
  <!-- Main content -->
  <div class="col-lg-8">

    <div class="d-flex align-items-start mb-4">
      <?php if (!empty($detail->logo_path)): ?>
      <img src="<?php echo htmlspecialchars($detail->logo_path, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="rounded me-3" style="width: 80px; height: 80px; object-fit: contain;">
      <?php else: ?>
      <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
        <i class="fas fa-building fa-2x text-muted"></i>
      </div>
      <?php endif; ?>
      <div class="flex-grow-1">
        <div class="d-flex justify-content-between align-items-start">
          <h1 class="h3 mb-1">
            <?php echo htmlspecialchars($detail->name, ENT_QUOTES, 'UTF-8'); ?>
            <?php if (!empty($detail->is_verified)): ?>
              <i class="fas fa-check-circle text-primary ms-1" title="<?php echo __('Verified'); ?>"></i>
            <?php endif; ?>
          </h1>
          <?php $canEdit = (!empty($isAdmin) || (!empty($currentUserId) && isset($detail->created_by) && (int) $detail->created_by === (int) $currentUserId)); ?>
          <div class="d-flex gap-1 ms-2">
            <?php if ($sf_user->isAuthenticated()): ?>
            <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'favoriteToggle']); ?>" class="d-inline">
              <input type="hidden" name="entity_type" value="vendor">
              <input type="hidden" name="entity_id" value="<?php echo (int) $detail->id; ?>">
              <input type="hidden" name="return" value="<?php echo url_for(['module' => 'registry', 'action' => 'vendorView', 'slug' => $detail->slug]); ?>">
              <button type="submit" class="btn btn-sm <?php echo !empty($isFavorited) ? 'btn-warning' : 'btn-outline-warning'; ?>" title="<?php echo !empty($isFavorited) ? __('Remove from favorites') : __('Add to favorites'); ?>">
                <i class="fas fa-star"></i>
              </button>
            </form>
            <?php endif; ?>
            <?php if (!empty($detail->is_featured)): ?>
              <span class="btn btn-sm btn-outline-success disabled"><i class="fas fa-award me-1"></i><?php echo __('Featured'); ?></span>
            <?php endif; ?>
            <?php if ($canEdit): ?>
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'vendorEdit', 'id' => (int) $detail->id]); ?>" class="btn btn-sm btn-outline-secondary">
              <i class="fas fa-edit me-1"></i> <?php echo __('Edit'); ?>
            </a>
            <?php endif; ?>
          </div>
        </div>
        <div class="mb-1">
          <?php
            $rawVt = sfOutputEscaper::unescape($detail->vendor_type ?? '[]');
            $vtArr = is_string($rawVt) ? (json_decode($rawVt, true) ?: []) : (is_array($rawVt) ? $rawVt : []);
            foreach ($vtArr as $vt): ?>
              <span class="badge bg-success me-1"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $vt)), ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endforeach; ?>
          <?php if (!empty($detail->team_size)): ?>
            <span class="badge bg-secondary"><?php echo __('Team: %1%', ['%1%' => htmlspecialchars($detail->team_size, ENT_QUOTES, 'UTF-8')]); ?></span>
          <?php endif; ?>
        </div>
        <?php if (!empty($detail->city) || !empty($detail->country)): ?>
        <small class="text-muted">
          <i class="fas fa-map-marker-alt me-1"></i>
          <?php echo htmlspecialchars(implode(', ', array_filter([$detail->city ?? '', $detail->province_state ?? '', $detail->country ?? ''])), ENT_QUOTES, 'UTF-8'); ?>
        </small>
        <?php endif; ?>
      </div>
    </div>

    <!-- Description -->
    <?php if (!empty($detail->description)): ?>
    <div class="mb-4">
      <h2 class="h5"><?php echo __('About'); ?></h2>
      <div><?php echo nl2br(htmlspecialchars($detail->description, ENT_QUOTES, 'UTF-8')); ?></div>
    </div>
    <?php endif; ?>

    <!-- Specializations -->
    <?php if (!empty($detail->specializations)): ?>
    <div class="mb-4">
      <h2 class="h5"><?php echo __('Specializations'); ?></h2>
      <?php
        $rawSpecializations = sfOutputEscaper::unescape($detail->specializations);
        $specs = is_string($rawSpecializations) ? json_decode($rawSpecializations, true) : (array) $rawSpecializations;
        if (is_array($specs)):
          foreach ($specs as $spec): ?>
            <span class="badge bg-info text-dark me-1 mb-1"><?php echo htmlspecialchars($spec, ENT_QUOTES, 'UTF-8'); ?></span>
      <?php endforeach; endif; ?>
    </div>
    <?php endif; ?>

    <!-- Client institutions -->
    <?php if (!empty($vendor['clients'])): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Client Institutions'); ?></div>
      <ul class="list-group list-group-flush">
        <?php foreach ($vendor['clients'] as $client): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <div>
            <?php if (!empty($client->institution_slug)): ?>
              <a href="<?php echo url_for(['module' => 'registry', 'action' => 'institutionView', 'slug' => $client->institution_slug]); ?>">
                <?php echo htmlspecialchars($client->institution_name ?? '', ENT_QUOTES, 'UTF-8'); ?>
              </a>
            <?php else: ?>
              <?php echo htmlspecialchars($client->institution_name ?? '', ENT_QUOTES, 'UTF-8'); ?>
            <?php endif; ?>
          </div>
          <span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $client->relationship_type ?? '')), ENT_QUOTES, 'UTF-8'); ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <!-- Software products -->
    <?php if (!empty($vendor['software'])): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Software Products'); ?></div>
      <ul class="list-group list-group-flush">
        <?php foreach ($vendor['software'] as $sw): ?>
        <li class="list-group-item">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <?php if (!empty($sw->slug)): ?>
                <a href="<?php echo url_for(['module' => 'registry', 'action' => 'softwareView', 'slug' => $sw->slug]); ?>">
                  <strong><?php echo htmlspecialchars($sw->name ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
                </a>
              <?php else: ?>
                <strong><?php echo htmlspecialchars($sw->name ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
              <?php endif; ?>
              <?php if (!empty($sw->short_description)): ?>
                <br><small class="text-muted"><?php echo htmlspecialchars($sw->short_description, ENT_QUOTES, 'UTF-8'); ?></small>
              <?php endif; ?>
            </div>
            <?php if (!empty($sw->latest_version)): ?>
              <span class="badge bg-primary"><?php echo htmlspecialchars($sw->latest_version, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
          </div>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <!-- Reviews -->
    <?php if (!empty($vendor['reviews'])): ?>
    <div class="mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0"><?php echo __('Reviews'); ?></h2>
        <?php if (!empty($detail->average_rating)): ?>
        <div>
          <?php include_partial('registry/ratingStars', ['rating' => $detail->average_rating]); ?>
          <span class="text-muted ms-1">(<?php echo (int) $detail->rating_count; ?>)</span>
        </div>
        <?php endif; ?>
      </div>
      <?php foreach ($vendor['reviews'] as $review): ?>
      <div class="card mb-2">
        <div class="card-body py-2">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <div>
              <strong><?php echo htmlspecialchars($review->reviewer_name ?? __('Anonymous'), ENT_QUOTES, 'UTF-8'); ?></strong>
              <small class="text-muted ms-2"><?php echo date('M j, Y', strtotime($review->created_at)); ?></small>
            </div>
            <?php include_partial('registry/ratingStars', ['rating' => (int) $review->rating]); ?>
          </div>
          <?php if (!empty($review->title)): ?>
            <h6 class="mb-1"><?php echo htmlspecialchars($review->title, ENT_QUOTES, 'UTF-8'); ?></h6>
          <?php endif; ?>
          <p class="mb-0 small"><?php echo nl2br(htmlspecialchars($review->comment ?? '', ENT_QUOTES, 'UTF-8')); ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Contacts -->
    <div class="card mb-4">
      <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
        <?php echo __('Contacts'); ?>
        <?php if ($canEdit): ?>
          <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorContactAdd']); ?>?vendor=<?php echo (int) $detail->id; ?>" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-plus me-1"></i><?php echo __('Add'); ?>
          </a>
        <?php endif; ?>
      </div>
      <?php if (!empty($vendor['contacts'])): ?>
      <div class="card-body">
        <?php include_partial('registry/contactList', ['contacts' => $vendor['contacts'], 'canEdit' => $canEdit ?? false, 'entityType' => 'vendor']); ?>
      </div>
      <?php else: ?>
      <div class="card-body text-muted"><?php echo __('No contacts added yet.'); ?></div>
      <?php endif; ?>
    </div>

  </div>

  <!-- Sidebar -->
  <div class="col-lg-4">

    <!-- Quick info -->
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Details'); ?></div>
      <ul class="list-group list-group-flush">
        <?php if (!empty($detail->website)): ?>
        <li class="list-group-item">
          <i class="fas fa-globe me-2 text-muted"></i>
          <a href="<?php echo htmlspecialchars($detail->website, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars(preg_replace('#^https?://#', '', $detail->website), ENT_QUOTES, 'UTF-8'); ?></a>
        </li>
        <?php endif; ?>
        <?php if (!empty($detail->email)): ?>
        <li class="list-group-item">
          <i class="fas fa-envelope me-2 text-muted"></i>
          <a href="mailto:<?php echo htmlspecialchars($detail->email, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($detail->email, ENT_QUOTES, 'UTF-8'); ?></a>
        </li>
        <?php endif; ?>
        <?php if (!empty($detail->phone)): ?>
        <li class="list-group-item">
          <i class="fas fa-phone me-2 text-muted"></i>
          <?php echo htmlspecialchars($detail->phone, ENT_QUOTES, 'UTF-8'); ?>
        </li>
        <?php endif; ?>
        <?php if (!empty($detail->established_year)): ?>
        <li class="list-group-item">
          <i class="fas fa-calendar me-2 text-muted"></i>
          <?php echo __('Established %1%', ['%1%' => (int) $detail->established_year]); ?>
        </li>
        <?php endif; ?>
        <?php if (!empty($detail->client_count)): ?>
        <li class="list-group-item">
          <i class="fas fa-users me-2 text-muted"></i>
          <?php echo __('%1% clients', ['%1%' => (int) $detail->client_count]); ?>
        </li>
        <?php endif; ?>
      </ul>
    </div>

    <!-- Links -->
    <?php if (!empty($detail->github_url) || !empty($detail->gitlab_url) || !empty($detail->linkedin_url)): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Links'); ?></div>
      <ul class="list-group list-group-flush">
        <?php if (!empty($detail->github_url)): ?>
        <li class="list-group-item">
          <i class="fab fa-github me-2"></i>
          <a href="<?php echo htmlspecialchars($detail->github_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo __('GitHub'); ?></a>
        </li>
        <?php endif; ?>
        <?php if (!empty($detail->gitlab_url)): ?>
        <li class="list-group-item">
          <i class="fab fa-gitlab me-2"></i>
          <a href="<?php echo htmlspecialchars($detail->gitlab_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo __('GitLab'); ?></a>
        </li>
        <?php endif; ?>
        <?php if (!empty($detail->linkedin_url)): ?>
        <li class="list-group-item">
          <i class="fab fa-linkedin me-2"></i>
          <a href="<?php echo htmlspecialchars($detail->linkedin_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo __('LinkedIn'); ?></a>
        </li>
        <?php endif; ?>
      </ul>
    </div>
    <?php endif; ?>

    <!-- Tags -->
    <?php if (!empty($vendor['tags'])): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Tags'); ?></div>
      <div class="card-body">
        <?php foreach ($vendor['tags'] as $tag): ?>
          <span class="badge bg-light text-dark border me-1 mb-1"><?php echo htmlspecialchars($tag->tag ?? $tag, ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Write a review -->
    <?php if ($sf_user->isAuthenticated()): ?>
    <div class="card mb-4">
      <div class="card-body text-center">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionReview', 'type' => 'vendor', 'id' => (int) $detail->id]); ?>" class="btn btn-outline-primary btn-sm">
          <i class="fas fa-star me-1"></i> <?php echo __('Write a Review'); ?>
        </a>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php end_slot(); ?>
