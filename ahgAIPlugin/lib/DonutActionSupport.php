<?php

require_once dirname(__FILE__) . '/Services/ahgDonutService.php';

/**
 * Shared helpers for the DONUT per-action classes.
 *
 * Symfony 1.x resolves "one action per file" classes (e.g.
 * aiDonutDashboardAction), so the DONUT web endpoints live in separate
 * files. This support class holds the logic they share, loaded via
 * require_once + new (no autoload).
 */
class DonutActionSupport
{
    public static function service(): \ahgDonutService
    {
        return new \ahgDonutService();
    }

    public static function currentUserId($action): ?int
    {
        try {
            $user = $action->getUser();
            if ($user && method_exists($user, 'getUserID')) {
                $id = $user->getUserID();
                return $id ? (int) $id : null;
            }
        } catch (\Exception $e) {
            // unauthenticated - leave null.
        }

        return null;
    }

    public static function prefill($request, ?int $userId): array
    {
        $imagePath = (string) $request->getParameter('image_path', '');
        if ($imagePath === '' || !is_readable($imagePath)) {
            return ['success' => false, 'error' => 'Image not found: ' . $imagePath];
        }

        $objectId = $request->getParameter('object_id');
        $objectId = $objectId ? (int) $objectId : null;

        $result = self::service()->extract($imagePath, $objectId, $userId);
        if ($result === null) {
            return ['success' => false, 'error' => 'DONUT service unavailable'];
        }
        $result['success'] = $result['success'] ?? true;

        return $result;
    }

    public static function finalize($request): array
    {
        $extractionId = (int) $request->getParameter('extraction_id', 0);
        $ioId = (int) $request->getParameter('io_id', 0);

        if ($extractionId <= 0 || $ioId <= 0) {
            return ['success' => false, 'error' => 'extraction_id + io_id required'];
        }

        try {
            $db = \Illuminate\Database\Capsule\Manager::class;

            $updated = $db::table('ahg_donut_extraction')
                ->where('id', $extractionId)
                ->update([
                    'information_object_id' => $ioId,
                    'status'                => 'finalised',
                ]);

            $row = $db::table('ahg_donut_extraction')->where('id', $extractionId)->first();
            if ($row && !empty($row->input_hash)) {
                $db::table('ahg_ai_inference')
                    ->where('service_name', 'DONUT')
                    ->where('target_entity_type', 'pending')
                    ->where('input_hash', $row->input_hash)
                    ->update([
                        'target_entity_type' => 'information_object',
                        'target_entity_id'   => $ioId,
                    ]);
            }

            return ['success' => true, 'finalised' => $updated, 'io_id' => $ioId];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public static function positions($request): array
    {
        $docType = (string) $request->getParameter('doc_type', 'type_a');
        $result = self::service()->positions($docType);
        if ($result === null) {
            return ['success' => false, 'error' => 'DONUT service unavailable'];
        }
        $result['success'] = true;

        return $result;
    }

    public static function result($request): array
    {
        $id = (int) $request->getParameter('id', 0);
        if ($id <= 0) {
            return ['success' => false, 'error' => 'id required'];
        }

        try {
            $db = \Illuminate\Database\Capsule\Manager::class;
            $row = $db::table('ahg_donut_extraction')->where('id', $id)->first();
            if (!$row) {
                return ['success' => false, 'error' => 'Not found'];
            }

            return [
                'success'    => true,
                'id'         => (int) $row->id,
                'doc_type'   => $row->doc_type,
                'confidence' => $row->confidence !== null ? (float) $row->confidence : null,
                'status'     => $row->status,
                'fields'     => json_decode((string) $row->fields_json, true),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
