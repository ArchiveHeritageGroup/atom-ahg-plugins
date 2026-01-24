<?php use_helper('Display'); ?>
<?php $requestParams = $sf_request->getParameterHolder()->getAll(); ?>

<div class="container-fluid">
    <!-- Type Header -->
    <div class="type-header bg-<?php echo $typeInfo['color']; ?> text-white py-4 mb-4 rounded">
        <div class="container">
            <div class="d-flex align-items-center">
                <i class="fas <?php echo $typeInfo['icon']; ?> fa-3x me-4 opacity-75"></i>
                <div>
                    <h1 class="mb-1"><?php echo $typeInfo['title']; ?></h1>
                    <p class="mb-0 opacity-75"><?php echo $typeInfo['description']; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Type Navigation -->
    <div class="type-nav mb-4">
        <ul class="nav nav-pills justify-content-center">
            <li class="nav-item">
                <a class="nav-link <?php echo $objectType === 'archive' ? 'active' : ''; ?>" 
                   href="<?php echo url_for(['module' => 'displaySearch', 'action' => 'browse', 'type' => 'archive']); ?>">
                    <i class="fas fa-archive me-1"></i> Archives
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $objectType === 'museum' ? 'active' : ''; ?>" 
                   href="<?php echo url_for(['module' => 'displaySearch', 'action' => 'browse', 'type' => 'museum']); ?>">
                    <i class="fas fa-landmark me-1"></i> Museum
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $objectType === 'gallery' ? 'active' : ''; ?>" 
                   href="<?php echo url_for(['module' => 'displaySearch', 'action' => 'browse', 'type' => 'gallery']); ?>">
                    <i class="fas fa-palette me-1"></i> Gallery
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $objectType === 'library' ? 'active' : ''; ?>" 
                   href="<?php echo url_for(['module' => 'displaySearch', 'action' => 'browse', 'type' => 'library']); ?>">
                    <i class="fas fa-book me-1"></i> Books
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $objectType === 'dam' ? 'active' : ''; ?>" 
                   href="<?php echo url_for(['module' => 'displaySearch', 'action' => 'browse', 'type' => 'dam']); ?>">
                    <i class="fas fa-images me-1"></i> Photos
                </a>
            </li>
        </ul>
    </div>
    
    <div class="row">
        <!-- Facets Sidebar -->
        <div class="col-lg-3 col-md-4">
            <div class="facets-sidebar">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter</h5>
                    </div>
                    <div class="card-body">
                        <!-- Search within type -->
                        <form method="get" class="mb-4">
                            <input type="hidden" name="type" value="<?php echo $objectType; ?>">
                            <div class="input-group">
                                <input type="text" name="query" class="form-control form-control-sm" 
                                       placeholder="Search <?php echo strtolower($typeInfo['title']); ?>..." 
                                       value="<?php echo htmlspecialchars($params['query'] !== '*' ? $params['query'] : ''); ?>">
                                <button type="submit" class="btn btn-sm btn-<?php echo $typeInfo['color']; ?>">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                        
                        <?php echo $adapter->renderFacets($results['aggregations'] ?? []); ?>

                        <!-- Advanced Search Enhancements -->
                        <?php include_partial("search/advancedSearchEnhancements"); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Results -->
        <div class="col-lg-9 col-md-8">
            <!-- Results Header -->
            <div class="results-header d-flex justify-content-between align-items-center mb-4">
                <div>
                    <small class="text-muted"><?php echo number_format($results['total']); ?> items</small>
                </div>
                
                <div class="d-flex gap-2">
                    <!-- Layout Switcher -->
                    <div class="btn-group btn-group-sm">
                        <?php if ($objectType === 'dam' || $objectType === 'gallery'): ?>
                        <a href="?<?php echo http_build_query(array_merge($requestParams, ['layout' => 'masonry'])); ?>" 
                           class="btn btn-<?php echo $layout === 'masonry' ? $typeInfo['color'] : 'outline-secondary'; ?>" title="Masonry">
                            <i class="fas fa-grip-vertical"></i>
                        </a>
                        <a href="?<?php echo http_build_query(array_merge($requestParams, ['layout' => 'grid'])); ?>" 
                           class="btn btn-<?php echo $layout === 'grid' ? $typeInfo['color'] : 'outline-secondary'; ?>" title="Grid">
                            <i class="fas fa-th"></i>
                        </a>
                        <?php else: ?>
                        <a href="?<?php echo http_build_query(array_merge($requestParams, ['layout' => 'card'])); ?>" 
                           class="btn btn-<?php echo $layout === 'card' ? $typeInfo['color'] : 'outline-secondary'; ?>" title="Cards">
                            <i class="fas fa-th-large"></i>
                        </a>
                        <?php endif; ?>
                        <a href="?<?php echo http_build_query(array_merge($requestParams, ['layout' => 'list'])); ?>" 
                           class="btn btn-<?php echo $layout === 'list' ? $typeInfo['color'] : 'outline-secondary'; ?>" title="List">
                            <i class="fas fa-list"></i>
                        </a>
                    </div>
                    
                    <!-- Sort -->
                    <select class="form-select form-select-sm" style="width: auto;" onchange="location=this.value">
                        <option value="?<?php echo http_build_query(array_merge($requestParams, ['sort' => 'title_asc'])); ?>" <?php echo ($params['sort'] ?? '') === 'title_asc' ? 'selected' : ''; ?>>Title A-Z</option>
                        <option value="?<?php echo http_build_query(array_merge($requestParams, ['sort' => 'title_desc'])); ?>" <?php echo ($params['sort'] ?? '') === 'title_desc' ? 'selected' : ''; ?>>Title Z-A</option>
                        <option value="?<?php echo http_build_query(array_merge($requestParams, ['sort' => 'date_desc'])); ?>" <?php echo ($params['sort'] ?? '') === 'date_desc' ? 'selected' : ''; ?>>Date Newest</option>
                        <option value="?<?php echo http_build_query(array_merge($requestParams, ['sort' => 'date_asc'])); ?>" <?php echo ($params['sort'] ?? '') === 'date_asc' ? 'selected' : ''; ?>>Date Oldest</option>
                        <option value="?<?php echo http_build_query(array_merge($requestParams, ['sort' => 'identifier'])); ?>" <?php echo ($params['sort'] ?? '') === 'identifier' ? 'selected' : ''; ?>>Identifier</option>
                    </select>
                </div>
            </div>
            
            <!-- Results -->
            <?php if (!empty($results['hits'])): ?>
                <?php echo $adapter->renderResults($results, $layout); ?>
                
                <!-- Pagination -->
                <?php 
                $total = $results['total'];
                $from = $results['from'];
                $size = $results['size'];
                $pages = ceil($total / $size);
                $currentPage = floor($from / $size) + 1;
                ?>
                <?php if ($pages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($currentPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($requestParams, ['from' => ($currentPage - 2) * $size])); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php 
                        $start = max(1, $currentPage - 2);
                        $end = min($pages, $currentPage + 2);
                        for ($i = $start; $i <= $end; $i++): 
                        ?>
                        <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($requestParams, ['from' => ($i - 1) * $size])); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($currentPage < $pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($requestParams, ['from' => $currentPage * $size])); ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-<?php echo $typeInfo['color']; ?> bg-opacity-25">
                    <i class="fas fa-info-circle me-2"></i>
                    No <?php echo strtolower($typeInfo['title']); ?> found. 
                    <?php if ($params['query'] !== '*'): ?>
                    Try a different search term.
                    <?php else: ?>
                    Check back later or <a href="<?php echo url_for(['module' => 'display', 'action' => 'bulkSetType']); ?>">configure object types</a>.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.type-header {
    margin-left: -15px;
    margin-right: -15px;
    margin-top: -15px;
}
.nav-pills .nav-link {
    border-radius: 20px;
    padding: 8px 20px;
}
.nav-pills .nav-link.active {
    background-color: var(--bs-<?php echo $typeInfo['color']; ?>);
}
</style>
