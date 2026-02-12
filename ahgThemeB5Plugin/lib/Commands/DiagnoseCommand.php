<?php

namespace AtomFramework\Console\Commands\Theme;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Diagnose ahgThemeB5Plugin configuration issues.
 */
class DiagnoseCommand extends BaseCommand
{
    protected string $name = 'theme:diagnose';
    protected string $description = 'Diagnose ahgThemeB5Plugin configuration issues';
    protected string $detailedDescription = <<<'EOF'
    Check for common issues that prevent ahgThemeB5Plugin from rendering properly.

    It checks:
      - Plugin symlink exists and is valid
      - Theme is in atom_plugin table
      - No competing themes are interfering
      - Bundle assets exist
      - Decorator directories are correctly configured

    Call with --fix to attempt automatic fixes:
      php bin/atom theme:diagnose --fix
    EOF;

    protected function configure(): void
    {
        $this->addOption('fix', 'f', 'Attempt to fix detected issues');
    }

    protected function handle(): int
    {
        $rootDir = $this->getAtomRoot();
        $pluginsDir = $rootDir . '/plugins';
        $webDir = $rootDir;

        $this->newline();
        $this->bold('  === ahgThemeB5Plugin Diagnostic Report ===');
        $this->newline();

        $issues = [];
        $warnings = [];

        // Check 1: Symlink
        $this->info('[1] Checking plugin symlink...');
        $symlinkPath = $pluginsDir . '/ahgThemeB5Plugin';
        $targetPath = $rootDir . '/atom-ahg-plugins/ahgThemeB5Plugin';

        if (!file_exists($symlinkPath)) {
            $issues[] = "Symlink missing: {$symlinkPath}";
            $this->error("    Symlink does not exist at {$symlinkPath}");
        } elseif (!is_link($symlinkPath)) {
            $warnings[] = "Path exists but is not a symlink: {$symlinkPath}";
            $this->warning("    Path exists but is not a symlink: {$symlinkPath}");
        } else {
            $actualTarget = readlink($symlinkPath);
            $this->line("    OK: Symlink exists");
            $this->line("        Points to: {$actualTarget}");
            if (!file_exists($symlinkPath . '/config/ahgThemeB5PluginConfiguration.class.php')) {
                $issues[] = 'Symlink target invalid - Configuration file not found';
                $this->error('    Configuration file not found at symlink target');
            }
        }
        $this->newline();

        // Check 2: Plugin loading
        $this->info('[2] Checking plugin loading...');
        $enabledPlugins = DB::table('atom_plugin')
            ->where('name', 'ahgThemeB5Plugin')
            ->where('is_enabled', 1)
            ->first();

        if (!$enabledPlugins) {
            $issues[] = 'ahgThemeB5Plugin is not enabled in atom_plugin table';
            $this->error('    Plugin not enabled in atom_plugin table');
        } else {
            $this->line('    OK: Plugin is enabled in atom_plugin table');
            $core = $enabledPlugins->is_core ? ', core' : '';
            $locked = $enabledPlugins->is_locked ? ', locked' : '';
            $this->line("    Status: enabled{$core}{$locked}");
        }
        $this->newline();

        // Check 3: Competing themes
        $this->info('[3] Checking for competing themes...');
        $competingThemes = [];

        // Check database for other theme plugins
        $dbThemes = DB::table('atom_plugin')
            ->where('name', 'LIKE', '%Theme%')
            ->where('name', '!=', 'ahgThemeB5Plugin')
            ->where('is_enabled', 1)
            ->pluck('name')
            ->toArray();

        foreach ($dbThemes as $theme) {
            if (!in_array($theme, $competingThemes)) {
                $competingThemes[] = $theme;
            }
        }

        // Scan plugins directory for theme directories that aren't AHG symlinks
        $entries = is_dir($pluginsDir) ? scandir($pluginsDir) : [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..' || $entry === 'ahgThemeB5Plugin') {
                continue;
            }

            if (stripos($entry, 'Theme') !== false) {
                $path = $pluginsDir . '/' . $entry;
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
            $this->warning('    Found ' . count($competingThemes) . ' competing theme(s):');
            foreach ($competingThemes as $theme) {
                $themePath = $pluginsDir . '/' . $theme;
                $type = '';

                if (is_link($themePath)) {
                    $target = readlink($themePath);
                    $type = "symlink -> {$target}";
                } elseif (is_dir($themePath)) {
                    $type = 'real directory';
                } elseif (!file_exists($themePath)) {
                    $type = 'NOT FOUND in plugins dir';
                }

                $this->line("        - {$theme} ({$type})");

                // Check if it has templates that could conflict
                if (is_dir($themePath)) {
                    $templatePath = $themePath . '/templates/layout.php';
                    if (file_exists($templatePath)) {
                        $issues[] = "Competing theme {$theme} has layout.php that may override ahgThemeB5Plugin";
                        $this->error("          Has layout.php that will override ahgThemeB5Plugin!");
                    }
                }
            }

            $warnings[] = 'Found competing themes: ' . implode(', ', $competingThemes);
        } else {
            $this->line('    OK: No competing themes found');
        }
        $this->newline();

        // Check 4: Decorator directories
        $this->info('[4] Checking decorator directories...');
        $themeTemplatesPath = $pluginsDir . '/ahgThemeB5Plugin/templates';

        // Check if the templates directory exists
        if (!is_dir($themeTemplatesPath)) {
            $issues[] = 'ahgThemeB5Plugin templates directory not found';
            $this->error("    Templates directory not found: {$themeTemplatesPath}");
        } else {
            $this->line("    OK: Templates directory exists: {$themeTemplatesPath}");
        }
        $this->newline();

        // Check 5: Bundle assets
        $this->info('[5] Checking bundle assets...');

        // Check both possible dist locations
        $distDir = $webDir . '/dist';
        if (!is_dir($distDir)) {
            $distDir = $rootDir . '/dist';
        }
        $this->line("    Using dist dir: {$distDir}");

        $cssPattern = $distDir . '/css/ahgThemeB5Plugin.bundle.*.css';
        $jsPattern = $distDir . '/js/ahgThemeB5Plugin.bundle.*.js';
        $vendorPattern = $distDir . '/js/vendor.bundle.*.js';

        $cssFiles = glob($cssPattern);
        $jsFiles = glob($jsPattern);
        $vendorFiles = glob($vendorPattern);

        if (empty($vendorFiles)) {
            $warnings[] = "Vendor JS bundle not found: {$vendorPattern}";
            $this->warning('    Vendor JS bundle not found');
        } else {
            $this->line('    OK: Vendor JS bundle found: ' . basename($vendorFiles[0]));
        }

        if (empty($cssFiles)) {
            $issues[] = "CSS bundle not found: {$cssPattern}";
            $this->error('    CSS bundle not found');
        } else {
            $this->line('    OK: CSS bundle found: ' . basename($cssFiles[0]));
        }

        if (empty($jsFiles)) {
            $issues[] = "JS bundle not found: {$jsPattern}";
            $this->error('    JS bundle not found');
        } else {
            $this->line('    OK: JS bundle found: ' . basename($jsFiles[0]));
        }
        $this->newline();

        // Check 6: Database state
        $this->info('[6] Checking database state...');
        $dbThemeRows = DB::table('atom_plugin')
            ->where('name', 'LIKE', '%Theme%')
            ->select('name', 'is_enabled', 'is_core', 'is_locked')
            ->get();

        if ($dbThemeRows->isEmpty()) {
            $this->line('    INFO: No theme plugins in atom_plugin table');
        } else {
            $this->line('    Theme plugins in database:');
            foreach ($dbThemeRows as $row) {
                $enabled = $row->is_enabled ? 'enabled' : 'disabled';
                $core = $row->is_core ? ', core' : '';
                $locked = $row->is_locked ? ', locked' : '';
                $this->line("        - {$row->name} ({$enabled}{$core}{$locked})");
            }
        }
        $this->newline();

        // Check 7: _layout_start.php content
        $this->info('[7] Checking _layout_start.php...');
        $layoutStartPath = $pluginsDir . '/ahgThemeB5Plugin/templates/_layout_start.php';
        if (file_exists($layoutStartPath)) {
            $content = file_get_contents($layoutStartPath);
            if (strpos($content, 'ahgThemeB5Plugin.bundle') !== false) {
                $this->line('    OK: _layout_start.php references ahgThemeB5Plugin bundles');

                // Check if bundle hashes match actual files
                preg_match('/ahgThemeB5Plugin\.bundle\.([a-f0-9]+)\.css/', $content, $cssMatch);
                preg_match('/ahgThemeB5Plugin\.bundle\.([a-f0-9]+)\.js/', $content, $jsMatch);

                if (!empty($cssMatch[1]) && !empty($cssFiles)) {
                    $actualCssHash = preg_match('/\.bundle\.([a-f0-9]+)\.css/', basename($cssFiles[0]), $m) ? $m[1] : '';
                    if ($cssMatch[1] !== $actualCssHash) {
                        $issues[] = "CSS bundle hash mismatch: template has {$cssMatch[1]}, actual is {$actualCssHash}";
                        $this->error("    CSS hash mismatch - template: {$cssMatch[1]}, actual: {$actualCssHash}");
                    }
                }
                if (!empty($jsMatch[1]) && !empty($jsFiles)) {
                    $actualJsHash = preg_match('/\.bundle\.([a-f0-9]+)\.js/', basename($jsFiles[0]), $m) ? $m[1] : '';
                    if ($jsMatch[1] !== $actualJsHash) {
                        $issues[] = "JS bundle hash mismatch: template has {$jsMatch[1]}, actual is {$actualJsHash}";
                        $this->error("    JS hash mismatch - template: {$jsMatch[1]}, actual: {$actualJsHash}");
                    }
                }
            } else {
                $issues[] = '_layout_start.php does not reference ahgThemeB5Plugin bundles';
                $this->error('    Does not reference ahgThemeB5Plugin bundles');
            }
        } else {
            $issues[] = "_layout_start.php not found at {$layoutStartPath}";
            $this->error('    File not found');
        }
        $this->newline();

        // Summary
        $this->bold('  === Summary ===');
        $this->newline();

        if (empty($issues) && empty($warnings)) {
            $this->success('All checks passed. Theme should be rendering correctly.');
            $this->newline();
            $this->line('  If the theme still does not render, try:');
            $this->line('    1. Clear cache: rm -rf cache/* && php symfony cc');
            $this->line('    2. Restart PHP-FPM: sudo systemctl restart php8.3-fpm');
            $this->line('    3. Check browser developer tools for CSS/JS loading errors');
        } else {
            if (!empty($issues)) {
                $this->error('ERRORS (' . count($issues) . '):');
                foreach ($issues as $i => $issue) {
                    $this->line('  ' . ($i + 1) . '. ' . $issue);
                }
                $this->newline();
            }

            if (!empty($warnings)) {
                $this->warning('WARNINGS (' . count($warnings) . '):');
                foreach ($warnings as $i => $warningMsg) {
                    $this->line('  ' . ($i + 1) . '. ' . $warningMsg);
                }
                $this->newline();
            }

            if ($this->hasOption('fix')) {
                $this->info('Attempting fixes...');
                $this->attemptFixes($issues, $pluginsDir, $rootDir);
            } else {
                $this->line('  Run with --fix to attempt automatic fixes.');
            }
        }

        $this->newline();

        return empty($issues) ? 0 : 1;
    }

