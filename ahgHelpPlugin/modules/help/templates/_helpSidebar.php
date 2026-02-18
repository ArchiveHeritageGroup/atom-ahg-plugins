<?php use_helper('I18N') ?>

<div class="help-sidebar sticky-top" style="top: 1rem;">

  <!-- Search -->
  <div class="mb-3">
    <form action="<?php echo url_for('@help_search') ?>" method="get" class="input-group input-group-sm">
      <input type="text" name="q" class="form-control" id="help-search-sidebar"
        placeholder="<?php echo __('Search help...') ?>" autocomplete="off">
      <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
    </form>
    <div id="help-search-results-dropdown" class="help-search-dropdown d-none"></div>
  </div>

  <!-- Categories -->
  <h6 class="text-uppercase text-muted mb-2"><?php echo __('Categories') ?></h6>
  <ul class="nav flex-column mb-3">
    <?php if (isset($categories)): ?>
      <?php foreach ($categories as $cat): ?>
        <li class="nav-item">
          <a class="nav-link py-1 d-flex justify-content-between align-items-center"
            href="<?php echo url_for('@help_category?category=' . urlencode($cat['category'])) ?>">
            <span><?php echo $cat['category'] ?></span>
            <span class="badge bg-secondary rounded-pill"><?php echo $cat['article_count'] ?></span>
          </a>
        </li>
      <?php endforeach; ?>
    <?php endif; ?>
  </ul>

  <!-- Quick Links -->
  <h6 class="text-uppercase text-muted mb-2"><?php echo __('Quick Links') ?></h6>
  <ul class="nav flex-column small">
    <li class="nav-item">
      <a class="nav-link py-1" href="<?php echo url_for('@help_index') ?>">
        <i class="bi bi-house me-1"></i><?php echo __('Help Home') ?>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link py-1" href="<?php echo url_for('@help_article_view?slug=user-manual') ?>">
        <i class="bi bi-journal-text me-1"></i><?php echo __('User Manual') ?>
      </a>
    </li>
  </ul>
</div>
