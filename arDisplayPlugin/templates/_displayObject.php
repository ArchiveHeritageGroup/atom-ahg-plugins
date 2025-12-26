<?php
/**
 * Universal object display component
 * 
 * @var array $data - Display data from DisplayService::prepareForDisplay()
 */

if (empty($data)) return;

require_once sfConfig::get('sf_plugins_dir') . '/arDisplayPlugin/lib/helper/DisplayHelper.php';

$object = $data['object'];
$profile = $data['profile'];
$digitalObject = $data['digital_object'];
$objectType = $data['object_type'];
$layout = $data['layout'];
$fields = $data['fields'];
?>

<div class="display-object display-<?php echo $layout; ?> type-<?php echo $objectType; ?> <?php echo $data['css_class'] ?? ''; ?>" 
     data-object-id="<?php echo $object->id; ?>" 
     data-profile="<?php echo $profile->code; ?>"
     data-type="<?php echo $objectType; ?>">
    
    <?php // Profile Switcher (if multiple available) ?>
    <?php if (count($data['available_profiles']) > 1): ?>
    <div class="display-profile-switcher d-print-none mb-2">
        <div class="btn-group btn-group-sm">
            <?php foreach ($data['available_profiles'] as $p): ?>
                <a href="?profile=<?php echo $p->code; ?>" 
                   class="btn btn-<?php echo $p->code === $profile->code ? 'primary' : 'outline-secondary'; ?>"
                   title="<?php echo $p->name; ?>">
                    <i class="fas <?php echo get_layout_icon($p->layout_mode); ?>"></i>
                </a>
            <?php endforeach; ?>
        </div>
        <span class="ms-2 text-muted small">
            <i class="fas <?php echo get_type_icon($objectType); ?>"></i>
            <?php echo ucfirst($objectType); ?>
        </span>
    </div>
    <?php endif; ?>

    <?php 
    // Include layout-specific template
    $layoutTemplate = sfConfig::get('sf_plugins_dir') . '/arDisplayPlugin/templates/layouts/_' . $layout . '.php';
    if (file_exists($layoutTemplate)) {
        include $layoutTemplate;
    } else {
        include sfConfig::get('sf_plugins_dir') . '/arDisplayPlugin/templates/layouts/_detail.php';
    }
    ?>

    <?php // Actions ?>
    <?php if (!empty($data['actions'])): ?>
    <div class="object-actions mt-4 pt-3 border-top d-print-none">
        <?php foreach ($data['actions'] as $action): 
            $actionTemplate = sfConfig::get('sf_plugins_dir') . '/arDisplayPlugin/templates/actions/_' . $action . '.php';
            if (file_exists($actionTemplate)) {
                include $actionTemplate;
            }
        endforeach; ?>
    </div>
    <?php endif; ?>
</div>
