<?php

/*
 * Services Monitoring Dashboard
 *
 * Monitors health of all system services and sends notifications on failures
 */

use Illuminate\Database\Capsule\Manager as DB;

class SettingsServicesAction extends sfAction
{
    // Service status constants
    const STATUS_OK = 'ok';
    const STATUS_WARNING = 'warning';
    const STATUS_ERROR = 'error';
    const STATUS_UNKNOWN = 'unknown';

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

        $this->i18n = $this->context->i18n;
        $this->form = new sfForm();
        $this->form->getWidgetSchema()->setNameFormat('services[%s]');

        // Handle AJAX status check
        if ($request->isXmlHttpRequest() && $request->getParameter('check')) {
            $this->checkAllServices();
            $this->getResponse()->setContentType('application/json');

            return $this->renderText(json_encode([
                'services' => $this->services,
                'timestamp' => date('Y-m-d H:i:s'),
            ]));
        }

        // Handle test notification
        if ($request->isMethod('post') && $request->getParameter('test_notification')) {
            $this->sendTestNotification();
            $this->getUser()->setFlash('notice', $this->i18n->__('Test notification sent.'));
            $this->redirect(['module' => 'ahgSettings', 'action' => 'services']);
        }

        // Handle settings save
        if ($request->isMethod('post') && $request->getParameter('save_settings')) {
            $this->saveNotificationSettings($request);
            $this->getUser()->setFlash('notice', $this->i18n->__('Notification settings saved.'));
            $this->redirect(['module' => 'ahgSettings', 'action' => 'services']);
        }

        // Load notification settings
        $this->loadNotificationSettings();

        // Check all services
        $this->checkAllServices();

        // Check for status changes and send notifications
        $this->checkAndNotify();

