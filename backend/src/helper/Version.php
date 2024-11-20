<?php

namespace Shipyard;

class Version {
    /**
     * Retrieve the current application version from composer.json.
     *
     * @return string the version string
     */
    public static function getVersion() {
        if (($composerJson = file_get_contents($_SERVER['APP_ROOT'] . '/../composer.json')) === false) {
            return '';
        }

        return json_decode($composerJson, true)['version'];
    }
}
