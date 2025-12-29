<?php
/**
 * Item Physical Location View partial - Green Accordion Style
 * Editor-level access required
 * Include with: include_partial('informationobject/itemPhysicalLocationView', ['itemLocation' => $itemLocation])
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

if (empty($itemLocation)) return;

$locationParts = array_filter([
    !empty($itemLocation['box_number']) ? __('Box') . ' ' . $itemLocation['box_number'] : null,
    !empty($itemLocation['folder_number']) ? __('Folder') . ' ' . $itemLocation['folder_number'] : null,
    !empty($itemLocation['shelf']) ? __('Shelf') . ' ' . $itemLocation['shelf'] : null,
    !empty($itemLocation['row']) ? __('Row') . ' ' . $itemLocation['row'] : null,
    !empty($itemLocation['position']) ? __('Pos') . ' ' . $itemLocation['position'] : null,
    !empty($itemLocation['item_number']) ? __('Item') . ' ' . $itemLocation['item_number'] : null,
]);

// Container location path
$containerLocationParts = [];
if (!empty($itemLocation['container'])) {
    $c = $itemLocation['container'];
    $containerLocationParts = array_filter([
        $c['building'] ?? null,
        !empty($c['floor']) ? __('Floor') . ' ' . $c['floor'] : null,
        !empty($c['room']) ? __('Room') . ' ' . $c['room'] : null,
        !empty($c['aisle']) ? __('Aisle') . ' ' . $c['aisle'] : null,
        !empty($c['bay']) ? __('Bay') . ' ' . $c['bay'] : null,
        !empty($c['rack']) ? __('Rack') . ' ' . $c['rack'] : null,
        !empty($c['shelf']) ? __('Shelf') . ' ' . $c['shelf'] : null,
    ]);
}

$accessLabels = [
    'available' => __('Available'),
    'in_use' => __('In Use'),
    'restricted' => __('Restricted'),
    'offsite' => __('Offsite'),
    'missing' => __('Missing'),
];

$conditionLabels = [
    'excellent' => __('Excellent'),
    'good' => __('Good'),
    'fair' => __('Fair'),
    'poor' => __('Poor'),
    'critical' => __('Critical'),
];
?>

<section id="itemPhysicalLocationArea" class="border-bottom">
  <div class="accordion" id="itemLocationAccordion">
    <div class="accordion-item border-0">
      <h2 class="accordion-header" id="itemLocationHeading">
        <button class="accordion-button text-white" type="button" data-bs-toggle="collapse" data-bs-target="#itemLocationCollapse" aria-expanded="true" aria-controls="itemLocationCollapse" style="background-color: #198754;">
          <i class="fas fa-map-marker-alt me-2"></i><?php echo __('Item Physical Location'); ?>
        </button>
      </h2>
      <div id="itemLocationCollapse" class="accordion-collapse collapse show" aria-labelledby="itemLocationHeading">
        <div class="accordion-body p-0">

          <?php if (!empty($locationParts)): ?>
          <div class="field text-break row g-0">
            <h3 class="h6 lh-base m-0 text-muted col-3 border-end text-end p-2"><?php echo __('Item location'); ?></h3>
            <div class="col-9 p-2"><strong><i class="fas fa-folder-open me-1 text-warning"></i><?php echo implode(' &gt; ', $locationParts); ?></strong></div>
          </div>
          <?php endif; ?>

          <?php if (!empty($itemLocation['container'])): ?>
          <!-- Container Sub-Section -->
          <h4 class="h6 mb-0 py-2 px-3 text-white" style="background-color: #198754;"><i class="fas fa-warehouse me-2"></i><?php echo __('Storage Container'); ?></h4>

          <div class="field text-break row g-0">
            <h3 class="h6 lh-base m-0 text-muted col-3 border-end text-end p-2"><?php echo __('Container'); ?></h3>
            <div class="col-9 p-2">
              <a href="<?php echo url_for(['module' => 'physicalobject', 'slug' => $itemLocation['container']['slug']]); ?>" class="fw-bold text-success">
                <?php echo esc_entities($itemLocation['container']['name']); ?>
              </a>
              <?php if (!empty($itemLocation['container']['location'])): ?>
                <span class="text-muted ms-1">(<?php echo esc_entities($itemLocation['container']['location']); ?>)</span>
              <?php endif; ?>
            </div>
          </div>

          <?php if (!empty($containerLocationParts)): ?>
          <div class="field text-break row g-0">
            <h3 class="h6 lh-base m-0 text-muted col-3 border-end text-end p-2"><?php echo __('Container location'); ?></h3>
            <div class="col-9 p-2"><i class="fas fa-building me-1 text-primary"></i><?php echo implode(' &gt; ', $containerLocationParts); ?></div>
          </div>
          <?php endif; ?>

          <?php if (!empty($itemLocation['container']['barcode'])): ?>
          <div class="field text-break row g-0">
            <h3 class="h6 lh-base m-0 text-muted col-3 border-end text-end p-2"><?php echo __('Container barcode'); ?></h3>
            <div class="col-9 p-2"><code><i class="fas fa-barcode me-1"></i><?php echo esc_entities($itemLocation['container']['barcode']); ?></code></div>
          </div>
          <?php endif; ?>

          <?php if (!empty($itemLocation['container']['security_level'])): ?>
          <div class="field text-break row g-0">
            <h3 class="h6 lh-base m-0 text-muted col-3 border-end text-end p-2"><?php echo __('Security level'); ?></h3>
            <div class="col-9 p-2"><span class="badge bg-danger"><i class="fas fa-lock me-1"></i><?php echo ucfirst(esc_entities($itemLocation['container']['security_level'])); ?></span></div>
          </div>
          <?php endif; ?>
          <?php endif; ?>

          <!-- Item Details Sub-Section -->
          <h4 class="h6 mb-0 py-2 px-3 text-white" style="background-color: #198754;"><i class="fas fa-box me-2"></i><?php echo __('Item Details'); ?></h4>

          <?php if (!empty($itemLocation['barcode'])): ?>
          <div class="field text-break row g-0">
            <h3 class="h6 lh-base m-0 text-muted col-3 border-end text-end p-2"><?php echo __('Item barcode'); ?></h3>
            <div class="col-9 p-2"><code><i class="fas fa-barcode me-1"></i><?php echo esc_entities($itemLocation['barcode']); ?></code></div>
          </div>
          <?php endif; ?>

          <?php if (!empty($itemLocation['box_number'])): ?>
          <div class="field text-break row g-0">
            <h3 class="h6 lh-base m-0 text-muted col-3 border-end text-end p-2"><?php echo __('Box'); ?></h3>
            <div class="col-9 p-2"><?php echo esc_entities($itemLocation['box_number']); ?></div>
          </div>
          <?php endif; ?>

          <?php if (!empty($itemLocation['folder_number'])): ?>
          <div class="field text-break row g-0">
            <h3 class="h6 lh-base m-0 text-muted col-3 border-end text-end p-2"><?php echo __('Folder'); ?></h3>
            <div class="col-9 p-2"><?php echo esc_entities($itemLocation['folder_number']); ?></div>
          </div>
          <?php endif; ?>

          <?php if (!empty($itemLocation['shelf'])): ?>
          <div class="field text-break row g-0">
            <h3 class="h6 lh-base m-0 text-muted col-3 border-end text-end p-2"><?php echo __('Shelf'); ?></h3>
            <div class="col-9 p-2"><?php echo esc_entities($itemLocation['shelf']); ?></div>
          </div>
          <?php endif; ?>

          <?php if (!empty($itemLocation['row'])): ?>
          <div class="field text-break row g-0">
            <h3 class="h6 lh-base m-0 text-muted col-3 border-end text-end p-2"><?php echo __('Row'); ?></h3>
            <div class="col-9 p-2"><?php echo esc_entities($itemLocation['row']); ?></div>
          </div>
          <?php endif; ?>

          <?php if (!empty($itemLocation['position'])): ?>
          <div class="field text-break row g-0">
            <h3 class="h6 lh-base m-0 text-muted col-3 border-end text-end p-2"><?php echo __('Position'); ?></h3>
            <div class="col-9 p-2"><?php echo esc_entities($itemLocation['position']); ?></div>
          </div>
          <?php endif; ?>

          <?php if (!empty($itemLocation['item_number'])): ?>
          <div class="field text-break row g-0">
            <h3 class="h6 lh-base m-0 text-muted col-3 border-end text-end p-2"><?php echo __('Item number'); ?></h3>
            <div class="col-9 p-2"><?php echo esc_entities($itemLocation['item_number']); ?></div>
          </div>
          <?php endif; ?>

          <?php if (!empty($itemLocation['extent_value'])): ?>
          <div class="field text-break row g-0">
            <h3 class="h6 lh-base m-0 text-muted col-3 border-end text-end p-2"><?php echo __('Extent'); ?></h3>
            <div class="col-9 p-2"><?php echo esc_entities($itemLocation['extent_value']); ?> <?php echo esc_entities($itemLocation['extent_unit'] ?? ''); ?></div>
          </div>
          <?php endif; ?>

          <!-- Status & Condition Sub-Section -->
          <h4 class="h6 mb-0 py-2 px-3 text-white" style="background-color: #198754;"><i class="fas fa-clipboard-check me-2"></i><?php echo __('Status & Condition'); ?></h4>

          <div class="field text-break row g-0">
            <h3 class="h6 lh-base m-0 text-muted col-3 border-end text-end p-2"><?php echo __('Access status'); ?></h3>
            <div class="col-9 p-2">
              <?php 
                $status = $itemLocation['access_status'] ?? 'available';
                $badgeClass = match($status) {
                    'available' => 'bg-success',
                    'in_use' => 'bg-warning text-dark',
                    'restricted' => 'bg-danger',
                    'offsite' => 'bg-info',
                    'missing' => 'bg-dark',
                    default => 'bg-secondary'
                };
              ?>
              <span class="badge <?php echo $badgeClass; ?>"><?php echo $accessLabels[$status] ?? ucfirst($status); ?></span>
            </div>
          </div>

          <?php if (!empty($itemLocation['condition_status'])): ?>
          <div class="field text-break row g-0">
            <h3 class="h6 lh-base m-0 text-muted col-3 border-end text-end p-2"><?php echo __('Condition'); ?></h3>
            <div class="col-9 p-2">
              <?php 
                $condition = $itemLocation['condition_status'];
                $condBadgeClass = match($condition) {
                    'excellent' => 'bg-success',
                    'good' => 'bg-primary',
                    'fair' => 'bg-warning text-dark',
                    'poor' => 'bg-orange',
                    'critical' => 'bg-danger',
                    default => 'bg-secondary'
                };
              ?>
              <span class="badge <?php echo $condBadgeClass; ?>"><?php echo $conditionLabels[$condition] ?? ucfirst($condition); ?></span>
            </div>
          </div>
          <?php endif; ?>

          <?php if (!empty($itemLocation['condition_notes'])): ?>
          <div class="field text-break row g-0">
            <h3 class="h6 lh-base m-0 text-muted col-3 border-end text-end p-2"><?php echo __('Condition notes'); ?></h3>
            <div class="col-9 p-2"><?php echo nl2br(esc_entities($itemLocation['condition_notes'])); ?></div>
          </div>
          <?php endif; ?>

          <?php if (!empty($itemLocation['last_accessed_at'])): ?>
          <div class="field text-break row g-0">
            <h3 class="h6 lh-base m-0 text-muted col-3 border-end text-end p-2"><?php echo __('Last accessed'); ?></h3>
            <div class="col-9 p-2">
              <?php echo date('Y-m-d H:i', strtotime($itemLocation['last_accessed_at'])); ?>
              <?php if (!empty($itemLocation['accessed_by'])): ?>
                <span class="text-muted">(<?php echo esc_entities($itemLocation['accessed_by']); ?>)</span>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

          <?php if (!empty($itemLocation['notes'])): ?>
          <div class="field text-break row g-0">
            <h3 class="h6 lh-base m-0 text-muted col-3 border-end text-end p-2"><?php echo __('Notes'); ?></h3>
            <div class="col-9 p-2"><?php echo nl2br(esc_entities($itemLocation['notes'])); ?></div>
          </div>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>
</section>
