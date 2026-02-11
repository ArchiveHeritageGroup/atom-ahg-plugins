<?php

class ioManageActions extends AhgActions
{
    public function preExecute()
    {
        parent::preExecute();

        sfContext::getInstance()->getConfiguration()->loadHelpers(['I18N', 'Url', 'Qubit', 'Text', 'Date']);
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

    // ─── Treeview JSON API ────────────────────────────────────────────

    /**
     * Treeview data — returns JSON for sidebar treeview.
     *
     * Params: id (node ID), show (item|prevSiblings|nextSiblings), limit
     */
    public function executeTreeview(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $id = (int) $request->getParameter('id', 0);
        $show = $request->getParameter('show', 'item');
        $limit = max(1, min(50, (int) $request->getParameter('limit', 5)));
        $culture = $this->context->user->getCulture();
        $isAuth = $this->context->user->isAuthenticated();
        $sort = sfConfig::get('app_sort_treeview_informationobject', 'none');

        if (!$id) {
            return $this->renderText(json_encode(['error' => 'Missing id parameter']));
        }

        $svc = '\\AhgInformationObjectManage\\Services\\TreeviewService';

        switch ($show) {
            case 'prevSiblings':
                $result = $svc::getSiblings($id, 'previous', $culture, $isAuth, $limit, $sort);

                break;

            case 'nextSiblings':
                $result = $svc::getSiblings($id, 'next', $culture, $isAuth, $limit, $sort);

                break;

            case 'children':
                $result = $svc::getChildren($id, $culture, $isAuth, $limit, $sort);

                break;

            case 'ancestors':
                $result = ['items' => $svc::getAncestors($id, $culture, $isAuth)];

                break;

            case 'full':
                $result = $svc::getTreeViewData($id, $culture, $isAuth, $sort);

                break;

            case 'item':
            default:
                $result = $svc::getChildren($id, $culture, $isAuth, $limit, $sort);
        }

        return $this->renderText(json_encode($result));
    }

    /**
     * Full-width treeview — returns paginated flat list of all descendants.
     */
    public function executeTreeviewFull(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $rootId = (int) $request->getParameter('id', 0);
        $limit = max(1, min(10000, (int) $request->getParameter('limit', 8000)));
        $offset = max(0, (int) $request->getParameter('offset', 0));
        $culture = $this->context->user->getCulture();
        $isAuth = $this->context->user->isAuthenticated();
        $sort = sfConfig::get('app_sort_treeview_informationobject', 'none');

        if (!$rootId) {
            return $this->renderText(json_encode(['error' => 'Missing id parameter']));
        }

        $svc = '\\AhgInformationObjectManage\\Services\\TreeviewService';
        $result = $svc::getFullWidthTree($rootId, $culture, $isAuth, $limit, $offset, $sort);

        return $this->renderText(json_encode($result));
    }

    /**
     * Treeview sort — drag-drop move node after another.
     *
     * POST params: id (node to move), target (node to move after)
     */
    public function executeTreeviewSort(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        // Require authenticated editor/admin
        $user = $this->context->user;
        if (!$user->isAuthenticated()
            || !($user->hasGroup(QubitAclGroup::ADMINISTRATOR_ID) || $user->hasGroup(QubitAclGroup::EDITOR_ID))
        ) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Unauthorized']));
        }

        // Only allow when sort mode is 'none' (manual)
        if ('none' !== sfConfig::get('app_sort_treeview_informationobject', 'none')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Drag-drop sorting only available in manual mode']));
        }

        $nodeId = (int) $request->getParameter('id', 0);
        $afterId = (int) $request->getParameter('target', 0);

        if (!$nodeId || !$afterId) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Missing id or target']));
        }

        $svc = '\\AhgInformationObjectManage\\Services\\TreeviewService';
        $success = $svc::moveAfter($nodeId, $afterId);

        return $this->renderText(json_encode(['success' => $success]));
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

    // ─── Digital Object Actions ──────────────────────────────────────

