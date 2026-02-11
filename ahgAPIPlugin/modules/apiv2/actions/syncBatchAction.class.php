<?php

use AtomFramework\Http\Controllers\AhgApiController;
class apiv2SyncBatchAction extends AhgApiController
{
    public function POST($request)
    {
        if (!$this->hasScope('write')) {
            return $this->error(403, 'Forbidden', 'Write scope required');
        }

        $data = $this->getJsonInput();
        $results = [];

        // Process conditions
        if (!empty($data['conditions'])) {
            $results['conditions'] = $this->processConditions($data['conditions']);
        }

        // Process assets
        if (!empty($data['assets'])) {
            $results['assets'] = $this->processAssets($data['assets']);
        }

        // Process valuations
        if (!empty($data['valuations'])) {
            $results['valuations'] = $this->processValuations($data['valuations']);
        }

        return $this->success([
            'processed' => $results,
            'server_time' => date('c')
        ]);
    }

    protected function processConditions($items)
    {
        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($items as $item) {
            try {
                if (!empty($item['id'])) {
                    $this->repository->updateCondition($item['id'], $item);
                    $updated++;
                } else {
                    $this->repository->createCondition($item);
                    $created++;
                }
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        return ['created' => $created, 'updated' => $updated, 'errors' => $errors];
    }

    protected function processAssets($items)
    {
        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($items as $item) {
            try {
                if (!empty($item['id'])) {
                    $this->repository->updateAsset($item['id'], $item);
                    $updated++;
                } else {
                    $this->repository->createAsset($item);
                    $created++;
                }
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        return ['created' => $created, 'updated' => $updated, 'errors' => $errors];
    }

    protected function processValuations($items)
    {
        $created = 0;
        $errors = [];

        foreach ($items as $item) {
            try {
                $this->repository->createValuation($item);
                $created++;
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        return ['created' => $created, 'errors' => $errors];
    }
}
