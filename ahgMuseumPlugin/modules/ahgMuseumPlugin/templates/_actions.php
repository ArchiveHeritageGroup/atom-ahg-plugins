<?php
/**
 * Actions Partial for Information Object
 *
 * @package    ahgMuseumPlugin
 * @subpackage templates
 */

use Illuminate\Database\Capsule\Manager as DB;

// ACL Group IDs
define('GROUP_ADMINISTRATOR', 100);
define('GROUP_EDITOR', 101);
define('GROUP_CONTRIBUTOR', 102);
define('GROUP_TRANSLATOR', 103);

/**
 * Check ACL permissions for resource
 */
function io_check_acl($resource, $actions): bool
{
    $user = sfContext::getInstance()->getUser();
    
    if (!$user->isAuthenticated()) {
        return false;
    }
    
    $userId = $user->getAttribute('user_id');
    if (!$userId) {
        return false;
    }
    
    // Normalize actions to array
    if (!is_array($actions)) {
        $actions = [$actions];
    }
    
    // Get user groups
    $userGroups = DB::table('acl_user_group')
        ->where('user_id', $userId)
        ->pluck('group_id')
        ->toArray();
    
    // Administrator can do everything
    if (in_array(GROUP_ADMINISTRATOR, $userGroups)) {
        return true;
    }
    
    // Editor can do most things
    if (in_array(GROUP_EDITOR, $userGroups)) {
        $editorActions = ['create', 'update', 'delete', 'translate', 'publish'];
        if (count(array_intersect($actions, $editorActions)) > 0) {
            return true;
        }
    }
    
    // Contributor can create and update own
    if (in_array(GROUP_CONTRIBUTOR, $userGroups)) {
        if (in_array('create', $actions)) {
            return true;
        }
        if (in_array('update', $actions) || in_array('translate', $actions)) {
            // Check if user owns resource
            $createdBy = DB::table('object')
                ->where('id', $resource->id)
                ->value('created_by');
            if ($createdBy == $userId) {
                return true;
            }
        }
    }
    
    // Translator can translate
    if (in_array(GROUP_TRANSLATOR, $userGroups) && in_array('translate', $actions)) {
        return true;
    }
    
    return false;
}

/**
 * Check if user is in editor group
 */
function io_user_is_editor(): bool
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
        ->whereIn('group_id', [GROUP_ADMINISTRATOR, GROUP_EDITOR])
        ->exists();
}

/**
 * Check if digital object upload is allowed
 */
function io_is_upload_allowed(): bool
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
 * Get repository upload limit for resource
 */
function io_get_repository_upload_limit($resource): ?int
{
    $repositoryId = $resource->repository_id ?? null;
    
    // If no direct repository, try to inherit from parent
    if (!$repositoryId && isset($resource->lft) && isset($resource->rgt)) {
        $repositoryId = DB::table('information_object')
            ->where('lft', '<', $resource->lft)
            ->where('rgt', '>', $resource->rgt)
            ->whereNotNull('repository_id')
            ->orderBy('lft', 'desc')
            ->value('repository_id');
    }
    
    if (!$repositoryId) {
        return null;
    }
    
    return DB::table('repository')
        ->where('id', $repositoryId)
        ->value('upload_limit');
}

/**
 * Build URL for resource action using slug
 */
function io_url($resource, string $module, string $action): string
{
    $slug = $resource->slug ?? null;
    if ($slug) {
        return url_for(['module' => $module, 'action' => $action, 'slug' => $slug]);
    }
    return url_for(['module' => $module, 'action' => $action, 'id' => $resource->id]);
}

// ========== Template Logic ==========

