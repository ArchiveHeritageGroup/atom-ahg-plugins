<?php

/**
 * Diagnose theme configuration issues.
 *
 * This task helps identify why ahgThemeB5Plugin might not be rendering properly.
 */
class themeDiagnoseTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('fix', null, sfCommandOption::PARAMETER_NONE, 'Attempt to fix detected issues'),
        ]);

        $this->namespace = 'theme';
        $this->name = 'diagnose';
        $this->briefDescription = 'Diagnose ahgThemeB5Plugin configuration issues';
        $this->detailedDescription = <<<'EOF'
The theme:diagnose task checks for common issues that prevent ahgThemeB5Plugin
from rendering properly.

It checks:
  - Plugin symlink exists and is valid
  - Theme is in $corePlugins or atom_plugin table
  - No competing themes are interfering
  - Bundle assets exist
  - Decorator directories are correctly configured

Call with --fix to attempt automatic fixes:

  php symfony theme:diagnose --fix
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        $this->log('');
        $this->log('=== ahgThemeB5Plugin Diagnostic Report ===');
        $this->log('');

        $issues = [];
        $warnings = [];

        // Check 1: Symlink
        $this->log('[1] Checking plugin symlink...');
        $symlinkPath = sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin';
        $targetPath = sfConfig::get('sf_root_dir').'/atom-ahg-plugins/ahgThemeB5Plugin';

        if (!file_exists($symlinkPath)) {
            $issues[] = "Symlink missing: $symlinkPath";
            $this->log("    ERROR: Symlink does not exist at $symlinkPath");
        } elseif (!is_link($symlinkPath)) {
            $warnings[] = "Path exists but is not a symlink: $symlinkPath";
            $this->log("    WARNING: Path exists but is not a symlink: $symlinkPath");
        } else {
            $actualTarget = readlink($symlinkPath);
            $this->log("    OK: Symlink exists");
            $this->log("        Points to: $actualTarget");
            if (!file_exists($symlinkPath.'/config/ahgThemeB5PluginConfiguration.class.php')) {
                $issues[] = "Symlink target invalid - Configuration file not found";
                $this->log("    ERROR: Configuration file not found at symlink target");
            }
        }
        $this->log('');

        // Check 2: Plugin loading
        $this->log('[2] Checking plugin loading...');
        $plugins = $this->configuration->getPlugins();
        $themeIndex = array_search('ahgThemeB5Plugin', $plugins);

        if (false === $themeIndex) {
            $issues[] = "ahgThemeB5Plugin is not in the loaded plugins list";
            $this->log("    ERROR: Plugin not loaded");
        } else {
            $this->log("    OK: Plugin is loaded (index: $themeIndex)");

            // Check if it's first (should be moved to top by Configuration)
            if ($themeIndex !== 0) {
                $warnings[] = "Theme is not at index 0 - may not override other plugins";
                $this->log("    WARNING: Theme is not at index 0 (expected for overriding other plugins)");
            }
        }
        $this->log('');

        // Check 3: Competing themes (in loaded plugins)
        $this->log('[3] Checking for competing themes...');
        $competingThemes = [];
        foreach ($plugins as $plugin) {
            if ($plugin !== 'ahgThemeB5Plugin' && stripos($plugin, 'Theme') !== false) {
                $competingThemes[] = $plugin;
            }
        }

        // Also check for detected competing themes from Configuration
        $detectedCompeting = sfConfig::get('app_ahg_theme_competing');
        if (!empty($detectedCompeting)) {
            foreach ($detectedCompeting as $theme) {
                if (!in_array($theme, $competingThemes)) {
                    $competingThemes[] = $theme;
                }
            }
        }

        // Scan plugins directory for theme directories that aren't AHG symlinks
        $pluginsDir = sfConfig::get('sf_plugins_dir');
        $entries = is_dir($pluginsDir) ? scandir($pluginsDir) : [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..' || $entry === 'ahgThemeB5Plugin') {
                continue;
            }

            if (stripos($entry, 'Theme') !== false) {
                $path = $pluginsDir.'/'.$entry;
                if (is_dir($path)) {
                    $isAhgSymlink = false;
                    if (is_link($path)) {
                        $target = readlink($path);
                        if (strpos($target, 'atom-ahg-plugins') !== false) {
                            $isAhgSymlink = true;
                        }
                    }

                    if (!$isAhgSymlink && !in_array($entry, $competingThemes)) {
                        $competingThemes[] = $entry;
                    }
                }
            }
        }

        if (!empty($competingThemes)) {
            $this->log("    WARNING: Found ".count($competingThemes)." competing theme(s):");
            foreach ($competingThemes as $theme) {
                $themePath = $pluginsDir.'/'.$theme;
                $type = '';

                if (is_link($themePath)) {
                    $target = readlink($themePath);
                    $type = "symlink -> $target";
                } elseif (is_dir($themePath)) {
                    $type = 'real directory';
                } elseif (!file_exists($themePath)) {
                    $type = 'NOT FOUND in plugins dir';
                }

                $this->log("        - $theme ($type)");

                // Check if it has templates that could conflict
                if (is_dir($themePath)) {
                    $templatePath = $themePath.'/templates/layout.php';
                    if (file_exists($templatePath)) {
                        $issues[] = "Competing theme $theme has layout.php that may override ahgThemeB5Plugin";
                        $this->log("          ERROR: Has layout.php that will override ahgThemeB5Plugin!");
                    }
                }
            }

            $warnings[] = "Found competing themes: ".implode(', ', $competingThemes);
        } else {
            $this->log("    OK: No competing themes found");
        }
        $this->log('');

        // Check 4: Decorator directories
        $this->log('[4] Checking decorator directories...');
        $decoratorDirs = sfConfig::get('sf_decorator_dirs');
        $themeTemplatesPath = sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/templates';

        if (empty($decoratorDirs)) {
            $issues[] = "sf_decorator_dirs is empty - theme templates won't be used";
            $this->log("    ERROR: sf_decorator_dirs is empty");
        } else {
            $found = false;
            $this->log("    Decorator directories:");
            foreach ($decoratorDirs as $i => $dir) {
                $exists = is_dir($dir) ? 'exists' : 'MISSING';
                $this->log("        [$i] $dir ($exists)");
                if ($dir === $themeTemplatesPath) {
                    $found = true;
                }
            }

            if (!$found) {
                $issues[] = "ahgThemeB5Plugin templates not in sf_decorator_dirs";
                $this->log("    ERROR: ahgThemeB5Plugin templates NOT in decorator_dirs");
            } else {
                $this->log("    OK: ahgThemeB5Plugin templates found in decorator_dirs");
            }
        }
        $this->log('');

        // Check 5: Bundle assets
        $this->log('[5] Checking bundle assets...');
        $webDir = sfConfig::get('sf_web_dir');
        $rootDir = sfConfig::get('sf_root_dir');
        $this->log("    sf_web_dir: $webDir");
        $this->log("    sf_root_dir: $rootDir");

        // Check both possible dist locations
        $distDir = $webDir.'/dist';
        if (!is_dir($distDir)) {
            $distDir = $rootDir.'/dist';
        }
        $this->log("    Using dist dir: $distDir");

        $cssPattern = $distDir.'/css/ahgThemeB5Plugin.bundle.*.css';
        $jsPattern = $distDir.'/js/ahgThemeB5Plugin.bundle.*.js';
        $vendorPattern = $distDir.'/js/vendor.bundle.*.js';

        $cssFiles = glob($cssPattern);
        $jsFiles = glob($jsPattern);
        $vendorFiles = glob($vendorPattern);

        if (empty($vendorFiles)) {
            $warnings[] = "Vendor JS bundle not found: $vendorPattern";
            $this->log("    WARNING: Vendor JS bundle not found");
        } else {
            $this->log("    OK: Vendor JS bundle found: ".basename($vendorFiles[0]));
        }

        if (empty($cssFiles)) {
            $issues[] = "CSS bundle not found: $cssPattern";
            $this->log("    ERROR: CSS bundle not found");
        } else {
            $this->log("    OK: CSS bundle found: ".basename($cssFiles[0]));
        }

        if (empty($jsFiles)) {
            $issues[] = "JS bundle not found: $jsPattern";
            $this->log("    ERROR: JS bundle not found");
        } else {
            $this->log("    OK: JS bundle found: ".basename($jsFiles[0]));
        }
        $this->log('');

        // Check 6: app_b5_theme setting
        $this->log('[6] Checking app_b5_theme setting...');
        $b5Theme = sfConfig::get('app_b5_theme');
        if ($b5Theme) {
            $this->log("    OK: app_b5_theme is set to true");
        } else {
            $issues[] = "app_b5_theme is not set - Configuration may not have run";
            $this->log("    ERROR: app_b5_theme is NOT set (Configuration::initialize() may not have run)");
        }
        $this->log('');

        // Check 7: Database state
        $this->log('[7] Checking database state...');
        if (class_exists('Propel') && method_exists('Propel', 'getConnection')) {
            $conn = Propel::getConnection();
            $sql = "SELECT name, is_enabled, is_core, is_locked FROM atom_plugin WHERE name LIKE '%Theme%'";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $dbThemes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $dbThemes = \Illuminate\Database\Capsule\Manager::table('atom_plugin')
                ->where('name', 'LIKE', '%Theme%')
                ->select('name', 'is_enabled', 'is_core', 'is_locked')
                ->get()
                ->map(function ($row) {
                    return (array) $row;
                })
                ->toArray();
        }

        if (empty($dbThemes)) {
            $this->log("    INFO: No theme plugins in atom_plugin table");
        } else {
            $this->log("    Theme plugins in database:");
            foreach ($dbThemes as $row) {
                $enabled = $row['is_enabled'] ? 'enabled' : 'disabled';
                $core = $row['is_core'] ? ', core' : '';
                $locked = $row['is_locked'] ? ', locked' : '';
                $this->log("        - {$row['name']} ($enabled$core$locked)");
            }
        }
        $this->log('');

        // Check 8: _layout_start.php content
        $this->log('[8] Checking _layout_start.php...');
        $layoutStartPath = sfConfig::get('sf_plugins_dir').'/ahgThemeB5Plugin/templates/_layout_start.php';
        if (file_exists($layoutStartPath)) {
            $content = file_get_contents($layoutStartPath);
            if (strpos($content, 'ahgThemeB5Plugin.bundle') !== false) {
                $this->log("    OK: _layout_start.php references ahgThemeB5Plugin bundles");

                // Check if bundle hashes match actual files
                preg_match('/ahgThemeB5Plugin\.bundle\.([a-f0-9]+)\.css/', $content, $cssMatch);
                preg_match('/ahgThemeB5Plugin\.bundle\.([a-f0-9]+)\.js/', $content, $jsMatch);

                if (!empty($cssMatch[1]) && !empty($cssFiles)) {
                    $actualCssHash = preg_match('/\.bundle\.([a-f0-9]+)\.css/', basename($cssFiles[0]), $m) ? $m[1] : '';
                    if ($cssMatch[1] !== $actualCssHash) {
                        $issues[] = "CSS bundle hash mismatch: template has {$cssMatch[1]}, actual is $actualCssHash";
                        $this->log("    ERROR: CSS hash mismatch - template: {$cssMatch[1]}, actual: $actualCssHash");
                    }
                }
                if (!empty($jsMatch[1]) && !empty($jsFiles)) {
                    $actualJsHash = preg_match('/\.bundle\.([a-f0-9]+)\.js/', basename($jsFiles[0]), $m) ? $m[1] : '';
                    if ($jsMatch[1] !== $actualJsHash) {
                        $issues[] = "JS bundle hash mismatch: template has {$jsMatch[1]}, actual is $actualJsHash";
                        $this->log("    ERROR: JS hash mismatch - template: {$jsMatch[1]}, actual: $actualJsHash");
                    }
                }
            } else {
                $issues[] = "_layout_start.php does not reference ahgThemeB5Plugin bundles";
                $this->log("    ERROR: Does not reference ahgThemeB5Plugin bundles");
            }
        } else {
            $issues[] = "_layout_start.php not found at $layoutStartPath";
            $this->log("    ERROR: File not found");
        }
        $this->log('');

        // Summary
        $this->log('=== Summary ===');
        $this->log('');

        if (empty($issues) && empty($warnings)) {
            $this->log('All checks passed. Theme should be rendering correctly.');
            $this->log('');
            $this->log('If the theme still does not render, try:');
            $this->log('  1. Clear cache: rm -rf cache/* && php symfony cc');
            $this->log('  2. Restart PHP-FPM: sudo systemctl restart php8.3-fpm');
            $this->log('  3. Check browser developer tools for CSS/JS loading errors');
        } else {
            if (!empty($issues)) {
                $this->log('ERRORS ('.count($issues).'):');
                foreach ($issues as $i => $issue) {
                    $this->log('  '.($i + 1).'. '.$issue);
                }
                $this->log('');
            }

            if (!empty($warnings)) {
                $this->log('WARNINGS ('.count($warnings).'):');
                foreach ($warnings as $i => $warning) {
                    $this->log('  '.($i + 1).'. '.$warning);
                }
                $this->log('');
            }

            if ($options['fix']) {
                $this->log('Attempting fixes...');
                $this->attemptFixes($issues, $warnings);
            } else {
                $this->log('Run with --fix to attempt automatic fixes.');
            }
        }

        $this->log('');
    }

    protected function attemptFixes(array $issues, array $warnings)
    {
        $pluginsDir = sfConfig::get('sf_plugins_dir');
        $rootDir = sfConfig::get('sf_root_dir');

        foreach ($issues as $issue) {
            if (strpos($issue, 'Symlink missing') !== false) {
                $this->log('  Creating symlink...');
                $symlinkPath = $pluginsDir.'/ahgThemeB5Plugin';
                $targetPath = $rootDir.'/atom-ahg-plugins/ahgThemeB5Plugin';

                if (file_exists($targetPath)) {
                    if (@symlink($targetPath, $symlinkPath)) {
                        $this->log('    FIXED: Symlink created');
                    } else {
                        $this->log('    FAILED: Could not create symlink (permission issue?)');
                    }
                } else {
                    $this->log('    FAILED: Target path does not exist: '.$targetPath);
                }
            }

            if (strpos($issue, 'Competing theme') !== false && strpos($issue, 'layout.php') !== false) {
                // Extract theme name from error
                preg_match('/Competing theme (\w+)/', $issue, $m);
                if (!empty($m[1])) {
                    $competingTheme = $m[1];
                    $themePath = $pluginsDir.'/'.$competingTheme;

                    $this->log("  Competing theme detected: $competingTheme");

                    // Check if it's a real directory (not symlink)
                    if (is_dir($themePath) && !is_link($themePath)) {
                        // Rename to .disabled
                        $disabledPath = $themePath.'.disabled';
                        $this->log("    Attempting to disable by renaming to $competingTheme.disabled...");

                        if (@rename($themePath, $disabledPath)) {
                            $this->log('    FIXED: Renamed to '.$competingTheme.'.disabled');
                        } else {
                            $this->log('    FAILED: Could not rename (permission issue?)');
                            $this->log("    MANUAL FIX REQUIRED:");
                            $this->log("      sudo mv $themePath $disabledPath");
                        }
                    } elseif (is_link($themePath)) {
                        // Remove symlink
                        $this->log("    Attempting to remove symlink...");

                        if (@unlink($themePath)) {
                            $this->log('    FIXED: Symlink removed');
                        } else {
                            $this->log('    FAILED: Could not remove symlink (permission issue?)');
                            $this->log("    MANUAL FIX REQUIRED:");
                            $this->log("      sudo rm $themePath");
                        }
                    } else {
                        $this->log("    MANUAL FIX REQUIRED:");
                        $this->log("    1. Remove or disable $competingTheme");
                        $this->log("    2. Run: sudo rm -rf $themePath");
                    }
                }
            }
        }

        $this->log('');
        $this->log('After fixes, remember to:');
        $this->log('  1. Clear cache: rm -rf cache/* && php symfony cc');
        $this->log('  2. Restart PHP-FPM: sudo systemctl restart php8.3-fpm');
    }
}
