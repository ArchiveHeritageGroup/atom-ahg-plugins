<?php

class translationComponents extends AhgComponents
{
    public function executeTranslateModal()
    {
        $this->objectId = (int)$this->getVar('objectId');

        // Query which cultures actually exist for this record
        $this->availableCultures = [];
        try {
            $rows = \Illuminate\Database\Capsule\Manager::table('information_object_i18n')
                ->where('id', $this->objectId)
                ->pluck('culture')
                ->toArray();
            $this->availableCultures = $rows;
        } catch (\Exception $e) {
            $this->availableCultures = [];
        }
    }
}
