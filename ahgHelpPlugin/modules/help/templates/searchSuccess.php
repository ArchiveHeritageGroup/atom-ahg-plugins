<?php use_helper('I18N') ?>
<?php
  $rawQuery = sfOutputEscaper::unescape($query);
  $rawArticleResults = sfOutputEscaper::unescape($articleResults);
  $rawSectionResults = sfOutputEscaper::unescape($sectionResults);
?>

<div class="container-fluid py-4">
  <div class="row">

    <!-- Sidebar -->
    <div class="col-lg-3 col-md-4 mb-4">
      <?php include_partial('help/helpSidebar', ['categories' => sfOutputEscaper::unescape(\AhgHelp\Services\HelpArticleService::getCategories())]) ?>
    </div>

    <!-- Main Content -->
    <div class="col-lg-9 col-md-8">

      <!-- Breadcrumb -->
      <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="<?php echo url_for('@help_index') ?>"><?php echo __('Help Center') ?></a></li>
          <li class="breadcrumb-item active"><?php echo __('Search Results') ?></li>
        </ol>
      </nav>

      <!-- Search Bar -->
      <form action="<?php echo url_for('@help_search') ?>" method="get" class="mb-4">
        <div class="input-group input-group-lg">
          <input type="text" name="q" class="form-control" id="help-search-results-page"
            value="<?php echo htmlspecialchars($rawQuery) ?>"
            placeholder="<?php echo __('Search help articles...') ?>" autocomplete="off">
          <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> <?php echo __('Search') ?></button>
        </div>
        <div id="help-search-results-dropdown" class="help-search-dropdown d-none"></div>
      </form>

      <?php if (empty($rawQuery)): ?>
        <p class="text-muted"><?php echo __('Enter a search term to find help articles.') ?></p>
      <?php elseif (empty($rawArticleResults) && empty($rawSectionResults)): ?>
        <div class="alert alert-info">
          <i class="bi bi-info-circle me-1"></i>
          <?php echo __('No results found for "%1%". Try different keywords.', ['%1%' => htmlspecialchars($rawQuery)]) ?>
        </div>
      <?php else: ?>

        <!-- Article Results -->
        <?php if (!empty($rawArticleResults)): ?>
          <h2 class="h5 mb-3">
            <?php echo __('Articles') ?>
            <span class="badge bg-primary ms-1"><?php echo count($rawArticleResults) ?></span>
          </h2>
          <div class="list-group mb-4">
            <?php foreach ($rawArticleResults as $result): ?>
              <a href="<?php echo url_for('@help_article_view?slug=' . urlencode($result['slug'])) ?>"
                class="list-group-item list-group-item-action">
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <h6 class="mb-1"><?php echo htmlspecialchars($result['title']) ?></h6>
                    <span class="badge bg-info me-1"><?php echo htmlspecialchars($result['category']) ?></span>
                    <?php if (!empty($result['subcategory'])): ?>
                      <span class="badge bg-light text-dark"><?php echo htmlspecialchars($result['subcategory']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($result['snippet'])): ?>
                      <p class="mb-0 mt-1 small text-muted help-search-snippet">
                        <?php echo htmlspecialchars(substr($result['snippet'], 0, 200)) ?>...
                      </p>
                    <?php endif; ?>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Section Results -->
        <?php if (!empty($rawSectionResults)): ?>
          <h2 class="h5 mb-3">
            <?php echo __('Sections') ?>
            <span class="badge bg-secondary ms-1"><?php echo count($rawSectionResults) ?></span>
          </h2>
          <div class="list-group mb-4">
            <?php foreach ($rawSectionResults as $result): ?>
              <a href="<?php echo url_for('@help_article_view?slug=' . urlencode($result['slug'])) ?>#<?php echo htmlspecialchars($result['anchor']) ?>"
                class="list-group-item list-group-item-action">
                <div>
                  <h6 class="mb-1">
                    <?php echo htmlspecialchars($result['heading']) ?>
                    <small class="text-muted ms-2"><?php echo __('in %1%', ['%1%' => htmlspecialchars($result['article_title'])]) ?></small>
                  </h6>
                  <span class="badge bg-info"><?php echo htmlspecialchars($result['category']) ?></span>
                  <?php if (!empty($result['snippet'])): ?>
                    <p class="mb-0 mt-1 small text-muted"><?php echo htmlspecialchars(substr($result['snippet'], 0, 200)) ?>...</p>
                  <?php endif; ?>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      <?php endif; ?>

    </div>
  </div>
</div>
