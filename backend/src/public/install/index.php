<?php

use Shipyard\User;

require __DIR__ . '/../../bootstrap.php';

echo 'Installing.<br><br>\n\n';

echo 'Running migrations.<br>\n';
require_once __DIR__ . '/../../migrations/1.php';
echo 'Inserting administrator user.<br>\n';
$admin = User::create([
    'name'       => 'administrator',
    'email'      => 'admin@tls-wiki.com',
    'password'   => password_hash('secret', PASSWORD_BCRYPT),
    'activated'  => true,
]);
$admin->assignRole('administrator');

echo 'Running seeds.<br>\n';
require_once 'seeder.php';

echo '<br>\nDone.';
