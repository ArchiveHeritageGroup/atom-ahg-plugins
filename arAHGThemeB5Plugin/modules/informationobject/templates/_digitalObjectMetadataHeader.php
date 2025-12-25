<?php
/**
 * Digital Object Metadata Header with TIFF to PDF Merge Button
 * Add this to your information object view template
 */
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
        
        <!-- TIFF to PDF Merge Button -->
        <?php include_partial('arAHGThemeB5Plugin/tiffPdfMergeButton', [
            'informationObjectId' => $resource->id,
            'buttonClass' => 'btn btn-outline-secondary btn-sm'
        ]); ?>
        
        <?php endif; ?>
    </div>
</div>

<!-- Include modal (once per page) -->
<?php include_partial('arAHGThemeB5Plugin/tiffPdfMergeModal', [
    'informationObjectId' => $resource->id
]); ?>

<!-- Load required scripts -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="<?php echo public_path('plugins/arAHGThemeB5Plugin/js/tiff-pdf-merge.js'); ?>"></script>
