/**
 * research-tiptap.js - TipTap editor wrapper for ahgResearchPlugin
 *
 * API:
 *   ResearchTipTap.create(containerId, options) -> TipTap Editor instance
 *   ResearchTipTap.instances[containerId] -> editor
 *
 * Options:
 *   profile: 'minimal' | 'medium' | 'full' (default: 'minimal')
 *   uploadUrl: string|null  - POST endpoint for image upload (null = no image btn)
 *   resolveUrl: string|null - GET endpoint for internal thumbnail resolution
 *   placeholder: string
 *   initialContent: string  - HTML to load on init
 *   onUpdate: function(html) - callback on content change
 */
(function() {
  'use strict';

  // All TipTap exports come from the pre-bundled window.TipTap global
  var T = window.TipTap;
  var Editor = T.Editor;
  var StarterKitExt = T.StarterKit;
  var TextStyleExt = T.TextStyle;
  var ImageExt = T.Image;
  var UnderlineExt = T.Underline;
  var TextAlignExt = T.TextAlign;
  var ColorExt = T.Color;
  var HighlightExt = T.Highlight;
  var LinkExt = T.Link;

  var instances = {};

  // ─── Toolbar Definitions ──────────────────────────────────────

  function buildToolbar(profile, hasUpload, hasResolve) {
    var items = [];

    // Heading select (all profiles)
    items.push({ type: 'heading-select' });
    items.push({ type: 'separator' });

    // Inline formatting (all profiles)
    items.push({ type: 'button', cmd: 'bold', icon: 'fa-bold', title: 'Bold' });
    items.push({ type: 'button', cmd: 'italic', icon: 'fa-italic', title: 'Italic' });
    items.push({ type: 'button', cmd: 'underline', icon: 'fa-underline', title: 'Underline' });
    items.push({ type: 'button', cmd: 'strike', icon: 'fa-strikethrough', title: 'Strikethrough' });
    items.push({ type: 'separator' });

    // Lists (all profiles)
    items.push({ type: 'button', cmd: 'bulletList', icon: 'fa-list-ul', title: 'Bullet List' });
    items.push({ type: 'button', cmd: 'orderedList', icon: 'fa-list-ol', title: 'Ordered List' });
    items.push({ type: 'button', cmd: 'blockquote', icon: 'fa-quote-left', title: 'Blockquote' });
    items.push({ type: 'separator' });

    // Link (all profiles)
    items.push({ type: 'button', cmd: 'link', icon: 'fa-link', title: 'Link' });

    // Image (medium + full with upload)
    if ((profile === 'medium' || profile === 'full') && hasUpload) {
      items.push({ type: 'button', cmd: 'image', icon: 'fa-image', title: 'Insert Image' });
    }

    // Embed Record (medium + full with resolve endpoint)
    if ((profile === 'medium' || profile === 'full') && hasResolve) {
      items.push({ type: 'button', cmd: 'embedRecord', icon: 'fa-archive', title: 'Embed Record Thumbnail' });
    }

    // Color + highlight (full only)
    if (profile === 'full') {
      items.push({ type: 'separator' });
      items.push({ type: 'color', cmd: 'color', title: 'Text Color' });
      items.push({ type: 'color', cmd: 'highlight', title: 'Highlight' });
    }

    items.push({ type: 'separator' });
    items.push({ type: 'button', cmd: 'clear', icon: 'fa-eraser', title: 'Clear Formatting' });

    return items;
  }

  function renderToolbar(container, items, editor, options) {
    var toolbar = document.createElement('div');
    toolbar.className = 'rtt-toolbar';

    items.forEach(function(item) {
      if (item.type === 'separator') {
        var sep = document.createElement('span');
        sep.className = 'separator';
        toolbar.appendChild(sep);
        return;
      }

      if (item.type === 'heading-select') {
        var sel = document.createElement('select');
        sel.title = 'Block Type';
        sel.innerHTML =
          '<option value="p">Normal</option>' +
          '<option value="1">Heading 1</option>' +
          '<option value="2">Heading 2</option>' +
          '<option value="3">Heading 3</option>';
        sel.addEventListener('change', function() {
          var v = this.value;
          if (v === 'p') {
            editor.chain().focus().setParagraph().run();
          } else {
            editor.chain().focus().toggleHeading({ level: parseInt(v) }).run();
          }
        });
        sel._rttHeadingSelect = true;
        toolbar.appendChild(sel);
        return;
      }

      if (item.type === 'color') {
        var inp = document.createElement('input');
        inp.type = 'color';
        inp.title = item.title;
        inp.value = item.cmd === 'highlight' ? '#ffff00' : '#000000';
        inp.addEventListener('input', function() {
          if (item.cmd === 'highlight') {
            editor.chain().focus().toggleHighlight({ color: this.value }).run();
          } else {
            editor.chain().focus().setColor(this.value).run();
          }
        });
        toolbar.appendChild(inp);
        return;
      }

      // Button
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'btn';
      btn.title = item.title;
      btn.innerHTML = '<i class="fas ' + item.icon + '"></i>';
      btn.dataset.cmd = item.cmd;

      btn.addEventListener('click', function(e) {
        e.preventDefault();
        handleCommand(editor, item.cmd, options);
      });

      toolbar.appendChild(btn);
    });

    container.insertBefore(toolbar, container.firstChild);
    return toolbar;
  }

  function handleCommand(editor, cmd, options) {
    switch (cmd) {
      case 'bold':
        editor.chain().focus().toggleBold().run();
        break;
      case 'italic':
        editor.chain().focus().toggleItalic().run();
        break;
      case 'underline':
        editor.chain().focus().toggleUnderline().run();
        break;
      case 'strike':
        editor.chain().focus().toggleStrike().run();
        break;
      case 'bulletList':
        editor.chain().focus().toggleBulletList().run();
        break;
      case 'orderedList':
        editor.chain().focus().toggleOrderedList().run();
        break;
      case 'blockquote':
        editor.chain().focus().toggleBlockquote().run();
        break;
      case 'link':
        var prevUrl = editor.getAttributes('link').href || '';
        var url = prompt('Enter URL:', prevUrl);
        if (url === null) return;
        if (url === '') {
          editor.chain().focus().unsetLink().run();
        } else {
          editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
        }
        break;
      case 'image':
        if (options && options.uploadUrl) {
          uploadImage(editor, options.uploadUrl);
        }
        break;
      case 'embedRecord':
        if (options && options.resolveUrl) {
          openEmbedRecordModal(editor, options.resolveUrl);
        }
        break;
      case 'clear':
        editor.chain().focus().unsetAllMarks().clearNodes().run();
        break;
    }
  }

  function uploadImage(editor, uploadUrl) {
    var input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.addEventListener('change', function() {
      if (!this.files || !this.files[0]) return;
      var formData = new FormData();
      formData.append('image', this.files[0]);
      fetch(uploadUrl, { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (data.url) {
            editor.chain().focus().setImage({ src: data.url }).run();
          } else if (data.error) {
            alert('Upload failed: ' + data.error);
          }
        })
        .catch(function(err) {
          alert('Upload error: ' + err.message);
        });
    });
    input.click();
  }

  // ─── Embed Record Modal ─────────────────────────────────────

  function openEmbedRecordModal(editor, resolveUrl) {
    // Remove any existing modal
    var existing = document.getElementById('rtt-embed-modal');
    if (existing) existing.remove();

    var backdrop = document.createElement('div');
    backdrop.id = 'rtt-embed-modal';
    backdrop.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:10000;display:flex;align-items:center;justify-content:center;';

    var modal = document.createElement('div');
    modal.style.cssText = 'background:#fff;border-radius:8px;width:540px;max-width:95vw;max-height:80vh;display:flex;flex-direction:column;box-shadow:0 8px 32px rgba(0,0,0,0.3);';

    // Header
    var header = document.createElement('div');
    header.style.cssText = 'padding:16px 20px;border-bottom:1px solid #dee2e6;display:flex;justify-content:space-between;align-items:center;';
    header.innerHTML = '<h5 style="margin:0;font-size:1.1rem;"><i class="fas fa-archive" style="margin-right:8px;color:#0d6efd;"></i>Embed Record Thumbnail</h5>';
    var closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.innerHTML = '<i class="fas fa-times"></i>';
    closeBtn.style.cssText = 'border:none;background:none;font-size:1.2rem;cursor:pointer;color:#6c757d;padding:0;';
    closeBtn.addEventListener('click', function() { backdrop.remove(); });
    header.appendChild(closeBtn);
    modal.appendChild(header);

    // Search input
    var searchWrap = document.createElement('div');
    searchWrap.style.cssText = 'padding:12px 20px;border-bottom:1px solid #dee2e6;';
    var searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = 'Search records by title or paste a URL/slug...';
    searchInput.style.cssText = 'width:100%;padding:8px 12px;border:1px solid #ced4da;border-radius:6px;font-size:0.95rem;outline:none;';
    searchInput.addEventListener('focus', function() { this.style.borderColor = '#86b7fe'; });
    searchInput.addEventListener('blur', function() { this.style.borderColor = '#ced4da'; });
    searchWrap.appendChild(searchInput);
    modal.appendChild(searchWrap);

    // Results container
    var resultsWrap = document.createElement('div');
    resultsWrap.style.cssText = 'padding:8px 20px;overflow-y:auto;flex:1;min-height:120px;max-height:400px;';
    resultsWrap.innerHTML = '<p style="color:#6c757d;text-align:center;margin-top:32px;">Type to search for archival records...</p>';
    modal.appendChild(resultsWrap);

    backdrop.appendChild(modal);
    document.body.appendChild(backdrop);

    // Close on backdrop click
    backdrop.addEventListener('click', function(e) {
      if (e.target === backdrop) backdrop.remove();
    });

    // Close on Escape
    function onKey(e) { if (e.key === 'Escape') { backdrop.remove(); document.removeEventListener('keydown', onKey); } }
    document.addEventListener('keydown', onKey);

    searchInput.focus();

    // Debounced search
    var timer = null;
    searchInput.addEventListener('input', function() {
      clearTimeout(timer);
      var val = this.value.trim();
      if (val.length < 2) {
        resultsWrap.innerHTML = '<p style="color:#6c757d;text-align:center;margin-top:32px;">Type to search for archival records...</p>';
        return;
      }
      timer = setTimeout(function() { doSearch(val, resolveUrl, resultsWrap, editor, backdrop); }, 300);
    });
  }

  function doSearch(query, resolveUrl, container, editor, backdrop) {
    container.innerHTML = '<p style="color:#6c757d;text-align:center;margin-top:32px;"><i class="fas fa-spinner fa-spin"></i> Searching...</p>';

    // Detect if it looks like a URL or slug
    var param = 'q';
    if (query.indexOf('/') !== -1 || query.indexOf('.') !== -1) {
      param = 'slug';
    }

    fetch(resolveUrl + '?' + param + '=' + encodeURIComponent(query))
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.results || data.results.length === 0) {
          container.innerHTML = '<p style="color:#6c757d;text-align:center;margin-top:32px;">No records found.</p>';
          return;
        }
        container.innerHTML = '';
        data.results.forEach(function(rec) {
          var card = document.createElement('div');
          card.style.cssText = 'display:flex;align-items:center;gap:12px;padding:10px 12px;margin-bottom:6px;border:1px solid #dee2e6;border-radius:6px;cursor:pointer;transition:background 0.15s;';
          card.addEventListener('mouseenter', function() { this.style.background = '#f0f4ff'; });
          card.addEventListener('mouseleave', function() { this.style.background = '#fff'; });

          // Thumbnail or placeholder
          var thumbEl = document.createElement('div');
          thumbEl.style.cssText = 'width:56px;height:56px;flex-shrink:0;border-radius:4px;overflow:hidden;background:#f8f9fa;display:flex;align-items:center;justify-content:center;border:1px solid #e9ecef;';
          if (rec.thumbnailUrl) {
            thumbEl.innerHTML = '<img src="' + escHtml(rec.thumbnailUrl) + '" style="width:100%;height:100%;object-fit:cover;" alt="">';
          } else {
            thumbEl.innerHTML = '<i class="fas fa-file-alt" style="font-size:1.4rem;color:#adb5bd;"></i>';
          }
          card.appendChild(thumbEl);

          // Text
          var textEl = document.createElement('div');
          textEl.style.cssText = 'flex:1;min-width:0;';
          var titleText = rec.title || 'Untitled';
          var sub = rec.identifier ? '<div style="font-size:0.8rem;color:#6c757d;">' + escHtml(rec.identifier) + '</div>' : '';
          textEl.innerHTML = '<div style="font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + escHtml(titleText) + '</div>' + sub;
          card.appendChild(textEl);

          // Insert icon
          var insertEl = document.createElement('div');
          insertEl.style.cssText = 'flex-shrink:0;color:#0d6efd;font-size:0.85rem;';
          insertEl.innerHTML = '<i class="fas fa-plus-circle"></i>';
          card.appendChild(insertEl);

          card.addEventListener('click', function() {
            insertEmbeddedRecord(editor, rec);
            backdrop.remove();
          });

          container.appendChild(card);
        });
      })
      .catch(function(err) {
        container.innerHTML = '<p style="color:#dc3545;text-align:center;margin-top:32px;">Search error: ' + escHtml(err.message) + '</p>';
      });
  }

  function insertEmbeddedRecord(editor, rec) {
    var title = rec.title || 'Untitled';
    var url = rec.url || '#';

    if (rec.thumbnailUrl) {
      // Insert thumbnail image followed by a linked title
      editor.chain().focus()
        .setImage({ src: rec.thumbnailUrl, alt: title, title: title })
        .insertContent({
          type: 'paragraph',
          content: [{
            type: 'text',
            marks: [{ type: 'link', attrs: { href: url, target: '_blank' } }],
            text: title
          }]
        })
        .run();
    } else {
      // No thumbnail — insert as a linked text reference
      editor.chain().focus()
        .insertContent({
          type: 'paragraph',
          content: [
            { type: 'text', marks: [{ type: 'bold' }], text: 'Record: ' },
            {
              type: 'text',
              marks: [{ type: 'link', attrs: { href: url, target: '_blank' } }],
              text: title
            }
          ]
        })
        .run();
    }
  }

  function escHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str || ''));
    return div.innerHTML;
  }

  function updateActiveStates(toolbar, editor) {
    toolbar.querySelectorAll('.btn[data-cmd]').forEach(function(btn) {
      var cmd = btn.dataset.cmd;
      var active = false;
      switch (cmd) {
        case 'bold': active = editor.isActive('bold'); break;
        case 'italic': active = editor.isActive('italic'); break;
        case 'underline': active = editor.isActive('underline'); break;
        case 'strike': active = editor.isActive('strike'); break;
        case 'bulletList': active = editor.isActive('bulletList'); break;
        case 'orderedList': active = editor.isActive('orderedList'); break;
        case 'blockquote': active = editor.isActive('blockquote'); break;
        case 'link': active = editor.isActive('link'); break;
      }
      btn.classList.toggle('active', active);
    });

    // Update heading select
    toolbar.querySelectorAll('select').forEach(function(sel) {
      if (!sel._rttHeadingSelect) return;
      if (editor.isActive('heading', { level: 1 })) sel.value = '1';
      else if (editor.isActive('heading', { level: 2 })) sel.value = '2';
      else if (editor.isActive('heading', { level: 3 })) sel.value = '3';
      else sel.value = 'p';
    });
  }

  // ─── Public API ───────────────────────────────────────────────

  function create(containerId, options) {
    options = options || {};
    var profile = options.profile || 'minimal';
    var placeholder = options.placeholder || 'Write here...';
    var initialContent = options.initialContent || '';
    var uploadUrl = options.uploadUrl || null;
    var resolveUrl = options.resolveUrl || null;
    var onUpdate = options.onUpdate || null;

    var container = document.getElementById(containerId);
    if (!container) {
      console.error('ResearchTipTap: container not found: ' + containerId);
      return null;
    }

    // If already initialized, return existing
    if (instances[containerId]) {
      return instances[containerId];
    }

    // Build wrapper structure
    container.innerHTML = '';
    var wrapper = document.createElement('div');
    wrapper.className = 'rtt-wrapper';
    wrapper.id = 'rtt-wrapper-' + containerId;

    var editorEl = document.createElement('div');
    editorEl.className = 'rtt-editor-area';
    wrapper.appendChild(editorEl);
    container.appendChild(wrapper);

    // Build extensions
    var extensions = [
      StarterKitExt.configure({
        heading: { levels: [1, 2, 3] }
      }),
      TextStyleExt,
      UnderlineExt,
      LinkExt.configure({ openOnClick: false }),
      ImageExt.configure({ inline: false, allowBase64: true })
    ];

    if (profile === 'full') {
      extensions.push(ColorExt);
      extensions.push(HighlightExt.configure({ multicolor: true }));
      extensions.push(TextAlignExt.configure({ types: ['heading', 'paragraph'] }));
    }

    // Create editor
    var editor = new Editor({
      element: editorEl,
      extensions: extensions,
      content: initialContent || '',
      editorProps: {
        attributes: {
          'data-placeholder': placeholder
        }
      },
      onUpdate: function(props) {
        var html = props.editor.getHTML();
        if (onUpdate) onUpdate(html);
        updateActiveStates(toolbar, props.editor);
      },
      onSelectionUpdate: function(props) {
        updateActiveStates(toolbar, props.editor);
      }
    });

    // Build and insert toolbar
    var toolbarItems = buildToolbar(profile, !!uploadUrl, !!resolveUrl);
    var toolbar = renderToolbar(wrapper, toolbarItems, editor, options);

    instances[containerId] = editor;
    return editor;
  }

  window.ResearchTipTap = {
    create: create,
    instances: instances
  };

})();
