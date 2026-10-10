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

        if ($request->isMethod('post')) {
            if ('revoke' === $request->getParameter('form_action')) {
                \Illuminate\Database\Capsule\Manager::table('ahg_api_key')
                    ->where('id', (int) $request->getParameter('key_id'))->where('user_id', $userId)->delete();
                $this->redirect(['module' => 'api', 'action' => 'keys']);
            }
            $name = trim((string) $request->getParameter('name'));
            $scopes = array_values(array_intersect((array) $request->getParameter('scopes', []), array_keys($this->scopes)));
            if ('' === $name || !$scopes) {
                $this->error = 'Give the key a name and at least one scope.';
            } else {
                // Shown once on this response only; the database keeps a hash.
                $this->newKey = (new \AhgAPIPlugin\Service\ApiKeyService())->createApiKey($userId, mb_substr($name, 0, 100), $scopes);
            }
        }

        $this->keys = \Illuminate\Database\Capsule\Manager::table('ahg_api_key')->where('user_id', $userId)
            ->orderByDesc('created_at')->get(['id', 'name', 'api_key_prefix', 'scopes', 'last_used_at', 'is_active', 'created_at'])->all();
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
