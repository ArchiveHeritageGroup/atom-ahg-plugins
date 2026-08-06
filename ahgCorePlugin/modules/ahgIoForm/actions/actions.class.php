<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Shared edit-form endpoints for every descriptive standard.
 *
 * WHY THESE LIVE IN ahgCorePlugin
 *
 * ISAD, RAD, DACS, Dublin Core, MODS and RiC all post to the same form
 * machinery and all call these endpoints by route name. They previously lived in
 * ahgInformationObjectManagePlugin, which meant five standard plugins depended
 * on a sixth that none of them declared: each extension.json lists only
 * ahgCorePlugin. Installed on its own against stock AtoM, a standard plugin
 * would call url_for('@io_term_autocomplete'), find no such route, and fatal on
 * the first edit form opened.
 *
 * ahgCorePlugin is core, locked and always enabled, and is the dependency every
 * standard plugin already declares, so shared endpoints belong here. Route names
 * and URLs are unchanged, so no template needed editing.
 */
class ahgIoFormActions extends AhgController
{
        /**
     * Single entry point for creating and editing a description, in any standard.
     *
     * Every standard's form posts here: ISAD, RAD, DACS, Dublin Core, MODS and
     * RiC all render an action of "@io_add_override" or "@io_edit_override".
     * This detects the record's descriptive standard and forwards to the module
     * that renders it.
     *
     * It lives in ahgCorePlugin because all six need it while declaring only
     * ahgCorePlugin. Previously it sat in ahgInformationObjectManagePlugin, so
     * installing any other standard on its own gave a form that posted to a
     * route that did not exist.
     *
     * ISAD forwards to ioManage like any other standard. It used to be the
     * fall-through case that rendered in place, which cannot work from here:
     * the ISAD template belongs to ioManage.
     */
    public function executeEdit($request)
    {
        $culture = $this->culture();
        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        $user = $this->getUser();

        if (!$user->isAuthenticated()
            || !($user->hasGroup(\AtomExtensions\Constants\AclConstants::ADMINISTRATOR_ID)
                 || $user->hasGroup(\AtomExtensions\Constants\AclConstants::EDITOR_ID))
        ) {
            \AtomExtensions\Services\AclService::forwardUnauthorized();
        }

        $standard = \IoFormHelper::loadIoData($this, $request, $culture);

        // ISAD has no MODULE_MAP entry because it was historically the
        // fall-through; it is ioManage's standard.
        $module = \IoFormHelper::MODULE_MAP[$standard] ?? 'ioManage';

        try {
            $this->forward($module, 'edit');
        } catch (\sfConfigurationException $e) {
            // The plugin that renders this standard is not installed. Say so,
            // rather than fatalling or silently rendering the wrong form.
            $this->getResponse()->setStatusCode(501);

            return $this->renderText(sprintf(
                '<h1>%s</h1><p>%s</p>',
                __('This descriptive standard is not available'),
                __('The plugin that renders "%1%" descriptions is not installed on this site.', ['%1%' => $standard])
            ));
        }
    }

/**
     * Actor autocomplete — returns JSON [{id, name}].
     */
    public function executeActorAutocomplete($request)
    {
        $this->getResponse()->setContentType('application/json');

        $q = trim($request->getParameter('query', ''));
        $limit = max(1, min(50, (int) $request->getParameter('limit', 10)));
        $culture = $this->culture();

        if (strlen($q) < 2) {
            return $this->renderText(json_encode([]));
        }

        $results = \Illuminate\Database\Capsule\Manager::table('actor')
            ->join('actor_i18n', function ($j) use ($culture) {
                $j->on('actor.id', '=', 'actor_i18n.id')
                    ->where('actor_i18n.culture', '=', $culture);
            })
            ->where('actor_i18n.authorized_form_of_name', 'LIKE', '%' . $q . '%')
            ->where('actor.id', '!=', \QubitActor::ROOT_ID)
            ->select('actor.id', 'actor_i18n.authorized_form_of_name as name')
            ->orderBy('actor_i18n.authorized_form_of_name')
            ->limit($limit)
            ->get()
            ->all();

        $json = array_map(function ($r) {
            return ['id' => (int) $r->id, 'name' => $r->name];
        }, $results);

        return $this->renderText(json_encode($json));
    }

