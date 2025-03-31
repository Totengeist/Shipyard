<?php

namespace Shipyard\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shipyard\Auth;
use Shipyard\Log;
use Shipyard\Models\PasswordReset;
use Shipyard\Models\User;
use Shipyard\Models\UserActivation;
use Shipyard\NotificationManager;
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
     * Create a validator for passwords.
     *
     * @param mixed[] $data
     *
     * @return Validator
     */
    protected function password_validator($data) {
        $v = new Validator($data);
        $v->rules([
            'required' => [
                ['password'],
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
        $user = new User([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);
        $user->set_password($data['password']);
        $user->save();

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
        $activation = $user->create_activation();
        Log::get()->channel('registration')->info('Registered user. Activation link: ' . $_SERVER['BASE_URL_ABS'] . '/activate/' . $activation->token, $user->toArray());
        /** @var \Shipyard\EmailNotifier|null $channel */
        $channel = NotificationManager::get()->channel('email-text');
        if (null !== $channel) {
            $channel->addAddress($user->email);
        }
        if ($channel !== null) {
            $channel->send('Thank you for registering with Shipyard. Please click this link to activate your account or copy and paste the link into your browser:\n\n' . $_SERVER['BASE_URL_ABS'] . '/activate/' . $activation->token, 'Shipyard Account Activation');
        }

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
        Log::get()->channel('registration')->info('Activated user.', $user->toArray());

        $payload = (string) json_encode($user);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Handle a password reset request for the application.
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function request_reset(Request $request, Response $response, $args) {
        try {
            $data = (array) $request->getParsedBody();
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = User::query()->where('email', $data['email']);
            /** @var User $user */
            $user = $query->firstOrFail();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            Log::get()->channel('registration')->critical('Password reset error: ' . $ex->getMessage() . "\n" . $ex->getFile() . ':' . $ex->getLine());
            $response->getBody()->write('[]');

            return $response
              ->withHeader('Content-Type', 'application/json');
        }

        $reset = $user->create_password_reset();
        Log::get()->channel('registration')->info('Password reset requested. Reset link: ' . $_SERVER['BASE_URL_ABS'] . '/activate/' . $reset->token, $user->toArray());
        /** @var \Shipyard\EmailNotifier|null $channel */
        $channel = NotificationManager::get()->channel('email-text');
        if (null !== $channel) {
            $channel->addAddress($user->email);
        }
        if ($channel !== null) {
            $channel->send('A password reset was requested for the account using this email address on Shipyard. Please click this link to reset your password or copy and paste the link into your browser:\n\n' . $_SERVER['BASE_URL_ABS'] . '/password_reset/' . $reset->token, 'Shipyard Account Activation');
        }

        $response->getBody()->write('[]');

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Handle a password reset for the application.
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function reset_password(Request $request, Response $response, $args) {
        try {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = PasswordReset::query()->where('token', $args['token']);
            /** @var PasswordReset $reset */
            $reset = $query->firstOrFail();
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = User::query()->where('email', $reset->email);
            /** @var User $user */
            $user = $query->firstOrFail();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            Log::get()->channel('registration')->critical('Password reset error: ' . $ex->getMessage() . "\n" . $ex->getFile() . ':' . $ex->getLine());
            $response->getBody()->write('[]');

            return $response
              ->withHeader('Content-Type', 'application/json');
        }

        $data = (array) $request->getParsedBody();
        /** @var string[] $errors */
        $errors = $this->password_validator($data)->errors();

        if (count($errors)) {
            Log::get()->channel('registration')->critical('Password reset error: ' . http_build_query($errors));
            $response->getBody()->write('[]');

            return $response
              ->withHeader('Content-Type', 'application/json');
        }

        $user->set_password($data['password']);
        $user->save();
        $reset->delete();
        Log::get()->channel('registration')->info('Reset user password.', $user->toArray());

        $response->getBody()->write('[]');

        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Handle an activation request for the application with redirect.
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function activate_redirect(Request $request, Response $response, $args) {
        $activation_response = $this->activate($request, $response, $args);

        if ($activation_response->getStatusCode() != 200) {
            return $activation_response;
        }

        return $response
          ->withHeader('Location', $_SERVER['BASE_URL_ABS'])
          ->withStatus(302);
    }

    /**
     * Display the specified resource.
     *
     * @param array<string,string> $args
     *
     * @return Response
     */
    public function show(Request $request, Response $response, $args) {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = User::query()->where([['ref', $args['ref']]])->with(['ships', 'saves', 'modifications']);
        $user = $query->first();
        if ($user == null) {
            return $this->not_found_response('User');
        }
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
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = User::query()->where('ref', $args['user_ref']);
        /** @var User $user */
        $user = $query->first();
        if ($user == null) {
            return $this->not_found_response('User');
        }
        if (($perm_check = $this->isOrCan($user->id, 'delete-users')) !== true) {
            return $perm_check;
        }

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = UserActivation::query()->where('email', $user->email);
        $activations = $query->get();
        foreach ($activations as $activation) {
            $activation->delete();
        }
        $user->delete();
        Log::get()->channel('registration')->info('Deleted user.', $user->toArray());

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
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = User::query()->where('ref', $args['user_ref']);
        /** @var User $user */
        $user = $query->first();
        if ($user == null) {
            return $this->not_found_response('User');
        }
        if (($perm_check = $this->isOrCan($user->id, 'edit-users')) !== true) {
            return $perm_check;
        }
        $data = (array) $request->getParsedBody();
        if (isset($data['email']) && ($data['email'] == $user->email)) {
            unset($data['email']);
        }

        $subdata = array_intersect_key($data, array_flip(['name', 'email', 'password', 'password_confirmation']));
        /** @var string[] $errors */
        $errors = $this->validator($subdata, true)->errors();
        if (count($errors)) {
            $this->invalid_input_response($errors);
        }

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
            $user->set_password($subdata['password']);
        }

        $user->save();
        Log::get()->channel('registration')->info('Updated user.', $user->toArray());

        if ($this->isUser($user->id) === true) {
            Auth::login($user);
        }

        $payload = (string) json_encode($user);

        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
