<?php

use Shipyard\Ship;
use Shipyard\User;

$admin = User::firstOrCreate(['email' => 'admin@tls-wiki.com']);
Ship::firstOrCreate(['user_id' => $admin->id, 'title' => 'Ship 1', 'description' => 'An example ship.']);

if (file_exists('custom_seeds.php')) {
    include 'custom_seeds.php';
}
