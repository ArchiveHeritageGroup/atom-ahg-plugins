<?php
class ahgDisplayPluginConfiguration extends sfPluginConfiguration
{
    public static $summary = 'Context-aware display system for archives, museums, galleries, libraries and DAM';
    public static $version = '1.0.0';

    public function initialize()
    {
        $this->dispatcher->connect('routing.load_configuration', [$this, 'loadRoutes']);
        $this->dispatcher->connect('template.filter_parameters', [$this, 'onTemplateFilterParameters']);
        $this->dispatcher->connect('QubitInformationObject.save', [$this, 'onInformationObjectSave']);
        $this->dispatcher->connect('QubitInformationObject.insert', [$this, 'onInformationObjectSave']);

        $enabledModules = sfConfig::get('sf_enabled_modules', []);
        $enabledModules[] = 'ahgDisplay';
        $enabledModules[] = 'ahgDisplaySearch';
        sfConfig::set('sf_enabled_modules', $enabledModules);
    }

    /**
     * Check if ahgThemeB5Plugin is the active theme
     */
    private function isAhgThemeActive(): bool
    {
        try {
            $sql = "SELECT si.value FROM setting s 
                    JOIN setting_i18n si ON s.id = si.id 
                    WHERE s.name = 'plugins' AND s.scope IS NULL LIMIT 1";
            $value = QubitPdo::fetchColumn($sql);
            if ($value) {
                $plugins = unserialize($value);
                if (is_array($plugins) && in_array('ahgThemeB5Plugin', $plugins)) {
                    return true;
                }
            }
        } catch (Exception $e) {
            // Ignore errors during routing
        }
        return false;
    }

    public function loadRoutes(sfEvent $event)
    {
        $routing = $event->getSubject();

        // GLAM routes are always available at /glam/*
        $routing->prependRoute('glam_index', new sfRoute('/glam', ['module' => 'ahgDisplay', 'action' => 'index']));
        $routing->prependRoute('glam_browse', new sfRoute('/glam/browse', ['module' => 'ahgDisplay', 'action' => 'browse']));
        $routing->prependRoute('glam_print', new sfRoute('/glam/print', ['module' => 'ahgDisplay', 'action' => 'print']));
        $routing->prependRoute('glam_csv', new sfRoute('/glam/csv', ['module' => 'ahgDisplay', 'action' => 'exportCsv']));
        $routing->prependRoute('glam_change_type', new sfRoute('/glam/changeType', ['module' => 'ahgDisplay', 'action' => 'changeType']));
        $routing->prependRoute('glam_profiles', new sfRoute('/glam/profiles', ['module' => 'ahgDisplay', 'action' => 'profiles']));
        $routing->prependRoute('glam_levels', new sfRoute('/glam/levels', ['module' => 'ahgDisplay', 'action' => 'levels']));
        $routing->prependRoute('glam_fields', new sfRoute('/glam/fields', ['module' => 'ahgDisplay', 'action' => 'fields']));
        $routing->prependRoute('glam_set_type', new sfRoute('/glam/setType', ['module' => 'ahgDisplay', 'action' => 'setType']));
        $routing->prependRoute('glam_assign_profile', new sfRoute('/glam/assignProfile', ['module' => 'ahgDisplay', 'action' => 'assignProfile']));
        $routing->prependRoute('glam_bulk_set_type', new sfRoute('/glam/bulkSetType', ['module' => 'ahgDisplay', 'action' => 'bulkSetType']));

        // Only override /informationobject/browse when ahgThemeB5Plugin is active
        if ($this->isAhgThemeActive()) {
            $routing->prependRoute('informationobject_browse_redirect', new sfRoute('/informationobject/browse', ['module' => 'ahgDisplay', 'action' => 'browse']));
        }
    }

    public function onTemplateFilterParameters(sfEvent $event, $parameters)
    {
        if (!isset($parameters['resource']) || (!isset($parameters['resource']->id))) {
            return $parameters;
        }
        try {
            require_once __DIR__ . '/../lib/Services/DisplayTypeDetector.php';
            $objectId = (int) $parameters['resource']->id;
            if ($objectId > 1) {
                $parameters['display_type'] = DisplayTypeDetector::detect($objectId);
                $parameters['display_profile'] = DisplayTypeDetector::getProfile($objectId);
            }
        } catch (Exception $e) {
            error_log('ahgDisplayPlugin: ' . $e->getMessage());
        }
        return $parameters;
    }

    public function onInformationObjectSave(sfEvent $event)
    {
        $object = $event->getSubject();
        if (!$object || (!isset($object->id)) || $object->id <= 1) {
            return;
        }
        try {
            require_once __DIR__ . '/../lib/Services/DisplayTypeDetector.php';
            DisplayTypeDetector::detectAndSave((int) $object->id, true);
        } catch (Exception $e) {
            error_log('ahgDisplayPlugin save: ' . $e->getMessage());
        }
    }
}
