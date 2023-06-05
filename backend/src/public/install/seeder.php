<?php

use Shipyard\Models\Release;
use Shipyard\Models\User;

echo 'Inserting administrator user.<br>\n';
$admin = User::create([
    'name'       => 'administrator',
    'email'      => 'admin@tls-wiki.com',
    'password'   => password_hash('secret', PASSWORD_BCRYPT),
    'activated'  => true,
]);
$admin->assignRole('administrator');
echo 'Inserting releases.<br>\n';
Release::firstOrCreate(['label' => 'Update1.rc2']);
Release::firstOrCreate(['label' => 'Update2.rc1']);
Release::firstOrCreate(['label' => 'Update2.rc3']);
Release::firstOrCreate(['label' => 'DEMO1']);
Release::firstOrCreate(['label' => 'DEMO2B']);
Release::firstOrCreate(['label' => 'DEMO3A']);
Release::firstOrCreate(['label' => 'Alpha1A']);
Release::firstOrCreate(['label' => 'Alpha1C']);
Release::firstOrCreate(['label' => 'Alpha1D']);
Release::firstOrCreate(['label' => 'Alpha2D']);

if (file_exists(__DIR__ . '/custom_seeds.php')) {
    include __DIR__ . '/custom_seeds.php';
}
