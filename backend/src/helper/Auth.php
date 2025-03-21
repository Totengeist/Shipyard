<?php

namespace Shipyard;

use Shipyard\Models\User;
use Slim\Psr7\Factory\ResponseFactory;
use SlimSession\Helper as SessionHelper;

class Auth {
    /**
     * The active session.
     *
     * @var SessionHelper<mixed>
     */
    public static $session;

    /**
     * @param User $user
     *
     * @return void
     */
    public static function login($user) {
        if (static::$session === null) {
            static::$session = new SessionHelper();
        }

        $log_user = array_diff_key($user->attributesToArray(), ['email', 'created_at', 'updated_at']);
        Log::get()->channel('auth')->info('Logged in user.', $log_user);
        static::$session->set('user', $user);
    }

    /**
     * @return void
     */
    public static function logout() {
        if (static::$session === null) {
            static::$session = new SessionHelper();
        }

        $log_user = [];
        if (static::$session->get('user') !== null) {
            $log_user = array_diff_key(static::$session->get('user')->attributesToArray(), ['email', 'created_at', 'updated_at']);
        }
        Log::get()->channel('auth')->info('Logged out user.', $log_user);
        static::$session->destroy();
    }

    /**
     * @return User|null
     */
    public static function user() {
        if (static::$session === null) {
            static::$session = new SessionHelper();
        }

        return static::$session->get('user');
    }

    /**
     * @return string
     */
    public static function session_id() {
        if (static::$session === null) {
            static::$session = new SessionHelper();
        }

        return static::$session::id();
    }

    /**
     * @return bool
     */
    public static function check() {
        if (static::$session === null) {
            return false;
        }

        if (!static::$session->exists('user')) {
            return false;
        }

        $user = static::$session->get('user');

        /**
         * Verify the user in the session exists.
         *
         * @var \Illuminate\Database\Eloquent\Builder $query
         */
        $query = User::query()->where([
            ['email', '=', $user->email],
            ['id', '=', $user->id]
        ]);

        return $query->first() != null;
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

    /**
     * Reload a different session.
     *
     * Since creating a session is done often and slim-sessions doesn't handle reloading a session,
     * we need to destroy the current one and open the requested one.
     *
     * @param string $session_id the ID of an existing session that should be resumed
     *
     * @return void
     */
    public static function load_session($session_id) {
        if (static::$session !== null) {
            static::$session->destroy();
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_destroy();
        }
        session_id($session_id);
        session_start();
        static::$session = new SessionHelper();
    }

    /**
     * Save information about the current request.
     *
     * @param mixed[] $info session information
     *
     * @todo Add a merge option.
     *
     * @return void
     */
    public static function request_info($info) {
        if (static::$session === null) {
            static::$session = new SessionHelper();
        }

        static::$session->set('request_info', $info);
    }
}
