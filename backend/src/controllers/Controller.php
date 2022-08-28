<?php

namespace Shipyard\Controllers;

use Psr\Container\ContainerInterface;

class Controller {
    protected $container;

    // constructor receives container instance
    public function __construct(ContainerInterface $container = null) {
        $this->container = $container;
    }
}
