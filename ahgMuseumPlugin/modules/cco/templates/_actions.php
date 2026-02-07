<?php
/**
 * CCO Actions Partial
 *
 * @package    ahgMuseumPlugin
 * @subpackage templates
 */

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Check ACL permission for resource
 */
function cco_check_acl($resource, string $action): bool
{
    $user = sfContext::getInstance()->getUser();
    
    if (!$user->isAuthenticated()) {
        return false;
    }
    
    $userId = $user->getAttribute('user_id');
    if (!$userId) {
        return false;
    }
    
    // Get user groups
    $userGroups = DB::table('acl_user_group')
        ->where('user_id', $userId)
        ->pluck('group_id')
        ->toArray();
    
    // Administrator can do everything
    if (in_array(100, $userGroups)) { // GROUP_ADMINISTRATOR
        return true;
    }
    
    // Editor can update, translate, create, delete
    if (in_array(101, $userGroups)) { // GROUP_EDITOR
        if (in_array($action, ['update', 'translate', 'create', 'delete', 'publish'])) {
            return true;
        }
    }
    
    // Contributor can create and update own
    if (in_array(102, $userGroups)) { // GROUP_CONTRIBUTOR
        if ($action === 'create') {
            return true;
        }
        // Check if user owns the resource for update/delete
        if (in_array($action, ['update', 'delete']) && $resource) {
            // Check if user created this resource
            $resourceUserId = DB::table('object')
                ->where('id', $resource->id)
                ->value('created_by');
            if ($resourceUserId == $userId) {
                return true;
            }
        }
    }
    
    // Translator can translate
    if (in_array(103, $userGroups) && $action === 'translate') { // GROUP_TRANSLATOR
        return true;
    }
    
    return false;
}

/**
 * Check if user is in editor group
 */
function cco_user_is_editor(): bool
{
    $user = sfContext::getInstance()->getUser();
    
    if (!$user->isAuthenticated()) {
        return false;
    }
    
    $userId = $user->getAttribute('user_id');
    if (!$userId) {
        return false;
    }
    
    return DB::table('acl_user_group')
        ->where('user_id', $userId)
        ->whereIn('group_id', [100, 101]) // Admin or Editor
        ->exists();
}

/**
 * Check if digital object upload is allowed
 */
function cco_is_upload_allowed(): bool
{
    // Check global setting
    if (!sfConfig::get('app_upload_enabled', true)) {
        return false;
    }
    
    // Check user authentication
    $user = sfContext::getInstance()->getUser();
    if (!$user->isAuthenticated()) {
        return false;
    }
    
    return true;
}

/**
 * Build URL for resource action using slug
 */
function cco_url($resource, string $module, string $action): string
{
    $slug = $resource->slug ?? null;
    if ($slug) {
        return url_for(['module' => $module, 'action' => $action, 'slug' => $slug]);
    }
    return url_for(['module' => $module, 'action' => $action, 'id' => $resource->id]);
}

/**
 * Get digital object for resource
 */
function cco_get_digital_object($resourceId): ?object
{
    return DB::table('digital_object')
        ->where('object_id', $resourceId)
        ->first();
}

/**
 * Check if resource has children
 */
function cco_has_children($resourceId): bool
{
    return DB::table('information_object')
        ->where('parent_id', $resourceId)
        ->exists();
}

/**
 * Get repository for resource (with inheritance)
 */
function cco_get_repository($resource): ?object
{
    if (isset($resource->repository_id) && $resource->repository_id) {
        return DB::table('repository')
            ->where('id', $resource->repository_id)
            ->first();
    }
    return null;
}
?>

