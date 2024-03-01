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
set_error_handler('log_errors');
try {
    require __DIR__ . '/../bootstrap.php';

    $app = (new Shipyard\App())->get();
    $app->run();
} catch (Exception $e) {
    log_errors(E_ERROR, $e->getMessage(), $e->getFile(), $e->getLine());
}
