<?php decorate_with('layout_1col.php') ?>

<?php slot('title') ?>
  <h1><i class="fas fa-comments me-2" aria-hidden="true"></i><?php echo __('Ask the Archive') ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php if ($error) { ?>
  <div class="alert alert-warning"><?php echo esc_entities($error) ?></div>
<?php } ?>

<p class="text-muted"><?php echo __('Last 30 days. Logged questions are masked for personal details and deleted after the retention period; no IP addresses are kept.') ?></p>

<div class="row g-3 mb-4">
  <?php foreach ([
      'questions' => __('Questions'),
      'unanswered' => __('No matching records'),
      'fallback' => __('AI unavailable'),
      'up' => __('Helpful'),
      'down' => __('Not helpful'),
  ] as $key => $label) { ?>
    <div class="col-6 col-md">
      <div class="card text-center"><div class="card-body">
        <div class="fs-3 fw-bold"><?php echo (int) ($totals[$key] ?? 0) ?></div>
        <div class="small text-muted"><?php echo $label ?></div>
      </div></div>
    </div>
  <?php } ?>
</div>

<h2 class="h5"><?php echo __('Questions to review') ?></h2>
<p class="small text-muted"><?php echo __('Answers that found no records or were marked not helpful. These show what visitors look for that the catalogue does not yet describe well.') ?></p>
<?php if ($review) { ?>
<div class="table-responsive mb-4">
  <table class="table table-bordered table-sm">
    <thead><tr><th><?php echo __('When') ?></th><th><?php echo __('Question') ?></th><th><?php echo __('Answer') ?></th><th><?php echo __('Why') ?></th></tr></thead>
    <tbody>
    <?php foreach ($review as $row) { ?>
      <tr>
        <td class="text-nowrap"><?php echo esc_entities($row->created_at) ?></td>
        <td><?php echo esc_entities($row->question) ?></td>
        <td><?php echo esc_entities(mb_substr($row->answer, 0, 300)) ?></td>
        <td><?php echo -1 === (int) $row->rating ? __('Not helpful') : __('No records') ?></td>
      </tr>
    <?php } ?>
    </tbody>
  </table>
</div>
<?php } else { ?>
  <p><?php echo __('Nothing to review.') ?></p>
<?php } ?>

<h2 class="h5"><?php echo __('Questions per day') ?></h2>
<?php if ($perDay) { ?>
<table class="table table-bordered table-sm mb-4">
  <thead><tr><th><?php echo __('Day') ?></th><th><?php echo __('Questions') ?></th></tr></thead>
  <tbody>
  <?php foreach ($perDay as $d) { ?>
    <tr><td><?php echo esc_entities($d->day) ?></td><td><?php echo (int) $d->questions ?></td></tr>
  <?php } ?>
  </tbody>
</table>
<?php } else { ?>
  <p><?php echo __('No questions yet.') ?></p>
<?php } ?>

<h2 class="h5"><?php echo __('Settings') ?></h2>
<p class="small text-muted"><?php echo __('Stored in ahg_settings (group "chatbot").') ?></p>
<table class="table table-bordered table-sm">
  <thead><tr><th><?php echo __('Setting') ?></th><th><?php echo __('Value') ?></th></tr></thead>
  <tbody>
  <?php foreach ($settings as $key => $value) { ?>
    <tr><td><code><?php echo esc_entities($key) ?></code></td><td><?php echo esc_entities('' === (string) $value ? __('(default)') : $value) ?></td></tr>
  <?php } ?>
  </tbody>
</table>
<?php end_slot() ?>
