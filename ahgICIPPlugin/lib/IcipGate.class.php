<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Applies IcipPolicy to requests, from Symfony events, so no base or sector
 * template changes. A refused record or file gets the ordinary 404, the same
 * response as one that does not exist.
 */
class IcipGate
{
    public const SESSION_ACKS = 'icip_acknowledged_notices';

    /** Their own flows, or (apiv2) a caller who is signed in only inside the action. */
    private const EXEMPT_MODULES = ['icip', 'accessFilter', 'securityClearance', 'securityAudit', 'default', 'user', 'apiv2'];

    /** Pages that show a description, one per sector and descriptive standard. */
    private const VIEW_MODULES = [
        'informationobject', 'sfIsadPlugin', 'sfRadPlugin', 'sfDcPlugin', 'sfModsPlugin', 'sfDacsPlugin',
        'museum', 'library', 'gallery', 'dam',
    ];

    /** IIIF actions addressed by a numeric record id rather than a slug. */
    private const IIIF_ID_ACTIONS = ['manifestById', 'viewer', 'ocrExport', 'aiCanvases', 'aiExtractList', 'aiExtractReview'];

    public static function onChangeAction(sfEvent $event): void
    {
        $params = $event->getParameters();
        $module = $params['module'] ?? '';
        if (in_array($module, self::EXEMPT_MODULES, true)) {
            return;
        }

        // The 404 page keeps the slug of the request that failed. Refusing it again
        // from inside its own render discards the page and sends a blank 200, which
        // would mark this record as different from one that does not exist.
        if (class_exists('sfConfig', false)
            && sfConfig::get('sf_error_404_module') === $module
            && sfConfig::get('sf_error_404_action') === ($params['action'] ?? '')) {
            return;
        }

        $context = sfContext::getInstance();
        $request = $context->getRequest();

        if ('digitalobject' === $module && 'view' === ($params['action'] ?? '')) {
            self::gateFile($context);

            return;
        }

        if ('iiifAuth' === $module && 'cantaloupeCheck' === ($params['action'] ?? '')) {
            self::gateImageServer($context);

            return;
        }

        if ('iiif' === $module && in_array($params['action'] ?? '', self::IIIF_ID_ACTIONS, true)) {
            self::gateIiifById($context);

            return;
        }

        $slug = (string) $request->getParameter('slug', '');
        if ('' === $slug || $request->getAttribute('ahg_icip_checked')) {
            return;
        }
        $request->setAttribute('ahg_icip_checked', true);

        try {
            $objectId = (int) DB::table('slug as s')
                ->join('information_object as io', 'io.id', '=', 's.object_id')
                ->where('s.slug', $slug)
                ->value('io.id');
        } catch (\Throwable $e) {
            error_log('icip.gate.lookup_failed: '.$e->getMessage());

            throw new sfError404Exception('Not found');
        }
        if ($objectId <= 1) {
            return; // not a description
        }

        $result = IcipPolicy::explain($objectId, self::userId($context), self::sessionAcks($context));
        self::audit($objectId, self::userId($context), $result);

        if (IcipPolicy::DENY === $result['decision']) {
            $request->setAttribute('ahg_icip_refused_slug', $slug);
            // Page furniture reads these to decorate a record's page; a missing record has neither.
            $request->getParameterHolder()->remove('template');
            $request->getParameterHolder()->remove('slug');

            throw new sfError404Exception('Not found');
        }

        // A IIIF manifest or content search is the media itself.
        if ('iiif' === $module && IcipPolicy::ALLOW !== $result['decision']) {
            throw new sfError404Exception('Not found');
        }

        if ([] !== $result['pending']) {
            $request->setAttribute('ahg_icip_pending', $result['pending']);
            $context->getController()->forward('icip', 'notice');

            throw new sfStopException();
        }

        $request->setAttribute('ahg_icip_result', $result + ['object_id' => $objectId]);
    }

