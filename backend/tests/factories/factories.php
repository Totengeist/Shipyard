<?php

$factory('Shipyard\User', [
    'name' => $faker->name,
    'ref' => $faker->md5(),
    'email' => $faker->unique()->safeEmail,
    'password' => password_hash('secret', PASSWORD_BCRYPT),
]);
$factory('Shipyard\Role', [
    'slug' => $faker->slug,
    'label' => $faker->words(3, true),
]);
$factory('Shipyard\Permission', [
    'slug' => $faker->slug,
    'label' => $faker->words(3, true),
]);

$factory('Shipyard\Ship', [
    'user_id' => $faker->randomDigit(),
    'ref' => $faker->md5(),
    'title' => $faker->words(3, true),
    'description' => $faker->paragraph(),
    'file_path' => realpath('../assets/science-vessel.ship'),
    'downloads' => $faker->randomNumber(5, false),
]);
$factory('Shipyard\Save', [
    'user_id' => $faker->randomDigit(),
    'ref' => $faker->md5(),
    'title' => $faker->words(3, true),
    'description' => $faker->paragraph(),
    'file_path' => realpath('../assets/Battle.space'),
]);
$factory('Shipyard\Challenge', [
    'user_id' => $faker->randomDigit(),
    'save_id' => $faker->randomDigit(),
    'ref' => $faker->md5(),
    'title' => $faker->words(3, true),
    'description' => $faker->paragraph(),
]);

$factory('Shipyard\Tag', [
    'slug' => $faker->slug,
    'label' => $faker->words(5, true),
    'description' => $faker->paragraph(),
]);

$factory('Shipyard\Release', [
    'slug' => $faker->slug,
    'label' => $faker->words(5, true),
    'description' => $faker->paragraph(),
]);
