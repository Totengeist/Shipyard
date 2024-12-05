<?php

namespace Shipyard;

use Slim\Factory\AppFactory;

class App {
    /**
     * Stores an instance of the Slim application.
     *
     * @var \Slim\App
     */
    private static $app;

    public function __construct() {
        $container = new \DI\Container();

        // Register globally to app
        $container->set('session', function () {
            return new \SlimSession\Helper();
        });
        $container->set('logger', Log::get());
        AppFactory::setContainer($container);

        $app = AppFactory::create();
        self::$app = $app;

        $container->get('logger')->debug('App initialized.');

        $app->add(
            new \Slim\Middleware\Session([
                'autorefresh' => true,
                'lifetime' => '1 hour',
            ])
        );
        require __DIR__ . '/config/routes.php';
    }

    /**
     * Get an instance of the application.
     *
     * @return \Slim\App
     */
    public static function get() {
        return self::$app;
    }
}
