<?php

namespace AhgIiif\Listeners;

/**
 * A way into the comparison workspace that does not involve typing a URL.
 *
 * /iiif/compare?slugs=a,b has existed and worked for as long as the plugin has,
 * and nothing anywhere linked to it - the route registration and its own action
 * were the only references in the whole suite. So the feature was reachable only
 * by someone who already knew the query-string format, which in practice means
 * whoever wrote it.
 *
 * This adds the missing half: a Compare button on each description, a selection
 * that survives page navigation, and a launcher once two records are picked.
 *
 * WHY THE SELECTION IS IN localStorage
 *
 * Comparing means choosing records across several pages, so the choice has to
 * outlive a page load. The obvious home is AtoM's clipboard, but the clipboard is
 * base AtoM and holds every kind of record, while this needs exactly the ones
 * with a IIIF manifest. localStorage keeps it self-contained: no schema, no
 * session state, nothing to clean up, and no base file touched.
 *
 * WHY IT IS INJECTED
 *
 * A description page is rendered by whichever descriptive-standard module the
 * record uses, so there is no single template to edit - and editing base AtoM is
 * not on the table. RecordActionBar puts the button in the same bar every other
 * contributor uses, so it inherits the theme rather than fighting it.
 */
class CompareInjector
{
    private const MARKER = 'ahg-iiif-compare-btn';

    public static function filter(\sfEvent $event, $content)
    {
        if (!class_exists('\AtomFramework\Views\RecordActionBar')) {
            return $content;
        }

        $html = \AtomFramework\Views\RecordActionBar::render(
            $event,
            $content,
            self::MARKER,
            static function (string $slug): string {
                // Icon only, btn-xs, and hidden until Mirador is the active
                // viewer. Comparison opens Mirador in mosaic mode, so offering it
                // while another viewer is showing invites a click that switches
                // the user somewhere they did not ask to go. The viewer manager
                // toggles d-none in showViewer(); with no viewer on the page at
                // all the button simply never appears, which is correct - there
                // is nothing to compare.
                return sprintf(
                    '<button type="button" class="btn btn-xs btn-outline-secondary d-none %s"'
                    .' data-ahg-compare-slug="%s" title="%s" data-bs-toggle="tooltip">'
                    .'<i class="fas fa-clone"></i></button>',
                    self::MARKER,
                    htmlspecialchars($slug, ENT_QUOTES),
                    htmlspecialchars(self::t('Compare'), ENT_QUOTES)
                );
            }
        );

        return self::withAssets($html);
    }

