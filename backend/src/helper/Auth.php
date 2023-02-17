<?php

namespace Shipyard;

use Slim\Psr7\Factory\ResponseFactory;
use SlimSession\Helper as SessionHelper;

class Auth {
    /**
     * The active session.
     *
     * @var \SlimSession\Helper
     */
    public static $session;

    public static function login($user) {
        if (static::$session === null) {
            static::$session = new SessionHelper();
        }

        static::$session->set('user', $user);
    }

    public static function logout() {
        if (static::$session === null) {
            static::$session = new SessionHelper();
        }

        static::$session->destroy();
    }

    public static function user() {
        if (static::$session === null) {
            static::$session = new SessionHelper();
        }

        return static::$session->get('user');
    }

    public static function check() {
        if (static::$session === null) {
            static::$session = new SessionHelper();
        }

        return static::$session->exists('user');
    }

    public static function abort($code, $message) {
        $factory = new ResponseFactory();
        $response = $factory->createResponse($code, $message);
        $response->withStatus($code, $message);

        return $response;
    }
}
