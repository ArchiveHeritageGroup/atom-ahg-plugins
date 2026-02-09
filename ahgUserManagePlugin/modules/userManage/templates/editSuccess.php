<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0" aria-describedby="heading-label">
      <?php echo $isNew ? __('Add new user') : __('Edit user'); ?>
    </h1>
    <?php if (!$isNew) { ?>
      <span class="small" id="heading-label">
        <?php echo esc_specialchars($userRecord['username']); ?>
      </span>
    <?php } ?>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php if (!empty($errors)) { ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($sf_data->getRaw('errors') as $error) { ?>
          <li><?php echo $error; ?></li>
        <?php } ?>
      </ul>
    </div>
  <?php } ?>

  <form method="post" action="<?php echo $isNew ? url_for('@user_add_override') : url_for('@user_edit_override?slug=' . $userRecord['slug']); ?>" id="editForm" autocomplete="off">

    <?php echo $form->renderHiddenFields(); ?>

    <div class="accordion mb-3">
      <div class="accordion-item">
        <h2 class="accordion-header" id="basicInfo-heading">
          <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#basicInfo-collapse" aria-expanded="true" aria-controls="basicInfo-collapse">
            <?php echo __('Basic info'); ?>
          </button>
        </h2>
        <div id="basicInfo-collapse" class="accordion-collapse collapse show" aria-labelledby="basicInfo-heading">
          <div class="accordion-body">
            <div class="mb-3">
              <label for="acct_name" class="form-label">
                <?php echo __('Username'); ?>
                <span class="form-required" title="<?php echo __('This is a mandatory field.'); ?>">*</span>
              </label>
              <input type="text" class="form-control" id="acct_name" name="acct_name"
                     value="<?php echo esc_specialchars($userRecord['username']); ?>" required autocomplete="off">
            </div>

            <div class="mb-3">
              <label for="acct_email" class="form-label">
                <?php echo __('Email'); ?>
                <span class="form-required" title="<?php echo __('This is a mandatory field.'); ?>">*</span>
              </label>
              <input type="text" class="form-control" id="acct_email" name="acct_email"
                     value="<?php echo esc_specialchars($userRecord['email']); ?>" required autocomplete="off">
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="new_pw" class="form-label">
                  <?php echo __('Password'); ?>
                  <?php if ($isNew) { ?>
                    <span class="form-required" title="<?php echo __('This is a mandatory field.'); ?>">*</span>
                  <?php } ?>
                </label>
                <input type="password" class="form-control" id="new_pw" name="new_pw"
                       <?php echo $isNew ? 'required' : ''; ?> autocomplete="new-password">
                <div class="progress mt-1" style="height: 5px;" id="passwordStrengthBar">
                  <div class="progress-bar" role="progressbar" style="width: 0%;" id="passwordStrengthFill"></div>
                </div>
                <div class="form-text" id="passwordStrengthText">
                  <?php if (!$isNew) { ?>
                    <?php echo __('Leave blank to keep current password.'); ?>
                  <?php } ?>
                </div>
              </div>
              <div class="col-md-6 mb-3">
                <label for="confirm_pw" class="form-label"><?php echo __('Confirm password'); ?></label>
                <input type="password" class="form-control" id="confirm_pw" name="confirm_pw" autocomplete="new-password">
                <div class="form-text" id="passwordMatchText"></div>
              </div>
            </div>

            <div class="mb-3">
              <label for="active" class="form-label"><?php echo __('Active'); ?></label>
              <select class="form-select" id="active" name="active">
                <option value="1" <?php echo $userRecord['active'] ? 'selected' : ''; ?>><?php echo __('Active'); ?></option>
                <option value="0" <?php echo !$userRecord['active'] ? 'selected' : ''; ?>><?php echo __('Inactive'); ?></option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="accordion-item">
        <h2 class="accordion-header" id="accessControl-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#accessControl-collapse" aria-expanded="false" aria-controls="accessControl-collapse">
            <?php echo __('Access control'); ?>
          </button>
        </h2>
        <div id="accessControl-collapse" class="accordion-collapse collapse" aria-labelledby="accessControl-heading">
          <div class="accordion-body">
            <?php
              $rawGroups = $sf_data->getRaw('assignableGroups');
              $rawRecord = $sf_data->getRaw('userRecord');
              $currentGroupIds = array_map(function ($g) { return (int) $g->id; }, $rawRecord['groups']);
            ?>
            <div class="mb-3">
              <label for="groups" class="form-label"><?php echo __('User groups'); ?></label>
              <select class="form-select" id="groups" name="groups[]" multiple size="<?php echo min(max(count($rawGroups), 3), 8); ?>">
                <?php foreach ($rawGroups as $group) { ?>
                  <option value="<?php echo $group->id; ?>"
                          <?php echo in_array((int) $group->id, $currentGroupIds) ? 'selected' : ''; ?>>
                    <?php echo esc_specialchars($group->name ?? __('Group %1%', ['%1%' => $group->id])); ?>
                  </option>
                <?php } ?>
              </select>
              <div class="form-text"><?php echo __('Hold Ctrl/Cmd to select multiple groups.'); ?></div>
            </div>
            <?php if (empty($rawGroups)) { ?>
              <p class="text-muted mb-0"><?php echo __('No assignable groups found.'); ?></p>
            <?php } ?>
          </div>
        </div>
      </div>

      <div class="accordion-item">
        <h2 class="accordion-header" id="translate-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#translate-collapse" aria-expanded="false" aria-controls="translate-collapse">
            <?php echo __('Allowed languages for translation'); ?>
          </button>
        </h2>
        <div id="translate-collapse" class="accordion-collapse collapse" aria-labelledby="translate-heading">
          <div class="accordion-body">
            <?php
              $rawLanguages = $sf_data->getRaw('availableLanguages');
              $rawTranslate = $sf_data->getRaw('translateLanguages');
            ?>
            <?php if (!empty($rawLanguages)) { ?>
              <div class="mb-3">
                <label for="translate" class="form-label"><?php echo __('Translate'); ?></label>
                <select class="form-select" id="translate" name="translate[]" multiple size="<?php echo min(max(count($rawLanguages), 3), 8); ?>">
                  <?php foreach ($rawLanguages as $lang) { ?>
                    <option value="<?php echo esc_specialchars($lang); ?>"
                            <?php echo in_array($lang, $rawTranslate) ? 'selected' : ''; ?>>
                      <?php echo format_language($lang); ?>
                    </option>
                  <?php } ?>
                </select>
                <div class="form-text"><?php echo __('Hold Ctrl/Cmd to select multiple languages. User will be allowed to translate content into selected languages.'); ?></div>
              </div>
            <?php } else { ?>
              <p class="text-muted mb-0"><?php echo __('No languages configured. Add languages in Admin > Settings > I18n.'); ?></p>
            <?php } ?>
          </div>
        </div>
      </div>

      <?php if (!$isNew) { ?>
        <div class="accordion-item">
          <h2 class="accordion-header" id="apiKeys-heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#apiKeys-collapse" aria-expanded="false" aria-controls="apiKeys-collapse">
              <?php echo __('API keys'); ?>
            </button>
          </h2>
          <div id="apiKeys-collapse" class="accordion-collapse collapse" aria-labelledby="apiKeys-heading">
            <div class="accordion-body">
              <div class="mb-3">
                <label for="restApiKey" class="form-label">
                  <?php echo __('REST API access key'); ?>
                  <?php if (isset($restApiKey) && $restApiKey) { ?>
                    <code class="ms-2"><?php echo esc_specialchars($restApiKey); ?></code>
                  <?php } ?>
                </label>
                <select class="form-select" id="restApiKey" name="restApiKey">
                  <option value=""><?php echo __('-- Select action --'); ?></option>
                  <option value="generate"><?php echo __('(Re)generate API key'); ?></option>
                  <option value="delete"><?php echo __('Delete API key'); ?></option>
                </select>
                <?php if (!isset($restApiKey) || !$restApiKey) { ?>
                  <div class="form-text"><?php echo __('Not generated yet.'); ?></div>
                <?php } ?>
              </div>

              <div class="mb-3">
                <label for="oaiApiKey" class="form-label">
                  <?php echo __('OAI-PMH API access key'); ?>
                  <?php if (isset($oaiApiKey) && $oaiApiKey) { ?>
                    <code class="ms-2"><?php echo esc_specialchars($oaiApiKey); ?></code>
                  <?php } ?>
                </label>
                <select class="form-select" id="oaiApiKey" name="oaiApiKey">
                  <option value=""><?php echo __('-- Select action --'); ?></option>
                  <option value="generate"><?php echo __('(Re)generate API key'); ?></option>
                  <option value="delete"><?php echo __('Delete API key'); ?></option>
                </select>
                <?php if (!isset($oaiApiKey) || !$oaiApiKey) { ?>
                  <div class="form-text"><?php echo __('Not generated yet.'); ?></div>
                <?php } ?>
              </div>
            </div>
          </div>
        </div>
      <?php } ?>

    </div>

    <ul class="actions mb-3 nav gap-2">
      <?php if (!$isNew) { ?>
        <li><?php echo link_to(__('Cancel'), '@user_view_override?slug=' . $userRecord['slug'], ['class' => 'btn atom-btn-outline-light', 'role' => 'button']); ?></li>
        <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Save'); ?>"></li>
      <?php } else { ?>
        <li><?php echo link_to(__('Cancel'), '@user_list_override', ['class' => 'btn atom-btn-outline-light', 'role' => 'button']); ?></li>
        <li><input class="btn atom-btn-outline-success" type="submit" value="<?php echo __('Create'); ?>"></li>
      <?php } ?>
    </ul>

  </form>

  <script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
  (function() {
    // Override browser autofill with correct server values
    var correctUsername = <?php echo json_encode($sf_data->getRaw('userRecord')['username'] ?? ''); ?>;
    var correctEmail = <?php echo json_encode($sf_data->getRaw('userRecord')['email'] ?? ''); ?>;
    function resetAutofill() {
      var u = document.getElementById('acct_name');
      var e = document.getElementById('acct_email');
      if (u && u.value !== correctUsername) u.value = correctUsername;
      if (e && e.value !== correctEmail) e.value = correctEmail;
      var p = document.getElementById('new_pw');
      var cp = document.getElementById('confirm_pw');
      if (p) p.value = '';
      if (cp) cp.value = '';
    }
    resetAutofill();
    setTimeout(resetAutofill, 100);
    setTimeout(resetAutofill, 500);
    setTimeout(resetAutofill, 1000);

    var pw = document.getElementById('new_pw');
    var cpw = document.getElementById('confirm_pw');
    var strengthFill = document.getElementById('passwordStrengthFill');
    var strengthText = document.getElementById('passwordStrengthText');
    var matchText = document.getElementById('passwordMatchText');
    var isNew = <?php echo $isNew ? 'true' : 'false'; ?>;
    var defaultHint = isNew ? '' : <?php echo json_encode(__('Leave blank to keep current password.')); ?>;

    function checkStrength(val) {
      var score = 0;
      if (val.length === 0) {
        strengthFill.style.width = '0%';
        strengthFill.className = 'progress-bar';
        strengthText.textContent = defaultHint;
        return;
      }
      if (val.length >= 8) score++;
      if (val.length >= 12) score++;
      if (/[a-z]/.test(val) && /[A-Z]/.test(val)) score++;
      if (/\d/.test(val)) score++;
      if (/[^a-zA-Z0-9]/.test(val)) score++;

      var pct, cls, label;
      if (score <= 1) {
        pct = '20%'; cls = 'progress-bar bg-danger'; label = <?php echo json_encode(__('Weak')); ?>;
      } else if (score === 2) {
        pct = '40%'; cls = 'progress-bar bg-warning'; label = <?php echo json_encode(__('Fair')); ?>;
      } else if (score === 3) {
        pct = '60%'; cls = 'progress-bar bg-info'; label = <?php echo json_encode(__('Good')); ?>;
      } else if (score === 4) {
        pct = '80%'; cls = 'progress-bar bg-primary'; label = <?php echo json_encode(__('Strong')); ?>;
      } else {
        pct = '100%'; cls = 'progress-bar bg-success'; label = <?php echo json_encode(__('Very strong')); ?>;
      }
      strengthFill.style.width = pct;
      strengthFill.className = cls;
      strengthText.textContent = label;
    }

    function checkMatch() {
      if (cpw.value.length === 0 && pw.value.length === 0) {
        matchText.textContent = '';
        matchText.className = 'form-text';
        cpw.classList.remove('is-valid', 'is-invalid');
        return;
      }
      if (cpw.value.length === 0) {
        matchText.textContent = '';
        matchText.className = 'form-text';
        cpw.classList.remove('is-valid', 'is-invalid');
        return;
      }
      if (pw.value === cpw.value) {
        matchText.textContent = <?php echo json_encode(__('Passwords match.')); ?>;
        matchText.className = 'form-text text-success';
        cpw.classList.add('is-valid');
        cpw.classList.remove('is-invalid');
      } else {
        matchText.textContent = <?php echo json_encode(__('Passwords do not match.')); ?>;
        matchText.className = 'form-text text-danger';
        cpw.classList.add('is-invalid');
        cpw.classList.remove('is-valid');
      }
    }

    pw.addEventListener('input', function() { checkStrength(this.value); checkMatch(); });
    cpw.addEventListener('input', checkMatch);
  })();
  </script>

<?php end_slot(); ?>
