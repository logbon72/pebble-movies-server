<?php

if (version_compare(PHP_VERSION, '5.4.0') < 0) {
    die("Deployment environment requires PHP 5.4 and above...");
}

$hostConfigMap = array(
    '/^localhost$/' => "dev",
    '/^.*testvisacover\.com/' => "dev",
);

$__host = $_SERVER['HTTP_HOST'];
$__envConfig = false;
foreach ($hostConfigMap as $pattern => $appEnv) {
    if (preg_match($pattern, $__host)) {
        $__envConfig = $appEnv;
        break;
    }
}

if ($__envConfig === false) {
    $__envConfig = getenv('DEFAULT_CONFIG') ? : 'live';
}

putenv("CONFIG={$__envConfig}");
$_ENV['CONFIG'] = $__envConfig;

/**
 * Root of the application
 */
define('APP_ROOT', __DIR__ . DIRECTORY_SEPARATOR);

/**
 * Directory ao application folder, where all the magic happens
 */
define('APP_DIR', APP_ROOT . 'application' . DIRECTORY_SEPARATOR);
/**
 * Path to Libraries
 */
define('LIB_DIR', APP_DIR . 'library' . DIRECTORY_SEPARATOR);