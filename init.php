<?php

if (version_compare(PHP_VERSION, '5.4.0') < 0) {
    die("Deployment environment requires PHP 5.4 and above...");
}

$hostConfigMap = array(
    '/^localhost$/' => "dev",
    '/^jto$/' => "test",
    '/^192\.169\.42\.186$/' => "test",
    '/^192\.168\./' => "dev",
    '/pbmovies\.orilogbon\.me$/' => "live",
);

$__envConfig = false;

if (isset($_SERVER['HTTP_HOST'])) {
    $__host = $_SERVER['HTTP_HOST'];
    foreach ($hostConfigMap as $pattern => $appEnv) {
        if (preg_match($pattern, $__host)) {
            $__envConfig = $appEnv;
            break;
        }
    }
}

if ($__envConfig === false) {
    $__envConfig = getenv('DEFAULT_CONFIG') ?: 'live';
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

//You can also load environmental valriables from .environment file in your application root.
__init_env();

include_once 'vendor/autoload.php';

function __init_env()
{
    if (file_exists(__DIR__ . '/.environment')) {
        $vals = file(__DIR__ . '/.environment');
        foreach ($vals as $envLine) {
            list($var, $value) = explode('=', trim($envLine));
            if ($var && $value) {
                putenv("{$var}={$value}");
                $_ENV[$var] = $value;
            }
        }
    }
}