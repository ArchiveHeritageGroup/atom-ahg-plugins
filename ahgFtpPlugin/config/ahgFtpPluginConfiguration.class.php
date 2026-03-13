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

        // Get disk path for display
        $diskPath = '';
        try {
            $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
            if (file_exists($frameworkPath)) {
                require_once $frameworkPath;
            }
            $row = \Illuminate\Database\Capsule\Manager::table('ahg_settings')
                ->where('setting_key', 'ftp_disk_path')
                ->value('setting_value');
            $diskPath = $row ?: '';
        } catch (\Exception $e) {
            // Ignore
        }

        $diskPathJs = json_encode(rtrim($diskPath, '/'));

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
                var html = '<p class="text-muted small mb-2">Select a file to link as a digital object. The file path will be set automatically.</p>';
                html += '<div class="list-group">';
                data.files.forEach(function(f) {
                    var size = formatBytes(f.size);
                    html += '<a href="#" class="list-group-item list-group-item-action ftp-select-file" data-filename="' + escapeAttr(f.name) + '">' +
                        '<div class="d-flex justify-content-between align-items-center">' +
                            '<span><i class="fa fa-file me-2 text-muted"></i><strong>' + escapeHtml(f.name) + '</strong></span>' +
                            '<span class="badge bg-secondary">' + size + '</span>' +
                        '</div>' +
                    '</a>';
                });
                html += '</div>';
                container.innerHTML = html;

                container.querySelectorAll('.ftp-select-file').forEach(function(el) {
                    el.addEventListener('click', function(e) {
                        e.preventDefault();
                        var filename = this.getAttribute('data-filename');
                        var fullPath = {$diskPathJs} + '/' + filename;

                        // Set the URL field and expand its accordion
                        var urlField = document.querySelector('input[name="url"]') || document.querySelector('#external-collapse input[type="text"]');
                        if (urlField) {
                            urlField.value = fullPath;
                            // Expand the external link accordion
                            var extCollapse = document.getElementById('external-collapse');
                            if (extCollapse && !extCollapse.classList.contains('show')) {
                                var bsCollapse = new bootstrap.Collapse(extCollapse, {show: true});
                            }
                            // Close FTP accordion
                            var ftpCollapse = document.getElementById('ftp-collapse');
                            if (ftpCollapse) {
                                var bsFtp = bootstrap.Collapse.getInstance(ftpCollapse);
                                if (bsFtp) bsFtp.hide();
                            }
                        }

                        // Highlight selected
                        container.querySelectorAll('.ftp-select-file').forEach(function(s) { s.classList.remove('active'); });
                        this.classList.add('active');
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
