<?php

use Illuminate\Database\Capsule\Manager as DB;

class StatisticsService
{
    protected ?array $config = null;
    protected ?array $botPatterns = null;

    // =========================================================================
    // CONFIGURATION
    // =========================================================================

    public function getConfig(string $key, $default = null)
    {
        if ($this->config === null) {
            $this->config = [];
            $settings = DB::table('ahg_statistics_config')->get();
            foreach ($settings as $setting) {
                $value = $setting->setting_value;
                if ($setting->setting_type === 'integer') {
                    $value = (int) $value;
                } elseif ($setting->setting_type === 'boolean') {
                    $value = (bool) $value;
                } elseif ($setting->setting_type === 'json') {
                    $value = json_decode($value, true);
                }
                $this->config[$setting->setting_name] = $value;
            }
        }
        return $this->config[$key] ?? $default;
    }

    public function setConfig(string $key, $value, string $type = 'string'): bool
    {
        if ($type === 'json' && is_array($value)) {
            $value = json_encode($value);
        }

        $exists = DB::table('ahg_statistics_config')->where('setting_name', $key)->exists();

        if ($exists) {
            DB::table('ahg_statistics_config')
                ->where('setting_name', $key)
                ->update(['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')]);
        } else {
            DB::table('ahg_statistics_config')->insert([
                'setting_name' => $key,
                'setting_value' => $value,
                'setting_type' => $type,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->config = null; // Reset cache
        return true;
    }

    // =========================================================================
    // EVENT LOGGING
    // =========================================================================

    /**
     * Log a usage event.
     */
    public function logEvent(
        string $eventType,
        string $objectType,
        int $objectId,
        ?int $digitalObjectId = null,
        ?string $searchQuery = null
    ): ?int {
        // Skip if configured to track authenticated only
        if ($this->getConfig('track_authenticated_only', false)) {
            $userId = $this->getCurrentUserId();
            if (!$userId) {
                return null;
            }
        }

        $ipAddress = $this->getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Check if bot
        $isBot = false;
        $botName = null;
        if ($this->getConfig('bot_filtering_enabled', true)) {
            [$isBot, $botName] = $this->detectBot($userAgent);
        }

        // Get repository ID
        $repositoryId = null;
        if ($objectType === 'information_object') {
            $repositoryId = DB::table('information_object')
                ->where('id', $objectId)
                ->value('repository_id');
        }

        // GeoIP lookup
        $geoData = $this->getGeoIpData($ipAddress);

        // Anonymize IP if configured
        $ipHash = null;
        if ($this->getConfig('anonymize_ip', true)) {
            $ipHash = hash('sha256', $ipAddress . date('Y-m'));
        }

        $eventId = DB::table('ahg_usage_event')->insertGetId([
            'event_type' => $eventType,
            'object_type' => $objectType,
            'object_id' => $objectId,
            'digital_object_id' => $digitalObjectId,
            'repository_id' => $repositoryId,
            'user_id' => $this->getCurrentUserId(),
            'session_id' => session_id() ?: null,
            'ip_address' => $this->getConfig('anonymize_ip', true) ? '0.0.0.0' : $ipAddress,
            'ip_hash' => $ipHash,
            'user_agent' => substr($userAgent, 0, 500),
            'referer' => isset($_SERVER['HTTP_REFERER']) ? substr($_SERVER['HTTP_REFERER'], 0, 1000) : null,
            'country_code' => $geoData['country_code'] ?? null,
            'country_name' => $geoData['country_name'] ?? null,
            'city' => $geoData['city'] ?? null,
            'region' => $geoData['region'] ?? null,
            'latitude' => $geoData['latitude'] ?? null,
            'longitude' => $geoData['longitude'] ?? null,
            'is_bot' => $isBot ? 1 : 0,
            'bot_name' => $botName,
            'search_query' => $searchQuery,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $eventId;
    }

    /**
     * Log a page view (convenience method).
     */
    public function logView(string $objectType, int $objectId): ?int
    {
        return $this->logEvent('view', $objectType, $objectId);
    }

    /**
     * Log a download (convenience method).
     */
    public function logDownload(int $objectId, int $digitalObjectId): ?int
    {
        return $this->logEvent('download', 'information_object', $objectId, $digitalObjectId);
    }

    // =========================================================================
    // BOT DETECTION
    // =========================================================================

    /**
     * Detect if user agent is a bot.
     */
    protected function detectBot(string $userAgent): array
    {
        if ($this->botPatterns === null) {
            $this->botPatterns = DB::table('ahg_bot_list')
                ->where('is_active', 1)
                ->select('name', 'pattern')
                ->get()
                ->toArray();
        }

        foreach ($this->botPatterns as $bot) {
            if (preg_match('/' . $bot->pattern . '/i', $userAgent)) {
                return [true, $bot->name];
            }
        }

        return [false, null];
    }

    // =========================================================================
    // GEOIP LOOKUP
    // =========================================================================

    /**
     * Get GeoIP data for an IP address.
     */
    protected function getGeoIpData(string $ipAddress): array
    {
        if (!$this->getConfig('geoip_enabled', true)) {
            return [];
        }

        $dbPath = $this->getConfig('geoip_database_path', '/usr/share/GeoIP/GeoLite2-City.mmdb');

        if (!file_exists($dbPath)) {
            return [];
        }

        try {
            // Use MaxMind GeoIP2 Reader if available
            if (class_exists('GeoIp2\Database\Reader')) {
                $reader = new \GeoIp2\Database\Reader($dbPath);
                $record = $reader->city($ipAddress);

                return [
                    'country_code' => $record->country->isoCode,
                    'country_name' => $record->country->name,
                    'city' => $record->city->name,
                    'region' => $record->mostSpecificSubdivision->name,
                    'latitude' => $record->location->latitude,
                    'longitude' => $record->location->longitude,
                ];
            }

            // Fallback to geoip_record_by_name if extension available
            if (function_exists('geoip_record_by_name')) {
                $record = @geoip_record_by_name($ipAddress);
                if ($record) {
                    return [
                        'country_code' => $record['country_code'] ?? null,
                        'country_name' => $record['country_name'] ?? null,
                        'city' => $record['city'] ?? null,
                        'region' => $record['region'] ?? null,
                        'latitude' => $record['latitude'] ?? null,
                        'longitude' => $record['longitude'] ?? null,
                    ];
                }
            }
        } catch (Exception $e) {
            error_log('GeoIP lookup failed: ' . $e->getMessage());
        }

        return [];
    }

    // =========================================================================
    // STATISTICS QUERIES
    // =========================================================================

    /**
     * Get dashboard summary statistics.
     */
    public function getDashboardStats(?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $endDate ?? date('Y-m-d');

        return [
            'total_views' => DB::table('ahg_usage_event')
                ->where('event_type', 'view')
                ->where('is_bot', 0)
                ->whereBetween('event_date', [$startDate, $endDate])
                ->count(),
            'total_downloads' => DB::table('ahg_usage_event')
                ->where('event_type', 'download')
                ->where('is_bot', 0)
                ->whereBetween('event_date', [$startDate, $endDate])
                ->count(),
            'unique_visitors' => DB::table('ahg_usage_event')
                ->where('is_bot', 0)
                ->whereBetween('event_date', [$startDate, $endDate])
                ->distinct('ip_hash')
                ->count('ip_hash'),
            'countries' => DB::table('ahg_usage_event')
                ->where('is_bot', 0)
                ->whereBetween('event_date', [$startDate, $endDate])
                ->whereNotNull('country_code')
                ->distinct('country_code')
                ->count('country_code'),
            'bot_requests' => DB::table('ahg_usage_event')
                ->where('is_bot', 1)
                ->whereBetween('event_date', [$startDate, $endDate])
                ->count(),
        ];
    }

    /**
     * Get views over time for charts.
     */
    public function getViewsOverTime(string $startDate, string $endDate, string $groupBy = 'day'): array
    {
        $dateFormat = $groupBy === 'month' ? '%Y-%m' : '%Y-%m-%d';

        return DB::table('ahg_usage_event')
            ->where('event_type', 'view')
            ->where('is_bot', 0)
            ->whereBetween('event_date', [$startDate, $endDate])
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"),
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(DISTINCT ip_hash) as unique_visitors')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->toArray();
    }

    /**
     * Get downloads over time.
     */
    public function getDownloadsOverTime(string $startDate, string $endDate): array
    {
        return DB::table('ahg_usage_event')
            ->where('event_type', 'download')
            ->where('is_bot', 0)
            ->whereBetween('event_date', [$startDate, $endDate])
            ->select(
                DB::raw("DATE(created_at) as period"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->toArray();
    }

    /**
     * Get top viewed items.
     */
    public function getTopItems(string $eventType = 'view', int $limit = 20, ?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $endDate ?? date('Y-m-d');

        return DB::table('ahg_usage_event as e')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('e.object_id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('slug as s', 'e.object_id', '=', 's.object_id')
            ->where('e.event_type', $eventType)
            ->where('e.object_type', 'information_object')
            ->where('e.is_bot', 0)
            ->whereBetween('e.event_date', [$startDate, $endDate])
            ->select(
                'e.object_id',
                'ioi.title',
                's.slug',
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(DISTINCT e.ip_hash) as unique_visitors')
            )
            ->groupBy('e.object_id', 'ioi.title', 's.slug')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get geographic distribution.
     */
    public function getGeographicStats(?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $endDate ?? date('Y-m-d');

        return DB::table('ahg_usage_event')
            ->where('is_bot', 0)
            ->whereBetween('event_date', [$startDate, $endDate])
            ->whereNotNull('country_code')
            ->select(
                'country_code',
                'country_name',
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(DISTINCT ip_hash) as unique_visitors')
            )
            ->groupBy('country_code', 'country_name')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    /**
     * Get statistics for a specific item.
     */
    public function getItemStats(int $objectId, ?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $endDate ?? date('Y-m-d');

        $views = DB::table('ahg_usage_event')
            ->where('object_id', $objectId)
            ->where('event_type', 'view')
            ->where('is_bot', 0)
            ->whereBetween('event_date', [$startDate, $endDate]);

        $downloads = DB::table('ahg_usage_event')
            ->where('object_id', $objectId)
            ->where('event_type', 'download')
            ->where('is_bot', 0)
            ->whereBetween('event_date', [$startDate, $endDate]);

        return [
            'total_views' => (clone $views)->count(),
            'unique_views' => (clone $views)->distinct('ip_hash')->count('ip_hash'),
            'total_downloads' => (clone $downloads)->count(),
            'views_by_day' => (clone $views)
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->toArray(),
            'top_countries' => (clone $views)
                ->whereNotNull('country_code')
                ->select('country_code', 'country_name', DB::raw('COUNT(*) as count'))
                ->groupBy('country_code', 'country_name')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->toArray(),
        ];
    }

    /**
     * Get repository statistics.
     */
    public function getRepositoryStats(int $repositoryId, ?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $endDate ?? date('Y-m-d');

        $baseQuery = DB::table('ahg_usage_event')
            ->where('repository_id', $repositoryId)
            ->where('is_bot', 0)
            ->whereBetween('event_date', [$startDate, $endDate]);

        return [
            'total_views' => (clone $baseQuery)->where('event_type', 'view')->count(),
            'total_downloads' => (clone $baseQuery)->where('event_type', 'download')->count(),
            'unique_visitors' => (clone $baseQuery)->distinct('ip_hash')->count('ip_hash'),
            'top_items' => $this->getTopItemsForRepository($repositoryId, 10, $startDate, $endDate),
        ];
    }

    protected function getTopItemsForRepository(int $repositoryId, int $limit, string $startDate, string $endDate): array
    {
        return DB::table('ahg_usage_event as e')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('e.object_id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('e.repository_id', $repositoryId)
            ->where('e.event_type', 'view')
            ->where('e.is_bot', 0)
            ->whereBetween('e.event_date', [$startDate, $endDate])
            ->select('e.object_id', 'ioi.title', DB::raw('COUNT(*) as total'))
            ->groupBy('e.object_id', 'ioi.title')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    // =========================================================================
    // AGGREGATION
    // =========================================================================

    /**
     * Aggregate daily statistics.
     */
    public function aggregateDaily(?string $date = null): int
    {
        $date = $date ?? date('Y-m-d', strtotime('-1 day'));

        // Delete existing aggregates for this date
        DB::table('ahg_statistics_daily')->where('stat_date', $date)->delete();

        // Aggregate by object
        $objectStats = DB::table('ahg_usage_event')
            ->where('event_date', $date)
            ->select(
                DB::raw("'{$date}' as stat_date"),
                'event_type',
                'object_type',
                'object_id',
                'repository_id',
                'country_code',
                DB::raw('COUNT(*) as total_count'),
                DB::raw('COUNT(DISTINCT ip_hash) as unique_visitors'),
                DB::raw('SUM(CASE WHEN user_id IS NOT NULL THEN 1 ELSE 0 END) as authenticated_count'),
                DB::raw('SUM(is_bot) as bot_count')
            )
            ->groupBy('event_type', 'object_type', 'object_id', 'repository_id', 'country_code')
            ->get();

        $inserted = 0;
        foreach ($objectStats as $stat) {
            DB::table('ahg_statistics_daily')->insert([
                'stat_date' => $stat->stat_date,
                'event_type' => $stat->event_type,
                'object_type' => $stat->object_type,
                'object_id' => $stat->object_id,
                'repository_id' => $stat->repository_id,
                'country_code' => $stat->country_code,
                'total_count' => $stat->total_count,
                'unique_visitors' => $stat->unique_visitors,
                'authenticated_count' => $stat->authenticated_count,
                'bot_count' => $stat->bot_count,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $inserted++;
        }

        return $inserted;
    }

    /**
     * Aggregate monthly statistics.
     */
    public function aggregateMonthly(?int $year = null, ?int $month = null): int
    {
        $year = $year ?? (int) date('Y', strtotime('-1 month'));
        $month = $month ?? (int) date('n', strtotime('-1 month'));

        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        // Delete existing aggregates
        DB::table('ahg_statistics_monthly')
            ->where('stat_year', $year)
            ->where('stat_month', $month)
            ->delete();

        // Aggregate from daily stats
        $monthlyStats = DB::table('ahg_statistics_daily')
            ->whereBetween('stat_date', [$startDate, $endDate])
            ->select(
                'event_type',
                'object_type',
                'object_id',
                'repository_id',
                'country_code',
                DB::raw('SUM(total_count) as total_count'),
                DB::raw('SUM(unique_visitors) as unique_visitors')
            )
            ->groupBy('event_type', 'object_type', 'object_id', 'repository_id', 'country_code')
            ->get();

        $inserted = 0;
        foreach ($monthlyStats as $stat) {
            // Find peak day
            $peak = DB::table('ahg_statistics_daily')
                ->whereBetween('stat_date', [$startDate, $endDate])
                ->where('event_type', $stat->event_type)
                ->where('object_type', $stat->object_type)
                ->where('object_id', $stat->object_id)
                ->orderByDesc('total_count')
                ->first();

            DB::table('ahg_statistics_monthly')->insert([
                'stat_year' => $year,
                'stat_month' => $month,
                'event_type' => $stat->event_type,
                'object_type' => $stat->object_type,
                'object_id' => $stat->object_id,
                'repository_id' => $stat->repository_id,
                'country_code' => $stat->country_code,
                'total_count' => $stat->total_count,
                'unique_visitors' => $stat->unique_visitors,
                'peak_day' => $peak->stat_date ?? null,
                'peak_count' => $peak->total_count ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $inserted++;
        }

        return $inserted;
    }

    /**
     * Cleanup old raw events.
     */
    public function cleanupOldEvents(?int $days = null): int
    {
        $days = $days ?? $this->getConfig('retention_days', 90);
        $cutoffDate = date('Y-m-d', strtotime("-{$days} days"));

        return DB::table('ahg_usage_event')
            ->where('event_date', '<', $cutoffDate)
            ->delete();
    }

    // =========================================================================
    // EXPORT
    // =========================================================================

    /**
     * Export statistics to CSV.
     */
    public function exportToCsv(string $type, string $startDate, string $endDate): string
    {
        $data = match ($type) {
            'views' => $this->getViewsOverTime($startDate, $endDate),
            'downloads' => $this->getDownloadsOverTime($startDate, $endDate),
            'top_items' => $this->getTopItems('view', 100, $startDate, $endDate),
            'geographic' => $this->getGeographicStats($startDate, $endDate),
            default => [],
        };

        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');

        // Header row
        fputcsv($output, array_keys((array) $data[0]));

        // Data rows
        foreach ($data as $row) {
            fputcsv($output, (array) $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    protected function getCurrentUserId(): ?int
    {
        try {
            $user = sfContext::getInstance()->getUser();
            if ($user && $user->isAuthenticated()) {
                return (int) $user->getAttribute('user_id');
            }
        } catch (Exception $e) {
            // Context not available
        }
        return null;
    }

    protected function getClientIp(): string
    {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    // =========================================================================
    // BOT MANAGEMENT
    // =========================================================================

    public function getBotList(): array
    {
        return DB::table('ahg_bot_list')
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    public function addBot(array $data): int
    {
        return DB::table('ahg_bot_list')->insertGetId([
            'name' => $data['name'],
            'pattern' => $data['pattern'],
            'category' => $data['category'] ?? 'crawler',
            'is_active' => $data['is_active'] ?? 1,
            'exclude_from_stats' => $data['exclude_from_stats'] ?? 1,
            'notes' => $data['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function updateBot(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return DB::table('ahg_bot_list')->where('id', $id)->update($data) > 0;
    }

    public function deleteBot(int $id): bool
    {
        return DB::table('ahg_bot_list')->where('id', $id)->delete() > 0;
    }
}
