<?php use_helper('I18N') ?>

<div class="container-fluid py-4">
  <div class="row">

    <!-- Sidebar -->
    <div class="col-lg-3 col-md-4 mb-4">
      <?php include_partial('help/helpSidebar', ['categories' => $categories]) ?>
    </div>

    <!-- Main Content -->
    <div class="col-lg-9 col-md-8">

      <!-- Hero Search -->
      <div class="card bg-primary text-white mb-4">
        <div class="card-body text-center py-5">
          <h1 class="mb-3"><i class="bi bi-question-circle me-2"></i><?php echo __('Help Center') ?></h1>
          <p class="lead mb-4"><?php echo __('Search the documentation or browse by category') ?></p>
          <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
              <form action="<?php echo url_for('@help_search') ?>" method="get" class="input-group input-group-lg">
                <input type="text" name="q" class="form-control" id="help-search-main"
                  placeholder="<?php echo __('Search help articles...') ?>" autocomplete="off">
                <button type="submit" class="btn btn-light"><i class="bi bi-search"></i></button>
              </form>
              <div id="help-search-results-dropdown" class="help-search-dropdown d-none"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Category Cards -->
      <h2 class="h4 mb-3"><?php echo __('Browse by Category') ?></h2>
      <div class="row g-3 mb-4">
        <?php foreach ($categories as $cat): ?>
          <?php $catName = $cat['category']; ?>
          <div class="col-lg-4 col-md-6">
            <a href="<?php echo url_for('@help_category?category=' . urlencode($catName)) ?>" class="text-decoration-none">
              <div class="card h-100 help-category-card">
                <div class="card-body">
                  <div class="d-flex align-items-center mb-2">
                    <i class="bi <?php echo isset($categoryIcons[$catName]) ? $categoryIcons[$catName] : 'bi-folder' ?> fs-3 text-primary me-2"></i>
                    <h5 class="card-title mb-0"><?php echo $catName ?></h5>
                  </div>
                  <p class="card-text text-muted small">
                    <?php echo isset($categoryDescriptions[$catName]) ? $categoryDescriptions[$catName] : '' ?>
                  </p>
                  <span class="badge bg-secondary"><?php echo $cat['article_count'] ?> <?php echo __('articles') ?></span>
                </div>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Recently Updated -->
      <?php if (!empty($recentArticles)): ?>
        <h2 class="h4 mb-3"><?php echo __('Recently Updated') ?></h2>
        <div class="list-group mb-4">
          <?php foreach ($recentArticles as $article): ?>
            <a href="<?php echo url_for('@help_article_view?slug=' . urlencode($article['slug'])) ?>"
              class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
              <div>
                <strong><?php echo htmlspecialchars($article['title']) ?></strong>
                <span class="badge bg-info ms-2"><?php echo $article['category'] ?></span>
                <?php if (!empty($article['subcategory'])): ?>
                  <span class="badge bg-light text-dark ms-1"><?php echo $article['subcategory'] ?></span>
                <?php endif; ?>
              </div>
              <small class="text-muted"><?php echo date('M j, Y', strtotime($article['updated_at'])) ?></small>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>
