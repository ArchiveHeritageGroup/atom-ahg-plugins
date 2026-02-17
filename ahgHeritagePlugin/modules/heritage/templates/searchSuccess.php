<?php
/**
 * Heritage Search Results Page.
 *
 * Displays search results with filters sidebar.
 */

decorate_with('layout_2col');

// Helper to deeply convert Symfony escaped arrays to plain arrays
$toArray = function($val) use (&$toArray) {
    if (is_array($val)) {
        return array_map($toArray, $val);
    }
    if ($val instanceof Traversable) {
        return array_map($toArray, iterator_to_array($val));
    }
    return $val;
};

$resultsRaw = $toArray($results ?? []);
$totalResults = $resultsRaw['total'] ?? 0;
$currentPage = $resultsRaw['page'] ?? 1;
$totalPages = $resultsRaw['pages'] ?? 1;
$searchResults = $toArray($resultsRaw['results'] ?? []);
$facets = $toArray($resultsRaw['facets'] ?? []);
$suggestions = $toArray($resultsRaw['suggestions'] ?? []);
$searchId = $resultsRaw['search_id'] ?? 0;
$parsedQuery = $toArray($resultsRaw['parsed_query'] ?? []);
$termMatches = $toArray($resultsRaw['term_matches'] ?? []);

// Identify unmatched search terms
$unmatchedTerms = [];
$matchedTerms = [];
foreach ($termMatches as $tm) {
    if (!($tm['matched'] ?? true)) {
        $unmatchedTerms[] = $tm['term'];
    } else {
        $matchedTerms[] = $tm['term'];
    }
}

// Convert filterOptions and their nested values
$filterOptionsRaw = $toArray($filterOptions ?? []);
$filterOptions = [];
foreach ($filterOptionsRaw as $fo) {
    $foArray = $toArray($fo);
    $foArray['values'] = $toArray($foArray['values'] ?? []);
    $filterOptions[] = $foArray;
}

// Deep convert filters to plain array
$filters = $toArray($filters ?? []);
?>

<?php slot('title'); ?>
<div class="d-flex justify-content-between align-items-center">
    <h1 class="h3 mb-0">
        <?php if ($query): ?>
            Search: "<?php echo esc_specialchars($query); ?>"
        <?php else: ?>
            Browse Collections
        <?php endif; ?>
    </h1>
    <span class="badge bg-secondary"><?php echo number_format($totalResults); ?> results</span>
</div>
<?php if (!empty($unmatchedTerms)): ?>
<div class="alert alert-info mt-3 mb-0 py-2 small">
    <i class="fas fa-info-circle me-1"></i>
    No results found for <strong><?php echo esc_specialchars(implode(', ', $unmatchedTerms)); ?></strong>.
    <?php if (!empty($matchedTerms)): ?>
        Showing results matching: <strong><?php echo esc_specialchars(implode(', ', $matchedTerms)); ?></strong>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>
