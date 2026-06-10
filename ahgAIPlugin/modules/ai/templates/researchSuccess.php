<?php
/* Researcher Copilot (#149) — persistent research workspace. */
$n = sfConfig::get('csp_nonce', '');
$nonce = $n ? ' '.preg_replace('/^nonce=/', 'nonce="', $n).'"' : '';
$available = !empty($aiAvailable);
?>
<div class="container-fluid py-4 research-copilot">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <span class="h2 mb-0"><i class="fas fa-user-graduate me-2"></i><?php echo __('Researcher Copilot') ?></span>
    <span class="badge bg-<?php echo $available ? 'success' : 'secondary' ?>"><?php echo $available ? __('AI ready') : __('AI offline') ?></span>
  </div>
  <p class="text-muted"><?php echo __('Ask questions about the catalogue in plain language. Your sessions are saved here so you can pick up research where you left off, and export a transcript. Answers are grounded in published descriptions and cite their records.') ?></p>
  <?php if (!$available): ?><div class="alert alert-warning"><?php echo __('No language model is configured — answers fall back to a list of relevant records.') ?></div><?php endif ?>

  <div class="row g-3">
    <div class="col-lg-3">
      <div class="d-grid mb-2">
        <button class="btn btn-primary" id="rc-new"><i class="fas fa-plus me-1"></i><?php echo __('New session') ?></button>
      </div>
      <div class="list-group" id="rc-sessions">
        <div class="text-muted small p-2"><?php echo __('Loading sessions…') ?></div>
      </div>
    </div>

    <div class="col-lg-9">
      <div class="d-flex align-items-center mb-2 gap-2">
        <strong id="rc-title" class="flex-grow-1 text-truncate"><?php echo __('New research session') ?></strong>
        <a id="rc-export" class="btn btn-outline-secondary btn-sm disabled" href="#"><i class="fas fa-download me-1"></i><?php echo __('Export') ?></a>
        <button id="rc-delete" class="btn btn-outline-danger btn-sm" disabled><i class="fas fa-trash"></i></button>
      </div>
      <div id="rc-log" class="border rounded p-3 mb-3 bg-light" style="height: 460px; overflow-y: auto;">
        <div class="text-muted small text-center mt-5" id="rc-empty"><?php echo __('Start a new question, or open a saved session on the left.') ?></div>
      </div>
      <form id="rc-form" class="input-group">
        <input type="text" id="rc-input" class="form-control" placeholder="<?php echo __('Ask about the collection…') ?>" autocomplete="off" maxlength="500" required>
        <button class="btn btn-primary" type="submit" id="rc-send"><i class="fas fa-paper-plane me-1"></i><?php echo __('Send') ?></button>
      </form>
      <div class="form-text"><?php echo __('Only published descriptions are searched. The assistant can be wrong — always verify against the record.') ?></div>
    </div>
  </div>
</div>

<script src="/plugins/ahgAIPlugin/web/js/research-copilot.js"></script>
<script<?php echo $nonce ?>>
  window.AhgResearchCopilot && window.AhgResearchCopilot.init({
    askUrl: '/ai/research/ask',
    sessionsUrl: '/ai/research/sessions',
    sessionUrl: '/ai/research/session/',
    recordBase: '/index.php/'
  });
</script>
