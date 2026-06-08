<?php

/**
 * ahgFtpPlugin configuration.
 *
 * Browser-based FTP/SFTP upload for CSV import digital objects.
 */
class ahgFtpPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'FTP/SFTP upload for CSV import digital objects';
    public static $version = '1.0.0';

    public function contextLoadFactories(sfEvent $event)
    {
        $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($frameworkPath)) {
            require_once $frameworkPath;
        }
    }

    public function initialize()
    {
        $this->dispatcher->connect('context.load_factories', [$this, 'contextLoadFactories']);
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);
        $this->dispatcher->connect('response.filter_content', [$this, 'injectFtpPicker']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'ftpUpload';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }

    /**
     * Inject FTP file picker on addDigitalObject pages.
     */
    public function injectFtpPicker(sfEvent $event, $content)
    {
        // Only inject on addDigitalObject pages
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (stripos($uri, 'addDigitalObject') === false) {
            return $content;
        }

        // Only for authenticated users
        $context = sfContext::getInstance();
        if (!$context->getUser()->isAuthenticated()) {
            return $content;
        }

        $nonce = sfConfig::get('csp_nonce', '');
        $nonceAttr = $nonce ? preg_replace('/^nonce=/', 'nonce="', $nonce) . '"' : '';
        $listUrl = $context->getRouting()->generate('ftp_upload_list');
        $importUrl = $context->getRouting()->generate('ftp_upload_import');

        // Extract the resource slug from the URL
        // Formats: /{slug}/addDigitalObject or /{slug}/object/addDigitalObject
        $resourceSlug = '';
        if (preg_match('#/([^/]+)/object/addDigitalObject#i', $uri, $m)) {
            $resourceSlug = $m[1];
        } elseif (preg_match('#/([^/]+)/addDigitalObject#i', $uri, $m)) {
            $resourceSlug = $m[1];
        }
        $resourceSlugJs = json_encode($resourceSlug);

        $script = <<<FTPJS
<script {$nonceAttr}>
(function(){
    'use strict';
    var accordion = document.querySelector('#uploadForm .accordion');
    if (!accordion) return;

    var item = document.createElement('div');
    item.className = 'accordion-item';
    item.innerHTML =
        '<h2 class="accordion-header" id="ftp-heading">' +
            '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ftp-collapse" aria-expanded="false" aria-controls="ftp-collapse">' +
                '<i class="fa fa-upload me-2"></i>Select from FTP/SFTP server' +
            '</button>' +
        '</h2>' +
        '<div id="ftp-collapse" class="accordion-collapse collapse" aria-labelledby="ftp-heading">' +
            '<div class="accordion-body" id="ftp-file-list">' +
                '<div class="text-center py-3"><span class="spinner-border spinner-border-sm"></span> Loading remote files...</div>' +
            '</div>' +
        '</div>';
    accordion.appendChild(item);

    // Load files when panel opens
    var loaded = false;
    document.getElementById('ftp-heading').addEventListener('click', function() {
        if (loaded) return;
        loaded = true;
        fetch('{$listUrl}')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var container = document.getElementById('ftp-file-list');
                if (!data.success || !data.files || data.files.length === 0) {
                    container.innerHTML = '<div class="alert alert-info mb-0"><i class="fa fa-info-circle me-2"></i>No files on FTP server. <a href="/index.php/ftp-upload">Upload files first</a>.</div>';
                    return;
                }
                // Build a type filter from the files' extensions.
                var extCounts = {};
                data.files.forEach(function(f) { var e = (f.name.split('.').pop() || '').toLowerCase(); extCounts[e] = (extCounts[e] || 0) + 1; });
                var filterOpts = '<option value="">All types (' + data.files.length + ')</option>';
                Object.keys(extCounts).sort().forEach(function(e) { filterOpts += '<option value="' + escapeAttr(e) + '">.' + escapeHtml(e) + ' (' + extCounts[e] + ')</option>'; });

                var html = '<p class="text-muted small mb-2">Select a file to upload as a digital object (with thumbnails and derivatives).</p>';
                html += '<div class="mb-2"><label class="form-label small mb-1">Filter by type</label><select class="form-select form-select-sm ftp-type-filter" style="max-width:260px"><\/select></div>';
                html += '<div class="list-group ftp-file-list-group">';
                data.files.forEach(function(f) {
                    var size = formatBytes(f.size);
                    var ext = (f.name.split('.').pop() || '').toLowerCase();
                    html += '<a href="#" class="list-group-item list-group-item-action ftp-select-file" data-filename="' + escapeAttr(f.name) + '" data-ext="' + escapeAttr(ext) + '">' +
                        '<div class="d-flex justify-content-between align-items-center">' +
                            '<span><i class="fa fa-file me-2 text-muted"></i><strong>' + escapeHtml(f.name) + '</strong></span>' +
                            '<span class="badge bg-secondary">' + size + '</span>' +
                        '</div>' +
                    '</a>';
                });
                html += '</div>';
                container.innerHTML = html;

                // Populate + wire the type filter.
                var filterSel = container.querySelector('.ftp-type-filter');
                if (filterSel) {
                    filterSel.innerHTML = filterOpts;
                    filterSel.addEventListener('change', function() {
                        var v = this.value;
                        container.querySelectorAll('.ftp-select-file').forEach(function(it) {
                            it.style.display = (!v || it.getAttribute('data-ext') === v) ? '' : 'none';
                        });
                    });
                }

                container.querySelectorAll('.ftp-select-file').forEach(function(el) {
                    el.addEventListener('click', function(e) {
                        e.preventDefault();
                        var filename = this.getAttribute('data-filename');
                        var slug = {$resourceSlugJs};

                        if (!slug) {
                            alert('Cannot determine resource. Please use the normal upload instead.');
                            return;
                        }

                        // Highlight selected
                        container.querySelectorAll('.ftp-select-file').forEach(function(s) { s.classList.remove('active'); });
                        this.classList.add('active');

                        // Show uploading state
                        var origHtml = this.innerHTML;
                        this.innerHTML = '<div class="d-flex align-items-center"><span class="spinner-border spinner-border-sm me-2"></span><strong>Importing ' + escapeHtml(filename) + '...</strong> <span class="text-muted ms-2">Creating derivatives</span></div>';
                        this.style.pointerEvents = 'none';

                        // Disable all other file links
                        container.querySelectorAll('.ftp-select-file').forEach(function(s) { s.style.pointerEvents = 'none'; s.style.opacity = '0.5'; });
                        this.style.opacity = '1';

                        // POST to import endpoint
                        fetch('{$importUrl}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({ filename: filename, slug: slug })
                        })
                        .then(function(r) { return r.json(); })
                        .then(function(result) {
                            if (result.success && result.redirect) {
                                window.location.href = result.redirect;
                            } else {
                                alert('Import failed: ' + (result.message || 'Unknown error'));
                                // Restore links
                                container.querySelectorAll('.ftp-select-file').forEach(function(s) { s.style.pointerEvents = ''; s.style.opacity = ''; });
                                el.innerHTML = origHtml;
                            }
                        })
                        .catch(function(err) {
                            alert('Import failed: ' + err.message);
                            container.querySelectorAll('.ftp-select-file').forEach(function(s) { s.style.pointerEvents = ''; s.style.opacity = ''; });
                            el.innerHTML = origHtml;
                        });
                    });
                });
            })
            .catch(function() {
                document.getElementById('ftp-file-list').innerHTML = '<div class="alert alert-danger mb-0">Failed to load FTP files.</div>';
            });
    });

    function escapeHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function escapeAttr(s) { return s.replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }
    function formatBytes(b) { if(!b)return '0 B'; var k=1024,s=['B','KB','MB','GB']; var i=Math.floor(Math.log(b)/Math.log(k)); return parseFloat((b/Math.pow(k,i)).toFixed(1))+' '+s[i]; }
})();
</script>
FTPJS;

        return str_replace('</body>', $script . '</body>', $content);
    }

    public function addRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        $r = new \AtomFramework\Routing\RouteLoader('ftpUpload');

        $r->any('ftp_upload_index', '/ftp-upload', 'index');
        $r->any('ftp_upload_do', '/ftp-upload/upload', 'upload');
        $r->any('ftp_upload_chunk', '/ftp-upload/chunk', 'uploadChunk');
        $r->any('ftp_upload_list', '/ftp-upload/list', 'listFiles');
        $r->any('ftp_upload_delete', '/ftp-upload/delete', 'deleteFile');
        $r->any('ftp_upload_import', '/ftp-upload/import-as-upload', 'importAsUpload');

        $r->register($routing);
    }

    public static function getPluginInfo()
    {
        return [
            'name' => 'FTP / SFTP Upload',
            'version' => self::$version,
            'description' => self::$summary,
            'author' => 'The Archive and Heritage Group (Pty) Ltd',
            'features' => [
                'Browser-based drag-and-drop file upload',
                'FTP and SFTP protocol support',
                'Remote file listing and management',
                'CSV digitalObjectPath guidance',
            ],
        ];
    }
}
