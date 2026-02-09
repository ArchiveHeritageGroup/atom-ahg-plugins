<?php

class ioManageActions extends sfActions
{
    public function preExecute()
    {
        parent::preExecute();

        sfContext::getInstance()->getConfiguration()->loadHelpers(['I18N', 'Url', 'Qubit', 'Text', 'Date']);

        if (!class_exists('Illuminate\Database\Capsule\Manager')) {
            $frameworkBoot = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
            if (file_exists($frameworkBoot)) {
                require_once $frameworkBoot;
            }
        }
    }

    /**
     * Edit or create an information object.
     *
     * Detects the descriptive standard and forwards to the appropriate
     * standard plugin's module (DC, RAD, MODS, DACS). Falls through
     * to ISAD(G) template for the default standard.
     */
    public function executeEdit(sfWebRequest $request)
    {
        $culture = $this->context->user->getCulture();
        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        // ACL — require editor/admin
        $user = $this->context->user;
        if (!$user->isAuthenticated()
            || !($user->hasGroup(QubitAclGroup::ADMINISTRATOR_ID) || $user->hasGroup(QubitAclGroup::EDITOR_ID))
        ) {
            QubitAcl::forwardUnauthorized();
        }

        // Load IO data + detect standard
        $standard = \IoFormHelper::loadIoData($this, $request, $culture);

        // Forward to standard-specific module if not ISAD
        if (isset(\IoFormHelper::MODULE_MAP[$standard])) {
            $module = \IoFormHelper::MODULE_MAP[$standard];

            // Check if the target module's plugin is enabled
            try {
                $this->forward($module, 'edit');
            } catch (\sfConfigurationException $e) {
                // Plugin not installed — fall through to ISAD
            }
        }

        // ISAD(G) — load dropdowns and continue to editSuccess.php
        \IoFormHelper::loadDropdowns($this, $culture);

        // Handle POST
        if ($request->isMethod('post')) {
            \IoFormHelper::handlePost($this, $request, $culture);
        }
    }

    /**
     * Delete an information object.
     */
    public function executeDelete(sfWebRequest $request)
    {
        $this->form = new sfForm();
        $culture = $this->context->user->getCulture();

        // ACL
        $user = $this->context->user;
        if (!$user->isAuthenticated()
            || !($user->hasGroup(QubitAclGroup::ADMINISTRATOR_ID) || $user->hasGroup(QubitAclGroup::EDITOR_ID))
        ) {
            QubitAcl::forwardUnauthorized();
        }

        $slug = $request->getParameter('slug');
        $this->io = \AhgInformationObjectManage\Services\InformationObjectCrudService::getBySlug($slug, $culture);
        if (!$this->io) {
            $this->forward404();
        }

        // Check for children
        $this->hasChildren = \AhgInformationObjectManage\Services\NestedSetService::hasChildren($this->io['id']);

        if ($request->isMethod('delete')) {
            if ($this->hasChildren) {
                $this->errors = [__('Cannot delete: this description has child records. Delete or move children first.')];

                return;
            }

            $this->form->bind($request->getPostParameters());
            if ($this->form->isValid()) {
                $parentSlug = $this->io['parentSlug'];
                \AhgInformationObjectManage\Services\InformationObjectCrudService::delete($this->io['id']);

                if ($parentSlug) {
                    $this->redirect('/' . $parentSlug);
                } else {
                    $this->redirect('/');
                }
            }
        }
    }

    // ─── AJAX proxy endpoints ─────────────────────────────────────────

    /**
     * Actor autocomplete — returns JSON [{id, name}].
     */
    public function executeActorAutocomplete(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $q = trim($request->getParameter('query', ''));
        $limit = max(1, min(50, (int) $request->getParameter('limit', 10)));
        $culture = $this->context->user->getCulture();

        if (strlen($q) < 2) {
            return $this->renderText(json_encode([]));
        }

        if (!class_exists('Illuminate\Database\Capsule\Manager')) {
            $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
            if (file_exists($bootstrap)) {
                require_once $bootstrap;
            }
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
    public function executeRepositoryAutocomplete(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $q = trim($request->getParameter('query', ''));
        $limit = max(1, min(50, (int) $request->getParameter('limit', 10)));
        $culture = $this->context->user->getCulture();

        if (strlen($q) < 2) {
            return $this->renderText(json_encode([]));
        }

        if (!class_exists('Illuminate\Database\Capsule\Manager')) {
            $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
            if (file_exists($bootstrap)) {
                require_once $bootstrap;
            }
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
    public function executeTermAutocomplete(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $q = trim($request->getParameter('query', ''));
        $taxonomyId = (int) $request->getParameter('taxonomy', 0);
        $limit = max(1, min(50, (int) $request->getParameter('limit', 10)));
        $culture = $this->context->user->getCulture();

        if (strlen($q) < 2 || !$taxonomyId) {
            return $this->renderText(json_encode([]));
        }

        if (!class_exists('Illuminate\Database\Capsule\Manager')) {
            $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
            if (file_exists($bootstrap)) {
                require_once $bootstrap;
            }
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
     * Generate identifier using Archive Standard scheme {REPO}/{FONDS}/{SEQ:4}.
     *
     * Expects query params: repositoryId, parentId
     * Returns JSON {identifier, scheme}.
     */
    public function executeGenerateIdentifier(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $culture = $this->context->user->getCulture();
        $repositoryId = (int) $request->getParameter('repositoryId', 0);
        $parentId = (int) $request->getParameter('parentId', 0);

        if (!class_exists('Illuminate\Database\Capsule\Manager')) {
            $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
            if (file_exists($bootstrap)) {
                require_once $bootstrap;
            }
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
            return $this->renderText(json_encode([
                'identifier' => '',
                'error' => 'Select a repository first.',
            ]));
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
