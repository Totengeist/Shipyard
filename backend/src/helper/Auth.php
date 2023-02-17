<?php

namespace Shipyard;

use DateTimeImmutable;
use Dotenv\Dotenv;
use Lcobucci\Clock\FrozenClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
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

    public static function parse($jwt) {
        $signer = new Sha256();

        return static::getConfig()->parser()->parse($jwt);
    }

    public static function validate($token) {
        $signer = new Sha256();
        $config = static::getConfig();
        $config->setValidationConstraints(
            //new LooseValidAt(new FrozenClock(new DateTimeImmutable()))
            new SignedWith(new Sha256(), InMemory::plainText($_ENV['JWT_SECRET']))
        );

        return static::getConfig()->validator()->validate($token, ...$config->validationConstraints());
    }

    private static function getConfig() {
        return Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($_ENV['JWT_SECRET']));
    }

    public static function generate_token() {
        $signer = new Sha256();
        $time = new DateTimeImmutable();
        $config = static::getConfig();

        $dotenv = Dotenv::createImmutable(realpath(__DIR__ . '/../..'));
        $dotenv->load();

        $token = $config->builder()->issuedAt($time) // Configures the time that the token was issue (iat claim)
                                   ->canOnlyBeUsedAfter($time->modify('+1 minute')) // Configures the time that the token can be used (nbf claim)
                                   ->expiresAt($time->modify('+1 hour')) // Configures the expiration time of the token (exp claim)
                                   ->relatedTo(static::user()->id) // Adds the ID of the user
                                   ->getToken($config->signer(), $config->signingKey()); // Retrieves the generated token

        return $token;
    }

    public static function abort($code, $message) {
        $factory = new ResponseFactory();
        $response = $factory->createResponse($code, $message);
        $response->withStatus($code, $message);

        return $response;
    }
}
