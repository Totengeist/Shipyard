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
    'file_path' => realpath('../testPaper.pdf'),
    'downloads' => $faker->randomNumber(5, false),
]);
$factory('Shipyard\Save', [
    'user_id' => $faker->randomDigit(),
    'ref' => $faker->md5(),
    'title' => $faker->words(3, true),
    'description' => $faker->paragraph(),
    'file_path' => realpath('../testPaper.pdf'),
]);
$factory('Shipyard\Challenge', [
    'user_id' => $faker->randomDigit(),
    'save_id' => $faker->randomDigit(),
    'ref' => $faker->md5(),
    'title' => $faker->words(3, true),
    'description' => $faker->paragraph(),
]);
$factory('Shipyard\User', [
    'name' => $faker->name,
    'email' => $faker->email(),
    'password' => $faker->md5(),
]);
