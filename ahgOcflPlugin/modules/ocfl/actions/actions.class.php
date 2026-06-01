<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * ocfl module - admin dashboard + API for the OCFL preservation storage layer.
 *
 * Routes (see ahgOcflPluginConfiguration):
 *   GET  /admin/ocfl                     dashboard (storage root status + object list)
 *   POST /api/ocfl/init                  initialise the storage root
 *   POST /api/ocfl/ingest/:id            snapshot an IO into OCFL
 *   POST /api/ocfl/verify/:id            verify one OCFL object
 *   POST /api/ocfl/verify-all            verify the whole storage root
 *   POST /api/ocfl/export/:id            export one OCFL object to a tarball
 *
 * @copyright  The Archive and Heritage Group (Pty) Ltd
 * @license    AGPL-3.0-or-later
 */
class ocflActions extends AhgController
{
    protected function requireAdmin(): void
    {
        $user = $this->getUser();
        if (!$user->isAuthenticated()
            || !$user->hasGroup(\AtomExtensions\Constants\AclConstants::ADMINISTRATOR_ID)
        ) {
            \AtomExtensions\Services\AclService::forwardUnauthorized();
        }
    }

    protected function service(): \AtomExtensions\Ocfl\Services\OcflService
    {
        require_once dirname(__DIR__, 3) . '/lib/bootstrap.php';

        return new \AtomExtensions\Ocfl\Services\OcflService();
    }

    // ------------------------------------------------------------------
    // Dashboard
    // ------------------------------------------------------------------

    public function executeIndex($request)
    {
        $this->requireAdmin();
        $svc = $this->service();
        $root = $svc->storageRoot();

        $this->storageRootPath = $svc->storageRootPath();
        $this->storageLayout = $svc->layout();
        $this->digestAlgorithm = $svc->digestAlgorithm();
        $this->exportPath = $svc->exportPath();
        $this->initialized = $root->isInitialized();

        $this->objects = [];
        if ($this->initialized) {
            foreach ($root->list() as $objectId) {
                try {
                    $obj = $root->read($objectId);
                    $this->objects[] = [
                        'object_id' => $objectId,
                        'head' => $obj->inventory->head,
                        'versions' => count($obj->inventory->versions),
                        'files' => count($obj->inventory->manifest),
                    ];
                } catch (\Throwable $e) {
                    $this->objects[] = [
                        'object_id' => $objectId,
                        'head' => '?',
                        'versions' => 0,
                        'files' => 0,
                    ];
                }
            }
        }

        $this->pageTitle = 'OCFL Preservation Storage';
    }

    // ------------------------------------------------------------------
    // API
    // ------------------------------------------------------------------

    public function executeApiInit($request)
    {
        $this->requireAdmin();
        $svc = $this->service();
        $root = $svc->storageRoot();

        if ($root->isInitialized()) {
            return $this->renderJson(['success' => true, 'initialized' => true, 'message' => 'Storage root already initialised.']);
        }

        $root->initialize();

        return $this->renderJson([
            'success' => true,
            'initialized' => true,
            'message' => 'Storage root initialised.',
            'layout' => $root->layout->layout,
            'digest' => $root->digester->algorithm,
        ]);
    }

    public function executeApiIngest($request)
    {
        $this->requireAdmin();
        $svc = $this->service();

        $ioId = (int) $request->getParameter('id');
        $message = $request->getParameter('message');
        [$userName, $userAddress] = $this->resolveUser();

        $result = $svc->ingestInformationObject($ioId, $message ?: null, $userName, $userAddress);

        return $this->renderJson(['success' => 'ok' === $result['status']] + $result);
    }

    public function executeApiVerify($request)
    {
        $this->requireAdmin();
        $svc = $this->service();
        $root = $svc->storageRoot();

        $ioId = (int) $request->getParameter('id');
        $objectId = $svc->resolveObjectId($ioId);

        if (!$root->exists($objectId)) {
            return $this->renderJson(['success' => false, 'message' => "OCFL object for IO {$ioId} not found ({$objectId})."], 404);
        }

        $errors = $root->verify($objectId);

        return $this->renderJson([
            'success' => [] === $errors,
            'object_id' => $objectId,
            'errors' => $errors,
        ]);
    }

    public function executeApiVerifyAll($request)
    {
        $this->requireAdmin();
        $svc = $this->service();
        $root = $svc->storageRoot();

        if (!$root->isInitialized()) {
            return $this->renderJson(['success' => false, 'message' => 'Storage root is not initialised.'], 400);
        }

        $results = [];
        $failed = 0;
        foreach ($root->list() as $id) {
            $errors = $root->verify($id);
            if ([] !== $errors) {
                ++$failed;
            }
            $results[$id] = $errors;
        }

        return $this->renderJson([
            'success' => 0 === $failed,
            'failed' => $failed,
            'total' => count($results),
            'results' => $results,
        ]);
    }

    public function executeApiExport($request)
    {
        $this->requireAdmin();
        $svc = $this->service();

        $result = $svc->exportObject((int) $request->getParameter('id'));

        return $this->renderJson(['success' => 'ok' === $result['status']] + $result, 'ok' === $result['status'] ? 200 : 400);
    }

    /** @return array{0:?string,1:?string} */
    protected function resolveUser(): array
    {
        $user = $this->getUser();
        $name = 'admin';
        try {
            $u = $user->getUserObject();
            if (is_object($u)) {
                $name = (string) ($u->email ?? $u->username ?? 'admin');
            }
        } catch (\Throwable $e) {
            $name = 'admin';
        }

        return ['' !== $name ? $name : 'admin', null];
    }
}