<div class="heritage-search-sidebar">

    <!-- Search Box -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="<?php echo url_for(['module' => 'heritage', 'action' => 'search']); ?>" method="get">
                <div class="input-group">
                    <input type="text"
                           name="q"
                           class="form-control"
                           placeholder="Search..."
                           value="<?php echo esc_specialchars($query); ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>

                <!-- Preserve existing filters -->
                <?php foreach ($filters as $filterCode => $filterValues): ?>
                    <?php foreach ((array) $filterValues as $val): ?>
                    <input type="hidden" name="<?php echo esc_specialchars($filterCode); ?>[]" value="<?php echo esc_specialchars($val); ?>">
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </form>
        </div>
    </div>

    <!-- Active Filters -->
    <?php if (!empty($filters)): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 pb-0">
            <h5 class="card-title h6 mb-0">Active Filters</h5>
        </div>
        <div class="card-body">
            <?php foreach ($filters as $filterCode => $filterValues): ?>
                <?php foreach ((array) $filterValues as $val): ?>
                <?php
                // Build URL without this filter
                $newFilters = $filters;
                $newFilters[$filterCode] = array_diff((array) $newFilters[$filterCode], [$val]);
                if (empty($newFilters[$filterCode])) {
                    unset($newFilters[$filterCode]);
                }
                $removeUrl = url_for(['module' => 'heritage', 'action' => 'search', 'q' => $query]) .
                    (empty($newFilters) ? '' : '&' . http_build_query($newFilters));
                ?>
                <a href="<?php echo $removeUrl; ?>" class="btn btn-sm btn-outline-secondary mb-1 me-1">
                    <?php echo esc_specialchars($val); ?>
                    <i class="fas fa-times ms-1"></i>
                </a>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <div class="mt-2">
                <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'search', 'q' => $query]); ?>" class="small text-muted">
                    Clear all filters
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filter Options -->
    <?php foreach ($filterOptions as $filterOption): ?>
    <?php if ($filterOption['show_in_search'] && !empty($filterOption['values'])): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent border-0 d-flex align-items-center">
            <?php if (!empty($filterOption['icon'])): ?>
            <i class="<?php echo esc_specialchars($filterOption['icon']); ?> me-2 text-muted"></i>
            <?php endif; ?>
            <h5 class="card-title h6 mb-0"><?php echo esc_specialchars($filterOption['label']); ?></h5>
        </div>
        <div class="card-body pt-0">
            <ul class="list-unstyled mb-0">
                <?php foreach (array_slice($filterOption['values'], 0, 10) as $value): ?>
                <?php
                $filterCode = $filterOption['code'];
                $filterValues = $filters[$filterCode] ?? [];
                $isSelected = !empty($filterValues) && in_array($value['value'], (array) $filterValues);

                // Create a fresh copy for URL building
                $newFilters = [];
                foreach ($filters as $k => $v) {
                    $newFilters[$k] = is_array($v) ? array_values($v) : $v;
                }

                if ($isSelected) {
                    $newFilters[$filterCode] = array_values(array_diff((array) ($newFilters[$filterCode] ?? []), [$value['value']]));
                    if (empty($newFilters[$filterCode])) {
                        unset($newFilters[$filterCode]);
                    }
                } else {
                    if (!isset($newFilters[$filterCode])) {
                        $newFilters[$filterCode] = [];
                    }
                    $newFilters[$filterCode][] = $value['value'];
                }
                ?>
                <li class="mb-1">
                    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'search', 'q' => $query]); ?>&<?php echo http_build_query($newFilters); ?>"
                       class="text-decoration-none d-flex justify-content-between align-items-center py-1 <?php echo $isSelected ? 'fw-bold text-primary' : 'text-dark'; ?>">
                        <span class="text-truncate">
                            <?php if ($isSelected): ?><i class="fas fa-check2 me-1"></i><?php endif; ?>
                            <?php echo esc_specialchars($value['label']); ?>
                        </span>
                        <span class="badge bg-light text-muted"><?php echo number_format($value['count']); ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php if (count($filterOption['values']) > 10): ?>
            <a href="#" class="small text-muted" data-bs-toggle="modal" data-bs-target="#filter-modal-<?php echo esc_specialchars($filterOption['code']); ?>">
                Show all <?php echo count($filterOption['values']); ?> options
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>

    <!-- Back to Landing -->
    <div class="mt-4">
        <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'landing']); ?>" class="btn btn-outline-secondary w-100">
            <i class="fas fa-home me-2"></i> Back to Home
        </a>
    </div>

</div>
<?php end_slot(); ?>

