<?php

/**
 * Popular this week stub.
 * Returns NONE if QubitAccessLog is unavailable (standalone mode).
 */
class DefaultPopularComponent extends sfComponent
{
    public function execute($request)
    {
        if (!class_exists('QubitAccessLog')) {
            return sfView::NONE;
        }

        $this->popularThisWeek = QubitAccessLog::getPopularThisWeek(
            ['limit' => isset($this->limit) ? $this->limit : 10]
        );

        if (0 == count($this->popularThisWeek)) {
            return sfView::NONE;
        }
    }
}
