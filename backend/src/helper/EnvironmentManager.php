<?php

namespace Shipyard;

class EnvironmentManager {
    /**
     * Determine and create the storage location based on environment variables or fallback.
     *
     * @return string
     */
    public static function storage() {
        $storage = dirname($_SERVER['APP_ROOT']) . '/storage';
        if (isset($_SERVER['STORAGE'])) {
            if ($_SERVER['STORAGE'][0] == DIRECTORY_SEPARATOR || $_SERVER['STORAGE'][0] == '/' || $_SERVER['STORAGE'][0] == '\\') {
                $storage = $_SERVER['STORAGE'];
            } else {
                $storage = dirname($_SERVER['APP_ROOT']) . '/' . $_SERVER['STORAGE'];
            }
        }
        if (!is_dir($storage)) {
            mkdir($storage, 0700, true);
        }
        (new Log())->channel('files')->debug('Setting storage location to: ' . realpath($storage) . DIRECTORY_SEPARATOR);

        return realpath($storage) . DIRECTORY_SEPARATOR;
    }

    /**
     * Create and register logger.
     *
     * @return void
     */
    public static function initializeLogger() {
        $logger = new Log();
        $logger->setAsGlobal();
    }

    /**
     * Create and register notifiers.
     *
     * @return void
     */
    public static function initializeNotifier() {
        $notifier = new NotificationManager();
        $notifier->setAsGlobal();
        $notifier->add_channel('email-text', new EmailNotifier());
        $htmlemail = new EmailNotifier();
        $htmlemail->isHTML();
        $notifier->add_channel('email-html', $htmlemail);
    }
}
