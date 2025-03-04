<?php

namespace Shipyard\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shipyard\Auth;
use Shipyard\Log;
use Shipyard\Models\User;

class SteamController extends Controller {
    /** @var string */
    private static $openidUrl = 'http://specs.openid.net/auth/2.0';
    /** @var string */
    private static $openIdIdent = '/identifier_select';
    /** @var string */
    private static $steamUrl = 'https://steamcommunity.com/openid';

    /**
     * Register a Steam account to a Shipyard account.
     *
     * @return void
     */
    public function register(Request $request, Response $response) {
        $this->login($request, $response, [], 'registration');
    }

    /**
     * Register a Steam account to a Shipyard account or login using a registered Steam account.
     *
     * @param array<string,string> $args
     * @param string               $action the action to take (login/registration)
     *
     * @return void
     */
    public function login(Request $request, Response $response, $args, $action = 'login') {
        $login_url_params = [
            'openid.ns'         => self::$openidUrl,
            'openid.mode'       => 'checkid_setup',
            'openid.return_to'  => $_SERVER['BASE_URL_ABS'] . '/steam/process_' . $action,
            'openid.realm'      => (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'],
            'openid.identity'   => self::$openidUrl . self::$openIdIdent,
            'openid.claimed_id' => self::$openidUrl . self::$openIdIdent,
        ];

        Log::get()->channel('registration')->info('Begin Steam ID ' . $action . '.');
        $steam_login_url = self::$steamUrl . '/login?' . http_build_query($login_url_params, '', '&');

        header("location: $steam_login_url");
        exit;
    }

    /**
     * Process a Steam OpenID login attempt.
     *
     * @return int|bool
     */
    public function processSteam() {
        $params = [
            'openid.assoc_handle' => $_GET['openid_assoc_handle'],
            'openid.signed'       => $_GET['openid_signed'],
            'openid.sig'          => $_GET['openid_sig'],
            'openid.ns'           => self::$openidUrl,
            'openid.mode'         => 'check_authentication',
        ];

        $signed = explode(',', $_GET['openid_signed']);

        foreach ($signed as $item) {
            $val = $_GET['openid_' . str_replace('.', '_', $item)];
            $params['openid.' . $item] = stripslashes($val);
        }

        $data = http_build_query($params);
        // data prep
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Accept-language: en\r\n" .
                "Content-type: application/x-www-form-urlencoded\r\n" .
                'Content-Length: ' . strlen($data) . "\r\n",
                'content' => $data,
            ],
        ]);

        // get the data
        $result = file_get_contents(self::$steamUrl . '/login', false, $context);
        if ($result === false) {
            return false;
        }

        if (preg_match("#is_valid\s*:\s*true#i", $result)) {
            preg_match('#^' . self::$steamUrl . '/id/(\d{17,25})#', $_GET['openid_claimed_id'], $matches);

            return count($matches) ? (int) $matches[1] : 0;
        }

        return false;
    }

    /**
     * Register a successful Steam OpenID login to a Shipyard account.
     *
     * @return void|Response
     */
    public function processRegistration(Request $request, Response $response) {
        /** @var User|null $auth_user */
        $auth_user = Auth::user();

        // If there is no authenticated user, then redirect to the login screen.
        if ($auth_user == null) {
            header('location: ' . $_SERVER['BASE_URL'] . '/login');
            exit;
        }
        if ($steamid = $this->processSteam()) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = User::query()->where([['steamid', $steamid]]);
            /** @var User|null $existing_user */
            $existing_user = $query->first();

            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = User::query()->where([['ref', $auth_user->ref]]);
            /** @var User $user */
            $user = $query->first();
            if ($user == null) {
                header('location: ' . $_SERVER['BASE_URL'] . '/home');
                exit;
            }

            if ($existing_user !== null && $existing_user->id !== $user->id) {
                header('location: ' . $_SERVER['BASE_URL'] . '/profile?error=steam_already_linked');
                exit;
            }

            Log::get()->channel('registration')->info('Registering Steam ID to user.', $user->toArray());
            $auth_user->steamid = (int) $steamid;
            $user->steamid = (int) $steamid;
            $user->save();
            header('location: ' . $_SERVER['BASE_URL'] . '/profile');
            exit;
        }
    }

    /**
     * Process a successful Steam OpenID login attempt.
     *
     * @return void|Response
     */
    public function processLogin(Request $request, Response $response) {
        if ($steamid = $this->processSteam()) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = User::query()->where([['steamid', $steamid]]);
            /** @var User $user */
            $user = $query->first();
            if ($user == null) {
                return $this->not_found_response('User');
            }

            Auth::login($user);

            header('location: ' . $_SERVER['BASE_URL'] . '/profile');
            exit;
        }
        header('location: ' . $_SERVER['BASE_URL'] . '/login?error=steam_not_linked');
        exit;
    }

    /**
     * Register a successful Steam OpenID login to a Shipyard account.
     *
     * @return void|Response
     */
    public function remove(Request $request, Response $response) {
        /** @var User $auth_user */
        $auth_user = Auth::user();

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = User::query()->where([['ref', $auth_user->ref]]);
        /** @var User $user */
        $user = $query->first();
        if ($user == null) {
            return $this->not_found_response('User');
        }

        $auth_user->steamid = null;
        $user->steamid = null;
        $user->save();
        Log::get()->channel('registration')->info('Unregistering Steam ID from user.', $user->toArray());
        $response->getBody()->write((string) json_encode(['message' => 'Success!']));

        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
