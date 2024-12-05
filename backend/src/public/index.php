<?php

/**
 * @param int     $severity
 * @param string  $message
 * @param string  $file
 * @param int     $line
 * @param mixed[] $options
 *
 * @return never
 */
function log_errors($severity, $message, $file, $line, $options = []) {
    $_SERVER['LOG_LEVEL'] = 'DEBUG';
    $_SERVER['APP_ROOT'] = __DIR__ . '/..';
    $logger = new Shipyard\Log('root-logger');
    $logger->critical("\n\nCRITICAL ERROR!\n===============\n\n" . $message . "\n" . $file . ':' . $line . "\n\n");
    include __DIR__ . '/error.html';
    exit;
}
/**
 * @return never
 */
function log_fatal() {
    $file = 'unknown file';
    $message  = 'shutdown';
    $severity   = E_CORE_ERROR;
    $line = 0;

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
    require __DIR__ . '/../bootstrap.php';

    $app = (new Shipyard\App())->get();
    $app->run();
} catch (Exception $e) {
    log_errors(E_ERROR, $e->getMessage(), $e->getFile(), $e->getLine());
}
