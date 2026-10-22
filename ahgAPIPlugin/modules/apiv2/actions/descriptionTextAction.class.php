<?php

require_once sfConfig::get('sf_plugins_dir').'/ahgAPIPlugin/lib/ApiVisibility.php';

use AtomFramework\Http\Controllers\AhgApiController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * GET /api/v2/descriptions/:slug/text?start=0&chars=20000
 *
 * The text held for a description's digital objects, for reading by people and
 * agents (GLAM MCP, #218): text AtoM extracted from the file (the "transcript"
 * property, e.g. PDF text or OCR), IIIF OCR, and audio/video transcriptions.
 *
 * The text is derived from the file, so it is offered only where the caller may
 * see the record (ApiVisibility) and view its reference copy (AtoM readReference).
 * Field redaction rules apply under the field name "transcript"; the privacy
 * plugin's response filter also masks personal information in the JSON output.
 * Long text is paged by character offset.
 */
class apiv2DescriptionTextAction extends AhgApiController
{
    private const MAX_LIMIT = 50000;

    public function GET($request)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }
        $slug = (string) $request->getParameter('slug');
        if (!ApiVisibility::canSeeSlug($slug, $this->getUser())) {
            return $this->error(404, 'Not Found', 'Description not found');
        }
        $ioId = (int) DB::table('slug')->where('slug', $slug)->value('object_id');
        $resource = \QubitInformationObject::getById($ioId);
        if (!$resource || !\QubitAcl::check($resource, 'readReference')) {
            return $this->success(['slug' => $slug, 'texts' => [], 'note' => 'You may not view this record\'s files.']);
        }

        // Own parameter names: "limit" and "offset" are taken over by the shared
        // API paging before they reach this action (any limit came back as 10).
        $offset = max(0, (int) $request->getParameter('start', 0));
        $limit = max(1, min((int) $request->getParameter('chars', 20000), self::MAX_LIMIT));
        $doIds = DB::table('digital_object')->where('object_id', $ioId)->whereNull('parent_id')->pluck('id', 'id')->all();

        $sources = [];
        if ($doIds) {
            foreach (DB::table('property as p')->join('property_i18n as pi', 'pi.id', '=', 'p.id')
                ->whereIn('p.object_id', array_keys($doIds))->where('p.name', 'transcript')
                ->get(['p.object_id', 'pi.culture', 'pi.value']) as $r) {
                $sources[] = ['source' => 'extracted text', 'digital_object_id' => (int) $r->object_id, 'language' => $r->culture, 'text' => (string) $r->value];
            }
            foreach (DB::table('iiif_ocr_text')->whereIn('digital_object_id', array_keys($doIds))
                ->get(['digital_object_id', 'language', 'full_text']) as $r) {
                $sources[] = ['source' => 'ocr', 'digital_object_id' => (int) $r->digital_object_id, 'language' => $r->language, 'text' => (string) $r->full_text];
            }
            foreach (DB::table('media_transcription')->whereIn('digital_object_id', array_keys($doIds))
                ->get(['digital_object_id', 'language', 'full_text']) as $r) {
                $sources[] = ['source' => 'media transcription', 'digital_object_id' => (int) $r->digital_object_id, 'language' => $r->language, 'text' => (string) $r->full_text];
            }
        }

        $dir = sfConfig::get('sf_plugins_dir').'/ahgPrivacyPlugin/lib/Service/';
        $redact = null;
        if (is_file($dir.'PrivacyRedactionService.php')) {
            require_once $dir.'RedactionAccess.php';
            require_once $dir.'PrivacyRedactionService.php';
            if (!\ahgPrivacyPlugin\Service\RedactionAccess::apiMaySeeUnredacted($this->getUser(), $this->hasScope('admin'))) {
                $redact = new \ahgPrivacyPlugin\Service\PrivacyRedactionService();
            }
        }

        $texts = [];
        foreach ($sources as $s) {
            $full = trim($s['text']);
            if ('' === $full) {
                continue;
            }
            if ($redact) {
                $full = (string) ($redact->redactPayload(['id' => $ioId, 'transcript' => $full])['transcript'] ?? $full);
            }
            $length = mb_strlen($full);
            $texts[] = [
                'source' => $s['source'],
                'digital_object_id' => $s['digital_object_id'],
                'language' => $s['language'],
                'length' => $length,
                'offset' => $offset,
                'text' => mb_substr($full, $offset, $limit),
                'more' => $offset + $limit < $length,
            ];
        }

        return $this->success(['slug' => $slug, 'texts' => $texts]);
    }
}
