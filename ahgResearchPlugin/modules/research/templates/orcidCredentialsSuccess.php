<?php decorate_with('layout_1col'); ?>

<?php $cred = $sf_data->getRaw('cred'); ?>

<?php slot('title'); ?>
  <h1><i class="fab fa-orcid text-success me-2"></i><?php echo __('ORCID Credentials'); ?></h1>
<?php end_slot(); ?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('research/orcid'); ?>"><?php echo __('ORCID'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Credentials'); ?></li>
  </ol>
</nav>

<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<div class="alert alert-info small">
  <?php echo __('Register an ORCID API client at'); ?>
  <a href="https://orcid.org/developer-tools" target="_blank" rel="noopener">orcid.org/developer-tools</a>.
  <?php echo __('Set the redirect URI to your /research/orcid/callback URL. Your secret is encrypted at rest and never shown again.'); ?>
</div>

<form method="post" action="<?php echo url_for('research/orcidCredentials'); ?>">
  <div class="card mb-4">
    <div class="card-body">
      <div class="mb-3">
        <label class="form-label required"><?php echo __('Client ID'); ?></label>
        <input type="text" name="client_id" class="form-control" required value="<?php echo esc_entities($cred->client_id ?? ''); ?>"
               placeholder="APP-XXXXXXXXXXXXXXXX">
      </div>
      <div class="mb-3">
        <label class="form-label required"><?php echo __('Client Secret'); ?></label>
        <input type="password" name="client_secret" class="form-control" <?php echo $cred ? '' : 'required'; ?>
               placeholder="<?php echo $cred ? __('leave blank to keep the stored secret') : ''; ?>">
        <?php if ($cred): ?><div class="form-text"><?php echo __('A secret is already stored. Enter a new one only to replace it.'); ?></div><?php endif; ?>
      </div>
      <div class="mb-3">
        <label class="form-label"><?php echo __('API base (optional)'); ?></label>
        <input type="text" name="api_base" class="form-control" value="<?php echo esc_entities($cred->api_base ?? ''); ?>"
               placeholder="https://pub.orcid.org">
        <div class="form-text"><?php echo __('Use https://api.orcid.org for a Member API client; leave blank for the Public API.'); ?></div>
      </div>
    </div>
  </div>
  <div class="d-flex justify-content-between">
    <a href="<?php echo url_for('research/orcid'); ?>" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i><?php echo __('Cancel'); ?></a>
    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i><?php echo __('Save credentials'); ?></button>
  </div>
</form>
