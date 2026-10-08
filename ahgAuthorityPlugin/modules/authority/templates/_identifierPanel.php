<?php
/**
 * Embeddable panel: External identifier badges for actor view pages.
 * Usage: include_partial('authority/identifierPanel', ['actorId' => $actorId])
 */
$actorId = $actorId ?? 0;
if (!$actorId) return;

require_once dirname(__FILE__, 4).'/lib/Services/AuthorityIdentifierService.php';
$identifiers = (new \AhgAuthority\Services\AuthorityIdentifierService())->getIdentifiers($actorId);
$canEdit = $sf_user->hasCredential(['administrator', 'editor'], false);
// Visitors see the panel only when there is something to show; staff always
// see it, or they could never reach the page that adds the first identifier.
if (empty($identifiers) && !$canEdit) return;
?>

<div class="card mb-3 authority-identifier-panel">
  <div class="card-header py-2">
    <i class="fas fa-link me-1"></i><?php echo __('External Identifiers'); ?>
  </div>
  <div class="card-body py-2">
    <?php foreach ($identifiers as $ident): ?>
      <a href="<?php echo htmlspecialchars($ident->uri ?? '#'); ?>"
         target="_blank" rel="noopener"
         class="badge bg-secondary text-decoration-none me-1 mb-1"
         title="<?php echo htmlspecialchars($ident->identifier_value); ?>">
        <?php echo strtoupper($ident->identifier_type); ?>
        <?php if ($ident->is_verified): ?>
          <i class="fas fa-check-circle ms-1"></i>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
    <?php if ($canEdit): ?>
      <?php if (empty($identifiers)): ?>
        <span class="text-muted small me-2"><?php echo __('None yet.'); ?></span>
        <a href="<?php echo url_for('@ahg_authority_identifiers?actorId=' . $actorId); ?>" class="btn btn-sm btn-outline-primary">
          <i class="fas fa-plus me-1" aria-hidden="true"></i><?php echo __('Add Wikidata, VIAF and other identifiers'); ?>
        </a>
      <?php else: ?>
        <a href="<?php echo url_for('@ahg_authority_identifiers?actorId=' . $actorId); ?>"
           class="btn btn-sm btn-outline-primary ms-2" title="<?php echo __('Edit identifiers'); ?>">
          <i class="fas fa-edit" aria-hidden="true"></i><span class="visually-hidden"><?php echo __('Edit identifiers'); ?></span>
        </a>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
