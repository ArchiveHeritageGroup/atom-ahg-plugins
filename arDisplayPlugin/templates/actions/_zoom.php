<?php if ($digitalObject): ?>
<a href="<?php echo $digitalObject->path; ?>" 
   class="btn btn-outline-info me-2" data-lightbox="object" data-title="<?php echo $object->title ?? ''; ?>">
    <i class="fas fa-search-plus me-1"></i> Zoom
</a>
<?php endif; ?>
