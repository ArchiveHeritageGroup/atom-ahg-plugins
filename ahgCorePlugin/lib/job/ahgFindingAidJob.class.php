<?php

/**
 * Base AtoM's finding aid job, generating through the AHG generator so the
 * PDF carries custom fields (#202). Delete and upload stay base behaviour.
 * Registered for the Gearman workers in ahgCorePlugin/config/gearman.yml.
 */
class ahgFindingAidJob extends arFindingAidJob
{
    public function runJob($parameters)
    {
        $wantsGeneration = empty($parameters['delete']) && !isset($parameters['uploadPath']);
        if (!$wantsGeneration || !class_exists('\\AtomFramework\\FindingAid\\AhgFindingAidGenerator')) {
            return parent::runJob($parameters);
        }

        $resource = QubitInformationObject::getById($parameters['objectId']);
        if (!isset($resource) || !isset($resource->parent)) {
            $this->error($this->i18n->__('Error: Could not find an information object with id: %1', ['%1' => $parameters['objectId']]));

            return false;
        }

        $generator = new \AtomFramework\FindingAid\AhgFindingAidGenerator($resource);
        $generator->setLogger($this->logger);
        $generator->setAuthLevel(QubitFindingAidGenerator::getPublicSetting());
        $generator->setFormat(QubitFindingAidGenerator::getFormatSetting());
        $generator->setModel(QubitFindingAidGenerator::getModelSetting());
        if (!$generator->generate()) {
            return false;
        }

        $this->job->setStatusCompleted();
        $this->job->save();

        return true;
    }

    /** Latest finding aid job status for a record, from either job name. */
    public static function getStatus($id)
    {
        $sql = 'SELECT j.status_id as statusId
            FROM job j JOIN object o ON j.id = o.id
            WHERE j.name IN (?, ?) AND j.object_id = ?
            ORDER BY o.created_at DESC';
        $ret = QubitPdo::fetchOne($sql, ['arFindingAidJob', 'ahgFindingAidJob', $id]);

        return $ret ? (int) $ret->statusId : null;
    }
}
