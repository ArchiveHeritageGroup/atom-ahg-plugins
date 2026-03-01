<?php
/**
 * Embeddable panel: External identifier badges for actor view pages.
 * Usage: include_partial('authority/identifierPanel', ['actorId' => $actorId])
 */
$actorId = $actorId ?? 0;
if (!$actorId) return;

require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgAuthorityPlugin/lib/Services/AuthorityIdentifierService.php';
$identifiers = (new \AhgAuthority\Services\AuthorityIdentifierService())->getIdentifiers($actorId);
if (empty($identifiers)) return;
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
    <a href="<?php echo url_for('@ahg_authority_identifiers?actorId=' . $actorId); ?>"
       class="btn btn-sm btn-outline-primary ms-2">
      <i class="fas fa-edit"></i>
    </a>
  </div>
</div>
