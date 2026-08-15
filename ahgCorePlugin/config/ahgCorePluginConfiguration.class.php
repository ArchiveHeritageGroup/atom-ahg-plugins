<?php

/**
 * ahgCorePlugin Configuration
 *
 * Core utilities plugin for AHG extensions.
 * Provides shared services and contracts for all AHG plugins.
 */
class ahgCorePluginConfiguration extends sfPluginConfiguration
{
    /**
     * Shown by AtoM's stock plugin admin (sfPluginAdminPlugin). A plugin without
     * $summary is silently omitted from that list - see
     * plugins/sfPluginAdminPlugin/modules/sfPluginAdminPlugin/actions/pluginsAction.class.php:59.
     * Must not contain the word "theme", which the same line uses to filter themes out.
     */
    public static $summary = 'Core utilities and shared services for AHG plugins';

    public static $version = '1.0.0';

    /**
     * Plugin initialization
     */
    public function initialize()
    {
        // Bring the framework up before anything else in this plugin or any
        // other AHG plugin runs. See bootstrapFramework() for why this lives
        // here rather than in AtoM's ProjectConfiguration.
        self::bootstrapFramework($this->configuration->getRootDir());

        // Register autoloader for AhgCore namespace
        $this->registerAutoloader();

        // Register global error notification handler
        \AhgCore\Services\ErrorNotificationService::register();

        // Register queue handler for async error alert emails
        if (class_exists('AtomFramework\Services\QueueJobRegistry', false)) {
            \AtomFramework\Services\QueueJobRegistry::register(
                'error:send-alert',
                'AhgCore\Services\ErrorAlertQueueHandler'
            );
        }

        // Culture guard (HTTP 500 fix): reset any request culture that is not an
        // enabled UI language (app_i18n_languages) back to the default culture.
        // Without this, term/actor/IO browse sorts on i18n.<culture>.title.alphasort,
        // which has no mapping in OpenSearch for non-enabled cultures present only in
        // (test/legacy) i18n data — crawlers follow the translation-link switcher to
        // those cultures and every page 500s. Falling back to the default renders the
        // page normally instead of erroring.
        $this->dispatcher->connect('controller.change_action', ['ahgCorePluginConfiguration', 'enforceEnabledCulture']);

        // Missing-representation guard. Connected after enforceEnabledCulture so
        // that guard's HTML fallback for authority modules is applied first.
        $this->dispatcher->connect('controller.change_action', ['ahgCorePluginConfiguration', 'refuseUnavailableFormat']);

        // Base AtoM dereferences route-parse results without checking them in 53
        // places across 13 modules. See sanitisePostedResourceValues().
        //
        // This runs here, during plugin configuration, because it has to mutate
        // $_POST itself. sfWebRequest::initialize() takes a copy
        // ($this->postParameters = $_POST) and only the parameter holder is
        // exposed to the request.filter_parameters event; base binds its forms
        // with $request->getPostParameters(), which returns that untouched copy.
        // Filtering the event therefore has no effect on form binding at all -
        // established the hard way after two guards that appeared correct and
        // changed nothing.
        // Drop posted values that base AtoM will dereference without checking.
        // Done at controller.change_action, not here: the only reliable test is
        // routing->parse(), and routing does not exist during plugin config.
        // See guardPostedResourceValues().
        $this->dispatcher->connect('controller.change_action', ['ahgCorePluginConfiguration', 'guardPostedResourceValues']);

        // Shared edit-form endpoints, used by every descriptive standard.
        // Registered here rather than in ahgInformationObjectManagePlugin so a
        // standard plugin installed on its own has what it declares. See
        // modules/ahgIoForm/actions/actions.class.php.
        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadIoFormRoutes']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);

        if (!in_array('ahgIoForm', $enabledModules, true)) {
            $enabledModules[] = 'ahgIoForm';
            sfConfig::set('sf_enabled_modules', $enabledModules);
        }

        // Apply the instance's own timezone, if it has set one. AtoM ships
        // America/Vancouver in locked base config and symfony applies it after
        // plugin initialize(), so this is the first point it will stick.
        // See TimezoneOverride.
        require_once __DIR__.'/../lib/Listeners/TimezoneOverride.php';
        $this->dispatcher->connect('context.load_factories', ['\AhgCore\Listeners\TimezoneOverride', 'apply']);

        // Capture exceptions symfony handles itself.
        //
        // ErrorNotificationService installs set_exception_handler, which only
        // fires for exceptions that reach PHP uncaught. Symfony catches most of
        // them first, renders its own error page and returns 500, so the only
        // record left was the shutdown handler's bare "HTTP 500 Internal Server
        // Error: /index.php/..." with no file, no line and no trace. That is
        // enough to know something failed and nothing else, which cost most of a
        // morning on the repository form.
        //
        // application.throw_exception is symfony's own notification for exactly
        // those. Listening does not change behaviour: nothing is returned, so
        // symfony still renders the page it would have.
        $this->dispatcher->connect('application.throw_exception', ['ahgCorePluginConfiguration', 'logThrownException']);

        // Then, for administrators only, show what broke instead of the generic
        // page. Registered AFTER the logger on purpose: notifyUntil stops at the
        // first listener returning true, and this one does when it takes over the
        // response - so it must not run before the exception has been recorded.
        // logThrownException returns nothing, so it never stops the chain itself.
        require_once __DIR__.'/../lib/Listeners/AdminErrorDetail.php';
        $this->dispatcher->connect('application.throw_exception', ['\AhgCore\Listeners\AdminErrorDetail', 'handle']);

        // Supply the CSRF token wherever CSRF is enforced. See injectCsrfToken().
        $this->dispatcher->connect('response.filter_content', ['ahgCorePluginConfiguration', 'injectCsrfToken']);

        // Render whatever plugins have contributed to AhgNav. See injectNavEntries().
        $this->dispatcher->connect('response.filter_content', ['ahgCorePluginConfiguration', 'injectNavEntries']);

        // Make the CSP helpers available to every template. See AhgCspHelper.php.
        $this->dispatcher->connect('context.load_factories', ['ahgCorePluginConfiguration', 'loadCoreHelpers']);

        // Apply data-ahg-style declarations. See injectStyleApplier().
        $this->dispatcher->connect('response.filter_content', ['ahgCorePluginConfiguration', 'injectStyleApplier']);

        // A route into registration from the login screen. Offers whichever
        // registrations the instance actually has, read from the routing table -
        // so it works with the ordinary-account plugin, the researcher one, both,
        // or neither. See LoginRegisterLinkInjector.
        require_once __DIR__.'/../lib/Listeners/LoginRegisterLinkInjector.php';
        $this->dispatcher->connect('response.filter_content', ['\AhgCore\Listeners\LoginRegisterLinkInjector', 'filter']);

        // Attribution in the footer of pages an AHG plugin serves. See injectPluginCredit().
        $this->dispatcher->connect('response.filter_content', ['ahgCorePluginConfiguration', 'injectPluginCredit']);
    }

