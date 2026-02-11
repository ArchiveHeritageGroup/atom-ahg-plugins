<?php
// Resolve URLs for carousel items using Laravel Query Builder
// The base component provides $thumbnails where each $item->parent is the master digital object
use Illuminate\Database\Capsule\Manager as DB;

if (!isset($thumbnailMeta) || empty($thumbnailMeta)) {
    $thumbnailMeta = [];

    // Collect all digital object IDs from the parent (master) DOs
    $doIds = [];
    // Unwrap Symfony output escaper to access Propel objects directly
    $rawThumbnails = sfOutputEscaper::unescape($thumbnails);
    foreach ($rawThumbnails as $item) {
        try {
            // For actual DB digital objects, use objectId (digital_object.object_id -> information_object.id)
            if ($item->id && $item->objectId) {
                $doIds[] = (int) $item->id;
            }
            // For generic representations with parent set, use parent's objectId
            elseif ($item->parent && $item->parent->id && $item->parent->objectId) {
                $doIds[] = (int) $item->parent->id;
            }
        } catch (Exception $e) {
            // skip
        }
    }

    // Batch-fetch slugs and titles via SQL
    $slugTitleMap = [];
    if (!empty($doIds)) {
        try {
            $rows = DB::table('digital_object as do2')
                ->join('slug as s', 's.object_id', '=', 'do2.object_id')
                ->leftJoin('information_object_i18n as ioi', function ($join) {
                    $join->on('ioi.id', '=', 'do2.object_id')
                         ->where('ioi.culture', '=', 'en');
                })
                ->whereIn('do2.id', $doIds)
                ->select('do2.id as do_id', 's.slug', 'ioi.title')
                ->get();
            foreach ($rows as $r) {
                $slugTitleMap[(int) $r->do_id] = ['slug' => $r->slug, 'title' => $r->title ?? ''];
            }
        } catch (Exception $e) {
            // Fallback: leave empty
        }
    }

    foreach ($rawThumbnails as $item) {
        $doId = null;
        try {
            if ($item->id && $item->objectId) {
                $doId = (int) $item->id;
            } elseif ($item->parent && $item->parent->id && $item->parent->objectId) {
                $doId = (int) $item->parent->id;
            }
        } catch (Exception $e) {
            // skip
        }
        $meta = ($doId && isset($slugTitleMap[$doId])) ? $slugTitleMap[$doId] : ['slug' => null, 'title' => ''];
        $thumbnailMeta[] = $meta;
    }
}

// Map file extension to FontAwesome icon class
$_extIconMap = [
    // Audio
    'mp3' => 'fas fa-file-audio', 'wav' => 'fas fa-file-audio', 'ogg' => 'fas fa-file-audio',
    'flac' => 'fas fa-file-audio', 'aac' => 'fas fa-file-audio', 'wma' => 'fas fa-file-audio',
    // Video
    'mp4' => 'fas fa-file-video', 'avi' => 'fas fa-file-video', 'mkv' => 'fas fa-file-video',
    'mov' => 'fas fa-file-video', 'wmv' => 'fas fa-file-video', 'webm' => 'fas fa-file-video',
    // PDF
    'pdf' => 'fas fa-file-pdf',
    // Word
    'doc' => 'fas fa-file-word', 'docx' => 'fas fa-file-word', 'odt' => 'fas fa-file-word',
    // Excel
    'xls' => 'fas fa-file-excel', 'xlsx' => 'fas fa-file-excel', 'ods' => 'fas fa-file-excel',
    'csv' => 'fas fa-file-excel',
    // PowerPoint
    'ppt' => 'fas fa-file-powerpoint', 'pptx' => 'fas fa-file-powerpoint',
    // Archive
    'zip' => 'fas fa-file-archive', 'rar' => 'fas fa-file-archive', 'tar' => 'fas fa-file-archive',
    'gz' => 'fas fa-file-archive', '7z' => 'fas fa-file-archive',
    // 3D
    'glb' => 'fas fa-cube', 'gltf' => 'fas fa-cube', 'obj' => 'fas fa-cube',
    'stl' => 'fas fa-cube', 'fbx' => 'fas fa-cube',
    // Code/text
    'xml' => 'fas fa-file-code', 'json' => 'fas fa-file-code', 'html' => 'fas fa-file-code',
    'txt' => 'fas fa-file-alt',
];
$_imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tif', 'tiff', 'svg'];

