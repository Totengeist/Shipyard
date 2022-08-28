<?php

use Shipyard\User;

require __DIR__ . '/../../bootstrap.php';

echo 'Installing.<br><br>';

echo 'Running migrations.<br>';
require_once '../../migrations/1.php';
echo 'Inserting administrator user.<br>';
$admin = User::create([
    'name'       => 'administrator',
    'email'      => 'admin@tls-wiki.com',
    'password'   => password_hash('secret', PASSWORD_BCRYPT),
    'activated'  => true,
]);
$admin->assignRole('administrator');

echo 'Running seeds.<br>';
require_once 'seeder.php';

echo '<br>Done.';
