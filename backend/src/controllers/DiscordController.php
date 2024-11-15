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
     * Register a Discord account to a Shipyard account.
     *
     * @return void
     */
    public function register(Request $request, Response $response) {
        $discord_login_url = 'https://discord.com/oauth2/authorize?client_id=' . $_SERVER['DISCORD_CLIENT_ID'] . '&response_type=code&redirect_uri='
            . urlencode((!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['BASE_URL'] . '/discord/process_registration')
            . '&scope=identify';

        header("location: $discord_login_url");
        exit;
    }

    /**
     * Login using a registered Discord account.
     *
     * @return void
     */
    public function login(Request $request, Response $response) {
        $discord_login_url = 'https://discord.com/oauth2/authorize?client_id=' . $_SERVER['DISCORD_CLIENT_ID'] . '&response_type=code&redirect_uri='
            . urlencode((!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['BASE_URL'] . '/discord/process_login')
            . '&scope=identify';

        header("location: $discord_login_url");
        exit;
    }

    /**
     * Process a Discord OpenID login attempt.
     *
     * @param string $code The access code from Discord
     * @param string $uri  The relevant portion of the return URI
     *
     * @return int|bool
     */
    public function process_discord($code, $uri) {
        $payload = [
            'code'=>$code,
            'grant_type'=>'authorization_code',
            'redirect_uri'=>(!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['BASE_URL'] . '/discord/' . $uri,
            'scope'=>'identify',
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

        if (isset($result['id'])) {
            $discordid = is_numeric($result['id']) ? (int) $result['id'] : 0;

            return $discordid;
        }

        return false;
    }

    /**
     * Register a successful Discord OpenID login to a Shipyard account.
     *
     * @return void|Response
     */
    public function process_registration(Request $request, Response $response) {
        $code = $request->getQueryParams()['code'];
        if ($discordid = $this->process_discord($code, 'process_registration')) {
            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = User::query()->where([['discordid', $discordid]]);
            /** @var User|null $existing_user */
            $existing_user = $query->first();

            /** @var User $auth_user */
            $auth_user = Auth::user();

            /** @var \Illuminate\Database\Eloquent\Builder $query */
            $query = User::query()->where([['ref', $auth_user->ref]]);
            /** @var User $user */
            $user = $query->first();
            if ($user == null) {
                header('location: ' . $_SERVER['BASE_URL'] . '/home');
                exit;
            }

            if ($existing_user !== null && $existing_user->id !== $user->id) {
                header('location: ' . $_SERVER['BASE_URL'] . '/profile?error=discord_already_linked');
                exit;
            }

            Log::get()->channel('registration')->info('Registering Discord ID to user.', $user->toArray());
            $auth_user->discordid = (int) $discordid;
            $user->discordid = (int) $discordid;
            $user->save();
            header('location: ' . $_SERVER['BASE_URL'] . '/profile');
            exit;
        }
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
            $query = User::query()->where([['discordid', $discordid]]);
            /** @var User $user */
            $user = $query->first();
            if ($user == null) {
                return $this->not_found_response('User');
            }

            Auth::login($user);

            header('location: ' . $_SERVER['BASE_URL'] . '/home');
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
