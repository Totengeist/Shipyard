<?php

namespace Shipyard\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shipyard\Auth;
use Shipyard\User;
use Shipyard\UserActivation;

class LoginController extends Controller {
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    /**
     * Handle a login request to the application.
     *
     * @param Psr\Http\Message\ServerRequestInterface $request
     *
     * @return Psr\Http\Message\ResponseInterface
     */
    public function login(Request $request, Response $response) {
        $data = (array) $request->getParsedBody();

        $email = (string) ($data['email'] ?? '');
        $password = (string) ($data['password'] ?? '');
        $user = User::where('email', $email)->with('roles', 'roles.permissions')->first();

        if ($user === null || !password_verify($password, $user->password)) {
            $response->getBody()->write(json_encode(['message' => 'These credentials do not match our records.']));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401, 'Unauthorized');
        }

        if (!UserActivation::where('email', $user->email)->get()->isEmpty()) {
            $response->getBody()->write(json_encode(['message' => 'This account has not been activated. Please check your email.']));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401, 'Unauthorized');
        }

        Auth::login($user);
        $data = Auth::user();

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Handle a request for authenticated user information to the application.
     *
     * @param Psr\Http\Message\ServerRequestInterface $request
     *
     * @return Psr\Http\Message\ResponseInterface
     */
    public function me(Request $request, Response $response) {
        $payload = json_encode(Auth::user());

        $response->getBody()->write($payload);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    public function logout() {
    }
}
