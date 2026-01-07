<?php
use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        if (!DB::schema()->hasTable('creative_commons_license')) {
            DB::statement("
                CREATE TABLE `creative_commons_license` (
                    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                    `uri` varchar(255) NOT NULL,
                    `icon_url` varchar(255) DEFAULT NULL,
                    `code` varchar(30) NOT NULL,
                    `version` varchar(10) DEFAULT '4.0',
                    `allows_adaptation` tinyint(1) DEFAULT 1,
                    `allows_commercial` tinyint(1) DEFAULT 1,
                    `requires_attribution` tinyint(1) DEFAULT 1,
                    `requires_sharealike` tinyint(1) DEFAULT 0,
                    `icon_filename` varchar(100) DEFAULT NULL,
                    `is_active` tinyint(1) DEFAULT 1,
                    `sort_order` int DEFAULT 0,
                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uri` (`uri`),
                    UNIQUE KEY `code` (`code`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Seed data
            DB::statement("
                INSERT INTO `creative_commons_license` (`uri`, `icon_url`, `code`, `version`, `allows_adaptation`, `allows_commercial`, `requires_attribution`, `requires_sharealike`, `icon_filename`, `is_active`, `sort_order`) VALUES
                ('https://creativecommons.org/publicdomain/zero/1.0/', 'https://licensebuttons.net/l/zero/1.0/88x31.png', 'CC0-1.0', '1.0', 1, 1, 0, 0, 'cc-zero.png', 1, 1),
                ('https://creativecommons.org/licenses/by/4.0/', 'https://licensebuttons.net/l/by/4.0/88x31.png', 'CC-BY-4.0', '4.0', 1, 1, 1, 0, 'cc-by.png', 1, 2),
                ('https://creativecommons.org/licenses/by-sa/4.0/', 'https://licensebuttons.net/l/by-sa/4.0/88x31.png', 'CC-BY-SA-4.0', '4.0', 1, 1, 1, 1, 'cc-by-sa.png', 1, 3),
                ('https://creativecommons.org/licenses/by-nc/4.0/', 'https://licensebuttons.net/l/by-nc/4.0/88x31.png', 'CC-BY-NC-4.0', '4.0', 1, 0, 1, 0, 'cc-by-nc.png', 1, 4),
                ('https://creativecommons.org/licenses/by-nc-sa/4.0/', 'https://licensebuttons.net/l/by-nc-sa/4.0/88x31.png', 'CC-BY-NC-SA-4.0', '4.0', 1, 0, 1, 1, 'cc-by-nc-sa.png', 1, 5),
                ('https://creativecommons.org/licenses/by-nd/4.0/', 'https://licensebuttons.net/l/by-nd/4.0/88x31.png', 'CC-BY-ND-4.0', '4.0', 0, 1, 1, 0, 'cc-by-nd.png', 1, 6),
                ('https://creativecommons.org/licenses/by-nc-nd/4.0/', 'https://licensebuttons.net/l/by-nc-nd/4.0/88x31.png', 'CC-BY-NC-ND-4.0', '4.0', 0, 0, 1, 0, 'cc-by-nc-nd.png', 1, 7),
                ('https://creativecommons.org/publicdomain/mark/1.0/', NULL, 'PDM-1.0', '1.0', 1, 1, 0, 0, 'publicdomain.png', 1, 8)
            ");
        }
    }

    public function down(): void
    {
        DB::schema()->dropIfExists('creative_commons_license');
    }
};
