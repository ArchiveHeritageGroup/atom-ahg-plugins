<?php use_helper('I18N') ?>

<div class="container-fluid py-4">
  <div class="row">

    <!-- Sidebar -->
    <div class="col-lg-3 col-md-4 mb-4">
      <?php include_partial('help/helpSidebar', ['categories' => \AhgHelp\Services\HelpArticleService::getCategories()]) ?>
    </div>

    <!-- Main Content -->
    <div class="col-lg-9 col-md-8">

      <!-- Breadcrumb -->
      <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?php echo url_for('@help_index') ?>"><?php echo __('Help Center') ?></a></li>
          <li class="breadcrumb-item active"><?php echo htmlspecialchars($category) ?></li>
        </ol>
      </nav>

      <h1 class="mb-4"><?php echo htmlspecialchars($category) ?></h1>

      <?php foreach ($grouped as $subcategory => $articles): ?>
        <h2 class="h5 text-muted mt-4 mb-3">
          <i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($subcategory) ?>
          <span class="badge bg-secondary ms-1"><?php echo count($articles) ?></span>
        </h2>

        <div class="list-group mb-3">
          <?php foreach ($articles as $article): ?>
            <a href="<?php echo url_for('@help_article_view?slug=' . urlencode($article['slug'])) ?>"
              class="list-group-item list-group-item-action">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <h6 class="mb-1"><?php echo htmlspecialchars($article['title']) ?></h6>
                  <?php if (!empty($article['related_plugin'])): ?>
                    <small class="text-muted"><i class="bi bi-puzzle me-1"></i><?php echo $article['related_plugin'] ?></small>
                  <?php endif; ?>
                </div>
                <div class="text-end text-nowrap ms-3">
                  <small class="text-muted d-block"><?php echo number_format($article['word_count']) ?> words</small>
                  <small class="text-muted"><?php echo date('M j, Y', strtotime($article['updated_at'])) ?></small>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>

    </div>
  </div>
</div>
