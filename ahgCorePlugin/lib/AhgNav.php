<?php

/**
 * Navigation entries contributed by plugins.
 *
 * WHY THIS EXISTS
 *
 * The theme used to name plugin routes directly - url_for('@spectrum_dashboard'),
 * url_for('@glam_browse'), and fifteen more across nine plugins. url_for() throws
 * on an unknown route, so enabling ahgThemeB5Plugin without, say, ahgSpectrumPlugin
 * did not merely hide a link: it took down every page carrying it with a 500.
 * Found on a clean AtoM 2.10 with only core and the theme enabled.
 *
 * That is the dependency pointing the wrong way. A theme is the most general
 * thing in the system and must not know which features happen to be installed.
 * So the direction is inverted here: the theme renders whatever is registered,
 * and each plugin contributes its own entries when it initialises. Remove a
 * plugin and its entries simply are not there.
 *
 * HOW A PLUGIN CONTRIBUTES
 *
 * In the plugin's Configuration::initialize():
 *
 *   AhgNav::register('admin', 'spectrum_dashboard', [
 *       'route'       => '@spectrum_dashboard',
 *       'label'       => 'Collections procedures',
 *       'credentials' => ['administrator', 'editor'],
 *       'weight'      => 40,
 *   ]);
 *
 * Groups in use: 'admin', 'user', 'manage', 'browse', 'help'.
 *
 * WHY THE ROUTE IS STILL CHECKED
 *
 * A plugin can register an entry and then fail to register its route - a typo,
 * or a routing listener that did not run. items() drops any entry whose route
 * cannot be resolved, so a mistake costs a missing link rather than a dead site.
 * Belt and braces: the registry alone should make it impossible, and this
 * guarantees it.
 */
class AhgNav
{
    /** @var array<string, array<string, array>> group => id => config */
    private static array $items = [];

    /** @var array<string, bool> route name => resolvable */
    private static array $routeCache = [];

    /**
     * Contribute a navigation entry.
     *
     * Registering the same group and id twice replaces the first, so a plugin
     * can safely re-register on a second initialise.
     */
    public static function register(string $group, string $id, array $config): void
    {
        if (empty($config['route']) && empty($config['url'])) {
            return;
        }

        $config += [
            'label' => $id,
            'weight' => 100,
            'credentials' => [],
            'icon' => null,
            'section' => null,
            // Optional callables, owned by the contributing plugin:
            //   badge   fn(): ?int      a count to show beside the label
            //   visible fn($user): bool a rule beyond simple credentials
            // These exist so the theme never queries a plugin's tables. It used
            // to: the user menu ran SELECTs against access_request,
            // research_researcher and research_booking, and called
            // ahgSpectrumWorkflowService directly, which meant the theme knew
            // three plugins' schemas and broke when they were absent.
            'badge' => null,
            'visible' => null,
        ];

        self::$items[$group][$id] = $config;
    }

    /**
     * The entries for a group, ordered by weight, filtered to what this user may
     * see and to routes that actually resolve.
     *
     * @param mixed $user sfUser, or null to skip the credential check
     */
    public static function items(string $group, $user = null): array
    {
        if (empty(self::$items[$group])) {
            return [];
        }

        $items = array_filter(self::$items[$group], static function ($config) use ($user) {
            if (!self::routeResolves($config) || !self::permitted($config, $user)) {
                return false;
            }

            if (is_callable($config['visible'] ?? null)) {
                try {
                    return (bool) ($config['visible'])($user);
                } catch (Exception $e) {
                    // A plugin's own visibility rule must not take down the menu.
                    return false;
                }
            }

            return true;
        });

        uasort($items, static fn ($a, $b) => ($a['weight'] ?? 100) <=> ($b['weight'] ?? 100));

        return $items;
    }

    /**
     * Resolve one entry's URL, or null when it is unavailable.
     *
     * This is what a template uses where a single link is wanted rather than a
     * whole group, and it is the safe replacement for a bare url_for('@route').
     */
    public static function url(string $group, string $id): ?string
    {
        $config = self::$items[$group][$id] ?? null;

        if (null === $config || !self::routeResolves($config)) {
            return null;
        }

        return self::resolve($config);
    }

