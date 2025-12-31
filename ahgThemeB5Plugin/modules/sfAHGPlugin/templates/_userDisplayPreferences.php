<?php
/**
 * User Display Preferences Component
 * 
 * Include in user profile/settings page:
 * <?php include_component('sfAHGPlugin', 'userDisplayPreferences'); ?>
 */

use AtomExtensions\Services\DisplayModeService;

$displayService = new DisplayModeService();

$modules = [
    'informationobject' => 'Archival Descriptions',
    'actor' => 'Authority Records',
    'repository' => 'Archival Institutions',
    'digitalobject' => 'Digital Objects',
    'library' => 'Library',
    'gallery' => 'Gallery',
    'dam' => 'Digital Asset Management',
    'search' => 'Search Results',
];

$allModes = [
    'tree' => ['name' => 'Hierarchy', 'icon' => 'bi-diagram-3'],
    'grid' => ['name' => 'Grid', 'icon' => 'bi-grid-3x3-gap'],
    'gallery' => ['name' => 'Gallery', 'icon' => 'bi-images'],
    'list' => ['name' => 'List', 'icon' => 'bi-list-ul'],
    'timeline' => ['name' => 'Timeline', 'icon' => 'bi-clock-history'],
];
?>

<div class="user-display-preferences">
    <h4 class="mb-3">
        <i class="bi bi-display me-2"></i>
        <?php echo __('Display Preferences'); ?>
    </h4>
    
    <p class="text-muted small mb-4">
        <?php echo __('Customize how content is displayed when browsing different modules. 
        Your preferences will be remembered across sessions.'); ?>
    </p>
    
    <div class="accordion" id="displayPrefsAccordion">
        <?php foreach ($modules as $module => $label): ?>
            <?php 
            $settings = $displayService->getDisplaySettings($module);
            $source = $settings['_source'] ?? 'default';
            $canOverride = $displayService->canOverride($module);
            $hasCustom = $displayService->hasCustomPreference($module);
            $availableModes = $displayService->getModeMetas($module);
            ?>
            
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#pref_<?php echo $module; ?>">
                        <span class="me-auto"><?php echo $label; ?></span>
                        
                        <?php if (!$canOverride): ?>
                            <span class="badge bg-secondary me-2" title="Locked by administrator">
                                <i class="bi bi-lock"></i>
                            </span>
                        <?php elseif ($hasCustom): ?>
                            <span class="badge bg-primary me-2" title="Custom preference">
                                <i class="bi bi-person-check"></i>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-light text-dark me-2" title="Using default">
                                Default
                            </span>
                        <?php endif; ?>
                        
                        <span class="badge bg-info">
                            <i class="bi <?php echo $allModes[$settings['display_mode']]['icon'] ?? 'bi-list-ul'; ?>"></i>
                            <?php echo $allModes[$settings['display_mode']]['name'] ?? 'List'; ?>
                        </span>
                    </button>
                </h2>
                
                <div id="pref_<?php echo $module; ?>" class="accordion-collapse collapse" 
                     data-bs-parent="#displayPrefsAccordion">
                    <div class="accordion-body">
                        <?php if (!$canOverride): ?>
                            <div class="alert alert-secondary">
                                <i class="bi bi-lock me-2"></i>
                                Display mode for this module is set by the administrator.
                            </div>
                        <?php else: ?>
                            <form class="user-pref-form" data-module="<?php echo $module; ?>">
                                <div class="row g-3">
                                    <!-- Display Mode -->
                                    <div class="col-md-6">
                                        <label class="form-label"><?php echo __('Display Mode'); ?></label>
                                        <div class="btn-group d-flex" role="group">
                                            <?php foreach ($availableModes as $mode => $meta): ?>
                                                <input type="radio" class="btn-check" 
                                                       name="display_mode" 
                                                       id="dm_<?php echo $module; ?>_<?php echo $mode; ?>"
                                                       value="<?php echo $mode; ?>"
                                                       <?php echo $meta['active'] ? 'checked' : ''; ?>>
                                                <label class="btn btn-outline-primary" 
                                                       for="dm_<?php echo $module; ?>_<?php echo $mode; ?>"
                                                       title="<?php echo $meta['description']; ?>">
                                                    <i class="bi <?php echo $meta['icon']; ?>"></i>
                                                    <span class="d-none d-lg-inline ms-1"><?php echo $meta['name']; ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Items Per Page -->
                                    <div class="col-md-3">
                                        <label class="form-label"><?php echo __('Items Per Page'); ?></label>
                                        <select name="items_per_page" class="form-select">
                                            <?php foreach ([10, 20, 30, 50, 100] as $count): ?>
                                                <option value="<?php echo $count; ?>"
                                                    <?php echo ($settings['items_per_page'] ?? 30) == $count ? 'selected' : ''; ?>>
                                                    <?php echo $count; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Card Size -->
                                    <div class="col-md-3">
                                        <label class="form-label"><?php echo __('Card Size'); ?></label>
                                        <select name="card_size" class="form-select">
                                            <option value="small" <?php echo ($settings['card_size'] ?? 'medium') === 'small' ? 'selected' : ''; ?>>Small</option>
                                            <option value="medium" <?php echo ($settings['card_size'] ?? 'medium') === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                            <option value="large" <?php echo ($settings['card_size'] ?? 'medium') === 'large' ? 'selected' : ''; ?>>Large</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Options -->
                                    <div class="col-12">
                                        <div class="form-check form-check-inline">
                                            <input type="checkbox" class="form-check-input" 
                                                   name="show_thumbnails" value="1"
                                                   id="thumb_<?php echo $module; ?>"
                                                   <?php echo ($settings['show_thumbnails'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="thumb_<?php echo $module; ?>">
                                                Show thumbnails
                                            </label>
                                        </div>
                                        
                                        <div class="form-check form-check-inline">
                                            <input type="checkbox" class="form-check-input" 
                                                   name="show_descriptions" value="1"
                                                   id="desc_<?php echo $module; ?>"
                                                   <?php echo ($settings['show_descriptions'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="desc_<?php echo $module; ?>">
                                                Show descriptions
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <!-- Actions -->
                                    <div class="col-12 d-flex justify-content-between">
                                        <?php if ($hasCustom): ?>
                                            <button type="button" class="btn btn-outline-secondary btn-sm reset-pref-btn">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>
                                                Reset to Default
                                            </button>
                                        <?php else: ?>
                                            <span></span>
                                        <?php endif; ?>
                                        
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="bi bi-save me-1"></i>
                                            Save Preference
                                        </button>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script <?php echo __(sfConfig::get('csp_nonce', '')); ?>>
document.addEventListener('DOMContentLoaded', function() {
    const API_BASE = '/atom-framework/public/api/display-mode.php';
    
    // Save preference forms
    document.querySelectorAll('.user-pref-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const module = this.dataset.module;
            const data = new FormData(this);
            data.append('action', 'preferences');
            data.append('module', module);
            
            const submitBtn = this.querySelector('[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';
            
            try {
                const response = await fetch(API_BASE, {
                    method: 'POST',
                    body: data
                });
                const result = await response.json();
                
                if (result.success) {
                    submitBtn.innerHTML = '<i class="bi bi-check-lg"></i> Saved!';
                    submitBtn.classList.remove('btn-primary');
                    submitBtn.classList.add('btn-success');
                    
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.classList.remove('btn-success');
                        submitBtn.classList.add('btn-primary');
                        submitBtn.disabled = false;
                    }, 2000);
                } else {
                    throw new Error(result.error || 'Save failed');
                }
            } catch (error) {
                alert('Error: ' + error.message);
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });
    });
    
    // Reset buttons
    document.querySelectorAll('.reset-pref-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const form = this.closest('form');
            const module = form.dataset.module;
            
            if (!confirm('Reset display preferences for this module to default?')) {
                return;
            }
            
            const data = new FormData();
            data.append('action', 'reset');
            data.append('module', module);
            
            try {
                const response = await fetch(API_BASE, {
                    method: 'POST',
                    body: data
                });
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    throw new Error(result.error || 'Reset failed');
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        });
    });
});
</script>
