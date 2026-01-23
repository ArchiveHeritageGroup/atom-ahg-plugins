<?php
/**
 * Embargo Badge Template
 *
 * Displays an embargo indicator badge when an object is under embargo.
 * $resource is passed from the DisplayActionRegistry
 */

if (!isset($resource) || !isset($resource->id)) {
    return;
}

// Check embargo status
$isEmbargoed = false;
$embargoEndDate = null;

try {
    \AhgCore\Core\AhgDb::init();
    $embargo = \Illuminate\Database\Capsule\Manager::table('embargo')
        ->where('object_id', $resource->id)
        ->where('is_active', 1)
        ->where('end_date', '>=', date('Y-m-d'))
        ->first();

    if ($embargo) {
        $isEmbargoed = true;
        $embargoEndDate = $embargo->end_date;
    }
} catch (Exception $e) {
    // Silently fail
}

if (!$isEmbargoed) {
    return;
}
?>
<span class="badge bg-warning text-dark" title="Under embargo until <?php echo htmlspecialchars($embargoEndDate); ?>">
    <i class="bi bi-lock-fill"></i> Embargo
</span>
