<?php use_helper('Display'); ?>

<div class="container-fluid">
    <div class="row">
        <!-- Facets Sidebar -->
        <div class="col-lg-3 col-md-4">
            <div class="facets-sidebar sticky-top" style="top: 20px;">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Results</h5>
                    </div>
                    <div class="card-body">
                        <!-- Search within results -->
                        <form method="get" class="mb-4">
                            <div class="input-group">
                                <input type="text" name="query" class="form-control" 
                                       placeholder="Search..." 
                                       value="<?php echo htmlspecialchars($params['query'] !== '*' ? $params['query'] : ''); ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                        
                        <?php echo $adapter->renderFacets($results['aggregations'] ?? []); ?>
                        
                        <?php if (!empty($params['object_type']) || !empty($params['media_type'])): ?>
                        <div class="mt-3">
                            <a href="<?php echo url_for(['module' => 'ahgDisplaySearch', 'action' => 'search']); ?>" class="btn btn-outline-secondary btn-sm w-100">
                                <i class="fas fa-times me-1"></i>Clear All Filters
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Results -->
        <div class="col-lg-9 col-md-8">
            <!-- Results Header -->
            <div class="results-header d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-0">
                        <?php if ($params['query'] !== '*'): ?>
                        Search: "<?php echo htmlspecialchars($params['query']); ?>"
                        <?php else: ?>
                        Browse All
                        <?php endif; ?>
                    </h4>
                    <small class="text-muted"><?php echo number_format($results['total']); ?> results</small>
                </div>
                
                <div class="d-flex gap-2">
                    <!-- Layout Switcher -->
                    <div class="btn-group btn-group-sm">
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['layout' => 'card'])); ?>" 
                           class="btn btn-<?php echo $layout === 'card' ? 'primary' : 'outline-secondary'; ?>" title="Cards">
                            <i class="fas fa-th-large"></i>
                        </a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['layout' => 'grid'])); ?>" 
                           class="btn btn-<?php echo $layout === 'grid' ? 'primary' : 'outline-secondary'; ?>" title="Grid">
                            <i class="fas fa-th"></i>
                        </a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['layout' => 'list'])); ?>" 
                           class="btn btn-<?php echo $layout === 'list' ? 'primary' : 'outline-secondary'; ?>" title="List">
                            <i class="fas fa-list"></i>
                        </a>
                    </div>
                    
                    <!-- Sort -->
                    <select class="form-select form-select-sm" style="width: auto;" onchange="location=this.value">
                        <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => '_score'])); ?>" <?php echo $params['sort'] === '_score' ? 'selected' : ''; ?>>Relevance</option>
                        <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'title_asc'])); ?>" <?php echo $params['sort'] === 'title_asc' ? 'selected' : ''; ?>>Title A-Z</option>
                        <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'title_desc'])); ?>" <?php echo $params['sort'] === 'title_desc' ? 'selected' : ''; ?>>Title Z-A</option>
                        <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'date_desc'])); ?>" <?php echo $params['sort'] === 'date_desc' ? 'selected' : ''; ?>>Date Newest</option>
                        <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'date_asc'])); ?>" <?php echo $params['sort'] === 'date_asc' ? 'selected' : ''; ?>>Date Oldest</option>
                    </select>
                </div>
            </div>
            
            <!-- Results Grid -->
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
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['from' => ($currentPage - 2) * $size])); ?>">
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
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['from' => ($i - 1) * $size])); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($currentPage < $pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['from' => $currentPage * $size])); ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No results found. Try adjusting your filters or search terms.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
