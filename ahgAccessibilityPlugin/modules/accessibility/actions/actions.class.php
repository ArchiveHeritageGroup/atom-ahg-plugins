<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Accessibility module — image alternative text (WCAG 1.1.1).
 *
 * index : coverage dashboard over image master digital objects + authoring list.
 * edit  : author alt text for one digital object across cultures.
 * save  : persist the edit form.
 * apiObject / apiSlug : JSON consumer API (front-end enhancer, IIIF, etc.).
 */
class accessibilityActions extends AhgController
{
    /** Active interface languages alt text may be authored in. @return string[] */
    private function languages(): array
    {
        $langs = \sfConfig::get('sf_languages', ['en']);
        if (!is_array($langs) || empty($langs)) {
            $langs = ['en'];
        }

        return array_values(array_unique($langs));
    }

    /** GET /accessibility/alt-text — coverage dashboard + authoring list. */
    public function executeIndex($request)
    {
        $this->requireAuth();

        $svc = new \AhgAccessibility\Service\AltTextService();
        $filters = [
            'missing' => (bool) $request->getParameter('missing', false),
            'q' => trim((string) $request->getParameter('q', '')),
            'page' => (int) $request->getParameter('page', 1),
        ];

        $this->counts = $svc->counts();
        $this->result = $svc->imageList($filters);
        $this->filters = $filters;
    }

    /** GET /accessibility/alt-text/edit/:id — author alt text for one digital object. */
    public function executeEdit($request)
    {
        $this->requireAuth();

        $doId = (int) $request->getParameter('id');
        $do = \Illuminate\Database\Capsule\Manager::table('digital_object')
            ->where('id', $doId)
            ->where('media_type_id', \AhgAccessibility\Service\AltTextService::MEDIA_TYPE_IMAGE)
            ->where('usage_id', \AhgAccessibility\Service\AltTextService::USAGE_MASTER)
            ->first(['id', 'name', 'object_id', 'path']);
        if (!$do) {
            $this->forward404('Image digital object not found');
        }

        $svc = new \AhgAccessibility\Service\AltTextService();
        $this->digitalObject = $do;
        $this->altMap = $svc->map($doId);
        $this->languages = $this->languages();

        $i18n = \Illuminate\Database\Capsule\Manager::table('information_object_i18n')
            ->where('id', $do->object_id)->where('culture', 'en')->first(['title']);
        $this->recordTitle = $i18n ? $i18n->title : null;
        $this->recordSlug = \Illuminate\Database\Capsule\Manager::table('slug')
            ->where('object_id', $do->object_id)->value('slug');
    }

    /** POST /accessibility/alt-text/save — persist the edit form. */
    public function executeSave($request)
    {
        $this->requireAuth();

        $doId = (int) $request->getParameter('digital_object_id');
        $alt = (array) $request->getParameter('alt', []);
        if ($doId > 0) {
            $svc = new \AhgAccessibility\Service\AltTextService();
            foreach ($this->languages() as $lang) {
                $svc->set($doId, $lang, (string) ($alt[$lang] ?? ''), $this->userId());
            }
        }

        $this->redirect('/accessibility/alt-text/edit/' . $doId);

        return;
    }

    /** GET /accessibility/alt-text/api/object/:id — alt map for one digital object. */
    public function executeApiObject($request)
    {
        $doId = (int) $request->getParameter('id');
        $svc = new \AhgAccessibility\Service\AltTextService();

        return $this->renderJson(['digital_object_id' => $doId, 'alt' => $svc->map($doId)]);
    }

    /** GET /accessibility/alt-text/api/slug/:slug — alt for every image master on a record. */
    public function executeApiSlug($request)
    {
        $slug = (string) $request->getParameter('slug');
        $row = \Illuminate\Database\Capsule\Manager::table('slug')
            ->where('slug', $slug)->first(['object_id']);
        if (!$row) {
            return $this->renderJson(['slug' => $slug, 'images' => []]);
        }

        $svc = new \AhgAccessibility\Service\AltTextService();
        $images = [];
        foreach ($svc->forInformationObject((int) $row->object_id) as $d) {
            $images[] = [
                'digital_object_id' => (int) $d->id,
                'name' => $d->name,
                'alt' => $d->alt,
            ];
        }

        return $this->renderJson(['slug' => $slug, 'object_id' => (int) $row->object_id, 'images' => $images]);
    }
}
