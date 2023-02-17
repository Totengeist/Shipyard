<?php

namespace Shipyard;

use Slim\Factory\AppFactory;

class App {
    /**
     * Stores an instance of the Slim application.
     *
     * @var \Slim\App
     */
    private $app;

    public function __construct() {
        $container = new \DI\Container();

        // Register globally to app
        $container->set('session', function () {
            return new \SlimSession\Helper();
        });
        AppFactory::setContainer($container);

        $app = AppFactory::create();
        require __DIR__ . '/config/routes.php';
        $this->app = $app;

        $app->add(
          new \Slim\Middleware\Session([
            'autorefresh' => true,
            'lifetime' => '1 hour',
          ])
        );
    }

    /**
     * Get an instance of the application.
     *
     * @return \Slim\App
     */
    public function get() {
        return $this->app;
    }
}
