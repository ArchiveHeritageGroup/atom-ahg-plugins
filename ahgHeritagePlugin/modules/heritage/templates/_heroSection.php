<?php
/**
 * Hero Section partial.
 *
 * Full viewport hero with rotating images and search.
 */

$hasImages = !empty($heroImages);
$firstImage = $hasImages ? $heroImages[0] : null;

// Get overlay settings from first image or use defaults
$overlayType = $firstImage['overlay_type'] ?? 'gradient';
$overlayColor = $firstImage['overlay_color'] ?? '#000000';
$overlayOpacity = $firstImage['overlay_opacity'] ?? 0.5;
?>

<section class="heritage-hero position-relative vh-100 d-flex align-items-center justify-content-center overflow-hidden" id="heritage-hero">

    <!-- Background Images -->
    <div class="heritage-hero-backgrounds position-absolute top-0 start-0 w-100 h-100">
        <?php if ($hasImages): ?>
            <?php foreach ($heroImages as $index => $image): ?>
            <?php
                $effectClass = ($image['ken_burns'] ?? 1) ? 'kenburns' : 'fade';
                $slideDuration = $image['display_duration'] ?? 8;
            ?>
            <div class="heritage-hero-bg <?php echo $index === 0 ? 'active' : ''; ?> <?php echo $effectClass; ?>"
                 data-index="<?php echo $index; ?>"
                 data-duration="<?php echo $slideDuration; ?>"
                 style="background-image: url('<?php echo esc_specialchars($image['image_path']); ?>');">
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="heritage-hero-bg active" style="background: linear-gradient(135deg, var(--heritage-primary) 0%, #1a1a2e 100%);"></div>
        <?php endif; ?>
    </div>

    <!-- Gradient Overlay -->
    <?php if ($overlayType === 'gradient'): ?>
    <div class="heritage-hero-overlay position-absolute top-0 start-0 w-100 h-100"
         style="background: linear-gradient(to bottom, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0.6) 100%);"></div>
    <?php elseif ($overlayType === 'solid'): ?>
    <div class="heritage-hero-overlay position-absolute top-0 start-0 w-100 h-100"
         style="background-color: <?php echo esc_specialchars($overlayColor); ?>; opacity: <?php echo $overlayOpacity; ?>;"></div>
    <?php endif; ?>

    <!-- Content -->
    <div class="heritage-hero-content position-relative text-center text-white px-4" style="z-index: 10;">

        <!-- Tagline -->
        <h1 class="heritage-hero-tagline display-3 fw-bold mb-4">
            <?php echo esc_specialchars($tagline); ?>
        </h1>

        <!-- Subtext -->
        <?php if ($subtext): ?>
        <p class="heritage-hero-subtext lead mb-5 mx-auto" style="max-width: 600px;">
            <?php echo esc_specialchars($subtext); ?>
        </p>
        <?php endif; ?>

        <!-- Search Box -->
        <form action="<?php echo url_for(['module' => 'heritage', 'action' => 'search']); ?>" method="get" class="heritage-search-form mx-auto mb-4" style="max-width: 600px;">
            <div class="input-group input-group-lg shadow-lg">
                <input type="text"
                       name="q"
                       class="form-control border-0 py-3"
                       placeholder="<?php echo esc_specialchars($searchPlaceholder); ?>"
                       aria-label="Search"
                       autocomplete="off"
                       id="heritage-search-input">
                <button class="btn btn-primary px-4" type="submit" aria-label="Search">
                    <i class="fas fa-search"></i>
                </button>
            </div>

            <!-- Autocomplete dropdown -->
            <div class="heritage-autocomplete position-absolute w-100 bg-white rounded-bottom shadow-lg d-none" id="heritage-autocomplete">
            </div>
        </form>

        <!-- Suggested Searches -->
        <?php if (!empty($suggestedSearches)): ?>
        <div class="heritage-suggested-searches">
            <span class="text-white-50 me-2">Try:</span>
            <?php foreach ($suggestedSearches as $search): ?>
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'search', 'q' => $search]); ?>"
               class="btn btn-outline-light btn-sm rounded-pill me-2 mb-2">
                <?php echo esc_specialchars($search); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>

    <!-- Image Caption (bottom) -->
    <?php if ($firstImage && ($firstImage['source_collection'] || $firstImage['photographer_credit'])): ?>
    <div class="heritage-hero-caption position-absolute bottom-0 start-0 w-100 p-4 text-white" style="z-index: 10;" id="heritage-hero-caption">
        <div class="container-xxl">
            <?php if ($firstImage['source_collection']): ?>
            <small class="d-block opacity-75" id="caption-collection">
                <?php echo esc_specialchars($firstImage['source_collection']); ?>
            </small>
            <?php endif; ?>
            <?php if ($firstImage['photographer_credit']): ?>
            <span id="caption-text"><?php echo esc_specialchars($firstImage['photographer_credit']); ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Scroll Indicator -->
    <div class="heritage-scroll-indicator position-absolute bottom-0 start-50 translate-middle-x pb-5" style="z-index: 10;">
        <a href="#heritage-explore" class="text-white text-decoration-none">
            <div class="d-flex flex-column align-items-center">
                <span class="small mb-2">Explore</span>
                <i class="fas fa-chevron-down animate-bounce"></i>
            </div>
        </a>
    </div>

</section>
