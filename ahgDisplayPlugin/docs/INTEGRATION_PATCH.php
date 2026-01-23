<?php
/**
 * INTEGRATION PATCH for DisplayService.php
 *
 * This file shows the modifications needed to integrate rights and
 * privacy (visual redaction) into the centralized display system.
 *
 * FILE: /plugins/ahgDisplayPlugin/lib/Services/DisplayService.php
 *
 * =====================================================================
 * STEP 1: Add this at the top of the file (after use statements)
 * =====================================================================
 */

// Add after: use Illuminate\Database\Capsule\Manager as DB;
// require_once __DIR__ . '/../DisplayRightsExtension.php';
// require_once __DIR__ . '/../DisplayPrivacyExtension.php';

/**
 * =====================================================================
 * STEP 2: Modify the prepareForDisplay() method
 * 
 * Replace the existing method with this updated version:
 * =====================================================================
 */

public function prepareForDisplay(int $objectId, ?string $profileCode = null, string $context = 'default'): array
{
    // Get base object data with all standard AtoM fields
    $object = $this->getObjectData($objectId);
    if (!$object) return [];

    // Get profile
    $profile = null;
    if ($profileCode) {
        $profile = DB::table('display_profile as dp')
            ->leftJoin('display_profile_i18n as dpi', function($j) {
                $j->on('dp.id', '=', 'dpi.id')->where('dpi.culture', '=', $this->culture);
            })
            ->where('dp.code', $profileCode)
            ->select('dp.*', 'dpi.name', 'dpi.description')
            ->first();
    }
    $profile = $profile ?? $this->getProfile($objectId, $context);

    // Get digital object
    $digitalObject = $this->getDigitalObject($objectId);

    // Get object type
    $objectType = $this->getObjectType($objectId);

    // Build field groups
    $fieldGroups = [
        'identity' => $this->buildFieldGroup($object, json_decode($profile->identity_fields ?? '[]', true)),
        'description' => $this->buildFieldGroup($object, json_decode($profile->description_fields ?? '[]', true)),
        'context' => $this->buildFieldGroup($object, json_decode($profile->context_fields ?? '[]', true)),
        'access' => $this->buildFieldGroup($object, json_decode($profile->access_fields ?? '[]', true)),
        'technical' => $this->buildFieldGroup($object, json_decode($profile->technical_fields ?? '[]', true)),
    ];

    // Get current user
    $userId = null;
    $canEdit = false;
    try {
        $sf_user = sfContext::getInstance()->getUser();
        $userId = $sf_user->getAttribute('user_id');
        $canEdit = sfContext::getInstance()->getUser()->isAdministrator() || sfContext::getInstance()->getUser()->hasCredential('editor');
    } catch (Exception $e) {
        // Context not available (CLI, etc.)
    }

    // Get actions from profile
    $actions = json_decode($profile->available_actions ?? '[]', true);

    // === GET RIGHTS DATA ===
    $rightsData = [];
    if (class_exists('DisplayRightsExtension')) {
        $rightsData = DisplayRightsExtension::getRightsData($objectId, $userId, $canEdit);
        // Add rights action if appropriate
        $actions = DisplayRightsExtension::addRightsAction($actions, $objectId, $canEdit);
    }

    // === GET PRIVACY/REDACTION DATA ===
    $privacyData = [];
    if (class_exists('DisplayPrivacyExtension')) {
        $privacyData = DisplayPrivacyExtension::getPrivacyData($objectId, $canEdit);
        // Add visual_redaction action for editors with digital objects
        $actions = DisplayPrivacyExtension::addRedactionAction($actions, $objectId, $canEdit);
    }

    // Build display data
    $data = [
        'object' => $object,
        'object_type' => $objectType,
        'profile' => $profile,
        'digital_object' => $digitalObject,
        'layout' => $profile->layout_mode,
        'thumbnail_size' => $profile->thumbnail_size,
        'thumbnail_position' => $profile->thumbnail_position,
        'fields' => $fieldGroups,
        'actions' => $actions,
        'available_profiles' => $this->getAvailableProfiles($objectId),
        'css_class' => $profile->css_class,
        'rights' => $rightsData,
        'privacy' => $privacyData, // Visual redaction data
    ];

    // Add children for hierarchy view
    if ($profile->layout_mode === 'hierarchy') {
        $data['children'] = $this->getChildren($objectId);
        $data['ancestors'] = $this->getAncestors($objectId);
    }

    // Add siblings for gallery view
    if ($profile->layout_mode === 'gallery') {
        $data['siblings'] = $this->getSiblings($objectId);
    }

    return $data;
}

/**
 * =====================================================================
 * STEP 3: Update the detail layout template
 * 
 * FILE: /plugins/ahgDisplayPlugin/templates/layouts/_detail.php
 * 
 * Add this line after the Technical Section (around line 101):
 * =====================================================================
 */

// Add after: <?php endif; ?> (closing technical section)
// <?php include __DIR__ . '/_rights_section.php'; ?>

/**
 * =====================================================================
 * COMPLETE UPDATED _detail.php FOR REFERENCE
 * =====================================================================
 */

/*
<?php
// ... existing code ...

        <?php // Technical Section (DAM) ?>
        <?php if (!empty($fields['technical'])): ?>
        <section class="field-section technical-section mb-4">
            <h5 class="section-title border-bottom pb-2 mb-3">
                <i class="fas fa-cog text-muted me-2"></i>Technical Details
            </h5>
            <dl class="row mb-0">
                <?php foreach ($fields['technical'] as $field): ?>
                <dt class="col-sm-4 text-muted"><?php echo $field['label']; ?></dt>
                <dd class="col-sm-8"><?php echo format_field_value($field); ?></dd>
                <?php endforeach; ?>
            </dl>
        </section>
        <?php endif; ?>

        <?php // === NEW: Rights Section === ?>
        <?php include __DIR__ . '/_rights_section.php'; ?>
        
    </div>
</div>
*/
