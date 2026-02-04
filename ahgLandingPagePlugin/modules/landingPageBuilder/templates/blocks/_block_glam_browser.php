<?php
/**
 * GLAM Browser Block - AJAX Version
 *
 * Renders a placeholder that loads the full GLAM browse interface
 * via AJAX to prevent PHP timeouts on heavy queries.
 */
$blockId = 'glam-browser-' . ($block->id ?? uniqid());
$browseUrl = url_for('@glam_browse_ajax');
$defaultView = $config['default_view'] ?? 'card';
$defaultLimit = $config['items_per_page'] ?? 10;
$showSidebar = $config['show_sidebar'] ?? true;
?>

<div id="<?php echo $blockId ?>" class="glam-browser-block"
     data-browse-url="<?php echo $browseUrl ?>"
     data-default-view="<?php echo $defaultView ?>"
     data-default-limit="<?php echo $defaultLimit ?>"
     data-show-sidebar="<?php echo $showSidebar ? '1' : '0' ?>">

  <!-- Loading state -->
  <div class="glam-browser-loading text-center py-5">
    <div class="spinner-border text-success mb-3" role="status">
      <span class="visually-hidden">Loading...</span>
    </div>
    <p class="text-muted">Loading collections...</p>
  </div>

  <!-- Content loaded via AJAX -->
  <div class="glam-browser-content" style="display: none;"></div>

  <!-- Error state -->
  <div class="glam-browser-error" style="display: none;">
    <div class="alert alert-warning">
      <i class="fas fa-exclamation-triangle me-2"></i>
      Unable to load browse interface.
      <a href="<?php echo url_for(['module' => 'display', 'action' => 'browse']) ?>" class="alert-link">
        Click here to browse collections
      </a>
    </div>
  </div>
</div>

<?php $cspNonce = sfConfig::get('csp_nonce', ''); ?>
<script <?php echo $cspNonce ? preg_replace('/^nonce=/', 'nonce="', $cspNonce) . '"' : ''; ?>>
(function() {
  var container = document.getElementById('<?php echo $blockId ?>');
  if (!container) return;

  var browseUrl = container.dataset.browseUrl;
  var loading = container.querySelector('.glam-browser-loading');
  var content = container.querySelector('.glam-browser-content');
  var error = container.querySelector('.glam-browser-error');

  // Get current URL params to pass through
  var urlParams = new URLSearchParams(window.location.search);
  var params = new URLSearchParams();

  // Pass through filter params
  ['type', 'level', 'repo', 'subject', 'place', 'creator', 'genre', 'media',
   'hasDigital', 'parent', 'view', 'limit', 'sort', 'dir', 'page', 'query', 'topLevel'].forEach(function(key) {
    if (urlParams.has(key)) {
      params.set(key, urlParams.get(key));
    }
  });

  // Set defaults from block config
  if (!params.has('view')) params.set('view', container.dataset.defaultView);
  if (!params.has('limit')) params.set('limit', container.dataset.defaultLimit);
  params.set('showSidebar', container.dataset.showSidebar);
  params.set('embedded', '1');

  var fetchUrl = browseUrl + '?' + params.toString();

  fetch(fetchUrl)
    .then(function(response) {
      if (!response.ok) throw new Error('Network response was not ok');
      return response.text();
    })
    .then(function(html) {
      loading.style.display = 'none';
      content.innerHTML = html;
      content.style.display = 'block';

      // Update links to work within the page
      content.querySelectorAll('a[href]').forEach(function(link) {
        var href = link.getAttribute('href');
        // Keep links that go to detail pages as-is
        if (href.includes('/informationobject/') || href.includes('/slug/')) return;
        // Update browse links to reload the block
        if (href.includes('display/browse') || href.includes('type=') || href.includes('level=')) {
          link.addEventListener('click', function(e) {
            e.preventDefault();
            // Update URL and reload block
            var newUrl = new URL(href, window.location.origin);
            history.pushState({}, '', newUrl.pathname + newUrl.search);
            loadBrowseContent(newUrl.search);
          });
        }
      });
    })
    .catch(function(err) {
      console.error('GLAM Browser load error:', err);
      loading.style.display = 'none';
      error.style.display = 'block';
    });

  function loadBrowseContent(search) {
    loading.style.display = 'block';
    content.style.display = 'none';
    error.style.display = 'none';

    var params = new URLSearchParams(search);
    params.set('showSidebar', container.dataset.showSidebar);
    params.set('embedded', '1');

    fetch(browseUrl + '?' + params.toString())
      .then(function(response) { return response.text(); })
      .then(function(html) {
        loading.style.display = 'none';
        content.innerHTML = html;
        content.style.display = 'block';
      })
      .catch(function() {
        loading.style.display = 'none';
        error.style.display = 'block';
      });
  }

  // Handle browser back/forward
  window.addEventListener('popstate', function() {
    loadBrowseContent(window.location.search);
  });
})();
</script>
