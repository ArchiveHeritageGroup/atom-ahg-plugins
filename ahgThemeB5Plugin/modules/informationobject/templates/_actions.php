<?php
/**
 * Actions Partial for Information Object
 * Works with QubitInformationObject
 */
use Illuminate\Database\Capsule\Manager as DB;
$cspNonce = sfConfig::get('csp_nonce', '');
?>
<style <?php echo $cspNonce; ?>>
.dropdown-menu .dropend { position: relative; }
.dropdown-menu .dropend > .dropdown-menu { display: none; position: absolute; left: 100%; top: auto; bottom: 0; }
.dropdown-menu .dropend:hover > .dropdown-menu,
.dropup .dropdown-menu .dropend:hover > .dropdown-menu,
.dropup .dropdown-menu.show .dropend:hover > .dropdown-menu { display: block !important; visibility: visible !important; opacity: 1 !important; }
</style>
<?php
// Initialize Laravel if needed
if (\AtomExtensions\Database\DatabaseBootstrap::getCapsule() === null) {
    \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
}
// ACL Group IDs
if (!defined('GROUP_ADMINISTRATOR')) { define('GROUP_ADMINISTRATOR', 100); }
if (!defined('GROUP_EDITOR')) { define('GROUP_EDITOR', 101); }
if (!defined('GROUP_CONTRIBUTOR')) { define('GROUP_CONTRIBUTOR', 102); }
if (!defined('GROUP_TRANSLATOR')) { define('GROUP_TRANSLATOR', 103); }

if (!function_exists('io_check_acl')) {
    function io_check_acl($resource, $actions): bool
    {
        $user = sfContext::getInstance()->getUser();
        if (!$user->isAuthenticated()) return false;
        $userId = $user->getAttribute('user_id');
        if (!$userId) return false;
        if (!is_array($actions)) $actions = [$actions];
        $userGroups = DB::table('acl_user_group')->where('user_id', $userId)->pluck('group_id')->toArray();
        if (in_array(GROUP_ADMINISTRATOR, $userGroups)) return true;
        if (in_array(GROUP_EDITOR, $userGroups)) {
            $editorActions = ['create', 'update', 'delete', 'translate', 'publish'];
            if (count(array_intersect($actions, $editorActions)) > 0) return true;
        }
        if (in_array(GROUP_CONTRIBUTOR, $userGroups)) {
            if (in_array('create', $actions)) return true;
        }
        if (in_array(GROUP_TRANSLATOR, $userGroups) && in_array('translate', $actions)) return true;
        return false;
    }
}

if (!function_exists('io_user_is_editor')) {
    function io_user_is_editor(): bool
    {
        $user = sfContext::getInstance()->getUser();
        if (!$user->isAuthenticated()) return false;
        $userId = $user->getAttribute('user_id');
        if (!$userId) return false;
        return DB::table('acl_user_group')->where('user_id', $userId)->whereIn('group_id', [GROUP_ADMINISTRATOR, GROUP_EDITOR])->exists();
    }
}

if (!function_exists('io_is_upload_allowed')) {
    function io_is_upload_allowed(): bool
    {
        if (!sfConfig::get('app_upload_enabled', true)) return false;
        $user = sfContext::getInstance()->getUser();
        if (!$user->isAuthenticated()) return false;
        return true;
    }
}

if (!function_exists('io_get_repository_upload_limit')) {
    function io_get_repository_upload_limit($resource): ?int
    {
        $repositoryId = null;
        
        if ($resource instanceof QubitInformationObject) {
            $repo = $resource->getRepository(['inherit' => true]);
            $repositoryId = $repo ? $repo->id : null;
        } else {
            $resourceId = is_object($resource) ? ($resource->id ?? null) : null;
            if ($resourceId) {
                $repositoryId = DB::table('information_object')->where('id', $resourceId)->value('repository_id');
                if (!$repositoryId) {
                    $lft = $resource->lft ?? null;
                    $rgt = $resource->rgt ?? null;
                    if ($lft && $rgt) {
                        $repositoryId = DB::table('information_object')
                            ->where('lft', '<', $lft)
                            ->where('rgt', '>', $rgt)
                            ->whereNotNull('repository_id')
                            ->orderBy('lft', 'desc')
                            ->value('repository_id');
                    }
                }
            }
        }
        
        if (!$repositoryId) return null;
        return DB::table('repository')->where('id', $repositoryId)->value('upload_limit');
    }
}

