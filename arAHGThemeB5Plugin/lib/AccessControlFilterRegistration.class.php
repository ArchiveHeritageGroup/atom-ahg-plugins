<?php

/**
 * Register access control filter with AtoM search
 *
 * Add to your plugin's configuration initialize() method:
 *   AccessControlFilterRegistration::register($this->dispatcher);
 */
class AccessControlFilterRegistration
{
    public static function register(sfEventDispatcher $dispatcher)
    {
        // Hook into search query building
        $dispatcher->connect('search.query.build', ['AccessControlSearchFilter', 'filterQuery']);
    }
}
