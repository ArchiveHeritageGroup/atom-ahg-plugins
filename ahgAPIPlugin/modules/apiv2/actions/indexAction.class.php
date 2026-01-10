<?php

class apiv2IndexAction extends AhgApiAction
{
    public function GET($request)
    {
        return $this->success([
            'name' => 'AtoM AHG REST API',
            'version' => 'v2.0.0',
            'endpoints' => [
                'descriptions' => '/api/v2/descriptions',
                'authorities' => '/api/v2/authorities',
                'repositories' => '/api/v2/repositories',
                'accessions' => '/api/v2/accessions',
                'taxonomies' => '/api/v2/taxonomies',
                'search' => '/api/v2/search',
                'batch' => '/api/v2/batch',
                'webhooks' => '/api/v2/webhooks',
                'keys' => '/api/v2/keys'
            ],
            'authentication' => [
                'header' => 'X-API-Key: your-api-key',
                'bearer' => 'Authorization: Bearer your-api-key'
            ],
            'documentation' => 'https://docs.theahg.co.za/api/v2'
        ]);
    }
}
