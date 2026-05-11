<?php

/**
 * versionControl module — list + show + diff actions for the version-history UI.
 *
 * Routes (registered in plugin Configuration):
 *   GET  /version-control/:entity/:id              → list
 *   GET  /version-control/:entity/:id/:number      → show
 *   GET  /version-control/:entity/:id/diff/:v1/:v2 → diff (Phase G renders the UI)
 *
 * @phase F (list + show) — diff scaffolded for Phase G
 */
class versionControlActions extends sfActions
{
    private const ENTITY_TABLE_MAP = [
        'information_object' => [
            'version_table' => 'information_object_version',
            'fk'            => 'information_object_id',
            'parent_table'  => 'information_object',
            'view_module'   => 'informationobject',
        ],
        'actor' => [
            'version_table' => 'actor_version',
            'fk'            => 'actor_id',
            'parent_table'  => 'actor',
            'view_module'   => 'actor',
        ],
    ];

    private const PAGE_SIZE = 20;

    public function executeList(sfWebRequest $request): void
    {
        $this->requireAuthenticated();
        $this->requireAclAction(\AhgVersionControl\Services\AclCheck::ACTION_LIST);

        $entityType = (string) $request->getParameter('entity');
        $entityId = (int) $request->getParameter('id');
        $page = max(1, (int) $request->getParameter('page', 1));

        if (!isset(self::ENTITY_TABLE_MAP[$entityType]) || $entityId <= 0) {
            $this->forward404();
        }

        $config = self::ENTITY_TABLE_MAP[$entityType];

        // Resolve the entity title for the page heading
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->entitySlug = $this->resolveSlug($entityId);
        $this->entityTitle = $this->resolveTitle($entityType, $entityId);
        $this->viewUrl = $this->entitySlug
            ? '/' . $this->entitySlug
            : '/' . $config['view_module'] . '/view?id=' . $entityId;

        $query = \Illuminate\Database\Capsule\Manager::table($config['version_table'])
            ->leftJoin('user', 'user.id', '=', $config['version_table'] . '.created_by')
            ->where($config['fk'], $entityId)
            ->orderBy('version_number', 'desc')
            ->select(
                $config['version_table'] . '.id',
                $config['version_table'] . '.version_number',
                $config['version_table'] . '.change_summary',
                $config['version_table'] . '.changed_fields',
                $config['version_table'] . '.created_by',
                $config['version_table'] . '.created_at',
                $config['version_table'] . '.is_restore',
                $config['version_table'] . '.restored_from_version',
                'user.username AS created_by_username',
            );

        $this->totalCount = (int) (clone $query)->count();
        $this->page = $page;
        $this->pageSize = self::PAGE_SIZE;
        $this->totalPages = max(1, (int) ceil($this->totalCount / self::PAGE_SIZE));
        $this->versions = $query
            ->offset(($page - 1) * self::PAGE_SIZE)
            ->limit(self::PAGE_SIZE)
            ->get();
    }

    public function executeShow(sfWebRequest $request): void
    {
        $this->requireAuthenticated();
        $this->requireAclAction(\AhgVersionControl\Services\AclCheck::ACTION_LIST);

        $entityType = (string) $request->getParameter('entity');
        $entityId = (int) $request->getParameter('id');
        $versionNumber = (int) $request->getParameter('number');

        if (!isset(self::ENTITY_TABLE_MAP[$entityType]) || $entityId <= 0 || $versionNumber <= 0) {
            $this->forward404();
        }

        $config = self::ENTITY_TABLE_MAP[$entityType];

        $row = \Illuminate\Database\Capsule\Manager::table($config['version_table'])
            ->leftJoin('user', 'user.id', '=', $config['version_table'] . '.created_by')
            ->where($config['fk'], $entityId)
            ->where('version_number', $versionNumber)
            ->select(
                $config['version_table'] . '.*',
                'user.username AS created_by_username',
            )
            ->first();

        if (!$row) {
            $this->forward404();
        }

        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->versionNumber = $versionNumber;
        $this->version = $row;
        $this->snapshot = is_string($row->snapshot) ? (json_decode($row->snapshot, true) ?? []) : [];
        $this->changedFields = is_string($row->changed_fields) ? (json_decode($row->changed_fields, true) ?? []) : [];
        $this->entityTitle = $this->resolveTitle($entityType, $entityId);
        $this->entitySlug = $this->resolveSlug($entityId);
    }