<section class="actions">
  <ul>

      <?php if (cco_check_acl($resource, 'update') || cco_check_acl($resource, 'translate')) { ?>
        <li><?php //echo link_to(__('Edit'), cco_url($resource, 'cco', 'edit'), ['class' => 'c-btn c-btn-submit']); ?></li>
      <?php } ?>
    <?php if (cco_check_acl($resource, 'update') || cco_check_acl($resource, 'translate')) { ?>
      <li><a href="<?php echo cco_url($resource, 'informationobject', 'edit'); ?>" class="btn atom-btn-outline-light"><?php echo __('Edit'); ?></a></li>
    <?php } ?>
    
      <?php if (cco_check_acl($resource, 'delete')) { ?>
        <li><a href="<?php echo cco_url($resource, 'cco', 'delete'); ?>" class="c-btn c-btn-delete"><?php echo __('Delete'); ?></a></li>
      <?php } ?>

      <?php if (cco_check_acl($resource, 'create')) { ?>
        <li><?php echo link_to(__('Add new'), ['module' => 'cco', 'action' => 'add', 'parent' => $resource->slug], ['class' => 'c-btn']); ?></li>
        <li><?php echo link_to(__('Duplicate'), ['module' => 'cco', 'action' => 'copy', 'source' => $resource->id], ['class' => 'c-btn']); ?></li>
      <?php } ?>

      <?php if (cco_check_acl($resource, 'update') || cco_user_is_editor()) { ?>

        <li><a href="<?php echo cco_url($resource, 'default', 'move'); ?>" class="c-btn"><?php echo __('Move'); ?></a></li>

        <li class="divider"></li>

        <li>
       <div class="dropup">
          <button type="button" class="btn atom-btn-outline-light dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <?php echo __('More'); ?>
          </button>
          <ul class="dropdown-menu mb-2">

            <li><a href="<?php echo cco_url($resource, 'informationobject', 'rename'); ?>" class="dropdown-item"><?php echo __('Rename'); ?></a></li>

            <?php if (cco_check_acl($resource, 'publish')) { ?>
              <li><a href="<?php echo cco_url($resource, 'informationobject', 'updatePublicationStatus'); ?>" 
                     id="update-publication-status" 
                     data-cy="update-publication-status" 
                     class="dropdown-item"><?php echo __('Update publication status'); ?></a></li>
            <?php } ?>

            <li><hr class="dropdown-divider"></li>

           <li><a href="<?php echo cco_url($resource, 'object', 'editPhysicalObjects'); ?>" class="dropdown-item"><?php echo __('Link physical storage'); ?></a></li>

              <li class="divider"></li>

              <?php 
              $digitalObject = cco_get_digital_object($resource->id);
              if ($digitalObject && cco_is_upload_allowed()) { ?>
                <li><a href="<?php echo url_for(['module' => 'digitalobject', 'action' => 'edit', 'id' => $digitalObject->id]); ?>" class="dropdown-item"><?php echo __('Edit %1%', ['%1%' => mb_strtolower(sfConfig::get('app_ui_label_digitalobject'))]); ?></a></li>

              <?php } elseif (cco_is_upload_allowed()) { ?>
                
                <li>
                    <a href="<?php echo cco_url($resource, 'object', 'addDigitalObject'); ?>" class="dropdown-item"><?php echo __('Link %1%', ['%1%' => mb_strtolower(sfConfig::get('app_ui_label_digitalobject'))]); ?></a>
                </li>
              <?php } ?>


            <?php 
            $repository = cco_get_repository($resource);
            if ((null === $repository || $repository->upload_limit != 0) && cco_is_upload_allowed()) { ?>
              <li><a href="<?php echo cco_url($resource, 'informationobject', 'multiFileUpload'); ?>" class="dropdown-item"><?php echo __('Import digital objects'); ?></a></li>
            <?php } ?>

            <li><hr class="dropdown-divider"></li>

            <li><a href="<?php echo url_for(['module' => 'right', 'action' => 'edit', 'slug' => $resource->slug]); ?>" class="dropdown-item"><?php echo __('Create new rights'); ?></a></li>
            <li><a href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'edit', 'slug' => $resource->slug]); ?>" class="dropdown-item"><?php echo __('Extended Rights'); ?></a></li>
            <?php if (cco_has_children($resource->id)) { ?>
              <li><a href="<?php echo url_for(['module' => 'right', 'action' => 'manage', 'slug' => $resource->slug]); ?>" class="dropdown-item"><?php echo __('Manage rights inheritance'); ?></a></li>
            <?php } ?>

            <hr class="dropdown-divider">
            <a class="dropdown-item" href="<?php echo cco_url($resource, 'grap', 'index'); ?>">
                <?php echo __('View GRAP data'); ?>
            </a>

            <a class="dropdown-item" href="<?php echo cco_url($resource, 'grapReport', 'edit'); ?>">
                <?php echo __('Edit GRAP data'); ?>
            </a>
                    
            <?php if (ahg_is_plugin_enabled('ahgSpectrumPlugin')): ?>
            <hr class="dropdown-divider">
            <a class="dropdown-item" href="<?php echo cco_url($resource, 'spectrum', 'index'); ?>">
                <?php echo __('View Spectrum data'); ?>
            </a>
            <a class="dropdown-item" href="<?php echo url_for(['module' => 'spectrum', 'action' => 'barcode', 'slug' => $resource->slug, 'type' => 'label']); ?>">
                <?php echo __('Generate label'); ?>
            </a>
          <?php
          // Check for active loans for this object
          $loanOut = DB::table('spectrum_loan_out')
              ->where('object_id', $resource->id)
              ->whereNull('actual_return_date')
              ->first();
          if ($loanOut) { ?>
                <a href="<?php echo url_for('/spectrum/loan-agreement/view/' . $loanOut->id . '?type=out'); ?>"
                   class="dropdown-item"><?php echo __('Generate Loan Agreement'); ?></a>
          <?php } ?>
            <!-- Workflow Status -->
            <a class="dropdown-item" href="<?php echo url_for(['module' => 'spectrum', 'action' => 'workflow', 'procedure_type' => 'acquisition', 'record_id' => $resource->id]); ?>">
                <?php echo __('Workflow Status'); ?>
            </a>
            <?php endif; ?>
            
            <!-- Provenance -->
            <a class="dropdown-item" href="<?php echo cco_url($resource, 'cco', 'provenance'); ?>">
                <?php echo __('Provenance'); ?>
            </a>

          <?php if (sfConfig::get('app_audit_log_enabled', false)) { ?>
            <li class="divider"></li>

            <li><a href="<?php echo cco_url($resource, 'informationobject', 'modifications'); ?>" class="dropdown-item"><?php echo __('View modification history'); ?></a></li>
          <?php } ?>


          </ul>
          </div>
        </li>

      <?php } ?>

  </ul>
</section>