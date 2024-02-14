<?php

namespace Shipyard;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class Log {
    /**
     * The logger.
     *
     * @var Logger
     */
    private static $logger;

    /**
     * Instanciate the logger.
     *
     * @return Logger
     */
    public static function instanciateLogger() {
        if (!(isset($_SERVER['LOG_LEVEL']) || isset($_SERVER['LOG_FILE']))) {
            $log_level = 'OFF';
        } else {
            $log_level = isset($_SERVER['LOG_LEVEL']) ? strtoupper($_SERVER['LOG_LEVEL']) : 'INFO';
            $log_level = \in_array($log_level, ['DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY', 'OFF']) ? $log_level : 'INFO';
        }

        if ($log_level == 'OFF') {
            $stream = new NullHandler();
        } else {
            $log_file = isset($_SERVER['LOG_FILE']) ? $_SERVER['LOG_FILE'] : 'debug.log';
            $output = "%datetime%: [%channel%:%level_name%] > %message% %context% %extra%\n";
            $formatter = new LineFormatter($output, null, false, true);
            $stream = new StreamHandler($log_file, constant("\Monolog\Logger::$log_level"));
            $stream->setFormatter($formatter);
        }

        $logger = new Logger($_SERVER['APP_TITLE'] . ':main');
        $logger->pushHandler($stream);
        $logger->info("Logger initialized: {$log_level}");

        self::$logger = $logger;

        return $logger;
    }

    /**
     * @param string  $message The log message
     * @param mixed[] $context The log context
     *
     * @return void
     */
    public static function debug(string $message, array $context = []) {
        self::$logger->debug($message, $context);
    }

    /**
     * @param string  $message The log message
     * @param mixed[] $context The log context
     *
     * @return void
     */
    public static function info(string $message, array $context = []) {
        self::$logger->info($message, $context);
    }

    /**
     * @param string  $message The log message
     * @param mixed[] $context The log context
     *
     * @return void
     */
    public static function notice(string $message, array $context = []) {
        self::$logger->notice($message, $context);
    }

    /**
     * @param string  $message The log message
     * @param mixed[] $context The log context
     *
     * @return void
     */
    public static function warning(string $message, array $context = []) {
        self::$logger->warning($message, $context);
    }

    /**
     * @param string  $message The log message
     * @param mixed[] $context The log context
     *
     * @return void
     */
    public static function error(string $message, array $context = []) {
        self::$logger->error($message, $context);
    }

    /**
     * @param string  $message The log message
     * @param mixed[] $context The log context
     *
     * @return void
     */
    public static function critical(string $message, array $context = []) {
        self::$logger->critical($message, $context);
    }

    /**
     * @param string  $message The log message
     * @param mixed[] $context The log context
     *
     * @return void
     */
    public static function alert(string $message, array $context = []) {
        self::$logger->alert($message, $context);
    }

    /**
     * @param string  $message The log message
     * @param mixed[] $context The log context
     *
     * @return void
     */
    public static function emergency(string $message, array $context = []) {
        self::$logger->emergency($message, $context);
    }

    /**
     * @param string $name The channel name
     *
     * @return Logger
     */
    public static function channel(string $name) {
        return self::$logger->withName($_SERVER['APP_TITLE'] . ':' . $name);
    }
}
