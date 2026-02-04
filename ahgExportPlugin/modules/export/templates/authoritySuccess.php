<?php $format = $format ?? 'eac'; ?>
<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
<h1><i class="bi bi-person-badge me-2"></i><?php echo __('Export Authority Records'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'staticpage', 'action' => 'static', 'id' => 'homepage']); ?>"><?php echo __('Home'); ?></a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'export', 'action' => 'index']); ?>"><?php echo __('Export'); ?></a></li>
        <li class="breadcrumb-item active"><?php echo __('Authority Records'); ?></li>
    </ol>
</nav>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-download me-2"></i><?php echo __('Export Settings'); ?></h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('Export Format'); ?></label>
                            <select name="format" class="form-select">
                                <option value="eac" <?php echo $format === 'eac' ? 'selected' : ''; ?>>EAC-CPF (XML)</option>
                                <option value="csv" <?php echo $format === 'csv' ? 'selected' : ''; ?>>CSV</option>
                            </select>
                            <div class="form-text"><?php echo __('EAC-CPF is the standard for authority record exchange.'); ?></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('Entity Type'); ?></label>
                            <select name="entity_type_id" class="form-select">
                                <option value=""><?php echo __('All types'); ?></option>
                                <?php foreach ($entityTypes as $type): ?>
                                <option value="<?php echo $type->id; ?>"><?php echo esc_entities($type->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?php echo __('Limit'); ?></label>
                            <select name="limit" class="form-select">
                                <option value="0"><?php echo __('No limit (all records)'); ?></option>
                                <option value="100">100 <?php echo __('records'); ?></option>
                                <option value="500">500 <?php echo __('records'); ?></option>
                                <option value="1000">1,000 <?php echo __('records'); ?></option>
                                <option value="5000">5,000 <?php echo __('records'); ?></option>
                            </select>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="<?php echo url_for(['module' => 'export', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i><?php echo __('Back'); ?>
                        </a>
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-download me-1"></i><?php echo __('Export'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i><?php echo __('Statistics'); ?></h6>
            </div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt><?php echo __('Total Authority Records'); ?></dt>
                    <dd class="h3 text-primary"><?php echo number_format($actorCount); ?></dd>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-question-circle me-2"></i><?php echo __('About EAC-CPF'); ?></h6>
            </div>
            <div class="card-body small">
                <p><strong>EAC-CPF</strong> (Encoded Archival Context - Corporate Bodies, Persons, and Families) is an XML standard for encoding contextual information about the creators of archival materials.</p>
                <p class="mb-0">Use this format for exchanging authority records with other archival systems or for backup purposes.</p>
            </div>
        </div>
    </div>
</div>
<?php end_slot(); ?>