    /**
     * Upload a digital object for an information object.
     *
     * GET: Shows upload form (requires ?io=<slug>).
     * POST: Processes file upload, delegates to QubitDigitalObject for
     *       file handling and derivative generation.
     */
    public function executeDoUpload(sfWebRequest $request)
    {
        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        $culture = $this->context->user->getCulture();

        // ACL — require editor/admin
        $user = $this->context->user;
        if (!$user->isAuthenticated()
            || !($user->hasGroup(QubitAclGroup::ADMINISTRATOR_ID) || $user->hasGroup(QubitAclGroup::EDITOR_ID))
        ) {
            QubitAcl::forwardUnauthorized();
        }

        // Resolve the information object
        $ioSlug = $request->getParameter('io', '');
        if (empty($ioSlug)) {
            $this->forward404();
        }

        $this->io = \AhgInformationObjectManage\Services\InformationObjectCrudService::getBySlug($ioSlug, $culture);
        if (!$this->io) {
            $this->forward404();
        }

        // Check if a digital object already exists
        $this->existingDo = \AhgInformationObjectManage\Services\DigitalObjectService::getByInformationObjectId($this->io['id']);

        // Max upload size for display
        $this->maxUploadSize = \AhgInformationObjectManage\Services\DigitalObjectService::formatFileSize(
            \AhgInformationObjectManage\Services\DigitalObjectService::getMaxUploadSize()
        );

        $this->errors = [];

        // Handle POST
        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters());

            if (!$this->form->isValid()) {
                $this->errors[] = __('Invalid form submission.');

                return;
            }

            // Check for uploaded file
            if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
                $this->errors[] = __('Please select a file to upload.');

