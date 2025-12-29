<?php decorate_with('layout_1col'); ?>
<?php slot('title'); ?><h1><?php echo __('Search Templates'); ?></h1><?php end_slot(); ?>
<?php slot('content'); ?>
<?php foreach ($templatesByCategory as $category => $templates): ?>
<h5><?php echo esc_entities($category); ?></h5>
<table class="table table-striped mb-4">
<thead><tr><th>Name</th><th>Entity</th><th>Featured</th><th>Active</th></tr></thead>
<tbody>
<?php foreach ($templates as $t): ?>
<tr>
<td><i class="fa <?php echo $t->icon; ?> text-<?php echo $t->color; ?>"></i> <?php echo esc_entities($t->name); ?></td>
<td><?php echo $t->entity_type; ?></td>
<td><?php echo $t->is_featured ? 'Yes' : '-'; ?></td>
<td><?php echo $t->is_active ? 'Yes' : 'No'; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endforeach; ?>
<?php end_slot(); ?>