    /**
     * The behaviour, added once per response.
     *
     * Carried inline with the CSP nonce rather than registered with
     * addJavascript(): the theme never calls include_javascripts(), so a
     * registered asset is silently dropped and the button would render and do
     * nothing.
     *
     * No inline event handlers and no style attributes - a nonce covers script
     * and style ELEMENTS, never attributes, so both would be discarded wherever
     * the enforcing header is on.
     */
    private static function withAssets($html)
    {
        $html = (string) $html;

        if (false === strpos($html, self::MARKER)
            || false !== strpos($html, 'ahg-iiif-compare-js')) {
            return $html;
        }

        $nonce = \sfConfig::get('csp_nonce', '');
        $attr = $nonce ? ' '.preg_replace('/^nonce=/', 'nonce="', (string) $nonce).'"' : '';

        $base = \sfConfig::get('sf_relative_url_root', '');
        $compareUrl = $base.'/index.php/iiif/compare';

        $labels = json_encode([
            'add' => self::t('Compare'),
            'added' => self::t('Selected'),
            'open' => self::t('Compare %s records'),
            'clear' => self::t('Clear'),
            'need' => self::t('Pick one more record to compare'),
        ]);

        $css = <<<CSS
.ahg-compare-bar{position:fixed;right:1rem;bottom:1rem;z-index:1080;display:flex;
gap:.5rem;align-items:center;background:#fff;border:1px solid rgba(0,0,0,.2);
border-radius:.375rem;box-shadow:0 .5rem 1rem rgba(0,0,0,.15);padding:.5rem .75rem}
.ahg-compare-bar[hidden]{display:none}
.ahg-compare-count{font-weight:600}
CSS;

        $js = <<<JS
(function () {
  var KEY = 'ahgIiifCompare';

  var L = {$labels};
  var URL_BASE = '{$compareUrl}';

  function read() {
    try { return JSON.parse(localStorage.getItem(KEY)) || []; } catch (e) { return []; }
  }
  function write(list) {
    try { localStorage.setItem(KEY, JSON.stringify(list)); } catch (e) {}
  }

  function paintButtons(list) {
    document.querySelectorAll('[data-ahg-compare-slug]').forEach(function (b) {
      var on = list.indexOf(b.getAttribute('data-ahg-compare-slug')) !== -1;
      b.classList.toggle('btn-secondary', on);
      b.classList.toggle('btn-outline-secondary', !on);
      // The button is icon-only, so its lastChild is the <i>. Writing a label into
      // it put text inside the icon element and produced a small coloured block.
      // State goes on the tooltip instead.
      b.setAttribute('title', on ? L.added : L.add);
    });
  }

  function paintBar(list) {
    var bar = document.getElementById('ahg-compare-bar');
    if (!bar) { return; }
    // Only offer the launcher where comparison can actually be started: that is,
    // where a compare button is currently visible, which means Mirador is the
    // active viewer. Otherwise a stored selection left a fixed-position bar
    // floating over every page in the site, including records with no image.
    var live = document.querySelector('.ahg-iiif-compare-btn:not(.d-none)');
    bar.hidden = list.length === 0 || !live;
    bar.querySelector('.ahg-compare-count').textContent =
      list.length < 2 ? L.need : L.open.replace('%s', list.length);
    bar.querySelector('.ahg-compare-go').disabled = list.length < 2;
  }

  function refresh() {
    var list = read();
    paintButtons(list);
    paintBar(list);
  }

  // The viewer manager shows and hides the compare button as the active viewer
  // changes, after this script has already painted. The bar has to follow it.
  document.addEventListener('ahg:compare-repaint', refresh);

  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest ? ev.target.closest('[data-ahg-compare-slug]') : null;
    if (btn) {
      ev.preventDefault();
      var slug = btn.getAttribute('data-ahg-compare-slug');
      var list = read();
      var i = list.indexOf(slug);
      if (i === -1) { list.push(slug); } else { list.splice(i, 1); }
      write(list);
      refresh();
      return;
    }
    if (ev.target.closest && ev.target.closest('.ahg-compare-go')) {
      var picked = read();
      if (picked.length >= 2) {
        window.location = URL_BASE + '?slugs=' + picked.map(encodeURIComponent).join(',');
      }
    }
    if (ev.target.closest && ev.target.closest('.ahg-compare-clear')) {
      write([]);
      refresh();
    }
  });

  var bar = document.createElement('div');
  bar.id = 'ahg-compare-bar';
  bar.className = 'ahg-compare-bar';
  bar.hidden = true;
  bar.innerHTML =
    '<span class="ahg-compare-count"></span>' +
    '<button type="button" class="btn btn-sm btn-primary ahg-compare-go"></button>' +
    '<button type="button" class="btn btn-sm btn-link ahg-compare-clear"></button>';
  document.addEventListener('DOMContentLoaded', function () {
    document.body.appendChild(bar);
    bar.querySelector('.ahg-compare-go').textContent = L.open.replace('%s', '');
    bar.querySelector('.ahg-compare-clear').textContent = L.clear;
    refresh();
  });
})();
JS;

        $block = '<style'.$attr.'>'.$css.'</style>'
            ."\n".'<script id="ahg-iiif-compare-js"'.$attr.'>'.$js.'</script>';

        if (false !== stripos($html, '</body>')) {
            return preg_replace('#</body>#i', $block."\n</body>", $html, 1);
        }

        return $html.$block;
    }

    private static function t(string $s): string
    {
        return function_exists('__') ? __($s) : $s;
    }
}
