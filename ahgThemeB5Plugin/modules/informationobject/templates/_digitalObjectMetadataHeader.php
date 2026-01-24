<?php
/**
 * Digital Object Metadata Header with TIFF to PDF Merge Button
 * Add this to your information object view template
 */
$hasTiffPdfMerge = class_exists('ahgTiffPdfMergePluginConfiguration');
?>

<div class="digital-object-actions mb-3">
    <div class="btn-group" role="group">
        <?php if ($sf_user->hasCredential(['contributor', 'editor', 'administrator'], false)): ?>

        <!-- Upload Digital Object -->
        <a href="<?php echo url_for(['module' => 'digitalobject', 'action' => 'edit', 'informationObject' => $resource->slug]); ?>"
           class="btn btn-outline-primary btn-sm">
            <i class="fas fa-upload me-1"></i>
            Upload
        </a>

        <!-- TIFF to PDF Merge Button (from ahgTiffPdfMergePlugin) -->
        <?php if ($hasTiffPdfMerge): ?>
        <?php include_partial('tiffpdfmerge/tiffPdfMergeButton', [
            'informationObjectId' => $resource->id,
            'buttonClass' => 'btn btn-outline-secondary btn-sm'
        ]); ?>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<!-- Include modal (once per page) -->
<?php if ($hasTiffPdfMerge): ?>
<?php include_partial('tiffpdfmerge/tiffPdfMergeModal', [
    'informationObjectId' => $resource->id
]); ?>

<!-- Load required scripts -->
<script src="/plugins/ahgCorePlugin/web/js/vendor/sortable.min.js"></script>
<script src="<?php echo public_path('plugins/tiffpdfmerge/web/js/tiff-pdf-merge.js'); ?>"></script>
<?php endif; ?>
