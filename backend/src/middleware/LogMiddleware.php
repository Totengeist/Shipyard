<?php

namespace Shipyard\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Shipyard\Auth;
use Shipyard\Log;
use Slim\Psr7\Response;

/**
 * Middleware.
 */
class LogMiddleware implements MiddlewareInterface {
    /**
     * Invoke middleware.
     *
     * @return ResponseInterface The response
     */
    public function process(Request $request, Handler $handler): ResponseInterface {
        if (isset($request->getServerParams()['REMOTE_ADDR'])) {
            Auth::request_info(['ip_address' => $request->getServerParams()['REMOTE_ADDR']]);
            Log::debug('Starting session.');
        }

        return $handler->handle($request);
    }
}
