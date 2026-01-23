<?php
/**
 * Privacy & PII Section - Reusable partial for GLAM/DAM templates
 * Includes: Scan for PII, PII Review Queue, Visual Redaction Editor
 */
if (!isset($resource) || !$sf_user->isAuthenticated()) return;
if (!in_array('ahgPrivacyPlugin', sfProjectConfiguration::getActive()->getPlugins())) return;

$resourceId = is_object($resource) ? $resource->id : (int)$resource;
$hasDigitalObject = \Illuminate\Database\Capsule\Manager::table('digital_object')->where('object_id', $resourceId)->exists();
?>
<section class="card mb-3">
  <div class="card-header bg-warning text-dark">
    <h4 class="mb-0"><i class="fas fa-shield-alt me-2"></i><?php echo __('Privacy & PII'); ?></h4>
  </div>
  <div class="card-body">
    <ul class="list-unstyled mb-0">
      <li class="mb-2">
        <a href="#" onclick="scanForPii(<?php echo $resourceId; ?>); return false;" class="text-decoration-none">
          <i class="fas fa-search me-2 text-warning"></i><?php echo __('Scan for PII'); ?>
        </a>
      </li>
      <li class="mb-2">
        <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'piiReview']); ?>" class="text-decoration-none">
          <i class="fas fa-clipboard-check me-2 text-info"></i><?php echo __('PII Review Queue'); ?>
        </a>
      </li>
      <?php if ($hasDigitalObject && $sf_user->hasCredential(['editor', 'administrator'], false)): ?>
      <li>
        <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'visualRedactionEditor', 'id' => $resourceId]); ?>" class="text-decoration-none">
          <i class="fas fa-mask me-2 text-dark"></i><?php echo __('Visual Redaction Editor'); ?>
        </a>
      </li>
      <?php endif; ?>
    </ul>
  </div>
</section>

<!-- PII Scan Modal -->
<div class="modal fade" id="piiModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title"><i class="fas fa-shield-alt me-2"></i><?php echo __('PII Detection Results'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="piiModalBody"></div>
      <div class="modal-footer">
        <span id="piiRiskScore" class="me-auto"></span>
        <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'piiReview']); ?>" class="btn btn-warning" id="piiReviewBtn" style="display:none;"><?php echo __('Review PII'); ?></a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Close'); ?></button>
      </div>
    </div>
  </div>
</div>

<script>
function scanForPii(objectId) {
  var modal = new bootstrap.Modal(document.getElementById('piiModal'));
  modal.show();
  document.getElementById('piiModalBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-warning"></div><p class="mt-2">Scanning for PII...</p></div>';
  document.getElementById('piiReviewBtn').style.display = 'none';
  document.getElementById('piiRiskScore').textContent = '';

  fetch('/index.php/privacyAdmin/piiScanAjax?id=' + objectId, { method: 'GET' })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.error) {
        document.getElementById('piiModalBody').innerHTML = '<div class="alert alert-danger">' + data.error + '</div>';
        return;
      }
      var html = '';
      var total = 0;
      var categories = data.findings || {};
      for (var cat in categories) {
        var items = categories[cat];
        if (items && items.length > 0) {
          total += items.length;
          html += '<div class="mb-3"><strong class="text-capitalize">' + cat.replace(/_/g, ' ') + '</strong> (' + items.length + ')<div class="mt-1">';
          for (var i = 0; i < items.length; i++) {
            var risk = items[i].risk_level || 'low';
            var riskBadge = risk === 'high' ? 'bg-danger' : (risk === 'medium' ? 'bg-warning text-dark' : 'bg-secondary');
            html += '<span class="badge ' + riskBadge + ' me-1 mb-1">' + items[i].value + '</span>';
          }
          html += '</div></div>';
        }
      }
      if (total === 0) {
        html = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>No PII detected in this record</div>';
      } else {
        document.getElementById('piiReviewBtn').style.display = 'inline-block';
        if (data.high_risk > 0) {
          html = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><strong>' + data.high_risk + ' high-risk PII entities detected!</strong></div>' + html;
        }
      }
      document.getElementById('piiModalBody').innerHTML = html;
      document.getElementById('piiRiskScore').innerHTML = '<span class="badge ' + (data.risk_score > 50 ? 'bg-danger' : (data.risk_score > 20 ? 'bg-warning text-dark' : 'bg-success')) + '">Risk Score: ' + data.risk_score + '/100</span> | Found ' + total + ' entities';
    })
    .catch(function(e) {
      document.getElementById('piiModalBody').innerHTML = '<div class="alert alert-danger">Error scanning: ' + e.message + '</div>';
    });
}
</script>
