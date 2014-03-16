<?php

require_once 'init.php';
define('UPLOADS_DIR', realpath('uploads') . DIRECTORY_SEPARATOR);
 
ini_set('memory_limit', "256M");

require_once APP_DIR . "library/framework/Application.php";
//require_once APP_DIR . 'SessionInit.php';
define("APP_DEFAULT_MODULE", 'main');

try {
    $application = new Application(array('session.start' => false));
    $application->getClientRequest()
            ->setUseModuleNamespace(true);

    $application->run();
} catch (ApplicationException $e) {
    echo 'Site is currently down';
    trigger_error("Core Application Error Occured: \n" . $e->getTraceAsString() . "\n", E_USER_ERROR);
} catch (Exception $e) {
    try {
        if (!$application) {
            $request = new ClientHttpRequest(array());
        } else {
            $request = $application->getClientRequest();
        }
        $request->redirect500($e);
    } catch (Exception $e) {
        echo "System Offline, please contact administrator";
        if (ENVIRONMENT_DEV == SYSTEM_ENV) {
            echo "<pre style='width : 1000px; margin: 5px auto; padding : 10px; overflow: auto;'>",
            get_class($e), ": ",
            $e->getMessage(),
            PHP_EOL, $e->getTraceAsString(),
            "</pre>";
        } else {
            trigger_error("System Error, Exception Message ({$e->getMessage()}):<br/> " . nl2br($e->getTraceAsString()), E_USER_ERROR);
        }
    }
}
