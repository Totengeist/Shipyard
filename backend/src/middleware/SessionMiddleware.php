<?php

namespace Shipyard\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Shipyard\Auth;
use Slim\Psr7\Response;

/**
 * Middleware.
 */
class SessionMiddleware implements MiddlewareInterface {
    /**
     * Invoke middleware.
     *
     * @return ResponseInterface The response
     */
    public function process(Request $request, Handler $handler): ResponseInterface {
        if (!Auth::check()) {
            // Check for an existing session with a bearer token
            if ($request->getHeader('Authorization') && preg_match('/Bearer ([0-9a-z]*)/', $request->getHeader('Authorization')[0], $token_check)) {
                Auth::load_session($token_check[1]);
                if (Auth::check()) { /* @phpstan-ignore-line */
                    return $handler->handle($request);
                }
            }

            // Invalid authentication credentials
            return (new Response())
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401, 'Unauthorized');
        }

        return $handler->handle($request);
    }
}
