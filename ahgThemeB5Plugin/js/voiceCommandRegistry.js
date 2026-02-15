/**
 * AHG Voice Command Registry — Phase 1: Navigation Commands
 *
 * Command definitions for voice-driven navigation.
 * Each command has: patterns (string[] or RegExp[]), action, mode, description.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
var AHGVoiceRegistry = (function () {
  'use strict';

  var commands = [
    // -- Navigation -------------------------------------------------------
    {
      patterns: ['go home', 'go to home', 'home', 'homepage'],
      action: function () { window.location.href = '/'; },
      mode: 'nav',
      description: 'Go to homepage'
    },
    {
      patterns: [/^(?:go to )?browse(?: records)?$/, 'browse'],
      action: function () { window.location.href = '/informationobject/browse'; },
      mode: 'nav',
      description: 'Browse archival records'
    },
    {
      patterns: ['go to admin', 'admin', 'admin panel'],
      action: function () { window.location.href = '/admin'; },
      mode: 'nav',
      description: 'Go to admin panel'
    },
    {
      patterns: ['go to settings', 'settings', 'ahg settings'],
      action: function () { window.location.href = '/ahgSettings'; },
      mode: 'nav',
      description: 'Go to settings'
    },
    {
      patterns: ['go to clipboard', 'clipboard', 'open clipboard'],
      action: function () { window.location.href = '/clipboard'; },
      mode: 'nav',
      description: 'Go to clipboard'
    },
    {
      patterns: ['go back', 'back', 'previous page'],
      action: function () { window.history.back(); },
      mode: 'nav',
      description: 'Go back'
    },
    {
      patterns: ['next page', 'go to next page'],
      action: function () {
        var link = document.querySelector('.pager .next a, .pagination .page-item:last-child a');
        if (link) { link.click(); }
        else { window.ahgVoice && window.ahgVoice.speak('No next page available'); }
      },
      mode: 'nav',
      description: 'Next page'
    },
    {
      patterns: ['previous page', 'go to previous page', 'prev page'],
      action: function () {
        var link = document.querySelector('.pager .previous a, .pagination .page-item:first-child a');
        if (link) { link.click(); }
        else { window.ahgVoice && window.ahgVoice.speak('No previous page available'); }
      },
      mode: 'nav',
      description: 'Previous page'
    },
    {
      patterns: [/^search (?:for )?(.+)$/],
      action: function (text) {
        var match = text.match(/^search (?:for )?(.+)$/);
        if (!match) return;
        var term = match[1];
        var input = document.querySelector('#search-form-wrapper input[type="text"], #search-form-wrapper input[name="query"], input[name="query"]');
        var form = document.querySelector('#search-form-wrapper form, form[action*="search"]');
        if (input && form) {
          input.value = term;
          form.submit();
        } else {
          window.location.href = '/informationobject/browse?query=' + encodeURIComponent(term);
        }
      },
      mode: 'nav',
      description: 'Search for a term'
    },
    {
      patterns: ['go to donors', 'donors', 'browse donors'],
      action: function () { window.location.href = '/donor/browse'; },
      mode: 'nav',
      description: 'Browse donors'
    },
    {
      patterns: ['go to research', 'research', 'reading room'],
      action: function () { window.location.href = '/research'; },
      mode: 'nav',
      description: 'Go to research/reading room'
    },
    {
      patterns: ['go to authorities', 'authorities', 'browse authorities', 'authority records'],
      action: function () { window.location.href = '/actor/browse'; },
      mode: 'nav',
      description: 'Browse authority records'
    },
    {
      patterns: ['go to places', 'places', 'browse places'],
      action: function () { window.location.href = '/taxonomy/browse?taxonomy=places'; },
      mode: 'nav',
      description: 'Browse places'
    },
    {
      patterns: ['go to subjects', 'subjects', 'browse subjects'],
      action: function () { window.location.href = '/taxonomy/browse?taxonomy=subjects'; },
      mode: 'nav',
      description: 'Browse subjects'
    },
    {
      patterns: ['go to digital objects', 'digital objects', 'browse digital objects'],
      action: function () { window.location.href = '/digitalobject/browse'; },
      mode: 'nav',
      description: 'Browse digital objects'
    },
    {
      patterns: ['go to accessions', 'accessions', 'browse accessions'],
      action: function () { window.location.href = '/accession/browse'; },
      mode: 'nav',
      description: 'Browse accessions'
    },
    {
      patterns: ['go to repositories', 'repositories', 'institutions', 'browse repositories'],
      action: function () { window.location.href = '/repository/browse'; },
      mode: 'nav',
      description: 'Browse repositories'
    },

    // -- Help -------------------------------------------------------------
    {
      patterns: ['help', 'show commands', 'voice help', 'what can you do', 'list commands'],
      action: function () {
        var modal = document.getElementById('voice-help-modal');
        if (modal && typeof bootstrap !== 'undefined') {
          var bsModal = bootstrap.Modal.getOrCreateInstance(modal);
          bsModal.show();
        }
      },
      mode: 'nav',
      description: 'Show voice commands help'
    }
  ];

  return {
    getCommands: function () { return commands; },

    /**
     * Get commands grouped by mode for the help modal.
     */
    getGrouped: function () {
      var groups = {};
      commands.forEach(function (cmd) {
        var g = cmd.mode || 'other';
        if (!groups[g]) groups[g] = [];
        groups[g].push(cmd);
      });
      return groups;
    }
  };
})();
