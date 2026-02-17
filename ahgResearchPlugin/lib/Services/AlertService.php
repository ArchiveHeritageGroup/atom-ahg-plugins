<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * AlertService - Real-Time Search Alert Service
 *
 * Handles search alert processing, notification queuing, and baseline management.
 * Implements real-time alerts (not cron-based) per user requirements.
 *
 * @package ahgResearchPlugin
 * @version 2.0.0
 */
class AlertService
{
    // =========================================================================
    // ALERT CHECKING
    // =========================================================================

    /**
     * Check for new results on a saved search.
     *
     * This should be called when search results are displayed or
     * when a relevant object is created/updated.
     *
     * @param int $savedSearchId The saved search ID
     * @return array Result with new_results count
     */
    public function checkForNewResults(int $savedSearchId): array
    {
        $search = DB::table('research_saved_search')
            ->where('id', $savedSearchId)
            ->first();

        if (!$search) {
            return ['error' => 'Saved search not found'];
        }

        // Get current result count by executing the search
        $currentCount = $this->executeSearchCount($search);

        $previousCount = $search->total_results_at_save ?? 0;
        $newResultsCount = max(0, $currentCount - $previousCount);

        // Update the saved search with new results count
        DB::table('research_saved_search')
            ->where('id', $savedSearchId)
            ->update([
                'new_results_count' => $newResultsCount,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return [
            'saved_search_id' => $savedSearchId,
            'previous_count' => $previousCount,
            'current_count' => $currentCount,
            'new_results_count' => $newResultsCount,
        ];
    }

    /**
     * Execute a search and return the result count.
     *
     * @param object $search The saved search object
     * @return int Result count
     */
    private function executeSearchCount(object $search): int
    {
        // Parse the search query and filters
        $query = $search->search_query ?? '';
        $filters = json_decode($search->search_filters ?? '{}', true);
        $searchType = $search->search_type ?? 'informationobject';

        // Build a basic count query for information objects
        // This is a simplified implementation - full Elasticsearch integration would be more accurate
        if ($searchType === 'informationobject') {
            $dbQuery = DB::table('information_object as io')
                ->join('information_object_i18n as ioi', function ($join) {
                    $join->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                })
                ->where('io.id', '!=', 1); // Exclude root

            if (!empty($query)) {
                $dbQuery->where(function ($q) use ($query) {
                    $q->where('ioi.title', 'like', '%' . $query . '%')
                        ->orWhere('ioi.scope_and_content', 'like', '%' . $query . '%');
                });
            }

            // Apply filters
            if (!empty($filters['repository'])) {
                $dbQuery->where('io.repository_id', $filters['repository']);
            }

            if (!empty($filters['level_of_description'])) {
                $dbQuery->where('io.level_of_description_id', $filters['level_of_description']);
            }

            if (!empty($filters['date_from'])) {
                $dbQuery->whereExists(function ($subquery) use ($filters) {
                    $subquery->select(DB::raw(1))
                        ->from('event')
                        ->whereColumn('event.object_id', 'io.id')
                        ->where('event.end_date', '>=', $filters['date_from']);
                });
            }

            if (!empty($filters['date_to'])) {
                $dbQuery->whereExists(function ($subquery) use ($filters) {
                    $subquery->select(DB::raw(1))
                        ->from('event')
                        ->whereColumn('event.object_id', 'io.id')
                        ->where('event.start_date', '<=', $filters['date_to']);
                });
            }

            return $dbQuery->count();
        }

        // For other search types, return 0 (would need specific implementation)
        return 0;
    }

    /**
     * Check all enabled alerts for a researcher.
     *
     * @param int $researcherId The researcher ID
     * @return array List of alerts with new results
     */
    public function checkResearcherAlerts(int $researcherId): array
    {
        $searches = DB::table('research_saved_search')
            ->where('researcher_id', $researcherId)
            ->where('alert_enabled', 1)
            ->get();

        $alerts = [];
        foreach ($searches as $search) {
            $result = $this->checkForNewResults($search->id);
            if (($result['new_results_count'] ?? 0) > 0) {
                $alerts[] = [
                    'saved_search_id' => $search->id,
                    'name' => $search->name,
                    'new_results_count' => $result['new_results_count'],
                ];
            }
        }

        return $alerts;
    }

    // =========================================================================
    // NOTIFICATION
    // =========================================================================

    /**
     * Send an alert notification.
     *
     * @param int $savedSearchId The saved search ID
     * @param int $newResultsCount Number of new results
     * @return bool Success status
     */
    public function sendAlert(int $savedSearchId, int $newResultsCount): bool
    {
        $search = DB::table('research_saved_search as s')
            ->join('research_researcher as r', 's.researcher_id', '=', 'r.id')
            ->where('s.id', $savedSearchId)
            ->select('s.*', 'r.email', 'r.first_name', 'r.last_name')
            ->first();

        if (!$search || !$search->alert_enabled) {
            return false;
        }

        // Check frequency constraints
        if (!$this->shouldSendAlert($search)) {
            return false;
        }

        // Log the alert
        $this->logAlert($savedSearchId, $search->researcher_id, $search->total_results_at_save ?? 0, ($search->total_results_at_save ?? 0) + $newResultsCount, $newResultsCount);

        // Send notification (email or in-app)
        $notificationSent = $this->sendEmailNotification($search, $newResultsCount);

        // Update last alert timestamp
        DB::table('research_saved_search')
            ->where('id', $savedSearchId)
            ->update([
                'last_alert_at' => date('Y-m-d H:i:s'),
            ]);

        return $notificationSent;
    }

    /**
     * Check if an alert should be sent based on frequency.
     *
     * @param object $search The saved search
     * @return bool True if alert should be sent
     */
    private function shouldSendAlert(object $search): bool
    {
        if ($search->alert_frequency === 'realtime') {
            return true;
        }

        if (!$search->last_alert_at) {
            return true;
        }

        $lastAlert = strtotime($search->last_alert_at);
        $now = time();

        return match ($search->alert_frequency) {
            'daily' => ($now - $lastAlert) >= 86400,
            'weekly' => ($now - $lastAlert) >= 604800,
            'monthly' => ($now - $lastAlert) >= 2592000,
            default => true,
        };
    }

    /**
     * Send email notification for search alert.
     *
     * @param object $search The saved search with researcher info
     * @param int $newResultsCount Number of new results
     * @return bool Success status
     */
    private function sendEmailNotification(object $search, int $newResultsCount): bool
    {
        // Get site URL for links
        $siteUrl = sfConfig::get('app_siteBaseUrl', '');

        $subject = "New results for your saved search: {$search->name}";

        $body = "Dear {$search->first_name},\n\n";
        $body .= "Your saved search \"{$search->name}\" has {$newResultsCount} new result(s).\n\n";
        $body .= "Search query: {$search->search_query}\n\n";
        $body .= "View your saved searches at: {$siteUrl}/research/saved-searches\n\n";
        $body .= "You can manage your alert settings from your researcher workspace.\n\n";
        $body .= "Best regards,\nThe Archive Team";

        // Use the plugin's email service if available
        $emailServicePath = dirname(__FILE__) . '/EmailService.php';
        if (file_exists($emailServicePath)) {
            require_once $emailServicePath;
            if (class_exists('EmailService')) {
                $emailService = new EmailService();
                return $emailService->send($search->email, $subject, $body);
            }
        }

        // Fallback to basic mail
        $headers = "From: noreply@" . ($_SERVER['HTTP_HOST'] ?? 'archive.local') . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        return mail($search->email, $subject, $body, $headers);
    }

    // =========================================================================
    // BASELINE MANAGEMENT
    // =========================================================================

    /**
     * Update the baseline (total_results_at_save) for a saved search.
     *
     * @param int $savedSearchId The saved search ID
     * @return int The new baseline count
     */
    public function updateBaseline(int $savedSearchId): int
    {
        $search = DB::table('research_saved_search')
            ->where('id', $savedSearchId)
            ->first();

        if (!$search) {
            return 0;
        }

        $currentCount = $this->executeSearchCount($search);

        DB::table('research_saved_search')
            ->where('id', $savedSearchId)
            ->update([
                'total_results_at_save' => $currentCount,
                'new_results_count' => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return $currentCount;
    }

    /**
     * Mark alerts as seen (reset new_results_count).
     *
     * @param int $savedSearchId The saved search ID
     * @return bool Success status
     */
    public function markAlertsSeen(int $savedSearchId): bool
    {
        // First update the baseline
        $this->updateBaseline($savedSearchId);

        return DB::table('research_saved_search')
            ->where('id', $savedSearchId)
            ->update([
                'new_results_count' => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]) >= 0;
    }

    // =========================================================================
    // LOGGING
    // =========================================================================

    /**
     * Log an alert event.
     *
     * @param int $savedSearchId The saved search ID
     * @param int $researcherId The researcher ID
     * @param int $previousCount Previous result count
     * @param int $newCount New result count
     * @param int $newItemsCount Number of new items
     * @param bool $notificationSent Whether notification was sent
     * @param string|null $notificationMethod Method used (email, in-app, etc.)
     */
    public function logAlert(
        int $savedSearchId,
        int $researcherId,
        int $previousCount,
        int $newCount,
        int $newItemsCount,
        bool $notificationSent = true,
        ?string $notificationMethod = 'email'
    ): void {
        DB::table('research_search_alert_log')->insert([
            'saved_search_id' => $savedSearchId,
            'researcher_id' => $researcherId,
            'previous_count' => $previousCount,
            'new_count' => $newCount,
            'new_items_count' => $newItemsCount,
            'notification_sent' => $notificationSent ? 1 : 0,
            'notification_method' => $notificationMethod,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get alert history for a researcher.
     *
     * @param int $researcherId The researcher ID
     * @param int $limit Maximum records to return
     * @return array Alert history
     */
    public function getAlertHistory(int $researcherId, int $limit = 50): array
    {
        return DB::table('research_search_alert_log as l')
            ->join('research_saved_search as s', 'l.saved_search_id', '=', 's.id')
            ->where('l.researcher_id', $researcherId)
            ->select('l.*', 's.name as search_name', 's.search_query')
            ->orderBy('l.created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get alert history for a specific saved search.
     *
     * @param int $savedSearchId The saved search ID
     * @param int $limit Maximum records to return
     * @return array Alert history
     */
    public function getSearchAlertHistory(int $savedSearchId, int $limit = 20): array
    {
        return DB::table('research_search_alert_log')
            ->where('saved_search_id', $savedSearchId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    // =========================================================================
    // BATCH PROCESSING
    // =========================================================================

    /**
     * Process all pending alerts (for scheduled/batch processing if needed).
     *
     * Even though the system is designed for real-time alerts,
     * this can be used as a fallback or for catch-up processing.
     *
     * @return array Processing results
     */
    public function processAllPendingAlerts(): array
    {
        $results = [
            'processed' => 0,
            'alerts_sent' => 0,
            'errors' => [],
        ];

        $searches = DB::table('research_saved_search')
            ->where('alert_enabled', 1)
            ->get();

        foreach ($searches as $search) {
            try {
                $checkResult = $this->checkForNewResults($search->id);

                if (($checkResult['new_results_count'] ?? 0) > 0) {
                    $sent = $this->sendAlert($search->id, $checkResult['new_results_count']);
                    if ($sent) {
                        $results['alerts_sent']++;
                    }
                }

                $results['processed']++;
            } catch (Exception $e) {
                $results['errors'][] = [
                    'search_id' => $search->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    // =========================================================================
    // REAL-TIME INTEGRATION HOOKS
    // =========================================================================

    /**
     * Hook to be called when a new information object is created.
     *
     * Checks all relevant saved searches and triggers alerts.
     *
     * @param int $objectId The new object ID
     */
    public function onObjectCreated(int $objectId): void
    {
        // Get the object's searchable content
        $object = DB::table('information_object as io')
            ->join('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('io.id', $objectId)
            ->first();

        if (!$object) {
            return;
        }

        // Find saved searches that might match this object
        // This is a simplified check - full implementation would use proper search matching
        $searches = DB::table('research_saved_search')
            ->where('alert_enabled', 1)
            ->where('alert_frequency', 'realtime')
            ->get();

        foreach ($searches as $search) {
            $query = $search->search_query ?? '';

            // Simple text matching
            $matches = empty($query) ||
                stripos($object->title ?? '', $query) !== false ||
                stripos($object->scope_and_content ?? '', $query) !== false;

            if ($matches) {
                // Check filters
                $filters = json_decode($search->search_filters ?? '{}', true);
                $filterMatch = true;

                if (!empty($filters['repository']) && $object->repository_id != $filters['repository']) {
                    $filterMatch = false;
                }

                if ($filterMatch) {
                    // Trigger alert check
                    $this->checkForNewResults($search->id);

                    // For real-time, send immediately if there are new results
                    $savedSearch = DB::table('research_saved_search')
                        ->where('id', $search->id)
                        ->first();

                    if ($savedSearch && ($savedSearch->new_results_count ?? 0) > 0) {
                        $this->sendAlert($search->id, $savedSearch->new_results_count);
                    }
                }
            }
        }
    }

    /**
     * Get researcher's searches with pending alerts.
     *
     * @param int $researcherId The researcher ID
     * @return array Searches with new results
     */
    public function getPendingAlerts(int $researcherId): array
    {
        return DB::table('research_saved_search')
            ->where('researcher_id', $researcherId)
            ->where('new_results_count', '>', 0)
            ->orderBy('updated_at', 'desc')
            ->get()
            ->toArray();
    }
}