    /**
     * Attribution line in the footer, on pages an AHG plugin serves.
     *
     * Deliberately not site-wide. Someone who installs one plugin has not handed
     * over their whole site, and stamping a credit onto AtoM's own browse and
     * search pages would claim work that is not ours. The test is therefore the
     * module: if the request is being served out of an ahg*Plugin, the credit
     * appears; otherwise the page is left exactly as AtoM rendered it.
     *
     * Injected here rather than written into templates because the alternative is
     * the same block copied into every template of every plugin, which drifts the
     * moment one of them is edited.
     *
     * Markup carries no style attribute. An enforcing Content Security Policy
     * drops those silently, so the styling comes from Bootstrap classes the theme
     * already ships - see AhgCspHelper.php for the longer version of that story.
     */
    public static function injectPluginCredit($event, $content)
    {
        if (!is_string($content) || false === stripos($content, '</footer>')) {
            return $content;
        }

        // Idempotent: response.filter_content can fire more than once per request.
        if (false !== stripos($content, 'ahg-plugin-credit')) {
            return $content;
        }

        if (!self::isAhgModule()) {
            return $content;
        }

        $credit =
            '<div class="ahg-plugin-credit text-center small text-muted py-2">'
            .'Powered by: '
            .'<a href="https://theahg.co.za/" rel="noopener noreferrer" target="_blank">'
            .'The Archive and Heritage Digital Commons Group</a>'
            .' and '
            .'<a href="https://plainsailingisystems.co.za/" rel="noopener noreferrer" target="_blank">'
            .'Plain Sailing Informations Systems</a>'
            .'</div>';

        // Last thing inside the footer, so it sits under whatever the theme put there.
        $position = strripos($content, '</footer>');

        if (false === $position) {
            return $content;
        }

        return substr($content, 0, $position).$credit.substr($content, $position);
    }

    /**
     * Is this page an AHG plugin's own screen, rather than one of AtoM's?
     *
     * Two tests, and both have to pass.
     *
     * First: the action actually executing must live inside an ahg*Plugin. Taken
     * by reflection on the running action rather than from the module name,
     * because a name alone proves nothing about who is serving it.
     *
     * Second: base AtoM must not ship a module of that name. ahgCorePlugin
     * overrides informationobject, user and settings - all three are AtoM's own
     * module names, so a name-only test put the credit on browse, on the login
     * page and on the home page. Overriding one of AtoM's screens does not make it
     * ours, and claiming it in the footer would be exactly the over-reach this
     * check exists to prevent.
     */
    protected static function isAhgModule()
    {
        try {
            if (!sfContext::hasInstance()) {
                return false;
            }

            $context = sfContext::getInstance();
            $module = $context->getModuleName();

            if (!$module) {
                return false;
            }

            // A module base AtoM also ships is AtoM's screen, override or not.
            $appModuleDir = sfConfig::get('sf_app_module_dir');

            if ($appModuleDir && is_dir($appModuleDir.'/'.$module)) {
                return false;
            }

            $entry = $context->getActionStack()->getLastEntry();

            if (!$entry) {
                return false;
            }

            $file = (new ReflectionClass($entry->getActionInstance()))->getFileName();

            return $file && preg_match('#[/\\\\]plugins[/\\\\]ahg[A-Za-z0-9]*Plugin[/\\\\]#', $file);
        } catch (Throwable $e) {
            // Attribution is never worth a failed request.
            return false;
        }
    }

