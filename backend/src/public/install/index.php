<?php

require_once __DIR__ . '/../../bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

try {
    $schema = Capsule::table('meta')->select(['name', 'default', 'value'])->where('name', '=', 'schema_version')->first();
} catch (PDOException $e) {
    $schema = null;
}

if ($schema == null) {
    echo "Installing.<br><br>\n\n";

    echo "Running migrations.<br>\n";
    require_once __DIR__ . '/../../migrations/1.php';

    echo "Running seeds.<br>\n";
    require_once 'seeder.php';

    echo "<br>\nDone.";
} else {
    echo "Checking for available updates.<br><br>\n\n";
    $upgrades = 0;
    $version = $schema->value; // @phpstan-ignore property.notFound
    if ($version == null) {
        $version = intval($schema->default); // @phpstan-ignore property.notFound
    }

    $version++;
    while (file_exists(__DIR__ . "/../../migrations/{$version}.php")) {
        echo "Updating to schema version {$version}<br>\n";
        require_once __DIR__ . "/../../migrations/{$version}.php";
        $version++;
        $upgrades++;

        echo "<br>\nDone.";
    }
    if ($upgrades == 0) {
        echo 'Already up to date.';
    }
}
