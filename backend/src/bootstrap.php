<?php

require __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;

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
if (!isset($_SERVER['STORAGE'])) {
    $_SERVER['STORAGE'] = realpath($_SERVER['APP_ROOT'] . '/public/storage') . DIRECTORY_SEPARATOR;
}

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
