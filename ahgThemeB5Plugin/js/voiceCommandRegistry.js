/**
 * AHG Voice Command Registry — Navigation + Action Commands
 *
 * Command definitions for voice-driven navigation and context-aware actions.
 * Each command has: patterns, action, mode, description, contextCheck (optional).
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

    // -- Actions: Edit screens --------------------------------------------
    {
      patterns: ['save', 'save record', 'save this'],
      action: function () {
        var v = window.ahgVoice;
        var btn = document.querySelector('form#editForm .btn-success[type="submit"], form.form-edit .btn-success[type="submit"], form#editForm button[type="submit"], form.form-edit button[type="submit"]');
        if (btn && v) {
          v.highlightElement(btn);
          setTimeout(function () { btn.click(); }, 150);
        } else if (v) {
          v.speak('No save button found');
        }
      },
      mode: 'action_edit',
      description: 'Save the current record',
      contextCheck: function () { return !!document.querySelector('form#editForm, form.form-edit'); }
    },
    {
      patterns: ['cancel', 'cancel edit'],
      action: function () {
        var v = window.ahgVoice;
        var btn = document.querySelector('form#editForm a.btn-secondary, form.form-edit a.btn-secondary, a.btn[href*="cancel"], .actions a.btn-secondary');
        if (btn && v) {
          v.highlightElement(btn);
          setTimeout(function () { btn.click(); }, 150);
        } else if (v) {
          v.speak('No cancel button found');
        }
      },
      mode: 'action_edit',
      description: 'Cancel editing',
      contextCheck: function () { return !!document.querySelector('form#editForm, form.form-edit'); }
    },
    {
      patterns: ['delete', 'delete record', 'delete this'],
      action: function () {
        var v = window.ahgVoice;
        var btn = document.querySelector('a.btn-danger[href*="delete"], button.btn-danger, a.btn-danger, input[value="Delete"]');
        if (btn && v) {
          v.highlightElement(btn);
          setTimeout(function () { btn.click(); }, 150);
        } else if (v) {
          v.speak('No delete button found');
        }
      },
      mode: 'action_edit',
      description: 'Delete the current record',
      contextCheck: function () { return !!document.querySelector('form#editForm, form.form-edit'); }
    },

    // -- Actions: View screens --------------------------------------------
    {
      patterns: ['edit', 'edit record', 'edit this'],
      action: function () {
        var v = window.ahgVoice;
        var btn = document.querySelector('a[href*="/edit"], a.btn[href*="edit"], .actions a[href*="edit"]');
        if (btn && v) {
          v.highlightElement(btn);
          setTimeout(function () { btn.click(); }, 150);
        } else if (v) {
          v.speak('No edit button found');
        }
      },
      mode: 'action_view',
      description: 'Edit the current record',
      contextCheck: function () { return !document.querySelector('form#editForm, form.form-edit'); }
    },
    {
      patterns: ['print', 'print page', 'print this'],
      action: function () { window.print(); },
      mode: 'action_view',
      description: 'Print the current page'
    },
    {
      patterns: ['export csv', 'export to csv', 'download csv'],
      action: function () {
        var v = window.ahgVoice;
        var link = document.querySelector('a[href*="csv"], a[href*="CSV"]');
        if (link && v) {
          v.highlightElement(link);
          setTimeout(function () { link.click(); }, 150);
        } else if (v) {
          v.speak('No CSV export link found');
        }
      },
      mode: 'action_view',
      description: 'Export as CSV',
      contextCheck: function () { return !!document.querySelector('a[href*="csv"], a[href*="CSV"]'); }
    },
    {
      patterns: ['export ead', 'export to ead', 'download ead'],
      action: function () {
        var v = window.ahgVoice;
        var link = document.querySelector('a[href*="ead"], a[href*="EAD"]');
        if (link && v) {
          v.highlightElement(link);
          setTimeout(function () { link.click(); }, 150);
        } else if (v) {
          v.speak('No EAD export link found');
        }
      },
      mode: 'action_view',
      description: 'Export as EAD',
      contextCheck: function () { return !!document.querySelector('a[href*="ead"], a[href*="EAD"]'); }
    },

    // -- Actions: Browse screens ------------------------------------------
    {
      patterns: ['first result', 'open first', 'click first'],
      action: function () {
        var v = window.ahgVoice;
        var link = document.querySelector('.search-results article a, .result-count ~ * a, #content .search-result a, td a[href]');
        if (link && v) {
          v.highlightElement(link);
          setTimeout(function () { link.click(); }, 150);
        } else if (v) {
          v.speak('No results found');
        }
      },
      mode: 'action_browse',
      description: 'Open the first result',
      contextCheck: function () { return !!document.querySelector('.result-count, .pager, .pagination, .browse-results'); }
    },
    {
      patterns: ['sort by title', 'sort title'],
      action: function () {
        var v = window.ahgVoice;
        var opt = document.querySelector('select[name="sort"] option[value="alphabetic"], select[name="sort"] option[value="title"]');
        if (opt) {
          opt.parentElement.value = opt.value;
          if (v) v.highlightElement(opt.parentElement);
          var evt = new Event('change', { bubbles: true });
          opt.parentElement.dispatchEvent(evt);
          // Submit the form if there is one
          var form = opt.parentElement.closest('form');
          if (form) setTimeout(function () { form.submit(); }, 200);
        } else if (v) {
          v.speak('No sort option found');
        }
      },
      mode: 'action_browse',
      description: 'Sort results by title',
      contextCheck: function () { return !!document.querySelector('select[name="sort"]'); }
    },
    {
      patterns: ['sort by date', 'sort date'],
      action: function () {
        var v = window.ahgVoice;
        var opt = document.querySelector('select[name="sort"] option[value="date"], select[name="sort"] option[value="startDate"]');
        if (opt) {
          opt.parentElement.value = opt.value;
          if (v) v.highlightElement(opt.parentElement);
          var evt = new Event('change', { bubbles: true });
          opt.parentElement.dispatchEvent(evt);
          var form = opt.parentElement.closest('form');
          if (form) setTimeout(function () { form.submit(); }, 200);
        } else if (v) {
          v.speak('No date sort option found');
        }
      },
      mode: 'action_browse',
      description: 'Sort results by date',
      contextCheck: function () { return !!document.querySelector('select[name="sort"]'); }
    },

    // -- Global actions ---------------------------------------------------
    {
      patterns: ['toggle advanced search', 'advanced search', 'show advanced search'],
      action: function () {
        var v = window.ahgVoice;
        var toggle = document.querySelector('#toggle-advanced-search, a[href*="advanced"], .advanced-search-toggle, button[data-bs-target*="advanced"]');
        if (toggle && v) {
          v.highlightElement(toggle);
          setTimeout(function () { toggle.click(); }, 150);
        } else if (v) {
          v.speak('No advanced search toggle found');
        }
      },
      mode: 'global',
      description: 'Toggle advanced search'
    },
    {
      patterns: ['clear search', 'clear the search'],
      action: function () {
        var input = document.querySelector('#search-form-wrapper input[type="text"], input[name="query"]');
        var form = document.querySelector('#search-form-wrapper form, form[action*="search"]');
        if (input && form) {
          input.value = '';
          form.submit();
        }
      },
      mode: 'global',
      description: 'Clear search and reload'
    },
    {
      patterns: ['scroll down', 'page down'],
      action: function () { window.scrollBy({ top: 500, behavior: 'smooth' }); },
      mode: 'global',
      description: 'Scroll down'
    },
    {
      patterns: ['scroll up', 'page up'],
      action: function () { window.scrollBy({ top: -500, behavior: 'smooth' }); },
      mode: 'global',
      description: 'Scroll up'
    },
    {
      patterns: ['scroll to top', 'go to top', 'top of page'],
      action: function () { window.scrollTo({ top: 0, behavior: 'smooth' }); },
      mode: 'global',
      description: 'Scroll to top'
    },
    {
      patterns: ['scroll to bottom', 'go to bottom', 'bottom of page'],
      action: function () { window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' }); },
      mode: 'global',
      description: 'Scroll to bottom'
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
