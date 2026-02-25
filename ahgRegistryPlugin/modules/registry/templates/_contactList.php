<?php
  $showEdit = !empty($canEdit);
  $eType = $entityType ?? '';
?>
<?php if (!empty($contacts)): ?>
<div class="table-responsive">
  <table class="table table-sm table-hover mb-0">
    <thead class="table-light">
      <tr>
        <th><?php echo __('Name'); ?></th>
        <th><?php echo __('Title'); ?></th>
        <th><?php echo __('Email'); ?></th>
        <th><?php echo __('Phone'); ?></th>
        <th><?php echo __('Role'); ?></th>
        <?php if ($showEdit): ?><th class="text-end"><?php echo __('Actions'); ?></th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($contacts as $c): ?>
        <tr>
          <td>
            <?php
              $fullName = trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? ''));
              echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
            ?>
            <?php if (!empty($c->is_primary)): ?>
              <i class="fas fa-star text-warning ms-1" title="<?php echo __('Primary Contact'); ?>"></i>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!empty($c->job_title)): ?>
              <small><?php echo htmlspecialchars($c->job_title, ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!empty($c->email)): ?>
              <a href="mailto:<?php echo htmlspecialchars($c->email, ENT_QUOTES, 'UTF-8'); ?>" class="small">
                <?php echo htmlspecialchars($c->email, ENT_QUOTES, 'UTF-8'); ?>
              </a>
            <?php endif; ?>
          </td>
          <td>
            <small>
              <?php if (!empty($c->phone)): ?>
                <?php echo htmlspecialchars($c->phone, ENT_QUOTES, 'UTF-8'); ?>
              <?php endif; ?>
              <?php if (!empty($c->mobile)): ?>
                <?php if (!empty($c->phone)): ?><br><?php endif; ?>
                <?php echo htmlspecialchars($c->mobile, ENT_QUOTES, 'UTF-8'); ?>
              <?php endif; ?>
            </small>
          </td>
          <td>
            <?php
              $roles = [];
              if (!empty($c->contact_roles)) {
                $rawContactRoles = sfOutputEscaper::unescape($c->contact_roles);
                $roles = is_string($rawContactRoles) ? json_decode($rawContactRoles, true) : (array) $rawContactRoles;
              } elseif (!empty($c->role)) {
                $roles = [$c->role];
              }
              if (is_array($roles)):
                foreach ($roles as $role):
            ?>
              <span class="badge bg-light text-dark border me-1"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $role)), ENT_QUOTES, 'UTF-8'); ?></span>
            <?php
                endforeach;
              endif;
            ?>
          </td>
          <?php if ($showEdit): ?>
          <td class="text-end text-nowrap">
            <?php
              $editAction = $eType === 'vendor' ? 'myVendorContactEdit' : 'myInstitutionContactEdit';
            ?>
            <a href="<?php echo url_for(['module' => 'registry', 'action' => $editAction, 'id' => (int) $c->id]); ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('Edit'); ?>">
              <i class="fas fa-edit"></i>
            </a>
          </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php else: ?>
<p class="text-muted small mb-0"><?php echo __('No contacts listed.'); ?></p>
<?php endif; ?>