                return;
            }

            $file = $_FILES['file'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => __('The uploaded file exceeds the server limit.'),
                    UPLOAD_ERR_FORM_SIZE => __('The uploaded file exceeds the form limit.'),
                    UPLOAD_ERR_PARTIAL => __('The file was only partially uploaded.'),
                    UPLOAD_ERR_NO_TMP_DIR => __('Missing a temporary folder.'),
                    UPLOAD_ERR_CANT_WRITE => __('Failed to write file to disk.'),
                ];
                $this->errors[] = $errorMessages[$file['error']] ?? __('Unknown upload error.');

                return;
            }

            try {
                $ioId = (int) $this->io['id'];

                // If replacing, delete existing digital object first
                if ($this->existingDo && $request->getParameter('replace', '0') === '1') {
                    \AhgInformationObjectManage\Services\DigitalObjectService::delete($this->existingDo['id']);
                } elseif ($this->existingDo) {
                    $this->errors[] = __('A digital object already exists. Check "Replace existing" to overwrite.');

                    return;
                }

                // Move uploaded file to tmp directory
                $movedFile = \Qubit::moveUploadFile($file);
                $tmpFilePath = $movedFile['tmp_name'];

                // Create QubitDigitalObject via Propel (handles derivatives + filesystem)
                $do = new \QubitDigitalObject();
                $do->objectId = $ioId;
                $do->usageId = \QubitTerm::MASTER_ID;
                $do->assets[] = new \QubitAsset($tmpFilePath);
                $do->save();

                // Update search index
                $ioObject = \QubitInformationObject::getById($ioId);
                if ($ioObject) {
                    \QubitSearch::getInstance()->update($ioObject);
                }

                // Clean up tmp file if it still exists
                if (file_exists($tmpFilePath)) {
                    @unlink($tmpFilePath);
                }

                // Redirect to the IO view page
                $this->redirect('/' . $this->io['slug']);
            } catch (\Exception $e) {
                $this->errors[] = __('Upload failed: %1%', ['%1%' => $e->getMessage()]);
            }
        }
    }

    /**
     * Edit digital object metadata.
     *
     * GET: Shows metadata form (filename, mime type, size + editable fields).
     * POST: Updates altText, displayAsCompound, mediaTypeId via Laravel QB.
     */
    public function executeDoEdit(sfWebRequest $request)
    {
        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        $culture = $this->context->user->getCulture();

        // ACL
        $user = $this->context->user;
        if (!$user->isAuthenticated()
            || !($user->hasGroup(QubitAclGroup::ADMINISTRATOR_ID) || $user->hasGroup(QubitAclGroup::EDITOR_ID))
        ) {
            QubitAcl::forwardUnauthorized();
        }

        $doId = (int) $request->getParameter('id', 0);
        if (!$doId) {
            $this->forward404();
        }

        $svc = '\\AhgInformationObjectManage\\Services\\DigitalObjectService';

        $this->digitalObject = $svc::getById($doId);
        if (!$this->digitalObject) {
            $this->forward404();
        }

        // Get the information object for breadcrumb/redirect
        $ioId = $svc::getInformationObjectId($doId);
        $this->ioSlug = $ioId ? $svc::getIoSlug($ioId) : null;

        // Load properties
        $this->properties = $svc::getProperties($doId, $culture);

        // Load media types for dropdown
        $this->mediaTypes = $svc::getMediaTypes($culture);
        $this->mediaTypeName = $svc::getMediaTypeName($this->digitalObject['mediaTypeId'], $culture);
        $this->usageName = $svc::getUsageName($this->digitalObject['usageId'], $culture);

        // Load metadata from extended table
        $this->metadata = $svc::getMetadata($doId);

        // File size formatted
        $this->fileSizeFormatted = $svc::formatFileSize($this->digitalObject['byteSize']);

        // Thumbnail URL for preview
        $this->thumbnailUrl = null;
        if (!empty($this->digitalObject['derivatives']['thumbnail'])) {
            $thumbDo = $this->digitalObject['derivatives']['thumbnail'];
            $this->thumbnailUrl = '/' . ltrim($thumbDo['path'], '/') . $thumbDo['name'];
        }

        // Reference URL for preview
        $this->referenceUrl = null;
        if (!empty($this->digitalObject['derivatives']['reference'])) {
            $refDo = $this->digitalObject['derivatives']['reference'];
            $this->referenceUrl = '/' . ltrim($refDo['path'], '/') . $refDo['name'];
        }

        $this->errors = [];

        // Handle POST
        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters());
            if (!$this->form->isValid()) {
                $this->errors[] = __('Invalid form submission.');

                return;
            }

            // Update properties (altText, displayAsCompound)
            $svc::updateProperties($doId, [
                'altText' => $request->getParameter('altText', ''),
                'displayAsCompound' => (bool) $request->getParameter('displayAsCompound', 0),
            ], $culture);

            // Update media type via direct QB update
            $newMediaTypeId = $request->getParameter('mediaTypeId', '');
            if ($newMediaTypeId !== '') {
                \Illuminate\Database\Capsule\Manager::table('digital_object')
                    ->where('id', $doId)
                    ->update(['media_type_id' => (int) $newMediaTypeId ?: null]);
            }

            // Redirect back to IO
            if ($this->ioSlug) {
                $this->redirect('/' . $this->ioSlug);
            } else {
                $this->redirect('/');
            }
        }
    }

    /**
     * Delete a digital object and all its derivatives.
     *
     * GET: Shows confirmation page.
     * POST (sf_method=delete): Delegates to QubitDigitalObject::delete()
     *       which handles file cleanup, derivative removal, and index update.
     */
    public function executeDoDelete(sfWebRequest $request)
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

        $doId = (int) $request->getParameter('id', 0);
        if (!$doId) {
            $this->forward404();
        }

        $svc = '\\AhgInformationObjectManage\\Services\\DigitalObjectService';

        $this->digitalObject = $svc::getById($doId);
        if (!$this->digitalObject) {
            $this->forward404();
        }

        // Get the information object for redirect
        $ioId = $svc::getInformationObjectId($doId);
        $this->ioSlug = $ioId ? $svc::getIoSlug($ioId) : null;
        $this->fileSizeFormatted = $svc::formatFileSize($this->digitalObject['byteSize']);

        // Count derivatives
        $this->derivativeCount = 0;
        if (!empty($this->digitalObject['derivatives'])) {
            foreach ($this->digitalObject['derivatives'] as $d) {
                if ($d) {
                    ++$this->derivativeCount;
                }
            }
        }

        if ($request->isMethod('delete')) {
            $this->form->bind($request->getPostParameters());

            if ($this->form->isValid()) {
                $success = $svc::delete($doId);

                if ($success && $this->ioSlug) {
                    $this->redirect('/' . $this->ioSlug);
                } elseif ($success) {
                    $this->redirect('/');
                } else {
                    $this->errors = [__('Failed to delete the digital object.')];
                }
            }
        }
    }
}
