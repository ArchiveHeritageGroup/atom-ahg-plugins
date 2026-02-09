<?php decorate_with('layout_1col'); ?>
<?php use_helper('Text'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-exchange-alt me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column">
      <h1 class="mb-0"><?php echo render_title($title); ?></h1>
    </div>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>

  <?php if (isset($form->confirm)) { ?>
    <div class="alert alert-warning mb-3">
      <h5 class="alert-heading">
        <?php echo __('This will permanently modify %1% records.', ['%1%' => isset($pager) ? $pager->getNbResults() : 0]); ?>
      </h5>
      <p class="mb-0 fw-bold"><?php echo __('This action cannot be undone!'); ?></p>
    </div>
  <?php } ?>

  <form method="post" action="<?php echo url_for(['module' => 'ahgSearch', 'action' => 'globalReplace']); ?>">

    <div class="card mb-3">
      <div class="card-body">
        <h5 class="card-title"><?php echo __('Search and replace'); ?></h5>

        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label"><?php echo __('Field'); ?></label>
            <?php echo $form['column']->render(['class' => 'form-select']); ?>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-5">
            <label class="form-label"><?php echo __('Find'); ?></label>
            <?php echo $form['pattern']->render(['class' => 'form-control']); ?>
          </div>
          <div class="col-md-5">
            <label class="form-label"><?php echo __('Replace with'); ?></label>
            <?php echo $form['replacement']->render(['class' => 'form-control']); ?>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-4">
            <div class="form-check">
              <?php echo $form['caseSensitive']->render(['class' => 'form-check-input']); ?>
              <label class="form-check-label"><?php echo __('Case sensitive'); ?></label>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-check">
              <?php echo $form['allowRegex']->render(['class' => 'form-check-input']); ?>
              <label class="form-check-label"><?php echo __('Use regular expressions'); ?></label>
            </div>
          </div>
        </div>

        <?php if (isset($form->confirm)) { ?>
          <?php echo $form['confirm']->render(); ?>
        <?php } ?>
      </div>
    </div>

    <div class="d-flex gap-2 mb-3">
      <button type="submit" class="btn btn-primary">
        <?php if (isset($form->confirm)) { ?>
          <i class="fas fa-check me-1"></i>
          <?php echo __('Confirm replacement'); ?>
        <?php } else { ?>
          <i class="fas fa-search me-1"></i>
          <?php echo __('Search and replace'); ?>
        <?php } ?>
      </button>
      <a href="<?php echo url_for(['module' => 'ahgSearch', 'action' => 'globalReplace']); ?>" class="btn btn-outline-secondary">
        <?php echo __('Cancel'); ?>
      </a>
    </div>

  </form>

  <?php if (isset($error)) { ?>
    <div class="alert alert-danger">
      <?php echo $error; ?>
    </div>
  <?php } ?>

  <?php if (isset($pager) && $pager->getNbResults() > 0) { ?>
    <div class="card">
      <div class="card-header">
        <?php echo __('Matching records (%1%)', ['%1%' => $pager->getNbResults()]); ?>
      </div>
      <div class="table-responsive">
        <table class="table table-bordered mb-0">
          <thead>
            <tr>
              <th><?php echo __('Title'); ?></th>
              <th><?php echo __('Identifier'); ?></th>
              <th><?php echo __('Level'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pager->getResults() as $hit) { ?>
              <?php $data = $hit['_source'] ?? []; ?>
              <tr>
                <td>
                  <?php echo link_to(
                      render_title(get_search_i18n($data, 'title', ['allowEmpty' => false])),
                      ['slug' => $data['slug'] ?? '', 'module' => 'informationobject']
                  ); ?>
                </td>
                <td><?php echo isset($data['identifier']) ? render_value_inline($data['identifier']) : ''; ?></td>
                <td>
                  <?php if (isset($data['levelOfDescriptionId'])) { ?>
                    <?php $level = QubitTerm::getById($data['levelOfDescriptionId']); ?>
                    <?php echo $level ? render_value_inline($level) : ''; ?>
                  <?php } ?>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php } ?>

<?php end_slot(); ?>