if (io_check_acl($resource, ['create', 'update', 'delete', 'translate'])) {

    // Get digital objects count using Laravel
    $digitalObjectCount = 0;
    $digitalObject = null;
    try {
        $digitalObject = DB::table('digital_object')
            ->where('object_id', $resource->id)
            ->first();
        $digitalObjectCount = $digitalObject ? 1 : 0;
    } catch (Exception $e) {
    }

    // Check if upload is allowed
    $uploadAllowed = io_is_upload_allowed();

    // Get repository upload limit using Laravel
    $repositoryUploadLimit = io_get_repository_upload_limit($resource);

    // Check if resource has children using Laravel
    $hasChildren = false;
    try {
        $childCount = DB::table('information_object')
            ->where('parent_id', $resource->id)
            ->count();
        $hasChildren = $childCount > 0;
    } catch (Exception $e) {
    }

    ?>

  <ul class="actions mb-3 nav gap-2">

    <?php if (io_check_acl($resource, 'update') || io_check_acl($resource, 'translate')) { ?>
      <li><a href="<?php echo io_url($resource, 'informationobject', 'edit'); ?>" class="btn atom-btn-outline-light"><?php echo __('Edit'); ?></a></li>
    <?php } ?>

    <?php if (io_check_acl($resource, 'delete')) { ?>
      <li><a href="<?php echo io_url($resource, 'informationobject', 'delete'); ?>" class="btn atom-btn-outline-danger"><?php echo __('Delete'); ?></a></li>
    <?php } ?>

    <?php if (io_check_acl($resource, 'create')) { ?>

      <?php
        // Check default template for information objects using Laravel
        $defaultTemplate = DB::table('setting')
            ->join('setting_i18n', 'setting.id', '=', 'setting_i18n.id')
            ->where('setting.scope', 'default_template')
            ->where('setting.name', 'informationobject')
            ->value('setting_i18n.value');

        // Use museum module if museum is default, otherwise use informationobject
        $addModule = ($defaultTemplate == 'museum') ? 'ahgMuseumPlugin' : 'informationobject';
        ?>
      <li><a href="<?php echo url_for(['module' => $addModule, 'action' => 'add', 'parent' => $resource->slug]); ?>" class="btn atom-btn-outline-light"><?php echo __('Add new'); ?></a></li>

      <li><a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'copy', 'source' => $resource->id]); ?>" class="btn atom-btn-outline-light"><?php echo __('Duplicate'); ?></a></li>
    <?php } ?>

    <?php if (io_check_acl($resource, 'update') || io_user_is_editor()) { ?>

      <li><a href="<?php echo io_url($resource, 'default', 'move'); ?>" class="btn atom-btn-outline-light"><?php echo __('Move'); ?></a></li>

      <li>
        <div class="dropup">
          <button type="button" class="btn atom-btn-outline-light dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <?php echo __('More'); ?>
          </button>
          <ul class="dropdown-menu mb-2">

            <li><a href="<?php echo io_url($resource, 'informationobject', 'rename'); ?>" class="dropdown-item"><?php echo __('Rename'); ?></a></li>

            <?php if (io_check_acl($resource, 'publish')) { ?>
              <li><a href="<?php echo io_url($resource, 'informationobject', 'updatePublicationStatus'); ?>"
                     id="update-publication-status"
                     data-cy="update-publication-status"
                     class="dropdown-item"><?php echo __('Update publication status'); ?></a></li>
            <?php } ?>

            <li><hr class="dropdown-divider"></li>

            <li><a href="<?php echo io_url($resource, 'object', 'editPhysicalObjects'); ?>" class="dropdown-item"><?php echo __('Link physical storage'); ?></a></li>

            <li><hr class="dropdown-divider"></li>

            <?php if ($digitalObjectCount > 0 && $uploadAllowed) { ?>
              <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'digitalobject', 'action' => 'edit', 'id' => $digitalObject->id]); ?>">
                <?php echo __('Edit %1%', ['%1%' => mb_strtolower(sfConfig::get('app_ui_label_digitalobject'))]); ?>
              </a></li>
            <?php } elseif ($uploadAllowed) { ?>
              <li><a href="<?php echo io_url($resource, 'object', 'addDigitalObject'); ?>" class="dropdown-item"><?php echo __('Link %1%', ['%1%' => mb_strtolower(sfConfig::get('app_ui_label_digitalobject'))]); ?></a></li>
            <?php } ?>

            <?php if (($repositoryUploadLimit === null || $repositoryUploadLimit != 0) && $uploadAllowed) { ?>
              <li><a href="<?php echo io_url($resource, 'informationobject', 'multiFileUpload'); ?>" class="dropdown-item"><?php echo __('Import digital objects'); ?></a></li>
            <?php } ?>

            <li><hr class="dropdown-divider"></li>

            <li><a href="<?php echo url_for(['module' => 'right', 'action' => 'edit', 'slug' => $resource->slug]); ?>" class="dropdown-item"><?php echo __('Create new rights'); ?></a></li>
            <?php if ($hasChildren) { ?>
              <li><a href="<?php echo url_for(['module' => 'right', 'action' => 'manage', 'slug' => $resource->slug]); ?>" class="dropdown-item"><?php echo __('Manage rights inheritance'); ?></a></li>
            <?php } ?>

            <li><hr class="dropdown-divider"></li>

            <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'grap', 'action' => 'index', 'slug' => $resource->slug]); ?>">
              <?php echo __('View GRAP data'); ?>
            </a></li>

            <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'grap', 'action' => 'edit', 'slug' => $resource->slug]); ?>">
              <?php echo __('Edit GRAP data'); ?>
            </a></li>

            <li><hr class="dropdown-divider"></li>

            <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'spectrum', 'action' => 'index', 'slug' => $resource->slug]); ?>">
              <?php echo __('View Spectrum data'); ?>
            </a></li>

            <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'spectrum', 'action' => 'label', 'slug' => $resource->slug]); ?>">
              <?php echo __('Generate label'); ?>
            </a></li>

            <?php
            // Check for active loans for this object using Laravel
            try {
                $loanOut = DB::table('spectrum_loan')
                    ->join('spectrum_loan_item', 'spectrum_loan.id', '=', 'spectrum_loan_item.loan_id')
                    ->where('spectrum_loan_item.object_id', $resource->id)
                    ->where('spectrum_loan.status', 'active')
                    ->first();
                if ($loanOut) { ?>
                  <li><a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'loanAgreement', 'id' => $loanOut->id]); ?>" class="dropdown-item">
                    <?php echo __('Generate Loan Agreement'); ?>
                  </a></li>
                <?php }
            } catch (Exception $e) { /* Table may not exist */
            } ?>

            <!-- Provenance -->
            <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'cco', 'action' => 'provenance', 'slug' => $resource->slug]); ?>">
              <?php echo __('Provenance'); ?>
            </a></li>

            <!-- Workflow Status -->
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'spectrum', 'action' => 'workflow', 'slug' => $resource->slug]); ?>">
              <?php echo __('Workflow Status'); ?>
            </a></li>

            <?php
            // Check if audit log is enabled using sfConfig (this is a config value, not DB)
            if (sfConfig::get('app_audit_log_enabled', false)) { ?>
              <li><hr class="dropdown-divider"></li>
              <li><a href="<?php echo io_url($resource, 'informationobject', 'modifications'); ?>" class="dropdown-item"><?php echo __('View modification history'); ?></a></li>
            <?php } ?>

          </ul>
        </div>
      </li>

    <?php } ?>

  </ul>
<?php } ?>