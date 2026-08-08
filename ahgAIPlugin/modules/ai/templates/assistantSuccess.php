<?php // Rules moved out of style attributes: a CSP nonce covers <style>
      // elements and never an attribute, so under an enforcing policy every one
      // of these was dropped silently.
      $cspNonce = sfConfig::get('csp_nonce', ''); ?>
<style <?php echo $cspNonce ? preg_replace('/^nonce=/', 'nonce="', $cspNonce).'"' : ''; ?>>
  .ai-height-420px-overflow-y-auto-ce2f { height: 420px; overflow-y: auto; }
  .ai-max-width-820px-c788 { max-width: 820px; }
</style>
<?php
$n = sfConfig::get('csp_nonce', '');
$nonce = $n ? ' ' . preg_replace('/^nonce=/', 'nonce="', $n) . '"' : '';
$available = !empty($aiAvailable);
?>
<div class="container py-4 ai-max-width-820px-c788" >
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="h2"><i class="fas fa-comments me-2"></i><?php echo __('Collection assistant'); ?></span>
        <span class="badge bg-<?php echo $available ? 'success' : 'secondary'; ?>"><?php echo $available ? __('AI ready') : __('AI offline'); ?></span>
    </div>
    <p class="text-muted"><?php echo __('Ask a question about the catalogue in plain language. Answers are grounded in published descriptions and cite the records they came from.'); ?></p>

    <?php if (!$available): ?>
    <div class="alert alert-warning"><?php echo __('No language model is configured, so answers will fall back to a list of relevant records. Configure one under AI settings to enable generated answers.'); ?></div>
    <?php endif; ?>

    <div id="chat-log" class="border rounded p-3 mb-3 bg-light ai-height-420px-overflow-y-auto-ce2f" >
        <div class="text-muted small text-center mt-5" id="chat-empty"><?php echo __('Ask something like “What records do we hold about land claims in the 1960s?”'); ?></div>
    </div>

    <form id="chat-form" class="input-group">
        <input type="text" id="chat-input" class="form-control" placeholder="<?php echo __('Ask about the collection…'); ?>" autocomplete="off" maxlength="500" required>
        <button class="btn btn-primary" type="submit" id="chat-send"><i class="fas fa-paper-plane me-1"></i><?php echo __('Send'); ?></button>
    </form>
    <div class="form-text"><?php echo __('Only published descriptions are searched. The assistant can be wrong — always verify against the record.'); ?></div>
</div>

<script src="/plugins/ahgAIPlugin/web/js/collection-chatbot.js"></script>
<script<?php echo $nonce; ?>>
  window.AhgCollectionChatbot && window.AhgCollectionChatbot.init({
    askUrl: '/ai/assistant/ask',
    recordBase: '/index.php/'
  });
</script>
