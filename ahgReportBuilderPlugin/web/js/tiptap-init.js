/**
 * tiptap-init.js - TipTapManager wrapper for ahgReportBuilderPlugin
 * Drop-in replacement for QuillManager with identical API.
 *
 * API:
 *   TipTapManager.init(containerId, hiddenId, options) -> editor
 *   TipTapManager.getContent(containerId) -> html
 *   TipTapManager.setContent(containerId, html)
 *   TipTapManager.destroy(containerId)
 *   TipTapManager.getAll() -> {containerId: editor}
 */
(function() {
  'use strict';

  // All TipTap exports come from the pre-bundled window.TipTap global
  var T = window.TipTap;
  var Editor = T.Editor;
  var StarterKitResolved = T.StarterKit;
  var TextStyleResolved = T.TextStyle;
  var ImageResolved = T.Image;
  var UnderlineResolved = T.Underline;
  var TextAlignResolved = T.TextAlign;
  var ColorResolved = T.Color;
  var HighlightResolved = T.Highlight;
  var LinkResolved = T.Link;

  var editors = {};

  // ─── Toolbar ──────────────────────────────────────────────────

  function buildToolbar(wrapper, editor) {
    var toolbar = document.createElement('div');
    toolbar.className = 'rtt-toolbar';

    var items = [
      { type: 'heading-select' },
      { type: 'sep' },
      { cmd: 'bold', icon: 'fa-bold', title: 'Bold' },
      { cmd: 'italic', icon: 'fa-italic', title: 'Italic' },
      { cmd: 'underline', icon: 'fa-underline', title: 'Underline' },
      { cmd: 'strike', icon: 'fa-strikethrough', title: 'Strikethrough' },
      { type: 'sep' },
      { type: 'color', cmd: 'color', title: 'Text Color' },
      { type: 'color', cmd: 'highlight', title: 'Highlight' },
      { type: 'sep' },
      { cmd: 'bulletList', icon: 'fa-list-ul', title: 'Bullet List' },
      { cmd: 'orderedList', icon: 'fa-list-ol', title: 'Ordered List' },
      { type: 'sep' },
      { cmd: 'alignLeft', icon: 'fa-align-left', title: 'Align Left' },
      { cmd: 'alignCenter', icon: 'fa-align-center', title: 'Align Center' },
      { cmd: 'alignRight', icon: 'fa-align-right', title: 'Align Right' },
      { cmd: 'alignJustify', icon: 'fa-align-justify', title: 'Justify' },
      { type: 'sep' },
      { cmd: 'blockquote', icon: 'fa-quote-left', title: 'Blockquote' },
      { cmd: 'codeBlock', icon: 'fa-code', title: 'Code Block' },
      { cmd: 'link', icon: 'fa-link', title: 'Link' },
      { cmd: 'image', icon: 'fa-image', title: 'Insert Image (Base64)' },
      { type: 'sep' },
      { cmd: 'clear', icon: 'fa-eraser', title: 'Clear Formatting' }
    ];

    items.forEach(function(item) {
      if (item.type === 'sep') {
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
          '<option value="3">Heading 3</option>' +
          '<option value="4">Heading 4</option>';
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

      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'btn';
      btn.title = item.title;
      btn.innerHTML = '<i class="fas ' + item.icon + '"></i>';
      btn.dataset.cmd = item.cmd;

      btn.addEventListener('click', function(e) {
        e.preventDefault();
        execCommand(editor, item.cmd);
      });

      toolbar.appendChild(btn);
    });

    wrapper.insertBefore(toolbar, wrapper.firstChild);
    return toolbar;
  }

  function execCommand(editor, cmd) {
    switch (cmd) {
      case 'bold': editor.chain().focus().toggleBold().run(); break;
      case 'italic': editor.chain().focus().toggleItalic().run(); break;
      case 'underline': editor.chain().focus().toggleUnderline().run(); break;
      case 'strike': editor.chain().focus().toggleStrike().run(); break;
      case 'bulletList': editor.chain().focus().toggleBulletList().run(); break;
      case 'orderedList': editor.chain().focus().toggleOrderedList().run(); break;
      case 'blockquote': editor.chain().focus().toggleBlockquote().run(); break;
      case 'codeBlock': editor.chain().focus().toggleCodeBlock().run(); break;
      case 'alignLeft': editor.chain().focus().setTextAlign('left').run(); break;
      case 'alignCenter': editor.chain().focus().setTextAlign('center').run(); break;
      case 'alignRight': editor.chain().focus().setTextAlign('right').run(); break;
      case 'alignJustify': editor.chain().focus().setTextAlign('justify').run(); break;
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
        var input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.addEventListener('change', function() {
          if (!this.files || !this.files[0]) return;
          var reader = new FileReader();
          reader.onload = function(e) {
            editor.chain().focus().setImage({ src: e.target.result }).run();
          };
          reader.readAsDataURL(this.files[0]);
        });
        input.click();
        break;
      case 'clear':
        editor.chain().focus().unsetAllMarks().clearNodes().run();
        break;
    }
  }

  function updateActive(toolbar, editor) {
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
        case 'codeBlock': active = editor.isActive('codeBlock'); break;
        case 'link': active = editor.isActive('link'); break;
        case 'alignLeft': active = editor.isActive({ textAlign: 'left' }); break;
        case 'alignCenter': active = editor.isActive({ textAlign: 'center' }); break;
        case 'alignRight': active = editor.isActive({ textAlign: 'right' }); break;
        case 'alignJustify': active = editor.isActive({ textAlign: 'justify' }); break;
      }
      btn.classList.toggle('active', active);
    });

    toolbar.querySelectorAll('select').forEach(function(sel) {
      if (!sel._rttHeadingSelect) return;
      if (editor.isActive('heading', { level: 1 })) sel.value = '1';
      else if (editor.isActive('heading', { level: 2 })) sel.value = '2';
      else if (editor.isActive('heading', { level: 3 })) sel.value = '3';
      else if (editor.isActive('heading', { level: 4 })) sel.value = '4';
      else sel.value = 'p';
    });
  }

  // ─── Public API ───────────────────────────────────────────────

  var TipTapManager = {
    /**
     * Initialize a TipTap editor in the given container.
     * @param {string} containerId - DOM id of the container element
     * @param {string} hiddenId - DOM id of the hidden input to sync content to
     * @param {object} options - { placeholder: string }
     * @returns {object} TipTap editor instance
     */
    init: function(containerId, hiddenId, options) {
      options = options || {};
      var container = document.getElementById(containerId);
      var hiddenInput = document.getElementById(hiddenId);
      if (!container || !hiddenInput) {
        console.error('TipTapManager: container or hidden input not found', containerId, hiddenId);
        return null;
      }

      // If already initialized, return existing
      if (editors[containerId]) {
        return editors[containerId];
      }

      var initialContent = hiddenInput.value || '';
      var placeholder = options.placeholder || 'Write your narrative...';

      // Build wrapper structure
      container.innerHTML = '';
      var wrapper = document.createElement('div');
      wrapper.className = 'rtt-wrapper';

      var editorEl = document.createElement('div');
      editorEl.className = 'rtt-editor-area';
      wrapper.appendChild(editorEl);
      container.appendChild(wrapper);

      var editor = new Editor({
        element: editorEl,
        extensions: [
          StarterKitResolved.configure({
            heading: { levels: [1, 2, 3, 4] }
          }),
          TextStyleResolved,
          UnderlineResolved,
          ColorResolved,
          HighlightResolved.configure({ multicolor: true }),
          TextAlignResolved.configure({ types: ['heading', 'paragraph'] }),
          LinkResolved.configure({ openOnClick: false }),
          ImageResolved.configure({ inline: false, allowBase64: true })
        ],
        content: initialContent,
        editorProps: {
          attributes: {
            'data-placeholder': placeholder
          }
        },
        onUpdate: function(props) {
          var html = props.editor.getHTML();
          hiddenInput.value = html;
          hiddenInput.dispatchEvent(new Event('change'));
          updateActive(toolbar, props.editor);
        },
        onSelectionUpdate: function(props) {
          updateActive(toolbar, props.editor);
        }
      });

      var toolbar = buildToolbar(wrapper, editor);

      editors[containerId] = editor;
      return editor;
    },

    getContent: function(containerId) {
      if (editors[containerId]) {
        return editors[containerId].getHTML();
      }
      return '';
    },

    setContent: function(containerId, html) {
      if (editors[containerId]) {
        editors[containerId].commands.setContent(html || '');
      }
    },

    destroy: function(containerId) {
      if (editors[containerId]) {
        editors[containerId].destroy();
        delete editors[containerId];
      }
    },

    getAll: function() {
      return editors;
    }
  };

  window.TipTapManager = TipTapManager;
  // Backward-compat alias so existing code referencing QuillManager works
  window.QuillManager = TipTapManager;

})();
