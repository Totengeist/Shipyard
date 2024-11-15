<?php

namespace Shipyard\Controllers;

use Psr\Container\ContainerInterface;
use Shipyard\Log;
use Slim\Psr7\Factory\ResponseFactory;

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

    /**
     * @param int    $code
     * @param string $status
     * @param string $body
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public static function error_response($code, $status, $body = '') {
        $factory = new ResponseFactory();
        $response = $factory->createResponse($code);
        $response->getBody()->write($body);
        $response->withStatus($code, $status);

        Log::error("$status ($code): " . $body . "\n" . (new \Exception())->getTraceAsString());

        return $response;
    }

    /**
     * @param string $type
     * @param string $message
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public static function not_found_response($type, $message = '') {
        if ($message == '') {
            $message = "$type not found";
        }

        return self::error_response(404, $message, (string) json_encode(['errors' => [$message]]))
                   ->withHeader('Content-Type', 'application/json');
    }

    /**
     * @param string[] $errors
     * @param string   $message
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public static function invalid_input_response($errors = [], $message = 'Invalid input') {
        return self::error_response(422, $message, (string) json_encode(['errors' => $errors]))
                   ->withHeader('Content-Type', 'application/json');
    }

    /**
     * @param string[] $errors
     * @param string   $message
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public static function unauthorized_response($errors = [], $message = 'Unauthorized') {
        return self::error_response(401, $message, (string) json_encode(['errors' => $errors]))
                   ->withHeader('Content-Type', 'application/json');
    }
}
