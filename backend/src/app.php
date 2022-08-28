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
        $app = AppFactory::create();
        require __DIR__ . '/config/routes.php';
        $this->app = $app;
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
