<?php

/**
 * Spectrum Plugin Installation Service
 *
 * Handles database schema creation and plugin initialization.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgSpectrumPlugin
 */

use Illuminate\Database\Capsule\Manager as DB;

class arSpectrumInstallService
{
    // Setting scope
    const SETTING_SCOPE = 'ahgSpectrumPlugin';

    /**
     * Install database schema
     */
    public static function install(): bool
    {
        // Create spectrum_event table
        $sql = "CREATE TABLE IF NOT EXISTS spectrum_event (
            id INT AUTO_INCREMENT PRIMARY KEY,
            object_id INT NOT NULL,
            procedure_id VARCHAR(50) NOT NULL,
            event_type VARCHAR(50) NOT NULL,
            status_from VARCHAR(50),
            status_to VARCHAR(50),
            user_id INT,
            assigned_to_id INT,
            due_date DATE,
            completed_date DATE,
            location VARCHAR(255),
            notes TEXT,
            metadata JSON,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_object_procedure (object_id, procedure_id),
            INDEX idx_object (object_id),
            INDEX idx_procedure (procedure_id),
            INDEX idx_created (created_at),
            INDEX idx_user (user_id),
            INDEX idx_status (status_to),
            INDEX idx_due_date (due_date),
            FOREIGN KEY (object_id) REFERENCES information_object(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            DB::statement($sql);
            echo "Created spectrum_event table\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
            echo "spectrum_event table already exists\n";
        }

        // Create spectrum_approval table
        $sql = "CREATE TABLE IF NOT EXISTS spectrum_approval (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            approver_id INT NOT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            comments TEXT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_event (event_id),
            INDEX idx_approver (approver_id),
            FOREIGN KEY (event_id) REFERENCES spectrum_event(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            DB::statement($sql);
            echo "Created spectrum_approval table\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
            echo "spectrum_approval table already exists\n";
        }

        // Create spectrum_notification table
        $sql = "CREATE TABLE IF NOT EXISTS spectrum_notification (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT,
            user_id INT NOT NULL,
            notification_type VARCHAR(50) NOT NULL,
            subject VARCHAR(255),
            message TEXT,
            read_at DATETIME,
            created_at DATETIME NOT NULL,
            INDEX idx_user (user_id),
            INDEX idx_unread (user_id, read_at),
            FOREIGN KEY (event_id) REFERENCES spectrum_event(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            DB::statement($sql);
            echo "Created spectrum_notification table\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
            echo "spectrum_notification table already exists\n";
        }

        echo "ahgSpectrumPlugin installation complete\n";
        return true;
    }

    /**
     * Uninstall database schema
     */
    public static function uninstall(bool $dropTables = false): bool
    {
        if (!$dropTables) {
            echo "Uninstall called but dropTables=false, skipping\n";
            return true;
        }

        $tables = ['spectrum_notification', 'spectrum_approval', 'spectrum_event'];

        foreach ($tables as $table) {
            try {
                DB::statement("DROP TABLE IF EXISTS {$table}");
                echo "Dropped {$table}\n";
            } catch (Exception $e) {
                echo "Error dropping {$table}: " . $e->getMessage() . "\n";
            }
        }

        // Remove settings
        $settingIds = DB::table('setting')
            ->where('scope', self::SETTING_SCOPE)
            ->pluck('id')
            ->toArray();

        if (!empty($settingIds)) {
            // Delete i18n entries first
            DB::table('setting_i18n')
                ->whereIn('id', $settingIds)
                ->delete();

            // Delete settings
            DB::table('setting')
                ->whereIn('id', $settingIds)
                ->delete();

            echo "Removed " . count($settingIds) . " settings\n";
        }

        echo "ahgSpectrumPlugin uninstallation complete\n";
        return true;
    }

