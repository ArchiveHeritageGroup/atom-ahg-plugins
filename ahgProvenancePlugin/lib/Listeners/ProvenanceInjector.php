<?php

declare(strict_types=1);

namespace AhgProvenancePlugin\Listeners;

/**
 * Puts the provenance panel on an archival description page without requiring a
 * theme template override.
 *
 * Why this exists: the panel is rendered by the `provenanceDisplay` component, and
 * the only callers of it are theme templates (ahgThemeB5Plugin, ahgMuseumPlugin,
 * ahgLibraryPlugin). On a stock AtoM install with no AHG theme, provenance data can
 * be captured and stored but is never displayed. Filtering the response shows it
 * without colliding with whatever theme is installed.
 *
 * Mirrors ahgIiifPlugin's ViewerInjector, which solved the same problem for viewers.
 */
class ProvenanceInjector
{
    /**
     * A description page is NOT served by the 'informationobject' module - AtoM
     * forwards /{slug} to the module for the record's descriptive standard, so
     * checking only for 'informationobject' means this silently never fires.
     */
    private const VIEW_MODULES = [
        'informationobject',
        'sfIsadPlugin',
        'sfRadPlugin',
        'sfDcPlugin',
        'sfModsPlugin',
        'sfDacsPlugin',
    ];

    /** response.filter_content can fire more than once per request. */
    private static bool $injected = false;

    public static function filter(\sfEvent $event, $content)
    {
        try {
            $self = new self();

            // Provenance module pages: build the left block they have no layout for.
            $content = $self->injectLeftBlock($event, $content);

            // Record pages: the provenance panel.
            return $self->inject($event, $content);
        } catch (\Throwable $e) {
            // A display panel is an enhancement. It must never take a record page down.
            return $content;
        }
    }

    /**
     * When an AHG theme is present its templates already call the component, so
     * injecting here would render the panel twice.
     */
    private function themeProvidesPanel(): bool
    {
        return is_dir((string) \sfConfig::get('sf_plugins_dir') . '/ahgThemeB5Plugin');
    }

    private function inject(\sfEvent $event, $content)
    {
        $response = $event->getSubject();

        if (self::$injected || $this->themeProvidesPanel()) {
            return $content;
        }
        if (false !== stripos((string) $content, 'ahg-provenance-panel')) {
            return $content;
        }
        if (!$this->isHtmlGet($response)) {
            return $content;
        }

        $context = \sfContext::getInstance();
        if (!in_array($context->getModuleName(), self::VIEW_MODULES, true)
            || 'index' !== $context->getActionName()) {
            return $content;
        }

        $resource = $context->getActionStack()->getLastEntry()->getActionInstance()->resource ?? null;
        if (!$resource || !isset($resource->id)) {
            return $content;
        }

        // Render nothing at all when the record has no provenance: an empty
        // "Provenance" heading on every description is worse than no heading.
        $service = new \AhgProvenancePlugin\Service\ProvenanceService();
        $provenance = $service->getProvenanceForObject((int) $resource->id, $this->culture());
        if (empty($provenance['exists'])) {
            return $content;
        }

        $panel = $this->render((int) $resource->id);
        if ('' === $panel) {
            return $content;
        }

        self::$injected = true;

        return $this->place($content, $panel);
    }


    /**
     * Build a left block on pages that render single-column.
     *
     * The provenance module's own pages use layout_1col, which has no #sidebar. A
     * template could simply ask for layout_2col, but doing it here keeps the layout
     * decision with the plugin that wants the block and leaves the templates alone.
     *
     * layout_1col gives us:
     *   <div id="wrapper"><div id="main-column" role="main"> … </div></div>
     *
     * and we rewrite it to the shape layout_2col produces:
     *   <div id="wrapper"><div class="row">
     *     <div id="sidebar" class="col-md-3"> … </div>
     *     <div id="main-column" role="main" class="col-md-9"> … </div>
     *   </div></div>
     */
    private function injectLeftBlock($event, string $content): string
    {
        if (!$this->isHtmlGet($event->getSubject())) {
            return $content;
        }
        if (false !== stripos($content, 'ahg-collections-management')) {
            return $content;   // already placed on this response
        }

        // Not while editing. An edit screen is a task the user is in the middle of;
        // navigation away from it belongs in the page's own Cancel and Back controls,
        // not in a block beside the form.
        if ($this->isEditingScreen()) {
            return $content;
        }

        $block = $this->collectionsManagementBlock();
        if ('' === $block) {
            return $content;   // nothing to show (no slug on this page)
        }

        // Where a left block already exists - the treeview on a description page -
        // add to it rather than building a second one beside it.
        $existing = stripos($content, '<div id="sidebar"');
        if (false !== $existing) {
            $openEnd = strpos($content, '>', $existing);
            if (false === $openEnd) {
                return $content;
            }
            $closeAt = $this->matchingClose($content, $openEnd + 1);

            return null === $closeAt ? $content : substr_replace($content, $block, $closeAt, 0);
        }

        $at = stripos($content, '<div id="main-column"');
        if (false === $at) {
            return $content;
        }
        $tagEnd = strpos($content, '>', $at);
        if (false === $tagEnd) {
            return $content;
        }
        $close = $this->matchingClose($content, $tagEnd + 1);
        if (null === $close) {
            return $content;
        }

        $openTag = substr($content, $at, $tagEnd - $at + 1);
        $inner = substr($content, $tagEnd + 1, $close - ($tagEnd + 1));

        // main-column carries no width class in layout_1col; it needs one to sit
        // beside the sidebar rather than under it.
        $openTag = false === stripos($openTag, 'class=')
            ? substr($openTag, 0, -1).' class="col-md-9">'
            : preg_replace('/class="/i', 'class="col-md-9 ', $openTag, 1);

        $sidebar = '<div id="sidebar" class="col-md-3">'.$block.'</div>';

        $replacement = '<div class="row">'.$sidebar.$openTag.$inner.'</div>'.'</div>';

        return substr_replace($content, $replacement, $at, ($close + strlen('</div>')) - $at);
    }

