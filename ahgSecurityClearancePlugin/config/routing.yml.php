<?php

/**
 * Security Clearance Plugin Routing.
 */

// User security clearance
$this->addRoute(
    'user_security',
    new sfRoute(
        '/admin/users/:slug/security',
        ['module' => 'arSecurityClearance', 'action' => 'user']
    )
);

// Information Object security routes
$this->addRoute(
    'informationobject_security',
    new QubitResourceRoute(
        '/:slug/security',
        ['module' => 'arSecurityClearance', 'action' => 'object'],
        ['model' => 'QubitInformationObject']
    )
);

$this->addRoute(
    'informationobject_classify',
    new QubitResourceRoute(
        '/:slug/security/classify',
        ['module' => 'arSecurityClearance', 'action' => 'classify'],
        ['model' => 'QubitInformationObject']
    )
);

$this->addRoute(
    'informationobject_declassify',
    new QubitResourceRoute(
        '/:slug/security/declassify',
        ['module' => 'arSecurityClearance', 'action' => 'declassify'],
        ['model' => 'QubitInformationObject']
    )
);

// Security Dashboard (admin)
$this->addRoute(
    'security_dashboard',
    new sfRoute(
        '/admin/security',
        ['module' => 'arSecurityClearance', 'action' => 'dashboard']
    )
);

// User Clearances Management
$this->addRoute(
    'security_clearances',
    new sfRoute(
        '/admin/security/clearances',
        ['module' => 'arSecurityClearance', 'action' => 'clearances']
    )
);

$this->addRoute(
    'security_clearance_grant',
    new sfRoute(
        '/admin/security/clearances/grant',
        ['module' => 'arSecurityClearance', 'action' => 'grant']
    )
);

$this->addRoute(
    'security_clearance_edit',
    new sfRoute(
        '/admin/security/clearances/:userId/edit',
        ['module' => 'arSecurityClearance', 'action' => 'editClearance'],
        ['userId' => '\d+']
    )
);

// Classified Objects Browse
$this->addRoute(
    'security_objects',
    new sfRoute(
        '/admin/security/objects',
        ['module' => 'arSecurityClearance', 'action' => 'objects']
    )
);

// Security Audit Log
$this->addRoute(
    'security_audit',
    new sfRoute(
        '/admin/security/audit',
        ['module' => 'arSecurityClearance', 'action' => 'audit']
    )
);
