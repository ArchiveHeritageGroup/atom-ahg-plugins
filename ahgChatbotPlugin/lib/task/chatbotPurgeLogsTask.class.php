<?php

/**
 * chatbot:purge-logs - delete logged conversations older than the retention
 * period (ahg_settings chatbot_retention_days, default 30). Run daily.
 */
class chatbotPurgeLogsTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('days', null, sfCommandOption::PARAMETER_OPTIONAL, 'Override the retention period in days'),
        ]);
        $this->namespace = 'chatbot';
        $this->name = 'purge-logs';
        $this->briefDescription = 'Delete Ask the Archive conversations past the retention period';
    }

    public function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        \AhgCore\Core\AhgDb::init();
        require_once __DIR__.'/../Services/ChatbotVectorIndex.php';
        require_once __DIR__.'/../Services/ChatbotRetriever.php';
        require_once __DIR__.'/../Services/ChatbotService.php';

        $days = null !== $options['days'] ? max(1, (int) $options['days']) : null;
        $removed = \AhgChatbotPlugin\Services\ChatbotService::purge($days);
        $this->logSection('chatbot', "Deleted {$removed} logged messages.");

        return 0;
    }
}
