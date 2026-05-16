/*
 * citation-popover.js
 *
 * Spec: docs/atom-heratio-research-enhancements-spec.md §1.4
 *
 * Walks text nodes inside the configured containers, wraps every [N] pattern
 * in <a class="citation-marker">, and shows a Bootstrap popover sourced from
 * #studio-citations [data-citation-n="N"] when the user hovers/clicks.
 *
 * Template contract: any view that wants popovers MUST render
 *   <ul id="studio-citations">
 *     <li data-citation-n="1">
 *       <strong>Title</strong>
 *       <a href="/index.php/slug">Open source</a>
 *       <div class="small text-muted">snippet…</div>
 *     </li>
 *     ...
 *   </ul>
 *
 * No build step; vanilla JS + Bootstrap 5 popovers (already in the theme).
 */
(function () {
    'use strict';

    var TARGET_SELECTORS = ['.markdown-body', '#studio-body', '.studio-citations-host'];
    var MARKER_RE = /\[(\d{1,3})\]/g;

    function init() {
        var sources = collectSources();
        if (!Object.keys(sources).length) return;

        TARGET_SELECTORS.forEach(function (sel) {
            document.querySelectorAll(sel).forEach(function (host) {
                walkAndWrap(host, sources);
            });
        });

        if (window.bootstrap && window.bootstrap.Popover) {
            document.querySelectorAll('.citation-marker').forEach(function (a) {
                new window.bootstrap.Popover(a, {
                    html:      true,
                    trigger:   'hover focus',
                    placement: 'top',
                    container: 'body',
                });
            });
        }
    }

    function collectSources() {
        var out = {};
        document.querySelectorAll('#studio-citations [data-citation-n]').forEach(function (li) {
            var n = li.getAttribute('data-citation-n');
            if (!n) return;
            var title = (li.querySelector('strong, .citation-title') || {}).textContent || ('Source ' + n);
            var snippet = (li.querySelector('.small, .text-muted, .citation-snippet') || {}).textContent || '';
            var link = li.querySelector('a');
            var href = link ? link.getAttribute('href') : null;
            out[n] = { title: title.trim(), snippet: snippet.trim(), url: href };
        });
        return out;
    }

    function walkAndWrap(root, sources) {
        var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
            acceptNode: function (node) {
                if (!node.nodeValue || !MARKER_RE.test(node.nodeValue)) return NodeFilter.FILTER_SKIP;
                MARKER_RE.lastIndex = 0;
                // Don't re-wrap inside an existing marker
                var p = node.parentNode;
                while (p && p !== root) {
                    if (p.classList && p.classList.contains('citation-marker')) return NodeFilter.FILTER_REJECT;
                    p = p.parentNode;
                }
                return NodeFilter.FILTER_ACCEPT;
            }
        });

        var toReplace = [];
        while (walker.nextNode()) toReplace.push(walker.currentNode);

        toReplace.forEach(function (textNode) {
            replaceTextNode(textNode, sources);
        });
    }

    function replaceTextNode(textNode, sources) {
        var text = textNode.nodeValue;
        var frag = document.createDocumentFragment();
        var lastIdx = 0;
        var m;

        MARKER_RE.lastIndex = 0;
        while ((m = MARKER_RE.exec(text)) !== null) {
            var n = m[1];
            if (m.index > lastIdx) {
                frag.appendChild(document.createTextNode(text.substring(lastIdx, m.index)));
            }
            var src = sources[n];
            if (src) {
                var a = document.createElement('a');
                a.className = 'citation-marker';
                a.textContent = '[' + n + ']';
                a.href = src.url || '#';
                a.setAttribute('role', 'button');
                a.setAttribute('data-bs-toggle', 'popover');
                a.setAttribute('title', src.title);
                var contentHtml = escapeHtml(src.snippet);
                if (src.url) {
                    contentHtml += '<div class="mt-2"><a href="' + escapeAttr(src.url) + '">Open source &raquo;</a></div>';
                }
                a.setAttribute('data-bs-content', contentHtml);
                a.addEventListener('click', function (ev) {
                    var li = document.querySelector('#studio-citations [data-citation-n="' + n + '"]');
                    if (li) {
                        ev.preventDefault();
                        li.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        li.classList.add('citation-flash');
                        setTimeout(function () { li.classList.remove('citation-flash'); }, 1500);
                    }
                });
                frag.appendChild(a);
            } else {
                frag.appendChild(document.createTextNode(m[0]));
            }
            lastIdx = m.index + m[0].length;
        }
        if (lastIdx < text.length) {
            frag.appendChild(document.createTextNode(text.substring(lastIdx)));
        }
        textNode.parentNode.replaceChild(frag, textNode);
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' })[c];
        });
    }
    function escapeAttr(s) { return escapeHtml(s); }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
