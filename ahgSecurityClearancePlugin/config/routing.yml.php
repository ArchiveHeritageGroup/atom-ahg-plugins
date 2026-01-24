<?php

/**
 * Security Clearance Plugin Routing.
 */

// User security clearance
$this->addRoute(
    'user_security',
    new sfRoute(
        '/admin/users/:slug/security',
        ['module' => 'securityClearance', 'action' => 'user']
    )
);

// Information Object security routes
$this->addRoute(
    'informationobject_security',
    new QubitResourceRoute(
        '/:slug/security',
        ['module' => 'securityClearance', 'action' => 'object'],
        ['model' => 'QubitInformationObject']
    )
);

$this->addRoute(
    'informationobject_classify',
    new QubitResourceRoute(
        '/:slug/security/classify',
        ['module' => 'securityClearance', 'action' => 'classify'],
        ['model' => 'QubitInformationObject']
    )
);

$this->addRoute(
    'informationobject_declassify',
    new QubitResourceRoute(
        '/:slug/security/declassify',
        ['module' => 'securityClearance', 'action' => 'declassify'],
        ['model' => 'QubitInformationObject']
    )
);

// Security Dashboard (admin)
$this->addRoute(
    'security_dashboard',
    new sfRoute(
        '/admin/security',
        ['module' => 'securityClearance', 'action' => 'dashboard']
    )
);

// User Clearances Management
$this->addRoute(
    'security_clearances',
    new sfRoute(
        '/admin/security/clearances',
        ['module' => 'securityClearance', 'action' => 'clearances']
    )
);

$this->addRoute(
    'security_clearance_grant',
    new sfRoute(
        '/admin/security/clearances/grant',
        ['module' => 'securityClearance', 'action' => 'grant']
    )
);

$this->addRoute(
    'security_clearance_edit',
    new sfRoute(
        '/admin/security/clearances/:userId/edit',
        ['module' => 'securityClearance', 'action' => 'editClearance'],
        ['userId' => '\d+']
    )
);

// Classified Objects Browse
$this->addRoute(
    'security_objects',
    new sfRoute(
        '/admin/security/objects',
        ['module' => 'securityClearance', 'action' => 'objects']
    )
);

// Security Audit Log
$this->addRoute(
    'security_audit',
    new sfRoute(
        '/admin/security/audit',
        ['module' => 'securityClearance', 'action' => 'audit']
    )
);
