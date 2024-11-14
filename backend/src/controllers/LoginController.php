<?php

namespace Shipyard\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shipyard\Auth;
use Shipyard\Models\User;
use Shipyard\Models\UserActivation;

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
     * @return Response
     */
    public function login(Request $request, Response $response) {
        $data = (array) $request->getParsedBody();

        $email = (string) ($data['email'] ?? '');
        $password = (string) ($data['password'] ?? '');
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = User::query()->where('email', $email)->with(['roles', 'roles.permissions']);
        /** @var User $user */
        $user = $query->first();

        if ($user == null || !password_verify($password, $user->password)) {
            return $this->invalid_input_response('These credentials do not match our records.');
        }

        if (password_needs_rehash($data['password'], PASSWORD_BCRYPT)) {
            $user->password = password_hash($data['password'], PASSWORD_BCRYPT);
            $user->save();
        }

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = UserActivation::query()->where('email', $user->email);
        if (!$query->get()->isEmpty()) {
            return $this->unauthorized_response('This account has not been activated. Please check your email.');
        }

        Auth::login($user);
        $data = (Auth::user() !== null) ? Auth::user()->makeVisible(['email', 'created_at', 'updated_at']) : [];
        $data['session_id'] = Auth::session_id();

        $data['steam'] = (Auth::user() !== null) ? (bool) Auth::user()->steamid : false;

        $response->getBody()->write((string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Handle a request for authenticated user information to the application.
     *
     * @return Response
     */
    public function me(Request $request, Response $response) {
        $data = (Auth::user() !== null) ? Auth::user()->makeVisible(['email', 'created_at', 'updated_at']) : [];
        $data['session_id'] = Auth::session_id();

        $data['steam'] = (Auth::user() !== null) ? (bool) Auth::user()->steamid : false;

        $response->getBody()->write((string) json_encode($data));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * Handle a request for logging a user out of the application.
     *
     * @return Response
     */
    public function logout(Request $request, Response $response) {
        Auth::logout();

        $response->getBody()->write((string) (string) json_encode(['message' => 'You have been logged out.']));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
