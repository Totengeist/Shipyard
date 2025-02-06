<?php

namespace Shipyard;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * @method        void alert(string $message, mixed[] $context = [])
 * @method static void alert(string $message, mixed[] $context = [])
 * @method        void critical(string $message, mixed[] $context = [])
 * @method static void critical(string $message, mixed[] $context = [])
 * @method        void debug(string $message, mixed[] $context = [])
 * @method static void debug(string $message, mixed[] $context = [])
 * @method        void emergency(string $message, mixed[] $context = [])
 * @method static void emergency(string $message, mixed[] $context = [])
 * @method        void error(string $message, mixed[] $context = [])
 * @method static void error(string $message, mixed[] $context = [])
 * @method        void info(string $message, mixed[] $context = [])
 * @method static void info(string $message, mixed[] $context = [])
 * @method        void notice(string $message, mixed[] $context = [])
 * @method static void notice(string $message, mixed[] $context = [])
 * @method        void warning(string $message, mixed[] $context = [])
 * @method static void warning(string $message, mixed[] $context = [])
 */
class Log {
    /**
     * A globalized logger.
     *
     * @var Log
     */
    private static $global_logger;
    /**
     * The logger.
     *
     * @var Logger
     */
    private $logger;
    /**
     * The logging functions.
     *
     * @var string[]
     */
    private static $logger_functions = [
        'debug',
        'info',
        'notice',
        'warning',
        'error',
        'critical',
        'alert',
        'emergency'
    ];

    /**
     * Instanciate the logger.
     *
     * @param Logger|string|null $logger
     */
    public function __construct($logger = null) {
        if ($logger == null || is_string($logger)) {
            $log_level = self::get_log_level();

            $logger = new Logger(is_string($logger) ? $logger : $_SERVER['APP_TITLE'] . ':main');
            $logger->pushHandler(self::get_stream_handler($log_level));
            $logger->debug("Logger initialized: {$log_level}");
        }

        $this->logger = $logger;
    }

    /**
     * @return void
     */
    public function setAsGlobal() {
        self::$global_logger = $this;
    }

    /**
     * @param string $log_level the lowest level of alert to log
     *
     * @return NullHandler|StreamHandler
     */
    public static function get_stream_handler($log_level = null) {
        if ($log_level == null) {
            $log_level = self::get_log_level();
        }
        if ($log_level == 'OFF') {
            $stream = new NullHandler();
        } else {
            $log_file = self::get_log_file();
            $format = "%datetime%: [%channel%:%level_name%] > %message% %context% %extra%\n";
            $formatter = new LineFormatter($format, null, true, true);
            $stream = new StreamHandler($log_file, constant("\Monolog\Logger::$log_level"));
            $stream->setFormatter($formatter);
        }

        return $stream;
    }

    /**
     * @return string
     */
    public static function get_log_level() {
        $log_level = 'OFF';
        if (isset($_SERVER['LOG_LEVEL']) || isset($_SERVER['LOG_FILE'])) {
            $log_level = isset($_SERVER['LOG_LEVEL']) ? strtoupper($_SERVER['LOG_LEVEL']) : 'INFO';
            $log_level = \in_array($log_level, ['DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY', 'OFF']) ? $log_level : 'INFO';
        }

        return $log_level;
    }

    /**
     * @return string
     */
    public static function get_log_file() {
        if (isset($_SERVER['LOG_FILE'])) {
            $path_info = pathinfo($_SERVER['LOG_FILE']);
            if (isset($path_info['dirname']) && $path_info['dirname'][0] == '.') {
                return $_SERVER['APP_ROOT'] . '/' . $_SERVER['LOG_FILE'];
            }

            return $_SERVER['LOG_FILE'];
        }

        return $_SERVER['APP_ROOT'] . '/debug.log';
    }

    /**
     * @param string  $name      the logging function name
     * @param mixed[] $arguments the arguments to the function
     *
     * @return void
     */
    public static function __callStatic($name, $arguments) {
        if (!in_array($name, self::$logger_functions) || count($arguments)<1) {
            throw new \BadMethodCallException();
        }

        self::$global_logger->$name(...$arguments);
    }

    /**
     * @param string  $name      the logging function name
     * @param mixed[] $arguments the arguments to the function
     *
     * @return void
     */
    public function __call($name, $arguments) {
        if (!in_array($name, self::$logger_functions) || count($arguments)<1) {
            throw new \BadMethodCallException();
        }

        if (isset($_SESSION)) {
            $context = [
                'this' => isset($arguments[1]) ? $arguments[1] : null,
                'user' => Auth::user(),
                'request' => Auth::$session->get('request_info')
            ];
        } else {
            $context = [
                'this' => isset($arguments[1]) ? $arguments[1] : null,
                'user' => null,
                'request' => null
            ];
        }

        $this->logger->$name($arguments[0], $context);
    }

    /**
     * @return Log
     */
    public static function get() {
        return self::$global_logger;
    }

    /**
     * @param string $name The channel name
     *
     * @return Log
     */
    public function channel(string $name) {
        return new Log($this->logger->withName($_SERVER['APP_TITLE'] . ':' . $name));
    }
}