    /**
     * digitalobject/view is where nginx sends masters, and derivatives the
     * fast gate would not grant. Only an outright allow gets the file.
     */
    private static function gateFile(sfContext $context): void
    {
        $request = $context->getRequest();

        try {
            // Normalised so /index.php/uploads/... and doubled slashes resolve to the stored path.
            $path = rawurldecode((string) parse_url($request->getUri(), PHP_URL_PATH));
            $path = preg_replace('#/+#', '/', preg_replace('#^/index\.php(?=/)#', '', $path));
            $slash = strrpos($path, '/');
            if (false === $slash) {
                return;
            }

            $file = DB::table('digital_object as d')
                ->leftJoin('digital_object as master', 'master.id', '=', 'd.parent_id')
                ->where('d.path', substr($path, 0, $slash + 1))
                ->where('d.name', substr($path, $slash + 1))
                ->first(['d.object_id', 'master.object_id as master_object_id']);
            $objectId = (int) ($file->object_id ?? 0) ?: (int) ($file->master_object_id ?? 0);
            if ($objectId <= 1) {
                return; // not a description's file
            }

            $result = IcipPolicy::explain($objectId, self::userId($context), self::sessionAcks($context));
            self::audit($objectId, self::userId($context), $result);
            $allowed = IcipPolicy::ALLOW === $result['decision'];
        } catch (\Throwable $e) {
            error_log('icip.gate.file_failed: '.$e->getMessage());
            $allowed = false;
        }

        if (!$allowed) {
            throw new sfError404Exception('File not found');
        }
    }

    private static function gateIiifById(sfContext $context): void
    {
        try {
            $id = (int) $context->getRequest()->getParameter('id');
            // The viewer accepts a digital object id as well as a description id.
            if ($id > 0 && !DB::table('information_object')->where('id', $id)->exists()) {
                $id = (int) DB::table('digital_object')->where('id', $id)->value('object_id');
            }
            if ($id <= 1) {
                return; // the action answers for an unknown id
            }

            $result = IcipPolicy::explain($id, self::userId($context), self::sessionAcks($context));
            self::audit($id, self::userId($context), $result);
            $allowed = IcipPolicy::ALLOW === $result['decision'];
        } catch (\Throwable $e) {
            error_log('icip.gate.iiif_failed: '.$e->getMessage());
            $allowed = false;
        }

        if (!$allowed) {
            throw new sfError404Exception('Not found');
        }
    }

