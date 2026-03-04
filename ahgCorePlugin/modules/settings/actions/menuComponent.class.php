<?php

/**
 * Settings menu component stub.
 * Renders the admin settings sidebar navigation.
 */
class SettingsMenuComponent extends sfComponent
{
    public function execute($request)
    {
        $title = $this->context->i18n->__(ucfirst(implode(' ', array_map('strtolower', preg_split('/(?=[A-Z])/', $this->context->getActionName())))));
        $this->response->setTitle("{$title} setting - {$this->response->getTitle()}");

        $i18n = $this->context->i18n;
        $this->nodes = [
            ['label' => $i18n->__('Clipboard'), 'action' => 'clipboard'],
            ['label' => $i18n->__('CSV Validator'), 'action' => 'csvValidator'],
            ['label' => $i18n->__('Default page elements'), 'action' => 'pageElements'],
            ['label' => $i18n->__('Default template'), 'action' => 'template'],
            ['label' => $i18n->__('Diacritics'), 'action' => 'diacritics'],
            ['label' => $i18n->__('Digital object derivatives'), 'action' => 'digitalObjectDerivatives'],
            ['label' => $i18n->__('DIP upload'), 'action' => 'dipUpload'],
            ['label' => $i18n->__('Finding Aid'), 'action' => 'findingAid'],
            ['label' => $i18n->__('Global'), 'action' => 'global'],
            ['label' => $i18n->__('Header customizations'), 'action' => 'header'],
            ['label' => $i18n->__('I18n languages'), 'action' => 'language'],
            ['label' => $i18n->__('Identifiers'), 'action' => 'identifier'],
            ['label' => $i18n->__('Inventory'), 'action' => 'inventory'],
            ['label' => $i18n->__('LDAP Authentication'), 'action' => 'ldap', 'hide' => !($this->context->user instanceof ldapUser)],
            ['label' => $i18n->__('Markdown'), 'action' => 'markdown'],
            ['label' => $i18n->__('OAI repository'), 'action' => 'oai', 'hide' => !$this->context->getConfiguration()->isPluginEnabled('arOaiPlugin')],
            ['label' => $i18n->__('Permissions'), 'action' => 'permissions'],
            ['label' => $i18n->__('Privacy Notification'), 'action' => 'privacyNotification'],
            ['label' => $i18n->__('Security'), 'action' => 'security'],
            ['label' => $i18n->__('Site information'), 'action' => 'siteInformation'],
            ['label' => $i18n->__('Storage service'), 'module' => 'arStorageServiceSettings', 'action' => 'settings', 'hide' => !$this->context->getConfiguration()->isPluginEnabled('arStorageServicePlugin')],
            ['label' => $i18n->__('Treeview'), 'action' => 'treeview'],
            ['label' => $i18n->__('Uploads'), 'action' => 'uploads'],
            ['label' => $i18n->__('User interface labels'), 'action' => 'interfaceLabel'],
            ['label' => $i18n->__('Web analytics'), 'action' => 'analytics'],
            // AHG Extensions
            ['label' => $i18n->__('AHG Settings'), 'module' => 'ahgSettings', 'action' => 'index', 'hide' => !$this->context->getConfiguration()->isPluginEnabled('ahgSettingsPlugin')],
            ['label' => $i18n->__('AI Condition Assessment'), 'module' => 'aiCondition', 'action' => 'index', 'hide' => !$this->context->getConfiguration()->isPluginEnabled('ahgAiConditionPlugin')],
            ['label' => $i18n->__('Dropdown Manager'), 'module' => 'ahgDropdown', 'action' => 'index', 'hide' => !$this->context->getConfiguration()->isPluginEnabled('ahgSettingsPlugin')],
        ];

        foreach ($this->nodes as $i => &$node) {
            if (!empty($node['hide']) && true === $node['hide']) {
                unset($this->nodes[$i]);
            }
            $node['active'] = $this->context->getActionName() === $node['action'];
        }

        usort($this->nodes, function ($el1, $el2) {
            return strnatcmp($el1['label'], $el2['label']);
        });
    }
}
