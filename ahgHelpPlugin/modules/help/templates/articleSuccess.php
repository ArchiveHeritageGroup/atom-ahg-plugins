<?php use_helper('I18N') ?>

<div class="container-fluid py-4">
  <div class="row">

    <!-- TOC Sidebar -->
    <div class="col-lg-3 col-md-4 mb-4">
      <div class="help-toc-sidebar sticky-top" style="top: 1rem;">
        <!-- Search -->
        <div class="mb-3">
          <form action="<?php echo url_for('@help_search') ?>" method="get" class="input-group input-group-sm">
            <input type="text" name="q" class="form-control" id="help-search-article"
              placeholder="<?php echo __('Search help...') ?>" autocomplete="off">
            <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
          </form>
          <div id="help-search-results-dropdown" class="help-search-dropdown d-none"></div>
        </div>

        <!-- Back link -->
        <a href="<?php echo url_for('@help_category?category=' . urlencode($article['category'])) ?>" class="d-block mb-3 small">
          <i class="bi bi-arrow-left me-1"></i><?php echo __('Back to %1%', ['%1%' => $article['category']]) ?>
        </a>

        <!-- Table of Contents -->
        <?php if (!empty($toc)): ?>
          <h6 class="text-uppercase text-muted mb-2"><?php echo __('Contents') ?></h6>
          <nav class="help-toc-nav">
            <ul class="nav flex-column">
              <?php foreach ($toc as $entry): ?>
                <li class="nav-item">
                  <a class="nav-link py-1 <?php echo $entry['level'] === 3 ? 'ms-3 small' : '' ?>"
                    href="#<?php echo htmlspecialchars($entry['anchor']) ?>">
                    <?php echo htmlspecialchars($entry['text']) ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </nav>
        <?php endif; ?>

        <!-- Metadata -->
        <div class="mt-3 pt-3 border-top small text-muted">
          <div class="mb-1">
            <i class="bi bi-tag me-1"></i>
            <a href="<?php echo url_for('@help_category?category=' . urlencode($article['category'])) ?>">
              <?php echo $article['category'] ?>
            </a>
            <?php if (!empty($article['subcategory'])): ?>
              / <?php echo $article['subcategory'] ?>
            <?php endif; ?>
          </div>
          <div class="mb-1"><i class="bi bi-body-text me-1"></i><?php echo number_format($article['word_count']) ?> words</div>
          <div><i class="bi bi-clock me-1"></i><?php echo date('M j, Y', strtotime($article['updated_at'])) ?></div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="col-lg-9 col-md-8">

      <!-- Breadcrumb -->
      <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?php echo url_for('@help_index') ?>"><?php echo __('Help Center') ?></a></li>
          <li class="breadcrumb-item">
            <a href="<?php echo url_for('@help_category?category=' . urlencode($article['category'])) ?>">
              <?php echo $article['category'] ?>
            </a>
          </li>
          <li class="breadcrumb-item active"><?php echo htmlspecialchars($article['title']) ?></li>
        </ol>
      </nav>

      <!-- Article Content -->
      <article class="help-article-content">
        <?php echo $article['body_html'] ?>
      </article>

      <!-- Prev / Next Navigation -->
      <nav class="d-flex justify-content-between mt-5 pt-4 border-top">
        <?php if ($prevArticle): ?>
          <a href="<?php echo url_for('@help_article_view?slug=' . urlencode($prevArticle['slug'])) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-chevron-left me-1"></i><?php echo htmlspecialchars($prevArticle['title']) ?>
          </a>
        <?php else: ?>
          <span></span>
        <?php endif; ?>

        <?php if ($nextArticle): ?>
          <a href="<?php echo url_for('@help_article_view?slug=' . urlencode($nextArticle['slug'])) ?>" class="btn btn-outline-secondary">
            <?php echo htmlspecialchars($nextArticle['title']) ?><i class="bi bi-chevron-right ms-1"></i>
          </a>
        <?php else: ?>
          <span></span>
        <?php endif; ?>
      </nav>

    </div>
  </div>
</div>