    /**
     * Cantaloupe asks this action before serving a tile. It carries no session,
     * so the answer is the anonymous one, unless a signed master grant shows
     * which member of staff opened the manifest. Unknown files are left to the
     * action, which refuses them.
     */
    private static function gateImageServer(sfContext $context): void
    {
        $request = $context->getRequest();

        try {
            $path = str_replace('_SL_', '/', rawurldecode((string) $request->getParameter('identifier', '')));
            $path = '/'.ltrim((string) preg_replace('/;[0-9]+$/', '', $path), '/');
            $slash = strrpos($path, '/');
            $file = DB::table('digital_object as d')
                ->leftJoin('digital_object as master', 'master.id', '=', 'd.parent_id')
                ->where('d.path', substr($path, 0, $slash + 1))
                ->where('d.name', substr($path, $slash + 1))
                ->first(['d.object_id', 'master.object_id as master_object_id']);
            $objectId = (int) ($file->object_id ?? 0) ?: (int) ($file->master_object_id ?? 0);
            if ($objectId <= 1) {
                return;
            }

            // An acknowledgement cannot be seen from here, and is not a hard control
            // (blocks_access is): tiles of an acknowledge-only record stay available.
            $anonymous = IcipPolicy::decide($objectId);
            $allowed = IcipPolicy::ALLOW === $anonymous || IcipPolicy::ACKNOWLEDGE === $anonymous;
            $authService = dirname(__DIR__, 2).'/ahgIiifPlugin/lib/Services/IiifAuthService.php';
            if (!$allowed && !class_exists('IiifAuthService', false) && is_file($authService)) {
                require_once $authService;
            }
            if (!$allowed && class_exists('IiifAuthService', false)) {
                foreach (array_filter(explode(',', (string) $request->getParameter('grants', ''))) as $grant) {
                    if (\IiifAuthService::masterGranted($objectId, [$grant])
                        && IcipPolicy::ALLOW === IcipPolicy::decide($objectId, (int) strtok(trim($grant), '.'))) {
                        $allowed = true;

                        break;
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('icip.gate.image_failed: '.$e->getMessage());
            $allowed = false;
        }

        if (!$allowed) {
            $context->getController()->forward('icip', 'imageDenied');

            throw new sfStopException();
        }
    }

    /**
     * Notice banners and TK/BC labels on the five sector view pages, and for a
     * metadata-only result, the media taken out of the page. If media cannot
     * be withheld the page is not sent.
     */
    public static function filterContent(sfEvent $event, $content)
    {
        $context = sfContext::getInstance();
        $response = $event->getSubject();

        $refused = (string) $context->getRequest()->getAttribute('ahg_icip_refused_slug', '');
        if ('' !== $refused && false !== stripos((string) $response->getContentType(), 'text/html')) {
            return self::asMissingPage((string) $content, (string) $context->getRequest()->getPathInfoPrefix().(string) $context->getRequest()->getPathInfo());
        }

        $result = $context->getRequest()->getAttribute('ahg_icip_result');
        if (!$result || !self::isViewPage($context) || false === stripos((string) $response->getContentType(), 'text/html')) {
            return $content;
        }

        $metadataOnly = IcipPolicy::METADATA_ONLY === $result['decision'];

        try {
            $html = (string) $content;
            if ($metadataOnly) {
                $html = self::withholdMedia($html, (int) $result['object_id']);
            }

            return self::place($html, self::banner($result, ahgICIPService::getObjectTKLabels((int) $result['object_id'])));
        } catch (\Throwable $e) {
            error_log('icip.banner_failed: '.$e->getMessage());
            if ($metadataOnly) {
                $response->setStatusCode(404);

                return '';
            }

            return $content;
        }
    }

    /**
     * The 404 page is rendered with the route of the record that was refused, so
     * its search box offers that record's repository and its language links carry
     * the sector route. A slug that does not exist has neither.
     */
    public static function asMissingPage(string $html, string $path): string
    {
        $html = preg_replace('#[ \t]*<div class="form-check">\s*<input\b[^>]*\bid="search-realm-repo"[^>]*>.*?</div>\s*?\n?#is', '', $html) ?? $html;

        $path = htmlspecialchars($path, ENT_QUOTES, 'UTF-8');

        return preg_replace_callback(
            '#(<a\b[^>]*\bhref=")[^"?]*\?sf_culture=([A-Za-z_]+)(?:&amp;[^"]*)?(")#',
            static fn ($m) => $m[1].$path.'?sf_culture='.$m[2].$m[3],
            $html
        ) ?? $html;
    }

    private static function isViewPage(sfContext $context): bool
    {
        return 'index' === $context->getActionName() && in_array($context->getModuleName(), self::VIEW_MODULES, true);
    }

    /** Removes media tags for this record's files and blanks any other mention of their path. */
    public static function withholdMedia(string $html, int $objectId): string
    {
        $paths = DB::table('digital_object as d')
            ->leftJoin('digital_object as master', 'master.id', '=', 'd.parent_id')
            ->where('d.object_id', $objectId)
            ->orWhere('master.object_id', $objectId)
            ->distinct()
            ->pluck('d.path')->filter()->all();

        foreach ($paths as $path) {
            $bare = ltrim((string) $path, '/');
            // As written in attributes, URL-encoded, JSON-escaped, and as a Cantaloupe identifier.
            foreach ([$path, $bare, rawurlencode($path), rawurlencode($bare), str_replace('/', '\\/', $path), str_replace('/', '_SL_', $bare)] as $form) {
                $html = preg_replace(
                    '#<(?:img|source|video|audio|track|iframe|embed|object|model-viewer)\b[^>]*'.preg_quote($form, '#').'[^>]*>#i',
                    '',
                    $html
                ) ?? '';
                $html = str_replace($form, '#withheld/', $html);
            }
        }

        return $html;
    }

    public static function banner(array $result, array $labels): string
    {
        $e = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $out = '';

        if (IcipPolicy::METADATA_ONLY === $result['decision']) {
            $out .= '<div class="alert alert-warning" role="alert">'.$e(self::t('The digital content of this record is withheld under a cultural protocol.')).'</div>';
        }
        foreach ($result['visible'] as $notice) {
            $severity = strtolower((string) $notice->severity);
            $class = 'critical' === $severity ? 'danger' : ('warning' === $severity ? 'warning' : 'info');
            $out .= '<div class="alert alert-'.$class.'" role="alert"><strong>'.$e($notice->name).'</strong>'
                .'<div class="small">'.$e($notice->custom_text ?: $notice->default_text).'</div></div>';
        }
        if ($labels) {
            $out .= '<div class="mb-3" aria-label="'.$e(self::t('Traditional knowledge and biocultural labels')).'">';
            foreach ($labels as $label) {
                $name = $e($label->label_name);
                $url = (string) $label->local_contexts_url;
                $out .= preg_match('#^https?://#i', $url)
                    ? '<a class="badge bg-secondary me-1 text-decoration-none" href="'.$e($url).'" rel="noopener" target="_blank">'.$name.'</a>'
                    : '<span class="badge bg-secondary me-1">'.$name.'</span>';
            }
            $out .= '</div>';
        }

        return '' === $out ? '' : '<div class="icip-record-notices">'.$out.'</div>';
    }

    /** The I18N helper is not loaded this late on every request. */
    private static function t(string $text): string
    {
        return sfContext::hasInstance() ? sfContext::getInstance()->getI18N()->__($text) : $text;
    }

    private static function place(string $html, string $block): string
    {
        if ('' === $block) {
            return $html;
        }
        if (preg_match('#<div\b[^>]*\bid="main-column"[^>]*>#i', $html, $m, PREG_OFFSET_CAPTURE)) {
            return substr_replace($html, $block, $m[0][1] + strlen($m[0][0]), 0);
        }
        if (preg_match('#<body\b[^>]*>#i', $html, $m, PREG_OFFSET_CAPTURE)) {
            return substr_replace($html, $block, $m[0][1] + strlen($m[0][0]), 0);
        }

        return $block.$html;
    }

    private static function userId(sfContext $context): ?int
    {
        $id = $context->getUser()->getAttribute('user_id');

        return $id ? (int) $id : null;
    }

    private static function sessionAcks(sfContext $context): array
    {
        return (array) $context->getUser()->getAttribute(self::SESSION_ACKS, []);
    }

    /** After the response, so an audit write can never hold up or fail the request. */
    private static function audit(int $objectId, ?int $userId, array $result): void
    {
        if (!$result['governed']) {
            return;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        register_shutdown_function(static function () use ($objectId, $userId, $result, $ip) {
            try {
                if (!$result['admin'] && '1' !== (string) ahgICIPService::getConfig('audit_all_icip_access', '0')) {
                    return;
                }
                DB::table('icip_access_log')->insert([
                    'information_object_id' => $objectId,
                    'user_id' => $userId,
                    'access_allowed' => IcipPolicy::DENY === $result['decision'] ? 0 : 1,
                    'blocked_reason' => $result['admin'] ? 'administrator access' : $result['decision'],
                    'ip_address' => $ip,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {
                error_log('icip.audit_failed: '.$e->getMessage());
            }
        });
    }
}
