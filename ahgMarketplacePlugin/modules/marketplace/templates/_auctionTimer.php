<?php
/**
 * _auctionTimer.php - Countdown timer for auction listings.
 *
 * Variables:
 *   $auction   (object) end_time (UTC datetime string), status
 *   $elementId (string) unique DOM id for this timer instance
 */
$isEnded = ($auction->status === 'ended' || $auction->status === 'closed');
?>
<div class="mkt-timer" id="<?php echo esc_entities($elementId); ?>">
  <?php if ($isEnded): ?>
    <span class="mkt-timer-ended text-danger fw-bold"><?php echo __('ENDED'); ?></span>
  <?php else: ?>
    <div class="d-flex gap-2 justify-content-center">
      <div class="mkt-timer-box text-center">
        <span class="mkt-timer-value" data-unit="days">00</span>
        <span class="mkt-timer-label"><?php echo __('Days'); ?></span>
      </div>
      <div class="mkt-timer-box text-center">
        <span class="mkt-timer-value" data-unit="hours">00</span>
        <span class="mkt-timer-label"><?php echo __('Hrs'); ?></span>
      </div>
      <div class="mkt-timer-box text-center">
        <span class="mkt-timer-value" data-unit="minutes">00</span>
        <span class="mkt-timer-label"><?php echo __('Min'); ?></span>
      </div>
      <div class="mkt-timer-box text-center">
        <span class="mkt-timer-value" data-unit="seconds">00</span>
        <span class="mkt-timer-label"><?php echo __('Sec'); ?></span>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php if (!$isEnded): ?>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function() {
  var elId = <?php echo json_encode($elementId); ?>;
  var endUTC = <?php echo json_encode($auction->end_time); ?>;
  if (typeof window.initAuctionTimer === 'function') {
    window.initAuctionTimer(elId, endUTC);
  } else {
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof window.initAuctionTimer === 'function') {
        window.initAuctionTimer(elId, endUTC);
      }
    });
  }
})();
</script>
<?php endif; ?>
