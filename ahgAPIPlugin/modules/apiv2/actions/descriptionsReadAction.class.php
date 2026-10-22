<?php

require_once sfConfig::get('sf_plugins_dir').'/ahgAPIPlugin/lib/ApiVisibility.php';

use AtomFramework\Http\Controllers\AhgApiController;
class apiv2DescriptionsReadAction extends AhgApiController
{
    public function GET($request)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }

        $slug = $request->getParameter('slug');
        if (empty($slug)) {
            return $this->error(400, 'Bad Request', 'Slug parameter required');
        }

        // Hidden descriptions answer exactly like missing ones (ApiVisibility).
        if (!ApiVisibility::canSeeSlug((string) $slug, $this->getUser())) {
            return $this->error(404, 'Not Found', 'Description not found');
        }

        $full = filter_var($request->getParameter('full', false), FILTER_VALIDATE_BOOLEAN);

        if ($full) {
            $result = $this->repository->getFullDescription($slug);
        } else {
            $result = $this->repository->getDescriptionBySlug($slug);
        }

        if (!$result) {
            return $this->error(404, 'Not Found', "Description '{$slug}' not found");
        }

        if ($full) {
            $result = $this->limitFullToCaller($result);
        }

        // Custom fields (#202): staff keys see every field, other keys only the
        // fields marked visible to the public. Added before redaction, so the
        // privacy rules below apply to them too.
        if (class_exists('\\AtomFramework\\Services\\CustomFieldValues')) {
            $result['custom_fields'] = \AtomFramework\Services\CustomFieldValues::forObject(
                (int) $result['id'],
                'informationobject',
                !ApiVisibility::isStaff($this->getUser())
            );
        }

        // #130 refinement 2 - field-level redaction on the REST layer, using the
        // same authority as the web view so the two cannot drift. Both service
        // files must be required: the namespace is not autoloaded here, and a
        // missing require surfaces as a fatal that silently disables redaction.
        $dir = sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/';
        require_once $dir . 'RedactionAccess.php';
        require_once $dir . 'PrivacyRedactionService.php';

        if (!\ahgPrivacyPlugin\Service\RedactionAccess::apiMaySeeUnredacted($this->getUser(), $this->hasScope('admin'))) {
            $result = (new \ahgPrivacyPlugin\Service\PrivacyRedactionService())->redactPayload($result);
        }

        return $this->success($result);
    }

    /**
     * The full read listed every ancestor and counted every child, hidden or
     * not, so a published record under a draft or embargoed parent gave away the
     * parent's title. Its file links were unusable ("/uploads/" doubled, no file
     * name) and offered the master to any key. Now: only ancestors and children
     * the caller may see, and each file only where AtoM's own readMaster,
     * readReference or readThumbnail check allows it (#218, GLAM MCP).
     */
    private function limitFullToCaller(array $result): array
    {
        $user = $this->getUser();

        $result['hierarchy'] = array_values(array_filter(
            (array) ($result['hierarchy'] ?? []),
            fn ($a) => !empty($a['slug']) && ApiVisibility::canSeeSlug((string) $a['slug'], $user)
        ));

        $children = \Illuminate\Database\Capsule\Manager::table('information_object as io')
            ->where('io.parent_id', (int) $result['id']);
        ApiVisibility::applyToQuery($children, $user);
        $result['children_count'] = $children->count();

        $resource = \QubitInformationObject::getById((int) $result['id']);
        $files = [];
        foreach ((array) ($result['digital_objects'] ?? []) as $do) {
            $rows = \Illuminate\Database\Capsule\Manager::table('digital_object')
                ->where('id', (int) $do['id'])->orWhere('parent_id', (int) $do['id'])
                ->get(['usage_id', 'path', 'name'])->keyBy('usage_id');
            $url = function (int $usage, string $action) use ($rows, $resource) {
                $row = $rows[$usage] ?? null;

                return $row && $row->path && $resource && \QubitAcl::check($resource, $action) ? $row->path.$row->name : null;
            };
            $master = $url(\QubitTerm::MASTER_ID, 'readMaster');
            $files[] = [
                'id' => $do['id'],
                'name' => $master ? $do['name'] : null,
                'mime_type' => $do['mime_type'],
                'byte_size' => $master ? $do['byte_size'] : null,
                'checksum' => $master ? $do['checksum'] : null,
                'thumbnail_url' => $url(\QubitTerm::THUMBNAIL_ID, 'readThumbnail'),
                'reference_url' => $url(\QubitTerm::REFERENCE_ID, 'readReference'),
                'master_url' => $master,
            ];
        }
        $result['digital_objects'] = $files;

        $result['provenance'] = $this->provenanceFor((int) $result['id'], ApiVisibility::isStaff($user));

        return $result;
    }

    /**
     * The record's provenance chain (ahgProvenancePlugin), when it is marked public
     * or the caller is staff, as the record page shows it. Sale prices and auction
     * details only for staff.
     */
    private function provenanceFor(int $objectId, bool $staff): ?array
    {
        $db = \Illuminate\Database\Capsule\Manager::class;
        try {
            $rec = $db::table('provenance_record')->where('information_object_id', $objectId)->first();
        } catch (\Throwable $e) {
            return null; // provenance plugin not installed
        }
        if (!$rec || (!$rec->is_public && !$staff)) {
            return null;
        }
        $summary = $db::table('provenance_record_i18n')->where('id', $rec->id)
            ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())->value('provenance_summary');
        $entries = [];
        foreach ($db::table('provenance_entry')->where('information_object_id', $objectId)->orderBy('sequence')->get() as $e) {
            $row = [
                'sequence' => (int) $e->sequence,
                'owner' => $e->owner_name, 'owner_type' => $e->owner_type, 'location' => $e->owner_location,
                'from' => trim(($e->start_date_qualifier ? $e->start_date_qualifier.' ' : '').$e->start_date) ?: null,
                'to' => trim(($e->end_date_qualifier ? $e->end_date_qualifier.' ' : '').$e->end_date) ?: null,
                'transfer' => $e->transfer_type, 'transfer_details' => $e->transfer_details,
                'certainty' => $e->certainty, 'sources' => $e->sources,
                'is_gap' => (bool) $e->is_gap, 'gap_explanation' => $e->gap_explanation,
            ];
            if ($staff) {
                $row += ['sale_price' => $e->sale_price, 'sale_currency' => $e->sale_currency, 'auction_house' => $e->auction_house, 'auction_lot' => $e->auction_lot];
            }
            $entries[] = $row;
        }

        // Events recorded on the provenance screen (ahgProvenancePlugin): transfers
        // between agents, each with its own public flag. The museum (CCO) screen
        // writes the owner chain above instead; a record may have either or both.
        $culture = \AtomExtensions\Helpers\CultureHelper::getCulture();
        $events = [];
        $q = $db::table('provenance_event as e')
            ->leftJoin('provenance_agent as fa', 'fa.id', '=', 'e.from_agent_id')
            ->leftJoin('provenance_agent as ta', 'ta.id', '=', 'e.to_agent_id')
            ->leftJoin('provenance_event_i18n as ei', function ($j) use ($culture) {
                $j->on('ei.id', '=', 'e.id')->where('ei.culture', '=', $culture);
            })
            ->where('e.provenance_record_id', $rec->id)
            ->orderBy('e.sequence_number')->orderBy('e.sort_order')->orderBy('e.event_date_start')
            ->select('e.*', 'fa.name as from_name', 'ta.name as to_name', 'ei.event_description', 'ei.evidence_description as evidence_i18n', 'ei.source_reference as source_i18n');
        if (!$staff) {
            $q->where('e.is_public', 1);
        }
        foreach ($q->get() as $e) {
            $date = $e->event_date_text ?: trim(($e->event_date_start ?: $e->event_date ?: '').($e->event_date_end && $e->event_date_end !== $e->event_date_start ? ' - '.$e->event_date_end : ''));
            $row = [
                'type' => $e->event_type, 'date' => $date ?: null, 'date_certainty' => $e->date_certainty,
                'from' => $e->from_name, 'to' => $e->to_name,
                'place' => implode(', ', array_filter([$e->event_location, $e->event_city, $e->event_country])) ?: null,
                'description' => $e->event_description, 'certainty' => $e->certainty,
                'evidence' => trim(($e->evidence_type ? $e->evidence_type.': ' : '').($e->evidence_i18n ?: $e->evidence_description)) ?: null,
                'source' => $e->source_i18n ?: $e->source_reference,
            ];
            if ($staff) {
                $row += ['price' => $e->price, 'currency' => $e->currency, 'sale_reference' => $e->sale_reference, 'notes' => $e->notes];
            }
            $events[] = $row;
        }

        return [
            'summary' => $summary, 'current_status' => $rec->current_status, 'acquisition_type' => $rec->acquisition_type,
            'certainty' => $rec->certainty_level, 'has_gaps' => (bool) $rec->has_gaps, 'is_complete' => (bool) $rec->is_complete,
            'entries' => $entries,
            'events' => $events,
        ];
    }
}