    protected function attemptFixes(array $issues, string $pluginsDir, string $rootDir): void
    {
        foreach ($issues as $issue) {
            if (strpos($issue, 'Symlink missing') !== false) {
                $this->line('  Creating symlink...');
                $symlinkPath = $pluginsDir . '/ahgThemeB5Plugin';
                $targetPath = $rootDir . '/atom-ahg-plugins/ahgThemeB5Plugin';

                if (file_exists($targetPath)) {
                    if (@symlink($targetPath, $symlinkPath)) {
                        $this->success('  Symlink created');
                    } else {
                        $this->error('  Could not create symlink (permission issue?)');
                    }
                } else {
                    $this->error("  Target path does not exist: {$targetPath}");
                }
            }

            if (strpos($issue, 'Competing theme') !== false && strpos($issue, 'layout.php') !== false) {
                // Extract theme name from error
                preg_match('/Competing theme (\w+)/', $issue, $m);
                if (!empty($m[1])) {
                    $competingTheme = $m[1];
                    $themePath = $pluginsDir . '/' . $competingTheme;

                    $this->line("  Competing theme detected: {$competingTheme}");

                    // Check if it's a real directory (not symlink)
                    if (is_dir($themePath) && !is_link($themePath)) {
                        // Rename to .disabled
                        $disabledPath = $themePath . '.disabled';
                        $this->line("    Attempting to disable by renaming to {$competingTheme}.disabled...");

                        if (@rename($themePath, $disabledPath)) {
                            $this->success("  Renamed to {$competingTheme}.disabled");
                        } else {
                            $this->error('  Could not rename (permission issue?)');
                            $this->line("    MANUAL FIX REQUIRED:");
                            $this->line("      sudo mv {$themePath} {$disabledPath}");
                        }
                    } elseif (is_link($themePath)) {
                        // Remove symlink
                        $this->line('    Attempting to remove symlink...');

                        if (@unlink($themePath)) {
                            $this->success('  Symlink removed');
                        } else {
                            $this->error('  Could not remove symlink (permission issue?)');
                            $this->line("    MANUAL FIX REQUIRED:");
                            $this->line("      sudo rm {$themePath}");
                        }
                    } else {
                        $this->line('    MANUAL FIX REQUIRED:');
                        $this->line("    1. Remove or disable {$competingTheme}");
                        $this->line("    2. Run: sudo rm -rf {$themePath}");
                    }
                }
            }
        }

        $this->newline();
        $this->line('  After fixes, remember to:');
        $this->line('    1. Clear cache: rm -rf cache/* && php symfony cc');
        $this->line('    2. Restart PHP-FPM: sudo systemctl restart php8.3-fpm');
    }
}
