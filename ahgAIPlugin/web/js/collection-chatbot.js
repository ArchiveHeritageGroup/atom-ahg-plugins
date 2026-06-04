/* Collection chatbot UI (#121). RAG Q&A over the catalogue. Vanilla JS. */
(function (w) {
  'use strict';

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  // Minimal, safe markdown: bold, italics, code, line breaks. Escapes first.
  function mdToHtml(t) {
    return esc(t)
      .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
      .replace(/\*([^*]+)\*/g, '<em>$1</em>')
      .replace(/`([^`]+)`/g, '<code>$1</code>')
      .replace(/\n/g, '<br>');
  }

  function init(opts) {
    var log = document.getElementById('chat-log'),
        form = document.getElementById('chat-form'),
        input = document.getElementById('chat-input'),
        send = document.getElementById('chat-send'),
        empty = document.getElementById('chat-empty'),
        history = [];

    if (!log || !form) return;

    function bubble(role, html) {
      if (empty) { empty.remove(); empty = null; }
      var wrap = document.createElement('div');
      wrap.className = 'mb-3 d-flex ' + (role === 'user' ? 'justify-content-end' : 'justify-content-start');
      var b = document.createElement('div');
      b.className = 'p-2 px-3 rounded ' + (role === 'user' ? 'bg-primary text-white' : 'bg-white border');
      b.style.maxWidth = '85%';
      b.innerHTML = html;
      wrap.appendChild(b);
      log.appendChild(wrap);
      log.scrollTop = log.scrollHeight;
      return b;
    }

    function sourcesHtml(sources) {
      if (!sources || !sources.length) return '';
      var items = sources.map(function (s) {
        return '<li><a href="' + esc(opts.recordBase) + esc(s.slug) + '">' + esc(s.title) + '</a></li>';
      }).join('');
      return '<div class="mt-2 small text-muted"><div>Sources:</div><ul class="mb-0">' + items + '</ul></div>';
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var msg = input.value.trim();
      if (!msg) return;
      bubble('user', esc(msg));
      input.value = '';
      input.disabled = send.disabled = true;
      var thinking = bubble('assistant', '<span class="text-muted"><i class="fas fa-spinner fa-spin me-1"></i>Searching the catalogue…</span>');

      fetch(opts.askUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: msg, history: history.slice(-6) })
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.error === 'not_authenticated') { thinking.innerHTML = 'Please sign in to use the assistant.'; return; }
          thinking.innerHTML = mdToHtml(data.answer || 'No answer.') + sourcesHtml(data.sources);
          if (data.error) {
            thinking.innerHTML += '<div class="small text-warning mt-1">' + esc(data.error) + '</div>';
          }
          history.push({ role: 'user', content: msg });
          history.push({ role: 'assistant', content: data.answer || '' });
        })
        .catch(function (err) { thinking.innerHTML = 'Request failed: ' + esc(err && err.message ? err.message : err); })
        .finally(function () { input.disabled = send.disabled = false; input.focus(); log.scrollTop = log.scrollHeight; });
    });
  }

  w.AhgCollectionChatbot = { init: init };
})(window);
