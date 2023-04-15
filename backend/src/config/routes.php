<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shipyard\Middleware\SessionMiddleware;
use Slim\Routing\RouteCollectorProxy;

$app->group($_ENV['BASE_URL'] . '/api/v1', function (RouteCollectorProxy $group) {
    $group->post('/register', 'Shipyard\Controllers\RegisterController:register');
    $group->post('/activate/{token}', 'Shipyard\Controllers\RegisterController:activate');
    $group->post('/login', 'Shipyard\Controllers\LoginController:login');
    $group->get('/logout', 'Shipyard\Controllers\LoginController:logout');
    $group->get('/version', function (Request $request, Response $response, $args) {
        $raw_version = Capsule::select('select `default`,`value` from `meta` where `name` = ?', ['schema_version'])[0];
        $version = (empty($raw_version->value) ? $raw_version->default : $raw_version->value);
        $payload = json_encode(['app' => $_ENV['APP_TITLE'], 'version' => 'alpha', 'schema' => $version]);
        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    });

    $group->group('', function (RouteCollectorProxy $group) {
        $group->get('/ship', 'Shipyard\Controllers\ShipController:index');
        $group->get('/ship/{ref}', 'Shipyard\Controllers\ShipController:show');

        $group->get('/save', 'Shipyard\Controllers\SaveController:index');
        $group->get('/save/{ref}', 'Shipyard\Controllers\SaveController:show');

        $group->get('/challenge', 'Shipyard\Controllers\ChallengeController:index');
        $group->get('/challenge/{ref}', 'Shipyard\Controllers\ChallengeController:show');

        $group->get('/tag', 'Shipyard\Controllers\TagController:index');
        $group->get('/tag/{slug}', 'Shipyard\Controllers\TagController:show');

        $group->get('/release', 'Shipyard\Controllers\ReleaseController:index');
        $group->get('/release/{slug}', 'Shipyard\Controllers\ReleaseController:show');
    });

    $group->group('', function (RouteCollectorProxy $group) {
        $group->get('/me', 'Shipyard\Controllers\LoginController:me');

        $group->post('/user/{user_id}', 'Shipyard\Controllers\RegisterController:update');
        $group->delete('/user/{userid}', 'Shipyard\Controllers\RegisterController:destroy');

        $group->get('/permission', 'Shipyard\Controllers\PermissionController:index');
        $group->post('/permission', 'Shipyard\Controllers\PermissionController:store');
        $group->get('/permission/{slug}', 'Shipyard\Controllers\PermissionController:show');
        $group->post('/permission/{slug}', 'Shipyard\Controllers\PermissionController:update');
        $group->delete('/permission/{slug}', 'Shipyard\Controllers\PermissionController:destroy');

        $group->get('/role', 'Shipyard\Controllers\RoleController:index');
        $group->post('/role', 'Shipyard\Controllers\RoleController:store');
        $group->get('/role/{slug}', 'Shipyard\Controllers\RoleController:show');
        $group->post('/role/{slug}', 'Shipyard\Controllers\RoleController:update');
        $group->delete('/role/{slug}', 'Shipyard\Controllers\RoleController:destroy');

        $group->post('/ship', 'Shipyard\Controllers\ShipController:store');
        $group->post('/ship/{ref}', 'Shipyard\Controllers\ShipController:update');
        $group->delete('/ship/{ref}', 'Shipyard\Controllers\ShipController:destroy');

        $group->post('/save', 'Shipyard\Controllers\SaveController:store');
        $group->post('/save/{ref}', 'Shipyard\Controllers\SaveController:update');
        $group->delete('/save/{ref}', 'Shipyard\Controllers\SaveController:destroy');

        $group->post('/challenge', 'Shipyard\Controllers\ChallengeController:store');
        $group->post('/challenge/{ref}', 'Shipyard\Controllers\ChallengeController:update');
        $group->delete('/challenge/{ref}', 'Shipyard\Controllers\ChallengeController:destroy');

        $group->post('/tag', 'Shipyard\Controllers\TagController:store');
        $group->post('/tag/{slug}', 'Shipyard\Controllers\TagController:update');
        $group->delete('/tag/{slug}', 'Shipyard\Controllers\TagController:destroy');

        $group->post('/release', 'Shipyard\Controllers\ReleaseController:store');
        $group->post('/release/{slug}', 'Shipyard\Controllers\ReleaseController:update');
        $group->delete('/release/{slug}', 'Shipyard\Controllers\ReleaseController:destroy');
    })->add(SessionMiddleware::class);
    $group->get('/{path:.*}', function ($request, $response, array $args) {
        return $response
             ->withStatus(404);
    });
});
$app->get('/{path:.*}', function ($request, $response, array $args) {
    ob_start();
    require __DIR__ . '/../public/index.html';
    $output = ob_get_contents();
    ob_end_clean();
    $response->getBody()->write($output);

    return $response;
});
