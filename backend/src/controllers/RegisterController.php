<?php

namespace Shipyard\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shipyard\Auth;
use Shipyard\Models\User;
use Shipyard\Models\UserActivation;
use Shipyard\Traits\ChecksPermissions;
use Valitron\Validator;

class RegisterController extends Controller {
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */
    use ChecksPermissions;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        //$this->middleware('guest', ['except' => ['destroy', 'update']]);
    }

    /**
     * Create or add on to a validator.
     *
     * @return Validator
     */
    protected function validator(array $data, $optional = false) {
        Validator::addRule('unique', function ($field, $value, array $params, array $fields) {
            if (User::query()->where('email', $fields['email'])->get()->isEmpty()) {
                return true;
            }

            return false;
        }, 'is not unique.');

        if ($optional) {
            $v = new Validator($data);
            $v->rules([
                'optional' => [
                    ['name'],
                    ['email'],
                    ['password'],
                ],
                'unique' => [
                    ['email'],
                ],
                'email' => [
                    ['email'],
                ],
                'lengthMax' => [
                    ['name', 255],
                    ['email', 255],
                ],
                'lengthMin' => [
                    ['password', 6],
                ],
                'equals' => [
                    ['password', 'password_confirmation'],
                ],
            ]);
            $v->validate();

            return $v;
        }
        $v = new Validator($data);
        $v->rules([
            'required' => [
                ['name'],
                ['email'],
                ['password'],
            ],
            'unique' => [
                ['email'],
            ],
            'email' => [
                ['email'],
            ],
            'lengthMax' => [
                ['name', 255],
                ['email', 255],
            ],
            'lengthMin' => [
                ['password', 6],
            ],
            'equals' => [
                ['password', 'password_confirmation'],
            ],
        ]);
        $v->validate();

        return $v;
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @return \Shipyard\User
     */
    protected function create(array $data) {
        return User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
        ]);
    }

    /**
     * Handle a registration request for the application.
     *
     * @override
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request, Response $response, $args) {
        $data = $request->getParsedBody();
        $errors = $this->validator($data)->errors();

        if (count($errors)) {
            $payload = json_encode(['errors' => $errors]);

            $response->getBody()->write($payload);

            return $response
              ->withStatus(401)
              ->withHeader('Content-Type', 'application/json');
        }

        $subdata = array_intersect_key($data, array_flip((array) ['name', 'email', 'password', 'password_confirmation']));
        $user = $this->create($subdata);
        $user->create_activation();

        $payload = json_encode(['user' => $user]);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Handle an activation request for the application.
     *
     * @override
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function activate(Request $request, Response $response, $args) {
        $activation = UserActivation::query()->where('token', $args['token'])->firstOrFail();
        $user = User::query()->where('email', $activation->email)->first();
        $user->activate();

        Auth::login($user);

        $payload = json_encode($user);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Response $response, $args) {
        $id = $args['userid'];
        if (($perm_check = $this->isOrCan($id, 'delete-users')) !== null) {
            return $perm_check;
        }
        $user = User::query()->find($id);

        $activations = UserActivation::query()->where('email', $user->email)->get();
        foreach ($activations as $activation) {
            $activation->delete();
        }
        $user->delete();

        $payload = json_encode(['message' => 'successful']);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Response $response, $args) {
        $id = $args['user_id'];
        if (($perm_check = $this->isOrCan($id, 'edit-users')) !== null) {
            return $perm_check;
        }
        $data = $request->getParsedBody();

        $subdata = array_intersect_key($data, array_flip((array) ['name', 'email', 'password', 'password_confirmation']));
        $errors = $this->validator($subdata, true)->errors();
        if (count($errors)) {
            $response->getBody()->write(['errors' => $errors]);

            return $response
                ->withHeader('Content-Type', 'application/json');
        }

        $user = User::query()->where('id', $id)->first();

        if (isset($subdata['name'])) {
            $user->name = $subdata['name'];
        }
        if (isset($subdata['email'])) {
            $user->email = $subdata['email'];
        }
        if (isset($subdata['password'])) {
            $user->password = $subdata['password'];
        }

        $user->save();

        $payload = json_encode(['user' => $user]);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
