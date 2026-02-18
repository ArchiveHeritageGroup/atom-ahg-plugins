<?php

use AtomFramework\Http\Controllers\AhgController;
/*
 * System Information Page
 *
 * Displays installed software versions and system health information
 */

class SettingsSystemInfoAction extends AhgController
{
    public function execute($request)
    {
        // Check authentication
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        // Check admin permission
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        $this->atomRoot = $this->config('sf_root_dir');

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

        // Get metadata export formats
        $this->exportFormats = $this->getMetadataExportFormats();

        // Get DOI statistics
        $this->doiStats = $this->getDoiStatistics();
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
            'AtoM Root' => $this->config('sf_root_dir'),
            'Uploads' => $this->config('sf_upload_dir'),
            'Cache' => $this->config('sf_cache_dir'),
            'Logs' => $this->config('sf_log_dir'),
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
            'OpenSearch' => 'database',
            'Elasticsearch' => 'database',
            'Search Engine' => 'database',
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
            'PyMuPDF' => 'ai',
            'spaCy' => 'ai',
            'Argos Translate' => 'ai',
            'Pillow' => 'ai',
            'OpenCV' => 'ai',
            'GNU Aspell' => 'ai',
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

        // Search Engine (auto-detect Elasticsearch or OpenSearch)
        try {
            $esHost = $this->config('app_elasticsearch_host', 'localhost');
            $esPort = $this->config('app_elasticsearch_port', 9200);

            // Use factory for auto-detection (may not exist on older installs)
            $engineName = 'Search Engine';
            $engineVersion = '';
            if (class_exists('SearchEngineFactory')) {
                $engineName = SearchEngineFactory::getEngineName($esHost, (int) $esPort);
                $engineVersion = SearchEngineFactory::getEngineVersion($esHost, (int) $esPort);
            }

            if ($engineVersion) {
                $versions[] = [
                    'name' => $engineName,
                    'version' => $engineVersion,
                    'icon' => 'bi-search',
                    'status' => 'ok',
                    'path' => "http://{$esHost}:{$esPort}",
                ];
            } else {
                $versions[] = ['name' => 'Search Engine', 'version' => 'Not responding', 'icon' => 'bi-search', 'status' => 'error', 'path' => ''];
            }
        } catch (\Exception $e) {
            $versions[] = ['name' => 'Search Engine', 'version' => 'Error', 'icon' => 'bi-search', 'status' => 'error', 'path' => ''];
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

        // PyMuPDF (fitz) - PDF redaction
        $result = shell_exec('python3 -c "import fitz; print(fitz.version[0])" 2>&1');
        if (preg_match('/^(\d+\.\d+\.?\d*)/', trim($result ?? ''), $matches)) {
            $versions[] = [
                'name' => 'PyMuPDF',
                'version' => $matches[1],
                'icon' => 'bi-file-earmark-pdf',
                'status' => 'ok',
                'path' => 'pip3: pymupdf',
            ];
        } else {
            $versions[] = ['name' => 'PyMuPDF', 'version' => 'Not installed', 'icon' => 'bi-file-earmark-pdf', 'status' => 'warning', 'path' => 'pip3 install pymupdf'];
        }

        // spaCy - NER
        $result = shell_exec('python3 -c "import spacy; print(spacy.__version__)" 2>&1');
        if (preg_match('/^(\d+\.\d+\.?\d*)/', trim($result ?? ''), $matches)) {
            $versions[] = [
                'name' => 'spaCy',
                'version' => $matches[1],
                'icon' => 'bi-diagram-3',
                'status' => 'ok',
                'path' => 'pip3: spacy',
            ];
        } else {
            $versions[] = ['name' => 'spaCy', 'version' => 'Not installed', 'icon' => 'bi-diagram-3', 'status' => 'warning', 'path' => 'pip3 install spacy'];
        }

        // Argos Translate - Machine translation
        $result = shell_exec('python3 -c "import argostranslate; print(argostranslate.__version__)" 2>&1');
        if (preg_match('/^(\d+\.\d+\.?\d*)/', trim($result ?? ''), $matches)) {
            $versions[] = [
                'name' => 'Argos Translate',
                'version' => $matches[1],
                'icon' => 'bi-translate',
                'status' => 'ok',
                'path' => 'pip3: argostranslate',
            ];
        } else {
            $versions[] = ['name' => 'Argos Translate', 'version' => 'Not installed', 'icon' => 'bi-translate', 'status' => 'warning', 'path' => 'pip3 install argostranslate'];
        }

        // Pillow - Image processing
        $result = shell_exec('python3 -c "from PIL import Image; import PIL; print(PIL.__version__)" 2>&1');
        if (preg_match('/^(\d+\.\d+\.?\d*)/', trim($result ?? ''), $matches)) {
            $versions[] = [
                'name' => 'Pillow',
                'version' => $matches[1],
                'icon' => 'bi-image',
                'status' => 'ok',
                'path' => 'pip3: Pillow',
            ];
        } else {
            $versions[] = ['name' => 'Pillow', 'version' => 'Not installed', 'icon' => 'bi-image', 'status' => 'warning', 'path' => 'pip3 install Pillow'];
        }

        // OpenCV - Face detection
        $result = shell_exec('python3 -c "import cv2; print(cv2.__version__)" 2>&1');
        if (preg_match('/^(\d+\.\d+\.?\d*)/', trim($result ?? ''), $matches)) {
            $versions[] = [
                'name' => 'OpenCV',
                'version' => $matches[1],
                'icon' => 'bi-camera',
                'status' => 'ok',
                'path' => 'pip3: opencv-python',
            ];
        } else {
            $versions[] = ['name' => 'OpenCV', 'version' => 'Not installed', 'icon' => 'bi-camera', 'status' => 'warning', 'path' => 'pip3 install opencv-python-headless'];
        }

        // aspell - Spellcheck
        $result = shell_exec('aspell --version 2>&1');
        if (preg_match('/(\d+\.\d+\.?\d*)/', $result ?? '', $matches)) {
            $versions[] = [
                'name' => 'GNU Aspell',
                'version' => $matches[1],
                'icon' => 'bi-spellcheck',
                'status' => 'ok',
                'path' => trim(shell_exec('which aspell 2>/dev/null') ?: ''),
            ];
        } else {
            $versions[] = ['name' => 'GNU Aspell', 'version' => 'Not installed', 'icon' => 'bi-spellcheck', 'status' => 'warning', 'path' => ''];
        }

        // Blender - 3D rendering
        $result = shell_exec('blender --version 2>&1');
        if (preg_match('/Blender (\d+\.\d+\.?\d*)/', $result ?? '', $matches)) {
            $versions[] = [
                'name' => 'Blender',
                'version' => $matches[1],
                'icon' => 'bi-box',
                'status' => 'ok',
                'path' => trim(shell_exec('which blender 2>/dev/null') ?: ''),
            ];
        } else {
            $versions[] = ['name' => 'Blender', 'version' => 'Not installed', 'icon' => 'bi-box', 'status' => 'warning', 'path' => ''];
        }

        // MeshLab - 3D processing
        $result = shell_exec('meshlabserver --version 2>&1 || meshlab --version 2>&1');
        if (preg_match('/(\d+\.\d+\.?\d*)/', $result ?? '', $matches)) {
            $versions[] = [
                'name' => 'MeshLab',
                'version' => $matches[1],
                'icon' => 'bi-grid-3x3',
                'status' => 'ok',
                'path' => trim(shell_exec('which meshlabserver 2>/dev/null || which meshlab 2>/dev/null') ?: ''),
            ];
        } else {
            $versions[] = ['name' => 'MeshLab', 'version' => 'Not installed', 'icon' => 'bi-grid-3x3', 'status' => 'warning', 'path' => ''];
        }

        // Cantaloupe
        try {
            $cantaloupeHost = $this->config('app_cantaloupe_host', 'localhost:8182');
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
        $versionFile = $this->config('sf_root_dir') . '/VERSION';
        if (file_exists($versionFile)) {
            $atomVersion = trim(file_get_contents($versionFile));
        }
        $versions[] = [
            'name' => 'AtoM',
            'version' => $atomVersion,
            'icon' => 'bi-archive',
            'status' => 'ok',
            'path' => $this->config('sf_root_dir'),
        ];

        // Symfony Version
        $versions[] = [
            'name' => 'Symfony',
            'version' => SYMFONY_VERSION,
            'icon' => 'bi-bricks',
            'status' => 'ok',
            'path' => $this->config('sf_symfony_lib_dir'),
        ];

        return $versions;
    }

    /**
     * Get metadata export formats from ahgMetadataExportPlugin
     */
    protected function getMetadataExportFormats(): array
    {
        $formats = [
            [
                'code' => 'ead3',
                'name' => 'EAD3',
                'sector' => 'Archives',
                'output' => 'XML',
                'icon' => 'bi-file-earmark-code',
                'description' => 'Encoded Archival Description v3 for finding aids',
                'status' => 'ok',
            ],
            [
                'code' => 'rico',
                'name' => 'RIC-O',
                'sector' => 'Archives',
                'output' => 'JSON-LD',
                'icon' => 'bi-diagram-3',
                'description' => 'Records in Contexts Ontology (ICA linked data)',
                'status' => 'ok',
            ],
            [
                'code' => 'lido',
                'name' => 'LIDO',
                'sector' => 'Museums',
                'output' => 'XML',
                'icon' => 'bi-bank',
                'description' => 'Lightweight Information Describing Objects',
                'status' => 'ok',
            ],
            [
                'code' => 'marc21',
                'name' => 'MARC21',
                'sector' => 'Libraries',
                'output' => 'XML',
                'icon' => 'bi-book',
                'description' => 'Machine-Readable Cataloging for libraries',
                'status' => 'ok',
            ],
            [
                'code' => 'bibframe',
                'name' => 'BIBFRAME',
                'sector' => 'Libraries',
                'output' => 'JSON-LD',
                'icon' => 'bi-diagram-2',
                'description' => 'Bibliographic Framework (Library of Congress)',
                'status' => 'ok',
            ],
            [
                'code' => 'vra-core',
                'name' => 'VRA Core 4',
                'sector' => 'Visual',
                'output' => 'XML',
                'icon' => 'bi-images',
                'description' => 'Visual Resources Association core categories',
                'status' => 'ok',
            ],
            [
                'code' => 'pbcore',
                'name' => 'PBCore',
                'sector' => 'Media',
                'output' => 'XML',
                'icon' => 'bi-film',
                'description' => 'Public Broadcasting metadata standard',
                'status' => 'ok',
            ],
            [
                'code' => 'ebucore',
                'name' => 'EBUCore',
                'sector' => 'Media',
                'output' => 'XML',
                'icon' => 'bi-broadcast',
                'description' => 'European Broadcasting Union core metadata',
                'status' => 'ok',
            ],
            [
                'code' => 'premis',
                'name' => 'PREMIS',
                'sector' => 'Preservation',
                'output' => 'XML',
                'icon' => 'bi-shield-lock',
                'description' => 'Preservation Metadata Implementation Strategies',
                'status' => 'ok',
            ],
        ];

        // Check if ahgMetadataExportPlugin is enabled
        $pluginEnabled = class_exists('AhgMetadataExport\Services\ExportService');

        if (!$pluginEnabled) {
            foreach ($formats as &$format) {
                $format['status'] = 'warning';
            }
        }

        return [
            'formats' => $formats,
            'pluginEnabled' => $pluginEnabled,
            'command' => 'php symfony metadata:export --list',
        ];
    }

    /**
     * Get DOI statistics from ahgDoiPlugin
     */
    protected function getDoiStatistics(): array
    {
        $stats = [
            'enabled' => false,
            'total' => 0,
            'by_status' => [
                'draft' => 0,
                'registered' => 0,
                'findable' => 0,
                'failed' => 0,
            ],
            'queue_pending' => 0,
            'config' => null,
        ];

        // Check if DOI tables exist
        try {
            $db = \Illuminate\Database\Capsule\Manager::connection();

            // Check if table exists
            if (!$db->getSchemaBuilder()->hasTable('ahg_doi')) {
                return $stats;
            }

            $stats['enabled'] = true;

            // Get DOI counts by status
            $counts = $db->table('ahg_doi')
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                    SUM(CASE WHEN status = 'registered' THEN 1 ELSE 0 END) as registered,
                    SUM(CASE WHEN status = 'findable' THEN 1 ELSE 0 END) as findable,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
                ")
                ->first();

            if ($counts) {
                $stats['total'] = (int) ($counts->total ?? 0);
                $stats['by_status'] = [
                    'draft' => (int) ($counts->draft ?? 0),
                    'registered' => (int) ($counts->registered ?? 0),
                    'findable' => (int) ($counts->findable ?? 0),
                    'failed' => (int) ($counts->failed ?? 0),
                ];
            }

            // Get queue pending count
            if ($db->getSchemaBuilder()->hasTable('ahg_doi_queue')) {
                $stats['queue_pending'] = $db->table('ahg_doi_queue')
                    ->where('status', 'pending')
                    ->count();
            }

            // Get configuration status
            if ($db->getSchemaBuilder()->hasTable('ahg_doi_config')) {
                $config = $db->table('ahg_doi_config')
                    ->where('is_active', 1)
                    ->first();

                if ($config) {
                    $stats['config'] = [
                        'prefix' => $config->datacite_prefix ?? 'Not set',
                        'environment' => $config->environment ?? 'test',
                        'auto_mint' => (bool) ($config->auto_mint ?? false),
                    ];
                }
            }
        } catch (\Exception $e) {
            // DOI plugin not installed or tables don't exist
            return $stats;
        }

        return $stats;
    }
}
