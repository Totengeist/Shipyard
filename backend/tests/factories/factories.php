<?php

$factory('Shipyard\Models\User', [
    'name' => $faker->name,
    'email' => $faker->unique()->safeEmail,
    'password' => password_hash('secret', PASSWORD_BCRYPT),
]);
$factory('Shipyard\Models\Role', [
    'slug' => $faker->slug,
    'label' => $faker->words(3, true),
]);
$factory('Shipyard\Models\Permission', [
    'slug' => $faker->slug,
    'label' => $faker->words(3, true),
]);

$factory('Shipyard\Models\Ship', [
    'user_id' => $faker->randomDigit(),
    'title' => $faker->words(3, true),
    'description' => $faker->paragraph(),
    'file_path' => realpath(__DIR__ . '/../assets/science-vessel.ship'),
    'downloads' => $faker->randomNumber(5, false),
]);
$factory('Shipyard\Models\Save', [
    'user_id' => $faker->randomDigit(),
    'title' => $faker->words(3, true),
    'description' => $faker->paragraph(),
    'file_path' => realpath(__DIR__ . '/../assets/Battle.space'),
    'downloads' => $faker->randomNumber(5, false),
]);
$factory('Shipyard\Models\Challenge', [
    'user_id' => $faker->randomDigit(),
    'save_id' => $faker->randomDigit(),
    'title' => $faker->words(3, true),
    'description' => $faker->paragraph(),
]);

$factory('Shipyard\Models\Tag', [
    'slug' => $faker->slug,
    'label' => $faker->words(5, true),
    'description' => $faker->paragraph(),
]);

$factory('Shipyard\Models\Release', [
    'slug' => $faker->slug,
    'label' => $faker->words(5, true),
    'description' => $faker->paragraph(),
]);

$factory('Shipyard\Models\Screenshot', [
    'description' => $faker->paragraph(),
    'primary' => false,
    'file_path' => realpath(__DIR__ . '/../assets/science-vessel.png'),
]);
