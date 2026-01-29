<?php
/**
 * Heritage Landing Page - Rijksstudio Style.
 *
 * Pixel-perfect implementation based on rijksmuseum.nl/en/rijksstudio
 */

// Use full-width layout without sidebar
decorate_with('layout_1col');

// Helper to unwrap Symfony escaper objects
$unwrap = function ($value) use (&$unwrap) {
    if ($value instanceof sfOutputEscaperObjectDecorator) {
        $raw = $value->getRawValue();
        return is_object($raw) ? (array) $raw : $raw;
    }
    if ($value instanceof sfOutputEscaperArrayDecorator || $value instanceof Traversable) {
        $result = [];
        foreach ($value as $k => $v) {
            $result[$k] = $unwrap($v);
        }
        return $result;
    }
    if (is_array($value)) {
        return array_map($unwrap, $value);
    }
    if (is_object($value)) {
        return (array) $value;
    }
    return $value;
};

// Unwrap data arrays
$heroImagesArray = $unwrap($heroImages ?? []);
$filtersArray = $unwrap($filters ?? []);
$storiesArray = $unwrap($stories ?? []);
$recentActivityArray = $unwrap($recentActivity ?? []);
$recentAdditionsArray = $unwrap($recentAdditions ?? []);
$configArray = $unwrap($config ?? []);

// Extract config values
$tagline = $configArray['hero_tagline'] ?? 'Discover Our Heritage';
$subtext = $configArray['hero_subtext'] ?? 'Explore collections spanning centuries of history, culture, and human achievement';
$searchPlaceholder = $configArray['hero_search_placeholder'] ?? 'Search photographs, documents, artifacts...';
$suggestedSearches = $configArray['suggested_searches'] ?? [];
if (is_string($suggestedSearches)) {
    $suggestedSearches = json_decode($suggestedSearches, true) ?: [];
}
$primaryColor = $configArray['primary_color'] ?? '#0d6efd';

// Get first hero image for background
$firstHero = !empty($heroImagesArray) ? $heroImagesArray[0] : null;
?>

<?php slot('title'); ?>
<?php end_slot(); ?>

<?php slot('pre'); ?>
<link rel="stylesheet" href="/plugins/ahgThemeB5Plugin/web/css/heritage-landing.css">
<style>
:root {
    --heritage-primary: <?php echo $primaryColor; ?>;
}
</style>
<?php end_slot(); ?>

