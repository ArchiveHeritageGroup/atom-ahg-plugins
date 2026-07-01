<?php
/**
 * Extended Rights Area for Information Object View
 *
 * Displays:
 * - RightsStatements.org badge
 * - Creative Commons license
 * - TK Labels
 * - Embargo status
 * - Provenance/donor info (if user has permission)
 */

// Only show if resource exists and is valid
if (!isset($resource)) {
    return;
}

// Get resource ID safely
$resourceId = null;
if ($resource instanceof QubitInformationObject) {
    $resourceId = $resource->id;
} elseif (is_object($resource) && isset($resource->id)) {
    $resourceId = $resource->id;
}

if (!$resourceId) {
    return;
}

// Check if user can see detailed rights
$canSeeDetails = $sf_user->isAuthenticated();

// Safe ACL check - only if resource is a proper Qubit object
$canEdit = false;
if ($resource instanceof QubitInformationObject) {
    try {
        $canEdit = \AtomExtensions\Services\AclService::check($resource, 'update');
    } catch (Exception $e) {
        $canEdit = false;
    }
}
?>

<?php // Robust check: checkPluginEnabled() is only defined inside _mainMenu.php
      // (template-scoped), so relying on function_exists() here silently blanked
      // this block when that template hadn't rendered first. Use the plugin list. ?>
<?php if (in_array('ahgExtendedRightsPlugin', sfProjectConfiguration::getActive()->getPlugins())): ?>
<!-- Extended Rights Display -->
<?php include_component('extendedRights', 'rightsDisplay', ['objectId' => $resourceId]); ?>
<!-- Provenance Display (authenticated users only) -->
<?php if ($canSeeDetails): ?>
  <?php include_component('extendedRights', 'provenanceDisplay', ['objectId' => $resourceId]); ?>
<?php endif; ?>
<!-- Embargo Warning (public) -->
<?php include_component('extendedRights', 'embargoStatus', ['objectId' => $resourceId]); ?>
<?php endif; ?>
