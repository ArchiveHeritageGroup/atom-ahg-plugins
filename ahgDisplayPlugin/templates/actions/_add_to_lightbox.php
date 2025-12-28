<?php if ($digitalObject): ?>
<a href="<?php echo url_for(['module' => 'dam', 'action' => 'addToLightbox', 'digital_object_id' => $digitalObject->id]); ?>" 
   class="btn btn-outline-info me-2">
    <i class="fas fa-plus me-1"></i> Add to Lightbox
</a>
<?php endif; ?>
