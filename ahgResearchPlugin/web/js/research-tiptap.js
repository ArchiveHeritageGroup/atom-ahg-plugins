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
 *   placeholder: string
 *   initialContent: string  - HTML to load on init
 *   onUpdate: function(html) - callback on content change
 */
(function() {
  'use strict';

  var TipTapCore = window['@tiptap/core'];
  var StarterKit = window['@tiptap/starter-kit'];
  var TextStyle = window['@tiptap/extension-text-style'];
  var Image = window['@tiptap/extension-image'];
  var Underline = window['@tiptap/extension-underline'];
  var TextAlign = window['@tiptap/extension-text-align'];
  var Color = window['@tiptap/extension-color'];
  var Highlight = window['@tiptap/extension-highlight'];
  var Link = window['@tiptap/extension-link'];

  // Resolve UMD default exports
  var Editor = TipTapCore.Editor || TipTapCore.default && TipTapCore.default.Editor || TipTapCore;
  var StarterKitExt = StarterKit.StarterKit || StarterKit.default || StarterKit;
  var TextStyleExt = TextStyle.TextStyle || TextStyle.default || TextStyle;
  var ImageExt = Image.Image || Image.default || Image;
  var UnderlineExt = Underline.Underline || Underline.default || Underline;
  var TextAlignExt = TextAlign.TextAlign || TextAlign.default || TextAlign;
  var ColorExt = Color.Color || Color.default || Color;
  var HighlightExt = Highlight.Highlight || Highlight.default || Highlight;
  var LinkExt = Link.Link || Link.default || Link;

  var instances = {};

  // ─── Toolbar Definitions ──────────────────────────────────────

  function buildToolbar(profile, hasUpload) {
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
    var toolbarItems = buildToolbar(profile, !!uploadUrl);
    var toolbar = renderToolbar(wrapper, toolbarItems, editor, options);

    instances[containerId] = editor;
    return editor;
  }

  window.ResearchTipTap = {
    create: create,
    instances: instances
  };

})();
