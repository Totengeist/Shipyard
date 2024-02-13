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

$factory('Shipyard\Models\Ship', function ($faker) {
    $file = Shipyard\FileManager::moveUploadedFile(Tests\APITestCase::createSampleUpload());

    return [
        'user_id' => $faker->randomDigit(),
        'title' => $faker->words(3, true),
        'description' => $faker->paragraph(),
        'file_id' => $file->id,
        'downloads' => $faker->randomNumber(5, false),
    ];
});
$factory('Shipyard\Models\Save', function ($faker) {
    $file = Shipyard\FileManager::moveUploadedFile(Tests\APITestCase::createSampleUpload('Battle.space'));

    return [
    'user_id' => $faker->randomDigit(),
    'title' => $faker->words(3, true),
    'description' => $faker->paragraph(),
    'file_id' => $file->id,
    'downloads' => $faker->randomNumber(5, false),
    ];
});
$factory('Shipyard\Models\Modification', [
    $file = Shipyard\FileManager::moveUploadedFile(Tests\APITestCase::createSampleUpload('Battle.space'));

    'user_id' => $faker->randomDigit(),
    'title' => $faker->words(3, true),
    'description' => $faker->paragraph(),
    'file_id' => $file->id,
    'downloads' => $faker->randomNumber(5, false),
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

$factory('Shipyard\Models\Screenshot', function ($faker) {
    $file = Shipyard\FileManager::moveUploadedFile(Tests\APITestCase::createSampleUpload('science-vessel.png'));

    return [
        'description' => $faker->paragraph(),
        'file_id' => $file->id,
    ];
});
