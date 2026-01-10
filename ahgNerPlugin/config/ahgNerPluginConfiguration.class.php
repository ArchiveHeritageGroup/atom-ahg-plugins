<?php

class ahgNerPluginConfiguration extends sfPluginConfiguration
{
    public function initialize()
    {
        $this->dispatcher->connect('response.filter_content', [$this, 'addNerButton']);
    }

    public function addNerButton(sfEvent $event, $content)
    {
        // Add NER button to information object pages
        return $content;
    }
}
