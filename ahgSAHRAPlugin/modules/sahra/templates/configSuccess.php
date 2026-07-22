<?php use_helper('Date'); ?>

<div class="container">
  <div class="row">
    <div class="col-lg-8 mx-auto">
      <h1 class="h3 mb-3"><i class="fas fa-cog me-2"></i>SAHRA permit settings</h1>

      <form method="post" action="<?php echo url_for('@sahra_config'); ?>">
        <div class="card border-<?php echo $featureEnabled ? 'success' : 'warning'; ?> mb-3">
          <div class="card-body">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" id="sahra_enabled" name="sahra_enabled" value="1"<?php echo $featureEnabled ? ' checked' : ''; ?>>
              <label class="form-check-label fw-bold" for="sahra_enabled">Enable SAHRA / NHRA heritage permits on this instance</label>
            </div>
            <small class="text-muted d-block mt-1">
              This plugin ships with the software everywhere. Leave it <strong>off</strong> for instances outside South Africa
              (e.g. Australia); the feature and its Research-dashboard entry stay hidden until switched on here.
              Researchers apply from the <strong>Research dashboard</strong> (/research).
            </small>
          </div>
        </div>

        <div class="card">
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label">Default permit validity (months)</label>
              <input type="number" name="permit_validity_months" class="form-control" value="<?php echo htmlspecialchars((string) $config['permit_validity_months']); ?>">
              <small class="text-muted">Used when SAHRA issues a permit without an explicit end date.</small>
            </div>
            <div class="mb-3">
              <label class="form-label">Default issuing authority</label>
              <input type="text" name="default_authority" class="form-control" value="<?php echo htmlspecialchars((string) $config['default_authority']); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Issuing authorities</label>
              <textarea name="authorities" class="form-control" rows="4"><?php echo htmlspecialchars(str_replace('|', "\n", (string) $config['authorities'])); ?></textarea>
              <small class="text-muted">One authority per line (SAHRA and the provincial heritage resources authorities).</small>
            </div>
            <div class="mb-3">
              <label class="form-label">Application reference prefix</label>
              <input type="text" name="application_prefix" class="form-control" value="<?php echo htmlspecialchars((string) $config['application_prefix']); ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Expiry warning (days)</label>
              <input type="number" name="expiry_warning_days" class="form-control" value="<?php echo htmlspecialchars((string) $config['expiry_warning_days']); ?>">
            </div>
          </div>
          <div class="card-footer text-end">
            <a href="<?php echo url_for('@sahra_index'); ?>" class="btn btn-secondary">Back</a>
            <button class="btn btn-primary"><i class="fas fa-save me-1"></i> Save settings</button>
          </div>
        </div>
      </form>

      <!-- SAHRA reviewers -->
      <div class="card mt-4">
        <div class="card-header"><strong><i class="fas fa-stamp me-1"></i> SAHRA reviewers</strong>
          <small class="text-muted d-block">Users who may issue or decline permit applications directly on this instance ("SAHRA approves from their side").</small>
        </div>
        <div class="card-body">
          <?php if (empty($reviewers)): ?>
            <p class="text-muted">No SAHRA reviewers designated yet.</p>
          <?php else: ?>
            <table class="table table-sm align-middle">
              <thead><tr><th>User</th><th>Email</th><th>Acts for</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($reviewers as $r): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($r->username); ?></td>
                    <td><small class="text-muted"><?php echo htmlspecialchars($r->email ?? ''); ?></small></td>
                    <td><?php echo htmlspecialchars($r->authority ?? 'SAHRA'); ?></td>
                    <td class="text-end">
                      <form method="post" action="<?php echo url_for(['module' => 'sahra', 'action' => 'reviewerRemove', 'id' => $r->user_id]); ?>" class="d-inline">
                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this SAHRA reviewer?');"><i class="fas fa-user-minus"></i></button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>

          <form method="post" action="<?php echo url_for('@sahra_reviewer_add'); ?>" class="row g-2 align-items-end mt-1">
            <div class="col-md-6">
              <label class="form-label mb-0 small">Add SAHRA reviewer</label>
              <select name="user_id" class="form-select form-select-sm" required>
                <option value="">-- Select user --</option>
                <?php foreach ($candidateUsers as $u): ?>
                  <option value="<?php echo $u->id; ?>"><?php echo htmlspecialchars($u->username); ?><?php echo $u->email ? ' (' . htmlspecialchars($u->email) . ')' : ''; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label mb-0 small">Acts for</label>
              <select name="authority" class="form-select form-select-sm">
                <?php foreach ($authorities as $a): ?>
                  <option value="<?php echo htmlspecialchars($a); ?>"><?php echo htmlspecialchars($a); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2"><button class="btn btn-sm btn-primary w-100"><i class="fas fa-user-plus me-1"></i> Add</button></div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : ''; ?>>
// textarea uses newlines; convert back to pipe-separated on submit
document.querySelector('form[action$="config"]').addEventListener('submit', function () {
  var ta = this.querySelector('textarea[name="authorities"]');
  if (ta) ta.value = ta.value.split(/\n+/).map(function (s) { return s.trim(); }).filter(Boolean).join('|');
});
</script>