    /**
     * Apply the declarations carried in data-ahg-style.
     *
     * A style attribute is dropped outright by an enforcing Content Security
     * Policy, and no nonce can rescue it - nonces apply to <style> and <script>
     * elements. A fixed declaration can become a class, but a computed one cannot:
     * a per-record width or an institution's own colour is not knowable when the
     * stylesheet is written.
     *
     * CSP does not cover the CSSOM, so the declaration travels in a data attribute
     * and is set as a property here. That is the one route that keeps a computed
     * value working under the enforcing header.
     *
     * Injected once from core rather than as a script in each of the 178 templates
     * that need it, so a template author writes data-ahg-style and nothing else.
     */
    public static function injectStyleApplier($event, $content)
    {
        if (!is_string($content) || false === stripos($content, 'data-ahg-style')) {
            return $content;
        }

        if (false === stripos($content, '</body>')) {
            return $content;
        }

        $nonce = sfConfig::get('csp_nonce', '');
        $nonceAttr = $nonce ? ' '.preg_replace('/^nonce=/', 'nonce="', $nonce).'"' : '';

        // setProperty rather than assigning cssText or the style attribute:
        // writing the attribute back would be blocked by exactly the policy this
        // exists to work with. Custom properties (--foo) are handled too, which
        // is why the name is passed through untouched.
        $js = <<<'APPLIER'
(function () {
  function apply(root) {
    root.querySelectorAll('[data-ahg-style]').forEach(function (el) {
      var decls = el.getAttribute('data-ahg-style');
      if (!decls) { return; }
      decls.split(';').forEach(function (decl) {
        var i = decl.indexOf(':');
        if (i < 1) { return; }
        var name = decl.slice(0, i).trim();
        var value = decl.slice(i + 1).trim();
        if (!name || !value) { return; }
        try { el.style.setProperty(name, value); } catch (e) {}
      });
      el.removeAttribute('data-ahg-style');
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { apply(document); });
  } else {
    apply(document);
  }

  // Content added after load - autocompletes, modals, AJAX panels - carries the
  // same attributes, so watch for it rather than leaving those elements bare.
  if (window.MutationObserver) {
    new MutationObserver(function (records) {
      records.forEach(function (r) {
        r.addedNodes.forEach(function (n) {
          if (1 !== n.nodeType) { return; }
          if (n.hasAttribute && n.hasAttribute('data-ahg-style')) { apply(n.parentNode || document); }
          else if (n.querySelector && n.querySelector('[data-ahg-style]')) { apply(n); }
        });
      });
    }).observe(document.documentElement, { childList: true, subtree: true });
  }
})();
APPLIER;

        $script = "\n<script".$nonceAttr.">".$js."</script>\n";
        $pos = stripos($content, '</body>');

        return substr($content, 0, $pos).$script.substr($content, $pos);
    }

    /**
     * Load ahgCorePlugin's template helpers.
     *
     * AhgCsp gives templates ahg_style_block() and ahg_script_block(), which emit
     * inline CSS and JavaScript carrying the CSP nonce. Loaded centrally so that
     * writing a correct inline style is the path of least resistance: every
     * instance of this being got wrong so far has been an author not knowing the
     * rule rather than disagreeing with it, and an unnonced <style> fails
     * silently, which is the worst way for a convention to be enforced.
     */
    public static function loadCoreHelpers($event)
    {
        try {
            $event->getSubject()->getConfiguration()->loadHelpers(['AhgCsp']);
        } catch (Throwable $e) {
            // A missing helper must not take down the request.
        }
    }

    /**
     * Put contributed navigation entries into the stock AtoM menus.
     *
     * AhgNav has always let a plugin register its own entries, but nothing
     * rendered them outside ahgThemeB5Plugin - so on a stock AtoM an installed
     * plugin had no way into the navigation at all. ahgFeedbackPlugin was
     * reachable at /feedback and invisible from every menu; ahgPreservationPlugin
     * had no entry of any kind. A plugin nobody can find is a plugin nobody uses.
     *
     * Two targets, both present in stock AtoM 2.9 and 2.10:
     *
     *   quick-links-menu  management entries. It exists in the top navigation
     *                     and ships with nothing under its heading, which is
     *                     exactly what it is for.
     *   browse-menu       browse entries, alongside AtoM's own seven.
     *
     * Skipped entirely when ahgThemeB5Plugin is enabled: that theme renders its
     * own Manage menu from the same registry, and both firing would duplicate
     * every entry.
     */
    public static function injectNavEntries($event, $content)
    {
        if (!is_string($content) || false === stripos($content, 'quick-links-menu')) {
            return $content;
        }

        if (!class_exists('AhgNav', false)) {
            return $content;
        }

        try {
            $configuration = sfProjectConfiguration::getActive();

            if ($configuration && in_array('ahgThemeB5Plugin', $configuration->getPlugins(), true)) {
                return $content;
            }

            $user = sfContext::hasInstance() ? sfContext::getInstance()->getUser() : null;
        } catch (Throwable $e) {
            return $content;
        }

        foreach ([
            'manage' => 'quick-links-menu',
            'browse' => 'browse-menu',
        ] as $group => $anchor) {
            $items = AhgNav::resolved($group, $user);

            if (!$items) {
                continue;
            }

            $html = '';

            foreach ($items as $item) {
                $badge = empty($item['badgeCount'])
                    ? ''
                    : ' <span class="badge bg-secondary">'.(int) $item['badgeCount'].'</span>';

                $html .= sprintf(
                    '<li><a class="dropdown-item" href="%s">%s%s</a></li>',
                    htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'),
                    $badge
                );
            }

            // Append inside the target <ul>, so the entries sit under the
            // heading AtoM already renders rather than replacing anything.
            $content = preg_replace(
                '#(<ul[^>]*aria-labelledby="'.preg_quote($anchor, '#').'"[^>]*>)(.*?)(</ul>)#s',
                '$1$2'.str_replace('\\', '\\\\', $html).'$3',
                $content,
                1
            );
        }

        return $content;
    }

    /**
     * Put the CSRF token on the page, because the framework enforces it.
     *
     * CsrfService rejects unsafe requests that arrive without a token, and the
     * browser gets that token from <meta name="csrf-token">. Only
     * ahgThemeB5Plugin's _layout_start.php ever emitted it. Enforcement therefore
     * shipped in the framework while supply shipped in the theme, and an install
     * without the theme enforced a token nothing could provide: every AJAX POST
     * in every AHG plugin came back 403 "CSRF token validation failed". Found on
     * ahgBackupPlugin, whose buttons all failed this way.
     *
     * The injected script is the theme's, verbatim. It reads the meta and adds
     * the token to fetch, XMLHttpRequest and posted forms, and it opens with
     * "if (!t) return" - so with no meta it silently did nothing, which is why
     * this failed quietly rather than loudly.
     *
     * Skipped when the theme has already done it, so the two never both fire.
     */
    public static function injectCsrfToken($event, $content)
    {
        if (!is_string($content) || '' === $content) {
            return $content;
        }

        // Only complete HTML documents. JSON and partials have no head to patch.
        if (false === stripos($content, '</head>')) {
            return $content;
        }

        if (false !== stripos($content, 'name="csrf-token"')) {
            return $content;
        }

        if (!class_exists('\AtomFramework\Services\CsrfService')) {
            return $content;
        }

        try {
            $meta = \AtomFramework\Services\CsrfService::getMetaTag();
        } catch (Throwable $e) {
            // A missing token must not cost the page.
            return $content;
        }

        // Inline scripts are dropped silently without the nonce under this
        // project's CSP, which would leave the meta present and useless.
        $nonce = sfConfig::get('csp_nonce', '');
        $nonceAttr = $nonce ? ' '.preg_replace('/^nonce=/', 'nonce="', $nonce).'"' : '';

        $js = '(function(){var m=document.querySelector(\'meta[name="csrf-token"]\');var t=m?m.getAttribute(\'content\'):\'\';if(!t)return;var u=/^(POST|PUT|DELETE|PATCH)$/i;function so(x){try{return new URL(x,window.location.href).origin===window.location.origin;}catch(e){return true;}}if(window.fetch){var f=window.fetch;window.fetch=function(i,n){n=n||{};var url=(typeof i===\'string\')?i:(i&&i.url)||\'\';var mth=n.method||(typeof i===\'object\'&&i.method)||\'GET\';if(u.test(mth)&&so(url)){var h=new Headers(n.headers||(typeof i===\'object\'&&i.headers)||{});if(!h.has(\'X-CSRF-TOKEN\'))h.set(\'X-CSRF-TOKEN\',t);n.headers=h;}return f.call(this,i,n);};}var o=XMLHttpRequest.prototype.open,s=XMLHttpRequest.prototype.send;XMLHttpRequest.prototype.open=function(m2,url){this.__csrf=u.test(m2||\'\')&&so(url||\'\');return o.apply(this,arguments);};XMLHttpRequest.prototype.send=function(){if(this.__csrf){try{this.setRequestHeader(\'X-CSRF-TOKEN\',t);}catch(e){}}return s.apply(this,arguments);};function af(fm){if(!fm||fm.tagName!==\'FORM\')return;if((fm.getAttribute(\'method\')||\'GET\').toUpperCase()!==\'POST\')return;if(!so(fm.getAttribute(\'action\')||window.location.href))return;if(fm.querySelector(\'input[name="_ahg_csrf_token"]\'))return;if(fm.querySelector(\'input[name="_csrf_token"]\'))return;var el=document.createElement(\'input\');el.type=\'hidden\';el.name=\'_ahg_csrf_token\';el.value=t;fm.appendChild(el);}document.addEventListener(\'submit\',function(e){af(e.target);},true);var fsub=HTMLFormElement.prototype.submit;HTMLFormElement.prototype.submit=function(){af(this);return fsub.apply(this,arguments);};})();';

        $injection = "\n    ".$meta."\n    <script".$nonceAttr.">".$js."</script>\n";

        $pos = stripos($content, '</head>');

        return substr($content, 0, $pos).$injection.substr($content, $pos);
    }

    /**
     * Register the framework autoloader and the routing classes named by string.
     *
     * This is what lets the AHG plugins run on an unmodified AtoM.
     *
     * AtoM already enables plugins from the database: sfPluginAdminPlugin is in
     * AtoM's own hardcoded list, and its initialize() reads the `plugins` setting
     * and calls enablePlugins() on the list it finds. So the plugins themselves
     * need no change to ProjectConfiguration at all - they are already being
     * enabled. What was missing was only the autoloader: every AHG plugin
     * references AtomFramework\* classes, and without vendor/autoload plus the
     * PSR-4 prefixes those are undefined, so the first plugin configuration to
     * name one fatals and the whole response comes back empty with a 200.
     *
     * Doing it here rather than in ProjectConfiguration means an AtoM install
     * needs no modified files. bootstrap.php guards itself with
     * ATOM_FRAMEWORK_LOADED, so an instance whose ProjectConfiguration still
     * calls it is unaffected.
     *
     * Ordering matters: this must run before any other AHG plugin's
     * initialize(). ExtensionManager keeps ahgCorePlugin ahead of its siblings
     * in the `plugins` setting for that reason.
     */
    public static function bootstrapFramework(string $rootDir): void
    {
        // The framework ships from two places: as atom-framework/ beside the AtoM
        // root, which is how PSIS and Heratio run it, and packaged as
        // plugins/ahgRuntimePlugin, which is what bin/build-runtime-plugin
        // generates for a customer install. Only the first was looked for here,
        // and the miss returned silently - so a packaged install loaded every
        // plugin and registered every route, then died on the first database call
        // with "connection() on null" and nothing to point at the cause.
        $frameworkDir = null;

        foreach ([
            $rootDir.'/atom-framework',
            $rootDir.'/plugins/ahgRuntimePlugin',
        ] as $candidate) {
            if (file_exists($candidate.'/bootstrap.php')) {
                $frameworkDir = $candidate;

                break;
            }
        }

        if (null === $frameworkDir) {
            return;
        }

        require_once $frameworkDir.'/bootstrap.php';

        // Routes are declared with classes named as strings, which RouteLoader
        // instantiates when routing.load_configuration fires. They live in
        // .class.php files in the global namespace, so PSR-4 does not reach
        // them and the autoloader registered above is not enough. Missing, the
        // first plugin route takes down every page rather than one route.
        //
        // The Qubit parents must exist before the AHG subclasses extend them.
        foreach (['QubitRoute', 'QubitMetadataRoute'] as $class) {
            $path = $rootDir.'/lib/routing/'.$class.'.class.php';

            if (!class_exists($class, false) && file_exists($path)) {
                require_once $path;
            }
        }

        foreach (['AhgMetadataRoute', 'AddActionRoute', 'SafeRequestRoute'] as $class) {
            if (class_exists($class, false)) {
                continue;
            }

            foreach ([
                $frameworkDir.'/src/Routing/'.$class.'.class.php',
                $rootDir.'/lib/routing/'.$class.'.class.php',
            ] as $path) {
                if (file_exists($path)) {
                    require_once $path;

                    break;
                }
            }
        }
    }

    /**
     * Return 404 when the requested format has no template in the target module.
     *
     * Symfony resolves the template path to an empty string when a module ships
     * no template for the requested format, and sfPHPView::renderFile() then runs
     * require('') and fatals. Because there is an ob_start() immediately above
     * that require, the buffer is discarded and the caller gets either a blank
     * HTTP 200 or a bare 500 rather than an error page - and the log fills with
     * "Failed opening required '/'".
     *
     * Reached by malformed export URLs only, never by the UI. The supported
     * syntax is the semicolon form (/<slug>;ead?sf_format=xml), which routes to
     * sfEadPlugin and works correctly; the query-string form (?template=ead)
     * never populates the route's template parameter, so the module stays on the
     * record's display standard, which has no XML template. A 404 is the honest
     * answer: that representation does not exist.
     *
     * Fails open - if the template directories cannot be resolved, the request
     * proceeds untouched.
     *
     * @param sfEvent $event
     */
    public static function refuseUnavailableFormat($event)
    {
        // Refuse at most once per request. Throwing a second time would do it
        // from inside the forward the first throw caused, which is the failure
        // described below.
        static $refused = false;

        $module = $event['module'] ?? null;
        if (empty($module)) {
            return;
        }

        try {
            $context = sfContext::getInstance();
            $request = $context ? $context->getRequest() : null;
            if (!$request) {
                return;
            }

            $format = $request->getParameter('sf_format');
            if (empty($format) || 'html' === $format) {
                return;
            }

            // Never refuse symfony's own internal pages.
            //
            // A 404 from anywhere - this guard or the action itself - forwards to
            // error_404_module, which settings.yml sets to "admin". That forward
            // fires controller.change_action again with sf_format still set, and
            // the admin module ships no non-HTML template at all. So the guard
            // refused the very page symfony was rendering to report the first
            // 404, throwing from inside the ob_start() above
            // sfPHPView::renderFile(). The buffer is discarded and the caller
            // gets a 0-byte HTTP 200 instead of the 404 - which is exactly the
            // blank 200 this guard exists to prevent, caused by the guard.
            //
            // Measured on PSIS 2026-08-15: every non-HTML format on a museum
            // record returned 200 with an empty body.
            //
            // Force HTML for these instead, so the error template resolves and
            // the status code is actually delivered. Matched on module AND
            // action, because "admin" is a real module whose other actions must
            // keep whatever formats they support.
            $action = $event['action'] ?? null;
            $internal = [
                [sfConfig::get('sf_error_404_module'), sfConfig::get('sf_error_404_action', 'error404')],
                [sfConfig::get('sf_secure_module'), sfConfig::get('sf_secure_action', 'secure')],
                [sfConfig::get('sf_login_module'), sfConfig::get('sf_login_action', 'login')],
                [sfConfig::get('sf_module_disabled_module'), sfConfig::get('sf_module_disabled_action', 'disabled')],
            ];

            foreach ($internal as $pair) {
                if ($module === $pair[0] && $action === $pair[1]) {
                    $request->setRequestFormat('html');
                    $request->setParameter('sf_format', 'html');

                    return;
                }
            }

            if ($refused) {
                // Already refused this request; this is a forward off the back of
                // it. Let it render rather than throwing again.
                $request->setRequestFormat('html');
                $request->setParameter('sf_format', 'html');

                return;
            }

            if (self::moduleHasFormatTemplate($module, $format)) {
                return;
            }

            // Reset to HTML before raising: the 404 page is itself rendered
            // through the view layer, so leaving the format as (say) xml makes
            // the error template lookup fail exactly the same way and the caller
            // gets a blank 200 instead of a 404.
            $request->setRequestFormat('html');
            $request->setParameter('sf_format', 'html');
            $refused = true;
        } catch (\Throwable $e) {
            return; // never let the guard break the request
        }

        // Deliberately outside the try: this must propagate, not be swallowed.
        throw new sfError404Exception(sprintf(
            'No "%s" representation exists for module "%s".',
            $format,
            $module
        ));
    }

    /**
     * Whether a module ships at least one template for the given format.
     *
     * Returns true when it cannot be determined, so an unexpected condition
     * lets the request through rather than 404ing it.
     */
    private static function moduleHasFormatTemplate($module, $format)
    {
        // Formats are used to build a glob; keep them to a safe character set.
        if (!preg_match('/^[a-z0-9]{1,16}$/i', (string) $format)) {
            return true;
        }

        try {
            $dirs = sfContext::getInstance()->getConfiguration()->getTemplateDirs($module);
        } catch (\Throwable $e) {
            return true;
        }

        foreach ((array) $dirs as $dir) {
            if (is_dir($dir) && glob(rtrim($dir, '/').'/*.'.$format.'.php')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Runs before every action (controller.change_action). Two HTTP-500 guards,
     * both fail open (any error here must never break the request):
     *   1. Reset a non-enabled request culture to the default (else term/actor/IO
     *      browse sorts on an unmapped OpenSearch culture field and 500s).
     *   2. Force HTML for the actor show action when an unsupported format is
     *      requested (the base actor module has no non-HTML template, so
     *      ?sf_format=xml from crawlers throws sfRenderException -> 500).
     */
    public static function enforceEnabledCulture($event)
    {
        try {
            $context = sfContext::getInstance();
            if (!$context || !$context->getUser()) {
                return;
            }
            $request = $context->getRequest();

            // --- Guard 2: authority-record show with an unsupported (non-HTML) format ---
            // Actor/authority pages render via the ISAAR/ISDIAH/ISDF plugin modules,
            // which have no non-HTML template — so ?sf_format=xml (appended by crawlers)
            // throws sfRenderException -> HTTP 500. Force HTML for them. IO/EAD xml
            // export (informationobject module) is deliberately not in this list.
            $authorityModules = ['sfIsaarPlugin', 'sfIsdiahPlugin', 'sfIsdfPlugin'];
            if ($request && in_array($event['module'] ?? null, $authorityModules, true) && 'index' === ($event['action'] ?? null)) {
                $fmt = $request->getParameter('sf_format');
                if (!empty($fmt) && 'html' !== $fmt) {
                    $request->setRequestFormat('html');
                    $request->setParameter('sf_format', 'html');
                }
            }

            // --- Guard 1: non-enabled request culture ---
            $enabled = self::enabledCultures();
            if (empty($enabled)) {
                return; // no allow-list resolvable -> leave culture untouched
            }

            $user = $context->getUser();

            // Prefer an explicit ?sf_culture (may not be applied to the user yet),
            // otherwise the user's current culture.
            $requested = $request ? $request->getParameter('sf_culture') : null;
            $culture = $requested ?: $user->getCulture();

            if (!$culture || in_array($culture, $enabled, true)) {
                return; // enabled (or unset) -> nothing to do
            }

            $default = sfConfig::get('default_culture');
            if (!$default || !in_array($default, $enabled, true)) {
                $default = in_array('en', $enabled, true) ? 'en' : reset($enabled);
            }

            $user->setCulture($default);
            if ($request) {
                $request->setParameter('sf_culture', $default);
            }
        } catch (\Throwable $e) {
            // never let the guard break the request
        }
    }

    /**
     * Enabled UI languages (the allow-list), read from the same source AtoM uses
     * to build app_i18n_languages: setting rows with scope 'i18n_languages'.
     * sfConfig('app_i18n_languages') is not reliably populated at
     * controller.change_action time, so query directly. Cached per request.
     */
    private static function enabledCultures(): array
    {
        static $cultures = null;
        if (null !== $cultures) {
            return $cultures;
        }
        $cultures = [];
        try {
            // Prefer the runtime config when available...
            $cfg = sfConfig::get('app_i18n_languages');
            if (is_array($cfg) && !empty($cfg)) {
                $cultures = array_values($cfg);
                return $cultures;
            }
            // ...otherwise read the enabled-languages settings directly.
            $rows = QubitPdo::fetchAll("SELECT name FROM setting WHERE scope = 'i18n_languages'");
            foreach ($rows as $row) {
                if (!empty($row->name)) {
                    $cultures[] = $row->name;
                }
            }
        } catch (\Throwable $e) {
            $cultures = [];
        }
        return $cultures;
    }

    /**
     * Register PSR-4 autoloader for plugin classes
     */
    protected function registerAutoloader()
    {
        spl_autoload_register(function ($class) {
            // Handle AhgCore namespace
            if (strpos($class, 'AhgCore\\') === 0) {
                $relativePath = str_replace('AhgCore\\', '', $class);
                $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativePath);
                $filePath = __DIR__ . '/../lib/' . $relativePath . '.php';

                if (file_exists($filePath)) {
                    require_once $filePath;
                    return true;
                }
            }

            // Handle ahgCorePlugin namespace (used by dependent plugins)
            if (strpos($class, 'ahgCorePlugin\\') === 0) {
                $relativePath = str_replace('ahgCorePlugin\\', '', $class);
                $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativePath);
                $filePath = __DIR__ . '/../lib/' . $relativePath . '.php';

                if (file_exists($filePath)) {
                    require_once $filePath;
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Get plugin root path
     */
    public static function getPluginPath(): string
    {
        return dirname(__DIR__);
    }

    /**
     * Get lib path
     */
    public static function getLibPath(): string
    {
        return dirname(__DIR__) . '/lib';
    }

    /**
     * Get web assets path
     */
    public static function getWebPath(): string
    {
        return dirname(__DIR__) . '/web';
    }

    /**
     * Drop submitted resource-URL values that do not resolve to a record.
     *
     * Base AtoM repeatedly does this when processing a form field:
     *
     *   $params = $this->context->routing->parse(Qubit::pathInfo($value));
     *   $resource = $params['_sf_route']->resource;   // null if nothing matches
     *   $value[$resource->id] = ...                   // fatal
     *
     * There are 53 of these across 13 modules in apps/qubit/modules - term (14),
     * informationobject (10), actor (6), user, repository, relation, object,
     * event, right, search, physicalobject, function and default. None checks the
     * result. Any submitted value that no longer resolves, whether because a term
     * was deleted, a slug changed, or the field was typed rather than selected,
     * takes the whole save down with an HTTP 500 and loses everything on the form.
     *
     * Found the hard way on 2026-08-06: accession, then repository, then actor,
     * each failing the same way within minutes of each other.
     *
     * apps/ is base AtoM and is never modified by this project, so the values are
     * removed before base sees them. Guarding it here rather than per plugin
     * because the defect is in thirteen modules and counting, and a per-module
     * fix would have to be written again for each one.
     *
     * SCOPE - deliberately narrow
     *
     * Only values shaped like an AtoM resource path are considered: a leading
     * slash, no whitespace, slug characters only. A website field holding
     * "https://example.com" does not match, and neither does ordinary prose. Of
     * those candidates, only ones whose slug is absent from the slug table are
     * dropped. Anything that resolves is passed through untouched, so a correctly
     * completed form behaves exactly as before.
     */
    /**
     * Form fields whose value is always a reference to another record.
     *
     * Base AtoM feeds each of these to routing->parse() and dereferences the
     * result without checking, so ANY value that does not resolve is fatal -
     * not just something shaped like a path. A donor name typed by hand rather
     * than picked from the autocomplete is the common case, and it is exactly
     * what a person doing this for the first time will do.
     *
     * Every one of these is a select or autocomplete bound to controlled
     * vocabulary, so a value that resolves to nothing carries no meaning that
     * could be preserved. Dropping it loses nothing that base would have kept:
     * base would have crashed and lost the entire form.
     */
    protected const RESOURCE_FIELDS = [
        'resource',                 // accession donor, relation targets
        'type', 'geographicSubregion', 'thematicArea',
        'descStatus', 'descDetail',
        'entityType', 'maintainingRepository',
        'placeAccessPoints', 'subjectAccessPoints', 'nameAccessPoints', 'genreAccessPoints',
        'levelOfDescription', 'repository', 'parent',
        'language', 'script', 'languageOfDescription', 'scriptOfDescription',
    ];

    public static function sanitisePostedResourceValues(): void
    {
        if ('POST' !== ($_SERVER['REQUEST_METHOD'] ?? '') || empty($_POST)) {
            return;
        }

        $parameters = $_POST;

        foreach ($parameters as $key => $value) {
            if (in_array($key, ['module', 'action', '_sf_route'], true)) {
                continue;
            }

            $always = in_array($key, self::RESOURCE_FIELDS, true);

            if (is_string($value)) {
                if ('' !== trim($value)
                    && ($always || self::isResourcePath($value))
                    && !self::slugResolves($value)
                ) {
                    unset($parameters[$key]);
                    self::noteDrop($key, $value);
                }

                continue;
            }

            if (!is_array($value)) {
                continue;
            }

            $kept = [];
            $changed = false;

            foreach ($value as $index => $item) {
                if (is_string($item)
                    && '' !== trim($item)
                    && ($always || self::isResourcePath($item))
                    && !self::slugResolves($item)
                ) {
                    $changed = true;
                    self::noteDrop($key, $item);

                    continue;
                }

                $kept[$index] = $item;
            }

            if ($changed) {
                $parameters[$key] = array_values($kept);
            }
        }

        $_POST = $parameters;
    }

    /**
     * Does this look like an AtoM resource URL rather than user prose?
     */
    protected static function isResourcePath(string $value): bool
    {
        $value = trim($value);

        if ('' === $value || '/' !== substr($value, 0, 1)) {
            return false;
        }

        return 1 === preg_match('#^/[A-Za-z0-9._~/\-]+$#', $value);
    }

    /**
     * Is the trailing path segment a slug that exists?
     */
    protected static function slugResolves(string $value): bool
    {
        $segments = array_values(array_filter(explode('/', parse_url(trim($value), PHP_URL_PATH) ?: ''), static function ($s) {
            return '' !== $s && 'index.php' !== $s;
        }));

        if ($segments === []) {
            return false;
        }

        $slug = (string) end($segments);

        $connection = self::slugConnection();

        if (null === $connection) {
            // If the check cannot run, keep the value: discarding something that
            // may be perfectly valid is worse than the crash we are avoiding.
            return true;
        }

        try {
            $statement = $connection->prepare('SELECT 1 FROM slug WHERE slug = ? LIMIT 1');
            $statement->execute([$slug]);

            return false !== $statement->fetchColumn();
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * A connection usable during plugin configuration.
     *
     * Propel is not initialised this early - its database manager is set up well
     * after plugin configuration runs - so asking it for a connection here always
     * throws, the check fails open, and the guard silently does nothing. Reading
     * AtoM's own config and opening one connection avoids depending on
     * initialisation order. Held for the request, so this costs one connection.
     */
    protected static function slugConnection(): ?\PDO
    {
        static $connection = false;

        if (false !== $connection) {
            return $connection;
        }

        $connection = null;

        try {
            $configFile = dirname(__DIR__, 3).'/config/config.php';

            if (!file_exists($configFile)) {
                return null;
            }

            $config = require $configFile;
            $params = $config['all']['propel']['param'] ?? null;

            if (!is_array($params) || empty($params['dsn'])) {
                return null;
            }

            $dsn = $params['dsn'];

            if (false === strpos($dsn, 'host=')) {
                $dsn = preg_replace('/^mysql:/', 'mysql:host=localhost;', $dsn);
            }

            $connection = new \PDO(
                $dsn,
                $params['username'] ?? 'root',
                $params['password'] ?? '',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_TIMEOUT => 3]
            );
        } catch (\Exception $e) {
            $connection = null;
        }

        return $connection;
    }

    protected static function noteDrop(string $field, string $value): void
    {
        try {
            error_log(sprintf('ahgCore: dropped unresolvable resource value for "%s": %s', $field, $value));
        } catch (\Exception $e) {
        }
    }


    /**
     * Record an exception symfony is about to handle, with its stack trace.
     *
     * Deliberately returns nothing so the event stays unhandled and symfony's
     * error page renders exactly as before. This only observes.
     */
    public static function logThrownException($event)
    {
        try {
            $exception = $event->getSubject();

            if (!$exception instanceof \Throwable) {
                return;
            }

            if (!class_exists('AhgCore\\Services\\ErrorNotificationService', false)
                && !class_exists('AhgCore\\Services\\ErrorNotificationService')) {
                return;
            }

            // A 404 is not a server fault, and this listener sees a lot of them:
            // symfony raises sfError404Exception for a missing record, for an
            // unroutable URL, and - through refuseUnavailableFormat() - for a
            // format a module does not ship. Search engine crawlers probe all
            // three continuously (?sf_format=xml, ?template=eac, ;skos), and each
            // was recorded at error level against a fabricated HTTP 500, which
            // buries genuine faults under expected refusals.
            //
            // Skipped rather than downgraded, to match the policy the shutdown
            // handler already applies to the same responses:
            // ErrorNotificationService::logHttpErrorResponse() drops every 4xx as
            // not actionable. Downgrading would still write a row per crawler
            // probe and leave the two paths disagreeing about the same request.
            if ($exception instanceof \sfError404Exception) {
                return;
            }

            \AhgCore\Services\ErrorNotificationService::logToDatabase(
                'error',
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                $exception->getTraceAsString(),
                $exception,
                500
            );
        } catch (\Throwable $ignored) {
            // Logging must never become the failure it is trying to record.
        }
    }


    /**
     * Remove posted values that base AtoM would dereference into a fatal.
     *
     * THE TEST THAT MATTERS
     *
     * Base does exactly this, in 53 places across 13 modules:
     *
     *   $params = $this->context->routing->parse(Qubit::pathInfo($value));
     *   $resource = $params['_sf_route']->resource;   // never checked
     *
     * So the only question worth asking is whether routing->parse() yields a
     * resource. Asking anything else gives the wrong answer: an earlier version
     * of this guard checked whether the slug existed in the slug table, which
     * passed the donor "rock-art-research-institute" through as valid while
     * routing still resolved it to a route with a null resource, and the crash
     * continued unchanged. Same value, two tests, opposite verdicts.
     *
     * WHY HERE
     *
     * routing is not available during plugin configuration, and $_POST is not
     * what base reads. sfWebRequest::initialize() copies $_POST into its own
     * postParameters, and forms are bound with getPostParameters(); the
     * request.filter_parameters event only reaches the parameter holder, so
     * filtering it has no effect on form binding whatsoever. This runs at
     * controller.change_action - after routing, before the action executes and
     * binds the form - and edits postParameters directly.
     *
     * Only fields that are always record references are considered, so ordinary
     * text is never touched. A reference that resolves to nothing has no meaning
     * to preserve, and base's alternative is to lose the entire form.
     */
    public static function guardPostedResourceValues($event)
    {
        try {
            $context = sfContext::getInstance();
            $request = $context->getRequest();

            if (!$request instanceof sfWebRequest || !$request->isMethod('post')) {
                return;
            }

            $property = new ReflectionProperty('sfWebRequest', 'postParameters');
            $property->setAccessible(true);

            $post = $property->getValue($request);

            if (!is_array($post) || $post === []) {
                return;
            }

            $changed = false;
            $post = self::filterResourceValues($post, $context, $changed);

            if ($changed) {
                // Both stores, because base reads from both. Forms bind with
                // getPostParameters(), while the relation components read
                // $this->request['relatedDonor'], which comes from the parameter
                // holder. Updating only one leaves the other still carrying the
                // value that crashes.
                $property->setValue($request, $post);

                $holder = $request->getParameterHolder();

                foreach ($post as $key => $value) {
                    if (null !== $holder->get($key)) {
                        $holder->set($key, $value);
                    }
                }
            }
        } catch (Throwable $ignored) {
            // A guard that throws is worse than the crash it prevents.
        }
    }

    /**
     * Base AtoM's own resolution test, applied honestly.
     */
    protected static function routeResolves($context, string $value): bool
    {
        try {
            $params = $context->routing->parse(Qubit::pathInfo($value));

            return isset($params['_sf_route']) && null !== $params['_sf_route']->resource;
        } catch (Throwable $e) {
            return false;
        }
    }


    /**
     * Walk a posted structure and drop reference values that resolve to nothing.
     *
     * Recursive because the values are not at the top level. The relation
     * components post nested structures - relatedDonor[resource], or
     * relatedDonors[0][resource] once the dialog JavaScript has run - and a
     * guard that only inspected top-level keys silently did nothing while the
     * crash continued. That was this bug's fourth false start; the shape of the
     * data was the thing to check first.
     */
    protected static function filterResourceValues(array $input, $context, bool &$changed): array
    {
        $output = [];

        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $output[$key] = self::filterResourceValues($value, $context, $changed);

                continue;
            }

            // A bare integer is a primary key, not a route reference, and must be
            // passed through untouched.
            //
            // The same field name carries both shapes. A note's "type" posts a
            // term id from a select, while a relation's "type" posts a resource
            // path. routeResolves() runs the value through Qubit::pathInfo(), which
            // an id can never satisfy, so every id-valued field in RESOURCE_FIELDS
            // was being dropped - silently, since dropping is the guard's normal
            // and quiet behaviour. Saving a repository lost its note types that
            // way: 120, 124 and 125 were discarded although all three are real
            // terms with slugs.
            $isIdentifier = is_string($value) && ctype_digit(trim($value));

            if (!is_string($value)
                || '' === trim($value)
                || $isIdentifier
                || !in_array((string) $key, self::RESOURCE_FIELDS, true)
                || self::routeResolves($context, $value)
            ) {
                $output[$key] = $value;

                continue;
            }

            $changed = true;
            self::noteDrop((string) $key, $value);
        }

        return $output;
    }


    /**
     * Is any module capable of rendering a description form installed?
     *
     * Checked by scanning enabled plugin paths for the module directory rather
     * than by asking the routing or the context, neither of which is usable this
     * early - routing.load_configuration fires before the context exists.
     *
     * The list is spelled out here instead of read from IoFormHelper::MODULE_MAP
     * because that class is in core's lib/ and is not guaranteed to be autoloaded
     * at configuration time; a fatal here would take down every route, not one.
     */
    private function hasStandardRenderer(): bool
    {
        static $has = null;

        if (null !== $has) {
            return $has;
        }

        $modules = [
            'ioManage',   // ISAD, the default
            'dcManage', 'radManage', 'modsManage', 'dacsManage', 'ricManage',
            'museum', 'library', 'gallery', 'dam',
        ];

        foreach ($this->configuration->getAllPluginPaths() as $path) {
            foreach ($modules as $module) {
                if (is_dir($path.'/modules/'.$module)) {
                    return $has = true;
                }
            }
        }

        return $has = false;
    }

    /**
     * Routes for the shared edit-form endpoints.
     *
     * Names and URLs are deliberately identical to the ones
     * ahgInformationObjectManagePlugin used to register, so no template changed.
     * That plugin no longer registers them; registering them in both places
     * would be a duplicate route name.
     */
    public function loadIoFormRoutes($event)
    {
        $routing = $event->getSubject();

        $routes = [
            'io_actor_autocomplete' => ['/informationobject/actorAutocomplete', 'actorAutocomplete'],
            'io_repository_autocomplete' => ['/informationobject/repositoryAutocomplete', 'repositoryAutocomplete'],
            'io_term_autocomplete' => ['/informationobject/termAutocomplete', 'termAutocomplete'],
            'io_term_create' => ['/informationobject/termCreate', 'termCreate'],
            'io_generate_identifier' => ['/informationobject/generateIdentifierJson', 'generateIdentifier'],
        ];

        // The add and edit forms are only ours to claim if something can actually
        // render them.
        //
        // ahgIoForm does not draw a form itself; it detects the record's standard
        // and forwards to the module that owns it - ioManage for ISAD, dcManage for
        // Dublin Core, and so on - each of which lives in a separate plugin. Taking
        // these two routes unconditionally meant that on an install without any of
        // those plugins, core intercepted base AtoM's perfectly good edit form and
        // replied "This descriptive standard is not available". Installing an
        // unrelated plugin such as Provenance took away description editing.
        //
        // So when no renderer is present, leave the routes alone and let base AtoM
        // serve them. The guard in ahgIoForm::executeEdit stays as a backstop for
        // the case where a renderer exists but fails partway.
        if ($this->hasStandardRenderer()) {
            $routes['io_add_override'] = ['/informationobject/add', 'edit'];

            $routing->prependRoute('io_edit_override', new sfRoute(
                '/informationobject/:slug/edit',
                ['module' => 'ahgIoForm', 'action' => 'edit']
            ));
        }

        foreach ($routes as $name => $route) {
            // Prepended: '/informationobject/:slug' would otherwise match these
            // paths as a slug, find no record, and 404 - the same collision that
            // hid /repository/autocomplete.
            $routing->prependRoute($name, new sfRoute(
                $route[0],
                ['module' => 'ahgIoForm', 'action' => $route[1]]
            ));
        }
    }

}
