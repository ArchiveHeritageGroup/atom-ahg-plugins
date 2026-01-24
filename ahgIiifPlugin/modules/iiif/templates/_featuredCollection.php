<?php
/**
 * Featured Collection Carousel for Homepage
 * Uses derivative images (reference/thumbnail) with IIIF fallback
 * Excludes audio/video without image thumbnails
 */

// Initialize database via framework bootstrap
require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

// Load settings from database
$dbSettings = DB::table('iiif_viewer_settings')
    ->whereIn('setting_key', [
        'homepage_collection_id',
        'homepage_collection_enabled',
        'homepage_carousel_height',
        'homepage_carousel_autoplay',
        'homepage_carousel_interval',
        'homepage_show_captions',
        'homepage_max_items'
    ])
    ->pluck('setting_value', 'setting_key')
    ->all();

// Check if enabled
$enabled = ($dbSettings['homepage_collection_enabled'] ?? '1') === '1';
if (!$enabled && !isset($collectionId) && !isset($collectionSlug)) {
    return;
}

// Get parameters - passed values override database settings
$collectionId = $collectionId ?? ($dbSettings['homepage_collection_id'] ?: null);
$collectionSlug = $collectionSlug ?? null;
$maxItems = $maxItems ?? (int)($dbSettings['homepage_max_items'] ?? 12);
$height = $height ?? ($dbSettings['homepage_carousel_height'] ?? '450px');
$autoplay = $autoplay ?? (($dbSettings['homepage_carousel_autoplay'] ?? '1') === '1');
$interval = $interval ?? (int)($dbSettings['homepage_carousel_interval'] ?? 5000);
$showTitle = $showTitle ?? true;
$customSubtitle = $customSubtitle ?? null;
$showCaptions = $showCaptions ?? (($dbSettings['homepage_show_captions'] ?? '1') === '1');
$showViewAll = $showViewAll ?? true;

// Usage IDs
$USAGE_REFERENCE = 141;
$USAGE_THUMBNAIL = 142;

// Media type IDs
$MEDIA_IMAGE = 136;
$MEDIA_AUDIO = 135;
$MEDIA_VIDEO = 138;

// IIIF supported formats (for images only)
$iiifSupportedFormats = ['image/jpeg', 'image/png', 'image/gif', 'image/tiff', 'image/jp2'];

// Image mime types for derivative checking
$imageMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/tiff', 'image/jp2', 'image/webp', 'image/bmp'];

// Get collection by ID or slug
if ($collectionId) {
    $collection = DB::table('iiif_collection')->where('id', $collectionId)->first();
} elseif ($collectionSlug) {
    $collection = DB::table('iiif_collection')->where('slug', $collectionSlug)->first();
} else {
    // Get first public collection
    $collection = DB::table('iiif_collection')->where('is_public', 1)->orderBy('sort_order')->first();
}

if (!$collection) {
    return; // No collection to display
}

// Get collection items with their digital objects
$items = DB::table('iiif_collection_item as ci')
    ->leftJoin('information_object as io', 'ci.object_id', '=', 'io.id')
    ->leftJoin('information_object_i18n as i18n', function($join) {
        $join->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
    })
    ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
    ->leftJoin('digital_object as do', function($join) {
        $join->on('io.id', '=', 'do.object_id')->whereNull('do.parent_id');
    })
    ->where('ci.collection_id', $collection->id)
    ->whereNotNull('do.id')
    ->select(
        'ci.id as item_id',
        'ci.label as custom_label',
        'io.id as object_id',
        'io.identifier',
        'i18n.title',
        'slug.slug',
        'do.id as digital_object_id',
        'do.name as filename',
        'do.path as filepath',
        'do.mime_type',
        'do.media_type_id'
    )
    ->orderBy('ci.sort_order')
    ->limit($maxItems * 2) // Get extra to account for filtered items
    ->get();

if ($items->isEmpty()) {
    return; // No items to display
}

// Get all IMAGE derivatives for these digital objects (exclude audio/video derivatives)
$doIds = $items->pluck('digital_object_id')->toArray();
$derivatives = DB::table('digital_object')
    ->whereIn('parent_id', $doIds)
    ->whereIn('usage_id', [$USAGE_REFERENCE, $USAGE_THUMBNAIL])
    ->where(function($query) use ($imageMimeTypes) {
        // Only get image derivatives
        $query->whereIn('mime_type', $imageMimeTypes)
              ->orWhere('mime_type', 'LIKE', 'image/%');
    })
    ->select('id', 'parent_id', 'name', 'path', 'usage_id', 'mime_type')
    ->get()
    ->groupBy('parent_id');

// Build image URLs
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = "{$protocol}://{$host}";

$slides = [];
$skippedCount = 0;

