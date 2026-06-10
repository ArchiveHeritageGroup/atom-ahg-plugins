<?php

/**
 * CatalogueService (#148) — introspect the live system into a browsable catalogue.
 *
 * Routes come from the live routing table; CLI tasks and services are scanned
 * from the plugin + framework source trees.
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * Licensed under the GNU Affero General Public License v3.0 or later.
 */
class CatalogueService
{
    private string $root;
    private string $pluginsDir;
    private string $frameworkDir;

    public function __construct()
    {
        $this->root = \sfConfig::get('sf_root_dir');
        $this->pluginsDir = $this->root.'/plugins';
        $this->frameworkDir = $this->root.'/atom-framework';
    }

    /** Live route table: name → pattern + module/action. */
    public function routes(): array
    {
        $out = [];
        try {
            $routing = \sfContext::getInstance()->getRouting();
            foreach ($routing->getRoutes() as $name => $route) {
                if (!is_object($route) || !method_exists($route, 'getPattern')) {
                    continue;
                }
                $defaults = method_exists($route, 'getDefaults') ? $route->getDefaults() : [];
                $out[] = [
                    'name' => (string) $name,
                    'pattern' => $route->getPattern(),
                    'module' => $defaults['module'] ?? '',
                    'action' => $defaults['action'] ?? '',
                ];
            }
        } catch (\Throwable $e) {
            // routing unavailable — return what we have
        }
        usort($out, fn ($a, $b) => strcmp($a['pattern'], $b['pattern']));
        return $out;
    }

    /** CLI tasks scanned from plugin lib/task + framework commands. */
    public function tasks(): array
    {
        $out = [];
        foreach (glob($this->pluginsDir.'/*/lib/task/*Task.class.php') ?: [] as $file) {
            $src = (string) file_get_contents($file);
            $ns = $this->grab($src, '/\$this->namespace\s*=\s*[\'"]([^\'"]+)[\'"]/');
            $name = $this->grab($src, '/\$this->name\s*=\s*[\'"]([^\'"]+)[\'"]/');
            $desc = $this->grab($src, '/briefDescription\s*=\s*[\'"]([^\'"]+)[\'"]/');
            if ($name === '') {
                continue;
            }
            $plugin = $this->pluginOf($file);
            $out[] = [
                'command' => 'php symfony '.($ns !== '' ? $ns.':' : '').$name,
                'description' => $desc,
                'source' => $plugin,
                'file' => $this->rel($file),
            ];
        }
        // Framework console commands (php bin/atom ...).
        foreach (glob($this->frameworkDir.'/src/Console/Commands/*.php') ?: [] as $file) {
            $src = (string) file_get_contents($file);
            $sig = $this->grab($src, '/protected\s+\$signature\s*=\s*[\'"]([^\'"]+)[\'"]/');
            $desc = $this->grab($src, '/protected\s+\$description\s*=\s*[\'"]([^\'"]+)[\'"]/');
            if ($sig === '') {
                continue;
            }
            $out[] = [
                'command' => 'php bin/atom '.$sig,
                'description' => $desc,
                'source' => 'atom-framework',
                'file' => $this->rel($file),
            ];
        }
        usort($out, fn ($a, $b) => strcmp($a['command'], $b['command']));
        return $out;
    }

    /** Service classes from framework + plugins. */
    public function services(): array
    {
        $out = [];
        $globs = [
            $this->frameworkDir.'/src/Services/*Service.php',
            $this->pluginsDir.'/*/lib/Services/*Service.php',
        ];
        foreach ($globs as $g) {
            foreach (glob($g) ?: [] as $file) {
                $class = basename($file, '.php');
                $src = (string) file_get_contents($file);
                $doc = $this->firstDocLine($src);
                $out[] = [
                    'class' => $class,
                    'description' => $doc,
                    'source' => str_contains($file, '/atom-framework/') ? 'atom-framework' : $this->pluginOf($file),
                    'file' => $this->rel($file),
                ];
            }
        }
        usort($out, fn ($a, $b) => strcmp($a['class'], $b['class']));
        return $out;
    }

    public function counts(array $r, array $t, array $s): array
    {
        return ['routes' => count($r), 'tasks' => count($t), 'services' => count($s)];
    }

    // ── helpers ──────────────────────────────────────────────────────────────
    private function grab(string $src, string $re): string
    {
        return preg_match($re, $src, $m) ? trim($m[1]) : '';
    }

    private function firstDocLine(string $src): string
    {
        if (preg_match('/\/\*\*\s*\n\s*\*\s*([^\n@]+)/', $src, $m)) {
            return trim(rtrim($m[1], '.'));
        }
        return '';
    }

    private function pluginOf(string $file): string
    {
        if (preg_match('#/plugins/([^/]+)/#', $file, $m)) {
            return $m[1];
        }
        return '';
    }

    private function rel(string $file): string
    {
        return str_replace($this->root.'/', '', $file);
    }
}