/**
 * Get file extension icon class or null if it's a displayable image
 */
function _getFileIcon($path, $extIconMap, $imageExts)
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (in_array($ext, $imageExts)) {
        return null; // displayable image — use <img>
    }

    return isset($extIconMap[$ext]) ? $extIconMap[$ext] : 'fas fa-file';
}
?>
<div
  class="accordion"
  id="atom-digital-object-carousel"
  data-carousel-instructions-text-text-link="<?php echo __('Clicking this description title link will open the description view page for this digital object. Advancing the carousel above will update this title text.'); ?>"
  data-carousel-instructions-text-image-link="<?php echo __('Changing the current slide of this carousel will change the description title displayed in the following carousel. Clicking any image in this carousel will open the related description view page.'); ?>"
  data-carousel-next-arrow-button-text="<?php echo __('Next'); ?>"
  data-carousel-prev-arrow-button-text="<?php echo __('Previous'); ?>"
  data-carousel-images-region-label="<?php echo __('Archival description images carousel'); ?>"
  data-carousel-title-region-label="<?php echo __('Archival description title link'); ?>">
  <div class="accordion-item border-0">
    <h2 class="accordion-header rounded-0 rounded-top border border-bottom-0" id="heading-carousel">
      <button class="accordion-button rounded-0 rounded-top text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-carousel" aria-expanded="true" aria-controls="collapse-carousel">
        <span><?php echo __('Image carousel'); ?></span>
      </button>
    </h2>
    <div id="collapse-carousel" class="accordion-collapse collapse show" aria-labelledby="heading-carousel">
      <div class="accordion-body bg-secondary px-5 pt-4 pb-3">
        <div id="atom-slider-images" class="mb-0">
          <?php foreach ($thumbnails as $idx => $item) { ?>
            <?php
              $meta = isset($thumbnailMeta[$idx]) ? $thumbnailMeta[$idx] : null;
              $slug = $meta ? $meta['slug'] : null;
              $title = $meta ? $meta['title'] : '';
              $href = $slug ? url_for(['module' => 'informationobject', 'slug' => $slug]) : '#';
              $filePath = $item->getFullPath();
              $iconClass = _getFileIcon($filePath, $_extIconMap, $_imageExts);
            ?>
            <a title="<?php echo esc_entities($title); ?>" href="<?php echo $href; ?>">
              <?php if ($iconClass): ?>
              <span class="img-thumbnail mx-2 d-inline-flex align-items-center justify-content-center" style="width:120px;height:120px;background:#f8f9fa;">
                <i class="<?php echo $iconClass; ?> fa-3x text-secondary"></i>
              </span>
              <?php else: ?>
              <?php echo image_tag($filePath, ['class' => 'img-thumbnail mx-2', 'longdesc' => $href, 'alt' => strip_markdown($item->getDigitalObjectAltText() ?: $title), 'style' => 'max-height:120px;']); ?>
              <?php endif; ?>
            </a>
          <?php } ?>
        </div>

        <div id="atom-slider-title">
          <?php foreach ($thumbnails as $idx => $item) { ?>
            <?php
              $meta = isset($thumbnailMeta[$idx]) ? $thumbnailMeta[$idx] : null;
              $slug = $meta ? $meta['slug'] : null;
              $title = $meta ? $meta['title'] : '';
              $href = $slug ? url_for(['module' => 'informationobject', 'slug' => $slug]) : '#';
            ?>
            <a href="<?php echo $href; ?>" class="text-white text-center mt-2 mb-1">
              <?php echo strip_markdown($title ?: __('Untitled')); ?>
            </a>
          <?php } ?>
        </div>

        <?php if (isset($limit) && $limit < $total) { ?>
          <div class="text-white text-center mt-2 mb-1">
            <?php echo __('Results %1% to %2% of %3%', ['%1%' => 1, '%2%' => $limit, '%3%' => $total]); ?>
            <a class='btn atom-btn-outline-light btn-sm ms-2' href="<?php echo url_for([
                'module' => 'informationobject',
                'action' => 'browse',
                'ancestor' => $resource->id,
                'topLod' => false,
                'view' => 'card',
                'onlyMedia' => true, ]); ?>"><?php echo __('Show all'); ?></a>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>
</div>
