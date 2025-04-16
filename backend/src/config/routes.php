<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shipyard\Middleware\LogMiddleware;
use Shipyard\Middleware\SessionMiddleware;
use Shipyard\SitemapGenerator;
use Shipyard\AtomGenerator;
use Shipyard\Version;
use Slim\Routing\RouteCollectorProxy;

$app->group($_SERVER['BASE_URL'] . '/steam', function (RouteCollectorProxy $group) {
    $group->get('/register', 'Shipyard\Controllers\SteamController:register');
    $group->get('/login', 'Shipyard\Controllers\SteamController:login');
    $group->get('/process_registration', 'Shipyard\Controllers\SteamController:processRegistration');
    $group->get('/process_login', 'Shipyard\Controllers\SteamController:processLogin');
    $group->post('/remove', 'Shipyard\Controllers\SteamController:remove');
});
$app->group($_SERVER['BASE_URL'] . '/discord', function (RouteCollectorProxy $group) {
    $group->get('/login', 'Shipyard\Controllers\DiscordController:login');
    $group->get('/process_login', 'Shipyard\Controllers\DiscordController:processLogin');
    $group->post('/remove', 'Shipyard\Controllers\DiscordController:remove');
});
$app->get($_SERVER['BASE_URL'] . '/activate/{token}', 'Shipyard\Controllers\RegisterController:activate_redirect');
$app->group($_SERVER['BASE_URL'] . '/api/v1', function (RouteCollectorProxy $group) {
    $group->post('/register', 'Shipyard\Controllers\RegisterController:register');
    $group->get('/activate/{token}', 'Shipyard\Controllers\RegisterController:activate');
    $group->post('/login', 'Shipyard\Controllers\LoginController:login');
    $group->post('/logout', 'Shipyard\Controllers\LoginController:logout');
    $group->post('/password_reset', 'Shipyard\Controllers\RegisterController:request_reset');
    $group->post('/password_reset/{token}', 'Shipyard\Controllers\RegisterController:reset_password');
    $group->get('/version', function (Request $request, Response $response) {
        $payload = (string) json_encode(['app' => $_SERVER['APP_TITLE'], 'version' => Version::getVersion(), 'commit' => Version::getCommit()]);
        $response->getBody()->write($payload);

        return $response
          ->withHeader('Content-Type', 'application/json');
    })->add(LogMiddleware::class);

    $group->group('', function (RouteCollectorProxy $group) {
        $group->get('/ship', 'Shipyard\Controllers\ShipController:index');
        $group->post('/ship', 'Shipyard\Controllers\ShipController:store');
        $group->get('/ship/{ref}', 'Shipyard\Controllers\ShipController:show');
        $group->get('/ship/{ref}/download', 'Shipyard\Controllers\ShipController:download');
        $group->get('/ship/{ref}/screenshots', 'Shipyard\Controllers\ShipController:index_screenshots');

        $group->get('/save', 'Shipyard\Controllers\SaveController:index');
        $group->post('/save', 'Shipyard\Controllers\SaveController:store');
        $group->get('/save/{ref}', 'Shipyard\Controllers\SaveController:show');
        $group->get('/save/{ref}/download', 'Shipyard\Controllers\SaveController:download');
        $group->get('/save/{ref}/screenshots', 'Shipyard\Controllers\SaveController:index_screenshots');

        $group->get('/modification', 'Shipyard\Controllers\ModificationController:index');
        $group->post('/modification', 'Shipyard\Controllers\ModificationController:store');
        $group->get('/modification/{ref}', 'Shipyard\Controllers\ModificationController:show');
        $group->get('/modification/{ref}/download', 'Shipyard\Controllers\ModificationController:download');
        $group->get('/modification/{ref}/screenshots', 'Shipyard\Controllers\ModificationController:index_screenshots');

        $group->get('/tag', 'Shipyard\Controllers\TagController:index');
        $group->get('/tag/{slug}', 'Shipyard\Controllers\TagController:show');

        $group->get('/release', 'Shipyard\Controllers\ReleaseController:index');
        $group->get('/release/{slug}', 'Shipyard\Controllers\ReleaseController:show');

        $group->get('/screenshot/{ref}', 'Shipyard\Controllers\ScreenshotController:show');
        $group->get('/screenshot/{ref}/download', 'Shipyard\Controllers\ScreenshotController:download');
        $group->get('/screenshot/{ref}/preview', 'Shipyard\Controllers\ScreenshotController:preview');
        $group->get('/screenshot/{ref}/preview/{size}', 'Shipyard\Controllers\ScreenshotController:preview');

        $group->get('/user/{ref}', 'Shipyard\Controllers\RegisterController:show');

        $group->get('/search/tag/{query}', 'Shipyard\Controllers\TagController:search');
    })->add(LogMiddleware::class);

    $group->group('', function (RouteCollectorProxy $group) {
        $group->get('/me', 'Shipyard\Controllers\LoginController:me');

        $group->post('/user/{user_ref}', 'Shipyard\Controllers\RegisterController:update');
        $group->delete('/user/{user_ref}', 'Shipyard\Controllers\RegisterController:destroy');

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

        $group->post('/ship/{ref}', 'Shipyard\Controllers\ShipController:update');
        $group->post('/ship/{ref}/upgrade', 'Shipyard\Controllers\ShipController:upgrade');
        $group->post('/ship/{ref}/screenshots', 'Shipyard\Controllers\ShipController:store_screenshots');
        $group->delete('/ship/{ref}', 'Shipyard\Controllers\ShipController:destroy');

        $group->post('/save/{ref}', 'Shipyard\Controllers\SaveController:update');
        $group->post('/save/{ref}/upgrade', 'Shipyard\Controllers\SaveController:upgrade');
        $group->post('/save/{ref}/screenshots', 'Shipyard\Controllers\SaveController:store_screenshots');
        $group->delete('/save/{ref}', 'Shipyard\Controllers\SaveController:destroy');

        $group->post('/modification/{ref}', 'Shipyard\Controllers\ModificationController:update');
        $group->post('/modification/{ref}/upgrade', 'Shipyard\Controllers\ModificationController:upgrade');
        $group->post('/modification/{ref}/screenshots', 'Shipyard\Controllers\ModificationController:store_screenshots');
        $group->delete('/modification/{ref}', 'Shipyard\Controllers\ModificationController:destroy');

        $group->post('/tag', 'Shipyard\Controllers\TagController:store');
        $group->post('/tag/{slug}', 'Shipyard\Controllers\TagController:update');
        $group->delete('/tag/{slug}', 'Shipyard\Controllers\TagController:destroy');

        $group->post('/release', 'Shipyard\Controllers\ReleaseController:store');
        $group->post('/release/{slug}', 'Shipyard\Controllers\ReleaseController:update');
        $group->delete('/release/{slug}', 'Shipyard\Controllers\ReleaseController:destroy');

        $group->post('/screenshot/{ref}', 'Shipyard\Controllers\ScreenshotController:update');
        $group->delete('/screenshot/{ref}', 'Shipyard\Controllers\ScreenshotController:destroy');

        $group->get('/steam/login', 'Shipyard\SteamHelper:login()');
    })->add(LogMiddleware::class)->add(SessionMiddleware::class);
    $group->get('/{path:.*}', function ($request, $response) {
        return $response
             ->withStatus(404);
    })->add(LogMiddleware::class);
})->add(LogMiddleware::class);
// Item stubs.
$app->group($_SERVER['BASE_URL'], function (RouteCollectorProxy $group) {
    $group->get('/ship/{ref}', 'Shipyard\Controllers\ShipController:show_stub');
    $group->get('/save/{ref}', 'Shipyard\Controllers\SaveController:show_stub');
    $group->get('/modification/{ref}', 'Shipyard\Controllers\ModificationController:show_stub');
});
$app->get($_SERVER['BASE_URL'] . '/sitemap.xml', function ($request, $response) {
    $response->getBody()->write(SitemapGenerator::generate());

    return $response
        ->withHeader('Content-Type', 'application/xml');
});
$app->get($_SERVER['BASE_URL'] . '/feed', function ($request, $response) {
    $response->getBody()->write(AtomGenerator::generate());

    return $response
        ->withHeader('Content-Type', 'application/atom+xml');
});
$app->get('/{path:.*}', function ($request, $response) {
    ob_start();
    require __DIR__ . '/../public/index.html';
    $output = ob_get_contents();
    ob_end_clean();
    $response->getBody()->write($output);

    return $response;
});
