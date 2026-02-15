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

        <?php
        // Helper to render a command list section
        $renderSection = function ($commands, $ctxClass = '') {
            echo '<ul class="voice-cmd-list">';
            foreach ($commands as $phrase => $desc) {
                echo '<li>';
                echo '<span class="voice-cmd-phrase">"' . esc_specialchars($phrase) . '"</span>';
                echo '<span class="voice-cmd-desc">' . esc_specialchars(__($desc));
                if ($ctxClass) {
                    $labels = ['edit' => 'edit pages', 'view' => 'view pages', 'browse' => 'browse pages', 'dictation' => 'dictation mode', 'ai' => 'requires AI'];
                    echo ' <span class="voice-ctx-badge voice-ctx-' . $ctxClass . '">' . ($labels[$ctxClass] ?? $ctxClass) . '</span>';
                }
                echo '</span></li>';
            }
            echo '</ul>';
        };
        ?>

        <h6><i class="bi bi-signpost-2 me-1"></i><?php echo __('Navigation'); ?></h6>
        <?php $renderSection([
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
        ]); ?>

        <h6><i class="bi bi-pencil me-1"></i><?php echo __('Actions (Edit)'); ?></h6>
        <?php $renderSection([
            'save / save record' => 'Save the current record',
            'cancel' => 'Cancel editing',
            'delete / delete record' => 'Delete the current record',
        ], 'edit'); ?>

        <h6><i class="bi bi-eye me-1"></i><?php echo __('Actions (View)'); ?></h6>
        <?php $renderSection([
            'edit / edit record' => 'Edit the current record',
            'print' => 'Print the current page',
            'export csv' => 'Export as CSV',
            'export ead' => 'Export as EAD',
        ], 'view'); ?>

        <h6><i class="bi bi-list-ul me-1"></i><?php echo __('Actions (Browse)'); ?></h6>
        <?php $renderSection([
            'first result / open first' => 'Open the first result',
            'sort by title' => 'Sort results by title',
            'sort by date' => 'Sort results by date',
        ], 'browse'); ?>

        <h6><i class="bi bi-globe me-1"></i><?php echo __('Global'); ?></h6>
        <?php $renderSection([
            'toggle advanced search' => 'Toggle advanced search',
            'clear search' => 'Clear search and reload',
            'scroll down' => 'Scroll down',
            'scroll up' => 'Scroll up',
            'scroll to top' => 'Scroll to top',
            'scroll to bottom' => 'Scroll to bottom',
        ], 'global'); ?>

        <h6><i class="bi bi-image me-1"></i><?php echo __('Image & Reading'); ?></h6>
        <?php $renderSection([
            'read image info' => 'Read image metadata aloud',
            'read title' => 'Read the record title',
            'read description' => 'Read the description aloud',
            'stop reading / shut up' => 'Stop speech output',
            'slower / faster' => 'Adjust speech rate',
        ], 'view'); ?>

        <h6><i class="bi bi-robot me-1"></i><?php echo __('AI Image Description'); ?></h6>
        <?php $renderSection([
            'describe image / AI describe' => 'Generate AI description of image',
            'save to description' => 'Save AI description to record',
            'save to alt text' => 'Save as image alt text',
            'save to both' => 'Save to description and alt text',
            'discard' => 'Discard AI description',
        ], 'ai'); ?>

        <h6><i class="bi bi-keyboard me-1"></i><?php echo __('Dictation'); ?></h6>
        <?php $renderSection([
            'start dictating' => 'Start dictating into focused field',
            'stop dictating' => 'Stop dictation, return to command mode',
        ], 'dictation'); ?>
        <p class="text-muted small mt-1 mb-2"><?php echo __('While dictating, say these for punctuation:'); ?></p>
        <?php $renderSection([
            'period / full stop' => 'Insert .',
            'comma' => 'Insert ,',
            'question mark' => 'Insert ?',
            'exclamation mark' => 'Insert !',
            'colon / semicolon' => 'Insert : or ;',
            'new line' => 'Insert line break',
            'new paragraph' => 'Insert double line break',
            'open quote / close quote' => 'Insert curly quotes',
            'open bracket / close bracket' => 'Insert ( or )',
            'dash / hyphen' => 'Insert dash or hyphen',
            'undo last' => 'Remove last dictated segment',
            'clear field' => 'Clear the entire field (with confirmation)',
            'read back' => 'Read the field content aloud',
        ], 'dictation'); ?>

        <h6><i class="bi bi-question-circle me-1"></i><?php echo __('Help'); ?></h6>
        <?php $renderSection([
            'help / show commands' => 'Show this help modal',
        ]); ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal"><?php echo __('Close'); ?></button>
      </div>
    </div>
  </div>
</div>
