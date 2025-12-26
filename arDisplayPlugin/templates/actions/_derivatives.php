<?php if ($digitalObject): ?>
<a href="<?php echo url_for(['module' => 'dam', 'action' => 'viewDerivatives', 'digital_object_id' => $digitalObject->id]); ?>" 
   class="btn btn-outline-secondary me-2">
    <i class="fas fa-copy me-1"></i> Derivatives
</a>
<?php endif; ?>
