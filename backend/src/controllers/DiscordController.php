<?php

namespace Shipyard\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shipyard\Auth;
use Shipyard\Log;
use Shipyard\Models\User;

class DiscordController extends Controller {
    /** @var string * */
    private $discord_api_endpoint = 'https://discord.com/api/v10';

    /**
     * Login using a registered Discord account.
     *
     * @return void
     */
    public function login(Request $request, Response $response) {
        $discord_login_url = 'https://discord.com/oauth2/authorize?client_id=' . $_SERVER['DISCORD_CLIENT_ID'] . '&response_type=code&redirect_uri='
            . urlencode($_SERVER['BASE_URL_ABS'] . '/discord/process_login')
            . '&scope=identify email';

        Log::get()->channel('registration')->info('Begin Discord ID login.');
        header("location: $discord_login_url");
        exit;
    }

    /**
     * Process a Discord OpenID login attempt.
     *
     * @param string $code The access code from Discord
     * @param string $uri  The relevant portion of the return URI
     *
     * @return array<string, string|int>|false
     */
    public function process_discord($code, $uri) {
        $payload = [
            'code'=>$code,
            'grant_type'=>'authorization_code',
            'redirect_uri'=>$_SERVER['BASE_URL_ABS'] . '/discord/' . $uri,
            'scope'=>'identify email',
        ];

        $data = http_build_query($payload);
        // data prep
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => ['Authorization: Basic ' . base64_encode($_SERVER['DISCORD_CLIENT_ID'] . ':' . $_SERVER['DISCORD_CLIENT_SECRET']), 'Content-type: application/x-www-form-urlencoded', 'Content-Length: ' . strlen($data)],
                'content' => $data,
            ],
        ]);

        // get the data
        $result = file_get_contents($this->discord_api_endpoint . '/oauth2/token', false, $context);
        if ($result === false) {
            return false;
        }

        $result = json_decode($result, true);

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => ['Authorization: Bearer ' . $result['access_token'], 'Content-type: application/x-www-form-urlencoded'],
            ],
        ]);

        // get the data
        $result = file_get_contents('https://discordapp.com/api/users/@me', false, $context);
        if ($result === false) {
            return false;
        }

        $result = json_decode($result, true);

        $discordid = [];
        $discordid['id'] = is_numeric($result['id']) ? (int) $result['id'] : 0;
        $discordid['email'] = $result['email'];
        $discordid['username'] = $result['username'];

        return $discordid;
    }

    /**
     * Register a successful Discord OpenID login to a Shipyard account.
     *
     * @param array<string, string|int> $discordid Discord ID and email address
     *
     * @return void|Response
     */
    public function process_registration($discordid) {
        /** @var User|null $auth_user */
        $auth_user = Auth::user();

        // If there is no authenticated user, then register using the Discord provided information and a randomized password.
        if ($auth_user == null) {
            /** @var User $user */
            $user = User::query()->create([
                'name' => $discordid['username'],
                'email' => $discordid['email'],
                'password' => password_hash(bin2hex(random_bytes(128)), PASSWORD_BCRYPT),
            ]);
            Log::get()->channel('registration')->info('Registered user.', $user->toArray());
            Auth::login($user);
            /** @var User $auth_user */
            $auth_user = Auth::user();
        } else {
            // If the authenticated user is not found, then this is an old session for a deleted account. Bail out.
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = User::query()->where([['ref', $auth_user->ref]]);
            /** @var User $user */
            $user = $query->first();
            Auth::logout();
            if ($user == null) {
                header('location: ' . $_SERVER['BASE_URL'] . '/home');
                exit;
            }

            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = User::query()->where([['discordid', $discordid['id']]]);
            /** @var User|null $existing_user */
            $existing_user = $query->first();

            if ($existing_user !== null && $existing_user->id !== $user->id) {
                header('location: ' . $_SERVER['BASE_URL'] . '/profile?error=discord_already_linked');
                exit;
            }
        }

        Log::get()->channel('registration')->info('Registering Discord ID to user.', $user->toArray());
        $auth_user->discordid = (int) $discordid['id'];
        $user->discordid = (int) $discordid['id'];
        $user->save();
        header('location: ' . $_SERVER['BASE_URL'] . '/profile');
        exit;
    }

    /**
     * Process a successful Discord OpenID login attempt.
     *
     * @return void|Response
     */
    public function process_login(Request $request, Response $response) {
        $code = $request->getQueryParams()['code'];
        if ($discordid = $this->process_discord($code, 'process_login')) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = User::query()->where([['discordid', $discordid['id']]]);
            /** @var User $user */
            $user = $query->first();
            if ($user == null) {
                /** @var \Illuminate\Database\Eloquent\Builder $query */
                $query = User::query()->where([['email', $discordid['email']]]);
                /** @var User $user */
                $user = $query->first();
                if ($user == null) {
                    return $this->process_registration($discordid);
                }
            }
            $user->discordid = (int) $discordid['id'];
            $user->save();

            Auth::login($user);

            header('location: ' . $_SERVER['BASE_URL'] . '/profile');
            exit;
        }
        header('location: ' . $_SERVER['BASE_URL'] . '/login?error=discord_not_linked');
        exit;
    }

    /**
     * Register a successful Discord OpenID login to a Shipyard account.
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

        $auth_user->discordid = null;
        $user->discordid = null;
        $user->save();
        Log::get()->channel('registration')->info('Unregistering Discord ID from user.', $user->toArray());
        $response->getBody()->write((string) json_encode(['message' => 'Success!']));

        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
