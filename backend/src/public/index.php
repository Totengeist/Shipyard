<?php

/**
 * @param int    $severity
 * @param string $message
 * @param string $file
 * @param int    $line
 *
 * @return never
 */
function log_errors($severity, $message, $file, $line) {
    $_SERVER['LOG_LEVEL'] = 'DEBUG';
    $_SERVER['APP_ROOT'] = __DIR__ . '/..';
    $logger = new Shipyard\Log('root-logger');
    $logger->critical("\n\nCRITICAL ERROR! (Severity $severity)\n===============\n\n" . $message . "\n" . $file . ':' . $line . "\n\n");
    include_once __DIR__ . '/error.html';
    exit;
}
/**
 * @return never
 */
function log_fatal() {
    $error = error_get_last();

    if ($error !== null) {
        $severity = $error['type'];
        $file = $error['file'];
        $line = $error['line'];
        $message  = $error['message'];
        log_errors($severity, $message, $file, $line);
    }
    exit;
}
set_error_handler('log_errors');
register_shutdown_function('log_fatal');
try {
    require_once __DIR__ . '/../bootstrap.php';

    $app = (new Shipyard\App())->get();
    $app->run();
} catch (Exception $e) {
    log_errors(E_ERROR, $e->getMessage(), $e->getFile(), $e->getLine());
}
