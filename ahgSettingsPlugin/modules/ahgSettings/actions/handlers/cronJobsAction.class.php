<?php

use AtomFramework\Http\Controllers\AhgController;
/*
 * Cron Jobs Information Page
 *
 * Displays all available cron jobs with explanations and scheduling examples
 */

class SettingsCronJobsAction extends AhgController
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

        // Define all cron jobs with explanations
        $this->cronJobs = $this->getAllCronJobs();

        // Group by category
        $this->categories = $this->groupByCategory($this->cronJobs);
    }

    protected function _unusedGetSoftwareVersions(): array
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
            $esHost = $this->config('app_elasticsearch_host', 'localhost:9200');
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

        // ============================================
        // PRESERVATION SOFTWARE
        // ============================================

        // Siegfried (PRONOM format identification)
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

        // ClamAV (virus scanning)
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

        // BagIt (Python package for OAIS packages)
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

        // Cantaloupe (IIIF Image Server)
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
                // Try to get version from status endpoint
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

        // ============================================
        // 3D RENDERING & PROCESSING SOFTWARE
        // ============================================

        // Blender
        $result = shell_exec('blender --version 2>&1');
        if (preg_match('/Blender (\d+\.\d+\.?\d*)/', $result, $matches)) {
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

        // MeshLab
        $result = shell_exec('meshlab --version 2>&1');
        if (preg_match('/MeshLab[_ ](\d+\.\d+\.?\d*)/i', $result, $matches)) {
            $versions[] = [
                'name' => 'MeshLab',
                'version' => $matches[1],
                'icon' => 'bi-dice-3',
                'status' => 'ok',
                'path' => trim(shell_exec('which meshlab 2>/dev/null') ?: ''),
            ];
        } else {
            // Try meshlabserver
            $result = shell_exec('meshlabserver --version 2>&1');
            if (preg_match('/(\d+\.\d+\.?\d*)/', $result, $matches)) {
                $versions[] = [
                    'name' => 'MeshLab Server',
                    'version' => $matches[1],
                    'icon' => 'bi-dice-3',
                    'status' => 'ok',
                    'path' => trim(shell_exec('which meshlabserver 2>/dev/null') ?: ''),
                ];
            } else {
                $versions[] = ['name' => 'MeshLab', 'version' => 'Not installed', 'icon' => 'bi-dice-3', 'status' => 'warning', 'path' => ''];
            }
        }

        // OpenSCAD
        $result = shell_exec('openscad --version 2>&1');
        if (preg_match('/OpenSCAD version (\d+\.\d+\.?\d*)/i', $result, $matches)) {
            $versions[] = [
                'name' => 'OpenSCAD',
                'version' => $matches[1],
                'icon' => 'bi-rulers',
                'status' => 'ok',
                'path' => trim(shell_exec('which openscad 2>/dev/null') ?: ''),
            ];
        } else {
            $versions[] = ['name' => 'OpenSCAD', 'version' => 'Not installed', 'icon' => 'bi-rulers', 'status' => 'warning', 'path' => ''];
        }

        // Assimp (Open Asset Import Library)
        $result = shell_exec('assimp version 2>&1');
        if (preg_match('/(\d+\.\d+\.?\d*)/', $result, $matches)) {
            $versions[] = [
                'name' => 'Assimp',
                'version' => $matches[1],
                'icon' => 'bi-file-earmark-binary',
                'status' => 'ok',
                'path' => trim(shell_exec('which assimp 2>/dev/null') ?: ''),
            ];
        } else {
            $versions[] = ['name' => 'Assimp', 'version' => 'Not installed', 'icon' => 'bi-file-earmark-binary', 'status' => 'warning', 'path' => ''];
        }

        // F3D (Fast 3D viewer)
        $result = shell_exec('f3d --version 2>&1');
        if (preg_match('/f3d (\d+\.\d+\.?\d*)/i', $result, $matches)) {
            $versions[] = [
                'name' => 'F3D Viewer',
                'version' => $matches[1],
                'icon' => 'bi-badge-3d',
                'status' => 'ok',
                'path' => trim(shell_exec('which f3d 2>/dev/null') ?: ''),
            ];
        } else {
            $versions[] = ['name' => 'F3D Viewer', 'version' => 'Not installed', 'icon' => 'bi-badge-3d', 'status' => 'warning', 'path' => ''];
        }

        // gltf-pipeline (Node.js based)
        $result = shell_exec('npx gltf-pipeline --version 2>&1');
        if (preg_match('/(\d+\.\d+\.?\d*)/', $result, $matches)) {
            $versions[] = [
                'name' => 'glTF Pipeline',
                'version' => $matches[1],
                'icon' => 'bi-box-seam',
                'status' => 'ok',
                'path' => 'npx gltf-pipeline',
            ];
        } else {
            $versions[] = ['name' => 'glTF Pipeline', 'version' => 'Not installed', 'icon' => 'bi-box-seam', 'status' => 'warning', 'path' => ''];
        }

        // obj2gltf
        $result = shell_exec('npx obj2gltf --version 2>&1');
        if (preg_match('/(\d+\.\d+\.?\d*)/', $result, $matches)) {
            $versions[] = [
                'name' => 'obj2gltf',
                'version' => $matches[1],
                'icon' => 'bi-arrow-left-right',
                'status' => 'ok',
                'path' => 'npx obj2gltf',
            ];
        } else {
            $versions[] = ['name' => 'obj2gltf', 'version' => 'Not installed', 'icon' => 'bi-arrow-left-right', 'status' => 'warning', 'path' => ''];
        }

        // VTK (check Python module)
        $result = shell_exec('python3 -c "import vtk; print(vtk.vtkVersion.GetVTKVersion())" 2>&1');
        if (preg_match('/(\d+\.\d+\.?\d*)/', $result, $matches)) {
            $versions[] = [
                'name' => 'VTK (Python)',
                'version' => $matches[1],
                'icon' => 'bi-diagram-3',
                'status' => 'ok',
                'path' => 'python3 -c "import vtk"',
            ];
        } else {
            $versions[] = ['name' => 'VTK (Python)', 'version' => 'Not installed', 'icon' => 'bi-diagram-3', 'status' => 'warning', 'path' => ''];
        }

        // Potree Converter
        $result = shell_exec('PotreeConverter --version 2>&1');
        if (preg_match('/(\d+\.\d+\.?\d*)/', $result, $matches)) {
            $versions[] = [
                'name' => 'Potree Converter',
                'version' => $matches[1],
                'icon' => 'bi-cloud',
                'status' => 'ok',
                'path' => trim(shell_exec('which PotreeConverter 2>/dev/null') ?: ''),
            ];
        } else {
            $versions[] = ['name' => 'Potree Converter', 'version' => 'Not installed', 'icon' => 'bi-cloud', 'status' => 'warning', 'path' => ''];
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

    protected function getAllCronJobs(): array
    {
        return [
            // ============================================
            // SEARCH & INDEXING
            // ============================================
            [
                'name' => 'Populate Search Index',
                'command' => 'php symfony search:populate',
                'description' => 'Rebuilds the entire Elasticsearch search index from the database. Use this after major data imports or if search results are inconsistent.',
                'options' => [
                    '--slug=SLUG' => 'Only index a specific repository by slug',
                    '--exclude-types=TYPES' => 'Comma-separated list of types to exclude (e.g., informationobject,actor)',
                    '--show-types' => 'List all available document types',
                ],
                'schedule' => 'Run manually or weekly during off-hours',
                'example' => '0 2 * * 0 cd {root} && php symfony search:populate >> /var/log/atom/search-populate.log 2>&1',
                'duration' => 'Long (depends on database size)',
                'category' => 'search',
            ],
            [
                'name' => 'Update Index (Incremental)',
                'command' => 'php symfony search:update',
                'description' => 'Updates the search index incrementally for records that have changed. Much faster than full populate.',
                'options' => [],
                'schedule' => 'Every 5-15 minutes',
                'example' => '*/5 * * * * cd {root} && php symfony search:update >> /var/log/atom/search-update.log 2>&1',
                'duration' => 'Short',
                'category' => 'search',
            ],

            // ============================================
            // JOBS WORKER
            // ============================================
            [
                'name' => 'Jobs Worker',
                'command' => 'php symfony jobs:worker',
                'description' => 'Runs the background job worker that processes queued tasks like imports, exports, and file processing. Should run as a systemd service, not a cron job.',
                'options' => [
                    '--timeout=SECONDS' => 'Worker timeout (default: no timeout)',
                ],
                'schedule' => 'Run as systemd service (atom-worker.service)',
                'example' => '# systemctl start atom-worker\n# systemctl enable atom-worker',
                'duration' => 'Continuous',
                'category' => 'jobs',
            ],
            [
                'name' => 'Clear Failed Jobs',
                'command' => 'php symfony jobs:clear',
                'description' => 'Removes failed or stale jobs from the job queue.',
                'options' => [
                    '--failed' => 'Only clear failed jobs',
                    '--all' => 'Clear all jobs (use with caution)',
                ],
                'schedule' => 'Weekly or as needed',
                'example' => '0 3 * * 0 cd {root} && php symfony jobs:clear --failed >> /var/log/atom/jobs-clear.log 2>&1',
                'duration' => 'Short',
                'category' => 'jobs',
            ],

            // ============================================
            // CACHE MANAGEMENT
            // ============================================
            [
                'name' => 'Clear Cache',
                'command' => 'php symfony cc',
                'description' => 'Clears the Symfony application cache. Run after configuration changes or deployments.',
                'options' => [
                    'app' => 'Clear only application cache',
                    'config' => 'Clear only config cache',
                    'template' => 'Clear only template cache',
                ],
                'schedule' => 'After deployments or configuration changes',
                'example' => 'cd {root} && php symfony cc',
                'duration' => 'Short',
                'category' => 'cache',
            ],
            [
                'name' => 'Purge XML Cache',
                'command' => 'php symfony cache:xml-purge',
                'description' => 'Purges cached XML exports (EAD, EAC, DC, MODS). Use when XML exports are outdated.',
                'options' => [],
                'schedule' => 'Weekly or after major edits',
                'example' => '0 4 * * 0 cd {root} && php symfony cache:xml-purge >> /var/log/atom/xml-purge.log 2>&1',
                'duration' => 'Medium',
                'category' => 'cache',
            ],

            // ============================================
            // DIGITAL OBJECTS
            // ============================================
            [
                'name' => 'Regenerate Derivatives',
                'command' => 'php symfony digitalobject:regen-derivatives',
                'description' => 'Regenerates thumbnail and reference images for digital objects. Use after changing derivative settings.',
                'options' => [
                    '--slug=SLUG' => 'Process only objects under this description',
                    '--type=TYPE' => 'Only process specific type (thumbnail, reference)',
                    '--force' => 'Regenerate even if derivatives exist',
                    '--only-externals' => 'Only process external digital objects',
                    '--json' => 'Output as JSON',
                ],
                'schedule' => 'Run manually after settings changes',
                'example' => 'cd {root} && php symfony digitalobject:regen-derivatives --force >> /var/log/atom/derivatives.log 2>&1',
                'duration' => 'Long (depends on number of objects)',
                'category' => 'digitalobjects',
            ],
            [
                'name' => 'Load Digital Objects',
                'command' => 'php symfony digitalobject:load',
                'description' => 'Batch load digital objects from a directory structure.',
                'options' => [
                    '--path=PATH' => 'Path to directory containing files',
                    '--attach-to=SLUG' => 'Attach to specific information object',
                    '--limit=N' => 'Maximum number to process',
                ],
                'schedule' => 'Run manually for batch imports',
                'example' => 'cd {root} && php symfony digitalobject:load --path=/imports/images',
                'duration' => 'Long',
                'category' => 'digitalobjects',
            ],

            // ============================================
            // FINDING AIDS
            // ============================================
            [
                'name' => 'Generate Finding Aids',
                'command' => 'php symfony finding-aid:generate',
                'description' => 'Generates PDF/RTF finding aids for archival descriptions.',
                'options' => [
                    '--slug=SLUG' => 'Generate for specific description',
                    '--all' => 'Generate for all top-level descriptions',
                    '--format=FORMAT' => 'Output format (pdf, rtf)',
                ],
                'schedule' => 'Nightly or weekly',
                'example' => '0 1 * * * cd {root} && php symfony finding-aid:generate --all >> /var/log/atom/finding-aids.log 2>&1',
                'duration' => 'Long',
                'category' => 'findingaids',
            ],
            [
                'name' => 'Delete Finding Aids',
                'command' => 'php symfony finding-aid:delete',
                'description' => 'Removes generated finding aid files.',
                'options' => [
                    '--slug=SLUG' => 'Delete for specific description',
                    '--older-than=DAYS' => 'Delete files older than N days',
                ],
                'schedule' => 'Monthly cleanup',
                'example' => '0 3 1 * * cd {root} && php symfony finding-aid:delete --older-than=90',
                'duration' => 'Short',
                'category' => 'findingaids',
            ],

            // ============================================
            // IMPORTS & EXPORTS
            // ============================================
            [
                'name' => 'CSV Import',
                'command' => 'php symfony csv:import',
                'description' => 'Import records from CSV files. Various import types available.',
                'options' => [
                    '--source-name=NAME' => 'Source of the import',
                    '--default-legacy-parent-id=ID' => 'Default parent ID',
                    '--skip-matched' => 'Skip records that match existing',
                    '--update=match' => 'Update matching records',
                    '--limit=N' => 'Maximum records to import',
                    '--index' => 'Index records after import',
                ],
                'schedule' => 'Run manually or scheduled for regular imports',
                'example' => 'cd {root} && php symfony csv:import /path/to/file.csv --source-name="Batch Import"',
                'duration' => 'Long (depends on file size)',
                'category' => 'import',
            ],
            [
                'name' => 'EAD Import',
                'command' => 'php symfony import:bulk',
                'description' => 'Bulk import EAD/XML files from a directory.',
                'options' => [
                    '--source=PATH' => 'Directory containing XML files',
                    '--schema=SCHEMA' => 'Schema type (ead, dc, mods, etc.)',
                    '--output=FILE' => 'Output log file',
                ],
                'schedule' => 'Run manually',
                'example' => 'cd {root} && php symfony import:bulk --source=/imports/ead --schema=ead',
                'duration' => 'Long',
                'category' => 'import',
            ],
            [
                'name' => 'Export Descriptions',
                'command' => 'php symfony export:bulk',
                'description' => 'Bulk export descriptions to XML files.',
                'options' => [
                    '--criteria=CRITERIA' => 'Selection criteria',
                    '--format=FORMAT' => 'Export format (ead, dc, mods)',
                    '--path=PATH' => 'Output directory',
                ],
                'schedule' => 'Weekly backup or as needed',
                'example' => '0 2 * * 0 cd {root} && php symfony export:bulk --format=ead --path=/backups/ead',
                'duration' => 'Long',
                'category' => 'import',
            ],

            // ============================================
            // OAI-PMH
            // ============================================
            [
                'name' => 'OAI Harvest',
                'command' => 'php symfony oai:harvest',
                'description' => 'Harvest records from OAI-PMH repositories.',
                'options' => [
                    '--url=URL' => 'OAI-PMH base URL',
                    '--set=SET' => 'Specific set to harvest',
                    '--from=DATE' => 'Harvest records from date',
                    '--until=DATE' => 'Harvest records until date',
                ],
                'schedule' => 'Daily or weekly',
                'example' => '0 5 * * * cd {root} && php symfony oai:harvest --url=https://example.org/oai',
                'duration' => 'Medium to Long',
                'category' => 'oai',
            ],

            // ============================================
            // MAINTENANCE
            // ============================================
            [
                'name' => 'Propel Build Model',
                'command' => 'php symfony propel:build-model',
                'description' => 'Rebuilds Propel ORM model classes. Run after database schema changes.',
                'options' => [],
                'schedule' => 'After database changes',
                'example' => 'cd {root} && php symfony propel:build-model',
                'duration' => 'Short',
                'category' => 'maintenance',
            ],
            [
                'name' => 'Fix Nested Set',
                'command' => 'php symfony propel:build-nested-set',
                'description' => 'Rebuilds the nested set tree structure for hierarchical data. Use if hierarchy appears broken.',
                'options' => [
                    '--model=MODEL' => 'Specific model to rebuild (e.g., QubitTerm)',
                ],
                'schedule' => 'Run manually when needed',
                'example' => 'cd {root} && php symfony propel:build-nested-set',
                'duration' => 'Medium to Long',
                'category' => 'maintenance',
            ],
            [
                'name' => 'Database Backup',
                'command' => 'mysqldump -u USER -p DATABASE > backup.sql',
                'description' => 'Creates a full database backup. Essential for disaster recovery.',
                'options' => [
                    '--single-transaction' => 'Consistent backup without locking',
                    '--routines' => 'Include stored procedures',
                    '--triggers' => 'Include triggers',
                ],
                'schedule' => 'Daily',
                'example' => '0 1 * * * mysqldump -u root --single-transaction archive > /backups/atom-$(date +\\%Y\\%m\\%d).sql',
                'duration' => 'Medium',
                'category' => 'maintenance',
            ],
            [
                'name' => 'Cleanup Uploads',
                'command' => 'find uploads/tmp -mtime +7 -delete',
                'description' => 'Removes temporary upload files older than 7 days.',
                'options' => [
                    '-mtime +N' => 'Files older than N days',
                ],
                'schedule' => 'Daily',
                'example' => '0 4 * * * find {root}/uploads/tmp -mtime +7 -delete',
                'duration' => 'Short',
                'category' => 'maintenance',
            ],

            // ============================================
            // AUDIT & LOGGING
            // ============================================
            [
                'name' => 'Rotate Logs',
                'command' => 'logrotate /etc/logrotate.d/atom',
                'description' => 'Rotates AtoM log files to prevent disk space issues.',
                'options' => [],
                'schedule' => 'Handled by system logrotate',
                'example' => '# Configure in /etc/logrotate.d/atom',
                'duration' => 'Short',
                'category' => 'audit',
            ],
            [
                'name' => 'Purge Audit Logs',
                'command' => 'php symfony audit:purge',
                'description' => 'Removes old audit trail entries to manage database size.',
                'options' => [
                    '--older-than=DAYS' => 'Purge entries older than N days',
                ],
                'schedule' => 'Monthly',
                'example' => '0 3 1 * * cd {root} && php symfony audit:purge --older-than=365',
                'duration' => 'Medium',
                'category' => 'audit',
            ],

            // ============================================
            // AHG SPECIFIC
            // ============================================
            [
                'name' => 'Services Monitor Check',
                'command' => 'curl -s "URL/ahgSettings/services?check=1"',
                'description' => 'Checks all system services and sends notifications if any are down.',
                'options' => [
                    'check=1' => 'Trigger service check',
                ],
                'schedule' => 'Every 5 minutes',
                'example' => '*/5 * * * * curl -s "https://your-site.com/index.php/ahgSettings/services?check=1" > /dev/null',
                'duration' => 'Short',
                'category' => 'ahg',
            ],
            [
                'name' => 'AI NER Processing',
                'command' => 'php symfony ai:ner-extract',
                'description' => 'Extracts named entities (persons, organizations, places) from record descriptions using AI.',
                'options' => [
                    '--limit=N' => 'Maximum records to process',
                    '--unprocessed' => 'Only process records not yet analyzed',
                ],
                'schedule' => 'Nightly',
                'example' => '0 2 * * * cd {root} && php symfony ai:ner-extract --limit=100 --unprocessed',
                'duration' => 'Long',
                'category' => 'ahg',
            ],
            [
                'name' => 'AI Translation',
                'command' => 'php symfony ai:translate',
                'description' => 'Automatically translates record fields using Argos Translate.',
                'options' => [
                    '--from=LANG' => 'Source language code',
                    '--to=LANG' => 'Target language code',
                    '--limit=N' => 'Maximum records to process',
                ],
                'schedule' => 'Nightly or weekly',
                'example' => '0 3 * * * cd {root} && php symfony ai:translate --from=en --to=af --limit=50',
                'duration' => 'Long',
                'category' => 'ahg',
            ],
            [
                'name' => 'AI Process Pending Queue',
                'command' => 'php symfony ai:process-pending',
                'description' => 'Processes pending AI extraction queue for auto-triggered NER jobs when Gearman is unavailable. Handles records queued from document uploads with automatic retry on failure.',
                'options' => [
                    '--limit=N' => 'Maximum items to process (default: 50)',
                    '--task-type=TYPE' => 'Task type to process: ner, summarize (default: ner)',
                    '--dry-run' => 'Preview without processing',
                ],
                'schedule' => 'Every 5 minutes',
                'example' => '*/5 * * * * cd {root} && php symfony ai:process-pending --limit=20 >> /var/log/atom/ai-pending.log 2>&1',
                'duration' => 'Short to Medium',
                'category' => 'ahg',
            ],
            [
                'name' => 'AI Description Suggestion',
                'command' => 'php symfony ai:suggest-description',
                'description' => 'Generates AI-powered scope_and_content suggestions using LLM (Ollama, OpenAI, or Anthropic). Analyzes OCR text and metadata to draft descriptions for custodian review. Suggestions are saved for approval before being applied to records.',
                'options' => [
                    '--object=ID' => 'Process specific object ID',
                    '--repository=ID' => 'Filter by repository ID',
                    '--level=LEVEL' => 'Filter by level (fonds, series, file, item)',
                    '--empty-only' => 'Only records with empty scope_and_content',
                    '--with-ocr' => 'Only records that have OCR text',
                    '--limit=N' => 'Maximum number to process (default: 50)',
                    '--template=ID' => 'Prompt template ID to use',
                    '--llm-config=ID' => 'LLM configuration ID to use',
                    '--dry-run' => 'Preview without generating suggestions',
                    '--delay=N' => 'Delay between requests in seconds (default: 2)',
                ],
                'schedule' => 'Nightly or weekly, depending on collection size',
                'example' => '0 2 * * * cd {root} && php symfony ai:suggest-description --repository=5 --empty-only --with-ocr --limit=100 >> /var/log/atom/ai-suggest.log 2>&1',
                'duration' => 'Long (depends on number of records and LLM response time)',
                'category' => 'ahg',
            ],
            [
                'name' => 'AI Suggestion Review Dashboard',
                'command' => 'curl -s "URL/ai/suggest/review"',
                'description' => 'Access the web-based review dashboard to approve, edit, or reject AI-generated description suggestions. Custodians can compare existing vs. suggested text side-by-side before applying changes.',
                'options' => [],
                'schedule' => 'Access via web browser as needed',
                'example' => '# Open in browser: https://your-site.com/ai/suggest/review',
                'duration' => 'N/A (Web UI)',
                'category' => 'ahg',
            ],
            [
                'name' => 'LLM Health Check',
                'command' => 'curl -s "URL/ai/llm/health"',
                'description' => 'Checks the health status of configured LLM providers (Ollama, OpenAI, Anthropic). Returns connection status, available models, and configuration details.',
                'options' => [],
                'schedule' => 'Every 5 minutes for monitoring',
                'example' => '*/5 * * * * curl -s "https://your-site.com/ai/llm/health" | jq .providers.*.status',
                'duration' => 'Short',
                'category' => 'ahg',
            ],
            [
                'name' => 'ICIP Consent Expiry Check',
                'command' => 'php symfony icip:check-expiry',
                'description' => 'Checks for ICIP consents expiring soon and sends notification emails.',
                'options' => [
                    '--days=N' => 'Warning threshold in days (default: 90)',
                ],
                'schedule' => 'Daily',
                'example' => '0 8 * * * cd {root} && php symfony icip:check-expiry --days=90',
                'duration' => 'Short',
                'category' => 'ahg',
            ],
            [
                'name' => 'Workflow Process',
                'command' => 'php symfony workflow:process',
                'description' => 'Processes pending workflow tasks including sending notification emails and escalating overdue tasks. V2.0: Also processes SLA breaches and auto-reassignment per SLA policies.',
                'options' => [
                    '--dry-run' => 'Show what would be processed without making changes',
                    '--escalate' => 'Escalate overdue tasks to supervisors',
                    '--limit=N' => 'Maximum tasks to process',
                ],
                'schedule' => 'Every 15 minutes',
                'example' => '*/15 * * * * cd {root} && php symfony workflow:process --escalate >> /var/log/atom/workflow-process.log 2>&1',
                'duration' => 'Short',
                'category' => 'ahg',
            ],
            [
                'name' => 'Workflow SLA Breach Detection',
                'command' => 'php symfony workflow:sla-check',
                'description' => 'V2.0: Scans all open tasks for SLA breaches and emits audit events. Processes escalation actions (notify_lead, notify_admin, auto_reassign) based on SLA policies. Idempotent — safe to run frequently.',
                'options' => [
                    '--dry-run' => 'Report breaches without taking action',
                    '--queue=SLUG' => 'Check only tasks in specific queue',
                ],
                'schedule' => 'Every 15 minutes',
                'example' => '*/15 * * * * cd {root} && php symfony workflow:sla-check >> /var/log/atom/workflow-sla.log 2>&1',
                'duration' => 'Short',
                'category' => 'ahg',
            ],
            [
                'name' => 'Workflow Status',
                'command' => 'php symfony workflow:status',
                'description' => 'Displays current workflow status including pending tasks, task assignments, SLA health metrics, and queue statistics.',
                'options' => [
                    '--pending' => 'Show only pending tasks',
                    '--overdue' => 'Show only overdue tasks',
                    '--sla' => 'Include SLA health overview',
                    '--queues' => 'Show queue-level statistics',
                    '--format=FORMAT' => 'Output format (table, json, csv)',
                ],
                'schedule' => 'Run manually or daily for reporting',
                'example' => '0 8 * * * cd {root} && php symfony workflow:status --sla --format=csv > /var/log/atom/workflow-daily.csv',
                'duration' => 'Short',
                'category' => 'ahg',
            ],
            [
                'name' => 'Statistics Aggregate',
                'command' => 'php symfony statistics:aggregate',
                'description' => 'Aggregates raw usage statistics into daily and monthly summaries. Essential for dashboard performance. Also performs cleanup of old raw events.',
                'options' => [
                    '--all' => 'Run all aggregations (daily + monthly + cleanup)',
                    '--daily' => 'Run daily aggregation only',
                    '--monthly' => 'Run monthly aggregation only',
                    '--cleanup' => 'Cleanup old raw events',
                    '--days=N' => 'Retention period for cleanup (default: 90)',
                    '--backfill=N' => 'Backfill N days of daily aggregates',
                ],
                'schedule' => 'Daily at 2am',
                'example' => '0 2 * * * cd {root} && php symfony statistics:aggregate --all >> /var/log/atom/stats-aggregate.log 2>&1',
                'duration' => 'Medium',
                'category' => 'ahg',
            ],
            [
                'name' => 'Statistics Report',
                'command' => 'php symfony statistics:report',
                'description' => 'Generates usage statistics reports for views, downloads, and top items. Supports CSV export for external analysis.',
                'options' => [
                    '--type=TYPE' => 'Report type (summary, views, downloads, top_items, geographic)',
                    '--start=DATE' => 'Start date (YYYY-MM-DD)',
                    '--end=DATE' => 'End date (YYYY-MM-DD)',
                    '--limit=N' => 'Limit results (for top_items)',
                    '--format=FORMAT' => 'Output format (table, csv, json)',
                    '--output=FILE' => 'Output file path for exports',
                ],
                'schedule' => 'Weekly or monthly for reporting',
                'example' => '0 6 1 * * cd {root} && php symfony statistics:report --type=summary --format=csv --output=/var/log/atom/monthly-stats.csv',
                'duration' => 'Short to Medium',
                'category' => 'ahg',
            ],
            [
                'name' => 'Embargo Process',
                'command' => 'php symfony embargo:process',
                'description' => 'Processes embargo rules - lifts expired embargoes with auto-release enabled and sends expiry warning notifications. Essential for maintaining access control compliance.',
                'options' => [
                    '--dry-run' => 'Preview what would be processed without making changes',
                    '--notify-only' => 'Only send notifications, do not lift embargoes',
                    '--lift-only' => 'Only lift expired embargoes, do not send notifications',
                ],
                'schedule' => 'Daily at 6am',
                'example' => '0 6 * * * cd {root} && php symfony embargo:process >> /var/log/atom/embargo-process.log 2>&1',
                'duration' => 'Short',
                'category' => 'ahg',
            ],
            [
                'name' => 'Embargo Report',
                'command' => 'php symfony embargo:report',
                'description' => 'Generates reports on embargo status including active embargoes, upcoming expirations, and recently lifted embargoes.',
                'options' => [
                    '--active' => 'List all active embargoes',
                    '--expiring=DAYS' => 'List embargoes expiring within N days',
                    '--lifted' => 'List recently lifted embargoes',
                    '--format=FORMAT' => 'Output format (table, csv, json)',
                ],
                'schedule' => 'Weekly or on-demand',
                'example' => '0 7 * * 1 cd {root} && php symfony embargo:report --expiring=30 --format=csv > /var/log/atom/embargo-weekly.csv',
                'duration' => 'Short',
                'category' => 'ahg',
            ],
            [
                'name' => 'Heritage Region Install',
                'command' => 'php symfony heritage:install',
                'description' => 'Installs the heritage accounting database schema and optionally installs regional accounting standards (IPSAS, GRAP, FRS, GASB, etc.).',
                'options' => [
                    '--region=CODE' => 'Install specific region(s) (comma-separated)',
                    '--all-regions' => 'Install all available regions',
                ],
                'schedule' => 'Run manually during setup',
                'example' => 'cd {root} && php symfony heritage:install --region=africa_ipsas,south_africa_grap',
                'duration' => 'Short',
                'category' => 'ahg',
            ],
            [
                'name' => 'Heritage Region Management',
                'command' => 'php symfony heritage:region',
                'description' => 'Manages regional heritage accounting standards - list available regions, install/uninstall regions, set active region for compliance reporting.',
                'options' => [
                    '--install=CODE' => 'Install a region (africa_ipsas, south_africa_grap, uk_frs, etc.)',
                    '--uninstall=CODE' => 'Uninstall a region',
                    '--set-active=CODE' => 'Set active region for compliance',
                    '--info=CODE' => 'Show detailed region information',
                    '--repository=ID' => 'Repository ID for set-active (null = global)',
                ],
                'schedule' => 'Run manually as needed',
                'example' => 'cd {root} && php symfony heritage:region --set-active=africa_ipsas',
                'duration' => 'Short',
                'category' => 'ahg',
            ],
            [
                'name' => 'TripoSR 3D Model Generation',
                'command' => 'php symfony triposr:generate',
                'description' => 'Generates 3D models from 2D images using TripoSR AI. Supports local CPU/GPU processing or remote GPU server with automatic fallback. Models are output as GLB or OBJ format.',
                'options' => [
                    '--image=PATH' => 'Path to input image (PNG, JPG, WEBP)',
                    '--object-id=ID' => 'Link to information_object ID',
                    '--import' => 'Import generated model to AtoM after generation',
                    '--remove-bg=BOOL' => 'Remove background from image (default: true)',
                    '--resolution=N' => 'Mesh resolution 128-512 (default: 256)',
                    '--texture' => 'Bake texture into model (exports as OBJ)',
                    '--health' => 'Check TripoSR API health status',
                    '--preload' => 'Preload TripoSR model into memory',
                    '--stats' => 'Show generation statistics',
                    '--jobs' => 'List recent generation jobs',
                ],
                'schedule' => 'Run manually or batch process nightly',
                'example' => 'cd {root} && php symfony triposr:generate --image=/imports/artifacts/*.jpg --import >> /var/log/atom/triposr.log 2>&1',
                'duration' => 'Long (60-180s per image on CPU, 10-30s with GPU)',
                'category' => 'ahg',
            ],
            [
                'name' => 'TripoSR Health Check',
                'command' => 'php symfony triposr:generate --health',
                'description' => 'Checks the health status of the TripoSR API server including CUDA/GPU availability, model loading status, and remote GPU server configuration.',
                'options' => [],
                'schedule' => 'Every 5 minutes for monitoring',
                'example' => '*/5 * * * * cd {root} && php symfony triposr:generate --health | grep -q "API Status: OK" || echo "TripoSR DOWN" | mail -s "Alert" admin@example.com',
                'duration' => 'Short',
                'category' => 'ahg',
            ],
            [
                'name' => 'TripoSR Model Preload',
                'command' => 'php symfony triposr:generate --preload',
                'description' => 'Preloads the TripoSR AI model into memory for faster subsequent generation requests. Run after server restart to warm up the model.',
                'options' => [],
                'schedule' => 'After server restart or service restart',
                'example' => '# Add to systemd service or run manually:\ncd {root} && php symfony triposr:generate --preload',
                'duration' => 'Medium (2-5 minutes to load model)',
                'category' => 'ahg',
            ],
            [
                'name' => '3D Thumbnail Derivatives',
                'command' => 'php atom-framework/bin/atom 3d:derivatives',
                'description' => 'Generates thumbnail and reference image derivatives for 3D model files (GLB, GLTF, OBJ, STL, FBX, PLY, DAE) using Blender. Processes all 3D digital objects missing derivatives, or a specific object by ID.',
                'options' => [
                    '--id=N' => 'Process only the specified digital object ID',
                    '--force' => 'Regenerate even if derivatives already exist',
                    '--dry-run' => 'List objects that would be processed without generating',
                ],
                'schedule' => 'Nightly or after 3D model uploads',
                'example' => '0 2 * * * cd {root} && php atom-framework/bin/atom 3d:derivatives >> /var/log/atom/3d-derivatives.log 2>&1',
                'duration' => 'Medium to Long (10-30s per model via Blender)',
                'category' => 'ahg',
            ],
            [
                'name' => '3D Multi-Angle Renders',
                'command' => 'php atom-framework/bin/atom 3d:multiangle',
                'description' => 'Generates 6 multi-angle renders (front, back, left, right, top, detail) of 3D models using Blender. Used for AI description and gallery display. Optionally sends renders to LLM for automated description.',
                'options' => [
                    '--id=N' => 'Process only the specified digital object ID',
                    '--force' => 'Regenerate even if renders already exist',
                    '--describe' => 'After rendering, send images to LLM and output AI description',
                    '--dry-run' => 'List objects that would be processed without rendering',
                ],
                'schedule' => 'Nightly or after 3D model uploads',
                'example' => '0 3 * * * cd {root} && php atom-framework/bin/atom 3d:multiangle >> /var/log/atom/3d-multiangle.log 2>&1',
                'duration' => 'Long (30-60s per model for rendering, +30s if --describe)',
                'category' => 'ahg',
            ],
            // ============================================
            // AI CONDITION ASSESSMENT
            // ============================================
            [
                'name' => 'AI Condition Bulk Scan',
                'command' => 'php symfony ai-condition:bulk-scan',
                'description' => 'Runs AI-powered condition assessment on digital objects using YOLOv8 damage detection and EfficientNet classification. Scans images for 15 damage types (tear, stain, foxing, mold, etc.) and generates condition scores.',
                'options' => [
                    '--repository=ID' => 'Restrict scan to specific repository ID',
                    '--limit=N' => 'Maximum objects to scan (default: 50)',
                    '--confidence=N' => 'Minimum confidence threshold 0.1-0.9 (default: 0.25)',
                ],
                'schedule' => 'Nightly or weekly depending on collection activity',
                'example' => '0 3 * * * cd {root} && php symfony ai-condition:bulk-scan --limit=100 --confidence=0.25 >> /var/log/atom/ai-condition.log 2>&1',
                'duration' => 'Long (2-10s per image depending on GPU)',
                'category' => 'ahg',
            ],
            [
                'name' => 'AI Condition Service Status',
                'command' => 'php symfony ai-condition:status',
                'description' => 'Checks the health of the AI Condition Assessment service (FastAPI on port 8100). Reports model loading status, GPU availability, database connectivity, and usage statistics.',
                'options' => [],
                'schedule' => 'Every 5 minutes for monitoring',
                'example' => '*/5 * * * * cd {root} && php symfony ai-condition:status >> /var/log/atom/ai-condition-status.log 2>&1',
                'duration' => 'Short',
                'category' => 'ahg',
            ],

            // ============================================
            // QDRANT VECTOR SEARCH (Discovery Plugin)
            // ============================================
            [
                'name' => 'Qdrant Vector Index — Full Rebuild',
                'command' => 'python3 {root}/atom-ahg-plugins/ahgDiscoveryPlugin/scripts/qdrant_index.py',
                'description' => 'Rebuilds the Qdrant vector collection from scratch. Embeds all archival record titles, scope_and_content, and OCR transcript text using sentence-transformers (all-MiniLM-L6-v2, 384 dimensions) and indexes into Qdrant for semantic/vector search in the Discovery plugin.',
                'options' => [
                    '--db-name=NAME' => 'MySQL database name (archive or atom)',
                    '--db-user=USER' => 'MySQL user (default: root)',
                    '--db-password=PASS' => 'MySQL password',
                    '--collection=NAME' => 'Qdrant collection name (e.g. archive_records, anc_records)',
                    '--reset' => 'Drop and recreate the collection before indexing',
                    '--offset=N' => 'Start from record offset N (for resuming)',
                    '--limit=N' => 'Maximum records to index (0=all)',
                ],
                'schedule' => 'Weekly or after large imports — run at low priority (nice 19)',
                'example' => '0 1 * * 0 cd {root} && nice -n 19 python3 atom-ahg-plugins/ahgDiscoveryPlugin/scripts/qdrant_index.py --db-name=archive --db-user=root --collection=archive_records >> /var/log/atom/qdrant-index.log 2>&1',
                'duration' => 'Long (1-6 hours depending on record count)',
                'category' => 'ahg',
            ],
            [
                'name' => 'AI Summarize — OCR Transcript to Scope',
                'command' => 'php symfony ai:summarize --all-empty --field=scope_and_content',
                'description' => 'Generates AI summaries from OCR transcript text (stored in the property table via digital objects) and writes them to the scope_and_content field of information objects. Processes records that have transcript text but no scope_and_content.',
                'options' => [
                    '--all-empty' => 'Process all records with empty target field',
                    '--field=FIELD' => 'Target i18n field to write summary to (default: scope_and_content)',
                    '--object=ID' => 'Process a single object by ID',
                    '--repository=ID' => 'Limit to a specific repository',
                    '--limit=N' => 'Maximum records to process',
                    '--dry-run' => 'Preview without writing changes',
                ],
                'schedule' => 'Weekly or after large imports — run at low priority (nice 19)',
                'example' => '0 2 * * 0 cd {root} && nice -n 19 php symfony ai:summarize --all-empty --field=scope_and_content >> /var/log/atom/ai-summarize.log 2>&1',
                'duration' => 'Long (depends on record count and AI API speed)',
                'category' => 'ahg',
            ],

            // ============================================
            // RIC TRIPLESTORE
            // ============================================
            [
                'name' => 'RiC Triplestore Sync',
                'command' => 'php symfony ric:queue-process',
                'description' => 'Syncs AtoM records to the Fuseki RiC triplestore as RiC-O linked data. Uses the Python RiC extractor to generate JSON-LD from the database and loads it into Fuseki. Results are logged to ric_sync_log for the dashboard.',
                'options' => [
                    '--limit=N' => 'Maximum records to process',
                    '--fonds=IDS' => 'Specific fonds IDs (comma-separated)',
                    '--clear' => 'Clear triplestore before sync',
                    '--validate' => 'Run SHACL validation after sync',
                    '--backup' => 'Create backup before sync',
                    '--status' => 'Show triplestore status only',
                ],
                'schedule' => 'Nightly or after significant record changes',
                'example' => '0 2 * * * cd {root} && php symfony ric:queue-process --limit=500 >> /var/log/atom/ric-sync.log 2>&1',
                'duration' => 'Medium to Long (depends on record count)',
                'category' => 'ric',
            ],
            [
                'name' => 'RiC Triplestore Status',
                'command' => 'php symfony ric:queue-process --status',
                'description' => 'Displays the current status of the Fuseki triplestore including triple count, dataset size, and connectivity. Useful for monitoring health without modifying data.',
                'options' => [],
                'schedule' => 'Hourly for monitoring, or run manually',
                'example' => '0 * * * * cd {root} && php symfony ric:queue-process --status >> /var/log/atom/ric-status.log 2>&1',
                'duration' => 'Short',
                'category' => 'ric',
            ],
            [
                'name' => 'RiC Integrity Check',
                'command' => 'php atom-framework/bin/atom ric:integrity-check',
                'description' => 'Checks integrity between AtoM records and Fuseki triplestore. Detects orphaned triples, missing records, and inconsistencies. Part of the RiC sync monitoring system.',
                'options' => [
                    '--fix' => 'Attempt to fix detected issues',
                    '--verbose' => 'Show detailed comparison output',
                ],
                'schedule' => 'Weekly integrity verification',
                'example' => '0 5 * * 0 cd {root} && php atom-framework/bin/atom ric:integrity-check >> /var/log/atom/ric-integrity.log 2>&1',
                'duration' => 'Medium',
                'category' => 'ric',
            ],
            // ============================================
            // DIGITAL PRESERVATION
            // ============================================
            [
                'name' => 'Preservation Scheduler',
                'command' => 'php symfony preservation:scheduler',
                'description' => 'Runs scheduled preservation workflows configured in the database. Executes fixity checks, virus scans, format identification, and other preservation tasks on their defined schedules.',
                'options' => [
                    '--dry-run' => 'Show what would run without executing',
                    '--force' => 'Run all scheduled tasks regardless of schedule',
                ],
                'schedule' => 'Every 5 minutes',
                'example' => '*/5 * * * * cd {root} && php symfony preservation:scheduler >> /var/log/atom/preservation-scheduler.log 2>&1',
                'duration' => 'Short to Medium',
                'category' => 'preservation',
            ],
            [
                'name' => 'Fixity Verification',
                'command' => 'php symfony preservation:fixity',
                'description' => 'Verifies file integrity by comparing stored checksums against recalculated values. Detects bit rot, corruption, or unauthorized modifications to digital objects.',
                'options' => [
                    '--algorithm=ALG' => 'Checksum algorithm (md5, sha1, sha256, sha512)',
                    '--limit=N' => 'Maximum objects to verify',
                    '--age=DAYS' => 'Only verify objects not checked in N days',
                    '--repository=SLUG' => 'Only verify objects in specific repository',
                    '--report' => 'Generate detailed verification report',
                ],
                'schedule' => 'Daily or weekly depending on collection size',
                'example' => '0 2 * * * cd {root} && php symfony preservation:fixity --age=30 --report >> /var/log/atom/fixity.log 2>&1',
                'duration' => 'Long (depends on collection size)',
                'category' => 'preservation',
            ],
            [
                'name' => 'Format Identification',
                'command' => 'php symfony preservation:identify',
                'description' => 'Identifies file formats using Siegfried and PRONOM registry. Records format information for preservation planning and identifies files at risk of obsolescence.',
                'options' => [
                    '--limit=N' => 'Maximum objects to process',
                    '--unidentified' => 'Only process objects without format data',
                    '--update' => 'Re-identify all objects (updates existing data)',
                ],
                'schedule' => 'Nightly or after bulk imports',
                'example' => '0 3 * * * cd {root} && php symfony preservation:identify --unidentified >> /var/log/atom/format-id.log 2>&1',
                'duration' => 'Medium to Long',
                'category' => 'preservation',
            ],
            [
                'name' => 'Virus Scanning',
                'command' => 'php symfony preservation:virus-scan',
                'description' => 'Scans digital objects for malware using ClamAV. Quarantines infected files and logs PREMIS events. Essential for collections accepting external submissions.',
                'options' => [
                    '--limit=N' => 'Maximum objects to scan',
                    '--unscanned' => 'Only scan objects not previously scanned',
                    '--quarantine' => 'Move infected files to quarantine folder',
                    '--update-defs' => 'Update virus definitions before scanning',
                ],
                'schedule' => 'Nightly or after uploads',
                'example' => '0 4 * * * cd {root} && php symfony preservation:virus-scan --unscanned --quarantine >> /var/log/atom/virus-scan.log 2>&1',
                'duration' => 'Long (depends on collection size)',
                'category' => 'preservation',
            ],
            [
                'name' => 'Replication Sync',
                'command' => 'php symfony preservation:replicate',
                'description' => 'Synchronizes digital objects to configured replication targets (local directories, S3, SFTP, etc.). Creates geographic copies for disaster recovery.',
                'options' => [
                    '--target=NAME' => 'Specific replication target to sync',
                    '--verify' => 'Verify checksums after transfer',
                    '--dry-run' => 'Show what would be transferred',
                    '--full' => 'Full sync (not incremental)',
                ],
                'schedule' => 'Nightly',
                'example' => '0 1 * * * cd {root} && php symfony preservation:replicate --verify >> /var/log/atom/replication.log 2>&1',
                'duration' => 'Long (depends on data volume)',
                'category' => 'preservation',
            ],
            [
                'name' => 'OAIS Package Generation',
                'command' => 'php symfony preservation:package',
                'description' => 'Generates OAIS-compliant Archival Information Packages (AIPs) using BagIt format. Includes metadata, checksums, and PREMIS preservation events.',
                'options' => [
                    '--type=TYPE' => 'Package type (sip, aip, dip)',
                    '--slug=SLUG' => 'Generate package for specific description',
                    '--output=PATH' => 'Output directory for packages',
                    '--include-derivatives' => 'Include derivative files in package',
                ],
                'schedule' => 'Weekly or on-demand',
                'example' => '0 0 * * 0 cd {root} && php symfony preservation:package --type=aip --output=/preservation/aips >> /var/log/atom/packages.log 2>&1',
                'duration' => 'Long',
                'category' => 'preservation',
            ],
            [
                'name' => 'Obsolescence Report',
                'command' => 'php symfony preservation:obsolescence-report',
                'description' => 'Generates a report of file formats at risk of obsolescence based on PRONOM registry data. Helps prioritize format migration efforts.',
                'options' => [
                    '--output=FILE' => 'Output file path (CSV or JSON)',
                    '--risk-level=LEVEL' => 'Minimum risk level to include (low, medium, high, critical)',
                ],
                'schedule' => 'Monthly',
                'example' => '0 6 1 * * cd {root} && php symfony preservation:obsolescence-report --output=/reports/obsolescence.csv',
                'duration' => 'Medium',
                'category' => 'preservation',
            ],
            [
                'name' => 'Format Migration',
                'command' => 'php symfony preservation:migrate',
                'description' => 'Executes format migration plans, converting at-risk file formats to sustainable formats while preserving originals.',
                'options' => [
                    '--plan=ID' => 'Execute specific migration plan',
                    '--dry-run' => 'Preview migrations without executing',
                    '--preserve-original' => 'Keep original files (default: true)',
                ],
                'schedule' => 'Run manually after review',
                'example' => 'cd {root} && php symfony preservation:migrate --plan=1 --preserve-original',
                'duration' => 'Long',
                'category' => 'preservation',
            ],
            [
                'name' => 'Preservation Statistics',
                'command' => 'php symfony preservation:stats',
                'description' => 'Generates preservation statistics including storage usage, format distribution, fixity status, and replication health.',
                'options' => [
                    '--output=FILE' => 'Output file path',
                    '--format=FORMAT' => 'Output format (json, csv, html)',
                ],
                'schedule' => 'Daily or weekly',
                'example' => '0 7 * * * cd {root} && php symfony preservation:stats --format=json --output=/var/log/atom/preservation-stats.json',
                'duration' => 'Medium',
                'category' => 'preservation',
            ],

            // ============================================
            // METADATA EXPORT (GLAM Standards)
            // ============================================
            [
                'name' => 'GLAM Metadata Export',
                'command' => 'php symfony metadata:export',
                'description' => 'Exports archival descriptions to various GLAM metadata standards. Supports 9 export formats across archives, libraries, museums, and preservation domains.',
                'options' => [
                    '--format=FORMAT' => 'Export format: ead3, rico, lido, marc21, bibframe, vra-core, pbcore, ebucore, premis, or "all"',
                    '--slug=SLUG' => 'Export specific record by slug',
                    '--repository=ID' => 'Export all records from repository',
                    '--output=PATH' => 'Output directory (default: /tmp)',
                    '--include-children' => 'Include child records (hierarchical)',
                    '--include-digital-objects' => 'Include digital object metadata',
                    '--include-drafts' => 'Include draft/unpublished records',
                    '--list' => 'List all available export formats',
                ],
                'schedule' => 'Weekly or on-demand for bulk exports',
                'example' => '0 3 * * 0 cd {root} && php symfony metadata:export --format=all --repository=5 --output=/exports/weekly >> /var/log/atom/metadata-export.log 2>&1',
                'duration' => 'Medium to Long (depends on scope)',
                'category' => 'metadata',
            ],
            [
                'name' => 'EAD3 Export (Archives)',
                'command' => 'php symfony metadata:export --format=ead3',
                'description' => 'Exports to EAD3 (Encoded Archival Description version 3) XML format. The primary standard for archival finding aids, compliant with ISAD(G).',
                'options' => [
                    '--slug=SLUG' => 'Export specific record',
                    '--repository=ID' => 'Export all from repository',
                    '--output=PATH' => 'Output directory',
                    '--include-children' => 'Include hierarchical children',
                ],
                'schedule' => 'Weekly for published finding aids',
                'example' => '0 2 * * 1 cd {root} && php symfony metadata:export --format=ead3 --repository=5 --output=/exports/ead3 >> /var/log/atom/ead3-export.log 2>&1',
                'duration' => 'Medium',
                'category' => 'metadata',
            ],
            [
                'name' => 'RIC-O Export (Linked Data)',
                'command' => 'php symfony metadata:export --format=rico',
                'description' => 'Exports to RIC-O (Records in Contexts Ontology) JSON-LD format. ICA linked data standard for archives, enabling semantic web integration.',
                'options' => [
                    '--slug=SLUG' => 'Export specific record',
                    '--output=PATH' => 'Output directory',
                    '--include-children' => 'Include hierarchical children',
                ],
                'schedule' => 'Weekly or for linked data publishing',
                'example' => '0 4 * * 1 cd {root} && php symfony metadata:export --format=rico --repository=5 --output=/exports/rico >> /var/log/atom/rico-export.log 2>&1',
                'duration' => 'Medium',
                'category' => 'metadata',
            ],
            [
                'name' => 'LIDO Export (Museums)',
                'command' => 'php symfony metadata:export --format=lido',
                'description' => 'Exports to LIDO (Lightweight Information Describing Objects) XML format. Standard for museum and cultural heritage object metadata.',
                'options' => [
                    '--slug=SLUG' => 'Export specific record',
                    '--output=PATH' => 'Output directory',
                    '--include-digital-objects' => 'Include image metadata',
                ],
                'schedule' => 'Weekly for museum collections',
                'example' => '0 2 * * 2 cd {root} && php symfony metadata:export --format=lido --output=/exports/lido >> /var/log/atom/lido-export.log 2>&1',
                'duration' => 'Medium',
                'category' => 'metadata',
            ],
            [
                'name' => 'MARC21 Export (Libraries)',
                'command' => 'php symfony metadata:export --format=marc21',
                'description' => 'Exports to MARC21 XML format. Standard for library catalog records, compatible with ILS systems like Koha, Evergreen, and Alma.',
                'options' => [
                    '--slug=SLUG' => 'Export specific record',
                    '--output=PATH' => 'Output directory',
                ],
                'schedule' => 'Weekly for library integration',
                'example' => '0 2 * * 3 cd {root} && php symfony metadata:export --format=marc21 --output=/exports/marc21 >> /var/log/atom/marc21-export.log 2>&1',
                'duration' => 'Medium',
                'category' => 'metadata',
            ],
            [
                'name' => 'PREMIS Export (Preservation)',
                'command' => 'php symfony metadata:export --format=premis',
                'description' => 'Exports to PREMIS (Preservation Metadata Implementation Strategies) XML format. Standard for digital preservation metadata including fixity, events, and rights.',
                'options' => [
                    '--slug=SLUG' => 'Export specific record',
                    '--output=PATH' => 'Output directory',
                    '--include-digital-objects' => 'Include full digital object metadata',
                ],
                'schedule' => 'Weekly for preservation workflows',
                'example' => '0 5 * * 0 cd {root} && php symfony metadata:export --format=premis --output=/exports/premis >> /var/log/atom/premis-export.log 2>&1',
                'duration' => 'Medium',
                'category' => 'metadata',
            ],

            // ============================================
            // DOI MANAGEMENT (DataCite Integration)
            // ============================================
            [
                'name' => 'DOI Mint',
                'command' => 'php symfony doi:mint',
                'description' => 'Mints new DOIs (Digital Object Identifiers) via DataCite API for archival records. Creates persistent identifiers for academic citation and discovery.',
                'options' => [
                    '--slug=SLUG' => 'Mint DOI for specific record by slug',
                    '--object-id=ID' => 'Mint DOI for specific object ID',
                    '--repository=ID' => 'Mint DOIs for all eligible records in repository',
                    '--state=STATE' => 'Initial DOI state: draft, registered, or findable (default: findable)',
                    '--dry-run' => 'Preview without minting',
                    '--limit=N' => 'Maximum DOIs to mint',
                ],
                'schedule' => 'Run manually or nightly for batch minting',
                'example' => '0 1 * * * cd {root} && php symfony doi:mint --repository=5 --state=findable --limit=50 >> /var/log/atom/doi-mint.log 2>&1',
                'duration' => 'Medium (API calls to DataCite)',
                'category' => 'doi',
            ],
            [
                'name' => 'DOI Process Queue',
                'command' => 'php symfony doi:process-queue',
                'description' => 'Processes the DOI queue for pending mint, update, or delete operations. Handles batch operations and retries failed requests.',
                'options' => [
                    '--limit=N' => 'Maximum queue items to process (default: 100)',
                    '--retry-failed' => 'Retry previously failed operations',
                    '--operation=OP' => 'Filter by operation type: mint, update, delete',
                ],
                'schedule' => 'Every 15 minutes',
                'example' => '*/15 * * * * cd {root} && php symfony doi:process-queue --limit=50 >> /var/log/atom/doi-queue.log 2>&1',
                'duration' => 'Short to Medium',
                'category' => 'doi',
            ],
            [
                'name' => 'DOI Verify',
                'command' => 'php symfony doi:verify',
                'description' => 'Verifies DOI registrations with DataCite API. Checks that local DOI records match DataCite metadata and URL resolution is correct.',
                'options' => [
                    '--all' => 'Verify all DOIs (not just recent)',
                    '--fix' => 'Attempt to fix mismatches by updating DataCite',
                    '--repository=ID' => 'Verify DOIs for specific repository',
                    '--limit=N' => 'Maximum DOIs to verify',
                ],
                'schedule' => 'Weekly verification',
                'example' => '0 6 * * 0 cd {root} && php symfony doi:verify --all >> /var/log/atom/doi-verify.log 2>&1',
                'duration' => 'Medium to Long',
                'category' => 'doi',
            ],
            [
                'name' => 'DOI Update',
                'command' => 'php symfony doi:update',
                'description' => 'Updates existing DOI metadata at DataCite when archival records change. Syncs title, dates, creators, and landing page URLs.',
                'options' => [
                    '--slug=SLUG' => 'Update DOI for specific record',
                    '--modified-since=DATE' => 'Update DOIs for records modified since date (YYYY-MM-DD)',
                    '--all' => 'Update all DOIs',
                    '--dry-run' => 'Preview without updating',
                ],
                'schedule' => 'Nightly or after bulk edits',
                'example' => '0 3 * * * cd {root} && php symfony doi:update --modified-since=$(date -d "yesterday" +%Y-%m-%d) >> /var/log/atom/doi-update.log 2>&1',
                'duration' => 'Medium',
                'category' => 'doi',
            ],
            [
                'name' => 'DOI Report',
                'command' => 'php symfony doi:report',
                'description' => 'Generates reports on DOI status, usage statistics, and configuration. Useful for monitoring DOI health and DataCite quota usage.',
                'options' => [
                    '--type=TYPE' => 'Report type: summary, status, failed, usage',
                    '--format=FORMAT' => 'Output format: table, csv, json',
                    '--output=FILE' => 'Output file path for exports',
                ],
                'schedule' => 'Weekly or monthly for reporting',
                'example' => '0 7 1 * * cd {root} && php symfony doi:report --type=summary --format=csv --output=/var/log/atom/doi-monthly.csv',
                'duration' => 'Short',
                'category' => 'doi',
            ],
            [
                'name' => 'DOI Sync',
                'command' => 'php symfony doi:sync',
                'description' => 'Syncs DOI metadata with DataCite to ensure records are up to date. Updates titles, dates, creators, and other metadata for all minted DOIs.',
                'options' => [
                    '--all' => 'Sync all DOIs (up to limit)',
                    '--id=ID' => 'Sync specific DOI record ID',
                    '--status=STATUS' => 'Filter by status (findable, registered, draft)',
                    '--repository=ID' => 'Filter by repository ID',
                    '--limit=N' => 'Maximum DOIs to sync (default: 100)',
                    '--queue' => 'Queue for background processing instead of direct sync',
                    '--dry-run' => 'Preview without syncing',
                ],
                'schedule' => 'Weekly or after bulk edits',
                'example' => '0 4 * * 0 cd {root} && php symfony doi:sync --all --limit=500 >> /var/log/atom/doi-sync.log 2>&1',
                'duration' => 'Medium to Long',
                'category' => 'doi',
            ],
            [
                'name' => 'DOI Deactivate',
                'command' => 'php symfony doi:deactivate',
                'description' => 'Deactivates DOIs (creates tombstones) when records are deleted. Hides DOIs from DataCite discovery while maintaining URL resolution for citation integrity.',
                'options' => [
                    '--id=ID' => 'DOI record ID to deactivate',
                    '--object-id=ID' => 'Information object ID to deactivate DOI for',
                    '--reason=TEXT' => 'Reason for deactivation',
                    '--reactivate' => 'Reactivate a previously deactivated DOI',
                    '--list-deleted' => 'List all deactivated DOIs',
                    '--dry-run' => 'Preview without making changes',
                ],
                'schedule' => 'Run manually or as part of record deletion workflow',
                'example' => 'cd {root} && php symfony doi:deactivate --object-id=123 --reason="Record deleted"',
                'duration' => 'Short',
                'category' => 'doi',
            ],

            // ============================================
            // AI — HTR, SPELLCHECK, NER SYNC, ENTITY CACHE
            // ============================================
            [
                'name' => 'AI HTR — Handwritten Text Recognition',
                'command' => 'php symfony ai:htr',
                'description' => 'Extracts handwritten text from digital object images using TrOCR models (4 modes: general, date, digits, letters). Zone detection splits images into text lines for higher accuracy. Transcripts are stored in the property table alongside OCR text.',
                'options' => [
                    '--object=ID' => 'Process specific information object ID',
                    '--repository=ID' => 'Process all image objects in repository',
                    '--all' => 'Process all objects with images but no transcript',
                    '--limit=N' => 'Maximum to process (default: 100)',
                    '--mode=MODE' => 'TrOCR mode: all, date, digits, letters (default: all)',
                    '--no-zones' => 'Disable zone detection (process full image)',
                    '--overwrite' => 'Overwrite existing transcripts',
                    '--dry-run' => 'Show what would be processed',
                ],
                'schedule' => 'Nightly or after handwritten document uploads',
                'example' => '0 3 * * * cd {root} && php symfony ai:htr --all --limit=100 >> /var/log/atom/ai-htr.log 2>&1',
                'duration' => 'Long (0.5-7s per image depending on complexity)',
                'category' => 'ahg',
            ],
            [
                'name' => 'AI Spellcheck',
                'command' => 'php symfony ai:spellcheck',
                'description' => 'Checks spelling and grammar in archival record fields using aspell with multi-language dictionary support. Results can be reviewed and applied via the web UI.',
                'options' => [
                    '--object=ID' => 'Process specific object ID',
                    '--repository=ID' => 'Process all in repository',
                    '--all' => 'Process all unprocessed records',
                    '--limit=N' => 'Maximum to process (default: 100)',
                    '--dry-run' => 'Preview without processing',
                ],
                'schedule' => 'Weekly or after imports',
                'example' => '0 4 * * 0 cd {root} && php symfony ai:spellcheck --all --limit=200 >> /var/log/atom/ai-spellcheck.log 2>&1',
                'duration' => 'Medium',
                'category' => 'ahg',
            ],
            [
                'name' => 'AI NER Sync Training Data',
                'command' => 'php symfony ai:ner-sync',
                'description' => 'Synchronises NER training data from approved entity extractions back to the model. Improves entity recognition accuracy over time based on custodian-reviewed extractions.',
                'options' => [
                    '--export' => 'Export training data to JSON',
                    '--retrain' => 'Trigger model retraining',
                ],
                'schedule' => 'Monthly or after significant entity curation',
                'example' => '0 1 1 * * cd {root} && php symfony ai:ner-sync --retrain >> /var/log/atom/ai-ner-sync.log 2>&1',
                'duration' => 'Medium to Long',
                'category' => 'ahg',
            ],
            [
                'name' => 'AI Sync Entity Cache',
                'command' => 'php symfony ai:sync-entity-cache',
                'description' => 'Rebuilds the NER entity cache used by the Discovery plugin for entity-based search. Aggregates approved entities from ahg_ner_entity into the search cache.',
                'options' => [],
                'schedule' => 'Daily after NER extraction',
                'example' => '0 5 * * * cd {root} && php symfony ai:sync-entity-cache >> /var/log/atom/ai-entity-cache.log 2>&1',
                'duration' => 'Short to Medium',
                'category' => 'ahg',
            ],
            [
                'name' => 'AI Install / Database Setup',
                'command' => 'php symfony ai:install',
                'description' => 'Creates or updates the AI plugin database tables (ahg_ai_settings, ahg_ner_extraction, ahg_ner_entity, ahg_translation_log, etc.). Run after initial installation or version upgrade.',
                'options' => [],
                'schedule' => 'Run manually during installation or upgrades',
                'example' => 'cd {root} && php symfony ai:install',
                'duration' => 'Short',
                'category' => 'ahg',
            ],

            // ============================================
            // QDRANT IMAGE SIMILARITY (Discovery Plugin)
            // ============================================
            [
                'name' => 'Qdrant Image Index — CLIP Embeddings',
                'command' => 'python3 {root}/atom-ahg-plugins/ahgDiscoveryPlugin/scripts/qdrant_image_index.py',
                'description' => 'Indexes digital object images into Qdrant using CLIP (ViT-B/32, 512 dimensions) for visual similarity search. Prefers reference derivatives (480px) for faster processing. Creates a separate {db}_images collection alongside the text collection.',
                'options' => [
                    '--db-name=NAME' => 'MySQL database name (default: archive)',
                    '--db-user=USER' => 'MySQL user (default: root)',
                    '--db-password=PASS' => 'MySQL password',
                    '--collection=NAME' => 'Qdrant collection name (default: {db-name}_images)',
                    '--atom-root=PATH' => 'AtoM root directory',
                    '--reset' => 'Drop and recreate the collection',
                    '--offset=N' => 'Start from offset N (for resuming)',
                    '--limit=N' => 'Maximum images to index (0=all)',
                ],
                'schedule' => 'Weekly or after large digital object uploads',
                'example' => '0 2 * * 0 cd {root} && nice -n 19 python3 atom-ahg-plugins/ahgDiscoveryPlugin/scripts/qdrant_image_index.py --db-name=archive --db-user=root >> /var/log/atom/qdrant-image-index.log 2>&1',
                'duration' => 'Long (depends on image count, ~2-5 img/s)',
                'category' => 'ahg',
            ],

            // ============================================
            // DISPLAY PLUGIN
            // ============================================
            [
                'name' => 'Display Auto-Detect GLAM Types',
                'command' => 'php symfony display:auto-detect',
                'description' => 'Auto-detects GLAM object types (archive, library, museum, gallery, DAM) in the collection and updates the display_object_config table. Run after fresh install or after importing records from a new GLAM sector.',
                'options' => [],
                'schedule' => 'Run manually after imports or fresh install',
                'example' => 'cd {root} && php symfony display:auto-detect',
                'duration' => 'Short to Medium',
                'category' => 'ahg',
            ],
            [
                'name' => 'Display Reindex Facets',
                'command' => 'php symfony display:reindex',
                'description' => 'Rebuilds the GLAM browse facet cache (display_facet_cache) for fast faceted navigation. Essential after data imports or if facet counts appear incorrect.',
                'options' => [],
                'schedule' => 'After data imports or weekly',
                'example' => '0 4 * * 0 cd {root} && php symfony display:reindex >> /var/log/atom/display-reindex.log 2>&1',
                'duration' => 'Medium',
                'category' => 'ahg',
            ],

            // ============================================
            // DATA INGEST
            // ============================================
            [
                'name' => 'Ingest Commit — Background Job',
                'command' => 'php symfony ingest:commit',
                'description' => 'Processes a data ingest commit job (step 6 of the ingest wizard). Normally launched automatically from the web UI via nohup; use CLI for manual processing or retries. Creates records, digital objects, and OAIS packages (SIP/AIP/DIP).',
                'options' => [
                    '--job-id=ID' => 'Process a specific ingest job by ID',
                    '--session-id=ID' => 'Create and process a job for a session ID',
                ],
                'schedule' => 'Launched by web UI; use CLI for retries',
                'example' => 'cd {root} && php symfony ingest:commit --job-id=123 >> /var/log/atom/ingest-commit.log 2>&1',
                'duration' => 'Long (depends on row count and AI processing options)',
                'category' => 'ahg',
            ],

            // ============================================
            // COMPLIANCE — PRIVACY, CDPA, NAZ, NMMZ
            // ============================================
            [
                'name' => 'Privacy PII Scan',
                'command' => 'php symfony privacy:scan-pii',
                'description' => 'Scans archival records for personally identifiable information (PII) such as ID numbers, phone numbers, emails, and addresses. Supports POPIA, GDPR, CCPA, PIPEDA, NDPA, DPA, UK GDPR jurisdictions.',
                'options' => [
                    '--jurisdiction=CODE' => 'Jurisdiction to scan for (popia, gdpr, ccpa, etc.)',
                    '--limit=N' => 'Maximum records to scan',
                    '--repository=ID' => 'Restrict to repository',
                    '--dry-run' => 'Preview without flagging',
                ],
                'schedule' => 'Weekly or monthly compliance check',
                'example' => '0 2 * * 1 cd {root} && php symfony privacy:scan-pii --jurisdiction=popia --limit=500 >> /var/log/atom/privacy-scan.log 2>&1',
                'duration' => 'Medium to Long',
                'category' => 'ahg',
            ],
            [
                'name' => 'Privacy Jurisdiction Report',
                'command' => 'php symfony privacy:jurisdiction',
                'description' => 'Generates a compliance report for a specific privacy jurisdiction showing PII findings, remediation status, and compliance metrics.',
                'options' => [
                    '--jurisdiction=CODE' => 'Jurisdiction (popia, gdpr, ccpa, pipeda, ndpa, dpa, uk_gdpr)',
                    '--format=FORMAT' => 'Output format (table, csv, json)',
                ],
                'schedule' => 'Monthly for compliance reporting',
                'example' => '0 6 1 * * cd {root} && php symfony privacy:jurisdiction --jurisdiction=popia --format=csv > /var/log/atom/popia-report.csv',
                'duration' => 'Short to Medium',
                'category' => 'ahg',
            ],
            [
                'name' => 'CDPA License Check (Zimbabwe)',
                'command' => 'php symfony cdpa:license-check',
                'description' => 'Checks data processing license compliance under Zimbabwe\'s Cyber & Data Protection Act [Chapter 12:07]. Verifies POTRAZ registration and data controller obligations.',
                'options' => [
                    '--report' => 'Generate compliance report',
                ],
                'schedule' => 'Monthly',
                'example' => '0 6 1 * * cd {root} && php symfony cdpa:license-check >> /var/log/atom/cdpa-license.log 2>&1',
                'duration' => 'Short',
                'category' => 'ahg',
            ],
            [
                'name' => 'CDPA Status Report (Zimbabwe)',
                'command' => 'php symfony cdpa:status',
                'description' => 'Displays current CDPA compliance status including data processing registrations, request handling metrics, and outstanding obligations.',
                'options' => [],
                'schedule' => 'Run manually or monthly',
                'example' => 'cd {root} && php symfony cdpa:status',
                'duration' => 'Short',
                'category' => 'ahg',
            ],
            [
                'name' => 'NAZ Closure Check (Zimbabwe)',
                'command' => 'php symfony naz:closure-check',
                'description' => 'Checks records against the National Archives of Zimbabwe Act [Chapter 25:06] 25-year closure rule. Identifies records eligible for public release and those still under restriction.',
                'options' => [
                    '--limit=N' => 'Maximum records to check',
                    '--report' => 'Generate detailed report',
                ],
                'schedule' => 'Monthly or quarterly',
                'example' => '0 6 1 * * cd {root} && php symfony naz:closure-check >> /var/log/atom/naz-closure.log 2>&1',
                'duration' => 'Medium',
                'category' => 'ahg',
            ],
            [
                'name' => 'NAZ Transfer Due (Zimbabwe)',
                'command' => 'php symfony naz:transfer-due',
                'description' => 'Identifies government records due for transfer to the National Archives of Zimbabwe based on retention schedules defined under the National Archives Act.',
                'options' => [
                    '--days=N' => 'Warning threshold in days (default: 90)',
                ],
                'schedule' => 'Monthly',
                'example' => '0 6 1 * * cd {root} && php symfony naz:transfer-due --days=90 >> /var/log/atom/naz-transfer.log 2>&1',
                'duration' => 'Short to Medium',
                'category' => 'ahg',
            ],
            [
                'name' => 'NMMZ Report (Zimbabwe)',
                'command' => 'php symfony nmmz:report',
                'description' => 'Generates a compliance report for the National Museums and Monuments of Zimbabwe Act [Chapter 25:11]. Reports on registered monuments, archaeological sites, and protection status.',
                'options' => [
                    '--format=FORMAT' => 'Output format (table, csv, json)',
                ],
                'schedule' => 'Quarterly or on demand',
                'example' => 'cd {root} && php symfony nmmz:report --format=csv > /var/log/atom/nmmz-report.csv',
                'duration' => 'Short',
                'category' => 'ahg',
            ],

            // ============================================
            // DEDUPE, FORMS, HERITAGE, LIBRARY, MUSEUM
            // ============================================
            [
                'name' => 'Duplicate Detection Scan',
                'command' => 'php symfony dedupe:scan',
                'description' => 'Scans archival records for potential duplicates using title similarity, identifier matching, and date comparison. Results are queued for custodian review.',
                'options' => [
                    '--limit=N' => 'Maximum records to scan',
                    '--repository=ID' => 'Restrict to repository',
                    '--threshold=N' => 'Similarity threshold 0-100 (default: 80)',
                    '--dry-run' => 'Preview without saving results',
                ],
                'schedule' => 'Weekly or after large imports',
                'example' => '0 3 * * 0 cd {root} && php symfony dedupe:scan --limit=500 --threshold=80 >> /var/log/atom/dedupe-scan.log 2>&1',
                'duration' => 'Long',
                'category' => 'ahg',
            ],
            [
                'name' => 'Duplicate Merge',
                'command' => 'php symfony dedupe:merge',
                'description' => 'Merges confirmed duplicate records. Preserves the primary record and transfers children, digital objects, and relations from the duplicate before deletion.',
                'options' => [
                    '--pair-id=ID' => 'Merge a specific duplicate pair',
                    '--all-approved' => 'Merge all approved duplicate pairs',
                    '--dry-run' => 'Preview without merging',
                ],
                'schedule' => 'Run manually after review',
                'example' => 'cd {root} && php symfony dedupe:merge --all-approved >> /var/log/atom/dedupe-merge.log 2>&1',
                'duration' => 'Medium',
                'category' => 'ahg',
            ],
            [
                'name' => 'Duplicate Report',
                'command' => 'php symfony dedupe:report',
                'description' => 'Generates a report of detected duplicates including match scores, record details, and merge recommendations.',
                'options' => [
                    '--format=FORMAT' => 'Output format (table, csv, json)',
                    '--status=STATUS' => 'Filter by status (pending, approved, rejected)',
                ],
                'schedule' => 'On demand',
                'example' => 'cd {root} && php symfony dedupe:report --format=csv > /var/log/atom/dedupe-report.csv',
                'duration' => 'Short',
                'category' => 'ahg',
            ],
            [
                'name' => 'Forms Export',
                'command' => 'php symfony forms:export',
                'description' => 'Exports form configurations (field layouts, validation rules) to JSON for backup or migration between instances.',
                'options' => [
                    '--repository=ID' => 'Export forms for specific repository',
                    '--output=FILE' => 'Output file path',
                ],
                'schedule' => 'Before deployments or as backup',
                'example' => 'cd {root} && php symfony forms:export --output=/tmp/forms-backup.json',
                'duration' => 'Short',
                'category' => 'ahg',
            ],
            [
                'name' => 'Forms Import',
                'command' => 'php symfony forms:import',
                'description' => 'Imports form configurations from a JSON export file. Use to restore configurations or synchronise between instances.',
                'options' => [
                    '--file=PATH' => 'Path to JSON export file',
                    '--repository=ID' => 'Import to specific repository',
                ],
                'schedule' => 'Run manually during setup',
                'example' => 'cd {root} && php symfony forms:import --file=/tmp/forms-backup.json',
                'duration' => 'Short',
                'category' => 'ahg',
            ],
            [
                'name' => 'Heritage Build Knowledge Graph',
                'command' => 'php symfony heritage:build-graph',
                'description' => 'Builds the heritage knowledge graph from contributor data, heritage sites, and related records. Used by the heritage discovery platform for contextual browsing. V2.0: Add --link-getty to auto-link entities to Getty vocabularies (TGN, ULAN, AAT).',
                'options' => [
                    '--full' => 'Full rebuild (vs incremental)',
                    '--link-getty' => 'Auto-link graph entities to Getty vocabularies after build',
                    '--getty-limit=N' => 'Maximum entities to link to Getty (default: unlimited)',
                ],
                'schedule' => 'Weekly or after heritage data changes',
                'example' => '0 3 * * 0 cd {root} && php symfony heritage:build-graph --link-getty >> /var/log/atom/heritage-graph.log 2>&1',
                'duration' => 'Medium to Long',
                'category' => 'ahg',
            ],
            [
                'name' => 'Linked Data Sync',
                'command' => 'php symfony linked-data:sync',
                'description' => 'Synchronises heritage entity graph nodes with external linked data sources: VIAF (persons/organisations), Wikidata (all entity types), and Getty vocabularies (TGN, ULAN, AAT). Populates viaf_id, wikidata_id, and getty links for contextual enrichment.',
                'options' => [
                    '--source=SOURCE' => 'Data source: viaf, wikidata, getty, or all (default: all)',
                    '--entity-type=TYPE' => 'Filter by entity type: person, organization, place, concept',
                    '--limit=N' => 'Maximum entities to process',
                    '--dry-run' => 'Show matches without saving',
                    '--stats' => 'Show current linking statistics only',
                ],
                'schedule' => 'Weekly or after heritage graph build',
                'example' => '0 4 * * 0 cd {root} && php symfony linked-data:sync --source=all --limit=500 >> /var/log/atom/linked-data-sync.log 2>&1',
                'duration' => 'Medium to Long (network-dependent, rate-limited)',
                'category' => 'ahg',
            ],
            [
                'name' => 'Library Process Covers',
                'command' => 'php symfony library:process-covers',
                'description' => 'Downloads and processes book cover images for library records using ISBN lookup. Updates digital object derivatives with retrieved cover images.',
                'options' => [
                    '--limit=N' => 'Maximum records to process',
                    '--missing-only' => 'Only process records without covers',
                ],
                'schedule' => 'Weekly or after library imports',
                'example' => '0 4 * * 0 cd {root} && php symfony library:process-covers --missing-only --limit=100 >> /var/log/atom/library-covers.log 2>&1',
                'duration' => 'Medium (depends on network speed)',
                'category' => 'ahg',
            ],
            [
                'name' => 'Museum Exhibition Management',
                'command' => 'php symfony museum:exhibition',
                'description' => 'Manages museum exhibitions — checks for upcoming openings/closings, moves objects between exhibition and storage status, and sends notifications.',
                'options' => [
                    '--check' => 'Check upcoming exhibition changes',
                    '--process' => 'Process scheduled object movements',
                ],
                'schedule' => 'Daily',
                'example' => '0 7 * * * cd {root} && php symfony museum:exhibition --process >> /var/log/atom/museum-exhibition.log 2>&1',
                'duration' => 'Short',
                'category' => 'ahg',
            ],
            [
                'name' => 'Portable Export',
                'command' => 'php symfony portable:export',
                'description' => 'Generates a standalone offline catalogue (HTML/JS/CSS) for distribution on CD, USB, or ZIP. Includes FlexSearch for client-side search and hierarchical navigation. Suitable for field work and offline access.',
                'options' => [
                    '--repository=SLUG' => 'Export specific repository',
                    '--output=DIR' => 'Output directory',
                    '--include-images' => 'Include digital object images',
                    '--include-search' => 'Include FlexSearch index',
                ],
                'schedule' => 'On demand or quarterly for distribution',
                'example' => 'cd {root} && php symfony portable:export --repository=my-repo --output=/tmp/export --include-images >> /var/log/atom/portable-export.log 2>&1',
                'duration' => 'Long (depends on collection size and images)',
                'category' => 'ahg',
            ],
            [
                'name' => 'API Webhook Retry',
                'command' => 'php symfony api:webhook-process-retries',
                'description' => 'Processes failed webhook deliveries from the REST API. Retries pending webhooks with exponential backoff. Part of the ahgAPIPlugin integration layer.',
                'options' => [
                    '--limit=N' => 'Maximum retries to process',
                    '--max-attempts=N' => 'Maximum retry attempts before giving up (default: 5)',
                ],
                'schedule' => 'Every 15 minutes',
                'example' => '*/15 * * * * cd {root} && php symfony api:webhook-process-retries --limit=50 >> /var/log/atom/webhook-retries.log 2>&1',
                'duration' => 'Short to Medium',
                'category' => 'ahg',
            ],
            [
                'name' => 'IPSAS Report',
                'command' => 'php symfony ipsas:report',
                'description' => 'Generates International Public Sector Accounting Standards (IPSAS 45) heritage asset reports. Outputs valuation, depreciation, and disclosure schedules.',
                'options' => [
                    '--format=FORMAT' => 'Output format (table, csv, json, pdf)',
                    '--period=PERIOD' => 'Reporting period (YYYY or YYYY-MM)',
                ],
                'schedule' => 'Quarterly or annually for financial reporting',
                'example' => 'cd {root} && php symfony ipsas:report --format=csv --period=2026 > /var/log/atom/ipsas-2026.csv',
                'duration' => 'Short to Medium',
                'category' => 'ahg',
            ],
            [
                'name' => 'Metadata Export (All Formats)',
                'command' => 'php symfony metadata:export',
                'description' => 'Exports metadata in various standards. Supports 12 formats: CIDOC-CRM, BIBFRAME, PBCore, RIC-O, LIDO, VRA Core, MARC21, EAD3, Schema.org, PREMIS, Ebucore, Dublin Core.',
                'options' => [
                    '--format=FORMAT' => 'Export format (cidoc-crm, bibframe, pbcore, rico, lido, vra, marc21, ead3, schema-org, premis, ebucore, dc)',
                    '--repository=SLUG' => 'Restrict to repository',
                    '--output=FILE' => 'Output file path',
                    '--limit=N' => 'Maximum records',
                ],
                'schedule' => 'On demand for data exchange',
                'example' => 'cd {root} && php symfony metadata:export --format=cidoc-crm --output=/tmp/cidoc-export.xml',
                'duration' => 'Medium to Long',
                'category' => 'metadata',
            ],

            // ---------------------------------------------------------
            // Integrity Assurance (ahgIntegrityPlugin)
            // ---------------------------------------------------------
            [
                'name' => 'Integrity: Run Due Schedules',
                'command' => 'php symfony integrity:schedule --run-due',
                'description' => 'Checks for due integrity verification schedules and executes them. This is the primary cron entry for automated fixity checking. Delegates hash verification to PreservationService and records results in the append-only integrity ledger.',
                'options' => [],
                'schedule' => 'Every 15 minutes (scheduler checks, only runs when schedules are due)',
                'example' => '*/15 * * * * cd {root} && php symfony integrity:schedule --run-due >> /var/log/atom/integrity-scheduler.log 2>&1',
                'duration' => 'Variable (depends on schedule batch size and object count)',
                'category' => 'integrity',
            ],
            [
                'name' => 'Integrity: Verify Objects',
                'command' => 'php symfony integrity:verify',
                'description' => 'Run ad-hoc fixity verification on digital objects. Verifies stored checksums (from preservation_checksum) against current file hashes. Results recorded in integrity_ledger. Objects failing 3+ consecutive times are escalated to the dead-letter queue.',
                'options' => [
                    '--object-id=ID' => 'Verify a single digital object by ID',
                    '--schedule-id=ID' => 'Run a specific schedule',
                    '--repository-id=ID' => 'Verify objects in a specific repository',
                    '--limit=N' => 'Maximum objects to verify (default: 200)',
                    '--stale-days=N' => 'Only verify objects not checked in N days (default: 7)',
                    '--all' => 'Verify all master digital objects (ignores --limit)',
                    '--throttle=MS' => 'IO throttle in ms between objects (default: 10)',
                    '--status' => 'Show current verification status and statistics',
                    '--dry-run' => 'Show what would be verified without verifying',
                ],
                'schedule' => 'On demand or via schedules (use integrity:schedule --run-due for automated runs)',
                'example' => '0 3 * * * cd {root} && php symfony integrity:verify --limit=500 --stale-days=14 --throttle=20 >> /var/log/atom/integrity-verify.log 2>&1',
                'duration' => 'Medium to Long (depends on object count and IO speed)',
                'category' => 'integrity',
            ],
            [
                'name' => 'Integrity: Schedule Management',
                'command' => 'php symfony integrity:schedule',
                'description' => 'Manage integrity verification schedules. Schedules support scoping (global, per-repository, per-hierarchy), frequency (daily/weekly/monthly/cron), concurrency controls (batch size, IO throttle, memory/runtime limits), and notification settings.',
                'options' => [
                    '--list' => 'List all configured schedules with status',
                    '--status' => 'Show schedule status summary (total, enabled, due, active)',
                    '--run-due' => 'Run all due schedules (for cron)',
                    '--run-id=ID' => 'Run a specific schedule immediately',
                    '--enable=ID' => 'Enable a schedule by ID',
                    '--disable=ID' => 'Disable a schedule by ID',
                ],
                'schedule' => 'On demand for management; --run-due every 15 min for automation',
                'example' => 'cd {root} && php symfony integrity:schedule --list',
                'duration' => 'Short (management) to Long (--run-due)',
                'category' => 'integrity',
            ],
            [
                'name' => 'Integrity: Generate Report',
                'command' => 'php symfony integrity:report',
                'description' => 'Generate integrity verification reports including summary statistics, ledger entries, and dead-letter queue status. Supports text, JSON, and CSV output formats for integration with external monitoring systems.',
                'options' => [
                    '--summary' => 'Show summary report (pass rate, schedules, recent runs)',
                    '--dead-letter' => 'Show dead letter queue report',
                    '--date-from=DATE' => 'Start date filter (YYYY-MM-DD)',
                    '--date-to=DATE' => 'End date filter (YYYY-MM-DD)',
                    '--repository-id=ID' => 'Filter by repository',
                    '--format=FMT' => 'Output format: text, json, csv (default: text)',
                ],
                'schedule' => 'Weekly summary report recommended',
                'example' => '0 8 * * 1 cd {root} && php symfony integrity:report --summary --format=json >> /var/log/atom/integrity-report.json 2>&1',
                'duration' => 'Short',
                'category' => 'integrity',
            ],
            [
                'name' => 'Integrity: Export CSV / Auditor Pack',
                'command' => 'php symfony integrity:report',
                'description' => 'Export the verification ledger to CSV or generate a self-contained Auditor Pack ZIP for compliance audits. The auditor pack contains summary.html (standalone, no dependencies), exceptions.csv, and config-snapshot.json.',
                'options' => [
                    '--export-csv=PATH' => 'Export ledger to CSV file (use - for stdout)',
                    '--auditor-pack=PATH' => 'Generate auditor pack ZIP to specified path',
                    '--date-from=DATE' => 'Start date filter (YYYY-MM-DD)',
                    '--date-to=DATE' => 'End date filter (YYYY-MM-DD)',
                    '--repository-id=ID' => 'Filter by repository',
                ],
                'schedule' => 'Weekly auditor pack export recommended (e.g. Monday 8:30am)',
                'example' => '30 8 * * 1 cd {root} && php symfony integrity:report --auditor-pack=/tmp/integrity_weekly.zip >> /var/log/atom/integrity-report.log 2>&1',
                'duration' => 'Short to Medium',
                'category' => 'integrity',
            ],
            [
                'name' => 'Integrity: Retention Scan',
                'command' => 'php symfony integrity:retention',
                'description' => 'Scan for records that have passed their retention period and add them to the disposition review queue. Evaluates all enabled retention policies based on their trigger type (ingest date, last modified, closure date, last access) and scope.',
                'options' => [
                    '--scan-eligible' => 'Scan for eligible disposition candidates',
                    '--policy-id=ID' => 'Filter scan to a specific policy',
                    '--list' => 'List all retention policies',
                    '--status' => 'Show retention and disposition status',
                ],
                'schedule' => 'Daily at 1am recommended',
                'example' => '0 1 * * * cd {root} && php symfony integrity:retention --scan-eligible >> /var/log/atom/integrity-retention.log 2>&1',
                'duration' => 'Short to Medium',
                'category' => 'integrity',
            ],
            [
                'name' => 'Integrity: Process Dispositions',
                'command' => 'php symfony integrity:retention',
                'description' => 'Process approved disposition queue entries by marking them as disposed. IMPORTANT: This only marks the status — it does NOT delete any records or files. Actual deletion is a separate manual process outside the plugin.',
                'options' => [
                    '--process-queue' => 'Process all approved dispositions',
                ],
                'schedule' => 'Daily at 2am recommended (after retention scan)',
                'example' => '0 2 * * * cd {root} && php symfony integrity:retention --process-queue >> /var/log/atom/integrity-retention.log 2>&1',
                'duration' => 'Short',
                'category' => 'integrity',
            ],
            [
                'name' => 'Integrity: Legal Hold Management',
                'command' => 'php symfony integrity:retention',
                'description' => 'Place or release legal holds on information objects. Legal holds prevent records from being disposed of, even if past their retention period. All hold actions are logged to the integrity ledger for audit trail.',
                'options' => [
                    '--hold=IO_ID' => 'Place a legal hold on the specified information object',
                    '--release=HOLD_ID' => 'Release a legal hold by hold ID',
                    '--reason="TEXT"' => 'Reason for placing the hold (required for audit trail)',
                ],
                'schedule' => 'On demand only',
                'example' => 'cd {root} && php symfony integrity:retention --hold=12345 --reason="Legal investigation ref LH-2026-001"',
                'duration' => 'Short',
                'category' => 'integrity',
            ],

            // ---------------------------------------------------------
            // Accession Management V2 (ahgAccessionManagePlugin)
            // ---------------------------------------------------------
            [
                'name' => 'Accession: Intake Queue Stats',
                'command' => 'php symfony accession:intake --stats',
                'description' => 'Displays intake queue statistics including counts by status, queue depth (submitted + under review), average processing time, and overdue items (>7 days).',
                'options' => [],
                'schedule' => 'Daily for monitoring or on demand',
                'example' => '0 8 * * * cd {root} && php symfony accession:intake --stats >> /var/log/atom/accession-intake.log 2>&1',
                'duration' => 'Short',
                'category' => 'ahg',
            ],
            [
                'name' => 'Accession: Intake Queue List',
                'command' => 'php symfony accession:intake --queue',
                'description' => 'Lists the current intake queue with accession identifiers, titles, status, priority, and assigned users. Supports filtering by status.',
                'options' => [
                    '--status=STATUS' => 'Filter by status (draft, submitted, under_review, accepted, rejected, returned)',
                    '--priority=PRIORITY' => 'Filter by priority (low, normal, high, urgent)',
                ],
                'schedule' => 'On demand',
                'example' => 'cd {root} && php symfony accession:intake --queue --status=submitted',
                'duration' => 'Short',
                'category' => 'ahg',
            ],
            [
                'name' => 'Accession: Assign to User',
                'command' => 'php symfony accession:intake --assign=ID --user=USER_ID',
                'description' => 'Assigns an accession to a staff member for review. Records the assignment in the chain-of-custody timeline.',
                'options' => [
                    '--assign=ID' => 'Accession ID to assign',
                    '--user=USER_ID' => 'User ID to assign to',
                ],
                'schedule' => 'On demand',
                'example' => 'cd {root} && php symfony accession:intake --assign=123 --user=5',
                'duration' => 'Short',
                'category' => 'ahg',
            ],
            [
                'name' => 'Accession: Accept/Reject',
                'command' => 'php symfony accession:intake --accept=ID',
                'description' => 'Accept or reject an accession via CLI. Acceptance sets status to accepted and records the timestamp. Rejection requires a reason.',
                'options' => [
                    '--accept=ID' => 'Accept accession by ID',
                    '--reject=ID' => 'Reject accession by ID',
                    '--reason="TEXT"' => 'Reason for rejection (required with --reject)',
                ],
                'schedule' => 'On demand',
                'example' => 'cd {root} && php symfony accession:intake --reject=123 --reason="Incomplete documentation"',
                'duration' => 'Short',
                'category' => 'ahg',
            ],
            [
                'name' => 'Accession: View Checklist',
                'command' => 'php symfony accession:intake --checklist=ID',
                'description' => 'Displays the intake checklist for a specific accession, showing item labels and completion status.',
                'options' => [
                    '--checklist=ID' => 'Accession ID to view checklist for',
                ],
                'schedule' => 'On demand',
                'example' => 'cd {root} && php symfony accession:intake --checklist=123',
                'duration' => 'Short',
                'category' => 'ahg',
            ],
            [
                'name' => 'Accession: View Timeline',
                'command' => 'php symfony accession:intake --timeline=ID',
                'description' => 'Displays the chain-of-custody timeline for a specific accession, showing all events with actor, timestamp, and description.',
                'options' => [
                    '--timeline=ID' => 'Accession ID to view timeline for',
                ],
                'schedule' => 'On demand',
                'example' => 'cd {root} && php symfony accession:intake --timeline=123',
                'duration' => 'Short',
                'category' => 'ahg',
            ],
            [
                'name' => 'Accession: Status Report',
                'command' => 'php symfony accession:report --status',
                'description' => 'Generates a summary report showing total accessions, counts by intake status, queue depth, average processing time, overdue items, and the 10 most recent accessions.',
                'options' => [],
                'schedule' => 'Daily or weekly for management reporting',
                'example' => '0 7 * * 1 cd {root} && php symfony accession:report --status >> /var/log/atom/accession-report.log 2>&1',
                'duration' => 'Short',
                'category' => 'ahg',
            ],
            [
                'name' => 'Accession: Portfolio Valuation Report',
                'command' => 'php symfony accession:report --valuation',
                'description' => 'Generates a GRAP 103 / IPSAS 45 compliant portfolio valuation report showing total value by currency, valuation type breakdown, and valued accession count.',
                'options' => [
                    '--repository=ID' => 'Filter by repository ID',
                ],
                'schedule' => 'Monthly or quarterly for financial reporting',
                'example' => '0 6 1 * * cd {root} && php symfony accession:report --valuation >> /var/log/atom/accession-valuation.log 2>&1',
                'duration' => 'Short',
                'category' => 'ahg',
            ],
            [
                'name' => 'Accession: Export CSV',
                'command' => 'php symfony accession:report --export-csv',
                'description' => 'Exports all accessions with V2 fields (status, priority, submitted/accepted dates) to a CSV file in the downloads/ directory.',
                'options' => [
                    '--repository=ID' => 'Filter by repository ID',
                    '--date-from=YYYY-MM-DD' => 'Filter from date',
                    '--date-to=YYYY-MM-DD' => 'Filter to date',
                ],
                'schedule' => 'On demand or monthly for archives',
                'example' => '0 5 1 * * cd {root} && php symfony accession:report --export-csv --date-from=$(date -d "last month" +%%Y-%%m-01) >> /var/log/atom/accession-export.log 2>&1',
                'duration' => 'Medium',
                'category' => 'ahg',
            ],

            // === Authority Records (ahgAuthorityPlugin) ===
            [
                'name' => 'Authority: Completeness Scan',
                'command' => 'php symfony authority:completeness-scan',
                'description' => 'Scans all authority records and calculates completeness scores based on ISAAR(CPF) field population, external identifiers, relations, and linked resources.',
                'options' => [
                    '--limit=N' => 'Limit the number of actors to process (default: all)',
                ],
                'schedule' => 'Daily or weekly',
                'example' => '0 3 * * * cd {root} && php symfony authority:completeness-scan >> /var/log/atom/authority-completeness.log 2>&1',
                'duration' => 'Medium',
                'category' => 'ahg',
            ],
            [
                'name' => 'Authority: NER Pipeline',
                'command' => 'php symfony authority:ner-pipeline',
                'description' => 'Processes unlinked NER entities and creates stub authority records for persons and organizations above the confidence threshold.',
                'options' => [
                    '--dry-run' => 'Show what would be created without making changes',
                    '--threshold=N' => 'Minimum confidence score (0.0-1.0, default: from settings)',
                ],
                'schedule' => 'After NER extraction or daily',
                'example' => '0 4 * * * cd {root} && php symfony authority:ner-pipeline >> /var/log/atom/authority-ner.log 2>&1',
                'duration' => 'Medium',
                'category' => 'ahg',
            ],
            [
                'name' => 'Authority: Dedup Scan',
                'command' => 'php symfony authority:dedup-scan',
                'description' => 'Scans authority records for potential duplicates using name similarity (Jaro-Winkler), date overlap, and shared identifier matching.',
                'options' => [
                    '--limit=N' => 'Limit the number of actors to compare (default: all)',
                ],
                'schedule' => 'Weekly',
                'example' => '0 2 * * 0 cd {root} && php symfony authority:dedup-scan >> /var/log/atom/authority-dedup.log 2>&1',
                'duration' => 'Long',
                'category' => 'ahg',
            ],
            [
                'name' => 'Authority: Merge Report',
                'command' => 'php symfony authority:merge-report',
                'description' => 'Generates a summary report of all merge/split operations with transferred relations, resources, and contacts.',
                'options' => [],
                'schedule' => 'Monthly or on demand',
                'example' => '0 6 1 * * cd {root} && php symfony authority:merge-report >> /var/log/atom/authority-merge.log 2>&1',
                'duration' => 'Short',
                'category' => 'ahg',
            ],
            [
                'name' => 'Authority: Function Sync',
                'command' => 'php symfony authority:function-sync',
                'description' => 'Validates actor-function links, reports orphaned references where actors or functions have been deleted, and optionally cleans up invalid links.',
                'options' => [
                    '--clean' => 'Remove orphaned links (default: report only)',
                ],
                'schedule' => 'After function edits or daily',
                'example' => '0 5 * * * cd {root} && php symfony authority:function-sync >> /var/log/atom/authority-function-sync.log 2>&1',
                'duration' => 'Short',
                'category' => 'ahg',
            ],

            // ============================================
            // QUEUE ENGINE
            // ============================================
            [
                'name' => 'Queue Worker',
                'command' => 'php atom-framework/bin/atom queue:work',
                'description' => 'Persistent queue worker that polls for and processes background jobs. Should run as a systemd service, not a cron job. Supports multiple queues, memory limits, and graceful shutdown.',
                'options' => [
                    '--queue=QUEUES' => 'Comma-separated queue names (default: default)',
                    '--once' => 'Process one job then exit',
                    '--sleep=N' => 'Seconds to sleep when idle (default: 3)',
                    '--max-jobs=N' => 'Exit after N jobs (0 = unlimited)',
                    '--max-memory=N' => 'Exit at memory limit in MB (default: 256)',
                    '--timeout=N' => 'Per-job timeout in seconds (default: 300)',
                ],
                'schedule' => 'Run as systemd service (atom-queue-worker@default.service)',
                'example' => "# sudo cp atom-framework/packaging/templates/systemd/atom-queue-worker@.service /etc/systemd/system/\n# sudo systemctl daemon-reload\n# sudo systemctl enable --now atom-queue-worker@default",
                'duration' => 'Continuous',
                'category' => 'queue',
            ],
            [
                'name' => 'Queue Status',
                'command' => 'php atom-framework/bin/atom queue:status',
                'description' => 'Shows per-queue job counts by status (pending, running, completed, failed) and lists active workers.',
                'options' => [
                    '--queue=NAME' => 'Filter by queue name',
                ],
                'schedule' => 'Run on demand for monitoring',
                'example' => 'cd {root} && php atom-framework/bin/atom queue:status',
                'duration' => 'Short',
                'category' => 'queue',
            ],
            [
                'name' => 'Queue Cleanup',
                'command' => 'php atom-framework/bin/atom queue:cleanup',
                'description' => 'Purges completed, cancelled, and failed job records older than a specified number of days. Also cleans up associated log entries and batch records.',
                'options' => [
                    '--days=N' => 'Delete items older than N days (default: 30)',
                ],
                'schedule' => 'Daily at 3am',
                'example' => '0 3 * * * cd {root} && php atom-framework/bin/atom queue:cleanup --days=30 >> /var/log/atom/queue-cleanup.log 2>&1',
                'duration' => 'Short',
                'category' => 'queue',
            ],
            [
                'name' => 'Queue Retry Failed',
                'command' => 'php atom-framework/bin/atom queue:retry --all',
                'description' => 'Moves all permanently failed jobs back to the queue for re-processing. Failed jobs are reset with fresh attempt counts.',
                'options' => [
                    '--all' => 'Retry all failed jobs',
                    'id' => 'Retry a specific failed job by ID',
                ],
                'schedule' => 'Run on demand or weekly for recovery',
                'example' => '0 6 * * 1 cd {root} && php atom-framework/bin/atom queue:retry --all >> /var/log/atom/queue-retry.log 2>&1',
                'duration' => 'Short',
                'category' => 'queue',
            ],
            [
                'name' => 'Queue Failed — List / Flush',
                'command' => 'php atom-framework/bin/atom queue:failed',
                'description' => 'Lists failed jobs from the archive table, or flushes (deletes) all failed records.',
                'options' => [
                    '--flush' => 'Delete all failed job records',
                    '--limit=N' => 'Number of entries to display (default: 25)',
                ],
                'schedule' => 'Run on demand for housekeeping',
                'example' => 'cd {root} && php atom-framework/bin/atom queue:failed --flush',
                'duration' => 'Short',
                'category' => 'queue',
            ],
        ];
    }

    protected function groupByCategory(array $jobs): array
    {
        $categories = [
            'search' => ['title' => 'Search & Indexing', 'icon' => 'bi-search', 'jobs' => []],
            'jobs' => ['title' => 'Background Jobs', 'icon' => 'bi-gear-wide-connected', 'jobs' => []],
            'cache' => ['title' => 'Cache Management', 'icon' => 'bi-lightning', 'jobs' => []],
            'digitalobjects' => ['title' => 'Digital Objects', 'icon' => 'bi-image', 'jobs' => []],
            'findingaids' => ['title' => 'Finding Aids', 'icon' => 'bi-file-earmark-pdf', 'jobs' => []],
            'import' => ['title' => 'Import & Export', 'icon' => 'bi-arrow-left-right', 'jobs' => []],
            'oai' => ['title' => 'OAI-PMH', 'icon' => 'bi-cloud-download', 'jobs' => []],
            'preservation' => ['title' => 'Digital Preservation', 'icon' => 'bi-shield-lock', 'jobs' => []],
            'integrity' => ['title' => 'Integrity Assurance', 'icon' => 'bi-shield-check', 'jobs' => []],
            'metadata' => ['title' => 'Metadata Export', 'icon' => 'bi-file-earmark-code', 'jobs' => []],
            'doi' => ['title' => 'DOI Management', 'icon' => 'bi-link-45deg', 'jobs' => []],
            'maintenance' => ['title' => 'Maintenance', 'icon' => 'bi-wrench', 'jobs' => []],
            'audit' => ['title' => 'Audit & Logging', 'icon' => 'bi-journal-text', 'jobs' => []],
            'ahg' => ['title' => 'AHG Extensions', 'icon' => 'bi-puzzle', 'jobs' => []],
            'ric' => ['title' => 'RiC Triplestore', 'icon' => 'bi-diagram-3', 'jobs' => []],
            'queue' => ['title' => 'Queue Engine', 'icon' => 'bi-stack', 'jobs' => []],
        ];

        foreach ($jobs as $job) {
            $cat = $job['category'] ?? 'maintenance';
            if (isset($categories[$cat])) {
                $categories[$cat]['jobs'][] = $job;
            }
        }

        // Remove empty categories
        return array_filter($categories, fn($cat) => !empty($cat['jobs']));
    }
}