if (!function_exists('io_url')) {
    function io_url($resource, string $module, string $action): string
    {
        $slug = ($resource instanceof QubitInformationObject) ? $resource->slug : ($resource->slug ?? null);
        if ($slug) return url_for(['module' => $module, 'action' => $action, 'slug' => $slug]);
        $id = is_object($resource) ? ($resource->id ?? null) : $resource;
        return url_for(['module' => $module, 'action' => $action, 'id' => $id]);
    }
}

if (!function_exists('io_get_id')) {
    function io_get_id($resource): ?int
    {
        if ($resource instanceof QubitInformationObject) return $resource->id;
        return is_object($resource) ? ($resource->id ?? null) : null;
    }
}

if (!function_exists('io_get_slug')) {
    function io_get_slug($resource): ?string
    {
        if ($resource instanceof QubitInformationObject) return $resource->slug;
        return is_object($resource) ? ($resource->slug ?? null) : null;
    }
}

// Check if a plugin is enabled
if (!function_exists('checkPluginEnabled')) {
    function checkPluginEnabled($pluginName) {
        static $plugins = null;
        if ($plugins === null) {
            try {
                $conn = Propel::getConnection();
                $stmt = $conn->prepare('SELECT name FROM atom_plugin WHERE is_enabled = 1');
                $stmt->execute();
                $plugins = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
            } catch (Exception $e) {
                $plugins = [];
            }
        }
        return isset($plugins[$pluginName]);
    }
}

// ========== Template Logic ==========
$resourceId = io_get_id($resource);
$resourceSlug = io_get_slug($resource);

