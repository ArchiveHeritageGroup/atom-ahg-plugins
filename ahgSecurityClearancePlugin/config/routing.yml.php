<?php

/**
 * Security Clearance Plugin Routing.
 */

// User security clearance
$this->addRoute(
    'user_security',
    new sfRoute(
        '/admin/users/:slug/security',
        ['module' => 'ahgSecurityClearance', 'action' => 'user']
    )
);

// Information Object security routes
$this->addRoute(
    'informationobject_security',
    new QubitResourceRoute(
        '/:slug/security',
        ['module' => 'ahgSecurityClearance', 'action' => 'object'],
        ['model' => 'QubitInformationObject']
    )
);

$this->addRoute(
    'informationobject_classify',
    new QubitResourceRoute(
        '/:slug/security/classify',
        ['module' => 'ahgSecurityClearance', 'action' => 'classify'],
        ['model' => 'QubitInformationObject']
    )
);

$this->addRoute(
    'informationobject_declassify',
    new QubitResourceRoute(
        '/:slug/security/declassify',
        ['module' => 'ahgSecurityClearance', 'action' => 'declassify'],
        ['model' => 'QubitInformationObject']
    )
);

// Security Dashboard (admin)
$this->addRoute(
    'security_dashboard',
    new sfRoute(
        '/admin/security',
        ['module' => 'ahgSecurityClearance', 'action' => 'dashboard']
    )
);

// User Clearances Management
$this->addRoute(
    'security_clearances',
    new sfRoute(
        '/admin/security/clearances',
        ['module' => 'ahgSecurityClearance', 'action' => 'clearances']
    )
);

$this->addRoute(
    'security_clearance_grant',
    new sfRoute(
        '/admin/security/clearances/grant',
        ['module' => 'ahgSecurityClearance', 'action' => 'grant']
    )
);

$this->addRoute(
    'security_clearance_edit',
    new sfRoute(
        '/admin/security/clearances/:userId/edit',
        ['module' => 'ahgSecurityClearance', 'action' => 'editClearance'],
        ['userId' => '\d+']
    )
);

// Classified Objects Browse
$this->addRoute(
    'security_objects',
    new sfRoute(
        '/admin/security/objects',
        ['module' => 'ahgSecurityClearance', 'action' => 'objects']
    )
);

// Security Audit Log
$this->addRoute(
    'security_audit',
    new sfRoute(
        '/admin/security/audit',
        ['module' => 'ahgSecurityClearance', 'action' => 'audit']
    )
);
