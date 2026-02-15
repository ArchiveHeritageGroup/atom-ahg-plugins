<?php
/**
 * Voice Commands UI partial — included in layout_end.
 *
 * Renders: navbar mic button, floating mic button, listening indicator,
 * toast container, and help modal. Only rendered if not a bot/crawler.
 */

// Skip for bots/crawlers
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (preg_match('/bot|crawl|spider|slurp|bingpreview|facebookexternalhit/i', $ua)) {
    return;
}
?>

<!-- Voice: Listening indicator bar -->
<div id="voice-indicator" class="voice-indicator voice-ui" style="display:none"></div>

<!-- Voice: Floating mic button (bottom-right) -->
<button id="voice-floating-btn"
  class="voice-floating-btn voice-ui"
  style="display:none"
  type="button"
  aria-label="<?php echo __('Toggle voice commands'); ?>"
  title="<?php echo __('Voice commands'); ?>">
  <i class="bi bi-mic"></i>
</button>

<!-- Voice: Toast container -->
<div id="voice-toast-container" class="voice-toast-container voice-ui" style="display:none" aria-live="polite"></div>

<!-- Voice: Help modal -->
<div class="modal fade voice-ui" id="voice-help-modal" tabindex="-1" aria-labelledby="voice-help-label" aria-hidden="true" style="display:none">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="voice-help-label"><i class="bi bi-mic me-2"></i><?php echo __('Voice Commands'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo __('Close'); ?>"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small"><?php echo __('Click the mic button and speak a command. Commands are not case-sensitive.'); ?></p>

        <h6><i class="bi bi-signpost-2 me-1"></i><?php echo __('Navigation'); ?></h6>
        <ul class="voice-cmd-list">
          <?php
          $navCommands = [
              'go home' => 'Go to homepage',
              'browse / go to browse' => 'Browse archival records',
              'go to admin' => 'Go to admin panel',
              'go to settings' => 'Go to settings',
              'go to clipboard' => 'Go to clipboard',
              'go back' => 'Go back',
              'next page' => 'Next page',
              'previous page' => 'Previous page',
              'search for [term]' => 'Search for a term',
              'go to donors' => 'Browse donors',
              'go to research / reading room' => 'Go to research / reading room',
              'go to authorities' => 'Browse authority records',
              'go to places' => 'Browse places',
              'go to subjects' => 'Browse subjects',
              'go to digital objects' => 'Browse digital objects',
              'go to accessions' => 'Browse accessions',
              'go to repositories' => 'Browse repositories',
          ];
          foreach ($navCommands as $phrase => $desc) {
              echo '<li>';
              echo '<span class="voice-cmd-phrase">"' . esc_specialchars($phrase) . '"</span>';
              echo '<span class="voice-cmd-desc">' . esc_specialchars(__($desc)) . '</span>';
              echo '</li>';
          }
          ?>
        </ul>

        <h6><i class="bi bi-question-circle me-1"></i><?php echo __('Help'); ?></h6>
        <ul class="voice-cmd-list">
          <li>
            <span class="voice-cmd-phrase">"help" / "show commands"</span>
            <span class="voice-cmd-desc"><?php echo __('Show this help modal'); ?></span>
          </li>
        </ul>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal"><?php echo __('Close'); ?></button>
      </div>
    </div>
  </div>
</div>
