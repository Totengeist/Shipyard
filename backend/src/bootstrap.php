<?php

require __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;

$dotenv = Dotenv::createMutable(realpath(dirname(__DIR__)));
if (getenv('APP_ENV') == 'development') {
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
