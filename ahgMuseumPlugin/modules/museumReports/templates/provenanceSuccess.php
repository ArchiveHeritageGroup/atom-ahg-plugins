<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <h4><?php echo __('Summary'); ?></h4>
    <p><strong><?php echo $summary['withProvenance']; ?></strong> <?php echo __('with provenance'); ?></p>
    <p><strong><?php echo $summary['withLegalStatus']; ?></strong> <?php echo __('with legal status'); ?></p>
    <hr>
    <a href="<?php echo url_for(['module' => 'museumReports', 'action' => 'index']); ?>" class="btn btn-outline-primary btn-sm w-100"><i class="fas fa-arrow-left me-2"></i><?php echo __('Back'); ?></a>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1><i class="fas fa-history"></i> <?php echo __('Provenance Report'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="table-responsive">
    <table class="table table-striped">
        <thead class="table-dark">
            <tr>
                <th><?php echo __('Object'); ?></th>
                <th><?php echo __('Provenance'); ?></th>
                <th><?php echo __('Legal Status'); ?></th>
                <th><?php echo __('Rights Holder'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $r): ?>
            <tr>
                <td><strong><?php echo esc_specialchars($r->title ?? 'Untitled'); ?></strong></td>
                <td><small><?php echo esc_specialchars(substr($r->provenance ?? $r->provenance_text ?? '-', 0, 150)); ?></small></td>
                <td><span class="badge bg-info"><?php echo esc_specialchars($r->legal_status ?? '-'); ?></span></td>
                <td><small><?php echo esc_specialchars($r->rights_holder ?? '-'); ?></small></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php end_slot(); ?>
