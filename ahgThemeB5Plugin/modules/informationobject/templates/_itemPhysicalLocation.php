<?php
/**
 * Item Physical Location partial (Edit Form)
 * Editor-level access required
 * Include with: include_partial('informationobject/itemPhysicalLocation', ['resource' => $resource, 'itemLocation' => $itemLocation])
 */

// Check editor access
$user = sfContext::getInstance()->getUser();
if (!$user->isAuthenticated()) return;

// Check for editor/admin group membership
$userId = $user->getAttribute('user_id');
if (!$userId) return;

$isEditor = \Illuminate\Database\Capsule\Manager::table('acl_user_group')
    ->where('user_id', $userId)
    ->whereIn('group_id', [100, 101]) // GROUP_ADMINISTRATOR=100, GROUP_EDITOR=101
    ->exists();

if (!$isEditor) return;

$itemLocation = $itemLocation ?? [];

// Get physical object options for dropdown
$physicalObjects = [];
$poResult = \Illuminate\Database\Capsule\Manager::table('physical_object as po')
    ->leftJoin('physical_object_i18n as poi', function($join) {
        $join->on('poi.id', '=', 'po.id')->where('poi.culture', '=', 'en');
    })
    ->select(['po.id', 'poi.name', 'poi.location'])
    ->orderBy('poi.name')
    ->get();
foreach ($poResult as $po) {
    $physicalObjects[$po->id] = $po->name . ($po->location ? ' (' . $po->location . ')' : '');
}
?>
<div class="card mb-4">
  <div class="card-header text-white" style="background-color: #198754;">
    <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i><?php echo __('Item Physical Location'); ?></h5>
  </div>
  <div class="card-body">
    <!-- Container Link -->
    <div class="row mb-3">
      <div class="col-md-6">
        <label class="form-label"><?php echo __('Storage container'); ?></label>
        <select name="item_physical_object_id" class="form-select">
          <option value=""><?php echo __('-- Select container --'); ?></option>
          <?php foreach ($physicalObjects as $id => $name): ?>
            <option value="<?php echo $id; ?>" <?php echo (($itemLocation['physical_object_id'] ?? '') == $id) ? 'selected' : ''; ?>>
              <?php echo esc_entities($name); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <small class="form-text text-muted"><?php echo __('Link to a physical storage container'); ?></small>
      </div>
      <div class="col-md-6">
        <label class="form-label"><?php echo __('Item barcode'); ?></label>
        <input type="text" name="item_barcode" class="form-control" value="<?php echo esc_entities($itemLocation['barcode'] ?? ''); ?>">
      </div>
    </div>

    <!-- Location within container -->
    <h6 class="text-white py-2 px-3 mb-3" style="background-color: #198754;"><i class="fas fa-box me-2"></i><?php echo __('Location within container'); ?></h6>
    <div class="row mb-3">
      <div class="col-md-2">
        <label class="form-label"><?php echo __('Box'); ?></label>
        <input type="text" name="item_box_number" class="form-control" value="<?php echo esc_entities($itemLocation['box_number'] ?? ''); ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label"><?php echo __('Folder'); ?></label>
        <input type="text" name="item_folder_number" class="form-control" value="<?php echo esc_entities($itemLocation['folder_number'] ?? ''); ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label"><?php echo __('Shelf'); ?></label>
        <input type="text" name="item_shelf" class="form-control" value="<?php echo esc_entities($itemLocation['shelf'] ?? ''); ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label"><?php echo __('Row'); ?></label>
        <input type="text" name="item_row" class="form-control" value="<?php echo esc_entities($itemLocation['row'] ?? ''); ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label"><?php echo __('Position'); ?></label>
        <input type="text" name="item_position" class="form-control" value="<?php echo esc_entities($itemLocation['position'] ?? ''); ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label"><?php echo __('Item #'); ?></label>
        <input type="text" name="item_item_number" class="form-control" value="<?php echo esc_entities($itemLocation['item_number'] ?? ''); ?>">
      </div>
    </div>

    <!-- Extent -->
    <div class="row mb-3">
      <div class="col-md-3">
        <label class="form-label"><?php echo __('Extent value'); ?></label>
        <input type="number" step="0.01" name="item_extent_value" class="form-control" value="<?php echo esc_entities($itemLocation['extent_value'] ?? ''); ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label"><?php echo __('Extent unit'); ?></label>
        <select name="item_extent_unit" class="form-select">
          <option value=""><?php echo __('-- Select --'); ?></option>
          <?php 
          $units = ['items' => __('Items'), 'pages' => __('Pages'), 'folders' => __('Folders'), 'boxes' => __('Boxes'), 'cm' => __('cm'), 'm' => __('metres'), 'cubic_m' => __('cubic metres')];
          foreach ($units as $val => $label): ?>
            <option value="<?php echo $val; ?>" <?php echo (($itemLocation['extent_unit'] ?? '') == $val) ? 'selected' : ''; ?>><?php echo $label; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- Condition & Status -->
    <h6 class="text-white py-2 px-3 mb-3" style="background-color: #198754;"><i class="fas fa-clipboard-check me-2"></i><?php echo __('Condition & Status'); ?></h6>
    <div class="row mb-3">
      <div class="col-md-3">
        <label class="form-label"><?php echo __('Condition'); ?></label>
        <select name="item_condition_status" class="form-select">
          <option value=""><?php echo __('-- Select --'); ?></option>
          <?php 
          $conditions = ['excellent' => __('Excellent'), 'good' => __('Good'), 'fair' => __('Fair'), 'poor' => __('Poor'), 'critical' => __('Critical')];
          foreach ($conditions as $val => $label): ?>
            <option value="<?php echo $val; ?>" <?php echo (($itemLocation['condition_status'] ?? '') == $val) ? 'selected' : ''; ?>><?php echo $label; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label"><?php echo __('Access status'); ?></label>
        <select name="item_access_status" class="form-select">
          <?php 
          $statuses = ['available' => __('Available'), 'in_use' => __('In Use'), 'restricted' => __('Restricted'), 'offsite' => __('Offsite'), 'missing' => __('Missing')];
          foreach ($statuses as $val => $label): ?>
            <option value="<?php echo $val; ?>" <?php echo (($itemLocation['access_status'] ?? 'available') == $val) ? 'selected' : ''; ?>><?php echo $label; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label"><?php echo __('Condition notes'); ?></label>
        <input type="text" name="item_condition_notes" class="form-control" value="<?php echo esc_entities($itemLocation['condition_notes'] ?? ''); ?>">
      </div>
    </div>

    <!-- Notes -->
    <div class="row mb-3">
      <div class="col-md-12">
        <label class="form-label"><?php echo __('Location notes'); ?></label>
        <textarea name="item_location_notes" class="form-control" rows="2"><?php echo esc_entities($itemLocation['notes'] ?? ''); ?></textarea>
      </div>
    </div>
  </div>
</div>