        // Get service history
        $this->serviceHistory = $this->getServiceHistory();
    }

    protected function checkAllServices()
    {
        $this->services = [];

        // Core Services
        $this->services['mysql'] = $this->checkMySQL();
        $this->services['elasticsearch'] = $this->checkElasticsearch();
        $this->services['php_fpm'] = $this->checkPHPFPM();
        $this->services['gearman'] = $this->checkGearman();
        $this->services['atom_worker'] = $this->checkAtomWorker();

        // Optional Services
        $this->services['redis'] = $this->checkRedis();
        $this->services['memcached'] = $this->checkMemcached();
        $this->services['smtp'] = $this->checkSMTP();

        // Plugin-specific Services
        if ($this->context->getConfiguration()->isPluginEnabled('arStorageServicePlugin')) {
            $this->services['storage_service'] = $this->checkStorageService();
        }

        if ($this->context->getConfiguration()->isPluginEnabled('ahgAIPlugin')) {
            $this->services['ai_python'] = $this->checkAIPython();
        }

        if ($this->context->getConfiguration()->isPluginEnabled('ahgICIPPlugin')) {
            $this->services['local_contexts'] = $this->checkLocalContexts();
        }

        // External Services
        $this->services['cantaloupe'] = $this->checkCantaloupe();
        $this->services['disk_space'] = $this->checkDiskSpace();

        // Calculate overall status
        $this->overallStatus = $this->calculateOverallStatus();
    }

    protected function checkMySQL(): array
    {
        $start = microtime(true);
        try {
            $result = DB::select('SELECT 1 as ok');
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            // Check connection count
            $processCount = DB::select('SHOW STATUS LIKE "Threads_connected"');
            $connections = $processCount[0]->Value ?? 0;

            return [
                'name' => 'MySQL Database',
                'status' => self::STATUS_OK,
                'message' => "Connected ({$connections} active connections)",
                'response_time' => $responseTime,
                'icon' => 'bi-database',
                'category' => 'core',
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'MySQL Database',
                'status' => self::STATUS_ERROR,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'response_time' => null,
                'icon' => 'bi-database',
                'category' => 'core',
            ];
        }
    }

    protected function checkElasticsearch(): array
    {
        $start = microtime(true);
        try {
            $host = sfConfig::get('app_elasticsearch_host', 'localhost');
            $port = sfConfig::get('app_elasticsearch_port', '9200');

            // Auto-detect engine type (Elasticsearch or OpenSearch)
            $engineName = 'Search Engine';
            $engineVersion = '';
            if (class_exists('SearchEngineFactory')) {
                $engineName = SearchEngineFactory::getEngineName($host, (int) $port);
                $engineVersion = SearchEngineFactory::getEngineVersion($host, (int) $port);
            }

            $ch = curl_init("http://{$host}:{$port}/_cluster/health");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_CONNECTTIMEOUT => 3,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $responseTime = round((microtime(true) - $start) * 1000, 2);

            if ($httpCode === 200 && $response) {
                $health = json_decode($response, true);
                $clusterStatus = $health['status'] ?? 'unknown';

                $status = match ($clusterStatus) {
                    'green' => self::STATUS_OK,
                    'yellow' => self::STATUS_WARNING,
                    default => self::STATUS_ERROR,
                };

                $versionInfo = $engineVersion ? " v{$engineVersion}" : '';

                return [
                    'name' => $engineName . $versionInfo,
                    'status' => $status,
                    'message' => "Cluster: {$clusterStatus}, Nodes: " . ($health['number_of_nodes'] ?? '?'),
                    'response_time' => $responseTime,
                    'icon' => 'bi-search',
                    'category' => 'core',
                ];
            }

            return [
                'name' => $engineName ?: 'Search Engine',
                'status' => self::STATUS_ERROR,
                'message' => "HTTP {$httpCode} - Service unavailable",
                'response_time' => $responseTime,
                'icon' => 'bi-search',
                'category' => 'core',
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Search Engine',
                'status' => self::STATUS_ERROR,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'response_time' => null,
                'icon' => 'bi-search',
                'category' => 'core',
            ];
        }
    }

    protected function checkPHPFPM(): array
    {
        // If we're running, PHP-FPM is working
        $version = PHP_VERSION;
        $memory = ini_get('memory_limit');

        return [
            'name' => 'PHP-FPM',
            'status' => self::STATUS_OK,
            'message' => "PHP {$version}, Memory limit: {$memory}",
            'response_time' => null,
            'icon' => 'bi-filetype-php',
            'category' => 'core',
        ];
    }

    protected function checkGearman(): array
    {
        $start = microtime(true);
        try {
            $host = sfConfig::get('app_gearman_host', '127.0.0.1');
            $port = sfConfig::get('app_gearman_port', 4730);

            $socket = @fsockopen($host, $port, $errno, $errstr, 3);

            if ($socket) {
                fwrite($socket, "status\n");
                $response = fread($socket, 8192);
                fclose($socket);

                $responseTime = round((microtime(true) - $start) * 1000, 2);

                // Count workers
                $lines = explode("\n", trim($response));
                $workers = 0;
                foreach ($lines as $line) {
                    if (preg_match('/\d+\s+\d+\s+(\d+)/', $line, $m)) {
                        $workers += (int) $m[1];
                    }
                }

                return [
                    'name' => 'Gearman Job Server',
                    'status' => self::STATUS_OK,
                    'message' => "Running, {$workers} workers available",
                    'response_time' => $responseTime,
                    'icon' => 'bi-gear',
                    'category' => 'core',
                ];
            }

            return [
                'name' => 'Gearman Job Server',
                'status' => self::STATUS_WARNING,
                'message' => 'Not running (background jobs unavailable)',
                'response_time' => null,
                'icon' => 'bi-gear',
                'category' => 'core',
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Gearman Job Server',
                'status' => self::STATUS_WARNING,
                'message' => 'Not available: ' . $e->getMessage(),
                'response_time' => null,
                'icon' => 'bi-gear',
                'category' => 'core',
            ];
        }
    }

    protected function checkAtomWorker(): array
    {
        try {
            $output = [];
            $returnCode = 0;
            exec('systemctl is-active atom-worker 2>&1', $output, $returnCode);

            $status = trim(implode('', $output));

            if ($status === 'active') {
                // Get more details
                $details = [];
                exec('systemctl show atom-worker --property=MainPID,MemoryCurrent 2>&1', $details);
                $pid = '';
                $memory = '';
                foreach ($details as $line) {
                    if (str_starts_with($line, 'MainPID=')) {
                        $pid = substr($line, 8);
                    }
                    if (str_starts_with($line, 'MemoryCurrent=')) {
                        $bytes = (int) substr($line, 14);
                        $memory = round($bytes / 1024 / 1024, 1) . ' MB';
                    }
                }

                return [
                    'name' => 'AtoM Worker',
                    'status' => self::STATUS_OK,
                    'message' => "Running (PID: {$pid}, Memory: {$memory})",
                    'response_time' => null,
                    'icon' => 'bi-cpu',
                    'category' => 'core',
                ];
            }

            return [
                'name' => 'AtoM Worker',
                'status' => self::STATUS_ERROR,
                'message' => "Not running (status: {$status})",
                'response_time' => null,
                'icon' => 'bi-cpu',
                'category' => 'core',
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'AtoM Worker',
                'status' => self::STATUS_UNKNOWN,
                'message' => 'Cannot check: ' . $e->getMessage(),
                'response_time' => null,
                'icon' => 'bi-cpu',
                'category' => 'core',
            ];
        }
    }

    protected function checkRedis(): array
    {
        $start = microtime(true);
        try {
            $host = sfConfig::get('app_redis_host', '127.0.0.1');
            $port = sfConfig::get('app_redis_port', 6379);

            $socket = @fsockopen($host, $port, $errno, $errstr, 3);

            if ($socket) {
                fwrite($socket, "PING\r\n");
                $response = fread($socket, 1024);
                fclose($socket);

                $responseTime = round((microtime(true) - $start) * 1000, 2);

                if (str_contains($response, 'PONG')) {
                    return [
                        'name' => 'Redis Cache',
                        'status' => self::STATUS_OK,
                        'message' => 'Connected and responding',
                        'response_time' => $responseTime,
                        'icon' => 'bi-lightning',
                        'category' => 'optional',
                    ];
                }
            }

            return [
                'name' => 'Redis Cache',
                'status' => self::STATUS_UNKNOWN,
                'message' => 'Not configured or not running',
                'response_time' => null,
                'icon' => 'bi-lightning',
                'category' => 'optional',
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Redis Cache',
                'status' => self::STATUS_UNKNOWN,
                'message' => 'Not available',
                'response_time' => null,
                'icon' => 'bi-lightning',
                'category' => 'optional',
            ];
        }
    }

    protected function checkMemcached(): array
    {
        $start = microtime(true);
        try {
            $host = sfConfig::get('app_memcached_host', '127.0.0.1');
            $port = sfConfig::get('app_memcached_port', 11211);

            $socket = @fsockopen($host, $port, $errno, $errstr, 3);

            if ($socket) {
                fwrite($socket, "stats\r\n");
                $response = fread($socket, 8192);
                fwrite($socket, "quit\r\n");
                fclose($socket);

                $responseTime = round((microtime(true) - $start) * 1000, 2);

                if (str_contains($response, 'STAT')) {
                    // Extract hit ratio
                    preg_match('/STAT get_hits (\d+)/', $response, $hits);
                    preg_match('/STAT get_misses (\d+)/', $response, $misses);
                    $hitRatio = 'N/A';
                    if (isset($hits[1], $misses[1])) {
                        $total = (int) $hits[1] + (int) $misses[1];
                        if ($total > 0) {
                            $hitRatio = round(((int) $hits[1] / $total) * 100, 1) . '%';
                        }
                    }

                    return [
                        'name' => 'Memcached',
                        'status' => self::STATUS_OK,
                        'message' => "Running, Hit ratio: {$hitRatio}",
                        'response_time' => $responseTime,
                        'icon' => 'bi-memory',
                        'category' => 'optional',
                    ];
                }
            }

            return [
                'name' => 'Memcached',
                'status' => self::STATUS_UNKNOWN,
                'message' => 'Not configured or not running',
                'response_time' => null,
                'icon' => 'bi-memory',
                'category' => 'optional',
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Memcached',
                'status' => self::STATUS_UNKNOWN,
                'message' => 'Not available',
                'response_time' => null,
                'icon' => 'bi-memory',
                'category' => 'optional',
            ];
        }
    }

    protected function checkSMTP(): array
    {
        $start = microtime(true);
        try {
            $host = sfConfig::get('app_smtp_host', '');
            $port = sfConfig::get('app_smtp_port', 25);

            if (empty($host)) {
                return [
                    'name' => 'SMTP Email',
                    'status' => self::STATUS_UNKNOWN,
                    'message' => 'Not configured',
                    'response_time' => null,
                    'icon' => 'bi-envelope',
                    'category' => 'optional',
                ];
            }

            $socket = @fsockopen($host, $port, $errno, $errstr, 5);

            if ($socket) {
                $response = fgets($socket, 1024);
                fclose($socket);

                $responseTime = round((microtime(true) - $start) * 1000, 2);

                if (str_starts_with($response, '220')) {
                    return [
                        'name' => 'SMTP Email',
                        'status' => self::STATUS_OK,
                        'message' => "Connected to {$host}:{$port}",
                        'response_time' => $responseTime,
                        'icon' => 'bi-envelope',
                        'category' => 'optional',
                    ];
                }
            }

            return [
                'name' => 'SMTP Email',
                'status' => self::STATUS_ERROR,
                'message' => "Cannot connect to {$host}:{$port}",
                'response_time' => null,
                'icon' => 'bi-envelope',
                'category' => 'optional',
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'SMTP Email',
                'status' => self::STATUS_ERROR,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'response_time' => null,
                'icon' => 'bi-envelope',
                'category' => 'optional',
            ];
        }
    }

    protected function checkStorageService(): array
    {
        $start = microtime(true);
        try {
            $baseUrl = DB::table('setting')
                ->join('setting_i18n', 'setting.id', '=', 'setting_i18n.id')
                ->where('setting.name', 'storage_service_api_url')
                ->value('setting_i18n.value');

            if (empty($baseUrl)) {
                return [
                    'name' => 'Archivematica Storage',
                    'status' => self::STATUS_UNKNOWN,
                    'message' => 'Not configured',
                    'response_time' => null,
                    'icon' => 'bi-archive',
                    'category' => 'plugin',
                ];
            }

            $ch = curl_init(rtrim($baseUrl, '/') . '/api/v2/pipeline/');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $responseTime = round((microtime(true) - $start) * 1000, 2);

            if ($httpCode >= 200 && $httpCode < 300) {
                return [
                    'name' => 'Archivematica Storage',
                    'status' => self::STATUS_OK,
                    'message' => 'API responding',
                    'response_time' => $responseTime,
                    'icon' => 'bi-archive',
                    'category' => 'plugin',
                ];
            }

            return [
                'name' => 'Archivematica Storage',
                'status' => self::STATUS_ERROR,
                'message' => "HTTP {$httpCode}",
                'response_time' => $responseTime,
                'icon' => 'bi-archive',
                'category' => 'plugin',
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Archivematica Storage',
                'status' => self::STATUS_ERROR,
                'message' => 'Error: ' . $e->getMessage(),
                'response_time' => null,
                'icon' => 'bi-archive',
                'category' => 'plugin',
            ];
        }
    }

    protected function checkAIPython(): array
    {
        $start = microtime(true);
        try {
            // Check if Python and required packages are available
            $output = [];
            $returnCode = 0;
            exec('python3 -c "import spacy; import argostranslate" 2>&1', $output, $returnCode);

            $responseTime = round((microtime(true) - $start) * 1000, 2);

            if ($returnCode === 0) {
                return [
                    'name' => 'AI Python Services',
                    'status' => self::STATUS_OK,
                    'message' => 'Python packages available',
                    'response_time' => $responseTime,
                    'icon' => 'bi-robot',
                    'category' => 'plugin',
                ];
            }

            return [
                'name' => 'AI Python Services',
                'status' => self::STATUS_WARNING,
                'message' => 'Missing packages: ' . implode(' ', $output),
                'response_time' => $responseTime,
                'icon' => 'bi-robot',
                'category' => 'plugin',
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'AI Python Services',
                'status' => self::STATUS_WARNING,
                'message' => 'Cannot verify: ' . $e->getMessage(),
                'response_time' => null,
                'icon' => 'bi-robot',
                'category' => 'plugin',
            ];
        }
    }

    protected function checkLocalContexts(): array
    {
        $start = microtime(true);
        try {
            $enabled = DB::table('icip_config')
                ->where('config_key', 'local_contexts_hub_enabled')
                ->value('config_value');

            if ($enabled !== '1') {
                return [
                    'name' => 'Local Contexts Hub',
                    'status' => self::STATUS_UNKNOWN,
                    'message' => 'Integration disabled',
                    'response_time' => null,
                    'icon' => 'bi-globe',
                    'category' => 'plugin',
                ];
            }

            $ch = curl_init('https://localcontextshub.org/api/v1/');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $responseTime = round((microtime(true) - $start) * 1000, 2);

            if ($httpCode >= 200 && $httpCode < 400) {
                return [
                    'name' => 'Local Contexts Hub',
                    'status' => self::STATUS_OK,
                    'message' => 'API reachable',
                    'response_time' => $responseTime,
                    'icon' => 'bi-globe',
                    'category' => 'plugin',
                ];
            }

            return [
                'name' => 'Local Contexts Hub',
                'status' => self::STATUS_WARNING,
                'message' => "HTTP {$httpCode}",
                'response_time' => $responseTime,
                'icon' => 'bi-globe',
                'category' => 'plugin',
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Local Contexts Hub',
                'status' => self::STATUS_WARNING,
                'message' => 'Cannot reach: ' . $e->getMessage(),
                'response_time' => null,
                'icon' => 'bi-globe',
                'category' => 'plugin',
            ];
        }
    }

    protected function checkCantaloupe(): array
    {
        $start = microtime(true);
        try {
            $port = 8182;
            $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 3);

            if ($socket) {
                fclose($socket);
                $responseTime = round((microtime(true) - $start) * 1000, 2);

                return [
                    'name' => 'Cantaloupe IIIF',
                    'status' => self::STATUS_OK,
                    'message' => "Running on port {$port}",
                    'response_time' => $responseTime,
                    'icon' => 'bi-image',
                    'category' => 'optional',
                ];
            }

            return [
                'name' => 'Cantaloupe IIIF',
                'status' => self::STATUS_UNKNOWN,
                'message' => 'Not running (IIIF images unavailable)',
                'response_time' => null,
                'icon' => 'bi-image',
                'category' => 'optional',
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Cantaloupe IIIF',
                'status' => self::STATUS_UNKNOWN,
                'message' => 'Not available',
                'response_time' => null,
                'icon' => 'bi-image',
                'category' => 'optional',
            ];
        }
    }

    protected function checkDiskSpace(): array
    {
        try {
            $uploadPath = sfConfig::get('sf_upload_dir', '/usr/share/nginx/archive/uploads');
            $totalSpace = disk_total_space($uploadPath);
            $freeSpace = disk_free_space($uploadPath);
            $usedPercent = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 1);

            $freeGB = round($freeSpace / 1024 / 1024 / 1024, 1);

            $status = self::STATUS_OK;
            if ($usedPercent > 90) {
                $status = self::STATUS_ERROR;
            } elseif ($usedPercent > 80) {
                $status = self::STATUS_WARNING;
            }

            return [
                'name' => 'Disk Space',
                'status' => $status,
                'message' => "{$usedPercent}% used, {$freeGB} GB free",
                'response_time' => null,
                'icon' => 'bi-hdd',
                'category' => 'system',
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Disk Space',
                'status' => self::STATUS_UNKNOWN,
                'message' => 'Cannot determine',
                'response_time' => null,
                'icon' => 'bi-hdd',
                'category' => 'system',
            ];
        }
    }

    protected function calculateOverallStatus(): string
    {
        $hasError = false;
        $hasWarning = false;

        foreach ($this->services as $service) {
            if ($service['status'] === self::STATUS_ERROR) {
                $hasError = true;
            } elseif ($service['status'] === self::STATUS_WARNING) {
                $hasWarning = true;
            }
        }

        if ($hasError) {
            return self::STATUS_ERROR;
        }
        if ($hasWarning) {
            return self::STATUS_WARNING;
        }

        return self::STATUS_OK;
    }

    protected function loadNotificationSettings()
    {
        $this->notificationSettings = [
            'enabled' => $this->getConfig('services_notification_enabled', '0'),
            'email' => $this->getConfig('services_notification_email', ''),
            'check_interval' => $this->getConfig('services_check_interval', '5'),
            'notify_on_warning' => $this->getConfig('services_notify_on_warning', '1'),
            'notify_on_recovery' => $this->getConfig('services_notify_on_recovery', '1'),
        ];

        // Set form defaults
        $this->form->setDefault('notification_enabled', $this->notificationSettings['enabled']);
        $this->form->setDefault('notification_email', $this->notificationSettings['email']);
        $this->form->setDefault('check_interval', $this->notificationSettings['check_interval']);
        $this->form->setDefault('notify_on_warning', $this->notificationSettings['notify_on_warning']);
        $this->form->setDefault('notify_on_recovery', $this->notificationSettings['notify_on_recovery']);

        // Add form widgets
        $yesNo = ['0' => $this->i18n->__('No'), '1' => $this->i18n->__('Yes')];

        $this->form->setWidget('notification_enabled', new sfWidgetFormSelectRadio([
            'choices' => $yesNo,
        ], ['class' => 'radio']));
        $this->form->setValidator('notification_enabled', new sfValidatorChoice([
            'choices' => array_keys($yesNo),
            'required' => false,
        ]));

        $this->form->setWidget('notification_email', new sfWidgetFormInput([], [
            'class' => 'form-control',
            'placeholder' => 'admin@example.com',
        ]));
        $this->form->setValidator('notification_email', new sfValidatorEmail([
            'required' => false,
        ]));

        $intervals = [
            '1' => $this->i18n->__('1 minute'),
            '5' => $this->i18n->__('5 minutes'),
            '15' => $this->i18n->__('15 minutes'),
            '30' => $this->i18n->__('30 minutes'),
            '60' => $this->i18n->__('1 hour'),
        ];
        $this->form->setWidget('check_interval', new sfWidgetFormChoice([
            'choices' => $intervals,
        ], ['class' => 'form-select']));
        $this->form->setValidator('check_interval', new sfValidatorChoice([
            'choices' => array_keys($intervals),
            'required' => false,
        ]));

        $this->form->setWidget('notify_on_warning', new sfWidgetFormSelectRadio([
            'choices' => $yesNo,
        ], ['class' => 'radio']));
        $this->form->setValidator('notify_on_warning', new sfValidatorChoice([
            'choices' => array_keys($yesNo),
            'required' => false,
        ]));

        $this->form->setWidget('notify_on_recovery', new sfWidgetFormSelectRadio([
            'choices' => $yesNo,
        ], ['class' => 'radio']));
        $this->form->setValidator('notify_on_recovery', new sfValidatorChoice([
            'choices' => array_keys($yesNo),
            'required' => false,
        ]));
    }

    protected function saveNotificationSettings($request)
    {
        $this->setConfig('services_notification_enabled', $request->getParameter('notification_enabled', '0'));
        $this->setConfig('services_notification_email', $request->getParameter('notification_email', ''));
        $this->setConfig('services_check_interval', $request->getParameter('check_interval', '5'));
        $this->setConfig('services_notify_on_warning', $request->getParameter('notify_on_warning', '1'));
        $this->setConfig('services_notify_on_recovery', $request->getParameter('notify_on_recovery', '1'));
    }

    protected function checkAndNotify()
    {
        if ($this->notificationSettings['enabled'] !== '1') {
            return;
        }

        $email = $this->notificationSettings['email'];
        if (empty($email)) {
            return;
        }

        // Get previous status
        $previousStatus = $this->getConfig('services_last_status', '{}');
        $previousStatus = json_decode($previousStatus, true) ?: [];

        $changes = [];
        $newStatus = [];

        foreach ($this->services as $key => $service) {
            $newStatus[$key] = $service['status'];

            $prevStat = $previousStatus[$key] ?? null;

            // Check for status change
            if ($prevStat !== null && $prevStat !== $service['status']) {
                // Service went down
                if ($service['status'] === self::STATUS_ERROR) {
                    $changes[] = [
                        'service' => $service['name'],
                        'type' => 'down',
                        'message' => $service['message'],
                    ];
                    $this->logServiceEvent($key, $service['name'], 'down', $service['message']);
                }
                // Service has warning
                elseif ($service['status'] === self::STATUS_WARNING && $this->notificationSettings['notify_on_warning'] === '1') {
                    $changes[] = [
                        'service' => $service['name'],
                        'type' => 'warning',
                        'message' => $service['message'],
                    ];
                    $this->logServiceEvent($key, $service['name'], 'warning', $service['message']);
                }
                // Service recovered
                elseif ($prevStat === self::STATUS_ERROR && $service['status'] === self::STATUS_OK && $this->notificationSettings['notify_on_recovery'] === '1') {
                    $changes[] = [
                        'service' => $service['name'],
                        'type' => 'recovered',
                        'message' => $service['message'],
                    ];
                    $this->logServiceEvent($key, $service['name'], 'recovered', $service['message']);
                }
            }
        }

        // Save new status
        $this->setConfig('services_last_status', json_encode($newStatus));
        $this->setConfig('services_last_check', date('Y-m-d H:i:s'));

        // Send notification if there are changes
        if (!empty($changes)) {
            $this->sendNotification($email, $changes);
        }
    }

    protected function sendNotification(string $email, array $changes)
    {
        $siteName = sfConfig::get('app_siteTitle', 'AtoM');
        $subject = "[{$siteName}] Service Status Alert";

        $body = "Service status changes detected:\n\n";

        foreach ($changes as $change) {
            $icon = match ($change['type']) {
                'down' => '🔴',
                'warning' => '🟡',
                'recovered' => '🟢',
                default => '⚪',
            };

            $body .= "{$icon} {$change['service']}: " . strtoupper($change['type']) . "\n";
            $body .= "   {$change['message']}\n\n";
        }

        $body .= "---\n";
        $body .= "Check services at: " . sfContext::getInstance()->getRouting()->generate('ahgSettings/services', [], true) . "\n";
        $body .= "Time: " . date('Y-m-d H:i:s') . "\n";

        // Use Swift Mailer if available, otherwise mail()
        try {
            if (class_exists('Swift_Message')) {
                $message = new Swift_Message($subject, $body);
                $message->setFrom(sfConfig::get('app_mail_from', 'noreply@' . $_SERVER['SERVER_NAME'] ?? 'localhost'));
                $message->setTo($email);

                sfContext::getInstance()->getMailer()->send($message);
            } else {
                mail($email, $subject, $body);
            }
        } catch (\Exception $e) {
            error_log('Failed to send service notification: ' . $e->getMessage());
        }
    }

    protected function sendTestNotification()
    {
        $email = $this->getConfig('services_notification_email', '');
        if (empty($email)) {
            return;
        }

        $changes = [[
            'service' => 'Test Service',
            'type' => 'down',
            'message' => 'This is a test notification to verify email delivery.',
        ]];

        $this->sendNotification($email, $changes);
    }

    protected function logServiceEvent(string $serviceKey, string $serviceName, string $eventType, string $message)
    {
        try {
            DB::table('service_monitor_log')->insert([
                'service_key' => $serviceKey,
                'service_name' => $serviceName,
                'event_type' => $eventType,
                'message' => $message,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            // Table may not exist yet, ignore
        }
    }

    protected function getServiceHistory(): array
    {
        try {
            return DB::table('service_monitor_log')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function getConfig(string $key, $default = null)
    {
        try {
            $value = DB::table('setting')
                ->join('setting_i18n', 'setting.id', '=', 'setting_i18n.id')
                ->where('setting.name', $key)
                ->value('setting_i18n.value');

            return $value !== null ? $value : $default;
        } catch (\Exception $e) {
            return $default;
        }
    }

    protected function setConfig(string $key, $value): void
    {
        try {
            $existing = DB::table('setting')->where('name', $key)->first();

            if ($existing) {
                DB::table('setting_i18n')
                    ->where('id', $existing->id)
                    ->update(['value' => $value]);
            } else {
                $id = DB::table('setting')->insertGetId([
                    'name' => $key,
                    'scope' => 'sfConfig',
                    'editable' => 1,
                    'deleteable' => 0,
                ]);
                DB::table('setting_i18n')->insert([
                    'id' => $id,
                    'culture' => 'en',
                    'value' => $value,
                ]);
            }
        } catch (\Exception $e) {
            error_log('Failed to save config: ' . $e->getMessage());
        }
    }
}
