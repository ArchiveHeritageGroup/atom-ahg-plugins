<?php use_helper('I18N') ?>
<?php
  // Unescape to get raw arrays — Symfony 1.x auto-escapes all template variables
  $rawArticle = sfOutputEscaper::unescape($article);
  $rawToc = sfOutputEscaper::unescape($toc);
  $rawPrev = sfOutputEscaper::unescape($prevArticle);
  $rawNext = sfOutputEscaper::unescape($nextArticle);
?>

<div class="container-fluid py-4">
  <div class="row">

    <!-- TOC Sidebar -->
    <div class="col-lg-3 col-md-4 mb-4 help-toc-col">
      <div class="help-toc-sidebar">
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
        <a href="<?php echo url_for('@help_category?category=' . urlencode($rawArticle['category'])) ?>" class="d-block mb-3 small">
          <i class="bi bi-arrow-left me-1"></i><?php echo __('Back to %1%', ['%1%' => htmlspecialchars($rawArticle['category'])]) ?>
        </a>

        <!-- Table of Contents -->
        <?php if (!empty($rawToc)): ?>
          <h6 class="text-uppercase text-muted mb-2"><?php echo __('Contents') ?></h6>
          <nav class="help-toc-nav">
            <ul class="nav flex-column">
              <?php foreach ($rawToc as $entry): ?>
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
            <a href="<?php echo url_for('@help_category?category=' . urlencode($rawArticle['category'])) ?>">
              <?php echo htmlspecialchars($rawArticle['category']) ?>
            </a>
            <?php if (!empty($rawArticle['subcategory'])): ?>
              / <?php echo htmlspecialchars($rawArticle['subcategory']) ?>
            <?php endif; ?>
          </div>
          <div class="mb-1"><i class="bi bi-body-text me-1"></i><?php echo number_format($rawArticle['word_count']) ?> words</div>
          <div><i class="bi bi-clock me-1"></i><?php echo date('M j, Y', strtotime($rawArticle['updated_at'])) ?></div>
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
            <a href="<?php echo url_for('@help_category?category=' . urlencode($rawArticle['category'])) ?>">
              <?php echo htmlspecialchars($rawArticle['category']) ?>
            </a>
          </li>
          <li class="breadcrumb-item active"><?php echo htmlspecialchars($rawArticle['title']) ?></li>
        </ol>
      </nav>

      <!-- Article Content -->
      <article class="help-article-content">
        <?php echo $rawArticle['body_html'] ?>
      </article>

      <!-- Related manuals (linked from the article's plugin) -->
      <?php
        $relPlugin = $rawArticle['related_plugin'] ?? '';
        if ($relPlugin) {
            static $manualMap = null;
            if ($manualMap === null) {
                $mapFile = sfConfig::get('sf_plugins_dir') . '/ahgHelpPlugin/lib/manualLinks.php';
                $manualMap = is_file($mapFile) ? (include $mapFile) : [];
            }
            $toHelpSlug = function ($filename) {
                $filename = basename($filename, ".md");
                $slug = preg_replace("/_?user[_-]?guide$/i", "-user-guide", $filename);
                $slug = preg_replace("/_?user[_-]?manual$/i", "-user-manual", $slug);
                $slug = strtolower(str_replace(["_", " "], "-", $slug));
                $slug = preg_replace("/-+/", "-", $slug);

                return trim($slug, "-");
            };
            $techUrl = url_for("@help_article_view?slug=" . urlencode($toHelpSlug($relPlugin . ".md")));
            $userFile = $manualMap[$relPlugin] ?? null;
            $userUrl = $userFile ? url_for("@help_article_view?slug=" . urlencode($toHelpSlug($userFile))) : null;
      ?>
        <div class="card mt-4 help-related-manuals">
          <div class="card-header bg-light"><i class="bi bi-book me-2"></i><?php echo __('Full manuals') ?></div>
          <div class="card-body d-flex flex-wrap gap-2">
            <?php if ($userUrl): ?>
              <a href="<?php echo htmlspecialchars($userUrl) ?>" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-person-lines-fill me-1"></i><?php echo __('User manual') ?>
              </a>
            <?php endif; ?>
            <a href="<?php echo htmlspecialchars($techUrl) ?>" class="btn btn-outline-secondary btn-sm">
              <i class="bi bi-gear me-1"></i><?php echo __('Technical manual') ?>
            </a>
          </div>
        </div>
      <?php } ?>

      <!-- Prev / Next Navigation -->
      <nav class="help-article-nav d-flex justify-content-between mt-5 pt-4 border-top">
        <?php if ($rawPrev): ?>
          <a href="<?php echo url_for('@help_article_view?slug=' . urlencode($rawPrev['slug'])) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-chevron-left me-1"></i><?php echo htmlspecialchars($rawPrev['title']) ?>
          </a>
        <?php else: ?>
          <span></span>
        <?php endif; ?>

        <?php if ($rawNext): ?>
          <a href="<?php echo url_for('@help_article_view?slug=' . urlencode($rawNext['slug'])) ?>" class="btn btn-outline-secondary">
            <?php echo htmlspecialchars($rawNext['title']) ?><i class="bi bi-chevron-right ms-1"></i>
          </a>
        <?php else: ?>
          <span></span>
        <?php endif; ?>
      </nav>

    </div>
  </div>
</div>
