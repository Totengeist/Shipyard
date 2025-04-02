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
        'user_id' => 1,
        'title' => $faker->words(3, true),
        'description' => $faker->paragraph(),
        'file_id' => $file->id,
        'downloads' => $faker->randomNumber(5, false),
    ];
});
$factory('Shipyard\Models\Save', function ($faker) {
    $file = Shipyard\FileManager::moveUploadedFile(Tests\APITestCase::createSampleUpload('Battle.space'));

    return [
        'user_id' => 1,
        'title' => $faker->words(3, true),
        'description' => $faker->paragraph(),
        'file_id' => $file->id,
        'downloads' => $faker->randomNumber(5, false),
    ];
});
$factory('Shipyard\Models\Modification', function ($faker) {
    $file = Shipyard\FileManager::moveUploadedFile(Tests\APITestCase::createSampleUpload('Battle.space'));

    return [
        'user_id' => 1,
        'title' => $faker->words(3, true),
        'description' => $faker->paragraph(),
        'file_id' => $file->id,
        'downloads' => $faker->randomNumber(5, false),
    ];
});

$factory('Shipyard\Models\File', function ($faker) {
    return [
        'filename' => $faker->regexify('[a-z0-4]{20}'),
        'media_type' => $faker->randomElement(['application/tls-save+introversion', 'application/tls-ship+introversion', 'image/png; charset=binary']),
        'extension' => $faker->randomElement(['space', 'ship', 'png']),
        'filepath' => $faker->regexify('[a-z0-4]{20}'),
        'compressed' => true
    ];
});

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

$factory('Shipyard\Models\Thumbnail', function ($faker) {
    $file = Shipyard\FileManager::moveUploadedFile(Tests\APITestCase::createSampleUpload('science-vessel.png'));

    return [
        'size' => 800,
        'file_id' => $file->id
    ];
});
