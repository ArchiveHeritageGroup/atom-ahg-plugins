<?php decorate_with('layout_1col'); ?>

<?php
  $researcher = $sf_data->getRaw('researcher');
  $link       = $sf_data->getRaw('link');
  $cred       = $sf_data->getRaw('cred');
?>

<?php slot('title'); ?>
  <h1><i class="fab fa-orcid text-success me-2"></i><?php echo __('ORCID Connect & Sync'); ?></h1>
<?php end_slot(); ?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('research/dashboard'); ?>"><?php echo __('Research'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for('research/profile'); ?>"><?php echo __('My Profile'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('ORCID'); ?></li>
  </ol>
</nav>

<?php if ($sf_user->hasFlash('success')): ?>
  <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<div class="card mb-4">
  <div class="card-header bg-light"><h5 class="mb-0"><?php echo __('Connection status'); ?></h5></div>
  <div class="card-body">
    <?php if ($link && !empty($link->orcid_id)): ?>
      <p class="mb-1"><?php echo __('Connected ORCID iD'); ?>:
        <a href="https://orcid.org/<?php echo esc_entities($link->orcid_id); ?>" target="_blank" rel="noopener">
          <i class="fab fa-orcid text-success"></i> <?php echo esc_entities($link->orcid_id); ?>
        </a>
      </p>
      <?php if (!empty($link->last_synced_at)): ?>
        <p class="text-muted small mb-1"><?php echo __('Works last synced'); ?>: <?php echo esc_entities($link->last_synced_at); ?>
          <?php if (!empty($link->last_works_count)): ?>(<?php echo (int) $link->last_works_count; ?> <?php echo __('works'); ?>)<?php endif; ?>
        </p>
      <?php endif; ?>
      <?php if (!empty($link->last_profile_synced_at)): ?>
        <p class="text-muted small mb-2"><?php echo __('Profile last pulled'); ?>: <?php echo esc_entities($link->last_profile_synced_at); ?></p>
      <?php endif; ?>
      <div class="d-flex gap-2 flex-wrap">
        <form method="post" action="<?php echo url_for('research/orcidPullProfile'); ?>">
          <button type="submit" class="btn btn-outline-primary"><i class="fas fa-download me-1"></i><?php echo __('Pull profile from ORCID'); ?></button>
        </form>
        <a href="<?php echo url_for('research/orcidWorks'); ?>" class="btn btn-outline-secondary"><i class="fas fa-sync me-1"></i><?php echo __('Sync works'); ?></a>
        <a href="<?php echo url_for('research/orcidDisconnect'); ?>" class="btn btn-outline-danger" onclick="return confirm('<?php echo __('Disconnect ORCID?'); ?>')"><?php echo __('Disconnect'); ?></a>
      </div>
    <?php else: ?>
      <p class="text-muted"><?php echo __('Your account is not yet linked to an ORCID iD.'); ?></p>
      <?php if ($isConfigured): ?>
        <a href="<?php echo url_for('research/orcidConnect'); ?>" class="btn btn-success"><i class="fab fa-orcid me-1"></i><?php echo __('Connect & Sync with ORCID'); ?></a>
      <?php else: ?>
        <div class="alert alert-info mb-0"><?php echo __('Add your ORCID client credentials below to enable Connect & Sync.'); ?></div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-header bg-light d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><?php echo __('Your ORCID application'); ?></h5>
    <?php if ($hasCredentials): ?><span class="badge bg-success"><?php echo __('Configured'); ?></span><?php endif; ?>
  </div>
  <div class="card-body">
    <p class="text-muted small">
      <?php echo __('Register your own ORCID API client (Member or Public) and store its credentials here — no administrator or server configuration required. Fetch/Pull of public profiles works even without credentials.'); ?>
    </p>
    <?php if ($hasCredentials && $cred): ?>
      <p class="mb-2"><?php echo __('Client ID'); ?>: <code><?php echo esc_entities($cred->client_id); ?></code></p>
    <?php endif; ?>
    <div class="d-flex gap-2">
      <a href="<?php echo url_for('research/orcidCredentials'); ?>" class="btn btn-outline-primary">
        <i class="fas fa-key me-1"></i><?php echo $hasCredentials ? __('Edit credentials') : __('Add credentials'); ?>
      </a>
      <?php if ($hasCredentials): ?>
        <form method="post" action="<?php echo url_for('research/orcidClearCredentials'); ?>" onsubmit="return confirm('<?php echo __('Remove your ORCID credentials?'); ?>')">
          <button type="submit" class="btn btn-outline-danger"><i class="fas fa-trash me-1"></i><?php echo __('Remove'); ?></button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
