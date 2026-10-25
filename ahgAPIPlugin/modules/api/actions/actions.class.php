<?php

use AtomFramework\Http\Controllers\AhgController;
class apiActions extends AhgController
{
    /**
     * API keys page: a signed-in user lists, creates and revokes their own REST API v2
     * keys. Before this page a key could only be issued by a raw POST /api/v2/keys
     * carrying the session's CSRF token, which no ordinary user can make.
     */
    public function executeKeys($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }
        require_once \sfConfig::get('sf_plugins_dir').'/ahgAPIPlugin/lib/Services/ApiKeyService.php';
        $userId = (int) $this->getUser()->getAttribute('user_id');
        $this->scopes = ['read' => 'Read', 'write' => 'Create and update', 'delete' => 'Delete', 'batch' => 'Batch'];
        $this->newKey = null;
        $this->error = null;

        $db = \Illuminate\Database\Capsule\Manager::class;
        $this->isAdmin = $this->getUser()->isAdministrator();
        $hasIssued = $db::schema()->hasColumn('ahg_api_key', 'issued_by');

        if ($request->isMethod('post')) {
            if ('revoke' === $request->getParameter('form_action')) {
                // Your own keys, or (administrators) keys you issued to someone else.
                $db::table('ahg_api_key')->where('id', (int) $request->getParameter('key_id'))
                    ->where(function ($q) use ($userId, $hasIssued) {
                        $q->where('user_id', $userId);
                        if ($hasIssued && $this->isAdmin) {
                            $q->orWhere('issued_by', $userId);
                        }
                    })->delete();
                $this->redirect(['module' => 'api', 'action' => 'keys']);
            }
            $name = trim((string) $request->getParameter('name'));
            $scopes = array_values(array_intersect((array) $request->getParameter('scopes', []), array_keys($this->scopes)));
            $expires = trim((string) $request->getParameter('expires_at'));
            $daily = (int) $request->getParameter('daily_limit') ?: null;
            $ownerId = $userId;
            $issuedBy = null;
            $for = trim((string) $request->getParameter('issue_for'));
            if ('' !== $for) {
                // Administrators issue keys to other people (e.g. read-only keys for
                // testing the MCP). The key acts as its owner, with the owner's groups.
                $ownerId = $this->isAdmin ? (int) $db::table('user')->where('username', $for)->orWhere('email', $for)->value('id') : 0;
                $issuedBy = $userId;
            }
            if ('' === $name || !$scopes) {
                $this->error = 'Give the key a name and at least one scope.';
            } elseif ('' !== $for && !$ownerId) {
                $this->error = 'No user with that username or email.';
            } elseif ('' !== $expires && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expires) || $expires <= date('Y-m-d'))) {
                $this->error = 'The expiry date must be a date after today.';
            } else {
                // Shown once on this response only; the database keeps a hash.
                $this->newKey = (new \AhgAPIPlugin\Service\ApiKeyService())->createApiKey($ownerId, mb_substr($name, 0, 100), $scopes, 1000,
                    '' !== $expires ? $expires.' 23:59:59' : null, $daily, $issuedBy);
                $this->newKey['for'] = '' !== $for ? $for : null;
            }
        }

        $cols = array_merge(['id', 'name', 'api_key_prefix', 'scopes', 'rate_limit', 'expires_at', 'last_used_at', 'is_active', 'created_at'],
            $hasIssued ? ['daily_limit'] : []);
        $this->keys = $db::table('ahg_api_key')->where('user_id', $userId)->orderByDesc('created_at')->get($cols)->all();
        $this->issued = $hasIssued && $this->isAdmin ? $db::table('ahg_api_key as k')->leftJoin('user as u', 'u.id', '=', 'k.user_id')
            ->where('k.issued_by', $userId)->where('k.user_id', '!=', $userId)->orderByDesc('k.created_at')
            ->get(array_merge(array_map(fn ($c) => 'k.'.$c, $cols), ['u.username']))->all() : [];
        $this->canIssue = $hasIssued && $this->isAdmin;
    }

    public function executeSearchInformationObjects($request)
    {
        $this->getResponse()->setContentType('application/json');

        $query = trim($request->getParameter('q', ''));
        $limit = min((int)$request->getParameter('limit', 20), 50);

        if (strlen($query) < 2) {
            return $this->renderText(json_encode(['results' => [], 'error' => 'Query too short']));
        }


        $results = \Illuminate\Database\Capsule\Manager::table('information_object')
            ->join('information_object_i18n', function($join) {
                $join->on('information_object.id', '=', 'information_object_i18n.id')
                     ->where('information_object_i18n.culture', '=', 'en');
            })
            ->leftJoin('slug', 'information_object.id', '=', 'slug.object_id')
            ->leftJoin('term_i18n as lod', function($join) {
                $join->on('information_object.level_of_description_id', '=', 'lod.id')
                     ->where('lod.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('repository', 'information_object.repository_id', '=', 'repository.id')
            ->leftJoin('actor_i18n as repo_name', function($join) {
                $join->on('repository.id', '=', 'repo_name.id')
                     ->where('repo_name.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where(function($q) use ($query) {
                $q->where('information_object_i18n.title', 'LIKE', "%{$query}%")
                  ->orWhere('information_object.identifier', 'LIKE', "%{$query}%");
            })
            ->where('information_object.id', '!=', 1) // Exclude root
            ->select([
                'information_object.id',
                'information_object.identifier',
                'information_object_i18n.title',
                'slug.slug',
                'lod.name as level_of_description',
                'repo_name.authorized_form_of_name as repository'
            ])
            ->orderBy('information_object_i18n.title')
            ->limit($limit)
            ->get();

        return $this->renderText(json_encode([
            'results' => $results->toArray(),
            'count' => $results->count()
        ]));
    }

    public function executeAutocompleteGlam($request)
    {
        $this->getResponse()->setContentType('application/json');

        $query = trim($request->getParameter('q', ''));
        $limit = min((int)$request->getParameter('limit', 10), 20);

        if (strlen($query) < 2) {
            return $this->renderText(json_encode([]));
        }


        $results = \Illuminate\Database\Capsule\Manager::table('information_object')
            ->join('information_object_i18n', function($join) {
                $join->on('information_object.id', '=', 'information_object_i18n.id')
                     ->where('information_object_i18n.culture', '=', 'en');
            })
            ->leftJoin('slug', 'information_object.id', '=', 'slug.object_id')
            ->leftJoin('term_i18n as lod', function($join) {
                $join->on('information_object.level_of_description_id', '=', 'lod.id')
                     ->where('lod.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where(function($q) use ($query) {
                $q->where('information_object_i18n.title', 'LIKE', "%{$query}%")
                  ->orWhere('information_object.identifier', 'LIKE', "%{$query}%");
            })
            ->where('information_object.id', '!=', 1)
            ->select([
                'information_object.id',
                'information_object.identifier',
                'information_object_i18n.title',
                'slug.slug',
                'lod.name as level_of_description'
            ])
            ->orderBy('information_object_i18n.title')
            ->limit($limit)
            ->get();

        // Format for autocomplete
        $formatted = [];
        foreach ($results as $row) {
            $formatted[] = [
                'id' => $row->id,
                'value' => $row->title,
                'label' => $row->title . ($row->identifier ? ' [' . $row->identifier . ']' : ''),
                'identifier' => $row->identifier,
                'slug' => $row->slug,
                'level' => $row->level_of_description
            ];
        }

        return $this->renderText(json_encode($formatted));
    }

    /**
     * Plugin protection status API.
     *
     * Returns protection info for all plugins (record counts, lock status).
     */
    public function executePluginProtection($request)
    {
        $this->getResponse()->setContentType('application/json');

        // Check user is authenticated
        if (!$this->getUser()->isAuthenticated()) {
            $this->getResponse()->setStatusCode(401);

            return $this->renderText(json_encode(['error' => 'Unauthorized']));
        }

        // Check admin permission
        if (!$this->getUser()->hasCredential('administrator')) {
            $this->getResponse()->setStatusCode(403);

            return $this->renderText(json_encode(['error' => 'Forbidden']));
        }

        $protectionStatus = [];

        $frameworkPath = $this->config('sf_root_dir') . '/atom-framework/src/Extensions/ExtensionProtection.php';

        if (file_exists($frameworkPath)) {
            require_once $frameworkPath;
            $protection = new \AtomFramework\Extensions\ExtensionProtection();
            $protectionStatus = $protection->getAllProtectionStatus();
        }

        return $this->renderText(json_encode([
            'success' => true,
            'plugins' => $protectionStatus,
        ]));
    }
}
