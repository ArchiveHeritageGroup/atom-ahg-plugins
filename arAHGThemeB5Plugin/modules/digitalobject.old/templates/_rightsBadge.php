<?php
/**
 * Rights Badge for Digital Objects
 * Shows small rights indicator on thumbnails/viewers
 */

if (!isset($resource) || !$resource->id) {
    return;
}

// Get parent information object
$informationObject = $resource->object ?? null;
if (!$informationObject) {
    return;
}

$culture = sfContext::getInstance()->user->getCulture();

// Get rights
$rights = \Illuminate\Database\Capsule\Manager::table('extended_rights as er')
    ->leftJoin('rights_statement as rs', 'rs.id', '=', 'er.rights_statement_id')
    ->leftJoin('creative_commons_license as cc', 'cc.id', '=', 'er.creative_commons_id')
    ->where('er.object_id', $informationObject->id)
    ->select('rs.code as rs_code', 'rs.uri as rs_uri', 'cc.code as cc_code', 'cc.uri as cc_uri', 'cc.icon_url as cc_icon')
    ->first();

if (!$rights) {
    return;
}
?>

<div class="rights-badge position-absolute bottom-0 end-0 m-2" style="z-index:10;">
  <?php if ($rights->cc_code): ?>
    <a href="<?php echo $rights->cc_uri; ?>" target="_blank" title="<?php echo $rights->cc_code; ?>" class="d-inline-block">
      <img src="<?php echo $rights->cc_icon; ?>" alt="<?php echo $rights->cc_code; ?>" style="height:20px;">
    </a>
  <?php elseif ($rights->rs_code): ?>
    <a href="<?php echo $rights->rs_uri; ?>" target="_blank" class="badge bg-dark text-decoration-none" title="<?php echo $rights->rs_code; ?>">
      <i class="fas fa-copyright"></i> <?php echo $rights->rs_code; ?>
    </a>
  <?php endif; ?>
</div>
