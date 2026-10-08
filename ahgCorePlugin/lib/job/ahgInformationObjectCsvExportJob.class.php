<?php

/**
 * Base AtoM's description CSV export job, with one column per custom field
 * appended to the files it writes (#202). A public export keeps to the fields
 * marked visible to the public. Registered in ahgCorePlugin/config/gearman.yml.
 */
class ahgInformationObjectCsvExportJob extends arInformationObjectCsvExportJob
{
    private bool $ahgPublicOnly = false;

    public function runJob($parameters)
    {
        $this->ahgPublicOnly = !empty($parameters['public']);

        return parent::runJob($parameters);
    }

    /**
     * The clipboard records to export, in tree order, read from the database.
     *
     * Base finds them through the search index with Elastica's createSearch(),
     * which the OpenSearch index wrapper does not have, so every clipboard
     * description export failed. Same criteria as base: the clipboard slugs,
     * published only for a public export. Descendants and levels are applied
     * per record by exportResource(), as in base.
     *
     * @return int[]
     */
    public static function clipboardRecordIds(array $parameters): array
    {
        $slugs = array_values(array_filter((array) ($parameters['params']['slugs'] ?? []), 'is_string'));
        if (!$slugs) {
            return [];
        }
        $query = \Illuminate\Database\Capsule\Manager::table('information_object as io')
            ->join('slug as s', 's.object_id', '=', 'io.id')
            ->whereIn('s.slug', $slugs);
        if (!empty($parameters['public'])) {
            $query->join('status as st', 'st.object_id', '=', 'io.id')
                ->where('st.type_id', QubitTerm::STATUS_TYPE_PUBLICATION_ID)
                ->where('st.status_id', QubitTerm::PUBLICATION_STATUS_PUBLISHED_ID);
        }

        return array_map('intval', $query->orderBy('io.lft')->pluck('io.id')->all());
    }

    protected function doExport($path)
    {
        if (empty($this->params['params']['fromClipboard'])) {
            parent::doExport($path);
        } else {
            $this->csvWriter = $this->getCsvWriter($path);
            $ids = self::clipboardRecordIds($this->params);
            if ($ids) {
                $this->info($this->i18n->__('Exporting %1 clipboard item(s).', ['%1' => count($ids)]));
            }
            foreach ($ids as $id) {
                if (null !== $resource = QubitInformationObject::getById($id)) {
                    $this->exportResource($resource, $path);
                }
            }
        }

        if (!class_exists('\\AtomFramework\\Services\\CustomFieldValues')) {
            return;
        }
        $files = is_dir($path) ? (glob(rtrim($path, '/').'/*.csv') ?: []) : [$path];
        foreach ($files as $file) {
            try {
                \AtomFramework\Services\CustomFieldValues::appendToCsv($file, 'informationobject', $this->ahgPublicOnly);
            } catch (\Throwable $e) {
                $this->logger->err('Custom fields not added to '.basename($file).': '.$e->getMessage());
            }
        }
    }
}