if (io_check_acl($resource, ['create', 'update', 'delete', 'translate'])) {
    $digitalObjectCount = 0;
    $digitalObject = null;
    
    if ($resource instanceof QubitInformationObject) {
        $digitalObjects = $resource->digitalObjectsRelatedByobjectId;
        $digitalObjectCount = count($digitalObjects);
        $digitalObject = $digitalObjectCount > 0 ? $digitalObjects[0] : null;
    } else {
        $digitalObject = DB::table('digital_object')->where('object_id', $resourceId)->first();
        $digitalObjectCount = $digitalObject ? 1 : 0;
    }

    $uploadAllowed = io_is_upload_allowed();
    $repositoryUploadLimit = io_get_repository_upload_limit($resource);

    $hasChildren = false;
    if ($resource instanceof QubitInformationObject) {
        $hasChildren = $resource->hasChildren();
    } else {
        $hasChildren = DB::table('information_object')->where('parent_id', $resourceId)->exists();
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
      // Detect sector from current module context first
      $addModule = 'informationobject';
      $currentModule = sfContext::getInstance()->getModuleName();
      
      // If viewing through a sector plugin, use that plugin for Add new
      if (in_array($currentModule, ['ahgMuseumPlugin', 'ahgLibraryPlugin', 'ahgGalleryPlugin', 'ahgDAMPlugin'])) {
          $addModule = $currentModule;
      } else {
          // Otherwise detect from level of description
          $levelOfDescId = $resource->levelOfDescriptionId ?? null;
          if ($levelOfDescId) {
              $sector = DB::table('level_of_description_sector')
                  ->where('term_id', $levelOfDescId)
                  ->value('sector');
              if ($sector === 'library') {
                  $addModule = 'ahgLibraryPlugin';
              } elseif ($sector === 'museum') {
                  $addModule = 'ahgMuseumPlugin';
              } elseif ($sector === 'gallery') {
                  $addModule = 'ahgGalleryPlugin';
              } elseif ($sector === 'dam') {
                  $addModule = 'ahgDAMPlugin';
              }
          }
          // Fallback to default template if no sector detected
          if ($addModule === 'informationobject') {
              $defaultTemplate = DB::table('setting')
                  ->join('setting_i18n', 'setting.id', '=', 'setting_i18n.id')
                  ->where('setting.scope', 'default_template')
                  ->where('setting.name', 'informationobject')
                  ->value('setting_i18n.value');
              if ($defaultTemplate === 'museum') {
                  $addModule = 'ahgMuseumPlugin';
              } elseif ($defaultTemplate === 'library') {
                  $addModule = 'ahgLibraryPlugin';
              }
          }
      }
    ?>
    <li><a href="<?php echo url_for(['module' => $addModule, 'action' => 'add', 'parent' => $resourceSlug]); ?>" class="btn atom-btn-outline-light"><?php echo __('Add new'); ?></a></li>
    <li><a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'copy', 'source' => $resourceId]); ?>" class="btn atom-btn-outline-light"><?php echo __('Duplicate'); ?></a></li>
  <?php } ?>
  <?php if (io_check_acl($resource, 'update') || io_user_is_editor()) { ?>
    <li><a href="<?php echo io_url($resource, 'default', 'move'); ?>" class="btn atom-btn-outline-light"><?php echo __('Move'); ?></a></li>

    <li>
      <div class="dropup">
        <button type="button" class="btn atom-btn-outline-light dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><?php echo __('More'); ?></button>
        <ul class="dropdown-menu mb-2">
          <li><a href="<?php echo io_url($resource, 'informationobject', 'rename'); ?>" class="dropdown-item"><?php echo __('Rename'); ?></a></li>
          
          <?php if (io_check_acl($resource, 'publish')) { ?>
            <li><a href="<?php echo io_url($resource, 'informationobject', 'updatePublicationStatus'); ?>" class="dropdown-item"><?php echo __('Update publication status'); ?></a></li>
          <?php } ?>

          <li><hr class="dropdown-divider"></li>
          <li><a href="<?php echo io_url($resource, 'object', 'editPhysicalObjects'); ?>" class="dropdown-item"><?php echo __('Link physical storage'); ?></a></li>
          <li><hr class="dropdown-divider"></li>

          <?php if ($digitalObjectCount > 0 && $uploadAllowed && $digitalObject) { ?>
            <?php $doId = is_object($digitalObject) ? ($digitalObject->id ?? null) : null; ?>
            <?php if ($doId) { ?>
              <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'digitalobject', 'action' => 'edit', 'id' => $doId]); ?>"><?php echo __('Edit %1%', ['%1%' => mb_strtolower(sfConfig::get('app_ui_label_digitalobject'))]); ?></a></li>
            <?php } ?>
          <?php } elseif ($uploadAllowed) { ?>
            <li><a href="<?php echo io_url($resource, 'object', 'addDigitalObject'); ?>" class="dropdown-item"><?php echo __('Link %1%', ['%1%' => mb_strtolower(sfConfig::get('app_ui_label_digitalobject'))]); ?></a></li>
          <?php } ?>

          <?php if (($repositoryUploadLimit === null || $repositoryUploadLimit != 0) && $uploadAllowed) { ?>
            <li><a href="<?php echo io_url($resource, 'informationobject', 'multiFileUpload'); ?>" class="dropdown-item"><?php echo __('Import digital objects'); ?></a></li>
          <?php } ?>

          <li><hr class="dropdown-divider"></li>
          <li><?php echo link_to(__('Create new rights'), [$resource, 'sf_route' => 'slug/default', 'module' => 'right', 'action' => 'edit'], ['class' => 'dropdown-item']); ?></li>
		  <?php if (checkPluginEnabled('ahgExtendedRightsPlugin') || checkPluginEnabled('ahgGrapPlugin') || checkPluginEnabled('ahgSpectrumPlugin') || checkPluginEnabled('sfMuseumPlugin') || checkPluginEnabled('ahgCcoPlugin') || checkPluginEnabled('ahgConditionPlugin')): ?>
          <!-- Extensions Submenu with Flyout -->
          <li class="dropend" >
            <a class="dropdown-item dropdown-toggle" href="javascript:void(0);">
              <i class="fas fa-puzzle-piece fa-fw me-2"></i><?php echo __('Extensions'); ?>
            </a>
            <ul class="dropdown-menu" style="position: absolute; left: 100%; bottom: 0; top: auto;">
              <?php if (checkPluginEnabled('ahgExtendedRightsPlugin')): ?>
              <li><h6 class="dropdown-header"><?php echo __('Rights & Compliance'); ?></h6></li>
              <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'extendedRights', 'action' => 'edit', 'slug' => $resourceSlug]); ?>"><i class="fas fa-balance-scale fa-fw me-2"></i><?php echo __('Extended Rights'); ?></a></li>
              <li><hr class="dropdown-divider"></li>
              <?php endif; ?>
              <?php if (checkPluginEnabled('ahgGrapPlugin')): ?>
              <li><h6 class="dropdown-header"><?php echo __('GRAP 103 Heritage Assets'); ?></h6></li>
              <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'grap', 'action' => 'index', 'slug' => $resourceSlug]); ?>"><i class="fas fa-eye fa-fw me-2"></i><?php echo __('View GRAP data'); ?></a></li>
              <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'grap', 'action' => 'edit', 'slug' => $resourceSlug]); ?>"><i class="fas fa-edit fa-fw me-2"></i><?php echo __('Edit GRAP data'); ?></a></li>
              <li><hr class="dropdown-divider"></li>
              <?php endif; ?>
			  
			  <?php if (checkPluginEnabled('ahgConditionPlugin')): ?>
              <li><h6 class="dropdown-header"><?php echo __('Condition Assessment'); ?></h6></li>
              <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'ahgCondition', 'action' => 'conditionCheck', 'slug' => $resourceSlug]); ?>"><i class="fas fa-clipboard-check fa-fw me-2"></i><?php echo __('Condition Check'); ?></a></li>
              <li><hr class="dropdown-divider"></li>
              <?php endif; ?>
			  
              <?php if (checkPluginEnabled('ahgSpectrumPlugin') || checkPluginEnabled('sfMuseumPlugin')): ?>
              <li><h6 class="dropdown-header"><?php echo __('SPECTRUM Collections'); ?></h6></li>
              <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'spectrum', 'action' => 'index', 'slug' => $resourceSlug]); ?>"><i class="fas fa-clipboard-list fa-fw me-2"></i><?php echo __('View Spectrum data'); ?></a></li>
              <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'spectrum', 'action' => 'label', 'slug' => $resourceSlug]); ?>"><i class="fas fa-tag fa-fw me-2"></i><?php echo __('Generate label'); ?></a></li>
              <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'spectrum', 'action' => 'workflow', 'slug' => $resourceSlug]); ?>"><i class="fas fa-tasks fa-fw me-2"></i><?php echo __('Workflow Status'); ?></a></li>
              <li><hr class="dropdown-divider"></li>
              <?php endif; ?>
              <?php if (checkPluginEnabled('ahgCcoPlugin')): ?>
              <li><h6 class="dropdown-header"><?php echo __('CCO Provenance'); ?></h6></li>
              <li><a class="dropdown-item" href="<?php echo url_for(['module' => 'cco', 'action' => 'provenance', 'slug' => $resourceSlug]); ?>"><i class="fas fa-history fa-fw me-2"></i><?php echo __('Provenance'); ?></a></li>
              <?php endif; ?>
            </ul>
          </li>
          <?php endif; ?>

          <?php if (sfConfig::get('app_audit_log_enabled', false)) { ?>
            <li><hr class="dropdown-divider"></li>
            <li><a href="<?php echo io_url($resource, 'informationobject', 'modifications'); ?>" class="dropdown-item"><?php echo __('View modification history'); ?></a></li>
          <?php } ?>
        </ul>
      </div>
    </li>
  <?php } ?>
</ul>
<?php } ?>
