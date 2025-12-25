<?php
/**
 * Button to trigger TIFF to PDF merge
 * Include this near digital object upload areas
 */
$informationObjectId = $informationObjectId ?? null;
$buttonClass = $buttonClass ?? 'btn btn-outline-secondary btn-sm';
$buttonText = $buttonText ?? 'Merge Images to PDF';
?>

<button type="button" 
        class="<?php echo $buttonClass; ?>" 
        data-tpm-open
        data-information-object-id="<?php echo $informationObjectId; ?>"
        title="Upload multiple TIFF/image files and merge them into a single PDF/A document">
    <i class="fas fa-layer-group me-1"></i>
    <?php echo $buttonText; ?>
</button>
