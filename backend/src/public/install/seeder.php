<?php

use Shipyard\Models\Release;
use Shipyard\Models\User;

echo "Inserting administrator user.<br>\n";
/** @var User $system */
$system = User::query()->create([
    'name'       => 'system',
    'email'      => 'system',
    'password'   => password_hash('secret', PASSWORD_BCRYPT),
    'activated'  => true,
]);
$system->ref = 'system';
$system->save();

/** @var User $admin */
$admin = User::query()->create([
    'name'       => 'administrator',
    'email'      => 'shipyard@example.com',
    'password'   => password_hash('secret', PASSWORD_BCRYPT),
    'activated'  => true,
]);
$admin->assignRole('administrator');

echo "Inserting releases.<br>\n";
Release::query()->firstOrCreate(['label' => 'Update1.rc2']);
Release::query()->firstOrCreate(['label' => 'Update2.rc1']);
Release::query()->firstOrCreate(['label' => 'Update2.rc3']);
Release::query()->firstOrCreate(['label' => 'DEMO1']);
Release::query()->firstOrCreate(['label' => 'DEMO2B']);
Release::query()->firstOrCreate(['label' => 'DEMO3A']);
Release::query()->firstOrCreate(['label' => 'Alpha1A']);
Release::query()->firstOrCreate(['label' => 'Alpha1C']);
Release::query()->firstOrCreate(['label' => 'Alpha1D']);
Release::query()->firstOrCreate(['label' => 'Alpha2D']);

if (file_exists(__DIR__ . '/custom_seeds.php')) {
    include_once __DIR__ . '/custom_seeds.php';
}
