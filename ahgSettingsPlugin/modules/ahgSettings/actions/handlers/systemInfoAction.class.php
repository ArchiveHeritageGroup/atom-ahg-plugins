<?php

/*
 * System Information Page
 *
 * Displays installed software versions and system health information
 */

class SettingsSystemInfoAction extends sfAction
{
    public function execute($request)
    {
        // Check authentication
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('user/login');
        }

        // Check admin permission
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        $this->atomRoot = sfConfig::get('sf_root_dir');

        // Get software versions
        $this->softwareVersions = $this->getSoftwareVersions();

        // Group software by category
        $this->softwareCategories = $this->groupSoftwareByCategory($this->softwareVersions);

        // Get system information
        $this->systemInfo = $this->getSystemInfo();

        // Get PHP extensions
        $this->phpExtensions = $this->getPhpExtensions();

        // Get disk usage
        $this->diskUsage = $this->getDiskUsage();
    }

    protected function getSystemInfo(): array
    {
        return [
            'hostname' => gethostname(),
            'os' => php_uname('s') . ' ' . php_uname('r'),
            'architecture' => php_uname('m'),
            'php_sapi' => php_sapi_name(),
            'php_memory_limit' => ini_get('memory_limit'),
            'php_max_execution_time' => ini_get('max_execution_time') . 's',
            'php_upload_max_filesize' => ini_get('upload_max_filesize'),
            'php_post_max_size' => ini_get('post_max_size'),
            'php_timezone' => date_default_timezone_get(),
            'server_time' => date('Y-m-d H:i:s T'),
            'uptime' => $this->getUptime(),
            'load_average' => $this->getLoadAverage(),
        ];
    }

    protected function getUptime(): string
    {
        $uptime = @file_get_contents('/proc/uptime');
        if ($uptime) {
            $seconds = (int) explode(' ', $uptime)[0];
            $days = floor($seconds / 86400);
            $hours = floor(($seconds % 86400) / 3600);
            $minutes = floor(($seconds % 3600) / 60);

            return "{$days}d {$hours}h {$minutes}m";
        }

        return 'N/A';
    }

    protected function getLoadAverage(): string
    {
        $load = sys_getloadavg();
        if ($load) {
            return sprintf('%.2f, %.2f, %.2f', $load[0], $load[1], $load[2]);
        }

        return 'N/A';
    }

    protected function getPhpExtensions(): array
    {
        $required = [
            'curl' => 'HTTP requests',
            'gd' => 'Image processing',
            'mbstring' => 'Multi-byte string handling',
            'xml' => 'XML processing',
            'json' => 'JSON encoding/decoding',
            'pdo' => 'Database abstraction',
            'pdo_mysql' => 'MySQL database driver',
            'openssl' => 'SSL/TLS encryption',
            'zip' => 'ZIP archive handling',
            'intl' => 'Internationalization',
            'xsl' => 'XSL transformations',
            'fileinfo' => 'File type detection',
            'opcache' => 'PHP opcode caching',
            'apcu' => 'User data caching',
            'redis' => 'Redis caching (optional)',
            'memcached' => 'Memcached caching (optional)',
            'imagick' => 'Advanced image processing (optional)',
        ];

        $extensions = [];
        foreach ($required as $ext => $desc) {
            $extensions[] = [
                'name' => $ext,
                'description' => $desc,
                'loaded' => extension_loaded($ext),
                'version' => extension_loaded($ext) ? (phpversion($ext) ?: 'loaded') : 'not loaded',
            ];
        }

        return $extensions;
    }

    protected function getDiskUsage(): array
    {
        $paths = [
            'AtoM Root' => sfConfig::get('sf_root_dir'),
            'Uploads' => sfConfig::get('sf_upload_dir'),
            'Cache' => sfConfig::get('sf_cache_dir'),
            'Logs' => sfConfig::get('sf_log_dir'),
        ];

        $usage = [];
        foreach ($paths as $label => $path) {
            if (is_dir($path)) {
                $total = @disk_total_space($path);
                $free = @disk_free_space($path);
                if ($total && $free) {
                    $used = $total - $free;
                    $usage[] = [
                        'label' => $label,
                        'path' => $path,
                        'total' => $this->formatBytes($total),
                        'used' => $this->formatBytes($used),
                        'free' => $this->formatBytes($free),
                        'percent' => round(($used / $total) * 100, 1),
                    ];
                }
            }
        }

        return $usage;
    }

    protected function formatBytes($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < 4) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    protected function groupSoftwareByCategory(array $software): array
    {
        $categories = [
            'core' => ['title' => 'Core Infrastructure', 'icon' => 'bi-server', 'items' => []],
            'database' => ['title' => 'Database & Search', 'icon' => 'bi-database', 'items' => []],
            'media' => ['title' => 'Media Processing', 'icon' => 'bi-film', 'items' => []],
            'preservation' => ['title' => 'Digital Preservation', 'icon' => 'bi-shield-lock', 'items' => []],
            '3d' => ['title' => '3D Processing', 'icon' => 'bi-box', 'items' => []],
            'ai' => ['title' => 'AI & Machine Learning', 'icon' => 'bi-robot', 'items' => []],
            'development' => ['title' => 'Development Tools', 'icon' => 'bi-code-slash', 'items' => []],
        ];

        $categoryMap = [
            'PHP' => 'core',
            'Nginx' => 'core',
            'AtoM' => 'core',
            'Symfony' => 'core',
            'MySQL' => 'database',
            'Elasticsearch' => 'database',
            'Redis' => 'database',
            'Memcached' => 'database',
            'Gearman' => 'core',
            'ImageMagick' => 'media',
            'FFmpeg' => 'media',
            'Ghostscript' => 'media',
            'Poppler Utils' => 'media',
            'Tesseract OCR' => 'media',
            'Cantaloupe' => 'media',
            'Siegfried' => 'preservation',
            'ClamAV' => 'preservation',
            'BagIt (Python)' => 'preservation',
            'Blender' => '3d',
            'MeshLab' => '3d',
            'MeshLab Server' => '3d',
            'OpenSCAD' => '3d',
            'Assimp' => '3d',
            'F3D Viewer' => '3d',
            'glTF Pipeline' => '3d',
            'obj2gltf' => '3d',
            'VTK (Python)' => '3d',
            'Potree Converter' => '3d',
            'Python' => 'ai',
            'Node.js' => 'development',
            'Composer' => 'development',
            'Git' => 'development',
        ];

        foreach ($software as $item) {
            $cat = $categoryMap[$item['name']] ?? 'core';
            if (isset($categories[$cat])) {
                $categories[$cat]['items'][] = $item;
            }
        }

        // Remove empty categories
        return array_filter($categories, fn ($cat) => !empty($cat['items']));
    }

    protected function getSoftwareVersions(): array
    {
        $versions = [];

        // PHP
        $versions[] = [
            'name' => 'PHP',
            'version' => PHP_VERSION,
            'icon' => 'bi-filetype-php',
            'status' => version_compare(PHP_VERSION, '8.0.0', '>=') ? 'ok' : 'warning',
            'path' => PHP_BINARY,
        ];

        // MySQL
        try {
            $result = shell_exec('mysql --version 2>&1');
            if (preg_match('/(\d+\.\d+\.\d+)/', $result, $matches)) {
                $versions[] = [
                    'name' => 'MySQL',
                    'version' => $matches[1],
                    'icon' => 'bi-database',
                    'status' => version_compare($matches[1], '8.0.0', '>=') ? 'ok' : 'warning',
                    'path' => trim(shell_exec('which mysql 2>/dev/null') ?: ''),
                ];
            }
        } catch (\Exception $e) {
            $versions[] = ['name' => 'MySQL', 'version' => 'Not found', 'icon' => 'bi-database', 'status' => 'error', 'path' => ''];
        }

        // Elasticsearch
        try {
            $esHost = sfConfig::get('app_elasticsearch_host', 'localhost:9200');
            $ch = curl_init("http://{$esHost}");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 2,
            ]);
            $response = curl_exec($ch);
            curl_close($ch);
            if ($response && $data = json_decode($response, true)) {
                $esVersion = $data['version']['number'] ?? 'Unknown';
                $versions[] = [
                    'name' => 'Elasticsearch',
                    'version' => $esVersion,
                    'icon' => 'bi-search',
                    'status' => 'ok',
                    'path' => "http://{$esHost}",
                ];
            } else {
                $versions[] = ['name' => 'Elasticsearch', 'version' => 'Not responding', 'icon' => 'bi-search', 'status' => 'error', 'path' => ''];
            }
        } catch (\Exception $e) {
            $versions[] = ['name' => 'Elasticsearch', 'version' => 'Error', 'icon' => 'bi-search', 'status' => 'error', 'path' => ''];
        }

        // Nginx
        $result = shell_exec('nginx -v 2>&1');
        if (preg_match('/nginx\/(\d+\.\d+\.\d+)/', $result, $matches)) {
            $versions[] = [
                'name' => 'Nginx',
                'version' => $matches[1],
                'icon' => 'bi-hdd-network',
                'status' => 'ok',
                'path' => trim(shell_exec('which nginx 2>/dev/null') ?: ''),
            ];
        }

        // ImageMagick
        $result = shell_exec('convert --version 2>&1');
        if (preg_match('/ImageMagick (\d+\.\d+\.\d+-\d+|\d+\.\d+\.\d+)/', $result, $matches)) {
            $versions[] = [
                'name' => 'ImageMagick',
                'version' => $matches[1],
                'icon' => 'bi-image',
                'status' => 'ok',
                'path' => trim(shell_exec('which convert 2>/dev/null') ?: ''),
            ];
        } else {
            $versions[] = ['name' => 'ImageMagick', 'version' => 'Not installed', 'icon' => 'bi-image', 'status' => 'warning', 'path' => ''];
        }

        // FFmpeg
        $result = shell_exec('ffmpeg -version 2>&1');
        if (preg_match('/ffmpeg version (\d+\.\d+\.?\d*|[\d\.]+)/', $result, $matches)) {
            $versions[] = [
                'name' => 'FFmpeg',
                'version' => $matches[1],
                'icon' => 'bi-film',
                'status' => 'ok',
                'path' => trim(shell_exec('which ffmpeg 2>/dev/null') ?: ''),
            ];
        } else {
            $versions[] = ['name' => 'FFmpeg', 'version' => 'Not installed', 'icon' => 'bi-film', 'status' => 'warning', 'path' => ''];
        }

        // Ghostscript
        $result = shell_exec('gs --version 2>&1');
        if (preg_match('/(\d+\.\d+\.?\d*)/', $result, $matches)) {
            $versions[] = [
                'name' => 'Ghostscript',
                'version' => $matches[1],
                'icon' => 'bi-file-earmark-pdf',
                'status' => 'ok',
                'path' => trim(shell_exec('which gs 2>/dev/null') ?: ''),
            ];
        } else {
            $versions[] = ['name' => 'Ghostscript', 'version' => 'Not installed', 'icon' => 'bi-file-earmark-pdf', 'status' => 'warning', 'path' => ''];
        }

        // Gearman
        $result = shell_exec('gearmand --version 2>&1');
        if (preg_match('/gearmand (\d+\.\d+\.?\d*)/', $result, $matches)) {
            $versions[] = [
                'name' => 'Gearman',
                'version' => $matches[1],
                'icon' => 'bi-gear',
                'status' => 'ok',
                'path' => trim(shell_exec('which gearmand 2>/dev/null') ?: ''),
            ];
        } else {
            $versions[] = ['name' => 'Gearman', 'version' => 'Not installed', 'icon' => 'bi-gear', 'status' => 'warning', 'path' => ''];
        }

        // Redis
        $result = shell_exec('redis-server --version 2>&1');
        if (preg_match('/v=(\d+\.\d+\.?\d*)/', $result, $matches)) {
            $versions[] = [
                'name' => 'Redis',
                'version' => $matches[1],
                'icon' => 'bi-memory',
                'status' => 'ok',
                'path' => trim(shell_exec('which redis-server 2>/dev/null') ?: ''),
            ];
        } else {
            $versions[] = ['name' => 'Redis', 'version' => 'Not installed', 'icon' => 'bi-memory', 'status' => 'warning', 'path' => ''];
        }

        // Memcached
        $result = shell_exec('memcached -V 2>&1');
        if (preg_match('/memcached (\d+\.\d+\.?\d*)/', $result, $matches)) {
            $versions[] = [
                'name' => 'Memcached',
                'version' => $matches[1],
                'icon' => 'bi-hdd-stack',
                'status' => 'ok',
                'path' => trim(shell_exec('which memcached 2>/dev/null') ?: ''),
            ];
        } else {
            $versions[] = ['name' => 'Memcached', 'version' => 'Not installed', 'icon' => 'bi-hdd-stack', 'status' => 'warning', 'path' => ''];
        }

        // poppler-utils (pdftotext)
        $result = shell_exec('pdftotext -v 2>&1');
        if (preg_match('/pdftotext version (\d+\.\d+\.?\d*)/', $result, $matches)) {
            $versions[] = [
                'name' => 'Poppler Utils',
                'version' => $matches[1],
                'icon' => 'bi-file-pdf',
                'status' => 'ok',
                'path' => trim(shell_exec('which pdftotext 2>/dev/null') ?: ''),
            ];
        } else {
            $versions[] = ['name' => 'Poppler Utils', 'version' => 'Not installed', 'icon' => 'bi-file-pdf', 'status' => 'warning', 'path' => ''];
        }

        // Tesseract OCR
        $result = shell_exec('tesseract --version 2>&1');
        if (preg_match('/tesseract (\d+\.\d+\.?\d*)/', $result, $matches)) {
            $versions[] = [
                'name' => 'Tesseract OCR',
                'version' => $matches[1],
                'icon' => 'bi-type',
                'status' => 'ok',
                'path' => trim(shell_exec('which tesseract 2>/dev/null') ?: ''),
            ];
        } else {
            $versions[] = ['name' => 'Tesseract OCR', 'version' => 'Not installed', 'icon' => 'bi-type', 'status' => 'warning', 'path' => ''];
        }

        // Node.js
        $result = shell_exec('node --version 2>&1');
        if (preg_match('/v(\d+\.\d+\.?\d*)/', $result, $matches)) {
            $versions[] = [
                'name' => 'Node.js',
                'version' => $matches[1],
                'icon' => 'bi-filetype-js',
                'status' => 'ok',
                'path' => trim(shell_exec('which node 2>/dev/null') ?: ''),
            ];
        } else {
            $versions[] = ['name' => 'Node.js', 'version' => 'Not installed', 'icon' => 'bi-filetype-js', 'status' => 'warning', 'path' => ''];
        }

        // Python
        $result = shell_exec('python3 --version 2>&1');
        if (preg_match('/Python (\d+\.\d+\.?\d*)/', $result, $matches)) {
            $versions[] = [
                'name' => 'Python',
                'version' => $matches[1],
                'icon' => 'bi-filetype-py',
                'status' => 'ok',
                'path' => trim(shell_exec('which python3 2>/dev/null') ?: ''),
            ];
        } else {
            $versions[] = ['name' => 'Python', 'version' => 'Not installed', 'icon' => 'bi-filetype-py', 'status' => 'warning', 'path' => ''];
        }

        // Composer
        $result = shell_exec('composer --version 2>&1');
        if (preg_match('/Composer version (\d+\.\d+\.?\d*)/', $result, $matches)) {
            $versions[] = [
                'name' => 'Composer',
                'version' => $matches[1],
                'icon' => 'bi-box',
                'status' => 'ok',
                'path' => trim(shell_exec('which composer 2>/dev/null') ?: ''),
            ];
        } else {
            $versions[] = ['name' => 'Composer', 'version' => 'Not installed', 'icon' => 'bi-box', 'status' => 'warning', 'path' => ''];
        }

        // Git
        $result = shell_exec('git --version 2>&1');
        if (preg_match('/git version (\d+\.\d+\.?\d*)/', $result, $matches)) {
            $versions[] = [
                'name' => 'Git',
                'version' => $matches[1],
                'icon' => 'bi-git',
                'status' => 'ok',
                'path' => trim(shell_exec('which git 2>/dev/null') ?: ''),
            ];
        }

        // Preservation tools
        $result = shell_exec('sf -version 2>&1');
        if (preg_match('/siegfried (\d+\.\d+\.?\d*)/', $result, $matches)) {
            $versions[] = [
                'name' => 'Siegfried',
                'version' => $matches[1],
                'icon' => 'bi-file-earmark-check',
                'status' => 'ok',
                'path' => trim(shell_exec('which sf 2>/dev/null') ?: ''),
            ];
        } else {
            $versions[] = ['name' => 'Siegfried', 'version' => 'Not installed', 'icon' => 'bi-file-earmark-check', 'status' => 'warning', 'path' => ''];
        }

        // ClamAV
        $result = shell_exec('clamscan --version 2>&1');
        if (preg_match('/ClamAV (\d+\.\d+\.?\d*)/', $result, $matches)) {
            $versions[] = [
                'name' => 'ClamAV',
                'version' => $matches[1],
                'icon' => 'bi-shield-check',
                'status' => 'ok',
                'path' => trim(shell_exec('which clamscan 2>/dev/null') ?: ''),
            ];
        } else {
            $versions[] = ['name' => 'ClamAV', 'version' => 'Not installed', 'icon' => 'bi-shield-check', 'status' => 'warning', 'path' => ''];
        }

        // BagIt
        $result = shell_exec('python3 -c "import bagit; print(bagit.VERSION)" 2>&1');
        if (preg_match('/(\d+\.\d+\.?\d*)/', $result, $matches)) {
            $versions[] = [
                'name' => 'BagIt (Python)',
                'version' => $matches[1],
                'icon' => 'bi-bag-check',
                'status' => 'ok',
                'path' => 'python3 -c "import bagit"',
            ];
        } else {
            $versions[] = ['name' => 'BagIt (Python)', 'version' => 'Not installed', 'icon' => 'bi-bag-check', 'status' => 'warning', 'path' => ''];
        }

        // Cantaloupe
        try {
            $cantaloupeHost = sfConfig::get('app_cantaloupe_host', 'localhost:8182');
            $ch = curl_init("http://{$cantaloupeHost}/iiif/2");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 2,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 400) {
                $ch2 = curl_init("http://{$cantaloupeHost}/status");
                curl_setopt_array($ch2, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 2,
                ]);
                $statusResponse = curl_exec($ch2);
                curl_close($ch2);

                $version = 'Running';
                if ($statusResponse && $data = json_decode($statusResponse, true)) {
                    $version = $data['applicationVersion'] ?? 'Running';
                }

                $versions[] = [
                    'name' => 'Cantaloupe',
                    'version' => $version,
                    'icon' => 'bi-images',
                    'status' => 'ok',
                    'path' => "http://{$cantaloupeHost}",
                ];
            } else {
                $versions[] = ['name' => 'Cantaloupe', 'version' => 'Not responding', 'icon' => 'bi-images', 'status' => 'error', 'path' => "http://{$cantaloupeHost}"];
            }
        } catch (\Exception $e) {
            $versions[] = ['name' => 'Cantaloupe', 'version' => 'Not configured', 'icon' => 'bi-images', 'status' => 'warning', 'path' => ''];
        }

        // Ollama (LLM)
        try {
            $ollamaHost = 'localhost:11434';
            $ch = curl_init("http://{$ollamaHost}/api/tags");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 2,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                $modelCount = count($data['models'] ?? []);
                $versions[] = [
                    'name' => 'Ollama',
                    'version' => "Running ({$modelCount} models)",
                    'icon' => 'bi-robot',
                    'status' => 'ok',
                    'path' => "http://{$ollamaHost}",
                ];
            } else {
                $versions[] = ['name' => 'Ollama', 'version' => 'Not responding', 'icon' => 'bi-robot', 'status' => 'warning', 'path' => ''];
            }
        } catch (\Exception $e) {
            $versions[] = ['name' => 'Ollama', 'version' => 'Not configured', 'icon' => 'bi-robot', 'status' => 'warning', 'path' => ''];
        }

        // AtoM Version
        $atomVersion = 'Unknown';
        $versionFile = sfConfig::get('sf_root_dir') . '/VERSION';
        if (file_exists($versionFile)) {
            $atomVersion = trim(file_get_contents($versionFile));
        }
        $versions[] = [
            'name' => 'AtoM',
            'version' => $atomVersion,
            'icon' => 'bi-archive',
            'status' => 'ok',
            'path' => sfConfig::get('sf_root_dir'),
        ];

        // Symfony Version
        $versions[] = [
            'name' => 'Symfony',
            'version' => SYMFONY_VERSION,
            'icon' => 'bi-bricks',
            'status' => 'ok',
            'path' => sfConfig::get('sf_symfony_lib_dir'),
        ];

        return $versions;
    }
}
