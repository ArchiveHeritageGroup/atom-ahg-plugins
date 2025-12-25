<?php
/**
 * Search Result Badge - Shows classification in search results
 * Usage in search result template: <?php include_component('accessFilter', 'classificationBadge', ['objectId' => $hit->id]); ?>
 */

// This is a lightweight version for search results
// Just shows the classification code as a small badge

$objectId = isset($objectId) ? $objectId : null;
if (!$objectId) return;

// Use cached data if available, otherwise quick lookup
static $classificationCache = [];

if (!isset($classificationCache[$objectId])) {
    $classificationCache[$objectId] = \Illuminate\Database\Capsule\Manager::table('object_security_classification as osc')
        ->join('security_classification as sc', 'sc.id', '=', 'osc.classification_id')
        ->where('osc.object_id', $objectId)
        ->where('osc.active', 1)
        ->select('sc.code', 'sc.name', 'sc.level')
        ->first();
}

$classification = $classificationCache[$objectId];
if (!$classification) return;

$colors = ['PUBLIC' => 'success', 'INTERNAL' => 'info', 'CONFIDENTIAL' => 'primary', 'SECRET' => 'warning', 'TOP_SECRET' => 'danger'];
$color = $colors[$classification->code] ?? 'secondary';
?>
<span class="badge bg-<?php echo $color; ?> classification-badge-sm ms-1" title="<?php echo esc_entities($classification->name); ?>">
    <i class="fas fa-shield-alt"></i>
</span>
