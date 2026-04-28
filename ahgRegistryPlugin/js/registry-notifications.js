// Registry notifications: bell dropdown + top bar wiring.
(function () {
  'use strict';

  var bellToggle = document.getElementById('regNotifBellToggle');
  var listEl = document.getElementById('regNotifList');
  var badge = document.getElementById('regNotifBadge');
  var markAllBtn = document.getElementById('regNotifMarkAllRead');
  var bar = document.getElementById('regNotifBar');
  var barClose = document.getElementById('regNotifBarClose');

  function escapeHtml(s) {
    if (s == null) return '';
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function renderItems(payload) {
    if (!listEl) return;
    var items = payload && payload.items ? payload.items : [];
    if (badge) {
      var unread = payload && typeof payload.unread === 'number' ? payload.unread : 0;
      if (unread > 0) {
        badge.textContent = unread > 99 ? '99+' : String(unread);
        badge.classList.remove('d-none');
      } else {
        badge.classList.add('d-none');
      }
    }
    if (items.length === 0) {
      listEl.innerHTML = '<div class="reg-notif-empty"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No notifications yet.</div>';
      return;
    }
    var html = '';
    items.forEach(function (n) {
      var href = n.link || '/registry/notifications';
      var unreadCls = n.is_read ? '' : ' unread';
      html += '<a href="' + escapeHtml(href) + '" class="reg-notif-item' + unreadCls + '" data-id="' + n.id + '">'
        + '<div class="reg-notif-title">' + escapeHtml(n.title) + '</div>'
        + (n.message ? '<div class="reg-notif-msg">' + escapeHtml(n.message.substring(0, 140)) + '</div>' : '')
        + '<div class="reg-notif-time">' + escapeHtml(n.created_at_human || '') + '</div>'
        + '</a>';
    });
    listEl.innerHTML = html;

    // Wire item clicks: mark as read before navigating.
    listEl.querySelectorAll('.reg-notif-item').forEach(function (a) {
      a.addEventListener('click', function (ev) {
        var id = a.getAttribute('data-id');
        if (!id || !a.classList.contains('unread')) return;
        // Fire-and-forget; don't block navigation.
        try {
          var fd = new FormData();
          fetch('/registry/api/notifications/' + encodeURIComponent(id) + '/read', {
            method: 'POST',
            credentials: 'same-origin',
            body: fd,
          });
        } catch (e) { /* ignore */ }
      });
    });
  }

  function fetchNotifications() {
    if (!listEl) return;
    fetch('/registry/api/notifications', { credentials: 'same-origin' })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (data) { if (data) renderItems(data); })
      .catch(function () { /* silent */ });
  }

  if (bellToggle) {
    // Lazy-load the first time the dropdown opens; refresh on each subsequent open.
    bellToggle.addEventListener('click', function () { fetchNotifications(); });
    // Initial load so the badge stays accurate (already SSR'd, but JS keeps it in sync).
    fetchNotifications();
    // Refresh every 60 seconds.
    setInterval(fetchNotifications, 60000);
  }

  if (markAllBtn) {
    markAllBtn.addEventListener('click', function (ev) {
      ev.preventDefault();
      var fd = new FormData();
      fetch('/registry/api/notifications/read-all', {
        method: 'POST',
        credentials: 'same-origin',
        body: fd,
      })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (data) {
          if (data && badge) {
            badge.classList.add('d-none');
          }
          fetchNotifications();
        })
        .catch(function () { /* silent */ });
    });
  }

  if (bar && barClose) {
    barClose.addEventListener('click', function () {
      var id = bar.getAttribute('data-id');
      bar.style.display = 'none';
      if (!id) return;
      var fd = new FormData();
      fetch('/registry/api/notifications/' + encodeURIComponent(id) + '/dismiss-bar', {
        method: 'POST',
        credentials: 'same-origin',
        body: fd,
      }).catch(function () { /* silent */ });
    });
  }
})();
