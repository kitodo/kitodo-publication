<?php
// Minimal autoloader for unit tests that don't need TYPO3.
// Only loads EWW\Dpf classes from the Classes/ directory.
spl_autoload_register(function (string $class) {
    if (strpos($class, 'EWW\\Dpf\\') !== 0) {
        return;
    }
    $relative = str_replace('EWW\\Dpf\\', '', $class);
    $path = __DIR__ . '/../Classes/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
});