foreach ($items as $item) {
    // Stop if we have enough slides
    if (count($slides) >= $maxItems) {
        break;
    }
    
    $doDerivatives = $derivatives->get($item->digital_object_id, collect());
    
    // Find reference and thumbnail IMAGE derivatives only
    $reference = $doDerivatives->firstWhere('usage_id', $USAGE_REFERENCE);
    $thumbnail = $doDerivatives->firstWhere('usage_id', $USAGE_THUMBNAIL);
    
    // Determine media type
    $isImage = ($item->media_type_id == $MEDIA_IMAGE);
    $isAudio = ($item->media_type_id == $MEDIA_AUDIO);
    $isVideo = ($item->media_type_id == $MEDIA_VIDEO);
    
    // Has IMAGE derivative (not audio/video derivative)
    $hasImageDerivative = $reference || $thumbnail;
    $isIiifCompatible = $isImage && in_array($item->mime_type, $iiifSupportedFormats);
    
    // Skip non-displayable items
    if ($isAudio && !$hasImageDerivative) {
        $skippedCount++;
        continue; // Audio without cover art image
    }
    if ($isVideo && !$hasImageDerivative) {
        $skippedCount++;
        continue; // Video without thumbnail image
    }
    if (!$isImage && !$isAudio && !$isVideo) {
        $skippedCount++;
        continue; // Text/Other media types
    }
    
    // Build direct URLs to derivative files
    $imageLarge = null;
    $imageThumb = null;
    
    if ($reference) {
        $imageLarge = rtrim($reference->path, '/') . '/' . $reference->name;
    }
    if ($thumbnail) {
        $imageThumb = rtrim($thumbnail->path, '/') . '/' . $thumbnail->name;
    }
    
    // Fallback to IIIF for images without derivatives (if format is supported)
    if (!$imageLarge && $isImage && $isIiifCompatible) {
        $imagePath = ltrim($item->filepath, '/');
        $cantaloupeId = str_replace('/', '_SL_', $imagePath) . $item->filename;
        $imageLarge = "{$baseUrl}/iiif/2/{$cantaloupeId}/full/1200,/0/default.jpg";
        if (!$imageThumb) {
            $imageThumb = "{$baseUrl}/iiif/2/{$cantaloupeId}/full/200,/0/default.jpg";
        }
    }
    
    // Use thumbnail as fallback for large if still missing
    if (!$imageLarge && $imageThumb) {
        $imageLarge = $imageThumb;
    }
    
    // Skip items with no displayable images
    if (!$imageLarge) {
        $skippedCount++;
        continue;
    }
    
    // Use large as fallback for thumb
    if (!$imageThumb) {
        $imageThumb = $imageLarge;
    }

    $slides[] = [
        'id' => $item->object_id,
        'title' => $item->custom_label ?: $item->title ?: 'Untitled',
        'identifier' => $item->identifier,
        'slug' => $item->slug,
        'image_large' => $imageLarge,
        'image_thumb' => $imageThumb,
        'link' => "/index.php/{$item->slug}",
        'media_type' => $isImage ? 'image' : ($isVideo ? 'video' : ($isAudio ? 'audio' : 'other')),
    ];
}

if (empty($slides)) {
    return; // No displayable items
}

$carouselId = 'featured-collection-' . $collection->id;
?>

