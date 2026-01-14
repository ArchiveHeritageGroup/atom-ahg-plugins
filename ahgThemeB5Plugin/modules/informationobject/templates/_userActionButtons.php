<?php
/**
 * User Action Buttons Partial
 * Consistent buttons for Favorites, Feedback, Request to Publish, Cart
 * 
 * Required: $resource (the information object)
 * Optional: $rawResource (unwrapped resource for ID access)
 * 
 * Logic:
 * - Favorites: Show if plugin enabled AND user logged in
 * - Feedback: Show if plugin enabled
 * - Request to Publish: Show if plugin enabled AND has digital object
 * - Cart: Show if plugin enabled AND user logged in AND has digital object
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */

use Illuminate\Database\Capsule\Manager as DB;

// Initialize Laravel if needed
if (\AtomExtensions\Database\DatabaseBootstrap::getCapsule() === null) {
    \AtomExtensions\Database\DatabaseBootstrap::initializeFromAtom();
}

// Get resource ID
$resourceId = isset($rawResource) ? $rawResource->id : (is_object($resource) ? $resource->id : null);
$resourceSlug = is_object($resource) ? $resource->slug : null;

if (!$resourceId) {
    return;
}

// Get user ID
$userId = sfContext::getInstance()->getUser()->getAttribute('user_id');

// Get session ID for guests
$sessionId = session_id();
if (empty($sessionId) && !$userId) {
    @session_start();
    $sessionId = session_id();
}

// Check for digital object
$hasDigitalObject = DB::table('digital_object')
    ->where('object_id', $resourceId)
    ->exists();

// Get favorite/cart status if user logged in
$favoriteId = null;
$cartId = null;
if ($userId) {
    $favoriteId = DB::table('favorites')
        ->where('user_id', $userId)
        ->where('archival_description_id', $resourceId)
        ->value('id');
    
    $cartId = DB::table('cart')
        ->where('user_id', $userId)
        ->where('archival_description_id', $resourceId)
        ->whereNull('completed_at')
        ->value('id');
}

// Check cart for guest users
if (!$cartId && $sessionId) {
    $cartId = DB::table('cart')
        ->where('session_id', $sessionId)
        ->where('archival_description_id', $resourceId)
        ->whereNull('completed_at')
        ->value('id');
}

// Check which plugins are enabled
$favoritesEnabled = class_exists('ahgFavoritesPluginConfiguration');
$feedbackEnabled = class_exists('ahgFeedbackPluginConfiguration');
$requestToPublishEnabled = class_exists('ahgRequestToPublishPluginConfiguration');
$cartEnabled = class_exists('ahgCartPluginConfiguration');
?>

<div class="card mb-4 mt-3">
  <div class="card-body py-2">
    <div class="d-flex flex-wrap gap-2">
      
      <?php // ===== FAVORITES BUTTON ===== ?>
      <?php if ($favoritesEnabled && $userId): ?>
        <?php if ($favoriteId): ?>
          <?php echo link_to(
            '<i class="fas fa-heart-broken me-1"></i>' . __('Remove from Favorites'),
            ['module' => 'ahgFavorites', 'action' => 'remove', 'id' => $favoriteId],
            ['class' => 'btn btn-sm btn-outline-danger']
          ); ?>
        <?php else: ?>
          <?php echo link_to(
            '<i class="fas fa-heart me-1"></i>' . __('Add to Favorites'),
            ['module' => 'ahgFavorites', 'action' => 'add', 'slug' => $resourceSlug],
            ['class' => 'btn btn-sm btn-outline-danger']
          ); ?>
        <?php endif; ?>
      <?php endif; ?>

      <?php // ===== FEEDBACK BUTTON ===== ?>
      <?php if ($feedbackEnabled): ?>
        <?php echo link_to(
          '<i class="fas fa-comment me-1"></i>' . __('Item Feedback'),
          ['module' => 'ahgFeedback', 'action' => 'submit', 'slug' => $resourceSlug],
          ['class' => 'btn btn-sm btn-outline-secondary']
        ); ?>
      <?php endif; ?>

      <?php // ===== REQUEST TO PUBLISH BUTTON (only if has digital object) ===== ?>
      <?php if ($requestToPublishEnabled && $hasDigitalObject): ?>
        <?php echo link_to(
          '<i class="fas fa-paper-plane me-1"></i>' . __('Request to Publish'),
          ['module' => 'requestToPublish', 'action' => 'submit', 'slug' => $resourceSlug],
          ['class' => 'btn btn-sm btn-outline-primary']
        ); ?>
      <?php endif; ?>

      <?php // ===== CART BUTTON (only if has digital object AND user logged in) ===== ?>
      <?php if ($cartEnabled && $hasDigitalObject): ?>
        <?php if ($cartId): ?>
          <?php echo link_to(
            '<i class="fas fa-shopping-cart me-1"></i>' . __('Go to Cart'),
            ['module' => 'ahgCart', 'action' => 'browse'],
            ['class' => 'btn btn-sm btn-outline-success']
          ); ?>
        <?php else: ?>
          <?php echo link_to(
            '<i class="fas fa-cart-plus me-1"></i>' . __('Add to Cart'),
            ['module' => 'ahgCart', 'action' => 'add', 'slug' => $resourceSlug],
            ['class' => 'btn btn-sm btn-outline-success']
          ); ?>
        <?php endif; ?>
      <?php endif; ?>

    </div>
  </div>
</div>