    /**
     * Phase H — restore a previous version of the entity.
     * POST only. Confirms via standard Symfony CSRF token on the form.
     */
    public function executeRestore(sfWebRequest $request): void
    {
        $this->requireAuthenticated();

        if (!$request->isMethod('post')) {
            $this->forward404();
        }

        // Always require version.restore.
        $this->requireAclAction(\AhgVersionControl\Services\AclCheck::ACTION_RESTORE);

        // Additionally require version.restore_classified when the target
        // record carries a classification (Phase J clearance check runs in
        // RestoreService::restore for the actual level check).
        $entityType = (string) $request->getParameter('entity');
        $entityId = (int) $request->getParameter('id');
        if (isset(self::ENTITY_TABLE_MAP[$entityType]) && $entityId > 0) {
            $classified = \Illuminate\Database\Capsule\Manager::table('object_security_classification')
                ->where('object_id', $entityId)
                ->where('active', 1)
                ->exists();
            if ($classified) {
                $this->requireAclAction(\AhgVersionControl\Services\AclCheck::ACTION_RESTORE_CLASSIFIED);
            }
        }

        $entityType = (string) $request->getParameter('entity');
        $entityId = (int) $request->getParameter('id');
        $versionNumber = (int) $request->getParameter('number');

        if (!isset(self::ENTITY_TABLE_MAP[$entityType]) || $entityId <= 0 || $versionNumber <= 0) {
            $this->forward404();
        }

        $libDir = realpath(__DIR__ . '/../../../lib/Services');
        require_once $libDir . '/ClearanceCheck.php';
        require_once $libDir . '/InsufficientClearanceException.php';
        require_once $libDir . '/RestoreService.php';

        $service = new \AhgVersionControl\Services\RestoreService();
        try {
            $newVersion = $service->restore(
                entityType: $entityType,
                entityId: $entityId,
                targetVersionNumber: $versionNumber,
                userId: $this->getUser()->isAuthenticated()
                    ? (int) $this->getUser()->getAttribute('user_id')
                    : null,
            );
            $this->getUser()->setFlash(
                'notice',
                sprintf(__('Restored from v%1$d. New version v%2$d created.'), $versionNumber, $newVersion),
            );
        } catch (\AhgVersionControl\Services\InsufficientClearanceException $e) {
            $this->getResponse()->setStatusCode(403);
            $this->getUser()->setFlash('error', $e->getMessage());
        } catch (\Throwable $e) {
            $this->getUser()->setFlash('error', __('Restore failed: ') . $e->getMessage());
        }

        $this->redirect([
            'module' => 'versionControl',
            'action' => 'list',
            'entity' => $entityType,
            'id'     => $entityId,
        ]);
    }

    /**
     * Phase G — diff renderer.
     */
    public function executeDiff(sfWebRequest $request): void
    {
        $this->requireAuthenticated();
        $this->requireAclAction(\AhgVersionControl\Services\AclCheck::ACTION_DIFF);

        $entityType = (string) $request->getParameter('entity');
        $entityId = (int) $request->getParameter('id');
        $v1 = (int) $request->getParameter('v1');
        $v2 = (int) $request->getParameter('v2');

        if (!isset(self::ENTITY_TABLE_MAP[$entityType]) || $entityId <= 0 || $v1 <= 0 || $v2 <= 0) {
            $this->forward404();
        }

        $config = self::ENTITY_TABLE_MAP[$entityType];
        $table = $config['version_table'];
        $fk = $config['fk'];

        $snap1Json = \Illuminate\Database\Capsule\Manager::table($table)
            ->where($fk, $entityId)->where('version_number', $v1)->value('snapshot');
        $snap2Json = \Illuminate\Database\Capsule\Manager::table($table)
            ->where($fk, $entityId)->where('version_number', $v2)->value('snapshot');
        if (!is_string($snap1Json) || !is_string($snap2Json)) {
            $this->forward404();
        }

        require_once realpath(__DIR__ . '/../../../lib/Services/DiffComputer.php');
        $computer = new \AhgVersionControl\Services\DiffComputer();
        $this->diff = $computer->diff(
            json_decode($snap1Json, true) ?? [],
            json_decode($snap2Json, true) ?? [],
        );
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->v1 = $v1;
        $this->v2 = $v2;
        $this->entityTitle = $this->resolveTitle($entityType, $entityId);
    }

    // ------------------------------------------------------------------

    private function requireAuthenticated(): void
    {
        $user = $this->getUser();
        if (!$user || !$user->isAuthenticated()) {
            $this->forward('user', 'login');
        }
    }

    /**
     * Phase K — gate the action on a version.* ACL permission.
     * Administrator group bypasses all checks (AclCheck handles that).
     */
    private function requireAclAction(string $action): void
    {
        $libDir = realpath(__DIR__ . '/../../../lib/Services');
        require_once $libDir . '/AclCheck.php';

        $user = $this->getUser();
        $userId = ($user && $user->isAuthenticated())
            ? (int) $user->getAttribute('user_id')
            : null;

        $check = new \AhgVersionControl\Services\AclCheck();
        if (!$check->canUserDo($userId, $action)) {
            $this->forward403();
        }
    }

    private function resolveTitle(string $entityType, int $entityId): string
    {
        $i18nTable = $entityType === 'information_object' ? 'information_object_i18n' : 'actor_i18n';
        $field = $entityType === 'information_object' ? 'title' : 'authorized_form_of_name';
        $culture = $this->getUser()->getCulture() ?? 'en';
        $row = \Illuminate\Database\Capsule\Manager::table($i18nTable)
            ->where('id', $entityId)
            ->where('culture', $culture)
            ->value($field);
        if (is_string($row) && $row !== '') {
            return $row;
        }
        // Fallback: any culture
        $row = \Illuminate\Database\Capsule\Manager::table($i18nTable)
            ->where('id', $entityId)
            ->orderBy('culture')
            ->value($field);
        return is_string($row) && $row !== '' ? $row : "#{$entityId}";
    }

    private function resolveSlug(int $objectId): ?string
    {
        $row = \Illuminate\Database\Capsule\Manager::table('slug')
            ->where('object_id', $objectId)
            ->value('slug');
        return is_string($row) ? $row : null;
    }
}
