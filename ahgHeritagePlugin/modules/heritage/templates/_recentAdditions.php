<?php
/**
 * Recent Additions partial.
 *
 * Horizontal scroll of recently added items.
 */
?>

<section class="heritage-recent py-5 bg-light">
    <div class="container-xxl">

        <!-- Section Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h3 mb-0">Recently Added</h2>
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'search', 'sort' => 'newest']); ?>" class="btn btn-link text-decoration-none">
                View all new <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <!-- Horizontal Scroll Container -->
        <div class="heritage-recent-scroll position-relative">
            <div class="d-flex gap-3 overflow-auto pb-3" style="scroll-snap-type: x mandatory;">
                <?php foreach ($items as $item): ?>
                <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $item['slug']]); ?>"
                   class="heritage-recent-item flex-shrink-0 text-decoration-none"
                   style="width: 180px; scroll-snap-align: start;">

                    <!-- Thumbnail -->
                    <?php
                    // Determine icon and color based on media type
                    $iconClass = match($item['media_type'] ?? null) {
                        'image' => 'fas fa-image',
                        'video' => 'fas fa-video',
                        'audio' => 'fas fa-music',
                        'document' => 'bi-file-pdf',
                        'text' => 'bi-file-text',
                        default => 'bi-archive'
                    };
                    $bgColor = match($item['media_type'] ?? null) {
                        'image' => 'success',
                        'video' => 'danger',
                        'audio' => 'warning',
                        'document' => 'info',
                        'text' => 'secondary',
                        default => 'primary'
                    };
                    ?>
                    <div class="heritage-recent-thumb rounded overflow-hidden mb-2" style="height: 180px;">
                        <?php if (!empty($item['thumbnail'])): ?>
                        <img src="<?php echo esc_specialchars($item['thumbnail']); ?>"
                             alt="<?php echo esc_specialchars($item['title']); ?>"
                             class="w-100 h-100 object-fit-cover"
                             loading="lazy"
                             onerror="this.parentElement.innerHTML='<div class=\'w-100 h-100 bg-<?php echo $bgColor; ?> bg-opacity-25 d-flex align-items-center justify-content-center\'><i class=\'bi <?php echo $iconClass; ?> display-4 text-<?php echo $bgColor; ?>\'></i></div>';">
                        <?php else: ?>
                        <div class="w-100 h-100 bg-<?php echo $bgColor; ?> bg-opacity-25 d-flex align-items-center justify-content-center">
                            <i class="fas <?php echo $iconClass; ?> display-4 text-<?php echo $bgColor; ?>"></i>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Title -->
                    <p class="small text-dark mb-0 text-truncate" title="<?php echo esc_specialchars($item['title']); ?>">
                        <?php echo esc_specialchars($item['title']); ?>
                    </p>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Scroll Indicators (optional, can be enhanced with JS) -->
            <button class="heritage-scroll-btn heritage-scroll-prev btn btn-light rounded-circle position-absolute start-0 top-50 translate-middle-y shadow d-none d-md-flex"
                    aria-label="Previous" style="z-index: 5;">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="heritage-scroll-btn heritage-scroll-next btn btn-light rounded-circle position-absolute end-0 top-50 translate-middle-y shadow d-none d-md-flex"
                    aria-label="Next" style="z-index: 5;">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>

    </div>
</section>
