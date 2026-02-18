/**
 * ahgHelpPlugin — Help Chatbot Widget
 *
 * A floating chat widget that provides help from the documentation.
 * Two modes:
 *   1. Search-based (default): Matches questions to articles via FULLTEXT search
 *   2. AI-powered: Uses LLM to generate contextual answers from documentation
 *
 * AI mode activates automatically when ahgAIPlugin is installed with an LLM config.
 */
(function () {
  'use strict';

  var HelpChatbot = {
    panel: null,
    button: null,
    isOpen: false,
    mode: 'search',
    aiAvailable: false,
    history: [],
    initialized: false,

    init: function () {
      // Don't double-init
      if (this.initialized) return;
      this.initialized = true;

      this.createButton();
      this.createPanel();
      this.bindEvents();
    },

    createButton: function () {
      // Chat button — positioned above the contextual help button
      this.button = document.createElement('button');
      this.button.className = 'help-chat-btn';
      this.button.setAttribute('title', 'Help Chat');
      this.button.setAttribute('aria-label', 'Open help chatbot');
      this.button.innerHTML = '<i class="bi bi-chat-dots"></i>';

      var self = this;
      this.button.addEventListener('click', function () {
        self.toggle();
      });

      document.body.appendChild(this.button);
    },

    createPanel: function () {
      var html = ''
        + '<div class="help-chat-panel d-none" id="helpChatPanel">'
        + '  <div class="help-chat-header">'
        + '    <div class="d-flex align-items-center">'
        + '      <i class="bi bi-robot me-2"></i>'
        + '      <strong>Help Assistant</strong>'
        + '      <span class="help-chat-mode-badge ms-2" id="helpChatModeBadge">Search</span>'
        + '    </div>'
        + '    <div class="d-flex align-items-center">'
        + '      <button class="btn btn-sm btn-link text-white p-0 me-2" id="helpChatModeToggle" title="Toggle AI mode" style="display:none;">'
        + '        <i class="bi bi-stars"></i>'
        + '      </button>'
        + '      <button class="btn btn-sm btn-link text-white p-0" id="helpChatClose" title="Close">'
        + '        <i class="bi bi-x-lg"></i>'
        + '      </button>'
        + '    </div>'
        + '  </div>'
        + '  <div class="help-chat-messages" id="helpChatMessages">'
        + '    <div class="help-chat-message assistant">'
        + '      <div class="help-chat-bubble">'
        + '        Hello! I can help you find information in the documentation. Ask me anything about AtoM Heratio.'
        + '      </div>'
        + '    </div>'
        + '  </div>'
        + '  <div class="help-chat-input-area">'
        + '    <form id="helpChatForm" class="d-flex gap-2">'
        + '      <input type="text" class="form-control form-control-sm" id="helpChatInput"'
        + '        placeholder="Ask a question..." autocomplete="off">'
        + '      <button type="submit" class="btn btn-primary btn-sm">'
        + '        <i class="bi bi-send"></i>'
        + '      </button>'
        + '    </form>'
        + '  </div>'
        + '</div>';

      document.body.insertAdjacentHTML('beforeend', html);
      this.panel = document.getElementById('helpChatPanel');
    },

    bindEvents: function () {
      var self = this;

      document.getElementById('helpChatClose').addEventListener('click', function () {
        self.close();
      });

      document.getElementById('helpChatForm').addEventListener('submit', function (e) {
        e.preventDefault();
        self.sendMessage();
      });

      document.getElementById('helpChatModeToggle').addEventListener('click', function () {
        self.toggleMode();
      });

      // Keyboard shortcut: Escape to close
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && self.isOpen) {
          self.close();
        }
      });
    },

    toggle: function () {
      if (this.isOpen) {
        this.close();
      } else {
        this.open();
      }
    },

    open: function () {
      this.panel.classList.remove('d-none');
      this.isOpen = true;
      this.button.classList.add('active');
      document.getElementById('helpChatInput').focus();

      // Check AI availability on first open
      if (!this.aiAvailable) {
        this.checkAiAvailability();
      }
    },

    close: function () {
      this.panel.classList.add('d-none');
      this.isOpen = false;
      this.button.classList.remove('active');
    },

    checkAiAvailability: function () {
      var self = this;
      var xhr = new XMLHttpRequest();
      xhr.open('POST', '/help/api/chat');
      xhr.setRequestHeader('Content-Type', 'application/json');
      xhr.onload = function () {
        if (xhr.status === 200) {
          try {
            var data = JSON.parse(xhr.responseText);
            if (data.ai_available) {
              self.aiAvailable = true;
              document.getElementById('helpChatModeToggle').style.display = 'block';
            }
          } catch (e) { /* ignore */ }
        }
      };
      xhr.send(JSON.stringify({ message: 'ping', mode: 'search' }));
    },

    toggleMode: function () {
      if (this.mode === 'search' && this.aiAvailable) {
        this.mode = 'ai';
        document.getElementById('helpChatModeBadge').textContent = 'AI';
        document.getElementById('helpChatModeBadge').classList.add('ai-mode');
        this.addMessage('assistant', 'Switched to AI mode. I will use the AI to generate contextual answers from the documentation.');
      } else {
        this.mode = 'search';
        document.getElementById('helpChatModeBadge').textContent = 'Search';
        document.getElementById('helpChatModeBadge').classList.remove('ai-mode');
        this.addMessage('assistant', 'Switched to search mode. I will match your questions directly to help articles.');
      }
    },

    sendMessage: function () {
      var input = document.getElementById('helpChatInput');
      var message = input.value.trim();
      if (!message) return;

      // Show user message
      this.addMessage('user', message);
      input.value = '';

      // Add to history
      this.history.push({ role: 'user', content: message });

      // Show typing indicator
      var typingId = this.addTypingIndicator();

      // Send to API
      var self = this;
      var xhr = new XMLHttpRequest();
      xhr.open('POST', '/help/api/chat');
      xhr.setRequestHeader('Content-Type', 'application/json');
      xhr.onload = function () {
        self.removeTypingIndicator(typingId);

        if (xhr.status === 200) {
          try {
            var data = JSON.parse(xhr.responseText);
            self.handleResponse(data);
          } catch (e) {
            self.addMessage('assistant', 'Sorry, I encountered an error. Please try again.');
          }
        } else {
          self.addMessage('assistant', 'Sorry, I could not process your request. Please try again.');
        }
      };
      xhr.onerror = function () {
        self.removeTypingIndicator(typingId);
        self.addMessage('assistant', 'Sorry, a network error occurred. Please check your connection.');
      };
      xhr.send(JSON.stringify({
        message: message,
        mode: this.mode,
        history: this.history.slice(-6) // Last 3 exchanges
      }));
    },

    handleResponse: function (data) {
      var answer = data.answer || 'I could not find a relevant answer.';

      // Format answer (convert markdown bold to HTML)
      answer = answer.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
      answer = answer.replace(/\n/g, '<br>');

      // Build sources links
      var sourcesHtml = '';
      if (data.sources && data.sources.length > 0) {
        sourcesHtml = '<div class="help-chat-sources">';
        sourcesHtml += '<small class="text-muted d-block mt-2">Related articles:</small>';
        for (var i = 0; i < Math.min(data.sources.length, 4); i++) {
          var s = data.sources[i];
          var url = '/help/article/' + encodeURIComponent(s.slug);
          if (s.anchor) {
            url += '#' + s.anchor;
          }
          sourcesHtml += '<a href="' + url + '" class="help-chat-source-link" target="_blank">'
            + '<i class="bi bi-file-text me-1"></i>' + this.escapeHtml(s.title)
            + '</a>';
        }
        sourcesHtml += '</div>';
      }

      this.addMessage('assistant', answer + sourcesHtml, true);

      // Add to history
      this.history.push({ role: 'assistant', content: data.answer || '' });
    },

    addMessage: function (role, content, isHtml) {
      var container = document.getElementById('helpChatMessages');
      var div = document.createElement('div');
      div.className = 'help-chat-message ' + role;

      var bubble = document.createElement('div');
      bubble.className = 'help-chat-bubble';

      if (isHtml) {
        bubble.innerHTML = content;
      } else {
        bubble.textContent = content;
      }

      div.appendChild(bubble);
      container.appendChild(div);

      // Scroll to bottom
      container.scrollTop = container.scrollHeight;
    },

    addTypingIndicator: function () {
      var container = document.getElementById('helpChatMessages');
      var id = 'typing-' + Date.now();

      var div = document.createElement('div');
      div.className = 'help-chat-message assistant';
      div.id = id;

      div.innerHTML = '<div class="help-chat-bubble help-chat-typing">'
        + '<span class="dot"></span><span class="dot"></span><span class="dot"></span>'
        + '</div>';

      container.appendChild(div);
      container.scrollTop = container.scrollHeight;

      return id;
    },

    removeTypingIndicator: function (id) {
      var el = document.getElementById(id);
      if (el) el.remove();
    },

    escapeHtml: function (str) {
      var div = document.createElement('div');
      div.textContent = str;
      return div.innerHTML;
    }
  };

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      HelpChatbot.init();
    });
  } else {
    HelpChatbot.init();
  }
})();
