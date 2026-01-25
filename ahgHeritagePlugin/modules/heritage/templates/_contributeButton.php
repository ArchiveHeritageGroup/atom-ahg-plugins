<?php
/**
 * Contribute Button Partial.
 *
 * Include on item pages to show contribution options.
 *
 * Required variables:
 * - $slug: The item slug
 * - $hasDigitalObject: Whether the item has a digital object
 * - $mimeType: The MIME type of the digital object (optional)
 */

$hasDigitalObject = $hasDigitalObject ?? false;
$mimeType = $mimeType ?? null;
$isImage = $mimeType && str_starts_with($mimeType, 'image/');
?>

<div class="card border-0 shadow-sm heritage-contribute-card">
    <div class="card-header bg-primary bg-opacity-10">
        <h5 class="mb-0 text-primary">
            <i class="fas fa-users-fill me-2"></i>Help Improve This Record
        </h5>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            Your knowledge can help preserve our heritage. Choose how you'd like to contribute:
        </p>

        <div class="d-grid gap-2">
            <?php if ($hasDigitalObject): ?>
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'contribute', 'slug' => $slug, 'type' => 'transcription']); ?>"
               class="btn btn-outline-primary text-start">
                <i class="fas fa-file-text me-2"></i>
                <span>Transcribe Document</span>
                <small class="d-block text-muted">Help make handwritten or typed text searchable</small>
            </a>
            <?php endif; ?>

            <?php if ($isImage): ?>
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'contribute', 'slug' => $slug, 'type' => 'identification']); ?>"
               class="btn btn-outline-success text-start">
                <i class="fas fa-user-badge me-2"></i>
                <span>Identify People</span>
                <small class="d-block text-muted">Do you recognize anyone in this photograph?</small>
            </a>
            <?php endif; ?>

            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'contribute', 'slug' => $slug, 'type' => 'context']); ?>"
               class="btn btn-outline-info text-start">
                <i class="fas fa-book me-2"></i>
                <span>Add Context</span>
                <small class="d-block text-muted">Share historical information or personal memories</small>
            </a>

            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'contribute', 'slug' => $slug, 'type' => 'correction']); ?>"
               class="btn btn-outline-warning text-start">
                <i class="fas fa-pencil-alt-square me-2"></i>
                <span>Suggest Correction</span>
                <small class="d-block text-muted">Found an error? Let us know</small>
            </a>

            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'contribute', 'slug' => $slug, 'type' => 'tag']); ?>"
               class="btn btn-outline-dark text-start">
                <i class="fas fa-tags me-2"></i>
                <span>Add Tags</span>
                <small class="d-block text-muted">Help others find this record with keywords</small>
            </a>
        </div>

        <hr class="my-3">

        <div class="d-flex justify-content-between align-items-center">
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'leaderboard']); ?>" class="small text-decoration-none">
                <i class="fas fa-trophy me-1"></i>View Leaderboard
            </a>
            <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'contributorRegister']); ?>" class="small text-decoration-none">
                <i class="fas fa-user-plus me-1"></i>Join Community
            </a>
        </div>
    </div>
</div>