<div class="heritage-landing" style="margin: 0; width: 100%; max-width: none;">

    <!-- ================================================================
         Section 1: Hero (Full Viewport)
         ================================================================ -->
    <section class="heritage-hero" id="heritage-hero" style="height: calc(100vh - 280px); min-height: 450px;">

        <!-- Background Images -->
        <div class="heritage-hero-backgrounds">
            <?php if (!empty($heroImagesArray)): ?>
                <?php foreach ($heroImagesArray as $index => $image): ?>
                <div class="heritage-hero-bg <?php echo $index === 0 ? 'active' : ''; ?> <?php echo ($image['ken_burns'] ?? 1) ? 'kenburns' : ''; ?>"
                     data-index="<?php echo $index; ?>"
                     data-duration="<?php echo $image['display_duration'] ?? 8; ?>"
                     style="background-image: url('<?php echo esc_specialchars($image['image_path'] ?? ''); ?>');">
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="heritage-hero-bg active" style="background: linear-gradient(135deg, var(--heritage-primary) 0%, #1a1a2e 100%);"></div>
            <?php endif; ?>
        </div>

        <!-- Gradient Overlay -->
        <div class="heritage-hero-overlay"></div>

        <!-- Content -->
        <div class="heritage-hero-content">
            <h1 class="heritage-hero-tagline"><?php echo esc_specialchars($tagline); ?></h1>

            <?php if ($subtext): ?>
            <p class="heritage-hero-subtext"><?php echo esc_specialchars($subtext); ?></p>
            <?php endif; ?>

            <!-- Search Box -->
            <form action="<?php echo url_for(['module' => 'heritage', 'action' => 'search']); ?>" method="get" class="heritage-search-box">
                <input type="text"
                       name="q"
                       placeholder="<?php echo esc_specialchars($searchPlaceholder); ?>"
                       autocomplete="off"
                       id="heritage-search-input">
                <button type="submit" aria-label="Search">
                    <i class="fas fa-search"></i>
                </button>
            </form>

            <!-- Suggested Searches -->
            <?php if (!empty($suggestedSearches)): ?>
            <div class="heritage-suggested-searches">
                <span>Try:</span>
                <?php foreach ($suggestedSearches as $search): ?>
                <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'search', 'q' => $search]); ?>">
                    <?php echo esc_specialchars($search); ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Scroll Indicator -->
        <a href="#heritage-explore" class="heritage-scroll-indicator">
            <span>Explore</span>
            <i class="fas fa-chevron-down"></i>
        </a>
    </section>

    <!-- ================================================================
         Section 2: Explore By (Category Buttons)
         ================================================================ -->
    <section class="heritage-explore-by" id="heritage-explore">
        <div class="heritage-section-label">Explore By</div>
        <div class="heritage-explore-buttons">
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'timeline']); ?>" class="heritage-explore-btn">
                <i class="fas fa-clock"></i> Time
            </a>
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'explore', 'category' => 'place']); ?>" class="heritage-explore-btn">
                <i class="fas fa-map-marker-alt"></i> Place
            </a>
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'creators']); ?>" class="heritage-explore-btn">
                <i class="fas fa-users"></i> People
            </a>
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'explore', 'category' => 'theme']); ?>" class="heritage-explore-btn">
                <i class="fas fa-tag"></i> Theme
            </a>
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'explore', 'category' => 'format']); ?>" class="heritage-explore-btn">
                <i class="fas fa-layer-group"></i> Format
            </a>
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'trending']); ?>" class="heritage-explore-btn">
                <i class="fas fa-chart-line"></i> Trending
            </a>
        </div>
    </section>

    <!-- ================================================================
         Section 3: Curated Collections (IIIF + Archival Collections)
         Only show if we have curated collections
         ================================================================ -->
    <?php
    // Unwrap curated collections from Symfony escaper
    $collectionsArray = $unwrap($curatedCollections ?? []);
    $placeholderGradients = [
        'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
        'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
        'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
        'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
        'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
    ];
    ?>
    <?php if (!empty($collectionsArray)): ?>
    <section class="heritage-collections">
        <div class="heritage-section-header">
            <h2 class="heritage-section-title">Curated Collections</h2>
            <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']); ?>" class="heritage-view-all">
                View All <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="heritage-carousel heritage-carousel-autorotate" data-autorotate="5000">
            <button class="heritage-carousel-arrow left" aria-label="Previous" data-carousel="collections-track" data-scroll="-320">
                <i class="fas fa-chevron-left"></i>
            </button>

            <div class="heritage-carousel-track" id="collections-track">
                <?php foreach ($collectionsArray as $index => $collection): ?>
                <?php
                // Determine URL based on collection type
                if (($collection['type'] ?? 'iiif') === 'archival') {
                    // Link to browse page filtered by this collection (shows all children)
                    $collectionUrl = url_for(['module' => 'informationobject', 'action' => 'browse', 'collection' => $collection['id']]);
                } else {
                    // Link to IIIF collection builder/view
                    $collectionUrl = url_for(['module' => 'iiifCollection', 'action' => 'view', 'id' => $collection['id']]);
                }
                $collectionIcon = ($collection['type'] ?? 'iiif') === 'archival' ? 'fa-archive' : 'fa-layer-group';
                ?>
                <a href="<?php echo $collectionUrl; ?>" class="heritage-collection-card">
                    <div class="heritage-card-images single-image">
                        <?php if (!empty($collection['thumbnail'])): ?>
                        <img class="main-image"
                             src="<?php echo esc_specialchars($collection['thumbnail']); ?>"
                             alt="<?php echo esc_specialchars($collection['name']); ?>"
                             loading="lazy"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="main-image fallback-gradient" style="background: <?php echo $placeholderGradients[$index % 6]; ?>; height: 280px; display: none; align-items: center; justify-content: center;">
                            <i class="fas <?php echo $collectionIcon; ?>" style="font-size: 3rem; color: rgba(255,255,255,0.5);"></i>
                        </div>
                        <?php else: ?>
                        <div class="main-image" style="background: <?php echo $placeholderGradients[$index % 6]; ?>; height: 280px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas <?php echo $collectionIcon; ?>" style="font-size: 3rem; color: rgba(255,255,255,0.5);"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="heritage-card-body">
                        <h3 class="heritage-card-title"><?php echo esc_specialchars($collection['name'] ?? 'Untitled Collection'); ?></h3>
                        <p class="heritage-card-subtitle"><?php echo esc_specialchars(substr($collection['description'] ?? '', 0, 60)); ?><?php echo strlen($collection['description'] ?? '') > 60 ? '...' : ''; ?></p>
                        <span class="heritage-card-count">
                            <i class="fas <?php echo $collectionIcon; ?> me-1" style="font-size: 0.75em;"></i>
                            <?php echo number_format($collection['item_count'] ?? 0); ?> items
                        </span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>

            <button class="heritage-carousel-arrow right" aria-label="Next" data-carousel="collections-track" data-scroll="320">
                <i class="fas fa-chevron-right"></i>
            </button>

            <!-- Carousel indicators -->
            <div class="heritage-carousel-indicators" id="collections-indicators"></div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ================================================================
         Section 4: Browse by Creator
         ================================================================ -->
    <section class="heritage-creators">
        <div class="heritage-section-header">
            <h2 class="heritage-section-title">Browse by Creator</h2>
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'creators']); ?>" class="heritage-view-all">
                View All <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="heritage-creators-track" id="creators-track">
            <?php
            // Get top creators from database
            require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';
            $creators = \Illuminate\Database\Capsule\Manager::table('actor')
                ->leftJoin('actor_i18n', function($join) {
                    $join->on('actor.id', '=', 'actor_i18n.id')
                        ->where('actor_i18n.culture', '=', 'en');
                })
                ->leftJoin('slug', function($join) {
                    $join->on('actor.id', '=', 'slug.object_id');
                })
                ->leftJoin('relation', 'actor.id', '=', 'relation.object_id')
                ->select('actor.id', 'slug.slug', 'actor_i18n.authorized_form_of_name as name')
                ->selectRaw('COUNT(relation.id) as item_count')
                ->whereNotNull('actor_i18n.authorized_form_of_name')
                ->where('actor_i18n.authorized_form_of_name', '!=', '')
                ->groupBy('actor.id', 'slug.slug', 'actor_i18n.authorized_form_of_name')
                ->orderByDesc('item_count')
                ->limit(10)
                ->get();
            ?>
            <?php if ($creators->count() > 0): ?>
                <?php foreach ($creators as $creator): ?>
                <?php
                    $initial = strtoupper(substr($creator->name, 0, 1));
                    $avatarSvg = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect fill='%23667eea' width='100' height='100' rx='50'/%3E%3Ctext x='50' y='62' font-size='40' text-anchor='middle' fill='white' font-family='system-ui'%3E{$initial}%3C/text%3E%3C/svg%3E";
                ?>
                <a href="<?php echo url_for(['module' => 'actor', 'slug' => $creator->slug]); ?>" class="heritage-creator-card">
                    <img class="heritage-creator-avatar"
                         src="<?php echo $avatarSvg; ?>"
                         alt="<?php echo esc_specialchars($creator->name); ?>">
                    <div class="heritage-creator-name"><?php echo esc_specialchars($creator->name); ?></div>
                    <div class="heritage-creator-count"><?php echo number_format($creator->item_count); ?> items</div>
                </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted text-center w-100 py-4">No creators found</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- ================================================================
         Section 5: Interactive Timeline
         ================================================================ -->
    <section class="heritage-timeline">
        <div class="heritage-section-header">
            <h2 class="heritage-section-title">Explore by Time</h2>
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'timeline']); ?>" class="heritage-view-all">
                Full Timeline <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <?php
        // Get timeline periods
        $timelinePeriods = \Illuminate\Database\Capsule\Manager::table('heritage_timeline_period')
            ->where('is_enabled', 1)
            ->where('show_on_landing', 1)
            ->orderBy('start_year')
            ->get();

        $periodCount = $timelinePeriods->count();
        ?>

        <?php if ($periodCount > 0): ?>
        <div class="heritage-timeline-bar">
            <?php foreach ($timelinePeriods as $index => $period): ?>
            <?php
                $position = $periodCount > 1 ? ($index / ($periodCount - 1)) * 100 : 50;
            ?>
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'timeline', 'period_id' => $period->id]); ?>"
               class="heritage-timeline-marker"
               style="left: <?php echo $position; ?>%;"
               title="<?php echo esc_specialchars($period->name); ?>">
                <div class="heritage-timeline-label">
                    <span class="heritage-period-name"><?php echo esc_specialchars($period->short_name ?? $period->name); ?></span>
                    <span class="heritage-period-years"><?php echo $period->start_year; ?><?php echo $period->end_year ? '-' . ($period->end_year > 2000 ? 'Present' : $period->end_year) : '+'; ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- ================================================================
         Section 6: Recently Added (Masonry Grid)
         ================================================================ -->
    <section class="heritage-recent">
        <div class="heritage-section-header">
            <h2 class="heritage-section-title">Recently Added</h2>
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'search', 'sort' => 'recent']); ?>" class="heritage-view-all">
                View All <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="heritage-masonry">
            <?php
            // Get recent items with thumbnails
            $recentItems = \Illuminate\Database\Capsule\Manager::table('information_object')
                ->join('object', 'information_object.id', '=', 'object.id')
                ->leftJoin('information_object_i18n', function($join) {
                    $join->on('information_object.id', '=', 'information_object_i18n.id')
                        ->where('information_object_i18n.culture', '=', 'en');
                })
                ->leftJoin('slug', 'information_object.id', '=', 'slug.object_id')
                ->leftJoin('digital_object', 'information_object.id', '=', 'digital_object.object_id')
                ->select(
                    'information_object.id',
                    'slug.slug',
                    'information_object_i18n.title',
                    'digital_object.path as image_path',
                    'digital_object.name as image_name'
                )
                ->whereNotNull('digital_object.id')
                ->where('information_object.id', '!=', 1) // Skip root
                ->orderByDesc('object.created_at')
                ->limit(12)
                ->get();
            ?>
            <?php if ($recentItems->count() > 0): ?>
                <?php foreach ($recentItems as $item): ?>
                <?php
                    // Path already includes /uploads/ prefix and trailing slash
                    // AtoM stores thumbnails as filename_142.ext in same directory
                    $basePath = $item->image_path; // Already has trailing slash
                    $filename = $item->image_name;
                    $ext = pathinfo($filename, PATHINFO_EXTENSION);
                    $basename = pathinfo($filename, PATHINFO_FILENAME);
                    // Thumbnail is basename_142.jpg (always jpg for thumbnails)
                    $thumbPath = $basePath . $basename . '_142.jpg';
                ?>
                <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $item->slug]); ?>" class="heritage-masonry-item">
                    <img src="<?php echo esc_specialchars($thumbPath); ?>"
                         alt="<?php echo esc_specialchars($item->title ?? 'Item'); ?>"
                         onerror="this.parentElement.style.display='none';">
                    <div class="heritage-masonry-overlay">
                        <h4 class="heritage-masonry-title"><?php echo esc_specialchars($item->title ?? 'Untitled'); ?></h4>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted text-center w-100 py-4" style="column-span: all;">No recent items with images found</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- ================================================================
         Section 7: Help Us Improve (Contributions CTA)
         ================================================================ -->
    <section class="heritage-contribute">
        <div class="heritage-contribute-inner">
            <h2 class="heritage-contribute-title">Help Us Preserve History</h2>
            <p class="heritage-contribute-subtitle">
                Join our community of contributors helping to document and preserve our shared heritage.
            </p>

            <div class="heritage-cta-cards">
                <div class="heritage-cta-card">
                    <div class="heritage-cta-icon"><i class="fas fa-file-alt"></i></div>
                    <h3 class="heritage-cta-title">Transcribe</h3>
                    <p class="heritage-cta-description">Help make handwritten documents searchable by transcribing them.</p>
                    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'contributorLogin']); ?>" class="heritage-cta-button">Start Transcribing</a>
                </div>

                <div class="heritage-cta-card">
                    <div class="heritage-cta-icon"><i class="fas fa-id-badge"></i></div>
                    <h3 class="heritage-cta-title">Identify</h3>
                    <p class="heritage-cta-description">Help identify people, places, and objects in historical photographs.</p>
                    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'contributorLogin']); ?>" class="heritage-cta-button">Help Identify</a>
                </div>

                <div class="heritage-cta-card">
                    <div class="heritage-cta-icon"><i class="fas fa-book"></i></div>
                    <h3 class="heritage-cta-title">Add Context</h3>
                    <p class="heritage-cta-description">Share your knowledge about local history and personal memories.</p>
                    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'contributorLogin']); ?>" class="heritage-cta-button">Share Stories</a>
                </div>
            </div>

            <!-- Leaderboard -->
            <div class="heritage-leaderboard">
                <h4 class="heritage-leaderboard-title">Top Contributors This Month</h4>
                <div class="heritage-leaderboard-row">
                    <?php
                    $topContributors = \Illuminate\Database\Capsule\Manager::table('heritage_contributor')
                        ->where('is_active', 1)
                        ->orderByDesc('points')
                        ->limit(5)
                        ->get();
                    ?>
                    <?php if ($topContributors->count() > 0): ?>
                        <?php foreach ($topContributors as $contributor): ?>
                        <div class="heritage-leaderboard-item">
                            <img class="heritage-leaderboard-avatar"
                                 src="<?php echo esc_specialchars($contributor->avatar_url ?? ''); ?>"
                                 alt=""
                                 onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 40 40%22><rect fill=%22%23fff3%22 width=%2240%22 height=%2240%22 rx=%2220%22/></svg>';">
                            <div>
                                <div class="heritage-leaderboard-name"><?php echo esc_specialchars($contributor->display_name); ?></div>
                                <div class="heritage-leaderboard-points"><?php echo number_format($contributor->points); ?> points</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="opacity: 0.8; font-size: 0.875rem;">Be the first to contribute!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- ================================================================
         Section 8: Footer
         ================================================================ -->
    <footer class="heritage-footer">
        <div class="heritage-footer-inner">
            <div class="heritage-footer-links">
                <a href="<?php echo url_for(['module' => 'staticpage', 'slug' => 'about']); ?>">About</a>
                <a href="<?php echo url_for(['module' => 'staticpage', 'slug' => 'contact']); ?>">Contact</a>
                <a href="<?php echo url_for(['module' => 'staticpage', 'slug' => 'privacy']); ?>">Privacy</a>
                <a href="<?php echo url_for(['module' => 'staticpage', 'slug' => 'terms']); ?>">Terms</a>
            </div>
            <div class="heritage-footer-copyright">
                &copy; <?php echo date('Y'); ?> <?php echo esc_specialchars(sfConfig::get('app_siteTitle', 'Heritage Archive')); ?>. All rights reserved.
            </div>
        </div>
    </footer>