    /**
     * Repository autocomplete — returns JSON [{id, name}].
     */
    public function executeRepositoryAutocomplete($request)
    {
        $this->getResponse()->setContentType('application/json');

        $q = trim($request->getParameter('query', ''));
        $limit = max(1, min(50, (int) $request->getParameter('limit', 10)));
        $culture = $this->culture();

        if (strlen($q) < 2) {
            return $this->renderText(json_encode([]));
        }

        $results = \Illuminate\Database\Capsule\Manager::table('repository')
            ->join('actor_i18n', function ($j) use ($culture) {
                $j->on('repository.id', '=', 'actor_i18n.id')
                    ->where('actor_i18n.culture', '=', $culture);
            })
            ->where('actor_i18n.authorized_form_of_name', 'LIKE', '%' . $q . '%')
            ->select('repository.id', 'actor_i18n.authorized_form_of_name as name')
            ->orderBy('actor_i18n.authorized_form_of_name')
            ->limit($limit)
            ->get()
            ->all();

        $json = array_map(function ($r) {
            return ['id' => (int) $r->id, 'name' => $r->name];
        }, $results);

        return $this->renderText(json_encode($json));
    }

    /**
     * Term autocomplete — returns JSON [{id, name}].
     * Requires ?taxonomy=ID&query=text
     */
    public function executeTermAutocomplete($request)
    {
        $this->getResponse()->setContentType('application/json');

        $q = trim($request->getParameter('query', ''));
        $taxonomyId = (int) $request->getParameter('taxonomy', 0);
        $limit = max(1, min(50, (int) $request->getParameter('limit', 10)));
        $culture = $this->culture();

        if (strlen($q) < 2 || !$taxonomyId) {
            return $this->renderText(json_encode([]));
        }

        $results = \Illuminate\Database\Capsule\Manager::table('term')
            ->join('term_i18n', function ($j) use ($culture) {
                $j->on('term.id', '=', 'term_i18n.id')
                    ->where('term_i18n.culture', '=', $culture);
            })
            ->where('term.taxonomy_id', $taxonomyId)
            ->where('term_i18n.name', 'LIKE', '%' . $q . '%')
            ->select('term.id', 'term_i18n.name')
            ->orderBy('term_i18n.name')
            ->limit($limit)
            ->get()
            ->all();

        $json = array_map(function ($r) {
            return ['id' => (int) $r->id, 'name' => $r->name];
        }, $results);

        return $this->renderText(json_encode($json));
    }

    /**
     * Create a new access-point term, for editors and administrators only.
     *
     * WHY THIS IS GATED
     *
     * Access points exist so that a concept has one name. If anyone who can edit
     * a description can also invent vocabulary by typing, a collection ends up
     * with "Pretoria", "pretoria", "Pretoria, Gauteng" and "PTA" as four separate
     * place terms, and browsing by place stops meaning anything. That is the
     * exact problem controlled vocabulary is there to prevent.
     *
     * So contributors get lookup and no more; editors and administrators, who are
     * accountable for the vocabulary, can add to it. This mirrors the publication
     * split already in place: a contributor drafts, an editor commits.
     *
     * Restricted to the three access-point taxonomies. Without that, this
     * endpoint would be a way to inject terms into levels of description,
     * publication status or any other controlled list in the system.
     *
     * Deduplicates case-insensitively and returns the existing term rather than
     * erroring, so two people adding "Rock art" at the same time converge on one
     * term instead of creating two.
     */
    public function executeTermCreate($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$request->isMethod('post')) {
            $this->getResponse()->setStatusCode(405);

            return $this->renderText(json_encode(['error' => 'POST required']));
        }

        $user = $this->context->user;

        if (!$user->isAuthenticated()
            || (!$user->hasCredential('administrator') && !$user->hasCredential('editor'))) {
            $this->getResponse()->setStatusCode(403);

            return $this->renderText(json_encode(['error' => 'Not permitted to create terms']));
        }

        $taxonomyId = (int) $request->getParameter('taxonomy', 0);
        $name = trim((string) $request->getParameter('name', ''));

        // Subject, place, genre. Nothing else.
        if (!in_array($taxonomyId, [35, 42, 78], true)) {
            $this->getResponse()->setStatusCode(400);

            return $this->renderText(json_encode(['error' => 'Taxonomy not open to term creation']));
        }

        if ('' === $name || mb_strlen($name) > 255) {
            $this->getResponse()->setStatusCode(400);

            return $this->renderText(json_encode(['error' => 'A name between 1 and 255 characters is required']));
        }

        $culture = $this->culture();

        // Already there under any casing: hand back what exists.
        $existing = \Illuminate\Database\Capsule\Manager::table('term')
            ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
            ->where('term.taxonomy_id', $taxonomyId)
            ->whereRaw('LOWER(term_i18n.name) = ?', [mb_strtolower($name)])
            ->select('term.id', 'term_i18n.name')
            ->first();

        if ($existing) {
            return $this->renderText(json_encode([
                'id' => (int) $existing->id,
                'name' => $existing->name,
                'created' => false,
            ]));
        }

