<?php if ($digitalObject): ?>
<a href="<?php echo url_for(['module' => 'dam', 'action' => 'createLicense', 'digital_object_id' => $digitalObject->id]); ?>" 
   class="btn btn-outline-secondary me-2">
    <i class="fas fa-file-contract me-1"></i> License
</a>
<?php endif; ?>