</div>

<?php slot('after-content'); ?>
<script>
// Add body class for CSS targeting
document.body.classList.add('heritage-landing-page');

// Carousel scroll function
function scrollCarousel(trackId, amount) {
    const track = document.getElementById(trackId);
    if (track) {
        track.scrollBy({ left: amount, behavior: 'smooth' });
    }
}

// Attach carousel button event listeners
document.querySelectorAll('.heritage-carousel-arrow').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var trackId = this.getAttribute('data-carousel');
        var amount = parseInt(this.getAttribute('data-scroll'), 10);
        scrollCarousel(trackId, amount);
    });
});

// Hero image rotation
(function() {
    const backgrounds = document.querySelectorAll('.heritage-hero-bg');
    if (backgrounds.length <= 1) return;

    let currentIndex = 0;
    const defaultDuration = 8000;

    function rotateHero() {
        const current = backgrounds[currentIndex];
        const duration = (parseInt(current.dataset.duration) || 8) * 1000;

        setTimeout(function() {
            current.classList.remove('active');
            currentIndex = (currentIndex + 1) % backgrounds.length;
            backgrounds[currentIndex].classList.add('active');
            rotateHero();
        }, duration);
    }

    rotateHero();
})();

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth' });
        }
    });
});

// Auto-rotating carousel for collections
(function() {
    const carousels = document.querySelectorAll('.heritage-carousel-autorotate');

    carousels.forEach(function(carousel) {
        const track = carousel.querySelector('.heritage-carousel-track');
        const indicatorsContainer = carousel.querySelector('.heritage-carousel-indicators');
        if (!track) return;

        const cards = track.querySelectorAll('.heritage-collection-card');
        if (cards.length === 0) return;

        const autorotateDelay = parseInt(carousel.dataset.autorotate) || 5000;
        const cardWidth = 320; // Card width + gap
        let currentPosition = 0;
        let autorotateInterval = null;
        let isPaused = false;

        // Calculate number of visible cards and total scroll positions
        const trackWidth = track.offsetWidth;
        const visibleCards = Math.floor(trackWidth / cardWidth);
        const totalPositions = Math.max(1, cards.length - visibleCards + 1);

        // Create indicators
        if (indicatorsContainer && totalPositions > 1) {
            for (let i = 0; i < totalPositions; i++) {
                const dot = document.createElement('button');
                dot.className = 'heritage-carousel-dot' + (i === 0 ? ' active' : '');
                dot.setAttribute('aria-label', 'Go to slide ' + (i + 1));
                dot.addEventListener('click', function() {
                    goToPosition(i);
                    resetAutorotate();
                });
                indicatorsContainer.appendChild(dot);
            }
        }

        function updateIndicators() {
            if (!indicatorsContainer) return;
            const dots = indicatorsContainer.querySelectorAll('.heritage-carousel-dot');
            dots.forEach(function(dot, index) {
                dot.classList.toggle('active', index === currentPosition);
            });
        }

        function goToPosition(position) {
            currentPosition = position;
            const scrollAmount = position * cardWidth;
            track.scrollTo({ left: scrollAmount, behavior: 'smooth' });
            updateIndicators();
        }

        function nextPosition() {
            if (isPaused) return;
            currentPosition = (currentPosition + 1) % totalPositions;
            goToPosition(currentPosition);
        }

        function startAutorotate() {
            if (totalPositions <= 1) return;
            autorotateInterval = setInterval(nextPosition, autorotateDelay);
        }

        function resetAutorotate() {
            clearInterval(autorotateInterval);
            startAutorotate();
        }

        // Pause on hover
        carousel.addEventListener('mouseenter', function() {
            isPaused = true;
        });

        carousel.addEventListener('mouseleave', function() {
            isPaused = false;
        });

        // Pause on focus within
        carousel.addEventListener('focusin', function() {
            isPaused = true;
        });

        carousel.addEventListener('focusout', function() {
            isPaused = false;
        });

        // Update position on manual scroll
        track.addEventListener('scroll', function() {
            const newPosition = Math.round(track.scrollLeft / cardWidth);
            if (newPosition !== currentPosition && newPosition >= 0 && newPosition < totalPositions) {
                currentPosition = newPosition;
                updateIndicators();
            }
        });

        // Start auto-rotation
        startAutorotate();
    });
})();
</script>
<?php end_slot(); ?>