        try {
            // Propel, not a raw insert: a term needs its base object row, its
            // slug and its i18n row, and QubitTerm is what knows to create all
            // three. A direct insert fails under STRICT mode.
            $term = new \QubitTerm();
            $term->taxonomyId = $taxonomyId;
            $term->setName($name, ['culture' => $culture]);
            $term->save();
        } catch (\Exception $e) {
            $this->getResponse()->setStatusCode(500);

            return $this->renderText(json_encode(['error' => 'Could not create the term']));
        }

        return $this->renderText(json_encode([
            'id' => (int) $term->id,
            'name' => $name,
            'created' => true,
        ]));
    }

    /**
     * Generate identifier using Archive Standard scheme {REPO}/{FONDS}/{SEQ:4}.
     *
     * Expects query params: repositoryId, parentId
     * Returns JSON {identifier, scheme}.
     */
    public function executeGenerateIdentifier($request)
    {
        $this->getResponse()->setContentType('application/json');

        $culture = $this->culture();
        $repositoryId = (int) $request->getParameter('repositoryId', 0);
        $parentId = (int) $request->getParameter('parentId', 0);
        $sector = $request->getParameter('sector', 'archive');

        // Try sector-aware NumberingService first. Guard the require so we don't
        // re-include an already-autoloaded class (the unconditional require_once
        // collided -> "Cannot declare class ... already in use"); the class lives
        // under AtomExtensions\Services (NOT AtomFramework\); catch Throwable so any
        // failure degrades to the legacy logic instead of a 500.
        try {
            if (!class_exists('AtomExtensions\\Services\\NumberingService', false)) {
                require_once \sfConfig::get('sf_root_dir') . '/atom-framework/src/Services/NumberingService.php';
            }
            $service = \AtomExtensions\Services\NumberingService::getInstance();
            $identifier = $service->getNextReference($sector, [], $repositoryId ?: null);
            if (!empty($identifier)) {
                return $this->renderText(json_encode(['identifier' => $identifier]));
            }
        } catch (\Throwable $e) {
            // Fall through to legacy logic
        }

        $DB = \Illuminate\Database\Capsule\Manager::class;
        $rootId = \AhgInformationObjectManage\Services\InformationObjectCrudService::ROOT_ID;

        // 1. Resolve REPO code
        $repoCode = '';
        if ($repositoryId) {
            $repo = $DB::table('repository')
                ->where('id', $repositoryId)
                ->value('identifier');

            if (!$repo) {
                // Fallback: abbreviate the repository name
                $name = $DB::table('actor_i18n')
                    ->where('id', $repositoryId)
                    ->where('culture', $culture)
                    ->value('authorized_form_of_name');

                if ($name) {
                    // Use uppercase initials of each word, max 6 chars
                    $words = preg_split('/\s+/', trim($name));
                    $repo = '';
                    foreach ($words as $w) {
                        $repo .= strtoupper(mb_substr($w, 0, 1));
                    }
                    $repo = substr($repo, 0, 6);
                }
            }
            $repoCode = $repo ?: 'REPO';
        }

        if (!$repoCode) {
            // Repository is optional — fall back to a placeholder code rather than
            // forcing the user to select a repository before generating.
            $repoCode = 'REPO';
        }

        // 2. Resolve FONDS — walk up parent chain to find fonds-level ancestor
        $fondsCode = '';
        $effectiveParent = $parentId ?: $rootId;

        if ($effectiveParent && $effectiveParent != $rootId) {
            // Walk up from parent to find the fonds (child of root)
            $currentId = $effectiveParent;
            $visited = [];
            while ($currentId && $currentId != $rootId && !isset($visited[$currentId])) {
                $visited[$currentId] = true;
                $row = $DB::table('information_object')
                    ->where('id', $currentId)
                    ->select('identifier', 'parent_id')
                    ->first();

                if (!$row) {
                    break;
                }

                if ((int) $row->parent_id === $rootId) {
                    // This is the fonds-level ancestor
                    $fondsCode = $row->identifier ?: '';
                    break;
                }
                $currentId = (int) $row->parent_id;
            }
        }

        // 3. Sequence — count existing children of target parent + 1
        $childCount = $DB::table('information_object')
            ->where('parent_id', $effectiveParent)
            ->count();
        $seq = str_pad((string) ($childCount + 1), 4, '0', STR_PAD_LEFT);

        // 4. Build identifier
        if ($fondsCode) {
            $identifier = $repoCode . '/' . $fondsCode . '/' . $seq;
        } else {
            // Creating at fonds level (parent is root) — no fonds component
            $identifier = $repoCode . '/' . $seq;
        }

        return $this->renderText(json_encode(['identifier' => $identifier]));
    }

}
