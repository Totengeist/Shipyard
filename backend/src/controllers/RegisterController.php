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
        // $this->middleware('guest', ['except' => ['destroy', 'update']]);
    }

    /**
     * Create or add on to a validator.
     *
     * @param mixed[] $data
     * @param bool    $optional
     *
     * @return Validator
     */
    protected function validator($data, $optional = false) {
        Validator::addRule('unique', function ($field, $value, array $params, array $fields) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = User::query()->where('email', $fields['email']);
            if ($query->get()->isEmpty()) {
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
     * @param array<string, string> $data
     *
     * @return User
     */
    protected function create(array $data) {
        /** @var User $user */
        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
        ]);

        return $user;
    }

    /**
     * Handle a registration request for the application.
     *
     * @override
     *
     * @return Response
     */
    public function register(Request $request, Response $response) {
        $data = (array) $request->getParsedBody();
        /** @var string[] $errors */
        $errors = $this->validator($data)->errors();

        if (count($errors)) {
            return $this->invalid_input_response($errors);
        }

        $subdata = array_intersect_key($data, array_flip((array) ['name', 'email', 'password', 'password_confirmation']));
        $user = $this->create($subdata)->makeVisible(['email', 'created_at', 'updated_at']);
        $user->create_activation();

        $payload = (string) json_encode(['user' => $user]);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Handle an activation request for the application.
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function activate(Request $request, Response $response, $args) {
        try {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = UserActivation::query()->where('token', $args['token']);
            /** @var UserActivation $activation */
            $activation = $query->firstOrFail();
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = User::query()->where('email', $activation->email);
            /** @var User $user */
            $user = $query->firstOrFail();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            return $this->not_found_response('Activation');
        }
        $user->makeVisible(['email', 'created_at', 'updated_at']);
        $user->activate();

        Auth::login($user);

        $payload = (string) json_encode($user);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function destroy(Request $request, Response $response, $args) {
        $id = (int) $args['user_id'];
        if (($perm_check = $this->isOrCan($id, 'delete-users')) !== null) {
            return $perm_check;
        }
        /** @var User $user */
        $user = User::query()->find($id);
        if ($user == null) {
            return $this->not_found_response('User');
        }

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = UserActivation::query()->where('email', $user->email);
        $activations = $query->get();
        foreach ($activations as $activation) {
            $activation->delete();
        }
        $user->delete();

        $payload = (string) json_encode(['message' => 'successful']);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function update(Request $request, Response $response, $args) {
        $id = (int) $args['user_id'];
        if (($perm_check = $this->isOrCan($id, 'edit-users')) !== null) {
            return $perm_check;
        }
        $data = (array) $request->getParsedBody();

        $subdata = array_intersect_key($data, array_flip(['name', 'email', 'password', 'password_confirmation']));
        /** @var string[] $errors */
        $errors = $this->validator($subdata, true)->errors();
        if (count($errors)) {
            $response->getBody()->write((string) json_encode(['errors' => $errors]));

            return $response
                ->withStatus(422)
                ->withHeader('Content-Type', 'application/json');
        }

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = User::query()->where('id', $id);
        /** @var User $user */
        $user = $query->first();
        if ($user == null) {
            return $this->not_found_response('User');
        }
        $user->makeVisible(['email', 'created_at', 'updated_at']);

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

        $payload = (string) json_encode(['user' => $user]);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
