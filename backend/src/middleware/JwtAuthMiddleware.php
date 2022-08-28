<?php

namespace Shipyard\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Shipyard\Auth;
use Shipyard\User;
use Slim\Psr7\Response;

/**
 * Middleware.
 */
final class JwtAuthMiddleware implements MiddlewareInterface {
    /**
     * Invoke middleware.
     *
     * @param ServerRequestInterface  $request The request
     * @param RequestHandlerInterface $handler The handler
     *
     * @return ResponseInterface The response
     */
    public function process(Request $request, Handler $handler): ResponseInterface {
        $auths = $request->getHeader('Authorization');
        $token = false;
        foreach ($auths as $auth) {
            if (strpos($auth, 'Bearer ') == 0) {
                $token_str = substr($auth, 7);
                $token = Auth::parse($token_str);
                break;
            }
        }

        $isValidToken = ($token !== false)&&Auth::validate($token);

        if (!$isValidToken) {
            // Invalid authentication credentials
            return (new Response())
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401, 'Unauthorized');
        }

        Auth::login(User::find($token->claims()->get('sub')));

        return $handler->handle($request);
    }
}
