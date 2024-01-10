<?php

namespace Shipyard;

use Slim\Psr7\Factory\ResponseFactory;
use SlimSession\Helper as SessionHelper;

class Auth {
    /**
     * The active session.
     *
     * @var \SlimSession\Helper<mixed>
     */
    public static $session;

    /**
     * @param Models\User $user
     *
     * @return void
     */
    public static function login($user) {
        if (static::$session === null) {
            static::$session = new SessionHelper();
        }

        static::$session->set('user', $user);
    }

    /**
     * @return void
     */
    public static function logout() {
        if (static::$session === null) {
            static::$session = new SessionHelper();
        }

        static::$session->destroy();
    }

    /**
     * @return Models\User
     */
    public static function user() {
        if (static::$session === null) {
            static::$session = new SessionHelper();
        }

        return static::$session->get('user');
    }

    /**
     * @return bool
     */
    public static function check() {
        if (static::$session === null) {
            static::$session = new SessionHelper();
        }

        return static::$session->exists('user');
    }

    /**
     * @param int    $code
     * @param string $message
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public static function abort($code, $message) {
        $factory = new ResponseFactory();
        $response = $factory->createResponse($code, $message);
        $response->withStatus($code, $message);

        return $response;
    }
}
