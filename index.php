<?php

define("APP_DEFAULT_MODULE", 'main');
define('CACHE_DIR', __DIR__ . '/cache');
ini_set('memory_limit', "256M");

require_once 'init.php';

try {
    $application = new Application(['session.start' => false, 'modules.use_ns' => true]);
    $application->run();
} catch (ApplicationException $e) {
    echo 'Site is currently down';
    trigger_error("Core Application Error Occured: \n" . $e->getTraceAsString() . "\n", E_USER_ERROR);
} catch (Exception $e1) {
    try {
        $request = $application ? $application->getClientRequest() : new ClientHttpRequest(array());
        //log this
        error_log("Exception occured: {$e1->getMessage()}, Trace: \n{$e1->getTraceAsString()}");
        //send to 500 page. can be customized by creating views/error.error500.tpl file.
        $request->redirect500($e1);
    } catch (Exception $e2) {
        echo "System Offline, please contact administrator";
        $exceptions = array($e1, $e2);
        foreach ($exceptions as $e) {
            if (ENVIRONMENT_DEV == SYSTEM_ENV) {
                echo "<pre style='width : 1000px; margin: 5px auto; padding : 10px; overflow: auto;'>",
                get_class($e), ": ",
                $e->getMessage(),
                PHP_EOL, $e->getTraceAsString(),
                "</pre>";
            } else {
                trigger_error("System Error, Exception Message ({$e->getMessage()}):<br/> " . nl2br($e->getTraceAsString()), E_USER_WARNING);
            }
        }
    }
}