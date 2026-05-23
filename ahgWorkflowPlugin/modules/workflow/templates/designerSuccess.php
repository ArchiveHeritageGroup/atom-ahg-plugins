<?php /* heratio#143 Phase 3 — drag-drop workflow designer (PSIS Symfony port) */ ?>
<?php
  $n = sfConfig::get('csp_nonce', '');
  $nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : '';

  $stepsArr = [];
  foreach ($steps as $s) {
    $stepsArr[] = [
      'id'          => (int) $s->id,
      'name'        => (string) $s->name,
      'step_order'  => (int) $s->step_order,
      'step_type'   => (string) ($s->step_type ?? 'review'),
      'is_optional' => (bool) ($s->is_optional ?? false),
    ];
  }
  $edgesArr = [];
  foreach ($edges as $e) {
    $edgesArr[] = [
      'from_step_id'   => (int) $e->from_step_id,
      'to_step_id'     => (int) $e->to_step_id,
      'condition_expr' => $e->condition_expr,
    ];
  }
  $csrfToken = method_exists($sf_user, 'getCSRFToken') ? $sf_user->getCSRFToken() : '';
?>

<link rel="stylesheet" href="/plugins/ahgWorkflowPlugin/js/drawflow.min.css">
<style <?php echo $nonceAttr; ?>>
  #wf-designer-canvas { position: relative; width: 100%; height: 600px; background: #fafbfc; border: 1px solid #dee2e6; border-radius: 6px; }
  .drawflow .drawflow-node { background: #ffffff; border: 2px solid #0d6efd; border-radius: 10px; min-width: 180px; padding: 8px 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); }
  .drawflow .drawflow-node.optional { border-color: #6f42c1; }
  .drawflow .drawflow-node .step-name { font-size: 13px; font-weight: 600; color: #212529; }
  .drawflow .drawflow-node .step-type { font-size: 11px; color: #6c757d; }
  .drawflow .connection .main-path { stroke: #adb5bd; stroke-width: 2; }
  .wf-designer-toolbar { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-bottom: 12px; }
  .wf-designer-help { font-size: 0.85rem; color: #6c757d; }
  .wf-designer-flash { display: none; margin-top: 12px; }
</style>

<div class="container-fluid px-4 py-3 workflow designer">
  <div class="d-flex flex-wrap align-items-baseline mb-3 gap-2">
    <h1 class="mb-0 flex-grow-1"><i class="fas fa-project-diagram me-2"></i><?php echo __('Designer:') ?> <?php echo esc_entities($workflow->name) ?></h1>
    <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'diagram', 'id' => $workflow->id]) ?>" class="btn btn-outline-info"><i class="fas fa-eye me-1"></i><?php echo __('View diagram') ?></a>
    <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'editWorkflow', 'id' => $workflow->id]) ?>" class="btn btn-outline-secondary"><i class="fas fa-edit me-1"></i><?php echo __('Edit workflow & steps') ?></a>
  </div>

  <div class="wf-designer-toolbar">
    <button type="button" id="wf-designer-save" class="btn btn-success"><i class="fas fa-save me-1"></i><?php echo __('Save edges') ?></button>
    <button type="button" id="wf-designer-clear" class="btn btn-outline-danger"><i class="fas fa-eraser me-1"></i><?php echo __('Clear all edges') ?></button>
    <button type="button" id="wf-designer-relayout" class="btn btn-outline-secondary"><i class="fas fa-magic me-1"></i><?php echo __('Auto-layout') ?></button>
    <span class="wf-designer-help ms-auto">
      <i class="fas fa-info-circle me-1"></i><?php echo __('Drag from a node\'s right handle to another node\'s left handle to connect. Right-click an edge to delete it.') ?>
    </span>
  </div>

  <div id="wf-designer-canvas" data-workflow-id="<?php echo (int) $workflow->id ?>"
       data-save-url="<?php echo url_for(['module' => 'workflow', 'action' => 'designerSave', 'id' => $workflow->id]) ?>"
       data-csrf="<?php echo esc_entities($csrfToken) ?>"></div>

  <div id="wf-designer-flash" class="alert wf-designer-flash" role="status" aria-live="polite"></div>

  <?php if (count($stepsArr) === 0): ?>
    <div class="alert alert-warning mt-3">
      <?php echo __('This workflow has no steps yet.') ?>
      <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'editWorkflow', 'id' => $workflow->id]) ?>"><?php echo __('Add steps first') ?></a>
      <?php echo __('— then come back here to connect them.') ?>
    </div>
  <?php endif ?>
</div>

<script id="wf-designer-data" type="application/json" <?php echo $nonceAttr; ?>><?php echo json_encode(['steps' => $stepsArr, 'edges' => $edgesArr], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

<script src="/plugins/ahgWorkflowPlugin/js/drawflow.min.js" <?php echo $nonceAttr; ?>></script>
<script src="/plugins/ahgWorkflowPlugin/js/workflow-designer.js" <?php echo $nonceAttr; ?>></script>
