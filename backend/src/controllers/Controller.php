<?php

namespace Shipyard\Controllers;

use Psr\Container\ContainerInterface;

class Controller {
    /** @var ContainerInterface */
    protected $container;

    // constructor receives container instance
    public function __construct(ContainerInterface $container = null) {
        if ($container === null) {
            return;
        }
        $this->container = $container;
    }

    /**
     * Paginate an Eloquent builder.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param int                                   $per_page the amount of items to return per page
     * @param int                                   $page     the page number to return
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection
     */
    public function paginate($builder, $per_page = 15, $page = 1) {
        if (isset($_SERVER['DISABLE_PAGINATION']) && $_SERVER['DISABLE_PAGINATION']) {
            return $builder->get();
        }

        return $builder->paginate($this->get_per_page($per_page), ['*'], 'page', $this->get_page($page));
    }

    /**
     * Get the current page number of an API request.
     *
     * @param int $page
     *
     * @return int
     */
    public function get_page($page = 1) {
        if (isset($_REQUEST['page'])) {
            $page = intval($_REQUEST['page']);
        }
        if ($page < 2) {
            $page = 1;
        }

        return $page;
    }

    /**
     * Get the number of items to return per page.
     *
     * @param int $per_page
     *
     * @return int
     */
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
