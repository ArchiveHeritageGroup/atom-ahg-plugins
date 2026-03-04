<?php

/**
 * Privacy notification banner stub.
 */
class DefaultPrivacyMessageComponent extends sfComponent
{
    public function execute($request)
    {
        if (
            null !== $this->context->user->getAttribute('privacy_message_dismissed')
            || !sfConfig::get('app_privacy_notification_enabled', false)
        ) {
            return sfView::NONE;
        }

        $this->notificationMessage = QubitSetting::getByName('privacy_notification');
    }
}
