<?php
/**
 * Featured Stories partial.
 *
 * Displays curated stories/collections.
 */
?>

<section class="heritage-stories py-5 bg-light">
    <div class="container-xxl">

        <!-- Section Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h3 mb-0">Featured</h2>
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'search', 'featured' => 1]); ?>" class="btn btn-link text-decoration-none">
                View all stories <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <!-- Stories Grid -->
        <div class="row g-4">
            <?php foreach ($stories as $story): ?>
            <div class="col-md-6 col-lg-4">
                <article class="card heritage-story-card h-100 border-0 shadow-sm overflow-hidden">
                    <!-- Cover Image -->
                    <div class="heritage-story-image position-relative" style="height: 200px;">
                        <?php if (!empty($story['cover_image'])): ?>
                        <img src="<?php echo esc_specialchars($story['cover_image']); ?>"
                             alt="<?php echo esc_specialchars($story['title']); ?>"
                             class="w-100 h-100 object-fit-cover">
                        <?php else: ?>
                        <?php
                        $colors = ['primary', 'success', 'info', 'warning', 'danger'];
                        $colorIndex = crc32($story['title'] ?? 'story') % count($colors);
                        $bgColor = $colors[$colorIndex];
                        $icons = ['bi-book', 'bi-collection', 'bi-archive', 'bi-folder2-open', 'bi-journals'];
                        $icon = $icons[$colorIndex];
                        ?>
                        <div class="w-100 h-100 bg-<?php echo $bgColor; ?> bg-opacity-25 d-flex align-items-center justify-content-center">
                            <i class="fas <?php echo $icon; ?> display-4 text-<?php echo $bgColor; ?>"></i>
                        </div>
                        <?php endif; ?>
                        <!-- Story Type Badge -->
                        <?php if (!empty($story['story_type'])): ?>
                        <span class="badge bg-primary position-absolute top-0 end-0 m-3">
                            <?php echo ucfirst(esc_specialchars($story['story_type'])); ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <div class="card-body">
                        <!-- Title -->
                        <h3 class="h5 card-title">
                            <?php
                            $linkUrl = '#';
                            if ($story['link_type'] === 'search') {
                                $linkUrl = url_for(['module' => 'heritage', 'action' => 'search', 'q' => $story['link_reference']]);
                            } elseif ($story['link_type'] === 'collection') {
                                $linkUrl = '/' . $story['link_reference'];
                            } elseif ($story['link_type'] === 'external') {
                                $linkUrl = $story['link_reference'];
                            } elseif ($story['link_type'] === 'page') {
                                $linkUrl = '/' . $story['link_reference'];
                            }
                            ?>
                            <a href="<?php echo $linkUrl; ?>" class="text-decoration-none text-dark stretched-link">
                                <?php echo esc_specialchars($story['title']); ?>
                            </a>
                        </h3>

                        <!-- Subtitle -->
                        <?php if (!empty($story['subtitle'])): ?>
                        <p class="card-text text-muted">
                            <?php echo esc_specialchars($story['subtitle']); ?>
                        </p>
                        <?php endif; ?>
                    </div>

                    <!-- Footer with item count -->
                    <?php if (!empty($story['item_count'])): ?>
                    <div class="card-footer bg-transparent border-top-0 pt-0">
                        <small class="text-muted">
                            <?php echo number_format($story['item_count']); ?> items
                            <i class="fas fa-arrow-right ms-1"></i>
                        </small>
                    </div>
                    <?php endif; ?>

                </article>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>
