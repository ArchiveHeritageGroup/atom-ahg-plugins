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
            $userId = \sfContext::getInstance()->getUser()->getAttribute('user_id');
            $hidden = array_flip(array_map('intval', \AtomExtensions\Services\Search\SearchAccessFilterService::getInstance()
                ->getRestrictedObjectIds($userId ? (int) $userId : null)));
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
