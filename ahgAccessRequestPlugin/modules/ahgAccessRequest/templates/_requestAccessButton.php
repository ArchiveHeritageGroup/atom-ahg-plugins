<?php
/**
 * Request Access Button - Include in record views
 * Usage: include_partial('ahgAccessRequest/requestAccessButton', ['resource' => $resource, 'type' => 'information_object'])
 */
if (!isset($type)) $type = 'information_object';
if (!isset($resource) || !$resource->id) return;

$userId = sfContext::getInstance()->user->getAttribute('user_id');
if (!$userId) return;

require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';
require_once sfConfig::get('sf_root_dir').'/atom-framework/src/Services/AccessRequestService.php';

$hasAccess = \AtomExtensions\Services\AccessRequestService::hasObjectAccess($userId, $type, $resource->id);
$hasPending = \AtomExtensions\Services\AccessRequestService::hasPendingRequestForObject($userId, $type, $resource->id);

if (!$hasAccess && !$hasPending):
?>
<a href="<?php echo url_for("security/request-object?type={$type}&id={$resource->id}"); ?>" 
   class="btn btn-sm btn-outline-warning" title="Request access to this record">
  <i class="fas fa-lock me-1"></i><?php echo __('Request Access'); ?>
</a>
<?php elseif ($hasPending): ?>
<span class="btn btn-sm btn-outline-secondary disabled" title="Access request pending">
  <i class="fas fa-clock me-1"></i><?php echo __('Request Pending'); ?>
</span>
<?php endif; ?>
