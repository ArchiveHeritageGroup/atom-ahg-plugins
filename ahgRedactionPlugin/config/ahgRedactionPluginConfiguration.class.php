<?php

/**
 * ahgRedactionPlugin - manual visual redaction of digital objects.
 *
 * Ported from ahgPrivacyPlugin's visual redaction editor, deliberately without the
 * privacy dashboard, the DSAR workflow, the PII scanner or the NER-driven
 * automatic redaction. What remains is the part an archivist drives by hand: open
 * a document, draw over what must not be seen, save, apply.
 */
class ahgRedactionPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Manual visual redaction of digital objects';
    public static $version = '1.0.0';

    public function initialize()
    {
        $this->dispatcher->connect('routing.load_configuration', [$this, 'addRoutes']);
        $this->dispatcher->connect('response.filter_content', [$this, 'addAssets']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'redaction';
        sfConfig::set('sf_enabled_modules', array_unique($enabledModules));
    }

    public function addRoutes(sfEvent $event)
    {
        $r = new \AtomFramework\Routing\RouteLoader('redaction');

        $r->any('redaction_editor', '/admin/redaction/:id', 'editor', ['id' => '\d+']);
        $r->any('redaction_get', '/redaction/regions', 'getRegions');
        $r->any('redaction_save', '/redaction/save', 'save');
        $r->any('redaction_delete', '/redaction/delete', 'delete');
        $r->any('redaction_apply', '/redaction/apply', 'apply');
        $r->any('redaction_document', '/redaction/document', 'document');

        $r->register($event->getSubject());
    }

    /**
     * Load the annotator only on the editor page.
     *
     * The paths carry /web/. The original omitted it - it asked for
     * /plugins/ahgPrivacyPlugin/css/redaction-annotator.css while the file sits at
     * /plugins/ahgPrivacyPlugin/web/css/... - so the stylesheet and the annotator
     * both 404'd and the editor rendered an inert canvas. That is still the case on
     * PSIS today; both URLs were checked.
     *
     * Fabric and PDF.js are vendored into this plugin rather than borrowed from
     * ahgCorePlugin and ahgIiifPlugin, so redaction works on an install that has
     * neither.
     */
    public function addAssets(sfEvent $event, $content)
    {
        try {
            $request = sfContext::getInstance()->getRequest();

            if ('redaction' !== $request->getParameter('module') || 'editor' !== $request->getParameter('action')) {
                return $content;
            }

            $base = '/plugins/ahgRedactionPlugin/web';
            $nonce = sfConfig::get('csp_nonce', '');
            $nonce = $nonce ? ' ' . preg_replace('/^nonce=/', 'nonce="', $nonce) . '"' : '';

            $head = '<link rel="stylesheet" href="'.$base.'/css/redaction-annotator.css">';
            // Scripts are not injected here. The editor template loads its own,
            // branching on whether the digital object is a PDF or an image, and a
            // second copy of PDF.js or Fabric would clobber the first.
            $content = str_replace('</head>', $head."\n</head>", $content);

            return $content;
        } catch (\Throwable $e) {
            // An editor that loses its toolbar is a bug; a filter that takes every
            // page down with it is an outage.
            return $content;
        }
    }
}
