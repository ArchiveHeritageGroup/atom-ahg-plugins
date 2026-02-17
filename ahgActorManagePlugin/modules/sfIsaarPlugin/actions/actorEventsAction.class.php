<?php

use AtomFramework\Http\Controllers\AhgController;
/*
 * This file is part of the Access to Memory (AtoM) software.
 *
 * Access to Memory (AtoM) is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Access to Memory (AtoM) is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Access to Memory (AtoM).  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Actor - show event data as JSON.
 */
class sfIsaarPluginActorEventsAction extends AhgController
{
    public function execute($request)
    {
        if (empty($request->slug)) {
            $this->getResponse()->setStatusCode(400);
            $errorMessage = $this->getContext()->i18n->__('Slug must be provided');

            return $this->renderText(json_encode(['error' => $errorMessage]));
        }

        $actor = QubitActor::getBySlug($request->slug);

        if (class_exists('Criteria')) {
            $criteria = new Criteria();
            $criteria->add(QubitEvent::ACTOR_ID, $actor->id);

            $data = [];
            $data['total'] = count(QubitEvent::get($criteria));

            $criteria->setOffset($request->skip);
            $criteria->setLimit($request->limit);

            $data['data'] = $this->assembleEventData($criteria);
        } else {
            $db = \Illuminate\Database\Capsule\Manager::class;
            $data = [];
            $data['total'] = $db::table('event')->where('actor_id', $actor->id)->count();

            $rows = $db::table('event')
                ->where('actor_id', $actor->id)
                ->offset((int) $request->skip)
                ->limit((int) $request->limit)
                ->get();

            $data['data'] = $this->assembleEventDataFromRows($rows);
        }

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');

        return $this->renderText(json_encode($data));
    }

    private function assembleEventData($criteria)
    {
        $events = [];

        foreach (QubitEvent::get($criteria) as $event) {
            $eventData = [
                'url' => url_for([$event, 'module' => 'event']),
                'title' => render_title($event->object),
                'type' => render_value_inline($event->type),
                'date' => render_value_inline(Qubit::renderDateStartEnd($event->date, $event->startDate, $event->endDate)),
            ];

            array_push($events, $eventData);
        }

        return $events;
    }

    private function assembleEventDataFromRows($rows)
    {
        $db = \Illuminate\Database\Capsule\Manager::class;
        $events = [];

        foreach ($rows as $row) {
            // Resolve event type name
            $typeName = '';
            if ($row->type_id) {
                $typeRow = $db::table('term_i18n')
                    ->where('id', $row->type_id)
                    ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
                    ->first();
                $typeName = $typeRow->name ?? '';
            }

            // Resolve information object title
            $title = '';
            $slug = '';
            if ($row->information_object_id) {
                $ioRow = $db::table('information_object_i18n')
                    ->where('id', $row->information_object_id)
                    ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
                    ->first();
                $title = $ioRow->title ?? '';

                $slugRow = $db::table('slug')
                    ->where('object_id', $row->information_object_id)
                    ->first();
                $slug = $slugRow->slug ?? '';
            }

            // Build date string
            $dateStr = $row->date ?? '';
            if (!$dateStr && ($row->start_date || $row->end_date)) {
                $dateStr = trim(($row->start_date ?? '') . ' - ' . ($row->end_date ?? ''), ' -');
            }

            $eventSlug = $db::table('slug')->where('object_id', $row->id)->value('slug') ?? '';

            $events[] = [
                'url' => url_for(['module' => 'event', 'slug' => $eventSlug]),
                'title' => $title,
                'type' => $typeName,
                'date' => $dateStr,
            ];
        }

        return $events;
    }
}