<section class="featured-collection mb-4">
    <?php if ($showTitle): ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0">
            <i class="fas fa-images me-2 text-primary"></i>
            <?php echo esc_entities($customTitle ?: $collection->name) ?>
        </h2>
        <?php if ($showViewAll): ?>
        <a href="/index.php/manifest-collection/<?php echo $collection->id ?>/view" class="btn btn-sm btn-primary">
            <?php echo __('View All') ?> <i class="fas fa-arrow-right ms-1"></i>
        </a>
        <?php endif ?>
    </div>
    <?php if ($customSubtitle ?: $collection->description): ?>
    <p class="text-muted mb-3"><?php echo esc_entities($customSubtitle ?: $collection->description) ?></p>
    <?php endif ?>
    <?php endif ?>

    <div id="<?php echo $carouselId ?>" class="carousel slide"
         data-bs-ride="<?php echo $autoplay ? 'carousel' : 'false' ?>"
         data-bs-interval="<?php echo $interval ?>">

        <?php if (count($slides) > 1): ?>
        <div class="carousel-indicators">
            <?php foreach ($slides as $idx => $slide): ?>
            <button type="button"
                    data-bs-target="#<?php echo $carouselId ?>"
                    data-bs-slide-to="<?php echo $idx ?>"
                    <?php echo $idx === 0 ? 'class="active" aria-current="true"' : '' ?>
                    aria-label="<?php echo esc_entities($slide['title']) ?>">
            </button>
            <?php endforeach ?>
        </div>
        <?php endif ?>

        <div class="carousel-inner rounded shadow" style="height: <?php echo $height ?>; background: #1a1a1a;">
            <?php foreach ($slides as $idx => $slide): ?>
            <div class="carousel-item <?php echo $idx === 0 ? 'active' : '' ?>" style="height: 100%;">
                <a href="<?php echo $slide['link'] ?>" class="d-block h-100">
                    <div class="d-flex align-items-center justify-content-center h-100">
                        <img src="<?php echo $slide['image_large'] ?>"
                             class="d-block"
                             style="max-width: 100%; max-height: 100%; object-fit: contain;"
                             alt="<?php echo esc_entities($slide['title']) ?>"
                             loading="<?php echo $idx < 3 ? 'eager' : 'lazy' ?>"
                             onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=\'text-white-50 text-center\'><i class=\'fas fa-image fa-3x mb-2\'></i><br>Image unavailable</div>';">
                    </div>
                </a>
                <?php if ($showCaptions): ?>
                <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-75 rounded p-2">
                    <h5 class="mb-1">
                        <?php if ($slide['media_type'] === 'video'): ?>
                        <i class="fas fa-film me-1"></i>
                        <?php elseif ($slide['media_type'] === 'audio'): ?>
                        <i class="fas fa-music me-1"></i>
                        <?php endif ?>
                        <?php echo esc_entities($slide['title']) ?>
                    </h5>
                    <?php if ($slide['identifier']): ?>
                    <small class="text-white-50"><?php echo esc_entities($slide['identifier']) ?></small>
                    <?php endif ?>
                </div>
                <?php endif ?>
            </div>
            <?php endforeach ?>
        </div>

        <?php if (count($slides) > 1): ?>
        <button class="carousel-control-prev" type="button" data-bs-target="#<?php echo $carouselId ?>" data-bs-slide="prev">
            <span class="carousel-control-prev-icon bg-dark bg-opacity-50 rounded-circle p-3" aria-hidden="true"></span>
            <span class="visually-hidden"><?php echo __('Previous') ?></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#<?php echo $carouselId ?>" data-bs-slide="next">
            <span class="carousel-control-next-icon bg-dark bg-opacity-50 rounded-circle p-3" aria-hidden="true"></span>
            <span class="visually-hidden"><?php echo __('Next') ?></span>
        </button>
        <?php endif ?>
    </div>

    <?php if (count($slides) > 1): ?>
    <div class="featured-thumbnails d-flex flex-wrap justify-content-center gap-2 mt-3">
        <?php foreach ($slides as $idx => $slide): ?>
        <div class="position-relative">
            <img src="<?php echo $slide['image_thumb'] ?>"
                 class="featured-thumb rounded border <?php echo $idx === 0 ? 'border-primary border-2' : '' ?>"
                 style="width: 70px; height: 50px; object-fit: cover; cursor: pointer; transition: all 0.2s;"
                 data-bs-target="#<?php echo $carouselId ?>"
                 data-bs-slide-to="<?php echo $idx ?>"
                 alt="<?php echo esc_entities($slide['title']) ?>"
                 title="<?php echo esc_entities($slide['title']) ?>"
                 onerror="this.style.display='none';">
            <?php if ($slide['media_type'] === 'video'): ?>
            <span class="position-absolute bottom-0 end-0 badge bg-dark bg-opacity-75" style="font-size: 0.6rem;"><i class="fas fa-film"></i></span>
            <?php elseif ($slide['media_type'] === 'audio'): ?>
            <span class="position-absolute bottom-0 end-0 badge bg-dark bg-opacity-75" style="font-size: 0.6rem;"><i class="fas fa-music"></i></span>
            <?php endif ?>
        </div>
        <?php endforeach ?>
    </div>
    <?php endif ?>
</section>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var carousel = document.getElementById('<?php echo $carouselId ?>');
    if (!carousel) return;

    var thumbs = document.querySelectorAll('[data-bs-target="#<?php echo $carouselId ?>"].featured-thumb');

    carousel.addEventListener('slid.bs.carousel', function(e) {
        thumbs.forEach(function(t, i) {
            t.classList.remove('border-primary', 'border-2');
            if (i === e.to) {
                t.classList.add('border-primary', 'border-2');
            }
        });
    });

    thumbs.forEach(function(thumb) {
        thumb.addEventListener('click', function() {
            var bsCarousel = bootstrap.Carousel.getOrCreateInstance(carousel);
            bsCarousel.to(parseInt(this.dataset.bsSlideTo));
        });
    });
});
</script>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.featured-thumb:hover {
    transform: scale(1.1);
    border-color: var(--bs-primary) !important;
}
.featured-collection .carousel-caption {
    bottom: 0;
    left: 0;
    right: 0;
    border-radius: 0 0 0.375rem 0.375rem !important;
}
</style>
