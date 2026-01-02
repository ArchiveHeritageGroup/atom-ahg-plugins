<?php
/**
 * Administration area partial for Museum edit
 * Bootstrap 5 compatible
 */
use Illuminate\Database\Capsule\Manager as DB;

// Get display standards for dropdown - taxonomy 70
$displayStandards = [];
$terms = DB::table('term as t')
    ->join('term_i18n as ti', 't.id', '=', 'ti.id')
    ->where('t.taxonomy_id', 70) // Display standard taxonomy
    ->where('ti.culture', 'en')
    ->orderBy('ti.name')
    ->select('t.id', 'ti.name')
    ->get();

foreach ($terms as $term) {
    $displayStandards[$term->id] = $term->name;
}

// Get current display standard - default to 449 (Museum CCO)
// Get museum term ID dynamically
$museumTerm = \Illuminate\Database\Capsule\Manager::table('term')
    ->where('code', 'museum')
    ->where('taxonomy_id', 70)
    ->value('id');
$currentDisplayStandard = $museumTerm ?? 353;
if (isset($resource) && $resource && isset($resource->display_standard_id) && $resource->display_standard_id) {
    $currentDisplayStandard = $resource->display_standard_id;
}

// Get source culture
$sourceCulture = isset($resource) && isset($resource->source_culture) ? $resource->source_culture : 'en';

// Get updated at
$updatedAt = null;
if (isset($resource) && $resource && isset($resource->id)) {
    $obj = DB::table('object')->where('id', $resource->id)->first();
    $updatedAt = $obj->updated_at ?? null;
}
?>

<div class="accordion-item">
    <h2 class="accordion-header" id="admin-heading">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                data-bs-target="#admin-collapse" aria-expanded="false" aria-controls="admin-collapse">
            <?php echo __('Administration area'); ?>
        </button>
    </h2>
    <div id="admin-collapse" class="accordion-collapse collapse" aria-labelledby="admin-heading">
        <div class="accordion-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('Source language'); ?></label>
                        <div>
                            <?php echo format_language($sourceCulture); ?>
                        </div>
                    </div>
                    
                    <?php if ($updatedAt): ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?php echo __('Last updated'); ?></label>
                        <div>
                            <?php echo date('F j, Y, g:i a', strtotime($updatedAt)); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="displayStandard" class="form-label fw-bold"><?php echo __('Display standard'); ?></label>
                        <select name="displayStandard" id="displayStandard" class="form-select">
                            <?php foreach ($displayStandards as $id => $name): ?>
                                <option value="<?php echo $id; ?>" <?php echo ((int)$currentDisplayStandard === (int)$id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted"><?php echo __('Select the display standard for this record'); ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="displayStandardUpdateDescendants" 
                                   name="displayStandardUpdateDescendants" value="1">
                            <label class="form-check-label" for="displayStandardUpdateDescendants">
                                <?php echo __('Make this selection the new default for existing children'); ?>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
