<?php $title = __('SharePoint user mapping'); ?>
<h1><?php echo $title; ?></h1>

<?php if ($mapping !== null): ?>
    <dl class="row">
        <dt class="col-sm-3">AAD oid</dt><dd class="col-sm-9 small text-muted"><?php echo htmlspecialchars($mapping->aad_object_id); ?></dd>
        <dt class="col-sm-3">UPN</dt><dd class="col-sm-9"><?php echo htmlspecialchars($mapping->aad_upn ?? '—'); ?></dd>
        <dt class="col-sm-3">Email</dt><dd class="col-sm-9"><?php echo htmlspecialchars($mapping->aad_email ?? '—'); ?></dd>
        <dt class="col-sm-3"><?php echo __('AtoM user id'); ?></dt><dd class="col-sm-9"><?php echo (int) $mapping->atom_user_id; ?></dd>
        <dt class="col-sm-3"><?php echo __('Created by'); ?></dt><dd class="col-sm-9"><?php echo htmlspecialchars($mapping->created_by); ?></dd>
        <dt class="col-sm-3"><?php echo __('Last seen'); ?></dt><dd class="col-sm-9"><?php echo htmlspecialchars($mapping->last_seen_at ?? '—'); ?></dd>
    </dl>

    <form method="post" onsubmit="return confirm('<?php echo __('Delete this mapping? The AtoM user account is NOT deleted.'); ?>');">
        <input type="hidden" name="form_action" value="delete">
        <button type="submit" class="btn btn-danger"><?php echo __('Remove mapping'); ?></button>
        <a class="btn btn-link" href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'userMappings']); ?>"><?php echo __('Back'); ?></a>
    </form>
<?php else: ?>
    <div class="alert alert-warning"><?php echo __('Mapping not found.'); ?></div>
<?php endif; ?>
