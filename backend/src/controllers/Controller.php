<?php

namespace Shipyard\Controllers;

use Psr\Container\ContainerInterface;

class Controller {
    /** @var \Psr\Container\ContainerInterface */

    /**
     * Create or add on to a validator.
     *
     * @param array<string, string> $data
     *
     * @return Validator
     */
    protected $container;

    // constructor receives container instance
    public function __construct(ContainerInterface $container = null) {
        if ($container === null) {
            return;
        }
        $this->container = $container;
    }

    public function paginate($builder, $per_page = 15, $page = 1) {
        if (isset($_SERVER['DISABLE_PAGINATION']) && $_SERVER['DISABLE_PAGINATION']) {
            return $builder->get();
        }

        return $builder->paginate($this->get_per_page($per_page), ['*'], 'page', $this->get_page($page));
    }

    public function get_page($page = 1) {
        if (isset($_REQUEST['page'])) {
            $page = intval($_REQUEST['page']);
        }
        if ($page < 2) {
            $page = 1;
        }

        return $page;
    }

    public function get_per_page($per_page = 15) {
        if (isset($_REQUEST['per_page'])) {
            $per_page = intval($_REQUEST['per_page']);
        }
        if ($per_page > 100) {
            $per_page = 100;
        }

        return $per_page;
    }
}
