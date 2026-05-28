<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Advanced Search'); ?></h1>
<?php end_slot(); ?>

<?php
  $results = $sf_data->getRaw('results');
  $total   = (int) $sf_data->getRaw('total');
  $page    = (int) $sf_data->getRaw('page');
  $query   = $sf_data->getRaw('query');
  $filters = $sf_data->getRaw('filters');
  $error   = $sf_data->getRaw('error');
  $totalPages = (int) $sf_data->getRaw('totalPages');
?>

<div class="row">
  <?php /* ============================================================
       LEFT — Search form + facet filters
       ============================================================ */ ?>
  <div class="col-md-4 col-lg-3">

    <?php /* Search form */ ?>
    <div class="card mb-3">
      <div class="card-header">
        <h6 class="mb-0"><?php echo __('Search'); ?></h6>
      </div>
      <div class="card-body">
        <form method="get" action="<?php echo url_for(['module' => 'library', 'action' => 'advanced-search']); ?>" id="adv-search-form">

          <?php /* Lucene query input */ ?>
          <div class="mb-3">
            <label for="q-input" class="form-label small fw-bold">
              <?php echo __('Query'); ?>
            </label>
            <input type="text"
                   class="form-control"
                   name="query"
                   id="q-input"
                   value="<?php echo esc_entities($query ?? ''); ?>"
                   placeholder='e.g. "digital archives" AND preservation'>
            <div class="form-text small">
              <?php echo __('Use'); ?>
              <code>AND</code>, <code>OR</code>, <code>NOT</code>,
              <code>field:value</code>,
              <code>"exact phrase"</code>
            </div>
          </div>

          <?php /* Material type filter */ ?>
          <div class="mb-3">
            <label for="mat-type" class="form-label small fw-bold">
              <?php echo __('Material type'); ?>
            </label>
            <select name="material_type" id="mat-type" class="form-select">
              <option value="">— <?php echo __('Any'); ?> —</option>
              <?php
                $matTypes = ['book','ebook','serial','journal','magazine','thesis','conference paper','video','audio','map','photograph','cd','dvd'];
                $selMat = $filters['material_type'] ?? '';
                foreach ($matTypes as $mt):
              ?>
                <option value="<?php echo esc_entities($mt); ?>" <?php echo $selMat === $mt ? 'selected' : ''; ?>>
                  <?php echo esc_entities(ucfirst($mt)); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <?php /* Language filter */ ?>
          <div class="mb-3">
            <label for="lang-filter" class="form-label small fw-bold">
              <?php echo __('Language'); ?>
            </label>
            <select name="language" id="lang-filter" class="form-select">
              <option value="">— <?php echo __('Any'); ?> —</option>
              <?php
                $langs = ['English','Afrikaans','isiZulu','isiXhosa','Sepedi','Sesotho','Setswana','Xitsonga','Tshivenda','isiNdebele'];
                $selLang = $filters['language'] ?? '';
                foreach ($langs as $lang):
              ?>
                <option value="<?php echo esc_entities($lang); ?>" <?php echo $selLang === $lang ? 'selected' : ''; ?>>
                  <?php echo esc_entities($lang); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <?php /* Date range */ ?>
          <div class="row mb-3">
            <div class="col-6">
              <label for="date-from" class="form-label small fw-bold">
                <?php echo __('From year'); ?>
              </label>
              <input type="text"
                     name="date_from"
                     id="date-from"
                     class="form-control"
                     value="<?php echo esc_entities($filters['date_from'] ?? ''); ?>"
                     placeholder="1900">
            </div>
            <div class="col-6">
              <label for="date-to" class="form-label small fw-bold">
                <?php echo __('To year'); ?>
              </label>
              <input type="text"
                     name="date_to"
                     id="date-to"
                     class="form-control"
                     value="<?php echo esc_entities($filters['date_to'] ?? ''); ?>"
                     placeholder="<?php echo date('Y'); ?>">
            </div>
          </div>

          <?php /* Sort */ ?>
          <div class="mb-3">
            <label for="sort-select" class="form-label small fw-bold">
              <?php echo __('Sort by'); ?>
            </label>
            <select name="sort" id="sort-select" class="form-select">
              <?php
                $sorts = [
                  'relevance'  => __('Relevance'),
                  'year_desc'  => __('Year (newest first)'),
                  'year_asc'   => __('Year (oldest first)'),
                  'title_asc'  => __('Title (A-Z)'),
                ];
                $selSort = $filters['sort'] ?? 'relevance';
                foreach ($sorts as $k => $v):
              ?>
                <option value="<?php echo $k; ?>" <?php echo $selSort === $k ? 'selected' : ''; ?>>
                  <?php echo $v; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-search me-1"></i><?php echo __('Search'); ?>
            </button>
            <a href="<?php echo url_for(['module' => 'library', 'action' => 'advanced-search']); ?>"
               class="btn btn-outline-secondary">
              <?php echo __('Clear'); ?>
            </a>
          </div>
        </form>
      </div>
    </div>

    <?php /* Quick reference */ ?>
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0"><?php echo __('Query syntax reference'); ?></h6>
      </div>
      <div class="card-body small">
        <dl>
          <dt><code>field:value</code></dt>
          <dd><?php echo __('Search within a field (e.g. <code>isbn:9780...</code>)'); ?></dd>

          <dt><code>"exact phrase"</code></dt>
          <dd><?php echo __('Match an exact phrase'); ?></dd>

          <dt><code>term1 AND term2</code></dt>
          <dd><?php echo __('Both terms must appear'); ?></dd>

          <dt><code>term1 OR term2</code></dt>
          <dd><?php echo __('Either term may appear'); ?></dd>

          <dt><code>term1 NOT term2</code></dt>
          <dd><?php echo __('term1 without term2'); ?></dd>

          <dt><code>year:[2000 TO 2010]</code></dt>
          <dd><?php echo __('Year range (inclusive)'); ?></dd>
        </dl>

        <hr>

        <p class="text-muted mb-1"><strong><?php echo __('Available fields'); ?></strong></p>
        <div class="d-flex flex-wrap gap-1">
          <?php foreach (['title','author','subject','isbn','issn','doi','publisher','year','material','language','call_number','dewey'] as $f): ?>
            <span class="badge bg-light text-dark border"><?php echo $f; ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  </div>

  <?php /* ============================================================
       RIGHT — Results
       ============================================================ */ ?>
  <div class="col-md-8 col-lg-9">

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?php echo esc_entities($error); ?></div>
    <?php endif; ?>

    <?php if (!empty($query) || !empty($filters['material_type']) || !empty($filters['language'])): ?>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <?php if ($total > 0): ?>
            <span class="text-muted">
              <?php echo __('%1% result(s) found', ['%1%' => $total]); ?>
              <?php if (!empty($query)): ?>
                <?php echo __('for'); ?> <strong><?php echo esc_entities($query); ?></strong>
              <?php endif; ?>
            </span>
          <?php else: ?>
            <span class="text-muted"><?php echo __('No results found.'); ?></span>
          <?php endif; ?>
        </div>
        <div>
          <?php if ($total > 0): ?>
            <span class="text-muted small">
              <?php echo __('Page %1% of %2%', ['%1%' => $page, '%2%' => $totalPages]); ?>
            </span>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if (empty($results)): ?>
      <?php if (empty($query) && empty($filters['material_type']) && empty($filters['language'])): ?>
        <div class="alert alert-info">
          <i class="fas fa-search me-2"></i>
          <?php echo __('Enter a query or set a filter to search the catalogue.'); ?>
        </div>
      <?php else: ?>
        <div class="alert alert-warning">
          <i class="fas fa-exclamation-circle me-2"></i>
          <?php echo __('No items matched your search. Try different terms or broaden your filters.'); ?>
        </div>
      <?php endif; ?>

    <?php else: ?>

      <div class="list-group">
        <?php foreach ($results as $item): ?>
          <div class="list-group-item list-group-item-action flex-column align-items-start py-3">
            <div class="d-flex w-100 justify-content-between align-items-start mb-1">
              <h5 class="mb-1">
                <?php if (!empty($item['url'])): ?>
                  <a href="<?php echo $item['url']; ?>">
                    <?php echo esc_entities($item['title']); ?>
                  </a>
                <?php else: ?>
                  <?php echo esc_entities($item['title']); ?>
                <?php endif; ?>
              </h5>
              <?php if (!empty($item['material_type'])): ?>
                <span class="badge bg-secondary text-nowrap ms-2">
                  <?php echo esc_entities(ucfirst($item['material_type'])); ?>
                </span>
              <?php endif; ?>
            </div>

            <div class="small text-muted mb-1">
              <?php if (!empty($item['creator'])): ?>
                <span class="me-3">
                  <i class="fas fa-user me-1"></i><?php echo esc_entities($item['creator']); ?>
                </span>
              <?php endif; ?>
              <?php if (!empty($item['publisher'])): ?>
                <span class="me-3">
                  <i class="fas fa-building me-1"></i><?php echo esc_entities($item['publisher']); ?>
                </span>
              <?php endif; ?>
              <?php if (!empty($item['publication_date'])): ?>
                <span class="me-3">
                  <i class="fas fa-calendar me-1"></i><?php echo esc_entities($item['publication_date']); ?>
                </span>
              <?php endif; ?>
            </div>

            <div class="small">
              <?php if (!empty($item['isbn'])): ?>
                <span class="me-3"><strong>ISBN:</strong> <?php echo esc_entities($item['isbn']); ?></span>
              <?php endif; ?>
              <?php if (!empty($item['issn'])): ?>
                <span class="me-3"><strong>ISSN:</strong> <?php echo esc_entities($item['issn']); ?></span>
              <?php endif; ?>
              <?php if (!empty($item['call_number'])): ?>
                <span class="me-3"><strong>Call No.:</strong> <code><?php echo esc_entities($item['call_number']); ?></code></span>
              <?php endif; ?>
              <?php if (!empty($item['language'])): ?>
                <span class="me-3"><strong>Lang:</strong> <?php echo esc_entities($item['language']); ?></span>
              <?php endif; ?>
            </div>

            <?php if (!empty($item['subjects'])): ?>
              <div class="mt-1">
                <?php foreach (preg_split('/;\s*/', $item['subjects']) as $subj): ?>
                  <?php $subj = trim($subj); if (empty($subj)) continue; ?>
                  <a href="<?php echo url_for(['module' => 'library', 'action' => 'advanced-search', 'query' => 'subject:' . rawurlencode($subj)]); ?>"
                     class="badge bg-light text-dark text-decoration-none border me-1">
                    <?php echo esc_entities($subj); ?>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <?php /* Pagination */ ?>
      <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
          <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
              <li class="page-item">
                <a class="page-link" href="<?php echo url_for(array_merge(['module' => 'library', 'action' => 'advanced-search'], array_filter(['query' => $query, 'material_type' => $filters['material_type'] ?? '', 'language' => $filters['language'] ?? '', 'date_from' => $filters['date_from'] ?? '', 'date_to' => $filters['date_to'] ?? '', 'sort' => $filters['sort'] ?? ''], fn($v) => !empty($v)) + ['page' => $page - 1])); ?>">
                  <i class="fas fa-chevron-left"></i>
                </a>
              </li>
            <?php endif; ?>

            <?php
              $startPage = max(1, $page - 2);
              $endPage   = min($totalPages, $page + 2);
            ?>
            <?php if ($startPage > 1): ?>
              <li class="page-item"><a class="page-link" href="<?php echo url_for(['module' => 'library', 'action' => 'advanced-search'] + array_filter(['query' => $query, 'material_type' => $filters['material_type'] ?? '', 'language' => $filters['language'] ?? '', 'date_from' => $filters['date_from'] ?? '', 'date_to' => $filters['date_to'] ?? '', 'sort' => $filters['sort'] ?? ''], fn($v) => !empty($v)) + ['page' => 1]); ?>">1</a></li>
              <?php if ($startPage > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
            <?php endif; ?>

            <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
              <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                <a class="page-link" href="<?php echo url_for(['module' => 'library', 'action' => 'advanced-search'] + array_filter(['query' => $query, 'material_type' => $filters['material_type'] ?? '', 'language' => $filters['language'] ?? '', 'date_from' => $filters['date_from'] ?? '', 'date_to' => $filters['date_to'] ?? '', 'sort' => $filters['sort'] ?? ''], fn($v) => !empty($v)) + ['page' => $p]); ?>">
                  <?php echo $p; ?>
                </a>
              </li>
            <?php endfor; ?>

            <?php if ($endPage < $totalPages): ?>
              <?php if ($endPage < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
              <li class="page-item"><a class="page-link" href="<?php echo url_for(['module' => 'library', 'action' => 'advanced-search'] + array_filter(['query' => $query, 'material_type' => $filters['material_type'] ?? '', 'language' => $filters['language'] ?? '', 'date_from' => $filters['date_from'] ?? '', 'date_to' => $filters['date_to'] ?? '', 'sort' => $filters['sort'] ?? ''], fn($v) => !empty($v)) + ['page' => $totalPages]); ?>"><?php echo $totalPages; ?></a></li>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
              <li class="page-item">
                <a class="page-link" href="<?php echo url_for(['module' => 'library', 'action' => 'advanced-search'] + array_filter(['query' => $query, 'material_type' => $filters['material_type'] ?? '', 'language' => $filters['language'] ?? '', 'date_from' => $filters['date_from'] ?? '', 'date_to' => $filters['date_to'] ?? '', 'sort' => $filters['sort'] ?? ''], fn($v) => !empty($v)) + ['page' => $page + 1]); ?>">
                  <i class="fas fa-chevron-right"></i>
                </a>
              </li>
            <?php endif; ?>
          </ul>
        </nav>
      <?php endif; ?>

    <?php endif; ?>

  </div>
</div>
