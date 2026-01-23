<?php

/**
 * ISBN Provider Edit Action
 */
class ahgLibraryPluginIsbnProviderEditAction extends sfAction
{
    public function execute($request)
    {
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        \AhgCore\Core\AhgDb::init();
        $db = \Illuminate\Database\Capsule\Manager::class;

        $this->provider = null;
        $id = $request->getParameter('id');
        
        if ($id) {
            $this->provider = $db::table('atom_isbn_provider')->find($id);
            if (!$this->provider) {
                $this->forward404('Provider not found');
            }
        }

        if ($request->isMethod('post')) {
            $data = [
                'name' => trim($request->getParameter('name')),
                'slug' => trim($request->getParameter('slug')),
                'api_endpoint' => trim($request->getParameter('api_endpoint')),
                'api_key_setting' => trim($request->getParameter('api_key_setting')) ?: null,
                'priority' => (int) $request->getParameter('priority', 50),
                'enabled' => $request->getParameter('enabled') ? 1 : 0,
                'rate_limit_per_minute' => (int) $request->getParameter('rate_limit_per_minute', 100),
                'response_format' => $request->getParameter('response_format', 'json'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if (empty($data['name']) || empty($data['slug']) || empty($data['api_endpoint'])) {
                $this->getUser()->setFlash('error', 'Name, slug, and API endpoint are required.');
                return sfView::SUCCESS;
            }

            if ($id) {
                $db::table('atom_isbn_provider')->where('id', $id)->update($data);
                $this->getUser()->setFlash('notice', 'Provider updated successfully.');
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                $db::table('atom_isbn_provider')->insert($data);
                $this->getUser()->setFlash('notice', 'Provider added successfully.');
            }

            $this->redirect(['module' => 'ahgLibraryPlugin', 'action' => 'isbnProviders']);
        }
    }
}
