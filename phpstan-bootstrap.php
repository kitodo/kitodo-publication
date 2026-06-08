<?php

// Custom bootstrap file for TYPO3 PHPStan analysis
if (!defined('TYPO3_MODE')) {
    define('TYPO3_MODE', 'BE'); // Backend mode
}

// In DDEV the full TYPO3 installation provides autoload; skip to avoid double-registration
if (!getenv('IS_DDEV_PROJECT')) {
    require_once __DIR__ . '/vendor/autoload.php';
}
