<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('training/trainingSidebar', ['active' => $sidebarActive ?? 'training']) ?>
<?php end_slot() ?>
<?php slot('title') ?>
<div class="d-flex justify-content-between align-items-center">
  <h1><i class="fas fa-graduation-cap text-primary me-2"></i><?php echo __('Training & LMS'); ?></h1>
  <a href="<?php echo url_for(['module' => 'training', 'action' => 'builder']); ?>" class="btn btn-primary">
    <i class="fas fa-plus me-1"></i><?php echo __('New course'); ?>
  </a>
</div>
<?php end_slot() ?>

<?php slot('content') ?>
<?php $courses = isset($courses) && is_array($courses) ? $courses : []; ?>
<?php $myEnrolments = isset($myEnrolments) && is_array($myEnrolments) ? $myEnrolments : []; ?>

<?php if ($myEnrolments): ?>
  <div class="card mb-4">
    <div class="card-header"><i class="fas fa-user-graduate me-2"></i><?php echo __('My enrolments'); ?></div>
    <ul class="list-group list-group-flush">
      <?php foreach ($myEnrolments as $e): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <span>
            <a href="<?php echo url_for(['module' => 'training', 'action' => 'learn', 'id' => $e['id']]); ?>">
              <?php echo htmlspecialchars((string) ($e['course_title'] ?? ('Course #' . $e['course_id']))); ?>
            </a>
          </span>
          <span>
            <span class="badge bg-secondary me-2"><?php echo htmlspecialchars((string) $e['status']); ?></span>
            <?php if ($e['status'] === 'completed'): ?>
              <a class="btn btn-sm btn-outline-success" href="<?php echo url_for(['module' => 'training', 'action' => 'certificate', 'id' => $e['id']]); ?>">
                <i class="fas fa-certificate me-1"></i><?php echo __('Certificate'); ?>
              </a>
            <?php endif; ?>
          </span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><i class="fas fa-book me-2"></i><?php echo __('Courses'); ?></div>
  <?php if (!$courses): ?>
    <div class="card-body text-muted"><?php echo __('No courses yet.'); ?></div>
  <?php else: ?>
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th><?php echo __('Title'); ?></th>
          <th><?php echo __('Audience'); ?></th>
          <th><?php echo __('Language'); ?></th>
          <th class="text-center"><?php echo __('Pass mark'); ?></th>
          <th class="text-center"><?php echo __('Status'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($courses as $c): ?>
          <tr>
            <td>
              <a href="<?php echo url_for(['module' => 'training', 'action' => 'show', 'id' => $c['id']]); ?>">
                <?php echo htmlspecialchars((string) $c['title']); ?>
              </a>
            </td>
            <td><?php echo htmlspecialchars((string) ($c['audience'] ?? '')); ?></td>
            <td><?php echo htmlspecialchars((string) ($c['language'] ?? '')); ?></td>
            <td class="text-center"><?php echo (int) $c['pass_mark']; ?>%</td>
            <td class="text-center"><span class="badge bg-secondary"><?php echo htmlspecialchars((string) $c['status']); ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<?php end_slot() ?>
