<?php $title = __('SharePoint user mappings'); ?>
<h1><?php echo $title; ?></h1>

<p class="lead text-muted"><?php echo __('AAD object id → AtoM user id. Auto-created on first manual push (toggle in Admin > AHG Settings > SharePoint).'); ?></p>

<?php if (count($mappings) === 0): ?>
    <div class="alert alert-info"><?php echo __('No mappings yet. The first manual push from a new SharePoint user will create one (if auto-create is enabled).'); ?></div>
<?php else: ?>
<table class="table table-striped">
    <thead>
        <tr><th>ID</th><th>UPN</th><th>Email</th><th><?php echo __('AtoM user'); ?></th><th><?php echo __('Created by'); ?></th><th><?php echo __('Last seen'); ?></th><th></th></tr>
    </thead>
    <tbody>
    <?php foreach ($mappings as $m): ?>
        <tr>
            <td><?php echo (int) $m->id; ?></td>
            <td class="small"><?php echo htmlspecialchars($m->aad_upn ?? '—'); ?></td>
            <td class="small"><?php echo htmlspecialchars($m->aad_email ?? '—'); ?></td>
            <td><?php echo (int) $m->atom_user_id; ?></td>
            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($m->created_by); ?></span></td>
            <td><?php echo htmlspecialchars($m->last_seen_at ?? '—'); ?></td>
            <td><a class="btn btn-sm btn-outline-secondary" href="<?php echo url_for(['module' => 'sharepoint', 'action' => 'userMappingEdit', 'id' => $m->id]); ?>"><?php echo __('Edit'); ?></a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