    /**
     * Check if plugin is installed
     */
    public static function isInstalled(): bool
    {
        try {
            $tables = DB::select("SHOW TABLES LIKE 'spectrum_event'");
            return count($tables) > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get installation status
     */
    public static function getStatus(): array
    {
        $status = [];

        $tables = ['spectrum_event', 'spectrum_approval', 'spectrum_notification'];

        foreach ($tables as $table) {
            try {
                $exists = count(DB::select("SHOW TABLES LIKE '{$table}'")) > 0;

                $count = 0;
                if ($exists) {
                    $count = DB::table($table)->count();
                }

                $status[$table] = [
                    'exists' => $exists,
                    'count' => $count,
                ];
            } catch (Exception $e) {
                $status[$table] = [
                    'exists' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $status;
    }

    /**
     * Migrate data from ahgMuseumPlugin JSON storage
     */
    public static function migrateFromJSON(): array
    {
        $eventService = new arSpectrumEventService();

        // Find all objects with spectrumProcedures property
        $properties = DB::table('property')
            ->where('name', 'spectrumProcedures')
            ->select('object_id', 'value')
            ->get();

        $migrated = 0;
        $errors = 0;

        foreach ($properties as $row) {
            $objectId = $row->object_id;
            $data = json_decode($row->value, true);

            if (!$data) {
                continue;
            }

            foreach ($data as $procedureId => $procData) {
                try {
                    // Create initial event
                    if (!empty($procData['status'])) {
                        $eventService->createEvent(
                            $objectId,
                            $procedureId,
                            arSpectrumEventService::EVENT_STATUS_CHANGE,
                            [
                                'status_to' => $procData['status'],
                                'due_date' => $procData['dueDate'] ?? null,
                                'notes' => 'Migrated from JSON storage',
                                'metadata' => [
                                    'migrated' => true,
                                    'original_data' => $procData,
                                ],
                            ]
                        );
                        $migrated++;
                    }

                    // Migrate events if present
                    if (!empty($procData['events'])) {
                        foreach ($procData['events'] as $event) {
                            $eventService->createEvent(
                                $objectId,
                                $procedureId,
                                $event['type'] ?? arSpectrumEventService::EVENT_NOTE_ADDED,
                                [
                                    'status_from' => $event['statusFrom'] ?? null,
                                    'status_to' => $event['statusTo'] ?? null,
                                    'notes' => $event['notes'] ?? null,
                                    'metadata' => ['migrated_event' => true],
                                ]
                            );
                        }
                    }
                } catch (Exception $e) {
                    $errors++;
                    echo "Error migrating object {$objectId}, procedure {$procedureId}: " . $e->getMessage() . "\n";
                }
            }
        }

        echo "Migration complete: {$migrated} procedures migrated, {$errors} errors\n";
        return ['migrated' => $migrated, 'errors' => $errors];
    }

    /**
     * Run database migrations/upgrades
     */
    public static function upgrade(): bool
    {
        // Add any schema upgrades here
        $upgrades = [
            // Example: Add column if not exists
            // "ALTER TABLE spectrum_event ADD COLUMN priority VARCHAR(20) DEFAULT 'normal' AFTER location",
        ];

        foreach ($upgrades as $sql) {
            try {
                DB::statement($sql);
                echo "Executed: " . substr($sql, 0, 50) . "...\n";
            } catch (Exception $e) {
                // Ignore duplicate column errors
                if (strpos($e->getMessage(), 'Duplicate column') === false) {
                    echo "Warning: " . $e->getMessage() . "\n";
                }
            }
        }

        echo "Upgrade complete\n";
        return true;
    }

    /**
     * Get table statistics
     */
    public static function getStatistics(): array
    {
        $stats = [];

        // Event counts by procedure
        $stats['events_by_procedure'] = DB::table('spectrum_event')
            ->select('procedure_id', DB::raw('COUNT(*) as count'))
            ->groupBy('procedure_id')
            ->orderByDesc('count')
            ->get()
            ->toArray();

        // Event counts by status
        $stats['events_by_status'] = DB::table('spectrum_event')
            ->select('status_to', DB::raw('COUNT(*) as count'))
            ->whereNotNull('status_to')
            ->groupBy('status_to')
            ->orderByDesc('count')
            ->get()
            ->toArray();

        // Pending approvals
        $stats['pending_approvals'] = DB::table('spectrum_approval')
            ->where('status', 'pending')
            ->count();

        // Unread notifications
        $stats['unread_notifications'] = DB::table('spectrum_notification')
            ->whereNull('read_at')
            ->count();

        // Events this month
        $stats['events_this_month'] = DB::table('spectrum_event')
            ->where('created_at', '>=', date('Y-m-01 00:00:00'))
            ->count();

        // Overdue items
        $stats['overdue_items'] = DB::table('spectrum_event')
            ->whereNotNull('due_date')
            ->where('due_date', '<', date('Y-m-d'))
            ->whereNull('completed_date')
            ->count();

        return $stats;
    }

    /**
     * Clean up old data
     */
    public static function cleanup(int $daysToKeep = 365): array
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));
        $results = [];

        // Delete old read notifications
        $results['notifications_deleted'] = DB::table('spectrum_notification')
            ->whereNotNull('read_at')
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        echo "Cleanup complete: {$results['notifications_deleted']} old notifications removed\n";
        return $results;
    }
}