<?php

require_once __DIR__ . '/../../bootstrap.php';

echo "Installing.<br><br>\n\n";

echo "Running migrations.<br>\n";
require_once __DIR__ . '/../../migrations/1.php';

echo "Running seeds.<br>\n";
require_once 'seeder.php';

echo "<br>\nDone.";
