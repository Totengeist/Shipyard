<?php

namespace Shipyard\Traits;

trait CreatesUniqueIDs {
    /**
     * @param int $length
     *
     * @return string
     */
    public static function get_guid($length = 16) {
        if ($length <= 8) {
            $length = 16;
        }
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length));
        }
        if (function_exists('mcrypt_create_iv')) {
            return bin2hex(mcrypt_create_iv($length));
        }
        if (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex((string) openssl_random_pseudo_bytes($length));
        }
        throw new \ErrorException('Unable to generate random string');
    }
}
