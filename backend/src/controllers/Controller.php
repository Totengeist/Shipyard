<?php

namespace Shipyard\Controllers;

use Psr\Container\ContainerInterface;

class Controller {
    /** @var \Psr\Container\ContainerInterface */
    protected $container;

    // constructor receives container instance
    public function __construct(ContainerInterface $container = null) {
        $this->container = $container;
    }
}
