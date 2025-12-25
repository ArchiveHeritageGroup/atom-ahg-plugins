<?php
/**
 * TIFF to PDF Merge Link
 * Include this where you want to show the merge option
 * 
 * Usage: include_partial('arAHGThemeB5Plugin/tiffPdfMergeLink', ['informationObject' => $resource]);
 */

$io = $informationObject ?? null;
$slug = $io ? $io->slug : null;
$btnClass = $buttonClass ?? 'btn btn-outline-secondary';
$showText = $showText ?? true;
?>

<?php if ($sf_user->hasCredential(['contributor', 'editor', 'administrator'], false)): ?>
<a href="<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'index', 'informationObject' => $slug]); ?>" 
   class="<?php echo $btnClass; ?>"
   title="Upload multiple images and merge into a single PDF document">
    <i class="fas fa-layer-group<?php echo $showText ? ' me-1' : ''; ?>"></i>
    <?php if ($showText): ?>Merge to PDF<?php endif; ?>
</a>
<?php endif; ?>
