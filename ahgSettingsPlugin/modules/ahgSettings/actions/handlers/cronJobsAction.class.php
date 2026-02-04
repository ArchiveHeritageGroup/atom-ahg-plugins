<?php

/*
 * Cron Jobs Information Page
 *
 * Displays all available cron jobs with explanations and scheduling examples
 */

class SettingsCronJobsAction extends sfAction
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
                'description' => 'Processes pending workflow tasks including sending notification emails and escalating overdue tasks. Run regularly to ensure timely workflow processing.',
                'options' => [
                    '--dry-run' => 'Show what would be processed without making changes',
                    '--escalate' => 'Escalate overdue tasks to supervisors',
                ],
                'schedule' => 'Every 15 minutes',
                'example' => '*/15 * * * * cd {root} && php symfony workflow:process >> /var/log/atom/workflow-process.log 2>&1',
                'duration' => 'Short',
                'category' => 'ahg',
            ],
            [
                'name' => 'Workflow Status',
                'command' => 'php symfony workflow:status',
                'description' => 'Displays current workflow status including pending tasks, task assignments, and workflow performance metrics.',
                'options' => [
                    '--pending' => 'Show only pending tasks',
                    '--overdue' => 'Show only overdue tasks',
                    '--format=FORMAT' => 'Output format (table, json, csv)',
                ],
                'schedule' => 'Run manually or daily for reporting',
                'example' => '0 8 * * * cd {root} && php symfony workflow:status --format=csv > /var/log/atom/workflow-daily.csv',
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
            'metadata' => ['title' => 'Metadata Export', 'icon' => 'bi-file-earmark-code', 'jobs' => []],
            'doi' => ['title' => 'DOI Management', 'icon' => 'bi-link-45deg', 'jobs' => []],
            'maintenance' => ['title' => 'Maintenance', 'icon' => 'bi-wrench', 'jobs' => []],
            'audit' => ['title' => 'Audit & Logging', 'icon' => 'bi-journal-text', 'jobs' => []],
            'ahg' => ['title' => 'AHG Extensions', 'icon' => 'bi-puzzle', 'jobs' => []],
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
