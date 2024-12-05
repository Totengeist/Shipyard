<?php

require __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;
use Shipyard\EnvironmentManager;

$dotenv = Dotenv::createMutable((string) realpath(dirname(__DIR__)));
if (!getenv('DB_HOST')) {
    $dotenv->load();
}
$dotenv->required([
    'APP_TITLE',
    'BASE_URL',
    'DB_HOST',
    'DB_PORT',
    'DB_DATABASE',
    'DB_USERNAME'
]);
$_SERVER['APP_ROOT'] = realpath(__DIR__);
$_SERVER['BASE_URL_ABS'] = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['BASE_URL'];
$_SERVER['STORAGE'] = EnvironmentManager::storage();
EnvironmentManager::initializeLogger();
EnvironmentManager::initializeNotifier();

$capsule = new Capsule();
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => $_SERVER['DB_HOST'],
    'database' => $_SERVER['DB_DATABASE'],
    'username' => $_SERVER['DB_USERNAME'],
    'password' => isset($_SERVER['DB_PASSWORD']) ? $_SERVER['DB_PASSWORD'] : '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();
