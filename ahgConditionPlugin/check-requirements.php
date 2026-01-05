<?php
/**
 * ahgConditionPlugin Requirements Check
 * Run this before installation: php check-requirements.php
 */

$requirements = [
    'php_version' => '8.1.0',
    'extensions' => ['gd', 'json', 'pdo_mysql'],
];

$errors = [];

// Check PHP version
if (version_compare(PHP_VERSION, $requirements['php_version'], '<')) {
    $errors[] = "PHP {$requirements['php_version']}+ required (found " . PHP_VERSION . ")";
}

// Check extensions
foreach ($requirements['extensions'] as $ext) {
    if (!extension_loaded($ext)) {
        $errors[] = "PHP extension '{$ext}' is required but not installed";
        
        // Provide install hint
        if ($ext === 'gd') {
            echo "  Install with: sudo apt-get install php" . PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION . "-gd\n";
        }
    }
}

if (empty($errors)) {
    echo "✓ All requirements met for ahgConditionPlugin\n";
    exit(0);
} else {
    echo "✗ Requirements not met:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    exit(1);
}
