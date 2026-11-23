<?php

/**
 * Removes descriptions this user may not see from treeview node lists.
 * Fails closed: if the restriction check cannot run, no nodes are shown.
 */
class TreeviewVisibility
{
    /**
     * @param iterable<object> $items nodes with an id
     *
     * @return object[]
     */
    public static function filter($items): array
    {
        try {
            $user = \sfContext::getInstance()->getUser();
            $userId = $user->getAttribute('user_id');
            $hidden = array_flip(array_map('intval', \AtomExtensions\Services\Search\SearchAccessFilterService::getInstance()
                ->getRestrictedObjectIds($userId ? (int) $userId : null)));

            // Drafts are for staff; being signed in is not enough. Terms have no
            // publication status, so only descriptions are checked.
            $items = is_array($items) ? $items : iterator_to_array($items, false);
            if (!($user->isAuthenticated() && $user->hasCredential(['administrator', 'editor', 'contributor'], false))) {
                $ids = array_map(static fn ($item) => (int) $item->id, $items);
                $drafts = \Illuminate\Database\Capsule\Manager::table('information_object as io')
                    ->whereIn('io.id', $ids)
                    ->whereNotExists(function ($q) {
                        $q->select(\Illuminate\Database\Capsule\Manager::raw(1))->from('status as vis')
                            ->whereColumn('vis.object_id', 'io.id')
                            ->where('vis.type_id', 158)->where('vis.status_id', 160);
                    })
                    ->pluck('io.id');
                foreach ($drafts as $id) {
                    $hidden[(int) $id] = true;
                }
            }
        } catch (\Throwable $e) {
            error_log('treeview.visibility_failed: '.$e->getMessage());

            return [];
        }

        $visible = [];
        foreach ($items as $item) {
            if (!isset($hidden[(int) $item->id])) {
                $visible[] = $item;
            }
        }

        return $visible;
    }
}