    public static function has(string $group, ?string $id = null): bool
    {
        if (null === $id) {
            return !empty(self::$items[$group]);
        }

        return isset(self::$items[$group][$id]) && self::routeResolves(self::$items[$group][$id]);
    }

    /**
     * Resolve a route name to a URL without throwing.
     *
     * The whole point of the class: url_for() raises on an unknown route, and a
     * raise in a layout partial is a site-wide 500.
     */
    public static function safeUrl(string $route, ?string $fallback = null): ?string
    {
        if (!self::knownRoute($route)) {
            return $fallback;
        }

        try {
            return url_for($route);
        } catch (Exception $e) {
            return $fallback;
        }
    }

    public static function all(): array
    {
        return self::$items;
    }

    public static function clear(): void
    {
        self::$items = [];
        self::$routeCache = [];
    }

    private static function resolve(array $config): ?string
    {
        if (!empty($config['url'])) {
            return $config['url'];
        }

        try {
            return url_for($config['route']);
        } catch (Exception $e) {
            return null;
        }
    }

    private static function routeResolves(array $config): bool
    {
        if (!empty($config['url'])) {
            return true;
        }

        // A missing or non-string route is a bad registration, and a bad
        // registration must cost its own menu entry and nothing else.
        //
        // knownRoute() is typed string. ahgSecurityClearancePlugin registered
        // ['module' => ..., 'action' => ...], and the resulting TypeError was
        // raised from inside the layout - so every page on that instance
        // returned an empty 200 because of one wrong menu entry. That is exactly
        // the failure this class documents itself as preventing: "a typo costs a
        // missing link, not a dead site".
        if (!isset($config['route']) || !is_string($config['route'])) {
            return false;
        }

        return self::knownRoute($config['route']);
    }

    /**
     * Is this route registered? Cached, because a layout can ask many times per
     * request and the routing table does not change mid-request.
     */
    private static function knownRoute(string $route): bool
    {
        $name = ltrim($route, '@');

        if (isset(self::$routeCache[$name])) {
            return self::$routeCache[$name];
        }

        $known = false;

        try {
            $routing = sfContext::getInstance()->getRouting();
            $known = $routing instanceof sfPatternRouting
                && array_key_exists($name, $routing->getRoutes());
        } catch (Exception $e) {
            $known = false;
        }

        return self::$routeCache[$name] = $known;
    }

    private static function permitted(array $config, $user): bool
    {
        $credentials = (array) ($config['credentials'] ?? []);

        if ([] === $credentials || null === $user) {
            return true;
        }

        try {
            if (!$user->isAuthenticated()) {
                return false;
            }

            foreach ($credentials as $credential) {
                if ($user->hasCredential($credential)) {
                    return true;
                }
            }
        } catch (Exception $e) {
            return false;
        }

        return false;
    }

    /**
     * Entries for a group, each with its URL and badge already resolved.
     *
     * This is what a template should use: it never has to call url_for, never
     * has to know a route name, and never has to count anything itself.
     *
     * @return array<string, array> id => config plus 'href' and 'badgeCount'
     */
    public static function resolved(string $group, $user = null): array
    {
        $out = [];

        foreach (self::items($group, $user) as $id => $config) {
            $config['href'] = self::resolve($config);

            if (null === $config['href']) {
                continue;
            }

            $config['badgeCount'] = self::badgeCount($config);
            $out[$id] = $config;
        }

        return $out;
    }

    /**
     * Group resolved entries under their section heading, preserving weight order.
     *
     * @return array<string, array> section label ('' when none) => entries
     */
    public static function sections(string $group, $user = null): array
    {
        $sections = [];

        foreach (self::resolved($group, $user) as $id => $config) {
            $sections[(string) ($config['section'] ?? '')][$id] = $config;
        }

        return $sections;
    }

    /**
     * Ask the contributing plugin for its count. Never throws: a badge is
     * decoration, and a plugin with a broken query should cost a number, not
     * the navigation.
     */
    private static function badgeCount(array $config): ?int
    {
        if (!is_callable($config['badge'] ?? null)) {
            return null;
        }

        try {
            $count = ($config['badge'])();
        } catch (Exception $e) {
            return null;
        }

        return (null === $count || $count <= 0) ? null : (int) $count;
    }

}
