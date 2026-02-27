<?php

/**
 * GIS module actions — JSON API for spatial queries and GeoJSON export.
 *
 * Routes:
 *   GET /gis/bbox?lat_min=&lat_max=&lng_min=&lng_max=&sources=&limit=
 *   GET /gis/radius?lat=&lng=&radius=&sources=&limit=
 *   GET /gis/geojson?sources=&limit=
 */
class gisActions extends sfActions
{
    /** @var \AhgGIS\Services\SpatialSearchService */
    private $spatialService;

    public function preExecute()
    {
        parent::preExecute();
        $this->spatialService = new \AhgGIS\Services\SpatialSearchService();
    }

    /**
     * Bounding box search.
     *
     * GET /gis/bbox?lat_min=-34.5&lat_max=-33.0&lng_min=18.0&lng_max=19.5&limit=100
     */
    public function executeBbox(sfWebRequest $request)
    {
        $latMin = (float) $request->getParameter('lat_min');
        $latMax = (float) $request->getParameter('lat_max');
        $lngMin = (float) $request->getParameter('lng_min');
        $lngMax = (float) $request->getParameter('lng_max');

        if ($latMin == 0 && $latMax == 0) {
            return $this->jsonResponse(['error' => 'lat_min and lat_max are required'], 400);
        }

        $sources = $this->parseSources($request);
        $limit = min((int) ($request->getParameter('limit', 200)), 1000);

        $results = $this->spatialService->boundingBox($latMin, $latMax, $lngMin, $lngMax, $sources, $limit);

        return $this->jsonResponse([
            'count' => count($results),
            'bbox' => ['lat_min' => $latMin, 'lat_max' => $latMax, 'lng_min' => $lngMin, 'lng_max' => $lngMax],
            'results' => $results,
        ]);
    }

    /**
     * Radius (proximity) search.
     *
     * GET /gis/radius?lat=-33.9&lng=18.4&radius=50&limit=100
     */
    public function executeRadius(sfWebRequest $request)
    {
        $lat = (float) $request->getParameter('lat');
        $lng = (float) $request->getParameter('lng');
        $radius = (float) $request->getParameter('radius', 50);

        if ($lat == 0 && $lng == 0) {
            return $this->jsonResponse(['error' => 'lat and lng are required'], 400);
        }

        if ($radius <= 0 || $radius > 20000) {
            return $this->jsonResponse(['error' => 'radius must be between 0 and 20000 km'], 400);
        }

        $sources = $this->parseSources($request);
        $limit = min((int) ($request->getParameter('limit', 200)), 1000);

        $results = $this->spatialService->radius($lat, $lng, $radius, $sources, $limit);

        return $this->jsonResponse([
            'count' => count($results),
            'centre' => ['lat' => $lat, 'lng' => $lng],
            'radius_km' => $radius,
            'results' => $results,
        ]);
    }

    /**
     * GeoJSON export.
     *
     * GET /gis/geojson?sources=contact,nmmz&limit=500
     */
    public function executeGeojson(sfWebRequest $request)
    {
        $sources = $this->parseSources($request);
        $limit = min((int) ($request->getParameter('limit', 1000)), 5000);

        $geoJson = $this->spatialService->toGeoJSON($sources, $limit);

        $this->getResponse()->setContentType('application/geo+json');
        $this->getResponse()->setContent(json_encode($geoJson, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return sfView::NONE;
    }

    /**
     * Parse the 'sources' query parameter into an array.
     */
    private function parseSources(sfWebRequest $request): array
    {
        $raw = $request->getParameter('sources', '');
        if (empty($raw)) {
            return [];
        }
        return array_filter(array_map('trim', explode(',', $raw)));
    }

    /**
     * Return a JSON response and stop rendering.
     */
    private function jsonResponse(array $data, int $statusCode = 200): string
    {
        $this->getResponse()->setStatusCode($statusCode);
        $this->getResponse()->setContentType('application/json');
        $this->getResponse()->setContent(json_encode($data, JSON_UNESCAPED_UNICODE));

        return sfView::NONE;
    }
}
