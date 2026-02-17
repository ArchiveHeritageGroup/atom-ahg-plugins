<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php slot('title') ?>
<h1><i class="fas fa-users text-primary me-2"></i><?php echo __('Researchers'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<div class="card">
  <div class="card-header">
    <div class="row align-items-center">
      <div class="col-md-6">
        <form method="get" class="d-flex gap-2">
          <select name="status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
            <option value=""><?php echo __('All Status'); ?></option>
            <option value="pending" <?php echo $currentStatus === 'pending' ? 'selected' : ''; ?>><?php echo __('Pending'); ?></option>
            <option value="approved" <?php echo $currentStatus === 'approved' ? 'selected' : ''; ?>><?php echo __('Approved'); ?></option>
            <option value="suspended" <?php echo $currentStatus === 'suspended' ? 'selected' : ''; ?>><?php echo __('Suspended'); ?></option>
            <option value="rejected" <?php echo $currentStatus === 'rejected' ? 'selected' : ''; ?>><?php echo __('Rejected'); ?></option>
          </select>
          <input type="text" name="q" class="form-control form-control-sm" placeholder="<?php echo __('Search...'); ?>" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>" style="width: 200px;">
          <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
        </form>
      </div>
      <div class="col-md-6 text-end">
        <span class="text-muted"><?php echo count($researchers); ?> <?php echo __('researchers'); ?></span>
      </div>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th><?php echo __('Name'); ?></th>
          <th><?php echo __('Email'); ?></th>
          <th><?php echo __('Institution'); ?></th>
          <th><?php echo __('Status'); ?></th>
          <th><?php echo __('Registered'); ?></th>
          <th width="100"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($researchers)): ?>
        <tr>
          <td colspan="6" class="text-center text-muted py-4"><?php echo __('No researchers found'); ?></td>
        </tr>
        <?php else: ?>
          <?php foreach ($researchers as $r): ?>
          <tr>
            <td>
              <strong><?php echo htmlspecialchars($r->title ? $r->title . ' ' : ''); ?><?php echo htmlspecialchars($r->first_name . ' ' . $r->last_name); ?></strong>
            </td>
            <td><a href="mailto:<?php echo htmlspecialchars($r->email); ?>"><?php echo htmlspecialchars($r->email); ?></a></td>
            <td><?php echo htmlspecialchars($r->institution ?? '-'); ?></td>
            <td>
              <span class="badge bg-<?php 
                echo $r->status === 'approved' ? 'success' : 
                    ($r->status === 'pending' ? 'warning' : 
                    ($r->status === 'rejected' ? 'danger' : 'secondary')); 
              ?>"><?php echo ucfirst($r->status); ?></span>
            </td>
            <td><small><?php echo date('Y-m-d', strtotime($r->created_at)); ?></small></td>
            <td>
              <a href="<?php echo url_for(['module' => 'research', 'action' => 'viewResearcher', 'id' => $r->id]); ?>" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-eye"></i>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php end_slot() ?>
