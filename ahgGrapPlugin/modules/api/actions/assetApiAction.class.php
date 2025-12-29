<?php
/**
 * GRAP API Action
 * 
 * RESTful API for GRAP heritage asset data.
 * 
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgGrapPlugin
 */

class apiAssetApiAction extends sfAction
{
    public function execute($request)
    {
        $this->response->setContentType('application/json');

        $method = $request->getMethod();
        $objectId = $request->getParameter('object_id');

        try {
            switch ($method) {
                case 'GET':
                    if ($objectId) {
                        return $this->getAsset($objectId);
                    } else {
                        return $this->listAssets($request);
                    }
                    break;

                case 'POST':
                    return $this->createOrUpdateAsset($request);
                    break;

                case 'PUT':
                    return $this->updateAsset($objectId, $request);
                    break;

                default:
                    return AhgCentralHelpers::apiJsonError($this, 'Method not allowed', 405);
            }
        } catch (Exception $e) {
            return AhgCentralHelpers::apiJsonError($this, $e->getMessage(), 500);
        }
    }

    protected function getAsset($objectId)
    {
        $service = new arGrapHeritageAssetService();
        $asset = $service->getAssetRecord($objectId);

        if (!$asset || !$asset['id']) {
            return AhgCentralHelpers::apiJsonError($this, 'Asset not found', 404);
        }

        // Add object details
        $object = QubitInformationObject::getById($objectId);
        if ($object) {
            $asset['identifier'] = $object->identifier;
            $asset['title'] = $object->getTitle(['cultureFallback' => true]);
            $asset['slug'] = $object->slug;
        }

        // Add Spectrum linkage status
        $asset['spectrum_status'] = $service->getLinkedSpectrumStatus($objectId);

        // Add compliance score
        $complianceService = new arGrapComplianceService();
        $compliance = $complianceService->checkCompliance($objectId);
        $asset['compliance_score'] = $compliance['overall_score'];

        return AhgCentralHelpers::apiJsonResponse($this, $asset);
    }

    protected function listAssets($request)
    {
        $repositoryId = $request->getParameter('repository_id');
        $status = $request->getParameter('status');
        $assetClass = $request->getParameter('asset_class');
        $limit = min((int)$request->getParameter('limit', 50), 200);
        $offset = (int)$request->getParameter('offset', 0);

        $conn = Propel::getConnection();

        $where = "WHERE 1=1";
        $params = [];

        if ($repositoryId) {
            $where .= " AND io.repository_id = :repository_id";
            $params[':repository_id'] = $repositoryId;
        }

        if ($status) {
            $where .= " AND g.recognition_status = :status";
            $params[':status'] = $status;
        }

        if ($assetClass) {
            $where .= " AND g.asset_class = :asset_class";
            $params[':asset_class'] = $assetClass;
        }

        // Count total
        $countSql = "SELECT COUNT(*) FROM grap_heritage_asset g 
                     JOIN information_object io ON g.object_id = io.id
                LEFT JOIN slug s ON io.id = s.object_id {$where}";
        $stmt = $conn->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();

        // Get assets
        $sql = "SELECT g.*, io.identifier, s.slug
                FROM grap_heritage_asset g
                JOIN information_object io ON g.object_id = io.id
                LEFT JOIN slug s ON io.id = s.object_id
                {$where}
                ORDER BY g.current_carrying_amount DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add titles
        foreach ($assets as &$asset) {
            $object = QubitInformationObject::getById($asset['object_id']);
            $asset['title'] = $object ? $object->getTitle(['cultureFallback' => true]) : '';
        }

        return AhgCentralHelpers::apiJsonResponse($this, [
            'total' => (int)$total,
            'limit' => $limit,
            'offset' => $offset,
            'assets' => $assets
        ]);
    }

    protected function createOrUpdateAsset($request)
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['object_id'])) {
            return AhgCentralHelpers::apiJsonError($this, 'object_id is required', 400);
        }

        $service = new arGrapHeritageAssetService();
        $id = $service->saveAssetRecord($data['object_id'], $data);

        return AhgCentralHelpers::apiJsonResponse($this, [
            'success' => true,
            'id' => $id,
            'object_id' => $data['object_id']
        ]);
    }

    protected function updateAsset($objectId, $request)
    {
        if (!$objectId) {
            return AhgCentralHelpers::apiJsonError($this, 'object_id is required', 400);
        }

        $data = json_decode($request->getContent(), true);

        $service = new arGrapHeritageAssetService();
        $id = $service->saveAssetRecord($objectId, $data);

        return AhgCentralHelpers::apiJsonResponse($this, [
            'success' => true,
            'id' => $id,
            'object_id' => $objectId
        ]);
    }
}
