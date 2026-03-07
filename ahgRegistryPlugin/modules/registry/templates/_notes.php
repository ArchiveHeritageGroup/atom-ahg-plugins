<?php
/**
 * Universal notes partial.
 * Usage: include_partial('registry/notes', [
 *   'entityType' => 'standard',  // standard|vendor|erd|software|institution|group
 *   'entityId'   => 123,
 *   'returnUrl'  => $sf_request->getUri(),
 * ]);
 */

use Illuminate\Database\Capsule\Manager as DB;

$_entityType = $entityType ?? '';
$_entityId = (int) ($entityId ?? 0);
$_returnUrl = $returnUrl ?? '/registry';

$_user = sfContext::getInstance()->getUser();
$_isLoggedIn = $_user && $_user->isAuthenticated();
$_isAdmin = $_isLoggedIn && $_user->hasCredential('administrator');

// Get current registry user id for ownership check
$_regUserId = null;
if ($_isLoggedIn) {
    try {
        $_regUser = DB::table('registry_user')
            ->where('atom_user_id', (int) $_user->getAttribute('user_id'))
            ->first();
        $_regUserId = $_regUser->id ?? null;
    } catch (\Exception $e) {}
}

// Fetch notes
$_notes = [];
try {
    $_notes = DB::table('registry_note')
        ->where('entity_type', $_entityType)
        ->where('entity_id', $_entityId)
        ->where('is_active', 1)
        ->orderBy('is_pinned', 'desc')
        ->orderBy('created_at', 'desc')
        ->get()
        ->all();
} catch (\Exception $e) {}

$_noteCount = count($_notes);
?>

<div class="card mb-4" id="notes">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i><?php echo __('Notes'); ?>
      <?php if ($_noteCount > 0): ?>
        <span class="badge bg-secondary ms-1"><?php echo $_noteCount; ?></span>
      <?php endif; ?>
    </h5>
  </div>

  <?php if (!empty($_notes)): ?>
  <div class="list-group list-group-flush">
    <?php foreach ($_notes as $_note): ?>
    <div class="list-group-item<?php echo $_note->is_pinned ? ' bg-light' : ''; ?>">
      <div class="d-flex justify-content-between align-items-start">
        <div class="flex-grow-1">
          <?php if ($_note->is_pinned): ?>
            <span class="badge bg-warning text-dark me-1"><i class="fas fa-thumbtack"></i></span>
          <?php endif; ?>
          <strong class="small"><?php echo htmlspecialchars($_note->user_name, ENT_QUOTES, 'UTF-8'); ?></strong>
          <span class="text-muted small ms-2"><?php echo date('M j, Y H:i', strtotime($_note->created_at)); ?></span>
        </div>
        <?php if ($_isAdmin || ($_regUserId && (int) $_regUserId === (int) $_note->user_id)): ?>
        <div class="d-flex gap-1">
          <?php if ($_isAdmin): ?>
          <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'notePin', 'id' => $_note->id]); ?>" class="d-inline">
            <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_returnUrl, ENT_QUOTES, 'UTF-8'); ?>#notes">
            <button type="submit" class="btn btn-sm btn-outline-warning py-0 px-1" title="<?php echo $_note->is_pinned ? __('Unpin') : __('Pin'); ?>">
              <i class="fas fa-thumbtack"></i>
            </button>
          </form>
          <?php endif; ?>
          <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'noteDelete', 'id' => $_note->id]); ?>" class="d-inline" onsubmit="return confirm('<?php echo __('Delete this note?'); ?>');">
            <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_returnUrl, ENT_QUOTES, 'UTF-8'); ?>#notes">
            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="<?php echo __('Delete'); ?>">
              <i class="fas fa-trash-alt"></i>
            </button>
          </form>
        </div>
        <?php endif; ?>
      </div>
      <div class="mt-1 small"><?php echo nl2br(htmlspecialchars($_note->content, ENT_QUOTES, 'UTF-8')); ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($_isLoggedIn): ?>
  <div class="card-body border-top">
    <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'noteSave']); ?>">
      <input type="hidden" name="entity_type" value="<?php echo htmlspecialchars($_entityType, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="entity_id" value="<?php echo $_entityId; ?>">
      <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_returnUrl, ENT_QUOTES, 'UTF-8'); ?>#notes">
      <div class="mb-2">
        <textarea class="form-control form-control-sm" name="note_content" rows="2" placeholder="<?php echo __('Add a note...'); ?>" required></textarea>
      </div>
      <button type="submit" class="btn btn-primary btn-sm">
        <i class="fas fa-paper-plane me-1"></i><?php echo __('Add Note'); ?>
      </button>
    </form>
  </div>
  <?php else: ?>
  <div class="card-body border-top">
    <p class="text-muted small mb-0">
      <a href="/registry/login"><?php echo __('Log in'); ?></a> <?php echo __('to add notes.'); ?>
    </p>
  </div>
  <?php endif; ?>
</div>
