<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Clipboard export (CallHub CH-000215).
 *
 * Base AtoM's clipboard export crashed on every submit with "Call to undefined
 * method IndexWrapper::createSearch()" inside its export job. Base is locked, so
 * the POST is routed here instead (the base form still renders the page). The
 * same writers produce the same files: AtoM's CSV classes (csvInformationObject,
 * csvActor, csvRepository, csvAccession) and the EAD / EAC templates, as the
 * framework's export commands use them. The theme's export form posts the
 * clipboard's slugs with the request, so nothing is read from the session.
 *
 * ponytail: runs in the request and is capped at MAX_RECORDS. Larger exports
 * belong in a background job; move this behind the jobs queue if clipboards that
 * big become normal.
 */
class clipboardExportActions extends AhgController
{
    private const MAX_RECORDS = 2000;

    /** exportType => formats this export supports. */
    private const FORMATS = [
        'informationObject' => ['csv', 'xml'],
        'actor' => ['csv', 'xml'],
        'repository' => ['csv'],
        'accession' => ['csv'],
    ];

    public function executeExport($request)
    {
        if (!$request->isMethod('post')) {
            $this->redirect(['module' => 'clipboard', 'action' => 'export']);
        }

        $type = (string) $request->getParameter('exportType', 'informationObject');
        $format = (string) $request->getParameter('format', 'csv');
        $slugs = array_values(array_unique(array_filter(array_map('strval', (array) $request->getParameter('slugs', [])))));

        if (!isset(self::FORMATS[$type])) {
            return $this->back(__('Unknown export type.'));
        }
        if (!in_array($format, self::FORMATS[$type], true)) {
            return $this->back(__('That format is not available for this type of record.'));
        }
        if (empty($slugs)) {
            return $this->back(__('The clipboard is empty for this entity type.'));
        }
        if (('accession' === $type || 'repository' === $type || 'xml' === $format) && !$this->getUser()->isAuthenticated()) {
            \AtomExtensions\Services\AclService::forwardUnauthorized();
        }

        $ids = DB::table('slug')->whereIn('slug', $slugs)->pluck('object_id')->map('intval')->all();

        $dir = sys_get_temp_dir().'/ahg-clipboard-export-'.bin2hex(random_bytes(6));
        mkdir($dir, 0700, true);

        try {
            $count = match ($type) {
                'informationObject' => $this->exportDescriptions($request, $ids, $format, $dir),
                'actor' => $this->exportActors($ids, $format, $dir),
                'repository' => $this->exportSimple($ids, 'repository', \csvRepositoryExport::class, 'QubitRepository', $dir.'/repositories.csv'),
                'accession' => $this->exportSimple($ids, 'accession', \csvAccessionExport::class, 'QubitAccession', $dir.'/accessions.csv'),
            };
            if (0 === $count) {
                return $this->back(__('Nothing on the clipboard can be exported with these options.'));
            }

            return $this->sendZip($dir, $type);
        } finally {
            $this->removeDir($dir);
        }
    }

    /**
     * Descriptions: the selected records, plus their descendants when asked
     * (optionally only at chosen levels), drafts only when asked and allowed,
     * never anything the access filter hides from this user.
     */
    private function exportDescriptions($request, array $ids, string $format, string $dir): int
    {
        $user = $this->getUser();
        $withDescendants = (bool) $request->getParameter('includeDescendants');
        $allLevels = (bool) $request->getParameter('includeAllLevels');
        $levels = array_filter(array_map('intval', (array) $request->getParameter('levels', [])));
        $withDrafts = $user->isAuthenticated() && $request->getParameter('includeDrafts');

        $roots = DB::table('information_object')->whereIn('id', $ids)->where('id', '>', 1)
            ->orderBy('lft')->get(['id', 'lft', 'rgt']);
        $allowed = function (array $candidate) use ($withDrafts, $user) {
            if (empty($candidate)) {
                return [];
            }
            if (!$withDrafts) {
                $candidate = DB::table('status')->where('type_id', \QubitTerm::STATUS_TYPE_PUBLICATION_ID)
                    ->where('status_id', \QubitTerm::PUBLICATION_STATUS_PUBLISHED_ID)
                    ->whereIn('object_id', $candidate)->pluck('object_id')->map('intval')->all();
            }
            $userId = $user->getAttribute('user_id');
            $restricted = array_flip(array_map('intval', \AtomExtensions\Services\Search\SearchAccessFilterService::getInstance()
                ->getRestrictedObjectIds($userId ? (int) $userId : null)));

            return array_values(array_filter($candidate, fn ($id) => !isset($restricted[$id])));
        };

        if ('xml' === $format) {
            $this->loadTemplateHelpers();
            $count = 0;
            foreach ($allowed($roots->pluck('id')->map('intval')->all()) as $id) {
                $resource = \QubitInformationObject::getById($id);
                $xml = $this->renderXml($resource, 'ead', ['current-level-only' => !$withDescendants, 'public' => !$withDrafts]);
                file_put_contents(sprintf('%s/%s.xml', $dir, $resource->slug ?: $id), $xml);
                ++$count;
                if ($count >= self::MAX_RECORDS) {
                    break;
                }
            }

            return $count;
        }

        // CSV: roots first, then their descendants, in tree order so a parent
        // always precedes its children (AtoM's CSV import needs that).
        $wanted = $roots->pluck('id')->map('intval')->all();
        if ($withDescendants) {
            foreach ($roots as $root) {
                $q = DB::table('information_object')->where('lft', '>', $root->lft)->where('rgt', '<', $root->rgt);
                if (!$allLevels && $levels) {
                    $q->whereIn('level_of_description_id', $levels);
                }
                $wanted = array_merge($wanted, $q->pluck('id')->map('intval')->all());
            }
        }
        $wanted = $allowed(array_values(array_unique($wanted)));
        $ordered = DB::table('information_object')->whereIn('id', $wanted)->orderBy('lft')
            ->limit(self::MAX_RECORDS)->pluck('id')->map('intval')->all();
        if (empty($ordered)) {
            return 0;
        }

        $writer = new \csvInformationObjectExport($dir, 'isad');
        $writer->user = $user;
        $writer->setOptions(['public' => !$withDrafts]);
        foreach ($ordered as $id) {
            $resource = \QubitInformationObject::getById($id); // exportResource() takes it by reference
            $writer->exportResource($resource);
        }
        $this->appendCustomFields($dir, 'informationobject', !$withDrafts);

        return count($ordered);
    }