    /**
     * Is a plugin enabled for this request?
     *
     * getPlugins() is the list Symfony actually loaded, which is what decides whether
     * a module's routes and actions exist. Testing for the directory instead would
     * offer links into a plugin that is present but switched off.
     */
    private function pluginEnabled(string $name): bool
    {
        return in_array($name, \sfContext::getInstance()->getConfiguration()->getPlugins(), true);
    }

    /**
     * Is the current request an edit/create screen?
     *
     * Matched on the action rather than a list of modules, so a new editing action in
     * any module is covered without this needing to know about it.
     */
    private function isEditingScreen(): bool
    {
        $action = strtolower((string) \sfContext::getInstance()->getActionName());

        foreach (['edit', 'add', 'create', 'update', 'new'] as $verb) {
            if ($action === $verb || 0 === strpos($action, $verb)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Does this slug belong to an archival description?
     *
     * Joined to information_object so a static page, a term, an actor or a repository
     * slug all answer no - only descriptions carry the procedures these links target.
     */
    private function isInformationObjectSlug(string $slug): bool
    {
        try {
            return \Illuminate\Database\Capsule\Manager::table('slug as s')
                ->join('information_object as io', 'io.id', '=', 's.object_id')
                ->where('s.slug', $slug)
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Does this record have any condition photos?
     *
     * Joined through spectrum_condition_check because photos hang off the check, not
     * off the record. Counted rather than fetched - only the existence matters here.
     * Any failure (tables absent because the plugin was never installed, an unknown
     * slug) answers no, so the link is omitted rather than the block lost.
     */
    private function hasConditionPhotos(string $slug): bool
    {
        try {
            return \Illuminate\Database\Capsule\Manager::table('spectrum_condition_photo as p')
                ->join('spectrum_condition_check as c', 'p.condition_check_id', '=', 'c.id')
                ->join('slug as s', 's.object_id', '=', 'c.object_id')
                ->where('s.slug', $slug)
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }


    /**
     * Does this record have provenance captured?
     *
     * A provenance_record row is what the view action reads; without one it
     * renders nothing. Any failure answers no, so the link falls back to the form
     * rather than the block being lost.
     */
    private function hasProvenance(string $slug): bool
    {
        try {
            return \Illuminate\Database\Capsule\Manager::table('provenance_record as p')
                ->join('slug as s', 's.object_id', '=', 'p.information_object_id')
                ->where('s.slug', $slug)
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * The record's id, but only when it has a digital object to work on.
     *
     * Returns null on any failure so the caller omits one link rather than losing
     * the block.
     */
    private function objectIdWithDigitalObject(string $slug): ?int
    {
        try {
            $id = \Illuminate\Database\Capsule\Manager::table('slug as s')
                ->join('information_object as io', 'io.id', '=', 's.object_id')
                ->join('digital_object as do', 'do.object_id', '=', 'io.id')
                ->where('s.slug', $slug)
                ->value('io.id');
        } catch (\Throwable $e) {
            return null;
        }

        return $id ? (int) $id : null;
    }

    /**
     * The Collections Management block: heading plus its links.
     *
     * Returns '' when there is nothing to show, so callers can leave the page alone
     * rather than adding an empty heading.
     */
    private function collectionsManagementBlock(): string
    {
        $context = \sfContext::getInstance();
        $context->getConfiguration()->loadHelpers(['I18N', 'Url']);

        $slug = $context->getRequest()->getParameter('slug');
        if (!$slug) {
            return '';   // not a record-scoped page: the links have nothing to point at
        }

        // A slug alone is not enough. Static pages carry one too - including the
        // homepage - so testing only for its presence put this block on pages that
        // have no record behind them. Every link here is record-scoped, so the slug
        // must actually resolve to an information object.
        if (!$this->isInformationObjectSlug($slug)) {
            return '';
        }

        // The block's entries. Add a link by adding a row here - nothing below this
        // needs to change.
        //
        // 'icon' is a Font Awesome class. Not Bootstrap Icons: the theme bundle ships
        // Font Awesome and no bootstrap-icons font, so a bi-* class renders as an
        // invisible glyph with nothing to indicate why. 'colour' is optional and
        // omitted by default so the icon inherits the theme's text colour rather than
        // imposing one - $primary is orange in arDominionB5Plugin.
        //
        // Gated on the same test as the page's own Manage button, so an anonymous
        // visitor is never shown a link that would 403.
        $candidates = [];
        if ($context->getUser()->isAuthenticated()) {
            // The view when there is provenance to look at, the form when there is
            // not. Always sending an archivist to /edit meant opening a populated
            // record in a form rather than reading it, and always sending them to
            // the view meant landing on an empty page with no way in.
            $candidates[] = [
                'label' => __('Provenance'),
                'route' => $this->hasProvenance($slug)
                    ? ['module' => 'provenance', 'action' => 'view', 'slug' => $slug]
                    : ['module' => 'provenance', 'action' => 'edit', 'slug' => $slug],
                'icon' => 'fas fa-history',
            ];

            // Only when the plugin is actually enabled - a directory on disk is not
            // the same thing, and a link to a module that isn't loaded 404s.
            if ($this->pluginEnabled('ahgSpectrumPlugin')) {
                $candidates[] = [
                    'label' => __('Collections Procedures'),
                    'route' => ['module' => 'spectrum', 'action' => 'index', 'slug' => $slug],
                    'icon' => 'fas fa-tasks',
                ];

                // Condition photos only when this record actually has some, so the
                // block does not offer a link to an empty page.
                if ($this->hasConditionPhotos($slug)) {
                    $candidates[] = [
                        'label' => __('Condition Photos'),
                        'route' => ['module' => 'spectrum', 'action' => 'conditionPhotos', 'slug' => $slug],
                        'icon' => 'fas fa-camera',
                    ];
                }
            }

            if ($this->pluginEnabled('ahgPreservationPlugin')) {
                $candidates[] = [
                    'label' => __('Preservation'),
                    'route' => ['module' => 'preservation', 'action' => 'packagesBySlug', 'slug' => $slug],
                    'icon' => 'fas fa-box-archive',
                ];
            }

            if ($this->pluginEnabled('ahgHeritageAccountingPlugin')) {
                $candidates[] = [
                    'label' => __('Heritage Accounting'),
                    'route' => ['module' => 'heritageAccounting', 'action' => 'viewByObject', 'slug' => $slug],
                    'icon' => 'fas fa-scale-balanced',
                ];
            }

            // Redaction is keyed on the object id, not the slug: the editor loads a
            // digital object rather than a description. Offered only when the record
            // actually has one, since the editor has nothing to draw on otherwise.
            if ($this->pluginEnabled('ahgRedactionPlugin') && null !== $objectId = $this->objectIdWithDigitalObject($slug)) {
                $candidates[] = [
                    'label' => __('Visual Redaction'),
                    'route' => ['module' => 'redaction', 'action' => 'editor', 'id' => $objectId],
                    'icon' => 'fas fa-marker',
                ];
            }
        }

        // Resolve each URL in isolation. url_for() throws when a route is missing, and
        // the caller's catch would otherwise swallow the whole block - one plugin's
        // broken route silently removing every other plugin's link.
        $links = [];
        foreach ($candidates as $candidate) {
            try {
                $candidate['url'] = url_for($candidate['route']);
            } catch (\Throwable $e) {
                continue;
            }
            $links[] = $candidate;
        }

        if (!$links) {
            return '';
        }

        // Markup mirrors AtoM's own sidebar box (the treeview next to it): a bordered
        // white panel wrapping a square-cornered list group. Utility classes only, no
        // inline style attributes - AtoM's CSP has no 'unsafe-inline', so an inline
        // style is dropped by the browser and the frame silently fails to appear.
        $body = '<h2 class="h6 mb-2">'.htmlspecialchars(__('Collections Management'), ENT_QUOTES).'</h2>'
              . '<div class="list-group rounded-0">';

        foreach ($links as $link) {
            $icon = $link['icon'] ?? 'fas fa-link';
            $colour = isset($link['colour']) ? ' '.htmlspecialchars((string) $link['colour'], ENT_QUOTES) : '';

            $body .= '<a class="list-group-item list-group-item-action d-flex align-items-center" href="'
                   . htmlspecialchars((string) $link['url'], ENT_QUOTES).'">'
                   . '<i class="'.htmlspecialchars((string) $icon, ENT_QUOTES).$colour.' me-2" aria-hidden="true"></i>'
                   . htmlspecialchars((string) $link['label'], ENT_QUOTES)
                   . '</a>';
        }

        return '<div class="ahg-collections-management mb-3"><div class="p-2 bg-white border">'
             . $body.'</div></div></div>';
    }

    /**
     * Offset of the </div> that closes the <div> opened before $from.
     *
     * Counting is necessary because the sidebar contains nested divs; matching the
     * first </div> would land inside the treeview.
     */
    private function matchingClose(string $html, int $from): ?int
    {
        $depth = 1;
        $offset = $from;

        while ($depth > 0) {
            if (!preg_match('#<(/?)div\b[^>]*>#i', $html, $m, PREG_OFFSET_CAPTURE, $offset)) {
                return null;   // malformed markup: leave the page alone
            }
            $depth += '/' === $m[1][0] ? -1 : 1;
            $offset = $m[0][1] + strlen($m[0][0]);
        }

        return $offset - strlen($m[0][0]);
    }

    private function culture(): string
    {
        return (string) \sfContext::getInstance()->getUser()->getCulture();
    }

    private function isHtmlGet($response): bool
    {
        if (!$response instanceof \sfWebResponse) {
            return false;
        }
        if ('GET' !== ($_SERVER['REQUEST_METHOD'] ?? 'GET')) {
            return false;
        }

        return false !== stripos((string) $response->getContentType(), 'text/html');
    }

    /**
     * Render the same component the themes use, so there is one panel implementation
     * and not a second copy that drifts.
     */
    private function render(int $objectId): string
    {
        $context = \sfContext::getInstance();
        // I18N too: place() calls __() on the heading, and the component template
        // uses it as well.
        $context->getConfiguration()->loadHelpers(['Partial', 'I18N']);

        return (string) get_component('provenance', 'provenanceDisplay', ['objectId' => $objectId]);
    }

    /**
     * The stylesheet is emitted as a <link> rather than an inline <style>: AtoM's CSP
     * has no 'unsafe-inline', so inline styles are silently dropped by the browser.
     */
    private function place(string $content, string $panel): string
    {
        // Heading markup copied from AtoM's own section headers ("Identity area",
        // "Digital object metadata") so the panel reads as part of the page rather
        // than as something bolted on.
        $heading = '<h2 class="h5 mb-0 atom-section-header">'
                 . '<div class="d-flex p-3 border-bottom text-primary">'
                 . htmlspecialchars(__('Provenance & Chain of Custody'), ENT_QUOTES)
                 . '</div></h2>';

        $block = '<link rel="stylesheet" href="/plugins/ahgProvenancePlugin/web/css/provenance.css">'
               . '<div class="ahg-provenance-panel"><section id="provenanceArea">'
               . $heading
               . $panel
               . '</section></div>';

        // Sit directly above the digital object metadata block. The viewer anchors are
        // fallbacks for records rendered without that block; which viewer marker is
        // present depends on whether ahgIiifPlugin's ViewerInjector has already run on
        // this content, since it replaces AtoM's own block with its viewer.
        $before = [
            '<div class="digitalObjectMetadata"',
            '<link rel="stylesheet" href="/plugins/ahgSeadragonPlugin',
            '<link rel="stylesheet" href="/plugins/ahgMiradorPlugin',
            '<div class="ahg-iiif-viewer',
            '<div class="digital-object-reference',
        ];
        foreach ($before as $anchor) {
            $at = stripos($content, $anchor);
            if (false !== $at) {
                return substr_replace($content, $block . "\n", $at, 0);
            }
        }

        // No digital object on this record: fall back to the usual containers.
        foreach (['<section id="relatedMaterialArea"', '<div id="content"', '<div id="main-column"'] as $anchor) {
            $at = stripos($content, $anchor);
            if (false === $at) {
                continue;
            }
            if (0 === strpos($anchor, '<section')) {
                return substr_replace($content, $block . "\n", $at, 0);
            }
            $close = strpos($content, '>', $at);
            if (false !== $close) {
                return substr_replace($content, "\n" . $block, $close + 1, 0);
            }
        }

        $pos = strripos($content, '</body>');

        return false === $pos ? $content . $block : substr_replace($content, $block . "\n", $pos, 0);
    }
}
