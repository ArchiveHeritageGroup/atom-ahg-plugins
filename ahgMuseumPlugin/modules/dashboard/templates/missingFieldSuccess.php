<?php decorate_with('layout_2col.php'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">

    <!-- Filter Panel -->
    <section class="sidebar-section">
        <h4><?php echo __('Filter'); ?></h4>
        <form method="get" action="<?php echo url_for(['module' => 'dashboard', 'action' => 'missingField', 'field' => $fieldName]); ?>">
            <div class="form-group">
                <label><?php echo __('Repository'); ?></label>
                <select name="repository" class="form-control form-control-sm">
                    <option value=""><?php echo __('All repositories'); ?></option>
                    <?php foreach ($repositories as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php echo $repositoryId == $id ? 'selected' : ''; ?>>
                            <?php echo $name; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm btn-block">
                <i class="fa fa-filter"></i> <?php echo __('Apply Filter'); ?>
            </button>
        </form>
    </section>

    <!-- Navigation -->
    <section class="sidebar-section">
        <h4><?php echo __('Navigation'); ?></h4>
        <a href="<?php echo url_for(['module' => 'dashboard', 'action' => 'index', 'repository' => $repositoryId]); ?>" 
           class="btn btn-outline-secondary btn-sm btn-block">
            <i class="fa fa-arrow-left"></i> <?php echo __('Back to Dashboard'); ?>
        </a>
    </section>

    <!-- Field Info -->
    <section class="sidebar-section">
        <h4><?php echo __('Field Information'); ?></h4>
        <dl>
            <dt><?php echo __('Category'); ?></dt>
            <dd><?php echo $categoryLabels[$fieldDefinition['category']]; ?></dd>
            
            <dt><?php echo __('Required'); ?></dt>
            <dd><?php echo $fieldDefinition['required'] ? __('Yes') : __('No'); ?></dd>
            
            <dt><?php echo __('Weight'); ?></dt>
            <dd><?php echo $fieldDefinition['weight']; ?> points</dd>
        </dl>
    </section>

</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<h1>
    <i class="fa fa-exclamation-circle"></i>
    <?php echo __('Records Missing: %1%', ['%1%' => $fieldDefinition['label']]); ?>
</h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<div class="missing-field-page">

    <!-- Summary -->
    <div class="alert alert-info">
        <i class="fa fa-info-circle"></i>
        <?php echo __('Found %1% records missing the "%2%" field.', [
            '%1%' => '<strong>' . count($records) . '</strong>',
            '%2%' => $fieldDefinition['label']
        ]); ?>
        <?php if ($fieldDefinition['required']): ?>
            <span class="badge badge-danger"><?php echo __('Required Field'); ?></span>
        <?php endif; ?>
    </div>

    <!-- Records Table -->
    <?php if (empty($records)): ?>
        <div class="alert alert-success">
            <i class="fa fa-check-circle"></i>
            <?php echo __('All records have this field populated. Great work!'); ?>
        </div>
    <?php else: ?>
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th><?php echo __('Identifier'); ?></th>
                    <th><?php echo __('Title'); ?></th>
                    <th><?php echo __('Actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $record): ?>
                    <tr>
                        <td>
                            <code><?php echo $record['identifier'] ?: '-'; ?></code>
                        </td>
                        <td>
                            <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $record['slug']]); ?>">
                                <?php echo $record['title'] ?: '[Untitled]'; ?>
                            </a>
                        </td>
                        <td>
                            <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'edit', 'slug' => $record['slug']]); ?>" 
                               class="btn btn-sm btn-outline-primary">
                                <i class="fa fa-pencil"></i> <?php echo __('Edit'); ?>
                            </a>
                            <?php if (isset($sf_data->getRaw('modules')['cco'])): ?>
                            <a href="<?php echo url_for(['module' => 'cco', 'action' => 'edit', 'slug' => $record['slug']]); ?>" 
                               class="btn btn-sm btn-outline-secondary">
                                <i class="fa fa-book"></i> <?php echo __('CCO Edit'); ?>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (count($records) >= 200): ?>
            <div class="alert alert-warning">
                <i class="fa fa-warning"></i>
                <?php echo __('Showing first 200 records. There may be more records missing this field.'); ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</div>

<?php end_slot(); ?>