    private function exportActors(array $ids, string $format, string $dir): int
    {
        $ids = DB::table('actor')->whereIn('id', $ids)->limit(self::MAX_RECORDS)->pluck('id')->map('intval')->all();
        if (empty($ids)) {
            return 0;
        }
        if ('xml' === $format) {
            $this->loadTemplateHelpers();
            foreach ($ids as $id) {
                $resource = \QubitActor::getById($id);
                file_put_contents(sprintf('%s/%s.xml', $dir, $resource->slug ?: $id), $this->renderXml($resource, 'eac'));
            }

            return count($ids);
        }
        $writer = new \csvActorExport($dir);
        $writer->user = $this->getUser();
        foreach ($ids as $id) {
            $resource = \QubitActor::getById($id);
            $writer->exportResource($resource);
        }
        $this->appendCustomFields($dir, 'actor', false);

        return count($ids);
    }

    private function exportSimple(array $ids, string $table, string $writerClass, string $model, string $file): int
    {
        $ids = DB::table($table)->whereIn('id', $ids)->limit(self::MAX_RECORDS)->pluck('id')->map('intval')->all();
        if (empty($ids)) {
            return 0;
        }
        $writer = new $writerClass($file);
        $writer->user = $this->getUser();
        foreach ($ids as $id) {
            $resource = $model::getById($id);
            $writer->exportResource($resource);
        }

        return count($ids);
    }

    /** Render a record through AtoM's own EAD / EAC template, as the framework's export commands do. */
    private function renderXml($resource, string $format, array $options = []): string
    {
        $plugin = 'sf'.ucfirst($format).'Plugin';
        $template = sfConfig::get('sf_root_dir').sprintf('/plugins/%s/modules/%s/templates/indexSuccess.xml.php', $plugin, $plugin);
        if ('ead' === $format) {
            $ead = new \sfEadPlugin($resource, $options);
            $findingAidVisibilitiy = false;
            // The EAD template's level mapping reads this, as in the framework's export command.
            $eadLevels = ['class', 'collection', 'file', 'fonds', 'item', 'otherlevel', 'recordgrp', 'series', 'subfonds', 'subgrp', 'subseries'];
        } else {
            $eac = new \sfEacPlugin($resource);
        }
        $iso639convertor = new \fbISO639_Map();
        $exportLanguage = $this->getUser()->getCulture();
        $sourceLanguage = $resource->getSourceCulture();

        ob_start();
        include $template;

        return \Qubit::tidyXml((string) ob_get_clean());
    }

    private function loadTemplateHelpers(): void
    {
        $this->getContext()->getConfiguration()->loadHelpers(['Url', 'Qubit', 'Text', 'I18N', 'Date', 'Escaping']);
    }

    private function appendCustomFields(string $dir, string $entity, bool $publicOnly): void
    {
        if (class_exists('\\AtomFramework\\Services\\CustomFieldValues')) {
            foreach (glob($dir.'/*.csv') ?: [] as $file) {
                \AtomFramework\Services\CustomFieldValues::appendToCsv($file, $entity, $publicOnly);
            }
        }
    }

    private function sendZip(string $dir, string $type)
    {
        $zipFile = $dir.'.zip';
        $zip = new \ZipArchive();
        $zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $zip->addFile($file->getPathname(), substr($file->getPathname(), strlen($dir) + 1));
        }
        $zip->close();

        $response = $this->getResponse();
        $response->setContentType('application/zip');
        $response->setHttpHeader('Content-Disposition', sprintf('attachment; filename="clipboard-%s-%s.zip"', $type, date('Y-m-d')));
        $response->setContent((string) file_get_contents($zipFile));
        @unlink($zipFile);

        return sfView::NONE;
    }

    private function back(string $message)
    {
        $this->getUser()->setFlash('error', $message);
        $this->redirect(['module' => 'clipboard', 'action' => 'export']);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $f) {
            $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
        rmdir($dir);
    }
}
