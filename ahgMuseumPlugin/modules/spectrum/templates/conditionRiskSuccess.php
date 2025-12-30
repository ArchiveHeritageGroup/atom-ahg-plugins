<?php use_helper('Text'); ?>
<h1 class="h3 mb-4"><?php echo __('Risk Assessment'); ?></h1>
<a href="/admin/condition" class="btn btn-secondary mb-3"><?php echo __('Back'); ?></a>
<div class="card">
    <div class="card-header bg-danger text-white"><h5 class="mb-0"><?php echo __('High Risk Items'); ?></h5></div>
    <div class="card-body">
        <?php if (!empty($riskItems)): ?>
            <table class="table">
                <thead><tr><th>Object</th><th>Condition</th><th>Last Check</th></tr></thead>
                <tbody>
                <?php foreach ($riskItems as $item): ?>
                    <tr>
                        <td><a href="/<?php echo $item->slug ?? ''; ?>"><?php echo esc_entities($item->title ?? 'Untitled'); ?></a></td>
                        <td><span class="badge bg-danger"><?php echo ucfirst($item->overall_condition ?? ''); ?></span></td>
                        <td><?php echo esc_entities($item->check_date ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-center py-4"><i class="fas fa-check-circle fa-3x text-success mb-2"></i><p><?php echo __('No high-risk items'); ?></p></div>
        <?php endif; ?>
    </div>
</div>
