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
}
