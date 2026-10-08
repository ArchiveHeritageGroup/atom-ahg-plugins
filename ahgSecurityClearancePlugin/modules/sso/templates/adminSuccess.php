<?php decorate_with('layout_1col'); ?>
<?php $s = $sf_data->getRaw('settings'); $chk = fn ($k) => 'true' === ($s[$k] ?? '') ? 'checked' : ''; ?>

<?php slot('title'); ?>
  <h1><?php echo __('Single sign-on'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<form method="post" action="<?php echo url_for('@sso_admin'); ?>">

  <section class="card mb-4">
    <h2 class="h5 card-header"><?php echo __('OpenID Connect (Google and others)'); ?></h2>
    <div class="card-body">
      <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" id="oidc_enabled" name="sso[sso_oidc_enabled]" value="true" <?php echo $chk('sso_oidc_enabled'); ?>>
        <label class="form-check-label" for="oidc_enabled"><?php echo __('Offer this sign-in on the login page'); ?></label>
      </div>
      <div class="row g-3">
        <div class="col-md-4"><label class="form-label" for="oidc_label"><?php echo __('Button label'); ?></label>
          <input class="form-control" id="oidc_label" name="sso[sso_oidc_label]" value="<?php echo esc_entities($s['sso_oidc_label']); ?>"></div>
        <div class="col-md-8"><label class="form-label" for="oidc_issuer"><?php echo __('Issuer'); ?></label>
          <input class="form-control" id="oidc_issuer" name="sso[sso_oidc_issuer]" value="<?php echo esc_entities($s['sso_oidc_issuer']); ?>">
          <div class="form-text"><?php echo __('Google: https://accounts.google.com. Microsoft Entra: https://login.microsoftonline.com/<tenant id>/v2.0'); ?></div></div>
        <div class="col-md-6"><label class="form-label" for="oidc_client_id"><?php echo __('Client ID'); ?></label>
          <input class="form-control" id="oidc_client_id" name="sso[sso_oidc_client_id]" value="<?php echo esc_entities($s['sso_oidc_client_id']); ?>" autocomplete="off"></div>
        <div class="col-md-6"><label class="form-label" for="oidc_secret"><?php echo __('Client secret'); ?></label>
          <input class="form-control" type="password" id="oidc_secret" name="sso[sso_oidc_client_secret]" value="" autocomplete="new-password"
            placeholder="<?php echo '' !== $s['sso_oidc_client_secret'] ? __('Stored - leave empty to keep') : ''; ?>"></div>
        <div class="col-md-6"><label class="form-label" for="oidc_domains"><?php echo __('Allowed e-mail domains'); ?></label>
          <input class="form-control" id="oidc_domains" name="sso[sso_oidc_allowed_domains]" value="<?php echo esc_entities($s['sso_oidc_allowed_domains']); ?>">
          <div class="form-text"><?php echo __('Comma separated, e.g. theahg.co.za. Empty allows any verified account, so set it unless new accounts are off.'); ?></div></div>
        <div class="col-md-6"><label class="form-label" for="oidc_groups"><?php echo __('Groups claim'); ?></label>
          <input class="form-control" id="oidc_groups" name="sso[sso_oidc_groups_claim]" value="<?php echo esc_entities($s['sso_oidc_groups_claim']); ?>"></div>
      </div>
      <p class="mt-3 mb-0 small"><?php echo __('Redirect URI to register with the provider:'); ?> <code><?php echo esc_entities($oidcCallback); ?></code></p>
    </div>
  </section>

  <section class="card mb-4">
    <h2 class="h5 card-header"><?php echo __('SAML 2.0 (Shibboleth, SAFIRE)'); ?></h2>
    <div class="card-body">
      <?php if (!$samlLibrary) { ?>
        <div class="alert alert-warning"><?php echo __('The SAML library is not installed on this server, so SAML stays off. Install it in atom-framework: composer require onelogin/php-saml:^4.2'); ?></div>
      <?php } ?>
      <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" id="saml_enabled" name="sso[sso_saml_enabled]" value="true" <?php echo $chk('sso_saml_enabled'); ?>>
        <label class="form-check-label" for="saml_enabled"><?php echo __('Offer this sign-in on the login page'); ?></label>
      </div>
      <div class="row g-3">
        <div class="col-md-4"><label class="form-label" for="saml_label"><?php echo __('Button label'); ?></label>
          <input class="form-control" id="saml_label" name="sso[sso_saml_label]" value="<?php echo esc_entities($s['sso_saml_label']); ?>"></div>
        <div class="col-md-8"><label class="form-label" for="saml_entity"><?php echo __('Identity provider entity ID'); ?></label>
          <input class="form-control" id="saml_entity" name="sso[sso_saml_idp_entity_id]" value="<?php echo esc_entities($s['sso_saml_idp_entity_id']); ?>"></div>
        <div class="col-12"><label class="form-label" for="saml_sso"><?php echo __('Identity provider sign-on URL (HTTP-Redirect)'); ?></label>
          <input class="form-control" id="saml_sso" name="sso[sso_saml_idp_sso_url]" value="<?php echo esc_entities($s['sso_saml_idp_sso_url']); ?>"></div>
        <div class="col-12"><label class="form-label" for="saml_cert"><?php echo __('Identity provider signing certificate (PEM)'); ?></label>
          <textarea class="form-control font-monospace" rows="4" id="saml_cert" name="sso[sso_saml_idp_x509cert]"><?php echo esc_entities($s['sso_saml_idp_x509cert']); ?></textarea></div>
        <div class="col-md-4"><label class="form-label" for="saml_email"><?php echo __('E-mail attribute'); ?></label>
          <input class="form-control" id="saml_email" name="sso[sso_saml_email_attribute]" value="<?php echo esc_entities($s['sso_saml_email_attribute']); ?>"></div>
        <div class="col-md-4"><label class="form-label" for="saml_name"><?php echo __('Name attribute'); ?></label>
          <input class="form-control" id="saml_name" name="sso[sso_saml_name_attribute]" value="<?php echo esc_entities($s['sso_saml_name_attribute']); ?>"></div>
        <div class="col-md-4"><label class="form-label" for="saml_groups"><?php echo __('Groups attribute'); ?></label>
          <input class="form-control" id="saml_groups" name="sso[sso_saml_groups_attribute]" value="<?php echo esc_entities($s['sso_saml_groups_attribute']); ?>"></div>
      </div>
      <p class="mt-3 mb-0 small"><?php echo __('For the identity provider - metadata:'); ?> <code><?php echo esc_entities($samlMetadata); ?></code>,
        <?php echo __('assertion consumer service:'); ?> <code><?php echo esc_entities($samlAcs); ?></code></p>
    </div>
  </section>

  <section class="card mb-4">
    <h2 class="h5 card-header"><?php echo __('Accounts and groups'); ?></h2>
    <div class="card-body">
      <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" id="jit" name="sso[sso_jit_create]" value="true" <?php echo $chk('sso_jit_create'); ?>>
        <label class="form-check-label" for="jit"><?php echo __('Create an account on first sign-in'); ?></label>
        <div class="form-text"><?php echo __('Off: only people who already have an account here (matched by e-mail) may sign in.'); ?></div>
      </div>
      <div class="row g-3">
        <div class="col-md-4"><label class="form-label" for="default_group"><?php echo __('Group for every SSO user'); ?></label>
          <select class="form-select" id="default_group" name="sso[sso_default_group]">
            <?php foreach (['' => __('None (signed-in user only)'), 'contributor' => 'contributor', 'translator' => 'translator', 'editor' => 'editor'] as $v => $l) { ?>
              <option value="<?php echo $v; ?>" <?php echo $v === $s['sso_default_group'] ? 'selected' : ''; ?>><?php echo $l; ?></option>
            <?php } ?>
          </select></div>
        <div class="col-md-8"><label class="form-label" for="group_map"><?php echo __('Group map'); ?></label>
          <textarea class="form-control font-monospace" rows="4" id="group_map" name="sso[sso_group_map]" placeholder="archive-staff = editor&#10;archive-admins = administrator"><?php echo esc_entities($s['sso_group_map']); ?></textarea>
          <div class="form-text"><?php echo __('One line per provider group: provider group = AtoM group (administrator, editor, contributor, translator). Groups are added, never removed.'); ?></div></div>
      </div>
    </div>
  </section>

  <ul class="actions mb-3 nav gap-2">
    <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Save'); ?>"></li>
  </ul>
</form>
<?php end_slot(); ?>
