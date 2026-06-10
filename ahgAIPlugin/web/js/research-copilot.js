/* Researcher Copilot (#149) — persistent research sessions over the RAG
 * assistant. Vanilla JS; reuses the #121 chatbot rendering. */
(function (w) {
  'use strict';

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function mdToHtml(t) {
    return esc(t)
      .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
      .replace(/\*([^*]+)\*/g, '<em>$1</em>')
      .replace(/`([^`]+)`/g, '<code>$1</code>')
      .replace(/\n/g, '<br>');
  }

  function init(opts) {
    var sessionsEl = document.getElementById('rc-sessions'),
        newBtn = document.getElementById('rc-new'),
        log = document.getElementById('rc-log'),
        form = document.getElementById('rc-form'),
        input = document.getElementById('rc-input'),
        send = document.getElementById('rc-send'),
        empty = document.getElementById('rc-empty'),
        titleEl = document.getElementById('rc-title'),
        exportEl = document.getElementById('rc-export'),
        deleteEl = document.getElementById('rc-delete');
    if (!log || !form) { return; }

    var activeId = 0;

    function clearEmpty() { if (empty) { empty.remove(); empty = null; } }
    function bubble(role, html) {
      clearEmpty();
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
      if (!sources || !sources.length) { return ''; }
      var items = sources.map(function (s) {
        return '<li><a href="' + esc(opts.recordBase) + esc(s.slug) + '">' + esc(s.title) + '</a></li>';
      }).join('');
      return '<div class="mt-2 small text-muted"><div>Sources:</div><ul class="mb-0">' + items + '</ul></div>';
    }

    function setActive(id, title) {
      activeId = id || 0;
      if (titleEl) { titleEl.textContent = title || 'New research session'; }
      var on = activeId > 0;
      if (exportEl) {
        exportEl.classList.toggle('disabled', !on);
        exportEl.href = on ? (opts.sessionUrl + activeId + '?op=export') : '#';
      }
      if (deleteEl) { deleteEl.disabled = !on; }
      // highlight the active item
      Array.prototype.forEach.call(sessionsEl.querySelectorAll('[data-sid]'), function (el) {
        el.classList.toggle('active', parseInt(el.getAttribute('data-sid'), 10) === activeId);
      });
    }

    function renderSessions(list) {
      sessionsEl.innerHTML = '';
      if (!list || !list.length) {
        sessionsEl.innerHTML = '<div class="text-muted small p-2">No saved sessions yet.</div>';
        return;
      }
      list.forEach(function (s) {
        var a = document.createElement('a');
        a.href = '#';
        a.className = 'list-group-item list-group-item-action py-2';
        a.setAttribute('data-sid', s.id);
        a.innerHTML = '<div class="text-truncate small fw-semibold">' + esc(s.title) + '</div>'
          + '<div class="text-muted" style="font-size:.72rem">' + esc((s.updated_at || '').substring(0, 16)) + '</div>';
        a.addEventListener('click', function (e) { e.preventDefault(); openSession(s.id, s.title); });
        sessionsEl.appendChild(a);
      });
      setActive(activeId, titleEl ? titleEl.textContent : '');
    }

    function loadSessions() {
      fetch(opts.sessionsUrl, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) { renderSessions(d.sessions || []); })
        .catch(function () { sessionsEl.innerHTML = '<div class="text-danger small p-2">Could not load sessions.</div>'; });
    }

    function resetLog(msg) {
      log.innerHTML = '<div class="text-muted small text-center mt-5" id="rc-empty">' + esc(msg || 'Start a new question.') + '</div>';
      empty = document.getElementById('rc-empty');
    }

    function openSession(id, title) {
      setActive(id, title);
      resetLog('Loading…');
      fetch(opts.sessionUrl + id, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          log.innerHTML = ''; empty = null;
          (d.messages || []).forEach(function (m) {
            if (m.role === 'user') { bubble('user', esc(m.content)); }
            else { bubble('assistant', mdToHtml(m.content) + sourcesHtml(m.sources)); }
          });
          if (!(d.messages || []).length) { resetLog('No messages yet — ask a question.'); }
        })
        .catch(function () { resetLog('Could not load this session.'); });
    }

    function newSession() {
      setActive(0, 'New research session');
      resetLog('Start a new question.');
      input.focus();
    }

    if (newBtn) { newBtn.addEventListener('click', function () { newSession(); }); }
    if (deleteEl) {
      deleteEl.addEventListener('click', function () {
        if (!activeId || !w.confirm('Delete this research session?')) { return; }
        var del = activeId;
        fetch(opts.sessionUrl + del + '?op=delete', { method: 'POST', credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function () { newSession(); loadSessions(); });
      });
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var msg = input.value.trim();
      if (!msg) { return; }
      bubble('user', esc(msg));
      input.value = '';
      input.disabled = send.disabled = true;
      var thinking = bubble('assistant', '<span class="text-muted"><i class="fas fa-spinner fa-spin me-1"></i>Searching the catalogue…</span>');

      fetch(opts.askUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ session_id: activeId, message: msg })
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.error === 'not_authenticated') { thinking.innerHTML = 'Please sign in to use the copilot.'; return; }
          thinking.innerHTML = mdToHtml(data.answer || 'No answer.') + sourcesHtml(data.sources);
          if (data.error) { thinking.innerHTML += '<div class="small text-warning mt-1">' + esc(data.error) + '</div>'; }
          var wasNew = !activeId;
          if (data.session_id) { setActive(data.session_id, data.title || (titleEl ? titleEl.textContent : '')); }
          if (wasNew) { loadSessions(); }
        })
        .catch(function (err) { thinking.innerHTML = 'Request failed: ' + esc(err && err.message ? err.message : err); })
        .finally(function () { input.disabled = send.disabled = false; input.focus(); log.scrollTop = log.scrollHeight; });
    });

    loadSessions();
  }

  w.AhgResearchCopilot = { init: init };
})(window);