<!-- Main Content: Search Results -->
<div class="heritage-search-results">

    <!-- No Results -->
    <?php if (empty($searchResults)): ?>
    <div class="text-center py-5">
        <i class="fas fa-search display-1 text-muted mb-4"></i>
        <h2 class="h4">No results found</h2>
        <p class="text-muted">
            <?php if ($query): ?>
                We couldn't find anything matching "<?php echo esc_specialchars($query); ?>".
            <?php else: ?>
                Try adjusting your filters or search terms.
            <?php endif; ?>
        </p>

        <?php if (!empty($suggestions)): ?>
        <div class="mt-4">
            <p class="mb-2">Did you mean:</p>
            <?php foreach ($suggestions as $suggestion): ?>
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'search', 'q' => $suggestion]); ?>" class="btn btn-outline-primary btn-sm me-2 mb-2">
                <?php echo esc_specialchars($suggestion); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'search']); ?>" class="btn btn-primary">
                Browse all items
            </a>
        </div>
    </div>

    <?php else: ?>

    <!-- Results List -->
    <div class="heritage-results-list heritage-results" data-search-id="<?php echo (int) $searchId; ?>">
        <?php foreach ($searchResults as $index => $result): ?>
        <article class="heritage-result-item card border-0 shadow-sm mb-3">
            <div class="row g-0">
                <!-- Thumbnail -->
                <div class="col-md-3">
                    <a href="<?php echo esc_specialchars($result['url']); ?>"
                       class="d-block h-100"
                       data-item-id="<?php echo (int) ($result['id'] ?? 0); ?>"
                       data-position="<?php echo $index + 1; ?>">
                        <?php if (!empty($result['thumbnail'])): ?>
                        <img src="<?php echo esc_specialchars($result['thumbnail']); ?>"
                             alt="<?php echo esc_specialchars($result['title']); ?>"
                             class="img-fluid rounded-start h-100 object-fit-cover"
                             style="max-height: 180px; width: 100%;"
                             loading="lazy"
                             onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'bg-light h-100 d-flex align-items-center justify-content-center text-muted rounded-start\' style=\'min-height: 150px;\'><i class=\'fas fa-image fs-1\'></i></div>';">
                        <?php else: ?>
                        <?php
                        $iconClass = match($result['media_type'] ?? null) {
                            'image' => 'fas fa-image',
                            'video' => 'fas fa-video',
                            'audio' => 'fas fa-music',
                            'model' => 'fas fa-cube',
                            'document' => 'bi-file-pdf',
                            'text' => 'bi-file-text',
                            default => 'bi-archive'
                        };
                        $bgClass = match($result['media_type'] ?? null) {
                            'image' => 'bg-info',
                            'video' => 'bg-danger',
                            'audio' => 'bg-warning',
                            'model' => 'bg-success',
                            'document' => 'bg-primary',
                            default => 'bg-secondary'
                        };
                        ?>
                        <div class="<?php echo $bgClass; ?> bg-opacity-25 h-100 d-flex align-items-center justify-content-center rounded-start" style="min-height: 150px;">
                            <i class="fas <?php echo $iconClass; ?> fs-1 text-<?php echo str_replace('bg-', '', $bgClass); ?>"></i>
                        </div>
                        <?php endif; ?>
                    </a>
                </div>

                <!-- Content -->
                <div class="col-md-9">
                    <div class="card-body">
                        <!-- Title -->
                        <h3 class="h5 card-title mb-2">
                            <a href="<?php echo esc_specialchars($result['url']); ?>"
                               class="text-decoration-none"
                               data-item-id="<?php echo (int) ($result['id'] ?? 0); ?>"
                               data-position="<?php echo $index + 1; ?>">
                                <?php echo esc_specialchars($result['title']); ?>
                            </a>
                        </h3>

                        <!-- Metadata -->
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <?php if (!empty($result['type'])): ?>
                            <span class="badge bg-light text-dark">
                                <i class="fas fa-tag me-1"></i><?php echo esc_specialchars($result['type']); ?>
                            </span>
                            <?php endif; ?>

                            <?php if (!empty($result['date'])): ?>
                            <span class="badge bg-light text-dark">
                                <i class="fas fa-calendar me-1"></i><?php echo esc_specialchars($result['date']); ?>
                            </span>
                            <?php endif; ?>

                            <?php if (!empty($result['collection'])): ?>
                            <span class="badge bg-light text-dark">
                                <i class="fas fa-layer-group me-1"></i><?php echo esc_specialchars($result['collection']); ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <!-- Snippet -->
                        <?php if (!empty($result['snippet'])): ?>
                        <p class="card-text text-muted small mb-0">
                            <?php echo esc_specialchars($result['snippet']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav aria-label="Search results pagination" class="mt-4">
        <ul class="pagination justify-content-center">
            <!-- Previous -->
            <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="<?php echo url_for(['module' => 'heritage', 'action' => 'search', 'q' => $query, 'page' => $currentPage - 1]) . '&' . http_build_query($filters); ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>

            <?php
            $startPage = max(1, $currentPage - 2);
            $endPage = min($totalPages, $currentPage + 2);
            ?>

            <?php if ($startPage > 1): ?>
            <li class="page-item">
                <a class="page-link" href="<?php echo url_for(['module' => 'heritage', 'action' => 'search', 'q' => $query, 'page' => 1]) . '&' . http_build_query($filters); ?>">1</a>
            </li>
            <?php if ($startPage > 2): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
            <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                <a class="page-link" href="<?php echo url_for(['module' => 'heritage', 'action' => 'search', 'q' => $query, 'page' => $i]) . '&' . http_build_query($filters); ?>">
                    <?php echo $i; ?>
                </a>
            </li>
            <?php endfor; ?>

            <?php if ($endPage < $totalPages): ?>
            <?php if ($endPage < $totalPages - 1): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; ?>
            <li class="page-item">
                <a class="page-link" href="<?php echo url_for(['module' => 'heritage', 'action' => 'search', 'q' => $query, 'page' => $totalPages]) . '&' . http_build_query($filters); ?>">
                    <?php echo $totalPages; ?>
                </a>
            </li>
            <?php endif; ?>

            <!-- Next -->
            <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                <a class="page-link" href="<?php echo url_for(['module' => 'heritage', 'action' => 'search', 'q' => $query, 'page' => $currentPage + 1]) . '&' . http_build_query($filters); ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>

    <?php endif; ?>

</div>

<!-- Initialize click tracking -->
<?php if ($searchId > 0): ?>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof HeritageApp !== 'undefined' && HeritageApp.setSearchContext) {
        HeritageApp.setSearchContext(<?php echo (int) $searchId; ?>);
    }
});
</script>
<?php endif; ?>

<!-- Show parsed query info for debugging (only if intent is detected) -->
<?php if (!empty($parsedQuery['intent']) && $parsedQuery['intent'] !== 'EXPLORE'): ?>
<div class="d-none" id="search-debug" data-intent="<?php echo esc_specialchars($parsedQuery['intent']); ?>"
     data-entities="<?php echo esc_specialchars(json_encode($parsedQuery['entities'] ?? [])); ?>">
</div>
<?php endif; ?>
